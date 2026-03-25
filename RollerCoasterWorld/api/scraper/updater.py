#!/usr/bin/env python3
"""
RCDB Updater - Supabase (PostgreSQL) edition
Escanea RCDB y compara con la base de datos.
Detecta coasters nuevas y cualquier cambio de dato en las existentes.
"""

import psycopg2
import psycopg2.extras
import requests
from bs4 import BeautifulSoup
import re
import time
import sys
import os
import json
from datetime import datetime
from typing import List, Dict, Any, Optional

CACHE_FILE = os.path.join(os.path.dirname(__file__), 'updater_cache.json')

# ──────────────────────────────────────────────────────────────────────────────
# CONFIGURACIÓN
# ──────────────────────────────────────────────────────────────────────────────

def load_env() -> Dict[str, str]:
    """Lee el .env que está en RollerCoasterWorld/.env"""
    env_path = os.path.join(os.path.dirname(__file__), '..', '..', '.env')
    config: Dict[str, str] = {}
    try:
        with open(env_path, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith('#') or '=' not in line:
                    continue
                # Ignorar líneas JS del .env (const, etc.)
                if line.startswith('const') or line.startswith('//'):
                    continue
                key, value = line.split('=', 1)
                config[key.strip()] = value.strip().strip('"\'')
    except FileNotFoundError:
        print(f"[ERROR] No se encontró el .env en: {env_path}")
        sys.exit(1)
    return config


_env = load_env()

DB_DSN = (
    f"host={_env.get('DB_HOST', 'localhost')} "
    f"port={_env.get('DB_PORT', '5432')} "
    f"dbname={_env.get('DB_NAME', 'postgres')} "
    f"user={_env.get('DB_USER', 'postgres')} "
    f"password={_env.get('DB_PASS', '')}"
)


# ──────────────────────────────────────────────────────────────────────────────
# UPDATER
# ──────────────────────────────────────────────────────────────────────────────

class RCDBUpdater:
    """
    Escanea toda la web de RCDB y compara con Supabase (PostgreSQL).
    Detecta coasters nuevas y cualquier cambio de dato en las existentes.
    """

    # Campos que se comparan y el nombre legible para mostrar al usuario
    FIELD_MAP = [
        # (campo_scraped,       campo_bd,               label_display)
        ('name',               'coaster_name',          'Nombre'),
        ('status',             'coaster_status',        'Estado'),
        ('make',               'coaster_manufacter',    'Fabricante'),
        ('model',              'coaster_model',         'Modelo'),
        ('year',               'opening_year',          'Año apertura'),
        ('inversions',         'inversions',            'Inversiones'),
        ('main_image_url',     'imagen_url',            'Imagen URL'),
        # Numéricos con tolerancia (se comparan como float)
        ('height_m',           'height',                'Altura (m)'),
        ('speed_kmh',          'speed',                 'Velocidad (km/h)'),
        ('length_m',           'coaster_length',        'Longitud (m)'),
    ]

    # Campos numéricos — se comparan con tolerancia para evitar falsos positivos
    NUMERIC_FIELDS = {'height_m', 'speed_kmh', 'length_m'}
    NUMERIC_TOLERANCE = 0.5   # diferencia mínima para considerar cambio

    def __init__(self):
        self.base_url = "https://rcdb.com"
        self.headers = {
            'User-Agent': (
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                'AppleWebKit/537.36 (KHTML, like Gecko) '
                'Chrome/121.0.0.0 Safari/537.36'
            ),
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
        }
        self.session = requests.Session()
        self.session.headers.update(self.headers)

        self.scanned      = 0
        self.not_found    = 0
        self.new_coasters: List[Dict]    = []
        self.changed_coasters: List[Dict] = []

        self.db:     Optional[psycopg2.extensions.connection] = None
        self.cursor: Optional[psycopg2.extensions.cursor]     = None

    # ──────────────────────────────────────────────────────────────────────────
    # BASE DE DATOS
    # ──────────────────────────────────────────────────────────────────────────

    def connect_db(self) -> bool:
        try:
            self.db = psycopg2.connect(DB_DSN)
            self.db.autocommit = False
            self.cursor = self.db.cursor(cursor_factory=psycopg2.extras.RealDictCursor)
            print("✅ Conectado a Supabase (PostgreSQL)")
            return True
        except psycopg2.Error as e:
            print(f"[ERROR] Conexión a Supabase: {e}")
            return False

    def _reconnect_db(self):
        """Reconecta si la conexión cayó (p.ej. timeout tras un escaneo largo)."""
        try:
            if self.db and not self.db.closed:
                # Ping real contra el servidor
                self.cursor.execute("SELECT 1")
                return   # sigue viva
        except Exception:
            pass   # cayó — reconectamos abajo

        print("   🔄 Reconectando a Supabase...")
        try:
            if self.cursor:
                self.cursor.close()
            if self.db:
                self.db.close()
        except Exception:
            pass
        self.db = psycopg2.connect(DB_DSN)
        self.db.autocommit = False
        self.cursor = self.db.cursor(cursor_factory=psycopg2.extras.RealDictCursor)
        print("   ✅ Reconectado")

    def close_db(self):
        if self.cursor:
            self.cursor.close()
        if self.db:
            self.db.close()

    def get_existing_coasters(self) -> Dict[int, Dict]:
        """Devuelve {rcdb_id: fila_completa} de todos los coasters con rcdb_id."""
        self.cursor.execute("""
            SELECT c.id, c.rcdb_id, c.coaster_name, c.park_id,
                   c.coaster_manufacter, c.coaster_model, c.coaster_status,
                   c.imagen_url,
                   CAST(c.height AS FLOAT)          AS height,
                   CAST(c.speed AS FLOAT)           AS speed,
                   CAST(c.coaster_length AS FLOAT)  AS coaster_length,
                   c.inversions, c.opening_year,
                   p.park_name
            FROM coasters c
            LEFT JOIN parks p ON c.park_id = p.id
            WHERE c.rcdb_id IS NOT NULL
        """)
        rows = self.cursor.fetchall()
        return {int(row['rcdb_id']): dict(row) for row in rows}

    def get_or_create_park(self, park_name: str, city: Optional[str], country: Optional[str]) -> int:
        """Busca el parque por nombre y localización; si no existe, lo inserta y devuelve su id."""
        location = city or ''
        park_country = country or ''
        self.cursor.execute(
            "SELECT id FROM parks WHERE park_name = %s AND park_location = %s LIMIT 1",
            (park_name, location)
        )
        row = self.cursor.fetchone()
        if row:
            return int(row['id'])

        self.cursor.execute(
            """INSERT INTO parks (park_name, park_location, park_country,
                                  num_coasters, operating_coasters, stars)
               VALUES (%s, %s, %s, 0, 0, 0)
               ON CONFLICT (park_name, park_location) DO NOTHING
               RETURNING id""",
            (park_name, location, park_country)
        )
        result = self.cursor.fetchone()
        if result:
            self.db.commit()
            print(f"   🏞️  Parque nuevo: {park_name} (ID {result['id']})")
            return int(result['id'])

        # Si llegamos aquí fue un race-condition — buscar de nuevo
        self.cursor.execute("SELECT id FROM parks WHERE park_name = %s AND park_location = %s LIMIT 1", (park_name, location))
        row = self.cursor.fetchone()
        self.db.commit()
        return int(row['id'])

    # ──────────────────────────────────────────────────────────────────────────
    # SCRAPING (misma lógica que scrapper.py)
    # ──────────────────────────────────────────────────────────────────────────

    def clean_text(self, text: str) -> str:
        if not text:
            return ""
        return re.sub(r'\s+', ' ', text).strip()

    def extract_number(self, text: str) -> Optional[float]:
        if not text:
            return None
        text = text.replace(',', '')
        match = re.search(r'([\d]+(?:\.\d+)?)', text)
        return float(match.group(1)) if match else None

    def get_text_safe(self, element, stop_tags: list) -> str:
        if not element:
            return ""
        parts = []
        for child in element.contents:
            if child.name in stop_tags:
                break
            if isinstance(child, str):
                parts.append(child)
            elif child.name == 'br':
                parts.append(' ')
            else:
                parts.append(child.get_text())
        return self.clean_text(" ".join(parts))

    def parse_stat_table(self, soup: BeautifulSoup) -> Dict[str, Any]:
        stats: Dict[str, Any] = {}
        for table in soup.find_all('table', class_='stat-tbl'):
            for row in table.find_all('tr'):
                th = row.find('th')
                td = row.find('td')
                if not th or not td:
                    continue

                stat_name      = self.get_text_safe(th, ['td'])
                cell_full_text = self.get_text_safe(td, ['tr', 'th'])

                # Buscar span.float para el valor numérico
                value_span = None
                for child in td.contents:
                    if child.name in ['tr', 'th']:
                        break
                    if hasattr(child, 'attrs') and 'float' in child.attrs.get('class', []):
                        value_span = child
                        break

                value_text = self.clean_text(value_span.text).replace(',', '.') if value_span else cell_full_text
                num = self.extract_number(value_text)

                # Extraer el número que aparece JUNTO a una unidad específica
                # (evita confundir ft con m cuando RCDB muestra ambas en la misma celda)
                def extract_near_unit(text: str, unit: str) -> Optional[float]:
                    import re as _re
                    pattern = rf'([\d]+(?:[.,]\d+)?)\s*{_re.escape(unit)}'
                    m = _re.search(pattern, text)
                    return float(m.group(1).replace(',', '.')) if m else None

                if 'Length' in stat_name:
                    if ' m' in cell_full_text and 'mph' not in cell_full_text:
                        val_m = extract_near_unit(cell_full_text, 'm')
                        if val_m:
                            stats['length_m'] = val_m
                    elif ' ft' in cell_full_text and num:
                        stats['length_m'] = round(num * 0.3048, 1)

                elif 'Height' in stat_name:
                    if ' m' in cell_full_text:
                        val_m = extract_near_unit(cell_full_text, 'm')
                        if val_m:
                            stats['height_m'] = val_m
                    elif ' ft' in cell_full_text and num:
                        stats['height_m'] = round(num * 0.3048, 1)

                elif 'Speed' in stat_name:
                    if 'km/h' in cell_full_text:
                        val_kmh = extract_near_unit(cell_full_text, 'km/h')
                        if val_kmh:
                            stats['speed_kmh'] = val_kmh
                    elif 'mph' in cell_full_text and num:
                        stats['speed_kmh'] = round(num * 1.60934, 1)

                elif 'Inversions' in stat_name:
                    if num is not None:
                        stats['inversions'] = int(num)

        return stats

    def scrape_coaster(self, rcdb_id: int) -> Optional[Dict[str, Any]]:
        """Descarga y parsea una coaster de RCDB. Devuelve None si no existe."""
        url = f"{self.base_url}/{rcdb_id}.htm"
        try:
            r = self.session.get(url, timeout=15)
            if r.status_code == 404:
                return None
            r.raise_for_status()
        except Exception:
            return None

        try:
            soup = BeautifulSoup(r.text, 'html.parser')
            coaster: Dict[str, Any] = {'rcdb_id': rcdb_id, 'rcdb_url': url}

            # Imagen
            opf = soup.find('a', id='opfAnchor')
            if opf and opf.get('data-url'):
                coaster['main_image_url'] = f"{self.base_url}{opf.get('data-url')}"
            else:
                meta = soup.find('meta', property='og:image')
                if meta and meta.get('content'):
                    coaster['main_image_url'] = meta.get('content')

            # Nombre
            h1 = soup.find('h1')
            if h1:
                coaster['name'] = h1.text.strip()

            # Parque y ubicación
            h1_parent = h1.parent if h1 else None
            if h1_parent:
                park_link = h1_parent.find('a', href=re.compile(r'/\d+\.htm'))
                if park_link:
                    coaster['park'] = park_link.text.strip()

                location_links = h1_parent.find_all('a', href=re.compile(r'/location\.htm'))
                locs = [a.text.strip() for a in location_links]
                if len(locs) >= 1:
                    coaster['city'] = locs[0]
                if len(locs) >= 4:
                    coaster['country'] = locs[3]
                elif len(locs) >= 1:
                    coaster['country'] = locs[-1]

            # Estado y año de apertura
            feature_div = soup.find('div', id='feature')
            if feature_div:
                status_p = feature_div.find('p')
                if status_p:
                    st = status_p.get_text()
                    if 'Operating' in st:
                        coaster['status'] = 'Operating'
                    elif 'Removed' in st:
                        coaster['status'] = 'Closed'
                    elif 'In Storage' in st:
                        coaster['status'] = 'In Storage'
                    elif 'Under Construction' in st:
                        coaster['status'] = 'Construction'
                    elif 'SBNO' in st or 'Standing But Not Operating' in st:
                        coaster['status'] = 'SBNO'
                    elif 'Relocated' in st:
                        coaster['status'] = 'Relocated'
                    else:
                        sl = status_p.find('a', href=re.compile(r'/g\.htm\?id=\d+'))
                        if sl:
                            coaster['status'] = sl.text.strip()

                    time_tag = status_p.find('time')
                    if time_tag and time_tag.get('datetime'):
                        ym = re.search(r'^(\d{4})', time_tag.get('datetime'))
                        if ym:
                            coaster['year'] = int(ym.group(1))

            # Make y Model
            text_content = soup.get_text()
            make_m = re.search(r'Make:\s*([^\n]+?)(?:Model:|Pictures|Videos|$)', text_content, re.DOTALL)
            if make_m:
                coaster['make'] = ' '.join(make_m.group(1).strip().split())

            model_m = re.search(r'Model:\s*([^\n]+?)(?:Pictures|Videos|Tracks|$)', text_content, re.DOTALL)
            if model_m:
                mt = re.sub(r'All Models\s*/\s*', '', model_m.group(1).strip())
                mt = ' '.join(mt.split())
                mt = re.sub(r'(Pictures|Videos|Maps|Tracks).*$', '', mt).strip()
                coaster['model'] = mt

            # Stats numéricas
            coaster.update(self.parse_stat_table(soup))
            return coaster

        except Exception:
            return None

    # ──────────────────────────────────────────────────────────────────────────
    # COMPARACIÓN — detecta CUALQUIER cambio
    # ──────────────────────────────────────────────────────────────────────────

    def _vals_equal(self, scraped_val, bd_val, field_scraped: str) -> bool:
        """Compara dos valores teniendo en cuenta tipo y tolerancia numérica."""
        if scraped_val is None or bd_val is None:
            return True   # Si el scraper no trajo el dato, no lo marcamos como cambio

        if field_scraped in self.NUMERIC_FIELDS:
            try:
                return abs(float(scraped_val) - float(bd_val)) < self.NUMERIC_TOLERANCE
            except (TypeError, ValueError):
                return str(scraped_val) == str(bd_val)

        # Para inversions y opening_year comparar como int
        if field_scraped in ('inversions', 'year'):
            try:
                return int(scraped_val) == int(bd_val)
            except (TypeError, ValueError):
                pass

        return str(scraped_val).strip() == str(bd_val).strip()

    def compare_coaster(self, scraped: Dict, existing: Dict) -> List[str]:
        """Devuelve lista de cadenas describiendo cada cambio detectado."""
        changes = []
        for field_s, field_bd, label in self.FIELD_MAP:
            new_val = scraped.get(field_s)
            old_val = existing.get(field_bd)
            if not self._vals_equal(new_val, old_val, field_s):
                changes.append(f"{label}: {repr(old_val)} → {repr(new_val)}")
        return changes

    # ──────────────────────────────────────────────────────────────────────────
    # ESCRITURA EN BD
    # ──────────────────────────────────────────────────────────────────────────

    def insert_coaster(self, scraped: Dict):
        """Inserta una coaster nueva en Supabase."""
        park_name = scraped.get('park', 'Desconocido')
        park_id = self.get_or_create_park(
            park_name,
            scraped.get('city'),
            scraped.get('country')
        )
        self.cursor.execute("""
            INSERT INTO coasters (
                rcdb_id, rcdb_url, coaster_name, park_id,
                coaster_manufacter, coaster_model, coaster_status,
                imagen_url, height, speed, coaster_length,
                inversions, opening_year, stars
            ) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,0)
            ON CONFLICT (rcdb_id) DO NOTHING
        """, (
            scraped.get('rcdb_id'),
            scraped.get('rcdb_url'),
            scraped.get('name', 'Sin nombre'),
            park_id,
            scraped.get('make'),
            scraped.get('model'),
            scraped.get('status'),
            scraped.get('main_image_url'),
            scraped.get('height_m'),
            scraped.get('speed_kmh'),
            scraped.get('length_m'),
            scraped.get('inversions', 0),
            scraped.get('year'),
        ))
        self.db.commit()

    def update_coaster(self, rcdb_id: int, scraped: Dict):
        """Actualiza TODOS los campos de una coaster existente con los datos frescos de RCDB."""
        self.cursor.execute("""
            UPDATE coasters SET
                coaster_name       = COALESCE(%s, coaster_name),
                coaster_manufacter = COALESCE(%s, coaster_manufacter),
                coaster_model      = COALESCE(%s, coaster_model),
                coaster_status     = COALESCE(%s, coaster_status),
                imagen_url         = COALESCE(%s, imagen_url),
                height             = COALESCE(%s, height),
                speed              = COALESCE(%s, speed),
                coaster_length     = COALESCE(%s, coaster_length),
                inversions         = COALESCE(%s, inversions),
                opening_year       = COALESCE(%s, opening_year)
            WHERE rcdb_id = %s
        """, (
            scraped.get('name'),
            scraped.get('make'),
            scraped.get('model'),
            scraped.get('status'),
            scraped.get('main_image_url'),
            scraped.get('height_m'),
            scraped.get('speed_kmh'),
            scraped.get('length_m'),
            scraped.get('inversions'),
            scraped.get('year'),
            rcdb_id,
        ))
        self.db.commit()

    # ──────────────────────────────────────────────────────────────────────────
    # BÚSQUEDA DEL ÚLTIMO ID
    # ──────────────────────────────────────────────────────────────────────────

    def find_last_id(self) -> int:
        """Búsqueda binaria + refinado para encontrar el último ID válido de RCDB."""
        print("🔍 Buscando el último ID de RCDB...")
        low, high = 10000, 40000
        last_valid = low

        while low <= high:
            mid = (low + high) // 2
            print(f"   Probando ID {mid}...", end=" ", flush=True)
            if self.scrape_coaster(mid):
                print("✅")
                last_valid = mid
                low = mid + 1
            else:
                print("❌")
                high = mid - 1
            time.sleep(0.3)

        # Refinado exhaustivo desde last_valid en pasos de 10
        print(f"   Refinando desde {last_valid}...")
        test_id = last_valid + 10
        while test_id <= last_valid + 2000:
            if self.scrape_coaster(test_id):
                last_valid = test_id
            test_id += 10
            time.sleep(0.2)

        print(f"✅ Último ID RCDB: {last_valid}")
        return last_valid

    # ──────────────────────────────────────────────────────────────────────────
    # ESCANEO PRINCIPAL
    # ──────────────────────────────────────────────────────────────────────────

    def run(self, start_id: int = 1, end_id: Optional[int] = None):
        """Ejecuta el escaneo completo."""
        print("\n" + "=" * 70)
        print("RCDB UPDATER  ·  Supabase (PostgreSQL)")
        print("=" * 70)

        if not self.connect_db():
            return

        # Cargar BD existente
        print("📦 Cargando coasters desde Supabase...", end=" ", flush=True)
        existing = self.get_existing_coasters()
        print(f"{len(existing)} coasters con rcdb_id")

        # Determinar rango
        if end_id is None:
            end_id = self.find_last_id()

        print(f"\n🚀 Escaneando IDs {start_id} → {end_id}\n")
        start_time = time.time()

        for rcdb_id in range(start_id, end_id + 1):
            scraped = self.scrape_coaster(rcdb_id)
            self.scanned += 1

            if not scraped:
                self.not_found += 1
            elif rcdb_id not in existing:
                # Coaster NUEVA
                self.new_coasters.append(scraped)
            else:
                # Coaster EXISTENTE — detectar cambios
                changes = self.compare_coaster(scraped, existing[rcdb_id])
                if changes:
                    self.changed_coasters.append({
                        'rcdb_id': rcdb_id,
                        'name':    scraped.get('name', '?'),
                        'changes': changes,
                        'scraped': scraped,
                    })

            # Progreso cada 50 IDs
            if self.scanned % 50 == 0:
                elapsed = time.time() - start_time
                speed = self.scanned / elapsed if elapsed > 0 else 1
                remaining = (end_id - (start_id + self.scanned - 1)) / speed
                print(
                    f"\r   [{self.scanned:,}/{end_id - start_id + 1:,}] "
                    f"Nuevas: {len(self.new_coasters)} | "
                    f"Cambios: {len(self.changed_coasters)} | "
                    f"~{remaining/60:.1f} min",
                    end="", flush=True
                )

            time.sleep(0.2)

        print("\n")
        self._save_cache()
        self._show_results()
        self.close_db()

    # ──────────────────────────────────────────────────────────────────────────
    # RESULTADOS E INTERACCIÓN
    # ──────────────────────────────────────────────────────────────────────────

    # ──────────────────────────────────────────────────────────────────────────
    # CACHE JSON  (para no perder resultados si la conexión cae)
    # ──────────────────────────────────────────────────────────────────────────

    def _save_cache(self):
        """Guarda new_coasters y changed_coasters en un JSON local."""
        data = {
            'saved_at': datetime.now().isoformat(),
            'scanned': self.scanned,
            'not_found': self.not_found,
            'new_coasters': self.new_coasters,
            'changed_coasters': self.changed_coasters,
        }
        with open(CACHE_FILE, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
        print(f"💾 Resultados guardados en: {CACHE_FILE}")

    @classmethod
    def from_cache(cls) -> 'RCDBUpdater':
        """Crea un updater a partir del cache JSON guardado."""
        if not os.path.exists(CACHE_FILE):
            raise FileNotFoundError(f"No existe cache: {CACHE_FILE}")
        with open(CACHE_FILE, 'r', encoding='utf-8') as f:
            data = json.load(f)
        inst = cls()
        inst.scanned           = data.get('scanned', 0)
        inst.not_found         = data.get('not_found', 0)
        inst.new_coasters      = data.get('new_coasters', [])
        inst.changed_coasters  = data.get('changed_coasters', [])
        print(f"✅ Cache cargado ({data.get('saved_at','?')}):")
        print(f"   Nuevas: {len(inst.new_coasters)} | Cambios: {len(inst.changed_coasters)}")
        return inst

    # ──────────────────────────────────────────────────────────────────────────
    # RESULTADOS E INTERACCIÓN
    # ──────────────────────────────────────────────────────────────────────────

    def _show_results(self):
        print("=" * 70)
        print("RESUMEN DEL ESCANEO")
        print("=" * 70)
        print(f"  IDs escaneados      : {self.scanned:,}")
        print(f"  IDs no encontrados  : {self.not_found:,}")
        print(f"  Coasters NUEVAS     : {len(self.new_coasters)}")
        print(f"  Coasters CON CAMBIOS: {len(self.changed_coasters)}")
        print()

        # ── Cambios en existentes ──────────────────────────────────────────────
        if self.changed_coasters:
            print("=" * 70)
            print("CAMBIOS DETECTADOS EN COASTERS EXISTENTES")
            print("=" * 70)
            for item in self.changed_coasters:
                print(f"\n  [{item['rcdb_id']}] {item['name']}")
                for ch in item['changes']:
                    print(f"    • {ch}")

            print()
            choice = input(
                "¿Aplicar cambios? [s = todos / n = ninguno / u = uno a uno]: "
            ).strip().lower()

            if choice in ('s', 'si', 'y', 'yes'):
                self._reconnect_db()
                for item in self.changed_coasters:
                    self.update_coaster(item['rcdb_id'], item['scraped'])
                    print(f"  ✅ Actualizado [{item['rcdb_id']}] {item['name']}")
                print(f"\n{len(self.changed_coasters)} coasters actualizadas.")

            elif choice in ('u', '1', 'uno'):
                self._reconnect_db()
                applied = 0
                for item in self.changed_coasters:
                    print(f"\n  [{item['rcdb_id']}] {item['name']}")
                    for ch in item['changes']:
                        print(f"    • {ch}")
                    ans = input("  ¿Aplicar este cambio? (s/n): ").strip().lower()
                    if ans in ('s', 'si', 'y'):
                        self.update_coaster(item['rcdb_id'], item['scraped'])
                        print("  ✅ Actualizado")
                        applied += 1
                    else:
                        print("  ⏭️  Saltado")
                print(f"\n{applied} coasters actualizadas.")
            else:
                print("Cambios no aplicados.")

        # ── Coasters nuevas ───────────────────────────────────────────────────
        if self.new_coasters:
            print()
            print("=" * 70)
            print("COASTERS NUEVAS ENCONTRADAS")
            print("=" * 70)
            for c in self.new_coasters[:50]:
                h = f"{c.get('height_m')}m"   if c.get('height_m') else "N/A"
                v = f"{c.get('speed_kmh')}km/h" if c.get('speed_kmh') else "N/A"
                print(
                    f"  [{c['rcdb_id']}] {c.get('name','?')} — "
                    f"{c.get('park','?')} — {c.get('status','?')} — "
                    f"H:{h} V:{v} Inv:{c.get('inversions','?')}"
                )
            if len(self.new_coasters) > 50:
                print(f"  ... y {len(self.new_coasters) - 50} más")

            print()
            choice = input(
                "¿Añadir nuevas a la BD? [s = todas / n = ninguna / u = una a una]: "
            ).strip().lower()

            if choice in ('s', 'si', 'y', 'yes'):
                self._reconnect_db()
                for c in self.new_coasters:
                    self.insert_coaster(c)
                    print(f"  ✅ Insertada [{c['rcdb_id']}] {c.get('name','?')}")
                print(f"\n{len(self.new_coasters)} coasters insertadas.")

            elif choice in ('u', '1', 'uno'):
                self._reconnect_db()
                inserted = 0
                for c in self.new_coasters:
                    h = f"{c.get('height_m')}m"    if c.get('height_m') else "N/A"
                    v = f"{c.get('speed_kmh')}km/h" if c.get('speed_kmh') else "N/A"
                    print(
                        f"\n  [{c['rcdb_id']}] {c.get('name','?')} — "
                        f"{c.get('park','?')} ({c.get('status','?')}) — "
                        f"H:{h} V:{v}"
                    )
                    ans = input("  ¿Insertar? (s/n): ").strip().lower()
                    if ans in ('s', 'si', 'y'):
                        self.insert_coaster(c)
                        print("  ✅ Insertada")
                        inserted += 1
                    else:
                        print("  ⏭️  Saltada")
                print(f"\n{inserted} coasters insertadas.")
            else:
                print("No se insertaron coasters nuevas.")

        print("\n" + "=" * 70)
        print("ESCANEO COMPLETADO")
        print("=" * 70)


# ──────────────────────────────────────────────────────────────────────────────
# MENÚ DE EJECUCIÓN
# ──────────────────────────────────────────────────────────────────────────────

def menu():
    print("\n" + "=" * 70)
    print("RCDB UPDATER - Supabase/PostgreSQL")
    print("=" * 70)
    print()
    print("1. COMPLETO   · Desde ID #1 hasta el último (puede tardar horas)")
    print("2. RÁPIDO     · Solo los últimos 2000 IDs (coasters recientes)")
    print("3. RANGO      · Tú eliges el rango de IDs")
    print("4. BUSCAR     · Solo encontrar cuál es el último ID de RCDB")
    cache_exists = os.path.exists(CACHE_FILE)
    if cache_exists:
        print(f"5. APLICAR CACHE · Cargar resultados guardados y aplicarlos (sin re-escanear)")
    print()

    choice = input(f"Opción (1-{'5' if cache_exists else '4'}): ").strip()

    # ── Opción 5: aplicar desde cache (no necesita escanear) ──────────────────
    if choice == '5' and cache_exists:
        try:
            updater = RCDBUpdater.from_cache()
        except Exception as e:
            print(f"[ERROR] {e}")
            return
        if not updater.connect_db():
            return
        updater._show_results()
        updater.close_db()
        return

    updater = RCDBUpdater()

    if choice == '1':
        if not updater.connect_db():
            return
        last_id = updater.find_last_id()
        confirm = input(f"¿Escanear desde #1 hasta #{last_id}? (s/n): ").strip().lower()
        if confirm in ('s', 'si', 'y'):
            updater.run(start_id=1, end_id=last_id)

    elif choice == '2':
        if not updater.connect_db():
            return
        last_id = updater.find_last_id()
        start_id = max(1, last_id - 2000)
        print(f"\nEscaneando IDs #{start_id} → #{last_id}")
        updater.run(start_id=start_id, end_id=last_id)

    elif choice == '3':
        if not updater.connect_db():
            return
        start_id = int(input("ID inicial: ").strip())
        end_id   = int(input("ID final  : ").strip())
        updater.run(start_id=start_id, end_id=end_id)

    elif choice == '4':
        if not updater.connect_db():
            return
        last_id = updater.find_last_id()
        print(f"\nÚltimo ID aproximado: #{last_id}")

    else:
        print("Opción no válida")


if __name__ == "__main__":
    try:
        menu()
    except KeyboardInterrupt:
        print("\n\nInterrumpido por el usuario")
        sys.exit(0)

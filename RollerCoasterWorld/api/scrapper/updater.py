#!/usr/bin/env python3
"""
RCDB Updater - Escanea RCDB y compara con la base de datos
Detecta coasters nuevas, cambios de nombre, stats actualizadas, etc.
"""

import mysql.connector
import requests
from bs4 import BeautifulSoup
import re
import time
import sys
import os
from typing import List, Dict, Any, Optional

# Leer configuración de BD desde .env (compartido con PHP)
def load_env():
    env_path = os.path.join(os.path.dirname(__file__), '..', '..', '.env')
    config = {}
    with open(env_path, 'r') as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith('#') and '=' in line:
                key, value = line.split('=', 1)
                config[key.strip()] = value.strip()
    return config

_env = load_env()
DB_CONFIG = {
    'host': _env.get('DB_HOST', 'localhost'),
    'user': _env.get('DB_USER', 'root'),
    'password': _env.get('DB_PASS', ''),
    'database': _env.get('DB_NAME', 'rollercoasterworld'),
    'charset': 'utf8mb4'
}


class RCDBUpdater:
    """
    Escanea TODA la web de RCDB y compara con la base de datos local.
    Detecta cambios y permite aplicarlos interactivamente.
    """

    def __init__(self):
        self.base_url = "https://rcdb.com"
        self.headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
        }
        self.session = requests.Session()
        self.session.headers.update(self.headers)

        # Estadísticas del escaneo
        self.scanned = 0
        self.new_coasters = []
        self.updated_coasters = []
        self.not_found = 0

        # Conexión a BD
        self.db = None
        self.cursor = None

    # ============================================================
    # BASE DE DATOS
    # ============================================================

    def connect_db(self):
        """Conectar a MySQL"""
        try:
            self.db = mysql.connector.connect(**DB_CONFIG)
            self.cursor = self.db.cursor(dictionary=True)
            print("Conectado a la base de datos")
            return True
        except mysql.connector.Error as e:
            print(f"Error conectando a la BD: {e}")
            return False

    def close_db(self):
        """Cerrar conexión"""
        if self.cursor:
            self.cursor.close()
        if self.db:
            self.db.close()

    def get_existing_coasters(self) -> Dict[int, Dict]:
        """
        Obtiene todas las coasters de la BD que tienen rcdb_id (no custom).
        Devuelve un dict {rcdb_id: {datos}}
        """
        self.cursor.execute("""
            SELECT c.id, c.rcdb_id, c.coaster_name, c.park_id, c.coaster_manufacter,
                   c.coaster_model, c.coaster_status, c.height, c.speed,
                   c.coaster_length, c.inversions, c.opening_year,
                   p.park_name
            FROM coasters c
            LEFT JOIN parks p ON c.park_id = p.id
            WHERE c.rcdb_id IS NOT NULL
        """)
        rows = self.cursor.fetchall()
        return {row['rcdb_id']: row for row in rows}

    def get_or_create_park(self, park_name: str, city: str = None, country: str = None) -> int:
        """
        Busca un parque por nombre. Si no existe, lo crea y devuelve su id.
        """
        self.cursor.execute("SELECT id FROM parks WHERE park_name = %s", (park_name,))
        result = self.cursor.fetchone()
        if result:
            return result['id']

        # Crear parque nuevo
        location = city if city else ''
        self.cursor.execute(
            "INSERT INTO parks (park_name, park_location, park_country) VALUES (%s, %s, %s)",
            (park_name, location, country if country else '')
        )
        self.db.commit()
        park_id = self.cursor.lastrowid
        print("Parque nuevo creado:", park_name, "(ID:", park_id, ")")
        return park_id

    # ============================================================
    # SCRAPING (reutiliza lógica del scrapper.py)
    # ============================================================

    def clean_text(self, text: str) -> str:
        if not text:
            return ""
        return re.sub(r'\s+', ' ', text).strip()

    def extract_number(self, text: str) -> Optional[float]:
        if not text:
            return None
        text = text.replace(',', '')
        match = re.search(r'([\d]+(?:\.\d+)?)', text)
        if match:
            return float(match.group(1))
        return None

    def get_text_safe(self, element, stop_tags: list) -> str:
        """Texto directo de un elemento, parando en tags prohibidos"""
        if not element:
            return ""
        text_parts = []
        for child in element.contents:
            if child.name in stop_tags:
                break
            if isinstance(child, str):
                text_parts.append(child)
            elif child.name == 'br':
                text_parts.append(' ')
            else:
                text_parts.append(child.get_text())
        return self.clean_text(" ".join(text_parts))

    def parse_stat_table(self, soup: BeautifulSoup) -> Dict[str, Any]:
        """Parsea las tablas stat-tbl"""
        stats = {}
        stat_tables = soup.find_all('table', class_='stat-tbl')

        for table in stat_tables:
            rows = table.find_all('tr')
            for row in rows:
                th = row.find('th')
                if not th:
                    continue
                td = row.find('td')
                if not td:
                    continue

                stat_name = self.get_text_safe(th, ['td'])
                cell_full_text = self.get_text_safe(td, ['tr', 'th'])

                # Buscar span.float
                value_span = None
                for child in td.contents:
                    if child.name in ['tr', 'th']:
                        break
                    if hasattr(child, 'attrs') and 'float' in child.attrs.get('class', []):
                        value_span = child
                        break

                if value_span:
                    value_text = self.clean_text(value_span.text).replace(',', '.')
                    num = self.extract_number(value_text)
                else:
                    value_text = cell_full_text
                    num = self.extract_number(value_text)

                # Procesar según tipo de stat
                if 'Length' in stat_name:
                    if num:
                        if ' m' in cell_full_text and 'mph' not in cell_full_text:
                            stats['length_m'] = num
                        elif ' ft' in cell_full_text:
                            stats['length_m'] = round(num * 0.3048, 1)

                elif 'Height' in stat_name:
                    if num:
                        if ' m' in cell_full_text:
                            stats['height_m'] = num
                        elif ' ft' in cell_full_text:
                            stats['height_m'] = round(num * 0.3048, 1)

                elif 'Speed' in stat_name:
                    if num:
                        if 'km/h' in cell_full_text:
                            stats['speed_kmh'] = num
                        elif 'mph' in cell_full_text:
                            stats['speed_kmh'] = round(num * 1.60934, 1)

                elif 'Inversions' in stat_name:
                    if num is not None:
                        stats['inversions'] = int(num)

        return stats

    def scrape_coaster(self, rcdb_id: int) -> Optional[Dict[str, Any]]:
        """Descarga los datos de una coaster por su ID de RCDB"""
        url = f"{self.base_url}/{rcdb_id}.htm"

        try:
            response = self.session.get(url, timeout=15)
            if response.status_code == 404:
                return None
            response.raise_for_status()
            soup = BeautifulSoup(response.text, 'html.parser')

            coaster = {'rcdb_id': rcdb_id, 'rcdb_url': url}

            # Imagen principal
            picture_div = soup.find('div', id='demo-pic')
            if picture_div:
                img_tag = picture_div.find('img')
                if img_tag and img_tag.get('src'):
                    coaster['main_image_url'] = f"{self.base_url}{img_tag.get('src')}"
            
            if 'main_image_url' not in coaster:
                meta_img = soup.find('meta', property='og:image')
                if meta_img and meta_img.get('content'):
                    coaster['main_image_url'] = meta_img.get('content')

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
                if location_links:
                    locations = [link.text.strip() for link in location_links]
                    if len(locations) >= 1:
                        coaster['city'] = locations[0]
                    if len(locations) >= 4:
                        coaster['country'] = locations[3]
                    elif len(locations) >= 1:
                        coaster['country'] = locations[-1]

            # Estado
            status_link = soup.find('a', href=re.compile(r'/g\.htm\?id=9[0-9]'))
            if status_link:
                coaster['status'] = status_link.text.strip()

            # Año
            text_content = soup.get_text()
            since_match = re.search(r'Operating since\s+(\d{1,2}/\d{1,2}/\d{4})', text_content)
            if since_match:
                year_match = re.search(r'/(\d{4})$', since_match.group(1))
                if year_match:
                    coaster['year'] = int(year_match.group(1))

            # Make
            make_section = re.search(r'Make:\s*([^\n]+?)(?:Model:|Pictures|Videos|$)', text_content, re.DOTALL)
            if make_section:
                coaster['make'] = ' '.join(make_section.group(1).strip().split())

            # Model
            model_section = re.search(r'Model:\s*([^\n]+?)(?:Pictures|Videos|Tracks|$)', text_content, re.DOTALL)
            if model_section:
                model_text = model_section.group(1).strip()
                model_text = re.sub(r'All Models\s*/\s*', '', model_text)
                model_text = ' '.join(model_text.split())
                model_text = re.sub(r'(Pictures|Videos|Maps|Tracks).*$', '', model_text).strip()
                coaster['model'] = model_text

            # Stats desde tablas
            stats = self.parse_stat_table(soup)
            coaster.update(stats)

            return coaster

        except Exception:
            return None

    # ============================================================
    # COMPARACIÓN Y DETECCIÓN DE CAMBIOS
    # ============================================================

    def compare_coaster(self, scraped: Dict, existing: Dict) -> List[str]:
        """
        Compara una coaster escaneada con la existente en BD.
        Devuelve lista de cambios detectados.
        """
        changes = []

        # Nombre
        if scraped.get('name') and scraped['name'] != existing.get('coaster_name'):
            changes.append(f"Nombre: \"{existing.get('coaster_name')}\" → \"{scraped['name']}\"")

        # Estado
        if scraped.get('status') and scraped['status'] != existing.get('coaster_status'):
            changes.append(f"Estado: \"{existing.get('coaster_status')}\" → \"{scraped['status']}\"")

        # Fabricante
        if scraped.get('make') and scraped['make'] != existing.get('coaster_manufacter'):
            changes.append(f"Fabricante: \"{existing.get('coaster_manufacter')}\" → \"{scraped['make']}\"")

        # Model
        if scraped.get('model') and scraped['model'] != existing.get('coaster_model'):
            changes.append(f"Modelo: \"{existing.get('coaster_model')}\" → \"{scraped['model']}\"")

        # Altura (en metros)
        new_h = scraped.get('height_m')
        old_h = float(existing['height']) if existing.get('height') else None
        if new_h and new_h != old_h:
            changes.append(f"Altura: {old_h} → {new_h} m")

        # Velocidad (en km/h)
        new_s = scraped.get('speed_kmh')
        old_s = float(existing['speed']) if existing.get('speed') else None
        if new_s and new_s != old_s:
            changes.append(f"Velocidad: {old_s} → {new_s} km/h")

        # Longitud
        new_l = scraped.get('length_m')
        old_l = float(existing['coaster_length']) if existing.get('coaster_length') else None
        if new_l and new_l != old_l:
            changes.append(f"Longitud: {old_l} → {new_l} m")

        # Inversiones
        new_i = scraped.get('inversions')
        old_i = int(existing['inversions']) if existing.get('inversions') is not None else None
        if new_i is not None and new_i != old_i:
            changes.append(f"Inversiones: {old_i} → {new_i}")

        # Año
        if scraped.get('year') and scraped['year'] != existing.get('opening_year'):
            changes.append(f"Año: {existing.get('opening_year')} → {scraped['year']}")

        return changes

    # ============================================================
    # APLICAR CAMBIOS EN BD
    # ============================================================

    def insert_coaster(self, scraped: Dict):
        """Inserta una coaster nueva en la BD"""
        park_name = scraped.get('park', 'Desconocido')
        park_id = self.get_or_create_park(
            park_name,
            scraped.get('city'),
            scraped.get('country')
        )

        self.cursor.execute("""
            INSERT INTO coasters (rcdb_id, rcdb_url, coaster_name, park_id,
                coaster_manufacter, coaster_model, coaster_status,
                height, speed, coaster_length, inversions, opening_year)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """, (
            scraped.get('rcdb_id'),
            scraped.get('rcdb_url'),
            scraped.get('name', 'Sin nombre'),
            park_id,
            scraped.get('make'),
            scraped.get('model'),
            scraped.get('status'),
            scraped.get('height_m'),
            scraped.get('speed_kmh'),
            scraped.get('length_m'),
            scraped.get('inversions', 0),
            scraped.get('year'),
        ))
        self.db.commit()

    def update_coaster(self, rcdb_id: int, scraped: Dict):
        """Actualiza una coaster existente en la BD"""
        self.cursor.execute("""
            UPDATE coasters SET
                coaster_name = %s,
                coaster_manufacter = %s,
                coaster_model = %s,
                coaster_status = %s,
                height = %s,
                speed = %s,
                coaster_length = %s,
                inversions = %s,
                opening_year = %s
            WHERE rcdb_id = %s
        """, (
            scraped.get('name'),
            scraped.get('make'),
            scraped.get('model'),
            scraped.get('status'),
            scraped.get('height_m'),
            scraped.get('speed_kmh'),
            scraped.get('length_m'),
            scraped.get('inversions', 0),
            scraped.get('year'),
            rcdb_id,
        ))
        self.db.commit()

    # ============================================================
    # LÓGICA PRINCIPAL
    # ============================================================

    def find_last_id(self) -> int:
        """Busca el último ID válido de RCDB con búsqueda binaria"""
        print("Buscando el último ID de RCDB...")
        low, high = 10000, 30000
        last_valid = low

        while low <= high:
            mid = (low + high) // 2
            print("Probando ID", mid, "...", end=" ", flush=True)
            coaster = self.scrape_coaster(mid)
            if coaster:
                print("✅")
                last_valid = mid
                low = mid + 1
            else:
                print("❌")
                high = mid - 1
            time.sleep(0.3)

        # Refinar
        print("Refinando desde", last_valid, "...")
        test_id = last_valid
        while test_id < last_valid + 1000:
            coaster = self.scrape_coaster(test_id)
            if coaster:
                last_valid = test_id
            test_id += 50
            time.sleep(0.2)

        print(f"Último ID: {last_valid}")
        return last_valid

    def run(self):
        """Ejecuta el escaneo completo"""
        print("\n" + "=" * 70)
        print("RCDB UPDATER - Escaneo completo y comparación con BD")
        print("=" * 70)

        # 1. Conectar a BD
        if not self.connect_db():
            return

        # 2. Obtener coasters existentes
        existing = self.get_existing_coasters()
        print(f"Coasters en BD con rcdb_id: {len(existing)}")

        # 3. Encontrar último ID
        last_id = self.find_last_id()
        print(f"Escaneando IDs 1 → {last_id}")
        print()

        start_time = time.time()

        # 4. Escanear TODAS
        for rcdb_id in range(1, last_id + 1):
            scraped = self.scrape_coaster(rcdb_id)
            self.scanned += 1

            if not scraped:
                self.not_found += 1
            elif rcdb_id in existing:
                # Existe → comparar cambios
                changes = self.compare_coaster(scraped, existing[rcdb_id])
                if changes:
                    self.updated_coasters.append({
                        'rcdb_id': rcdb_id,
                        'name': scraped.get('name', '?'),
                        'changes': changes,
                        'scraped': scraped,
                    })
            else:
                # Nueva
                self.new_coasters.append(scraped)

            # Progreso cada 10 IDs
            if self.scanned % 10 == 0:
                progress = (self.scanned / last_id) * 100
                elapsed = time.time() - start_time
                speed = self.scanned / elapsed if elapsed > 0 else 0
                remaining = (last_id - self.scanned) / speed if speed > 0 else 0
                print(f"\r   Escaneando... {self.scanned:,}/{last_id:,} ({progress:.1f}%) | "
                      f"Nuevos: {len(self.new_coasters)} | Cambios: {len(self.updated_coasters)} | "
                      f"~{remaining/60:.1f} min restantes",
                      end="", flush=True)

            time.sleep(0.2)

        print("\n")

        # 5. Mostrar resultados
        self.show_results()

        # 6. Cerrar BD
        self.close_db()

    def show_results(self):
        """Muestra los resultados y permite aplicar cambios"""
        print("=" * 70)
        print("RESUMEN DEL ESCANEO")
        print("=" * 70)
        print(f"Total escaneados: {self.scanned:,}")
        print(f"IDs no encontrados: {self.not_found:,}")
        print(f"Coasters nuevas: {len(self.new_coasters)}")
        print(f"Coasters con cambios: {len(self.updated_coasters)}")
        print()

        # ---- CAMBIOS EN COASTERS EXISTENTES ----
        if self.updated_coasters:
            print("=" * 70)
            print("CAMBIOS DETECTADOS EN COASTERS EXISTENTES")
            print("=" * 70)
            for item in self.updated_coasters:
                print(f"\n[{item['rcdb_id']}] {item['name']}:")
                for change in item['changes']:
                    print(f"• {change}")

            print()
            choice = input("¿Aplicar TODOS los cambios? (s/n/uno a uno): ").strip().lower()

            if choice in ['s', 'si', 'yes', 'y']:
                for item in self.updated_coasters:
                    self.update_coaster(item['rcdb_id'], item['scraped'])
                    print(f"Actualizado: [{item['rcdb_id']}] {item['name']}")
                print(f"\n{len(self.updated_coasters)} coasters actualizadas.")

            elif choice in ['uno', 'uno a uno', 'u', '1']:
                for item in self.updated_coasters:
                    print(f"\n[{item['rcdb_id']}] {item['name']}:")
                    for change in item['changes']:
                        print(f"• {change}")
                    apply = input("¿Aplicar? (s/n): ").strip().lower()
                    if apply in ['s', 'si', 'y']:
                        self.update_coaster(item['rcdb_id'], item['scraped'])
                        print(f"Actualizado")
                    else:
                        print(f"Saltado")
            else:
                print("Cambios no aplicados.")

        # ---- COASTERS NUEVAS ----
        if self.new_coasters:
            print()
            print("=" * 70)
            print("COASTERS NUEVAS ENCONTRADAS")
            print("=" * 70)
            for c in self.new_coasters[:50]:  # Mostrar máximo 50
                height_str = f"{c.get('height_m')}m" if c.get('height_m') else "N/A"
                speed_str = f"{c.get('speed_kmh')}km/h" if c.get('speed_kmh') else "N/A"
                print(f"[{c['rcdb_id']}] {c.get('name', '?')} - "
                      f"{c.get('park', '?')} - "
                      f"{c.get('status', '?')} - "
                      f"H:{height_str} V:{speed_str} Inv:{c.get('inversions', '?')}")

            if len(self.new_coasters) > 50:
                print(f"   ... y {len(self.new_coasters) - 50} más")

            print()
            choice = input("¿Añadir TODAS las nuevas a la BD? (s/n/uno a uno): ").strip().lower()

            if choice in ['s', 'si', 'yes', 'y']:
                for c in self.new_coasters:
                    self.insert_coaster(c)
                print(f"\n{len(self.new_coasters)} coasters insertadas.")

            elif choice in ['uno', 'uno a uno', 'u', '1']:
                for c in self.new_coasters:
                    print(f"\n[{c['rcdb_id']}] {c.get('name', '?')} - "
                          f"{c.get('park', '?')} ({c.get('status', '?')})")
                    apply = input("¿Añadir? (s/n): ").strip().lower()
                    if apply in ['s', 'si', 'y']:
                        self.insert_coaster(c)
                        print(f"Insertada")
                    else:
                        print(f"Saltada")
            else:
                print("Nuevas coasters no añadidas.")

        print("\n" + "=" * 70)
        print("ESCANEO COMPLETADO")
        print("=" * 70)


# ============================================================
# EJECUCIÓN DIRECTA
# ============================================================

if __name__ == "__main__":
    try:
        updater = RCDBUpdater()
        updater.run()
    except KeyboardInterrupt:
        print("\n\nInterrumpido por el usuario")
        sys.exit(0)

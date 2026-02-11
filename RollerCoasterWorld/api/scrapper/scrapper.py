"""
RCDB Scraper 
Descarga TODAS las montañas rusas de RCDB por rango de IDs
"""

import requests
from bs4 import BeautifulSoup
import json
import time
import re
from typing import List, Dict, Any, Optional
from datetime import datetime
import sys

class RCDBScraper:    
    def __init__(self):
        self.base_url = "https://rcdb.com"
        self.headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            'Connection': 'keep-alive',
        }
        self.session = requests.Session()
        self.session.headers.update(self.headers)
        self.successful = 0
        self.failed = 0
        self.not_found = 0
    
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
    
    def get_text_safe(self, element: Any, stop_tags: List[str]) -> str:
        """
        Obtiene el texto directo de un elemento, deteniéndose si encuentra tags prohibidos
        (útil para cuando el parser anida tr/td incorrectamente)
        """
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
        """
        Parsea la tabla con clase 'stat-tbl' que contiene las estadísticas
        """
        stats = {}
        
        # Buscar todas las tablas con clase stat-tbl
        stat_tables = soup.find_all('table', class_='stat-tbl')
        
        for table in stat_tables:
            # Iterar por cada fila de la tabla
            rows = table.find_all('tr')
            for row in rows:
                # El th tiene el nombre del stat
                th = row.find('th')
                if not th:
                    continue
                
                # El valor está en el td
                td = row.find('td')
                if not td:
                    continue
                
                # Extraer nombre del stat evitando el td anidado
                stat_name = self.get_text_safe(th, ['td'])
                
                # Extraer texto de la celda evitando tr/th anidados
                cell_full_text = self.get_text_safe(td, ['tr', 'th'])
                
                # Intentar buscar valor en span class="float"
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
                
                # --- PROCESAMIENTO SEGÚN NOMBRE DEL STAT ---
                
                if 'Length' in stat_name or 'Longitud' in stat_name:
                    if num:
                        if ' m' in cell_full_text and 'mph' not in cell_full_text:
                            stats['length_m'] = num
                            stats['length_ft'] = round(num / 0.3048, 0)
                            stats['length'] = f"{num} m" # Mantener formato string antiguo por compatibilidad si es necesario
                        elif ' ft' in cell_full_text:
                            stats['length_ft'] = int(num)
                            stats['length_m'] = round(num * 0.3048, 1)
                            stats['length'] = f"{round(num * 0.3048, 1)} m"

                elif 'Height' in stat_name or 'Altura' in stat_name:
                    if num:
                        if ' m' in cell_full_text:
                            stats['height_m'] = num
                            stats['height_ft'] = round(num / 0.3048, 0)
                            stats['height'] = f"{num} m"
                        elif ' ft' in cell_full_text:
                            stats['height_ft'] = int(num)
                            stats['height_m'] = round(num * 0.3048, 1)
                            stats['height'] = f"{round(num * 0.3048, 1)} m"

                elif 'Drop' in stat_name or 'Caída' in stat_name:
                     if num:
                        if ' m' in cell_full_text:
                            stats['drop_m'] = num
                            stats['drop_ft'] = round(num / 0.3048, 0)
                            stats['drop'] = f"{num} m"
                        elif ' ft' in cell_full_text:
                            stats['drop_ft'] = int(num)
                            stats['drop_m'] = round(num * 0.3048, 1)
                            stats['drop'] = f"{round(num * 0.3048, 1)} m"

                elif 'Speed' in stat_name or 'Velocidad' in stat_name:
                    if num:
                        if 'km/h' in cell_full_text:
                            stats['speed_kmh'] = num
                            stats['speed_mph'] = round(num / 1.60934, 1)
                            stats['speed'] = f"{num} km/h"
                        elif 'mph' in cell_full_text:
                            stats['speed_mph'] = num
                            stats['speed_kmh'] = round(num * 1.60934, 1)
                            stats['speed'] = f"{round(num * 1.60934, 1)} km/h"

                elif 'Inversions' in stat_name or 'Inversiones' in stat_name:
                    if num is not None: 
                        stats['inversions'] = int(num)
                
                elif 'Duration' in stat_name or 'Duración' in stat_name:
                    stats['duration'] = value_text
                
                elif 'Vertical Angle' in stat_name or 'Ángulo' in stat_name:
                    if num:
                        stats['vertical_angle'] = num # Se podría añadir 'deg' si se quiere string
                
                elif 'G-Force' in stat_name:
                    if num:
                        stats['g_force'] = num
                
                elif 'Capacity' in stat_name or 'Capacidad' in stat_name:
                     if num:
                        stats['capacity_per_hour'] = int(num)
                        stats['capacity'] = int(num) # Compatibilidad
                
                elif 'Cost' in stat_name or 'Coste' in stat_name:
                     if num:
                        stats['cost'] = int(num)
                        
        return stats

    def get_coaster_by_id(self, coaster_id: int) -> Optional[Dict[str, Any]]:
        """
        Obtiene los datos de una coaster por su ID de RCDB
        """
        url = f"{self.base_url}/{coaster_id}.htm"
        
        try:
            response = self.session.get(url, timeout=15)
            
            # Si es 404, la coaster no existe
            if response.status_code == 404:
                return None
            
            response.raise_for_status()
            soup = BeautifulSoup(response.text, 'html.parser')
            
            coaster = {
                'id': coaster_id,
                'rcdb_url': url
            }
            
            # Imagen principal - usar a#opfAnchor con data-url
            opf_anchor = soup.find('a', id='opfAnchor')
            if opf_anchor and opf_anchor.get('data-url'):
                coaster['main_image_url'] = f"{self.base_url}{opf_anchor.get('data-url')}"
            
            # Fallback: meta og:image
            if 'main_image_url' not in coaster:
                meta_img = soup.find('meta', property='og:image')
                if meta_img and meta_img.get('content'):
                    coaster['main_image_url'] = meta_img.get('content')
            
            # Nombre (h1)
            h1 = soup.find('h1')
            if h1:
                coaster['name'] = h1.text.strip()
            
            # Parque y ubicación están justo después del h1
            # Ejemplo: PortAventura Park (Salou, Tarragona, Catalonia, Spain)
            h1_parent = h1.parent if h1 else None
            if h1_parent:
                # Buscar el primer <a> con href que contenga un número (es el parque)
                park_link = h1_parent.find('a', href=re.compile(r'/\d+\.htm'))
                if park_link:
                    coaster['park'] = park_link.text.strip()
                
                # Buscar todos los links de ubicación
                location_links = h1_parent.find_all('a', href=re.compile(r'/location\.htm'))
                if location_links:
                    locations = [link.text.strip() for link in location_links]
                    if len(locations) >= 1:
                        coaster['city'] = locations[0]
                    if len(locations) >= 2:
                        coaster['state'] = locations[1]
                    if len(locations) >= 3:
                        coaster['region'] = locations[2]
                    if len(locations) >= 4:
                        coaster['country'] = locations[3]
                    elif len(locations) >= 1:
                        # Si solo hay uno, es el país
                        coaster['country'] = locations[-1]
            
            # Estado y Año de apertura - extraer de div#feature > p
            feature_div = soup.find('div', id='feature')
            if feature_div:
                status_p = feature_div.find('p')
                if status_p:
                    status_text = status_p.get_text()
                    # Status por keywords
                    if 'Operating' in status_text:
                        coaster['status'] = 'Operating'
                    elif 'Removed' in status_text:
                        coaster['status'] = 'Closed'
                    elif 'In Storage' in status_text:
                        coaster['status'] = 'In Storage'
                    elif 'Under Construction' in status_text:
                        coaster['status'] = 'Construction'
                    elif 'SBNO' in status_text or 'Standing But Not Operating' in status_text:
                        coaster['status'] = 'SBNO'
                    elif 'Relocated' in status_text:
                        coaster['status'] = 'Relocated'
                    else:
                        sl = status_p.find('a', href=re.compile(r'/g\.htm\?id=\d+'))
                        if sl:
                            coaster['status'] = sl.text.strip()
                    
                    # Año de apertura desde <time datetime="...">
                    time_tag = status_p.find('time')
                    if time_tag and time_tag.get('datetime'):
                        dt = time_tag.get('datetime')
                        coaster['opened'] = dt
                        ym = re.search(r'^(\d{4})', dt)
                        if ym:
                            coaster['year'] = int(ym.group(1))
            
            # text_content se usa más abajo para tipo, diseño, make, model
            text_content = soup.get_text()
            
            # Tipo y diseño - están en el texto después del h1
            # Steel, Wood, etc.
            if 'Steel' in text_content[:2000]:  # Buscar en los primeros 2000 chars
                coaster['type'] = 'Steel'
            elif 'Wood' in text_content[:2000]:
                coaster['type'] = 'Wood'
            
            # Sit Down, Inverted, Flying, etc.
            design_options = ['Sit Down', 'Inverted', 'Flying', 'Stand Up', 'Floorless', 
                            'Pipeline', 'Suspended', 'Wing', 'Bobsled', '4th Dimension']
            for design in design_options:
                if design in text_content[:2000]:
                    coaster['design'] = design
                    break
            
            # Make y Model - buscar links con href que contenga números (fabricantes)
            make_link = soup.find('a', href=re.compile(r'/\d{4,5}\.htm'))
            if make_link and make_link.text.strip() not in [coaster.get('park', ''), coaster.get('name', '')]:
                make_text = make_link.text.strip()
                if 'Bolliger' in make_text or 'Intamin' in make_text or 'Vekoma' in make_text or 'Mack' in make_text:
                    coaster['make'] = make_text
            
            # Make - buscar texto "Make:" seguido del fabricante
            make_section = re.search(r'Make:\s*([^\n]+?)(?:Model:|Pictures|Videos|$)', text_content, re.DOTALL)
            if make_section:
                make_text = make_section.group(1).strip()
                # Limpiar saltos de línea y espacios extra
                make_text = ' '.join(make_text.split())
                coaster['make'] = make_text
            
            # Model - buscar texto "Model:" seguido del modelo
            model_section = re.search(r'Model:\s*([^\n]+?)(?:Pictures|Videos|Tracks|$)', text_content, re.DOTALL)
            if model_section:
                model_text = model_section.group(1).strip()
                # Limpiar "All Models /"
                model_text = re.sub(r'All Models\s*/\s*', '', model_text)
                # Limpiar saltos de línea y espacios extra
                model_text = ' '.join(model_text.split())
                # Quitar "Pictures", "Videos", etc si quedaron
                model_text = re.sub(r'(Pictures|Videos|Maps|Tracks).*$', '', model_text).strip()
                coaster['model'] = model_text
            
            # --- USAR PARSER ROBUSTO DE TABLAS PARA ESTADÍSTICAS ---
            stats = self.parse_stat_table(soup)
            coaster.update(stats)
            
            # Elements / Elementos (Regex sigue siendo útil para esto si no está en tabla)
            elements_match = re.search(r'Elements\s+(.+?)(?:\n\n|\nTrains|\nDetails|$)', text_content, re.DOTALL)
            if elements_match:
                elements_text = elements_match.group(1).strip()
                # Limpiar saltos de línea
                elements_text = ' '.join(elements_text.split())
                coaster['elements'] = elements_text[:200]  # Limitar a 200 chars
            
            # Arrangement (trenes y configuración)
            arrangement_match = re.search(r'Arrangement\s+(\d+)\s+trains?\s+with\s+(\d+)\s+cars?\s+per\s+train', text_content, re.DOTALL)
            if arrangement_match:
                coaster['trains'] = int(arrangement_match.group(1).strip())
                coaster['cars_per_train'] = int(arrangement_match.group(2).strip())
            
            # Riders per train
            riders_match = re.search(r'total of\s+(\d+)\s+riders?\s+per\s+train', text_content, re.DOTALL)
            if riders_match:
                coaster['riders_per_train'] = int(riders_match.group(1).strip())
            
            return coaster
            
        except requests.exceptions.Timeout:
            return None
        except requests.exceptions.RequestException:
            return None
        except Exception as e:
            # Para debug, podemos ver qué falla
            # print(f"Error en ID {coaster_id}: {e}")
            return None
    
    def download_by_id_range(self, start_id: int = 1, end_id: int = 20000, 
                            batch_size: int = 100, save_interval: int = 500) -> List[Dict[str, Any]]:
        """
        Descarga coasters por rango de IDs con guardado incremental
        """
        print("=" * 70)
        print(f"DESCARGA COMPLETA POR ID: {start_id} → {end_id}")
        print("=" * 70)
        print()
        
        all_coasters = []
        total_ids = end_id - start_id + 1
        
        print(f"Total de IDs a procesar: {total_ids:,}")
        print(f"Guardado automático cada {save_interval} IDs")
        print(f"Tiempo estimado: {(total_ids * 0.3) / 60:.1f} minutos")
        print()
        
        start_time = time.time()
        
        for current_id in range(start_id, end_id + 1):
            # Obtener coaster
            coaster = self.get_coaster_by_id(current_id)
            
            if coaster:
                all_coasters.append(coaster)
                self.successful += 1
                status = "found"
            else:
                self.not_found += 1
                status = "NOT found"
            
            # Mostrar progreso cada 10 IDs
            if current_id % 10 == 0 or current_id == start_id:
                progress = ((current_id - start_id + 1) / total_ids) * 100
                elapsed = time.time() - start_time
                ids_per_sec = (current_id - start_id + 1) / elapsed if elapsed > 0 else 0
                remaining_secs = (end_id - current_id) / ids_per_sec if ids_per_sec > 0 else 0
                
                print(f"\r{status} ID {current_id:,}/{end_id:,} | "
                      f"Progreso: {progress:.1f}% | "
                      f"Encontradas: {self.successful:,} | "
                      f"No encontradas: {self.not_found:,} | "
                      f"Quedan ~{remaining_secs/60:.1f} min", 
                      end="", flush=True)
            
            # Guardado incremental
            if current_id % save_interval == 0 and all_coasters:
                print()
                temp_filename = f"rcdb_partial_{start_id}_{current_id}.json"
                self._save_partial(all_coasters, temp_filename)
                print(f"Guardado parcial: {temp_filename} ({len(all_coasters):,} coasters)")
            
            # Pausa para no saturar
            time.sleep(0.2)
        
        print()
        print()
        print("=" * 70)
        print("DESCARGA COMPLETADA")
        print("=" * 70)
        print(f"Coasters encontradas: {self.successful:,}")
        print(f"IDs no encontrados: {self.not_found:,}")
        print(f"Total procesado: {total_ids:,}")
        print(f"Tiempo total: {(time.time() - start_time) / 60:.1f} minutos")
        print("=" * 70)
        
        return all_coasters
    
    def _save_partial(self, data: List[Dict[str, Any]], filename: str):
        """
        Guarda un archivo parcial sin estadísticas completas
        """
        output = {
            'metadata': {
                'source': 'RCDB.com',
                'scraper': 'RCDB Ultimate Scraper',
                'partial': True,
                'download_date': datetime.now().isoformat(),
                'total_coasters': len(data)
            },
            'coasters': data
        }
        
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(output, f, ensure_ascii=False, indent=2)
    
    def find_last_id(self) -> int:
        """
        Intenta encontrar el último ID válido de RCDB
        """
        print("Buscando el último ID de RCDB...")
        
        # Probar IDs altos de forma binaria
        low = 10000
        high = 30000
        last_valid = low
        
        while low <= high:
            mid = (low + high) // 2
            print(f"   Probando ID {mid}...", end=" ", flush=True)
            
            coaster = self.get_coaster_by_id(mid)
            
            if coaster:
                print("Existe")
                last_valid = mid
                low = mid + 1
            else:
                print("No existe")
                high = mid - 1
            
            time.sleep(0.3)
        
        # Buscar hacia adelante desde el último válido
        print(f"\nRefinando desde {last_valid}...")
        test_id = last_valid
        
        while test_id < last_valid + 1000:
            coaster = self.get_coaster_by_id(test_id)
            if coaster:
                last_valid = test_id
                print(f"   ID {test_id}: Check")
            test_id += 50
            time.sleep(0.2)
        
        print(f"\nÚltimo ID encontrado: {last_valid}")
        return last_valid


def save_to_json(data: List[Dict[str, Any]], filename: str = "rcdb_complete_by_id.json"):
    """
    Guarda el archivo final con estadísticas completas
    """
    # Estadísticas
    countries = {}
    years = {}
    statuses = {}
    types = {}
    makes = {}
    
    for c in data:
        # Países
        country = c.get('country', 'Unknown')
        countries[country] = countries.get(country, 0) + 1
        
        # Años
        year = c.get('year', 'Unknown')
        years[year] = years.get(year, 0) + 1
        
        # Estados
        status = c.get('status', 'Unknown')
        statuses[status] = statuses.get(status, 0) + 1
        
        # Tipos
        tipo = c.get('type', 'Unknown')
        types[tipo] = types.get(tipo, 0) + 1
        
        # Fabricantes
        make = c.get('make', 'Unknown')
        if make and make != 'Unknown':
            makes[make] = makes.get(make, 0) + 1
    
    output = {
        'metadata': {
            'source': 'RCDB.com',
            'scraper': 'RCDB Ultimate Scraper by ID',
            'download_date': datetime.now().isoformat(),
            'total_coasters': len(data),
            'statistics': {
                'total_countries': len(countries),
                'total_years': len([y for y in years.keys() if y != 'Unknown']),
                'top_10_countries': dict(sorted(countries.items(), key=lambda x: x[1], reverse=True)[:10]),
                'top_10_manufacturers': dict(sorted(makes.items(), key=lambda x: x[1], reverse=True)[:10]),
                'top_10_types': dict(sorted(types.items(), key=lambda x: x[1], reverse=True)[:10]),
                'status_distribution': statuses
            }
        },
        'coasters': data
    }
    
    with open(filename, 'w', encoding='utf-8') as f:
        json.dump(output, f, ensure_ascii=False, indent=2)
    
    print(f"\nGUARDADO FINAL: '{filename}'")
    

def main():
    """
    Menú principal
    """
    print("\n" + "=" * 70)
    print("RCDB SCRAPER - Descarga por ID")
    print("=" * 70)
    
    scraper = RCDBScraper()
    
    print("\nOPCIONES DE DESCARGA:")
    print()
    print("1. COMPLETA: Desde ID #1 hasta encontrar el último (~15-30 min)")
    print("2. RÁPIDA: Desde ID #1 hasta #10000 (~30-50 min)")
    print("3. ULTRA RÁPIDA: Solo últimos 1000 IDs (~5 min)")
    print("4. PERSONALIZADA: Tú eliges el rango de IDs")
    print("5. BUSCAR: Encontrar cuál es el último ID de RCDB")
    
    choice = input("\nOpción (1-5): ").strip()
    
    coasters = []
    filename = "rcdb_complete.json"
    
    if choice == "1":
        print("\nADVERTENCIA:")
        print("   • Esto descargará TODA la base de datos de RCDB")
        print("   • Tardará entre +90 minutos")
        print("   • Se guardarán backups cada 500 IDs")
        print()
        
        # Primero buscar el último ID
        last_id = scraper.find_last_id()
        print()
        
        confirm = input(f"¿Descargar desde ID #1 hasta #{last_id}? (s/n): ").strip().lower()
        if confirm in ['s', 'si', 'yes', 'y']:
            coasters = scraper.download_by_id_range(1, last_id, save_interval=500)
            filename = f"rcdb_complete_1_{last_id}.json"
        else:
            return
    
    elif choice == "2":
        print("\nEsto descargará ~10,000 IDs")
        confirm = input("¿Continuar? (s/n): ").strip().lower()
        if confirm in ['s', 'si', 'yes', 'y']:
            coasters = scraper.download_by_id_range(1, 10000, save_interval=500)
            filename = "rcdb_1_10000.json"
        else:
            return
    
    elif choice == "3":
        # Buscar el último ID y descargar los últimos 1000
        last_id = scraper.find_last_id()
        start_id = max(1, last_id - 1000)
        print(f"\nDescargando IDs #{start_id} hasta #{last_id}")
        coasters = scraper.download_by_id_range(start_id, last_id, save_interval=200)
        filename = f"rcdb_{start_id}_{last_id}.json"
    
    elif choice == "4":
        start = int(input("ID inicial: ").strip())
        end = int(input("ID final: ").strip())
        interval = int(input("Guardar cada cuántos IDs (500): ").strip() or "500")
        coasters = scraper.download_by_id_range(start, end, save_interval=interval)
        filename = f"rcdb_{start}_{end}.json"
    
    elif choice == "5":
        last_id = scraper.find_last_id()
        print(f"\nEl último ID de RCDB es aproximadamente: #{last_id}")
        return
    
    else:
        print("Opción no válida")
        return
    
    if coasters:
        save_to_json(coasters, filename)
        
        
    else:
        print("\nNo se obtuvieron resultados")


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\nDescarga interrumpida por el usuario")
        print("Los archivos parciales se han guardado")
        sys.exit(0)

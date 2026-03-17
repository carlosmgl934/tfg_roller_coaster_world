import psycopg2
import requests
import re
import time

# ── Conexión a la BD ──────────────────────────────────────────────────────────
conn = psycopg2.connect(
    host="aws-1-eu-central-1.pooler.supabase.com",
    database="postgres",
    user="postgres.ubtoaaawqdneblyvbelr",
    password="2026.%Mdzca",
    port=5432
)
cursor = conn.cursor()

HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36"
}

def get_html(url):
    try:
        res = requests.get(url, headers=HEADERS, timeout=15, allow_redirects=True)
        return {"html": res.text, "code": res.status_code, "url": str(res.url)}
    except Exception as e:
        print(f"  Error en GET {url}: {e}")
        return {"html": "", "code": 0, "url": str(url)}

# ── Obtener parques sin imagen o sin año ──────────────────────────────────────
cursor.execute("""
    SELECT id, park_name, park_country FROM parks
    WHERE imagen_url IS NULL OR opening_year IS NULL
    ORDER BY id
""")
parks = cursor.fetchall()

print(f"Total a procesar (por lote): {len(parks)}")

for park in parks:
    park_id, name, country = park
    print(f"\n[{park_id}] {name} ({country}): ", end="")

    search_url = f"https://rcdb.com/qs.htm?qs={requests.utils.quote(name)}"
    res_search = get_html(search_url)

    park_html = ""
    park_url = ""

    # Si RCDB redirige directamente al parque (único resultado)
    if "/qs.htm" not in str(res_search["url"]) and re.search(r"rcdb\.com/\d+\.htm", str(res_search["url"])):
        park_html = res_search["html"]
        park_url = res_search["url"]
        print(f"Redirección directa a {park_url} ... ", end="")
    else:
        # Página de resultados — buscar el primer link que coincida con el nombre
        matches = re.findall(r'<a href="(/(\d+)\.htm)">(.*?)</a>', str(res_search["html"]))
        found = False
        for match in matches:
            link_path, _, link_text = str(match[0]), str(match[1]), str(match[2])
            if str(name).lower() in link_text.lower():
                park_url = "https://rcdb.com" + link_path
                print(f"Encontrado en resultados {park_url} ... ", end="")
                res_park = get_html(park_url)
                park_html = res_park["html"]
                found = True
                break

        if not found:
            print("No encontrado en la búsqueda. ", end="")
            cursor.execute("UPDATE parks SET imagen_url = '', opening_year = 0 WHERE id = %s", (park_id,))
            conn.commit()
            print("=> BD salteado")
            continue

    image_url = ""
    m = re.search(r'id=[\'"]?opfAnchor[\'"]?.*?data-url=[\'"]?([^\s>]+)[\'"]?', str(park_html), re.IGNORECASE | re.DOTALL)
    if m:
        img_path = str(m.group(1))
        image_url = str("https://rcdb.com" + img_path)
        # Cambiar última letra por 'a' para máxima resolución SÓLO si es la ruta alfabética hash de RCDB
        if re.match(r'^/[a-zA-Z0-9]+$', img_path):
            img_path = img_path[:-1] + "a"
        image_url = str("https://rcdb.com" + img_path)
    else:
        m = re.search(r'<meta property="og:image"\s+content="([^"]*)"', str(park_html), re.IGNORECASE)
        if m:
            image_url = m.group(1)

    # ── Extraer año de apertura ───────────────────────────────────────────────
    opening_year = None
    m = re.search(r'<time datetime="(\d{4})', str(park_html), re.IGNORECASE)
    if m:
        opening_year = int(m.group(1))
    else:
        m = re.search(r'Opened(?:\s|<[^>]+>)*(\d{4})', str(park_html), re.IGNORECASE | re.DOTALL)
        if m:
            opening_year = int(m.group(1))

    # ── Contar coasters desde BD (SOLO OPERATIVAS) ─────────────────────────
    cursor.execute("""
        SELECT COUNT(*) FROM coasters
        WHERE park_id = %s AND coaster_status IN ('Operating', 'Operativa', 'Abierta')
    """, (park_id,))
    num_operating = cursor.fetchone()[0]
    num_coasters = num_operating

    img_status = "SI" if image_url != "" else "NO"
    year_status = opening_year if opening_year else "NO"
    print(f"[Img: {img_status}] [Año: {year_status}] ", end="")

    # ── Actualizar BD ─────────────────────────────────────────────────────────
    cursor.execute("""
        UPDATE parks
        SET imagen_url = %s,
            opening_year = %s,
            num_coasters = %s,
            operating_coasters = %s
        WHERE id = %s
    """, (
        image_url if image_url != "" else '',
        opening_year or 0,
        num_coasters,
        num_operating,
        park_id
    ))
    conn.commit()
    print("=> BD Actualizada")

    time.sleep(0.1)

cursor.close()
conn.close()
print("\n¡Proceso FINALIZADO por completo!")

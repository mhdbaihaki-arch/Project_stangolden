import re, json, sys

sys.stdout.reconfigure(encoding='utf-8')

with open("STANGOLDEN_Latihan_Etika_PNS_4-13_V5_Bahasa_Ujian_100_Soal_FULL_PHP73.php", "r", encoding="utf-8") as f:
    text = f.read()

m = re.search(r'const\s+questions\s*=\s*(\[\s*\{.*?\}\s*\]);', text, re.DOTALL)
if m:
    raw_js = m.group(1)
    qs = json.loads(raw_js)
    print(f"Total questions in PHP: {len(qs)}")
    sources = set()
    for q in qs:
        src = q.get('source', '')
        sources.add(src)
    print("Sources found:", sorted(list(sources)))
    # Check page distribution
    pages_count = {}
    for q in qs:
        src = q.get('source', '')
        # extract page number
        m_p = re.search(r'halaman\s*(\d+)', src, re.I)
        p = int(m_p.group(1)) if m_p else -1
        pages_count[p] = pages_count.get(p, 0) + 1
    print("Questions per page:", sorted(pages_count.items()))

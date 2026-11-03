import json, re, sys

sys.stdout.reconfigure(encoding='utf-8')

with open("modul_json/1. Modul_Etika PNS_after UAT.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

modul_pages = {p["page"]: p["text"] for p in modul["pages"]}

with open("STANGOLDEN_Latihan_Etika_PNS_4-13_V5_Bahasa_Ujian_100_Soal_FULL_PHP73.php", "r", encoding="utf-8") as f:
    php_text = f.read()

m = re.search(r'const\s+questions\s*=\s*(\[\s*\{.*?\}\s*\]);', php_text, re.DOTALL)
qs = json.loads(m.group(1))

print(f"Total questions in PHP: {len(qs)}")

# Let's inspect questions 1 to 10 (Page 4)
for idx, q in enumerate(qs[:10], 1):
    src = q.get('source', '')
    m_p = re.search(r'halaman\s*(\d+)', src, re.I)
    p_num = int(m_p.group(1)) if m_p else 4
    txt_page = modul_pages.get(p_num, "")
    
    # Check if answer or question phrases appear on page
    kunci = q['options'][q['answer']]
    pertanyaan = q['question']
    
    # extract words from question to see overlap
    print(f"Q{idx} (Hlm {p_num}) - Kunci: {kunci[:40]}")
    print(f"   Pertanyaan: {pertanyaan[:70]}")

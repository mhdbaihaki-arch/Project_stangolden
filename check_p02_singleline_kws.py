import json
import re

with open('lines_tak_p02.txt', 'r', encoding='utf-8') as f:
    lines_text = f.read()

pages_raw = re.split(r'=== HALAMAN (\d+) ===', lines_text)
page_lines = {}
for i in range(1, len(pages_raw), 2):
    pnum = int(pages_raw[i])
    lines = [re.sub(r'^\[\d+\]\s*', '', l).strip() for l in pages_raw[i+1].strip().splitlines()]
    page_lines[pnum] = [l for l in lines if l]

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_02.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

print(f"Loaded {len(data['daftar_soal'])} questions.")

# Let's see current keywords and whether they are found
misses = []
for q in data['daftar_soal']:
    num = q['nomor']
    hlm = q['halaman_sumber']
    pl = page_lines.get(hlm, [])
    full_p = " ".join(pl)
    for kw in q.get('kata_kunci_modul', []):
        # check if kw is in any single line
        found_line = any(kw in l for l in pl)
        if not found_line:
            misses.append((num, hlm, kw, any(kw in full_p for l in [1])))

print(f"Total keyword misses on single lines: {len(misses)}")
for m in misses[:20]:
    print(f"Q{m[0]} (Hlm {m[1]}): '{m[2]}' (in full page: {m[3]})")

import json
from collections import Counter

with open('soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

soal_list = data['daftar_soal']
page_counts = Counter(q['halaman_sumber'] for q in soal_list)
print("Page distribution:")
for p in sorted(page_counts.keys()):
    nos = [q['nomor'] for q in soal_list if q['halaman_sumber'] == p]
    print(f"Halaman {p}: {len(nos)} soal (No {min(nos)}-{max(nos)})")

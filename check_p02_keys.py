import json
from collections import Counter

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_02.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

qs = data['daftar_soal']
key_counts = Counter(q['kunci_jawaban'] for q in qs)
print(f"Key counts: {key_counts}")

by_page = {}
for q in qs:
    by_page.setdefault(q['halaman_sumber'], []).append(q['kunci_jawaban'])

for p, keys in sorted(by_page.items()):
    print(f"Page {p} (count {len(keys)}): {Counter(keys)} -> {keys}")

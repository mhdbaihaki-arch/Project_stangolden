import json

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_02.json', 'r', encoding='utf-8') as f:
    d2 = json.load(f)

soals = {q['nomor']: q for q in d2['daftar_soal']}

p2_flagged = [1, 4, 12, 13, 14, 17, 30, 37, 40, 44, 50, 51, 52, 57, 59, 61, 63, 65, 70, 71, 73, 76, 77, 80, 82, 89, 90, 91, 97]

by_page = {}
for n in p2_flagged:
    p = soals[n]['halaman_sumber']
    by_page.setdefault(p, []).append(n)

print("Paket 2 flagged grouped by page:")
for p, nums in sorted(by_page.items()):
    print(f"Halaman {p}: soal {nums}")


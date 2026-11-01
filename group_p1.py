import json

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_01.json', 'r', encoding='utf-8') as f:
    d1 = json.load(f)

soals = {q['nomor']: q for q in d1['daftar_soal']}

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)
page_texts = {p['page']: p['text'] for p in modul['pages']}

p1_flagged = [4, 5, 8, 11, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 24, 25, 26, 27, 28, 35, 36, 38, 41, 47, 49, 60, 61, 63, 64, 65, 67, 70, 71, 73, 74, 77, 79, 82, 87, 90, 97, 98, 99]

# Group by page
by_page = {}
for n in p1_flagged:
    p = soals[n]['halaman_sumber']
    by_page.setdefault(p, []).append(n)

print("Paket 1 flagged grouped by page:")
for p, nums in sorted(by_page.items()):
    print(f"Halaman {p}: soal {nums}")


import json

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_03.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

meta_patterns = [
    "dalam materi", "menurut modul", "buku modul", "buku teks", "tabel 1", "tabel 2",
    "kata kunci modul", "penjelasan modul", "pada bab", "pada halaman", "bahan ajar",
    "sebagaimana tertulis dalam materi", "tertulis dalam materi", "pada subbab", "diktat"
]

for q in data['daftar_soal']:
    num = q['nomor']
    full_str = json.dumps(q, ensure_ascii=False).lower()
    for m in meta_patterns:
        if m in full_str:
            print(f"Q{num}: found '{m}'")

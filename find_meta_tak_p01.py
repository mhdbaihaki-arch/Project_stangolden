import json

target_file = 'soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_01.json'
with open(target_file, 'r', encoding='utf-8') as f:
    data = json.load(f)

meta_patterns = [
    "dokumen modul", "catatan modul", "catatan sejarah modul",
    "uraian materi", "buku materi", "dalam materi ini", "penjelasan modul"
]

for q in data['daftar_soal']:
    num = q['nomor']
    full_str = json.dumps(q, ensure_ascii=False).lower()
    for m in meta_patterns:
        if m in full_str:
            print(f"Q{num}: found '{m}'")
            print("  alasan:", q['pembahasan']['alasan_kunci'])

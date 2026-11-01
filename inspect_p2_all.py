import json
import re

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_02.json', 'r', encoding='utf-8') as f:
    d2 = json.load(f)

soals = {q['nomor']: q for q in d2['daftar_soal']}
p2_flagged = [1, 4, 12, 13, 14, 17, 30, 37, 40, 44, 50, 51, 52, 57, 59, 61, 63, 65, 70, 71, 73, 76, 77, 80, 82, 89, 90, 91, 97]

for n in p2_flagged:
    q = soals[n]
    print(f"=== NOMOR {n} (Hlm {q['halaman_sumber']}, Kunci {q['kunci_jawaban']}) ===")
    print("Q:", q['pertanyaan'])
    for opt, txt in q['pilihan'].items():
        print(f"  {opt} ({len(txt)} ch, {len(txt.split())} w): {txt}")
    print("Alasan:", q['pembahasan']['alasan_kunci'])
    print()

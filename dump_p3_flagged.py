import json
import re

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_03.json', 'r', encoding='utf-8') as f:
    paket3 = json.load(f)

soals = {q['nomor']: q for q in paket3['daftar_soal']}

p3_flagged = [4, 6, 7, 9, 11, 15, 18, 20, 22, 23, 25, 27, 28, 29, 35, 36, 39, 44, 48, 49, 50, 58, 59, 61, 62, 64, 65, 69, 71, 76, 79, 80, 81, 86, 89, 91, 92, 93, 94, 95, 96, 97, 99]

with open('p3_inspected.txt', 'w', encoding='utf-8') as out:
    for n in p3_flagged:
        q = soals[n]
        kunci = q['kunci_jawaban']
        alasan = q['pembahasan']['alasan_kunci']
        pert = q['pertanyaan']
        pilihan = q['pilihan']
        
        out.write(f"=== NOMOR {n} (Hlm {q['halaman_sumber']}, Kunci {kunci}) ===\n")
        out.write(f"Q ({len(pert.split())} w): {pert}\n")
        lens = []
        for opt, txt in pilihan.items():
            lens.append(len(txt))
            out.write(f"  {opt} ({len(txt)} ch, {len(txt.split())} w): {txt}\n")
        out.write(f"  Min len: {min(lens)}, Max len: {max(lens)}, Diff: {max(lens)-min(lens)}\n")
        out.write(f"Alasan: {alasan}\n\n")

print("Saved p3_inspected.txt")

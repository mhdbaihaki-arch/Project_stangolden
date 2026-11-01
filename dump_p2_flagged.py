import json

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_02.json', 'r', encoding='utf-8') as f:
    d2 = json.load(f)

soals = {q['nomor']: q for q in d2['daftar_soal']}

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)
page_texts = {p['page']: p['text'] for p in modul['pages']}

p2_flagged = [1, 4, 12, 13, 14, 17, 30, 37, 40, 44, 50, 51, 52, 57, 59, 61, 63, 65, 70, 71, 73, 76, 77, 80, 82, 89, 90, 91, 97]

with open('dump_p2.txt', 'w', encoding='utf-8') as out:
    for n in p2_flagged:
        q = soals[n]
        out.write(f"==================== NOMOR {n} (Hlm {q['halaman_sumber']}, Kunci {q['kunci_jawaban']}) ====================\n")
        out.write(f"Topik: {q['topik_materi']}\n")
        out.write(f"Kata kunci: {q.get('kata_kunci_modul')}\n")
        out.write(f"Pertanyaan ({len(q['pertanyaan'].split())} w): {q['pertanyaan']}\n")
        for k, v in q['pilihan'].items():
            out.write(f"  {k} ({len(v)} ch, {len(v.split())} w): {v}\n")
        out.write(f"Konsep: {q['pembahasan']['konsep_materi']}\n")
        out.write(f"Alasan: {q['pembahasan']['alasan_kunci']}\n")
        out.write("Bedah:\n")
        for k, v in q['pembahasan']['bedah_pengecoh'].items():
            out.write(f"  {k}: {v}\n")
        out.write("\n")

print("Saved dump_p2.txt")


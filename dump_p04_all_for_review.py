import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json", "r", encoding="utf-8") as f:
    pkg = json.load(f)

with open("p04_all_questions_review.txt", "w", encoding="utf-8") as out:
    for q in pkg["daftar_soal"]:
        out.write(f"=== Q{q['nomor']} (Hlm {q.get('halaman_sumber')}) Key: {q['kunci_jawaban']} ===\n")
        out.write(f"Topik: {q.get('topik_materi')}\n")
        out.write(f"KW: {q.get('kata_kunci_modul')}\n")
        out.write(f"Q: {q['pertanyaan']}\n")
        for k, v in q["pilihan"].items():
            mark = "(*)" if k == q["kunci_jawaban"] else "   "
            out.write(f"  {mark} [{k}] {v}\n")
        out.write("\n")

print("Dumped 100 questions of Paket 04 to p04_all_questions_review.txt")

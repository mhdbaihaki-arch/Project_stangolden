import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json", "r", encoding="utf-8") as f:
    data = json.load(f)

qs = data["daftar_soal"]

with open("p02_all_questions_review.txt", "w", encoding="utf-8") as out:
    for q in qs:
        out.write(f"=== Q{q['nomor']} (Hlm {q.get('halaman_sumber')}) Key: {q['kunci_jawaban']} ===\n")
        out.write(f"Topik: {q.get('topik_materi')}\n")
        out.write(f"KW: {q.get('kata_kunci_modul')}\n")
        out.write(f"Q: {q['pertanyaan']}\n")
        for k, v in q["pilihan"].items():
            mark = "(*)" if k == q["kunci_jawaban"] else "   "
            out.write(f"  {mark} [{k}] {v}\n")
        out.write("\n")

print(f"Written {len(qs)} questions to p02_all_questions_review.txt")

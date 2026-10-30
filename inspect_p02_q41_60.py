import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json", "r", encoding="utf-8") as f:
    qs = json.load(f)["daftar_soal"]

for q in qs[40:60]:
    print(f"Q{q['nomor']} (Hlm {q.get('halaman_sumber')}) [{q['kunci_jawaban']}]: {q['pertanyaan']}")
    for k, v in q["pilihan"].items():
        print(f"   {k}: {v}")
    print()

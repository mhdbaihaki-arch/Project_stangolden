import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json", "r", encoding="utf-8") as f:
    data = json.load(f)

questions = data["daftar_soal"]

for q in questions:
    print(f"--- Q{q['nomor']} (Hlm {q.get('halaman_sumber')}) Key: {q['kunci_jawaban']} ---")
    print("Q:", q["pertanyaan"])
    for k, v in q["pilihan"].items():
        print(f"  {k}: {v}")

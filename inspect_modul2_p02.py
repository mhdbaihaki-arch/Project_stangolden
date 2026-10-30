import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json", "r", encoding="utf-8") as f:
    data = json.load(f)

print(f"Paket: {data.get('paket_nomor')}, Dokumen: {data.get('dokumen_sumber')}, Rentang: {data.get('rentang_halaman')}")
questions = data["daftar_soal"]
print(f"Total questions: {len(questions)}")

# Let's inspect first 10 questions and look at their options
for q in questions[:10]:
    print(f"\nNo. {q['nomor']} (Hlm {q.get('halaman_sumber')}): {q['pertanyaan']}")
    print(f"Kunci: {q['kunci_jawaban']}")
    for k, v in q['pilihan'].items():
        print(f"  {k}: {v}")

import json

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_02.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

flagged_nums = [10, 13, 20, 22, 25, 70, 78, 79, 82]
for q in data['daftar_soal']:
    if q['nomor'] in flagged_nums:
        print(f"Q{q['nomor']} (Hlm {q['halaman_sumber']}) [Kunci: {q['kunci_jawaban']}]")
        print(f"  Q: {q['pertanyaan']}")
        for opt, txt in q['pilihan'].items():
            print(f"    {opt}: {txt}")
        print()

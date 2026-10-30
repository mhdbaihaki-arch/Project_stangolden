import json

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_06.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

targets = [6, 18, 19, 26, 27, 30, 41, 57, 64, 76, 87, 93]
for q in data['daftar_soal']:
    if q['nomor'] in targets:
        print(f"=== Q{q['nomor']} (Hlm {q['halaman_sumber']}) Kunci: {q['kunci_jawaban']} ===")
        print("Tanya:", q['pertanyaan'])
        for k, v in q['pilihan'].items():
            print(f"  {k}: {v}")
        print("KW:", q.get('kata_kunci_modul'))

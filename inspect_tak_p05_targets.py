import json

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_05.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

for q in data['daftar_soal']:
    if q['nomor'] in [19, 20]:
        print(f"=== Q{q['nomor']} (Hlm {q['halaman_sumber']}) Kunci: {q['kunci_jawaban']} ===")
        print("Tanya:", q['pertanyaan'])
        for k, v in q['pilihan'].items():
            print(f"  {k}: {v}")
        print("KW:", q.get('kata_kunci_modul'))

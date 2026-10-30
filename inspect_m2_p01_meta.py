import json

with open('soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_01.json', 'r', encoding='utf-8') as f:
    p01 = json.load(f)

for q in p01['daftar_soal']:
    if q['nomor'] in [61, 74]:
        print(f"=== Q{q['nomor']} ===")
        print(q['pembahasan'])

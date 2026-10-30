import json

with open('soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_01.json', 'r', encoding='utf-8') as f:
    p01 = json.load(f)

for q in p01['daftar_soal']:
    if q['nomor'] == 61:
        s = json.dumps(q, indent=2)
        for line in s.split('\n'):
            if 'dalam materi' in line.lower():
                print(line)

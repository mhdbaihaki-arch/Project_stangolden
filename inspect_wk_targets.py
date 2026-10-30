import json

# Inspect Paket 01
with open('soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_01.json', 'r', encoding='utf-8') as f:
    p01 = json.load(f)

for q in p01['daftar_soal']:
    if q['nomor'] in [29, 44]:
        print(f"=== P01 Q{q['nomor']} (Hlm {q['halaman_sumber']}) ===")
        print("Tanya:", q['pertanyaan'])
        print("Pilihan:", q['pilihan'])
        print("Pembahasan:", q['pembahasan'])
        print("KW:", q.get('kata_kunci_modul'))

# Inspect Paket 02
with open('soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_02.json', 'r', encoding='utf-8') as f:
    p02 = json.load(f)

for q in p02['daftar_soal']:
    if q['nomor'] in [7, 58]:
        print(f"=== P02 Q{q['nomor']} (Hlm {q['halaman_sumber']}) ===")
        print("Tanya:", q['pertanyaan'])
        print("Pilihan:", q['pilihan'])
        print("Pembahasan:", q['pembahasan'])

# Inspect Paket 03
with open('soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_03.json', 'r', encoding='utf-8') as f:
    p03 = json.load(f)

for q in p03['daftar_soal']:
    if q['nomor'] == 18:
        print(f"=== P03 Q{q['nomor']} (Hlm {q['halaman_sumber']}) ===")
        print("Tanya:", q['pertanyaan'])
        print("Pilihan:", q['pilihan'])
        print("Pembahasan:", q['pembahasan'])

# Inspect Paket 07
with open('soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_07.json', 'r', encoding='utf-8') as f:
    p07 = json.load(f)

for q in p07['daftar_soal']:
    if q['nomor'] == 52:
        print(f"=== P07 Q{q['nomor']} (Hlm {q['halaman_sumber']}) ===")
        print("Tanya:", q['pertanyaan'])
        print("KW:", q.get('kata_kunci_modul'))

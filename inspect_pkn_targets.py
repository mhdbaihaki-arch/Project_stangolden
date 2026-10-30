import json

with open('modul_json/4. Pengelolaan Keuangan Negara.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

# check modul structure
pages_dict = {}
if 'pages' in modul:
    for p in modul['pages']:
        pages_dict[p['page']] = p['text']
elif 'materi' in modul:
    for p in modul['materi']:
        pages_dict[p['halaman']] = p['isi_teks']

print("Loaded PKN pages:", len(pages_dict))

with open('soal_pengelolaan_keuangan_negara_json/4. Pengelolaan Keuangan Negara_paket_03.json', 'r', encoding='utf-8') as f:
    p03 = json.load(f)

for q in p03['daftar_soal']:
    if q['nomor'] in [42, 71, 95]:
        print(f"=== P03 Q{q['nomor']} (Hlm {q['halaman_sumber']}) Kunci: {q['kunci_jawaban']} ===")
        print("Tanya:", q['pertanyaan'])
        for k, v in q['pilihan'].items():
            print(f"  {k}: {v}")
        print("KW:", q.get('kata_kunci_modul'))

with open('soal_pengelolaan_keuangan_negara_json/4. Pengelolaan Keuangan Negara_paket_04.json', 'r', encoding='utf-8') as f:
    p04 = json.load(f)

for q in p04['daftar_soal']:
    if q['nomor'] == 43:
        print(f"=== P04 Q{q['nomor']} (Hlm {q['halaman_sumber']}) Kunci: {q['kunci_jawaban']} ===")
        print("Tanya:", q['pertanyaan'])
        for k, v in q['pilihan'].items():
            print(f"  {k}: {v}")
        print("KW:", q.get('kata_kunci_modul'))

# print lines from Hlm 32, 37, 46
print("\n--- PKN Hlm 32 text lines ---")
for line in pages_dict.get(32, '').split('\n'):
    if 'standar' in line.lower() or 'internasional' in line.lower():
        print(repr(line.strip()))

print("\n--- PKN Hlm 37 text lines ---")
for line in pages_dict.get(37, '').split('\n'):
    if 'perbendaharaan' in line.lower() or 'uu nomor 1' in line.lower():
        print(repr(line.strip()))

print("\n--- PKN Hlm 46 text lines ---")
for line in pages_dict.get(46, '').split('\n'):
    if 'harmonisasi' in line.lower() or 'uu nomor 7' in line.lower():
        print(repr(line.strip()))

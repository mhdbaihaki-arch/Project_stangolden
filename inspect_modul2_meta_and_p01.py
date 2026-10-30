import json

with open('modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

print("Modul 2 keys:", list(modul.keys()))
pages_dict = {}
if 'pages' in modul:
    for p in modul['pages']:
        pages_dict[p['page']] = p['text']
elif 'materi' in modul:
    for p in modul['materi']:
        pages_dict[p['halaman']] = p['isi_teks']

print("Modul 2 pages:", len(pages_dict))
print("Available pages sample:", list(pages_dict.keys())[:15])

# inspect Paket 01 Q1-10
with open('soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_01.json', 'r', encoding='utf-8') as f:
    p01 = json.load(f)

print("\n--- Paket 01 sample questions ---")
for q in p01['daftar_soal'][:5]:
    hlm = q['halaman_sumber']
    kw = q.get('kata_kunci_modul', [])
    print(f"Q{q['nomor']} Hlm {hlm} KW: {kw}")
    txt = pages_dict.get(hlm, '')
    print(f"Found on Hlm {hlm}? {[k in txt for k in kw]}")
    # let's search if kw is on another page
    for k in kw:
        for pg, pg_txt in pages_dict.items():
            if k in pg_txt:
                print(f"  -> '{k}' found on page {pg}!")

# inspect meta hits across all 4 packets
for pno in range(1, 5):
    path = f'soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_{pno:02d}.json'
    with open(path, 'r', encoding='utf-8') as f:
        pdata = json.load(f)
    print(f"\n--- Modul 2 Paket {pno} Meta Hits ---")
    for q in pdata['daftar_soal']:
        full = q['pertanyaan'] + ' ' + json.dumps(q['pilihan']) + ' ' + json.dumps(q['pembahasan'])
        for mp in ['dalam materi', 'sebagaimana tertulis']:
            if mp in full.lower():
                print(f"Q{q['nomor']} (Hlm {q['halaman_sumber']}): {mp}")
                print(f"   Pembahasan: {q['pembahasan']}")

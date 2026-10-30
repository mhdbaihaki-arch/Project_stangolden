import json

with open('modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

page_texts = {}
for p in modul['pages']:
    page_texts[int(p['page'])] = p['text']

with open('soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json', 'r', encoding='utf-8') as f:
    soal_data = json.load(f)

soal_list = soal_data['daftar_soal']

misses = []
for q in soal_list:
    no = q['nomor']
    hlm = q['halaman_sumber']
    kws = q.get('kata_kunci_modul', [])
    page_text = page_texts.get(hlm, "")
    
    for kw in kws:
        if kw not in page_text:
            misses.append((no, hlm, kw))

print(f"Total keyword misses: {len(misses)}")
for m in misses:
    print(f"No {m[0]} (Hlm {m[1]}): '{m[2]}' NOT FOUND in page {m[1]}")

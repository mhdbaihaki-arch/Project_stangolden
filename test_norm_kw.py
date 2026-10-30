import json, re

with open('modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

pages_dict = {}
for p in modul['pages']:
    # normalize whitespace in page text
    norm_txt = ' '.join(p['text'].split())
    pages_dict[p['page']] = norm_txt

with open('soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_01.json', 'r', encoding='utf-8') as f:
    p01 = json.load(f)

misses = []
for q in p01['daftar_soal']:
    hlm = q['halaman_sumber']
    norm_txt = pages_dict.get(hlm, '')
    for kw in q.get('kata_kunci_modul', []):
        norm_kw = ' '.join(kw.split())
        if norm_kw not in norm_txt:
            misses.append((q['nomor'], hlm, kw))

print(f"Total keyword misses when whitespace is normalized: {len(misses)}")
for m in misses[:10]:
    print(m)

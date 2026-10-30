import json, glob, os

with open('modul_json/5. Wawasan Kebangsaan.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

pages_dict = {}
for p in modul['pages']:
    pages_dict[p['page']] = p['text']

for jf in sorted(glob.glob('soal_wawasan_kebangsaan_json/*.json')):
    base = os.path.basename(jf)
    with open(jf, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    raw_misses = 0
    norm_misses = 0
    for q in data['daftar_soal']:
        hlm = q['halaman_sumber']
        txt = pages_dict.get(hlm, '')
        norm_txt = ' '.join(txt.split())
        for kw in q.get('kata_kunci_modul', []):
            if kw not in txt:
                raw_misses += 1
            if ' '.join(kw.split()) not in norm_txt:
                norm_misses += 1
                print(f"[{base}] Q{q['nomor']} Hlm {hlm} TRUE MISS: {repr(kw)}")
    print(f"{base}: Raw misses: {raw_misses} | Normalized misses: {norm_misses}")

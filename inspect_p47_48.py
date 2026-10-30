import json

with open('modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

for p in modul['pages']:
    if int(p['page']) in [47, 48]:
        print(f"=== PAGE {p['page']} ===")
        print(repr(p['text']))

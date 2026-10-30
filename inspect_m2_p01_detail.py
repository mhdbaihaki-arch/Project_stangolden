import json

with open('modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

for p in modul['pages']:
    if p['page'] in [5, 6, 7]:
        print(f"=== PAGE {p['page']} ===")
        print(p['text'])

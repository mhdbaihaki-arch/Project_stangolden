import json

with open('modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

print("Page 0 keys:", modul['pages'][0].keys())
print("Page 0 sample:", {k: str(v)[:50] for k, v in modul['pages'][0].items()})

import json

with open("modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

print("Modul keys:", list(modul.keys()) if isinstance(modul, dict) else len(modul))
if isinstance(modul, dict):
    for k in list(modul.keys())[:5]:
        print(f"Key {k}: {str(modul[k])[:100]}...")
elif isinstance(modul, list):
    for item in modul[:3]:
        print(item)

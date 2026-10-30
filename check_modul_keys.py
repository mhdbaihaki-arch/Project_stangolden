import json

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

print(list(modul.keys()))
if 'materi' in modul:
    print(len(modul['materi']))
    print(list(modul['materi'][0].keys()))

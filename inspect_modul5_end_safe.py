import json

with open('modul_json/5. Wawasan Kebangsaan.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

pages_dict = {p['page']: p['text'] for p in modul.get('pages', [])}

for pg in range(75, 80):
    print(f"\n==================== PAGE {pg} ====================")
    txt = pages_dict.get(pg, 'NOT FOUND')
    print(txt.encode('ascii', errors='replace').decode('ascii'))

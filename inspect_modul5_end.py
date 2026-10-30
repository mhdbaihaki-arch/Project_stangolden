import json

with open('modul_json/5. Wawasan Kebangsaan.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

print("Metadata:", modul.get('metadata'))
print("Excluded pages summary:", modul.get('excluded_pages_summary'))
print("\nChapters:")
for ch in modul.get('chapters', []):
    print(" ", ch)

pages_dict = {p['page']: p['text'] for p in modul.get('pages', [])}

for pg in range(75, 80):
    print(f"\n==================== PAGE {pg} ====================")
    print(pages_dict.get(pg, 'NOT FOUND'))

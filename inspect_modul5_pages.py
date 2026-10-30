import json

with open('modul_json/5. Wawasan Kebangsaan.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

print("Modul 5 title/keys:", list(modul.keys()))
pages = modul.get('pages', [])
print("Total pages in JSON:", len(pages))
if pages:
    page_numbers = [p['page'] for p in pages]
    print(f"Min page: {min(page_numbers)}, Max page: {max(page_numbers)}")

# Inspect last pages
print("\n--- Last 15 pages in Modul 5 JSON ---")
for p in pages[-15:]:
    pg = p['page']
    txt = p['text'].strip()
    first_line = txt.split('\n')[0] if txt else 'EMPTY'
    length = len(txt)
    print(f"Page {pg:02d}: length={length} chars | First line: {first_line[:60]}")

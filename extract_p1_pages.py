import json

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

page_texts = {p['page']: p['text'] for p in modul['pages']}

pages_needed = [7, 9, 10, 11, 12, 13, 15, 17, 19, 20]

with open('p1_source_pages.txt', 'w', encoding='utf-8') as out:
    for p in pages_needed:
        out.write(f"\n==================== HALAMAN {p} ====================\n")
        out.write(page_texts.get(p, "PAGE NOT FOUND") + "\n")

print("Saved p1_source_pages.txt")


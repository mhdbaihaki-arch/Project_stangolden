import json

with open("modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

for p in modul["pages"]:
    page_num = p["page"]
    if 15 <= page_num <= 24:
        first_few = " ".join(p["text"].split()[:25])
        print(f"Halaman {page_num} ({p['word_count']} kata): {first_few}...")

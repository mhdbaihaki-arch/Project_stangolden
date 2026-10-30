import json

with open("modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

with open("dump_hlm25_34.txt", "w", encoding="utf-8") as out:
    for p in modul["pages"]:
        if 25 <= p["page"] <= 34:
            out.write(f"=== HALAMAN {p['page']} ===\n")
            out.write(p["text"] + "\n\n")

print("Dumped pages 25-34 to dump_hlm25_34.txt")

import json

with open("modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

with open("dump_hlm35_48.txt", "w", encoding="utf-8") as out:
    for p in modul["pages"]:
        if 35 <= p["page"] <= 48:
            out.write(f"=== HALAMAN {p['page']} ===\n")
            out.write(p["text"] + "\n\n")

print("Dumped pages 35-48 to dump_hlm35_48.txt")

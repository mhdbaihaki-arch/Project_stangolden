import json

with open("modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

with open("dump_hlm15_24.txt", "w", encoding="utf-8") as out:
    for p in modul["pages"]:
        if 15 <= p["page"] <= 24:
            out.write(f"=== HALAMAN {p['page']} ===\n")
            out.write(p["text"] + "\n\n")

print("Dumped pages 15-24 to dump_hlm15_24.txt")

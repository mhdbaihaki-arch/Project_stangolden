import json

# Load current Paket 02
with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json", "r", encoding="utf-8") as f:
    pkg = json.load(f)

# Load textbook to verify exact verbatim matches
with open("modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

modul_pages = {p["page"]: p["text"] for p in modul["pages"]}

print("Package loaded successfully, questions count:", len(pkg["daftar_soal"]))

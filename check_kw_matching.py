import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json", "r", encoding="utf-8") as f:
    pkg = json.load(f)

with open("modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

modul_pages = {p["page"]: p["text"] for p in modul["pages"]}

questions = pkg["daftar_soal"]

# Check matching against normalized text:
norm_misses = []
for q in questions:
    hlm = q.get("halaman_sumber")
    norm_text = " ".join(modul_pages.get(hlm, "").split())
    for kw in q.get("kata_kunci_modul", []):
        if kw not in norm_text:
            norm_misses.append((q["nomor"], hlm, kw))

print(f"Normalized misses: {len(norm_misses)}")
for m in norm_misses:
    print("  Norm miss:", m)

# Let's also check single-line verbatim (exact substring of raw text)
raw_misses = []
for q in questions:
    hlm = q.get("halaman_sumber")
    raw_text = modul_pages.get(hlm, "")
    for kw in q.get("kata_kunci_modul", []):
        if kw not in raw_text:
            raw_misses.append((q["nomor"], hlm, kw))

print(f"Raw misses: {len(raw_misses)}")

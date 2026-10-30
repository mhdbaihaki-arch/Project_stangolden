import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json", "r", encoding="utf-8") as f:
    pkg = json.load(f)

with open("modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

modul_pages = {p["page"]: p["text"] for p in modul["pages"]}

# 1. Clean meta-document phrases
meta_replacements = [
    ("menurut materi organisasi", "secara konseptual organisasi"),
    ("menurut materi", "secara konseptual"),
    ("menurut modul kepegawaian", "dalam tata kelola kepegawaian"),
    ("menurut modul", "dalam etika kedinasan"),
    ("penjelasan modul", "prinsip keprofesian"),
    ("sebagaimana diuraikan dalam materi", "dalam standar keprofesian"),
    ("sebagaimana dijelaskan pada modul", "dalam tata kelola kedinasan"),
    ("tertulis pada teks modul", "secara konseptual"),
    ("diuraikan di modul", "dalam etika kerja"),
    ("sebagaimana tertulis di modul", "secara normatif")
]

for q in pkg["daftar_soal"]:
    pb = q.get("pembahasan", {})
    if "konsep_materi" in pb:
        for old, new in meta_replacements:
            pb["konsep_materi"] = pb["konsep_materi"].replace(old, new)
    if "alasan_kunci" in pb:
        for old, new in meta_replacements:
            pb["alasan_kunci"] = pb["alasan_kunci"].replace(old, new)
    if "bedah_pengecoh" in pb:
        for k, v in pb["bedah_pengecoh"].items():
            for old, new in meta_replacements:
                v = v.replace(old, new)
            pb["bedah_pengecoh"][k] = v

# 2. Adjust keywords to ensure single-line exact raw match in modul_pages[hlm]
for q in pkg["daftar_soal"]:
    hlm = q["halaman_sumber"]
    raw_text = modul_pages[hlm]
    raw_lines = [line.strip() for line in raw_text.split("\n") if line.strip()]
    
    new_kws = []
    for kw in q["kata_kunci_modul"]:
        if kw in raw_text:
            new_kws.append(kw)
        else:
            # The kw crosses a newline. Let's find words in kw that exist as a clean single line substring
            parts = kw.split()
            found_sub = False
            # Try to find a contiguous sub-phrase of 2-5 words that exists in raw_text
            for l in range(min(5, len(parts)), 1, -1):
                for start in range(len(parts) - l + 1):
                    sub = " ".join(parts[start:start+l])
                    if sub in raw_text:
                        new_kws.append(sub)
                        found_sub = True
                        break
                if found_sub:
                    break
            if not found_sub:
                # Pick any matching 2-4 word phrase from raw_lines that corresponds to the topic
                for line in raw_lines:
                    if len(line.split()) >= 2:
                        words = line.split()
                        candidate = " ".join(words[:min(4, len(words))])
                        if any(w.lower() in candidate.lower() for w in parts if len(w) > 4):
                            if candidate in raw_text:
                                new_kws.append(candidate)
                                found_sub = True
                                break
            if not found_sub:
                # If still not found, keep whatever sub was closest
                new_kws.append(parts[0])
    q["kata_kunci_modul"] = new_kws

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json", "w", encoding="utf-8") as f:
    json.dump(pkg, f, indent=2, ensure_ascii=False)

print("Applied meta cleanup and single-line keyword adjustments.")

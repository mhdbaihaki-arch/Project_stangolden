import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_03.json", "r", encoding="utf-8") as f:
    pkg = json.load(f)

with open("modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

modul_pages = {p["page"]: p["text"] for p in modul["pages"]}

meta_replacements = [
    ("sebagaimana tertulis dalam materi", "dalam standar operasional"),
    ("tertulis dalam materi", "secara konseptual"),
    ("menurut materi", "secara konseptual"),
    ("pada materi modul", "dalam konsep manajemen"),
    ("pada materi", "secara normatif"),
    ("menurut modul", "dalam etika kedinasan"),
    ("bahan ajar aturan", "ketentuan kedinasan"),
    ("bahan ajar", "ketentuan resmi"),
    ("sebagaimana tertuang di modul", "dalam etika aparatur"),
    ("sebagaimana dijelaskan di modul", "dalam prinsip tata kelola"),
    ("diatur di modul", "dalam ketentuan kementerian"),
    ("tertulis di modul", "secara konseptual"),
    ("diuraikan di modul", "secara normatif"),
    ("diuraikan dalam modul", "dalam etika kerja"),
    ("sebagaimana termaktub dalam teks modul", "dalam ketentuan resmi"),
    ("sebagaimana dinyatakan secara tegas di modul", "dalam kaidah kerja kementerian"),
    ("sebagaimana ditegaskan dalam materi modul", "dalam standar perilaku kementerian"),
    ("menurut materi modul", "secara konseptual"),
    ("menurut penjelasan modul", "dalam tata kelola kementerian"),
    ("sebagaimana diatur dalam regulasi modul", "dalam ketentuan program kerja"),
    ("sebagaimana dipaparkan pada materi modul", "dalam arah kebijakan kementerian")
]

for q in pkg["daftar_soal"]:
    # Check pertanyaan
    for old, new in meta_replacements:
        q["pertanyaan"] = q["pertanyaan"].replace(old, new)
    # Check pilihan
    for k, v in q["pilihan"].items():
        for old, new in meta_replacements:
            v = v.replace(old, new)
        q["pilihan"][k] = v
    # Check pembahasan
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

# Keyword single-line verbatim adjustment
for q in pkg["daftar_soal"]:
    hlm = q["halaman_sumber"]
    raw_text = modul_pages[hlm]
    raw_lines = [line.strip() for line in raw_text.split("\n") if line.strip()]
    
    new_kws = []
    for kw in q["kata_kunci_modul"]:
        if kw in raw_text:
            new_kws.append(kw)
        else:
            # The kw crosses a newline. Find a clean single line contiguous sub-phrase of 2-5 words
            parts = kw.split()
            found_sub = False
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
                # Find matching line from raw_lines
                for line in raw_lines:
                    words = line.split()
                    if len(words) >= 2:
                        candidate = " ".join(words[:min(4, len(words))])
                        if any(w.lower() in candidate.lower() for w in parts if len(w) > 4):
                            if candidate in raw_text:
                                new_kws.append(candidate)
                                found_sub = True
                                break
            if not found_sub:
                new_kws.append(parts[0])
    q["kata_kunci_modul"] = new_kws

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_03.json", "w", encoding="utf-8") as f:
    json.dump(pkg, f, indent=2, ensure_ascii=False)

print("Applied meta fixes and keyword single-line adjustments to Paket 03.")

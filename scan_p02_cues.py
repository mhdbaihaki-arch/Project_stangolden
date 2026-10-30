import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json", "r", encoding="utf-8") as f:
    data = json.load(f)

qs = data["daftar_soal"]

check_words = [
    "pribadi", "tunjangan", "fasilitas", "keistimewaan", "bebas", "pajak",
    "menghapus", "meniadakan", "mengurangi", "membatasi", "tanpa", "hukuman",
    "menolak", "liar", "uang", "gaji", "honor", "pamrih", "keluarga", "teman",
    "seremonial", "mewah", "instan", "mutlak", "paksaan", "keterpaksaan",
    "sekenanya", "asal-asalan", "formalitas", "semu", "beban", "mengambil alih",
    "menyeragamkan", "memusatkan"
]

print("Scanning all 100 questions for suspicious distractor words...")
found_list = []
for q in qs:
    bad = []
    key = q["kunci_jawaban"]
    for opt, txt in q["pilihan"].items():
        if opt == key:
            continue
        txt_l = txt.lower()
        matched = [w for w in check_words if w in txt_l]
        if matched:
            bad.append((opt, matched, txt))
    if bad:
        found_list.append((q["nomor"], q["pertanyaan"], key, bad))

print(f"Total questions with potentially biased/distractor cues: {len(found_list)}")
for num, pert, key, bad in found_list:
    print(f"\nQ{num} (Key {key}): {pert}")
    for opt, m, txt in bad:
        print(f"  [{opt}] ({', '.join(m)}): {txt}")

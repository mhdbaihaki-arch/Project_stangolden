import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_03.json", "r", encoding="utf-8") as f:
    pkg = json.load(f)

questions = pkg["daftar_soal"]
print(f"Total questions in Paket 03: {len(questions)}")

# Check key balance
counts = {"A": 0, "B": 0, "C": 0, "D": 0, "E": 0}
for q in questions:
    counts[q["kunci_jawaban"]] += 1
print(f"Key distribution: {counts}")

# Check negative cues in distractors
negative_cues = [
    "menghapus", "meniadakan", "mengurangi", "membatasi", "tanpa", "mengabaikan",
    "sepihak", "kaku", "pasif", "menolak", "bebas", "keistimewaan", "liar",
    "pribadi", "sengaja", "memperlambat", "menunda", "asal-asalan", "formalitas",
    "mengambil alih", "menyeragamkan", "memusatkan", "hukuman", "kecewa",
    "korupsi", "pungli", "membiarkan", "lepas tangan", "menyembunyikan", "melanggar",
    "seenaknya", "semena-mena", "ego sektoral", "menutup akses", "menghindari",
    "melempar tanggung jawab", "hanya", "semata-mata", "tidak pernah"
]

flagged = []
for q in questions:
    q_num = q["nomor"]
    bad_opts = []
    key = q["kunci_jawaban"]
    for opt, txt in q["pilihan"].items():
        if opt == key:
            continue
        txt_l = txt.lower()
        matched = [c for c in negative_cues if c in txt_l]
        if matched:
            bad_opts.append((opt, matched, txt))
    if bad_opts:
        flagged.append((q_num, q["pertanyaan"], key, bad_opts))

print(f"\nQuestions with suspicious distractors: {len(flagged)} / {len(questions)}")
for num, pert, key, bad in flagged:
    print(f"\nQ{num} (Key {key}): {pert}")
    for opt, m, txt in bad:
        print(f"  [{opt}] ({', '.join(m)}): {txt}")

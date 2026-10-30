import json
import re

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json", "r", encoding="utf-8") as f:
    data = json.load(f)

questions = data["daftar_soal"]

# Keywords indicating obvious negative/absurd distractors
negative_cues = [
    "menghapus", "meniadakan", "mengurangi", "membatasi", "tanpa", "mengabaikan",
    "sepihak", "kaku", "pasif", "menolak", "bebas dari kewajiban", "kebebasan menentukan",
    "keistimewaan", "pajak liar", "liar", "pribadi", "sengaja", "memperlambat",
    "menunda", "bukan tugas", "asal-asalan", "formalitas", "mengambil alih",
    "menyeragamkan", "memusatkan", "menaikkan tanpa", "beban", "hukuman"
]

flagged = []
for q in questions:
    q_num = q["nomor"]
    q_flagged = False
    opt_issues = []
    for opt, text in q["pilihan"].items():
        if opt == q["kunci_jawaban"]:
            continue
        t_low = text.lower()
        matched = [c for c in negative_cues if c in t_low]
        if matched:
            opt_issues.append((opt, matched, text))
    if len(opt_issues) >= 2: # 2 or more distractors have obvious negative cues
        flagged.append((q_num, q["pertanyaan"], q["kunci_jawaban"], opt_issues))

print(f"Total questions in Modul 2 Paket 02: {len(questions)}")
print(f"Questions with obvious negative distractors: {len(flagged)} / {len(questions)}")

for q_num, pert, key, issues in flagged[:15]:
    print(f"\nQ{q_num} (Kunci {key}): {pert}")
    for opt, m, t in issues:
        print(f"   [{opt}] ({', '.join(m)}): {t}")

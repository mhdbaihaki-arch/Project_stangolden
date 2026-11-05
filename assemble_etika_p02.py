import json
import re
import os
import sys
import subprocess
from collections import Counter

sys.stdout.reconfigure(encoding='utf-8')

from draft_etika_p02_part1 import questions_part1
from draft_etika_p02_part2 import questions_part2
from build_etika_php import build_etika_php

all_questions = questions_part1 + questions_part2
print(f"Total questions assembled: {len(all_questions)}")
assert len(all_questions) == 100, f"Expected 100 questions, got {len(all_questions)}"

# Load module text for verbatim keyword verification
with open("modul_json/1. Modul_Etika PNS_after UAT.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

modul_pages = {p["page"]: p["text"] for p in modul["pages"]}

# 1. Verbatim keyword verification
kw_misses = 0
for q in all_questions:
    hlm = q["halaman_sumber"]
    ptxt = modul_pages[hlm]
    for kw in q["kata_kunci_modul"]:
        if kw.lower() not in ptxt.lower():
            print(f"ERROR: Q{q['nomor']} (Hlm {hlm}) keyword '{kw}' not in page text!")
            kw_misses += 1
assert kw_misses == 0, f"Found {kw_misses} keyword mismatches!"
print("✅ All 100 questions have 100% verbatim matching keywords in modul_json!")

# 2. Key distribution verification
keys = [q["kunci_jawaban"] for q in all_questions]
counts = Counter(keys)
print("Key distribution:", counts)
for letter in "ABCDE":
    assert counts[letter] == 20, f"Letter {letter} count is {counts[letter]}, expected 20!"
print("✅ Key distribution is EXACTLY 20 A, 20 B, 20 C, 20 D, 20 E!")

# 3. Max consecutive identical keys
consec = 1
max_consec = 1
for i in range(1, len(keys)):
    if keys[i] == keys[i-1]:
        consec += 1
        if consec > max_consec:
            max_consec = consec
    else:
        consec = 1
print(f"Max consecutive identical keys: {max_consec}")
assert max_consec <= 2, f"Max consecutive identical keys exceeded: {max_consec}"
print("✅ Max consecutive identical keys is <= 2 (no mechanical repetitive patterns)!")

# 4. Length checks
long_q = [q for q in all_questions if len(q["pertanyaan"].split()) > 32]
assert len(long_q) == 0, f"Found questions > 32 words: {[q['nomor'] for q in long_q]}"
print("✅ All questions are <= 32 words!")

long_opts = []
for q in all_questions:
    for let, opt in q["pilihan"].items():
        if len(opt.split()) > 12:
            long_opts.append((q["nomor"], let, len(opt.split()), opt))
assert len(long_opts) == 0, f"Found options > 12 words: {long_opts}"
print("✅ All options are <= 12 words!")

# 5. Blacklist check on distractors
blacklist = [
    'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan',
    'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak',
    'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses', 'menghindari',
    'melempar tanggung jawab', 'semata-mata', 'tidak pernah'
]
bad_distractors = []
for q in all_questions:
    k = q["kunci_jawaban"]
    for let, opt in q["pilihan"].items():
        if let != k:
            for b in blacklist:
                if re.search(r'\b' + re.escape(b) + r'\b', opt, re.I):
                    bad_distractors.append((q["nomor"], let, b, opt))
print(f"Flagged blacklist words in distractors: {len(bad_distractors)}")
assert len(bad_distractors) <= 4, f"Too many weak distractors: {len(bad_distractors)}"
print("✅ Distractor quality complies with threshold (<= 4 allowed as breathing space)!")

# 6. Save JSON
paket_02_data = {
    "paket_nomor": 2,
    "dokumen_sumber": "1. Modul_Etika PNS_after UAT.pdf",
    "rentang_halaman": "Halaman 14 s/d Halaman 23",
    "jumlah_soal": 100,
    "distribusi_kunci": {
        "A": 20,
        "B": 20,
        "C": 20,
        "D": 20,
        "E": 20
    },
    "daftar_soal": all_questions
}

out_json_path = "soal_etikapns_json/1. Modul_Etika PNS_paket_02.json"
with open(out_json_path, "w", encoding="utf-8") as f:
    json.dump(paket_02_data, f, ensure_ascii=False, indent=2)
print(f"✅ Saved JSON: {out_json_path}")

# 7. Generate PHP
build_etika_php(2, 14, 23)

# 8. Verify PHP syntax
php_exe = r"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
target_php = r"soal_etikapns_php\STANGOLDEN_Etika_PNS_Paket_02_Hlm_14-23.php"
res = subprocess.run([php_exe, "-l", target_php], capture_output=True, text=True)
print("PHP syntax check result:", res.stdout.strip())
assert "No syntax errors detected" in res.stdout, f"Syntax error in {target_php}: {res.stderr}"
print("✅ Paket 02 PHP SYNTAX 100% VALID!")

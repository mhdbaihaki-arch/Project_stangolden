# -*- coding: utf-8 -*-
"""
Assembler & Verifier untuk Modul Etika PNS - Paket 03 (Halaman 24 s/d 31)
Total: 80 butir soal (8 halaman x 10 soal)
Kunci jawaban: tepat 16 A, 16 B, 16 C, 16 D, 16 E
"""

import json
import re
import os
import sys
import subprocess
from collections import Counter

sys.stdout.reconfigure(encoding='utf-8')

from draft_etika_p03_part1 import questions_part1
from draft_etika_p03_part2 import questions_part2
from build_etika_php import build_etika_php
from verify_draft_helper import verify_draft

all_questions = questions_part1 + questions_part2
print(f"Total questions assembled: {len(all_questions)}")
assert len(all_questions) == 80, f"Expected 80 questions, got {len(all_questions)}"

# Renumber sequentially just to be 100% certain
for idx, q in enumerate(all_questions):
    q["nomor"] = idx + 1

# 1. Comprehensive verification using verify_draft_helper
print("\n--- 1. Running Comprehensive Verifier ---")
errs = verify_draft(all_questions)
print(f"Total verifier flags: {len(errs)}")
if errs:
    for e in errs:
        print("  -", e)
assert len(errs) == 0, f"Verification failed with {len(errs)} errors!"
print("✅ All 80 questions passed verbatim keyword matching, brevity limits, and zero meta phrases!")

# 2. Key distribution verification
print("\n--- 2. Checking Key Distribution ---")
keys = [q["kunci_jawaban"] for q in all_questions]
counts = Counter(keys)
print("Key distribution:", dict(counts))
for letter in "ABCDE":
    assert counts[letter] == 16, f"Letter {letter} count is {counts[letter]}, expected 16!"
print("✅ Key distribution is EXACTLY 16 A, 16 B, 16 C, 16 D, 16 E!")

# 3. Max consecutive identical keys
print("\n--- 3. Checking Max Consecutive Identical Keys ---")
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
print("\n--- 4. Checking Question and Option Lengths ---")
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

# 5. Distractor Blacklist check
print("\n--- 5. Checking Distractor Quality ---")
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
print("✅ Distractor quality complies with threshold (<= 4 allowed as breathing space, got {len(bad_distractors)})!")

# 6. Save JSON
print("\n--- 6. Saving Final JSON ---")
paket_03_data = {
    "paket_nomor": 3,
    "dokumen_sumber": "1. Modul_Etika PNS_after UAT.pdf",
    "rentang_halaman": "Halaman 24 s/d Halaman 31",
    "jumlah_soal": 80,
    "distribusi_kunci": {
        "A": 16,
        "B": 16,
        "C": 16,
        "D": 16,
        "E": 16
    },
    "daftar_soal": all_questions
}

out_json_path = "soal_etikapns_json/1. Modul_Etika PNS_paket_03.json"
os.makedirs("soal_etikapns_json", exist_ok=True)
with open(out_json_path, "w", encoding="utf-8") as f:
    json.dump(paket_03_data, f, ensure_ascii=False, indent=2)
print(f"✅ Saved JSON: {out_json_path} ({os.path.getsize(out_json_path):,} bytes)")

# 7. Generate PHP
print("\n--- 7. Generating PHP CBT File ---")
os.makedirs("soal_etikapns_php", exist_ok=True)
build_etika_php(3, 24, 31)

# 8. Verify PHP syntax
print("\n--- 8. Verifying PHP Syntax via CLI ---")
php_exe = r"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
target_php = r"soal_etikapns_php\STANGOLDEN_Etika_PNS_Paket_03_Hlm_24-31.php"
res = subprocess.run([php_exe, "-l", target_php], capture_output=True, text=True)
print("PHP syntax check result:", res.stdout.strip())
assert "No syntax errors detected" in res.stdout, f"Syntax error in {target_php}: {res.stderr}"
print("✅ Paket 03 PHP SYNTAX 100% VALID!")

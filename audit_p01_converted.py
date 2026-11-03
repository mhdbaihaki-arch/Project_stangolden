import json
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

# Run build_p01_converter logic to get daftar_soal
from build_p01_converter import daftar_soal, letter_map

print(f"Total questions: {len(daftar_soal)}")

# 1. Key distribution
from collections import Counter
keys = [q['kunci_jawaban'] for q in daftar_soal]
print("Key distribution:", Counter(keys))

# Consecutive keys check
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

# Length check
long_q = [q for q in daftar_soal if len(q['pertanyaan'].split()) > 32]
print(f"Questions > 32 words: {len(long_q)}")
for q in long_q[:3]:
    print(f"  Q{q['nomor']} ({len(q['pertanyaan'].split())} w): {q['pertanyaan']}")

long_opts = []
for q in daftar_soal:
    for let, opt in q['pilihan'].items():
        if len(opt.split()) > 12:
            long_opts.append((q['nomor'], let, len(opt.split()), opt))
print(f"Options > 12 words: {len(long_opts)}")
for o in long_opts[:3]:
    print(f"  Q{o[0]} {o[1]} ({o[2]} w): {o[3]}")

# Blacklist words in distractors
blacklist = [
    'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan',
    'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak',
    'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses', 'menghindari',
    'melempar tanggung jawab', 'semata-mata', 'tidak pernah'
]
bad_distractors = []
for q in daftar_soal:
    k = q['kunci_jawaban']
    for let, opt in q['pilihan'].items():
        if let != k:
            for b in blacklist:
                if re.search(r'\b' + re.escape(b) + r'\b', opt, re.I):
                    bad_distractors.append((q['nomor'], let, b, opt))

print(f"Flagged blacklist words in distractors: {len(bad_distractors)}")
for bd in bad_distractors[:5]:
    print(f"  Q{bd[0]} {bd[1]} (word: {bd[2]}): {bd[3]}")

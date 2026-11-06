# apply_overhaul_pkn_p01.py
import json
import re
import overhaul_pkn_p01_data_part1 as p1
import overhaul_pkn_p01_data_part2 as p2

json_path = 'soal_pengelolaan_keuangan_negara_json/4. Pengelolaan Keuangan Negara_paket_01.json'

with open(json_path, 'r', encoding='utf-8') as f:
    target_data = json.load(f)

all_soal = p1.QUESTIONS_PART1 + p2.QUESTIONS_PART2

print(f"Total merged questions: {len(all_soal)}")
assert len(all_soal) == 100, f"Expected 100 questions, got {len(all_soal)}"

# Check keys
from collections import Counter
keys = [q['kunci_jawaban'] for q in all_soal]
c = Counter(keys)
print(f"Key counts: {c}")
assert c == {'A': 20, 'B': 20, 'C': 20, 'D': 20, 'E': 20}, f"Key distribution unbalanced: {c}"

# Assign to target_data
target_data['daftar_soal'] = all_soal

# Save to JSON
with open(json_path, 'w', encoding='utf-8') as f:
    json.dump(target_data, f, indent=2, ensure_ascii=False)

print(f"Successfully updated {json_path}")

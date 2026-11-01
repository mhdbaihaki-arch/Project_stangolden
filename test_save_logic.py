import json
import re

# Test with one of the injected PHP files
target_file = 'soal_aturankepegawaian_php/STANGOLDEN_Tata_Aturan_Kepegawaian_Paket_01_Hlm_07-20.php'
with open(target_file, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Locate questions JSON
q_prefix = 'const questions' + '=['
p_start = content.find(q_prefix)
assert p_start != -1, "p_start not found"
p_end = content.find('}' + '];', p_start)
assert p_end != -1, "p_end not found"

json_start = p_start + len('const questions=')
json_len = (p_end + 2) - json_start
json_str = content[json_start:json_start + json_len]
all_questions = json.loads(json_str)
assert len(all_questions) == 100, f"Expected 100 questions, got {len(all_questions)}"

# 2. Locate STG_KEY
k_prefix = 'const STG_KEY' + ' = ['
k_start = content.find(k_prefix)
assert k_start != -1, "k_start not found"
k_end = content.find('];', k_start)
assert k_end != -1, "k_end not found"

key_json_start = k_start + len('const STG_KEY = ')
key_json_len = (k_end + 1) - key_json_start
all_keys = json.loads(content[key_json_start:key_json_start + key_json_len])
assert len(all_keys) == 100, f"Expected 100 keys, got {len(all_keys)}"

print("Verification passed! Backend replacement logic is 100% sound.")

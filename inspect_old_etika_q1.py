import re, json, sys

sys.stdout.reconfigure(encoding='utf-8')

with open("STANGOLDEN_Latihan_Etika_PNS_4-13_V5_Bahasa_Ujian_100_Soal_FULL_PHP73.php", "r", encoding="utf-8") as f:
    text = f.read()

m = re.search(r'const\s+questions\s*=\s*(\[\s*\{.*?\}\s*\]);', text, re.DOTALL)
qs = json.loads(m.group(1))

# Check key distribution
from collections import Counter
keys = [q['answer'] for q in qs]
letter_map = {0: 'A', 1: 'B', 2: 'C', 3: 'D', 4: 'E'}
letter_keys = [letter_map[k] for k in keys]
print("Key distribution:", Counter(letter_keys))

# Check sample question 1
q1 = qs[0]
print("\nSample Q1:")
print("Question:", q1.get('question'))
print("Options:", q1.get('options'))
print("Answer:", letter_map[q1.get('answer')])
print("Explanation:", q1.get('explanation')[:300])

import json
from collections import Counter

with open('tak_p01_part1.json', 'r', encoding='utf-8') as f:
    p1 = json.load(f)
p1_list = [p1[str(i)] for i in range(1, 31)]

with open('tak_p01_part2.json', 'r', encoding='utf-8') as f:
    p2 = json.load(f)

with open('tak_p01_part3_4.json', 'r', encoding='utf-8') as f:
    p3_4 = json.load(f)

print("Part 1 (Q1-Q30):", Counter(q['kunci_jawaban'] for q in p1_list))
print("Part 2 (Q31-Q60):", Counter(q['kunci_jawaban'] for q in p2))
print("Part 3_4 (Q61-Q100):", Counter(q['kunci_jawaban'] for q in p3_4))

# By page:
all_q = p1_list + p2 + p3_4
by_page = {}
for q in all_q:
    hlm = q['halaman_sumber']
    by_page.setdefault(hlm, []).append(q['kunci_jawaban'])

for hlm, keys in sorted(by_page.items()):
    print(f"Hlm {hlm}: {Counter(keys)} -> {keys}")

import json

with open('tak_p01_full_review.txt', 'r', encoding='utf-8') as f:
    text = f.read()

import re
matches = re.findall(r'No (\d+) \(Hlm (\d+)\) \[Kunci: ([A-E])\]', text)
for m in matches[:30]:
    print(f"Q{m[0]} (Hlm {m[1]}): {m[2]}")

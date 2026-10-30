with open('lines_tak_p03.txt', 'r', encoding='utf-8') as f:
    text = f.read()

import re
pages = re.split(r'=== HALAMAN (\d+) ===', text)
for i in range(1, len(pages), 2):
    if pages[i] == '37':
        for idx, l in enumerate(pages[i+1].strip().splitlines(), 1):
            print(f"[{idx:02d}] {l}")

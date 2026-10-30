with open('lines_tak_p01.txt', 'r', encoding='utf-8') as f:
    text = f.read()

import re
pages = re.split(r'=== HALAMAN (\d+) ===', text)
for i in range(1, len(pages), 2):
    if pages[i] == '10':
        lines = pages[i+1].strip().splitlines()
        for idx, l in enumerate(lines, 1):
            if 'akuntabilitas' in l.lower() or 'menjamin' in l.lower() or idx > 25:
                print(f"[{idx:02d}] {l}")

with open('lines_tak_p02.txt', 'r', encoding='utf-8') as f:
    text = f.read()

import re
pages = re.split(r'=== HALAMAN (\d+) ===', text)
for i in range(1, len(pages), 2):
    pnum = pages[i]
    if pnum in ['21', '22', '23']:
        print(f"*** PAGE {pnum} ***")
        for idx, l in enumerate(pages[i+1].strip().splitlines(), 1):
            print(f"[{idx:02d}] {l}")
        print()

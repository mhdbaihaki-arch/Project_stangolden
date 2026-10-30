with open('lines_tak_p04.txt', 'r', encoding='utf-8') as f:
    text = f.read()

import re
pages = re.split(r'=== HALAMAN (\d+) ===', text)
for i in range(1, len(pages), 2):
    pnum = pages[i]
    if pnum in ['47', '49']:
        print(f"*** PAGE {pnum} ***")
        for idx, l in enumerate(pages[i+1].strip().splitlines(), 1):
            if any(k in l.lower() for k in ['pmk', '224', '325', 'kmk', 'penghargaan', 'sanksi', 'disiplin']):
                print(f"[{idx:02d}] {l}")
        print()

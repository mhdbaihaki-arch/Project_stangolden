import re

with open('lines_tak_p01.txt', 'r', encoding='utf-8') as f:
    text = f.read()

pages = re.split(r'=== HALAMAN (\d+) ===', text)
for i in range(1, len(pages), 2):
    pnum = pages[i]
    pbody = pages[i+1].strip()
    if pnum in ['15', '17', '19', '20']:
        print(f"*** PAGE {pnum} *** ({len(pbody.splitlines())} lines)")
        for l in pbody.splitlines():
            print("  ", l)

import json
import re
import sys
sys.stdout.reconfigure(encoding='utf-8')

with open("modul_json/5. Wawasan Kebangsaan.json", 'r', encoding='utf-8') as f:
    modul = json.load(f)

for p in modul['pages']:
    page_num = p['page']
    lines = [l.strip() for l in p['text'].split('\n') if l.strip()]
    headings = []
    for l in lines:
        if re.match(r'^(BAB\s+[IVXLCDM]+|PENDAHULUAN|[A-Z]\.\s+|[0-9]+\.\s+)', l, re.IGNORECASE):
            if len(l) < 80:
                headings.append(l)
    if headings:
        print(f"Page {page_num:02d}: {headings[:4]}")

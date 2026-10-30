import json
import sys
sys.stdout.reconfigure(encoding='utf-8')

with open("modul_json/5. Wawasan Kebangsaan.json", 'r', encoding='utf-8') as f:
    modul = json.load(f)

print(f"Total pages in JSON: {len(modul['pages'])}")
for p in modul['pages']:
    text = p['text'].strip()
    lines = [l.strip() for l in text.split('\n') if l.strip()]
    first_few = " // ".join(lines[:3]) if lines else "EMPTY"
    print(f"Hlm {p['page']:02d} | chars: {len(text):4d} | lines: {len(lines):2d} | {first_few[:90]}")

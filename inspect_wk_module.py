import json
import os
import sys

sys.stdout.reconfigure(encoding='utf-8')

modul_path = "modul_json/5. Wawasan Kebangsaan.json"
with open(modul_path, 'r', encoding='utf-8') as f:
    modul = json.load(f)

print("Title / Info:", modul.get('title', 'No Title'))
pages = modul.get('pages', [])
print(f"Total pages: {len(pages)}")

for p in pages:
    page_num = p['page']
    text = p['text'].strip()
    text_len = len(text)
    lines = [l.strip() for l in text.split('\n') if l.strip()]
    first_line = lines[0] if lines else "EMPTY"
    last_line = lines[-1] if lines else "EMPTY"
    print(f"Page {page_num:02d} (len: {text_len:4d}, lines: {len(lines):2d}): {first_line[:50]} | {last_line[:30]}")

import json
import sys
sys.stdout.reconfigure(encoding='utf-8')

with open("modul_json/5. Wawasan Kebangsaan.json", 'r', encoding='utf-8') as f:
    modul = json.load(f)

pages = [p['page'] for p in modul['pages']]
print("Total page objects:", len(pages))
print("Min page:", min(pages), "Max page:", max(pages))
expected = list(range(min(pages), max(pages)+1))
missing = set(expected) - set(pages)
print("Missing pages:", missing)

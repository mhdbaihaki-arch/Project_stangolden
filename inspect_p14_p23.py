import json, sys

sys.stdout.reconfigure(encoding='utf-8')

with open("modul_json/1. Modul_Etika PNS_after UAT.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

pages = {p["page"]: p["text"] for p in modul["pages"]}

for p in range(14, 24):
    txt = pages.get(p, "")
    print(f"\n{'='*70}\nPAGE {p} (words: {len(txt.split())})\n{'='*70}")
    print(txt[:600] + "..." if len(txt) > 600 else txt)

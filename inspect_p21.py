import json, sys

sys.stdout.reconfigure(encoding='utf-8')

with open("modul_json/1. Modul_Etika PNS_after UAT.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

pages = {p["page"]: p["text"] for p in modul["pages"]}
print("=== PAGE 21 ===")
print(pages[21])

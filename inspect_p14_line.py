import json, sys
sys.stdout.reconfigure(encoding='utf-8')
with open("modul_json/1. Modul_Etika PNS_after UAT.json", "r", encoding="utf-8") as f:
    modul = json.load(f)
p14 = modul["pages"][10]["text"] # hlm 14
print("--- Page 14 around pilih kasih ---")
for line in p14.splitlines():
    if 'pilih' in line or 'kasih' in line or 'konsisten' in line.lower():
        print(repr(line))

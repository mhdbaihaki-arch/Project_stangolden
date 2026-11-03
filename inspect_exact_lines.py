import json, sys

sys.stdout.reconfigure(encoding='utf-8')
with open("modul_json/1. Modul_Etika PNS_after UAT.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

modul_pages = {p["page"]: p["text"] for p in modul["pages"]}

targets = [
    (5, 14, 'permasalahan'),
    (60, 19, 'terbaik'),
    (64, 20, 'kebencian'),
    (79, 21, 'kepatuhan'),
    (88, 22, 'jabatan dan pangkat'),
    (90, 22, 'pemanggilan kedua'),
    (96, 23, 'fleksibilitas'),
    (97, 23, 'pengawasan langsung'),
    (98, 23, 'tempat dan/atau waktu')
]

for q_no, hlm, kw in targets:
    txt = modul_pages[hlm]
    print(f"--- Q{q_no} (Hlm {hlm}) search '{kw}' ---")
    for line in txt.splitlines():
        if kw.lower() in line.lower():
            print(repr(line))

import json
import sys
sys.stdout.reconfigure(encoding='utf-8')

with open("modul_json/5. Wawasan Kebangsaan.json", 'r', encoding='utf-8') as f:
    modul = json.load(f)

for hlm in range(6, 16):
    p = next((x for x in modul['pages'] if x['page'] == hlm), None)
    print(f"\n==================== HALAMAN {hlm:02d} ====================")
    if p:
        lines = [l.strip() for l in p['text'].split('\n') if l.strip()]
        for idx, l in enumerate(lines, 1):
            print(f"{idx:02d}: {l}")
    else:
        print("Page not found!")

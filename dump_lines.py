import json

with open('modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

with open('lines_pages_35_48.txt', 'w', encoding='utf-8') as out:
    for p in modul['pages']:
        p_num = int(p['page'])
        if 35 <= p_num <= 48:
            out.write(f"=== HALAMAN {p_num} ===\n")
            for i, line in enumerate(p['text'].split('\n'), 1):
                out.write(f"[{i:02d}] {line}\n")
            out.write("\n")

print("Dumped lines of pages 35-48 to lines_pages_35_48.txt")

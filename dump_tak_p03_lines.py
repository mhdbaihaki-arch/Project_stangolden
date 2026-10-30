import json

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

with open('lines_tak_p03.txt', 'w', encoding='utf-8') as out:
    for p in modul.get('pages', []):
        p_num = int(p['page'])
        if 31 <= p_num <= 41:
            out.write(f"=== HALAMAN {p_num} ===\n")
            for i, line in enumerate(p['text'].split('\n'), 1):
                out.write(f"[{i:02d}] {line}\n")
            out.write("\n")

print("Dumped lines of Tak Paket 03 (Hlm 31-41) to lines_tak_p03.txt")

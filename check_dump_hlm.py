with open('dump_hlm35_48.txt', 'r', encoding='utf-8') as f:
    text = f.read()

pages = text.split("=== HALAMAN ")
for p in pages[1:]:
    lines = p.split("\n")
    hlm_num = lines[0].split(" ")[0]
    print(f"Halaman {hlm_num}: {len(lines)} baris")

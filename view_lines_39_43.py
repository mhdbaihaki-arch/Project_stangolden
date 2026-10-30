with open('lines_pages_35_48.txt', 'r', encoding='utf-8') as f:
    text = f.read()

pages = text.split("=== HALAMAN ")
for p in pages:
    if any(p.startswith(f"{h} ") for h in range(39, 44)):
        print("=== HALAMAN " + p)

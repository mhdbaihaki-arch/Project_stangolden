with open('lines_pages_35_48.txt', 'r', encoding='utf-8') as f:
    text = f.read()

pages = text.split("=== HALAMAN ")
for p in pages:
    if p.startswith("35 ") or p.startswith("36 "):
        print("=== HALAMAN " + p)

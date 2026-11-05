import re, json, sys

sys.stdout.reconfigure(encoding='utf-8')
with open("STANGOLDEN_Latihan_Etika_PNS_4-13_V5_Bahasa_Ujian_100_Soal_FULL_PHP73.php", "r", encoding="utf-8") as f:
    text = f.read()

m = re.search(r'const\s+questions\s*=\s*(\[\s*\{.*?\}\s*\]);', text, re.DOTALL)
if not m:
    m = re.search(r'let\s+questions\s*=\s*(\[\s*\{.*?\}\s*\]);', text, re.DOTALL)
if not m:
    m = re.search(r'var\s+questions\s*=\s*(\[\s*\{.*?\}\s*\]);', text, re.DOTALL)

if m:
    raw_js = m.group(1)
    print("Found questions JS array, length:", len(raw_js))
    # Let's inspect the first object
    first_obj = raw_js[:1000]
    print("First snippet:\n", first_obj)
else:
    print("Could not match questions array regex")

import re, json, sys

sys.stdout.reconfigure(encoding='utf-8')

with open("STANGOLDEN_Latihan_Etika_PNS_4-13_V5_Bahasa_Ujian_100_Soal_FULL_PHP73.php", "r", encoding="utf-8") as f:
    text = f.read()

m = re.search(r'const\s+questions\s*=\s*(\[\s*\{.*?\}\s*\]);', text, re.DOTALL)
qs = json.loads(m.group(1))

for i in range(3):
    print(f"=== Q{i+1} EXPLANATION ===")
    print(qs[i]['explanation'])

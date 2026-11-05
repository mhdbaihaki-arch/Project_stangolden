import re, sys
sys.stdout.reconfigure(encoding='utf-8')

with open("STANGOLDEN_Latihan_Etika_PNS_4-13_V5_Bahasa_Ujian_100_Soal_FULL_PHP73.php", "r", encoding="utf-8") as f:
    text = f.read()

# find javascript array or php array of questions
m = re.findall(r'(\b[a-zA-Z0-9_]+)\s*=\s*\[\s*\{\s*["\']', text)
print("Array definitions matching [{:", m)

for line in text.splitlines():
    if 'pertanyaan' in line.lower() or 'pilihan' in line.lower() or 'pembahasan' in line.lower():
        print(line[:120])
        break

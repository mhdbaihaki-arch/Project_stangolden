import sys
sys.stdout.reconfigure(encoding='utf-8')

with open("STANGOLDEN_Latihan_Etika_PNS_4-13_V5_Bahasa_Ujian_100_Soal_FULL_PHP73.php", "r", encoding="utf-8") as f:
    text = f.read()

print("File size:", len(text))
for line in text.splitlines():
    if any(k in line for k in ['$soal', '$questions', 'raw_questions', 'array(', 'json_decode', 'QUESTIONS', 'soal =']):
        print(line[:120])

import re, json, sys

sys.stdout.reconfigure(encoding='utf-8')

with open("STANGOLDEN_Latihan_Etika_PNS_4-13_V5_Bahasa_Ujian_100_Soal_FULL_PHP73.php", "r", encoding="utf-8") as f:
    text = f.read()

# Check for JSON data in PHP file
# Looking for json_decode or raw array
m = re.search(r'json_decode\(\s*\'([^\']+)\'', text)
if m:
    print("Found json_decode with raw json string, length:", len(m.group(1)))
else:
    # Maybe heredoc or <<<JSON
    m2 = re.search(r'<<<\'?JSON\'?\s*\n(.*?)\n\s*JSON;', text, re.DOTALL)
    if m2:
        print("Found heredoc JSON, length:", len(m2.group(1)))
        try:
            d = json.loads(m2.group(1))
            print("Keys:", d.keys())
            if 'daftar_soal' in d:
                print("Total questions in heredoc:", len(d['daftar_soal']))
                print("First question:", d['daftar_soal'][0]['nomor'], d['daftar_soal'][0]['pertanyaan'][:60])
                print("Page range:", d.get('rentang_halaman'))
        except Exception as e:
            print("Error parsing JSON:", e)
    else:
        # Check how questions are defined
        m3 = re.findall(r'"nomor":\s*(\d+)', text)
        print("Matches for 'nomor':", len(m3))
        if m3:
            print("Nomor sample:", m3[:10], "...", m3[-5:])

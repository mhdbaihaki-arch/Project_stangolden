import json

with open('soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

soal_list = data['daftar_soal']

# Let's inspect each question's options
print(f"Total questions: {len(soal_list)}")

# We want to identify any options that are noticeably negative, silly, or obvious giveaways
# Let's write an inspector that flags:
# 1. Any words implying bad, wrong, silly, lazy, or corrupt behavior
# 2. Typos in questions
suspect_words = [
    'salah', 'buruk', 'malas', 'curang', 'lambat', 'asal', 'palsu', 'bohong',
    'curiga', 'batal', 'rusak', 'curian', 'ilegal', 'remeh', 'teledor',
    'diam-diam', 'peramal', 'mewah', 'pribadi', 'keliru', 'menghapus',
    'santai', 'berdebat', 'sepihak', 'kaku', 'formalitas', 'tanpa',
    'menyerahkan kewenangan', 'meminta bantuan', 'meniru', 'menyalahkan'
]

flagged = []
for q in soal_list:
    no = q['nomor']
    key = q['kunci_jawaban']
    pilihan = q['pilihan']
    hits = []
    for opt, text in pilihan.items():
        if opt != key:
            for sw in suspect_words:
                if sw in text.lower():
                    hits.append(f"[{opt}] '{sw}': {text}")
    if hits:
        flagged.append((no, key, q['pertanyaan'], hits))

print(f"Questions with suspect words: {len(flagged)}")
for no, key, pert, hits in flagged:
    print(f"No {no} (Key {key}):")
    for h in hits:
        print(f"  {h}")

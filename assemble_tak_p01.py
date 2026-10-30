import json
from collections import Counter

# Load parts
with open('tak_p01_part1.json', 'r', encoding='utf-8') as f:
    part1_raw = json.load(f)

if isinstance(part1_raw, dict):
    part1 = []
    for i in range(1, 31):
        item = part1_raw[str(i)]
        item['nomor'] = i
        part1.append(item)
else:
    part1 = part1_raw

with open('tak_p01_part2.json', 'r', encoding='utf-8') as f:
    part2 = json.load(f)

with open('tak_p01_part3_4.json', 'r', encoding='utf-8') as f:
    part3_4 = json.load(f)

all_questions = part1 + part2 + part3_4
print(f"Total questions: {len(all_questions)}")
assert len(all_questions) == 100

# Verify numbering 1..100
for i, q in enumerate(all_questions, 1):
    assert q['nomor'] == i, f"Expected nomor {i}, got {q['nomor']}"

# Count keys
key_counts = Counter(q['kunci_jawaban'] for q in all_questions)
print(f"Key distribution: {key_counts}")
assert key_counts == {'A': 20, 'B': 20, 'C': 20, 'D': 20, 'E': 20}

# Check max streak
max_streak = 1
cur_streak = 1
for i in range(1, len(all_questions)):
    if all_questions[i]['kunci_jawaban'] == all_questions[i-1]['kunci_jawaban']:
        cur_streak += 1
        max_streak = max(max_streak, cur_streak)
    else:
        cur_streak = 1
print(f"Max key streak: {max_streak}")

# Audit against modul text
with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

page_texts = {int(p['page']): p['text'] for p in modul['pages']}

blacklist = [
    'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan',
    'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak',
    'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses',
    'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah'
]

meta_patterns = [
    "dalam materi", "menurut modul", "buku modul", "buku teks", "tabel 1", "tabel 2",
    "kata kunci modul", "penjelasan modul", "pada bab", "pada halaman", "bahan ajar",
    "sebagaimana tertulis dalam materi", "tertulis dalam materi", "pada subbab", "diktat"
]

total_issues = 0
for q in all_questions:
    num = q['nomor']
    hlm = q['halaman_sumber']
    p_text = page_texts.get(hlm, '')

    # check keywords
    for kw in q['kata_kunci_modul']:
        if kw not in p_text:
            print(f"[KW MISS] Q{num} (Hlm {hlm}): '{kw}'")
            total_issues += 1

    # check blacklist
    for opt, text in q['pilihan'].items():
        for b in blacklist:
            if b in text.lower():
                print(f"[BLACKLIST] Q{num} [{opt}]: '{b}' in '{text}'")
                total_issues += 1

    # check meta patterns
    full_str = json.dumps(q, ensure_ascii=False).lower()
    for m in meta_patterns:
        if m in full_str:
            print(f"[META] Q{num}: '{m}'")
            total_issues += 1

print(f"Total audit issues across all 100 questions: {total_issues}")
assert total_issues == 0, f"Found {total_issues} issues!"

# Build final json
final_data = {
    "paket_nomor": 1,
    "dokumen_sumber": "3. Tata Aturan Kepegawaian.pdf",
    "rentang_halaman": "Halaman 7 s/d Halaman 20",
    "jumlah_soal": 100,
    "distribusi_kunci": {
        "A": 20,
        "B": 20,
        "C": 20,
        "D": 20,
        "E": 20
    },
    "daftar_soal": all_questions
}

target_file = 'soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_01.json'
with open(target_file, 'w', encoding='utf-8') as f:
    json.dump(final_data, f, ensure_ascii=False, indent=2)

print(f"Successfully saved 100 questions to {target_file}")

import json

with open('tak_p01_part3_4.json', 'r', encoding='utf-8') as f:
    part3_4 = json.load(f)

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

page_texts = {}
for p in modul['pages']:
    pnum = int(p['page'])
    page_texts[pnum] = p['text']

blacklist = [
    'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan',
    'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak',
    'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses',
    'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah'
]

meta_patterns = [
    "dalam materi", "menurut modul", "buku modul", "buku teks", "tabel 1", "tabel 2",
    "kata kunci modul", "penjelasan modul", "pada bab", "pada halaman", "bahan ajar"
]

print("AUDIT PART 3 & 4 (Q61-Q100):")
err_count = 0
for q in part3_4:
    num = q['nomor']
    hlm = q['halaman_sumber']
    p_text = page_texts.get(hlm, '')

    # check keywords
    for kw in q['kata_kunci_modul']:
        if kw not in p_text:
            print(f"  [KW MISS] No {num} (Hlm {hlm}): '{kw}'")
            err_count += 1

    # check blacklist
    for opt, text in q['pilihan'].items():
        for b in blacklist:
            if b in text.lower():
                print(f"  [BLACKLIST] No {num} [{opt}]: '{b}' in '{text}'")
                err_count += 1

    # check meta
    full_str = json.dumps(q, ensure_ascii=False).lower()
    for m in meta_patterns:
        if m in full_str:
            print(f"  [META] No {num}: '{m}'")
            err_count += 1

print(f"Part 3 & 4 audit finished with {err_count} issues.")

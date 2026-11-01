import json
import re

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_01.json', 'r', encoding='utf-8') as f:
    d1 = json.load(f)
with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)
page_texts = {p['page']: p['text'] for p in modul['pages']}

soals = {q['nomor']: q for q in d1['daftar_soal']}
flagged = [4, 5, 8, 11, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 24, 25, 26, 27, 28, 35, 36, 38, 41, 47, 49, 60, 61, 63, 64, 65, 67, 70, 71, 73, 74, 77, 79, 82, 87, 90, 97, 98, 99]

blacklist = ['mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan', 'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak', 'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses', 'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah']
meta_patterns = [r'tertulis dalam materi', r'dalam materi', r'pada materi', r'menurut materi', r'dimuat dalam materi', r'disebutkan dalam materi', r'buku materi', r'menurut modul', r'buku modul', r'buku teks', r'sesuai teks buku', r'pada bab ', r'pada subbab', r'pada halaman', r'sebagaimana tertulis pada halaman', r'halaman modul', r'tabel 1', r'tabel 2', r'tabel 3', r'kata kunci modul', r'dokumen modul', r'bahan ajar', r'uraian materi', r'diktat materi', r'rujukan\s+(?:resmi\s+)?modul', r'definisi\s+modul', r'sesuai\s+ketentuan\s+modul', r'ketentuan\s+modul', r'modul\s+menegaskan', r'rumusan\s+modul', r'modul\s+kepegawaian']

errors = 0
for n in flagged:
    q = soals[n]
    kunci = q['kunci_jawaban']
    alasan = q['pembahasan']['alasan_kunci']
    m = re.findall(r'opsi\s+([A-E])', alasan, re.IGNORECASE)
    if not m or m[-1].upper() != kunci:
        print(f'Soal {n}: Kunci mismatch (kunci {kunci}, alasan mentions {m})')
        errors += 1
    for opt, txt in q['pilihan'].items():
        if len(txt) > 80 or len(txt.split()) > 12:
            print(f'Soal {n}: Option {opt} too long ({len(txt)} chars, {len(txt.split())} words): {txt}')
            errors += 1
        for bl in blacklist:
            if re.search(r'\b' + bl + r'\b', txt, re.IGNORECASE):
                print(f'Soal {n}: Blacklist word "{bl}" in {opt}')
                errors += 1
    all_text = q['pertanyaan'] + ' ' + alasan
    for mp in meta_patterns:
        if re.search(mp, all_text, re.IGNORECASE):
            print(f'Soal {n}: Meta pattern "{mp}" found: {re.findall(mp, all_text, re.IGNORECASE)}')
            errors += 1

# Check key distribution
from collections import Counter
keys = [q['kunci_jawaban'] for q in d1['daftar_soal']]
print("Key distribution:", Counter(keys))
if Counter(keys) != {'A': 20, 'B': 20, 'C': 20, 'D': 20, 'E': 20}:
    print("KEY DISTRIBUTION ERROR!")
    errors += 1

# Check KW misses
kw_misses = 0
for q in d1['daftar_soal']:
    pg = q['halaman_sumber']
    text = page_texts.get(pg, '')
    for kw in q.get('kata_kunci_modul', []):
        if kw not in text:
            print(f'Soal {q["nomor"]} (Hlm {pg}): KW "{kw}" NOT FOUND')
            kw_misses += 1
if kw_misses > 0:
    print(f"Total KW misses: {kw_misses}")
    errors += kw_misses

print(f'Paket 1 validation finished with {errors} errors.')

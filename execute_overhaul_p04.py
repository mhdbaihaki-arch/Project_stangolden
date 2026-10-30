import json
import re

# Load original data
with open('soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json', 'r', encoding='utf-8') as f:
    soal_data = json.load(f)

# Load module text for keyword verification
with open('modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

page_texts = {int(p['page']): p['text'] for p in modul['pages']}

# Load all update parts
all_updates = {}
for p_idx in range(1, 6):
    fname = f'p04_updates_part{p_idx}.json'
    with open(fname, 'r', encoding='utf-8') as f:
        part_data = json.load(f)
        for k, v in part_data.items():
            all_updates[int(k)] = v

print(f"Total overhauled questions loaded: {len(all_updates)}")

# Apply updates
for q in soal_data['daftar_soal']:
    no = q['nomor']
    if no in all_updates:
        u = all_updates[no]
        # Keep original key
        assert u['kunci_jawaban'] == q['kunci_jawaban'], f"Key mismatch at Q{no}: original {q['kunci_jawaban']} vs updated {u['kunci_jawaban']}"
        q['halaman_sumber'] = u['halaman_sumber']
        q['kata_kunci_modul'] = u['kata_kunci_modul']
        q['topik_materi'] = u['topik_materi']
        q['level_kognitif'] = u['level_kognitif']
        q['pertanyaan'] = u['pertanyaan']
        q['pilihan'] = u['pilihan']
        q['kunci_jawaban'] = u['kunci_jawaban']
        q['pembahasan'] = u['pembahasan']

# Now let's run comprehensive verification
blacklist_words = [
    'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan', 
    'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak', 
    'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses', 
    'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah'
]

meta_patterns = [
    'tertulis dalam materi', 'dalam materi ini', 'pada materi', 'menurut materi',
    'dimuat dalam materi', 'disebutkan dalam materi', 'buku materi', 'dalam buku materi',
    'sesuai buku materi', 'menurut modul', 'buku modul', 'di buku modul',
    'sebagaimana tertuang di modul', 'penjelasan modul', 'buku teks', 'sesuai buku teks',
    'di dalam buku teks', 'buku panduan materi', 'buku pegangan', 'sesuai teks buku',
    'di teks buku', 'berdasarkan teks di atas', 'dalam teks bacaan', 'pada bab ',
    'pada subbab', 'pada halaman', 'sebagaimana tertulis pada halaman', 'halaman modul',
    'halaman penutup', 'halaman awal', 'tabel 1', 'tabel 2', 'tabel 3', 'kata kunci modul',
    'dokumen belajar', 'dokumen modul', 'dokumen ajar', 'bahan ajar', 'pokok materi budaya',
    'tidak termasuk dalam pembahasan', 'catatan modul', 'pengecualian resmi modul',
    'uraian materi', 'sebagaimana diuraikan dalam materi', 'paragraf di atas',
    'uraian di atas', 'kutipan teks', 'lembar kerja', 'diktat materi', 'handout'
]

bl_errors = []
meta_errors = []
kw_errors = []

for q in soal_data['daftar_soal']:
    no = q['nomor']
    hlm = q['halaman_sumber']
    key = q['kunci_jawaban']
    pilihan = q['pilihan']
    kws = q.get('kata_kunci_modul', [])
    pemb = q.get('pembahasan', {})
    
    # 1. Check blacklist words in options
    for opt_k, opt_v in pilihan.items():
        for bw in blacklist_words:
            if re.search(r'\b' + re.escape(bw) + r'\b', opt_v, re.IGNORECASE):
                bl_errors.append(f"Q{no} [{opt_k}]: found blacklist word '{bw}'")
                
    # 2. Check meta document patterns
    full_text = q['pertanyaan'] + " " + " ".join(pilihan.values()) + " " + json.dumps(pemb, ensure_ascii=False)
    for mp in meta_patterns:
        if mp in full_text.lower():
            meta_errors.append(f"Q{no}: found meta pattern '{mp}'")
            
    # 3. Check keywords in page text
    p_text = page_texts.get(hlm, "")
    for kw in kws:
        if kw not in p_text:
            kw_errors.append(f"Q{no} (Hlm {hlm}): '{kw}' NOT in page text")
            
    # 4. Check bedah pengecoh has all 4 non-key options
    non_keys = [o for o in ['A', 'B', 'C', 'D', 'E'] if o != key]
    bedah = pemb.get('bedah_pengecoh', {})
    for nk in non_keys:
        if nk not in bedah or not bedah[nk].strip():
            print(f"WARNING: Q{no} missing bedah_pengecoh for [{nk}]")

print(f"\n--- AUDIT RESULTS ---")
print(f"Total Blacklist Errors: {len(bl_errors)}")
for e in bl_errors:
    print(f"  {e}")

print(f"Total Meta Document Errors: {len(meta_errors)}")
for e in meta_errors:
    print(f"  {e}")

print(f"Total Keyword Misses: {len(kw_errors)}")
for e in kw_errors:
    print(f"  {e}")

# Check key distribution
from collections import Counter
keys = [q['kunci_jawaban'] for q in soal_data['daftar_soal']]
dist = Counter(keys)
print(f"Key Distribution: {dist}")

# Check streaks
max_streak = 1
cur_streak = 1
for i in range(1, len(keys)):
    if keys[i] == keys[i-1]:
        cur_streak += 1
        if cur_streak > max_streak:
            max_streak = cur_streak
    else:
        cur_streak = 1
print(f"Max Key Streak: {max_streak}")

# Save updated JSON if no critical errors
if len(bl_errors) == 0 and len(meta_errors) == 0 and len(kw_errors) == 0:
    with open('soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json', 'w', encoding='utf-8') as f:
        json.dump(soal_data, f, ensure_ascii=False, indent=2)
    print("\nSUCCESS: Successfully updated soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json!")
else:
    print("\nPlease fix errors before saving.")

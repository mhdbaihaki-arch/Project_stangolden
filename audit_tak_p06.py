import json, re

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_06.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

pages_dict = {p['page']: p['text'] for p in modul.get('pages', [])}

# dump lines
with open('lines_tak_p06.txt', 'w', encoding='utf-8') as f:
    for hlm in range(62, 71):
        f.write(f"=== HALAMAN {hlm} ===\n")
        txt = pages_dict.get(hlm, '')
        for line in txt.split('\n'):
            if line.strip():
                f.write(line.strip() + '\n')
        f.write('\n')

blacklist = ['mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan', 'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak', 'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses', 'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah']
meta_patterns = ['dalam materi', 'menurut modul', 'buku modul', 'buku teks', 'tabel 1', 'tabel 2', 'tabel 3', 'kata kunci modul', 'penjelasan modul', 'dokumen modul', 'catatan modul', 'uraian materi', 'bahan ajar', 'diktat materi', 'lembar kerja', 'pada bab ', 'pada halaman ', 'sebagaimana tertulis']

key_counts = {'A': 0, 'B': 0, 'C': 0, 'D': 0, 'E': 0}
last_key = None
streak = 0
max_streak = 0

print("Total questions:", len(data['daftar_soal']))

for q in data['daftar_soal']:
    no = q['nomor']
    hlm = q['halaman_sumber']
    k = q['kunci_jawaban']
    key_counts[k] += 1
    
    if k == last_key:
        streak += 1
        if streak > max_streak:
            max_streak = streak
    else:
        streak = 1
        last_key = k

    txt = pages_dict.get(hlm, '')
    for kw in q.get('kata_kunci_modul', []):
        if kw not in txt:
            print(f"Q{no} (Hlm {hlm}) KW MISS: {repr(kw)}")

    for opt_k, opt_v in q['pilihan'].items():
        for bl in blacklist:
            if re.search(r'\b' + re.escape(bl) + r'\b', opt_v, re.I):
                print(f"Q{no} [{opt_k}] BL: {bl} -> {opt_v}")

    full_t = q['pertanyaan'] + ' ' + json.dumps(q['pilihan']) + ' ' + json.dumps(q['pembahasan'])
    for mp in meta_patterns:
        if mp in full_t.lower():
            print(f"Q{no} META: {mp}")

print(f"Key counts: {key_counts}")
print(f"Max streak: {max_streak}")

import json
import glob
import re
from collections import Counter

# Load module text
with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

page_texts = {}
for p in modul.get('pages', []):
    page_texts[int(p['page'])] = p['text']

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

negative_cues = [
    'palsu', 'liar', 'firasat', 'peramal', 'seenaknya', 'tanpa dasar',
    'suka-suka', 'menghapus', 'curang', 'lambat', 'santai tanpa target',
    'keuntungan pribadi', 'kerabat dekat', 'keluarga pejabat'
]

json_files = sorted(glob.glob("soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_*.json"))

print(f"Total Modul 3 JSON files: {len(json_files)}\n")

for fpath in json_files:
    fname = fpath.split("\\")[-1]
    with open(fpath, 'r', encoding='utf-8') as f:
        data = json.load(f)
        
    soal_list = data.get('daftar_soal', [])
    keys = [q['kunci_jawaban'] for q in soal_list]
    key_dist = Counter(keys)
    
    max_streak = 1
    cur_streak = 1
    for i in range(1, len(keys)):
        if keys[i] == keys[i-1]:
            cur_streak += 1
            if cur_streak > max_streak:
                max_streak = cur_streak
        else:
            cur_streak = 1
            
    bl_hits = []
    meta_hits = []
    kw_misses = []
    cue_hits = []
    
    for q in soal_list:
        no = q['nomor']
        hlm = q['halaman_sumber']
        key = q['kunci_jawaban']
        pilihan = q['pilihan']
        pemb = q.get('pembahasan', {})
        kws = q.get('kata_kunci_modul', [])
        
        # Blacklist check
        for opt_k, opt_v in pilihan.items():
            for bw in blacklist_words:
                if re.search(r'\b' + re.escape(bw) + r'\b', opt_v, re.IGNORECASE):
                    bl_hits.append((no, opt_k, bw))
                    
        # Meta pattern check
        full_text = q['pertanyaan'] + " " + " ".join(pilihan.values()) + " " + json.dumps(pemb, ensure_ascii=False)
        for mp in meta_patterns:
            if mp in full_text.lower():
                meta_hits.append((no, mp))
                
        # Keyword check
        p_text = page_texts.get(hlm, "")
        for kw in kws:
            if kw not in p_text:
                kw_misses.append((no, hlm, kw))
                
        # Negative cues
        for opt_k, opt_v in pilihan.items():
            if opt_k != key:
                for c in negative_cues:
                    if c in opt_v.lower():
                        cue_hits.append((no, opt_k, c, opt_v))
                        
    print(f"=== {fname} ===")
    print(f"Jumlah soal: {len(soal_list)}")
    print(f"Rentang halaman: {data.get('rentang_halaman')}")
    print(f"Distribusi kunci: {dict(key_dist)} (Max streak: {max_streak})")
    print(f"Blacklist hits: {len(bl_hits)}")
    if bl_hits:
        print(f"  Sample: {bl_hits[:5]}")
    print(f"Meta-dokumen hits: {len(meta_hits)}")
    if meta_hits:
        print(f"  Sample: {meta_hits[:5]}")
    print(f"Keyword misses: {len(kw_misses)}")
    if kw_misses:
        print(f"  Sample: {kw_misses[:5]}")
    print(f"Negative cue hits: {len(cue_hits)}")
    if cue_hits:
        print(f"  Sample: {cue_hits[:5]}")
    print("-" * 50 + "\n")

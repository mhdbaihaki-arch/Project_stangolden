import json
import re

with open('soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

soal_list = data['daftar_soal']

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

cues_negative = [
    'tanpa', 'pribadi', 'menunda', 'membatasi', 'liar', 'menghapus', 'santai', 
    'menyeragamkan', 'mengurangi', 'memusatkan', 'mengambil alih', 'firasat',
    'keuntungan finansial', 'kebencian', 'permusuhan', 'kelalaian', 'beban',
    'terpencil', 'kesalahan rekan', 'menjatuhkan', 'paksaan', 'merugikan'
]

results = []

for q in soal_list:
    no = q['nomor']
    key = q['kunci_jawaban']
    pilihan = q['pilihan']
    kunci_text = pilihan[key]
    
    # Check blacklist
    bl_hits = []
    for opt_k, opt_v in pilihan.items():
        for bw in blacklist_words:
            if re.search(r'\b' + re.escape(bw) + r'\b', opt_v, re.IGNORECASE):
                bl_hits.append(f"[{opt_k}] {bw}")
                
    # Check meta in question, choices, explanation
    full_text = q['pertanyaan'] + " " + " ".join(pilihan.values()) + " " + json.dumps(q.get('pembahasan', {}), ensure_ascii=False)
    meta_hits = []
    for mp in meta_patterns:
        if mp in full_text.lower():
            meta_hits.append(mp)
            
    # Check suspicious negative cues in distractors
    cue_hits = []
    for opt_k, opt_v in pilihan.items():
        if opt_k != key:
            for c in cues_negative:
                if re.search(r'\b' + re.escape(c) + r'\b', opt_v, re.IGNORECASE):
                    cue_hits.append(f"[{opt_k}] '{c}': {opt_v}")
                    
    results.append({
        'no': no,
        'hlm': q['halaman_sumber'],
        'key': key,
        'q': q['pertanyaan'],
        'pilihan': pilihan,
        'bl_hits': bl_hits,
        'meta_hits': meta_hits,
        'cue_hits': cue_hits
    })

print(f"Total soal: {len(results)}")
bl_total = sum(len(r['bl_hits']) for r in results)
meta_total = sum(len(r['meta_hits']) for r in results)
cue_questions = [r for r in results if r['cue_hits'] or r['bl_hits']]

print(f"Total blacklist hits: {bl_total}")
print(f"Total meta hits: {meta_total}")
print(f"Total questions with flagged distractor cues/blacklist: {len(cue_questions)}")

with open('p04_flagged_details.txt', 'w', encoding='utf-8') as out:
    for r in results:
        out.write(f"=== No {r['no']} (Hlm {r['hlm']}, Key {r['key']}) ===\n")
        out.write(f"Q: {r['q']}\n")
        for k, v in r['pilihan'].items():
            mark = "(*KUNCI*)" if k == r['key'] else ""
            out.write(f"  {k}: {v} {mark}\n")
        if r['bl_hits']:
            out.write(f"  BLACKLIST HITS: {r['bl_hits']}\n")
        if r['meta_hits']:
            out.write(f"  META HITS: {r['meta_hits']}\n")
        if r['cue_hits']:
            out.write(f"  FLAGGED CUES:\n")
            for c in r['cue_hits']:
                out.write(f"    - {c}\n")
        out.write("\n")

print("Saved detailed review to p04_flagged_details.txt")

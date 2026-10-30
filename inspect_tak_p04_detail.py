import json
from collections import Counter

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_04.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

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

qs = data['daftar_soal']
print(f"Total questions: {len(qs)}")
key_counts = Counter(q['kunci_jawaban'] for q in qs)
print(f"Key counts: {key_counts}")

by_page = {}
for q in qs:
    by_page.setdefault(q['halaman_sumber'], []).append(q['kunci_jawaban'])

for p, keys in sorted(by_page.items()):
    print(f"Page {p} (count {len(keys)}): {Counter(keys)} -> {keys}")

print("\n--- DETAILED AUDIT FINDINGS ---")
for q in qs:
    num = q['nomor']
    hlm = q['halaman_sumber']
    kunci = q['kunci_jawaban']
    p_text = page_texts.get(hlm, '')
    
    # 1. Keywords
    kw_misses = [kw for kw in q.get('kata_kunci_modul', []) if kw not in p_text]
    if kw_misses:
        print(f"Q{num} (Hlm {hlm}) KW MISS: {kw_misses}")
        
    # 2. Blacklist
    for opt, text in q['pilihan'].items():
        for b in blacklist:
            if b in text.lower():
                print(f"Q{num} [{opt}] BLACKLIST '{b}': {text}")
                
    # 3. Meta patterns
    full_str = json.dumps(q, ensure_ascii=False).lower()
    for m in meta_patterns:
        if m in full_str:
            print(f"Q{num} META '{m}'")
            
    # 4. Negative cues in choices
    for opt, text in q['pilihan'].items():
        if opt != kunci:
            for nc in ["menghapus", "memotong", "meniadakan", "menutup", "memecat", "pungutan", "suap", "nepotisme"]:
                if nc in text.lower():
                    print(f"Q{num} [{opt}] NEG CUE '{nc}': {text}")

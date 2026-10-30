import json
import os
import re

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
with open(os.path.join(scratch_dir, "wk_p07_raw_text.txt"), "r", encoding="utf-8") as f:
    raw_text = f.read()

pages = raw_text.split("=== HALAMAN ")
page_dict = {}
for p in pages[1:]:
    num = int(p.split(" ")[0])
    page_dict[num] = p

draft_files = [
    "draft_p07_sub1_hlm66_67.json",
    "draft_p07_sub2_hlm68_69.json",
    "draft_p07_sub3_hlm70_71.json",
    "draft_p07_sub4_hlm72_73.json",
    "draft_p07_sub5_hlm74_75.json"
]

all_questions = []
for df in draft_files:
    with open(os.path.join(scratch_dir, df), "r", encoding="utf-8") as f:
        all_questions.extend(json.load(f))

print(f"Total loaded questions for Paket 07: {len(all_questions)}")

banned_words = [
    "mengabaikan", "menolak", "memalsukan", "korupsi", "pungli", "membiarkan", 
    "lepas tangan", "menyembunyikan", "melanggar", "seenaknya", "sepihak", 
    "semena-mena", "pasif", "kaku", "ego sektoral", "menutup akses", 
    "menghindari", "melempar tanggung jawab", "hanya", "semata-mata", "tidak pernah"
]

meta_patterns = [
    r"sebagaimana tertulis dalam materi", r"tertulis dalam materi", r"dalam materi ini",
    r"pada materi", r"menurut materi", r"dimuat dalam materi", r"disebutkan dalam materi",
    r"buku materi", r"dalam buku materi", r"sesuai buku materi", r"menurut modul",
    r"menurut modul ini", r"dalam modul ini", r"buku modul", r"dalam buku modul",
    r"di buku modul", r"sebagaimana tertuang di modul", r"penjelasan modul",
    r"buku teks", r"sesuai buku teks", r"di dalam buku teks", r"buku panduan materi",
    r"buku pegangan", r"sesuai teks buku", r"di teks buku", r"berdasarkan teks di atas",
    r"dalam teks bacaan", r"pada Bab [I|V|X]+", r"pada bab ini", r"pada Bab Penutup",
    r"pada bab pendahuluan", r"pada subbab ini", r"pada bagian subbab", r"pada halaman ini",
    r"pada halaman \d+", r"sebagaimana tertulis pada halaman", r"halaman modul",
    r"halaman penutup", r"halaman awal", r"Tabel \d+", r"sesuai kata kunci modul",
    r"kata kunci modul", r"dokumen belajar", r"dokumen modul", r"dokumen ajar",
    r"bahan ajar", r"dalam bahan ajar ini", r"sesuai bahan ajar",
    r"pokok materi budaya berfokus pada", r"tidak termasuk dalam pembahasan ini",
    r"catatan modul", r"catatan sejarah modul", r"pengecualian resmi modul",
    r"rujukan modul", r"uraian materi", r"sebagaimana diuraikan dalam materi",
    r"paragraf di atas", r"uraian di atas", r"kutipan teks", r"lembar kerja",
    r"diktat materi", r"handout"
]

kw_errors = []
bl_errors = []
meta_errors = []

for idx, q in enumerate(all_questions, 1):
    hlm = q["halaman_sumber"]
    p_text = page_dict.get(hlm, "")
    
    # Check keywords against that specific page text
    for kw in q.get("kata_kunci_modul", []):
        norm_kw = " ".join(kw.split()).lower()
        norm_page = " ".join(p_text.split()).lower()
        if norm_kw not in norm_page:
            kw_errors.append(f"Q{idx} (Hlm {hlm}): Keyword {kw!r} not in page text")
            
    # Check blacklist & meta
    items = []
    for k, v in q.get("pilihan", {}).items():
        items.append((f"pilihan {k}", v))
    items.append(("pertanyaan", q.get("pertanyaan", "")))
    pemb = q.get("pembahasan", {})
    items.append(("konsep_materi", pemb.get("konsep_materi", "")))
    items.append(("alasan_kunci", pemb.get("alasan_kunci", "")))
    for k, v in pemb.get("bedah_pengecoh", {}).items():
        items.append((f"bedah_pengecoh {k}", v))
        
    for label, text in items:
        t_lower = text.lower()
        for bw in banned_words:
            pattern = r'\b' + re.escape(bw) + r'\b'
            if re.search(pattern, t_lower):
                bl_errors.append(f"Q{idx} [{label}]: contains banned word '{bw}' in: {text!r}")
        for mp in meta_patterns:
            if re.search(mp, text, re.IGNORECASE):
                meta_errors.append(f"Q{idx} [{label}]: matches meta pattern '{mp}' in: {text!r}")

print(f"Total keyword errors: {len(kw_errors)}")
for e in kw_errors:
    print("  ", e)

print(f"Total blacklist errors: {len(bl_errors)}")
for e in bl_errors:
    print("  ", e)

print(f"Total meta errors: {len(meta_errors)}")
for e in meta_errors:
    print("  ", e)

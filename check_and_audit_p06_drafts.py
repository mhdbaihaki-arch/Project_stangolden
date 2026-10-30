import json
import os
import re

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"

# Load raw text
with open(os.path.join(scratch_dir, "wk_p06_raw_text.txt"), "r", encoding="utf-8") as f:
    raw_text = f.read()

# Load all 5 drafts
draft_files = [
    "draft_p06_sub1_hlm56_57.json",
    "draft_p06_sub2_hlm58_59.json",
    "draft_p06_sub3_hlm60_61.json",
    "draft_p06_sub4_hlm62_63.json",
    "draft_p06_sub5_hlm64_65.json"
]

all_questions = []
for df in draft_files:
    p = os.path.join(scratch_dir, df)
    with open(p, "r", encoding="utf-8") as f:
        data = json.load(f)
        all_questions.extend(data)

print(f"Total loaded questions: {len(all_questions)}")

# Blacklist words
banned_words = [
    "mengabaikan", "menolak", "memalsukan", "korupsi", "pungli", "membiarkan", 
    "lepas tangan", "menyembunyikan", "melanggar", "seenaknya", "sepihak", 
    "semena-mena", "pasif", "kaku", "ego sektoral", "menutup akses", 
    "menghindari", "melempar tanggung jawab", "hanya", "semata-mata", "tidak pernah"
]

# Meta patterns
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

print("--- AUDITING QUESTIONS ---")
kw_errors = []
bl_errors = []
meta_errors = []

for idx, q in enumerate(all_questions, 1):
    q_str = json.dumps(q, ensure_ascii=False)
    
    # 1. Check keywords
    kws = q.get("kata_kunci_modul", [])
    if not kws:
        kw_errors.append(f"Q{idx} has empty kata_kunci_modul")
    for kw in kws:
        if kw not in raw_text:
            kw_errors.append(f"Q{idx} keyword not in text: {kw!r}")
            
    # 2. Check blacklist in choices & explanations
    text_to_check = []
    for opt_k, opt_v in q.get("pilihan", {}).items():
        text_to_check.append((f"pilihan {opt_k}", opt_v))
    pemb = q.get("pembahasan", {})
    text_to_check.append(("konsep_materi", pemb.get("konsep_materi", "")))
    text_to_check.append(("alasan_kunci", pemb.get("alasan_kunci", "")))
    for bp_k, bp_v in pemb.get("bedah_pengecoh", {}).items():
        text_to_check.append((f"bedah_pengecoh {bp_k}", bp_v))
        
    for label, t in text_to_check:
        t_lower = t.lower()
        for bw in banned_words:
            # Check whole word match or phrase
            pattern = r'\b' + re.escape(bw) + r'\b'
            if re.search(pattern, t_lower):
                bl_errors.append(f"Q{idx} [{label}]: contains banned word '{bw}' in: {t[:60]}...")
                
        for mp in meta_patterns:
            if re.search(mp, t, re.IGNORECASE):
                meta_errors.append(f"Q{idx} [{label}]: matches meta pattern '{mp}' in: {t[:60]}...")

print(f"Keyword errors: {len(kw_errors)}")
for e in kw_errors[:15]:
    print("  ", e)

print(f"Blacklist errors: {len(bl_errors)}")
for e in bl_errors[:15]:
    print("  ", e)

print(f"Meta errors: {len(meta_errors)}")
for e in meta_errors[:15]:
    print("  ", e)

import json
import os
import re

# Blacklist of 21 words
BLACKLIST_WORDS = [
    "mengabaikan", "menolak", "memalsukan", "korupsi", "pungli", 
    "membiarkan", "lepas tangan", "menyembunyikan", "melanggar", 
    "seenaknya", "sepihak", "semena-mena", "pasif", "kaku", 
    "ego sektoral", "menutup akses", "menghindari", "melempar tanggung jawab", 
    "hanya", "semata-mata", "tidak pernah"
]

# 30 meta-document patterns
META_PATTERNS = [
    r"sebagaimana tertulis dalam materi", r"tertulis dalam materi",
    r"dalam materi ini", r"pada materi", r"menurut materi",
    r"dimuat dalam materi", r"disebutkan dalam materi",
    r"buku materi", r"dalam buku materi", r"sesuai buku materi",
    r"menurut modul", r"menurut modul ini", r"dalam modul ini",
    r"buku modul", r"dalam buku modul", r"di buku modul",
    r"sebagaimana tertuang di modul", r"penjelasan modul",
    r"buku teks", r"sesuai buku teks", r"di dalam buku teks",
    r"buku panduan materi", r"buku pegangan",
    r"sesuai teks buku", r"di teks buku",
    r"berdasarkan teks di atas", r"dalam teks bacaan",
    r"pada bab [ivxldcm]+", r"pada bab ini", r"pada bab penutup", r"pada bab pendahuluan",
    r"pada subbab ini", r"pada bagian subbab",
    r"pada halaman ini", r"pada halaman \d+",
    r"sebagaimana tertulis pada halaman", r"halaman modul", r"halaman penutup", r"halaman awal",
    r"tabel \d+", r"berdasarkan tabel \d+", r"dalam tabel \d+", r"sebagaimana dirinci pada tabel \d+",
    r"sesuai kata kunci modul", r"kata kunci modul",
    r"dokumen belajar", r"dokumen modul", r"dokumen ajar",
    r"bahan ajar", r"dalam bahan ajar ini", r"sesuai bahan ajar",
    r"pokok materi budaya berfokus pada",
    r"tidak termasuk dalam pembahasan ini",
    r"catatan modul", r"catatan sejarah modul",
    r"pengecualian resmi modul", r"rujukan modul",
    r"uraian materi", r"sebagaimana diuraikan dalam materi",
    r"paragraf di atas", r"uraian di atas", r"kutipan teks",
    r"lembar kerja", r"diktat materi", r"handout"
]

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"

# Load 5 parts
all_questions = []
for i in range(1, 6):
    file_map = {
        1: "draft_p04_sub1_hlm36_37.json",
        2: "draft_p04_sub2_hlm38_39.json",
        3: "draft_p04_sub3_hlm40_41.json",
        4: "draft_p04_sub4_hlm42_43.json",
        5: "draft_p04_sub5_hlm44_45.json"
    }
    path = os.path.join(scratch_dir, file_map[i])
    with open(path, "r", encoding="utf-8") as f:
        data = json.load(f)
        all_questions.extend(data)

print(f"Total loaded questions: {len(all_questions)}")

# Renumber 1 to 100
for idx, q in enumerate(all_questions, 1):
    q["nomor"] = idx

# Audit blacklist and meta-document patterns in questions & choices
audit_issues = []
for q in all_questions:
    q_num = q["nomor"]
    # check blacklist in choices
    for opt, text in q["pilihan"].items():
        for b in BLACKLIST_WORDS:
            # check whole word match
            pattern = r'\b' + re.escape(b) + r'\b'
            if re.search(pattern, text, re.IGNORECASE):
                audit_issues.append((q_num, f"Pilihan {opt}", f"Blacklist word '{b}' found in: {text}"))
    
    # check meta patterns in question and discussion
    full_text = q["pertanyaan"] + " " + q["pembahasan"]["konsep_materi"] + " " + q["pembahasan"]["alasan_kunci"]
    for opt, exp in q["pembahasan"]["bedah_pengecoh"].items():
        full_text += " " + exp
    for mp in META_PATTERNS:
        if re.search(mp, full_text, re.IGNORECASE):
            audit_issues.append((q_num, "Meta pattern", f"Meta pattern '{mp}' found"))

if audit_issues:
    print(f"Found {len(audit_issues)} audit issues:")
    for issue in audit_issues:
        print(issue)
else:
    print("Zero distractor blacklist and zero meta-document patterns detected!")

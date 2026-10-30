import json
import os
import re

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
draft_files = [
    "draft_p06_sub1_hlm56_57.json",
    "draft_p06_sub2_hlm58_59.json",
    "draft_p06_sub3_hlm60_61.json",
    "draft_p06_sub4_hlm62_63.json",
    "draft_p06_sub5_hlm64_65.json"
]

all_questions = []
for df in draft_files:
    with open(os.path.join(scratch_dir, df), "r", encoding="utf-8") as f:
        all_questions.extend(json.load(f))

banned_words = [
    "mengabaikan", "menolak", "memalsukan", "korupsi", "pungli", "membiarkan", 
    "lepas tangan", "menyembunyikan", "melanggar", "seenaknya", "sepihak", 
    "semena-mena", "pasif", "kaku", "ego sektoral", "menutup akses", 
    "menghindari", "melempar tanggung jawab", "hanya", "semata-mata", "tidak pernah"
]

for idx, q in enumerate(all_questions, 1):
    items = []
    for k, v in q.get("pilihan", {}).items():
        items.append((f"pilihan {k}", v))
    items.append(("konsep_materi", q["pembahasan"].get("konsep_materi", "")))
    items.append(("alasan_kunci", q["pembahasan"].get("alasan_kunci", "")))
    for k, v in q["pembahasan"].get("bedah_pengecoh", {}).items():
        items.append((f"bedah_pengecoh {k}", v))
        
    for loc, text in items:
        for bw in banned_words:
            pattern = r'\b' + re.escape(bw) + r'\b'
            if re.search(pattern, text, re.IGNORECASE):
                print(f"Q{idx} [{loc}]: '{bw}' in -> {text!r}")

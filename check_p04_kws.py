import json
import os

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
with open(os.path.join(scratch_dir, "wk_p04_raw_text.txt"), "r", encoding="utf-8") as f:
    raw_text = f.read()

# Load all questions
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
        all_questions.extend(json.load(f))

# Check exact verbatim match
kw_issues = []
for idx, q in enumerate(all_questions, 1):
    hlm = q["halaman_sumber"]
    # get text for this page
    marker = f"=== HALAMAN {hlm} ==="
    next_marker = f"=== HALAMAN {hlm+1} ==="
    p_start = raw_text.find(marker)
    p_end = raw_text.find(next_marker) if next_marker in raw_text else len(raw_text)
    page_text = raw_text[p_start:p_end] if p_start != -1 else raw_text
    
    # normalize whitespace for matching
    norm_page = " ".join(page_text.split()).lower()
    
    for kw in q["kata_kunci_modul"]:
        norm_kw = " ".join(kw.split()).lower()
        if norm_kw not in norm_page:
            kw_issues.append((idx, hlm, kw))

print(f"Total keyword issues: {len(kw_issues)}")
for issue in kw_issues:
    print(issue)

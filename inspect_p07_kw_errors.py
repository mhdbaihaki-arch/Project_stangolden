import json
import os

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

kw_errors = []
for idx, q in enumerate(all_questions, 1):
    hlm = q["halaman_sumber"]
    p_text = page_dict.get(hlm, "")
    for kw in q.get("kata_kunci_modul", []):
        norm_kw = " ".join(kw.split()).lower()
        norm_page = " ".join(p_text.split()).lower()
        if norm_kw not in norm_page:
            kw_errors.append((idx, hlm, kw))

print(f"Total keyword errors: {len(kw_errors)}")
for idx, hlm, kw in kw_errors:
    print(f"Q{idx} (Hlm {hlm}): {kw!r}")

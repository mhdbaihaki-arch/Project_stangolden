import os

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
with open(os.path.join(scratch_dir, "wk_p06_raw_text.txt"), "r", encoding="utf-8") as f:
    lines = f.readlines()

for idx, l in enumerate(lines):
    if "Satu Nusa" in l or "wawasan" in l.lower() and "etimologis" in l.lower():
        print(f"Line {idx}: {l.strip()!r}")

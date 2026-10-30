import os

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
path = os.path.join(scratch_dir, "wk_p07_raw_text.txt")
with open(path, "r", encoding="utf-8") as f:
    text = f.read()

pages = text.split("=== HALAMAN ")
print(f"Total parts in wk_p07_raw_text.txt: {len(pages)-1}")
for p in pages[1:]:
    lines = [l.strip() for l in p.splitlines() if l.strip()]
    header = lines[0] if lines else "EMPTY"
    page_num = header.split(" ")[0]
    sample = lines[1:5] if len(lines) > 1 else []
    print(f"Page {page_num}: {len(lines)} lines, sample: {sample}")

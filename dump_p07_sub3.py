import os

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
with open(os.path.join(scratch_dir, "wk_p07_raw_text.txt"), "r", encoding="utf-8") as f:
    text = f.read()

pages = text.split("=== HALAMAN ")
p70 = [p for p in pages if p.startswith("70 ")][0]
p71 = [p for p in pages if p.startswith("71 ")][0]

print("=== HALAMAN 70 ===")
for l in p70.splitlines():
    if l.strip():
        print(repr(l.strip()))

print("\n=== HALAMAN 71 ===")
for l in p71.splitlines():
    if l.strip():
        print(repr(l.strip()))

import os

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
with open(os.path.join(scratch_dir, "wk_p07_raw_text.txt"), "r", encoding="utf-8") as f:
    text = f.read()

pages = text.split("=== HALAMAN ")
p66 = [p for p in pages if p.startswith("66 ")][0]
p67 = [p for p in pages if p.startswith("67 ")][0]

print("=== HALAMAN 66 ===")
for l in p66.splitlines():
    if l.strip():
        print(repr(l.strip()))

print("\n=== HALAMAN 67 ===")
for l in p67.splitlines():
    if l.strip():
        print(repr(l.strip()))

import os

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
with open(os.path.join(scratch_dir, "wk_p07_raw_text.txt"), "r", encoding="utf-8") as f:
    text = f.read()

pages = text.split("=== HALAMAN ")
p72 = [p for p in pages if p.startswith("72 ")][0]
p73 = [p for p in pages if p.startswith("73 ")][0]

print("=== HALAMAN 72 ===")
for l in p72.splitlines():
    if l.strip():
        print(repr(l.strip()))

print("\n=== HALAMAN 73 ===")
for l in p73.splitlines():
    if l.strip():
        print(repr(l.strip()))

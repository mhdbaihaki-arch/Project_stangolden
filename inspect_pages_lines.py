import json
import os

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
with open(os.path.join(scratch_dir, "wk_p06_raw_text.txt"), "r", encoding="utf-8") as f:
    raw_text = f.read()

pages = raw_text.split("=== HALAMAN ")
p57 = [p for p in pages if p.startswith("57 ")][0]
p59 = [p for p in pages if p.startswith("59 ")][0]
p61 = [p for p in pages if p.startswith("61 ")][0]
p62 = [p for p in pages if p.startswith("62 ")][0]

print("--- P57 LINES ---")
for l in p57.splitlines():
    if l.strip():
        print(repr(l.strip()))

print("\n--- P59 LINES ---")
for l in p59.splitlines():
    if l.strip():
        print(repr(l.strip()))

print("\n--- P61 LINES ---")
for l in p61.splitlines():
    if l.strip():
        print(repr(l.strip()))

print("\n--- P62 LINES ---")
for l in p62.splitlines():
    if l.strip():
        print(repr(l.strip()))

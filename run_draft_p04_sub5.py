import sys
import shutil

src = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch\draft_p04_sub5_hlm44_45.py"
with open(src, "r", encoding="utf-8") as f:
    code = f.read()

exec(code)

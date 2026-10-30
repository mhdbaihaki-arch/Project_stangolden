import sys
import os

scratch_path = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
sys.path.append(scratch_path)
from build_wk_paket_php import build_wk_paket

build_wk_paket(4, 36, 45)
print("Paket 04 PHP built successfully!")

import sys
import os

scratch_path = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
sys.path.append(scratch_path)
from build_tak_paket_php import build_tak_paket

build_tak_paket(2, 21, 30)
print("Paket 02 PHP built successfully!")

import sys
sys.path.insert(0, r'C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch')

from build_wk_paket_php import build_wk_paket

ranges = {
    1: (6, 15),
    2: (16, 25),
    3: (26, 35),
    4: (36, 45),
    5: (46, 55),
    6: (56, 65),
    7: (66, 75)
}

for pno, (hlm_start, hlm_end) in ranges.items():
    build_wk_paket(pno, hlm_start, hlm_end)
    print(f"Rebuilt WK Paket {pno:02d} PHP!")

print("All WK PHP files rebuilt successfully!")

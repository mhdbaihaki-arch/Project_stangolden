import os

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
with open(os.path.join(scratch_dir, "wk_p05_raw_text.txt"), "r", encoding="utf-8") as f:
    text = f.read()

def find_phrase(p_num, phrase):
    marker = f"=== HALAMAN {p_num} ==="
    next_m = f"=== HALAMAN {p_num+1} ==="
    p_start = text.find(marker)
    p_end = text.find(next_m) if next_m in text else len(text)
    p_text = text[p_start:p_end]
    print(f"--- Page {p_num} search '{phrase}' ---")
    for line in p_text.splitlines():
        if any(w.lower() in line.lower() for w in phrase.split()[:2]):
            print("  ", line.strip())

find_phrase(46, "tiga puluh hari")
find_phrase(46, "Keuangan dan administratif")
find_phrase(48, "Legislasi")
find_phrase(48, "peraturan pemerintah pengganti")
find_phrase(49, "Interpelasi")
find_phrase(49, "Imunitas")
find_phrase(51, "Menguji secara")
find_phrase(51, "Bersifat mandiri")
find_phrase(53, "dibagi atas")

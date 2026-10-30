import os

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
with open(os.path.join(scratch_dir, "wk_p06_raw_text.txt"), "r", encoding="utf-8") as f:
    text = f.read()

def show_full(num):
    marker = f"=== HALAMAN {num} ==="
    next_m = f"=== HALAMAN {num+1} ==="
    start = text.find(marker)
    end = text.find(next_m) if next_m in text else len(text)
    print(f"=== FULL PAGE {num} ===")
    print(text[start:end])

show_full(62)
show_full(63)

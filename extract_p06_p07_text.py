import json
import os

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
os.makedirs(scratch_dir, exist_ok=True)

with open("modul_json/5. Wawasan Kebangsaan.json", "r", encoding="utf-8") as f:
    data = json.load(f)

pages_dict = {}
for p in data["pages"]:
    p_num = p.get("page") or p.get("halaman")
    content = p.get("content") or p.get("text") or ""
    pages_dict[p_num] = content

# Write Paket 06 (Hlm 56 - 65)
p06_text = ""
for p_num in range(56, 66):
    p06_text += f"\n========================================\n"
    p06_text += f"=== HALAMAN {p_num} ===\n"
    p06_text += f"========================================\n\n"
    p06_text += pages_dict.get(p_num, "") + "\n"

with open(os.path.join(scratch_dir, "wk_p06_raw_text.txt"), "w", encoding="utf-8") as f:
    f.write(p06_text)
print("Saved wk_p06_raw_text.txt")

# Write Paket 07 (Hlm 66 - 75)
p07_text = ""
for p_num in range(66, 76):
    p07_text += f"\n========================================\n"
    p07_text += f"=== HALAMAN {p_num} ===\n"
    p07_text += f"========================================\n\n"
    p07_text += pages_dict.get(p_num, "") + "\n"

with open(os.path.join(scratch_dir, "wk_p07_raw_text.txt"), "w", encoding="utf-8") as f:
    f.write(p07_text)
print("Saved wk_p07_raw_text.txt")

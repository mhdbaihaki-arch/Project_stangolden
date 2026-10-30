import json
import os

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
with open(os.path.join(scratch_dir, "wk_p06_raw_text.txt"), "r", encoding="utf-8") as f:
    raw_text = f.read()

pages = raw_text.split("=== HALAMAN ")
page_dict = {}
for p in pages[1:]:
    num = int(p.split(" ")[0])
    page_dict[num] = p

# Let's inspect pages 56, 57, 59, 61, 62, 63
check_q = [
    (5, 56, "17 agustus"),
    (19, 57, "orde baru"),
    (35, 59, "21 mei"),
    (37, 59, "19 oktober"),
    (60, 61, "sidang tahunan"),
    (67, 62, "pendidikan"),
    (80, 63, "bab iv")
]

for q_num, hlm, kw in check_q:
    print(f"\n--- Checking Q{q_num} on Hlm {hlm} for '{kw}' ---")
    p_text = page_dict[hlm]
    for line in p_text.splitlines():
        if kw.lower() in line.lower():
            print("  FOUND:", repr(line.strip()))

# Check Q80 text
json_path = r"c:\Users\Akhlan_Dev\Downloads\project\soal_wawasan_kebangsaan_json\5. Wawasan Kebangsaan_paket_06.json"
with open(json_path, "r", encoding="utf-8") as f:
    data = json.load(f)
q80 = data["daftar_soal"][79]
print("\n--- Q80 content ---")
print("Pertanyaan:", q80["pertanyaan"])
print("Konsep materi:", q80["pembahasan"]["konsep_materi"])
print("Alasan kunci:", q80["pembahasan"]["alasan_kunci"])
print("Bedah pengecoh:", q80["pembahasan"]["bedah_pengecoh"])

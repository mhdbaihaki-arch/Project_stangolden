import json

json_path = r"c:\Users\Akhlan_Dev\Downloads\project\soal_wawasan_kebangsaan_json\5. Wawasan Kebangsaan_paket_04.json"
with open(json_path, "r", encoding="utf-8") as f:
    data = json.load(f)

for idx in [46, 54]:
    q = data["daftar_soal"][idx]
    print(f"--- Q{q['nomor']} ---")
    print("Konsep:", q["pembahasan"]["konsep_materi"])
    print("Alasan:", q["pembahasan"]["alasan_kunci"])
    print("Bedah:", q["pembahasan"]["bedah_pengecoh"])

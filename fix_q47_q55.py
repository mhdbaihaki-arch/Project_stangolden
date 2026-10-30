import json

json_path = r"c:\Users\Akhlan_Dev\Downloads\project\soal_wawasan_kebangsaan_json\5. Wawasan Kebangsaan_paket_04.json"
with open(json_path, "r", encoding="utf-8") as f:
    data = json.load(f)

data["daftar_soal"][46]["pertanyaan"] = "Dalam sistem peraturan perundang-undangan di Indonesia, definisi resmi dari 'Peraturan Menteri' adalah peraturan yang..."
data["daftar_soal"][54]["pertanyaan"] = "Contoh konvensi ketatanegaraan yang terpelihara secara rutin dalam praktik ketatanegaraan Indonesia adalah..."

with open(json_path, "w", encoding="utf-8") as f:
    json.dump(data, f, indent=2, ensure_ascii=False)

print("Q47 and Q55 fixed!")

import json
import os
import re

json_path = r"c:\Users\Akhlan_Dev\Downloads\project\soal_wawasan_kebangsaan_json\5. Wawasan Kebangsaan_paket_06.json"
with open(json_path, "r", encoding="utf-8") as f:
    data = json.load(f)

questions = data["daftar_soal"]

# 1. Update keywords for Q5, Q19, Q35, Q37, Q60, Q67
questions[4]["kata_kunci_modul"] = ["tanggal 17 Agustus 1950 di seluruh Negara Kesatuan Republik"]
questions[18]["kata_kunci_modul"] = ["mengamankan negara. Lahirnya Supersemar dianggap sebagai lahirnya Orde"]
questions[34]["kata_kunci_modul"] = ["Pemerintahan Orde Baru berakhir."]
questions[36]["kata_kunci_modul"] = ["Mengadakan perubahan pertama UUD 1945 yang ditetapkan tanggal 19"]
questions[59]["kata_kunci_modul"] = ["agenda Sidang Tahunan MPR dari tahun 1999 hingga perubahan keempat pada"]
questions[66]["kata_kunci_modul"] = ["pendidikan", "kebudayaan,"]

# 2. Rewrite Q80 to be 100% standalone, no meta-document patterns
# Note: In page 8 (hlm 63), questions are Q71-Q80. Q80 is at index 79.
# The target key for Q80 in page 8 pattern (C, D, E, A, B, C, D, E, A, B) is 'B'!
questions[79] = {
    "nomor": 80,
    "halaman_sumber": 63,
    "kata_kunci_modul": [
        "persatuan sebuah bangsa yang majemuk."
    ],
    "topik_materi": "Alasan Mempertahankan Bentuk Negara Kesatuan",
    "level_kognitif": "C2 - Pemahaman",
    "pertanyaan": "Alasan utama kesepakatan MPR mempertahankan Negara Kesatuan Republik Indonesia sebagai bentuk final negara adalah karena dinilai paling tepat untuk...",
    "pilihan": {
        "A": "Memusatkan seluruh pengelolaan penerimaan pajak daerah",
        "B": "Mewadahi ide persatuan sebuah bangsa yang majemuk",
        "C": "Menyeragamkan struktur lembaga adat di seluruh kepulauan",
        "D": "Menghapuskan garis batas administratif antarprovinsi",
        "E": "Membatasi mobilitas penduduk antarpulau di nusantara"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Kesepakatan mempertahankan bentuk **Negara Kesatuan Republik Indonesia sebagai bentuk final** didasari pertimbangan bahwa negara kesatuan dipandang paling tepat untuk **mewadahi ide persatuan sebuah bangsa yang majemuk**.",
        "alasan_kunci": "Opsi B tepat karena bentuk negara kesatuan merupakan wadah yang paling kokoh dan representatif dalam mengintegrasikan keanekaragaman suku, agama, dan budaya di seluruh wilayah nusantara. Pilihan bentuk ini telah dikukuhkan sejak awal kemerdekaan guna mencegah potensi perpecahan bangsa. Kesepakatan konstitusional ini menjadi pilar utama keutuhan geopolitik nasional.",
        "bedah_pengecoh": {
            "A": "Pengelolaan pajak daerah diatur dalam kerangka desentralisasi fiskal dan perimbangan keuangan.",
            "C": "Menyeragamkan lembaga adat bertentangan dengan prinsip penghormatan kesatuan masyarakat hukum adat.",
            "D": "Menghapuskan batas administratif tidak sesuai dengan pembagian wilayah pemerintahan daerah provinsi dan kabupaten/kota.",
            "E": "Membatasi mobilitas penduduk bertentangan dengan hak asasi warga negara untuk berpindah tempat tinggal."
        },
        "sumber_rujukan": "Wawasan Kebangsaan"
    }
}

with open(json_path, "w", encoding="utf-8") as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print("Perfect fixes applied to Paket 06 JSON!")

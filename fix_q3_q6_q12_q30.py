import json

with open('tak_p01_part1.json', 'r', encoding='utf-8') as f:
    d1 = json.load(f)

# Q3 fix
d1['3']['pembahasan']['alasan_kunci'] = (
    "Opsi C merupakan lembaga legislatif pemegang kekuasaan membentuk undang-undang yang tepat. "
    "Sesuai konstitusi, persetujuan bersama antara DPR dan Presiden menjadi syarat sah lahirnya undang-undang baru. "
    "Ketentuan hukum menegaskan persetujuan pengesahan RUU ASN dilakukan oleh DPR-RI. "
    "Maka dari itu, opsi C adalah jawaban yang benar."
)

# Q6 fix
d1['6']['pilihan']['D'] = "Disesuaikan berdasarkan kebijakan otonomi daerah di wilayah masing-masing"
d1['6']['pembahasan']['alasan_kunci'] = (
    "Opsi A memuat syarat kumulatif keabsahan hukum aturan turunan lama. "
    "Prinsip ini mencegah kekosongan hukum (rechtsvacuum) selama peraturan pelaksanaan yang baru sedang disusun oleh instansi terkait. "
    "Ketentuan perundang-undangan menegaskan aturan lama tetap berlaku sepanjang tidak bertentangan dan belum diganti. "
    "Oleh karena itu, opsi A merupakan pilihan yang benar."
)

# Q12 fix
d1['12']['pembahasan']['alasan_kunci'] = (
    "Opsi C merupakan pejabat yang memiliki kewenangan menetapkan pengangkatan aparatur negara. "
    "Pejabat Pembina Kepegawaian (seperti Menteri, Kepala Lembaga, atau Gubernur/Bupati/Walikota) memegang mandat hukum dalam pembinaan karier pegawai ASN di lingkungannya. "
    "Ketentuan hukum menegaskan pengangkatan aparatur dilakukan oleh Pejabat Pembina Kepegawaian. "
    "Dengan demikian, opsi C adalah pilihan yang tepat."
)

# Q30 fix
d1['30']['kata_kunci_modul'] = [
    "menjamin Akuntabilitas Jabatan, yaitu meliputi terlaksananya:",
    "tidak diperbolehkan merangkap jabatan dengan Jabatan Fungsional dan harus"
]
d1['30']['pilihan'] = {
    "A": "Penyelenggaraan koordinasi rutin bersama instansi vertikal lain",
    "B": "Akuntabilitas jabatan sesuai standar operasional dinas",
    "C": "Pemberian fasilitas program pembinaan karier bagi staf baru",
    "D": "Pengembangan sistem digitalisasi tata naskah dinas persuratan",
    "E": "Penyediaan fasilitas sarana kerja pendukung efektivitas dinas"
}
d1['30']['pembahasan']['alasan_kunci'] = (
    "Opsi B merupakan kewajiban substansi kepemimpinan administrasi yang tepat. "
    "Menjamin akuntabilitas jabatan memastikan anggaran dan wewenang dipergunakan tepat sasaran serta menghasilkan pelayanan publik yang berkualitas tinggi. "
    "Regulasi kepegawaian secara tegas mewajibkan pejabat administrasi menjamin Akuntabilitas Jabatan. "
    "Oleh karena itu, opsi B merupakan pilihan yang benar."
)
d1['30']['pembahasan']['bedah_pengecoh'] = {
    "A": "Koordinasi eksternal merupakan aktivitas kerja sama operasional dan bukan definisi akuntabilitas jabatan.",
    "C": "Pembinaan staf merupakan fungsi manajemen SDM dan bukan kewajiban akuntabilitas jabatan spesifik.",
    "D": "Digitalisasi naskah dinas merupakan sarana tata kelola persuratan kantor.",
    "E": "Penyediaan sarana kerja merupakan pemenuhan fasilitas logistik dinas."
}

with open('tak_p01_part1.json', 'w', encoding='utf-8') as f:
    json.dump(d1, f, ensure_ascii=False, indent=2)

print("Applied fixes to Q3, Q6, Q12, Q30 in tak_p01_part1.json")

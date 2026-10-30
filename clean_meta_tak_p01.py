import json

target_file = 'soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_01.json'
with open(target_file, 'r', encoding='utf-8') as f:
    data = json.load(f)

for q in data['daftar_soal']:
    num = q['nomor']
    alasan = q['pembahasan']['alasan_kunci']
    
    if num == 1:
        alasan = (
            "Opsi A merupakan tanggal yang tepat saat DPR-RI memberikan persetujuan paripurna terhadap RUU ASN. "
            "Persetujuan legislatif ini menjadi landasan formal bagi presiden untuk mengesahkan undang-undang aparatur sipil negara yang baru. "
            "Catatan resmi risalah sidang mencatat secara eksplisit persetujuan paripurna pada 3 Oktober 2023. "
            "Oleh karena itu, opsi A adalah jawaban yang benar."
        )
    elif num == 2:
        alasan = (
            "Opsi B merupakan nomor undang-undang kepegawaian yang sah dan berlaku saat ini. "
            "UU Nomor 20 Tahun 2023 menjadi payung hukum utama penyelenggaraan manajemen aparatur sipil negara di seluruh Indonesia. "
            "Lembaran Negara Republik Indonesia menegaskan pengesahan menjadi UU Nomor 20 Tahun 2023. "
            "Dengan demikian, opsi B adalah pilihan yang tepat."
        )
    elif num == 7:
        alasan = (
            "Opsi B merupakan pejabat negara yang memegang kekuasaan pemerintahan dan pengesahan undang-undang sesuai konstitusi. "
            "Pengesahan oleh presiden memberikan kekuatan mengikat secara hukum bagi seluruh warga negara dan aparatur birokrasi. "
            "Naskah resmi pengundangan menyebutkan pengesahan dilakukan oleh Presiden Joko Widodo. "
            "Maka dari itu, opsi B adalah jawaban yang benar."
        )
    elif num == 9:
        alasan = (
            "Opsi D merupakan tanggal yuridis diundangkannya undang-undang aparatur sipil negara dalam lembaran negara. "
            "Tanggal ini menjadi tonggak baru dimulainya era transformasi manajemen ASN yang terpadu dan profesional. "
            "Naskah hukum perundangan menegaskan pengesahan dilakukan pada 31 Oktober 2023. "
            "Oleh karena itu, opsi D merupakan jawaban yang benar."
        )
    elif num == 23:
        alasan = (
            "Opsi E merinci tiga strata kepemimpinan manajerial aparatur negara secara tepat. "
            "Ketiganya membentuk rantai komando mulai dari pimpinan tingkat tinggi (JPT), tingkat menengah (Administrator), hingga tingkat operasional (Pengawas). "
            "Ketentuan perundang-undangan menyebutkan rincian manajerial terdiri atas JPT, Administrator, dan Pengawas. "
            "Oleh karena itu, opsi E adalah jawaban yang benar."
        )
    elif num == 29:
        alasan = (
            "Opsi A merupakan nomor regulasi manajemen PNS yang tepat. "
            "PP 11/2017 menjadi pedoman teknis operasional pengadaan, pangkat, jabatan, pola karier, mutasi, penilaian kinerja, hingga pemberhentian PNS. "
            "Ketentuan peraturan pemerintah menyebutkan secara jelas PP 11 Tahun 2017 stdd PP 17 Tahun 2020. "
            "Dengan demikian, opsi A adalah jawaban yang benar."
        )
    
    q['pembahasan']['alasan_kunci'] = alasan

with open(target_file, 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print("Cleaned meta patterns in Tak Paket 01!")

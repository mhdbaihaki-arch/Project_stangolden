import json

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_02.json', 'r', encoding='utf-8') as f:
    paket2 = json.load(f)

soals = {q['nomor']: q for q in paket2['daftar_soal']}

fixes = {}

# Soal 1
fixes[1] = {
    "pertanyaan": "Standar materi yang diujikan dalam Seleksi Kompetensi Dasar (SKD) bagi calon PNS meliputi...",
    "pilihan": {
        "A": "Karakteristik pribadi, intelegensia umum, dan wawasan kebangsaan",
        "B": "Keterampilan teknis jabatan, regulasi instansi, dan manajemen kas",
        "C": "Psikotes lanjutan, wawancara kepemimpinan, dan uji studi kasus",
        "D": "Penguasaan teknologi digital, portofolio kerja, dan uji terapan",
        "E": "Keahlian riset substantif, kemampuan bahasa, dan tata naskah"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Materi SKD meliputi: **a. tes wawasan kebangsaan (TWK)**; **b. tes intelegensia umum (TIU)**; dan **c. tes karakteristik pribadi (TKP)**.",
        "alasan_kunci": "Opsi A merupakan materi baku pengujian Seleksi Kompetensi Dasar (SKD) calon aparatur negara. Seleksi awal ini ditujukan untuk menyaring profil kepribadian, kapabilitas kognitif umum, serta komitmen wawasan kebangsaan peserta seleksi. Ketentuan perundang-undangan menetapkan bahwa materi SKD meliputi karakteristik pribadi, intelegensia umum, dan wawasan kebangsaan. Dengan demikian, opsi A merupakan materi yang tepat.",
        "bedah_pengecoh": {
            "B": "Merupakan rumpun materi kompetensi bidang keahlian perbendaharaan dan bukan materi SKD.",
            "C": "Merupakan instrumen tahapan seleksi kompetensi lanjutan dan bukan komponen dasar SKD.",
            "D": "Merupakan cakupan uji praktik kerja teknis pada tahapan Seleksi Kompetensi Bidang.",
            "E": "Merupakan instrumen pengujian keahlian jabatan fungsional tertentu pada tahapan SKB."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 4
fixes[4] = {
    "pertanyaan": "Dua syarat mutlak yang wajib dipenuhi oleh CPNS untuk dapat diangkat menjadi PNS definitif adalah...",
    "pilihan": {
        "A": "Memiliki kualifikasi sarjana dan sertifikat keahlian bahasa",
        "B": "Menyelesaikan penugasan orientasi dinas serta bebas catatan",
        "C": "Mencapai target kinerja istimewa dan rekomendasi atasan unit",
        "D": "Lulus pendidikan dan pelatihan serta sehat jasmani dan rohani",
        "E": "Memiliki masa pengabdian tugas paling singkat lima tahun kerja"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Calon PNS yang memenuhi persyaratan: **a. lulus pendidikan dan pelatihan; dan b. sehat jasmani dan rohani**, diangkat menjadi PNS oleh PPK.",
        "alasan_kunci": "Opsi D merupakan persyaratan kumulatif pengangkatan calon pegawai menjadi pegawai negeri sipil penuh. CPNS wajib membuktikan kelulusan pelatihan dasar kedinasan terpadu sekaligus kesiapan fisik dan mental melalui uji kesehatan resmi. Ketentuan perundang-undangan menetapkan syarat mutlak pengangkatan PNS adalah lulus diklat serta sehat jasmani dan rohani. Oleh karena itu, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Kualifikasi pendidikan merupakan syarat awal pada tahapan pendaftaran pelamaran CPNS.",
            "B": "Orientasi dinas merupakan masa adaptasi awal dan bukan syarat formal yuridis pengangkatan PNS.",
            "C": "Target kinerja istimewa merupakan kriteria promosi dan bukan syarat pengangkatan CPNS.",
            "E": "Masa kerja lima tahun melampaui masa percobaan CPNS yang dibatasi maksimal satu tahun."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 12
fixes[12] = {
    "pertanyaan": "Berdasarkan regulasi manajemen ASN, Jabatan ASN tertentu dapat diisi oleh prajurit TNI dan anggota Polri pada tingkat...",
    "pilihan": {
        "A": "Seluruh kantor cabang dinas perwakilan daerah kabupaten",
        "B": "Unit pelaksana teknis operasional pemerintah provinsi",
        "C": "Instansi Pusat sesuai ketentuan peraturan perundangan",
        "D": "Lembaga penegak hukum kewilayahan tingkat kecamatan",
        "E": "Kantor perwakilan distrik teritorial perbatasan dinas"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Jabatan ASN tertentu **dapat diisi dari: prajurit Tentara Nasional Indonesia dan anggota Kepolisian Negara Republik Indonesia** pada **Instansi Pusat**.",
        "alasan_kunci": "Opsi C merupakan lokus penugasan resmi prajurit TNI dan anggota Polri dalam struktur jabatan aparatur sipil negara. Pengisian jabatan sipil oleh unsur pertahanan dan keamanan dibatasi pada kementerian/lembaga pusat yang membutuhkan sinergi keahlian strategis nasional. Ketentuan perundang-undangan menegaskan bahwa pengisian jabatan ASN oleh TNI dan Polri dilaksanakan pada Instansi Pusat. Oleh karena itu, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Kantor cabang daerah kabupaten bukan lokus pengisian jabatan ASN oleh prajurit militer.",
            "B": "Unit pelaksana teknis daerah provinsi diisi oleh aparatur sipil daerah sesuai formasinya.",
            "D": "Lembaga penegak hukum kecamatan merupakan pos operasional kepolisian di luar struktur ASN.",
            "E": "Kantor distrik perbatasan merupakan wilayah administratif yang tidak menyerap penugasan ini."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 13
fixes[13] = {
    "pertanyaan": "Ketentuan resiprokal dalam Pasal 20 UU Nomor 20 Tahun 2023 mengatur bahwa Pegawai ASN (PNS/PPPK) dapat...",
    "pilihan": {
        "A": "Memilih wilayah penugasan kerja sesuai domisili tempat tinggal",
        "B": "Menduduki jabatan manajerial struktural tanpa seleksi terbuka",
        "C": "Memegang status keprajuritan ganda selama mengemban tugas dinas",
        "D": "Menduduki jabatan di lingkungan TNI dan Polri sesuai kompetensi",
        "E": "Menerima tunjangan kehormatan ganda dari dua instansi kementerian"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Pegawai ASN **dapat menduduki jabatan di lingkungan Tentara Nasional Indonesia dan Kepolisian Negara Republik Indonesia** sesuai kompetensi.",
        "alasan_kunci": "Opsi D merupakan asas mobilitas talenta dua arah antara birokrasi aparatur sipil dan institusi pertahanan keamanan negara. Pegawai ASN yang memiliki kompetensi teknis profesional tertentu dapat ditugaskan menduduki jabatan di internal institusi TNI atau Polri sesuai kebutuhan. Ketentuan Pasal 20 UU Nomor 20 Tahun 2023 merumuskan hak resiprokal ASN untuk menduduki jabatan di lingkungan TNI dan Polri. Dengan demikian, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Penempatan tugas pegawai ditentukan oleh kebutuhan dinas instansi dan bukan pilihan bebas.",
            "B": "Pengisian jabatan struktural tetap wajib mematuhi standar kualifikasi dan kompetensi terbuka.",
            "C": "Aparatur sipil negara tidak dapat memegang status keprajuritan militer secara aktif.",
            "E": "Prinsip pengelolaan anggaran negara melarang penerimaan penghasilan ganda untuk tugas yang sama."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 14
fixes[14] = {
    "pertanyaan": "Pengembangan talenta dan pola karier Pegawai Negeri Sipil wajib diselenggarakan berdasarkan...",
    "pilihan": {
        "A": "Senioritas masa kerja kelender dan daftar urutan kepangkatan",
        "B": "Rekomendasi subjektif pimpinan unit serta pertimbangan masa dinas",
        "C": "Rotasi berkala otomatis tanpa penilaian capaian target kinerja",
        "D": "Mekanisme penugasan internal berdasar ketersediaan kuota formasi",
        "E": "Sistem Merit melalui manajemen talenta instansi pemerintah"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Pengembangan talenta dan karier dilakukan dengan mempertimbangkan kualifikasi, kompetensi, kinerja, dan kebutuhan instansi **berdasarkan Sistem Merit melalui Manajemen Talenta**.",
        "alasan_kunci": "Opsi E merupakan pilar utama tata kelola karier aparatur sipil negara yang transparan, adil, dan objektif. Manajemen talenta memetakan potensi dan kinerja pegawai untuk penempatan posisi yang tepat demi mendukung sasaran strategis instansi. Ketentuan perundang-undangan menetapkan pengembangan talenta dan pola karier PNS wajib diselenggarakan berdasar Sistem Merit melalui manajemen talenta. Oleh karena itu, opsi E merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Senioritas kalender semata telah digantikan oleh penilaian prestasi kinerja dan kualifikasi.",
            "B": "Pertimbangan subjektif pimpinan bertentangan dengan prinsip transparansi sistem merit.",
            "C": "Rotasi jabatan wajib mempertimbangkan hasil pemetaan talenta dan kesesuaian kompetensi.",
            "D": "Penugasan formal tidak boleh mengabaikan standar kompetensi individu yang dipersyaratkan."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 17
fixes[17] = {
    "pertanyaan": "Pengisian jabatan pimpinan tinggi utama dan madya pada kementerian atau lembaga dilakukan secara...",
    "pilihan": {
        "A": "Tertutup melalui mekanisme penunjukan langsung oleh menteri",
        "B": "Penetapan berkala menurut daftar antrean kepangkatan pegawai",
        "C": "Terbuka dan kompetitif di kalangan PNS sesuai syarat jabatan",
        "D": "Rotasi horizontal internal antar-pejabat eselon tingkat daerah",
        "E": "Seleksi penugasan penyesuaian otomatis pasca pelatihan dasar"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Pengisian jabatan pimpinan tinggi utama dan madya pada kementerian, kesekretariatan lembaga, LPNK, dan Instansi Daerah dilakukan **secara terbuka dan kompetitif di kalangan PNS**.",
        "alasan_kunci": "Opsi C merupakan mekanisme baku seleksi terbuka untuk menjaring talenta birokrasi terbaik pada level pimpinan puncak. Seleksi kompetitif memastikan pejabat yang terpilih memenuhi kualifikasi rekam jejak, integritas moral, serta kompetensi manajerial yang teruji. Ketentuan perundang-undangan menegaskan bahwa pengisian JPT utama dan madya dilakukan secara terbuka dan kompetitif di kalangan PNS. Dengan demikian, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Penunjukan langsung tertutup tanpa seleksi terbuka menyalahi ketentuan tata kelola JPT.",
            "B": "Daftar urut kepangkatan tidak lagi menjadi dasar tunggal pengisian jabatan pimpinan tinggi.",
            "D": "Rotasi horizontal internal hanya dapat dilakukan setelah pejabat menduduki JPT definitif.",
            "E": "Pelatihan dasar merupakan syarat kelulusan CPNS dan bukan instrumen promosi pejabat tinggi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 30
fixes[30] = {
    "pertanyaan": "Ketentuan penugasan prajurit TNI dan anggota Polri pada jabatan pimpinan tinggi di instansi pemerintah mengatur bahwa...",
    "pilihan": {
        "A": "Penugasan dilakukan pada seluruh jabatan struktural kementerian",
        "B": "Dapat diisi oleh prajurit TNI dan Polri sesuai kompetensi dan aturan",
        "C": "Seluruh pejabat sipil definitif wajib dialihkan ke jabatan pelaksana",
        "D": "Penempatan personel militer menggantikan tata kelola birokrasi sipil",
        "E": "Seleksi pimpinan tinggi dihapuskan bagi instansi pemerintah pusat"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "JPT tertentu di lingkungan instansi pemerintah **dapat diisi oleh prajurit Tentara Nasional Indonesia dan anggota Kepolisian Negara Republik Indonesia** sesuai perundang-undangan.",
        "alasan_kunci": "Opsi B merupakan kebijakan sinergi kelembagaan bagi instansi yang memiliki tugas pokok terkait bidang pertahanan, keamanan, narkotika, atau intelijen. Penempatan personel tetap didasarkan pada kesesuaian kualifikasi kompetensi jabatan dan koridor regulasi yang berlaku. Ketentuan hukum menegaskan bahwa JPT tertentu dapat diisi prajurit TNI dan Polri sesuai kompetensi dan peraturan perundang-undangan. Dengan demikian, opsi B merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Pengisian jabatan oleh TNI dan Polri dibatasi pada instansi tertentu dan bukan seluruh kementerian.",
            "C": "Pejabat sipil kementerian tetap memegang hak karier sesuai formasi meritokrasi instansi.",
            "D": "Tata kelola birokrasi pemerintahan tetap tunduk pada hukum administrasi negara dan manajemen ASN.",
            "E": "Seleksi terbuka tetap menjadi norma dasar pengisian jabatan pimpinan tinggi di birokrasi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 37
fixes[37] = {
    "pertanyaan": "Dalam seleksi terbuka JPT Pratama di lingkungan pemerintah daerah, pihak yang berwenang menetapkan dan melantik pejabat terpilih adalah...",
    "pilihan": {
        "A": "Pejabat Pembina Kepegawaian pemerintah daerah bersangkutan",
        "B": "Sekretaris Daerah selaku pejabat yang berwenang instansi",
        "C": "Ketua Komisi Pengawas Persaingan Usaha tingkat provinsi",
        "D": "Pimpinan panitia seleksi terbuka instansi pemerintah daerah",
        "E": "Inspektur daerah yang memimpin pengawasan internal wilayah"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Pejabat Pembina Kepegawaian (PPK) daerah **memilih 1 (satu) dari 3 (tiga) nama calon yang diusulkan oleh Panitia Seleksi** untuk ditetapkan dan dilantik.",
        "alasan_kunci": "Opsi A merupakan otoritas kepegawaian kepala daerah selaku Pejabat Pembina Kepegawaian (PPK) terhadap pejabat eselon II di wilayahnya. Setelah menerima hasil kerja panitia seleksi, kepala daerah memiliki wewenang menetapkan dan melantik satu kandidat terpilih. Ketentuan perundang-undangan menegaskan penetapan dan pelantikan JPT Pratama dilakukan oleh PPK daerah bersangkutan. Oleh karena itu, opsi A merupakan jawaban yang benar.",
        "bedah_pengecoh": {
            "B": "Sekretaris Daerah bertindak sebagai PyB yang mengoordinasikan usulan namun tidak melantik pejabat.",
            "C": "Komisi persaingan usaha bertugas mengawasi praktik bisnis komersial di luar aparatur negara.",
            "D": "Panitia seleksi bertugas menyaring tiga calon terbaik dan tidak memiliki wewenang melantik.",
            "E": "Inspektur daerah menjalankan audit internal dan bukan pejabat pengangkat jabatan struktural."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 40
fixes[40] = {
    "pertanyaan": "Langkah pertama yang wajib dilakukan oleh Pejabat Pembina Kepegawaian dalam rangka pengisian JPT Pratama adalah...",
    "pilihan": {
        "A": "Mengusulkan formasi mutasi langsung kepada menteri keuangan",
        "B": "Terlebih dahulu membentuk panitia seleksi instansi pemerintah",
        "C": "Menetapkan nama pejabat pengganti sementara selama masa lelang",
        "D": "Melakukan penataan alokasi pagu belanja tunjangan kinerja satker",
        "E": "Mengoordinasikan jadwal uji kompetensi dengan dewan legislatif"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Pengisian JPT Pratama dilakukan oleh PPK **dengan terlebih dahulu membentuk panitia seleksi** instansi.",
        "alasan_kunci": "Opsi B merupakan prasyarat formal tata kelola pengisian jabatan pimpinan tinggi melalui seleksi terbuka. Pembentukan panitia seleksi yang independen dan kompeten menjadi langkah awal untuk menjamin transparansi penyaringan kandidat. Ketentuan perundang-undangan menetapkan bahwa pengisian JPT dilakukan dengan terlebih dahulu membentuk panitia seleksi. Dengan demikian, opsi B merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Usulan mutasi JPT Pratama daerah bukan merupakan wewenang Menteri Keuangan.",
            "C": "Penunjukan pelaksana tugas bersifat administratif penunjang dan bukan langkah formal seleksi.",
            "D": "Alokasi tunjangan kinerja diatur dalam peraturan tersendiri dan bukan tahapan seleksi jabatan.",
            "E": "Dewan legislatif tidak memiliki fungsi teknis dalam penyelenggaraan seleksi aparatur birokrasi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 44
fixes[44] = {
    "pertanyaan": "Berdasarkan regulasi manajemen ASN, masa jabatan seorang pejabat pimpinan tinggi hanya dapat diduduki paling lama...",
    "pilihan": {
        "A": "Dua tahun tanpa dapat diperpanjang",
        "B": "Tiga tahun dan dapat dievaluasi",
        "C": "Empat tahun berdasarkan uji berkala",
        "D": "Lima tahun dan dapat diperpanjang",
        "E": "Tujuh tahun mengikuti masa kepala"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "JPT hanya dapat diduduki **paling lama 5 (lima) tahun** dan **dapat diperpanjang berdasarkan pencapaian kinerja, kesesuaian kompetensi, dan kebutuhan instansi**.",
        "alasan_kunci": "Opsi D merupakan batas waktu maksimal memegang posisi pimpinan tinggi dalam tata aturan kepegawaian ASN. Pembatasan durasi 5 tahun ditujukan untuk menjaga dinamika organisasi, regenerasi kepemimpinan, dan evaluasi berkala kinerja pimpinan. Ketentuan regulasi menetapkan JPT diduduki paling lama 5 tahun dan dapat diperpanjang berdasarkan evaluasi kinerja dan kompetensi. Dengan demikian, opsi D merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Dua tahun merupakan batas minimal evaluasi berkala dan bukan masa jabatan maksimal JPT.",
            "B": "Tiga tahun bukan batas maksimal masa pemangkuan jabatan pimpinan tinggi dalam regulasi.",
            "C": "Empat tahun bukan batas waktu limitatif jabatan struktural pimpinan tinggi aparatur.",
            "E": "Tujuh tahun melampaui batas maksimal lima tahun yang diatur dalam ketentuan manajemen ASN."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 50
fixes[50] = {
    "pertanyaan": "Penerimaan calon PPPK dilaksanakan melalui penilaian objektif yang didasarkan pada faktor...",
    "pilihan": {
        "A": "Senioritas pengabdian pada lembaga pemerintah daerah",
        "B": "Tingkat kualifikasi akademik tertinggi yang diserahkan",
        "C": "Rekomendasi kepemimpinan dari atasan satuan kerja",
        "D": "Kompetensi, kualifikasi, dan kebutuhan instansi negara",
        "E": "Hasil verifikasi administrasi berkas kelulusan awal"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Penerimaan calon PPPK dilaksanakan berdasarkan **objektivitas penilaian yang didasarkan pada kompetensi, kualifikasi, dan kebutuhan Instansi Pemerintah**.",
        "alasan_kunci": "Opsi D merupakan perwujudan prinsip sistem merit dalam pengadaan aparatur pemerintah dengan perjanjian kerja. Seleksi objektif memadukan kecakapan teknis pelamar, standar pendidikan yang dipersyaratkan, serta formasi kebutuhan organisasi pemerintah. Ketentuan perundang-undangan menegaskan penerimaan calon PPPK didasarkan pada kompetensi, kualifikasi, dan kebutuhan instansi pemerintah. Oleh sebab itu, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Senioritas pengabdian semata bukan penentu utama dalam seleksi kompetensi objektif PPPK.",
            "B": "Kualifikasi akademik merupakan syarat administratif pelengkap dari penilaian kompetensi menyeluruh.",
            "C": "Rekomendasi atasan tidak dapat menggantikan tahapan ujian seleksi kompetensi resmi pemerintah.",
            "E": "Verifikasi berkas hanya tahapan penyaringan awal sebelum pelaksanaan seleksi kompetensi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 51
fixes[51] = {
    "pertanyaan": "Berdasarkan regulasi kepegawaian nasional, ketentuan peralihan status PPPK menjadi calon PNS adalah...",
    "pilihan": {
        "A": "Wajib mengikuti semua proses seleksi resmi calon PNS",
        "B": "Otomatis diangkat menjadi PNS setelah masa kerja dinas",
        "C": "Cukup melampirkan rekomendasi tertulis pimpinan satker",
        "D": "Dapat dialihkan melalui mekanisme penyesuaian berkala",
        "E": "Diberikan kuota khusus tanpa melalui tahapan seleksi"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "PPPK **tidak dapat diangkat secara otomatis menjadi calon PNS**. Untuk diangkat menjadi calon PNS, PPPK harus **mengikuti semua proses seleksi yang dilaksanakan bagi calon PNS**.",
        "alasan_kunci": "Opsi A menegaskan prinsip kesetaraan dan keadilan kompetitif dalam rekrutmen aparatur negara. Tidak ada jalur otomatis pengalihan status PPPK menjadi PNS, sehingga setiap pegawai PPPK tetap wajib menempuh seleksi formasi umum calon PNS. Ketentuan perundang-undangan menetapkan PPPK yang ingin menjadi PNS harus mengikuti semua proses seleksi calon PNS. Dengan demikian, opsi A merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "B": "Masa kerja dinas pada formasi PPPK tidak otomatis mengubah status pegawai menjadi PNS.",
            "C": "Rekomendasi pimpinan satker tidak dapat menganulir kewajiban menempuh seleksi nasional.",
            "D": "Mekanisme penyesuaian berkala hanya berlaku untuk jabatan fungsional dan bukan status PNS.",
            "E": "Regulasi ASN melarang pemberian kuota istimewa tanpa melalui proses seleksi kompetitif."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 52
fixes[52] = {
    "pertanyaan": "Pemerintah wajib memberikan gaji yang adil dan layak kepada pegawai PPPK yang didasarkan pada...",
    "pilihan": {
        "A": "Masa kerja pengabdian pegawai pada instansi pemerintah",
        "B": "Hasil capaian evaluasi perilaku kerja triwulanan dinas",
        "C": "Beban kerja, tanggung jawab, dan risiko pekerjaan",
        "D": "Tingkat kualifikasi ijazah dan golongan kepangkatan",
        "E": "Standar kelayakan biaya hidup pada masing-masing daerah"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Pemerintah wajib membayar gaji yang adil dan layak kepada PPPK yang didasarkan pada **beban kerja, tanggung jawab jabatan, dan risiko pekerjaan**.",
        "alasan_kunci": "Opsi C merupakan tiga parameter baku penetapan remunerasi aparatur berbasis keadilan dan bobot tugas jabatan. Penghitungan besaran kompensasi mempertimbangkan volume tugas dinas, wewenang jabatan yang diemban, serta tingkat bahaya pekerjaan yang dihadapi. Ketentuan hukum menetapkan gaji PPPK didasarkan pada beban kerja, tanggung jawab jabatan, dan risiko pekerjaan. Oleh karena itu, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Masa kerja bukan dasar utama dalam perumusan struktur skala upah remunerasi modern.",
            "B": "Evaluasi perilaku kerja merupakan dasar penilaian disiplin dan pemberian penghargaan prestasi.",
            "D": "Kualifikasi ijazah menentukan persyaratan formasi dan bukan variabel tunggal penetapan gaji.",
            "E": "Biaya hidup daerah diakomodasi melalui komponen tunjangan kemahalan dan bukan gaji pokok."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 57
fixes[57] = {
    "pertanyaan": "Pelanggaran disiplin tingkat berat yang dilakukan oleh pegawai PPPK berakibat pada pemutusan hubungan kerja berupa...",
    "pilihan": {
        "A": "Pemberian surat peringatan tertulis masa percobaan kerja",
        "B": "Penundaan perpanjangan masa kontrak perjanjian kepegawaian",
        "C": "Pemutusan hubungan kerja dengan hormat tidak atas permohonan",
        "D": "Pemindahan tugas penempatan operasional pada unit terluar",
        "E": "Penurunan kelas jabatan setingkat lebih rendah satu tahun"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Pemutusan hubungan perjanjian kerja PPPK dilakukan **dengan hormat tidak atas permintaan sendiri karena melakukan pelanggaran disiplin PPPK tingkat berat**.",
        "alasan_kunci": "Opsi C merupakan sanksi yuridis pemutusan hubungan kerja atas pelanggaran disiplin tingkat berat oleh aparatur perjanjian kerja. Pelanggaran berat menunjukkan ketidakmampuan pegawai menjaga integritas dan ketertiban kedinasan sehingga kontrak kerja diakhiri secara resmi oleh instansi. Ketentuan perundang-undangan menetapkan sanksi pelanggaran berat adalah pemutusan kerja dengan hormat tidak atas permintaan sendiri. Dengan demikian, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Peringatan tertulis merupakan sanksi pembinaan administratif ringan dan bukan sanksi berat.",
            "B": "Penundaan perpanjangan kontrak bukan bentuk hukuman disiplin pemutusan yang baku.",
            "D": "Pemindahan tugas merupakan bentuk rotasi penugasan dan bukan putusan pelanggaran berat.",
            "E": "Penurunan kelas jabatan merupakan jenis sanksi disiplin bagi PNS dan bukan pemutusan PPPK."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 59
fixes[59] = {
    "pertanyaan": "Seorang PPPK dijatuhi vonis pidana penjara 3 tahun karena terbukti melakukan kejahatan penipuan berencana. Pemutusan perjanjian kerja dilakukan secara...",
    "pilihan": {
        "A": "Pemutusan perjanjian kerja dengan hormat atas permohonan",
        "B": "Pemutusan hubungan perjanjian kerja tidak dengan hormat",
        "C": "Pemutusan perjanjian kerja dengan hormat karena perampingan",
        "D": "Pembebasan sementara dari tugas kedinasan selama banding",
        "E": "Penundaan evaluasi perjanjian kerja sampai hukuman selesai"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Pemutusan hubungan kerja dilakukan **tidak dengan hormat** karena **dihukum penjara berdasarkan putusan pengadilan yang berkekuatan hukum tetap karena melakukan tindak pidana dengan pidana penjara paling singkat 2 (dua) tahun dan pidana dilakukan dengan berencana**.",
        "alasan_kunci": "Opsi B merupakan bentuk pemutusan hubungan kerja terberat bagi aparatur yang terbukti melakukan tindak pidana dengan perencanaan matang dan vonis minimal 2 tahun. Kualifikasi kejahatan berencana membatalkan kelayakan integritas abdi negara sehingga diberhentikan secara tidak hormat. Ketentuan hukum menegaskan pemutusan hubungan perjanjian kerja dilakukan tidak dengan hormat. Oleh karena itu, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pemutusan dengan hormat atas permohonan sendiri diperuntukkan bagi pengunduran diri sukarela.",
            "C": "Pemutusan karena perampingan organisasi merupakan alasan penataan kelembagaan dinas.",
            "D": "Pembebasan sementara hanya berlangsung selama proses pemeriksaan sebelum vonis inkracht.",
            "E": "Penundaan evaluasi tidak berlaku apabila vonis pengadilan telah berkekuatan hukum tetap."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 61
fixes[61] = {
    "pertanyaan": "Berdasarkan regulasi manajemen PNS, batas usia pensiun bagi Pejabat Pimpinan Tinggi Utama dan Madya adalah...",
    "pilihan": {
        "A": "56 tahun",
        "B": "60 tahun",
        "C": "58 tahun",
        "D": "62 tahun",
        "E": "65 tahun"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Batas usia pensiun bagi: **60 (enam puluh) tahun bagi pejabat pimpinan tinggi**.",
        "alasan_kunci": "Opsi B merupakan batas usia pensiun (BUP) normatif bagi pejabat pimpinan tinggi dalam birokrasi pemerintahan. Pengaturan usia 60 tahun memberikan ruang pengabdian manajerial puncak dengan tetap menjaga sirkulasi regenerasi kepemimpinan aparatur. Ketentuan hukum kepegawaian menetapkan batas usia pensiun bagi pejabat pimpinan tinggi adalah 60 tahun. Dengan demikian, opsi B merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Batas usia 56 tahun merupakan batas usia pensiun pada ketentuan perundang-undangan lama.",
            "C": "Batas usia 58 tahun berlaku bagi pejabat administrasi, pengawas, dan pelaksana.",
            "D": "Batas usia 62 tahun bukan merupakan jenjang batas usia pensiun baku dalam manajemen ASN.",
            "E": "Batas usia 65 tahun berlaku bagi pejabat fungsional keahlian jenjang Ahli Utama."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 63
fixes[63] = {
    "pertanyaan": "PNS yang telah selesai menjalankan tugas sebagai pejabat negara diaktifkan kembali dalam jabatan PNS apabila...",
    "pilihan": {
        "A": "Tersedia lowongan formasi jabatan yang sesuai kualifikasi",
        "B": "Mendapat rekomendasi tertulis dari pimpinan partai politik",
        "C": "Menyelesaikan masa pembekalan tugas paling lama dua tahun",
        "D": "Memiliki masa kerja aparatur paling singkat sepuluh tahun",
        "E": "Menyatakan kesediaan ditempatkan pada instansi luar negeri"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "PNS yang selesai menjalankan tugas sebagai Pejabat Negara **dapat diaktifkan kembali dalam jabatan PNS apabila tersedia lowongan jabatan**.",
        "alasan_kunci": "Opsi A merupakan syarat administratif pengaktifan kembali aparatur pasca mengemban tugas sebagai pejabat negara. Karena status kepegawaian PNS tetap dipertahankan selama masa bakti negara, yang bersangkutan berhak kembali bertugas aktif sepanjang terdapat formasi jabatan yang lowong. Ketentuan regulasi menegaskan PNS diaktifkan kembali apabila tersedia lowongan jabatan. Oleh karena itu, opsi A merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "B": "Rekomendasi partai politik bertentangan dengan asas netralitas aparatur sipil negara.",
            "C": "Masa pembekalan dua tahun bukan prasyarat yuridis pengaktifan kembali status kepegawaian.",
            "D": "Masa kerja sepuluh tahun bukan parameter formal pengaktifan mantan pejabat negara.",
            "E": "Penempatan luar negeri merupakan mekanisme penugasan khusus dan bukan syarat pengaktifan."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 65
fixes[65] = {
    "pertanyaan": "Seorang PNS resmi ditetapkan oleh KPU sebagai calon kepala daerah dalam pemilihan umum. Kewajiban hukum yang wajib dipenuhi adalah...",
    "pilihan": {
        "A": "Mengajukan cuti tahunan selama masa kampanye pemilihan",
        "B": "Mengajukan cuti di luar tanggungan negara hingga coblosan",
        "C": "Menyatakan nonaktif sementara dari tugas dinas satker",
        "D": "Meminta izin penugasan khusus kepada menteri kepegawaian",
        "E": "Menyatakan pengunduran diri tertulis sebagai PNS sejak calon"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "PNS wajib **mengundurkan diri secara tertulis sebagai PNS sejak ditetapkan sebagai calon** Presiden, Wakil Presiden, DPR, DPD, DPRD, kepala daerah, atau wakil kepala daerah.",
        "alasan_kunci": "Opsi E merupakan garis demarkasi mutlak antara dunia politik elektoral dengan korps birokrasi netral. Keterlibatan dalam kontestasi pemilihan menuntut pelepasan permanen status aparatur demi menjamin asas netralitas dan mencegah penyalahgunaan wewenang negara. Ketentuan undang-undang mewajibkan PNS mengundurkan diri tertulis sejak ditetapkan sebagai calon. Oleh sebab itu, opsi E merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pengajuan cuti tahunan tidak diperbolehkan bagi aparatur yang mencalonkan diri dalam pemilu.",
            "B": "Cuti di luar tanggungan negara bukan sarana legalitas untuk mengikuti kontestasi politik.",
            "C": "Status nonaktif sementara tidak menggugurkan kewajiban pengunduran diri permanen.",
            "D": "Izin penugasan khusus tidak dapat diberikan untuk kepentingan politik praktis perorangan."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 70
fixes[70] = {
    "pertanyaan": "Tujuan utama pemberian jaminan bantuan hukum kepada aparatur sipil negara dalam penyelenggaraan tugas adalah...",
    "pilihan": {
        "A": "Memberikan pembebasan dari segala mekanisme pengawasan dinas",
        "B": "Meniadakan pertanggungjawaban hukum bagi aparatur pemerintah",
        "C": "Memberikan pendampingan hukum atas perkara pelaksanaan tugas",
        "D": "Melindungi pegawai dari kewajiban mematuhi aturan peradilan",
        "E": "Menjamin imunitas penuh atas pelanggaran kode etik kepegawaian"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Bantuan hukum diberikan dalam bentuk **pemberian bantuan hukum dalam perkara yang dihadapi di pengadilan terkait pelaksanaan tugasnya**.",
        "alasan_kunci": "Opsi C merupakan esensi perlindungan hukum negara bagi aparatur yang menghadapi sengketa hukum dalam menjalankan kebijakan resmi dinas. Pendampingan ini memberikan ketenangan dan perlindungan bagi pegawai yang bertindak sesuai regulasi dari risiko kriminalisasi. Ketentuan hukum menegaskan bantuan hukum diberikan dalam perkara terkait pelaksanaan tugas kedinasan. Oleh karena itu, opsi C merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Aparatur sipil negara tetap wajib tunduk pada seluruh mekanisme pengawasan internal dan eksternal.",
            "B": "Bantuan hukum bukan instrumen untuk menghapus akuntabilitas hukum dan pertanggungjawaban dinas.",
            "D": "Setiap pegawai pemerintah wajib menghormati dan mematuhi seluruh putusan lembaga peradilan.",
            "E": "Pelanggaran kode etik tetap diproses melalui majelis kehormatan tanpa imunitas khusus."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 71
fixes[71] = {
    "pertanyaan": "Penetapan mutasi PNS antar-kabupaten/kota dalam satu wilayah provinsi merupakan kewenangan...",
    "pilihan": {
        "A": "Walikota tempat domisili keluarga pemohon mutasi dinas",
        "B": "Bupati tempat satuan kerja unit penugasan asal pegawai",
        "C": "Kepala kantor wilayah kementerian keuangan provinsi",
        "D": "Pimpinan inspektorat wilayah pemerintahan provinsi",
        "E": "Gubernur setelah mendapat pertimbangan Kepala BKN"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Mutasi PNS antar-kabupaten/kota dalam satu provinsi **ditetapkan oleh gubernur setelah memperoleh pertimbangan Kepala BKN**.",
        "alasan_kunci": "Opsi E merupakan garis kewenangan koordinasi regional gubernur selaku wakil pemerintah pusat di wilayah terhadap mobilitas aparatur daerah. Keterlibatan pertimbangan teknis Kepala BKN memastikan perpindahan pegawai selaras dengan ketersediaan formasi dan basis data kepegawaian nasional. Ketentuan regulasi menetapkan mutasi antarkabupaten/kota dalam satu provinsi ditetapkan Gubernur setelah mendapat pertimbangan Kepala BKN. Dengan demikian, opsi E merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Walikota hanya berwenang menetapkan mutasi internal di dalam wilayah kotanya sendiri.",
            "B": "Bupati instansi asal hanya memberikan persetujuan pelepasan dan bukan menetapkan mutasi antar-daerah.",
            "C": "Kanwil Kemenkeu mengelola mutasi instansi vertikal kementerian dan bukan PNS pemerintah daerah.",
            "D": "Inspektorat provinsi menjalankan fungsi pengawasan dan tidak berwenang menetapkan mutasi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 73
fixes[73] = {
    "pertanyaan": "Pengalihan penugasan mutasi seorang PNS dari instansi pemerintah daerah ke Instansi Pusat ditetapkan oleh...",
    "pilihan": {
        "A": "Gubernur pemerintah daerah wilayah asal pegawai",
        "B": "Kepala Badan Kepegawaian Negara Republik Indonesia",
        "C": "Menteri Dalam Negeri selaku pembina daerah wilayah",
        "D": "Sekretaris Jenderal kementerian instansi penerima",
        "E": "Ketua Komisi Pengawas Manajemen Aparatur Sipil"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "Mutasi PNS antar-instansi daerah dan instansi pusat **ditetapkan oleh Kepala BKN**.",
        "alasan_kunci": "Opsi B merupakan peran integratif Badan Kepegawaian Negara sebagai otoritas pengelola administrasi kepegawaian nasional. Mutasi lintas yurisdiksi antara pemerintah daerah dengan kementerian lembaga pusat ditetapkan langsung oleh Kepala BKN untuk menjamin tertib administrasi nasional. Ketentuan perundang-undangan menetapkan mutasi antar-daerah dan instansi pusat ditetapkan oleh Kepala BKN. Oleh karena itu, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Gubernur berwenang pada mutasi antar-kabupaten/kota dalam satu provinsi dan bukan ke instansi pusat.",
            "C": "Mendagri membina kebijakan umum pemda namun penetapan mutasi teknis PNS dilakukan Kepala BKN.",
            "D": "Sekjen kementerian penerima memberikan persetujuan kebutuhan dan bukan menetapkan SK mutasi.",
            "E": "Komisi pengawas bertugas mengawasi sistem merit dan tidak menerbitkan keputusan mutasi pegawai."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 76
fixes[76] = {
    "pertanyaan": "Bentuk apresiasi kedinasan yang BUKAN merupakan hak penghargaan bagi PNS berprestasi kerja adalah...",
    "pilihan": {
        "A": "Pemberian tanda kehormatan kenegaraan resmi",
        "B": "Pemberian kenaikan pangkat istimewa kedinasan",
        "C": "Pemberian hak kepemilikan aset sarana negara",
        "D": "Kesempatan prioritas pengembangan kompetensi",
        "E": "Kesempatan menghadiri acara resmi kenegaraan"
    },
    "kunci_jawaban": "C",
    "pembahasan": {
        "konsep_materi": "Penghargaan dapat berupa: **tanda kehormatan, kenaikan pangkat istimewa, kesempatan prioritas pengembangan kompetensi, dan/atau menghadiri acara resmi/kenegaraan**.",
        "alasan_kunci": "Opsi C merupakan tindakan yang bertentangan dengan hukum pengelolaan Barang Milik Negara (BMN). Seluruh sarana dan gedung kantor merupakan kekayaan milik negara yang tidak dapat dipindahtangankan menjadi hak milik privat pegawai atas nama penghargaan. Ketentuan perundang-undangan menetapkan bentuk penghargaan berupa tanda kehormatan, kenaikan pangkat, pengembangan kompetensi, dan menghadiri acara resmi. Dengan demikian, opsi C merupakan jawaban pengecualian yang tepat.",
        "bedah_pengecoh": {
            "A": "Pemberian tanda kehormatan merupakan salah satu bentuk resmi penghargaan negara bagi PNS.",
            "B": "Kenaikan pangkat istimewa merupakan hak apresiasi bagi aparatur berprestasi luar biasa.",
            "D": "Prioritas pelatihan dan beasiswa merupakan bentuk penghargaan pengembangan kapasitas diri.",
            "E": "Undangan menghadiri acara kenegaraan merupakan bentuk pengakuan kehormatan resmi bagi aparatur."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 77
fixes[77] = {
    "pertanyaan": "Konsekuensi terhadap tanda kehormatan bagi PNS yang dijatuhi sanksi disiplin berat pemberhentian tidak dengan hormat adalah...",
    "pilihan": {
        "A": "Diberikan ganti rugi finansial setara nilai tanda jasa",
        "B": "Dicabut hak memakai tanda kehormatan berdasar aturan",
        "C": "Ditangguhkan pemakaiannya sampai terbit putusan baru",
        "D": "Dialihkan pencatatannya ke dalam dokumen masa purna",
        "E": "Dibatasi penggunaannya pada kegiatan internal keluarga"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "PNS yang dijatuhi hukuman disiplin berat berupa pemberhentian tidak dengan hormat **dicabut haknya untuk memakai tanda kehormatan**.",
        "alasan_kunci": "Opsi B merupakan sanksi moral dan kehormatan yang mencabut seluruh atribut apresiasi negara dari aparatur yang melanggar sumpah jabatan. Pemecatan secara tidak hormat membatalkan kelayakan moral pemakaian bintang jasa atau satyalancana kenegaraan. Ketentuan perundang-undangan menegaskan bahwa PNS yang diberhentikan tidak dengan hormat dicabut haknya untuk memakai tanda kehormatan. Oleh sebab itu, opsi B merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Tanda kehormatan bersifat moral kenegaraan dan tidak memiliki nilai kompensasi finansial.",
            "C": "Sanksi pemecatan tidak dengan hormat bersifat permanen dan bukan penangguhan sementara.",
            "D": "PNS yang dipecat tidak hormat tidak memperoleh hak pensiun maupun pencatatan purnatugas resmi.",
            "E": "Pencabutan tanda kehormatan berlaku mutlak di seluruh ruang publik maupun privat."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 80
fixes[80] = {
    "pertanyaan": "Nilai-nilai kualitatif yang menjadi prasyarat pertimbangan utama dalam pemberian penghargaan kedinasan bagi PNS adalah...",
    "pilihan": {
        "A": "Masa pengabdian tugas dan jumlah kehadiran apel dinas",
        "B": "Penguasaan bahasa asing dan frekuensi pelatihan luar",
        "C": "Ketaatan administratif dan keterlibatan panitia kerja",
        "D": "Kelulusan jenjang pendidikan dan kepatuhan pelaporan",
        "E": "Kesetiaan, pengabdian, kecakapan, jujur, dan prestasi"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "Penghargaan diberikan berdasarkan pertimbangan: **kesetiaan, pengabdian, kecakapan, kejujuran, disiplin, dan prestasi kerja**.",
        "alasan_kunci": "Opsi E merupakan pilar karakter moralitas dan kinerja unggul aparatur negara dalam pemberian apresiasi kenegaraan. Penghargaan negara dianugerahkan kepada pegawai yang membuktikan integritas pengabdian, loyalitas kebangsaan, serta prestasi nyata dalam pelayanan publik. Ketentuan regulasi menetapkan pertimbangan penghargaan mencakup kesetiaan, pengabdian, kecakapan, kejujuran, disiplin, dan prestasi. Oleh karena itu, opsi E merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Masa pengabdian dan kehadiran merupakan syarat umum disiplin dan bukan tolok ukur utama.",
            "B": "Bahasa asing dan frekuensi diklat merupakan indikator kompetensi dan bukan kriteria pokok.",
            "C": "Ketaatan administratif dan kepanitiaan merupakan tugas rutin sehari-hari abdi negara.",
            "D": "Kelulusan pendidikan merupakan kualifikasi formal dan bukan pertimbangan kelayakan penghargaan."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 82
fixes[82] = {
    "pertanyaan": "Karakteristik penyelenggaraan Sistem Informasi ASN yang diwajibkan oleh undang-undang adalah...",
    "pilihan": {
        "A": "Diselenggarakan nasional dan terintegrasi antar-instansi",
        "B": "Dikelola mandiri oleh masing-masing unit kerja pelaksana",
        "C": "Bersifat tertutup tanpa keterhubungan jaringan kementerian",
        "D": "Dikelola secara parsial mengikuti kebijakan pimpinan satker",
        "E": "Dibatasi penerapannya pada pengelolaan data pejabat tinggi"
    },
    "kunci_jawaban": "A",
    "pembahasan": {
        "konsep_materi": "Sistem Informasi ASN **diselenggarakan secara nasional dan terintegrasi antar-Instansi Pemerintah**.",
        "alasan_kunci": "Opsi A merupakan prinsip keterpaduan basis data aparatur sipil negara secara nasional (*single source of truth*). Integrasi data antarinstitusi mempermudah interoperabilitas layanan kepegawaian, perencanaan penempatan talenta, serta monitoring kinerja aparatur secara terpadu. Ketentuan undang-undang menetapkan Sistem Informasi ASN diselenggarakan secara nasional dan terintegrasi antar-instansi. Dengan demikian, opsi A merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "B": "Pengelolaan mandiri yang terfragmentasi bertentangan dengan asas integrasi data nasional.",
            "C": "Sistem informasi dirancang berbasis jaringan interoperabilitas dan bukan sistem tertutup.",
            "D": "Pengelolaan parsial menghambat koordinasi dan validitas basis data kepegawaian nasional.",
            "E": "Sistem informasi mencakup seluruh data pegawai ASN dari jenjang terendah hingga tertinggi."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 89
fixes[89] = {
    "pertanyaan": "PNS yang dinyatakan tewas atau hilang dalam menjalankan tugas operasi kedinasan negara diberhentikan secara...",
    "pilihan": {
        "A": "Pemberhentian sementara menunggu kepastian masa tugas",
        "B": "Dengan hormat atas jasa dan pengabdian kepada negara",
        "C": "Pemberhentian tidak atas permohonan sendiri berjangka",
        "D": "Pemberhentian administratif dengan pemutusan hak tunjangan",
        "E": "Pemberhentian jabatan definitif dengan status pegawai nonaktif"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "PNS yang tewas atau dinyatakan hilang dalam menjalankan tugas **diberhentikan dengan hormat**.",
        "alasan_kunci": "Opsi B merupakan penghormatan resmi negara atas pengorbanan jiwa raga abdi negara dalam menjalankan tugas kedinasan. Gugur atau dinyatakan tewas saat melaksanakan mandat kenegaraan diselesaikan melalui keputusan pemberhentian dengan hormat disertai hak-hak pensiun dan jaminan duka bagi ahli waris. Ketentuan perundang-undangan menegaskan pemberhentian tersebut dilakukan dengan hormat. Oleh karena itu, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pemberhentian sementara berlaku bagi PNS yang ditahan aparat hukum atau menjadi komisioner.",
            "C": "Bukan penamaan yuridis bagi aparatur yang gugur dalam menjalankan tugas negara.",
            "D": "Aparatur yang tewas berhak memperoleh jaminan perlindungan duka penuh dari negara.",
            "E": "Status pegawai nonaktif tidak berlaku bagi pegawai yang telah dinyatakan tewas dalam tugas."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 90
fixes[90] = {
    "pertanyaan": "Urgensi utama dibangunnya Sistem Informasi ASN yang terintegrasi secara nasional adalah menjamin...",
    "pilihan": {
        "A": "Keseragaman sistem pembukuan keuangan kas operasional",
        "B": "Standarisasi pengadaan sarana logistik kantor kementerian",
        "C": "Keterbukaan informasi data rahasia dokumen penyelidikan",
        "D": "Efisiensi, efektivitas, dan akurasi keputusan manajemen",
        "E": "Kemudahan pertukaran informasi kegiatan sosial aparatur"
    },
    "kunci_jawaban": "D",
    "pembahasan": {
        "konsep_materi": "Sistem Informasi ASN bertujuan untuk **menjamin efisiensi, efektivitas, dan akurasi pengambilan keputusan dalam Manajemen ASN**.",
        "alasan_kunci": "Opsi D merupakan sasaran strategis dari transformasi digital kepegawaian nasional. Basis data yang terintegrasi dan akurat mencegah terjadinya kekeliruan penempatan pegawai, mempercepat pemrosesan mutasi dan pensiun, serta menjamin keandalan keputusan pimpinan. Ketentuan hukum menetapkan tujuan sistem informasi adalah menjamin efisiensi, efektivitas, dan akurasi pengambilan keputusan. Oleh sebab itu, opsi D merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "Pembukuan kas operasional merupakan domain aplikasi perbendaharaan pada Kementerian Keuangan.",
            "B": "Pengadaan logistik kantor dikelola melalui sistem pengadaan barang dan jasa pemerintah.",
            "C": "Dokumen penyelidikan merupakan rahasia penegakan hukum dan bukan fungsi sistem informasi ASN.",
            "E": "Kegiatan sosial kemasyarakatan bukan sasaran utama pembangunan pangkalan data kepegawaian."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 91
fixes[91] = {
    "pertanyaan": "PNS dapat diberhentikan dengan hormat tidak atas permohonan sendiri apabila tidak melapor pasca tugas belajar dalam waktu paling lama...",
    "pilihan": {
        "A": "Tiga hari kerja sejak tanggal berakhirnya tugas belajar",
        "B": "Tujuh hari kerja sejak tanggal berakhirnya tugas belajar",
        "C": "Sepuluh hari kerja sesudah berakhirnya masa tugas belajar",
        "D": "Tiga puluh hari kalender sejak selesai tugas belajar",
        "E": "Lima belas hari kerja sejak berakhirnya masa tugas belajar"
    },
    "kunci_jawaban": "E",
    "pembahasan": {
        "konsep_materi": "PNS diberhentikan dengan hormat tidak atas permintaan sendiri apabila **tidak melapor kepada PPK dalam jangka waktu paling lama 15 (lima belas) hari kerja setelah berakhirnya masa tugas belajar**.",
        "alasan_kunci": "Opsi E merupakan batas waktu kepatuhan administratif bagi aparatur yang telah menyelesaikan tugas belajar. Kegagalan melapor kembali kepada Pejabat Pembina Kepegawaian dalam batas 15 hari kerja mencerminkan kelalaian tugas berat yang berakibat pada pemutusan hubungan kerja terhormat tidak atas permintaan sendiri. Ketentuan perundang-undangan menetapkan batas waktu pelaporan paling lama 15 hari kerja. Dengan demikian, opsi E merupakan pilihan yang tepat.",
        "bedah_pengecoh": {
            "A": "Tenggat tiga hari kerja terlalu singkat untuk penyelesaian administrasi kepulangan studi.",
            "B": "Tenggat tujuh hari kerja bukan batas limitatif yang diatur dalam ketentuan tugas belajar PNS.",
            "C": "Tenggat sepuluh hari kerja bukan batas waktu yuridis pemberhentian pasca tugas belajar.",
            "D": "Ketentuan kepegawaian menggunakan satuan hari kerja dan bukan tiga puluh hari kalender."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Soal 97
fixes[97] = {
    "pertanyaan": "Seorang PNS yang telah disetujui untuk menjalani cuti di luar tanggungan negara (CLTN) secara hukum berstatus...",
    "pilihan": {
        "A": "Diberhentikan tetap dan dicabut hak kepangkatan dinas",
        "B": "Diberhentikan dari jabatannya selama masa cuti berjalan",
        "C": "Tetap memegang jabatan definitif tanpa pemotongan tunjangan",
        "D": "Wajib menjalankan tugas kedinasan harian pada satuan kerja",
        "E": "Diberikan penugasan khusus pada instansi pemerintah daerah"
    },
    "kunci_jawaban": "B",
    "pembahasan": {
        "konsep_materi": "PNS yang menjalankan cuti di luar tanggungan negara **diberhentikan dari jabatannya** dan jabatannya dapat diisi oleh PNS lain.",
        "alasan_kunci": "Opsi B merupakan status kepegawaian resmi bagi pegawai yang memanfaatkan cuti di luar tanggungan negara. Pelepasan jabatan dilakukan agar posisi yang ditinggalkan dapat diisi oleh aparatur lain demi kelancaran operasional birokrasi, sementara status PNS tetap melekat tanpa penerimaan penghasilan negara. Ketentuan regulasi menetapkan PNS yang menjalani CLTN diberhentikan dari jabatannya. Oleh karena itu, opsi B merupakan jawaban yang tepat.",
        "bedah_pengecoh": {
            "A": "PNS yang menjalani CLTN tidak diberhentikan tetap dari status kepegawaian korps aparatur.",
            "C": "Selama masa CLTN, jabatan pegawai dilepaskan dan hak penghasilan dari kas negara dihentikan.",
            "D": "Pegawai dibebaskan sepenuhnya dari kewajiban tugas dinas sehari-hari selama masa cuti.",
            "E": "CLTN merupakan cuti kepentingan pribadi dan bukan mekanisme penugasan khusus instansi pemda."
        },
        "sumber_rujukan": "Tata Aturan Kepegawaian"
    }
}

# Apply fixes
applied = 0
for n, fix in fixes.items():
    q = soals[n]
    for k, v in fix.items():
        if k == 'pembahasan':
            for pk, pv in v.items():
                q['pembahasan'][pk] = pv
        else:
            q[k] = v
    applied += 1

print(f"Applied {applied} fixes to Paket 2.")

# Also check for any other 'undian' or 'paguyuban' in remaining questions of Paket 2:
for q in paket2['daftar_soal']:
    n = q['nomor']
    for opt, txt in q['pilihan'].items():
        if 'undian' in txt.lower():
            print(f"Remaining undian in Q{n} opt {opt}: {txt}")
        if 'paguyuban' in txt.lower():
            print(f"Remaining paguyuban in Q{n} opt {opt}: {txt}")

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_02.json', 'w', encoding='utf-8') as f:
    json.dump(paket2, f, ensure_ascii=False, indent=2)

print("Saved updated Paket 2 JSON.")

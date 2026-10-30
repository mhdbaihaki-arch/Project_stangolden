import json

# Sub-Agent 1: Halaman 46 dan 47 (Nomor 1 s/d 20)
# Halaman 46: Nomor 1 - 10
# Halaman 47: Nomor 11 - 20

questions = [
    # --- HALAMAN 46 (Q01 - Q10) ---
    {
        "nomor": 1,
        "halaman_sumber": 46,
        "kata_kunci_modul": ["selambat-lambatnya dalam waktu tiga puluh hari"],
        "topik_materi": "Pemilihan Presiden dan Wapres Pengganti oleh MPR",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Apabila Presiden dan Wakil Presiden mangkat atau berhenti secara bersamaan dalam masa jabatannya, MPR wajib menyelenggarakan sidang suksesi dalam batas waktu...",
        "pilihan": {
            "A": "Paling lambat empat belas hari kerja sejak naskah pengunduran diri disahkan dewan",
            "B": "Selambat-lambatnya dalam waktu tiga puluh hari dari dua paket calon peraih suara terbanyak",
            "C": "Maksimal enam puluh hari kalender sejak pembentukan panitia kerja darurat nasional",
            "D": "Dalam tenggat sembilan puluh hari setelah berkas pencalonan diverifikasi mahkamah",
            "E": "Tepat seratus dua puluh hari kerja terhitung sejak rapat pleno paripurna pertama"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "MPR bertugas memilih Presiden dan Wakil Presiden apabila keduanya berhenti secara bersamaan dari dua paket calon yang diajukan partai politik peraih suara terbanyak pertama dan kedua pada pemilu sebelumnya, **selambat-lambatnya dalam waktu tiga puluh hari**.",
            "alasan_kunci": "Opsi B tepat karena konstitusi membatasi durasi suksesi darurat maksimal tiga puluh hari agar pemerintahan definitif segera terbentuk kembali. Calon yang dapat dipilih berasal dari paket pasangan peraih suara terbanyak pertama dan kedua pada pemilu terakhir.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena batas waktu konstitusional adalah tiga puluh hari, bukan empat belas hari kerja.",
                "C": "Kurang tepat karena enam puluh hari merupakan batas waktu pengisian kekosongan posisi wakil presiden saja.",
                "D": "Kurang tepat karena sembilan puluh hari merupakan batas waktu pemeriksaan sengketa pemakzulan di MK.",
                "E": "Kurang tepat karena durasi 120 hari tidak dikenal dalam prosedur suksesi darurat kepresidenan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 2,
        "halaman_sumber": 46,
        "kata_kunci_modul": ["Selaku kepala pemerintahan (fungsi eksekutif dan fungsi legislatif) dan kepala negara"],
        "topik_materi": "Kedudukan Ganda Lembaga Kepresidenan Indonesia",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Dalam sistem ketatanegaraan Indonesia pasca-amandemen UUD 1945, kedudukan Presiden Republik Indonesia dirumuskan sebagai...",
        "pilihan": {
            "A": "Pelaksana kekuasaan yudikatif tertinggi peradilan kasasi perkara tata usaha",
            "B": "Selaku kepala pemerintahan (fungsi eksekutif dan fungsi legislatif) dan kepala negara",
            "C": "Pemimpin tunggal lembaga legislatif yang berwenang membubarkan parlemen",
            "D": "Mandataris mutlak majelis yang bertanggung jawab penuh kepada pimpinan fraksi",
            "E": "Ketua dewan pertimbangan yang menjalankan fungsi peradilan sengketa pemilu"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Presiden sebagai pengemban amanat rakyat memiliki kedudukan **selaku kepala pemerintahan (fungsi eksekutif dan fungsi legislatif) dan kepala negara**.",
            "alasan_kunci": "Opsi B tepat karena dalam sistem presidensial Indonesia, jabatan kepala negara (*head of state*) dan kepala pemerintahan (*head of government*) terintegrasi dalam diri Presiden. Di samping memegang kekuasaan eksekutif, Presiden juga memegang fungsi legislatif bersama DPR dalam membentuk undang-undang.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena kekuasaan peradilan kasasi tertinggi dipegang oleh Mahkamah Agung.",
                "C": "Kurang tepat karena Presiden dilarang membekukan atau membubarkan Dewan Perwakilan Rakyat.",
                "D": "Kurang tepat karena Presiden bukan mandataris MPR pasca-amandemen melainkan dipilih langsung oleh rakyat.",
                "E": "Kurang tepat karena peradilan perselisihan hasil pemilu merupakan wewenang Mahkamah Konstitusi."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 3,
        "halaman_sumber": 46,
        "kata_kunci_modul": ["Dipilih secara langsung oleh rakyat dalam suatu pemilihan umum"],
        "topik_materi": "Legitimasi Presiden Berdasarkan Pemilihan Umum Langsung",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Sumber legitimasi kekuasaan Presiden dan Wakil Presiden Republik Indonesia ditegaskan melalui asas bahwa pasangan kepresidenan...",
        "pilihan": {
            "A": "Ditetapkan secara bergantian oleh koalisi partai politik pemenang pemilu",
            "B": "Dipilih secara langsung oleh rakyat dalam suatu pemilihan umum",
            "C": "Diangkat oleh sidang gabungan seluruh perwakilan kepala daerah provinsi",
            "D": "Ditetapkan melalui pemungutan suara berjenjang utusan golongan masyarakat",
            "E": "Diputuskan oleh musyawarah pimpinan majelis permusyawaratan dewan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pasca-amandemen UUD 1945, Presiden dan Wakil Presiden **dipilih secara langsung oleh rakyat dalam suatu pemilihan umum** sehingga memiliki legitimasi mandat rakyat yang sangat kuat.",
            "alasan_kunci": "Opsi B tepat karena pemilihan umum secara langsung oleh rakyat memutus mata rantai subordinasi Presiden terhadap parlemen. Rakyat pemilih berdaulat penuh menentukan pemimpin nasional melalui pesta demokrasi lima tahunan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena presiden tidak ditetapkan bergantian oleh partai melainkan dipilih langsung oleh rakyat.",
                "C": "Kurang tepat karena kepala daerah tidak memiliki kewenangan memilih pasangan presiden definitif.",
                "D": "Kurang tepat karena utusan golongan telah ditiadakan dari sistem pemilihan ketatanegaraan Indonesia.",
                "E": "Kurang tepat karena MPR tidak lagi memilih presiden dalam kondisi normal pasca-pemilu langsung."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 4,
        "halaman_sumber": 46,
        "kata_kunci_modul": ["dapat dipilih kembali dalam jabatan yang sama untuk satu kali"],
        "topik_materi": "Batasan Masa Jabatan Eksekutif Berdasarkan UUD 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pembatasan masa pengabdian kepala pemerintahan dalam konstitusi Indonesia diatur secara definitif bahwa Presiden...",
        "pilihan": {
            "A": "Dapat memegang kekuasaan seumur hidup manakala didukung oleh konsensus dewan",
            "B": "Memegang jabatan lima tahun dan dapat dipilih kembali dalam jabatan yang sama untuk satu kali",
            "C": "Dapat dipilih berulang kali tanpa batasan periode asalkan disetujui mahkamah",
            "D": "Wajib meletakkan jabatan setelah tiga tahun pengabdian guna rotasi kabinet",
            "E": "Diberikan kewenangan memperpanjang mandat secara mandiri saat situasi krisis"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Presiden memegang jabatan selama lima tahun dan sesudahnya **dapat dipilih kembali dalam jabatan yang sama untuk satu kali** masa jabatan (maksimal sepuluh tahun).",
            "alasan_kunci": "Opsi B tepat karena ketentuan ini dirumuskan pasca-reformasi untuk mencegah otoritarianisme dan personifikasi kekuasaan mutlak. Masa bakti maksimal dibatasi dua periode berturut-turut maupun berselang.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena masa jabatan seumur hidup bertentangan dengan prinsip demokrasi konstitusional.",
                "C": "Kurang tepat karena pemilihan tanpa batas periode telah dihapus oleh amandemen UUD 1945.",
                "D": "Kurang tepat karena masa jabatan reguler yang sah adalah lima tahun, bukan tiga tahun.",
                "E": "Kurang tepat karena presiden tidak berhak memperpanjang masa jabatannya secara sekehendak sendiri."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 5,
        "halaman_sumber": 46,
        "kata_kunci_modul": ["Dapat diberhentikan dari jabatannya oleh MPR atas usul DPR"],
        "topik_materi": "Mekanisme Pemberhentian Presiden Berdasarkan Putusan MK",
        "level_kognitif": "C3 - Aplikasi",
        "pertanyaan": "Dalam hal Presiden terbukti melakukan pelanggaran hukum berat, prosedur konstitusional pemberhentiannya adalah...",
        "pilihan": {
            "A": "DPR memberhentikan Presiden secara langsung melalui rapat pleno internal komisi",
            "B": "Dapat diberhentikan dari jabatannya oleh MPR atas usul DPR berdasarkan putusan Mahkamah Konstitusi",
            "C": "Mahkamah Agung mengeksekusi pemberhentian seketika tanpa melibatkan sidang majelis",
            "D": "Kabinet kementerian mengambil alih kendali kekuasaan melalui mosi tidak percaya",
            "E": "Badan Pemeriksa Keuangan mencopot jabatan kepala negara atas dasar temuan audit"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Presiden **dapat diberhentikan dari jabatannya oleh MPR atas usul DPR berdasarkan putusan Mahkamah Konstitusi**.",
            "alasan_kunci": "Opsi B tepat karena proses *impeachment* memadukan mekanisme pengawasan politik dewan (DPR), verifikasi peradilan hukum yang adil (*due process of law* di MK), dan penetapan putusan politik tertinggi di forum MPR.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena DPR tidak berwenang memberhentikan presiden secara langsung.",
                "C": "Kurang tepat karena MA tidak mengadili pemakzulan presiden melainkan MK yang memutus pendapat DPR.",
                "D": "Kurang tepat karena kabinet menteri merupakan pembantu presiden dan tidak mengenal mekanisme mosi tidak percaya.",
                "E": "Kurang tepat karena BPK bertugas memeriksa keuangan negara dan tidak berwenang mencopot pejabat politik."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 6,
        "halaman_sumber": 46,
        "kata_kunci_modul": ["Tidak dapat membekukan atau membubarkan DPR"],
        "topik_materi": "Prinsip Kesetaraan Hubungan Presiden dan Parlemen",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Salah satu jaminan ketatanegaraan yang memperkuat kedudukan lembaga legislatif di hadapan eksekutif adalah aturan bahwa Presiden...",
        "pilihan": {
            "A": "Berhak menunda persidangan dewan apabila rancangan undang-undang ditangguhkan",
            "B": "Tidak dapat membekukan atau membubarkan DPR dalam situasi ketatanegaraan apa pun",
            "C": "Dapat membubarkan parlemen dengan persetujuan dewan pertimbangan agung",
            "D": "Memiliki wewenang mencopot pimpinan fraksi partai yang berbeda haluan kebijakan",
            "E": "Dapat menetapkan anggota legislatif pengganti antarwaktu secara otoritatif"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sesuai Pasal 7C UUD 1945, Presiden **tidak dapat membekukan atau membubarkan DPR**, yang menjamin independensi cabang kekuasaan legislatif.",
            "alasan_kunci": "Opsi B tepat karena ketentuan ini menjamin prinsip *checks and balances*. Presiden tidak dapat membubarkan DPR seperti dalam sistem parlementer atau maklumat masa lalu (Dekrit 1959), sehingga parlemen bebas menjalankan fungsi pengawasan tanpa ancaman pembubaran eksekutif.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Presiden tidak berwenang menunda jadwal masa sidang dewan.",
                "C": "Kurang tepat karena DPA telah dibubarkan dan presiden dilarang membubarkan dewan dalam bentuk apa pun.",
                "D": "Kurang tepat karena pergantian pimpinan fraksi merupakan ranah internal otonom partai politik.",
                "E": "Kurang tepat karena PAW anggota dewan tunduk pada undang-undang pemilu dan mekanisme internal partai."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 7,
        "halaman_sumber": 46,
        "kata_kunci_modul": ["diganti Wakil Presiden sampai habis masa jabatannya"],
        "topik_materi": "Suksesi Jabatan Presiden Apabila Berhalangan Tetap",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Apabila Presiden Republik Indonesia mangkat, berhenti, atau diberhentikan dalam masa jabatannya, ketentuan konstitusional menetapkan bahwa Presiden...",
        "pilihan": {
            "A": "Digantikan oleh pimpinan majelis permusyawaratan secara ad interim",
            "B": "Diganti Wakil Presiden sampai habis masa jabatannya secara definitif",
            "C": "Digantikan oleh koalisi tiga menteri koordinator hingga pemilu ulang",
            "D": "Diganti oleh ketua mahkamah agung selaku pejabat pelaksana harian",
            "E": "Digantikan oleh perwakilan partai politik pemenang kursi terbanyak"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pasal 8 ayat (1) UUD 1945 menentukan jika Presiden mangkat, berhenti, diberhentikan, atau tidak dapat melakukan kewajibannya, maka **diganti Wakil Presiden sampai habis masa jabatannya**.",
            "alasan_kunci": "Opsi B tepat karena Wakil Presiden merupakan pasangan dwitunggal yang terpilih bersamaan dalam pemilu. Saat kursi kepresidenan lowong karena halangan tetap, Wakil Presiden otomatis dilantik menjadi Presiden definitif guna menuntaskan sisa periode berjalan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pimpinan majelis tidak berkedudukan menggantikan presiden dalam kondisi ini.",
                "C": "Kurang tepat karena menteri koordinator bukan pelaksana tugas suksesi tunggal jika wapres masih ada.",
                "D": "Kurang tepat karena ketua MA memegang kekuasaan kehakiman dan dilarang memangku jabatan eksekutif.",
                "E": "Kurang tepat karena pergantian presiden terikat pada garis suksesi konstitusi, bukan penunjukan partai."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 8,
        "halaman_sumber": 46,
        "kata_kunci_modul": ["Menteri Luar Negeri, Menteri Dalam Negeri, dan Menteri Pertahanan secara bersama-sama"],
        "topik_materi": "Pelaksana Tugas Kepresidenan Bersama (Triumvirat)",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Apabila Presiden dan Wakil Presiden mangkat atau berhalangan tetap secara bersamaan dalam masa jabatannya, pelaksana tugas kepresidenan diemban oleh...",
        "pilihan": {
            "A": "Panglima TNI, Kepala Kepolisian Negara, dan Kepala Badan Intelijen Negara",
            "B": "Menteri Luar Negeri, Menteri Dalam Negeri, dan Menteri Pertahanan secara bersama-sama",
            "C": "Menteri Koordinator Bidang Polhukam, Menteri Keuangan, dan Jaksa Agung",
            "D": "Ketua MPR, Ketua DPR, serta Ketua Dewan Perwakilan Daerah secara kolegial",
            "E": "Ketua Mahkamah Agung, Ketua Mahkamah Konstitusi, dan Ketua Komisi Yudisial"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Jika Presiden dan Wapres mangkat bersamaan, maka Pelaksana Tugas Kepresidenan adalah **Menteri Luar Negeri, Menteri Dalam Negeri, dan Menteri Pertahanan secara bersama-sama** (*triumvirat*).",
            "alasan_kunci": "Opsi B tepat karena ketiga kementerian ini merepresentasikan pilar dasar stabilitas kedaulatan negara (diplomasi luar negeri, administrasi pemerintahan dalam negeri, dan pertahanan teritorial) yang bertugas mengawal transisi hingga MPR memilih presiden definitif.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena aparat keamanan dan intelijen bertindak sebagai pelaksana teknis pertahanan keamanan di bawah komando pemerintah.",
                "C": "Kurang tepat karena komposisi triumvirat konstitusional secara baku menetapkan Menlu, Mendagri, dan Menhan.",
                "D": "Kurang tepat karena pimpinan lembaga legislatif bertugas memimpin sidang pemilihan presiden definitif di MPR.",
                "E": "Kurang tepat karena pimpinan lembaga yudikatif menjaga independensi kekuasaan kehakiman dan tidak menjalankan eksekutif."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 9,
        "halaman_sumber": 46,
        "kata_kunci_modul": ["Mengajukan usul perubahan pasal-pasal Undang-Undang Dasar oleh sekurangkurangnya 1/3"],
        "topik_materi": "Hak Anggota MPR Mengajukan Perubahan UUD",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Salah satu hak konstitusional yang dimiliki oleh anggota Majelis Permusyawaratan Rakyat dalam menjalankan tugasnya adalah...",
        "pilihan": {
            "A": "Menerbitkan dekrit darurat pembubaran lembaga-lembaga tinggi negara",
            "B": "Mengajukan usul perubahan pasal-pasal Undang-Undang Dasar oleh sekurang-kurangnya 1/3 dari jumlah anggota MPR",
            "C": "Menetapkan besaran tarif retribusi daerah tanpa koordinasi pemerintah daerah",
            "D": "Membatalkan putusan peradilan tingkat banding perkara kejahatan perpajakan",
            "E": "Menyetujui pengangkatan pimpinan perusahaan milik negara secara administratif"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Hak anggota MPR meliputi: **mengajukan usul perubahan pasal-pasal Undang-Undang Dasar oleh sekurang-kurangnya 1/3 dari jumlah anggota MPR**, memilih dan dipilih, membela diri, imunitas, protokoler, serta keuangan dan administratif.",
            "alasan_kunci": "Opsi B tepat karena hak inisiatif amandemen merupakan wewenang substantif anggota MPR yang dijamin oleh UUD 1945 asalkan memenuhi kuorum usulan formal sekurang-kurangnya sepertiga anggota secara tertulis.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena anggota MPR tidak berwenang menerbitkan dekrit pembubaran lembaga negara.",
                "C": "Kurang tepat karena penetapan retribusi daerah merupakan wewenang DPRD bersama kepala daerah.",
                "D": "Kurang tepat karena pembatalan putusan peradilan merupakan wewenang hierarki badan yudikatif (kasasi di MA).",
                "E": "Kurang tepat karena pengangkatan direksi BUMN merupakan ranah kementerian BUMN dan rapat pemegang saham."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 10,
        "halaman_sumber": 46,
        "kata_kunci_modul": ["Imunitas; Protokoler; dan Keuangan dan administratif"],
        "topik_materi": "Hak Proteksi dan Kelengkapan Anggota MPR",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Selain hak mengajukan usul perubahan UUD dan hak membela diri, anggota MPR dilengkapi dengan hak...",
        "pilihan": {
            "A": "Veto mutlak terhadap regulasi menteri, eksekutif daerah, dan kejaksaan",
            "B": "Imunitas, protokoler, serta hak keuangan dan administratif kedinasan",
            "C": "Pemberian grasi massal bagi narapidana tindak pidana kepabeanan negara",
            "D": "Penggeledahan mandiri terhadap sarana kantor pemerintahan tingkat wilayah",
            "E": "Penyidikan langsung terhadap pelanggaran perizinan lingkungan hidup daerah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Hak-hak anggota MPR menurut UUD 1945 mencakup hak memilih dan dipilih, membela diri, **imunitas, protokoler, dan keuangan dan administratif**.",
            "alasan_kunci": "Opsi B tepat karena hak imunitas melindungi anggota dewan dari tuntutan hukum atas pernyataan yang disampaikan dalam sidang kedewanan, hak protokoler menjamin tata kehormatan kedudukan negara, dan hak keuangan/administratif menunjang operasional pengabdian kedewanan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena anggota MPR tidak memiliki hak veto terhadap peraturan menteri atau eksekutif daerah.",
                "C": "Kurang tepat karena pemberian grasi merupakan wewenang presiden atas pertimbangan Mahkamah Agung.",
                "D": "Kurang tepat karena penggeledahan merupakan wewenang aparat penegak hukum pro justitia.",
                "E": "Kurang tepat karena penyidikan lingkungan hidup merupakan wewenang PPNS lingkungan hidup dan kepolisian."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },

    # --- HALAMAN 47 (Q11 - Q20) ---
    {
        "nomor": 11,
        "halaman_sumber": 47,
        "kata_kunci_modul": ["Menjalankan kekuasaan pemerintahan negara menurut Undang-undang Dasar"],
        "topik_materi": "Tugas Presiden Menjalankan Pemerintahan Menurut UUD",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Tugas dan wewenang pokok Presiden Republik Indonesia selaku Kepala Pemerintahan dalam bidang eksekutif adalah...",
        "pilihan": {
            "A": "Menyelenggarakan penuntutan perkara kejahatan korporasi di peradilan tinggi",
            "B": "Menjalankan kekuasaan pemerintahan negara menurut Undang-undang Dasar",
            "C": "Meninjau kembali putusan sengketa hasil pemilihan kepala daerah provinsi",
            "D": "Mengadili pelanggaran kode etik para hakim agung di lingkungan peradilan",
            "E": "Mengesahkan kepengurusan partai politik oposisi secara mutlak otoritatif"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas dan wewenang Presiden selaku Kepala Pemerintahan nomor satu adalah **menjalankan kekuasaan pemerintahan negara menurut Undang-undang Dasar**.",
            "alasan_kunci": "Opsi B tepat karena Pasal 4 ayat (1) UUD 1945 memberikan mandat kekuasaan eksekutif kepada Presiden untuk memimpin birokrasi dan administrasi negara dengan bersandar teguh pada koridor konstitusi.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penuntutan pidana dijalankan oleh lembaga Kejaksaan Republik Indonesia.",
                "C": "Kurang tepat karena pengujian sengketa hasil pemilihan umum merupakan wewenang Mahkamah Konstitusi.",
                "D": "Kurang tepat karena pengawasan etik hakim agung merupakan ranah wewenang Komisi Yudisial.",
                "E": "Kurang tepat karena pengesahan badan hukum parpol tunduk pada aturan administrasi kementerian hukum, bukan keputusan personal presiden."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 12,
        "halaman_sumber": 47,
        "kata_kunci_modul": ["Menetapkan Peraturan Pemerintah untuk menjalankan undang-undang sebagaimana mestinya"],
        "topik_materi": "Kewenangan Menetapkan Peraturan Pemerintah (PP)",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan Pasal 5 ayat (2) UUD 1945, wewenang regulasi Presiden dalam rangka implementasi undang-undang adalah...",
        "pilihan": {
            "A": "Menerbitkan fatwa hukum yang mengikat jalannya persidangan perkara perdata",
            "B": "Menetapkan Peraturan Pemerintah untuk menjalankan undang-undang sebagaimana mestinya",
            "C": "Mengubah substansi pasal undang-undang secara sepihak lewat surat edaran",
            "D": "Menetapkan traktat bilateral tanpa memerlukan koordinasi perwakilan kementerian",
            "E": "Menginstruksikan pembatalan vonis hakim pengadilan umum demi stabilitas"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sesuai Pasal 5 ayat (2) UUD 1945, Presiden bertugas **menetapkan Peraturan Pemerintah untuk menjalankan undang-undang sebagaimana mestinya**.",
            "alasan_kunci": "Opsi B tepat karena Peraturan Pemerintah (PP) merupakan instrumen pelaksana yang menjabarkan norma undang-undang agar dapat diterapkan secara teknis operasional oleh kementerian dan lembaga.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penerbitan fatwa peradilan merupakan kompetensi internal lembaga peradilan.",
                "C": "Kurang tepat karena surat edaran tidak berkedudukan sederajat dengan UU dan dilarang mengubah isi pasal UU.",
                "D": "Kurang tepat karena traktat bilateral wajib melalui mekanisme perundingan dan ratifikasi diplomatik.",
                "E": "Kurang tepat karena eksekutif dilarang mengintervensi atau membatalkan vonis peradilan yang merdeka."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 13,
        "halaman_sumber": 47,
        "kata_kunci_modul": ["Mengajukan dan membahas rancangan undang-undang bersama DPR"],
        "topik_materi": "Kewenangan Legislatif Presiden Bersama DPR",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Keterlibatan Presiden Republik Indonesia dalam pelaksanaan fungsi legislasi diatur dalam konstitusi melalui hak untuk...",
        "pilihan": {
            "A": "Menolak pengundangan naskah undang-undang yang telah disetujui bersama dewan",
            "B": "Mengajukan dan membahas rancangan undang-undang bersama DPR guna persetujuan",
            "C": "Membatalkan sepihak undang-undang yang diterbitkan pada masa pemerintahan lalu",
            "D": "Mengesahkan undang-undang tanpa melibatkan pembahasan dengan komisi dewan",
            "E": "Menetapkan sanksi pidana dalam keputusan presiden tanpa dasar undang-undang"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Fungsi legislatif Presiden selaku Kepala Pemerintahan tercermin dalam hak untuk **mengajukan dan membahas rancangan undang-undang bersama DPR** guna mendapatkan persetujuan bersama.",
            "alasan_kunci": "Opsi B tepat karena proses pembentukan undang-undang di Indonesia melibatkan kerja sama setara antara Presiden dan DPR. Presiden berhak mengusulkan RUU inisiatif pemerintah serta wajib membahasnya bersama DPR.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena UU yang telah disetujui bersama otomatis sah berlaku setelah 30 hari meskipun tidak ditandatangani.",
                "C": "Kurang tepat karena pembatalan UU merupakan wewenang pengujian yudisial di Mahkamah Konstitusi.",
                "D": "Kurang tepat karena pembentukan UU wajib dibahas bersama DPR untuk memperoleh persetujuan bersama.",
                "E": "Kurang tepat karena penetapan sanksi pidana merupakan materi muatan undang-undang atau perda, bukan Keppres."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 14,
        "halaman_sumber": 47,
        "kata_kunci_modul": ["Menetapkan Peraturan Pemerintah Pengganti Undang-undang (Perpu)"],
        "topik_materi": "Kewenangan Menetapkan Perpu dalam Kegentingan Memaksa",
        "level_kognitif": "C3 - Aplikasi",
        "pertanyaan": "Dalam hal ihwal kegentingan yang memaksa dan menuntut payung hukum segera, instrumen regulasi darurat yang berhak diterbitkan Presiden adalah...",
        "pilihan": {
            "A": "Surat keputusan kementerian koordinator bidang perekonomian negara",
            "B": "Menetapkan Peraturan Pemerintah Pengganti Undang-undang (Perpu) secara mandiri",
            "C": "Instruksi gubernur militer untuk membekukan aktivitas lembaga daerah",
            "D": "Naskah perjanjian perbatasan wilayah dengan negara sahabat tetangga",
            "E": "Nota kesepahaman antarfraksi partai politik pemilik kursi mayoritas"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sesuai Pasal 22 UUD 1945, dalam hal ihwal kegentingan yang memaksa, Presiden berhak **menetapkan Peraturan Pemerintah Pengganti Undang-undang (Perpu)** yang kemudian harus mendapat persetujuan DPR pada masa sidang berikutnya.",
            "alasan_kunci": "Opsi B tepat karena Perpu memberikan kewenangan darurat kepada Presiden untuk segera mengatasi situasi darurat tanpa harus menunggu proses legislasi biasa di DPR yang memakan waktu panjang.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena keputusan menteri koordinator tidak berderajat setingkat undang-undang.",
                "C": "Kurang tepat karena gubernur militer tidak berwenang menerbitkan produk hukum berderajat undang-undang.",
                "D": "Kurang tepat karena perjanjian perbatasan memerlukan persetujuan DPR dan ratifikasi undang-undang.",
                "E": "Kurang tepat karena nota kesepahaman antarfraksi partai bukan merupakan produk peraturan perundangan negara."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 15,
        "halaman_sumber": 47,
        "kata_kunci_modul": ["Mengajukan dan membahas usul RAPBN bersama DPR"],
        "topik_materi": "Wewenang Presiden Dalam Penganggaran Negara",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Kewenangan fiskal Presiden Republik Indonesia selaku Kepala Pemerintahan dalam pengelolaan anggaran negara diwujudkan melalui tugas untuk...",
        "pilihan": {
            "A": "Mengesahkan penarikan pinjaman luar negeri tanpa keterlibatan dewan perwakilan",
            "B": "Mengajukan dan membahas usul RAPBN bersama DPR dengan pertimbangan DPD",
            "C": "Menentukan alokasi pagu dana kementerian secara tertutup tanpa audit publik",
            "D": "Mengalihkan surplus kas perbendaharaan negara ke rekening pribadi pimpinan",
            "E": "Menolak audit pertanggungjawaban belanja yang diajukan oleh auditor BPK"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tugas Presiden selaku Kepala Pemerintahan nomor 5 adalah **mengajukan dan membahas usul RAPBN bersama DPR** dengan memperhatikan pertimbangan DPD.",
            "alasan_kunci": "Opsi B tepat karena penyusunan anggaran negara (APBN) merupakan tanggung jawab eksekutif yang diajukan oleh Presiden setiap tahun (melalui Pidato Pengantar Nota Keuangan) untuk dibahas dan disetujui bersama DPR.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pembuatan perjanjian utang negara wajib tunduk pada ketentuan undang-undang dan pengawasan DPR.",
                "C": "Kurang tepat karena alokasi anggaran dibahas terbuka dan transparan di komisi dan badan anggaran dewan.",
                "D": "Kurang tepat karena kas perbendaharaan negara dikelola melalui Kas Umum Negara yang terpisah dari urusan pribadi.",
                "E": "Kurang tepat karena pemerintah wajib membuka akses pertanggungjawaban belanja kepada auditor BPK."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 16,
        "halaman_sumber": 47,
        "kata_kunci_modul": ["Memegang kekuasaan tertinggi atas Angkatan Darat, Angkatan Laut, dan Angkatan Udara"],
        "topik_materi": "Wewenang Panglima Tertinggi Angkatan Bersenjata",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan Pasal 10 UUD 1945, wewenang Presiden Republik Indonesia dalam kapasitasnya sebagai Kepala Negara adalah...",
        "pilihan": {
            "A": "Memimpin peradilan mahkamah militer agung dalam pengadilan desersi pasukan",
            "B": "Memegang kekuasaan tertinggi atas Angkatan Darat, Angkatan Laut, dan Angkatan Udara",
            "C": "Menetapkan pangkat perwira menengah di daerah tanpa melalui seleksi dinas",
            "D": "Menginstruksikan mobilisasi militer untuk kepentingan kampanye pemilihan umum",
            "E": "Menyita sarana transportasi sipil tanpa kompensasi pada waktu kondisi damai"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Presiden sebagai kepala negara bertugas **memegang kekuasaan tertinggi atas Angkatan Darat, Angkatan Laut, dan Angkatan Udara**.",
            "alasan_kunci": "Opsi B tepat karena Presiden merupakan Panglima Tertinggi (*Supreme Commander*) Tentara Nasional Indonesia yang memegang supremasi sipil atas kekuatan militer dalam kerangka pertahanan negara.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena peradilan militer dipimpin oleh hakim militer di bawah yurisdiksi teknis Mahkamah Agung.",
                "C": "Kurang tepat karena kepangkatan perwira menengah diatur menurut mekanisme meritokrasi dan regulasi internal TNI.",
                "D": "Kurang tepat karena TNI bersikap netral dalam politik praktis dan pemilu.",
                "E": "Kurang tepat karena penyitaan tanpa kompensasi bertentangan dengan jaminan hak kebendaan warga negara."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 17,
        "halaman_sumber": 47,
        "kata_kunci_modul": ["Dengan persetujuan DPR, menyatakan perang, membuat perdamaian dan perjanjian internasional"],
        "topik_materi": "Pernyataan Perang dan Hubungan Internasional",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Kewenangan Presiden dalam Pasal 11 UUD 1945 terkait pernyataan perang dan perjanjian internasional wajib dilaksanakan melalui syarat...",
        "pilihan": {
            "A": "Meminta pertimbangan tertulis dari dewan pimpinan mahkamah konstitusi",
            "B": "Dengan persetujuan DPR, menyatakan perang, membuat perdamaian dan perjanjian internasional",
            "C": "Melakukan konsultasi tertutup dengan komite pertahanan negara tetangga",
            "D": "Menunggu persetujuan aklamasi dari seluruh pimpinan satuan kerja dinas",
            "E": "Mendapatkan rekomendasi mandiri dari dewan pertimbangan daerah provinsi"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Presiden selaku Kepala Negara berwenang: **dengan persetujuan DPR, menyatakan perang, membuat perdamaian dan perjanjian internasional dengan negara lain**.",
            "alasan_kunci": "Opsi B tepat karena keputusan menyatakan perang dan perjanjian yang berdampak luas bagi kehidupan rakyat menyangkut beban keuangan negara serta kedaulatan, sehingga mutlak membutuhkan persetujuan wakil rakyat di DPR.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena persetujuan konstitusional berasal dari DPR, bukan dari Mahkamah Konstitusi.",
                "C": "Kurang tepat karena konsultasi dengan dewan parlemen nasional adalah keharusan, bukan dengan komite asing.",
                "D": "Kurang tepat karena persetujuan pimpinan satuan kerja dinas bukan syarat ketatanegaraan hubungan luar negeri.",
                "E": "Kurang tepat karena DPD memberikan pertimbangan di bidang legislasi otonomi daerah, bukan persetujuan perang."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 18,
        "halaman_sumber": 47,
        "kata_kunci_modul": ["Dengan memperhatikan pertimbangan Dewan Perwakilan Rakyat, mengangkat duta dan konsul"],
        "topik_materi": "Pengangkatan Duta dan Konsul Berdasarkan Pasal 13",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Dalam mengangkat duta dan konsul untuk perwakilan diplomatik di negara sahabat, Presiden wajib menempuh prosedur...",
        "pilihan": {
            "A": "Menyerahkan proses seleksi sepenuhnya kepada organisasi profesi advokat",
            "B": "Dengan memperhatikan pertimbangan Dewan Perwakilan Rakyat, mengangkat duta dan konsul",
            "C": "Mendapatkan rekomendasi mutlak dari komisi yudisial bidang pengawasan hakim",
            "D": "Meminta pengujian kepatutan administratif kepada badan pemeriksa keuangan",
            "E": "Mengangkat calon secara mandiri tanpa memerlukan pertimbangan lembaga dewan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pasal 13 UUD 1945 mengatur bahwa dalam mengangkat duta dan konsul, Presiden bertindak **dengan memperhatikan pertimbangan Dewan Perwakilan Rakyat**, serta menerima penempatan duta negara lain dengan memperhatikan pertimbangan DPR.",
            "alasan_kunci": "Opsi B tepat karena perwakilan diplomatik mencerminkan wibawa dan kepentingan strategis bangsa di luar negeri, sehingga proses penempatannya melibatkan pertimbangan komisi terkait di DPR demi menjamin integritas dan kompetensi calon.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penunjukan diplomat negara merupakan kewenangan eksekutif di bidang luar negeri.",
                "C": "Kurang tepat karena Komisi Yudisial bertugas di ranah kekuasaan kehakiman, bukan diplomatik.",
                "D": "Kurang tepat karena BPK bertugas memeriksa keuangan dan tidak menguji kompetensi diplomat.",
                "E": "Kurang tepat karena konstitusi mewajibkan Presiden memperhatikan pertimbangan DPR dalam mengangkat duta."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 19,
        "halaman_sumber": 47,
        "kata_kunci_modul": ["Dengan memperhatikan pertimbangan Mahkamah Agung, memberi grasi, dan rehabilitasi"],
        "topik_materi": "Hak Prerogatif Hukum Grasi dan Rehabilitasi",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan Pasal 14 ayat (1) UUD 1945, wewenang Presiden dalam memberikan pengampunan hukum berupa grasi dan rehabilitasi dilaksanakan...",
        "pilihan": {
            "A": "Secara mandiri tanpa memerlukan pertimbangan lembaga peradilan manapun",
            "B": "Dengan memperhatikan pertimbangan Mahkamah Agung, memberi grasi, dan rehabilitasi",
            "C": "Atas instruksi tertulis dari komite etik asosiasi advokat seluruh wilayah",
            "D": "Setelah mendapatkan persetujuan tertulis dari seluruh anggota parlemen",
            "E": "Melalui referendum umum masyarakat pemilih di wilayah terjadinya pidana"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Presiden selaku Kepala Negara berwenang: **dengan memperhatikan pertimbangan Mahkamah Agung, memberi grasi, dan rehabilitasi**.",
            "alasan_kunci": "Opsi B tepat karena grasi (pengampunan hukuman) dan rehabilitasi (pemulihan nama baik) berkaitan erat dengan putusan peradilan berkekuatan hukum tetap, sehingga pertimbangan yuridis Mahkamah Agung mutlak diperhatikan oleh Presiden sebelum mengambil keputusan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena konstitusi secara eksplisit mensyaratkan pertimbangan dari Mahkamah Agung.",
                "C": "Kurang tepat karena asosiasi advokat tidak memiliki kewenangan konstitusional dalam pemberian grasi.",
                "D": "Kurang tepat karena persetujuan/pertimbangan DPR dipersyaratkan untuk pemberian amnesti dan abolisi, bukan grasi.",
                "E": "Kurang tepat karena mekanisme referendum grasi tidak dikenal dalam sistem ketatanegaraan Indonesia."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 20,
        "halaman_sumber": 47,
        "kata_kunci_modul": ["Dengan memperhatikan pertimbangan Dewan Perwakilan Rakyat, memberi amnesti dan abolisi"],
        "topik_materi": "Pemberian Amnesti dan Abolisi Berdasarkan Pasal 14 Ayat 2",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Ketentuan Pasal 14 ayat (2) UUD 1945 menegaskan bahwa pemberian pengampunan massal berupa amnesti dan abolisi oleh Presiden dilakukan...",
        "pilihan": {
            "A": "Melalui penetapan keputusan pengadilan tinggi tata usaha negara",
            "B": "Dengan memperhatikan pertimbangan Dewan Perwakilan Rakyat, memberi amnesti dan abolisi",
            "C": "Atas pertimbangan mutlak ketua komisi pemilihan umum pusat",
            "D": "Berdasarkan permohonan tertulis dari majelis hakim mahkamah agung",
            "E": "Secara sepihak tanpa melibatkan pandangan dari dewan perwakilan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Presiden berwenang: **dengan memperhatikan pertimbangan Dewan Perwakilan Rakyat, memberi amnesti dan abolisi**.",
            "alasan_kunci": "Opsi B tepat karena amnesti (penghapusan akibat hukum tindak pidana) dan abolisi (peniadaan penuntutan pidana) biasanya berkaitan dengan tindak pidana berdimensi politik (seperti rekonsiliasi pasca-konflik). Oleh karena itu, pertimbangan Dewan Perwakilan Rakyat sebagai representasi politik rakyat sangat esensial.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena amnesti dan abolisi bukan produk putusan peradilan PTUN.",
                "C": "Kurang tepat karena KPU bertugas menyelenggarakan pemilu, bukan mengurusi amnesti pidana.",
                "D": "Kurang tepat karena MA memberikan pertimbangan untuk grasi dan rehabilitasi, sedangkan amnesti/abolisi ke DPR.",
                "E": "Kurang tepat karena pemberian amnesti/abolisi dilarang dilakukan tanpa memperhatikan pertimbangan DPR."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    }
]

output_path = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch\draft_p05_sub1_hlm46_47.json"
with open(output_path, "w", encoding="utf-8") as f:
    json.dump(questions, f, indent=2, ensure_ascii=False)

print(f"Sub-Agent 1 (Paket 05) finished! Total questions: {len(questions)}")

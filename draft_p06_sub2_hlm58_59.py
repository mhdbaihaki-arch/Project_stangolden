import json

# Sub-Agent 2 (Paket 06): Halaman 58 dan 59 (Nomor 21 s/d 40)
# Halaman 58: Nomor 21 - 30
# Halaman 59: Nomor 31 - 40

questions = [
    # --- HALAMAN 58 (Q21 - Q30) ---
    {
        "nomor": 21,
        "halaman_sumber": 58,
        "kata_kunci_modul": ["menarik kembali mandat MPRS dari Presiden pada saat itu yaitu Ir. Soekarno"],
        "topik_materi": "Sidang Istimewa MPRS Tahun 1967",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Ketetapan penting yang dihasilkan dalam Sidang Istimewa MPRS tahun 1967 melalui TAP MPRS No. XXXIII/MPRS/1967 adalah...",
        "pilihan": {
            "A": "Mengesahkan pembentukan kabinet koalisi bersama pimpinan partai politik",
            "B": "Menarik kembali mandat MPRS dari Presiden Ir. Soekarno dan mengangkat pejabat Presiden",
            "C": "Menolak pertanggungjawaban panitia perancang undang-undang darurat pemilu",
            "D": "Membubarkan secara permanen seluruh jajaran kepolisian dan kejaksaan agung",
            "E": "Menetapkan pemilihan umum demokratis pertama dalam kurun waktu sepuluh hari"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pada tahun 1967 diadakan Sidang Istimewa MPRS yang **menarik kembali mandat MPRS dari Presiden pada saat itu yaitu Ir. Soekarno, selanjutnya mengangkat Jenderal Soeharto sebagai pejabat Presiden (TAP MPRS No. XXXIII/MPRS/1967)**.",
            "alasan_kunci": "Opsi B tepat karena penolakan terhadap pidato Nawaksara dan pelengkap Nawaksara mendorong MPRS mencabut kekuasaan eksekutif dari Presiden Soekarno dan menyerahkan mandat pejabat presiden kepada Jenderal Soeharto.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Sidang Istimewa 1967 berfokus pada suksesi kepemimpinan kepala negara, bukan kabinet partai.",
                "C": "Kurang tepat karena MPRS mengadili pertanggungjawaban presiden, bukan panitia pemilu.",
                "D": "Kurang tepat karena MPRS tidak membubarkan aparat penegak hukum.",
                "E": "Kurang tepat karena pemilu baru diselenggarakan kemudian pada tahun 1971."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 22,
        "halaman_sumber": 58,
        "kata_kunci_modul": ["mengangkat Jenderal Soeharto sebagai Presiden tetap sampai terpilihnya Presiden hasil pemilu"],
        "topik_materi": "Sidang Umum MPRS Tahun 1968",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Perubahan status kepemimpinan nasional pada Sidang Umum MPRS tahun 1968 ditetapkan melalui keputusan untuk...",
        "pilihan": {
            "A": "Menetapkan masa jabatan kepala negara berlaku seumur hidup tanpa evaluasi",
            "B": "Mengangkat Jenderal Soeharto sebagai Presiden tetap sampai terpilihnya Presiden hasil pemilu",
            "C": "Membubarkan lembaga perwakilan rakyat dan membentuk dewan pertahanan darurat",
            "D": "Mendelegasikan fungsi anggaran belanja negara kepada pimpinan perbankan asing",
            "E": "Menyerahkan mandat peradilan perdata kepada komite arbitrase internasional"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pada tahun 1968 diadakan Sidang Umum MPRS yang **mengangkat Jenderal Soeharto sebagai Presiden tetap sampai terpilihnya Presiden hasil pemilu (TAP MPRS No. XLIV/MPRS/1968)**.",
            "alasan_kunci": "Opsi B tepat karena TAP MPRS No. XLIV/MPRS/1968 mengukuhkan status Jenderal Soeharto dari pejabat presiden menjadi Presiden Republik Indonesia definitif hingga pemilu umum berikutnya terlaksana.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pengangkatan presiden seumur hidup adalah produk MPRS 1963 era Orde Lama yang dicabut.",
                "C": "Kurang tepat karena MPRS tidak membubarkan DPR melainkan mengukuhkan kelembagaan negara.",
                "D": "Kurang tepat karena penetapan APBN tetap berada di bawah wewenang pemerintah dan DPR-GR.",
                "E": "Kurang tepat karena kedaulatan peradilan tetap berada pada badan peradilan nasional Mahkamah Agung."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 23,
        "halaman_sumber": 58,
        "kata_kunci_modul": ["Pemilu pertama dalam masa Orde Baru diadakan pada tahun 1971"],
        "topik_materi": "Pemilihan Umum Pertama Era Orde Baru",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pelaksanaan pesta demokrasi pemilihan umum legislatif pertama pada masa pemerintahan Orde Baru diselenggarakan pada...",
        "pilihan": {
            "A": "Tahun 1966 bersamaan dengan terbitnya surat perintah sebelas maret",
            "B": "Pemilu pertama dalam masa Orde Baru diadakan pada tahun 1971",
            "C": "Tahun 1973 bertepatan dengan penetapan garis-garis besar haluan negara",
            "D": "Tahun 1968 menjelang sidang umum majelis permusyawaratan darurat",
            "E": "Tahun 1955 pada masa transisi berlakunya undang-undang dasar sementara"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Pemilu pertama dalam masa Orde Baru diadakan pada tahun 1971**, selanjutnya pada tahun 1973 diadakan sidang umum MPR yang menetapkan GBHN dan memilih Presiden dan Wakil Presiden.",
            "alasan_kunci": "Opsi B tepat karena setelah Pemilu 1955, Indonesia baru menyelenggarakan pemilihan umum kembali pada tanggal 5 Juli 1971 yang diikuti oleh 10 kontestan (9 parpol dan Sekber Golkar).",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena tahun 1966 adalah momentum transisi politik terbitnya Supersemar.",
                "C": "Kurang tepat karena tahun 1973 adalah Sidang Umum MPR hasil Pemilu 1971.",
                "D": "Kurang tepat karena tahun 1968 adalah Sidang Umum MPRS pengangkatan presiden definitif.",
                "E": "Kurang tepat karena Pemilu 1955 adalah pemilu pertama Indonesia pada masa Demokrasi Liberal."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 24,
        "halaman_sumber": 58,
        "kata_kunci_modul": ["Hak Budget DPR tidak berjalan, dan pada tahun 1960 Presiden membubarkan DPR"],
        "topik_materi": "Pembubaran DPR Hasil Pemilu Oleh Presiden Tahun 1960",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Salah satu penyimpangan ketatanegaraan terhadap UUD 1945 yang terjadi pada masa Orde Lama tahun 1960 adalah...",
        "pilihan": {
            "A": "DPR membekukan operasional kementerian keuangan karena defisit anggaran belanja",
            "B": "Hak Budget DPR tidak berjalan, dan pada tahun 1960 Presiden membubarkan DPR",
            "C": "Mahkamah agung membatalkan naskah undang-undang dasar melalui putusan peradilan",
            "D": "Pemerintah daerah membentuk badan arbitrase perbatasan teritorial kepulauan",
            "E": "Menteri penerangan memecat pimpinan fraksi partai peserta pemilihan umum"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Penyimpangan kurun waktu 1945–1966 antara lain: **Hak Budget DPR tidak berjalan, dan pada tahun 1960 Presiden membubarkan DPR, karena DPR tidak menyetujui RAPBN yang diajukan pemerintah**.",
            "alasan_kunci": "Opsi B tepat karena Presiden Soekarno mengeluarkan Penetapan Presiden No. 3 Tahun 1960 yang membubarkan DPR hasil Pemilu 1955 dan menggantikannya dengan DPR-Gotong Royong (DPR-GR), yang bertentangan dengan prinsip UUD 1945.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena DPR tidak berwenang membekukan kementerian eksekutif.",
                "C": "Kurang tepat karena MA tidak berwenang membatalkan teks UUD 1945.",
                "D": "Kurang tepat karena pemda tidak memiliki wewenang urusan perbatasan internasional.",
                "E": "Kurang tepat karena menteri tidak berwenang memecat pimpinan fraksi parlemen."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 25,
        "halaman_sumber": 58,
        "kata_kunci_modul": ["MPRS mengangkat Presiden seumur hidup"],
        "topik_materi": "Pengangkatan Presiden Seumur Hidup",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Penyimpangan terhadap ketentuan Pasal 7 UUD 1945 naskah asli pada masa Demokrasi Terpimpin ditandai oleh tindakan MPRS yang...",
        "pilihan": {
            "A": "Menetapkan masa jabatan kepala negara terbatas pada rentang waktu tiga tahun",
            "B": "MPRS mengangkat Presiden seumur hidup melalui produk ketetapan majelis",
            "C": "Mewajibkan presiden mengundurkan diri setiap kali rancangan regulasi tertunda",
            "D": "Menghapuskan fungsi kepresidenan dan menggantikannya dengan dewan presidium",
            "E": "Mendelegasikan pengangkatan kepala eksekutif kepada komite perwakilan daerah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pada masa Orde Lama terjadi penyimpangan ketatanegaraan di mana **MPRS mengangkat Presiden seumur hidup** melalui Ketetapan MPRS No. III/MPRS/1963.",
            "alasan_kunci": "Opsi B tepat karena Pasal 7 UUD 1945 secara tegas mengatur masa jabatan presiden adalah lima tahun dan sesudahnya dapat dipilih kembali. Pengangkatan seumur hidup mencederai prinsip republik dan demokrasi berkala.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena masa jabatan yang ditetapkan MPRS 1963 adalah seumur hidup, bukan tiga tahun.",
                "C": "Kurang tepat karena pengangkatan seumur hidup justru menghapuskan mekanisme pergantian berkala.",
                "D": "Kurang tepat karena jabatan presiden tetap dipertahankan bahkan diperluas kewenangannya.",
                "E": "Kurang tepat karena pengangkatan dilakukan langsung oleh MPRS bentukan Dekrit."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 26,
        "halaman_sumber": 58,
        "kata_kunci_modul": ["Ketua lembaga-lembaga tinggi negara dijadikan menteri-menteri negara"],
        "topik_materi": "Subordinasi Pimpinan Lembaga Tinggi Menjadi Menteri",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Praktik pelemahan prinsip pemisahan kekuasaan (*trias politica*) pada masa Demokrasi Terpimpin terlihat dari kebijakan bahwa...",
        "pilihan": {
            "A": "Ketua dewan perwakilan rakyat memegang komando seluruh angkatan bersenjata",
            "B": "Ketua lembaga-lembaga tinggi negara dijadikan menteri-menteri negara pembantu Presiden",
            "C": "Pimpinan badan pemeriksa keuangan berhak memveto seluruh regulasi presiden",
            "D": "Hakim agung memimpin rapat koordinasi operasional kementerian perekonomian",
            "E": "Para menteri kabinet diwajibkan merangkap jabatan sebagai kepala daerah otonom"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Penyimpangan kurun waktu Orde Lama nomor 4 adalah **Ketua lembaga-lembaga tinggi negara dijadikan menteri-menteri negara**, yang menempatkan pimpinan MPRS, DPR-GR, dan MA berstatus menteri pembantu presiden.",
            "alasan_kunci": "Opsi B tepat karena kedudukan ketua lembaga legislatif dan yudikatif yang dijadikan menteri bawahan presiden merusak independensi cabang kekuasaan lain dan menciptakan pemusatan kekuasaan mutlak di tangan Presiden.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena komando tertinggi angkatan perang dipegang oleh Presiden, bukan ketua DPR.",
                "C": "Kurang tepat karena BPK saat itu justru mengalami pelemahan fungsi pengawasan.",
                "D": "Kurang tepat karena ketua MA dijadikan menteri yang tunduk pada presiden, bukan memimpin kementerian ekonomi.",
                "E": "Kurang tepat karena menteri tidak merangkap sebagai kepala daerah tingkat provinsi."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 27,
        "halaman_sumber": 58,
        "kata_kunci_modul": ["kekuasaan lembaga tinggi presiden sangat berperan"],
        "topik_materi": "Sentralisasi Kekuasaan Eksekutif Orde Baru",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Karakteristik penyelenggaraan ketatanegaraan pada masa Orde Baru (1966–1998) ditandai oleh fenomena hubungan kelembagaan di mana...",
        "pilihan": {
            "A": "Parlemen memegang dominasi penuh dalam menentukan arah seluruh kebijakan publik",
            "B": "Kekuasaan lembaga tinggi presiden sangat berperan dominan dibanding lembaga lainnya",
            "C": "Badan peradilan memiliki wewenang membatalkan ketetapan majelis permusyawaratan",
            "D": "Pemerintah daerah memegang kedaulatan mutlak pengelolaan sumber keuangan negara",
            "E": "Dewan pertimbangan agung menjalankan fungsi penuntutan perkara kejahatan ekonomi"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam pelaksanaan UUD 1945 kurun waktu 1966–1998: Di satu pihak, **kekuasaan lembaga tinggi presiden sangat berperan**, di lain pihak lembaga-lembaga negara lainnya belum berfungsi secara optimal (*executive heavy*).",
            "alasan_kunci": "Opsi B tepat karena meskipun lembaga negara telah dibentuk lengkap, kekuasaan eksekutif presiden sangat dominan mengendalikan proses politik dan hukum, sementara DPR dan lembaga pengawas cenderung bersikap menyetujui (*rubber stamp*).",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena DPR pada era Orde Baru berada di bawah pengaruh kuat dominasi eksekutif presiden.",
                "C": "Kurang tepat karena MA saat itu belum memiliki wewenang menguji undang-undang atau TAP MPR.",
                "D": "Kurang tepat karena tata kelola keuangan negara era Orde Baru bersifat sangat sentralistik.",
                "E": "Kurang tepat karena DPA berstatus dewan penasihat dan tidak menjalankan fungsi penuntutan hukum."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 28,
        "halaman_sumber": 58,
        "kata_kunci_modul": ["Lembaga-lembaga negara belum dibentuk berdasarkan Undang-undang"],
        "topik_materi": "Kendala Kelembagaan Pasca-Dekrit 1959",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pada masa awal kurun waktu kedua (1959–1966), pembentukan alat kelengkapan negara menghadapi kendala konstitusional berupa fakta bahwa...",
        "pilihan": {
            "A": "Seluruh anggota parlemen menolak menghadiri persidangan di ibu kota negara",
            "B": "Lembaga-lembaga negara belum dibentuk berdasarkan Undang-undang formal",
            "C": "Pemerintah menyerahkan pembentukan lembaga kepada perwakilan monarki asing",
            "D": "Kementerian kehakiman membekukan operasional hierarki peradilan tata usaha",
            "E": "Presiden dilarang mengangkat menteri sebelum persetujuan dari dewan adat"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Penyimpangan kurun waktu 1945 sampai sebelum 11 Maret 1966 nomor 1 adalah **Lembaga-lembaga negara belum dibentuk berdasarkan Undang-undang, sebagaimana tercantum dalam UUD 1945**, melainkan dibentuk lewat Penetapan Presiden.",
            "alasan_kunci": "Opsi B tepat karena MPRS, DPR-GR, dan DPA dibentuk hanya melalui Penetapan Presiden (Penpres) yang bersifat sepihak, bukan melalui undang-undang organik yang disetujui bersama oleh dewan perwakilan rakyat.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena anggota parlemen DPR-GR ditunjuk langsung oleh Presiden dan tetap bersidang.",
                "C": "Kurang tepat karena pembentukan kelembagaan dilakukan oleh otoritas internal Presiden Soekarno.",
                "D": "Kurang tepat karena peradilan tata usaha negara baru dibentuk pada era akhir Orde Baru (UU 5/1986).",
                "E": "Kurang tepat karena menteri diangkat langsung oleh Presiden sebagai hak prerogatif eksekutif."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 29,
        "halaman_sumber": 58,
        "kata_kunci_modul": ["Kurun waktu 11 Maret 1966 hingga 21 Mei 1998 yang dikenal dengan masa Orde Baru"],
        "topik_materi": "Rentang Waktu Masa Pemerintahan Orde Baru",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Rentang waktu berlangsungnya era kepemimpinan nasional yang dikenal dengan masa Orde Baru berjalan pada kurun...",
        "pilihan": {
            "A": "Terhitung sejak 17 Agustus 1950 hingga dekrit presiden 5 Juli 1959",
            "B": "Kurun waktu 11 Maret 1966 hingga 21 Mei 1998 yang dikenal dengan masa Orde Baru",
            "C": "Mulai sidang umum majelis tahun 1971 hingga pemilihan umum pertama reformasi",
            "D": "Sejak pengesahan konstitusi ris hingga tercapainya piagam persetujuan unifikasi",
            "E": "Terhitung sejak 21 Mei 1998 hingga rangkaian amandemen undang-undang dasar"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Kurun waktu 11 Maret 1966 hingga 21 Mei 1998 yang dikenal dengan masa Orde Baru** berlangsung selama kurang lebih 32 tahun di bawah kepemimpinan Presiden Soeharto.",
            "alasan_kunci": "Opsi B tepat karena masa Orde Baru dimulai sejak terbitnya Supersemar pada 11 Maret 1966 dan berakhir secara resmi saat Presiden Soeharto mengundurkan diri pada 21 Mei 1998 di hadapan Ketua Mahkamah Agung.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena rentang 1950–1959 adalah era berlakunya UUDS 1950 (Demokrasi Parlementer).",
                "C": "Kurang tepat karena Orde Baru telah dimulai sejak 1966 sebelum Pemilu 1971.",
                "D": "Kurang tepat karena era RIS berlangsung pada 1949–1950.",
                "E": "Kurang tepat karena periode pasca-21 Mei 1998 merupakan era Pasca-Orde Baru / Reformasi."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 30,
        "halaman_sumber": 58,
        "kata_kunci_modul": ["menetapkan GBHN dan memilih Presiden dan Wakil Presiden"],
        "topik_materi": "Peran MPR Memilih Presiden Pada Masa Orde Baru",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Mekanisme suksesi kepemimpinan eksekutif nasional yang berlaku pada era Orde Baru berdasarkan UUD 1945 sebelum amandemen adalah...",
        "pilihan": {
            "A": "Pasangan calon presiden dipilih langsung oleh seluruh rakyat melalui pemilu serentak",
            "B": "MPR bersidang untuk menetapkan GBHN dan memilih Presiden dan Wakil Presiden",
            "C": "Presiden ditunjuk secara bergiliran oleh para pimpinan dewan perwakilan rakyat daerah",
            "D": "Kepala pemerintahan diangkat oleh mahkamah agung berdasarkan hasil uji kompetensi",
            "E": "Suksesi kepemimpinan diserahkan kepada kesepakatan tertutup dewan perbankan nasional"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pada tahun 1973 dan sidang umum lima tahunan berikutnya diadakan sidang umum MPR yang bertugas **menetapkan GBHN dan memilih Presiden dan Wakil Presiden**.",
            "alasan_kunci": "Opsi B tepat karena sebelum amandemen UUD 1945, MPR berkedudukan sebagai lembaga tertinggi negara pemegang kedaulatan rakyat yang memilih dan mengangkat Presiden sebagai mandataris MPR.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pemilihan presiden langsung oleh rakyat baru dimulai pada Pemilu 2004 pasca-amandemen.",
                "C": "Kurang tepat karena DPRD tidak memilih presiden melainkan anggota MPR di tingkat pusat.",
                "D": "Kurang tepat karena Mahkamah Agung memegang cabang kekuasaan kehakiman dan tidak memilih presiden.",
                "E": "Kurang tepat karena suksesi kepresidenan merupakan mekanisme ketatanegaraan konstitusi formal."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },

    # --- HALAMAN 59 (Q31 - Q40) ---
    {
        "nomor": 31,
        "halaman_sumber": 59,
        "kata_kunci_modul": ["TAP MPR No.I/MPR/1983, dalam pasal 104 dinyatakan bahwa MPR berketetapan untuk mempertahankan dan tidak akan melakukan perubahan terhadap UUD 1945"],
        "topik_materi": "Ketetapan Sakralisasi UUD 1945 Era Orde Baru",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Sikap politik pemerintahan Orde Baru yang berupaya mensakralkan konstitusi dan menutup pintu perubahan dituangkan dalam...",
        "pilihan": {
            "A": "Ketetapan MPRS mengenai penunjukan presiden seumur hidup tanpa pergantian",
            "B": "Pasal 104 TAP MPR No. I/MPR/1983 yang berketetapan mempertahankan dan tidak mengubah UUD 1945",
            "C": "Undang-undang darurat penertiban organisasi partai politik tingkat nasional",
            "D": "Maklumat kementerian kehakiman mengenai pembekuan fungsi amandemen dewan",
            "E": "Keputusan presiden tentang pelarangan diskusi akademis di lingkungan kampus"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Dikeluarkannya TAP MPR No. I/MPR/1983, dalam pasal 104 dinyatakan bahwa MPR berketetapan untuk mempertahankan dan tidak akan melakukan perubahan terhadap UUD 1945**.",
            "alasan_kunci": "Opsi B tepat karena pasal 104 TAP MPR No. I/MPR/1983 mencerminkan kebijakan 'sakralisasi' konstitusi di era Orde Baru yang berusaha mengunci UUD 1945 dari upaya penyempurnaan atau perubahan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penetapan presiden seumur hidup adalah produk MPRS 1963 pada era Orde Lama.",
                "C": "Kurang tepat karena sakralisasi konstitusi dituangkan dalam produk hukum Ketetapan MPR formal.",
                "D": "Kurang tepat karena kementerian kehakiman tidak berwenang menerbitkan maklumat pembekuan konstitusi.",
                "E": "Kurang tepat karena sakralisasi UUD tertuang dalam aturan yuridis TAP MPR, bukan Keppres pelarangan diskusi."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 32,
        "halaman_sumber": 59,
        "kata_kunci_modul": ["dicabut dengan TAP MPR No. VII/MPR/I998 tanggal 13 Nopember 1998 karena tidak sejalan dengan pasaI 37 UUD 1945"],
        "topik_materi": "Pencabutan Ketetapan Larangan Perubahan UUD",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Alasan konstitusional dicabutnya Pasal 104 TAP MPR No. I/MPR/1983 oleh TAP MPR No. VII/MPR/1998 pada era reformasi adalah...",
        "pilihan": {
            "A": "Ketiadaan anggaran kas negara untuk menyelenggarakan sidang tahunan majelis",
            "B": "Ketentuan tersebut dicabut karena tidak sejalan dengan Pasal 37 UUD 1945",
            "C": "Instruksi dari dewan moneter internasional yang mewajibkan amandemen aturan",
            "D": "Adanya permohonan tertulis dari majelis perwakilan kerajaan negara tetangga",
            "E": "Ketidaksepakatan pimpinan kementerian terhadap besaran tarif pajak nasional"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Ketentuan pasal 104 TAP MPR No. I/MPR/1983 **telah dicabut dengan TAP MPR No. VII/MPR/1998 tanggal 13 Nopember 1998 karena tidak sejalan dengan pasal 37 UUD 1945** yang mengatur tata cara perubahan UUD.",
            "alasan_kunci": "Opsi B tepat karena Pasal 37 UUD 1945 justru memberikan hak dan mengatur prosedur formal bagi MPR untuk mengubah undang-undang dasar. Menutup pintu perubahan secara mutlak bertentangan dengan desain asli UUD 1945.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pencabutan didasarkan pada pertimbangan hukum konstitusional, bukan keterbatasan anggaran.",
                "C": "Kurang tepat karena pencabutan TAP MPR merupakan agenda kedaulatan internal gerakan reformasi.",
                "D": "Kurang tepat karena urusan konstitusi merupakan kedaulatan mutlak Republik Indonesia.",
                "E": "Kurang tepat karena regulasi perpajakan tidak berkaitan dengan pasal perubahan konstitusi."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 33,
        "halaman_sumber": 59,
        "kata_kunci_modul": ["TAP MPR No. IV/MPR/1983 tentang referendum"],
        "topik_materi": "Ketetapan MPR Tentang Referendum",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Instrumen hukum Orde Baru yang mewajibkan persetujuan rakyat terlebih dahulu sebelum MPR dapat mengubah pasal UUD 1945 adalah...",
        "pilihan": {
            "A": "Undang-undang pembatasan partai politik dan penyederhanaan organisasi",
            "B": "TAP MPR No. IV/MPR/1983 tentang referendum yang mempersyaratkan jajak pendapat",
            "C": "Surat keputusan kementerian penerangan mengenai sensor materi publikasi",
            "D": "Piagam kesepakatan dewan pertahanan wilayah mengenai status bahaya darurat",
            "E": "Ketetapan majelis permusyawaratan darurat tentang susunan perwakilan adat"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Dikeluarkannya TAP MPR No. IV/MPR/1983 tentang referendum**, di mana dinyatakan bahwa MPR berkehendak mempertahankan UUD 1945, dan apabila MPR hendak mengubah UUD 1945 harus melalui referendum.",
            "alasan_kunci": "Opsi B tepat karena Ketetapan Referendum (yang dijabarkan dalam UU No. 5/1985) menetapkan syarat kuorum yang hampir mustahil dicapai (90% pemilih terdaftar harus hadir dan 90% harus setuju), sehingga secara efektif membekukan amandemen konstitusi.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena UU penyederhanaan partai mengatur fusi parpol (PPP, PDI, Golkar), bukan referendum.",
                "C": "Kurang tepat karena SK menteri penerangan mengurusi SIUPP pers, bukan aturan referendum UUD.",
                "D": "Kurang tepat karena kesepakatan dewan pertahanan bukan instrumen konstitusional perubahan pasal.",
                "E": "Kurang tepat karena aturan referendum ditetapkan oleh MPR resmi melalui TAP MPR No. IV/1983."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 34,
        "halaman_sumber": 59,
        "kata_kunci_modul": ["dicabut dengan TAP MPR No. VIII/MPR/1998 tanggal 13 Nopember 1998"],
        "topik_materi": "Pencabutan Ketetapan Referendum Era Reformasi",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Dalam Sidang Istimewa MPR tahun 1998, ketentuan mengenai referendum untuk perubahan UUD 1945 secara resmi diakhiri melalui...",
        "pilihan": {
            "A": "Penerbitan dekrit darurat pembubaran dewan perwakilan rakyat tingkat pusat",
            "B": "Pencabutan dengan TAP MPR No. VIII/MPR/1998 karena tidak sejalan dengan Pasal 37 UUD",
            "C": "Surat edaran mahkamah agung mengenai penangguhan sengketa pemilihan umum",
            "D": "Perjanjian persekutuan dagang bilateral kawasan bersama negara sahabat",
            "E": "Keputusan menteri dalam negeri mengenai tata cara pemungutan suara daerah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "TAP MPR No. IV/MPR/1983 tentang referendum **telah dicabut dengan TAP MPR No. VIII/MPR/1998 tanggal 13 Nopember 1998 karena tidak sejalan dengan pasal 37 UUD 1945** yang mengatur tentang perubahan UUD 1945.",
            "alasan_kunci": "Opsi B tepat karena pencabutan TAP Referendum membuka jalan lebar bagi terselenggaranya rangkaian Amandemen UUD 1945 (1999–2002) sesuai mekanisme konstitusional murni yang digariskan Pasal 37 UUD 1945.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Sidang Istimewa 1998 tidak membubarkan DPR melainkan menetapkan TAP reformasi.",
                "C": "Kurang tepat karena MA tidak berwenang mencabut produk hukum Ketetapan MPR.",
                "D": "Kurang tepat karena perjanjian dagang kawasan tidak berkaitan dengan hukum tata negara referendum.",
                "E": "Kurang tepat karena pencabutan dilakukan oleh forum tertinggi MPR, bukan keputusan menteri."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 35,
        "halaman_sumber": 59,
        "kata_kunci_modul": ["Pada tanggal 21 Mei 1998, Pemerintahan Orde Baru berakhir"],
        "topik_materi": "Berakhirnya Masa Pemerintahan Orde Baru",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Momentum bersejarah yang menandai berakhirnya masa pemerintahan Orde Baru di Indonesia terjadi pada...",
        "pilihan": {
            "A": "Tanggal 11 Maret 1966 saat surat perintah pengamanan negara diterbitkan",
            "B": "Pada tanggal 21 Mei 1998 saat kepemimpinan Orde Baru resmi berakhir",
            "C": "Tanggal 19 Oktober 1999 saat pengesahan naskah amandemen pertama konstitusi",
            "D": "Tanggal 17 Agustus 1950 saat pembubaran negara serikat hasil konferensi",
            "E": "Tanggal 5 Juli 1959 saat pembacaan dekrit pemberlakuan kembali konstitusi"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Pada tanggal 21 Mei 1998, Pemerintahan Orde Baru berakhir** dengan pengunduran diri Presiden Soeharto setelah gelombang demonstrasi mahasiswa dan krisis moneter.",
            "alasan_kunci": "Opsi B tepat karena pada 21 Mei 1998 Presiden Soeharto menyatakan berhenti dari jabatannya di Istana Merdeka dan Wakil Presiden B.J. Habibie disumpah menjadi Presiden ketiga Republik Indonesia, mengawali era Reformasi.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena 11 Maret 1966 adalah awal lahirnya era Orde Baru.",
                "C": "Kurang tepat karena 19 Oktober 1999 adalah tanggal penetapan Amandemen Pertama UUD 1945.",
                "D": "Kurang tepat karena 17 Agustus 1950 adalah tanggal berdirinya kembali NKRI pasca-RIS.",
                "E": "Kurang tepat karena 5 Juli 1959 adalah tanggal Dekrit Presiden kembali ke UUD 1945.",
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 36,
        "halaman_sumber": 59,
        "kata_kunci_modul": ["Kurun waktu 21 Mei 1998 hingga 22 Oktober 1999 yang dikenal dengan masa Pasca Orde Baru"],
        "topik_materi": "Masa Pasca-Orde Baru (Transisi Reformasi)",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Rentang kurun waktu antara 21 Mei 1998 hingga 22 Oktober 1999 dalam lintasan ketatanegaraan Indonesia diklasifikasikan sebagai...",
        "pilihan": {
            "A": "Periode berlakunya undang-undang dasar sementara republik indonesia serikat",
            "B": "Masa Pasca Orde Baru yang menjadi jembatan awal era reformasi konstitusi",
            "C": "Kurun waktu berlakunya sistem demokrasi terpimpin di bawah kendali presiden",
            "D": "Masa persemakmuran bersama perwakilan komisi perdamaian internasional",
            "E": "Fase unifikasi pembentukan karesidenan wilayah kepulauan secara serentak"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Kurun waktu 21 Mei 1998 hingga 22 Oktober 1999 yang dikenal dengan masa Pasca Orde Baru** merupakan periode transisi pemerintahan Presiden B.J. Habibie yang meletakkan fondasi kebebasan pers, UU Politik baru, dan pelaksanaan Pemilu demokratis 1999.",
            "alasan_kunci": "Opsi B tepat karena dalam kurun waktu 17 bulan ini terjadi transformasi hukum fundamental, pembebasan tahanan politik, pencabutan restriksi Orde Baru, serta penyelenggaraan Pemilu demokratis 7 Juni 1999.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Konstitusi RIS berlaku pada tahun 1949–1950, bukan 1998–1999.",
                "C": "Kurang tepat karena Demokrasi Terpimpin berlangsung pada kurun 1959–1966.",
                "D": "Kurang tepat karena Indonesia berdaulat penuh dan bukan negara persemakmuran.",
                "E": "Kurang tepat karena sistem karesidenan tidak diberlakukan pada era pasca-Orde Baru."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 37,
        "halaman_sumber": 59,
        "kata_kunci_modul": ["Mengadakan perubahan pertama UUD 1945 yang ditetapkan tanggal 19 Oktober 1999"],
        "topik_materi": "Penetapan Perubahan Pertama UUD 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Peristiwa bersejarah dimulainya amandemen konstitusi pasca-reformasi ditandai dengan...",
        "pilihan": {
            "A": "Penetapan piagam persetujuan pembubaran negara serikat pada sidang paripurna",
            "B": "Perubahan pertama UUD 1945 yang ditetapkan tanggal 19 Oktober 1999 oleh MPR",
            "C": "Penerbitan dekrit pembatalan seluruh produk perundang-undangan era kemerdekaan",
            "D": "Pengesahan traktat bilateral batas wilayah perairan dengan komisi sekutu",
            "E": "Pembentukan dewan perancang konstitusi darurat tingkat keresidenan wilayah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sidang Umum MPR hasil pemilu 1999 **mengadakan perubahan pertama UUD 1945 yang ditetapkan tanggal 19 Oktober 1999**, yang menjadi titik tolak reformasi konstitusi nasional.",
            "alasan_kunci": "Opsi B tepat karena Perubahan Pertama UUD 1945 disahkan pada Sidang Umum MPR 1999 (meliputi 9 pasal: Pasal 5, 7, 9, 13, 14, 15, 17, 20, dan 21) yang berfokus membatasi kekuasaan eksekutif dan mengembalikan fungsi legislasi ke parlemen.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pembubaran negara serikat terjadi pada Agustus 1950.",
                "C": "Kurang tepat karena reformasi tidak membatalkan seluruh UU melainkan mengamandemen pasal konstitusi.",
                "D": "Kurang tepat karena traktat perairan bukan agenda sidang amandemen UUD MPR 1999.",
                "E": "Kurang tepat karena amandemen dilakukan oleh MPR hasil Pemilu 1999, bukan dewan karesidenan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 38,
        "halaman_sumber": 59,
        "kata_kunci_modul": ["dibatasinya masa jabatan Presiden paling banyak 2 masa jabatan"],
        "topik_materi": "Pembatasan Masa Jabatan Eksekutif Amandemen Pertama",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Salah satu substansi mendasar dalam Perubahan Pertama UUD 1945 terhadap Pasal 7 adalah aturan bahwa...",
        "pilihan": {
            "A": "Presiden dapat memegang kekuasaan seumur hidup asalkan didukung mayoritas fraksi",
            "B": "Masa jabatan Presiden dibatasi secara tegas paling banyak dua masa jabatan",
            "C": "Masa bakti eksekutif dapat diperpanjang tanpa batas saat terjadi gejolak sosial",
            "D": "Presiden wajib meletakkan jabatan setiap tiga tahun guna pemilihan kabinet",
            "E": "Pemilihan presiden ditiadakan dan diganti sistem kepemimpinan presidium bergilir"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam amandemen pertama, perubahan yang sangat penting adalah **dibatasinya masa jabatan Presiden paling banyak 2 masa jabatan** (maksimal 10 tahun).",
            "alasan_kunci": "Opsi B tepat karena sebelum amandemen Pasal 7 menimbulkan multitafsir ('dapat dipilih kembali' tanpa batas). Amandemen Pertama menambahkan frasa 'hanya untuk satu kali masa jabatan', sehingga mencegah langgengnya kekuasaan otoriter berkepanjangan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena masa jabatan seumur hidup telah dilarang mutlak oleh konstitusi.",
                "C": "Kurang tepat karena perpanjangan tanpa batas melanggar prinsip kepastian konstitusional Pasal 7.",
                "D": "Kurang tepat karena masa jabatan reguler adalah 5 tahun per periode, bukan 3 tahun.",
                "E": "Kurang tepat karena sistem presidensial tunggal tetap dipertahankan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 39,
        "halaman_sumber": 59,
        "kata_kunci_modul": ["pemegang kekuasaan pembentuk UU adalah DPR, bukan lagi Presiden"],
        "topik_materi": "Pergeseran Kekuasaan Legislasi ke DPR",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pergeseran kekuasaan membentuk undang-undang dalam Perubahan Pertama UUD 1945 pada Pasal 20 ayat (1) menegaskan bahwa...",
        "pilihan": {
            "A": "Kekuasaan legislasi sepenuhnya diserahkan kepada dewan pertimbangan kepresidenan",
            "B": "Pemegang kekuasaan pembentuk UU adalah DPR, bukan lagi Presiden",
            "C": "Mahkamah agung memegang hak monopoli menyusun naskah rancangan peraturan",
            "D": "Pemerintah daerah berhak mengesahkan undang-undang nasional secara mandiri",
            "E": "Kementerian koordinator memiliki hak veto membatalkan persetujuan dewan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam amandemen pertama dinyatakan bahwa **pemegang kekuasaan pembentuk UU adalah DPR, bukan lagi Presiden**, memulihkan hakikat parlemen sebagai cabang legislatif.",
            "alasan_kunci": "Opsi B tepat karena naskah asli Pasal 5 ayat (1) menempatkan Presiden sebagai pemegang kekuasaan membentuk UU dengan persetujuan DPR (*executive heavy*). Amandemen Pertama memindahkannya ke Pasal 20 ayat (1) sehingga DPR menjadi pemegang kekuasaan legislasi utama.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena dewan pertimbangan hanya memberi nasihat, bukan membentuk UU.",
                "C": "Kurang tepat karena Mahkamah Agung mengadili perkara peradilan, bukan membentuk undang-undang.",
                "D": "Kurang tepat karena pembentukan undang-undang merupakan ranah DPR dan Presiden di tingkat pusat.",
                "E": "Kurang tepat karena menteri tidak memiliki hak veto terhadap undang-undang."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 40,
        "halaman_sumber": 59,
        "kata_kunci_modul": ["perubahan kedua UUD 1945 yang ditetapkan pada tanggal 18 Agustus 2000"],
        "topik_materi": "Penetapan Perubahan Kedua UUD 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Penegasan mengenai tiga fungsi pokok DPR (legislasi, anggaran, dan pengawasan) serta hak-hak dewan dirumuskan dalam...",
        "pilihan": {
            "A": "Ketetapan majelis permusyawaratan darurat tentang susunan kabinet transisi",
            "B": "Perubahan kedua UUD 1945 yang ditetapkan pada tanggal 18 Agustus 2000",
            "C": "Maklumat bersama menteri koordinator mengenai penataan birokrasi daerah",
            "D": "Piagam kesepakatan dewan pertahanan nasional tentang keadaan bahaya perang",
            "E": "Surat edaran mahkamah konstitusi mengenai tata cara penyelesaian sengketa"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam Sidang Tahunan tahun 2000 diadakan **perubahan kedua UUD 1945 yang ditetapkan pada tanggal 18 Agustus 2000**, yang menegaskan fungsi DPR (legislasi, anggaran, pengawasan) dan hak dewan (interpelasi, angket, menyatakan pendapat).",
            "alasan_kunci": "Opsi B tepat karena Perubahan Kedua UUD 1945 menyempurnakan kelembagaan DPR (Pasal 20A), Pemerintah Daerah (Pasal 18, 18A, 18B), Wilayah Negara (Pasal 25A), Warga Negara (Pasal 26–27), Hak Asasi Manusia (Pasal 28A–28J), serta Pertahanan Keamanan (Pasal 30).",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penegasan fungsi DPR tertuang dalam naskah amandemen UUD, bukan ketetapan darurat.",
                "C": "Kurang tepat karena maklumat menteri tidak berkedudukan sebagai hukum dasar konstitusi.",
                "D": "Kurang tepat karena piagam dewan pertahanan bukan instrumen perubahan undang-undang dasar.",
                "E": "Kurang tepat karena Mahkamah Konstitusi belum terbentuk pada tahun 2000 (baru dibentuk tahun 2003)."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    }
]

output_path = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch\draft_p06_sub2_hlm58_59.json"
with open(output_path, "w", encoding="utf-8") as f:
    json.dump(questions, f, indent=2, ensure_ascii=False)

print(f"Sub-Agent 2 (Paket 06) finished! Total questions: {len(questions)}")

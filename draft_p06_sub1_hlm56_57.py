import json

# Sub-Agent 1 (Paket 06): Halaman 56 dan 57 (Nomor 1 s/d 20)
# Halaman 56: Nomor 1 - 10
# Halaman 57: Nomor 11 - 20

questions = [
    # --- HALAMAN 56 (Q01 - Q10) ---
    {
        "nomor": 1,
        "halaman_sumber": 56,
        "kata_kunci_modul": ["dimungkinkan dalam pasal 43 dan pasal 49 Konstitusi RlS"],
        "topik_materi": "Dasar Yuridis Pembubaran Negara Bagian RIS",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Penyatuan kembali negara-negara bagian federal ke dalam pangkuan Republik Indonesia secara yuridis didasarkan pada ketentuan bahwa...",
        "pilihan": {
            "A": "Peleburan wilayah serikat diputuskan sekehendak perwakilan militer sekutu",
            "B": "Pembubaran dan penggabungan negara-negara bagian itu dimungkinkan dalam pasal 43 dan pasal 49 Konstitusi RIS",
            "C": "Konstitusi federal melarang pembubaran entitas wilayah otonom tanpa izin ratu belanda",
            "D": "Negara bagian diwajibkan mempertahankan batas teritorialnya secara permanen",
            "E": "Pemerintah federal memegang hak monopoli mutlak membekukan dewan perwakilan daerah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Pembubaran dan penggabungan negara-negara bagian itu dimungkinkan dalam pasal 43 dan pasal 49 Konstitusi RIS**, yang menjadi jalan konstitusional kembalinya bentuk negara kesatuan.",
            "alasan_kunci": "Opsi B tepat karena Konstitusi RIS sendiri memuat klausul hukum yang memperbolehkan suatu negara bagian untuk bergabung dengan negara bagian lain atau membubarkan diri demi aspirasi rakyatnya, sehingga proses unifikasi berjalan sah secara hukum ketatanegaraan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena peleburan wilayah didasarkan pada kehendak rakyat dan pasal konstitusi, bukan komando sekutu.",
                "C": "Kurang tepat karena Konstitusi RIS justru membuka ruang pembubaran dan penggabungan wilayah.",
                "D": "Kurang tepat karena negara bagian memiliki hak konstitusional untuk menggabungkan wilayahnya.",
                "E": "Kurang tepat karena pembubaran negara bagian berakar dari keputusan rakyat dan parlemen negara bagian terkait."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 2,
        "halaman_sumber": 56,
        "kata_kunci_modul": ["negara bagian Indonesia Timur dan Sumatera Timur"],
        "topik_materi": "Negara Bagian Terakhir Menjelang Pembubaran RIS",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pada bulan April 1950, dua entitas federal yang tercatat sebagai wilayah bagian terakhir sebelum tercapainya kesepakatan negara kesatuan adalah...",
        "pilihan": {
            "A": "Negara Pasundan bersama dengan Daerah Istimewa Kalimantan Barat",
            "B": "Negara bagian Indonesia Timur dan Sumatera Timur",
            "C": "Negara Madura berdampingan dengan wilayah Negara Jawa Timur",
            "D": "Negara Sumatera Selatan bersama entitas Daerah Bangka Belitung",
            "E": "Daerah Banjar bersekutu dengan federasi Dayak Besar di Kalimantan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pada bulan April 1950 sebagian besar wilayah telah bergabung ke RI Yogyakarta, menyisakan **negara bagian Indonesia Timur dan Sumatera Timur** yang belum bergabung.",
            "alasan_kunci": "Opsi B tepat karena Negara Indonesia Timur (NIT) dan Negara Sumatera Timur (NST) merupakan dua negara bagian bentukan Belanda yang bertahan paling akhir sebelum akhirnya pimpinannya bersepakat untuk melebur ke dalam NKRI.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Negara Pasundan telah lebih awal membubarkan diri pada awal tahun 1950.",
                "C": "Kurang tepat karena Negara Madura dan Jawa Timur telah bergabung ke RI mendahului NIT dan NST.",
                "D": "Kurang tepat karena Sumatera Selatan dan Bangka Belitung telah melebur ke RI sebelum April 1950.",
                "E": "Kurang tepat karena satuan kenegaraan di Kalimantan telah menyatakan bergabung ke RI pada permulaan 1950."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 3,
        "halaman_sumber": 56,
        "kata_kunci_modul": ["dalam waktu sesingkat-singkatnya bersama-sama mendirikan negara kesatuan"],
        "topik_materi": "Kesepakatan Pendirian Kembali Negara Kesatuan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Inti kesepakatan politik antara pimpinan Republik Indonesia di Yogyakarta dengan pimpinan negara federal RIS pada Mei 1950 adalah tekad untuk...",
        "pilihan": {
            "A": "Mempertahankan bentuk uni persemakmuran di bawah pengawasan kerajaan asing",
            "B": "Dalam waktu sesingkat-singkatnya bersama-sama mendirikan negara kesatuan",
            "C": "Membagi wilayah kepulauan nusantara menjadi empat federasi ekonomi mandiri",
            "D": "Menyelenggarakan pemungutan suara berulang di setiap distrik persekutuan dagang",
            "E": "Membentuk kabinet koalisi permanen yang dipimpin oleh komisioner tinggi sekutu"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tercapai kesepakatan antara negara RI (Jogjakarta) dan negara RIS (mewakili NIT dan NST) untuk **dalam waktu sesingkat-singkatnya bersama-sama mendirikan negara kesatuan**.",
            "alasan_kunci": "Opsi B tepat karena kedua pihak menyadari bahwa format federal memecah persatuan bangsa, sehingga disepakati komitmen bersama untuk membubarkan RIS dan memproklamasikan kembali NKRI.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Uni Indonesia-Belanda justru hendak diakhiri demi kedaulatan merdeka penuh.",
                "C": "Kurang tepat karena kesepakatan ditujukan untuk membentuk negara kesatuan tunggal, bukan empat federasi.",
                "D": "Kurang tepat karena integrasi dicapai melalui konsensus musyawarah perwakilan parlemen yang sah.",
                "E": "Kurang tepat karena pemerintahan baru dipimpin mandiri oleh para pemimpin bangsa Indonesia."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 4,
        "halaman_sumber": 56,
        "kata_kunci_modul": ["piagam persetujuan tanggal 19 Mei 1950"],
        "topik_materi": "Piagam Persetujuan 19 Mei 1950",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Dokumen yuridis formal yang memuat kesepakatan resmi pembentukan kembali Negara Kesatuan Republik Indonesia antara RIS dan RI adalah...",
        "pilihan": {
            "A": "Nota kesepahaman gencatan senjata di atas kapal perang renville",
            "B": "Piagam persetujuan tanggal 19 Mei 1950 antara pemerintah RIS dan negara RI",
            "C": "Dekrit darurat militer yang disahkan di markas komando pertempuran",
            "D": "Perjanjian persekutuan ekonomi bilateral konferensi meja bundar den haag",
            "E": "Maklumat bersama pimpinan fraksi parlemen serikat di jakarta"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Persetujuan untuk mendirikan negara kesatuan secara resmi dimuat dalam suatu **piagam persetujuan tanggal 19 Mei 1950** antara pemerintah RIS dan pemerintah RI Yogyakarta.",
            "alasan_kunci": "Opsi B tepat karena Piagam Persetujuan 19 Mei 1950 (dikenal sebagai Piagam Hatta-Sukawati) merupakan tonggak sejarah yuridis yang menjadi landasan penyusunan rancangan konstitusi baru (UUDS 1950) guna memulihkan bentuk NKRI.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Perjanjian Renville ditandatangani pada Januari 1948 pada era revolusi fisik.",
                "C": "Kurang tepat karena Piagam 19 Mei 1950 adalah dokumen politik-hukum sipil, bukan dekrit militer.",
                "D": "Kurang tepat karena KMB di Den Haag menghasilkan pembentukan RIS, bukan pembubarannya.",
                "E": "Kurang tepat karena naskah kesepakatan ditandatangani oleh pimpinan eksekutif tertinggi kedua entitas."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 5,
        "halaman_sumber": 56,
        "kata_kunci_modul": ["Pada tanggal 17 Agustus 1950 Negara Indonesia yang berbentuk negara federal RIS menjadi negara kesatuan"],
        "topik_materi": "Proklamasi Berdirinya Kembali NKRI 17 Agustus 1950",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Peristiwa bersejarah kembalinya bentuk negara kesatuan di Indonesia ditandai pada momentum bahwa...",
        "pilihan": {
            "A": "Pada 27 Desember 1949 seluruh kedaulatan diserahkan kepada persemakmuran",
            "B": "Pada tanggal 17 Agustus 1950 Negara Indonesia yang berbentuk negara federal RIS menjadi negara kesatuan",
            "C": "Pada 5 Juli 1959 sistem kabinet parlementer dipulihkan kembali oleh parlemen",
            "D": "Pada 18 Agustus 1945 sistem pemerintahan serikat resmi diberlakukan pertama kali",
            "E": "Pada 10 November 1945 komisi internasional mengesahkan batas wilayah kepulauan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Pada tanggal 17 Agustus 1950 Negara Indonesia yang berbentuk negara federal RIS menjadi negara kesatuan** bertepatan dengan peringatan lima tahun Proklamasi Kemerdekaan.",
            "alasan_kunci": "Opsi B tepat karena Presiden Soekarno mengumumkan secara resmi pemulihan Negara Kesatuan Republik Indonesia pada 17 Agustus 1950, mengakhiri eksistensi RIS yang berumur kurang lebih 8 bulan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena 27 Desember 1949 adalah awal berdirinya bentuk federal RIS.",
                "C": "Kurang tepat karena Dekrit 5 Juli 1959 mengakhiri sistem parlementer UUDS 1950 dan kembali ke UUD 1945.",
                "D": "Kurang tepat karena 18 Agustus 1945 adalah penetapan UUD 1945 negara kesatuan, bukan serikat.",
                "E": "Kurang tepat karena 10 November 1945 adalah peristiwa Pertempuran Surabaya (Hari Pahlawan)."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 6,
        "halaman_sumber": 56,
        "kata_kunci_modul": ["membuat rancangan perubahan Konstitusi RIS menjadi Undang-Undang Dasar Sementara (UUDS) 1950"],
        "topik_materi": "Penyusunan RUU Perubahan Konstitusi RIS Menjadi UUDS 1950",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Langkah teknis perundang-undangan yang ditempuh bersama pasca-penandatanganan Piagam 19 Mei 1950 adalah...",
        "pilihan": {
            "A": "Menerbitkan mata uang bersama antara kerajaan belanda dengan bank indonesia",
            "B": "Membuat rancangan perubahan Konstitusi RIS menjadi Undang-Undang Dasar Sementara (UUDS) 1950",
            "C": "Menetapkan naskah piagam jakarta menjadi hukum dasar tertinggi persemakmuran",
            "D": "Mengadili para perwira militer yang terlibat dalam agresi senjata di daerah",
            "E": "Menyusun batas demarkasi pertahanan baru di bawah koordinasi staf sekutu"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Setelah persetujuan dimuat dalam piagam 19 Mei 1950, proses selanjutnya adalah **membuat rancangan perubahan Konstitusi RIS menjadi Undang-Undang Dasar Sementara (UUDS) 1950 Republik Indonesia oleh pihak RIS dan negara RI**.",
            "alasan_kunci": "Opsi B tepat karena secara yuridis peralihan dari negara federal ke negara kesatuan dilakukan melalui mekanisme amendemen terhadap Konstitusi RIS yang diubah menjadi teks UUDS 1950.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena unifikasi moneter dilakukan mandiri oleh Bank Indonesia pasca-nasionalisasi De Javasche Bank.",
                "C": "Kurang tepat karena Piagam Jakarta adalah dokumen perumusan dasar negara tahun 1945.",
                "D": "Kurang tepat karena fokus utama panitia bersama adalah penyusunan konstitusi unifikasi negara kesatuan.",
                "E": "Kurang tepat karena garis demarkasi Van Mook dihapus seiring runtuhnya negara bagian bentukan kolonial."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 7,
        "halaman_sumber": 56,
        "kata_kunci_modul": ["Di depan rapat gabungan senat dan DPR RIS"],
        "topik_materi": "Persetujuan Rapat Gabungan Senat dan DPR RIS",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Persetujuan formal terhadap naskah rancangan UUDS 1950 pada tanggal 15 Agustus 1950 dideklarasikan secara resmi...",
        "pilihan": {
            "A": "Di hadapan perwakilan dewan pertimbangan agung dan pimpinan kejaksaan",
            "B": "Di depan rapat gabungan senat dan DPR RIS oleh Presiden RIS",
            "C": "Dalam konferensi pers tertutup di kantor perwakilan komisi perdamaian",
            "D": "Pada rapat kerja komisi militer pertahanan tingkat wilayah kepulauan",
            "E": "Melalui siaran radio luar negeri yang dipancarkan dari benua eropa"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Di depan rapat gabungan senat dan DPR RIS**, pada tanggal 15 Agustus Presiden menyatakan bahwa rancangan perubahan telah disetujui oleh pihak RIS dan negara RI (Jogjakarta).",
            "alasan_kunci": "Opsi B tepat karena rapat gabungan Senat (mewakili negara-negara bagian) dan DPR RIS (mewakili rakyat) merupakan forum perwakilan parlemen tertinggi di tingkat federal yang berwenang menyetujui perubahan konstitusi.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena DPA bukan lembaga parlemen pada masa berlakunya Konstitusi RIS.",
                "C": "Kurang tepat karena deklarasi dilakukan di sidang resmi parlemen nasional, bukan konferensi pers tertutup.",
                "D": "Kurang tepat karena perubahan konstitusi merupakan kewenangan lembaga legislatif sipil.",
                "E": "Kurang tepat karena pengumuman resmi dibacakan langsung di ibu kota negara Jakarta."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 8,
        "halaman_sumber": 56,
        "kata_kunci_modul": ["ditandatangani bersama Perdana Menteri dan Menteri Kehakiman RIS"],
        "topik_materi": "Pengesahan Naskah Resmi UUDS 1950",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Naskah Undang-Undang Dasar Sementara 1950 yang telah disetujui bersama oleh kedua belah pihak secara administratif disahkan melalui prosedur...",
        "pilihan": {
            "A": "Diratifikasi oleh perwakilan ratu belanda di hadapan komisi arbitrase",
            "B": "Ditandatangani bersama Perdana Menteri dan Menteri Kehakiman RIS",
            "C": "Diberikan stempel persetujuan oleh komandan jenderal pasukan sekutu",
            "D": "Disahkan secara mandiri oleh pimpinan badan pekerja komite nasional",
            "E": "Ditetapkan melalui surat edaran menteri penerangan tingkat daerah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Naskah UUDS yang telah disetujui oleh pihak RIS dan negara RI (Jogjakarta) **ditandatangani bersama Perdana Menteri dan Menteri Kehakiman RIS** untuk kemudian diundangkan secara sah.",
            "alasan_kunci": "Opsi B tepat karena penandatanganan oleh Perdana Menteri (Mohammad Hatta) dan Menteri Kehakiman RIS menyempurnakan keabsahan yuridis naskah UUDS 1950 menjadi hukum positif yang mengikat seluruh tumpah darah Indonesia.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena UUDS 1950 adalah produk hukum kedaulatan Indonesia tanpa ratifikasi pihak asing.",
                "C": "Kurang tepat karena pasukan sekutu telah ditarik dari Indonesia sebelum tahun 1950.",
                "D": "Kurang tepat karena pengesahan dilakukan oleh pejabat eksekutif tertinggi pemerintahan RIS dan RI.",
                "E": "Kurang tepat karena konstitusi dasar tidak diundangkan melalui surat edaran menteri penerangan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 9,
        "halaman_sumber": 56,
        "kata_kunci_modul": ["belum bergabung dengan negara bagian Republik Indonesia (Jogjakarta)"],
        "topik_materi": "Status Entitas Sebelum Integrasi April 1950",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Kondisi geopolitik di tanah air menjelang terwujudnya kesepakatan Mei 1950 memperlihatkan fakta bahwa sebagian besar daerah...",
        "pilihan": {
            "A": "Telah menuntut pembentukan pemerintahan monarki di bawah kekuasaan kesultanan",
            "B": "Telah bergabung ke RI Yogyakarta dan menyisakan sedikit bagian yang belum bergabung",
            "C": "Menolak pembubaran pos-pos pangkalan militer pertahanan kerajaan asing",
            "D": "Menyusun mata uang daerah guna memisahkan diri dari kas perbendaharaan",
            "E": "Mewajibkan penggunaan bahasa asing dalam administrasi persidangan dewan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pada bulan April 1950 mayoritas wilayah telah melebur, sehingga hanya tinggal beberapa bagian dari negara bagian Indonesia Timur dan Sumatera Timur yang **belum bergabung dengan negara bagian Republik Indonesia (Jogjakarta)**.",
            "alasan_kunci": "Opsi B tepat karena gelombang tuntutan rakyat (*people power*) di berbagai negara bagian menuntut kembali ke pangkuan RI Yogyakarta, membuktikan bahwa rakyat Indonesia menolak politik adu domba federal buatan penjajah.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena aspirasi rakyat menghendaki bentuk republik kesatuan, bukan monarki kesultanan.",
                "C": "Kurang tepat karena rakyat justru menuntut penarikan seluruh kekuatan militer kolonial KNIL.",
                "D": "Kurang tepat karena daerah-daerah mendukung unifikasi kas dan mata uang rupiah nasional.",
                "E": "Kurang tepat karena bahasa Indonesia telah dijunjung tinggi sebagai bahasa persatuan resmi."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 10,
        "halaman_sumber": 56,
        "kata_kunci_modul": ["Terbentuknya Undang-Undang Dasar Sementara (UUDS)"],
        "topik_materi": "Hakikat Terbentuknya UUDS 1950",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pemberian nama 'Undang-Undang Dasar Sementara' pada konstitusi yang disahkan tahun 1950 dilandasi oleh pertimbangan bahwa...",
        "pilihan": {
            "A": "Konstitusi tersebut hanya akan berlaku sepanjang masa darurat perang terbuka",
            "B": "Terbentuknya Undang-Undang Dasar Sementara (UUDS) dimaksudkan menunggu konstitusi definitif dari Konstituante",
            "C": "Naskah undang-undang dasar disusun sepihak tanpa melibatkan tokoh-tokoh pergerakan",
            "D": "Pemerintah pusat merencanakan pembentukan kembali negara bagian pada masa depan",
            "E": "Aturan hukum tersebut sewaktu-waktu dapat dibatalkan oleh komisi perserikatan bangsa"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Terbentuknya Undang-Undang Dasar Sementara (UUDS)** 1950 dirancang sebagai hukum dasar transisional sebelum terbentuknya undang-undang dasar tetap yang akan disusun oleh badan Konstituante hasil pemilu.",
            "alasan_kunci": "Opsi B tepat karena kata 'Sementara' menunjukkan bahwa UUDS 1950 bukan produk konstitusi final. Tugas menyusun konstitusi permanen diserahkan kepada lembaga Konstituante yang dipilih langsung oleh rakyat melalui Pemilu 1955.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena UUDS 1950 diberlakukan dalam situasi damai pasca-pengakuan kedaulatan.",
                "C": "Kurang tepat karena UUDS disusun bersama oleh wakil-wakil sah dari RIS dan RI Yogyakarta.",
                "D": "Kurang tepat karena komitmen bangsa telah final menuju negara kesatuan tanpa kembali ke bentuk serikat.",
                "E": "Kurang tepat karena kedaulatan penetapan konstitusi berada mutlak di tangan bangsa Indonesia."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },

    # --- HALAMAN 57 (Q11 - Q20) ---
    {
        "nomor": 11,
        "halaman_sumber": 57,
        "kata_kunci_modul": ["Kekuasaan pemerintah di tangan kabinet yang dipimpin oleh Perdana Menteri"],
        "topik_materi": "Struktur Eksekutif Menurut UUDS 1950",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Ciri pokok sistem pemerintahan parlementer yang dianut dalam UUDS 1950 ditegaskan melalui aturan bahwa...",
        "pilihan": {
            "A": "Presiden bertindak sebagai kepala eksekutif yang memegang kekuasaan mutlak",
            "B": "Kekuasaan pemerintah di tangan kabinet yang dipimpin oleh Perdana Menteri",
            "C": "Seluruh keputusan kementerian wajib disetujui terlebih dahulu oleh dewan militer",
            "D": "Jabatan perdana menteri dihapuskan dan dirangkap oleh ketua mahkamah agung",
            "E": "Para menteri bertanggung jawab secara mandiri kepada asosiasi pengusaha daerah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam UUDS 1950, Presiden berkedudukan sebagai kepala negara konstitusional, sedangkan **kekuasaan pemerintah di tangan kabinet yang dipimpin oleh Perdana Menteri**.",
            "alasan_kunci": "Opsi B tepat karena sistem parlementer memisahkan fungsi kepala negara (Presiden) dari fungsi kepala pemerintahan (Perdana Menteri). Kabinet dipimpin Perdana Menteri yang memegang mandat operasional pemerintahan sehari-hari.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Presiden dalam UUDS 1950 tidak memegang kekuasaan pemerintahan eksekutif langsung.",
                "C": "Kurang tepat karena dewan militer tidak memegang wewenang persetujuan regulasi kabinet sipil.",
                "D": "Kurang tepat karena jabatan perdana menteri eksis dan terpisah dari cabang kekuasaan kehakiman.",
                "E": "Kurang tepat karena menteri bertanggung jawab kepada parlemen (DPR), bukan asosiasi pengusaha."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 12,
        "halaman_sumber": 57,
        "kata_kunci_modul": ["Para menteri yang dipimpin oleh Perdana Menteri bertanggung jawab kepada DPR/ Parlemen"],
        "topik_materi": "Akuntabilitas Kabinet Kepada Parlemen UUDS 1950",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pertanggungjawaban politik eksekutif dalam sistem ketatanegaraan UUDS 1950 diatur dengan ketentuan bahwa...",
        "pilihan": {
            "A": "Kabinet bertanggung jawab penuh kepada pimpinan majelis permusyawaratan adat",
            "B": "Para menteri yang dipimpin oleh Perdana Menteri bertanggung jawab kepada DPR/ Parlemen",
            "C": "Pemerintah menyampaikan pertanggungjawaban tahunan kepada lembaga kejaksaan",
            "D": "Perdana menteri memegang hak mutlak membekukan keanggotaan komisi peradilan",
            "E": "Menteri keuangan memegang hak veto terhadap setiap keputusan ketua parlemen"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sesuai karakteristik sistem parlementer pada UUDS 1950: **Para menteri yang dipimpin oleh Perdana Menteri bertanggung jawab kepada DPR/ Parlemen**.",
            "alasan_kunci": "Opsi B tepat karena parlemen memegang supremasi politik. Jika parlemen menjatuhkan mosi tidak percaya (*vote of no confidence*), Perdana Menteri beserta kabinetnya wajib mengembalikan mandat kepada Presiden.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena majelis adat bukan lembaga perwakilan politik tingkat nasional.",
                "C": "Kurang tepat karena kejaksaan menjalankan fungsi penuntutan hukum, bukan pengawasan politik kabinet.",
                "D": "Kurang tepat karena perdana menteri tidak berwenang membekukan komisi peradilan.",
                "E": "Kurang tepat karena menteri keuangan tidak memiliki hak veto terhadap parlemen."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 13,
        "halaman_sumber": 57,
        "kata_kunci_modul": ["UUDS menganut Demokrasi Liberal yang mengutamakan kebebasan individu"],
        "topik_materi": "Ideologi Demokrasi Liberal Pada Era UUDS 1950",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Orientasi nilai politik dan sistem demokrasi yang diterapkan pada era berlakunya UUDS 1950 adalah...",
        "pilihan": {
            "A": "Demokrasi sentralistik yang melarang pendirian organisasi kemasyarakatan",
            "B": "UUDS menganut Demokrasi Liberal yang mengutamakan kebebasan individu",
            "C": "Sistem feodalistik yang menempatkan aristokrasi sebagai pemegang mandat kekuasaan",
            "D": "Paham totaliter yang membatasi hak pilih warga negara berdasarkan golongan kelas",
            "E": "Demokrasi komunal yang menolak sistem perwakilan parlemen secara menyeluruh"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**UUDS menganut Demokrasi Liberal yang mengutamakan kebebasan individu**, yang melahirkan kebebasan berpendapat dan sistem multipartai yang sangat cair.",
            "alasan_kunci": "Opsi B tepat karena konstitusi UUDS 1950 sangat dipengaruhi oleh piagam HAM internasional (UDHR) yang menjunjung tinggi hak-hak sipil-politik dan kebebasan perorangan dalam berpolitik.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena era 1950-an justru ditandai menjamurnya berbagai partai politik dan ormas bebas.",
                "C": "Kurang tepat karena sistem feodal bertentangan dengan prinsip kesetaraan warga dalam demokrasi liberal.",
                "D": "Kurang tepat karena pemilu 1955 diselenggarakan secara demokratis tanpa pembatasan kasta golongan.",
                "E": "Kurang tepat karena sistem perwakilan parlemen justru menjadi pusat aktivitas kenegaraan saat itu."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 14,
        "halaman_sumber": 57,
        "kata_kunci_modul": ["terjadi pergantian kabinet sebanyak 7 (tujuh) kali karena dijatuhkan DPR"],
        "topik_materi": "Instabilitas Politik Kabinet Parlementer 1950-1959",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Bukti empiris bahwa sistem kabinet parlementer era UUDS 1950 tidak menjamin stabilitas pemerintahan adalah fakta bahwa...",
        "pilihan": {
            "A": "Presiden membekukan seluruh partai politik oposisi di dewan perwakilan",
            "B": "Telah terjadi pergantian kabinet sebanyak 7 (tujuh) kali karena dijatuhkan DPR",
            "C": "Pemilihan umum legislatif gagal diselenggarakan di seluruh wilayah kepulauan",
            "D": "Mahkamah agung menolak melantik pejabat tinggi eselon kementerian negara",
            "E": "Menteri keuangan memotong anggaran daerah secara serentak tanpa koordinasi"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam kurun waktu 17 Agustus 1950 hingga 5 Juli 1959, **telah terjadi pergantian kabinet sebanyak 7 (tujuh) kali karena dijatuhkan DPR**, yang menunjukkan sistem parlementer tidak menjamin kestabilan pemerintah.",
            "alasan_kunci": "Opsi B tepat karena rata-rata usia kabinet hanya bertahan sekitar satu tahun (seperti Kabinet Natsir, Sukiman, Wilopo, Ali I, Burhanuddin Harahap, Ali II, dan Djuanda). Dinamika jatuh-bangun kabinet ini mengganggu kelangsungan program pembangunan nasional.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pembubaran parpol baru terjadi pada masa Demokrasi Terpimpin pasca-1959.",
                "C": "Kurang tepat karena Pemilu 1955 terselenggara dengan sangat sukses, demokratis, dan aman.",
                "D": "Kurang tepat karena peradilan tidak mencampuri urusan suksesi birokrasi kabinet politik.",
                "E": "Kurang tepat karena problem utama adalah pertarungan politik fraksi-fraksi di parlemen dewan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 15,
        "halaman_sumber": 57,
        "kata_kunci_modul": ["Dasar hukum berlakunya UUD 1945 dalam kurun waktu kedua adalah Dekrit Presiden tanggal 5 Juli 1959"],
        "topik_materi": "Dekrit Presiden 5 Juli 1959 Kurun Waktu Kedua",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pemberlakuan kembali UUD 1945 sebagai hukum dasar negara Republik Indonesia pada kurun waktu kedua bertumpu pada dasar hukum...",
        "pilihan": {
            "A": "Resolusi majelis umum dewan keamanan perserikatan bangsa-bangsa di new york",
            "B": "Dasar hukum berlakunya UUD 1945 dalam kurun waktu kedua adalah Dekrit Presiden tanggal 5 Juli 1959",
            "C": "Ketetapan pleno istimewa komite nasional indonesia pusat di yogyakarta",
            "D": "Surat keputusan kementerian kehakiman mengenai ratifikasi piagam perjanjian",
            "E": "Hasil kesepakatan mufakat tertutup pimpinan partai pemenang pemilihan umum"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Dasar hukum berlakunya UUD 1945 dalam kurun waktu kedua adalah Dekrit Presiden tanggal 5 Juli 1959 yang memberlakukan kembali UUD 1945** sebagai konstitusi negara Republik Indonesia.",
            "alasan_kunci": "Opsi B tepat karena Dekrit 5 Juli 1959 yang dikeluarkan Presiden Soekarno membubarkan Konstituante, menetapkan berlakunya kembali UUD 1945 dan tidak berlakunya lagi UUDS 1950, serta pembentukan MPRS dan DPAS.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penetapan konstitusi nasional merupakan tindakan kedaulatan internal bangsa Indonesia.",
                "C": "Kurang tepat karena KNIP sudah tidak ada setelah pembentukan DPR hasil Pemilu 1955.",
                "D": "Kurang tepat karena kembalinya konstitusi ditetapkan melalui Dekrit Presiden, bukan SK menteri.",
                "E": "Kurang tepat karena kegagalan fraksi-fraksi di Konstituante yang memicu lahirnya Dekrit Presiden."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 16,
        "halaman_sumber": 57,
        "kata_kunci_modul": ["Bubarkan PKI; Bersihkan Kabinet dari unsur-unsur G-30-S/PKI; dan Turunkan harga-harga/ perbaikan ekonomi"],
        "topik_materi": "Tiga Tuntutan Rakyat (TRITURA)",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Tuntutan bersejarah yang disampaikan oleh rakyat dipelopori pemuda dan mahasiswa pada awal tahun 1966 yang dikenal sebagai TRITURA memuat isi...",
        "pilihan": {
            "A": "Pemberian amnesti narapidana, pembentukan federasi, dan kenaikan tarif cukai",
            "B": "Bubarkan PKI, bersihkan kabinet dari unsur G-30-S/PKI, dan turunkan harga/perbaikan ekonomi",
            "C": "Nasionalisasi perbankan, unifikasi kepolisian militer, dan penutupan jalur laut",
            "D": "Pemilihan presiden secara langsung, penghapusan DPD, dan revisi undang-undang agraria",
            "E": "Pembekuan dewan perwakilan daerah, privatisasi BUMN, dan pinjaman perbankan asing"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Tri Tuntutan Rakyat (TRITURA) yang dipelopori pemuda/mahasiswa berisi tiga tuntutan pokok: **(1) Bubarkan PKI, (2) Bersihkan Kabinet dari unsur-unsur G-30-S/PKI, dan (3) Turunkan harga-harga/perbaikan ekonomi**.",
            "alasan_kunci": "Opsi B tepat karena TRITURA lahir dari keprihatinan mendalam atas kekacauan politik dan krisis inflasi ekonomi parah pasca-pemberontakan G-30-S/PKI tahun 1965, menuntut penuntasan hukum terhadap dalang makar serta perbaikan taraf hidup rakyat.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena TRITURA menuntut pembubaran partai makar dan penurunan harga, bukan amnesti.",
                "C": "Kurang tepat karena penutupan jalur laut dan unifikasi bukan substansi tuntutan mahasiswa 1966.",
                "D": "Kurang tepat karena isu pilpres langsung dan DPD merupakan agenda gerakan reformasi 1998.",
                "E": "Kurang tepat karena privatisasi BUMN bukan merupakan bagian dari tuntutan TRITURA."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 17,
        "halaman_sumber": 57,
        "kata_kunci_modul": ["Terjadi pemberontakan G-30-S/PKI"],
        "topik_materi": "Peristiwa G-30-S/PKI Tahun 1965",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Peristiwa tragis yang mengguncang stabilitas politik dan kedaulatan negara pada kurun waktu pertama pasca-Dekrit 1959 adalah...",
        "pilihan": {
            "A": "Pemberontakan perhimpunan serikat buruh pelabuhan di tanjung perak",
            "B": "Terjadi pemberontakan G-30-S/PKI yang mengancam keutuhan ideologi negara",
            "C": "Serangan agresi militer armada perang kerajaan belanda di ibu kota",
            "D": "Pertikaian bersenjata perbatasan teritorial kepulauan dengan negara tetangga",
            "E": "Krisis likuiditas moneter perbankan swasta berskala multinasional"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pada periode 5 Juli 1959 hingga sebelum 11 Maret 1966: **Terjadi pemberontakan G-30-S/PKI**. Sewaktu terjadi pemberontakan kondisi negara memprihatinkan khususnya di bidang ekonomi, politik, dan hukum, namun pemberontakan berhasil digagalkan.",
            "alasan_kunci": "Opsi B tepat karena pemberontakan G-30-S/PKI tahun 1965 berusaha merongrong dasar negara Pancasila melalui penculikan dan pembunuhan para perwira tinggi militer AD, yang kemudian berhasil ditumpas oleh kekuatan patriotik bangsa.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pemogokan buruh bukan ancaman ideologis utama pada kurun tersebut.",
                "C": "Kurang tepat karena agresi militer Belanda terjadi pada masa 1945–1949, bukan tahun 1965.",
                "D": "Kurang tepat karena konfrontasi perbatasan bukan peristiwa pemberontakan internal di ibu kota.",
                "E": "Kurang tepat karena krisis ekonomi era tersebut berupa hiperinflasi domestik, bukan kebangkrutan bank asing."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 18,
        "halaman_sumber": 57,
        "kata_kunci_modul": ["Presiden mengeluarkan Supersemar kepada Letjen Soeharto"],
        "topik_materi": "Penerbitan Surat Perintah Sebelas Maret (Supersemar)",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Langkah strategis yang diambil Presiden Soekarno guna memulihkan keamanan dan stabilitas nasional pada 11 Maret 1966 adalah...",
        "pilihan": {
            "A": "Menyerahkan mandat kedaulatan kepada majelis permusyawaratan darurat",
            "B": "Presiden mengeluarkan Supersemar kepada Letjen Soeharto untuk mengambil tindakan pengamanan",
            "C": "Membubarkan seluruh kesatuan korps kepolisian dan kejaksaan agung",
            "D": "Menginstruksikan pembentukan persemakmuran pertahanan bersama negara sahabat",
            "E": "Mengumumkan pengunduran diri secara sepihak di hadapan pimpinan dewan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pada tanggal 11 Maret 1966 **Presiden mengeluarkan Supersemar kepada Letjen Soeharto untuk mengambil langkah-langkah dan tindakan untuk mengamankan negara**.",
            "alasan_kunci": "Opsi B tepat karena Surat Perintah Sebelas Maret memberi wewenang penuh kepada Menteri/Panglima Angkatan Darat Letjen Soeharto untuk memulihkan ketertiban, ketenangan, serta menjamin keselamatan pribadi dan kewibawaan Presiden.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena kedaulatan tetap dipegang oleh Presiden Soekarno pada saat penerbitan Supersemar.",
                "C": "Kurang tepat karena kepolisian dan kejaksaan tetap beroperasi mengawal hukum dan keamanan.",
                "D": "Kurang tepat karena Supersemar adalah instrumen pengamanan komando domestik internal.",
                "E": "Kurang tepat karena Presiden Soekarno tidak mengundurkan diri pada 11 Maret 1966."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 19,
        "halaman_sumber": 57,
        "kata_kunci_modul": ["Lahirnya Supersemar dianggap sebagai lahirnya Orde Baru"],
        "topik_materi": "Supersemar Sebagai Tonggak Lahirnya Orde Baru",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Dalam periodisasi sejarah politik ketatanegaraan Indonesia, penerbitan Supersemar memiliki makna simbolis penting yaitu...",
        "pilihan": {
            "A": "Menandai berakhirnya bentuk negara kesatuan republik indonesia",
            "B": "Lahirnya Supersemar dianggap sebagai lahirnya Orde Baru di Indonesia",
            "C": "Menjadi titik awal berlakunya sistem peradilan militer darurat tertutup",
            "D": "Mengawali pembentukan kabinet parlementer liberal di lingkungan dewan",
            "E": "Menandai berhentinya operasional seluruh badan usaha milik negara"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Lahirnya Supersemar dianggap sebagai lahirnya Orde Baru**, yang menandai babak transisi kepemimpinan nasional dari masa Orde Lama menuju tatanan pemerintahan Orde Baru.",
            "alasan_kunci": "Opsi B tepat karena Supersemar menjadi dasar tindakan pembubaran PKI dan pembersihan aparatur negara, yang menjadi fondasi berdirinya rezim Orde Baru yang berkomitmen melaksanakan Pancasila dan UUD 1945 secara murni dan konsekuen.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena bentuk NKRI tetap berdiri kokoh dan tidak pernah berakhir.",
                "C": "Kurang tepat karena Supersemar ditujukan untuk pemulihan ketertiban umum dan keamanan nasional.",
                "D": "Kurang tepat karena sistem parlementer telah diakhiri sejak Dekrit 1959 dan tidak dipulihkan kembali.",
                "E": "Kurang tepat karena BUMN tetap menjalankan fungsi produksi dan penyediaan kebutuhan masyarakat."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 20,
        "halaman_sumber": 57,
        "kata_kunci_modul": ["Kurun waktu kedua berlakunya UUD 1945 dari tanggal 5 Juli 1959 hingga sekarang dapat dibagi dalam empat periode"],
        "topik_materi": "Periodisasi Kurun Waktu Kedua UUD 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan lintasan sejarah konstitusi, kurun waktu kedua berlakunya UUD 1945 secara garis besar diklasifikasikan ke dalam...",
        "pilihan": {
            "A": "Dua tahapan transisi darurat militer dan persemakmuran bersama sekutu",
            "B": "Kurun waktu kedua berlakunya UUD 1945 dapat dibagi dalam empat periode ketatanegaraan",
            "C": "Tiga dasawarsa berlakunya sistem monarki perwakilan daerah otonom",
            "D": "Lima fase pembagian kedaulatan wilayah ke negara-negara serikat",
            "E": "Enam kurun perombakan total naskah pembukaan undang-undang dasar"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "**Kurun waktu kedua berlakunya UUD 1945 dari tanggal 5 Juli 1959 hingga sekarang dapat dibagi dalam empat periode**, yaitu Orde Lama (1959–1966), Orde Baru (1966–1998), Reformasi (1998–1999), dan Pasca-Amandemen (1999–sekarang).",
            "alasan_kunci": "Opsi B tepat karena pembagian empat periode ini mencerminkan dinamika penerapan UUD 1945 dari masa Demokrasi Terpimpin, masa Orde Baru, masa awal Reformasi, hingga modernisasi konstitusi lewat empat tahap amandemen.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Indonesia tidak pernah menjadi negara persemakmuran sekutu.",
                "C": "Kurang tepat karena Indonesia adalah negara kesatuan berbentuk republik, bukan monarki.",
                "D": "Kurang tepat karena bentuk federal hanya berlaku pada era RIS 1949–1950, bukan kurun kedua.",
                "E": "Kurang tepat karena naskah Pembukaan UUD 1945 tidak pernah dirombak dan bersifat permanen."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    }
]

output_path = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch\draft_p06_sub1_hlm56_57.json"
with open(output_path, "w", encoding="utf-8") as f:
    json.dump(questions, f, indent=2, ensure_ascii=False)

print(f"Sub-Agent 1 (Paket 06) finished! Total questions: {len(questions)}")

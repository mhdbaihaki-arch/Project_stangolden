import json

# Sub-Agent 5: Halaman 54 dan 55 (Nomor 81 s/d 100)
# Halaman 54: Nomor 81 - 90
# Halaman 55: Nomor 91 - 100

questions = [
    # --- HALAMAN 54 (Q81 - Q90) ---
    {
        "nomor": 81,
        "halaman_sumber": 54,
        "kata_kunci_modul": ["kurun waktu pertama dari tanggal 18 Agustus 1945 hingga tanggal 27 Desember 1949"],
        "topik_materi": "Kurun Waktu Pertama Berlakunya UUD 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Dalam sejarah konstitusi Indonesia, periode kurun waktu pertama berlakunya UUD 1945 berlangsung pada rentang...",
        "pilihan": {
            "A": "Mulai tanggal 17 Agustus 1945 hingga tanggal 17 Agustus 1950 secara penuh",
            "B": "Kurun waktu pertama dari tanggal 18 Agustus 1945 hingga tanggal 27 Desember 1949",
            "C": "Sejak pengesahan piagam jakarta hingga pembentukan dewan perancang nasional",
            "D": "Terhitung sejak 27 Desember 1949 hingga terbitnya dekrit presiden 1959",
            "E": "Mulai 5 Juli 1959 hingga rangkaian sidang umum majelis tahun 1965"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "UUD 1945 berlaku dalam dua kurun waktu. Berlakunya UUD 1945 dalam **kurun waktu pertama dari tanggal 18 Agustus 1945 hingga tanggal 27 Desember 1949**.",
            "alasan_kunci": "Opsi B tepat karena kurun waktu pertama dimulai sehari setelah Proklamasi Kemerdekaan saat PPKI mengesahkan UUD 1945 pada 18 Agustus 1945, dan berakhir saat kedaulatan diserahkan kepada Republik Indonesia Serikat (RIS) pada 27 Desember 1949.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pada 27 Desember 1949 diberlakukan Konstitusi RIS.",
                "C": "Kurang tepat karena Piagam Jakarta disusun pada 22 Juni 1945 sebelum kemerdekaan.",
                "D": "Kurang tepat karena rentang 1949–1959 merupakan periode berlakunya Konstitusi RIS dan UUDS 1950.",
                "E": "Kurang tepat karena kurun waktu pasca-Dekrit 5 Juli 1959 merupakan kurun waktu kedua berlakunya UUD 1945."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 82,
        "halaman_sumber": 54,
        "kata_kunci_modul": ["PPKI menetapkan Komite Nasional sebagai pembantu Presiden"],
        "topik_materi": "Penetapan Komite Nasional Oleh PPKI",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Karena lembaga tinggi negara (MPR, DPR, DPA) belum dapat dibentuk pada awal kemerdekaan, langkah yang diambil PPKI adalah...",
        "pilihan": {
            "A": "Menyerahkan kekuasaan administratif kepada kantor perwakilan sekutu",
            "B": "PPKI menetapkan Komite Nasional sebagai pembantu Presiden dalam menjalankan negara",
            "C": "Membentuk dewan militer darurat guna mengambil alih kendali pemerintahan",
            "D": "Menunjuk gubernur jenderal sebagai kepala pemerintahan sementara waktu",
            "E": "Membekukan seluruh aktivitas birokrasi kementerian di tingkat ibu kota"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Karena lembaga-lembaga negara (MPR, DPR, DPA) belum dapat dibentuk, **PPKI menetapkan Komite Nasional sebagai pembantu Presiden**, untuk pembenarannya diberlakukan Pasal IV Aturan Peralihan UUD 1945.",
            "alasan_kunci": "Opsi B tepat karena situasi revolusi fisik dan agresi militer tidak memungkinkan diselenggarakannya pemilu untuk membentuk MPR dan DPR. Komite Nasional Indonesia Pusat (KNIP) dibentuk sebagai wadah darurat penopang jalannya pemerintahan Presiden Soekarno.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena para pendiri bangsa mempertahankan kedaulatan merdeka penuh dari pihak asing.",
                "C": "Kurang tepat karena kepemimpinan negara tetap berada pada supremasi sipil dwitunggal Soekarno-Hatta.",
                "D": "Kurang tepat karena jabatan gubernur jenderal merupakan struktur kolonial yang telah runtuh.",
                "E": "Kurang tepat karena roda birokrasi kementerian tetap digerakkan untuk melayani rakyat merdeka."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 83,
        "halaman_sumber": 54,
        "kata_kunci_modul": ["Sistem Kabinet Presidensial yang harus dilaksanakan menurut UUD 1945 diubah menjadi Kabinet Parlementer"],
        "topik_materi": "Peralihan Sistem Kabinet Presidensial ke Parlementer",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Penyimpangan praktik ketatanegaraan terhadap teks konstitusi yang terjadi pada kurun waktu pertama kemerdekaan adalah...",
        "pilihan": {
            "A": "Penghapusan status kedaulatan teritorial negara kesatuan oleh pemerintah pusat",
            "B": "Sistem Kabinet Presidensial yang harus dilaksanakan menurut UUD 1945 diubah menjadi Kabinet Parlementer",
            "C": "Pembentukan dewan moneter yang mencetak mata uang gulden secara massal",
            "D": "Pengalihan wewenang panglima angkatan perang kepada pimpinan kepolisian",
            "E": "Pemberian hak veto kepada delegasi korporasi swasta dalam sidang kabinet"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam kurun waktu pertama, **sistem Kabinet Presidensial yang harus dilaksanakan menurut UUD 1945 diubah menjadi Kabinet Parlementer**.",
            "alasan_kunci": "Opsi B tepat karena Maklumat Pemerintah 14 November 1945 menggeser kepemimpinan kabinet dari Presiden ke Perdana Menteri (Sutan Sjahrir) dan menteri bertanggung jawab kepada KNIP. Langkah politis ini diambil untuk meyakinkan sekutu bahwa Indonesia adalah negara demokratis, bukan bentukan fasis Jepang.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena status kedaulatan negara kesatuan senantiasa dipertahankan dengan gigih.",
                "C": "Kurang tepat karena Indonesia menerbitkan mata uang sendiri (ORI) pada Oktober 1946.",
                "D": "Kurang tepat karena panglima angkatan perang (Jenderal Soedirman) memimpin struktur militer mandiri.",
                "E": "Kurang tepat karena kabinet tidak memberikan hak veto kepada korporasi swasta."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 84,
        "halaman_sumber": 54,
        "kata_kunci_modul": ["berlaku konstitusi RIS dari tanggal 27 Desember 1949 hingga tanggal 17 Agustus 1950"],
        "topik_materi": "Periode Berlakunya Konstitusi RIS",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Konstitusi Republik Indonesia Serikat (RIS) diberlakukan di Indonesia pada masa transisi ketatanegaraan yaitu...",
        "pilihan": {
            "A": "Terhitung sejak 17 Agustus 1945 hingga Konferensi Meja Bundar disepakati",
            "B": "Berlaku konstitusi RIS dari tanggal 27 Desember 1949 hingga tanggal 17 Agustus 1950",
            "C": "Mulai pembacaan teks proklamasi hingga penyerahan mandat perdana menteri",
            "D": "Sejak 17 Agustus 1950 hingga penetapan dekrit presiden 5 Juli 1959",
            "E": "Sepanjang masa agresi militer belanda pertama di wilayah kepulauan jawa"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Antara kurun waktu pertama dan kurun waktu kedua berlakunya UUD 1945, **berlaku konstitusi RIS dari tanggal 27 Desember 1949 hingga tanggal 17 Agustus 1950**.",
            "alasan_kunci": "Opsi B tepat karena berlakunya Konstitusi RIS merupakan konsekuensi dari hasil Konferensi Meja Bundar (KMB) di Den Haag, di mana Belanda mengakui kedaulatan Indonesia dalam bentuk negara federasi RIS yang berlangsung selama kurang lebih delapan bulan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pada 1945–1949 yang berlaku secara de jure adalah UUD 1945.",
                "C": "Kurang tepat karena pembacaan teks proklamasi mengawali berlakunya UUD 1945 kurun pertama.",
                "D": "Kurang tepat karena rentang 1950–1959 merupakan periode berlakunya UUDS 1950.",
                "E": "Kurang tepat karena Agresi Militer Belanda I terjadi pada tahun 1947 di era UUD 1945 kurun pertama."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 85,
        "halaman_sumber": 54,
        "kata_kunci_modul": ["Republik Indonesia yang beribukota Jogjakarta yang tetap memberlakukan UUD 1945"],
        "topik_materi": "Eksistensi RI Yogyakarta Selama Periode RIS",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Meskipun Konstitusi RIS diberlakukan secara federal, status UUD 1945 pada kurun 1949–1950 tetap dipertahankan oleh...",
        "pilihan": {
            "A": "Seluruh negara bagian boneka bentukan van Mook di wilayah timur",
            "B": "Negara bagian Republik Indonesia yang beribukota Jogjakarta yang tetap memberlakukan UUD 1945",
            "C": "Pemerintah distrik militer di kepulauan riau dan sekitarnya",
            "D": "Dewan perwakilan persekutuan dagang di pelabuhan tanjung priok",
            "E": "Kantor administrasi komisi jasa baik perserikatan bangsa-bangsa"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Konstitusi RIS tidak berlaku di **negara Republik Indonesia yang beribukota Jogjakarta yang tetap memberlakukan UUD 1945**.",
            "alasan_kunci": "Opsi B tepat karena dalam struktur federasi RIS, Republik Indonesia (dengan Presiden Mr. Assaat dan ibukota Yogyakarta) merupakan salah satu negara bagian yang tetap setia berpegang teguh pada UUD 1945 hingga pembubaran RIS.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena negara bagian bentukan Belanda tunduk pada Konstitusi RIS.",
                "C": "Kurang tepat karena distrik militer terikat pada struktur komando angkatan perang persekutuan.",
                "D": "Kurang tepat karena persekutuan dagang bukan entitas negara bagian ketatanegaraan.",
                "E": "Kurang tepat karena KTN/UNCI merupakan komisi penengah internasional PBB."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 86,
        "halaman_sumber": 54,
        "kata_kunci_modul": ["sejak tanggal 17 Agustus 1950 hingga tanggal 5 Juli 1959 berlaku Undang-Undang Dasar Sementara 1950"],
        "topik_materi": "Periode Berlakunya UUDS 1950",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Setelah negara federal RIS dibubarkan dan kembali ke bentuk kesatuan, hukum dasar yang digunakan Indonesia adalah...",
        "pilihan": {
            "A": "Rancangan konstitusi piagam jakarta yang disempurnakan panitia sembilan",
            "B": "Sejak tanggal 17 Agustus 1950 hingga tanggal 5 Juli 1959 berlaku Undang-Undang Dasar Sementara 1950",
            "C": "Naskah undang-undang dasar persemakmuran bentukan konferensi den haag",
            "D": "Kitab hukum tata negara transisi yang disahkan oleh komite arbitrase",
            "E": "Maklumat dewan perancang undang-undang darurat perang tahun 1950"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Selanjutnya, **sejak tanggal 17 Agustus 1950 hingga tanggal 5 Juli 1959 berlaku Undang-Undang Dasar Sementara 1950 (UUDS 1950)**.",
            "alasan_kunci": "Opsi B tepat karena piagam persetujuan RIS dan RI pada 19 Mei 1950 menyepakati pembentukan NKRI dengan memberlakukan konstitusi baru berkarakter parlementer liberal yang dinamakan UUDS 1950 hingga konstitusi definitif selesai dirumuskan oleh Konstituante.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Piagam Jakarta adalah dokumen perumusan naskah pra-kemerdekaan 1945.",
                "C": "Kurang tepat karena naskah Konstitusi RIS digantikan oleh UUDS 1950 pada 17 Agustus 1950.",
                "D": "Kurang tepat karena hukum dasar formal yang berlaku adalah UUDS 1950.",
                "E": "Kurang tepat karena UUDS 1950 ditetapkan melalui UU No. 7 Tahun 1950, bukan maklumat darurat."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 87,
        "halaman_sumber": 54,
        "kata_kunci_modul": ["Dekrit Presiden tanggal 5 Juli 1959 merupakan dasar hukum berlakunya UUD 1945 dalam kurun waktu kedua"],
        "topik_materi": "Dekrit Presiden 5 Juli 1959",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Landasan yuridis yang mengembalikan pemberlakuan UUD 1945 untuk kurun waktu kedua di Indonesia adalah...",
        "pilihan": {
            "A": "Resolusi majelis umum dewan perdamaian internasional perserikatan bangsa",
            "B": "Dekrit Presiden tanggal 5 Juli 1959 merupakan dasar hukum berlakunya UUD 1945 dalam kurun waktu kedua",
            "C": "Putusan bersama antara pimpinan fraksi parlemen dan mahkamah agung",
            "D": "Instruksi kepala staf komando pemulihan keamanan dan ketertiban nasional",
            "E": "Hasil kesepakatan pemungutan suara bulat seluruh anggota konstituante"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Ditetapkan **Dekrit Presiden tanggal 5 Juli 1959 merupakan dasar hukum berlakunya UUD 1945 dalam kurun waktu kedua hingga sekarang (sebelum diamandemen)**.",
            "alasan_kunci": "Opsi B tepat karena kebuntuan (*deadlock*) di badan Konstituante untuk menetapkan UUD baru mendorong Presiden Soekarno mengeluarkan Dekrit 5 Juli 1959 berdasarkan hukum keadaan darurat negara (*staatsnoodrecht*), yang membubarkan Konstituante dan memberlakukan kembali UUD 1945.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena kembalinya UUD 1945 merupakan tindakan hukum internal kedaulatan bangsa Indonesia.",
                "C": "Kurang tepat karena Dekrit 5 Juli 1959 merupakan tindakan unilateral Presiden Soekarno.",
                "D": "Kurang tepat karena Kopkamtib baru dibentuk pada tahun 1965 pasca-G30S.",
                "E": "Kurang tepat karena Konstituante gagal mencapai kuorum 2/3 dalam pemungutan suara kembali ke UUD 1945."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 88,
        "halaman_sumber": 54,
        "kata_kunci_modul": ["memusatkan segala upaya untuk mempertahankan kemerdekaan"],
        "topik_materi": "Fokus Perjuangan Indonesia Kurun 1945-1949",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Penyebab utama UUD 1945 belum dapat dilaksanakan secara tertib dan sempurna pada kurun waktu 1945–1949 adalah...",
        "pilihan": {
            "A": "Terjadinya krisis pertikaian perebutan batas perairan maritim antarprovinsi",
            "B": "Indonesia memusatkan segala upaya untuk mempertahankan kemerdekaan dari agresi kolonial Belanda",
            "C": "Adanya penolakan massal terhadap naskah pembukaan undang-undang dasar",
            "D": "Ketiadaan tenaga ahli sarjana hukum perundang-undangan di seluruh daerah",
            "E": "Kegagalan perbankan nasional dalam menstabilkan cadangan emas perbendaharaan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam kurun waktu tahun 1945-1949 Indonesia **memusatkan segala upaya untuk mempertahankan kemerdekaan**, karena pihak kolonial Belanda ingin menjajah kembali Indonesia yang sudah merdeka.",
            "alasan_kunci": "Opsi B tepat karena perang revolusi fisik mempertahankan kemerdekaan menyerap seluruh energi bangsa. Prioritas negara adalah perjuangan diplomasi dan bersenjata menghadapi agresi militer Belanda I dan II, sehingga agenda pembentukan lembaga formal pemilu terpaksa ditunda.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pertikaian antardaerah bukan faktor dominan melainkan agresi kolonial Belanda.",
                "C": "Kurang tepat karena naskah Pembukaan UUD 1945 diterima penuh sebagai konsensus luhur bangsa.",
                "D": "Kurang tepat karena para pendiri bangsa merupakan tokoh-tokoh berpendidikan tinggi dan sarjana hukum mumpuni.",
                "E": "Kurang tepat karena masalah moneter merupakan akibat perang, bukan penyebab utama kendala konstitusi."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 89,
        "halaman_sumber": 54,
        "kata_kunci_modul": ["Diperlakukan pasal IV Aturan Peralihan UUD 1945 (asli)"],
        "topik_materi": "Pemberlakuan Pasal IV Aturan Peralihan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Dasar yuridis yang digunakan untuk melegitimasi pemusatan wewenang pemerintahan darurat pada awal kemerdekaan adalah...",
        "pilihan": {
            "A": "Pemberlakuan pasal aturan tambahan mengenai tata cara perubahan pasal UUD",
            "B": "Diberlakukan pasal IV Aturan Peralihan UUD 1945 (asli) dalam penyelenggaraan kekuasaan",
            "C": "Penerapan instruksi darurat sipil yang disahkan oleh perwakilan negara sekutu",
            "D": "Perjanjian bantuan administrasi militer antara kementerian dengan milisi rakyat",
            "E": "Dekrit penyerahan kedaulatan sementara kepada komite palang merah internasional"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sebagai landasan ketatanegaraan darurat, **diberlakukan pasal IV Aturan Peralihan UUD 1945 (asli)** yang menjadi dasar hukum pelaksanaan wewenang lembaga tinggi negara oleh Presiden dibantu Komite Nasional.",
            "alasan_kunci": "Opsi B tepat karena pasal IV Aturan Peralihan UUD 1945 dirancang secara visioner oleh PPKI untuk menjembatani kevakuman kelembagaan sebelum lembaga-lembaga permanen (MPR, DPR, DPA) dapat dibentuk melalui pemilu demokratis.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena aturan tambahan mengatur status naskah UUD, bukan kewenangan eksekutif darurat.",
                "C": "Kurang tepat karena Indonesia menolak tunduk pada instruksi sekutu dan NICA.",
                "D": "Kurang tepat karena laskar rakyat berada dalam koordinasi pertahanan, bukan badan hukum administrasi negara.",
                "E": "Kurang tepat karena Indonesia berdaulat penuh dan tidak menyerahkan kekuasaan kepada PMI."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 90,
        "halaman_sumber": 54,
        "kata_kunci_modul": ["Lembaga-lembaga negara belum dapat dibentuk berdasarkan ketentuan UUD 1945"],
        "topik_materi": "Kondisi Kelembagaan Negara Awal Kemerdekaan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Realitas ketatanegaraan yang terjadi pada hari-hari pertama pasca-proklamasi kemerdekaan adalah...",
        "pilihan": {
            "A": "Seluruh anggota parlemen telah dilantik secara paripurna di gedung dewan",
            "B": "Lembaga-lembaga negara belum dapat dibentuk berdasarkan ketentuan UUD 1945",
            "C": "Sistem peradilan kasasi telah beroperasi penuh di seluruh pelosok keresidenan",
            "D": "Badan pemeriksa keuangan telah menyelesaikan audit neraca penutupan kas kolonial",
            "E": "Komisi pemilihan umum telah mengumumkan jadwal pemungutan suara nasional"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam catatan kurun waktu 1945-1949: **Lembaga-lembaga negara belum dapat dibentuk berdasarkan ketentuan UUD 1945, karena kondisi dan situasi yang tidak memungkinkan**.",
            "alasan_kunci": "Opsi B tepat karena negara Indonesia yang baru merdeka belum memiliki perangkat peraturan pelaksana pemilu, struktur logistik, maupun keamanan yang stabil. Akibatnya, fungsi kelembagaan negara dijalankan oleh Presiden dengan bantuan Komite Nasional.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena parlemen hasil pemilu baru terbentuk pada tahun 1955.",
                "C": "Kurang tepat karena operasional MA dan peradilan nasional baru dalam tahap perintisan unifikasi.",
                "D": "Kurang tepat karena BPK baru didirikan pada tanggal 1 Januari 1947 di Magelang.",
                "E": "Kurang tepat karena KPU pemilu pertama baru dibentuk pada dekade 1950-an."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },

    # --- HALAMAN 55 (Q91 - Q100) ---
    {
        "nomor": 91,
        "halaman_sumber": 55,
        "kata_kunci_modul": ["Sebelum MPR, DPR, DPA dibentuk menurut UUD ini, segala kekuasaannya dijalankan oleh Presiden"],
        "topik_materi": "Bunyi Pasal IV Aturan Peralihan UUD 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Rumusan yuridis Pasal IV Aturan Peralihan UUD 1945 naskah asli menegaskan bunyi ketentuan bahwa...",
        "pilihan": {
            "A": "Kekuasaan presiden berakhir seketika apabila dewan pertimbangan agung terbentuk",
            "B": "Sebelum MPR, DPR, DPA dibentuk menurut UUD ini, segala kekuasaannya dijalankan oleh Presiden dengan bantuan Komite Nasional",
            "C": "Kedaulatan negara diserahkan kepada pimpinan komisi jasa baik perserikatan bangsa",
            "D": "Presiden dilarang membentuk kabinet sebelum mendapat izin dari komite internasional",
            "E": "Penyelenggaraan peradilan kehakiman dipimpin langsung oleh pimpinan angkatan bersenjata"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pasal IV Aturan Peralihan UUD 1945 berbunyi: **'Sebelum MPR, DPR, DPA dibentuk menurut UUD ini, segala kekuasaannya dijalankan oleh Presiden dengan bantuan Komite Nasional'**.",
            "alasan_kunci": "Opsi B tepat karena pasal ini merupakan ketentuan transisi yang memberikan mandat legitimasi penuh bagi Presiden Soekarno untuk menjalankan fungsi kepresidenan sekaligus fungsi legislatif dan konsultatif selama masa revolusi kemerdekaan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena DPA merupakan dewan penasihat yang tidak mengakhiri kekuasaan eksekutif presiden.",
                "C": "Kurang tepat karena kedaulatan negara berada di tangan rakyat dan dijalankan Presiden, bukan komisi PBB.",
                "D": "Kurang tepat karena pembentukan kabinet adalah hak prerogatif presiden merdeka.",
                "E": "Kurang tepat karena peradilan tetap berpedoman pada undang-undang dan hakim independen."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 92,
        "halaman_sumber": 55,
        "kata_kunci_modul": ["kekuasaan Presiden sangat besar karena meliputi kewenangan semua lembaga-lembaga tinggi negara"],
        "topik_materi": "Konsentrasi Kekuasaan Presiden Awal Kemerdekaan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Konsekuensi ketatanegaraan dari penerapan Pasal IV Aturan Peralihan UUD 1945 pada awal kemerdekaan adalah...",
        "pilihan": {
            "A": "Kekuasaan eksekutif terbagi habis ke berbagai dewan pertimbangan daerah otonom",
            "B": "Kekuasaan Presiden sangat besar karena meliputi kewenangan semua lembaga-lembaga tinggi negara",
            "C": "Presiden kehilangan kendali operasional atas jajaran perwira tentara nasional",
            "D": "Lembaga parlemen memegang kendali mutlak pencopotan pasangan dwitunggal proklamator",
            "E": "Birokrasi pemerintahan dijalankan sepenuhnya oleh penasihat ekonomi asing"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Berdasarkan pasal IV Aturan Peralihan UUD 1945, **kekuasaan Presiden sangat besar karena meliputi kewenangan semua lembaga-lembaga tinggi negara**, sedangkan Komite Nasional hanya berfungsi sebagai pembantu Presiden.",
            "alasan_kunci": "Opsi B tepat karena Presiden bertindak sebagai pemegang kekuasaan eksekutif sekaligus pemegang kekuasaan legislatif pembentuk undang-undang, sehingga kekuasaan negara terpusat di tangan kepala negara demi kecepatan pengambilan keputusan darurat.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena kekuasaan justru memusat di tangan presiden, bukan terbagi habis ke daerah.",
                "C": "Kurang tepat karena Presiden memegang kekuasaan tertinggi atas angkatan perang.",
                "D": "Kurang tepat karena pada saat itu MPR dan DPR belum terbentuk.",
                "E": "Kurang tepat karena birokrasi dipimpin langsung oleh para menteri pejuang bangsa."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 93,
        "halaman_sumber": 55,
        "kata_kunci_modul": ["maklumat Wakil Presiden No. X tanggal 16 Oktober 1945"],
        "topik_materi": "Maklumat Wakil Presiden No. X Tahun 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Perubahan status dan fungsi Komite Nasional Indonesia Pusat (KNIP) menjadi lembaga legislatif ditetapkan melalui...",
        "pilihan": {
            "A": "Dekrit kepala staf angkatan bersenjata tentang tata tertib persidangan darurat",
            "B": "Maklumat Wakil Presiden No. X tanggal 16 Oktober 1945 yang ditandatangani Mohammad Hatta",
            "C": "Surat keputusan kementerian penerangan mengenai susunan fraksi politik dewan",
            "D": "Resolusi bersama dewan perwakilan serikat buruh dan tani seluruh indonesia",
            "E": "Instruksi tertulis pimpinan kesultanan jogjakarta kepada jajaran pamong praja"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "KNIP yang semula berstatus pembantu Presiden berubah menjadi badan pemegang kekuasaan legislatif dan ikut menetapkan GBHN berdasarkan **maklumat Wakil Presiden No. X tanggal 16 Oktober 1945**.",
            "alasan_kunci": "Opsi B tepat karena Maklumat No. X yang dikeluarkan oleh Wakil Presiden Mohammad Hatta merupakan salah satu konvensi ketatanegaraan terpenting yang mengubah fungsi KNIP dari sekadar pembantu presiden menjadi badan legislatif (DPR sementara) sebelum pemilu terselenggara.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena perubahan fungsi lembaga perwakilan sipil bukan ranah dekrit staf angkatan bersenjata.",
                "C": "Kurang tepat karena kementerian penerangan tidak berwenang mengubah kedudukan lembaga tinggi negara.",
                "D": "Kurang tepat karena serikat buruh bukan otoritas hukum penetapan konstitusi negara.",
                "E": "Kurang tepat karena Maklumat No. X berlaku nasional dan ditandatangani Wapres Mohammad Hatta."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 94,
        "halaman_sumber": 55,
        "kata_kunci_modul": ["memegang kekuasaan legislatif dan ikut menetapkan Garis-Garis Besar daripada Haluan Negara"],
        "topik_materi": "Fungsi Baru Komite Nasional Indonesia Pusat (KNIP)",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan Maklumat No. X tanggal 16 Oktober 1945, wewenang baru yang diberikan kepada Komite Nasional Pusat adalah...",
        "pilihan": {
            "A": "Menyelenggarakan peradilan kasasi sengketa perdata waris di daerah",
            "B": "Memegang kekuasaan legislatif dan ikut menetapkan Garis-Garis Besar daripada Haluan Negara",
            "C": "Mengangkat dan memberhentikan para menteri koordinator secara independen",
            "D": "Menetapkan besaran tarif cukai tembakau impor dari pelabuhan luar negeri",
            "E": "Menyita sarana transportasi perkeretaapian milik administrasi kolonial"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Berdasarkan Maklumat Wapres No. X, Komite Nasional Pusat berubah menjadi **badan yang memegang kekuasaan legislatif dan ikut menetapkan Garis-Garis Besar daripada Haluan Negara** (GBHN).",
            "alasan_kunci": "Opsi B tepat karena pemberian wewenang legislatif dan penetapan haluan negara ini menjadikan KNIP menjalankan fungsi ganda yang semestinya dimiliki oleh DPR dan MPR sebelum lembaga hasil pemilu terbentuk.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena peradilan kasasi merupakan ranah Mahkamah Agung.",
                "C": "Kurang tepat karena pengangkatan menteri tetap berada pada ranah kekuasaan eksekutif pemerintah.",
                "D": "Kurang tepat karena regulasi tarif cukai merupakan wewenang operasional kementerian keuangan.",
                "E": "Kurang tepat karena nasionalisasi aset dilakukan oleh pemerintah dan serikat buruh kereta api."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 95,
        "halaman_sumber": 55,
        "kata_kunci_modul": ["usul Badan Pekerja Komite Nasional Indonesia Pusat (BPKNIP) pada tanggal 11 Nopember 1945"],
        "topik_materi": "Inisiatif Pembentukan Kabinet Parlementer Oleh BPKNIP",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pergeseran sistem kabinet presidensial menuju kabinet parlementer pada November 1945 diawali oleh peristiwa...",
        "pilihan": {
            "A": "Gugatan peradilan tata usaha negara dari kalangan pegawai pamong praja",
            "B": "Usul Badan Pekerja Komite Nasional Indonesia Pusat (BPKNIP) pada tanggal 11 Nopember 1945",
            "C": "Tuntutan resmi dari perwakilan korps diplomatik komisi pengawasan sekutu",
            "D": "Mosi tidak percaya serentak dari gabungan pimpinan militer di yogyakarta",
            "E": "Hasil referendum pemilih yang diselenggarakan serentak di lima provinsi"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Perubahan sistem kabinet berakar dari **usul Badan Pekerja Komite Nasional Indonesia Pusat (BPKNIP) pada tanggal 11 Nopember 1945**, yang disetujui Presiden dan diumumkan lewat Maklumat Pemerintah 14 November 1945.",
            "alasan_kunci": "Opsi B tepat karena BPKNIP (yang dipimpin oleh Sutan Sjahrir) menginginkan para menteri bertanggung jawab secara politis kepada badan perwakilan rakyat, bukan sekadar menjadi pegawai pembantu presiden.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena PTUN belum ada pada awal kemerdekaan.",
                "C": "Kurang tepat karena usulan perubahan kabinet berasal dari inisiatif internal wakil rakyat BPKNIP.",
                "D": "Kurang tepat karena militer tidak mengajukan mosi tidak percaya pada 11 November 1945.",
                "E": "Kurang tepat karena Indonesia tidak menyelenggarakan referendum pada masa revolusi fisik."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 96,
        "halaman_sumber": 55,
        "kata_kunci_modul": ["Maklumat Pemerintah tanggal 14 Nopember 1945"],
        "topik_materi": "Maklumat Pemerintah 14 November 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Landasan resmi berlakunya sistem pemerintahan parlementer di awal sejarah Republik Indonesia adalah...",
        "pilihan": {
            "A": "Surat perintah pelimpahan wewenang darurat militer kepada pimpinan laskar",
            "B": "Maklumat Pemerintah tanggal 14 Nopember 1945 tentang perubahan sistem kabinet",
            "C": "Ketetapan majelis permusyawaratan darurat tentang struktur kementerian",
            "D": "Piagam kesepakatan damai antara pimpinan perundingan dengan utusan belanda",
            "E": "Undang-undang darurat pembentukan susunan kepresidenan federal 1945"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Perubahan sistem kabinet presidensial menjadi sistem kabinet parlementer diumumkan secara resmi melalui **Maklumat Pemerintah tanggal 14 Nopember 1945**.",
            "alasan_kunci": "Opsi B tepat karena Maklumat 14 November 1945 menandai lahirnya jabatan Perdana Menteri pertama Indonesia (Kabinet Sjahrir I) dan menandai dimulainya praktik parlementer di mana kabinet bertanggung jawab kepada KNIP.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena sistem parlementer merupakan urusan kepemimpinan politik pemerintahan, bukan darurat militer.",
                "C": "Kurang tepat karena MPR darurat belum pernah terbentuk pada tahun 1945.",
                "D": "Kurang tepat karena Maklumat 14 November 1945 diterbitkan sebelum perundingan damai Linggajati.",
                "E": "Kurang tepat karena undang-undang darurat federal baru muncul pada era Konstitusi RIS 1949."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 97,
        "halaman_sumber": 55,
        "kata_kunci_modul": ["kekuasaan pemerintah tidak dipegang oleh Presiden, tetapi dipegang oleh Perdana Menteri"],
        "topik_materi": "Karakteristik Kabinet Parlementer 1945",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Perubahan mendasar dalam struktur eksekutif sejak tanggal 14 November 1945 ditandai oleh kondisi bahwa...",
        "pilihan": {
            "A": "Presiden membubarkan KNIP dan memimpin seluruh sidang kementerian sendirian",
            "B": "Kekuasaan pemerintah dipegang oleh Perdana Menteri sebagai pimpinan kabinet",
            "C": "Para menteri bertanggung jawab langsung secara personal kepada ratu belanda",
            "D": "Seluruh keputusan kabinet wajib diratifikasi oleh komite militer gabungan",
            "E": "Jabatan presiden ditiadakan dan digantikan oleh gubernur jenderal sipil"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sejak tanggal 14 Nopember 1945, **kekuasaan pemerintah tidak dipegang oleh Presiden, tetapi dipegang oleh Perdana Menteri sebagai pimpinan kabinet** dengan para menteri sebagai anggota kabinet.",
            "alasan_kunci": "Opsi B tepat karena Presiden berkedudukan sebagai Kepala Negara yang bersifat simbolis (*head of state*), sedangkan kendali eksekutif operasional pemerintahan sehari-hari beralih ke tangan Perdana Menteri (*head of government*).",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena KNIP tidak dibubarkan melainkan bertindak sebagai parlemen pengawas kabinet.",
                "C": "Kurang tepat karena menteri bertanggung jawab kepada KNIP, bukan kepada ratu Belanda.",
                "D": "Kurang tepat karena kabinet parlementer dipimpin oleh figur sipil tanpa ratifikasi komite militer.",
                "E": "Kurang tepat karena Presiden Soekarno tetap menjabat sebagai Kepala Negara Republik Indonesia."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 98,
        "halaman_sumber": 55,
        "kata_kunci_modul": ["Para menteri dan perdana menteri bertanggung jawab kepada KNIP yang berfungsi sebagai DPR"],
        "topik_materi": "Akuntabilitas Kabinet Kepada KNIP",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pola pertanggungjawaban politik dalam sistem kabinet parlementer pasca-Maklumat 14 November 1945 diatur bahwa...",
        "pilihan": {
            "A": "Menteri bertanggung jawab secara eksklusif kepada pimpinan partai pendukung",
            "B": "Para menteri dan perdana menteri bertanggung jawab kepada KNIP yang berfungsi sebagai DPR",
            "C": "Pemerintah bertanggung jawab kepada majelis permusyawaratan tingkat karesidenan",
            "D": "Perdana menteri dapat mencopot anggota dewan perwakilan secara otoritatif",
            "E": "Kabinet hanya menyampaikan laporan kinerja kepada komisi pengawas sekutu"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Dalam sistem parlementer 1945, **para menteri dan perdana menteri bertanggung jawab kepada KNIP yang berfungsi sebagai DPR**, baik secara bersama-sama maupun sendiri-sendiri.",
            "alasan_kunci": "Opsi B tepat karena ciri khas sistem parlementer adalah kelangsungan kabinet bergantung pada mosi dukungan parlemen. KNIP sewaktu-waktu dapat menjatuhkan kabinet jika mosi tidak percaya diajukan oleh mayoritas anggota dewan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pertanggungjawaban disampaikan resmi ke forum perwakilan dewan (KNIP).",
                "C": "Kurang tepat karena majelis karesidenan bukan lembaga perwakilan tingkat nasional.",
                "D": "Kurang tepat karena perdana menteri tidak berwenang mencopot anggota parlemen KNIP.",
                "E": "Kurang tepat karena akuntabilitas kabinet ditujukan kepada wakil rakyat Indonesia sendiri."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 99,
        "halaman_sumber": 55,
        "kata_kunci_modul": ["berlangsung sangat singkat hanya 8 bulan yaitu dari tanggal 27 Desember 1949 hingga tanggal 17 Agustus 1950"],
        "topik_materi": "Masa Berlakunya Negara Federal RIS",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Durasi keberadaan bentuk negara federal Republik Indonesia Serikat (RIS) dalam lintasan sejarah Indonesia adalah...",
        "pilihan": {
            "A": "Bertahan selama lima tahun penuh hingga terselenggaranya pemilu demokratis 1955",
            "B": "Berlangsung amat singkat selama delapan bulan sejak 27 Desember 1949 hingga 17 Agustus 1950",
            "C": "Berjalan selama tiga dasawarsa sebelum akhirnya diamandemen oleh dewan perwakilan",
            "D": "Mencapai sepuluh tahun masa transisi di bawah perlindungan komisi perserikatan bangsa",
            "E": "Berlangsung selama dua tahun kalender terhitung sejak pembacaan dekrit presiden"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Negara federal RIS **berlangsung sangat singkat hanya 8 bulan yaitu dari tanggal 27 Desember 1949 hingga tanggal 17 Agustus 1950** sebelum kembali menjadi Negara Kesatuan Republik Indonesia.",
            "alasan_kunci": "Opsi B tepat karena bentuk federal RIS tidak berakar dari kehendak murni rakyat melainkan kompromi diplomatik KMB. Dalam hitungan bulan, arus unifikasi rakyat di berbagai negara bagian menuntut pembubaran federasi dan integrasi kembali ke NKRI.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pemilu 1955 berlangsung pada era UUDS 1950, bukan pada era RIS.",
                "C": "Kurang tepat karena RIS hanya bertahan 8 bulan, bukan tiga dasawarsa.",
                "D": "Kurang tepat karena durasi RIS kurang dari satu tahun.",
                "E": "Kurang tepat karena Dekrit Presiden terjadi pada tahun 1959, jauh setelah bubarnya RIS pada 1950."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 100,
        "halaman_sumber": 55,
        "kata_kunci_modul": ["timbul suatu keinginan dari negara-negara bagian RIS buatan Belanda yang merasa tidak cocok"],
        "topik_materi": "Runtuhnya Negara Federal RIS Menuju NKRI",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Faktor pendorong utama yang menyebabkan runtuhnya bentuk negara federal RIS dan kembalinya bentuk NKRI pada 17 Agustus 1950 adalah...",
        "pilihan": {
            "A": "Instruksi militer asing yang mewajibkan seluruh daerah melebur ke dalam persemakmuran",
            "B": "Timbul suatu keinginan dari negara-negara bagian RIS buatan Belanda yang merasa tidak cocok dan ingin bergabung ke RI",
            "C": "Kegagalan penandatanganan perjanjian utang luar negeri dengan serikat perbankan eropa",
            "D": "Penolakan mahkamah internasional terhadap permohonan keanggotaan federasi kepulauan",
            "E": "Keputusan sepihak pimpinan parlemen federal yang memecat seluruh perdana menteri bagian"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sejak berdirinya RIS, **timbul suatu keinginan dari negara-negara bagian RIS buatan Belanda yang merasa tidak cocok atas terbentuknya RIS hasil KMB, dan ingin bergabung dengan negara bagian Republik Indonesia yang beribukota Jogjakarta**.",
            "alasan_kunci": "Opsi B tepat karena negara-negara bagian bentukan van Mook (seperti Negara Madura, Pasundan, Sumatera Timur) menyadari bahwa federasi merupakan strategi politik pecah belah (*devide et impera*). Rakyat dan parlemen negara-negara bagian tersebut secara sukarela membubarkan diri dan bergabung kembali ke Republik Indonesia.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena kembalinya ke NKRI didorong oleh kehendak murni rakyat Indonesia sendiri.",
                "C": "Kurang tepat karena masalah utang KMB bukan faktor pendorong utama kembalinya integrasi NKRI.",
                "D": "Kurang tepat karena Mahkamah Internasional tidak mengintervensi bentuk internal ketatanegaraan RIS.",
                "E": "Kurang tepat karena pembubaran negara bagian dilakukan melalui prosedur resolusi perwakilan daerah masing-masing."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    }
]

output_path = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch\draft_p05_sub5_hlm54_55.json"
with open(output_path, "w", encoding="utf-8") as f:
    json.dump(questions, f, indent=2, ensure_ascii=False)

print(f"Sub-Agent 5 (Paket 05) finished! Total questions: {len(questions)}")

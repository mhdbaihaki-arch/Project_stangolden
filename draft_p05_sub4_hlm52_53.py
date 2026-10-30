import json

# Sub-Agent 4: Halaman 52 dan 53 (Nomor 61 s/d 80)
# Halaman 52: Nomor 61 - 70
# Halaman 53: Nomor 71 - 80

questions = [
    # --- HALAMAN 52 (Q61 - Q70) ---
    {
        "nomor": 61,
        "halaman_sumber": 52,
        "kata_kunci_modul": ["Memutus perselisihan hasil pemilihan umum"],
        "topik_materi": "Wewenang MK Memutus Perselisihan Pemilu",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Kewenangan yudisial Mahkamah Konstitusi dalam mengawal kemurnian suara kedaulatan rakyat ditegaskan melalui wewenang untuk...",
        "pilihan": {
            "A": "Menetapkan perolehan suara resmi secara sepihak sebelum rekapitulasi tuntas",
            "B": "Memutus perselisihan hasil pemilihan umum dengan putusan final dan mengikat",
            "C": "Menjatuhkan vonis kurungan pidana penjara bagi pelaku kecurangan pemilu",
            "D": "Membekukan kepengurusan komisi pemilihan umum di seluruh wilayah kepulauan",
            "E": "Membatalkan seluruh tahapan pemilu secara nasional saat terjadi ketegangan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Salah satu wewenang Mahkamah Konstitusi menurut Pasal 24C ayat (1) UUD 1945 adalah **memutus perselisihan hasil pemilihan umum** (PHPU).",
            "alasan_kunci": "Opsi B tepat karena MK merupakan peradilan tingkat pertama dan terakhir yang putusannya bersifat final dan mengikat (*final and binding*) dalam mengadili perselisihan penetapan perolehan suara pemilu legislatif, pemilu presiden, maupun pilkada.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penetapan perolehan suara resmi dilakukan oleh Komisi Pemilihan Umum (KPU).",
                "C": "Kurang tepat karena tindak pidana pemilu diproses oleh Sentra Gakkumdu dan diadili pengadilan negeri.",
                "D": "Kurang tepat karena penegakan etik komisioner KPU diputus oleh Dewan Kehormatan Penyelenggara Pemilu (DKPP).",
                "E": "Kurang tepat karena MK mengadili sengketa perselisihan angka hasil pemilu, bukan membatalkan pemilu secara serampangan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 62,
        "halaman_sumber": 52,
        "kata_kunci_modul": ["paling lama sembilan puluh hari"],
        "topik_materi": "Tenggat Waktu Pemeriksaan Pemakzulan di MK",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan UUD 1945, Mahkamah Konstitusi wajib memeriksa, mengadili, dan memutus pendapat DPR mengenai dugaan pelanggaran hukum oleh Presiden dalam batas waktu...",
        "pilihan": {
            "A": "Tiga puluh hari kalender sejak berkas usulan diterima panitera",
            "B": "Paling lama sembilan puluh hari setelah permintaan DPR tersebut diterima oleh MK",
            "C": "Empat puluh lima hari kerja terhitung sejak sidang pleno permulaan",
            "D": "Seratus dua puluh hari kalender setelah verifikasi dokumen dinyatakan lengkap",
            "E": "Enam puluh hari kerja sejak penunjukan susunan majelis hakim peradilan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Mahkamah Konstitusi wajib memeriksa, mengadili, dan memutus dengan seadil-adilnya terhadap pendapat DPR mengenai dugaan pelanggaran oleh Presiden/Wapres **paling lama sembilan puluh hari** setelah permintaan DPR diterima.",
            "alasan_kunci": "Opsi B tepat karena konstitusi membatasi durasi peradilan pemakzulan maksimal sembilan puluh hari guna menjamin kepastian hukum ketatanegaraan serta mencegah kevakuman atau ketidakpastian kepemimpinan nasional.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena tiga puluh hari merupakan batas waktu penyelenggaraan sidang paripurna MPR pasca-putusan MK.",
                "C": "Kurang tepat karena batas waktu konstitusional adalah sembilan puluh hari, bukan 45 hari kerja.",
                "D": "Kurang tepat karena jangka waktu 120 hari tidak diatur dalam mekanisme konstitusi pasal 7B.",
                "E": "Kurang tepat karena durasi 60 hari berlaku untuk pengisian kekosongan posisi wakil presiden di MPR."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 63,
        "halaman_sumber": 52,
        "kata_kunci_modul": ["bebas dan mandiri dalam memeriksa pengelolaan dan tanggung jawab keuangan negara"],
        "topik_materi": "Kedudukan Bebas dan Mandiri Badan Pemeriksa Keuangan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Kedudukan konstitusional Badan Pemeriksa Keuangan (BPK) menurut Pasal 23E ayat (1) UUD 1945 dicirikan oleh status bahwa lembaga ini...",
        "pilihan": {
            "A": "Merupakan sub-bagian teknis pembukuan kas di bawah kementerian keuangan",
            "B": "Bebas dan mandiri dalam memeriksa pengelolaan dan tanggung jawab keuangan negara",
            "C": "Berada di bawah kendali pimpinan komisi perbankan dan anggaran parlemen",
            "D": "Bertanggung jawab langsung secara struktural kepada kantor kepresidenan",
            "E": "Merupakan lembaga penuntut umum dalam perkara tindak pidana perpajakan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Kedudukan BPK ditegaskan dalam UUD 1945 sebagai lembaga negara yang **bebas dan mandiri dalam memeriksa pengelolaan dan tanggung jawab keuangan negara**.",
            "alasan_kunci": "Opsi B tepat karena status bebas (*free*) dan mandiri (*independent*) menjamin bahwa auditor BPK dapat melakukan pemeriksaan keuangan negara tanpa intimidasi, intervensi, atau pengaruh dari pihak yang diperiksa (eksekutif maupun legislatif).",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena BPK berkedudukan di luar kementerian keuangan dan memeriksa kementerian keuangan itu sendiri.",
                "C": "Kurang tepat karena BPK bersifat mandiri dan tidak berada di bawah kendali komisi DPR.",
                "D": "Kurang tepat karena BPK sejajar dengan Presiden sebagai lembaga tinggi negara, bukan bawahan kepresidenan.",
                "E": "Kurang tepat karena fungsi penuntutan pidana merupakan wewenang kejaksaan, bukan BPK."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 64,
        "halaman_sumber": 52,
        "kata_kunci_modul": ["pelaksana fungsi auditif, operatif, dan rekomendatif"],
        "topik_materi": "Tiga Fungsi Pokok Badan Pemeriksa Keuangan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Dalam tata kelola pengawasan keuangan negara, Badan Pemeriksa Keuangan menjalankan fungsi pokok sebagai...",
        "pilihan": {
            "A": "Pelaksana fungsi eksekutif, pembentukan regulasi, dan peradilan perdata",
            "B": "Pelaksana fungsi auditif, operatif, dan rekomendatif atas keuangan negara",
            "C": "Penyelenggara fungsi moneter, pencetakan uang tunai, dan penjaminan simpanan",
            "D": "Pengelola investasi dana pensiun aparatur dan penerbitan surat berharga",
            "E": "Penyidik intelijen keuangan dan penindak sengketa persaingan usaha"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Kedudukan BPK salah satunya berfungsi **sebagai pelaksana fungsi auditif, operatif, dan rekomendatif** terhadap keuangan negara.",
            "alasan_kunci": "Opsi B tepat karena fungsi auditif adalah memeriksa keuangan, fungsi operatif adalah melakukan pemeriksaan lapangan dan investigasi, serta fungsi rekomendatif adalah memberikan rekomendasi perbaikan sistem dan tata kelola perbendaharaan kepada pemerintah dan DPR.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena BPK bukan pelaksana fungsi eksekutif atau peradilan perdata.",
                "C": "Kurang tepat karena fungsi moneter dan pencetakan uang dijalankan oleh Bank Indonesia.",
                "D": "Kurang tepat karena pengelolaan investasi dan surat berharga merupakan ranah kementerian keuangan.",
                "E": "Kurang tepat karena intelijen keuangan dijalankan oleh PPATK dan persaingan usaha oleh KPPU."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 65,
        "halaman_sumber": 52,
        "kata_kunci_modul": ["Berkedudukan di ibukota negara dan memiliki perwakilan di setiap provinsi"],
        "topik_materi": "Struktur Domisili Badan Pemeriksa Keuangan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Jangkauan kelembagaan Badan Pemeriksa Keuangan dalam mengawal perbendaharaan di seluruh wilayah Indonesia diwujudkan melalui ketentuan bahwa BPK...",
        "pilihan": {
            "A": "Memusatkan seluruh auditornya di kantor pusat tanpa membuka kantor wilayah",
            "B": "Berkedudukan di ibukota negara dan memiliki perwakilan di setiap provinsi",
            "C": "Membentuk kantor cabang tetap di setiap kantor distrik setingkat kecamatan",
            "D": "Menitipkan tugas pemeriksaan keuangan kepada dinas pendapatan daerah",
            "E": "Mengalihkan operasional pengawasan kepada kantor perwakilan bank sentral"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sesuai Pasal 23G ayat (1) UUD 1945, BPK **berkedudukan di ibukota negara dan memiliki perwakilan di setiap provinsi**.",
            "alasan_kunci": "Opsi B tepat karena keberadaan kantor perwakilan BPK di setiap provinsi memastikan bahwa pengelolaan APBD pemerintah daerah dan instansi vertikal di daerah dapat diperiksa secara efektif, cepat, dan berkesinambungan.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena BPK tidak hanya di kantor pusat melainkan memiliki perwakilan di tiap provinsi.",
                "C": "Kurang tepat karena perwakilan BPK berada di tingkat provinsi, bukan di tingkat kecamatan.",
                "D": "Kurang tepat karena BPK merupakan lembaga eksternal yang mandiri dan tidak menitipkan tugas ke pemda.",
                "E": "Kurang tepat karena Bank Indonesia menjalankan otoritas moneter dan bukan pemeriksa anggaran negara."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 66,
        "halaman_sumber": 52,
        "kata_kunci_modul": ["menyerahkan hasil pemeriksaan atas pengelolaan dan tanggung jawab keuangan negara kepada DPR, DPD, dan DPRD"],
        "topik_materi": "Penyerahan Laporan Hasil Pemeriksaan (LHP) BPK",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Setelah menuntaskan audit keuangan negara, kewajiban konstitusional yang wajib dilaksanakan oleh BPK adalah...",
        "pilihan": {
            "A": "Menjual dokumen temuan audit kepada lembaga riset swasta internasional",
            "B": "Menyerahkan hasil pemeriksaan atas pengelolaan dan tanggung jawab keuangan negara kepada DPR, DPD, dan DPRD",
            "C": "Menyita secara mandiri gedung kantor pemerintah yang memiliki temuan belanja",
            "D": "Menjatuhkan putusan pemecatan seketika kepada pimpinan instansi terperiksa",
            "E": "Mengubah susunan anggaran kementerian secara otoritatif di luar dewan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "BPK bertugas **menyerahkan hasil pemeriksaan atas pengelolaan dan tanggung jawab keuangan negara kepada DPR, DPD, dan DPRD sesuai dengan kewenangannya** untuk ditindaklanjuti.",
            "alasan_kunci": "Opsi B tepat karena DPR menerima LHP keuangan pusat, DPD menerima LHP terkait kedaerahan, dan DPRD menerima LHP keuangan daerah guna menjalankan fungsi pengawasan dan tindak lanjut rekomendasi anggaran.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena hasil audit BPK disampaikan resmi kepada lembaga perwakilan rakyat sesuai konstitusi.",
                "C": "Kurang tepat karena BPK tidak berwenang menyita aset melainkan merekomendasikan pengembalian kas negara.",
                "D": "Kurang tepat karena penjatuhan sanksi pemecatan pegawai merupakan kewenangan pejabat pembina kepegawaian.",
                "E": "Kurang tepat karena BPK tidak berwenang merevisi anggaran APBN/APBD melainkan memeriksa pertanggungjawabannya."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 67,
        "halaman_sumber": 52,
        "kata_kunci_modul": ["Komisi Pemilihan Umum (KPU) yang bersifat nasional, tetap, dan mandiri"],
        "topik_materi": "Karakter Lembaga Komisi Pemilihan Umum",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan Pasal 22E ayat (5) UUD 1945, lembaga penyelenggara pemilihan umum di Indonesia dicirikan oleh sifat yuridis bahwa KPU...",
        "pilihan": {
            "A": "Bersifat kedaerahan, temporer, dan tunduk pada arahan partai penguasa",
            "B": "Merupakan suatu Komisi Pemilihan Umum yang bersifat nasional, tetap, dan mandiri",
            "C": "Merupakan badan ad-hoc eksekutif yang dibentuk dan dibubarkan setiap masa pemilu",
            "D": "Berada di bawah koordinasi operasional kepolisian daerah di setiap wilayah",
            "E": "Bertanggung jawab administratif kepada pimpinan koalisi partai politik dewan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sesuai Pasal 22E ayat (5) UUD 1945, pemilihan umum diselenggarakan oleh suatu **Komisi Pemilihan Umum (KPU) yang bersifat nasional, tetap, dan mandiri**.",
            "alasan_kunci": "Opsi B tepat karena sifat nasional mencakup seluruh wilayah NKRI, sifat tetap berarti kelembagaan bekerja secara berkesinambungan (bukan ad-hoc), dan sifat mandiri berarti bebas dari pengaruh eksekutif maupun kepentingan partisan peserta pemilu.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena KPU berskala nasional dan independen dari partai penguasa.",
                "C": "Kurang tepat karena sifat KPU adalah kelembagaan tetap (*permanent*), bukan badan ad-hoc yang dibubarkan.",
                "D": "Kurang tepat karena KPU tidak berada di bawah komando kepolisian melainkan lembaga penyelenggara mandiri.",
                "E": "Kurang tepat karena KPU tidak bertanggung jawab kepada koalisi fraksi partai politik."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 68,
        "halaman_sumber": 52,
        "kata_kunci_modul": ["langsung, umum, bebas, rahasia, jujur, dan adil setiap lima tahun sekali"],
        "topik_materi": "Asas dan Periodesasi Pemilihan Umum",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Prinsip pelaksanaan pemilihan umum di Indonesia menurut Pasal 22E ayat (1) UUD 1945 diselenggarakan secara...",
        "pilihan": {
            "A": "Terbuka perwakilan, bertahap, fleksibel, dan terjadwal setiap tiga tahun sekali",
            "B": "Langsung, umum, bebas, rahasia, jujur, dan adil setiap lima tahun sekali",
            "C": "Kolektif konsensus, tertutup perwakilan, dan diselenggarakan saat kas APBN surplus",
            "D": "Berjenjang melalui delegasi utusan adat tanpa jadwal periodisasi yang baku",
            "E": "Mandatoris partai, terpusat di ibu kota, dan digelar sewaktu-waktu atas dekrit"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pemilihan umum dilakukan secara **langsung, umum, bebas, rahasia, jujur, dan adil setiap lima tahun sekali** untuk memilih anggota DPR, DPD, Presiden dan Wapres, serta DPRD.",
            "alasan_kunci": "Opsi B tepat karena asas LUBER dan JURDIL merupakan fondasi demokrasi Pancasila yang menjamin kebebasan hak pilih setiap warga negara dan perlakuan yang setara bagi seluruh kontestan pemilu.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pemilu digelar secara langsung dan reguler tiap lima tahun, bukan tiga tahun.",
                "C": "Kurang tepat karena pemilu tidak boleh ditunda dengan alasan defisit APBN.",
                "D": "Kurang tepat karena pemilu langsung dan berjadwal pasti setiap lima tahun sekali.",
                "E": "Kurang tepat karena pemilu merupakan hak rakyat dan tidak dapat digelar sewaktu-waktu tanpa undang-undang."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 69,
        "halaman_sumber": 52,
        "kata_kunci_modul": ["Peserta pemilu untuk memilih anggota DPR dan anggota DPRD adalah parpol"],
        "topik_materi": "Peserta Pemilu Legislatif Dewan Perwakilan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Ketentuan Pasal 22E ayat (3) UUD 1945 menetapkan kontestan pemilihan umum legislatif DPR dan DPRD adalah...",
        "pilihan": {
            "A": "Perseorangan warga negara yang mendapatkan izin khusus dari kementerian",
            "B": "Peserta pemilu untuk memilih anggota DPR dan anggota DPRD adalah partai politik",
            "C": "Gabungan organisasi massa buruh dan serikat pekerja tingkat daerah",
            "D": "Kamar dagang dan industri bersama asosiasi korporasi berbadan hukum",
            "E": "Yayasan sosial kemasyarakatan dan dewan kelurahan se-Indonesia"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pasal 22E ayat (3) UUD 1945 menyatakan bahwa **peserta pemilu untuk memilih anggota DPR dan anggota DPRD adalah partai politik**.",
            "alasan_kunci": "Opsi B tepat karena partai politik diposisikan sebagai pilar demokrasi perwakilan yang mengagregasikan aspirasi masyarakat secara terorganisasi dan mencalonkan wakil rakyat di parlemen tingkat pusat dan daerah.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena calon perseorangan adalah peserta pemilu untuk memilih anggota DPD, bukan DPR/DPRD.",
                "C": "Kurang tepat karena ormas dan serikat buruh bukan badan peserta resmi pemilu legislatif.",
                "D": "Kurang tepat karena asosiasi bisnis dan industri bukan badan hukum peserta pemilihan umum.",
                "E": "Kurang tepat karena yayasan kemasyarakatan bukan kontestan pemilu dewan perwakilan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 70,
        "halaman_sumber": 52,
        "kata_kunci_modul": ["Peserta pemilu untuk memilih anggota DPD adalah perseorangan"],
        "topik_materi": "Jalur Perseorangan Dalam Pemilu Anggota DPD",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Perbedaan mendasar antara peserta pemilu DPR dengan pemilu DPD menurut Pasal 22E ayat (4) UUD 1945 terletak pada aturan bahwa...",
        "pilihan": {
            "A": "Peserta pemilu DPD wajib mendapatkan rekomendasi tertulis dari pimpinan partai",
            "B": "Peserta pemilu untuk memilih anggota DPD adalah perseorangan warga negara",
            "C": "Peserta pemilu DPD diajukan oleh koalisi fraksi dewan perwakilan rakyat",
            "D": "Peserta pemilu DPD diwajibkan menjabat sebagai pimpinan dinas di daerah",
            "E": "Peserta pemilu DPD harus berstatus sebagai perwira purnawirawan dinas"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pasal 22E ayat (4) UUD 1945 menyatakan bahwa **peserta pemilu untuk memilih anggota DPD adalah perseorangan**.",
            "alasan_kunci": "Opsi B tepat karena DPD dirancang sebagai representasi teritorial non-partisan. Calon anggota DPD maju sebagai perseorangan yang didukung oleh sejumlah KTP pemilih di daerahnya, bukan atas nama partai politik.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena calon DPD tidak memerlukan rekomendasi dari partai politik.",
                "C": "Kurang tepat karena koalisi fraksi DPR tidak memiliki hak mengusung calon anggota DPD.",
                "D": "Kurang tepat karena calon DPD tidak disyaratkan menjabat pimpinan dinas eksekutif.",
                "E": "Kurang tepat karena calon DPD terbuka bagi setiap warga negara yang memenuhi syarat undang-undang."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },

    # --- HALAMAN 53 (Q71 - Q80) ---
    {
        "nomor": 71,
        "halaman_sumber": 53,
        "kata_kunci_modul": ["mengatur dan mengurus sendiri urusan pemerintahan menurut asas otonomi dan tugas pembantuan"],
        "topik_materi": "Prinsip Otonomi Daerah dan Tugas Pembantuan",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan Pasal 18 ayat (2) UUD 1945, wewenang pemerintahan daerah provinsi, kabupaten, dan kota diselenggarakan melalui prinsip untuk...",
        "pilihan": {
            "A": "Menerapkan seluruh instruksi menteri secara mutlak tanpa penyesuaian lokal",
            "B": "Mengatur dan mengurus sendiri urusan pemerintahan menurut asas otonomi dan tugas pembantuan",
            "C": "Membentuk mata uang regional guna membiayai belanja operasional kantor",
            "D": "Menetapkan traktat perbatasan dengan otoritas perwakilan negara tetangga",
            "E": "Membubarkan satuan peradilan umum yang berada di wilayah teritorial daerah"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pemerintahan daerah provinsi, kabupaten, dan kota **mengatur dan mengurus sendiri urusan pemerintahan menurut asas otonomi dan tugas pembantuan**.",
            "alasan_kunci": "Opsi B tepat karena asas otonomi memberikan hak, wewenang, dan kewajiban bagi daerah untuk memprakarsai pembangunan sesuai potensi daerahnya, didampingi asas tugas pembantuan (*medebewind*) dari pemerintah tingkat atas.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena pemda memiliki diskresi otonomi dalam batas kewenangannya untuk menyesuaikan kebutuhan daerah.",
                "C": "Kurang tepat karena mata uang merupakan kewenangan absolut pemerintah pusat bidang moneter.",
                "D": "Kurang tepat karena traktat perbatasan merupakan kewenangan eksklusif pemerintah pusat bidang politik luar negeri.",
                "E": "Kurang tepat karena peradilan umum berada di bawah kekuasaan kehakiman Mahkamah Agung."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 72,
        "halaman_sumber": 53,
        "kata_kunci_modul": ["memiliki DPRD yang anggota-anggotanya dipilih melalui pemilu"],
        "topik_materi": "Lembaga Perwakilan Rakyat Daerah (DPRD)",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Prinsip kedaulatan rakyat dalam penyelenggaraan pemerintahan daerah menurut UUD 1945 diwujudkan melalui ketentuan bahwa pemerintah daerah...",
        "pilihan": {
            "A": "Dipimpin oleh dewan penasihat yang ditunjuk langsung oleh kementerian",
            "B": "Memiliki DPRD yang anggota-anggotanya dipilih melalui pemilu legislatif",
            "C": "Diawasi oleh komite pengawas swasta yang berkedudukan di ibu kota",
            "D": "Menyerahkan fungsi anggaran kepada majelis pertimbangan kesultanan",
            "E": "Dikelola sepenuhnya oleh aparat birokrasi karier tanpa perwakilan dewan"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pasal 18 ayat (3) UUD 1945 menetapkan pemerintahan daerah provinsi, daerah kabupaten, dan kota **memiliki DPRD yang anggota-anggotanya dipilih melalui pemilu**.",
            "alasan_kunci": "Opsi B tepat karena DPRD berkedudukan sebagai mitra sejajar kepala daerah dalam menjalankan fungsi legislasi daerah (Perda), penganggaran (APBD), dan pengawasan jalannya roda pemerintahan daerah.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena DPRD bukan dewan penasihat penunjukan melainkan wakil rakyat terpilih lewat pemilu.",
                "C": "Kurang tepat karena pengawasan politik dijalankan oleh DPRD dan pengawasan keuangan oleh BPK.",
                "D": "Kurang tepat karena hak anggaran daerah berada pada wewenang DPRD bersama kepala daerah.",
                "E": "Kurang tepat karena pemerintahan daerah melibatkan unsur perwakilan politik rakyat melalui DPRD."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 73,
        "halaman_sumber": 53,
        "kata_kunci_modul": ["Gubernur, Bupati, Walikota masing-masing sebagai kepala pemerintah daerah provinsi, kabupaten, dan kota dipilih secara demokratis"],
        "topik_materi": "Pemilihan Kepala Daerah Secara Demokratis",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan Pasal 18 ayat (4) UUD 1945, suksesi kepemimpinan eksekutif di tingkat daerah dilaksanakan melalui ketentuan bahwa...",
        "pilihan": {
            "A": "Kepala daerah diangkat secara mutlak oleh menteri dalam negeri atas dasar loyalitas",
            "B": "Gubernur, Bupati, Walikota masing-masing dipilih secara demokratis sesuai regulasi undang-undang",
            "C": "Jabatan kepala daerah diwariskan secara turun-temurun kepada garis keluarga penguasa",
            "D": "Kepala dinas senior secara otomatis dilantik menduduki kursi bupati dan walikota",
            "E": "Pemilihan kepala daerah ditentukan melalui lelang kontribusi investasi modal asing"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "UUD 1945 menetapkan bahwa **Gubernur, Bupati, Walikota masing-masing sebagai kepala pemerintah daerah provinsi, kabupaten, dan kota dipilih secara demokratis**.",
            "alasan_kunci": "Opsi B tepat karena rumusan 'dipilih secara demokratis' memberikan landasan konstitusional bagi pemilihan kepala daerah, baik melalui pemilihan langsung oleh rakyat (Pilkada) maupun sistem demokratis khusus yang diatur dalam undang-undang bagi daerah istimewa.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena kepala daerah tidak diangkat sepihak oleh menteri melainkan dipilih secara demokratis.",
                "C": "Kurang tepat karena suksesi kepemimpinan monarki dinasti bertentangan dengan prinsip demokrasi republik.",
                "D": "Kurang tepat karena kepala daerah merupakan jabatan politik hasil pemilihan demokratis, bukan promosi karier PNS.",
                "E": "Kurang tepat karena pemilihan kepala daerah bertumpu pada mandat suara kedaulatan rakyat."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 74,
        "halaman_sumber": 53,
        "kata_kunci_modul": ["Pemerintahan daerah menjalankan otonomi seluas-luasnya, kecuali urusan pemerintahan yang oleh undang-undang ditentukan sebagai urusan pemerintah pusat"],
        "topik_materi": "Prinsip Otonomi Seluas-Luasnya dan Urusan Pusat",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Prinsip pembagian kewenangan pemerintahan antara pusat dan daerah menurut Pasal 18 ayat (5) UUD 1945 diatur bahwa...",
        "pilihan": {
            "A": "Daerah berwenang mengendalikan kebijakan moneter dan mencetak uang lokal",
            "B": "Pemerintahan daerah menjalankan otonomi seluas-luasnya, kecuali urusan pemerintahan yang ditentukan sebagai urusan pusat",
            "C": "Pemerintah pusat melimpahkan wewenang peradilan dan pembentukan angkatan perang ke daerah",
            "D": "Seluruh urusan pelayanan publik wajib diputuskan secara terpusat di kantor kementerian",
            "E": "Pemerintah daerah tidak memiliki kewenangan mengatur tata kelola fasilitas umum"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sesuai Pasal 18 ayat (5) UUD 1945, **pemerintahan daerah menjalankan otonomi seluas-luasnya, kecuali urusan pemerintahan yang oleh undang-undang ditentukan sebagai urusan pemerintah pusat**.",
            "alasan_kunci": "Opsi B tepat karena daerah diberi keleluasaan luas untuk mengurus urusan konkuren dan lokal, sedangkan urusan absolut (politik luar negeri, pertahanan, keamanan, moneter, yustisi, dan agama) tetap menjadi wewenang eksklusif pemerintah pusat.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena kebijakan moneter merupakan urusan pemerintah pusat (Bank Indonesia).",
                "C": "Kurang tepat karena peradilan dan pertahanan militer merupakan wewenang absolut pusat.",
                "D": "Kurang tepat karena sentralisasi pelayanan publik bertentangan dengan semangat otonomi seluas-luasnya.",
                "E": "Kurang tepat karena pengelolaan fasilitas umum daerah merupakan urusan otonomi pemda."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 75,
        "halaman_sumber": 53,
        "kata_kunci_modul": ["memperhatikan kekhususan dan keragaman daerah"],
        "topik_materi": "Hubungan Kewenangan Pusat dan Daerah",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Dalam mengatur hubungan wewenang antara pemerintah pusat dan daerah, UUD 1945 mewajibkan adanya prinsip untuk...",
        "pilihan": {
            "A": "Menerapkan standardisasi tata kelola yang seragam kaku tanpa mempedulikan budaya",
            "B": "Memperhatikan kekhususan dan keragaman daerah dalam kerangka kesatuan negara",
            "C": "Menghapuskan kearifan lokal yang telah berkembang di tengah masyarakat suku",
            "D": "Memberikan hak pemisahan wilayah bagi daerah yang berpenghasilan tambang tinggi",
            "E": "Membatasi komunikasi resmi antara kepala daerah dengan jajaran kementerian"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Hubungan wewenang antara pusat dan pemda provinsi, kabupaten, dan kota diatur dengan undang-undang dengan **memperhatikan kekhususan dan keragaman daerah**.",
            "alasan_kunci": "Opsi B tepat karena Indonesia memiliki keragaman geografis, sosial, budaya, dan historis. Hubungan kewenangan pusat-daerah harus fleksibel dan akomodatif terhadap pluralitas daerah guna menjaga keharmonisan nasional.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penyeragaman birokrasi kaku bertentangan dengan pengakuan keragaman daerah.",
                "C": "Kurang tepat karena kearifan lokal dihormati dan dilindungi oleh konstitusi negara.",
                "D": "Kurang tepat karena hak pemisahan diri (secession) dilarang dalam kerangka NKRI.",
                "E": "Kurang tepat karena komunikasi koordinasi pusat-daerah wajib terjalin intensif."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 76,
        "halaman_sumber": 53,
        "kata_kunci_modul": ["Negara mengakui dan menghormati satuan-satuan pemda yang bersifat khusus atau bersifat istimewa"],
        "topik_materi": "Pengakuan Daerah Khusus dan Istimewa Pasal 18B Ayat 1",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan Pasal 18B ayat (1) UUD 1945, sikap konstitusional negara terhadap daerah yang memiliki keistimewaan historis ditegaskan bahwa...",
        "pilihan": {
            "A": "Pemerintah pusat menghapus otonomi khusus demi unifikasi tata kelola wilayah",
            "B": "Negara mengakui dan menghormati satuan-satuan pemda yang bersifat khusus atau istimewa",
            "C": "Daerah istimewa dibebaskan secara total dari kewajiban mematuhi konstitusi negara",
            "D": "Wilayah khusus diwajibkan membentuk angkatan bersenjata mandiri tingkat provinsi",
            "E": "Status keistimewaan daerah dicabut apabila terjadi pergantian kepemimpinan pusat"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Sesuai Pasal 18B ayat (1) UUD 1945, **Negara mengakui dan menghormati satuan-satuan pemda yang bersifat khusus atau bersifat istimewa diatur dengan undang-undang**.",
            "alasan_kunci": "Opsi B tepat karena konstitusi menghargai hak asal-usul dan jasa historis daerah istimewa (seperti Aceh dan DIY) serta daerah berstatus otonomi khusus (seperti DKI Jakarta dan Papua) yang diatur dengan undang-undang spesifik.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena negara mengakui dan menghormati status khusus tersebut, bukan menghapusnya.",
                "C": "Kurang tepat karena daerah istimewa tetap terikat dalam kerangka kedaulatan UUD 1945 dan NKRI.",
                "D": "Kurang tepat karena pertahanan militer tetap tunggal di bawah Tentara Nasional Indonesia.",
                "E": "Kurang tepat karena status keistimewaan dijamin permanen oleh undang-undang dan konstitusi."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 77,
        "halaman_sumber": 53,
        "kata_kunci_modul": ["Negara mengakui dan menghormati kesatuan-kesatuan masyarakat hukum adat beserta hak-hak tradisionalnya"],
        "topik_materi": "Pengakuan Masyarakat Hukum Adat Pasal 18B Ayat 2",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Perlindungan konstitusional terhadap kearifan lokal diatur dalam Pasal 18B ayat (2) UUD 1945 melalui ketentuan bahwa...",
        "pilihan": {
            "A": "Hukum adat dinyatakan gugur demi penegakan kodifikasi hukum perdata modern",
            "B": "Negara mengakui dan menghormati kesatuan masyarakat hukum adat beserta hak tradisionalnya",
            "C": "Tanah ulayat adat diambil alih secara mutlak oleh korporasi tanpa ganti rugi",
            "D": "Lembaga peradilan adat memegang wewenang memvonis hukuman pidana mati nasional",
            "E": "Masyarakat adat dilarang menggunakan bahasa daerah dalam pergaulan sehari-hari"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "UUD 1945 menegaskan: **Negara mengakui dan menghormati kesatuan-kesatuan masyarakat hukum adat beserta hak-hak tradisionalnya sepanjang masih hidup dan sesuai dengan perkembangan masyarakat dan prinsip NKRI**.",
            "alasan_kunci": "Opsi B tepat karena masyarakat hukum adat dan hak tradisionalnya (seperti hak ulayat) merupakan bagian tak terpisahkan dari kekayaan peradaban bangsa yang dijamin eksistensinya oleh hukum negara.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena hukum adat tetap diakui eksistensinya berdampingan dengan hukum positif.",
                "C": "Kurang tepat karena pengambilan tanah adat sewenang-wenang melanggar jaminan hak konstitusional.",
                "D": "Kurang tepat karena vonis pidana mati hanya dapat dijatuhkan oleh pengadilan peradilan umum berkekuatan tetap.",
                "E": "Kurang tepat karena bahasa daerah dilindungi dan dipelihara oleh negara sesuai Pasal 32 UUD 1945."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 78,
        "halaman_sumber": 53,
        "kata_kunci_modul": ["sepanjang masih hidup dan sesuai dengan perkembangan masyarakat dan prinsip NKRI"],
        "topik_materi": "Syarat Pengakuan Kesatuan Masyarakat Hukum Adat",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Pengakuan terhadap hak tradisional kesatuan masyarakat hukum adat dalam Pasal 18B ayat (2) UUD 1945 dibatasi oleh syarat konstitusional yaitu...",
        "pilihan": {
            "A": "Wajib memiliki kepengurusan formal yang disahkan oleh pimpinan dewan perwakilan",
            "B": "Sepanjang masih hidup dan sesuai dengan perkembangan masyarakat dan prinsip NKRI",
            "C": "Harus menyerahkan separuh hasil panen pertanian ke kas perbendaharaan pusat",
            "D": "Dibatasi pada wilayah teritorial yang berpenduduk lebih dari satu juta jiwa",
            "E": "Wajib menggunakan bahasa pengantar asing dalam pertemuan musyawarah adat"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pengakuan masyarakat adat berlaku bersyarat: **sepanjang masih hidup dan sesuai dengan perkembangan masyarakat dan prinsip NKRI, yang diatur dengan undang-undang**.",
            "alasan_kunci": "Opsi B tepat karena persyaratan ini menjamin bahwa praktik hukum adat yang diakui tidak bertentangan dengan prinsip hak asasi manusia modern, keutuhan kedaulatan teritorial NKRI, dan nilai-nilai Pancasila.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena legitimasi masyarakat adat bertumpu pada hukum adat turun-temurun, bukan pengesahan dewan.",
                "C": "Kurang tepat karena pengakuan hak adat tidak dikaitkan dengan upeti hasil panen.",
                "D": "Kurang tepat karena jumlah populasi bukan syarat pengakuan kesatuan masyarakat hukum adat.",
                "E": "Kurang tepat karena bahasa pengantar adat adalah bahasa daerah masing-masing."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 79,
        "halaman_sumber": 53,
        "kata_kunci_modul": ["Susunan dan tata cara penyelenggaraan pemerintahan daerah diatur dalam undang-undang"],
        "topik_materi": "Landasan Pengaturan Pemerintahan Daerah",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Berdasarkan Pasal 18 ayat (6) UUD 1945, pembentukan kelembagaan serta operasional pemerintahan daerah diatur melalui amanat bahwa...",
        "pilihan": {
            "A": "Penyelenggaraan tata kelola daerah ditentukan oleh kesepakatan asosiasi kepala desa",
            "B": "Susunan dan tata cara penyelenggaraan pemerintahan daerah diatur dalam undang-undang",
            "C": "Struktur satuan kerja perangkat daerah ditetapkan sekehendak pimpinan komisi",
            "D": "Tata kerja dinas daerah diserahkan kepada dewan arbitrase perdagangan kawasan",
            "E": "Birokrasi daerah berhak menolak pemberlakuan regulasi yang diterbitkan presiden"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "UUD 1945 menegaskan: **Susunan dan tata cara penyelenggaraan pemerintahan daerah diatur dalam undang-undang** (yaitu Undang-Undang Pemerintahan Daerah).",
            "alasan_kunci": "Opsi B tepat karena undang-undang menjadi instrumen hukum yang menyeragamkan tata kelola dasar, pembagian urusan, sistem pengawasan, dan tata tertib hubungan eksekutif-legislatif daerah secara nasional.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena penyelenggaraan pemerintahan daerah bersumber dari undang-undang formal negara.",
                "C": "Kurang tepat karena struktur perangkat daerah diatur berdasarkan pedoman regulasi undang-undang dan PP.",
                "D": "Kurang tepat karena dewan arbitrase mengurusi perdata sengketa dagang, bukan birokrasi daerah.",
                "E": "Kurang tepat karena birokrasi daerah wajib patuh pada hierarki peraturan perundang-undangan nasional."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    },
    {
        "nomor": 80,
        "halaman_sumber": 53,
        "kata_kunci_modul": ["NKRI dibagi atas daerah-daerah provinsi dan daerah provinsi itu dibagi atas kabupaten dan kota"],
        "topik_materi": "Struktur Pembagian Wilayah Negara Indonesia",
        "level_kognitif": "C2 - Pemahaman",
        "pertanyaan": "Konsep pembagian wilayah Negara Kesatuan Republik Indonesia menurut Pasal 18 ayat (1) UUD 1945 dirumuskan dengan ketentuan bahwa...",
        "pilihan": {
            "A": "Indonesia merupakan federasi serikat yang terdiri dari negara-negara bagian otonom",
            "B": "NKRI dibagi atas daerah provinsi dan daerah provinsi itu dibagi atas kabupaten dan kota",
            "C": "Wilayah nasional disatukan dalam satu karesidenan tunggal di bawah kendali presiden",
            "D": "Daerah kabupaten memegang kedaulatan mutlak yang setara dengan pemerintah pusat",
            "E": "Batas teritorial daerah ditetapkan berdasarkan persebaran kepemilikan modal swasta"
        },
        "kunci_jawaban": "B",
        "pembahasan": {
            "konsep_materi": "Pasal 18 ayat (1) UUD 1945 menegaskan: **Negara Kesatuan Republik Indonesia dibagi atas daerah-daerah provinsi dan daerah provinsi itu dibagi atas kabupaten dan kota, yang masing-masing mempunyai pemerintahan daerah yang diatur dengan undang-undang**.",
            "alasan_kunci": "Opsi B tepat karena frasa 'dibagi atas' (bukan 'terdiri atas') membuktikan secara yuridis bahwa Indonesia adalah negara kesatuan (*unitary state*), di mana kedaulatan tidak terbagi ke negara-negara bagian melainkan didelegasikan melalui pembagian wilayah administratif berpemerintahan sendiri.",
            "bedah_pengecoh": {
                "A": "Kurang tepat karena Indonesia adalah negara kesatuan, bukan negara federasi.",
                "C": "Kurang tepat karena sistem karesidenan merupakan warisan kolonial dan tidak dikenal dalam UUD 1945.",
                "D": "Kurang tepat karena kedaulatan berada di tangan rakyat dan negara kesatuan, bukan di kabupaten.",
                "E": "Kurang tepat karena batas teritorial daerah ditetapkan atas dasar administratif dan geografis perundang-undangan."
            },
            "sumber_rujukan": "Wawasan Kebangsaan"
        }
    }
]

output_path = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch\draft_p05_sub4_hlm52_53.json"
with open(output_path, "w", encoding="utf-8") as f:
    json.dump(questions, f, indent=2, ensure_ascii=False)

print(f"Sub-Agent 4 (Paket 05) finished! Total questions: {len(questions)}")

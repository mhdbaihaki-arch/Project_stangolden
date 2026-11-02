import json
import re
import subprocess
import os

# Define the exact fixes for the 49 questions across the 6 packages
fixes = {}

# =========================================================================
# 1. MODUL 4 PAKET 05 (7 soal: 5, 12, 23, 33, 36, 55, 72)
# =========================================================================

fixes[('M4', 5, 5)] = {
    'pilihan': {
        'A': 'Fasilitas pembebasan pajak bagi seluruh aparatur pemerintah',
        'B': 'Kewenangan mutlak dalam menetapkan harga komoditas pasar',
        'C': 'Kemampuan menghapus seluruh kewajiban utang luar negeri',
        'D': 'Daya dukung dalam stabilisasi likuiditas valuta asing negara',
        'E': 'Efek ganda dalam menumbuhkan ekonomi dan lapangan kerja'
    },
    'bedah': {
        'A': 'Aparatur pemerintah tetap dikenakan pemotongan pajak penghasilan resmi.',
        'B': 'Harga pasar komoditas ditentukan oleh mekanisme pasar dan daya beli.',
        'C': 'Belanja negara tidak otomatis menghapuskan kewajiban pokok utang.',
        'D': 'Stabilisasi valuta asing adalah fungsi moneter yang dijalankan oleh Bank Indonesia.'
    }
}

fixes[('M4', 5, 12)] = {
    'pilihan': {
        'A': 'Menghambat penarikan dana pinjaman luar negeri pemerintah',
        'B': 'Menimbulkan peluang duplikasi, penumpukan, dan penyimpangan',
        'C': 'Menghambat integrasi pelaporan realisasi belanja modal daerah',
        'D': 'Menurunkan jumlah penerimaan perpajakan kas umum negara',
        'E': 'Membatasi penambahan jumlah formasi aparatur sipil negara'
    },
    'bedah': {
        'A': 'Penarikan pinjaman luar negeri mengikuti tata cara perjanjian pinjaman.',
        'C': 'Pelaporan belanja modal mengikuti standar akuntansi pemerintahan yang berlaku.',
        'D': 'Format belanja tidak memengaruhi penerimaan perpajakan kas negara.',
        'E': 'Formasi ASN ditetapkan berdasarkan analisis jabatan dan beban kerja.'
    }
}

fixes[('M4', 5, 23)] = {
    'pilihan': {
        'A': 'Belanja barang untuk menunjang operasional dinas kantor',
        'B': 'Belanja pegawai untuk meningkatkan kinerja aparatur negara',
        'C': 'Belanja bantuan sosial untuk memproteksi risiko sosial warga',
        'D': 'Belanja modal untuk menambah kepemilikan aset kementerian',
        'E': 'Belanja bunga untuk melunasi kupon surat utang negara'
    },
    'bedah': {
        'A': 'Belanja barang digunakan untuk operasional instansi pemerintah.',
        'B': 'Belanja pegawai diberikan kepada aparatur negara, bukan masyarakat umum.',
        'D': 'Belanja modal menghasilkan aset tetap milik negara, bukan untuk warga.',
        'E': 'Belanja bunga adalah pelunasan kupon utang kepada pemegang obligasi.'
    }
}

fixes[('M4', 5, 33)] = {
    'pilihan': {
        'A': 'Tingkat suku bunga pinjaman komersial perbankan daerah',
        'B': 'Jumlah proposal permohonan bantuan yang diajukan oleh bupati',
        'C': 'Persentase atas pendapatan tertentu dalam APBN dan kinerja tertentu',
        'D': 'Alokasi penerimaan bea masuk pelabuhan ekspor kawasan',
        'E': 'Besaran sisa lebih anggaran belanja tahun anggaran sebelumnya'
    },
    'bedah': {
        'A': 'Suku bunga perbankan tidak menjadi dasar pembagian bagi hasil APBN.',
        'B': 'DBH bukan dana hibah proposal melainkan alokasi berbasis persentase.',
        'D': 'Bea masuk merupakan penerimaan pabean yang seluruhnya masuk kas negara.',
        'E': 'SiLPA daerah adalah saldo kas daerah yang tidak memengaruhi DBH.'
    }
}

fixes[('M4', 5, 36)] = {
    'pilihan': {
        'A': 'Kehutanan, minerba, migas, panas bumi, dan perikanan',
        'B': 'Industri perbankan, pasar modal, asuransi, dan dana pensiun',
        'C': 'Transportasi darat, penerbangan perintis, dan perkeretaapian',
        'D': 'Telekomunikasi digital, informatika, dan pos logistik paket',
        'E': 'Perkebunan kelapa sawit, kakao, karet rakyat, dan tebu'
    },
    'bedah': {
        'B': 'Industri perbankan dan asuransi adalah sektor jasa keuangan komersial.',
        'C': 'Transportasi adalah sektor perhubungan logistik, bukan SDA.',
        'D': 'Telekomunikasi adalah sektor infrastruktur teknologi informasi.',
        'E': 'Sektor perkebunan dikelola sebagai komoditas pertanian agrobisnis biasa.'
    }
}

fixes[('M4', 5, 55)] = {
    'pilihan': {
        'A': 'Penyediaan modal usaha perorangan bagi pengurus aparatur desa',
        'B': 'Perjalanan dinas aparatur kementerian, studi banding, dan seminar',
        'C': 'Pembelian saham obligasi korporasi di bursa efek domestik',
        'D': 'Talangan pelunasan utang macet pengurus badan usaha daerah',
        'E': 'Pemerintahan, pembangunan, pemberdayaan, dan kemasyarakatan desa'
    },
    'bedah': {
        'A': 'Dana Desa tidak dapat dialokasikan sebagai pinjaman perorangan aparatur.',
        'B': 'Dana Desa dilarang digunakan untuk perjalanan dinas aparatur pusat.',
        'C': 'Investasi saham komersial bukan bidang penggunaan Dana Desa.',
        'D': 'Dana Desa bukan dana talangan utang komersial badan usaha.'
    }
}

fixes[('M4', 5, 72)] = {
    'pilihan': {
        'A': 'Menguasai seluruh cabang produksi yang penting bagi hajat hidup',
        'B': 'BUMN tersebut tidak bersifat strategis bagi pertahanan negara',
        'C': 'Mengalami kerugian operasional menahun tanpa prospek pemulihan',
        'D': 'Beroperasi khusus di bidang pengadaan senjata dan amunisi militer',
        'E': 'Memiliki kepemilikan aset komersial sepenuhnya di luar negeri'
    },
    'bedah': {
        'A': 'Sektor yang menguasai hajat hidup orang banyak dilarang diprivatisasi penuh.',
        'C': 'BUMN rugi tanpa prospek sulit menarik minat investor pasar modal.',
        'D': 'Industri persenjataan militer adalah sektor strategis pertahanan vital.',
        'E': 'Kepemilikan aset luar negeri bukan indikator kelayakan privatisasi BUMN.'
    }
}

# =========================================================================
# 2. MODUL 5 PAKET 02 (5 soal: 12, 23, 40, 46, 97)
# =========================================================================

fixes[('M5', 2, 12)] = {
    'pilihan': {
        'A': 'Menjamin kepentingan masyarakat seluruhnya sebagai kesatuan',
        'B': 'Menjaga kebebasan individu mutlak di atas kehendak bersama',
        'C': 'Memperluas wilayah pengaruh kekuasaan militer ke luar negeri',
        'D': 'Mengutamakan hegemoni politik golongan pemenang pemilu',
        'E': 'Memusatkan seluruh aset properti warga ke tangan penguasa'
    },
    'bedah': {
        'B': 'Menjaga kebebasan individu mutlak merupakan inti ajaran individualisme liberal.',
        'C': 'Ekspansi pengaruh militer adalah corak negara imperialis.',
        'D': 'Mengutamakan hegemoni golongan merupakan ciri paham kelas/golongan.',
        'E': 'Sentralisasi properti sepihak adalah ciri komunisme ekstrem.'
    }
}

fixes[('M5', 2, 23)] = {
    'pilihan': {
        'A': 'Doktrin formal yang ditetapkan sepihak oleh otoritas legislatif',
        'B': 'Konsensus mayoritas warga mengenai nilai dasar pendirian negara',
        'C': 'Hasil perjanjian dagang bilateral dengan negara tetangga kawasan',
        'D': 'Dokumen rencana kerja tahunan satuan kerja kementerian negara',
        'E': 'Koleksi manuskrip kuno peninggalan kerajaan maritim nusantara'
    },
    'bedah': {
        'A': 'Doktrin formal sepihak mencerminkan paham kekuasaan otoriter tertutup.',
        'C': 'Perjanjian dagang adalah instrumen kerja sama ekonomi luar negeri.',
        'D': 'Rencana kerja tahunan adalah dokumen manajerial APBN/DIPA.',
        'E': 'Koleksi manuskrip kuno adalah benda cagar budaya sejarah.'
    }
}

fixes[('M5', 2, 40)] = {
    'pilihan': {
        'A': 'Prinsip usaha bersama dan asas kekeluargaan seluruh warga',
        'B': 'Pemberantasan seluruh bentuk kepemilikan swasta oleh militer',
        'C': 'Penerapan persaingan bebas berlandaskan mekanisme pasar terbuka',
        'D': 'Kewajiban warga menjual seluruh hasil pertanian kepada pejabat',
        'E': 'Peniadaan mata uang rupiah dan penerapan sistem barter kuno'
    },
    'bedah': {
        'B': 'Pemberantasan kepemilikan swasta mencerminkan corak sosialisme radikal.',
        'C': 'Mekanisme pasar bebas murni merupakan pilar ekonomi kapitalisme liberal.',
        'D': 'Kewajiban menyerahkan hasil panen adalah sistem tanam paksa era kolonial.',
        'E': 'Sistem barter adalah sistem pertukaran tradisional pra-modern.'
    }
}

fixes[('M5', 2, 46)] = {
    'pilihan': {
        'A': 'Kitab Gatotkacasraya gubahan pujangga istana Mpu Panuluh',
        'B': 'Kitab Smaradahana karangan pujangga kerajaan Mpu Dharmaja',
        'C': 'Kitab Sutasoma karangan pujangga terkemuka Mpu Tantular',
        'D': 'Kitab Kresnayana karangan pujangga istana Mpu Triguna',
        'E': 'Kitab Lubdhaka karangan pujangga sastra Mpu Tanakung'
    },
    'bedah': {
        'A': 'Kitab Gatotkacasraya mengisahkan kepahlawanan Gatotkaca dalam pewayangan.',
        'B': 'Kitab Smaradahana mengisahkan terbakarnya Dewa Kamajaya oleh Dewa Siwa.',
        'D': 'Kitab Kresnayana menguraikan kisah pernikahan Prabu Kresna dan Dewi Rukmini.',
        'E': 'Kitab Lubdhaka memuat ajaran tentang pengampunan dosa malam Siwaratri.'
    }
}

fixes[('M5', 2, 97)] = {
    'pilihan': {
        'A': 'Hasil kompromi paksa pejabat militer sekutu pasca perang dunia',
        'B': 'Instruksi tertulis gubernur jenderal hindia belanda di batavia',
        'C': 'Rangkaian musyawarah dan dinamika persidangan resmi BPUPKI',
        'D': 'Rancangan sepihak dari perwakilan komite perundingan luar negeri',
        'E': 'Keputusan rapat terbatas staf khusus pertahanan jepang tokyo'
    },
    'bedah': {
        'A': 'Sekutu tidak terlibat dalam perumusan falsafah dasar negara Indonesia.',
        'B': 'Pemerintah kolonial Belanda menentang keras kemerdekaan Indonesia.',
        'D': 'Pancasila dirumuskan secara mandiri oleh para pendiri bangsa di BPUPKI.',
        'E': 'Staf Tokyo hanya memfasilitasi pembentukan BPUPKI tanpa merumuskan isinya.'
    }
}

# =========================================================================
# 3. MODUL 5 PAKET 03 (10 soal: 28, 51, 56, 66, 69, 70, 74, 76, 77, 78)
# =========================================================================

fixes[('M5', 3, 28)] = {
    'pilihan': {
        'A': 'Menyerahkan rumusan naskah sepenuhnya kepada pihak perwakilan sekutu',
        'B': 'Benar-benar mementingkan keselamatan nasib dan persatuan bangsa',
        'C': 'Terburu-buru karena khawatir terhadap serangan militer sekutu',
        'D': 'Menyerahkan urusan kenegaraan kepada keputusan sekelompok elite',
        'E': 'Memiliki keterbatasan pemahaman terhadap substansi ilmu hukum'
    },
    'bedah': {
        'A': 'Para pendiri bangsa berdaulat penuh dan tidak tunduk pada kehendak sekutu.',
        'C': 'Kesepakatan lahir dari jiwa kenegarawanan luhur, bukan kepanikan militer.',
        'D': 'Keputusan diambil secara musyawarah mufakat demi seluruh rakyat.',
        'E': 'Para tokoh perumus adalah cendekiawan dan pakar hukum terkemuka.'
    }
}

fixes[('M5', 3, 51)] = {
    'pilihan': {
        'A': 'Keadilan sosial bagi seluruh rakyat Indonesia (sila kelima)',
        'B': 'Persekutuan pertahanan bersama dengan negara adidaya dunia',
        'C': 'Pemusatan seluruh hak kepemilikan tanah ke dalam kas negara',
        'D': 'Penerapan persaingan pasar bebas tanpa regulasi perlindungan',
        'E': 'Penghapusan seluruh subsidi sosial bagi masyarakat miskin'
    },
    'bedah': {
        'B': 'Indonesia menganut politik luar negeri bebas aktif non-blok.',
        'C': 'Hak milik perorangan tetap diakui dengan fungsi sosial yang seimbang.',
        'D': 'Pasar bebas murni tanpa regulasi bertentangan dengan ekonomi Pancasila.',
        'E': 'Pemerintah wajib melindungi fakir miskin dan warga terlantar.'
    }
}

fixes[('M5', 3, 56)] = {
    'pilihan': {
        'A': 'Bentuk negara theokrasi mutlak di bawah kepemimpinan otoritas ulama',
        'B': 'Bentuk negara monarki keagamaan dengan kewajiban satu agama tunggal',
        'C': 'Bentuk negara sekuler yang melarang seluruh simbol agama di ruang publik',
        'D': 'Bentuk negara ateis yang meniadakan keberadaan institusi rumah ibadah',
        'E': 'Bentuk negara berketuhanan yang bukan theokrasi dan bukan sekuler'
    },
    'bedah': {
        'A': 'Indonesia bukan theokrasi karena hukum negara tidak didasarkan pada satu agama.',
        'B': 'Indonesia berbentuk republik yang menjamin kemerdekaan memeluk beragam agama.',
        'C': 'Indonesia bukan sekuler karena nilai agama menjiwai hukum dan moralitas bangsa.',
        'D': 'Indonesia berdasar Ketuhanan Yang Maha Esa dan menentang keras ateisme.'
    }
}

fixes[('M5', 3, 66)] = {
    'pilihan': {
        'A': 'Ketiadaan kebebasan berserikat dan mengemukakan pendapat di umum',
        'B': 'Kewajiban warga negara mengikuti seluruh arahan penguasa dinas',
        'C': 'Tuntutan hak berlebih-lebihan hingga merugikan masyarakat keseluruhan',
        'D': 'Penghapusan kebebasan berniaga dan berusaha di sektor swasta',
        'E': 'Pembatasan kepemilikan aset produksi secara sentralistik dan kaku'
    },
    'bedah': {
        'A': 'Kebebasan berpendapat adalah pilar utama yang sangat dijunjung individualisme.',
        'B': 'Kepatuhan total pada penguasa mencerminkan sistem otoritarianisme.',
        'D': 'Individualisme menjamin kebebasan berusaha dan kepemilikan privat.',
        'E': 'Sentralisasi kepemilikan aset merupakan ciri khas sistem kolektivisme sosialis.'
    }
}

fixes[('M5', 3, 69)] = {
    'pilihan': {
        'A': 'Prinsip pemisahan mutlak urusan keagamaan dari hukum positif',
        'B': 'Pemberian kebebasan bersenjata bagi setiap warga sipil masyarakat',
        'C': 'Kepentingan dominasi permodalan investasi korporasi multinasional',
        'D': 'Standar baku konvensi perdagangan bebas internasional kawasan',
        'E': 'Nilai-nilai ketuhanan, kemanusiaan yang beradab, dan keadilan'
    },
    'bedah': {
        'A': 'Pemisahan mutlak agama dari hukum positif adalah asas sekulerisme Barat.',
        'B': 'Kepemilikan senjata api diatur ketat oleh aparat keamanan demi ketertiban.',
        'C': 'HAM di Indonesia berpijak pada kemanusiaan luhur, bukan kepentingan modal.',
        'D': 'Konvensi niaga mengatur pertukaran dagang, bukan landasan filosofis HAM.'
    }
}

fixes[('M5', 3, 70)] = {
    'pilihan': {
        'A': 'Negara kesatuan (unitaris) dan negara serikat (federal)',
        'B': 'Negara feodal bangsawan dan negara protektorat kolonial',
        'C': 'Negara dominion persemakmuran dan negara mandat internasional',
        'D': 'Negara garnisun militer dan negara kepulauan perhimpunan',
        'E': 'Negara kota mandiri dan negara perserikatan pedagang maritim'
    },
    'bedah': {
        'B': 'Negara protektorat adalah status wilayah di bawah perlindungan penjajah.',
        'C': 'Dominion adalah status wilayah bekas koloni imperium Britania.',
        'D': 'Garnisun militer adalah basis pertahanan pasukan bersenjata.',
        'E': 'Negara kota adalah format peradaban masa Yunani kuno (*polis*).'
    }
}

fixes[('M5', 3, 74)] = {
    'pilihan': {
        'A': 'Persaingan bebas antarpemilik modal untuk menguasai pasar komoditas',
        'B': 'Penguasaan cabang produksi strategis oleh korporasi multinasional',
        'C': 'Usaha bersama berlandaskan asas kekeluargaan demi kemakmuran bersama',
        'D': 'Penyerahan tata kelola niaga sepenuhnya kepada mekanisme pasar dunia',
        'E': 'Sistem ekonomi komando sentralistik yang melarang koperasi mandiri'
    },
    'bedah': {
        'A': 'Sistem *free fight liberalism* dilarang keras dalam perekonomian nasional.',
        'B': 'Cabang produksi strategis wajib dikuasai negara, bukan korporasi asing.',
        'D': 'Mekanisme pasar bebas murni mengikis keadilan dan perlindungan warga rentan.',
        'E': 'Koperasi berkedudukan sebagai soko guru perekonomian berdasar UUD 1945.'
    }
}

fixes[('M5', 3, 76)] = {
    'pilihan': {
        'A': 'Dipergunakan untuk sebesar-besar kemakmuran dan kesejahteraan rakyat',
        'B': 'Memperkaya para pejabat pengelola badan usaha milik negara secara pribadi',
        'C': 'Dijadikan agunan jaminan pembayaran cicilan utang luar negeri',
        'D': 'Diberikan konsesi komersial penuh kepada korporasi transnasional',
        'E': 'Ditutup akses pemanfaatannya dari seluruh aktivitas kegiatan industri'
    },
    'bedah': {
        'B': 'Kekayaan alam ditujukan untuk kemakmuran rakyat, bukan perorangan pengurus.',
        'C': 'Kekayaan bumi dan air bukan aset jaminan utang internasional.',
        'D': 'Pemanfaatan oleh swasta/asing dibatasi izin ketat demi kedaulatan negara.',
        'E': 'Pemanfaatan SDA tetap dibuka secara bertanggung jawab dan berkelanjutan.'
    }
}

fixes[('M5', 3, 77)] = {
    'pilihan': {
        'A': 'Mekanisme pasar murni, deregulasi total, dan persaingan modal bebas',
        'B': 'Deregulasian perbankan tanpa pengawasan, rente, dan inflasi bebas',
        'C': 'Sentralisme anggaran, subsidi nol, proteksionisme dogmatis, dan isolasi',
        'D': 'Kebersamaan, efisiensi berkeadilan, berkelanjutan, dan kemandirian',
        'E': 'Oligarki kepemilikan aset, kartel harga, dan ketergantungan impor'
    },
    'bedah': {
        'A': 'Deregulasi total dan pasar murni adalah ciri kapitalisme tanpa batas.',
        'B': 'Pengawasan perbankan dilakukan ketat oleh OJK dan Bank Indonesia.',
        'C': 'Isolasi ekonomi bertentangan dengan kerja sama internasional yang sehat.',
        'E': 'Kartel dan oligarki merugikan konsumen dan dilarang undang-undang persaingan.'
    }
}

fixes[('M5', 3, 78)] = {
    'pilihan': {
        'A': 'Penerapan persaingan pasar bebas tanpa pengawasan regulasi pemerintah',
        'B': 'Pemberian konsesi eksklusif kepada perusahaan konglomerasi swasta',
        'C': 'Kemandirian pangan dan jaminan stabilitas kebutuhan pokok masyarakat',
        'D': 'Ketergantungan impor pangan dari negara-negara tetangga kawasan',
        'E': 'Pengalihan seluruh aset pertanian pangan kepada korporasi perkebunan'
    },
    'bedah': {
        'A': 'Pasar bebas tanpa pengawasan mengancam kedaulatan pangan nasional.',
        'B': 'Konsesi eksklusif memicu ketimpangan ekonomi dan merugikan petani rakyat.',
        'D': 'Ketergantungan impor pangan membahayakan ketahanan pangan nasional.',
        'E': 'Lahan pertanian pangan wajib dilindungi dari alih fungsi komersial.'
    }
}

# =========================================================================
# 4. MODUL 5 PAKET 05 (9 soal: 9, 11, 24, 33, 34, 37, 41, 45, 61)
# =========================================================================

fixes[('M5', 5, 9)] = {
    'pilihan': {
        'A': 'Melindungi segenap bangsa Indonesia dan seluruh tumpah darah Indonesia',
        'B': 'Menguasai jalur pelayaran perdagangan komersial internasional kawasan',
        'C': 'Membentuk persekutuan pertahanan bersenjata dengan kekuatan adidaya',
        'D': 'Menjamin hegemoni politik dan permodalan satu kelompok tertentu',
        'E': 'Menjadikan seluruh kekayaan alam sebagai aset privat aparat negara'
    },
    'bedah': {
        'B': 'Tujuan nasional berfokus pada ketertiban dunia, bukan hegemoni maritim komersial.',
        'C': 'Indonesia memegang prinsip politik luar negeri bebas aktif non-blok.',
        'D': 'Negara hadir melindungi seluruh rakyat tanpa diskriminasi golongan.',
        'E': 'Kekayaan alam dikuasai negara untuk kemakmuran rakyat, bukan aset privat.'
    }
}

fixes[('M5', 5, 11)] = {
    'pilihan': {
        'A': 'Kekuatan persenjataan dan dominasi armada tempur militer terpadu',
        'B': 'Cadangan devisa emas dan kapasitas pembiayaan industri komersial',
        'C': 'Keluasan konsesi wilayah pertambangan dan persaingan niaga bebas',
        'D': 'Kedaulatan rakyat dan musyawarah perwakilan berlandaskan hukum',
        'E': 'Keseragaman latar belakang budaya dan garis keturunan primordial'
    },
    'bedah': {
        'A': 'Kekuatan senjata adalah instrumen pertahanan, bukan fondasi kedaulatan negara.',
        'B': 'Cadangan devisa menopang moneter, bukan dasar ketatanegaraan republik.',
        'C': 'Konsesi pertambangan adalah urusan perizinan ekonomi sektoral.',
        'E': 'Indonesia berlandaskan Bhinneka Tunggal Ika yang menjunjung kemajemukan.'
    }
}

fixes[('M5', 5, 24)] = {
    'pilihan': {
        'A': 'Sentralisasi kepemilikan tanah secara sepihak oleh penguasa militer',
        'B': 'Penyerahan penentuan kebijakan fiskal kepada mekanisme pasar bebas',
        'C': 'Peniadaan seluruh pungutan perpajakan demi kepentingan korporasi',
        'D': 'Kemakmuran bersama yang berkeadilan sosial bagi seluruh rakyat',
        'E': 'Pemberian hak istimewa berusaha bagi pejabat dan keluarganya'
    },
    'bedah': {
        'A': 'Sentralisasi tanah sepihak bertentangan dengan hak konstitusional warga.',
        'B': 'Kebijakan fiskal ditetapkan pemerintah bersama DPR, bukan pasar bebas.',
        'C': 'Pajak merupakan instrumen redistribusi pendapatan untuk pelayanan publik.',
        'E': 'Pemberian privilese kepada keluarga pejabat melanggar tata kelola yang baik.'
    }
}

fixes[('M5', 5, 33)] = {
    'pilihan': {
        'A': 'Pancasila sebagai sumber dari segala sumber hukum negara Republik Indonesia',
        'B': 'Hukum adat tradisional yang berlaku secara terisolasi di tiap daerah',
        'C': 'Peraturan perdagangan internasional yang disahkan badan arbitrase dunia',
        'D': 'Konvensi ketatanegaraan yang dibuat oleh sekelompok pemodal swasta',
        'E': 'Keputusan bersama para ketua umum partai politik pemenang pemilu'
    },
    'bedah': {
        'B': 'Hukum adat diakui sepanjang masih hidup dan sesuai prinsip NKRI.',
        'C': 'Konvensi internasional tunduk pada proses ratifikasi perundang-undangan.',
        'D': 'Hukum nasional bersumber dari konsensus kenegaraan, bukan entitas pemodal.',
        'E': 'Regulasi hukum dibentuk oleh lembaga resmi negara, bukan koalisi partai.'
    }
}

fixes[('M5', 5, 34)] = {
    'pilihan': {
        'A': 'Penguasaan jalur distribusi perdagangan pangan oleh korporasi swasta',
        'B': 'Keadilan sosial dan kemakmuran yang merata bagi seluruh masyarakat',
        'C': 'Pembatasan hak milik perorangan dalam segala bentuk kepemilikan aset',
        'D': 'Penyerahan cabang produksi hajat hidup kepada persaingan pasar bebas',
        'E': 'Pemusatan seluruh pendapatan daerah ke dalam kas operasional pusat'
    },
    'bedah': {
        'A': 'Distribusi bahan pokok diawasi ketat negara guna mencegah spekulasi pasar.',
        'C': 'UUD 1945 tetap mengakui dan melindungi hak milik privat yang sah.',
        'D': 'Cabang produksi vital wajib dikuasai negara berdasar Pasal 33 UUD 1945.',
        'E': 'Hubungan keuangan pusat-daerah dijalankan berimbang melalui transfer TKD.'
    }
}

fixes[('M5', 5, 37)] = {
    'pilihan': {
        'A': 'Penghapusan seluruh fungsi pengawasan dari lembaga audit negara',
        'B': 'Penyerahan tata kelola harga komoditas kepada kartel perdagangan bebas',
        'C': 'Pemberian fasilitas konsesi perikanan tanpa izin kepada kapal asing',
        'D': 'Kedaulatan wilayah, kesejahteraan rakyat, dan ketertiban hukum',
        'E': 'Pemusatan wewenang pemerintahan daerah kepada komando militer'
    },
    'bedah': {
        'A': 'Audit keuangan negara wajib dijalankan secara independen oleh BPK.',
        'B': 'Praktik kartel dilarang undang-undang karena merugikan perekonomian rakyat.',
        'C': 'Pengelolaan perikanan nasional wajib berizin dan mengutamakan nelayan lokal.',
        'E': 'Pemerintahan daerah menganut asas desentralisasi dan otonomi yang luas.'
    }
}

fixes[('M5', 5, 41)] = {
    'pilihan': {
        'A': 'Mengesahkan undang-undang perpajakan tanpa melibatkan persetujuan dari DPR',
        'B': 'Menerbitkan obligasi daerah otonom tanpa melalui pengawasan kementerian keuangan',
        'C': 'Menetapkan struktur tarif bea masuk komoditas perdagangan lintas kepulauan',
        'D': 'Menguji keabsahan materi undang-undang terhadap undang-undang dasar',
        'E': 'Mengajukan RUU bidang otonomi dan perimbangan keuangan ke DPR'
    },
    'bedah': {
        'A': 'Persetujuan UU perpajakan adalah wewenang DPR bersama Presiden.',
        'B': 'Penerbitan obligasi daerah wajib mematuhi persetujuan Menkeu dan DPRD.',
        'C': 'Tarif kepabeanan merupakan domain kebijakan fiskal pemerintah pusat.',
        'D': 'Pengujian undang-undang terhadap konstitusi adalah wewenang Mahkamah Konstitusi.'
    }
}

fixes[('M5', 5, 45)] = {
    'pilihan': {
        'A': 'Kebijakan pasar bebas tanpa batas yang menghapus proteksi ekonomi rakyat',
        'B': 'Penutupan akses pendidikan formal bagi masyarakat di pelosok pedalaman',
        'C': 'Pembatasan partisipasi politik perempuan dalam pemilihan umum legislatif',
        'D': 'Pemusatan fasilitas kesehatan modern khusus bagi aparatur sipil pusat',
        'E': 'Mencerdaskan kehidupan bangsa dan memajukan kesejahteraan umum'
    },
    'bedah': {
        'A': 'Pasar bebas tanpa batas mengancam pelaku usaha mikro dan ekonomi kerakyatan.',
        'B': 'UUD 1945 mewajibkan negara menjamin hak pendidikan bagi setiap warga negara.',
        'C': 'Konstitusi menjamin kesetaraan hak politik bagi seluruh warga negara.',
        'D': 'Layanan jaminan kesehatan wajib diberikan adil kepada seluruh masyarakat.'
    }
}

fixes[('M5', 5, 61)] = {
    'pilihan': {
        'A': 'Menetapkan perolehan suara resmi secara mendahului sebelum rekapitulasi tuntas',
        'B': 'Memutus perselisihan hasil pemilihan umum dengan putusan final dan mengikat',
        'C': 'Menyelesaikan sengketa pelanggaran administrasi kampanye pemilu di daerah',
        'D': 'Membekukan kepengurusan komisi pemilihan umum di seluruh wilayah kepulauan',
        'E': 'Membatalkan seluruh tahapan pemilu secara nasional saat terjadi ketegangan'
    },
    'bedah': {
        'A': 'Penetapan perolehan suara merupakan wewenang mutlak KPU setelah pleno tuntas.',
        'C': 'Pelanggaran administrasi dan kode etik kampanye ditangani oleh Bawaslu dan DKPP.',
        'D': 'MK tidak berwenang membekukan pengurus penyelenggara pemilu (KPU).',
        'E': 'MK hanya mengadili perkara perselisihan hasil, bukan membatalkan pemilu nasional.'
    }
}

# =========================================================================
# 5. MODUL 5 PAKET 06 (8 soal: 1, 23, 27, 35, 39, 41, 63, 66)
# =========================================================================

fixes[('M5', 6, 1)] = {
    'pilihan': {
        'A': 'Pembubaran dan penggabungan negara bagian diatur pasal 43 dan 49 RIS',
        'B': 'Peleburan wilayah serikat diputuskan sekehendak perwakilan militer sekutu',
        'C': 'Peleburan negara serikat harus disetujui langsung oleh parlemen Den Haag',
        'D': 'Negara bagian diwajibkan mempertahankan batas teritorialnya secara permanen',
        'E': 'Pemerintah federal berhak membekukan dewan perwakilan daerah'
    },
    'bedah': {
        'B': 'Penyatuan kembali RIS adalah kehendak sah rakyat, bukan instruksi sekutu.',
        'C': 'Indonesia telah berdaulat penuh dan tidak memerlukan izin parlemen Belanda.',
        'D': 'Konstitusi RIS secara terbuka membuka opsi penggabungan dengan Republik.',
        'E': 'Pembekuan dewan daerah bertentangan dengan prinsip demokrasi federalis saat itu.'
    }
}

fixes[('M5', 6, 23)] = {
    'pilihan': {
        'A': 'Tahun 1966 bersamaan dengan terbitnya surat perintah sebelas maret',
        'B': 'Tahun 1955 pada masa transisi berlakunya undang-undang dasar sementara',
        'C': 'Tahun 1973 bertepatan dengan penetapan garis-garis besar haluan negara',
        'D': 'Tahun 1968 menjelang sidang umum majelis permusyawaratan darurat',
        'E': 'Tahun 1971 sebagai pesta pemilu perdana pada masa pemerintahan Orde Baru'
    },
    'bedah': {
        'A': 'Tahun 1966 adalah momentum peralihan kekuasaan melalui Supersemar.',
        'B': 'Tahun 1955 adalah pemilu pertama di Indonesia pada masa Demokrasi Parlementer.',
        'C': 'Tahun 1973 adalah sidang MPR yang menetapkan GBHN pertama era Orba.',
        'D': 'Tahun 1968 adalah momentum pengangkatan Soeharto sebagai presiden definitif.'
    }
}

fixes[('M5', 6, 27)] = {
    'pilihan': {
        'A': 'Pemberlakuan sistem kabinet parlementer dengan menteri bertanggung jawab ke parlemen',
        'B': 'Pemisahan kekuasaan negara yang menempatkan dewan perwakilan sebagai otoritas tertinggi',
        'C': 'Peniadaan masa jabatan kepresidenan dan penunjukan kepala negara secara bergiliran',
        'D': 'Pemusatan kekuasaan pada presiden serta stabilitas politik dan pembangunan ekonomi',
        'E': 'Dominasi kekuasaan eksekutif yang membatasi ruang partisipasi oposisi bebas'
    },
    'bedah': {
        'A': 'Sistem parlementer berlaku pada masa Republik Indonesia Serikat dan UUDS 1950.',
        'B': 'Orde Baru berlandaskan sistem presidensial dengan kekuasaan eksekutif dominan.',
        'C': 'Konstitusi UUD 1945 tetap mengatur masa jabatan presiden selama lima tahunan.',
        'E': 'Pembatasan oposisi adalah ekses politik, bukan karakteristik normatif buku teks.'
    }
}

fixes[('M5', 6, 35)] = {
    'pilihan': {
        'A': 'Tanggal 11 Maret 1966 saat surat perintah pengamanan negara diterbitkan',
        'B': 'Tanggal 19 Oktober 1999 saat pengesahan naskah amandemen pertama konstitusi',
        'C': 'Tanggal 21 Mei 1998 saat pernyataan berhenti Presiden Soeharto dibacakan',
        'D': 'Tanggal 17 Agustus 1950 saat pembubaran negara serikat hasil konferensi',
        'E': 'Tanggal 5 Juli 1959 saat pembacaan dekrit pemberlakuan kembali konstitusi'
    },
    'bedah': {
        'A': 'Supersemar 1966 merupakan awal masa peralihan menuju pemerintahan Orde Baru.',
        'B': 'Amandemen pertama UUD 1945 disahkan pada era Reformasi tahun 1999.',
        'D': 'Pembubaran RIS pada 17 Agustus 1950 mengawali era Demokrasi Liberal.',
        'E': 'Dekrit Presiden 5 Juli 1959 merupakan penanda lahirnya masa Demokrasi Terpimpin.'
    }
}

fixes[('M5', 6, 39)] = {
    'pilihan': {
        'A': 'Kewenangan membuat undang-undang dialihkan sepenuhnya kepada mahkamah agung',
        'B': 'Dewan Perwakilan Rakyat memegang kekuasaan membentuk undang-undang',
        'C': 'Kekuasaan presiden menetapkan rancangan aturan kedinasan tanpa persetujuan dewan',
        'D': 'Kewenangan legislasi diserahkan kepada dewan pertimbangan agung kementerian',
        'E': 'Undang-undang hanya dapat disahkan setelah disetujui dewan pimpinan daerah'
    },
    'bedah': {
        'A': 'Mahkamah Agung menjalankan fungsi yudikatif, bukan membentuk undang-undang.',
        'C': 'Pasal 20 ayat (1) justru menggeser kekuasaan pembentukan UU dari Presiden ke DPR.',
        'D': 'Dewan Pertimbangan Agung (DPA) telah resmi dihapuskan dalam amandemen konstitusi.',
        'E': 'Persetujuan bersama pembentukan UU dilakukan oleh DPR bersama Presiden.'
    }
}

fixes[('M5', 6, 41)] = {
    'pilihan': {
        'A': 'Kedaulatan berada penuh di tangan majelis permusyawaratan sebagai penjelmaan rakyat',
        'B': 'Kedaulatan diserahkan kepada keputusan gabungan pimpinan partai politik nasional',
        'C': 'Kedaulatan dijalankan sepenuhnya oleh kepala negara dibantu aparat bersenjata',
        'D': 'Kedaulatan rakyat dibatasi hanya pada pelaksanaan pemilihan kepala desa serentak',
        'E': 'Kedaulatan berada di tangan rakyat dan dilaksanakan menurut Undang-Undang Dasar'
    },
    'bedah': {
        'A': 'MPR sebagai pemegang kedaulatan mutlak adalah rumusan UUD 1945 pra-amandemen.',
        'B': 'Partai politik adalah pilar demokrasi, bukan pemilik kedaulatan negara.',
        'C': 'Sistem ketatanegaraan Indonesia menolak konsentrasi kedaulatan pada individu presiden.',
        'D': 'Kedaulatan rakyat mencakup hak politik penuh dalam pemilu legislatif dan presiden.'
    }
}

fixes[('M5', 6, 63)] = {
    'pilihan': {
        'A': 'Tanggal 19 Oktober 1999 pada penutupan sidang umum pertama reformasi',
        'B': 'Tanggal 9 November 2001 saat pembentukan mahkamah konstitusi disetujui',
        'C': 'Tanggal 18 Agustus 2000 bertepatan dengan pengesahan bab hak asasi manusia',
        'D': 'Tanggal 10 Agustus 2002 pada penutupan Sidang Tahunan Majelis Permusyawaratan Rakyat',
        'E': 'Tanggal 17 Agustus 1950 saat pembubaran negara serikat hasil perundingan'
    },
    'bedah': {
        'A': '19 Oktober 1999 merupakan pengesahan Perubahan Pertama UUD 1945.',
        'B': '9 November 2001 merupakan pengesahan Perubahan Ketiga UUD 1945.',
        'C': '18 Agustus 2000 merupakan pengesahan Perubahan Kedua UUD 1945.',
        'E': '17 Agustus 1950 adalah momentum kembalinya Indonesia ke bentuk NKRI.'
    }
}

fixes[('M5', 6, 66)] = {
    'pilihan': {
        'A': 'Penyerahan tata kelola ekonomi kepada mekanisme pasar internasional bebas',
        'B': 'Menambahkan prinsip efisiensi berkeadilan dan kepedulian lingkungan hidup',
        'C': 'Menghapuskan fungsi kepemilikan negara atas seluruh badan usaha milik umum',
        'D': 'Mewajibkan penggunaan mata uang asing dalam seluruh transaksi perdagangan',
        'E': 'Membatasi keikutsertaan koperasi dalam pengelolaan sumber kekayaan alam'
    },
    'bedah': {
        'A': 'Ekonomi Indonesia berlandaskan keadilan sosial, menolak kapitalisme pasar bebas murni.',
        'C': 'Pasal 33 tetap mengamanatkan penguasaan negara atas cabang produksi strategis.',
        'D': 'Rupiah merupakan alat pembayaran sah yang wajib digunakan di wilayah NKRI.',
        'E': 'Koperasi tetap ditempatkan sebagai soko guru perekonomian nasional.'
    }
}

# =========================================================================
# 6. MODUL 5 PAKET 07 (10 soal: 17, 27, 29, 34, 46, 55, 67, 69, 72, 85)
# =========================================================================

fixes[('M5', 7, 17)] = {
    'pilihan': {
        'A': 'Aspek kepemilikan modal dan saham perbankan komersial',
        'B': 'Aspek kepangkatan aparatur militer dan pegawai sipil',
        'C': 'Aspek kehidupan nasional secara utuh menyeluruh lahir batin',
        'D': 'Aspek kekuatan armada industri pertambangan dan pelayaran',
        'E': 'Aspek penguasaan teknologi digital multinasional kawasan'
    },
    'bedah': {
        'A': 'Aspek modal perbankan adalah instrumen moneter, bukan konsep keutuhan bangsa.',
        'B': 'Hierarki kepangkatan adalah sistem manajerial ASN, bukan kesatuan wawasan.',
        'D': 'Armada pertambangan adalah sektor industri komersial ekstraktif.',
        'E': 'Teknologi digital adalah sarana informasi, bukan dasar falsafah kenegaraan.'
    }
}

fixes[('M5', 7, 27)] = {
    'pilihan': {
        'A': 'Penghapusan seluruh perbedaan adat istiadat suku bangsa kedaerahan',
        'B': 'Penyeragam bahasa percakapan sehari-hari di seluruh pelosok wilayah',
        'C': 'Peleburan kebudayaan daerah menjadi satu model budaya seragam tunggal',
        'D': 'Mempertahankan kemajemukan bangsa dan menjadikannya perekat persatuan',
        'E': 'Penutupan akses pertukaran budaya antardaerah demi kemurnian tradisi'
    },
    'bedah': {
        'A': 'Persatuan Indonesia tidak menghapuskan keanekaragaman adat istiadat nusantara.',
        'B': 'Bahasa daerah tetap dilestarikan sebagai kekayaan budaya nasional bangsa.',
        'C': 'Kebudayaan nasional bersumber dari puncak-puncak kebudayaan daerah.',
        'E': 'Interaksi budaya antardaerah justru memperkuat persaudaraan kebangsaan.'
    }
}

fixes[('M5', 7, 29)] = {
    'pilihan': {
        'A': 'Menjalani misinya di tengah kehidupan pergaulan antarbangsa dunia',
        'B': 'Mendominasi kepemilikan modal perbankan di kawasan asia tenggara',
        'C': 'Membentuk pakta pertahanan militer bersenjata yang bersifat eksklusif',
        'D': 'Membentuk aliansi ekonomi regional yang bersifat tertutup dan proteksionis',
        'E': 'Menyaingi kekuatan persenjataan nuklir negara adidaya dunia'
    },
    'bedah': {
        'B': 'Indonesia tidak berorientasi pada dominasi hegemoni permodalan regional.',
        'C': 'Politik bebas aktif melarang Indonesia bergabung dalam pakta militer pertahanan.',
        'D': 'Indonesia aktif mendorong kerja sama ekonomi terbuka yang saling menguntungkan.',
        'E': 'Indonesia secara konsisten menentang proliferasi dan penggunaan senjata nuklir.'
    }
}

fixes[('M5', 7, 34)] = {
    'pilihan': {
        'A': 'Kewajiban warga miskin mengganti dana bantuan melalui program kerja paksa',
        'B': 'Wujud komitmen konstitusional dalam menjamin perlindungan sosial warga',
        'C': 'Penyerahan penanganan kemiskinan sepenuhnya kepada inisiatif lembaga swasta',
        'D': 'Pemberian pinjaman uang berbunga tinggi dengan agunan sertifikat tanah',
        'E': 'Pembatasan pemberian bantuan hanya bagi warga yang tinggal di ibu kota'
    },
    'bedah': {
        'A': 'Bansos adalah hak jaminan sosial warga, bukan skema transaksi kerja paksa.',
        'C': 'Negara bertanggung jawab utama atas perlindungan fakir miskin (Pasal 34 UUD 1945).',
        'D': 'Bansos disalurkan secara cuma-cuma tanpa skema bunga perbankan komersial.',
        'E': 'Bantuan sosial disalurkan berkeadilan ke seluruh pelosok tanah air.'
    }
}

fixes[('M5', 7, 46)] = {
    'pilihan': {
        'A': 'Kepatuhan penuh pada arahan kebijakan ekonomi korporasi pendonor modal',
        'B': 'Penerapan teknologi modern dengan menyingkirkan kearifan budaya lokal',
        'C': 'Penyerahan tata kelola sumber daya strategis kepada konsesi modal luar',
        'D': 'Ketergantungan struktural pada skema pinjaman komersial lembaga asing',
        'E': 'Kemandirian nasional dan pemanfaatan potensi keanekaragaman bangsa'
    },
    'bedah': {
        'A': 'Pembangunan nasional berpedoman pada haluan negara, bukan kehendak donor.',
        'B': 'Modernisasi teknologi wajib diselaraskan dengan kearifan nilai luhur bangsa.',
        'C': 'Sumber daya strategis dikelola secara mandiri demi sebesar-besar kemakmuran rakyat.',
        'D': 'Kemandirian menuntut pengurangan ketergantungan utang luar negeri bertahap.'
    }
}

fixes[('M5', 7, 55)] = {
    'pilihan': {
        'A': 'Sikap chauvinisme yang merendahkan harkat dan martabat bangsa-bangsa lain',
        'B': 'Sikap isolasionis yang menolak kerja sama internasional dengan negara luar',
        'C': 'Pemberian konsesi eksploitasi alam seluas-luasnya demi penerimaan instan',
        'D': 'Penyelarasan seluruh nilai budaya bangsa dengan standar budaya barat modern',
        'E': 'Kesiagaan membela kedaulatan negara dan menjaga kelestarian lingkungan'
    },
    'bedah': {
        'A': 'Nasionalisme Indonesia berwatak kemanusiaan, menentang paham chauvinisme sempit.',
        'B': 'Indonesia aktif berpartisipasi mewujudkan perdamaian dunia dalam pergaulan global.',
        'C': 'Pemanfaatan alam wajib mengedepankan prinsip keberlanjutan ekologis jangka panjang.',
        'D': 'Bangsa Indonesia menjunjung tinggi kepribadian dan martabat kebudayaan sendiri.'
    }
}

fixes[('M5', 7, 67)] = {
    'pilihan': {
        'A': 'Semangat kosmopolitanisme global dan kebebasan perilaku perorangan',
        'B': 'Asas efisiensi ekonomi modern dan persaingan permodalan korporasi',
        'C': 'Martabat manusia, cinta tanah air, demokrasi, dan solidaritas sosial',
        'D': 'Kepatuhan mutlak tradisi leluhur dan penguatan norma komunal tertutup',
        'E': 'Kemandirian keluarga inti dan etos kerja profesional tanpa batas'
    },
    'bedah': {
        'A': 'Kosmopolitanisme tanpa akar nasionalisme melunturkan kepedulian pada bangsa.',
        'B': 'Efisiensi modal adalah ukuran ekonomi bisnis, bukan nilai luhur integrasi jiwa.',
        'D': 'Ketertutupan komunal menghambat dinamika kemajuan persatuan antarsuku.',
        'E': 'Fokus keluarga semata dapat memicu sikap individualistik yang mengikis gotong royong.'
    }
}

fixes[('M5', 7, 69)] = {
    'pilihan': {
        'A': 'Alasan untuk menuntut otonomi khusus tanpa integrasi hukum nasional',
        'B': 'Alat pembuktian keunggulan budaya satu kelompok di atas suku lainnya',
        'C': 'Bahan persaingan dagang dan penutupan pasar lokal dari suku tetangga',
        'D': 'Dasar penentuan kuota perwakilan politik di lembaga tinggi negara',
        'E': 'Kekayaan khazanah budaya yang memperkokoh persatuan dan kesatuan bangsa'
    },
    'bedah': {
        'A': 'Otonomi daerah dijalankan dalam kerangka keutuhan Negara Kesatuan Republik Indonesia.',
        'B': 'Kemajemukan bukan untuk mengadu superioritas antarsuku atau antargolongan.',
        'C': 'Persaingan dagang antardaerah harus sehat dan saling menopang ekonomi nasional.',
        'D': 'Keterwakilan politik didasarkan pada aspirasi rakyat melalui pemilu demokratis.'
    }
}

fixes[('M5', 7, 72)] = {
    'pilihan': {
        'A': 'Mengutamakan keunggulan bangsa sendiri dengan meremehkan negara-negara lain',
        'B': 'Dominasi penguasaan aset-aset ekonomi strategis di tingkat internasional',
        'C': 'Penyebaran paham ideologi nasional kepada perhimpunan bangsa-bangsa dunia',
        'D': 'Pembangunan persaudaraan universal dan perdamaian abadi antarbangsa',
        'E': 'Penolakan terhadap seluruh bentuk bantuan kemanusiaan dari luar negeri'
    },
    'bedah': {
        'A': 'Cinta tanah air di Indonesia menolak chauvinisme yang meremehkan bangsa lain.',
        'B': 'Tujuan nasional tidak berorientasi pada ekspansi dominasi kapital luar negeri.',
        'C': 'Pancasila bukan ideologi ekspansionis yang dipaksakan kepada negara lain.',
        'E': 'Indonesia menjunjung tinggi solidaritas kemanusiaan lintas batas bangsa.'
    }
}

fixes[('M5', 7, 85)] = {
    'pilihan': {
        'A': 'Mengabaikan kegiatan sosial kemasyarakatan demi menjaga jarak kedinasan',
        'B': 'Mengutamakan kepentingan bisnis keluarga di atas kepentingan lingkungan',
        'C': 'Menjadi pelopor keteladanan gotong royong, toleransi, dan kerukunan',
        'D': 'Membatasi interaksi sosial hanya dengan sesama rekan aparatur pemerintah',
        'E': 'Menuntut perlakuan istimewa dalam setiap musyawarah warga rukun tetangga'
    },
    'bedah': {
        'A': 'Aparatur negara harus membaur dan menjadi teladan di lingkungan tempat tinggalnya.',
        'B': 'Kepentingan umum dan kerukunan warga harus senantiasa diutamakan.',
        'D': 'Eksklusivitas pergaulan memicu jarak sosial antara aparatur dan rakyat.',
        'E': 'Aparatur sipil tidak boleh meminta privilese sosial di masyarakat sekitar.'
    }
}

print(f"Total fixes defined: {len(fixes)} questions.")

# Execution targets mapping: (mod_code, pkt_num) -> (json_path, php_path, default_sumber, category, sub)
targets = {
    ('M4', 5): (
        'soal_pengelolaan_keuangan_negara_json/4. Pengelolaan Keuangan Negara_paket_05.json',
        'soal_pengelolaan_keuangan_negara_php/STANGOLDEN_Pengelolaan_Keuangan_Negara_Paket_05_Hlm_55-64.php',
        'Pengelolaan Keuangan Negara', 'PKN', 'Pengelolaan Keuangan Negara — Paket 05'
    ),
    ('M5', 2): (
        'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_02.json',
        'soal_wawasan_kebangsaan_php/STANGOLDEN_Wawasan_Kebangsaan_Paket_02_Hlm_16-25.php',
        'Wawasan Kebangsaan', 'WK', 'Wawasan Kebangsaan — Paket 02'
    ),
    ('M5', 3): (
        'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_03.json',
        'soal_wawasan_kebangsaan_php/STANGOLDEN_Wawasan_Kebangsaan_Paket_03_Hlm_26-35.php',
        'Wawasan Kebangsaan', 'WK', 'Wawasan Kebangsaan — Paket 03'
    ),
    ('M5', 5): (
        'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_05.json',
        'soal_wawasan_kebangsaan_php/STANGOLDEN_Wawasan_Kebangsaan_Paket_05_Hlm_46-55.php',
        'Wawasan Kebangsaan', 'WK', 'Wawasan Kebangsaan — Paket 05'
    ),
    ('M5', 6): (
        'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_06.json',
        'soal_wawasan_kebangsaan_php/STANGOLDEN_Wawasan_Kebangsaan_Paket_06_Hlm_56-65.php',
        'Wawasan Kebangsaan', 'WK', 'Wawasan Kebangsaan — Paket 06'
    ),
    ('M5', 7): (
        'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_07.json',
        'soal_wawasan_kebangsaan_php/STANGOLDEN_Wawasan_Kebangsaan_Paket_07_Hlm_66-75.php',
        'Wawasan Kebangsaan', 'WK', 'Wawasan Kebangsaan — Paket 07'
    )
}

letter_to_idx = {'A': 0, 'B': 1, 'C': 2, 'D': 3, 'E': 4}

for (mod_code, pkt_num), (json_path, php_path, def_sumber, cat, sub_title) in targets.items():
    print(f"\nProcessing [{mod_code}] Paket {pkt_num}...")
    
    with open(json_path, 'r', encoding='utf-8') as f:
        jdata = json.load(f)
        
    soal_list = jdata['daftar_soal']
    updated_in_packet = 0
    
    for q in soal_list:
        no = q['nomor']
        if (mod_code, pkt_num, no) in fixes:
            fix = fixes[(mod_code, pkt_num, no)]
            q['pilihan'] = fix['pilihan']
            if 'pembahasan' not in q:
                q['pembahasan'] = {}
            if 'bedah_pengecoh' not in q['pembahasan']:
                q['pembahasan']['bedah_pengecoh'] = {}
            for ltr, b_txt in fix['bedah'].items():
                q['pembahasan']['bedah_pengecoh'][ltr] = b_txt
            updated_in_packet += 1
            
    print(f"  Updated {updated_in_packet} questions in JSON.")
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(jdata, f, ensure_ascii=False, indent=2)
        
    # Now rebuild PHP questions array and keys
    questions_list = []
    stg_keys = []
    
    for item in soal_list:
        kunci = item['kunci_jawaban']
        kunci_idx = letter_to_idx[kunci]
        stg_keys.append(kunci_idx)
        
        topik = item.get('topik_materi', '')
        pemb = item.get('pembahasan', {})
        konsep = pemb.get('konsep_materi', '')
        konsep_html = re.sub(r'\*\*(.*?)\*\*', r'<strong>\1</strong>', konsep)
        alasan = pemb.get('alasan_kunci', '')
        sumber = pemb.get('sumber_rujukan', def_sumber)
        hlm = item.get('halaman_sumber', '')
        hlm_str = f"{hlm:02d}" if isinstance(hlm, int) else str(hlm)
        kata_kunci = ' • '.join(item.get('kata_kunci_modul', []))
        opsi_tepat = item['pilihan'][kunci]
        
        bedah_html_items = []
        for ltr in ['A', 'B', 'C', 'D', 'E']:
            if ltr != kunci:
                pengecoh = pemb.get('bedah_pengecoh', {}).get(ltr, '')
                bedah_html_items.append(f'<li><strong>{ltr}</strong>: {pengecoh}</li>')
        bedah_ul = ''.join(bedah_html_items)
        
        explanation_html = (
            f'<div class="learn-box lesson-card">'
            f'<div class="learn-title">📚 Intisari Materi • {topik}</div>'
            f'<div class="lesson-section"><div class="lesson-kicker">01 · Konsep Inti Textbook</div>'
            f'<p>{konsep_html}</p></div>'
            f'<div class="lesson-section lesson-reason"><div class="lesson-kicker">02 · Alasan Kunci Jawaban (AREL)</div>'
            f'<p>{alasan}</p>'
            f'<p>Jawaban tepat: <strong>{kunci}. {opsi_tepat}</strong></p></div>'
            f'<div class="lesson-section"><div class="lesson-kicker">03 · Bedah Opsi Lainnya</div>'
            f'<ul style="margin:6px 0 0;padding-left:20px;line-height:1.6">{bedah_ul}</ul></div>'
            f'<div class="lesson-source">📖 Referensi: {sumber} (Hlm. {hlm_str}) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>{kata_kunci}</strong></div></div>'
        )
        
        q_obj = {
            "category": cat,
            "sub": sub_title,
            "difficulty": item.get('level_kognitif', 'C2 - Pemahaman'),
            "question": item['pertanyaan'],
            "options": [item['pilihan']['A'], item['pilihan']['B'], item['pilihan']['C'], item['pilihan']['D'], item['pilihan']['E']],
            "answer": kunci_idx,
            "source": sumber,
            "explanation": explanation_html
        }
        questions_list.append(q_obj)
        
    with open(php_path, 'r', encoding='utf-8') as f:
        php_content = f.read()
        
    # Replace const STG_KEY
    new_keys_str = f"const STG_KEY = {json.dumps(stg_keys)};"
    php_content = re.sub(r'const STG_KEY\s*=\s*\[.*?\];', new_keys_str, php_content, count=1)
    
    # Replace const questions
    pStart = php_content.find('const questions=[')
    pEnd = php_content.find('}];', pStart)
    if pStart == -1 or pEnd == -1:
        print(f"Error finding const questions in {php_path}")
        continue
        
    new_q_json = 'const questions=' + json.dumps(questions_list, ensure_ascii=False) + ';'
    php_content = php_content[:pStart] + new_q_json + php_content[pEnd + 3:]
    
    with open(php_path, 'w', encoding='utf-8') as f:
        f.write(php_content)
        
    res = subprocess.run(['php', '-l', php_path], capture_output=True, text=True)
    if 'No syntax errors' in res.stdout:
        print(f"  [OK] PHP rebuilt & syntax valid: {os.path.basename(php_path)}")
    else:
        print(f"  [SYNTAX ERROR] {os.path.basename(php_path)}: {res.stderr}")

print("\nAll threshold fixes applied successfully!")

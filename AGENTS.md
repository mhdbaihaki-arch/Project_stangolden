# PANDUAN & STANDAR OPERASIONAL PEMBUATAN SOAL PROFESIONAL (UPKP KEMENKEU)

Dokumen ini adalah **Standar Operasional Prosedur (SOP) Mutlak** bagi Agen Pembuat Soal pada proyek **STANGOLDEN**.

## 🎯 Filosofi Utama Produk: "Belajar Tuntas Textbook Lewat Soal"

Tujuan utama pembuatan bank soal ini adalah **agar peserta yang enggan atau tidak sempat membaca buku teks/modul tebal dapat menguasai seluruh isi textbook secara utuh cukup dengan mengerjakan soal dan membaca pembahasannya (*Learn-by-Testing / Active Recall*)**.

Oleh karena itu, setiap butir soal **WAJIB** memenuhi 4 karakter utama berikut:
1. **100% Fokus Isi Textbook (*Strict Textbook Focus*)** — Menguji langsung konsep, definisi ahli, unsur, ciri, tahapan, nilai dasar, dan ketentuan aturan sebagaimana tertulis di buku teks. **DILARANG terlalu analitis, filosofis, atau melebar dari isi buku.**
2. **Ringkas, Padat, & Anti-Bosan (*Short & Crisp Readability*)** — Karena 1 paket berisi **100 soal**, kalimat pertanyaan dan pilihan jawaban **WAJIB pendek dan langsung ke inti** agar peserta tidak pusing atau lelah membaca.
3. **Tingkat Kesulitan Sedang-Pas (*Balanced Mastery / MOTS*)** — **Tidak perlu dipaksakan HOTS berat** yang berbelit-belit, tetapi **tidak boleh terlalu gampang/murahan**. Ketepatan uji dijaga melalui pilihan jawaban yang ringkas namun setara dan mengecoh secara konseptual.
4. **Mudah Dicek ke Buku Teks (`kata_kunci_modul`)** — Setiap butir soal di JSON wajib menyertakan kata/frasa kunci eksak dari halaman sumber agar reviewer dapat langsung melakukan pencarian (`Ctrl+F`) di buku teks/modul.

---

## ✂️ 1. Aturan Keringkasan & Anti-Pusing (*Strict Brevity Rules*)

Membaca 100 soal yang panjang akan membuat peserta bosan dan kelelahan. Patuhi batas panjang berikut secara ketat:

1. **Panjang Pokok Soal (`pertanyaan`)**:
   - Gunakan **1 kalimat lugas** (atau maksimal **2 kalimat pendek** jika menggunakan ilustrasi penerapan).
   - **Target ideal: 10–24 kata** (**Batas maksimum mutlak: 32 kata / 220 karakter**).
   - **DILARANG** menulis kalimat pembuka basa-basi (*fluff preamble*) seperti: *"Dalam meletakkan fondasi pemahaman mengenai perilaku manusia di era birokrasi modern..."*. Langsung tanyakan intinya!
2. **Panjang Pilihan Jawaban (`pilihan` A–E)**:
   - **Target ideal: 2–8 kata per opsi** (**Batas maksimum mutlak: 12 kata / 80 karakter**).
   - Jika yang diuji adalah nama tokoh, istilah konsep, tahapan, atau kata sifat kunci di buku teks, tulis langsung frasa intinya tanpa anak kalimat panjang yang berulang-ulang.
3. **Gaya Bahasa Lugas & Setia Istilah Buku**:
   - Gunakan **kata dan istilah asli dari textbook** apa adanya (misal: *"masif, terstruktur, dan kontinu"*, *"berpikir, bertindak, dan berperilaku"*, *"attention to detail"*).
   - **DILARANG** mengganti istilah kunci buku teks dengan sinonim abstrak/akademis rumit yang justru membuat peserta gagal mengenali istilah asli saat ujian resmi.

---

## 📚 2. Fokus Materi Textbook & Proporsi Level Kognitif

### A. Strategi "Sapu Bersih 1 Halaman dalam 10 Soal" (*Sequential Page Mastery*)
Agar peserta yang tidak membaca buku tetap menguasai seluruh isi halaman:
1. Petakan isi 1 halaman `modul_json/[Nama_Modul].json` secara berurutan dari **paragraf atas hingga paragraf bawah**.
2. Pastikan ke-10 soal pada halaman tersebut merangkum **seluruh definisi, nama ahli, daftar rincian/poin, syarat aturan, pasal, hingga contoh** yang ada di halaman itu tanpa ada poin penting yang terlewat dan tanpa pengulangan inti soal.

### B. Proporsi Level Kognitif yang Pas (*Tidak HOTS Berat, Tidak Terlalu Gampang*)
Gunakan distribusi level kognitif yang fokus pada penguasaan materi buku teks secara nyaman:
1. **`C2 - Pemahaman` (~50% / 5 soal per halaman)**:
   - Menguji langsung penguasaan definisi konsep, tokoh pencetus, unsur pembentuk, karakteristik/sifat, fungsi, tujuan, atau bunyi ketentuan di buku teks secara lugas.
2. **`C3 - Aplikasi` (~35% / 3–4 soal per halaman)**:
   - Menguji penerapan konsep, panduan perilaku, atau aturan buku teks melalui **ilustrasi kerja 1 kalimat pendek** yang sederhana dan membumi.
3. **`C4 - Analisis` (~15% / 1–2 soal per halaman)**:
   - Menguji pembedaan ringkas antara dua konsep serumpun di buku teks atau soal identifikasi pengecualian (**KECUALI / BUKAN**) atas poin-poin rincian buku teks.
4. **LARANGAN `C5` Berat / Studi Kasus Panjang**:
   - **DILARANG** membuat soal evaluasi/studi kasus birokrasi panjang (3–5 kalimat) yang terlalu analitis dan mengaburkan fokus belajar textbook.

---

## 🔑 3. Atribut Wajib `kata_kunci_modul` (Pelacak Cepat ke Textbook)

Untuk memastikan 100% soal benar-benar bersumber dari buku teks sekaligus memudahkan pemeriksaan (*review*):
1. Setiap objek soal di dalam `daftar_soal` **WAJIB** memuat atribut `"kata_kunci_modul"` berupa *array of strings* berisi **1 s.d. 3 frasa pendek (masing-masing 2–6 kata)**.
2. Setiap frasa di dalam `"kata_kunci_modul"` **WAJIB berupa kutipan teks eksak (*exact verbatim substring*)** yang tertulis persis pada halaman terkait di `modul_json/[Nama_Modul].json`, sehingga ketika dicari dengan **`Ctrl+F`** di PDF atau JSON modul langsung ditemukan 100% cocok.
3. **Contoh yang Benar**:
   - `"kata_kunci_modul": ["Gordon Allport", "melakukan tindakan berdasarkan pilihannya"]`
   - `"kata_kunci_modul": ["masif, terstruktur, dan kontinu", "tidak hanya dianggap sebagai pajangan"]`

---

## ⚖️ 4. Kaidah Pilihan Jawaban: Setara & Anti-Bias Positif vs Negatif (*Zero Valence Bias*)

Soal yang pendek dan fokus textbook **tetap tidak boleh terlalu gampang ditebak** hanya dari konotasi kalimat baik vs buruk.

### A. Hukum Kesetaraan Valensi (Nada Opsi Sama-Sama Positif/Netral)
1. Kelima opsi (**A, B, C, D, E**) **WAJIB bernada setara** (sama-sama positif, normatif, atau netral-konseptual).
2. **DILARANG KERAS** membuat 1 kunci jawaban positif lalu 4 pengecoh bernada negatif, jahat, malas, konyol, atau melanggar aturan sehingga orang yang buta materi pun langsung tahu jawabannya.
3. **Daftar Kata Terlarang pada Pengecoh (*Distractor Blacklist*)**:
   - ❌ *mengabaikan, menolak, memalsukan, korupsi, pungli, membiarkan, lepas tangan, menyembunyikan, melanggar, seenaknya, sepihak, semena-mena, pasif, kaku, ego sektoral, menutup akses, menghindari, melempar tanggung jawab, hanya, semata-mata, tidak pernah.*

### B. Cara Membuat Pengecoh Pendek yang Tetap Berbobot (Dari Textbook)
Gunakan istilah atau konsep nyata dari buku teks sebagai pengecoh agar peserta ikut mengenal istilah lain di modul:
1. **Tukar Tokoh / Istilah Sejajar (*Horizontal Swap*)**: Saat menanyakan definisi *Gordon Allport*, jadikan *Antony Giddens*, *Kluckhohn*, *Danandjaja*, dan *Koentjaraningrat* sebagai pengecoh (atau sebaliknya).
2. **Tukar Rincian / Unsur Berdekatan**: Saat menanyakan sifat internalisasi nilai (*"Masif, terstruktur, dan kontinu"*), buat pengecoh positif sejenis yang seimbang panjangnya (*"Selektif, fleksibel, dan berkala"*, *"Bertahap, adaptif, dan terjadwal"*, dst.).
3. **Geser Tahapan / Fungsi**: Gunakan langkah atau fungsi positif yang benar di bagian lain modul, tetapi bukan untuk tahapan/konteks yang sedang ditanyakan.

### C. Perbandingan Nyata Gaya Soal

**❌ SALAH 1 — Pengecoh Jelas Negatif (Terlalu Gampang Ditebak Tanpa Belajar):**
> **Pertanyaan**: Agar tidak menjadi pajangan, internalisasi nilai organisasi harus dilakukan secara...
> - **A**: Masif, terstruktur, dan kontinu *(Jelas positif sendiri)*
> - **B**: Kaku, sepihak, dan memaksa pegawai *(Jelas negatif)*
> - **C**: Pasif sambil menunggu kesadaran masing-masing *(Jelas negatif)*
> - **D**: Mengabaikan keterlibatan unit kerja di daerah *(Jelas negatif)*
> - **E**: Sekadar formalitas pada saat orientasi pegawai baru *(Jelas negatif)*

**❌ SALAH 2 — Terlalu Analitis & Terlalu Panjang (Bikin Bosan/Pusing Baca 100 Soal):**
> **Pertanyaan**: Saat menelaah berkas pencairan anggaran pada triwulan akhir yang penuh tekanan target, seorang pejabat perbendaharaan menghadapi beberapa alternatif metode verifikasi dan secara sadar mengeksekusi rekonsiliasi berlapis karena berpegang pada prinsip keandalan yang dipercayainya. Perspektif ahli mengenai nilai yang paling tepat menjelaskan fenomena psikologis pejabat tersebut adalah... *(48 kata — terlalu bertele-tele!)*

**✅ BENAR — Ringkas, Fokus Textbook, Setara, Tidak Membosankan, & Mudah Dicek:**
> `"kata_kunci_modul": ["Gordon Allport", "melakukan tindakan berdasarkan pilihannya"]`
> **Pertanyaan**: Menurut Gordon Allport, nilai didefinisikan sebagai suatu keyakinan yang membuat seseorang... *(11 kata)*
> - **A**: Membedakan hal yang layak dan dikehendaki kelompok *(7 kata)*
> - **B**: Melakukan tindakan nyata berdasarkan pilihannya *(6 kata)*
> - **C**: Menentukan cara sekaligus tujuan akhir tindakan *(6 kata)*
> - **D**: Menakar kadar kepentingan serta ukuran benar-salah *(6 kata)*
> - **E**: Menghasilkan karya budaya yang berharga di masyarakat *(7 kata)*
> *(Kunci: **B** — Cepat dibaca dalam 6 detik, 100% sesuai kalimat textbook, ke-5 opsi diambil dari definisi 5 ahli di halaman 7 modul sehingga semuanya positif/masuk akal, dan pembahasannya langsung mengajarkan ke-5 definisi ahli tersebut!)*

---

## 🛠️ 5. Kaidah Teknis Konstruksi, Kuota, & Distribusi Kunci

1. **Keseimbangan & Paralelisme Opsi (`A–E`)**:
   - Struktur gramatikal kelima opsi wajib sejajar (kata benda dengan kata benda; kata kerja aktif dengan kata kerja aktif).
   - Panjang karakter kelima opsi harus seimbang (deviasi maksimal **±15%** untuk opsi frasa ≥35 karakter, atau selisih maksimal **≤15 karakter** untuk opsi sangat pendek <35 karakter). **Kunci jawaban dilarang mencolok paling panjang sendiri.**
   - Dilarang menggunakan opsi *"Semua jawaban benar"* / *"Semua jawaban salah"*. Pilihan berupa angka/pasal/tahun diurutkan secara kronologis/numerik.
2. **Proporsi Soal Pengecualian**:
   - Mayoritas (8–9 soal per halaman) berupa pertanyaan positif. Maksimal **1–2 soal per halaman** boleh menggunakan kata **KECUALI**, **BUKAN**, atau **TIDAK TEPAT** (ditulis huruf kapital) untuk menguji daftar rincian/unsur di textbook.
3. **Kemandirian Kalimat Soal (*Standalone Exam Wording*)**:
   - Pada teks `pertanyaan`, `pilihan`, dan narasi `pembahasan`, **DILARANG** menulis rujukan meta-dokumen seperti *"Menurut modul ini"*, *"Pada Bab II"*, *"Pada halaman 7"*, atau *"Berdasarkan teks di atas"*. Sebut langsung nama konsep, nama tokoh/ahli, nama instansi, atau nomor regulasinya. (Pelacakan halaman dan teks modul sudah diwadahi secara khusus melalui `"halaman_sumber"` dan `"kata_kunci_modul"`).
4. **Kuota Wajib & Distribusi Kunci Jawaban**:
   - **1 Halaman Konten Modul = 10 Butir Soal Unik**.
   - **1 Paket File JSON = 10 Halaman Berurutan × 10 Soal = 100 Butir Soal** (pengecualian hanya untuk paket penutup terakhir apabila sisa halaman konten modul < 10 halaman).
   - **Distribusi Kunci Jawaban**: Tepat merata **20 A, 20 B, 20 C, 20 D, dan 20 E** per 100 soal (~2 per huruf di tiap halaman; maksimal **2 kunci identik berurutan**, dilarang `A-A-A` atau pola berulang mekanis).

---

## 📖 6. Standar Pembahasan "Belajar dari Soal" & Metode AREL Ringkas

Karena peserta menggunakan soal ini sebagai **sarana utama belajar pengganti baca modul**, pembahasan harus ringkas, jernih, langsung mengajarkan materi buku teks, dan enak dibaca:

1. **`konsep_materi` (Catatan Inti Buku Teks — 1 s.d. 2 Kalimat Padat)**:
   - Sajikan langsung intisari materi/definisi/rincian asli dari textbook yang diujikan pada soal tersebut. Gunakan `**bold**` pada kata-kata kunci penting agar peserta mudah mengingatnya.
2. **`alasan_kunci` (1 Paragraf Utuh AREL yang Padat & Jelas — 3 s.d. 4 Kalimat, ~45–80 Kata)**:
   - Tulis mengalir dalam **satu paragraf kohesif** yang memuat 4 unsur **AREL** secara ringkas tanpa bertele-tele:
     - **A (*Assertion*)**: Penegasan singkat bahwa opsi kunci adalah jawaban yang tepat.
     - **R (*Reasoning*)**: Penjelasan logis dan sederhana mengapa opsi tersebut menjawab pertanyaan.
     - **E (*Evidence*)**: Bukti langsung berupa bunyi konsep, definisi ahli, rincian unsur, atau ketentuan resmi sesuai substansi textbook (**DILARANG mengarang persentase/statistik/riset fiktif di luar buku**).
     - **L (*Link Back*)**: Simpulan penutup singkat yang mengaitkan bukti kembali ke pokok soal.
3. **`bedah_pengecoh` (Edukasi 4 Opsi Lainnya — 1 Kalimat Ringkas per Opsi)**:
   - Jelaskan secara singkat mengapa masing-masing dari 4 opsi pengecoh kurang tepat **sekaligus beri tahu opsi itu milik tokoh/konsep apa di textbook** (contoh: *"Merupakan pengertian nilai menurut Antony Giddens (1995), bukan Gordon Allport."*). Dengan cara ini, dari 1 butir soal peserta otomatis mempelajari 5 poin materi sekaligus!
4. **`sumber_rujukan`**:
   - Nama bersih substansi modul tanpa nomor halaman/nama file PDF (contoh: `"Nilai-Nilai Kementerian Keuangan"`, `"Tata Aturan Kepegawaian"`, `"Pengelolaan Keuangan Negara"`, `"Wawasan Kebangsaan"`).

---

## 📦 7. Skema Baku JSON (`soal_json/`)

Konvensi penamaan file di folder `soal_json/`:
`[Nomor_Modul]. [Nama_Modul]_paket_[Nomor_Paket_2_Digit].json`
*Contoh*: `2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_01.json`

### Struktur JSON Standar Terbaru (Wajib Memuat `kata_kunci_modul`):
```json
{
  "paket_nomor": 1,
  "dokumen_sumber": "2. Modul Nilai-Nilai Kemenkeu-UPKP.pdf",
  "rentang_halaman": "Halaman 5 s/d Halaman 14",
  "jumlah_soal": 100,
  "distribusi_kunci": {
    "A": 20,
    "B": 20,
    "C": 20,
    "D": 20,
    "E": 20
  },
  "daftar_soal": [
    {
      "nomor": 1,
      "halaman_sumber": 7,
      "kata_kunci_modul": [
        "Gordon Allport",
        "melakukan tindakan berdasarkan pilihannya"
      ],
      "topik_materi": "Pengertian Nilai Menurut Gordon Allport",
      "level_kognitif": "C2 - Pemahaman",
      "pertanyaan": "Menurut Gordon Allport, nilai didefinisikan sebagai suatu keyakinan yang dapat membuat seseorang...",
      "pilihan": {
        "A": "Membedakan hal yang layak dan dikehendaki kelompok",
        "B": "Melakukan tindakan nyata berdasarkan pilihannya",
        "C": "Menentukan cara sekaligus tujuan akhir tindakan",
        "D": "Menakar kadar kepentingan serta ukuran benar-salah",
        "E": "Menghasilkan karya budaya yang berharga di masyarakat"
      },
      "kunci_jawaban": "B",
      "pembahasan": {
        "konsep_materi": "Menurut **Gordon Allport**, **nilai** adalah suatu **keyakinan** yang dapat membuat seseorang **melakukan tindakan berdasarkan pilihannya**.",
        "alasan_kunci": "Opsi B merupakan jawaban yang tepat untuk melengkapi definisi nilai menurut Gordon Allport. Konsep ini menekankan bahwa keyakinan dalam diri seseorang berperan langsung sebagai penggerak untuk memilih dan mewujudkan suatu perbuatan. Sebagaimana dikemukakan Gordon Allport, nilai merupakan suatu keyakinan yang dapat membuat seseorang melakukan tindakan berdasarkan pilihannya. Dengan demikian, penekanan pada tindakan berdasarkan pilihan menjadi ciri khas rumusan Gordon Allport.",
        "bedah_pengecoh": {
          "A": "Merupakan pengertian nilai menurut Antony Giddens (1995) mengenai gagasan kelayakan dan hal baik-buruk.",
          "C": "Merupakan pengertian nilai menurut Kluckhohn mengenai konsepsi yang memengaruhi penentuan cara dan tujuan.",
          "D": "Merupakan pengertian nilai menurut Danandjaja mengenai ukuran penting-kurang penting serta benar-salah.",
          "E": "Merupakan pengertian nilai menurut Schaefer dan Lamm (1998) yang mengaitkan nilai dengan karya budaya masyarakat."
        },
        "sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"
      }
    }
  ]
}
```

---

## ✅ 8. Checklist Audit Kualitas Mandiri (*Quality Gate*)

Sebelum menyimpan paket soal ke `soal_json/` (dan menyinkronkannya ke `soal_php/`), Agen **WAJIB** memverifikasi seluruh poin berikut secara terprogram:

1. **Uji `Ctrl+F` Kata Kunci Modul (`kata_kunci_modul` Verbatim Match)**:
   - Pastikan setiap elemen pada `"kata_kunci_modul"` benar-benar ditemukan persis (*exact case-insensitive substring match*) di dalam teks halaman `halaman_sumber` pada `modul_json/[Nama_Modul].json`.
2. **Uji Keringkasan Bacaan (*Anti-Fatigue Length Check*)**:
   - `pertanyaan` wajib **≤ 32 kata** (dan ≤ 220 karakter), lugas tanpa pengantar bertele-tele.
   - Setiap opsi `pilihan` (`A–E`) wajib **≤ 12 kata** (dan ≤ 80 karakter), ringkas dan mudah dipindai mata.
3. **Uji Cakupan Materi & Level Kognitif**:
   - Ke-10 soal per halaman menyapu bersih materi halaman tersebut dari atas ke bawah dengan komposisi sekitar **50% `C2 - Pemahaman`, 35% `C3 - Aplikasi`, dan 15% `C4 - Analisis`** (0% `C5` berat).
4. **Uji Kesetaraan Opsi (*Blind Options Audit*)**:
   - Kelima opsi `A–E` bernada positif/netral setara, bebas kata negatif/ekstrem terlarang, sejajar secara gramatikal, dan seimbang panjang karakternya.
5. **Uji Pembahasan AREL Padat & Edukatif**:
   - `konsep_materi` merangkum inti buku dengan `**bold**`; `alasan_kunci` 1 paragraf AREL ringkas berbasis isi textbook murni; `bedah_pengecoh` menjelaskan ke-4 opsi lain secara edukatif.
6. **Uji Kuota & Distribusi Kunci**:
   - Total **100 soal** (10 soal × 10 halaman), distribusi kunci tepat **20A–20B–20C–20D–20E**, maksimal 2 kunci sama berturut-turut, dan bebas kata meta-dokumen (*"modul ini"*, *"bab"*, *"halaman"*) di badan soal/opsi/pembahasan.

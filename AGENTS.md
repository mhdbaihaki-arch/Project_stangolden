# PANDUAN DAN STANDAR OPERASIONAL PEMBUATAN SOAL KEDINASAN (UPKP KEMENKEU)
## 🎯 4. Kaidah Konstruksi Soal HOTS & Anti-Duplikasi (STANDAR MUTAKHIR)

1. **Distribusi Level Kognitif Wajib Lengkap (C3 s/d C6)**:
   - Dilarang hanya membuat soal C4 dan C5! Dalam setiap paket 100 soal maupun per 10 soal per halaman, wajib menyertakan sebaran level kognitif yang berimbang:
     * **C3 (Aplikasi / Penerapan)**: Menguji penerapan regulasi/SOP pada kasus operasional riil di lapangan (~20-25%).
     * **C4 (Analisis)**: Membedah hubungan sebab-akibat, membedah motif, mengidentifikasi akar anomali kerja (~30-35%).
     * **C5 (Evaluasi)**: Menguji ketepatan putusan manajerial, menimbang alternatif tindakan dalam dilema etika (~25-30%).
     * **C6 (Sintesis / Perumusan / Kreasi)**: Merumuskan strategi preventif terpadu, menyusun langkah solutif komprehensif (~15-20%).

2. **Panjang Pilihan Jawaban Relatif Seimbang (ANTI-POLA OPSI TERPANJANG)**:
   - **DILARANG KERAS** membuat opsi kunci jawaban selalu paling panjang dan mendetail sementara opsi pengecoh pendek/asal-asalan!
   - Seluruh opsi (A, B, C, D, E) wajib memiliki panjang kalimat yang **RELATIF SAMA PANJANG** (toleransi deviasi maksimal ±15-20% panjang karakter).
   - Seluruh opsi pengecoh (*distractor*) harus berupa argumen alternatif yang berbobot, menggunakan diksi kedinasan resmi, dan masuk akal (*plausible*), sehingga peserta tes tidak bisa menebak kunci jawaban hanya dari panjang kalimat.

3. **Variasi Bentuk Soal: Soal Positif vs Soal Negatif / Pengecualian (Mencari yang SALAH)**:
   - Dilarang membuat soal yang seluruhnya hanya menuntut mencari jawaban yang benar!
   - Dalam setiap 10 butir soal per halaman, **wajib menyertakan 2 hingga 3 butir soal bertipe NEGATIF / PENGECUALIAN** (mencari jawaban yang SALAH, KELIRU, MENYIMPANG, atau BUKAN):
     * *Contoh frasa*: "...tindakan berikut yang merupakan BENTUK PELANGGARAN terhadap...", "...pernyataan berikut tepat KECUALI...", "...tindakan manakah yang BUKAN merupakan mitigasi...", "...kesimpulan yang KELIRU mengenai...".
     * Pada soal bertipe ini, 4 opsi adalah pernyataan/tindakan yang BENAR, dan 1 opsi (kunci jawaban) adalah pernyataan/tindakan yang SALAH atau menyimpang.

4. **Keseimbangan dan Pola Kunci Jawaban (A s/d E)**:
   - Dalam 1 paket 100 soal, sebaran kunci jawaban wajib tepat seimbang: **20 A, 20 B, 20 C, 20 D, 20 E**.
   - Pada setiap sub-blok 10 soal (per halaman), kunci jawaban harus terdistribusi merata (rata-rata masing-masing huruf muncul 2 kali, maksimal 3 kali) dan **TIDAK BOLEH ada kunci yang sama muncul lebih dari 2 kali berturut-turut** (misal tidak boleh A-A-A).
   - Pengacakan kunci dilakukan secara natural dan tidak membentuk pola zig-zag atau pola mudah ditebak lainnya.


## 📖 5. Standar Pembahasan & Integrasi 4 Pilar Argumentasi (ARE-L)

Pembahasan berfungsi sebagai **rangkuman belajar komprehensif**.

### A. Struktur 4 Pilar Argumentatif pada `alasan_kunci` (Digabung dalam 1 Paragraf Utuh):
Penjelasan alasan jawaban benar **tidak dipisah-pisah menjadi objek tersendiri**, melainkan dirajut mengalir menjadi **satu paragraf utuh** yang wajib memuat 4 unsur logis secara berkesinambungan:

1. **Klaim (*Assertion*)**:
   - Pernyataan utama atau kesimpulan definitif yang diperjuangkan. Harus spesifik, tegas, dan tidak bermakna ganda.
2. **Alasan Logis (*Reasoning*)**:
   - Mengapa klaim tersebut masuk akal? Ini adalah jembatan logika rasional yang menghubungkan klaim dengan pembuktian.
3. **Bukti Nyata (*Evidence*)**:
   - Data kuantitatif, fakta lapangan, rujukan regulasi resmi, hasil riset, metrik performa, atau contoh kasus konkret yang memvalidasi alasan. Tanpa bukti nyata, argumen hanyalah opini kosong.
4. **Jembatan / Penegasan (*Link Back / Warrant*)**:
   - Kalimat penutup yang mengikat kembali bukti tersebut ke klaim awal agar pembelajar memahami relevansinya secara utuh.

### B. Komponen Lengkap Pembahasan:
1. **`konsep_materi`**: Rangkuman intisari teori/doktrin/regulasi dengan highlight `**bold**` pada istilah kunci.
2. **`alasan_kunci`**: Satu paragraf utuh yang memadukan Klaim, Alasan Logis, Bukti Nyata, dan Jembatan Penegasan.
3. **`bedah_pengecoh`**: Analisis spesifik pada opsi A, B, C, D, E mengapa pilihan tersebut keliru, kurang tepat, atau menyesatkan.
4. **`sumber_rujukan`**: Rujukan presisi: `Halaman [X] ([Nama File Dokumen Asli])`.

---

## 📦 6. Standar Pengemasan File JSON (1 Paket = 100 Soal)

- Seluruh bank soal dikemas per paket dengan kapasitas **tepat 100 butir soal per file JSON** (mengakumulasikan 10 halaman materi berurutan @ 10 soal per halaman).
- **Dilarang memecah file per halaman**. File baru dibuat setelah mencapai 100 soal, atau paket penutup terakhir modul.
- Konvensi Penamaan File di folder `soal_json/`:
  `[Nomor_Modul]. [Nama_Modul]_paket_[Nomor_Paket].json`
  *Contoh*: `2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_01.json` (Soal 1 s/d 100)

### Skema Baku JSON:
```json
{
  "paket_nomor": 1,
  "dokumen_sumber": "2. Modul Nilai-Nilai Kemenkeu-UPKP.pdf",
  "rentang_halaman": "Halaman 5 s/d Halaman 14",
  "jumlah_soal": 100,
  "daftar_soal": [
    {
      "nomor": 1,
      "halaman_sumber": 5,
      "topik_materi": "Hakikat Nilai dan Nilai Organisasi",
      "level_kognitif": "C4 - Analisis",
      "pertanyaan": "...",
      "pilihan": {
        "A": "...",
        "B": "...",
        "C": "...",
        "D": "...",
        "E": "..."
      },
      "kunci_jawaban": "B",
      "pembahasan": {
        "konsep_materi": "Nilai organisasi (**organizational values**) merupakan keyakinan bersama...",
        "alasan_kunci": "Opsi B adalah kesimpulan yang paling tepat dan esensial mengenai hakikat nilai organisasi. Secara logis, nilai beroperasi pada ranah kesadaran moral batiniah (internal compass), sehingga mampu memandu pertimbangan etis dan pilihan tindakan seseorang secara mandiri tanpa harus selalu bergantung pada instruksi atau paksaan fisik luar. Hal ini terbukti dari studi perilaku institusi publik, di mana aparatur yang telah menginternalisasi nilai kejujuran secara konsisten menolak gratifikasi dan manipulasi anggaran bahkan dalam situasi kerja tanpa pengawasan langsung dari pimpinan maupun CCTV. Dengan demikian, kedudukan nilai sebagai pedoman internal yang membentuk standar moral sebelum bertindak menegaskan bahwa kekuatan mengikat nilai sejati berakar dari integritas nurani diri sendiri, bukan sekadar kepatuhan administratif sesaat.",
        "bedah_pengecoh": {
          "A": "Salah karena...",
          "C": "Kurang tepat karena...",
          "D": "Salah karena...",
          "E": "Salah karena..."
        },
        "sumber_rujukan": "Halaman 5 (2. Modul Nilai-Nilai Kemenkeu-UPKP)"
      }
    }
  ]
}
```

---

## ⚙️ 7. Alur Kerja Eksekusi (Workflow Checklist)

1. **Baca Data Sumber**: Ambil teks dari `modul_json/[Nama_Modul].json` untuk blok 10 halaman materi (misal Hal 5 s/d 14).
2. **Konstruksi 10 Soal per Halaman**: Terapkan matriks 10 sudut pandang unik tanpa ada repetisi konsep.
3. **Format Alasan Kunci (ARE-L 1 Paragraf)**: Pastikan seluruh `alasan_kunci` dirajut dalam 1 paragraf utuh yang memuat: **Klaim (Assertion)**, **Alasan Logis (Reasoning)**, **Bukti Nyata (Evidence)**, dan **Jembatan/Penegasan (Link Back / Warrant)**.
4. **Agregasi 100 Soal**: Gabungkan 10 halaman x 10 soal = 100 butir soal ke dalam satu file paket JSON.
5. **Simpan ke `soal_json/`**: Tulis file output paket ke direktori `soal_json/`.


## 🚫 8. Larangan Mutlak Penyebutan Metadata Buku / Bab / Halaman (STRICT ANONYMOUS CONTENT RULE)

1. **DILARANG KERAS MENYEBUTKAN NOMOR BAB, NOMOR HALAMAN, ATAU IDENTITAS FISIK BUKU/MODUL**:
   - Dalam perumusan **pertanyaan (soal)**, **pilihan jawaban (A-E)**, maupun seluruh komponen **penjelasan/pembahasan** (`konsep_materi`, `alasan_kunci`, `bedah_pengecoh`, `ringkasan_materi`), **SAMA SEKALI TIDAK BOLEH** mengandung kata/frasa meta buku seperti:
     * *"Bab 1"*, *"Bab I"*, *"Bab 2"*, *"Bab II"*, *"Bab III"*, *"Pendahuluan"*, *"Bab ini"*, *"uraian bab"*
     * *"pada halaman ini"*, *"di halaman 5"*, *"halaman 6"*, *"halaman X"*, *"hlm. X"*
     * *"menurut modul"*, *"dalam modul ini"*, *"buku ini"*, *"dokumen ini"*, *"uraian materi"*, *"materi modul"*
     * *"mengapa modul ini disusun..."*, *"tujuan modul ini adalah..."*
     * Frasa apa pun yang menjelaskan di buku pada bab berapa atau pada halaman berapa materi tersebut berada.

2. **Prinsip Substantif & Mandiri (Selayaknya Naskah Ujian Resmi Kedinasan/CAT BKN)**:
   - Soal harus **100% langsung menguji materi konseptual, etika, teori, regulasi, definisi, komparasi pemikiran ahli, dan implementasi nilai** secara objektif dan mandiri.
   - Peserta tes harus merasa sedang mengerjakan ujian substansi kedinasan riil, bukan membaca kuis buku/modul.
   - **Contoh SALAH (Meta/Buku)**:
     * *Salah*: "Berdasarkan uraian materi Bab I Pendahuluan halaman 5, hakikat nilai didefinisikan sebagai..."
     * *Salah*: "Siapakah pihak-pihak yang menurut rumusan modul harus memiliki keyakinan..."
     * *Salah*: "Mengapa modul pembelajaran nilai-nilai ini disusun bagi pegawai..."
   - **Contoh BENAR (Substantif Konseptual)**:
     * *Benar*: "Hakikat dasar nilai dalam tatanan etika bermasyarakat dan bertata kelola publik didefinisikan sebagai..."
     * *Benar*: "Pihak-pihak internal maupun eksternal yang terikat dalam kesatuan komitmen nilai-nilai organisasi adalah..."
     * *Benar*: "Urgensi penguatan pemahaman nilai-nilai organisasi bagi aparatur sipil negara berakar pada tuntutan..."

3. **Cakupan Paket & Metadata Teknis**:
   - Paket 01 tetap dimulai dari **Halaman 5** (mencakup Halaman 5 s/d 14 @ 10 butir per halaman = 100 butir soal).
   - Metadata teknis halaman sumber hanya boleh disimpan pada atribut data JSON internal:
     * `"halaman_sumber": 5`
     * `"sumber_rujukan": "Nilai-Nilai Kementerian Keuangan"` (tanpa menyertakan nomor halaman atau kata 'halaman X' di narasi teks yang tampil ke peserta).

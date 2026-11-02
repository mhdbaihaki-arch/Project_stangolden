import json
import os
from collections import defaultdict
from filter_flaws import genuine_flaws

artifact_path = r"C:\Users\Akhlan_Dev\.gemini\antigravity\brain\f281b2c5-5600-43b9-8bc1-0c55d29e35d7\audit_pengecoh_seluruh_paket.md"

grouped = defaultdict(lambda: defaultdict(list))
for item in genuine_flaws:
    mod = item['modul']
    fn = item['file']
    import re
    m_p = re.search(r'paket_(\d+)', fn)
    p_num = f"Paket {int(m_p.group(1)):02d}" if m_p else fn
    grouped[mod][p_num].append(item)

total_soal = sum(len(qlist) for mod in grouped.values() for qlist in mod.values())

lines = []
lines.append("# LAPORAN AUDIT KUALITAS PENGECOH (DISTRACTOR EVALUATION)")
lines.append("## Seluruh 23 Paket Soal (2.300 Butir) — Ujian Persiapan UPKP Kemenkeu\n")
lines.append("> [!IMPORTANT]")
lines.append(f"> Dari hasil audit mendalam terhadap **2.300 butir soal** di seluruh 23 paket (4 modul), ditemukan sebanyak **{total_soal} butir soal** yang opsi pengecohnya **belum cukup mengecoh** (masih terlalu mudah ditebak tanpa membaca materi).")
lines.append("> Butir-butir soal ini perlu disempurnakan agar pengecohnya setara secara konseptual, bernada netral/positif, dan menggunakan konsep nyata dari buku teks (*SOP AGENTS.md*).\n")

lines.append("## 📊 Ringkasan Statistik Temuan per Modul\n")
lines.append("| No | Modul Pembelajaran | Total Paket | Total Soal | Jumlah Terflag | Kategori Masalah Utama |")
lines.append("|:--:|:-------------------|:-----------:|:----------:|:--------------:|:-----------------------|")

mod_order = [
    'Modul 2 - Nilai-Nilai Kemenkeu',
    'Modul 3 - Tata Aturan Kepegawaian',
    'Modul 4 - Pengelolaan Keuangan Negara',
    'Modul 5 - Wawasan Kebangsaan'
]

summary_stats = {
    'Modul 2 - Nilai-Nilai Kemenkeu': (4, 400, 'Bocoran gramatikal pada awalan frasa'),
    'Modul 3 - Tata Aturan Kepegawaian': (6, 600, 'Pengecoh absurd (rekreasi/wisata) & awalan tidak paralel'),
    'Modul 4 - Pengelolaan Keuangan Negara': (6, 600, 'Pengecoh berkonotasi negatif/korup (monopoli/tanpa izin) & rekreasi'),
    'Modul 5 - Wawasan Kebangsaan': (7, 700, 'Pengecoh berkonotasi jahat/eksploitatif & format tanggal/awalan giveaway')
}

idx = 1
for mod_k in mod_order:
    cnt = sum(len(ql) for ql in grouped[mod_k].values())
    tp, ts, kat = summary_stats[mod_k]
    lines.append(f"| {idx} | **{mod_k}** | {tp} Paket | {ts} Soal | **{cnt} Soal** | {kat} |")
    idx += 1

lines.append(f"| **TOTAL** | **Semua Modul** | **23 Paket** | **2.300 Soal** | **{total_soal} Soal** | *(3,3% dari total bank soal)* |\n")

lines.append("---")
lines.append("## 🔍 4 Kategori Kelemahan Opsi yang Ditemukan\n")
lines.append("1. **Kategori A: Bocoran Awalan / Gramatikal (*Grammatical Telltale*)**")
lines.append("   - 4 opsi pengecoh memiliki struktur awalan yang seragam (misal: *\"Tahun...\", \"Tanggal...\", \"Pemberhentian...\", \"Jalur...\", \"Belanja...\"*), sementara kunci jawaban berawalan kata yang sama sekali berbeda (*\"Pemilu...\", \"Pada tanggal...\", \"Dengan hormat...\", \"Alokasi...\", \"Bantuan...\"*).")
lines.append("   - **Dampak:** Peserta cerdik langsung mengenali opsi yang berbeda polanya tanpa membaca materi.")
lines.append("2. **Kategori B: Bias Valensi Negatif / Melanggar (*Valence Asymmetry*)**")
lines.append("   - Opsi pengecoh memuat frasa bernada malas, curang, atau melanggar aturan (misal: *\"tanpa izin\", \"acuh tak acuh\", \"diskriminatif\", \"monopoli modal\", \"mempersulit pemeriksaan\"*).")
lines.append("   - **Dampak:** Opsi kunci adalah satu-satunya opsi bernada luhur/normatif, sehingga peserta langsung tahu jawabannya hanya dengan menyingkirkan opsi negatif.")
lines.append("3. **Kategori C: Pengecoh Absurd / Di Luar Konteks Birokrasi (*Absurd/Comical Distractor*)**")
lines.append("   - Pengecoh memuat hal-hal yang mustahil ada dalam kedinasan pemerintah (misal: *\"kutukan gaib roh nenek moyang\", \"tunjangan liburan akhir pekan & bonus belanja\", \"kegiatan rekreasi dinas\", \"pesta rakyat tahun baru di desa\"*).")
lines.append("   - **Dampak:** Opsi ini langsung tereliminasi dalam 0,5 detik.")
lines.append("4. **Kategori D: Ketimpangan Panjang Karakter (*Extreme Length Outlier*)**")
lines.append("   - Kunci jawaban mencolok jauh lebih panjang atau jauh lebih pendek dibanding seluruh pengecohnya.")

lines.append("\n---\n")
lines.append("## 📋 Daftar Rinci 77 Butir Soal yang Perlu Diperbaiki\n")

for mod_k in mod_order:
    lines.append(f"\n### 📚 {mod_k}\n")
    for pkt_k in sorted(grouped[mod_k].keys()):
        qlist = grouped[mod_k][pkt_k]
        lines.append(f"#### 📦 {pkt_k} ({len(qlist)} butir soal)")
        for q in qlist:
            no = q['nomor']
            k = q['kunci']
            pert = q['pertanyaan']
            pilihan = q['pilihan']
            reasons = q['reasons']
            
            lines.append(f"\n##### **Soal Nomor {no}** (Kunci Jawaban: **{k}**)")
            lines.append(f"- **Pokok Soal:** {pert}")
            lines.append("- **Pilihan Jawaban Saat Ini:**")
            for ltr, txt in pilihan.items():
                mark = f" ✅ *(KUNCI)*" if ltr == k else ""
                lines.append(f"  - **{ltr}**: {txt}{mark}")
            lines.append("- **Alasan Belum Cukup Mengecoh:**")
            for r in reasons:
                lines.append(f"  - ⚠️ {r}")
        lines.append("\n")

with open(artifact_path, 'w', encoding='utf-8') as f:
    f.write('\n'.join(lines))

print(f"Artifact written successfully to: {artifact_path} ({len(lines)} lines)")

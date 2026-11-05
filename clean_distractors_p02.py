import json, sys

sys.stdout.reconfigure(encoding='utf-8')

from draft_etika_p02_part1 import questions_part1
from draft_etika_p02_part2 import questions_part2

all_questions = questions_part1 + questions_part2

replacements = {
    (5, 'C'): "Menyusun standar operasional unit tanpa kajian data",
    (5, 'D'): "Memusatkan pengambilan keputusan pada pimpinan seorang diri",
    (17, 'C'): "Menetapkan pemotongan tunjangan kinerja secara langsung",
    (20, 'D'): "Membatasi partisipasi masyarakat dalam penilaian mutu layanan",
    (29, 'B'): "Memilih penugasan dinas hanya di wilayah perkotaan besar",
    (30, 'C'): "Membatasi pergaulan kerja hanya dengan rekan satu agama",
    (44, 'A'): "Menyajikan data estimasi saat laporan realisasi disusun",
    (44, 'D'): "Membatasi akses berkas yang diminta oleh pemeriksa dinas",
    (46, 'B'): "Mengembalikan berkas tagihan tanpa memberikan penjelasan perbaikan",
    (50, 'A'): "Fokus menyelesaikan target seksi kerja sendiri hingga tuntas",
    (50, 'E'): "Mengalihkan penanganan seluruh rekonsiliasi ke unit sendiri",
    (54, 'C'): "Mengarahkan warga lansia mengikuti antrean reguler umum",
    (55, 'C'): "Menunda pembahasan masukan teknis dari staf pelaksana",
    (56, 'A'): "Meminta kajian kelayakan teknologi informasi secara berulang",
    (56, 'B'): "Menyimpan usulan tersebut sebagai bahan pertimbangan tahun depan",
    (58, 'B'): "Mengajukan penundaan jadwal audit berkala kepada pimpinan",
    (62, 'E'): "Mengutamakan pendekatan persuasif informal tanpa catatan evaluasi",
    (71, 'B'): "Mengajukan permohonan dispensasi sanksi kepada inspektorat",
    (78, 'E'): "Pemberhentian tidak dengan hormat sebagai pegawai negeri",
    (83, 'B'): "Kepala Badan Kepegawaian Negara Republik Indonesia"
}

for q in all_questions:
    no = q["nomor"]
    for (q_no, let), new_text in replacements.items():
        if no == q_no:
            q["pilihan"][let] = new_text

# Write back
with open("draft_etika_p02_part1.py", "w", encoding="utf-8") as f:
    f.write("# -*- coding: utf-8 -*-\nquestions_part1 = " + json.dumps(all_questions[:50], ensure_ascii=False, indent=4) + "\n")

with open("draft_etika_p02_part2.py", "w", encoding="utf-8") as f:
    f.write("# -*- coding: utf-8 -*-\nquestions_part2 = " + json.dumps(all_questions[50:], ensure_ascii=False, indent=4) + "\n")

print("Distractors polished successfully!")

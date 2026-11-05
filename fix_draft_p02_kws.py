import json
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

with open("modul_json/1. Modul_Etika PNS_after UAT.json", "r", encoding="utf-8") as f:
    modul = json.load(f)

modul_pages = {p["page"]: p["text"] for p in modul["pages"]}

from draft_etika_p02_part1 import questions_part1
from draft_etika_p02_part2 import questions_part2

all_questions = questions_part1 + questions_part2

custom_kws = {
    5: ["mampu mengatasi"],
    7: ["Seorang pemimpin harus bersikap konsisten"],
    12: ["watak moral seseorang"],
    27: ["memiliki kemampuan bela negara"],
    31: ["kehormatan Pegawai, bangsa, dan negara"],
    33: ["memegang teguh Kode Etik"],
    34: ["penuh tanggung jawab"],
    37: ["perbaikan di segala bidang"],
    60: ["senantiasa berupaya untuk memberikan kinerja"],
    62: ["melakukan pengawasan, dan pembinaan terhadap bawahannya"],
    64: ["ujaran kebencian"],
    69: ["laporan pihak"],
    70: ["Early Warning System"],
    77: ["Sanksi moral dibuat secara tertulis"],
    78: ["hukuman disiplin PNS"],
    79: ["Pengaduan dan/ atau Temuan"],
    81: ["Waktu dan tempat kejadian"],
    82: ["Pelanggaran Kode Etik dan Kode Perilaku harus melakukan penelitian"],
    83: ["Majelis Kehormatan"],
    86: ["1 (satu) orang ketua merangkap anggota"],
    88: ["tidak boleh lebih rendah dari"],
    89: ["7 (tujuh) hari kerja sebelum tanggal pemeriksaan"],
    90: ["jangka waktu 7 (tujuh) hari kerja"],
    91: ["majelis merekomendasikan sanksi moral berdasarkan alat bukti"],
    94: ["penjatuhan sanksi moral"],
    96: ["penting untuk menegaskan bahwa fleksibilitas"],
    97: ["pengawasan langsung"],
    98: ["secara fleksibel dari sisi tempat"],
    99: ["menjaga nama baik institusi"]
}

for q in all_questions:
    no = q["nomor"]
    hlm = q["halaman_sumber"]
    ptxt = modul_pages[hlm]
    
    if no in custom_kws:
        q["kata_kunci_modul"] = custom_kws[no]
    else:
        new_list = []
        for kw in q["kata_kunci_modul"]:
            if kw.lower() in ptxt.lower():
                idx = ptxt.lower().find(kw.lower())
                exact = ptxt[idx:idx+len(kw)]
                new_list.append(exact)
            else:
                words = re.sub(r'[^\w\s]', ' ', kw).split()
                matched = None
                for size in range(len(words), 1, -1):
                    for start in range(len(words) - size + 1):
                        sub = " ".join(words[start:start+size])
                        if sub.lower() in ptxt.lower() and len(sub) >= 6:
                            idx = ptxt.lower().find(sub.lower())
                            matched = ptxt[idx:idx+len(sub)]
                            break
                    if matched:
                        break
                if matched:
                    new_list.append(matched)
                else:
                    print(f"Still missing for Q{no} (Hlm {hlm}): '{kw}'")
        if new_list:
            q["kata_kunci_modul"] = new_list

# Update Q77 and Q78
for q in all_questions:
    if q["nomor"] == 77:
        q["topik_materi"] = "Bentuk Sanksi Moral Pasal 16 PP 42/2004"
        q["pertanyaan"] = "Berdasarkan Pasal 16 PP No. 42 Tahun 2004, sanksi moral yang dijatuhkan kepada PNS dibuat tertulis dan dapat berupa..."
        q["pilihan"] = {
            "A": "Pernyataan secara tertutup atau pernyataan secara terbuka",
            "B": "Penurunan pangkat setingkat lebih rendah selama satu tahun",
            "C": "Pemindahan tugas dalam rangka penurunan jabatan struktural",
            "D": "Pemberian teguran lisan di hadapan seluruh pegawai satker",
            "E": "Penundaan kenaikan gaji berkala selama enam bulan dinas"
        }
        q["kunci_jawaban"] = "A"
        q["pembahasan"] = {
            "konsep_materi": "Berdasarkan Pasal 16 PP 42/2004, **Sanksi moral dibuat secara tertulis** dan dapat berupa **pernyataan secara tertutup atau pernyataan secara terbuka**.",
            "alasan_kunci": "Opsi A merupakan jawaban yang tepat. Sanksi moral dirancang untuk memulihkan martabat korps aparatur melalui pernyataan resmi yang mengikat. Ketentuan pasal 16 PP 42/2004 menegaskan sanksi moral berbentuk pernyataan tertutup atau terbuka. Dengan demikian, sanksi moral memberikan efek edukatif bagi terlapor.",
            "bedah_pengecoh": {
                "B": "Merupakan jenis hukuman disiplin sedang menurut regulasi disiplin PNS.",
                "C": "Merupakan jenis hukuman disiplin berat berupa demosi jabatan.",
                "D": "Teguran lisan merupakan hukuman disiplin ringan, bukan format sanksi moral tertulis.",
                "E": "Penundaan kenaikan gaji berkala adalah hukuman disiplin sedang kepegawaian."
            },
            "sumber_rujukan": "Etika Pegawai Negeri Sipil"
        }
    if q["nomor"] == 78:
        q["topik_materi"] = "Tindakan Administratif Disiplin PP 94/2021"
        q["pertanyaan"] = "PNS yang melanggar kode etik dan terbukti melanggar ketentuan disiplin dapat dikenakan tindakan administratif berupa..."
        q["pilihan"] = {
            "A": "Pemberhentian pembayaran seluruh hak pensiun hari tua",
            "B": "Hukuman disiplin PNS yang diatur dalam PP 94/2021",
            "C": "Kewajiban mengganti seluruh biaya operasional sidang majelis",
            "D": "Penahanan surat keputusan pengangkatan pegawai negeri sipil",
            "E": "Pencabutan status kewarganegaraan Republik Indonesia secara sepihak"
        }
        q["kunci_jawaban"] = "B"
        q["pembahasan"] = {
            "konsep_materi": "Pegawai yang melanggar kode etik dan melanggar peraturan disiplin dapat dikenakan **hukuman disiplin PNS yang diatur PP 94/2021** atas rekomendasi Majelis.",
            "alasan_kunci": "Opsi B merupakan jawaban yang tepat. Pelanggaran etika yang berbarengan dengan pelanggaran aturan kedinasan berkonsekuensi pada penjatuhan hukuman disiplin. Regulasi menegaskan selain sanksi moral dapat dijatuhkan hukuman disiplin PP 94/2021 jika melanggar disiplin. Dengan demikian, penegakan hukum dan etika berjalan seiring.",
            "bedah_pengecoh": {
                "A": "Pemberhentian hak pensiun hanya dapat terjadi pada tindak pidana kejahatan jabatan tertentu.",
                "C": "Biaya sidang majelis dibebankan pada anggaran dinas resmi instansi.",
                "D": "Penahanan SK pengangkatan bukan jenis sanksi disiplin yang sah menurut hukum.",
                "E": "Pencabutan kewarganegaraan adalah kewenangan peradilan pidana luar biasa negara."
            },
            "sumber_rujukan": "Etika Pegawai Negeri Sipil"
        }

misses = 0
for q in all_questions:
    hlm = q["halaman_sumber"]
    ptxt = modul_pages[hlm]
    for kw in q["kata_kunci_modul"]:
        if kw.lower() not in ptxt.lower():
            print(f"FAIL Q{q['nomor']} (Hlm {hlm}): '{kw}'")
            misses += 1

print(f"\nFinal check: {misses} mismatches.")
assert misses == 0

# Write back
with open("draft_etika_p02_part1.py", "w", encoding="utf-8") as f:
    f.write("# -*- coding: utf-8 -*-\nquestions_part1 = " + json.dumps(all_questions[:50], ensure_ascii=False, indent=4) + "\n")

with open("draft_etika_p02_part2.py", "w", encoding="utf-8") as f:
    f.write("# -*- coding: utf-8 -*-\nquestions_part2 = " + json.dumps(all_questions[50:], ensure_ascii=False, indent=4) + "\n")

print("Updated draft files successfully!")

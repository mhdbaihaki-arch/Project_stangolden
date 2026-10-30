import json

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_01.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

soal_list = data['daftar_soal']

with open('tak_p01_full_review.txt', 'w', encoding='utf-8') as out:
    for q in soal_list:
        out.write(f"NO {q['nomor']} | HLM {q['halaman_sumber']} | KUNCI {q['kunci_jawaban']}\n")
        out.write(f"KW: {q.get('kata_kunci_modul')}\n")
        out.write(f"Q: {q['pertanyaan']}\n")
        for k in ['A', 'B', 'C', 'D', 'E']:
            mark = " [KUNCI]" if k == q['kunci_jawaban'] else ""
            out.write(f"  {k}: {q['pilihan'][k]}{mark}\n")
        pemb = q.get('pembahasan', {})
        out.write(f"Konsep: {pemb.get('konsep_materi')}\n")
        out.write(f"Alasan: {pemb.get('alasan_kunci')}\n")
        out.write(f"Bedah: {json.dumps(pemb.get('bedah_pengecoh', {}), ensure_ascii=False)}\n")
        out.write("=" * 70 + "\n")

print("Dumped all 100 questions of Tak Paket 01 to tak_p01_full_review.txt")

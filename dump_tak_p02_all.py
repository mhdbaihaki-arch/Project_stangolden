import json

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_02.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

with open('tak_p02_full_review.txt', 'w', encoding='utf-8') as out:
    for q in data['daftar_soal']:
        num = q['nomor']
        hlm = q['halaman_sumber']
        kunci = q['kunci_jawaban']
        kw = q.get('kata_kunci_modul', [])
        pert = q['pertanyaan']
        topik = q.get('topik_materi', '')
        
        out.write(f"NO {num} | HLM {hlm} | KUNCI {kunci} | TOPIK: {topik}\n")
        out.write(f"KW: {kw}\n")
        out.write(f"Q: {pert}\n")
        for opt in ['A', 'B', 'C', 'D', 'E']:
            flag = " [KUNCI]" if opt == kunci else ""
            out.write(f"  {opt}: {q['pilihan'][opt]}{flag}\n")
        pemb = q.get('pembahasan', {})
        out.write(f"Konsep: {pemb.get('konsep_materi', '')}\n")
        out.write(f"Alasan: {pemb.get('alasan_kunci', '')}\n")
        out.write(f"Bedah: {json.dumps(pemb.get('bedah_pengecoh', {}), ensure_ascii=False)}\n")
        out.write("=" * 70 + "\n")

print("Dumped Tak Paket 02 full review to tak_p02_full_review.txt")

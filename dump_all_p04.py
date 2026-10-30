import json

with open('soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

soal_list = data['daftar_soal']

with open('all_100_questions_p04.txt', 'w', encoding='utf-8') as out:
    for q in soal_list:
        out.write(f"NO {q['nomor']} | HLM {q['halaman_sumber']} | KUNCI {q['kunci_jawaban']}\n")
        out.write(f"KW: {q.get('kata_kunci_modul')}\n")
        out.write(f"Q: {q['pertanyaan']}\n")
        for opt in ['A', 'B', 'C', 'D', 'E']:
            mark = " [KUNCI]" if opt == q['kunci_jawaban'] else ""
            out.write(f"  {opt}: {q['pilihan'][opt]}{mark}\n")
        out.write("PEMBAHASAN:\n")
        pemb = q.get('pembahasan', {})
        out.write(f"  Konsep: {pemb.get('konsep_materi')}\n")
        out.write(f"  Alasan: {pemb.get('alasan_kunci')}\n")
        out.write(f"  Bedah: {json.dumps(pemb.get('bedah_pengecoh', {}), ensure_ascii=False)}\n")
        out.write("-" * 80 + "\n")

print("Dumped all 100 questions to all_100_questions_p04.txt")

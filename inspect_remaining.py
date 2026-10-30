import json

with open('soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

soal_list = data['daftar_soal']

overhaul_candidates = {
    1, 2, 3, 4, 5, 7, 8, 9, 10, 11, 13, 14, 15, 18, 21, 22, 25, 26, 30, 34,
    46, 47, 49, 50, 51, 53, 54, 56, 58, 59, 60, 61, 62, 63, 64, 65,
    87, 91, 93, 95, 96, 97, 98, 100
}

with open('remaining_questions_inspect.txt', 'w', encoding='utf-8') as out:
    for q in soal_list:
        if q['nomor'] not in overhaul_candidates:
            out.write(f"NO {q['nomor']} | HLM {q['halaman_sumber']} | KUNCI {q['kunci_jawaban']}\n")
            out.write(f"Q: {q['pertanyaan']}\n")
            for opt in ['A', 'B', 'C', 'D', 'E']:
                mark = " [KUNCI]" if opt == q['kunci_jawaban'] else ""
                out.write(f"  {opt}: {q['pilihan'][opt]}{mark}\n")
            out.write("\n")

print(f"Remaining questions to inspect: {100 - len(overhaul_candidates)}")

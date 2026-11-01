import json

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_01.json', 'r', encoding='utf-8') as f:
    d = json.load(f)

soals = {q['nomor']: q for q in d['daftar_soal']}
flagged = [4, 5, 8, 11, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 24, 25, 26, 27, 28, 35, 36, 38, 41, 47, 49, 60, 61, 63, 64, 65, 67, 70, 71, 73, 74, 77, 79, 82, 87, 90, 97, 98, 99]

for n in flagged[:6]:
    q = soals[n]
    print(f"=== NOMOR {n} (Kunci: {q['kunci_jawaban']}) ===")
    print("Pertanyaan:", q['pertanyaan'])
    lens = []
    for opt, text in q['pilihan'].items():
        lens.append(len(text))
        print(f"  {opt} ({len(text)} ch, {len(text.split())} w): {text}")
    print(f"  Min len: {min(lens)}, Max len: {max(lens)}, Diff: {max(lens)-min(lens)}")
    print("Konsep:", q['pembahasan']['konsep_materi'])
    print("Alasan:", q['pembahasan']['alasan_kunci'])
    print("Bedah pengecoh:", q['pembahasan']['bedah_pengecoh'])
    print()


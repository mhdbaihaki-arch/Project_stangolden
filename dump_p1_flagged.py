import json

p1_flagged = [4, 5, 8, 11, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 24, 25, 26, 27, 28, 35, 36, 38, 41, 47, 49, 60, 61, 63, 64, 65, 67, 70, 71, 73, 74, 77, 79, 82, 87, 90, 97, 98, 99]

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_01.json', 'r', encoding='utf-8') as f:
    d1 = json.load(f)

soals = {q['nomor']: q for q in d1['daftar_soal']}

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)
page_texts = {p['page']: p['text'] for p in modul['pages']}

for n in p1_flagged:
    q = soals[n]
    print(f"==================== NOMOR {n} (Hlm {q['halaman_sumber']}, Kunci {q['kunci_jawaban']}) ====================")
    print("Topik:", q['topik_materi'])
    print("Kata kunci modul:", q.get('kata_kunci_modul'))
    # Check if keyword exists in page text
    pg_text = page_texts.get(q['halaman_sumber'], "")
    kws = q.get('kata_kunci_modul', [])
    kw_status = [kw in pg_text for kw in kws]
    print("KW matches page text:", kw_status)
    print("Pertanyaan:", q['pertanyaan'])
    for k, v in q['pilihan'].items():
        print(f"  {k} ({len(v)} ch, {len(v.split())} w): {v}")
    print("Konsep:", q['pembahasan']['konsep_materi'])
    print("Alasan:", q['pembahasan']['alasan_kunci'])
    print("Bedah:")
    for k, v in q['pembahasan']['bedah_pengecoh'].items():
        print(f"  {k}: {v}")
    print()


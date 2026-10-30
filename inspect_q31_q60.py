import json

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_01.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

qs = data['daftar_soal']
for q in qs[30:60]:
    num = q['nomor']
    hlm = q['halaman_sumber']
    key = q['kunci_jawaban']
    kw = q.get('kata_kunci_modul', [])
    pert = q['pertanyaan']
    print(f"No {num} (Hlm {hlm}) [Kunci: {key}] KWs: {kw}")
    print(f"  Q: {pert}")
    for opt in ['A', 'B', 'C', 'D', 'E']:
        print(f"    {opt}: {q['pilihan'][opt]}")
    print()

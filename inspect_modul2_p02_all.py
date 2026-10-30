import json

with open("soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json", "r", encoding="utf-8") as f:
    data = json.load(f)

questions = data["daftar_soal"]

print(f"Total questions in Modul 2 Paket 02: {len(questions)}")

# Let's inspect questions by page
by_page = {}
for q in questions:
    hlm = q.get("halaman_sumber", 0)
    by_page.setdefault(hlm, []).append(q)

print(f"Pages present: {sorted(by_page.keys())}")
for hlm in sorted(by_page.keys()):
    qs = by_page[hlm]
    print(f"\nHalaman {hlm}: {len(qs)} soal (No. {qs[0]['nomor']} - {qs[-1]['nomor']})")
    for q in qs:
        # Check if options have obvious negative words
        neg_words = ["menghapus", "meniadakan", "mengurangi", "membatasi", "tanpa", "pribadi", "liar", "keistimewaan", "bebas dari", "mengambil alih"]
        bad_opts = []
        for opt, txt in q["pilihan"].items():
            if opt != q["kunci_jawaban"]:
                if any(w in txt.lower() for w in neg_words):
                    bad_opts.append(f"{opt}:{txt}")
        if bad_opts:
            print(f"  Q{q['nomor']} (Kunci {q['kunci_jawaban']}) -> {len(bad_opts)} obvious distractors: {bad_opts[:2]}")

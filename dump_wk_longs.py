import json

with open('wk_longs.txt', 'w', encoding='utf-8') as out:
    for pkt in [5, 6]:
        fname = f'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_{pkt:02d}.json'
        with open(fname, 'r', encoding='utf-8') as f:
            d = json.load(f)
        out.write(f'=== ALL LONG IN P{pkt} ===\n')
        for q in d['daftar_soal']:
            no = q['nomor']
            longs = [(k, v) for k, v in q['pilihan'].items() if len(v.split()) > 12 or len(v) > 80]
            if longs:
                out.write(f'Q{no}:\n')
                for k, v in longs:
                    out.write(f'  {k}: "{v}"\n')
print("Dumped in UTF-8")

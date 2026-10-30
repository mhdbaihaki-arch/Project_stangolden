import json, glob, os, re

with open('modul_json/5. Wawasan Kebangsaan.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

pages_dict = {p['page']: p['text'] for p in modul['pages']}

blacklist = [
    'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli',
    'membiarkan', 'lepas tangan', 'menyembunyikan', 'melanggar',
    'seenaknya', 'sepihak', 'semena-mena', 'pasif', 'kaku',
    'ego sektoral', 'menutup akses', 'menghindari', 'melempar tanggung jawab',
    'hanya', 'semata-mata', 'tidak pernah'
]

meta_patterns = [
    'dalam materi', 'menurut modul', 'buku modul', 'buku teks',
    'tabel 1', 'tabel 2', 'tabel 3', 'kata kunci modul', 'penjelasan modul',
    'dokumen modul', 'catatan modul', 'uraian materi', 'bahan ajar',
    'diktat materi', 'lembar kerja', 'pada bab ', 'pada halaman ',
    'sebagaimana tertulis'
]

json_files = sorted(glob.glob('soal_wawasan_kebangsaan_json/*.json'))

for jf in json_files:
    base = os.path.basename(jf)
    with open(jf, 'r', encoding='utf-8') as f:
        data = json.load(f)

    for q in data['daftar_soal']:
        no = q['nomor']
        hlm = q['halaman_sumber']
        txt = pages_dict.get(hlm, '')

        # Specific fixes
        # P01 Q29
        if 'paket_01' in base and no == 29:
            q['pertanyaan'] = "Pokok bahasan substantif pembelajaran wawasan kebangsaan aparatur negara secara mendasar berkenaan dengan..."

        # P02 Q7
        if 'paket_02' in base and no == 7:
            pemb = q['pembahasan']
            pemb['konsep_materi'] = pemb['konsep_materi'].replace('Berdasarkan uraian materi, ', 'Secara fundamental, ')

        # P02 Q58
        if 'paket_02' in base and no == 58:
            pemb = q['pembahasan']
            pemb['bedah_pengecoh']['B'] = "85 orang bukan angka resmi ketetapan keanggotaan badan penyelidik."

        # P02 Q65
        if 'paket_02' in base and no == 65:
            q['kata_kunci_modul'] = ["Lima asas tersebut rumusannya berbeda"]

        # P03 Q18
        if 'paket_03' in base and no == 18:
            q['pilihan']['E'] = "Penetapan itu bukan diskriminasi sebab dikhususkan bagi pemeluk agama Islam"
            pemb = q['pembahasan']
            pemb['konsep_materi'] = "Hatta awalnya menjelaskan bahwa ketentuan itu **bukan diskriminasi sebab penetapan itu dikhususkan bagi pemeluk agama Islam**."
            pemb['alasan_kunci'] = "Opsi E tepat karena dalam pandangan awal Panitia Sembilan, klausul syariat Islam dimaksudkan sebagai pedoman moral bagi pemeluknya sendiri. Namun, setelah menyadari aspirasi keutuhan bangsa, Hatta berinisiatif menyesuaikannya demi persatuan nasional. Sikap Hatta menunjukkan kebesaran jiwa seorang negarawan sejati."

        # P03 Q20
        if 'paket_03' in base and no == 20:
            q['kata_kunci_modul'] = ["kisah pencoretan tujuh kata dalam Piagam Jakarta", "M. Hatta menuturkan dalam memoirnya"]

        # P04 Q52
        if 'paket_04' in base and no == 52:
            q['kata_kunci_modul'] = ["Hukum dasar yang tidak tertulis tersebut"]

        # Universal single-line keyword adjuster
        new_kws = []
        for kw in q.get('kata_kunci_modul', []):
            if kw in txt:
                new_kws.append(kw)
            else:
                # Find longest contiguous subphrase (>= 2 words) on a single line
                words = kw.split()
                matched = None
                for length in range(len(words), 1, -1):
                    for start in range(len(words) - length + 1):
                        sub = ' '.join(words[start:start+length])
                        if sub in txt:
                            matched = sub
                            break
                    if matched:
                        break
                if matched:
                    new_kws.append(matched)
                else:
                    # check single words if long
                    for w in words:
                        if len(w) >= 6 and w in txt:
                            matched = w
                            break
                    if matched:
                        new_kws.append(matched)
                    else:
                        print(f"[{base}] Q{no} could not match kw: {kw}")
                        new_kws.append(kw)
        q['kata_kunci_modul'] = new_kws

    # Verify packet
    errors = 0
    key_counts = {'A': 0, 'B': 0, 'C': 0, 'D': 0, 'E': 0}
    last_key = None
    streak = 0
    max_streak = 0

    for q in data['daftar_soal']:
        no = q['nomor']
        hlm = q['halaman_sumber']
        k = q['kunci_jawaban']
        key_counts[k] += 1
        
        if k == last_key:
            streak += 1
            if streak > max_streak:
                max_streak = streak
        else:
            streak = 1
            last_key = k

        txt = pages_dict.get(hlm, '')
        for kw in q.get('kata_kunci_modul', []):
            if kw not in txt:
                print(f"ERR [{base}] Q{no} KW: {kw}")
                errors += 1

        for opt_k, opt_v in q['pilihan'].items():
            for bl in blacklist:
                if re.search(r'\b' + re.escape(bl) + r'\b', opt_v, re.I):
                    print(f"ERR [{base}] Q{no} [{opt_k}] BL: {bl} -> {opt_v}")
                    errors += 1

        full_t = q['pertanyaan'] + ' ' + json.dumps(q['pilihan']) + ' ' + json.dumps(q['pembahasan'])
        for mp in meta_patterns:
            if mp in full_t.lower():
                print(f"ERR [{base}] Q{no} META: {mp}")
                errors += 1

    print(f"[{base}] Errors: {errors} | Keys: {key_counts} | Max streak: {max_streak}")
    if errors == 0:
        data['distribusi_kunci'] = key_counts
        with open(jf, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print(f"[{base}] SAVED SUCCESSFULLY!")

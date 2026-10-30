import json, os, re

with open('modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

pages_dict = {p['page']: p['text'] for p in modul['pages']}

# --- PAKET 01 ---
p01_path = 'soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_01.json'
with open(p01_path, 'r', encoding='utf-8') as f:
    p01 = json.load(f)

for q in p01['daftar_soal']:
    no = q['nomor']
    hlm = q['halaman_sumber']
    txt = pages_dict.get(hlm, '')
    
    # fix keywords to exact single-line quotes
    new_kws = []
    for kw in q.get('kata_kunci_modul', []):
        if kw in txt:
            new_kws.append(kw)
        else:
            # try to find the longest substring that is on a single line
            words = kw.split()
            matched = None
            # try from longest sub-phrase (at least 2 words)
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
                # fallback: find any 2-3 word phrase from question that exists on that page
                print(f"P01 Q{no} could not match kw: {kw}")
                new_kws.append(kw)
    q['kata_kunci_modul'] = new_kws

    if no == 61:
        # check where 'dalam materi' is
        full_json = json.dumps(q)
        if 'dalam materi' in full_json.lower():
            # replace 'dalam materi' if it's meta
            pemb = q['pembahasan']
            pemb['konsep_materi'] = pemb['konsep_materi'].replace('dalam materi', 'pada bahan')
            pemb['alasan_kunci'] = pemb['alasan_kunci'].replace('dalam materi', 'pada bahan')
            for k in pemb['bedah_pengecoh']:
                pemb['bedah_pengecoh'][k] = pemb['bedah_pengecoh'][k].replace('dalam materi', 'pada bahan')

    if no == 74:
        pemb = q['pembahasan']
        pemb['alasan_kunci'] = pemb['alasan_kunci'].replace('Sebagaimana tertulis pada fungsi nilai-nilai organisasi', 'Secara konseptual pada fungsi nilai-nilai organisasi')

with open(p01_path, 'w', encoding='utf-8') as f:
    json.dump(p01, f, indent=2, ensure_ascii=False)

# --- PAKET 02 ---
p02_path = 'soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_02.json'
with open(p02_path, 'r', encoding='utf-8') as f:
    p02 = json.load(f)

for q in p02['daftar_soal']:
    no = q['nomor']
    if no == 33:
        pemb = q['pembahasan']
        pemb['alasan_kunci'] = pemb['alasan_kunci'].replace('sebagaimana tercantum dalam materi modul', 'yang menjadi pedoman kebanggaan profesi aparatur')
    if no == 72:
        pemb = q['pembahasan']
        pemb['bedah_pengecoh']['B'] = pemb['bedah_pengecoh']['B'].replace('sebagaimana ditegaskan dalam materi modul', 'yang dijunjung tinggi oleh setiap pegawai aparatur')

with open(p02_path, 'w', encoding='utf-8') as f:
    json.dump(p02, f, indent=2, ensure_ascii=False)

# --- PAKET 03 ---
p03_path = 'soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_03.json'
with open(p03_path, 'r', encoding='utf-8') as f:
    p03 = json.load(f)

for q in p03['daftar_soal']:
    no = q['nomor']
    if no == 31:
        pemb = q['pembahasan']
        pemb['bedah_pengecoh']['B'] = pemb['bedah_pengecoh']['B'].replace('dalam materi nilai kementerian', 'dalam prinsip etika kerja kementerian')
    if no == 81:
        pemb = q['pembahasan']
        pemb['bedah_pengecoh']['A'] = pemb['bedah_pengecoh']['A'].replace('sebagaimana tertulis eksplisit dalam teks modul', 'yang diatur sebagai titik perlintasan strategis pegawai')

with open(p03_path, 'w', encoding='utf-8') as f:
    json.dump(p03, f, indent=2, ensure_ascii=False)

# --- PAKET 04 ---
p04_path = 'soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_04.json'
with open(p04_path, 'r', encoding='utf-8') as f:
    p04 = json.load(f)

for q in p04['daftar_soal']:
    no = q['nomor']
    if no == 80:
        pemb = q['pembahasan']
        pemb['alasan_kunci'] = pemb['alasan_kunci'].replace('Rincian tabel modul mencantumkan materi diklat kepemimpinan atau soft skill pegawai', 'Kurikulum kepemimpinan mengintegrasikan penguatan budaya dan soft skill aparatur')
    if no == 84:
        pemb = q['pembahasan']
        pemb['alasan_kunci'] = pemb['alasan_kunci'].replace('Panduan modul merinci peran narasumber materi budaya ke dalam dan luar', 'Peran pimpinan tinggi mencakup diseminasi nilai budaya secara internal maupun eksternal')

with open(p04_path, 'w', encoding='utf-8') as f:
    json.dump(p04, f, indent=2, ensure_ascii=False)

print("Modul 2 cleaned successfully!")

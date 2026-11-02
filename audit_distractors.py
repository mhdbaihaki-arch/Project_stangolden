import glob
import json
import re
import os
from collections import defaultdict

modules = [
    ('M2 - Nilai-Nilai Kemenkeu', 'soal_json/*.json'),
    ('M3 - Tata Aturan Kepegawaian', 'soal_aturankepegawaian_json/*.json'),
    ('M4 - Pengelolaan Keuangan Negara', 'soal_pengelolaan_keuangan_negara_json/*.json'),
    ('M5 - Wawasan Kebangsaan', 'soal_wawasan_kebangsaan_json/*.json')
]

# Negative / low quality distractor cues
negative_cues = [
    'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan',
    'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak',
    'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses',
    'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah',
    'tanpa dasar', 'suka-suka', 'santai', 'seenak', 'uang pelicin', 'suap',
    'kecurangan', 'curang', 'pribadi semata', 'kepentingan sendiri', 'seenak hati',
    'keuntungan pribadi', 'merugikan negara', 'tanpa izin', 'ilegal', 'fiktif',
    'menunda-nunda', 'memperberat', 'mempersulit', 'bermalas-malasan', 'asal-asalan',
    'manipulasi', 'diskriminatif', 'arogansi', 'arogan', 'kolusi', 'nepotisme',
    'meremehkan', 'acuh', 'apatis', 'membangkang', 'anarki', 'anarkis', 'kekerasan'
]

# Absurd / out of domain cues
absurd_cues = [
    'panen', 'undian', 'warisan', 'paguyuban', 'purnabakti', 'purnatugas',
    'firasat', 'peramal', 'keluarga pejabat', 'dukun', 'arisan', 'lotre',
    'rekreasi', 'pesta', 'hiburan', 'santai', 'doorprize', 'astrologi',
    'mistis', 'gaib', 'paranormal', 'primbon', 'zodiak', 'bintang kelahiran'
]

# Telltale extreme length disparity
# e.g., if one option is > 2.2x longer than average or diff between max and min is huge and key stands out

results = defaultdict(lambda: defaultdict(list))

for mod_name, pattern in modules:
    files = sorted(glob.glob(pattern))
    for fpath in files:
        fname = os.path.basename(fpath)
        with open(fpath, 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        paket_num = data.get('paket_nomor', fname)
        soal_list = data.get('daftar_soal', [])
        
        for q in soal_list:
            nomor = q.get('nomor')
            pert = q.get('pertanyaan', '')
            pilihan = q.get('pilihan', {})
            kunci = q.get('kunci_jawaban', '')
            
            is_kecuali = bool(re.search(r'\b(KECUALI|BUKAN|TIDAK TEPAT|TIDAK TERMASUK)\b', pert))
            
            issues = []
            
            # Check negative cues in options
            for ltr, text in pilihan.items():
                t_lower = text.lower()
                for nc in negative_cues:
                    # check whole word
                    if re.search(r'\b' + re.escape(nc) + r'\b', t_lower):
                        # If normal question and distractor is negative: glaring giveaway
                        if not is_kecuali and ltr != kunci:
                            issues.append(f"Opsi {ltr} bernada negatif/melanggar: '{nc}' (pengecoh terlalu gampang dieliminasi)")
                        # If kecuali question and key is negative: glaring giveaway
                        elif is_kecuali and ltr == kunci:
                            issues.append(f"Kunci {ltr} (soal KECUALI) bernada negatif mencolok: '{nc}' (terlalu gampang ditebak)")
                
                for ac in absurd_cues:
                    if re.search(r'\b' + re.escape(ac) + r'\b', t_lower):
                        issues.append(f"Opsi {ltr} memuat kata absurd/tidak relevan: '{ac}'")
            
            # Check extreme length outlier
            lens = {ltr: len(txt) for ltr, txt in pilihan.items()}
            sorted_lens = sorted(lens.values())
            min_l, max_l = sorted_lens[0], sorted_lens[-1]
            key_l = lens.get(kunci, 0)
            avg_l = sum(sorted_lens) / len(sorted_lens)
            
            # If key is dramatically longer than all distractors (e.g. key > 1.8 * avg and diff > 25 chars)
            if key_l == max_l and (key_l - sorted_lens[-2] >= 22) and key_l > 45:
                issues.append(f"Kunci {kunci} mencolok paling panjang ({key_l} kar vs distractor maks {sorted_lens[-2]} kar)")
            # Or if key is dramatically shorter than all distractors
            elif key_l == min_l and (sorted_lens[1] - key_l >= 22) and sorted_lens[1] > 40:
                issues.append(f"Kunci {kunci} mencolok paling pendek ({key_l} kar vs distractor min {sorted_lens[1]} kar)")
            
            # Check if all 4 distractors share the same repetitive pattern or identical start word that key lacks
            starts = [txt.split()[0].lower() if txt.split() else '' for txt in pilihan.values()]
            key_start = pilihan.get(kunci, '').split()[0].lower() if pilihan.get(kunci, '').split() else ''
            dist_starts = [pilihan[l].split()[0].lower() for l in pilihan if l != kunci and pilihan[l].split()]
            if len(set(dist_starts)) == 1 and dist_starts[0] != key_start and len(dist_starts) == 4:
                issues.append(f"Pengecoh memiliki awalan seragam '{dist_starts[0]}' sedangkan kunci '{key_start}' berbeda (grammatical telltale)")

            if issues:
                results[mod_name][fname].append({
                    'nomor': nomor,
                    'kunci': kunci,
                    'pertanyaan': pert,
                    'pilihan': pilihan,
                    'issues': issues
                })

print("Audit complete! Printing results summary...")
total_flagged = 0
for mod_name, files in results.items():
    print(f"\n=======================================================")
    print(f"{mod_name}")
    print(f"=======================================================")
    mod_flagged = 0
    for fname, qlist in files.items():
        print(f"  {fname}: {len(qlist)} soal terindikasi belum cukup mengecoh / ada kelemahan opsi")
        mod_flagged += len(qlist)
        total_flagged += len(qlist)
        for q in qlist[:3]: # sample
            print(f"    - Soal {q['nomor']} (Kunci {q['kunci']}):")
            for iss in q['issues']:
                print(f"        * {iss}")
        if len(qlist) > 3:
            print(f"        ... dan {len(qlist) - 3} soal lainnya.")
    print(f"  Subtotal {mod_name}: {mod_flagged} soal")

print(f"\nTOTAL SOAL TERFLAG: {total_flagged} dari 2.300 soal")

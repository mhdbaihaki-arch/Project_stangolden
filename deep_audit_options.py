import glob
import json
import re
import os
from collections import defaultdict

modules = [
    ('Modul 2 - Nilai-Nilai Kemenkeu', 'soal_json/*.json'),
    ('Modul 3 - Tata Aturan Kepegawaian', 'soal_aturankepegawaian_json/*.json'),
    ('Modul 4 - Pengelolaan Keuangan Negara', 'soal_pengelolaan_keuangan_negara_json/*.json'),
    ('Modul 5 - Wawasan Kebangsaan', 'soal_wawasan_kebangsaan_json/*.json')
]

negative_words = [
    'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan',
    'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak',
    'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses',
    'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah',
    'tanpa dasar', 'suka-suka', 'santai', 'seenak', 'uang pelicin', 'suap',
    'kecurangan', 'curang', 'keuntungan pribadi', 'merugikan negara', 'tanpa izin',
    'ilegal', 'fiktif', 'menunda-nunda', 'memperberat', 'mempersulit', 'bermalas-malasan',
    'asal-asalan', 'manipulasi', 'diskriminatif', 'arogansi', 'arogan', 'kolusi',
    'nepotisme', 'meremehkan', 'acuh', 'apatis', 'membangkang', 'anarki', 'anarkis',
    'kekerasan', 'fanatisme', 'primordial', 'perpecahan', 'eksploitasi', 'ketertutupan',
    'permusuhan', 'memusuhi', 'memicu konflik', 'menindas', 'keserakahan', 'monopoli',
    'tirani', 'otoriter', 'penjajahan', 'pemberontakan', 'separatisme', 'separatis',
    'makar', 'kejahatan', 'pemerasan', 'kesewenang-wenangan', 'kemunduran'
]

absurd_words = [
    'panen', 'undian', 'warisan', 'paguyuban', 'purnabakti', 'purnatugas',
    'firasat', 'peramal', 'keluarga pejabat', 'dukun', 'arisan', 'lotre',
    'rekreasi', 'pesta', 'hiburan', 'doorprize', 'astrologi', 'mistis',
    'gaib', 'paranormal', 'primbon', 'zodiak', 'bintang kelahiran', 'wisata'
]

all_flagged = defaultdict(lambda: defaultdict(list))

for mod_name, pattern in modules:
    files = sorted(glob.glob(pattern))
    for fpath in files:
        fname = os.path.basename(fpath)
        with open(fpath, 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        soal_list = data.get('daftar_soal', [])
        for q in soal_list:
            nomor = q.get('nomor')
            pert = q.get('pertanyaan', '')
            pilihan = q.get('pilihan', {})
            kunci = q.get('kunci_jawaban', '')
            
            is_kecuali = bool(re.search(r'\b(KECUALI|BUKAN|TIDAK TEPAT|TIDAK TERMASUK)\b', pert))
            
            reasons = []
            
            # 1. Valence / Tone check
            # Normal question: distractors should NOT be clearly evil/negative
            if not is_kecuali:
                neg_distractors = []
                for ltr, txt in pilihan.items():
                    if ltr != kunci:
                        for nw in negative_words:
                            if re.search(r'\b' + re.escape(nw) + r'\b', txt.lower()):
                                neg_distractors.append((ltr, nw))
                if len(neg_distractors) >= 1:
                    reasons.append(f"Pengecoh memuat kata negatif/cacat: {', '.join([f'{ltr} ({w})' for ltr, w in neg_distractors])} -> membuat kunci positif terlalu mudah ditebak")
            else:
                # Except question: key should not be the lone evil option
                for nw in negative_words:
                    if re.search(r'\b' + re.escape(nw) + r'\b', pilihan.get(kunci, '').lower()):
                        reasons.append(f"Soal KECUALI tapi kunci memuat kata negatif mencolok: '{nw}' -> peserta langsung tebak opsi terjelek")

            # 2. Absurd words check
            for ltr, txt in pilihan.items():
                for aw in absurd_words:
                    if re.search(r'\b' + re.escape(aw) + r'\b', txt.lower()):
                        reasons.append(f"Opsi {ltr} memuat kata absurd/tidak kontekstual birokrasi: '{aw}'")

            # 3. Grammatical / Prefix giveaway (4 distractors share a prefix or grammatical form that key lacks)
            p_starts = {ltr: txt.split()[0].lower() if txt.split() else '' for ltr, txt in pilihan.items()}
            k_start = p_starts.get(kunci, '')
            dist_starts = [p_starts[l] for l in pilihan if l != kunci]
            if len(set(dist_starts)) == 1 and dist_starts[0] != k_start and dist_starts[0] != '':
                reasons.append(f"Bocoran gramatikal: 4 pengecoh berawalan '{dist_starts[0]}' sedangkan kunci berawalan '{k_start}'")

            # 4. Length outlier (key significantly longer than all distractors or key significantly shorter)
            lens = {ltr: len(txt) for ltr, txt in pilihan.items()}
            sorted_lens = sorted(lens.values())
            key_l = lens.get(kunci, 0)
            # If key is longest and differs from 2nd longest by >= 20 chars
            if key_l == sorted_lens[-1] and (key_l - sorted_lens[-2] >= 20) and key_l > 40:
                reasons.append(f"Kunci mencolok paling panjang ({key_l} karakter vs pengecoh terpanjang {sorted_lens[-2]} karakter)")
            # If key is shortest and differs from 2nd shortest by >= 20 chars
            elif key_l == sorted_lens[0] and (sorted_lens[1] - key_l >= 20) and sorted_lens[1] > 35:
                reasons.append(f"Kunci mencolok paling pendek ({key_l} karakter vs pengecoh terpendek {sorted_lens[1]} karakter)")

            if reasons:
                all_flagged[mod_name][fname].append({
                    'nomor': nomor,
                    'kunci': kunci,
                    'pertanyaan': pert,
                    'pilihan': pilihan,
                    'reasons': reasons
                })

print("Deep audit complete! Outputting full summary...")
grand_total = 0
with open('deep_audit_report.txt', 'w', encoding='utf-8') as out:
    for mod, f_dict in all_flagged.items():
        out.write(f"\n=======================================================\n")
        out.write(f"MODUL: {mod}\n")
        out.write(f"=======================================================\n")
        mod_cnt = 0
        for fn, ql in f_dict.items():
            out.write(f"\n--- {fn} (Total: {len(ql)} soal) ---\n")
            mod_cnt += len(ql)
            grand_total += len(ql)
            for q in ql:
                out.write(f"\n[No. {q['nomor']}] (Kunci: {q['kunci']})\n")
                out.write(f"Pertanyaan: {q['pertanyaan']}\n")
                for l, t in q['pilihan'].items():
                    k_mark = "  <-- KUNCI" if l == q['kunci'] else ""
                    out.write(f"  {l}. {t}{k_mark}\n")
                out.write(f"Kelemahan Opsi:\n")
                for r in q['reasons']:
                    out.write(f"  - {r}\n")
        out.write(f"\nSubtotal {mod}: {mod_cnt} soal\n")
    out.write(f"\nGRAND TOTAL: {grand_total} soal perlu diperbaiki opsi pengecohnya agar lebih mengecoh.\n")

print(f"Grand total flagged: {grand_total}. Report written to deep_audit_report.txt")

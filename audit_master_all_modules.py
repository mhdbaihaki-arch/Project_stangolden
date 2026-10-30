import json
import os
import re
import glob

# Setup rules
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

modules = [
    {
        'name': 'Modul 2 Nilai-Nilai Kemenkeu',
        'json_dir': 'soal_json',
        'modul_file': 'modul_json/2. Modul Nilai-Nilai Kemenkeu-UPKP.json'
    },
    {
        'name': 'Modul 3 Tata Aturan Kepegawaian',
        'json_dir': 'soal_aturankepegawaian_json',
        'modul_file': 'modul_json/3. Tata Aturan Kepegawaian.json'
    },
    {
        'name': 'Modul 4 Pengelolaan Keuangan Negara',
        'json_dir': 'soal_pengelolaan_keuangan_negara_json',
        'modul_file': 'modul_json/4. Pengelolaan Keuangan Negara.json'
    },
    {
        'name': 'Modul 5 Wawasan Kebangsaan',
        'json_dir': 'soal_wawasan_kebangsaan_json',
        'modul_file': 'modul_json/5. Wawasan Kebangsaan.json'
    }
]

summary_report = []

for m in modules:
    print(f"\n==========================================")
    print(f"AUDITING {m['name']}")
    print(f"==========================================")
    
    # load modul source text
    pages_dict = {}
    if os.path.exists(m['modul_file']):
        with open(m['modul_file'], 'r', encoding='utf-8') as f:
            modul_data = json.load(f)
            # handle both pages and materi formats
            if 'pages' in modul_data:
                for p in modul_data['pages']:
                    pages_dict[p['page']] = p['text']
            elif 'materi' in modul_data:
                for p in modul_data['materi']:
                    pages_dict[p['halaman']] = p['isi_teks']
    else:
        print(f"WARNING: Modul file not found: {m['modul_file']}")

    json_files = sorted(glob.glob(os.path.join(m['json_dir'], '*.json')))
    for jf in json_files:
        base_name = os.path.basename(jf)
        with open(jf, 'r', encoding='utf-8') as f:
            paket = json.load(f)

        total_q = len(paket.get('daftar_soal', []))
        key_counts = {'A': 0, 'B': 0, 'C': 0, 'D': 0, 'E': 0}
        last_key = None
        streak = 0
        max_streak = 0
        
        bl_hits = []
        meta_hits = []
        kw_misses = []

        for q in paket.get('daftar_soal', []):
            q_num = q['nomor']
            hlm = q.get('halaman_sumber')
            kunci = q.get('kunci_jawaban')
            if kunci in key_counts:
                key_counts[kunci] += 1
            
            if kunci == last_key:
                streak += 1
                if streak > max_streak:
                    max_streak = streak
            else:
                streak = 1
                last_key = kunci

            # verify keywords
            hlm_text = pages_dict.get(hlm, '')
            for kw in q.get('kata_kunci_modul', []):
                if hlm_text and (kw not in hlm_text):
                    kw_misses.append((q_num, hlm, kw))

            # verify blacklist in options
            for opt_ltr, opt_text in q.get('pilihan', {}).items():
                for bl in blacklist:
                    if re.search(r'\b' + re.escape(bl) + r'\b', opt_text, re.I):
                        bl_hits.append((q_num, opt_ltr, bl, opt_text))

            # verify meta patterns
            full_q_text = q.get('pertanyaan', '') + ' ' + json.dumps(q.get('pilihan', {})) + ' ' + json.dumps(q.get('pembahasan', {}))
            for mp in meta_patterns:
                if mp in full_q_text.lower():
                    meta_hits.append((q_num, mp))

        status = "PASS" if (len(bl_hits) == 0 and len(meta_hits) == 0 and len(kw_misses) == 0 and all(v == 20 for v in key_counts.values())) else "NEEDS FIX"
        print(f"[{status}] {base_name}: {total_q} Qs | Keys: {key_counts} (max_streak: {max_streak}) | BL: {len(bl_hits)} | META: {len(meta_hits)} | KW MISS: {len(kw_misses)}")

        if status == "NEEDS FIX":
            if bl_hits:
                print(f"   Blacklist sample (up to 5): {bl_hits[:5]}")
            if meta_hits:
                print(f"   Meta sample (up to 5): {meta_hits[:5]}")
            if kw_misses:
                print(f"   KW sample (up to 5): {kw_misses[:5]}")

        summary_report.append({
            'module': m['name'],
            'file': base_name,
            'total_q': total_q,
            'status': status,
            'keys': key_counts,
            'max_streak': max_streak,
            'bl_hits': len(bl_hits),
            'meta_hits': len(meta_hits),
            'kw_misses': len(kw_misses)
        })

print("\n==========================================")
print("AUDIT SUMMARY")
print("==========================================")
pass_count = sum(1 for r in summary_report if r['status'] == 'PASS')
print(f"Total files audited: {len(summary_report)} | PASS: {pass_count} | NEEDS FIX: {len(summary_report) - pass_count}")

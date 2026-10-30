import json
import re
import os

def build_tak_paket(paket_no, hlm_start, hlm_end):
    json_path = f'soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_{paket_no:02d}.json'
    template_path = 'soal_aturankepegawaian_php/STANGOLDEN_Tata_Aturan_Kepegawaian_Paket_01_Hlm_07-20.php'
    output_php_path = f'soal_aturankepegawaian_php/STANGOLDEN_Tata_Aturan_Kepegawaian_Paket_{paket_no:02d}_Hlm_{hlm_start:02d}-{hlm_end:02d}.php'

    with open(json_path, 'r', encoding='utf-8') as f:
        paket_data = json.load(f)

    letter_to_idx = {'A': 0, 'B': 1, 'C': 2, 'D': 3, 'E': 4}
    questions_js = []
    stg_keys = []

    for item in paket_data['daftar_soal']:
        q_num = item['nomor']
        hlm = item['halaman_sumber']
        kunci = item['kunci_jawaban']
        kunci_idx = letter_to_idx[kunci]
        stg_keys.append(kunci_idx)
        
        topik = item['topik_materi']
        pemb = item['pembahasan']
        konsep = pemb['konsep_materi']
        konsep_html = re.sub(r'\*\*(.*?)\*\*', r'<strong>\1</strong>', konsep)
        alasan = pemb['alasan_kunci']
        sumber = pemb.get('sumber_rujukan', 'Tata Aturan Kepegawaian')
        kata_kunci = ' • '.join(item.get('kata_kunci_modul', []))
        opsi_tepat = item['pilihan'][kunci]
        
        bedah_html_items = []
        for ltr in ['A', 'B', 'C', 'D', 'E']:
            if ltr != kunci:
                alasan_pengecoh = pemb['bedah_pengecoh'].get(ltr, '')
                bedah_html_items.append(f'<li><strong>{ltr}</strong>: {alasan_pengecoh}</li>')
        bedah_ul = ''.join(bedah_html_items)
        
        explanation_html = (
            f'<div class="learn-box lesson-card">'
            f'<div class="learn-title">📚 Intisari Materi • {topik}</div>'
            f'<div class="lesson-section"><div class="lesson-kicker">01 · Konsep Inti Textbook</div>'
            f'<p>{konsep_html}</p></div>'
            f'<div class="lesson-section lesson-reason"><div class="lesson-kicker">02 · Alasan Kunci Jawaban (AREL)</div>'
            f'<p>{alasan}</p>'
            f'<p>Jawaban tepat: <strong>{kunci}. {opsi_tepat}</strong></p></div>'
            f'<div class="lesson-section"><div class="lesson-kicker">03 · Bedah Opsi Lainnya</div>'
            f'<ul style="margin:6px 0 0;padding-left:20px;line-height:1.6">{bedah_ul}</ul></div>'
            f'<div class="lesson-source">📖 Referensi: {sumber} (Hlm. {hlm}) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>{kata_kunci}</strong></div>'
            f'</div>'
        )
        
        options_list = [
            item['pilihan']['A'],
            item['pilihan']['B'],
            item['pilihan']['C'],
            item['pilihan']['D'],
            item['pilihan']['E']
        ]
        
        questions_js.append({
            'category': 'TSKKWK',
            'sub': f'Tata Aturan Kepegawaian • Paket {paket_no:02d}',
            'difficulty': item['level_kognitif'],
            'question': item['pertanyaan'],
            'options': options_list,
            'answer': kunci_idx,
            'source': sumber,
            'explanation': explanation_html
        })

    with open(template_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Update docblock & comments
    content = content.replace(
        '* STANGOLDEN.exam • Tata Aturan Kepegawaian Paket 01 (Halaman 07–20)',
        f'* STANGOLDEN.exam • Tata Aturan Kepegawaian Paket {paket_no:02d} (Halaman {hlm_start:02d}–{hlm_end:02d})'
    )
    content = content.replace(
        '// $DB->get_record("stgtak_p01_rank") => tabel fisik "mdlhv_stgtak_p01_rank".',
        f'// $DB->get_record("stgtak_p{paket_no:02d}_rank") => tabel fisik "mdlhv_stgtak_p{paket_no:02d}_rank".'
    )
    content = content.replace("const STG_LB_T = 'stgtak_p01_rank';", f"const STG_LB_T = 'stgtak_p{paket_no:02d}_rank';")
    content = content.replace("const STG_REVIEW_T = 'stgtak_p01_rev';", f"const STG_REVIEW_T = 'stgtak_p{paket_no:02d}_rev';")
    content = content.replace("const STG_ANNO_T = 'stgtak_p01_anno';", f"const STG_ANNO_T = 'stgtak_p{paket_no:02d}_anno';")
    
    # 2. STG_KEY replacement
    old_key_match = re.search(r'const STG_KEY = \[.*?\];', content)
    if not old_key_match:
        raise ValueError("Could not find const STG_KEY in template")
    new_key_str = f"const STG_KEY = {json.dumps(stg_keys)};"
    content = content[:old_key_match.start()] + new_key_str + content[old_key_match.end():]

    # 3. Error logs and page titles
    content = content.replace('STANGOLDEN TAK P01 HLM07-20', f'STANGOLDEN TAK P{paket_no:02d} HLM{hlm_start:02d}-{hlm_end:02d}')
    content = content.replace(
        '<title>Latihan Tata Aturan Kepegawaian Paket 01 (Hlm. 07–20) • STANGOLDEN</title>',
        f'<title>Latihan Tata Aturan Kepegawaian Paket {paket_no:02d} (Hlm. {hlm_start:02d}–{hlm_end:02d}) • STANGOLDEN</title>'
    )
    content = content.replace(
        '<span class="series-tag">Paket 01 • Hlm. 07–20</span>',
        f'<span class="series-tag">Paket {paket_no:02d} • Hlm. {hlm_start:02d}–{hlm_end:02d}</span>'
    )
    content = content.replace(
        '<h1>Latihan Tata Aturan Kepegawaian <span class="series-tag">Paket 01 • Hlm. 07–20</span></h1>',
        f'<h1>Latihan Tata Aturan Kepegawaian <span class="series-tag">Paket {paket_no:02d} • Hlm. {hlm_start:02d}–{hlm_end:02d}</span></h1>'
    )
    content = content.replace(
        '<h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Tata Aturan Kepegawaian Paket 01</h2>',
        f'<h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Tata Aturan Kepegawaian Paket {paket_no:02d}</h2>'
    )
    content = content.replace(
        'modalTitle.textContent="🏆 Lihat Ranking Tata Aturan Kepegawaian Paket 01"',
        f'modalTitle.textContent="🏆 Lihat Ranking Tata Aturan Kepegawaian Paket {paket_no:02d}"'
    )
    
    # 4. Storage keys
    content = content.replace(
        'const STG_LOCAL_KEY="stg_tak_p01_review_user_<?= (int)$USER->id ?>";',
        f'const STG_LOCAL_KEY="stg_tak_p{paket_no:02d}_review_user_<?= (int)$USER->id ?>";'
    )
    content = content.replace(
        "const ANNO_KEY='stangolden_tak_p01_review_annot_v1_user_<?= (int)$USER->id ?>';",
        f"const ANNO_KEY='stangolden_tak_p{paket_no:02d}_review_annot_v1_user_<?= (int)$USER->id ?>';"
    )
    content = content.replace('stg_tak_p01_map_visible', f'stg_tak_p{paket_no:02d}_map_visible')

    # 5. Questions JS array replacement
    old_q_match = re.search(r'const questions=\[.*?\];\n', content)
    if not old_q_match:
        raise ValueError("Could not find const questions= in template")
    new_q_str = f"const questions={json.dumps(questions_js, ensure_ascii=False)};\n"
    content = content[:old_q_match.start()] + new_q_str + content[old_q_match.end():]

    # Write to target PHP
    with open(output_php_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Generated PHP: {output_php_path} ({os.path.getsize(output_php_path)} bytes)")

if __name__ == '__main__':
    build_tak_paket(5, 52, 61)

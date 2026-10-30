import json
import re
import os

ranges = {
    1: (5, 14),
    2: (15, 24),
    3: (25, 34),
    4: (35, 48)
}

letter_to_idx = {'A': 0, 'B': 1, 'C': 2, 'D': 3, 'E': 4}

for pno, (hlm_start, hlm_end) in ranges.items():
    json_path = f'soal_json/2. Modul Nilai-Nilai Kemenkeu-UPKP_paket_{pno:02d}.json'
    php_path = f'soal_php/STANGOLDEN_Nilai_Kemenkeu_Paket_{pno:02d}_Hlm_{hlm_start:02d}-{hlm_end:02d}.php'
    
    if not os.path.exists(php_path):
        print(f"Skipping {php_path}, not found.")
        continue

    with open(json_path, 'r', encoding='utf-8') as f:
        pdata = json.load(f)

    stg_keys = []
    questions_js = []

    for item in pdata['daftar_soal']:
        kunci = item['kunci_jawaban']
        kunci_idx = letter_to_idx[kunci]
        stg_keys.append(kunci_idx)

        topik = item['topik_materi']
        pemb = item['pembahasan']
        konsep = pemb['konsep_materi']
        konsep_html = re.sub(r'\*\*(.*?)\*\*', r'<strong>\1</strong>', konsep)
        alasan = pemb['alasan_kunci']
        sumber = pemb.get('sumber_rujukan', 'Nilai-Nilai Kementerian Keuangan')
        kata_kunci = ' • '.join(item.get('kata_kunci_modul', []))
        opsi_tepat = item['pilihan'][kunci]

        bedah_html_items = []
        for ltr in ['A', 'B', 'C', 'D', 'E']:
            if ltr != kunci:
                alasan_pengecoh = pemb['bedah_pengecoh'].get(ltr, '')
                bedah_html_items.append(f'<li><strong>{ltr}</strong>: {alasan_pengecoh}</li>')
        bedah_ul = ''.join(bedah_html_items)

        hlm = item['halaman_sumber']
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
            f'<div class="lesson-source">📖 Referensi: {sumber} (Hlm. {hlm:02d}) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>{kata_kunci}</strong></div>'
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
            'category': 'NNK',
            'sub': f'Nilai-Nilai Kemenkeu • Paket {pno:02d}',
            'difficulty': item['level_kognitif'],
            'question': item['pertanyaan'],
            'options': options_list,
            'answer': kunci_idx,
            'source': sumber,
            'explanation': explanation_html
        })

    with open(php_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # replace STG_KEY
    old_key_match = re.search(r'const STG_KEY = \[.*?\];', content)
    if old_key_match:
        new_key_str = f"const STG_KEY = {json.dumps(stg_keys)};"
        content = content[:old_key_match.start()] + new_key_str + content[old_key_match.end():]

    # replace questions
    old_q_match = re.search(r'const questions=\[.*?\];\n', content)
    if old_q_match:
        new_q_str = f"const questions={json.dumps(questions_js, ensure_ascii=False)};\n"
        content = content[:old_q_match.start()] + new_q_str + content[old_q_match.end():]

    with open(php_path, 'w', encoding='utf-8') as f:
        f.write(content)

    print(f"Updated PHP: {php_path} ({os.path.getsize(php_path)} bytes)")

print("Modul 2 PHP files updated successfully!")

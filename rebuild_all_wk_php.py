import json
import re
import os
import subprocess

wk_packages = [
    (1, '06-15'),
    (2, '16-25'),
    (3, '26-35'),
    (4, '36-45'),
    (5, '46-55'),
    (6, '56-65'),
    (7, '66-75')
]

letter_to_idx = {'A': 0, 'B': 1, 'C': 2, 'D': 3, 'E': 4}

for pkt_no, hlm_range in wk_packages:
    json_path = f'soal_wawasan_kebangsaan_json/5. Wawasan Kebangsaan_paket_{pkt_no:02d}.json'
    php_path = f'soal_wawasan_kebangsaan_php/STANGOLDEN_Wawasan_Kebangsaan_Paket_{pkt_no:02d}_Hlm_{hlm_range}.php'
    
    with open(json_path, 'r', encoding='utf-8') as f:
        jdata = json.load(f)
        
    with open(php_path, 'r', encoding='utf-8') as f:
        php_content = f.read()
        
    questions_list = []
    stg_keys = []
    
    for item in jdata['daftar_soal']:
        kunci = item['kunci_jawaban']
        kunci_idx = letter_to_idx[kunci]
        stg_keys.append(kunci_idx)
        
        topik = item['topik_materi']
        pemb = item['pembahasan']
        konsep = pemb['konsep_materi']
        konsep_html = re.sub(r'\*\*(.*?)\*\*', r'<strong>\1</strong>', konsep)
        alasan = pemb['alasan_kunci']
        sumber = pemb.get('sumber_rujukan', 'Wawasan Kebangsaan')
        hlm = item.get('halaman_sumber', '')
        hlm_str = f"{hlm:02d}" if isinstance(hlm, int) else str(hlm)
        kata_kunci = ' • '.join(item.get('kata_kunci_modul', []))
        opsi_tepat = item['pilihan'][kunci]
        
        bedah_html_items = []
        for ltr in ['A', 'B', 'C', 'D', 'E']:
            if ltr != kunci:
                pengecoh = pemb['bedah_pengecoh'].get(ltr, '')
                bedah_html_items.append(f'<li><strong>{ltr}</strong>: {pengecoh}</li>')
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
            f'<div class="lesson-source">📖 Referensi: {sumber} (Hlm. {hlm_str}) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>{kata_kunci}</strong></div>'
            f'</div>'
        )
        
        options_list = [item['pilihan'][ltr] for ltr in ['A', 'B', 'C', 'D', 'E']]
        
        q_obj = {
            "category": "WK",
            "sub": f"Wawasan Kebangsaan • Paket {pkt_no:02d}",
            "difficulty": item.get('level_kognitif', 'C2 - Pemahaman'),
            "question": item['pertanyaan'],
            "options": options_list,
            "answer": kunci_idx,
            "source": sumber,
            "explanation": explanation_html
        }
        questions_list.append(q_obj)
        
    # Replace STG_KEY
    stg_key_str = f"const STG_KEY = {json.dumps(stg_keys)};"
    php_content = re.sub(r'const\s+STG_KEY\s*=\s*\[.*?\];', stg_key_str, php_content)
    
    # Replace const questions
    questions_json_str = json.dumps(questions_list, ensure_ascii=False)
    php_content = re.sub(r'const\s+questions\s*=\s*\[.*?\];', f'const questions={questions_json_str};', php_content, flags=re.DOTALL)
    
    with open(php_path, 'w', encoding='utf-8') as f:
        f.write(php_content)
        
    print(f"Rebuilt WK Paket {pkt_no:02d} ({php_path})")

print("Validating PHP syntax...")
for pkt_no, hlm_range in wk_packages:
    php_path = f'soal_wawasan_kebangsaan_php/STANGOLDEN_Wawasan_Kebangsaan_Paket_{pkt_no:02d}_Hlm_{hlm_range}.php'
    res = subprocess.run(['php', '-l', php_path], capture_output=True, text=True)
    if 'No syntax errors' in res.stdout:
        print(f"  {os.path.basename(php_path)}: SYNTAX OK")
    else:
        print(f"  {os.path.basename(php_path)}: ERROR {res.stderr}")

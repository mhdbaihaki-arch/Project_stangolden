import json
import re
import os
import sys

sys.stdout.reconfigure(encoding='utf-8')

def build_etika_php(paket_no, hlm_start, hlm_end):
    json_path = f"soal_etikapns_json/1. Modul_Etika PNS_paket_{paket_no:02d}.json"
    php_path = f"soal_etikapns_php/STANGOLDEN_Etika_PNS_Paket_{paket_no:02d}_Hlm_{hlm_start:02d}-{hlm_end:02d}.php"
    template_php_path = "soal_php/STANGOLDEN_Nilai_Kemenkeu_Paket_01_Hlm_05-14.php"
    
    with open(template_php_path, "r", encoding="utf-8") as f:
        template = f.read()
        
    with open(json_path, "r", encoding="utf-8") as f:
        data = json.load(f)
        
    letter_to_idx = {'A': 0, 'B': 1, 'C': 2, 'D': 3, 'E': 4}
    
    stg_keys = []
    questions_js = []
    
    for item in data['daftar_soal']:
        kunci = item['kunci_jawaban']
        kunci_idx = letter_to_idx[kunci]
        stg_keys.append(kunci_idx)
        
        topik = item['topik_materi']
        pemb = item['pembahasan']
        konsep = pemb['konsep_materi']
        konsep_html = re.sub(r'\*\*(.*?)\*\*', r'<strong>\1</strong>', konsep)
        alasan = pemb['alasan_kunci']
        sumber = pemb.get('sumber_rujukan', 'Etika Pegawai Negeri Sipil')
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
            'category': 'ETIKA',
            'sub': f'Etika PNS • Paket {paket_no:02d}',
            'difficulty': item['level_kognitif'],
            'question': item['pertanyaan'],
            'options': options_list,
            'answer': kunci_idx,
            'source': sumber,
            'explanation': explanation_html
        })
        
    total_q = len(questions_js)
    content = template
    
    # Replace constants for tables and keys
    content = content.replace("stgnk_p01_rank", f"stgnk_etika_p{paket_no:02d}_rank")
    content = content.replace("stgnk_p01_rev", f"stgnk_etika_p{paket_no:02d}_rev")
    content = content.replace("stgnk_p01_anno", f"stgnk_etika_p{paket_no:02d}_anno")
    content = content.replace("stg_nk_p01_review_user_", f"stg_etika_p{paket_no:02d}_review_user_")
    content = content.replace("stangolden_kemenkeu_p01_review_annot_v1_user_", f"stangolden_etika_p{paket_no:02d}_review_annot_v1_user_")
    
    # Replace JSON path in admin save backend
    content = re.sub(
        r"\$jsonPath\s*=\s*__DIR__\s*\.\s*'/.*?\.json';",
        f"$jsonPath = __DIR__ . '/../soal_etikapns_json/1. Modul_Etika PNS_paket_{paket_no:02d}.json';",
        content
    )
    
    # Replace titles and labels
    content = content.replace("Nilai-Nilai Kementerian Keuangan — Paket 01 (Halaman 05–14)", f"Etika Pegawai Negeri Sipil — Paket {paket_no:02d} (Halaman {hlm_start:02d}–{hlm_end:02d})")
    content = content.replace("Nilai-Nilai Kementerian Keuangan - Paket 01", f"Etika Pegawai Negeri Sipil - Paket {paket_no:02d}")
    content = content.replace("Paket 01 (Halaman 05–14)", f"Paket {paket_no:02d} (Halaman {hlm_start:02d}–{hlm_end:02d})")
    
    # If total_q != 100, dynamically adjust validation and display
    if total_q != 100:
        content = content.replace("$qIdx >= 100", f"$qIdx >= {total_q}")
        content = content.replace("(1-100)", f"(1-{total_q})")
        content = content.replace("count($allQuestions) !== 100", f"count($allQuestions) !== {total_q}")
        content = content.replace("Format 100 soal", f"Format {total_q} soal")
        content = content.replace("count($allKeys) === 100", f"count($allKeys) === {total_q}")
        content = content.replace("count($a)!==100||array_keys($a)!==range(0,99)", f"count($a)!=={total_q}||array_keys($a)!==range(0,{total_q-1})")
        content = content.replace("$q >= 100", f"$q >= {total_q}")
        content = content.replace("count($a)!==100", f"count($a)!=={total_q}")
        content = content.replace("<strong>100 Soal</strong>", f"<strong>{total_q} Soal</strong>")
        content = content.replace("0 dari 100 dijawab", f"0 dari {total_q} dijawab")
        content = content.replace("${row.correct}/100", f"${{row.correct}}/{total_q}")
        content = content.replace("Nomor 4-100", f"Nomor 4-{total_q}")
        content = content.replace("Bagian II (No. 51-100)", f"Bagian II (No. {total_q//2 + 1}-{total_q})")
    
    # Replace STG_KEY
    key_match = re.search(r'const STG_KEY = \[.*?\];', content)
    if key_match:
        new_key_str = f"const STG_KEY = {json.dumps(stg_keys)};"
        content = content[:key_match.start()] + new_key_str + content[key_match.end():]
        
    # Replace questions
    q_match = re.search(r'const questions=\[.*?\];\n', content)
    if q_match:
        new_q_str = f"const questions={json.dumps(questions_js, ensure_ascii=False)};\n"
        content = content[:q_match.start()] + new_q_str + content[q_match.end():]
        
    with open(php_path, "w", encoding="utf-8") as f:
        f.write(content)
        
    print(f"✅ Generated PHP file: {php_path} ({os.path.getsize(php_path):,} bytes, {total_q} questions)")

if __name__ == "__main__":
    build_etika_php(1, 4, 13)

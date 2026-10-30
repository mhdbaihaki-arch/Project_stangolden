import json
import re

with open('soal_aturankepegawaian_json/3. Tata Aturan Kepegawaian_paket_01.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

soal_list = data['daftar_soal']

blacklist_words = [
    'mengabaikan', 'menolak', 'memalsukan', 'korupsi', 'pungli', 'membiarkan', 
    'lepas tangan', 'menyembunyikan', 'melanggar', 'seenaknya', 'sepihak', 
    'semena-mena', 'pasif', 'kaku', 'ego sektoral', 'menutup akses', 
    'menghindari', 'melempar tanggung jawab', 'hanya', 'semata-mata', 'tidak pernah'
]

cues_negative = [
    'palsu', 'liar', 'firasat', 'peramal', 'seenaknya', 'tanpa dasar',
    'suka-suka', 'menghapus', 'curang', 'lambat', 'santai', 'pribadi',
    'keuntungan', 'kerabat', 'keluarga'
]

with open('tak_p01_issues.txt', 'w', encoding='utf-8') as out:
    for q in soal_list:
        no = q['nomor']
        hlm = q['halaman_sumber']
        key = q['kunci_jawaban']
        pilihan = q['pilihan']
        
        bl_found = []
        for opt, txt in pilihan.items():
            for bw in blacklist_words:
                if re.search(r'\b' + re.escape(bw) + r'\b', txt, re.IGNORECASE):
                    bl_found.append(f"[{opt}] {bw}: {txt}")
                    
        cues_found = []
        for opt, txt in pilihan.items():
            if opt != key:
                for c in cues_negative:
                    if re.search(r'\b' + re.escape(c) + r'\b', txt, re.IGNORECASE):
                        cues_found.append(f"[{opt}] {c}: {txt}")
                        
        out.write(f"NO {no} (Hlm {hlm}, Kunci {key})\n")
        out.write(f"KW: {q.get('kata_kunci_modul')}\n")
        out.write(f"Q: {q['pertanyaan']}\n")
        for opt in ['A', 'B', 'C', 'D', 'E']:
            mark = " [KUNCI]" if opt == key else ""
            out.write(f"  {opt}: {pilihan[opt]}{mark}\n")
        if bl_found:
            out.write(f"  BLACKLIST: {bl_found}\n")
        if cues_found:
            out.write(f"  NEGATIVE CUES: {cues_found}\n")
        out.write("-" * 60 + "\n")

print("Saved tak_p01_issues.txt")

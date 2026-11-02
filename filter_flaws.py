import glob
import json
import re

with open('deep_audit_report.txt', 'r', encoding='utf-8') as f:
    raw_text = f.read()

# Let's write an evaluator script that inspects each of the 83 questions
from deep_audit_options import all_flagged

genuine_flaws = []
false_positives = []

for mod_name, files in all_flagged.items():
    for fname, qlist in files.items():
        for q in qlist:
            nomor = q['nomor']
            pert = q['pertanyaan']
            pilihan = q['pilihan']
            kunci = q['kunci']
            reasons = q['reasons']
            
            # Check reasons
            is_genuine = False
            verified_reasons = []
            
            for r in reasons:
                # 1. Bocoran gramatikal -> ALWAYS genuine flaw!
                if "Bocoran gramatikal" in r:
                    is_genuine = True
                    verified_reasons.append(r)
                
                # 2. Absurd words -> Check context
                elif "kata absurd" in r:
                    # check if it's really absurd or historical context
                    # e.g. 'gaib' in norma hukum: 'Kutukan gaib yang datang dari roh nenek moyang' -> absurd distractor!
                    # e.g. 'pesta demokrasi' -> 'pesta' is a normal Indonesian phrase for pemilu!
                    # If 'pesta' is in 'pesta demokrasi pemilihan umum', it's NOT absurd, it's normal idiom!
                    is_real_absurd = False
                    for ltr, txt in pilihan.items():
                        txt_l = txt.lower()
                        if 'pesta demokrasi' in txt_l:
                            continue # not absurd
                        if any(w in txt_l for w in ['gaib', 'rekreasi', 'arisan', 'wisata', 'pesta rakyat', 'hiburan malam']):
                            is_real_absurd = True
                            verified_reasons.append(f"Opsi {ltr} memuat pengecoh konyol/tidak relevan untuk konteks ASN: '{txt}'")
                    if is_real_absurd:
                        is_genuine = True
                
                # 3. Negative words -> Check if question is asking about bad things
                elif "Pengecoh memuat kata negatif" in r or "Kunci memuat kata negatif" in r:
                    # If question is about ethics violation, e.g. "Larangan etika integritas melarang...", then key or options naturally have negative words!
                    pert_l = pert.lower()
                    if any(w in pert_l for w in ['larangan', 'pelanggaran', 'sanksi', 'hukuman', 'peristiwa tragis', 'ancaman', 'duplikasi']):
                        # If question asks about violation, having negative words might be contextual!
                        # BUT check if 4 options are positive and 1 is negative, or vice versa
                        pass
                    else:
                        # Question is asking about normal positive/neutral concept
                        is_genuine = True
                        verified_reasons.append(r)
                
                # 4. Length outlier -> ALWAYS genuine flaw
                elif "Kunci mencolok paling panjang" in r or "Kunci mencolok paling pendek" in r:
                    is_genuine = True
                    verified_reasons.append(r)

            if is_genuine and verified_reasons:
                genuine_flaws.append({
                    'modul': mod_name,
                    'file': fname,
                    'nomor': nomor,
                    'kunci': kunci,
                    'pertanyaan': pert,
                    'pilihan': pilihan,
                    'reasons': verified_reasons
                })
            else:
                false_positives.append({
                    'modul': mod_name,
                    'file': fname,
                    'nomor': nomor,
                    'pertanyaan': pert,
                    'reasons': reasons
                })

print(f"Genuine flaws: {len(genuine_flaws)}, False positives filtered: {len(false_positives)}")

with open('genuine_flaws_report.txt', 'w', encoding='utf-8') as out:
    for item in genuine_flaws:
        out.write(f"[{item['modul']}] {item['file']} - Soal No. {item['nomor']} (Kunci {item['kunci']})\n")
        out.write(f"Pertanyaan: {item['pertanyaan']}\n")
        for ltr, txt in item['pilihan'].items():
            k_mark = "  <-- KUNCI" if ltr == item['kunci'] else ""
            out.write(f"  {ltr}. {txt}{k_mark}\n")
        out.write("Alasan belum mengecoh:\n")
        for r in item['reasons']:
            out.write(f"  * {r}\n")
        out.write("\n")

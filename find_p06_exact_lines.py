import json
import os

scratch_dir = r"C:\Users\Akhlan_Dev\.gemini\antigravity-ide\brain\56678c1c-cbc4-4920-9fcf-4e6ee9173ad2\scratch"
with open(os.path.join(scratch_dir, "wk_p06_raw_text.txt"), "r", encoding="utf-8") as f:
    raw_text = f.read()

snippets = [
    ("Q3", "sesingkat-singkatnya"),
    ("Q5", "17 Agustus 1950"),
    ("Q6", "Konstitusi RIS"),
    ("Q8", "ditandatangani"),
    ("Q9", "Republik Indonesia (Jogjakarta)"),
    ("Q11", "Perdana Menteri"),
    ("Q12", "DPR/ Parlemen"),
    ("Q15", "5 Juli 1959"),
    ("Q16", "G-30-S/PKI"),
    ("Q18", "Supersemar"),
    ("Q19", "Orde Baru"),
    ("Q20", "empat periode"),
    ("Q21", "Ir. Soekarno"),
    ("Q22", "Presiden tetap"),
    ("Q24", "Hak Budget"),
    ("Q29", "11 Maret 1966"),
    ("Q31", "TAP MPR No.I/MPR/1983"),
    ("Q32", "VII/MPR/"),
    ("Q34", "VIII/MPR/1998"),
    ("Q35", "21 Mei 1998"),
    ("Q36", "Pasca Orde Baru"),
    ("Q37", "19 Oktober 1999"),
    ("Q39", "pembentuk UU"),
    ("Q40", "18 Agustus 2000"),
    ("Q43", "melantik Presiden"),
    ("Q45", "usul DPR"),
    ("Q46", "membekukan"),
    ("Q47", "DPD,"),
    ("Q48", "Bab baru"),
    ("Q50", "DPA"),
    ("Q54", "Penjelasan"),
    ("Q55", "adendum"),
    ("Q56", "IX/MPR/1999"),
    ("Q57", "I/MPR/2002"),
    ("Q58", "membatasi"),
    ("Q59", "wilayah negara"),
    ("Q60", "Sidang Tahunan"),
    ("Q62", "penghapusan"),
    ("Q64", "71 butir"),
    ("Q66", "perekonomian"),
    ("Q67", "kebudayaan"),
    ("Q69", "9 November 2001"),
    ("Q72", "tidak sedikitpun"),
    ("Q73", "memperkokoh"),
    ("Q75", "1928"),
    ("Q76", "Founding Fathers"),
    ("Q77", "bentuk final"),
    ("Q78", "majemuk"),
    ("Q79", "komprehensif"),
    ("Q80", "WAWASAN KEBANGSAAN"),
    ("Q88", "Satu Nusa"),
    ("Q91", "etimologis")
]

for q_label, snip in snippets:
    matches = [line.strip() for line in raw_text.splitlines() if snip.lower() in line.lower()]
    print(f"{q_label} [{snip}]:")
    for m in matches[:3]:
        print(f"   {m!r}")

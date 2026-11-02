from filter_flaws import genuine_flaws

print("=== MODUL 4 SAMPLES ===")
m4 = [q for q in genuine_flaws if 'Pengelolaan Keuangan Negara' in q['modul']]
for q in m4:
    print(f"{q['file']} No {q['nomor']} (Key {q['kunci']}):")
    print(f"  Pert: {q['pertanyaan']}")
    for ltr, txt in q['pilihan'].items():
        km = " [KUNCI]" if ltr == q['kunci'] else ""
        print(f"    {ltr}. {txt}{km}")
    for r in q['reasons']:
        print(f"    -> {r}")
    print()

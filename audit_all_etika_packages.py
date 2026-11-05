# -*- coding: utf-8 -*-
"""
Full Audit for All 3 Packages of Modul 1: Etika PNS
"""
import json
import subprocess
import os
import sys

sys.stdout.reconfigure(encoding='utf-8')
php_exe = r"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"

packages = [
    {
        "paket": 1,
        "json": "soal_etikapns_json/1. Modul_Etika PNS_paket_01.json",
        "php": "soal_etikapns_php/STANGOLDEN_Etika_PNS_Paket_01_Hlm_04-13.php",
        "expected_count": 100,
        "hlm": "04-13"
    },
    {
        "paket": 2,
        "json": "soal_etikapns_json/1. Modul_Etika PNS_paket_02.json",
        "php": "soal_etikapns_php/STANGOLDEN_Etika_PNS_Paket_02_Hlm_14-23.php",
        "expected_count": 100,
        "hlm": "14-23"
    },
    {
        "paket": 3,
        "json": "soal_etikapns_json/1. Modul_Etika PNS_paket_03.json",
        "php": "soal_etikapns_php/STANGOLDEN_Etika_PNS_Paket_03_Hlm_24-31.php",
        "expected_count": 80,
        "hlm": "24-31"
    }
]

total_soal_all = 0
for p in packages:
    print(f"\n==========================================")
    print(f"AUDIT PAKET {p['paket']} ({p['hlm']})")
    print(f"==========================================")
    
    # 1. Check JSON
    assert os.path.exists(p['json']), f"JSON not found: {p['json']}"
    with open(p['json'], 'r', encoding='utf-8') as f:
        data = json.load(f)
        
    soal_list = data['daftar_soal']
    count = len(soal_list)
    print(f"Jumlah soal di JSON: {count} (Expected: {p['expected_count']})")
    assert count == p['expected_count']
    total_soal_all += count
    
    # Check keys
    keys = [q['kunci_jawaban'] for q in soal_list]
    from collections import Counter
    cnt = Counter(keys)
    print(f"Distribusi kunci: {dict(cnt)}")
    expected_per_letter = count // 5
    for ltr in "ABCDE":
        assert cnt[ltr] == expected_per_letter, f"Letter {ltr} count {cnt[ltr]} != {expected_per_letter}"
        
    # Check PHP exists and syntax
    assert os.path.exists(p['php']), f"PHP not found: {p['php']}"
    php_size = os.path.getsize(p['php'])
    print(f"PHP file: {p['php']} ({php_size:,} bytes)")
    
    res = subprocess.run([php_exe, "-l", p['php']], capture_output=True, text=True)
    print(f"PHP Lint: {res.stdout.strip()}")
    assert "No syntax errors detected" in res.stdout, f"Syntax error in {p['php']}: {res.stderr}"

print(f"\n[SUCCESS] ALL 3 PACKAGES AUDITED SUCCESSFULLY!")
print(f"Total Soal: {total_soal_all} butir soal lengkap across 28 halaman modul.")

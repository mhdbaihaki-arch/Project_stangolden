import subprocess
import glob
import os

php_bin = r'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'

dirs = [
    'soal_php',
    'soal_aturankepegawaian_php',
    'soal_pengelolaan_keuangan_negara_php',
    'soal_wawasan_kebangsaan_php'
]

total_checked = 0
failed = 0

for d in dirs:
    print(f"\nVerifying PHP files in {d}...")
    files = sorted(glob.glob(os.path.join(d, '*.php')))
    for f in files:
        res = subprocess.run([php_bin, '-l', f], capture_output=True, text=True)
        total_checked += 1
        if res.returncode == 0:
            print(f"  [OK] {os.path.basename(f)}")
        else:
            print(f"  [FAILED] {os.path.basename(f)}: {res.stderr or res.stdout}")
            failed += 1

print("\n" + "="*40)
print(f"TOTAL PHP FILES VERIFIED: {total_checked}")
print(f"PASSED: {total_checked - failed} | FAILED: {failed}")
print("="*40)

import subprocess, glob, os, time

php_bin = r'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'

for d in ['soal_aturankepegawaian_php', 'soal_pengelolaan_keuangan_negara_php', 'soal_wawasan_kebangsaan_php']:
    print(f"\n--- Checking {d} ---")
    for f in sorted(glob.glob(os.path.join(d, '*.php'))):
        try:
            res = subprocess.run([php_bin, '-l', f], capture_output=True, text=True)
            print(f"  {os.path.basename(f)}: {res.stdout.strip()}")
        except Exception as e:
            print(f"  {os.path.basename(f)}: ERROR {e}")
        time.sleep(0.3)

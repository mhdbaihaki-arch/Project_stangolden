import os, subprocess

php_exe = r"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
target = r"soal_etikapns_php\STANGOLDEN_Etika_PNS_Paket_01_Hlm_04-13.php"

res = subprocess.run([php_exe, "-l", target], capture_output=True, text=True)
print("Stdout:", res.stdout)
print("Stderr:", res.stderr)
print("Return code:", res.returncode)

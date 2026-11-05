import sys
sys.stdout.reconfigure(encoding='utf-8')

with open("build_modul2_php.py", "r", encoding="utf-8") as f:
    text = f.read()

for line in text.splitlines()[50:110]:
    print(line)

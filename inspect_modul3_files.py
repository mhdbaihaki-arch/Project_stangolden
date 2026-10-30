import os
import glob
import json

print("=== Modul JSON files ===")
for f in glob.glob("modul_json/*"):
    print(f)

print("\n=== Soal Aturan Kepegawaian JSON files ===")
for f in sorted(glob.glob("soal_aturankepegawaian_json/*")):
    print(f)

print("\n=== Soal Aturan Kepegawaian PHP files ===")
for f in sorted(glob.glob("soal_aturankepegawaian_php/*")):
    print(f)

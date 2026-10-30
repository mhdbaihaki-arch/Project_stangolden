import json

with open('modul_json/3. Tata Aturan Kepegawaian.json', 'r', encoding='utf-8') as f:
    modul = json.load(f)

page_texts = {int(p['page']): p['text'] for p in modul['pages']}

# Q90
p19 = page_texts[19]
kw_q90 = [
    "menetapkan Kebijakan Perencanaan Kebutuhan Pegawai ASN secara",
    "pembangunan jangka menengah nasional serta dengan mempertimbangkan"
]
for k in kw_q90:
    print(f"Q90 '{k}': {k in p19}")

# Q94
p20 = page_texts[20]
kw_q94 = [
    "secara nasional. Jabatan dalam suatu instansi pemerintah yang dapat diisi",
    "(khusus pada Jabatan Pelaksana), Jabatan Fungsional Keahlian (yaitu"
]
for k in kw_q94:
    print(f"Q94 '{k}': {k in p20}")

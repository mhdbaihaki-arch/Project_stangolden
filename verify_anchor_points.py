import glob

anchor = "$stgaction=optional_param('action','',PARAM_ALPHANUMEXT);"
closing_body = '</body></html>'

missing_anchor = []
missing_body = []

for f in sorted(glob.glob('soal_*php/*.php')):
    with open(f, 'r', encoding='utf-8') as fp:
        c = fp.read()
    if anchor not in c:
        missing_anchor.append(f)
    if closing_body not in c:
        missing_body.append(f)

print(f"Missing anchor: {len(missing_anchor)}")
print(f"Missing body: {len(missing_body)}")
if not missing_anchor and not missing_body:
    print("ALL 23 PHP FILES HAVE EXACT SAME ANCHOR POINTS!")

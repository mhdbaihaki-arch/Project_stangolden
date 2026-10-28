import os
import sys
import time
import json
from pure_converter import PurePDFToJSONConverter

if sys.platform == "win32":
    try:
        sys.stdout.reconfigure(encoding="utf-8")
        sys.stderr.reconfigure(encoding="utf-8")
    except AttributeError:
        pass

PROJECT_DIR = r"C:\Users\Akhlan_Dev\Downloads\project"
OUTPUT_DIR = os.path.join(PROJECT_DIR, "modul_json")

def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    
    # Hapus file-file kuis lama jika ada di modul_json
    for old_f in os.listdir(OUTPUT_DIR):
        if old_f.endswith("_quiz.json") or old_f.startswith("INDEX_SUMMARY"):
            try:
                os.remove(os.path.join(OUTPUT_DIR, old_f))
            except Exception:
                pass

    pdf_files = [f for f in os.listdir(PROJECT_DIR) if f.lower().endswith(".pdf")]
    pdf_files.sort()

    print("================================================================================")
    print("📖 KONVERSI MURNI DOKUMEN PDF KE JSON (TANPA FORMAT SOAL / PROMPT AI)")
    print("================================================================================")
    print(f"📁 Folder Sumber : {PROJECT_DIR}")
    print(f"📁 Folder Output : {OUTPUT_DIR}")
    print(f"📄 Total Dokumen : {len(pdf_files)} PDF")
    print("--------------------------------------------------------------------------------\n")

    converter = PurePDFToJSONConverter()
    total_start = time.time()
    summary = []

    for idx, pdf_name in enumerate(pdf_files, 1):
        pdf_path = os.path.join(PROJECT_DIR, pdf_name)
        file_size_mb = os.path.getsize(pdf_path) / (1024 * 1024)

        print(f"[{idx}/{len(pdf_files)}] ⏳ Mengekstrak teks murni: {pdf_name} ({file_size_mb:.2f} MB)...")
        t0 = time.time()

        try:
            result = converter.extract(pdf_path)

            base_name = os.path.splitext(pdf_name)[0]
            clean_base_name = base_name.replace("(DONE) ", "").strip()
            out_name = f"{clean_base_name}.json"
            out_path = os.path.join(OUTPUT_DIR, out_name)

            with open(out_path, "w", encoding="utf-8") as f:
                json.dump(result, f, ensure_ascii=False, indent=2)

            dur = time.time() - t0
            meta = result["metadata"]

            print(f"      ✅ Berhasil ({dur:.2f} detik)")
            print(f"         - Halaman   : {meta['total_pages']} hal")
            print(f"         - Kata      : {meta['total_words']:,} kata")
            print(f"         - Bab/Topik : {meta['total_chapters']} bab")
            print(f"         - File JSON : modul_json/{out_name}\n")

            summary.append({
                "pdf_file": pdf_name,
                "json_file": out_name,
                "total_pages": meta["total_pages"],
                "total_words": meta["total_words"],
                "total_chapters": meta["total_chapters"],
                "chapters": [ch["chapter_title"] for ch in result["chapters"]]
            })

        except Exception as e:
            print(f"      ❌ Gagal: {e}\n")
            import traceback
            traceback.print_exc()

    total_dur = time.time() - total_start

    # Tulis README indeks ringkas di folder modul_json
    index_md = os.path.join(OUTPUT_DIR, "README_MODUL.md")
    with open(index_md, "w", encoding="utf-8") as f:
        f.write("# 📚 Data Dokumen Modul (Format JSON Murni)\n\n")
        f.write("Seluruh file PDF di bawah ini telah dikonversi secara murni ke dalam format JSON terstruktur.\n")
        f.write("Struktur JSON memuat:\n")
        f.write("- `metadata`: Informasi umum dokumen (nama file, total halaman, total kata, total bab)\n")
        f.write("- `chapters`: Pembagian teks per Bab dan Subbab asli buku modul\n")
        f.write("- `pages`: Teks bersih per halaman asli buku (running header/footer telah disaring)\n\n")
        f.write("| No | Nama File Asli (PDF) | Total Halaman | Total Kata | Total Bab | File JSON |\n")
        f.write("|---|---|---|---|---|---|\n")
        for i, s in enumerate(summary, 1):
            f.write(f"| {i} | {s['pdf_file']} | {s['total_pages']} | {s['total_words']:,} | {s['total_chapters']} | `{s['json_file']}` |\n")

    print("================================================================================")
    print(f"🎉 SEMUA {len(summary)} DOKUMEN PDF TELAH BERHASIL MENJADI JSON MURNI!")
    print(f"⏱️  Waktu Eksekusi : {total_dur:.2f} detik")
    print(f"📁 Lokasi Output   : {OUTPUT_DIR}")
    print("================================================================================")

if __name__ == "__main__":
    main()

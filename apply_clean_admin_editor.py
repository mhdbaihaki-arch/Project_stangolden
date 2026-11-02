import glob
import subprocess
import os
import re

backend_code = """
if ($stgaction === 'admin_save_question') {
    global $USER;
    if (!is_siteadmin($USER) && !is_siteadmin()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Akses ditolak: Hanya administrator yang diizinkan mengedit soal.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    require_sesskey();
    $raw = required_param('question_data', PARAM_RAW);
    $qdata = json_decode($raw, true);
    if (!is_array($qdata) || !isset($qdata['index'])) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Data soal tidak valid.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $qIdx = (int)$qdata['index'];
    if ($qIdx < 0 || $qIdx >= 100) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Nomor soal di luar batas (1-100).'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $filePath = __FILE__;
    $fileContent = file_get_contents($filePath);

    $qPrefix = 'const questions' . '=[';
    $pStart = strpos($fileContent, $qPrefix);
    if ($pStart === false) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Array questions tidak ditemukan pada file server.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $pEnd = strpos($fileContent, '}' . '];', $pStart);
    if ($pEnd === false) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Penutup array questions tidak ditemukan pada file server.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $jsonStart = $pStart + strlen('const questions=');
    $jsonLen = ($pEnd + 2) - $jsonStart;
    $jsonStr = substr($fileContent, $jsonStart, $jsonLen);
    $allQuestions = json_decode($jsonStr, true);
    if (!is_array($allQuestions) || count($allQuestions) !== 100) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Format 100 soal pada file tidak valid.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $allQuestions[$qIdx]['question'] = trim($qdata['question'] ?? '');
    $allQuestions[$qIdx]['options'] = array_values($qdata['options'] ?? []);
    $allQuestions[$qIdx]['answer'] = (int)($qdata['answer'] ?? 0);
    if (isset($qdata['explanation'])) {
        $allQuestions[$qIdx]['explanation'] = $qdata['explanation'];
    }

    $kPrefix = 'const STG_KEY' . ' = [';
    $kStart = strpos($fileContent, $kPrefix);
    if ($kStart !== false) {
        $kEnd = strpos($fileContent, '];', $kStart);
        if ($kEnd !== false) {
            $keyJsonStart = $kStart + strlen('const STG_KEY = ');
            $keyJsonLen = ($kEnd + 1) - $keyJsonStart;
            $allKeys = json_decode(substr($fileContent, $keyJsonStart, $keyJsonLen), true);
            if (is_array($allKeys) && count($allKeys) === 100) {
                $allKeys[$qIdx] = $allQuestions[$qIdx]['answer'];
                $newKeyStr = 'const STG_KEY = ' . json_encode($allKeys) . ';';
                $fileContent = substr_replace($fileContent, $newKeyStr, $kStart, ($kEnd + 2) - $kStart);
            }
        }
    }

    $pStart = strpos($fileContent, $qPrefix);
    $pEnd = strpos($fileContent, '}' . '];', $pStart);
    $newQuestionsStr = 'const questions=' . json_encode($allQuestions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
    $fileContent = substr_replace($fileContent, $newQuestionsStr, $pStart, ($pEnd + 3) - $pStart);

    @copy($filePath, $filePath . '.bak');

    if (file_put_contents($filePath, $fileContent, LOCK_EX) === false) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Gagal menulis perubahan ke file server (periksa izin tulis web server).'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($filePath, true);
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => 'Soal nomor ' . ($qIdx + 1) . ' berhasil disimpan permanen ke file server!',
        'question' => $allQuestions[$qIdx]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
"""

ui_html_code = r"""
<?php if (is_siteadmin($USER) || is_siteadmin()): ?>
<!-- ========================================== -->
<!-- STANGOLDEN LIVE IN-PAGE ADMIN QUESTION EDITOR -->
<!-- ========================================== -->
<style>
.stg-admin-btn-float {
  position: fixed;
  bottom: 22px;
  right: 22px;
  z-index: 9999;
  background: #17131F;
  color: #C7FF42;
  border: 3px solid #C7FF42;
  border-radius: 14px;
  padding: 12px 20px;
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 700;
  font-size: 15px;
  box-shadow: 4px 4px 0 #17131F;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: transform .15s, box-shadow .15s;
}
.stg-admin-btn-float:hover {
  transform: translate(-2px, -2px);
  box-shadow: 6px 6px 0 #17131F;
  background: #252033;
}
.stg-modal-admin {
  position: fixed;
  inset: 0;
  background: rgba(23, 19, 31, 0.75);
  backdrop-filter: blur(4px);
  display: none;
  align-items: center;
  justify-content: center;
  padding: 16px;
  z-index: 99999;
}
.stg-modal-admin.active {
  display: flex;
}
.stg-modal-admin-card {
  background: #fff;
  border: 3px solid #17131F;
  border-radius: 20px;
  box-shadow: 8px 8px 0 #17131F;
  width: 100%;
  max-width: 900px;
  max-height: 94vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  font-family: 'Plus Jakarta Sans', sans-serif;
}
.stg-modal-admin-head {
  padding: 16px 22px;
  background: #F2EEFF;
  border-bottom: 3px solid #17131F;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.stg-modal-admin-title {
  margin: 0;
  font-family: 'Space Grotesk', sans-serif;
  font-size: 18px;
  font-weight: 800;
  color: #17131F;
  display: flex;
  align-items: center;
  gap: 10px;
}
.stg-modal-admin-badge {
  background: #FF4D8D;
  color: #fff;
  font-size: 11px;
  padding: 3px 8px;
  border-radius: 6px;
  border: 1.5px solid #17131F;
  font-weight: 800;
}
.stg-modal-admin-body {
  padding: 20px 24px;
  overflow-y: auto;
  flex: 1;
}
.stg-admin-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  background: #FFF9D9;
  border: 2px solid #17131F;
  border-radius: 12px;
  padding: 10px 14px;
  margin-bottom: 18px;
}
.stg-admin-nav button {
  background: #fff;
  border: 2px solid #17131F;
  border-radius: 8px;
  padding: 6px 14px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 2px 2px 0 #17131F;
}
.stg-admin-nav button:hover {
  background: #C7FF42;
}
.stg-admin-nav select {
  border: 2px solid #17131F;
  border-radius: 8px;
  padding: 6px 12px;
  font-weight: 800;
  font-size: 14px;
  background: #fff;
}
.stg-form-group {
  margin-bottom: 16px;
}
.stg-form-group label {
  display: block;
  font-weight: 800;
  font-size: 13px;
  margin-bottom: 6px;
  color: #17131F;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.stg-form-control {
  width: 100%;
  border: 2px solid #17131F;
  border-radius: 10px;
  padding: 10px 12px;
  font-family: inherit;
  font-size: 14px;
  line-height: 1.5;
  box-sizing: border-box;
  background: #FAF8FF;
}
.stg-form-control:focus {
  outline: none;
  background: #fff;
  border-color: #7C3FFF;
  box-shadow: 0 0 0 3px rgba(124, 63, 255, 0.2);
}
.stg-opt-row {
  display: grid;
  grid-template-columns: 85px 1fr;
  gap: 10px;
  align-items: center;
  margin-bottom: 10px;
}
.stg-opt-radio-label {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #E8E0FF;
  border: 2px solid #17131F;
  border-radius: 8px;
  padding: 8px 10px;
  font-weight: 800;
  font-size: 13px;
  cursor: pointer;
}
.stg-bedah-row {
  display: grid;
  grid-template-columns: 75px 1fr;
  gap: 8px;
  align-items: center;
  margin-bottom: 8px;
}
.stg-bedah-label {
  font-weight: 800;
  font-size: 12px;
  color: #17131F;
  background: #E8E0FF;
  border: 1.5px solid #17131F;
  border-radius: 6px;
  text-align: center;
  padding: 8px 0;
  display: block;
}
.stg-bedah-row.is-key .stg-bedah-label {
  background: #C7FF42;
  color: #17131F;
}
.stg-bedah-row.is-key input {
  background: #F1FADC;
  color: #2D4A1D;
  font-style: italic;
  font-weight: 600;
}
.stg-expl-box {
  background: #FFFDF5;
  border: 2px solid #7C3FFF;
  border-radius: 14px;
  padding: 16px;
  margin-top: 10px;
}
.stg-modal-admin-foot {
  padding: 14px 22px;
  background: #F9F7FF;
  border-top: 3px solid #17131F;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}
.stg-btn-save {
  background: #C7FF42;
  color: #17131F;
  border: 2px solid #17131F;
  border-radius: 10px;
  padding: 10px 22px;
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 800;
  font-size: 15px;
  box-shadow: 3px 3px 0 #17131F;
  cursor: pointer;
  transition: transform .1s;
}
.stg-btn-save:hover {
  transform: translate(-1px, -1px);
  box-shadow: 4px 4px 0 #17131F;
}
.stg-btn-cancel {
  background: #fff;
  color: #17131F;
  border: 2px solid #17131F;
  border-radius: 10px;
  padding: 10px 18px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
}
.stg-admin-alert {
  padding: 10px 14px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 13px;
  margin-bottom: 14px;
  display: none;
}
.stg-admin-alert.success {
  display: block;
  background: #D4EDDA;
  color: #155724;
  border: 2px solid #C3E6CB;
}
.stg-admin-alert.error {
  display: block;
  background: #F8D7DA;
  color: #721C24;
  border: 2px solid #F5C6CB;
}
</style>

<div id="stgAdminFloatingWrap">
  <button class="stg-admin-btn-float" onclick="stgOpenAdminEditor(current)" title="Buka Editor Soal Admin">
    🛠️ <span>Edit Soal (Admin)</span>
  </button>
</div>

<div id="stgAdminModal" class="stg-modal-admin" onclick="if(event.target===this) stgCloseAdminEditor();">
  <div class="stg-modal-admin-card">
    <div class="stg-modal-admin-head">
      <div class="stg-modal-admin-title">
        <span>🛠️ Editor Soal STANGOLDEN</span>
        <span class="stg-modal-admin-badge">Admin Mode</span>
      </div>
      <button onclick="stgCloseAdminEditor()" style="background:none;border:none;font-size:24px;cursor:pointer;font-weight:800;">✕</button>
    </div>

    <div class="stg-modal-admin-body">
      <div id="stgAdminAlert" class="stg-admin-alert"></div>

      <div class="stg-admin-nav">
        <button type="button" onclick="stgNavAdminQ(-1)">◀ Sebelumnya</button>
        <div style="display:flex;align-items:center;gap:8px;">
          <label style="font-weight:800;font-size:14px;margin:0;">Pilih Soal:</label>
          <select id="stgAdminQSelect" onchange="stgLoadAdminQ(parseInt(this.value, 10))"></select>
        </div>
        <button type="button" onclick="stgNavAdminQ(1)">Selanjutnya ▶</button>
      </div>

      <!-- POKOK SOAL -->
      <div class="stg-form-group">
        <label>Pertanyaan (Pokok Soal):</label>
        <textarea id="stgAdminQText" class="stg-form-control" rows="3" placeholder="Tuliskan pertanyaan soal..."></textarea>
      </div>

      <!-- PILIHAN OPSI & KUNCI -->
      <div class="stg-form-group">
        <label>Pilihan Jawaban & Kunci (Pilih radio untuk menetapkan kunci):</label>
        <?php foreach (['A', 'B', 'C', 'D', 'E'] as $idx => $ltr): ?>
        <div class="stg-opt-row">
          <label class="stg-opt-radio-label">
            <input type="radio" name="stgAdminKunciRadio" value="<?= $idx ?>" onchange="stgOnAdminKeyChange(<?= $idx ?>)">
            <span>Opsi <?= $ltr ?></span>
          </label>
          <input type="text" id="stgAdminOpt<?= $idx ?>" class="stg-form-control" placeholder="Teks opsi <?= $ltr ?>..." oninput="stgUpdateLivePreview()">
        </div>
        <?php endforeach; ?>
      </div>

      <!-- PEMBAHASAN BERSIH (NON-HTML) -->
      <div class="stg-form-group">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
          <label style="margin:0;color:#3A1B73;font-size:14px;">📖 Pembahasan Lengkap (Bebas Tag HTML — Langsung Edit):</label>
          <button type="button" id="stgToggleRawBtn" onclick="stgToggleAdminRawMode()" style="background:#fff;border:1.5px solid #17131F;border-radius:6px;padding:3px 9px;font-size:11px;font-weight:800;cursor:pointer;">
            ⚙️ Mode HTML Mentah
          </button>
        </div>
        <small style="color:#665E7C;font-size:11px;display:block;margin-bottom:10px;">
          💡 Kolom di bawah bebas dari kode HTML rumit. Tuliskan teks materi biasa; sistem akan menyusun kartu belajar secara otomatis.
        </small>

        <div class="stg-expl-box">
          <!-- 1. Topik -->
          <div style="margin-bottom:12px;">
            <label style="font-size:12px;font-weight:800;margin-bottom:4px;display:block;">📌 Topik / Judul Pembahasan:</label>
            <input type="text" id="stgExpTopic" class="stg-form-control" placeholder="Contoh: Pengertian Dasar Nilai" oninput="stgUpdateLivePreview()">
          </div>

          <!-- 2. Konsep Inti -->
          <div style="margin-bottom:12px;">
            <label style="font-size:12px;font-weight:800;margin-bottom:4px;display:block;">
              01 · Konsep Inti Textbook (Gunakan **kata** jika ingin mencetak tebal):
            </label>
            <textarea id="stgExpKonsep" class="stg-form-control" rows="3" placeholder="Tuliskan intisari materi dari textbook... Contoh: **Nilai** adalah suatu **keyakinan**..." oninput="stgUpdateLivePreview()"></textarea>
          </div>

          <!-- 3. Alasan AREL -->
          <div style="margin-bottom:12px;">
            <label style="font-size:12px;font-weight:800;margin-bottom:4px;display:block;">
              02 · Alasan Kunci Jawaban (Metode AREL):
            </label>
            <textarea id="stgExpAlasan" class="stg-form-control" rows="4" placeholder="Jelaskan alasan logis mengapa opsi kunci merupakan jawaban yang tepat..." oninput="stgUpdateLivePreview()"></textarea>
            <small style="color:#665E7C;font-size:11px;display:block;margin-top:2px;">
              ℹ️ Kalimat <em>'Jawaban tepat: [Kunci]'</em> ditambahkan otomatis sesuai pilihan radio di atas.
            </small>
          </div>

          <!-- 4. Bedah Opsi -->
          <div style="margin-bottom:12px;">
            <label style="font-size:12px;font-weight:800;margin-bottom:6px;display:block;">
              03 · Bedah Opsi Lainnya (Pengecoh — Jelaskan secara ringkas alasan opsi lain tidak tepat):
            </label>
            <?php foreach (['A', 'B', 'C', 'D', 'E'] as $idx => $ltr): ?>
            <div class="stg-bedah-row" id="stgBedahRow<?= $idx ?>">
              <span class="stg-bedah-label">Opsi <?= $ltr ?></span>
              <input type="text" id="stgExpBedah<?= $idx ?>" class="stg-form-control" placeholder="Penjelasan mengapa Opsi <?= $ltr ?> kurang tepat / milik konsep apa..." oninput="stgUpdateLivePreview()">
            </div>
            <?php endforeach; ?>
          </div>

          <!-- 5. Referensi & Kata Kunci -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
            <div>
              <label style="font-size:12px;font-weight:800;margin-bottom:4px;display:block;">📖 Referensi Modul:</label>
              <input type="text" id="stgExpRef" class="stg-form-control" placeholder="Contoh: Nilai-Nilai Kementerian Keuangan (Hlm. 05)" oninput="stgUpdateLivePreview()">
            </div>
            <div>
              <label style="font-size:12px;font-weight:800;margin-bottom:4px;display:block;">🔍 Kata Kunci Cek Buku:</label>
              <input type="text" id="stgExpKw" class="stg-form-control" placeholder="Contoh: keyakinan • apa yang dianggap baik" oninput="stgUpdateLivePreview()">
            </div>
          </div>

          <!-- Mode HTML Mentah (Fallback) -->
          <div id="stgRawExplWrap" style="display:none;margin-bottom:14px;">
            <label style="font-size:12px;font-weight:800;margin-bottom:4px;display:block;color:#C7254E;">⚠️ Kode HTML Mentah (Mode Lanjutan):</label>
            <textarea id="stgAdminExplanation" class="stg-form-control" rows="7" style="font-family:monospace;font-size:12px;line-height:1.4;" placeholder="HTML explanation..." oninput="stgOnRawHtmlInput()"></textarea>
          </div>

          <!-- Live Preview -->
          <div style="margin-top:12px;border-top:1.5px dashed #D2C7E8;padding-top:12px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
              <span style="font-size:12px;font-weight:800;color:#17131F;">✨ Pratinjau Tampilan Siswa (Live Update):</span>
              <span style="font-size:11px;color:#7C3FFF;font-weight:700;">Otomatis Sesuai Isian Di Atas</span>
            </div>
            <div id="stgExpPreview" style="background:#FAF8FF;border:2px dashed #7C3FFF;border-radius:12px;padding:12px;overflow-x:auto;"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="stg-modal-admin-foot">
      <div style="font-size:12px;color:#665E7C;">
        💾 Menyimpan langsung ke file server secara permanen.
      </div>
      <div style="display:flex;gap:10px;">
        <button type="button" class="stg-btn-cancel" onclick="stgCloseAdminEditor()">Tutup</button>
        <button type="button" id="stgAdminSaveBtn" class="stg-btn-save" onclick="stgSaveAdminQ()">💾 Simpan Permanen</button>
      </div>
    </div>
  </div>
</div>

<script>
let stgAdminCurIdx = 0;
let stgIsRawModeActive = false;

function stgInitAdminSelect() {
  const sel = document.getElementById("stgAdminQSelect");
  if (!sel || sel.options.length > 0) return;
  for (let i = 0; i < questions.length; i++) {
    const opt = document.createElement("option");
    opt.value = i;
    opt.textContent = `Nomor ${i + 1}`;
    sel.appendChild(opt);
  }
}

function stgOpenAdminEditor(idx) {
  stgInitAdminSelect();
  if (typeof idx !== "number" || idx < 0 || idx >= questions.length) {
    idx = (typeof current === "number") ? current : 0;
  }
  stgLoadAdminQ(idx);
  document.getElementById("stgAdminModal").classList.add("active");
}

function stgCloseAdminEditor() {
  document.getElementById("stgAdminModal").classList.remove("active");
  const alertBox = document.getElementById("stgAdminAlert");
  alertBox.className = "stg-admin-alert";
  alertBox.style.display = "none";
}

function stgNavAdminQ(dir) {
  let next = stgAdminCurIdx + dir;
  if (next < 0) next = questions.length - 1;
  if (next >= questions.length) next = 0;
  stgLoadAdminQ(next);
}

function stgCleanText(html) {
  if (!html) return "";
  let s = html;
  s = s.replace(/<strong[^>]*>([\s\S]*?)<\/strong>/gi, "**$1**");
  s = s.replace(/<b[^>]*>([\s\S]*?)<\/b>/gi, "**$1**");
  s = s.replace(/<em[^>]*>([\s\S]*?)<\/em>/gi, "*$1*");
  s = s.replace(/<i[^>]*>([\s\S]*?)<\/i>/gi, "*$1*");
  s = s.replace(/<[^>]+>/g, " ");
  s = s.replace(/&nbsp;/gi, " ")
       .replace(/&amp;/gi, "&")
       .replace(/&lt;/gi, "<")
       .replace(/&gt;/gi, ">")
       .replace(/&quot;/gi, '"')
       .replace(/&#039;/gi, "'");
  return s.replace(/\s+/g, " ").trim();
}

function stgParseExplanation(html) {
  const result = {
    title: "",
    konsep: "",
    alasan: "",
    bedah: { A: "", B: "", C: "", D: "", E: "" },
    ref: "",
    kw: "",
    raw: html || ""
  };
  if (!html) return result;

  // 1. Title
  const mTitle = html.match(/class=["']learn-title["'][^>]*>([\s\S]*?)<\/div>/i);
  if (mTitle) {
    let t = stgCleanText(mTitle[1]);
    t = t.replace(/^[^\w\s]*\s*Intisari Materi\s*[•\-–]\s*/i, "").trim();
    result.title = t;
  }

  // 2. Konsep Inti
  const mKonsep = html.match(/Konsep Inti Textbook<\/div>\s*<p>([\s\S]*?)<\/p>/i);
  if (mKonsep) {
    result.konsep = stgCleanText(mKonsep[1]);
  }

  // 3. Alasan AREL
  const mAlasan = html.match(/Alasan Kunci Jawaban \(AREL\)<\/div>\s*<p>([\s\S]*?)<\/p>/i);
  if (mAlasan) {
    result.alasan = stgCleanText(mAlasan[1]);
  }

  // 4. Bedah Opsi
  const mBedahSec = html.match(/Bedah Opsi Lainnya<\/div>\s*<ul[^>]*>([\s\S]*?)<\/ul>/i);
  if (mBedahSec) {
    const liMatches = [...mBedahSec[1].matchAll(/<li>\s*<strong[^>]*>([A-E])<\/strong>\s*:\s*([\s\S]*?)<\/li>/gi)];
    for (const match of liMatches) {
      const letter = match[1].toUpperCase();
      result.bedah[letter] = stgCleanText(match[2]);
    }
  }

  // 5. Source & Kata Kunci
  const mSrc = html.match(/class=["']lesson-source["'][^>]*>([\s\S]*?)<\/div>/i);
  if (mSrc) {
    const rawSrc = mSrc[1];
    const mRef = rawSrc.match(/Referensi:\s*([^&|<]+)/i);
    if (mRef) result.ref = stgCleanText(mRef[1]);
    
    const mKw = rawSrc.match(/Kata Kunci[^:]*:\s*([\s\S]*)$/i);
    if (mKw) {
      let kw = stgCleanText(mKw[1]);
      kw = kw.replace(/\*\*/g, "").trim();
      result.kw = kw;
    }
  }

  return result;
}

function stgMarkdownToHtml(text) {
  if (!text) return "";
  let s = text.trim();
  s = s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
  s = s.replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>");
  s = s.replace(/\*(.+?)\*/g, "<em>$1</em>");
  return s;
}

function stgGetSelectedKey() {
  const radios = document.getElementsByName("stgAdminKunciRadio");
  for (let r of radios) {
    if (r.checked) return parseInt(r.value, 10);
  }
  return 0;
}

function stgBuildExplanationHtml(data, answerKey, options) {
  const letters = ["A", "B", "C", "D", "E"];
  const keyLetter = letters[answerKey] || "A";
  const keyOptText = (options && options[answerKey]) ? options[answerKey] : "";

  const titleText = (data.title || "").trim() || "Intisari Materi";
  const konsepHtml = stgMarkdownToHtml(data.konsep || "");
  const alasanHtml = stgMarkdownToHtml(data.alasan || "");

  let bedahLi = "";
  for (let i = 0; i < 5; i++) {
    const ltr = letters[i];
    if (i === answerKey) continue;
    const bText = (data.bedah && data.bedah[ltr]) ? data.bedah[ltr].trim() : "";
    if (bText) {
      bedahLi += `<li><strong>${ltr}</strong>: ${stgMarkdownToHtml(bText)}</li>`;
    }
  }

  const refText = (data.ref || "").trim() || "Bahan Modul Pembelajaran";
  const kwText = (data.kw || "").trim();

  return `<div class="learn-box lesson-card">` +
    `<div class="learn-title">📚 Intisari Materi • ${titleText}</div>` +
    `<div class="lesson-section">` +
      `<div class="lesson-kicker">01 · Konsep Inti Textbook</div>` +
      `<p>${konsepHtml}</p>` +
    `</div>` +
    `<div class="lesson-section lesson-reason">` +
      `<div class="lesson-kicker">02 · Alasan Kunci Jawaban (AREL)</div>` +
      `<p>${alasanHtml}</p>` +
      `<p>Jawaban tepat: <strong>${keyLetter}. ${keyOptText}</strong></p>` +
    `</div>` +
    `<div class="lesson-section">` +
      `<div class="lesson-kicker">03 · Bedah Opsi Lainnya</div>` +
      `<ul style="margin:6px 0 0;padding-left:20px;line-height:1.6">${bedahLi}</ul>` +
    `</div>` +
    `<div class="lesson-source">📖 Referensi: ${refText} &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>${kwText}</strong></div>` +
  `</div>`;
}

function stgOnAdminKeyChange(newKey) {
  const letters = ["A", "B", "C", "D", "E"];
  for (let i = 0; i < 5; i++) {
    const row = document.getElementById(`stgBedahRow${i}`);
    const inp = document.getElementById(`stgExpBedah${i}`);
    if (i === newKey) {
      row.classList.add("is-key");
      inp.disabled = true;
      inp.placeholder = `(Kunci Jawaban — otomatis tercakup di poin 02 Alasan AREL)`;
      inp.dataset.savedVal = inp.value;
      inp.value = "";
    } else {
      row.classList.remove("is-key");
      inp.disabled = false;
      inp.placeholder = `Penjelasan mengapa Opsi ${letters[i]} kurang tepat / milik konsep apa...`;
      if (inp.dataset.savedVal) {
        inp.value = inp.dataset.savedVal;
        delete inp.dataset.savedVal;
      }
    }
  }
  stgUpdateLivePreview();
}

function stgToggleAdminRawMode() {
  const rawWrap = document.getElementById("stgRawExplWrap");
  const btn = document.getElementById("stgToggleRawBtn");
  stgIsRawModeActive = !stgIsRawModeActive;
  if (stgIsRawModeActive) {
    rawWrap.style.display = "block";
    btn.textContent = "⬅️ Kembali ke Form Bebas HTML";
    btn.style.background = "#FFEDD5";
  } else {
    rawWrap.style.display = "none";
    btn.textContent = "⚙️ Mode HTML Mentah";
    btn.style.background = "#fff";
    // Sync raw HTML changes back to clean form
    const rawVal = document.getElementById("stgAdminExplanation").value.trim();
    if (rawVal) {
      const parsed = stgParseExplanation(rawVal);
      document.getElementById("stgExpTopic").value = parsed.title;
      document.getElementById("stgExpKonsep").value = parsed.konsep;
      document.getElementById("stgExpAlasan").value = parsed.alasan;
      const letters = ["A", "B", "C", "D", "E"];
      for (let i = 0; i < 5; i++) {
        const inp = document.getElementById(`stgExpBedah${i}`);
        if (!inp.disabled) {
          inp.value = parsed.bedah[letters[i]] || "";
        }
      }
      document.getElementById("stgExpRef").value = parsed.ref;
      document.getElementById("stgExpKw").value = parsed.kw;
    }
    stgUpdateLivePreview();
  }
}

function stgOnRawHtmlInput() {
  if (stgIsRawModeActive) {
    const rawVal = document.getElementById("stgAdminExplanation").value;
    document.getElementById("stgExpPreview").innerHTML = rawVal;
  }
}

function stgUpdateLivePreview() {
  const previewBox = document.getElementById("stgExpPreview");
  if (!previewBox) return;

  const curKey = stgGetSelectedKey();
  const opts = [];
  for (let i = 0; i < 5; i++) {
    opts.push(document.getElementById(`stgAdminOpt${i}`).value || "");
  }

  const letters = ["A", "B", "C", "D", "E"];
  const bedahData = {};
  for (let i = 0; i < 5; i++) {
    bedahData[letters[i]] = document.getElementById(`stgExpBedah${i}`).value || "";
  }

  const data = {
    title: document.getElementById("stgExpTopic").value,
    konsep: document.getElementById("stgExpKonsep").value,
    alasan: document.getElementById("stgExpAlasan").value,
    bedah: bedahData,
    ref: document.getElementById("stgExpRef").value,
    kw: document.getElementById("stgExpKw").value
  };

  const html = stgBuildExplanationHtml(data, curKey, opts);
  previewBox.innerHTML = html;

  const rawArea = document.getElementById("stgAdminExplanation");
  if (rawArea && !stgIsRawModeActive) {
    rawArea.value = html;
  }
}

function stgLoadAdminQ(idx) {
  stgAdminCurIdx = idx;
  const sel = document.getElementById("stgAdminQSelect");
  if (sel) sel.value = idx;
  const q = questions[idx];
  if (!q) return;

  document.getElementById("stgAdminQText").value = q.question || "";
  for (let i = 0; i < 5; i++) {
    const optVal = (q.options && q.options[i]) ? q.options[i] : "";
    document.getElementById(`stgAdminOpt${i}`).value = optVal;
  }
  const radios = document.getElementsByName("stgAdminKunciRadio");
  for (let r of radios) {
    r.checked = (parseInt(r.value, 10) === q.answer);
  }

  // Parse explanation to clean non-HTML fields
  const parsed = stgParseExplanation(q.explanation || "");
  document.getElementById("stgExpTopic").value = parsed.title;
  document.getElementById("stgExpKonsep").value = parsed.konsep;
  document.getElementById("stgExpAlasan").value = parsed.alasan;

  const letters = ["A", "B", "C", "D", "E"];
  for (let i = 0; i < 5; i++) {
    const inp = document.getElementById(`stgExpBedah${i}`);
    inp.value = parsed.bedah[letters[i]] || "";
    delete inp.dataset.savedVal;
  }

  document.getElementById("stgExpRef").value = parsed.ref;
  document.getElementById("stgExpKw").value = parsed.kw;
  document.getElementById("stgAdminExplanation").value = q.explanation || "";

  stgOnAdminKeyChange(q.answer);

  const alertBox = document.getElementById("stgAdminAlert");
  alertBox.className = "stg-admin-alert";
  alertBox.style.display = "none";
}

function stgSaveAdminQ() {
  const saveBtn = document.getElementById("stgAdminSaveBtn");
  const alertBox = document.getElementById("stgAdminAlert");
  
  const qText = document.getElementById("stgAdminQText").value.trim();
  if (!qText) {
    alertBox.textContent = "Pertanyaan soal tidak boleh kosong.";
    alertBox.className = "stg-admin-alert error";
    alertBox.style.display = "block";
    return;
  }

  const opts = [];
  const letters = ["A", "B", "C", "D", "E"];
  for (let i = 0; i < 5; i++) {
    const v = document.getElementById(`stgAdminOpt${i}`).value.trim();
    if (!v) {
      alertBox.textContent = `Pilihan opsi ${letters[i]} tidak boleh kosong.`;
      alertBox.className = "stg-admin-alert error";
      alertBox.style.display = "block";
      return;
    }
    opts.push(v);
  }

  const answerKey = stgGetSelectedKey();

  let finalExplanation = "";
  if (stgIsRawModeActive) {
    finalExplanation = document.getElementById("stgAdminExplanation").value.trim();
  } else {
    const bedahData = {};
    for (let i = 0; i < 5; i++) {
      bedahData[letters[i]] = document.getElementById(`stgExpBedah${i}`).value.trim();
    }
    const data = {
      title: document.getElementById("stgExpTopic").value.trim(),
      konsep: document.getElementById("stgExpKonsep").value.trim(),
      alasan: document.getElementById("stgExpAlasan").value.trim(),
      bedah: bedahData,
      ref: document.getElementById("stgExpRef").value.trim(),
      kw: document.getElementById("stgExpKw").value.trim()
    };
    finalExplanation = stgBuildExplanationHtml(data, answerKey, opts);
  }

  if (!finalExplanation) {
    alertBox.textContent = "Pembahasan soal tidak boleh kosong.";
    alertBox.className = "stg-admin-alert error";
    alertBox.style.display = "block";
    return;
  }

  const payload = {
    index: stgAdminCurIdx,
    question: qText,
    options: opts,
    answer: answerKey,
    explanation: finalExplanation
  };

  saveBtn.disabled = true;
  saveBtn.textContent = "⏳ Menyimpan ke Server...";
  alertBox.style.display = "none";

  const formData = new FormData();
  formData.append("action", "admin_save_question");
  formData.append("sesskey", (window.M && M.cfg && M.cfg.sesskey) ? M.cfg.sesskey : "<?= sesskey() ?>");
  formData.append("question_data", JSON.stringify(payload));

  const endpoint = window.location.href.split("?")[0].split("#")[0] + "?action=admin_save_question";

  fetch(endpoint, {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    saveBtn.disabled = false;
    saveBtn.textContent = "💾 Simpan Permanen";
    if (data.success) {
      questions[stgAdminCurIdx].question = qText;
      questions[stgAdminCurIdx].options = opts;
      questions[stgAdminCurIdx].answer = answerKey;
      questions[stgAdminCurIdx].explanation = finalExplanation;

      if (typeof render === "function") {
        render();
      }

      alertBox.textContent = "✅ " + (data.message || "Berhasil disimpan permanen!");
      alertBox.className = "stg-admin-alert success";
      alertBox.style.display = "block";

      setTimeout(() => {
        alertBox.style.display = "none";
      }, 3500);
    } else {
      alertBox.textContent = "❌ " + (data.message || "Gagal menyimpan.");
      alertBox.className = "stg-admin-alert error";
      alertBox.style.display = "block";
    }
  })
  .catch(err => {
    saveBtn.disabled = false;
    saveBtn.textContent = "💾 Simpan Permanen";
    alertBox.textContent = "❌ Terjadi kesalahan jaringan / server: " + err.message;
    alertBox.className = "stg-admin-alert error";
    alertBox.style.display = "block";
  });
}
</script>
<?php endif; ?>
"""

def update_file(fpath):
    with open(fpath, 'r', encoding='utf-8') as fp:
        c = fp.read()

    # 1. Update backend code
    b_marker_start = "if ($stgaction === 'admin_save_question') {"
    b_start = c.find(b_marker_start)
    if b_start != -1:
        s_pos = c.find('berhasil disimpan permanen ke file server!', b_start)
        if s_pos != -1:
            m_end = re.search(r'exit;\s*\}', c[s_pos:])
            if m_end:
                b_end = s_pos + m_end.end()
                c = c[:b_start] + backend_code.strip() + c[b_end:]
            else:
                print(f"Error: regex exit;}} not found after s_pos in {fpath}")
                return False
        else:
            print(f"Error: succ_msg not found after b_start in {fpath}")
            return False
    else:
        # Inject after anchor
        anchor = "$stgaction=optional_param('action','',PARAM_ALPHANUMEXT);"
        if anchor in c:
            c = c.replace(anchor, anchor + "\n" + backend_code.strip() + "\n")
        else:
            print(f"Error: anchor not found in {fpath}")
            return False

    # 2. Update UI code
    ui_marker = "<!-- STANGOLDEN LIVE IN-PAGE ADMIN QUESTION EDITOR -->"
    if ui_marker in c:
        ui_pos = c.find(ui_marker)
        ui_start = c.rfind("<?php if (is_siteadmin", 0, ui_pos)
        ui_end = c.find("<?php endif; ?>", ui_pos)
        if ui_start != -1 and ui_end != -1:
            ui_end += len("<?php endif; ?>")
            c = c[:ui_start] + ui_html_code.strip() + c[ui_end:]
        else:
            print(f"Error: UI markers not found in {fpath}")
            return False
    else:
        last_body = c.rfind('</body></html>')
        if last_body != -1:
            c = c[:last_body] + ui_html_code.strip() + '\n</body></html>'
        else:
            print(f"Error: </body></html> not found in {fpath}")
            return False

    with open(fpath, 'w', encoding='utf-8') as fp:
        fp.write(c)
    return True

all_php = sorted(glob.glob('soal_*php/*.php'))
print(f"Total PHP files to update: {len(all_php)}")

success_count = 0
for f in all_php:
    ok = update_file(f)
    if ok:
        res = subprocess.run(['php', '-l', f], capture_output=True, text=True)
        if 'No syntax errors' in res.stdout:
            print(f"  [OK] {os.path.basename(f)}")
            success_count += 1
        else:
            print(f"  [SYNTAX ERROR] {os.path.basename(f)}: {res.stderr}")
    else:
        print(f"  [FAILED UPDATE] {os.path.basename(f)}")

print(f"\nCompleted! {success_count} / {len(all_php)} files successfully updated & verified with php -l.")

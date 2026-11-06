<?php
/**
 * STANGOLDEN.exam • Nilai-Nilai Kemenkeu Paket 01 (Halaman 5–14)
 * Autentikasi & otorisasi memakai Moodle Stangolden saja.
 * Semua soal, desain, anotasi, dan hasil dijalankan pada halaman ini;
 * tabel ranking/review/anotasi pada database elearning, prefix Moodle mdlhv_.
 * Moodle $DB otomatis menambahkan prefix sesuai config.php; jangan masukkan mdlhv_ di konstanta tabel.
 */
declare(strict_types=1);
$configCandidates = [
    rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . '/elearning/config.php',
    __DIR__ . '/config.php',
    __DIR__ . '/elearning/config.php',
    dirname(__DIR__) . '/config.php',
    dirname(__DIR__) . '/elearning/config.php',
    dirname(__DIR__, 2) . '/elearning/config.php',
];
$configPath = null;
foreach ($configCandidates as $candidate) {
    if (is_file($candidate)) { $configPath = $candidate; break; }
}
if ($configPath === null) {
    http_response_code(500);
    exit('Konfigurasi Stangolden belum ditemukan. Hubungi administrator.');
}
require_once($configPath);
require_login();

// ID course hanya untuk otorisasi backend; tidak ditampilkan ke peserta.
$requiredCourseId = 255;
$context = context_course::instance($requiredCourseId);
$authorized = is_siteadmin($USER) || is_enrolled($context, $USER, '', true);
if (!$authorized) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    $dashboardUrl = htmlspecialchars((new moodle_url('/my/'))->out(false), ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Akses Terbatas • STANGOLDEN</title><style>body{font-family:system-ui,sans-serif;background:#f2eeff;min-height:100vh;margin:0;display:grid;place-items:center;color:#17131f}.card{background:white;border:3px solid #17131f;border-radius:18px;box-shadow:6px 6px 0 #17131f;padding:30px;max-width:500px;margin:20px;text-align:center}a{display:inline-block;background:#c7ff42;color:#17131f;padding:12px 18px;border:2px solid #17131f;border-radius:10px;font-weight:800;text-decoration:none}</style></head><body><div class="card"><h1>🔒 Akses Terbatas</h1><p>Materi latihan ini hanya tersedia untuk peserta yang terdaftar pada program persiapan UPKP Kemenkeu 2026 di Stangolden.</p><a href="' . $dashboardUrl . '">Kembali ke Dashboard</a></div></body></html>';
    exit;
}

// Database Moodle: elearning; prefix fisik mdlhv_ diambil otomatis dari config.php.
// $DB->get_record("stgnk_etika_p03_rank") => tabel fisik "mdlhv_stgnk_etika_p03_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgnk_etika_p03_rank';
const STG_REVIEW_T = 'stgnk_etika_p03_rev';
const STG_ANNO_T = 'stgnk_etika_p03_anno';
const STG_KEY = [0, 1, 2, 3, 4, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 3, 4, 0, 1, 2, 4, 0, 1, 2, 3, 0, 1, 2, 3, 4, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 3, 4, 0, 1, 2, 4, 0, 1, 2, 3, 0, 1, 2, 3, 4, 2, 3, 4, 0, 1, 1, 2, 3, 4, 0, 3, 4, 0, 1, 2, 2, 3, 4, 0, 1, 4, 0, 1, 2, 3];
// Saat submit pertama, tabel yang belum ada disiapkan otomatis.
// Halaman biasa tidak menjalankan CREATE TABLE.
function stgnk_table(string $name, bool $review): void {
    global $DB, $CFG;
    require_once($CFG->libdir . '/ddllib.php');
    if (!$DB->get_manager()->table_exists(new xmldb_table($name))) {
        throw new RuntimeException('Tabel latihan belum disiapkan oleh administrator.');
    }
}
function stgnk_create_table(string $name, bool $review): void {
    global $CFG;
    require_once($CFG->libdir . '/ddllib.php');
    global $DB;
    $mgr=$DB->get_manager(); $t=new xmldb_table($name);
    if($mgr->table_exists($t)) return;
    $t->add_field('id',XMLDB_TYPE_INTEGER,'10',null,XMLDB_NOTNULL,XMLDB_SEQUENCE);
    $t->add_field('userid',XMLDB_TYPE_INTEGER,'10',null,XMLDB_NOTNULL,null,0);
    $t->add_field('username',XMLDB_TYPE_CHAR,'100',null,XMLDB_NOTNULL,null,'');
    $t->add_field('score',XMLDB_TYPE_INTEGER,'4',null,XMLDB_NOTNULL,null,0);
    $t->add_field('correct',XMLDB_TYPE_INTEGER,'4',null,XMLDB_NOTNULL,null,0);
    $t->add_field('timetaken',XMLDB_TYPE_INTEGER,'10',null,XMLDB_NOTNULL,null,0);
    $t->add_field('attempts',XMLDB_TYPE_INTEGER,'10',null,XMLDB_NOTNULL,null,1);
    if($review) $t->add_field('answersjson',XMLDB_TYPE_TEXT,null,null,XMLDB_NOTNULL);
    $t->add_field('timecreated',XMLDB_TYPE_INTEGER,'10',null,XMLDB_NOTNULL,null,0);
    $t->add_field('timemodified',XMLDB_TYPE_INTEGER,'10',null,XMLDB_NOTNULL,null,0);
    $t->add_key('primary',XMLDB_KEY_PRIMARY,['id']);
    $t->add_key('user_unique',XMLDB_KEY_UNIQUE,['userid']);
    $mgr->create_table($t);
}
function stgnk_create_annotation_table(): void {
    global $CFG, $DB;
    require_once($CFG->libdir . '/ddllib.php');
    $mgr = $DB->get_manager();
    $t = new xmldb_table(STG_ANNO_T);
    if ($mgr->table_exists($t)) { return; }
    $t->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
    $t->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
    $t->add_field('questionnum', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, 0);
    $t->add_field('strokesjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
    $t->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
    $t->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $t->add_index('user_question_unique', XMLDB_INDEX_UNIQUE, ['userid', 'questionnum']);
    $mgr->create_table($t);
}
function stgnk_annotation_exists(): void {
    global $CFG, $DB;
    require_once($CFG->libdir . '/ddllib.php');
    if (!$DB->get_manager()->table_exists(new xmldb_table(STG_ANNO_T))) {
        stgnk_create_annotation_table();
    }
}
function stgnk_json(array $data,int $status=200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
}
$stgaction=optional_param('action','',PARAM_ALPHANUMEXT);

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
    if ($qIdx < 0 || $qIdx >= 80) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Nomor soal di luar batas (1-80).'], JSON_UNESCAPED_UNICODE);
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
    if (!is_array($allQuestions) || count($allQuestions) !== 80) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Format 80 soal pada file tidak valid.'], JSON_UNESCAPED_UNICODE);
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
            if (is_array($allKeys) && count($allKeys) === 80) {
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

if ($stgaction === 'setup') {
    if (!is_siteadmin($USER)) {
        http_response_code(403);
        exit('Akses hanya untuk administrator Stangolden.');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_sesskey();
        try {
            stgnk_create_table(STG_LB_T, false);
            stgnk_create_table(STG_REVIEW_T, true);
            stgnk_create_annotation_table();
            echo '<h2>✅ Tabel ranking, review dan anotasi berhasil disiapkan.</h2><p>Database Moodle memakai prefix dari config.php (contoh: mdlhv_). Tidak perlu membuat tabel tambahan apabila tiga tabel sudah ada.</p><p><a href="' . htmlspecialchars((new moodle_url('/my/'))->out(false), ENT_QUOTES, 'UTF-8') . '">Kembali ke dashboard</a></p>';
        } catch (Throwable $e) {
            error_log('STANGOLDEN NK P01 SETUP: ' . $e->getMessage());
            http_response_code(500);
            echo '<h2>Gagal menyiapkan tabel</h2><p>Periksa PHP error log dan hak CREATE TABLE pengguna database Moodle.</p><pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
        }
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="id"><meta charset="utf-8"><title>Setup Latihan STANGOLDEN</title><body style="font:16px system-ui;max-width:650px;margin:60px auto"><h2>Persiapan Ranking, Review & Anotasi</h2><p>Jalankan satu kali sebagai administrator. Tidak memengaruhi tabel Mini TO lain.</p><form method="post"><input type="hidden" name="sesskey" value="' . htmlspecialchars(sesskey(), ENT_QUOTES, 'UTF-8') . '"><button style="padding:14px 20px">Siapkan tabel latihan</button></form></body></html>';
    }
    exit;
}

if($stgaction!=='') {
    require_once($CFG->libdir . '/ddllib.php');
    // Semua tindakan tetap di balik verifikasi login + enrollment di atas.
    try {
        if($stgaction==='save_score') {
            if($_SERVER['REQUEST_METHOD']!=='POST') stgnk_json(['success'=>false,'message'=>'Metode tidak valid.'],405);
            require_sesskey();
            $raw=required_param('answersjson',PARAM_RAW);
            $a=json_decode($raw,true);
            if(!is_array($a)||count($a)!==80||array_keys($a)!==range(0,79)) stgnk_json(['success'=>false,'message'=>'Format jawaban tidak valid.'],400);
            $clean=[];$correct=0;
            foreach($a as $i=>$v){
                if($v===null){$clean[]=-1;continue;}
                if(!is_int($v)||$v<0||$v>4) stgnk_json(['success'=>false,'message'=>'Pilihan jawaban tidak valid.'],400);
                $clean[]=$v;
                if($v===STG_KEY[$i]) $correct++;
            }
            $score=$correct;
            $time=max(0,min(3900,required_param('timetaken',PARAM_INT)));
            // Auto-create HANYA saat peserta submit; tidak perlu SQL/manual setup.
            // Semua nama tabel tanpa prefix: Moodle menambahkan mdlhv_ otomatis.
            stgnk_create_table(STG_LB_T, false);
            stgnk_create_table(STG_REVIEW_T, true);
            stgnk_create_annotation_table();
            $now=time();$userid=(int)$USER->id;
            $existing=$DB->get_record(STG_LB_T,['userid'=>$userid]);
            $first=(!$existing);
            if($first){
                $record=(object)['userid'=>$userid,'username'=>$USER->username,'score'=>$score,'correct'=>$correct,'timetaken'=>$time,'attempts'=>1,'timecreated'=>$now,'timemodified'=>$now];
                $DB->insert_record(STG_LB_T,$record);
            }else{
                $existing->attempts=(int)$existing->attempts+1;
                $existing->timemodified=$now;$existing->username=$USER->username;
                $DB->update_record(STG_LB_T,$existing);
            }
            $last=$DB->get_record(STG_REVIEW_T,['userid'=>$userid]);
            if(!$last) $last=(object)['userid'=>$userid,'timecreated'=>$now];
            $last->username=$USER->username;$last->score=$score;$last->correct=$correct;
            $last->timetaken=$time;$last->answersjson=json_encode($clean);$last->attempts=($existing?(int)$existing->attempts:1);$last->timemodified=$now;
            if(!empty($last->id)) $DB->update_record(STG_REVIEW_T,$last);else $DB->insert_record(STG_REVIEW_T,$last);
            stgnk_json(['success'=>true,'firstattemptsaved'=>$first]);
        }
        if ($stgaction === 'anno_load') {
            if (!$DB->get_manager()->table_exists(new xmldb_table(STG_ANNO_T))) {
                stgnk_json(['success'=>true,'annotations'=>new stdClass()]);
            }
            $rows = $DB->get_records(STG_ANNO_T, ['userid'=>(int)$USER->id]);
            $out = new stdClass();
            foreach ($rows as $r) {
                $q = (int)$r->questionnum;
                if ($q < 0 || $q > 99) { continue; }
                $strokes = json_decode((string)$r->strokesjson, true);
                $out->{(string)$q} = is_array($strokes) ? $strokes : [];
            }
            stgnk_json(['success'=>true,'annotations'=>$out]);
        }
        if ($stgaction === 'anno_save') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') stgnk_json(['success'=>false,'message'=>'Metode tidak valid.'],405);
            require_sesskey();
            stgnk_annotation_exists();
            // Anotasi hanya boleh disimpan setelah peserta punya review yang tersimpan.
            if (!$DB->get_manager()->table_exists(new xmldb_table(STG_REVIEW_T)) || !$DB->record_exists(STG_REVIEW_T, ['userid'=>(int)$USER->id])) {
                stgnk_json(['success'=>false,'message'=>'Selesaikan ujian dahulu.'],403);
            }
            $q = required_param('question', PARAM_INT);
            if ($q < 0 || $q >= 80) stgnk_json(['success'=>false,'message'=>'Nomor soal tidak valid.'],400);
            $raw = required_param('strokesjson', PARAM_RAW);
            if (strlen($raw) > 250000) stgnk_json(['success'=>false,'message'=>'Coretan terlalu besar.'],413);
            $strokes = json_decode($raw, true);
            if (!is_array($strokes) || count($strokes)>350) stgnk_json(['success'=>false,'message'=>'Data coretan tidak valid.'],400);
            foreach ($strokes as $stroke) {
                if (!is_array($stroke) || !isset($stroke['tool'], $stroke['points']) ||
                    !in_array($stroke['tool'], ['pen','marker','eraser'], true) || !is_array($stroke['points']) || count($stroke['points']) > 3000) {
                    stgnk_json(['success'=>false,'message'=>'Format coretan tidak valid.'],400);
                }
                foreach ($stroke['points'] as $point) {
                    if (!is_array($point) || count($point)!==2 || !is_numeric($point[0]) || !is_numeric($point[1]) ||
                        $point[0]<0 || $point[0]>1 || $point[1]<0 || $point[1]>1) {
                        stgnk_json(['success'=>false,'message'=>'Koordinat coretan tidak valid.'],400);
                    }
                }
            }
            $where = ['userid'=>(int)$USER->id,'questionnum'=>$q];
            $r = $DB->get_record(STG_ANNO_T,$where);
            if (!$r) $r = (object)$where;
            $r->strokesjson = json_encode($strokes,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $r->timemodified = time();
            if (isset($r->id)) $DB->update_record(STG_ANNO_T,$r);
            else $DB->insert_record(STG_ANNO_T,$r);
            stgnk_json(['success'=>true]);
        }
        if ($stgaction === 'anno_clear') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') stgnk_json(['success'=>false,'message'=>'Metode tidak valid.'],405);
            require_sesskey();
            stgnk_annotation_exists();
            $DB->delete_records(STG_ANNO_T, ['userid'=>(int)$USER->id]);
            stgnk_json(['success'=>true]);
        }
        if($stgaction==='leaderboard') {
            if (!$DB->get_manager()->table_exists(new xmldb_table(STG_LB_T))) {
                stgnk_json(['success'=>true,'leaderboard'=>[]]);
            }
            $records=$DB->get_records(STG_LB_T,null,'score DESC, timetaken ASC, timecreated ASC');
            $rows=[];$rank=0;
            foreach($records as $r){$rank++;$rows[]=['rank'=>$rank,'username'=>$r->username,'score'=>(int)$r->score,'correct'=>(int)$r->correct,'timetaken'=>(int)$r->timetaken,'isme'=>((int)$r->userid===(int)$USER->id)];}
            stgnk_json(['success'=>true,'leaderboard'=>$rows]);
        }
        if($stgaction==='last_review') {
            if (!$DB->get_manager()->table_exists(new xmldb_table(STG_REVIEW_T))) {
                stgnk_json(['success'=>false,'message'=>'Belum ada jawaban terakhir.'],404);
            }
            $r=$DB->get_record(STG_REVIEW_T,['userid'=>(int)$USER->id]);
            if(!$r) stgnk_json(['success'=>false,'message'=>'Belum ada jawaban terakhir.'],404);
            $a=json_decode($r->answersjson,true);
            if(!is_array($a)||count($a)!==80) stgnk_json(['success'=>false,'message'=>'Data jawaban tidak valid.'],500);
            stgnk_json(['success'=>true,'review'=>['answers'=>array_map(static function($v) { return ((int)$v < 0) ? null : (int)$v; }, $a),'score'=>(int)$r->score,'correct'=>(int)$r->correct,'timetaken'=>(int)$r->timetaken]]);
        }
        stgnk_json(['success'=>false,'message'=>'Aksi tidak dikenal.'],400);
    }catch(Throwable $e){error_log('STANGOLDEN NK P01: '.$e->getMessage());stgnk_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
}

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, no-cache, must-revalidate');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<link rel="icon" type="image/png" href="https://stangolden.com/elearning/JOSS.png">
<title>Latihan Etika PNS Paket 01 (Hlm. 05–14) • STANGOLDEN</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#F2EEFF;--ink:#17131F;--violet:#7C3FFF;--lime:#C7FF42;--pink:#FF4D8D;--sky:#CDEBFF;--muted:#665E7C;--white:#fff;--red:#D84461;--green:#17865F;--border:3px solid var(--ink);--shadow:6px 6px 0 var(--ink);--shadow-sm:4px 4px 0 var(--ink)}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--bg);background-image:radial-gradient(circle,rgba(23,19,31,.08) 1.4px,transparent 1.4px);background-size:22px 22px;color:var(--ink);font-family:'Plus Jakarta Sans',sans-serif}h1,h2,h3{font-family:'Space Grotesk',sans-serif}
.wrap{max-width:1160px;margin:auto;padding:18px 18px 52px}.topbar{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:18px}.brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:21px}.brandmark{width:42px;height:42px;display:grid;place-items:center;border:var(--border);background:var(--ink);color:var(--lime);border-radius:10px;transform:rotate(-5deg);font-family:'Space Grotesk',sans-serif}.brand span{color:var(--violet)}
.btn{border:var(--border);border-radius:10px;padding:10px 14px;background:#fff;box-shadow:var(--shadow-sm);font-weight:800;cursor:pointer;font-family:inherit}.btn:hover{transform:translate(2px,2px);box-shadow:2px 2px 0 var(--ink)}.btn.primary{background:var(--pink);color:#fff}.btn.dark{background:var(--ink);color:var(--lime)}
.hero{border:var(--border);border-radius:18px;background:#fff;box-shadow:var(--shadow);padding:22px;margin-bottom:18px;position:relative;overflow:hidden}.hero:after{content:"✦";position:absolute;right:24px;top:8px;font-size:76px;color:var(--lime);transform:rotate(12deg);text-shadow:3px 3px 0 var(--ink)}.hero .eyebrow{display:inline-block;border:2px solid var(--ink);border-radius:999px;background:var(--lime);padding:6px 10px;font-size:12px;font-weight:900}.hero h1{font-size:34px;margin:10px 0 6px}.hero p{max-width:780px;color:var(--muted);margin:0;line-height:1.55}
.status{display:grid;grid-template-columns:auto 1fr auto;gap:14px;align-items:center;border:var(--border);border-radius:14px;background:#fff;box-shadow:var(--shadow);padding:13px 15px;margin-bottom:18px}.subject{border:2px solid var(--ink);border-radius:999px;padding:7px 11px;font-weight:900;font-size:12px;background:var(--sky)}.progress-wrap{min-width:180px}.progress-meta{display:flex;justify-content:space-between;font-size:12px;font-weight:800;color:var(--muted);margin-bottom:5px}.track{height:11px;border:2px solid var(--ink);border-radius:999px;overflow:hidden;background:#eee7ff}.fill{height:100%;width:0;background:repeating-linear-gradient(45deg,var(--lime),var(--lime) 10px,#b8ef36 10px,#b8ef36 20px)}.timer{background:var(--ink);color:#fff;border-radius:10px;padding:9px 13px;font-weight:900;font-variant-numeric:tabular-nums}
.grid{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:18px;align-items:start}.card{border:var(--border);border-radius:18px;background:#fff;box-shadow:var(--shadow)}.qcard{padding:24px}.qmeta{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px}.badge{border:2px solid var(--ink);border-radius:8px;padding:5px 9px;font-size:12px;font-weight:900}.badge.no{background:var(--lime)}.badge.tpa{background:var(--sky)}.badge.tsk{background:#FFD5E4}.badge.diff{background:#FFE49A}.qtext{font-size:20px;font-weight:800;line-height:1.5;margin-bottom:18px;text-align:justify;text-justify:inter-word}.passage{background:#F7F3FF;border:2px dashed var(--ink);padding:14px;border-radius:12px;margin-bottom:16px;line-height:1.6;color:#453D58}
.options{display:flex;flex-direction:column;gap:10px}.option{display:flex;gap:12px;align-items:flex-start;border:var(--border);border-radius:12px;padding:13px 14px;background:#fff;box-shadow:3px 3px 0 var(--ink);cursor:pointer;font-weight:700;line-height:1.45}.option:hover{transform:translate(-1px,-1px);box-shadow:5px 5px 0 var(--ink)}.option.selected{background:var(--violet);color:#fff;transform:translate(2px,2px);box-shadow:2px 2px 0 var(--ink)}.letter{width:30px;height:30px;display:grid;place-items:center;border:2px solid var(--ink);border-radius:8px;background:#EFE8FF;color:var(--ink);font-family:'Space Grotesk',sans-serif;flex:0 0 auto}.option.selected .letter{background:var(--lime)}.qnav{display:flex;justify-content:space-between;gap:10px;margin-top:20px}
.sidebar{position:sticky;top:10px;display:flex;flex-direction:column;gap:14px}.side{padding:16px}.side h3{margin:0 0 10px;font-size:15px}.numgrid{display:grid;grid-template-columns:repeat(5,1fr);gap:7px}.numgrid button{aspect-ratio:1;border:2px solid var(--ink);border-radius:8px;background:#fff;font-weight:800;cursor:pointer}.numgrid button.answered{background:var(--lime)}.numgrid button.current{background:var(--ink);color:#fff}
.fig-wrap{margin:4px 0 18px}.fig-board{display:grid;align-items:center;gap:8px;padding:12px;border:2px dashed #BFAFF0;border-radius:14px;background:#FAF8FF}.analogy-board{grid-template-columns:1fr auto 1fr auto 1fr auto 1fr}.figbox{position:relative;min-height:145px;display:grid;place-items:center}.figbox>span{position:absolute;left:6px;top:6px;width:26px;height:26px;border:2px solid var(--ink);border-radius:50%;display:grid;place-items:center;background:var(--lime);font-weight:900;z-index:2}.figsym{font-size:26px;font-weight:900}.qmark{border:3px solid var(--ink);border-radius:14px;background:#fff}.qmark b{font-size:56px}.fig-options{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}.fig-option{position:relative;border:var(--border);border-radius:12px;background:#fff;box-shadow:3px 3px 0 var(--ink);cursor:pointer;padding:8px}.fig-option.selected{outline:4px solid var(--violet);transform:translate(2px,2px);box-shadow:1px 1px 0 var(--ink)}.fig-option .flabel{position:absolute;left:8px;top:8px;width:29px;height:29px;border:2px solid var(--ink);border-radius:50%;display:grid;place-items:center;background:var(--lime);font-weight:900;z-index:2}.fig-svg{width:100%;height:auto;display:block}.fig-note{border:2px dashed var(--ink);background:#FFF9D9;border-radius:10px;padding:10px;font-size:13px;font-weight:700;margin-bottom:10px}
.result{display:none}.result-card{padding:24px}.scorehead{display:grid;grid-template-columns:150px 1fr;gap:20px;align-items:center}.scorebox{width:140px;height:140px;border:var(--border);border-radius:26px;background:var(--lime);box-shadow:var(--shadow);display:grid;place-items:center;transform:rotate(-2deg)}.scorebox strong{font-size:44px;font-family:'Space Grotesk',sans-serif}.breakdown{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin:18px 0}.break{border:var(--border);border-radius:14px;padding:14px;box-shadow:var(--shadow-sm)}.break.tpa{background:#DDF1FF}.break.tsk{background:#FFE0EA}.review{border:var(--border);border-radius:14px;padding:16px;margin:13px 0;box-shadow:var(--shadow-sm)}.ok{color:var(--green);font-weight:900}.bad{color:var(--red);font-weight:900}.source{display:inline-block;border:2px solid var(--ink);background:#F5F1FF;border-radius:8px;padding:6px 8px;font-size:12px;font-weight:800}
.modal-overlay{position:fixed;inset:0;background:rgba(23,19,31,.55);display:none;align-items:center;justify-content:center;padding:20px;z-index:50}.modal-overlay.show{display:flex}.modal{max-width:430px;width:100%;border:var(--border);border-radius:18px;background:#fff;box-shadow:8px 8px 0 var(--ink);padding:24px;text-align:center}
@media(max-width:850px){.grid{grid-template-columns:1fr}.sidebar{position:static;order:-1}.numgrid{grid-template-columns:repeat(10,1fr)}}
@media(max-width:620px){.wrap{padding:10px 10px 34px}.hero h1{font-size:26px}.hero:after{font-size:52px}.status{grid-template-columns:1fr auto}.progress-wrap{grid-column:1/-1;order:3}.qcard{padding:16px 13px}.qtext{font-size:17px}.numgrid{grid-template-columns:repeat(6,1fr)}.analogy-board{grid-template-columns:1fr}.figsym{transform:rotate(90deg);text-align:center}.fig-options{grid-template-columns:repeat(2,1fr)}.scorehead{grid-template-columns:1fr}.breakdown{grid-template-columns:1fr}}

.series-tag{display:inline-block;font-size:.48em;vertical-align:middle;background:var(--pink);color:#fff;border:2px solid var(--ink);border-radius:9px;padding:4px 9px;transform:rotate(-2deg);margin-left:7px;box-shadow:3px 3px 0 var(--ink)}
.learn-box{background:linear-gradient(180deg,#fff,#fbf9ff);border:2px solid #d9cff7;border-radius:14px;padding:15px 16px;line-height:1.65}
.learn-title{font-family:'Space Grotesk',sans-serif;font-weight:800;font-size:16px;margin-bottom:8px;color:#38206c}
.hl{background:#dfff72;padding:1px 4px;border-radius:4px;color:#17131f}
.steps{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px;margin:10px 0}
.steps.vertical{grid-template-columns:1fr}
.steps span,.formula{border:2px solid #17131f;border-radius:9px;background:#fff;padding:8px 10px;font-family:'Space Grotesk',sans-serif}
.formula{margin:8px 0;background:#f0e9ff}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:10px 0}
.two-col>div{border:2px solid #17131f;border-radius:10px;padding:10px;background:#fff}
.why{border-left:5px solid var(--violet);background:#f2edff;padding:10px 12px;border-radius:8px;margin:10px 0}
.takeaway{background:#fff4b8;border:2px solid #17131f;border-radius:10px;padding:10px 12px;margin-top:10px;font-weight:700}
.flow{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin:10px 0}
.flow span{border:2px solid #17131f;border-radius:9px;padding:7px 9px;background:#fff}
.logic-row{display:grid;grid-template-columns:repeat(5,1fr);gap:7px;margin:12px 0}
.logic-row span{border:2px solid #17131f;border-radius:9px;padding:10px 6px;text-align:center;background:#fff;font-weight:800}
.logic-row .fixed{background:#ffd7e5}
.logic-row.final span{background:#dfff72}
.review{background:linear-gradient(180deg,#fff,#fdfcff)}
.review .explanation{line-height:1.7;margin-top:12px}
@media(max-width:620px){
 .steps,.two-col{grid-template-columns:1fr}
 .logic-row{grid-template-columns:repeat(5,minmax(54px,1fr));overflow-x:auto}
 .series-tag{display:block;width:max-content;margin:8px 0 0}
}


.start-screen{
  max-width:820px;
  margin:36px auto 0;
}
.start-card{
  border:var(--border);
  border-radius:22px;
  background:#fff;
  box-shadow:var(--shadow);
  padding:29px;
  position:relative;
  overflow:hidden;
}
.start-card:after{
  content:"✦";
  position:absolute;
  right:29px;
  top:18px;
  font-size:72px;
  color:var(--lime);
  text-shadow:3px 3px 0 var(--ink);
  transform:rotate(10deg);
}
.start-badge{
  display:inline-block;
  border:2px solid var(--ink);
  border-radius:999px;
  background:var(--lime);
  padding:7px 12px;
  font-size:12px;
  font-weight:900;
}
.start-title{
  font-family:'Space Grotesk',sans-serif;
  font-size:34px;
  line-height:1.15;
  margin:14px 0 10px;
  max-width:650px;
}
.start-title .series-tag{
  font-size:.45em;
}
.start-sub{
  color:var(--muted);
  line-height:1.65;
  max-width:670px;
  margin-bottom:22px;
}
.start-info{
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:12px;
  margin:20px 0 24px;
}
.start-info-box{
  border:2px solid var(--ink);
  border-radius:13px;
  padding:14px;
  background:#faf8ff;
}
.start-info-box small{
  display:block;
  color:var(--muted);
  font-weight:700;
  margin-bottom:4px;
}
.start-info-box strong{
  font-family:'Space Grotesk',sans-serif;
  font-size:18px;
}
.start-rules{
  border:2px dashed #b9a8ef;
  background:#f8f5ff;
  border-radius:14px;
  padding:15px 16px;
  margin-bottom:22px;
}
.start-rules h3{
  margin:0 0 9px;
  font-size:16px;
}
.start-rules ul{
  margin:0;
  padding-left:20px;
  line-height:1.7;
  color:#4d4560;
}
.start-btn{
  width:100%;
  border:var(--border);
  border-radius:14px;
  background:var(--pink);
  color:#fff;
  padding:15px 18px;
  box-shadow:var(--shadow);
  font-family:'Space Grotesk',sans-serif;
  font-size:17px;
  font-weight:800;
  cursor:pointer;
  transition:.12s ease;
}
.start-btn:hover{
  transform:translate(3px,3px);
  box-shadow:3px 3px 0 var(--ink);
}
#examScreen{
  display:none;
}
@media(max-width:620px){
  .start-screen{margin-top:18px}
  .start-card{padding:20px 16px}
  .start-card:after{font-size:48px;right:16px}
  .start-title{font-size:27px}
  .start-info{grid-template-columns:1fr}
}


.top-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.leaderboard-overlay{
  position:fixed;
  inset:0;
  background:rgba(23,19,31,.58);
  display:none;
  align-items:center;
  justify-content:center;
  padding:18px;
  z-index:80;
}
.leaderboard-overlay.show{display:flex}
.leaderboard-modal{
  width:min(720px,100%);
  max-height:88vh;
  overflow:auto;
  border:var(--border);
  border-radius:20px;
  background:#fff;
  box-shadow:9px 9px 0 var(--ink);
  padding:22px;
}
.leaderboard-head{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:12px;
  margin-bottom:16px;
}
.leaderboard-title{
  margin:0;
  font-size:24px;
}
.leaderboard-sub{
  margin:5px 0 0;
  color:var(--muted);
  font-size:13px;
  line-height:1.5;
}
.lb-close{
  width:40px;
  height:40px;
  padding:0;
  border:var(--border);
  border-radius:10px;
  background:#fff;
  box-shadow:var(--shadow-sm);
  font-size:18px;
  font-weight:900;
  cursor:pointer;
}
.lb-table-wrap{
  overflow-x:auto;
  border:var(--border);
  border-radius:14px;
}
.lb-table{
  width:100%;
  border-collapse:collapse;
  min-width:500px;
}
.lb-table th,
.lb-table td{
  padding:12px 13px;
  border-bottom:2px solid var(--ink);
  text-align:left;
}
.lb-table th{
  background:#f2ecff;
  font-family:'Space Grotesk',sans-serif;
  font-size:12px;
  text-transform:uppercase;
  letter-spacing:.04em;
}
.lb-table tr:last-child td{border-bottom:0}
.lb-table tbody tr.me td{background:#efffc5}
.lb-rank{
  width:44px;
  height:32px;
  display:grid;
  place-items:center;
  border:2px solid var(--ink);
  border-radius:8px;
  font-family:'Space Grotesk',sans-serif;
  font-weight:900;
  background:#fff;
}
.lb-rank.top1{background:#ffd94f}
.lb-rank.top2{background:#e8e8ee}
.lb-rank.top3{background:#e8b779}
.lb-user{
  font-weight:900;
  word-break:break-word;
}
.lb-score{
  display:inline-block;
  min-width:54px;
  text-align:center;
  border:2px solid var(--ink);
  border-radius:8px;
  background:var(--lime);
  padding:5px 8px;
  font-family:'Space Grotesk',sans-serif;
  font-weight:900;
}
.lb-empty,
.lb-loading{
  padding:29px 16px;
  text-align:center;
  color:var(--muted);
}
.lb-note{
  margin-top:12px;
  padding:10px 12px;
  border:2px dashed #b8a9e9;
  border-radius:10px;
  background:#faf8ff;
  font-size:12px;
  color:#5b526f;
  line-height:1.55;
}

.ranking-locked{
  text-align:center;
  padding:8px 0 4px;
}
.ranking-locked-icon{
  width:54px;
  height:54px;
  margin:2px auto 10px;
  display:grid;
  place-items:center;
  border:var(--border);
  border-radius:14px;
  background:var(--ink);
  color:var(--lime);
  font-size:25px;
  box-shadow:3px 3px 0 var(--violet);
}
.ranking-locked h3{
  margin:0 0 7px;
  font-size:20px;
}
.ranking-locked p{
  margin:0 auto;
  max-width:560px;
  color:var(--muted);
  line-height:1.55;
  font-size:13px;
}
.ranking-promo{
  width:min(420px,100%);
  margin:16px auto 0;
}
.ranking-promo img{
  display:block;
  width:100%;
  height:auto;
  border:2px solid var(--ink);
  border-radius:12px;
  box-shadow:3px 3px 0 var(--ink);
}
.ranking-register-btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  margin-top:14px;
  padding:11px 16px;
  border:2px solid var(--ink);
  border-radius:10px;
  background:var(--lime);
  color:var(--ink);
  font-weight:900;
  text-decoration:none;
  box-shadow:3px 3px 0 var(--ink);
  transition:.15s ease;
}
.ranking-register-btn:hover{
  transform:translate(-1px,-1px);
  box-shadow:4px 4px 0 var(--ink);
}
.ranking-register-btn:active{
  transform:translate(2px,2px);
  box-shadow:1px 1px 0 var(--ink);
}
.save-status{
  margin-top:10px;
  font-size:12px;
  color:var(--muted);
}


.admin-question-stat{
  margin:10px 0 12px;
  padding:10px 12px;
  border:2px solid var(--ink);
  border-radius:10px;
  background:#eef8ff;
  box-shadow:2px 2px 0 var(--ink);
  font-size:12px;
  line-height:1.5;
  font-weight:700;
}
.admin-question-stat strong{
  font-weight:900;
}
.admin-question-stat .stat-good{
  color:#247500;
}
.admin-question-stat .stat-bad{
  color:#d92f55;
}
.review-locked{
  border:var(--border);
  border-radius:14px;
  padding:22px 18px;
  margin:13px 0;
  box-shadow:var(--shadow-sm);
  background:linear-gradient(135deg,#fff 0%,#f7f1ff 100%);
  text-align:center;
}
.review-lock-icon{
  width:52px;
  height:52px;
  margin:0 auto 10px;
  display:grid;
  place-items:center;
  border:var(--border);
  border-radius:14px;
  background:var(--ink);
  color:var(--lime);
  font-size:24px;
  box-shadow:3px 3px 0 var(--violet);
}
.review-locked h4{margin:0 0 6px;font-size:17px}
.review-locked p{margin:0;color:var(--muted);line-height:1.55;font-size:13px}
.review-locked-promo{
  width:min(420px,100%);
  margin:16px auto 0;
}
.review-locked-promo img{
  display:block;
  width:100%;
  height:auto;
  border:2px solid var(--ink);
  border-radius:12px;
  box-shadow:3px 3px 0 var(--ink);
}
.review-register-btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  margin-top:14px;
  padding:11px 16px;
  border:2px solid var(--ink);
  border-radius:10px;
  background:var(--lime);
  color:var(--ink);
  font-weight:900;
  text-decoration:none;
  box-shadow:3px 3px 0 var(--ink);
  transition:.15s ease;
}
.review-register-btn:hover{
  transform:translate(-1px,-1px);
  box-shadow:4px 4px 0 var(--ink);
}
.review-register-btn:active{
  transform:translate(2px,2px);
  box-shadow:1px 1px 0 var(--ink);
}
@media(max-width:620px){
  .top-actions{width:100%;justify-content:flex-end}
  .leaderboard-modal{padding:16px 12px}
  .leaderboard-title{font-size:21px}
}


.review-options{
  display:flex;
  flex-direction:column;
  gap:7px;
  margin:12px 0 14px;
}
.review-option{
  display:flex;
  gap:9px;
  align-items:flex-start;
  border:2px solid #d9d2e8;
  border-radius:9px;
  padding:9px 10px;
  background:#fff;
  line-height:1.45;
}
.review-option .review-letter{
  width:27px;
  height:27px;
  flex:0 0 auto;
  display:grid;
  place-items:center;
  border:2px solid var(--ink);
  border-radius:7px;
  background:#f2edff;
  font-family:'Space Grotesk',sans-serif;
  font-weight:900;
}
.review-option.correct-option{
  background:#efffc5;
  border-color:#88b92f;
}
.review-option.user-wrong-option{
  background:#ffe8ee;
  border-color:#e797aa;
}
.review-option .option-note{
  margin-left:auto;
  flex:0 0 auto;
  font-size:11px;
  font-weight:900;
  border-radius:999px;
  padding:3px 7px;
  border:1.5px solid var(--ink);
  white-space:nowrap;
}
.review-option.correct-option .option-note{background:var(--lime)}
.review-option.user-wrong-option .option-note{background:#ffb7c9}
@media(max-width:620px){
  .review-option{padding:8px;font-size:13px}
  .review-option .option-note{font-size:10px}
}


.review-question-head{
  display:flex;
  align-items:flex-start;
  gap:10px;
  margin-bottom:8px;
}
.review-number{
  flex:0 0 auto;
  min-width:34px;
  height:34px;
  display:grid;
  place-items:center;
  border:2px solid var(--ink);
  border-radius:9px;
  background:var(--lime);
  box-shadow:2px 2px 0 var(--ink);
  font-family:'Space Grotesk',sans-serif;
  font-size:14px;
  font-weight:900;
  line-height:1;
}
.review-question-text{
  flex:1;
  font-family:'Plus Jakarta Sans',sans-serif;
  font-size:15px;
  font-weight:800;
  line-height:1.55;
  text-align:justify;
  text-justify:inter-word;
  padding-top:4px;
}
@media(max-width:620px){
  .review-question-head{gap:8px}
  .review-number{
    min-width:31px;
    height:31px;
    font-size:13px;
    border-radius:8px;
  }
  .review-question-text{
    font-size:14px;
    line-height:1.5;
  }
}


.review-figure-wrap{
  margin:10px 0 14px;
  padding:12px;
  border:2px dashed #c8b8f1;
  border-radius:14px;
  background:#faf8ff;
}
.review-figure-wrap .fig-board{
  margin-bottom:10px;
}
.review-figure-wrap .fig-options{
  display:grid;
  grid-template-columns:repeat(5,minmax(0,1fr));
  gap:8px;
}
.review-figure-wrap .fig-option{
  border:2px solid var(--ink);
  border-radius:11px;
  background:#fff;
  padding:6px;
  position:relative;
}
.review-figure-wrap .fig-option.correct-option{
  background:#efffc5;
  border-color:#88b92f;
}
.review-figure-wrap .fig-option.user-option{
  background:#ffe8ee;
  border-color:#e797aa;
}
.review-figure-wrap .fig-option.correct-option.user-option{
  background:#dff7a8;
}
.review-figure-wrap .fig-option .flabel{
  position:absolute;
  left:6px;
  top:6px;
  width:22px;
  height:22px;
  border:2px solid var(--ink);
  border-radius:7px;
  display:grid;
  place-items:center;
  background:#f2edff;
  font-family:'Space Grotesk',sans-serif;
  font-weight:900;
  font-size:11px;
  z-index:2;
}
.review-figure-wrap .fig-option .option-note{
  position:absolute;
  right:6px;
  bottom:6px;
  font-size:10px;
  font-weight:900;
  border-radius:999px;
  padding:2px 6px;
  border:1.5px solid var(--ink);
  white-space:nowrap;
  background:#fff;
  z-index:2;
}
.review-figure-wrap .fig-option.correct-option .option-note{
  background:var(--lime);
}
.review-figure-wrap .fig-option.user-option .option-note{
  background:#ffb7c9;
}
.review-figure-wrap .fig-option.correct-option.user-option .option-note{
  background:#dfff72;
}
.review-figure-wrap .fig-svg{
  width:100%;
  height:auto;
}
@media(max-width:620px){
  .review-figure-wrap .fig-options{
    grid-template-columns:repeat(2,minmax(0,1fr));
  }
}


.matrix-board{
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:9px;
  max-width:540px;
  margin:0 auto;
  padding:10px;
  border:2px dashed #bfaef0;
  border-radius:14px;
  background:#faf8ff;
}
.matrix-cell{
  position:relative;
  border:2px solid var(--ink);
  border-radius:11px;
  background:#fff;
  min-height:135px;
  display:grid;
  place-items:center;
  overflow:hidden;
}
.matrix-cell.missing{
  background:#fff7c9;
  font-family:'Space Grotesk',sans-serif;
  font-size:48px;
  font-weight:900;
}
.matrix-cell .cell-label{
  position:absolute;
  top:5px;
  left:6px;
  font-size:10px;
  font-weight:900;
  color:#756c89;
}
@media(max-width:620px){
  .matrix-board{gap:5px;padding:6px}
  .matrix-cell{min-height:92px}
}


.series-hots-board{
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:10px;
  margin:10px 0 14px;
}
.series-hots-frame{
  position:relative;
  border:2px solid var(--ink);
  border-radius:12px;
  background:#fff;
  padding:5px;
}
.series-hots-frame .seq-label{
  position:absolute;
  top:6px;
  left:7px;
  z-index:3;
  min-width:24px;
  height:24px;
  padding:0 5px;
  border:2px solid var(--ink);
  border-radius:7px;
  background:var(--lime);
  display:grid;
  place-items:center;
  font:900 11px 'Space Grotesk',sans-serif;
}
.xor-matrix-board{
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:9px;
  max-width:560px;
  margin:10px auto 14px;
}
.xor-cell{
  position:relative;
  border:2px solid var(--ink);
  border-radius:11px;
  background:#fff;
  padding:4px;
  min-height:138px;
  display:grid;
  place-items:center;
}
.xor-cell.missing{
  background:#fff7c9;
  font:900 48px 'Space Grotesk',sans-serif;
}
.xor-cell .cell-label{
  position:absolute;
  top:5px;
  left:6px;
  font:900 10px 'Space Grotesk',sans-serif;
  color:#756c89;
}
@media(max-width:620px){
  .series-hots-board{grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
  .xor-matrix-board{gap:5px}
  .xor-cell{min-height:95px}
}


.data-table-wrap{
  margin:10px 0 18px;
  overflow-x:auto;
}
.data-table{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  background:#fff;
  border:2px solid var(--ink);
  border-radius:12px;
  overflow:hidden;
  font-size:14px;
}
.data-table th,
.data-table td{
  padding:10px 12px;
  border-right:1px solid #b8afc9;
  border-bottom:1px solid #b8afc9;
  text-align:center;
}
.data-table th{
  background:#efe8ff;
  font-family:'Space Grotesk',sans-serif;
  font-weight:900;
}
.data-table tr:last-child td{border-bottom:none}
.data-table th:last-child,
.data-table td:last-child{border-right:none}


/* Rangkuman belajar per soal: nyaman dibaca di desktop dan ponsel */
.lesson-card{padding:19px 20px;background:#fff;border:2px solid #d6c8ff;box-shadow:3px 3px 0 #e9e0ff;color:#292238;line-height:1.75}
.lesson-card .learn-title{font-size:17px;letter-spacing:-.25px;color:#332064;margin-bottom:16px}
.lesson-section{margin:12px 0;padding:13px 15px;border:1.5px solid #ddd6ef;border-radius:11px;background:#faf8ff}
.lesson-section.lesson-reason{background:#f1fff0;border-color:#b8e8b2}
.lesson-kicker{font-weight:900;font-size:11px;letter-spacing:.06em;color:#6552a0;text-transform:uppercase;margin-bottom:7px}
.lesson-section p{margin:7px 0;font-size:14px;text-align:left;line-height:1.75}
.lesson-section strong{color:#275c41}
.lesson-memory{display:flex;flex-direction:column;gap:7px;border:2px solid #24202f;border-radius:11px;padding:13px 15px;background:#fff2b5;font-size:13px;line-height:1.7}
.lesson-memory strong{font-size:13px}
.lesson-source{font-size:12px;color:#5c536d;margin-top:13px;padding-top:11px;border-top:1px dashed #c7bde3}
@media(max-width:620px){.lesson-card{padding:12px}.lesson-section{padding:11px 12px}.lesson-section p{font-size:13px}.lesson-card .learn-title{font-size:15px}}

/* Penanda konsep inti dalam rangkuman */
.study-highlight{background:linear-gradient(180deg,transparent 33%,#ffec83 33%,#ffec83 92%,transparent 92%);color:#28203c;font-weight:850;padding:0 2px;border-radius:2px;-webkit-box-decoration-break:clone;box-decoration-break:clone}
.lesson-reason .study-highlight{background:linear-gradient(180deg,transparent 33%,#b7f4bd 33%,#b7f4bd 92%,transparent 92%)}
.lesson-memory .study-highlight{background:#fff;color:#3b2b73;padding:1px 4px;border-radius:4px;box-shadow:inset 0 -2px #d6bcff}
.study-highlight::selection{background:#c1a9ff}

/* ANOTASI PESERTA — overlay di atas kartu soal */
.qcard{position:relative}
/* Coretan hanya tersedia dalam kartu pembahasan, bukan saat ujian. */
.review{position:relative;isolation:isolate}
.review-annotation-toolbar{position:sticky;top:10px;z-index:20;display:flex;align-items:center;gap:7px;flex-wrap:wrap;padding:10px;margin:12px 0;background:#f4efff;border:2px dashed #bba9ed;border-radius:12px}
.review-annotation-toolbar button{border:2px solid #17131f;background:#fff;border-radius:9px;padding:8px 10px;font:800 12px 'Plus Jakarta Sans',sans-serif;cursor:pointer}
.review-annotation-toolbar button.active{background:#c7ff42;box-shadow:2px 2px 0 #17131f}
.review-annotation-toolbar button:disabled{opacity:.4;cursor:default}
.review-annotation-toolbar small{font-size:11px;color:#665e7c;font-weight:700;width:100%}
.review-annotation-canvas{position:absolute;inset:0;width:100%;height:100%;z-index:3;pointer-events:none;touch-action:none;border-radius:13px}
.review.annotating{z-index:4}
.review.annotating .review-annotation-canvas{pointer-events:auto;cursor:crosshair}
@media(max-width:620px){.review-annotation-toolbar{top:6px;gap:5px;padding:8px}.review-annotation-toolbar button{padding:7px 8px;font-size:11px}}

/* Peta Soal versi lebar: memanfaatkan area kanan layar, tanpa mengubah UI lainnya. */
#examScreen.wrap{max-width:1560px;width:calc(100% - 20px)}
#examScreen .grid{grid-template-columns:minmax(0,1fr) minmax(360px,430px);gap:16px}
#examScreen .sidebar{top:12px;max-height:calc(100vh - 24px);min-width:0}
#examScreen .side{min-height:0;display:flex;flex-direction:column}
#examScreen .map-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}
#examScreen .map-head h3{margin:0}
#examScreen .map-toggle{border:2px solid var(--ink);background:#f2eeff;border-radius:9px;padding:7px 10px;font:800 12px 'Plus Jakarta Sans',sans-serif;cursor:pointer;white-space:nowrap}
#examScreen .map-toggle:hover{background:var(--lime)}
#examScreen .numgrid{grid-template-columns:repeat(10,minmax(0,1fr));gap:5px;overflow-y:auto;min-height:0;max-height:calc(100vh - 190px);padding:1px 3px 4px 1px;scrollbar-width:thin}
#examScreen .numgrid button{min-width:0;aspect-ratio:1;padding:0;font-size:12px;border-width:1.5px;border-radius:6px}
#examScreen .map-reopen{display:none;justify-self:end;align-self:start}
#examScreen .grid.map-hidden{grid-template-columns:minmax(0,1fr)}
#examScreen .grid.map-hidden .sidebar{display:none}
#examScreen .grid.map-hidden .map-reopen{display:block}
#examScreen .grid.map-hidden .qcard{grid-row:2}
@media(max-width:1000px){
 #examScreen.wrap{width:auto;max-width:1160px}
 #examScreen .grid{grid-template-columns:minmax(0,1fr) minmax(275px,320px)}
 #examScreen .numgrid{grid-template-columns:repeat(7,minmax(0,1fr))}
}
@media(max-width:850px){
 #examScreen .grid{grid-template-columns:minmax(0,1fr)}
 #examScreen .sidebar{position:static;order:-1;max-height:none}
 #examScreen .numgrid{grid-template-columns:repeat(10,minmax(0,1fr));max-height:225px}
 #examScreen .grid.map-hidden .qcard{grid-row:auto}
}
@media(max-width:620px){
 #examScreen .numgrid{grid-template-columns:repeat(8,minmax(0,1fr));max-height:195px}
 #examScreen .numgrid button{font-size:11px}
}
</style>
</head><body>

<div class="wrap start-screen" id="startScreen">
  <div class="topbar">
    <div class="brand">
      <div class="brandmark">S</div>
      STANGOLDEN<span>.exam</span>
    </div>
    <div class="top-actions">
      
      <button class="btn" onclick="openLeaderboard()">🏆 Lihat Ranking</button>
      <button class="btn" onclick="reviewLastAttempt()">📝 Review Jawaban Terakhir</button>
    </div>
  </div>

  <div class="start-card">
    <span class="start-badge">BOOTCAMP UPKP 2026</span>

    <h1 class="start-title">
      Latihan Etika PNS
      <span class="series-tag">Paket 01 • Hlm. 05–14</span>
    </h1>

    <p class="start-sub">
      Ujian belum dimulai. Baca informasi singkat di bawah, lalu klik
      <b>Mulai Ujian</b> jika sudah siap.
    </p>

    <div class="start-info">
      <div class="start-info-box">
        <small>Jumlah Soal</small>
        <strong>80 Soal</strong>
      </div>
      <div class="start-info-box">
        <small>Waktu</small>
        <strong>65 Menit</strong>
      </div>
      <div class="start-info-box">
        <small>Komposisi</small>
        <strong>10 halaman × 10 soal</strong>
      </div>
    </div>

    <div class="start-rules">
      <h3>📌 Sebelum mulai</h3>
      <ul>
        <li>Timer baru berjalan setelah tombol <b>Mulai Ujian</b> diklik.</li>
        <li>Durasi latihan ditetapkan 65 menit.</li>
        <li>Jawaban dapat diubah selama ujian belum dikumpulkan.</li>
        <li>Gunakan peta soal untuk berpindah antarsoal.</li>
        <li>Jika waktu habis, jawaban akan dikumpulkan otomatis.</li>
      </ul>
    </div>

    <button class="start-btn" onclick="startExam()">Mulai Ujian →</button>
  </div>
</div>

<div class="wrap" id="examScreen">
<div class="topbar"><div class="brand"><div class="brandmark">S</div> STANGOLDEN<span>.exam</span></div><div class="top-actions"><button class="btn" onclick="openLeaderboard()">🏆 Lihat Ranking</button><button class="btn" onclick="openSubmit()">Selesai ujian</button></div></div>
<section class="hero">
<span class="eyebrow">BOOTCAMP UPKP 2026</span>
<h1>Latihan Etika PNS <span class="series-tag">Paket 01 • Hlm. 05–14</span></h1>
</section>
<div class="status"><div class="subject" id="subject" style="display:none"></div><div class="progress-wrap"><div class="progress-meta"><span id="progText">0 dari 80 dijawab</span><span id="progPct">0%</span></div><div class="track"><div class="fill" id="progFill"></div></div></div><div class="timer" id="timer">65:00</div></div>
<div class="grid" id="examQuestionGrid"><button type="button" class="map-toggle map-reopen" id="mapReopenButton" onclick="toggleQuestionMap(true)" aria-controls="questionMapPanel" aria-expanded="false">☷ Tampilkan Peta Soal</button><main class="card qcard"><div class="qmeta"><span class="badge no" id="qNo">Soal 1</span></div><div id="passage"></div><div class="qtext" id="qText"></div><div id="figArea" class="fig-wrap"></div><div class="options" id="options"></div><div class="qnav"><button class="btn" id="prevBtn" onclick="prevQ()">← Sebelumnya</button><button class="btn primary" id="nextBtn" onclick="nextQ()">Berikutnya →</button></div></main><aside class="sidebar" id="questionMapPanel"><div class="card side"><div class="map-head"><h3>Peta Soal</h3><button type="button" class="map-toggle" id="mapHideButton" onclick="toggleQuestionMap(false)" aria-controls="questionMapPanel" aria-expanded="true">Sembunyikan ✕</button></div><div class="numgrid" id="numGrid"></div></div><button class="btn dark" onclick="openSubmit()">Kumpulkan Jawaban</button><button type="button" class="btn primary" onclick="openSubmit()" style="width:100%;">Selesai Ujian →</button></aside></div>
</div>
<div class="wrap result" id="resultScreen"><div class="topbar"><div class="brand"><div class="brandmark">S</div> STANGOLDEN<span>.exam</span></div><div class="top-actions"><button class="btn" onclick="openLeaderboard()">🏆 Lihat Ranking</button><button class="btn" onclick="reviewLastAttempt()">📝 Review Jawaban Terakhir</button><button class="btn" onclick="retakeQuiz()">Ulangi tes</button></div></div><div id="resultContent"></div></div>

<div class="modal-overlay" id="modal"><div class="modal"><h3 style="font-size:22px;margin:0 0 8px">🚀 Kumpulkan jawaban?</h3><p id="modalText" style="color:var(--muted);line-height:1.55"></p><div style="display:flex;gap:10px"><button class="btn" style="flex:1" onclick="closeSubmit()">Cek lagi</button><button class="btn primary" style="flex:1" onclick="submitExam()">Kumpulkan</button></div></div></div>
<!-- Dialog ranking: dipakai tombol Lihat Ranking di awal, ujian, hasil dan pembahasan. -->
<div class="leaderboard-overlay" id="leaderboardOverlay" role="dialog" aria-modal="true" aria-labelledby="rankingModalTitle" onclick="leaderboardBackdrop(event)">
  <div class="leaderboard-modal">
    <div class="leaderboard-head">
      <div>
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Paket 01</h2>
        <p class="leaderboard-sub" id="rankingModalSub">Ranking berdasarkan hasil percobaan pertama.</p>
      </div>
      <button type="button" class="lb-close" onclick="closeLeaderboard()" aria-label="Tutup ranking">✕</button>
    </div>
    <div id="leaderboardContent" aria-live="polite"><div class="lb-loading">Memuat ranking...</div></div>
    <div class="lb-note">Nilai ranking hanya diambil dari percobaan pertama. Jika nilai sama, peserta dengan waktu pengerjaan lebih cepat mendapat posisi lebih tinggi.</div>
  </div>
</div>
<script>
const MOODLE_SESSKEY=<?= json_encode(sesskey()) ?>;
const STG_LOCAL_KEY="stg_etika_p03_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penetapan Sistem Kerja Fleksibel melalui KMK Nomor 416/KMK.01/2023 merupakan bentuk...", "options": ["Adaptasi organisasi terhadap dinamika lingkungan kerja", "Pengurangan beban kerja operasional pegawai secara permanen", "Penghapusan presensi kehadiran harian di kementerian", "Pemberian kebebasan waktu kerja tanpa evaluasi kinerja", "Penyederhanaan struktur kelembagaan eselon kementerian"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Latar Belakang Sistem Kerja Fleksibel</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sistem Kerja Fleksibel (SKF) merupakan bentuk <strong>adaptasi organisasi terhadap dinamika lingkungan kerja yang terus berubah</strong> dengan tetap menjaga produktivitas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Transformasi digital menuntut organisasi pemerintah bersikap adaptif dan lincah dalam merespons perkembangan zaman. Kebijakan SKF diterbitkan sebagai respons adaptasi organisasi terhadap dinamika kerja modern. Dengan demikian, pelayanan publik tetap produktif dan akuntabel.</p><p>Jawaban tepat: <strong>A. Adaptasi organisasi terhadap dinamika lingkungan kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Beban target kinerja tetap sama dan tidak dikurangi dalam sistem SKF.</li><li><strong>C</strong>: Presensi kehadiran tetap wajib diisi via aplikasi otomasi perkantoran.</li><li><strong>D</strong>: Bekerja fleksibel tetap terikat pada target dan pemantauan atasan.</li><li><strong>E</strong>: Penataan struktur kelembagaan tunduk pada regulasi kelembagaan terpisah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>adaptasi organisasi terhadap dinamika</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dua bentuk fleksibilitas pokok yang diatur dalam Sistem Kerja Fleksibel Kementerian Keuangan adalah...", "options": ["Fleksibilitas target output dan fleksibilitas anggaran belanja", "Fleksibilitas tempat bekerja dan fleksibilitas waktu bekerja", "Fleksibilitas jenjang kepangkatan dan fleksibilitas tunjangan", "Fleksibilitas standar kompetensi dan fleksibilitas cuti dinas", "Fleksibilitas audit kepatuhan dan fleksibilitas tata naskah"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen Sistem Kerja Fleksibel</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sistem Kerja Fleksibel di lingkungan Kementerian Keuangan terdiri dari <strong>fleksibilitas tempat bekerja dan fleksibilitas waktu bekerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. SKF memberikan ruang penyesuaian lokasi kerja (WFO/WFH) dan alokasi waktu kerja yang adaptif. Regulasi KMK 416/2023 menetapkan dua komponen utama yaitu fleksibilitas tempat dan waktu bekerja. Dengan demikian, efektivitas penuntasan tugas dapat dioptimalkan.</p><p>Jawaban tepat: <strong>B. Fleksibilitas tempat bekerja dan fleksibilitas waktu bekerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Target capaian kinerja dan standar output pekerjaan bersifat mengikat.</li><li><strong>C</strong>: Pangkat dan tunjangan diatur baku dalam sistem kepegawaian nasional.</li><li><strong>D</strong>: Standar kompetensi jabatan tidak dapat dilonggarkan secara sepihak.</li><li><strong>E</strong>: Audit kepatuhan tetap berjalan ketat mengawal akuntabilitas instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>fleksibilitas tempat bekerja dan fleksibilitas waktu bekerja</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan tugas dalam sistem kerja fleksibel didukung sarana prasarana yang meliputi...", "options": ["Kendaraan dinas operasional dan fasilitas ruang rapat mewah", "Buku kas manual dan register surat fisik konvensional", "Perangkat keras, perangkat lunak, dan sarana pendukung", "Genset cadangan pribadi dan saluran telepon kabel rumah", "Voucher belanja konsumsi dan akomodasi penginapan dinas"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dukungan Sarana Prasarana SKF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>SKF didukung sarana prasarana teknologi meliputi <strong>perangkat keras, perangkat lunak (aplikasi otomasi perkantoran), dan sarana pendukung lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Kesiapan infrastruktur teknologi informasi adalah tulang punggung kelancaran kerja jarak jauh. Sarana SKF mencakup komputer/laptop, aplikasi otomasi perkantoran, dan jaringan komunikasi data resmi. Dengan demikian, konektivitas koordinasi pegawai tetap terjaga.</p><p>Jawaban tepat: <strong>C. Perangkat keras, perangkat lunak, dan sarana pendukung</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Fasilitas mobilitas fisik bukan sarana utama sistem kerja fleksibel.</li><li><strong>B</strong>: Sistem administrasi SKF bertumpu penuh pada aplikasi digital nirkertas.</li><li><strong>D</strong>: Peralatan sarana dinas disediakan sesuai standar perlengkapan kerja.</li><li><strong>E</strong>: Biaya belanja konsumsi pribadi bukan bagian dari sarana kerja TIK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>perangkat keras • perangkat lunak</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewajiban pegawai mematuhi kode etik dan disiplin dalam pelaksanaan SKF secara eksplisit diatur pada...", "options": ["Diktum pertama huruf a angka 1 KMK 416/2023", "Diktum kelima huruf c angka 2 KMK 416/2023", "Diktum kesepuluh huruf a angka 4 KMK 416/2023", "Diktum keduabelas huruf b angka 3 KMK 416/2023", "Diktum kelimabelas huruf d angka 1 KMK 416/2023"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Etika Diktum Keduabelas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewajiban etika SKF tertuang dalam <strong>Diktum keduabelas huruf b angka 3</strong> KMK 416/KMK.01/2023 tentang kepatuhan kode etik dan norma masyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Klausul ini secara tegas mengikat pegawai agar tidak menyalahartikan fleksibilitas sebagai pelonggaran disiplin. Diktum keduabelas huruf b angka 3 KMK 416/2023 mewajibkan kepatuhan kode etik dan kesopanan. Dengan demikian, kepatuhan moral menjadi syarat sah kerja fleksibel.</p><p>Jawaban tepat: <strong>D. Diktum keduabelas huruf b angka 3 KMK 416/2023</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Diktum pertama memuat ketentuan umum definisi dan ruang lingkup SKF.</li><li><strong>B</strong>: Diktum kelima mengatur pembagian waktu kerja dan jam operasional dinas.</li><li><strong>C</strong>: Diktum kesepuluh mengatur kriteria pegawai yang berhak mengikuti SKF.</li><li><strong>E</strong>: Diktum kelimabelas memuat ketentuan penutup dan masa berlaku keputusan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Diktum keduabelas huruf b angka 3</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Saat melaksanakan tugas kedinasan dari ruang kerja bersama (coworking space), perilaku pegawai wajib mencerminkan...", "options": ["Kebebasan bersikap tanpa memperhatikan lingkungan sekitar", "Pengutamaan kenyamanan pribadi di atas ketertiban umum", "Pemasangan identitas politik untuk menunjukkan keterbukaan", "Pemanfaatan jaringan internet umum tanpa proteksi keamanan", "Kepatuhan pada norma kesopanan dan kesusilaan masyarakat"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kepatuhan Norma Kesopanan Masyarakat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib mematuhi kode etik dan disiplin <strong>termasuk norma kesopanan dan kesusilaan yang berlaku di masyarakat</strong> di manapun bertugas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Pegawai yang bekerja di ruang publik membawa citra institusi kementerian di hadapan masyarakat luas. Ketentuan etika menegaskan kewajiban mematuhi norma kesopanan dan kesusilaan yang berlaku. Dengan demikian, ketertiban umum dan kehormatan korps tetap terjaga.</p><p>Jawaban tepat: <strong>E. Kepatuhan pada norma kesopanan dan kesusilaan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengabaikan etika publik mencoreng kehormatan aparatur sipil negara.</li><li><strong>B</strong>: Mementingkan kenyamanan sendiri berpotensi mengganggu pengguna lain.</li><li><strong>C</strong>: Melanggar asas netralitas politik aparatur sipil negara di ruang publik.</li><li><strong>D</strong>: Menggunakan internet publik tanpa proteksi memicu ancaman siber data.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kesopanan dan kesusilaan yang berlaku di masyarakat</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ketentuan etika kerja fleksibel menegaskan bahwa keleluasaan tempat dan waktu tidak boleh dimaknai sebagai...", "options": ["Peluang meningkatkan inovasi dan pemanfaatan teknologi baru", "Pelonggaran standar moral dan integritas profesional", "Sarana meningkatkan keharmonisan keluarga dan tugas dinas", "Kesempatan menghemat waktu perjalanan menuju kantor fisik", "Upaya membangun kemandirian penyelesaian tugas pekerjaan"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Moralitas Kerja Fleksibel</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Fleksibilitas tempat dan waktu bekerja <strong>tidak boleh dimaknai sebagai pelonggaran standar moral dan profesional</strong> aparatur.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Standar moral dan kualitas output kedinasan tetap berlaku sama persis baik di kantor maupun di rumah. Regulasi menegaskan fleksibilitas bukan alasan untuk mengendurkan disiplin moral dan profesionalisme. Dengan demikian, integritas aparatur tetap tegak di segala kondisi.</p><p>Jawaban tepat: <strong>B. Pelonggaran standar moral dan integritas profesional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemanfaatan inovasi teknologi justru menjadi tujuan positif SKF.</li><li><strong>C</strong>: Keseimbangan hidup-kerja merupakan dampak positif yang diharapkan.</li><li><strong>D</strong>: Penghematan waktu mobilitas adalah manfaat efisiensi kerja jarak jauh.</li><li><strong>E</strong>: Kemandirian tugas adalah kompetensi yang dituntut dalam era fleksibel.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pelonggaran standar moral dan profesional</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Bentuk akuntabilitas etis pegawai saat melaksanakan tugas WFH dalam pemanfaatan jam kerja kedinasan adalah...", "options": ["Menyelesaikan urusan belanja pribadi pada jam pelayanan publik", "Mematikan gawai dinas agar tidak terganggu panggilan rapat", "Fokus pada tugas dinas dan melaporkan hasil kerja secara jujur", "Mengalihkan pekerjaan kantor kepada rekan kerja yang di kantor", "Mengisi presensi hadir lalu meninggalkan tempat kerja tanpa izin"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Disiplin Waktu Kerja SKF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Aspek etika SKF menuntut <strong>disiplin dan kejujuran dalam penggunaan waktu kerja</strong> dengan fokus pada tugas kedinasan dan pelaporan valid.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Bekerja tanpa pengawasan fisik langsung membutuhkan komitmen kejujuran penggunaan jam kerja dinas. Pegawai wajib fokus pada tugas kantor serta melaporkan hasil kerja harian secara riil dan valid. Dengan demikian, amanah jam kerja terpenuhi dengan akuntabel.</p><p>Jawaban tepat: <strong>C. Fokus pada tugas dinas dan melaporkan hasil kerja secara jujur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menyalahgunakan jam dinas untuk urusan pribadi melanggar disiplin.</li><li><strong>B</strong>: Menolak panggilan koordinasi rapat virtual melanggar kewajiban dinas.</li><li><strong>D</strong>: Membebankan pekerjaan sendiri kepada rekan kerja mencederai sinergi.</li><li><strong>E</strong>: Presensi fiktif merupakan pelanggaran disiplin berat aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Disiplin dan Kejujuran dalam Penggunaan Waktu Kerja</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewajiban etis pegawai dalam aspek tanggung jawab dan integritas saat bekerja jarak jauh mencakup...", "options": ["Penyebarluasan draf kebijakan kementerian ke forum publik daring", "Pemberian akses akun sistem dinas kepada keluarga di rumah", "Penggunaan perangkat kerja kantor untuk bisnis sampingan pribadi", "Menjaga keamanan pekerjaan dan kerahasiaan data negara", "Penyimpanan dokumen rahasia pada layanan komputasi awan bebas"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab dan Integritas SKF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam aspek tanggung jawab dan integritas, pegawai wajib <strong>menjaga keamanan dan keselamatan pekerjaan, termasuk kerahasiaan data</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Bekerja di luar kantor memperbesar risiko kebocoran data rahasia perbendaharaan dan perpajakan negara. Kode etik mewajibkan pegawai menjaga keamanan pekerjaan dan mengamankan data kedinasan. Dengan demikian, kedaulatan data pemerintah tetap terlindungi.</p><p>Jawaban tepat: <strong>D. Menjaga keamanan pekerjaan dan kerahasiaan data negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Membocorkan draf rahasia negara merupakan pelanggaran pidana jabatan.</li><li><strong>B</strong>: Berbagi akun kepada pihak luar melanggar protokol keamanan informasi.</li><li><strong>C</strong>: Menyalahgunakan aset dinas untuk keuntungan komersial pribadi.</li><li><strong>E</strong>: Menyimpan data dinas pada komputasi awan bebas memicu pencurian data.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjaga keamanan dan keselamatan pekerjaan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perwujudan etika sosial dan kesopanan digital pegawai saat berinteraksi dalam rapat daring resmi ditunjukkan dengan...", "options": ["Menyalakan musik latar belakang yang keras selama rapat", "Menyela pembicaraan pimpinan rapat tanpa mengangkat tangan", "Mengenakan pakaian rumah santai saat kamera aktif menyala", "Menutup pandangan mata dari arah kamera selama rapat", "Menjaga penampilan rapi dan menghormati tata cara rapat resmi"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesopanan Digital dalam Forum Virtual</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Etika sosial diwujudkan melalui <strong>kesopanan dalam forum virtual, menjaga penampilan, serta menghormati tata cara kegiatan resmi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Etika pergaulan di ruang rapat virtual menuntut kesantunan dan kerapian busana yang setara dengan rapat fisik. Sikap santun ditunjukkan dengan busana rapi, tertib berbicara, dan mematuhi tata tertib pertemuan. Dengan demikian, suasana rapat berlangsung khidmat dan produktif.</p><p>Jawaban tepat: <strong>E. Menjaga penampilan rapi dan menghormati tata cara rapat resmi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Suara bising mengganggu ketertiban jalannya koordinasi rapat dinas.</li><li><strong>B</strong>: Menyela tanpa izin pemandu acara melanggar kesopanan komunikasi rapat.</li><li><strong>C</strong>: Pakaian tidak pantas merendahkan martabat institusi dalam forum resmi.</li><li><strong>D</strong>: Mengabaikan komunikasi mencerminkan sikap tidak responsif dan pasif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Etika Sosial dan Kesopanan Digital</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Bentuk akuntabilitas etis aparatur dalam mempertanggungjawabkan kinerja harian saat bekerja fleksibel adalah...", "options": ["Pelaporan hasil kerja yang jujur dan valid pada sistem", "Pencatatan target capaian fiktif agar dinilai berkinerja tinggi", "Pengiriman bukti pekerjaan milik rekan kerja sebagai tugas sendiri", "Penundaan pengunggahan laporan hingga akhir triwulan berjalan", "Penyusunan laporan ringkas tanpa menyertakan dokumen bukti kerja"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akuntabilitas Pelaporan Hasil Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pelaporan hasil kerja yang jujur dan valid merupakan bentuk akuntabilitas etis dalam bekerja</strong> pada pelaksanaan sistem kerja fleksibel.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Sistem kerja modern menuntut kejujuran input laporan hasil kerja sebagai basis evaluasi kinerja atasan. Pelaporan yang jujur dan valid membuktikan dedikasi profesional pegawai selama bekerja jarak jauh. Dengan demikian, penilaian kinerja mencerminkan prestasi riil pegawai.</p><p>Jawaban tepat: <strong>A. Pelaporan hasil kerja yang jujur dan valid pada sistem</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Laporan fiktif merupakan penipuan data kinerja yang melanggar disiplin.</li><li><strong>C</strong>: Mengklaim hasil kerja orang lain merupakan pelanggaran integritas hak cipta.</li><li><strong>D</strong>: Penundaan laporan menghambat monitoring pencapaian kinerja organisasi.</li><li><strong>E</strong>: Laporan tanpa bukti eviden menyulitkan verifikasi akuntabilitas pekerjaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 24) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pelaporan hasil kerja yang jujur dan valid</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Prinsip etis yang wajib dipedomani pegawai dalam memanfaatkan sarana teknologi informasi dinas adalah...", "options": ["Boleh digunakan anggota keluarga untuk keperluan bermain game", "Dapat dipinjamkan kepada pihak ketiga untuk kegiatan komersial", "Digunakan bertanggung jawab dan tidak untuk kepentingan pribadi", "Bebas dimodifikasi perangkat lunaknya tanpa izin unit TIK", "Ditinggalkan di ruang publik tanpa pengawasan demi kemudahan akses"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etika Pemanfaatan Sarana TIK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sarana TIK dinas harus <strong>digunakan secara bertanggung jawab, tidak disalahgunakan untuk kepentingan pribadi, dan memenuhi standar keamanan siber</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Laptop dan aset TIK instansi dibiayai anggaran negara khusus untuk menunjang tugas kedinasan. Penggunaan etis mewajibkan pemanfaatan sarana dinas secara bertanggung jawab dan bersih dari urusan privat. Dengan demikian, aset negara terpelihara optimal.</p><p>Jawaban tepat: <strong>C. Digunakan bertanggung jawab dan tidak untuk kepentingan pribadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemanfaatan aset dinas untuk hiburan keluarga menyalahi peruntukan.</li><li><strong>B</strong>: Meminjamkan sarana kantor kepada pihak luar melanggar pengamanan BMN.</li><li><strong>D</strong>: Modifikasi tanpa izin berisiko merusak sistem keamanan informasi kantor.</li><li><strong>E</strong>: Meninggalkan gawai tanpa pengawasan memicu pencurian data rahasia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penggunaan Sarana dan Prasarana Secara Etis</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sikap pegawai dalam memenuhi kewajiban pengawasan dan evaluasi kinerja saat bekerja jarak jauh adalah...", "options": ["Menunda tanggapan atas panggilan koordinasi atasan terkait progres", "Mematikan status keaktifan pada aplikasi kolaborasi kementerian", "Menyampaikan laporan kerja sewaktu ada inspeksi unit pemeriksa", "Terbuka terhadap pemantauan dan evaluasi kinerja oleh atasan", "Meminta rekan kerja mewakili saat evaluasi kinerja harian berlangsung"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kepatuhan Pengawasan Kinerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai memiliki kewajiban melaporkan kehadiran dan hasil kerja serta <strong>terbuka terhadap pemantauan dan evaluasi kinerja oleh atasan langsung</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Keterbukaan evaluasi mencerminkan kesiapan pegawai mempertanggungjawabkan hasil kerja secara transparan. Pegawai wajib bersikap kooperatif saat atasan atau unit pengawas memantau progres tugas dinas. Dengan demikian, kualitas hasil kerja tetap terkendali baik.</p><p>Jawaban tepat: <strong>D. Terbuka terhadap pemantauan dan evaluasi kinerja oleh atasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menghindar dari komunikasi atasan melanggar kewajiban responsivitas.</li><li><strong>B</strong>: Mematikan status daring menyulitkan koordinasi tugas tim kerja dinas.</li><li><strong>C</strong>: Pelaporan kerja wajib diinput rutin tanpa menunggu teguran pemeriksa.</li><li><strong>E</strong>: Tanggung jawab kinerja melekat personal dan tidak boleh diwakilkan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>terbuka terhadap pemantauan dan evaluasi kinerja</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Di lingkungan kerja digital, integritas profesional aparatur dalam berkomunikasi ditunjukkan melalui...", "options": ["Penggunaan singkatan bahasa gaul dalam surat dinas elektronik", "Pemberian respon pesan dinas setelah melewati beberapa hari kerja", "Pengabaian pesan koordinasi dari staf unit kerja yang berbeda", "Penyampaian informasi penting melalui panggilan telepon lisan", "Komunikasi yang efektif, jelas, sopan, dan senantiasa responsif"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etika Komunikasi Kerja Digital</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Di lingkungan kerja digital, <strong>komunikasi yang efektif, jelas, sopan, dan responsif menjadi bagian dari integritas profesional</strong> aparatur.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Komunikasi virtual yang cepat dan santun mencegah kesalahpahaman koordinasi tugas antar-lokasi. Standar etika komunikasi digital menuntut penyampaian pesan yang jelas, sopan, dan cepat ditanggapi. Dengan demikian, kelancaran tugas tim lintas tempat terjamin.</p><p>Jawaban tepat: <strong>E. Komunikasi yang efektif, jelas, sopan, dan senantiasa responsif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tata naskah dinas resmi wajib mematuhi kaidah bahasa baku kedinasan.</li><li><strong>B</strong>: Keterlambatan merespon koordinasi mendesak menghambat kinerja kantor.</li><li><strong>C</strong>: Mengabaikan pesan rekan unit lain mencerminkan ego sektoral negatif.</li><li><strong>D</strong>: Informasi kedinasan resmi memerlukan jejak rekam tertulis yang valid.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>komunikasi yang efektif, jelas, sopan, dan responsif</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewajiban pertama pegawai dalam pelaksanaan Sistem Kerja Fleksibel menurut KMK 416 Tahun 2023 adalah...", "options": ["Mengisi daftar hadir melalui aplikasi presensi otomasi perkantoran", "Menghubungi pimpinan unit melalui sambungan panggilan video", "Membuat ringkasan berita acara kehadiran manual di atas kertas", "Mengirimkan foto selfie di depan rumah ke grup percakapan dinas", "Menyerahkan kartu identitas pegawai kepada petugas keamanan kantor"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Presensi Digital</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewajiban pertama pegawai adalah <strong>mengisi daftar hadir melalui sistem aplikasi presensi pada aplikasi otomasi perkantoran (office automation)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Presensi digital merupakan bukti keabsahan kedisiplinan jam kerja harian pegawai selama bertugas. Regulasi mewajibkan pengisian presensi tepat waktu melalui aplikasi resmi kementerian. Dengan demikian, hak tunjangan kinerja dan kepatuhan jam kerja terekam akurat.</p><p>Jawaban tepat: <strong>A. Mengisi daftar hadir melalui aplikasi presensi otomasi perkantoran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Panggilan video bukan mekanisme resmi pencatatan kehadiran kedinasan.</li><li><strong>C</strong>: Pencatatan manual bertentangan dengan sistem otomasi digital kementerian.</li><li><strong>D</strong>: Foto selfie informal bukan standar instrumen presensi kedinasan sah.</li><li><strong>E</strong>: Penyerahan kartu tanda pengenal menyalahi tertib pengamanan identitas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengisi daftar hadir melalui sistem aplikasi presensi</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Sebelum memulai jadwal tugas bekerja dari rumah (WFH), tindakan persiapan yang wajib dipastikan oleh pegawai adalah...", "options": ["Menyiapkan hidangan konsumsi untuk rekan kerja yang berkunjung", "Memastikan ketersediaan sarana pendukung kerja dan koneksi internet", "Mengajukan permohonan penggantian biaya listrik rumah ke bendahara", "Meminta izin kepada ketua rukun tetangga setempat secara formal", "Menyewa ruang pertemuan komersial atas beban anggaran kantor"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesiapan Sarana Mandiri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai berkewajiban <strong>memastikan ketersediaan sarana dan prasarana pendukung kerja</strong> sebelum melaksanakan penugasan SKF.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Kelancaran tugas jarak jauh menuntut kesiapan perangkat gawai dan jaringan internet yang stabil. Pegawai wajib memastikan sarana kerja berfungsi baik agar pelayanan tidak terhenti karena kendala teknis. Dengan demikian, produktivitas kerja jarak jauh tetap terjaga.</p><p>Jawaban tepat: <strong>B. Memastikan ketersediaan sarana pendukung kerja dan koneksi internet</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Urusan konsumsi privat tidak berkaitan dengan kesiapan teknis dinas.</li><li><strong>C</strong>: Biaya listrik rumah tinggal tidak ditanggung oleh bendahara pengeluaran.</li><li><strong>D</strong>: Pemberitahuan warga bukan prosedur administratif kedinasan instansi.</li><li><strong>E</strong>: Sewa ruangan komersial tanpa alokasi DIPA melanggar aturan anggaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memastikan ketersediaan sarana dan prasarana pendukung kerja</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan butir 5 kewajiban KMK 416/2023, sikap pegawai selama jam kerja fleksibel berlangsung adalah...", "options": ["Bebas menonaktifkan gawai dinas di luar agenda pertemuan resmi", "Merespons pesan tertulis dari atasan langsung seorang diri", "Menyerahkan nomor ponsel pribadi kepada masyarakat pemohon layanan", "Responsif, dapat dihubungi, dan mengupayakan online pada aplikasi", "Memasang balasan pesan otomatis bahwa pegawai sedang tidak di kantor"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Responsivitas dan Status Online</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib <strong>responsif dan dapat dihubungi serta mengupayakan untuk online dalam peralatan kolaborasi Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Koordinasi tanggap darurat dan dinamika tugas mendesak menuntut ketersediaan komunikasi pegawai setiap saat. Kewajiban SKF menegaskan pegawai harus responsif dan menjaga status aktif pada platform kerja resmi. Dengan demikian, hambatan komunikasi tim dapat ditiadakan.</p><p>Jawaban tepat: <strong>D. Responsif, dapat dihubungi, dan mengupayakan online pada aplikasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menonaktifkan gawai dinas pada jam kerja melanggar kewajiban tugas.</li><li><strong>B</strong>: Koordinasi kerja melibatkan rekan tim dan pemangku kepentingan unit.</li><li><strong>C</strong>: Pemberian kontak pribadi berisiko keamanan dan privasi aparatur.</li><li><strong>E</strong>: Pesan otomatis tidak di kantor mengesankan pengabaian tugas dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>responsif dan dapat dihubungi</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Standar berpakaian yang wajib dikenakan pegawai saat melaksanakan tugas kedinasan secara fleksibel (SKF) adalah...", "options": ["Pakaian santai rumah tangga demi kenyamanan suasana kerja mandiri", "Busana pesta resmi lengkap dengan atribut organisasi kemasyarakatan", "Pakaian olahraga santai sepanjang tidak ada agenda pertemuan video", "Busana bebas tanpa batas asalkan rapi menurut penilaian pribadi", "Pakaian kerja sesuai ketentuan resmi seragam Kementerian Keuangan"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketentuan Pakaian Kerja SKF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai berkewajiban <strong>menggunakan pakaian kerja sesuai ketentuan mengenai pakaian kerja pegawai di lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Kedisiplinan berseragam mencerminkan profesionalitas dan kehormatan korps aparatur negara di manapun bertugas. Bekerja fleksibel tetap mewajibkan pakaian dinas yang diatur resmi oleh regulasi menteri. Dengan demikian, identitas dan wibawa aparatur tetap terpancar teguh.</p><p>Jawaban tepat: <strong>E. Pakaian kerja sesuai ketentuan resmi seragam Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pakaian rumah tangga merendahkan standar profesionalisme kedinasan.</li><li><strong>B</strong>: Atribut organisasi non-pemerintah dilarang dikenakan saat dinas aktif.</li><li><strong>C</strong>: Pakaian olahraga hanya diperkenankan pada agenda senam kesegaran dinas.</li><li><strong>D</strong>: Standar kerapian dinas mengacu pada regulasi pakaian kerja kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menggunakan pakaian kerja sesuai ketentuan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Ketika harus mengakses dokumen anggaran negara di tempat umum, tindakan pengamanan jaringan internet yang etis adalah...", "options": ["Memilih koneksi aman dan memakai jaringan resmi instansi", "Menghubungkan laptop ke fasilitas jaringan nirkabel gratis tanpa sandi", "Mematikan fitur firewall sistem operasi agar unduhan data berjalan cepat", "Meminjam sambungan hotspot milik pengunjung lain yang tidak dikenal", "Membagikan kata sandi VPN kantor kepada staf tempat umum setempat"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keamanan Jaringan Internet</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam menjaga keamanan informasi, pegawai wajib <strong>menghindari penggunaan internet yang tidak terjamin keamanannya</strong> (seperti wifi publik gratis tanpa proteksi).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Jaringan publik tanpa enkripsi rentan disusupi peretas yang dapat mencegat data rahasia kementerian. Protokol keamanan siber mewajibkan penghindaran wifi publik tidak aman dan mengutamakan jaringan privat dinas. Dengan demikian, integritas data keuangan negara terlindungi.</p><p>Jawaban tepat: <strong>A. Memilih koneksi aman dan memakai jaringan resmi instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Wifi publik gratis tanpa proteksi adalah sarang penyadapan data siber.</li><li><strong>C</strong>: Mematikan firewall membuka celah masuknya malware berbahaya ke laptop.</li><li><strong>D</strong>: Menghubungkan ke gawai orang asing berisiko tinggi penyalinan dokumen.</li><li><strong>E</strong>: Membocorkan kredensial VPN negara merupakan pelanggaran keamanan berat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menghindari penggunaan internet yang tidak terjamin keamanannya</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewajiban aparatur dalam menjaga perangkat laptop dinas dari ancaman perangkat perusak (malware) diwujudkan melalui...", "options": ["Pengunduhan seluruh aplikasi gratis yang tersedia di mesin pencari", "Sikap selektif dalam mengakses atau mengunduh dari situs internet", "Penyimpanan data kantor pada flashdisk temuan tanpa pemindaian virus", "Penonaktifan fitur pembaruan otomatis peramban situs komputer kantor", "Pemasangan aplikasi bajakan untuk menghemat anggaran operasional"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketelitian Akses Situs Internet</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib <strong>selektif dalam mengakses/mengunduh informasi pada situs internet yang terpercaya (internet sehat dan aman)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Situs yang tidak terpercaya sering kali menyisipkan virus, trojan, atau spyware pencuri berkas dinas. Sikap selektif saat berselancar di dunia maya mencegah infeksi perangkat kerja oleh malware berbahaya. Dengan demikian, kelaikan perangkat gawai dinas tetap terjaga prima.</p><p>Jawaban tepat: <strong>B. Sikap selektif dalam mengakses atau mengunduh dari situs internet</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengunduh sembarang aplikasi memicu kerusakan sistem operasi dinas.</li><li><strong>C</strong>: Media eksternal tanpa pemindaian virus berisiko tinggi menularkan malware.</li><li><strong>D</strong>: Menonaktifkan pembaruan peramban membiarkan celah kerentanan keamanan.</li><li><strong>E</strong>: Penggunaan perangkat lunak bajakan melanggar hak cipta dan hukum siber.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>selektif dalam mengakses/mengunduh informasi</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kanal resmi yang wajib digunakan pegawai dalam melaporkan hasil pelaksanaan pekerjaan kepada atasan langsung adalah...", "options": ["Buku catatan harian pribadi yang disimpan di meja kerja rumah", "Surat elektronik personal yang dikirimkan ke alamat email pribadi atasan", "Aplikasi pelaporan hasil kerja pada otomasi perkantoran instansi", "Pesan singkat obrolan informal pada aplikasi percakapan ponsel", "Kuitansi lembar kerja mingguan yang ditandatangani rekan sejawat"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mekanisme Pelaporan Hasil Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai berkewajiban <strong>melaporkan hasil pelaksanaan pekerjaan kepada atasan langsung melalui aplikasi pelaporan hasil kerja pada aplikasi otomasi perkantoran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Pelaporan terintegrasi pada sistem otomasi menjamin data kinerja tercatat resmi dan dapat diuji akuntabilitasnya. Regulasi mewajibkan pelaporan melalui modul laporan kinerja resmi kementerian. Dengan demikian, bukti capaian kerja tersimpan secara sistematis dan aman.</p><p>Jawaban tepat: <strong>C. Aplikasi pelaporan hasil kerja pada otomasi perkantoran instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Catatan buku privat tidak memiliki keabsahan dalam evaluasi kinerja dinas.</li><li><strong>B</strong>: Surel pribadi rentan kebocoran data dan menyalahi prosedur naskah dinas.</li><li><strong>D</strong>: Pesan chat informal bukan saluran resmi perekaman akuntabilitas output.</li><li><strong>E</strong>: Tanda tangan rekan sejawat tidak memiliki legalitas penilaian atasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 25) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pelaporan hasil kerja pada aplikasi otomasi perkantoran</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Langkah preventif yang wajib dilaksanakan pegawai untuk mencegah kehilangan data kedinasan akibat kerusakan perangkat adalah...", "options": ["Mencetak seluruh berkas sistem ke dalam dokumen kertas tebal", "Menyimpan seluruh data pada satu partisi cakram keras laptop", "Menghapus berkas lama setiap akhir bulan agar ruang cakram lega", "Mengirimkan data dinas ke akun media sosial publik milik pribadi", "Melakukan pencadangan data penting secara berkala pada media sah"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prosedur Pencadangan Data Dinas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai berkewajiban <strong>melakukan pencadangan data penting secara berkala dan memastikan sistem operasi, antivirus, dan aplikasi pendukung telah mutakhir</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Pencadangan berkala (backup) menjamin kelangsungan proses bisnis kedinasan saat terjadi bencana kerusakan hardware. Protokol TIK mewajibkan backup rutin pada media penyimpanan cloud resmi kementerian. Dengan demikian, risiko kehilangan data strategis negara teratasi.</p><p>Jawaban tepat: <strong>E. Melakukan pencadangan data penting secara berkala pada media sah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mencetak seluruh data bertentangan dengan kebijakan paperless birokrasi.</li><li><strong>B</strong>: Menyimpan pada satu partisi berisiko hilang total saat perangkat rusak.</li><li><strong>C</strong>: Menghapus data sembarangan melanggar jadwal retensi arsip negara.</li><li><strong>D</strong>: Mengunggah data dinas ke media sosial merupakan pelanggaran rahasia dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pencadangan data penting secara berkala</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ketentuan pengamanan gawai dinas mewajibkan aparatur untuk memastikan bahwa perangkat kerja...", "options": ["Digunakan secara pribadi atau pengguna yang memiliki wewenang", "Boleh dipinjamkan kepada tetangga untuk mencetak dokumen warga", "Dibiarkan menyala tanpa kunci pengaman layar saat ditinggal pergi", "Diserahkan kepada pihak teknisi tidak resmi di luar instansi", "Dapat diakses bersama oleh seluruh anggota keluarga di rumah tinggal"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penggunaan Perangkat oleh Pihak Berwenang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib <strong>memastikan perangkat digunakan secara pribadi atau pengguna yang memiliki kewenangan</strong> yang sah demi perlindungan informasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Pembatasan akses gawai mencegah penyalahgunaan akun dinas dan pencurian informasi rahasia perbendaharaan. Perangkat kerja kedinasan hanya boleh dioperasikan oleh pegawai yang berwenang. Dengan demikian, akuntabilitas digital aparatur tetap terjaga kokoh.</p><p>Jawaban tepat: <strong>A. Digunakan secara pribadi atau pengguna yang memiliki wewenang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Meminjamkan aset TIK dinas melanggar ketentuan tata kelola BMN.</li><li><strong>C</strong>: Layar tanpa kunci pengaman membuka celah pencurian data oleh pihak lain.</li><li><strong>D</strong>: Perbaikan gawai dinas wajib melalui unit layanan TIK resmi kementerian.</li><li><strong>E</strong>: Akses bersama oleh keluarga berisiko menghapus berkas penting dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memastikan perangkat digunakan secara pribadi</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Saat menerima surel mencurigakan berisi tautan pembaruan kata sandi dari pengirim tidak dikenal, respon etis pegawai adalah...", "options": ["Segera mengklik tautan tersebut agar akun surat dinas tidak terblokir", "Waspada terhadap phising dan segera melapor ke unit pengelola siber", "Meneruskan surel tersebut kepada seluruh rekan kerja di kantor", "Memasukkan nama pengguna dan kata sandi untuk menguji keaslian situs", "Membuka tautan tanpa verifikasi pengirim dan mematikan antivirus"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewaspadaan Serangan Phising</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib senantiasa <strong>waspada atas informasi yang berpotensi phising maupun serangan keamanan siber lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Phising adalah rekayasa sosial yang dirancang untuk merampas kredensial akses sistem keuangan negara. Sikap waspada ditunjukkan dengan tidak mengklik tautan asing dan segera melaporkan ke unit keamanan siber. Dengan demikian, intrusi jaringan kementerian dapat digagalkan.</p><p>Jawaban tepat: <strong>B. Waspada terhadap phising dan segera melapor ke unit pengelola siber</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengklik tautan asing berakibat langsung pada peretasan akun kedinasan.</li><li><strong>C</strong>: Menyebarkan surel phising memperbesar risiko rekan lain menjadi korban.</li><li><strong>D</strong>: Memasukkan kredensial menyerahkan kata sandi langsung ke tangan peretas.</li><li><strong>E</strong>: Menghapus antivirus melumpuhkan pertahanan perlindungan laptop dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>waspada atas informasi yang berpotensi phising</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewajiban ketertiban administrasi kepegawaian bagi seluruh aparatur di lingkungan kementerian diwujudkan melalui...", "options": ["Pencetakan berkas biodata keluarga ke dalam buku arsip manual", "Penyerahan berkas fotokopi ijazah secara langsung setiap pekan", "Melengkapi dan memperbarui data HRIS Kemenkeu secara berkala", "Penyimpanan data riwayat hidup pada media penyimpanan pribadi", "Pemberian kuasa pembaruan profil data diri kepada staf honorer"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Pembaruan Data HRIS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai berkewajiban <strong>harus melengkapi dan memperbarui data HRIS Kemenkeu secara berkala</strong> agar data kepegawaian valid.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Validitas data kepegawaian menjadi dasar penentuan hak remunerasi, mutasi, dan promosi berbasis meritokrasi. Pembaruan data HRIS secara berkala memastikan profil kepegawaian selalu akurat dan mutakhir. Dengan demikian, administrasi kepegawaian kementerian berjalan rapi.</p><p>Jawaban tepat: <strong>C. Melengkapi dan memperbarui data HRIS Kemenkeu secara berkala</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Arsip manual telah digantikan oleh basis data digital terintegrasi HRIS.</li><li><strong>B</strong>: Pembaruan ijazah dilakukan melalui mekanisme daring verifikasi kepegawaian.</li><li><strong>D</strong>: Penyimpanan pribadi tidak memperbarui basis data resmi kementerian.</li><li><strong>E</strong>: Data privasi kepegawaian wajib dikelola langsung oleh pegawai bersangkutan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memperbarui data HRIS Kemenkeu secara berkala</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pedoman sistem kerja dalam masa transisi tatanan normal baru di Kementerian Keuangan diatur dalam...", "options": ["Surat Edaran Nomor 44/MK.01/2017", "Peraturan Pemerintah Nomor 42 Tahun 2004", "Keputusan Menteri Keuangan Nomor 416/2023", "Surat Edaran Menteri Keuangan Nomor SE-22/MK.1/2020", "Peraturan Menteri Keuangan Nomor 190/2018"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Regulasi Transisi Kerja Normal Baru</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelaksanaan sistem kerja pada masa transisi normal baru mengacu pada <strong>Surat Edaran Menteri Keuangan Nomor SE-22/MK.1/2020</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Regulasi ini mengatur perpaduan kerja di kantor dan kerja fleksibel dengan standar protokol kesehatan dan keselamatan. SE Nomor 22/MK.1/2020 menjadi payung hukum operasional masa transisi kerja aparatur. Dengan demikian, produktivitas dan keselamatan pegawai terlindungi.</p><p>Jawaban tepat: <strong>D. Surat Edaran Menteri Keuangan Nomor SE-22/MK.1/2020</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan SE tentang penerapan nilai dasar ASN di lingkungan Kemenkeu.</li><li><strong>B</strong>: Merupakan PP tentang pembinaan jiwa korps dan kode etik PNS nasional.</li><li><strong>C</strong>: Merupakan KMK tentang penerapan sistem kerja fleksibel di Kemenkeu.</li><li><strong>E</strong>: Merupakan PMK tentang kode etik dan kode perilaku PNS Kemenkeu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Surat Edaran (SE) Menteri Keuangan Nomor SE22/MK.1/2020</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan butir 10 kewajiban pelaksanaan SKF, seluruh pegawai Kementerian Keuangan diwajibkan untuk...", "options": ["Melaksanakan pedoman acara kedinasan yang diadakan virtual", "Merekam seluruh jalannya rapat virtual untuk diunggah ke YouTube", "Mengikuti rapat virtual tanpa kewajiban mengenakan pakaian dinas", "Mewakilkan kehadiran rapat dinas virtual kepada asisten pribadi", "Membatalkan seluruh agenda rapat yang tidak diselenggarakan fisik"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Mematuhi Pedoman Acara Virtual</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai berkewajiban <strong>melaksanakan pedoman acara dan kegiatan kedinasan secara virtual</strong> sesuai aturan resmi yang berlaku.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Pertemuan kedinasan virtual tetap memiliki kekuatan hukum dan tata tertib protokoler yang mengikat. Kepatuhan pada pedoman acara virtual menjaga keseragaman dan ketertiban seluruh kegiatan kedinasan daring. Dengan demikian, martabat acara resmi pemerintah tetap terjaga.</p><p>Jawaban tepat: <strong>A. Melaksanakan pedoman acara kedinasan yang diadakan virtual</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mengunggah rekaman rapat internal ke platform publik melanggar kerahasiaan.</li><li><strong>C</strong>: Peserta rapat virtual wajib mematuhi ketentuan pakaian dinas resmi.</li><li><strong>D</strong>: Kehadiran rapat adalah kewajiban kedinasan yang tidak boleh dialihkan.</li><li><strong>E</strong>: Rapat virtual diakui sah dan resmi sebagai media koordinasi birokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melaksanakan pedoman acara dan kegiatan kedinasan secara virtual</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tindakan teknis yang wajib dilakukan pegawai agar sistem komputer kerja terhindar dari celah keamanan adalah...", "options": ["Menghapus perangkat lunak antivirus agar memori kerja tidak penuh", "Memastikan sistem operasi dan antivirus telah diperbarui mutakhir", "Mengunduh berkas pembaruan sistem dari situs bajakan pihak luar", "Mematikan sistem operasi secara paksa saat pembaruan berlangsung", "Menggunakan sistem operasi lawas yang sudah tidak didukung pabrikan"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembaruan Perangkat Lunak Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib <strong>memastikan sistem operasi, antivirus, dan aplikasi pendukung telah mutakhir</strong> dengan pembaruan resmi berkala.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Pembaruan berkala menambal celah keamanan sistem dari serangan virus dan eksploitasi siber global. Memastikan sistem operasi dan antivirus selalu mutakhir adalah kewajiban keamanan informasi pegawai. Dengan demikian, laptop kerja terlindungi dari infeksi program jahat.</p><p>Jawaban tepat: <strong>B. Memastikan sistem operasi dan antivirus telah diperbarui mutakhir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menghilangkan antivirus meruntuhkan sistem pertahanan dasar gawai.</li><li><strong>C</strong>: Sumber pembaruan bajakan sering disusupi trojan berbahaya peretas.</li><li><strong>D</strong>: Mematikan paksa merusak berkas sistem operasi komputer kedinasan.</li><li><strong>E</strong>: Sistem operasi usang sangat rentan disusupi peretas tanpa pelindung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>operasi, antivirus, dan aplikasi pendukung telah mutakhir</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ketentuan mengenai tata cara pelantikan pegawai melalui media elektronik secara nasional diatur oleh...", "options": ["Surat Edaran Menteri PANRB Nomor 1 Tahun 2021", "Peraturan Pemerintah Nomor 94 Tahun 2021", "SE Kepala BKN Nomor 10/SE/IV/2020", "Keputusan Menteri Keuangan Nomor 416 Tahun 2023", "Undang-Undang Nomor 20 Tahun 2023"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelantikan Melalui Media Elektronik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelantikan dengan media elektronik berpedoman pada <strong>Surat Edaran Kepala Badan Kepegawaian Negara Nomor 10/SE/IV/2020</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. BKN sebagai instansi pembina manajemen kepegawaian menerbitkan pedoman pelantikan daring resmi nasional. SE Kepala BKN Nomor 10/SE/IV/2020 menjadi rujukan hukum acara pelantikan dan sumpah janji jabatan virtual. Dengan demikian, keabsahan hukum pelantikan daring diakui sah.</p><p>Jawaban tepat: <strong>C. SE Kepala BKN Nomor 10/SE/IV/2020</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan instrumen spesifik tata cara pelantikan jabatan daring.</li><li><strong>B</strong>: Merupakan regulasi tentang disiplin aparatur sipil negara.</li><li><strong>D</strong>: Merupakan KMK pelaksanaan sistem kerja fleksibel di kementerian.</li><li><strong>E</strong>: Merupakan undang-undang payung pokok aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengenai sistem kerja dalam masa transisi normal baru dan SE Kepala</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Konsep internet sehat dan aman yang diterapkan di lingkungan kerja kementerian menuntut pegawai untuk...", "options": ["Menggunakan jaringan dinas untuk mengunduh film hiburan komersial", "Mengakses situs judi daring dan platform perjudian internasional", "Membuka media sosial sepanjang hari kerja tanpa batasan tugas", "Mengakses konten positif terpercaya demi menunjang tugas negara", "Menyebarluaskan tautan unduhan program bajakan ke rekan sejawat"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Internet Sehat dan Aman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan <strong>internet sehat dan aman</strong> mewajibkan pegawai hanya mengakses situs terpercaya yang relevan dan bernilai positif bagi tugas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Fasilitas kuota dan jaringan dinas disediakan semata-mata untuk mendukung kelancaran tugas pemerintahan. Internet sehat dan aman menuntut pemanfaatan web positif serta menghindari situs terlarang dan merusak. Dengan demikian, bandwidth dan integritas kantor terpelihara.</p><p>Jawaban tepat: <strong>D. Mengakses konten positif terpercaya demi menunjang tugas negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengunduh film komersial memboroskan kapasitas jaringan kantor.</li><li><strong>B</strong>: Perjudian daring merupakan tindak pidana dan pelanggaran disiplin berat.</li><li><strong>C</strong>: Akses media sosial berlebihan menurunkan produktivitas jam kerja.</li><li><strong>E</strong>: Membagikan tautan bajakan melanggar hukum hak atas kekayaan intelektual.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>internet sehat dan aman</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Setiap pegawai yang menjalankan tugas kedinasan secara fleksibel wajib mematuhi panduan mengenai...", "options": ["Strategi promosi pemasaran produk komersial pihak ketiga", "Pengelolaan investasi pasar modal spekulatif berisiko tinggi", "Penyelenggaraan acara hiburan musik akhir tahun satker", "Tata cara pengajuan gugatan kepegawaian ke meja hijau", "Pengelolaan keamanan informasi di lingkungan kementerian"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kepatuhan Pengelolaan Keamanan Informasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib memperhatikan panduan sesuai dengan ketentuan mengenai <strong>pengelolaan keamanan informasi di lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Keamanan data negara adalah tanggung jawab bersama setiap individu aparatur yang memegang akses sistem. Kepatuhan pada standar pengelolaan keamanan informasi menjaga kedaulatan data dari serangan spionase dan kebocoran. Dengan demikian, sistem keuangan negara aman terlindungi.</p><p>Jawaban tepat: <strong>E. Pengelolaan keamanan informasi di lingkungan kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemasaran komersial bukan bidang tugas aparatur perbendaharaan negara.</li><li><strong>B</strong>: Investasi spekulatif pribadi tidak berkaitan dengan tata kelola dinas.</li><li><strong>C</strong>: Kegiatan hiburan internal bukan standar wajib pengamanan informasi.</li><li><strong>D</strong>: Prosedur sengketa kepegawaian merupakan ranah hukum tata usaha negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 26) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengelolaan keamanan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pedoman etika dalam acara resmi kedinasan yang diselenggarakan secara virtual diatur melalui...", "options": ["Surat Edaran Menteri Pendayagunaan Aparatur Negara Nomor 15/2020", "Surat Edaran Menteri Keuangan nomor: SE-42/MK.01/2020", "Peraturan Pemerintah Republik Indonesia Nomor 94 Tahun 2021", "Keputusan Kepala Badan Kepegawaian Negara Nomor 21/2020", "Instruksi Presiden Republik Indonesia Nomor 2 Tahun 2020"], "answer": 1, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Regulasi Acara Resmi Virtual Kedinasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pedoman etika acara resmi kedinasan virtual ditetapkan melalui <strong>Surat Edaran Menteri Keuangan nomor: SE-42/MK.01/2020</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena SE-42/MK.01/2020 merupakan payung hukum khusus di lingkungan kementerian yang mengatur standar etika keprotokolan virtual. Edaran ini memandu seluruh pejabat dan pegawai agar tetap memelihara kehormatan dinas, kekhidmatan acara, dan ketertiban tata cara kedinasan meski dilaksanakan melalui media elektronik. Regulasi ini menjadi acuan seragam bagi seluruh unit kerja. Oleh sebab itu, SE-42/MK.01/2020 adalah dasar aturan yang tepat.</p><p>Jawaban tepat: <strong>B. Surat Edaran Menteri Keuangan nomor: SE-42/MK.01/2020</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena SE PAN-RB Nomor 15/2020 mengatur pedoman umum penyesuaian sistem kerja ASN di masa pandemi.</li><li><strong>C</strong>: Kurang tepat karena PP 94/2021 mengatur substansi disiplin pegawai negeri sipil secara umum, bukan teknis acara virtual.</li><li><strong>D</strong>: Kurang tepat karena Keputusan BKN 21/2020 memuat petunjuk pelaksanaan pembinaan kepegawaian, bukan etika protokol kementerian.</li><li><strong>E</strong>: Kurang tepat karena Inpres 2/2020 berkaitan dengan aksi nasional pencegahan tindak pidana korupsi aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Surat Edaran Menteri Keuangan nomor: SE-42/MK.01/2020</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sesuai SE-42/MK.01/2020, acara resmi kedinasan di lingkungan kementerian terbagi menjadi dua bentuk, yaitu...", "options": ["Pertemuan formal pleno dan rapat koordinasi komisi kerja", "Seminar ilmiah kedinasan dan lokakarya pelatihan kompetensi pegawai", "Upacara bendera dan bukan upacara bendera di lingkungan kedinasan", "Sosialisasi kebijakan internal dan konsultasi pelayanan publik masyarakat", "Dialog pimpinan kementerian dan sarasehan budaya organisasi kantor"], "answer": 2, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Acara Resmi Kedinasan Virtual</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Acara resmi kedinasan di kementerian dapat berbentuk <strong>upacara bendera dan bukan upacara bendera</strong> yang diatur keprotokolannya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena regulasi mengklasifikasikan agenda resmi ke dalam dua kelompok utama untuk membedakan perlakuan keprotokolan dan derajat kekhidmatannya. Upacara bendera menuntut tata upacara kenegaraan lengkap, sedangkan kegiatan bukan upacara bendera tetap memiliki format protokoler resmi yang melibatkan pejabat negara atau pelantikan jabatan. Pembedaan ini memudahkan teknis penyelenggaraan di ruang digital. Maka, upacara bendera dan bukan upacara bendera adalah klasifikasi resmi yang benar.</p><p>Jawaban tepat: <strong>C. Upacara bendera dan bukan upacara bendera di lingkungan kedinasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena rapat pleno dan komisi merupakan forum pembahasan substansi teknis kerja, bukan bentuk upacara resmi.</li><li><strong>B</strong>: Kurang tepat karena seminar dan workshop masuk dalam kategori kegiatan pembelajaran, bukan seremoni resmi keprotokolan.</li><li><strong>D</strong>: Kurang tepat karena sosialisasi regulasi adalah kegiatan edukasi publik, bukan format upacara resmi kedinasan.</li><li><strong>E</strong>: Kurang tepat karena dialog pimpinan tergolong forum komunikasi pimpinan-bawahan tanpa tata upacara formal bendera.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>upacara bendera dan bukan upacara bendera</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Salah satu agenda resmi kedinasan yang termasuk dalam kategori kegiatan bukan upacara bendera adalah...", "options": ["Peringatan detik-detik proklamasi kemerdekaan Republik Indonesia", "Upacara bendera peringatan Hari Oeang Republik Indonesia", "Upacara bendera memperingati Hari Lahir Pancasila nasional", "Penganugerahan tanda jasa dan tanda kehormatan bagi aparatur", "Upacara bendera rutin tanggal tujuh belas setiap bulannya"], "answer": 3, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bukan Upacara Bendera: Penganugerahan Kehormatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kegiatan resmi bukan upacara bendera antara lain meliputi <strong>penganugerahan tanda jasa dan tanda kehormatan</strong> kepada pegawai atau tokoh.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena penganugerahan penghargaan kedinasan merupakan seremonial resmi yang tidak melibatkan penaikan bendera sang saka merah putih secara fisik maupun digital. Acara ini diselenggarakan dengan tata keprotokolan khusus untuk menghormati jasa dan dedikasi aparatur berprestasi. Format ini menjadi contoh baku kegiatan resmi bukan upacara bendera. Oleh sebab itu, penganugerahan tanda jasa merupakan jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Penganugerahan tanda jasa dan tanda kehormatan bagi aparatur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena peringatan detik-detik proklamasi merupakan upacara bendera kenegaraan tingkat tertinggi.</li><li><strong>B</strong>: Kurang tepat karena Hari Oeang diselenggarakan dalam bentuk upacara bendera resmi kementerian.</li><li><strong>C</strong>: Kurang tepat karena Hari Lahir Pancasila diperingati secara nasional dengan tata cara upacara bendera.</li><li><strong>E</strong>: Kurang tepat karena upacara bulanan tanggal 17 adalah bentuk upacara bendera rutin aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penganugerahan tanda jasa dan tanda kehormatan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kegiatan resmi kedinasan bukan upacara bendera yang mengikat janji suci pengabdian pegawai adalah...", "options": ["Rapat kerja penyusunan target rencana strategis lima tahunan", "Evaluasi pencapaian target kinerja triwulanan unit vertikal", "Uji kepatutan dan kelayakan calon pejabat pimpinan tinggi", "Sidang dewan pertimbangan kepegawaian mengenai banding hukuman", "Pelantikan dan pengambilan sumpah/janji pegawai negeri sipil atau jabatan"], "answer": 4, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bukan Upacara Bendera: Pelantikan dan Sumpah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kegiatan bukan upacara bendera mencakup <strong>pelantikan dan pengambilan sumpah/janji pegawai negeri sipil atau sumpah/janji jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena prosesi pengambilan sumpah pegawai negeri sipil atau pejabat struktural/fungsional merupakan momen hukum kedinasan yang sakral. Acara ini mengikuti pedoman tata keprotokolan resmi dan disaksikan oleh rohaniwan serta pimpinan instansi tanpa memerlukan rangkaian penaikan bendera. Kegiatan ini menegaskan ikatan moral pengabdian aparatur sipil. Dengan demikian, pelantikan dan sumpah jabatan adalah contoh agenda bukan upacara bendera yang tepat.</p><p>Jawaban tepat: <strong>E. Pelantikan dan pengambilan sumpah/janji pegawai negeri sipil atau jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena raker renstra adalah rapat pembahasan substansi manajerial, bukan seremoni sumpah dinas.</li><li><strong>B</strong>: Kurang tepat karena evaluasi kinerja triwulanan bersifat pemantauan capaian tugas rutin kantor.</li><li><strong>C</strong>: Kurang tepat karena uji kepatutan (fit and proper test) merupakan tahapan seleksi kompetensi SDM.</li><li><strong>D</strong>: Kurang tepat karena sidang disiplin adalah forum penegakan hukum internal kepegawaian yang tertutup.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pelantikan dan pengambilan sumpah/janji pegawai negeri sipil atau</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Agenda kedinasan bukan upacara bendera yang melibatkan delegasi resmi pihak eksternal pemerintah adalah...", "options": ["Kunjungan tamu negara, tamu pemerintah, atau tamu lembaga negara", "Penerimaan audiensi masyarakat umum terkait pengaduan layanan loket", "Kunjungan studi lapangan mahasiswa perguruan tinggi ke kantor", "Sosialisasi regulasi perpajakan kepada para pelaku usaha mikro", "Pertemuan tahunan dengan mitra penyedia jasa sarana perkantoran"], "answer": 0, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bukan Upacara Bendera: Penerimaan Tamu Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Format acara resmi bukan upacara bendera meliputi <strong>kunjungan tamu negara, tamu pemerintah, dan/atau tamu lembaga negara lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penerimaan kunjungan pimpinan atau delegasi lembaga tinggi negara dan pemerintah asing tunduk pada aturan keprotokolan resmi kenegaraan. Forum virtual yang mempertemukan pejabat kementerian dengan tamu lembaga negara menuntut penghormatan simbolik yang setara dengan kunjungan fisik. Rangkaian acara ini menjaga wibawa diplomatik dan hubungan kelembagaan institusi. Oleh karenanya, kunjungan tamu kenegaraan adalah bentuk kegiatan resmi yang dimaksud.</p><p>Jawaban tepat: <strong>A. Kunjungan tamu negara, tamu pemerintah, atau tamu lembaga negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena penerimaan aspirasi masyarakat merupakan fungsi operasional pengaduan masyarakat, bukan seremoni protokoler.</li><li><strong>C</strong>: Kurang tepat karena kunjungan edukasi mahasiswa tergolong kegiatan kehumasan dan pengenalan kelembagaan.</li><li><strong>D</strong>: Kurang tepat karena edukasi wajib pajak merupakan program penyuluhan dan pembinaan teknis perpajakan.</li><li><strong>E</strong>: Kurang tepat karena koordinasi dengan rekanan pengadaan tunduk pada tata kelola pengadaan barang dan jasa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kunjungan tamu negara, tamu pemerintah, dan/atau tamu lembaga negara</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewajiban utama bagi pegawai yang menghadiri acara resmi kedinasan secara virtual adalah...", "options": ["Menyiapkan berkas nota pembelaan terkait kinerja unit kerja", "Menyusun ringkasan berita acara untuk dibagikan ke media massa", "Tetap melakukan penghormatan sesuai tata cara keprotokolan pejabat", "Mengatur resolusi kamera pada posisi paling rendah untuk koneksi", "Mengirimkan ucapan selamat melalui fitur pesan siaran massal"], "answer": 2, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Penghormatan Keprotokolan Virtual</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai yang hadir secara virtual wajib <strong>tetap melakukan penghormatan sesuai tata cara dalam keprotokolan bagi pejabat negara, pejabat pemerintahan, dan/atau pihak tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena peralihan medium fisik ke platform digital tidak melunturkan kewajiban kepatuhan terhadap aturan protokoler kedinasan. Penghormatan terhadap pejabat negara dan simbol kebangsaan mencerminkan disiplin korps serta ketertiban hierarki birokrasi pemerintahan. Sikap tertib ini menjamin wibawa forum kenegaraan tetap terjaga secara sempurna. Maka dari itu, melaksanakan penghormatan sesuai keprotokolan adalah kewajiban yang tepat.</p><p>Jawaban tepat: <strong>C. Tetap melakukan penghormatan sesuai tata cara keprotokolan pejabat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pembuatan nota dinas merupakan ranah administrasi laporan berkala, bukan etika keprotokolan.</li><li><strong>B</strong>: Kurang tepat karena membagikan siaran pers ke wartawan merupakan wewenang unit komunikasi publik dan humas.</li><li><strong>D</strong>: Kurang tepat karena penurunan kualitas video secara sepihak dapat mengganggu kejernihan tampilan peserta resmi.</li><li><strong>E</strong>: Kurang tepat karena pengiriman pesan siaran massal saat acara dapat mengalihkan fokus dan mengganggu ketenangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tetap melakukan penghormatan sesuai tata cara dalam keprotokolan bagi pejabat</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Bentuk penghormatan resmi yang diatur dalam keprotokolan acara virtual kedinasan mencakup...", "options": ["Pemberian cinderamata komersial bernilai tinggi kepada para peserta", "Penayangan dokumentasi video profil pimpinan satker berdurasi lama", "Pembacaan riwayat prestasi akademik seluruh panitia pelaksana acara", "Penghormatan menggunakan bendera negara dan lagu kebangsaan Indonesia", "Pengibaran spanduk organisasi non-pemerintah pada tampilan layar gawai"], "answer": 3, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Penghormatan Simbol Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bentuk penghormatan protokoler resmi dapat berupa <strong>penghormatan menggunakan bendera negara Republik Indonesia</strong> dan/atau lagu kebangsaan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena bendera merah putih dan lagu Indonesia Raya merupakan simbol kedaulatan negara tertinggi yang wajib dihormati oleh seluruh aparatur dalam acara resmi. Penggunaan kedua simbol negara ini menegaskan identitas nasional dan menumbuhkan semangat patriotisme pegawai sipil dalam menjalankan tugas konstitusional. Ketentuan keprotokolan mengaturnya secara baku dan terikat aturan perundang-undangan. Oleh karena itu, penghormatan bendera dan lagu kebangsaan adalah bentuk resmi yang diatur.</p><p>Jawaban tepat: <strong>D. Penghormatan menggunakan bendera negara dan lagu kebangsaan Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pembagian cendera mata berharga tinggi menyalahi prinsip kesederhanaan dan rawan gratifikasi.</li><li><strong>B</strong>: Kurang tepat karena pemutaran profil pribadi pimpinan bukan unsur pokok dalam penghormatan simbol resmi kenegaraan.</li><li><strong>C</strong>: Kurang tepat karena paparan prestasi panitia tidak termasuk dalam tata cara keprotokolan resmi kenegaraan.</li><li><strong>E</strong>: Kurang tepat karena pemasangan atribut organisasi luar dilarang dalam forum resmi institusi pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penghormatan menggunakan bendera negara Republik Indonesia</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Bentuk penghormatan lainnya yang dipandu oleh pewara saat pimpinan atau pejabat memasuki acara virtual adalah...", "options": ["Membacakan pantun jenaka sambutan melalui pengeras suara mikrofon", "Mematikan sambungan internet selama lima menit demi ketenangan ruang", "Mengunggah ucapan selamat datang secara serentak ke media sosial", "Meminta seluruh pegawai berdiri di atas kursi tempat bekerja", "Pengumuman peserta menyalakan kamera ketika pimpinan/pejabat hadir"], "answer": 4, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penghormatan Khusus Saat Pejabat Hadir</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penghormatan lainnya dapat berupa <strong>pengumuman oleh pemandu/pembawa acara untuk peserta menyalakan kamera ketika pimpinan/pejabat pemerintah hadir/memasuki acara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena instruksi mengaktifkan kamera secara serentak saat pejabat memasuki ruang pertemuan virtual setara dengan sikap berdiri menyambut pimpinan pada pertemuan fisik. Pemandangan visual seluruh aparatur yang bersiap rapi di layar mencerminkan kesiapan korps dan penghormatan tulus atas kehadiran pimpinan. Tata tertib ini menjaga etika hubungan dinas di dunia maya. Maka dari itu, menyalakan kamera saat pejabat hadir merupakan arahan penghormatan yang benar.</p><p>Jawaban tepat: <strong>E. Pengumuman peserta menyalakan kamera ketika pimpinan/pejabat hadir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pembacaan pantun jenaka dapat mengurangi keseriusan dan wibawa prosesi kehadiran pejabat tinggi.</li><li><strong>B</strong>: Kurang tepat karena memutus koneksi internet justru menghentikan partisipasi pegawai dalam jalannya agenda dinas.</li><li><strong>C</strong>: Kurang tepat karena mengalihkan perhatian ke media sosial pribadi melanggar konsentrasi rapat kedinasan.</li><li><strong>D</strong>: Kurang tepat karena berdiri di atas kursi membahayakan keselamatan fisik dan tidak sesuai etika kepantasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>acara untuk peserta menyalakan kamera ketika pimpinan/pejabat pemerintah</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sikap tubuh aparatur saat diperdengarkan lagu kebangsaan atau penaikan bendera secara virtual adalah...", "options": ["Berdiri tegak khidmat dengan pandangan lurus dan wajah menghadap kamera", "Duduk bersandar santai di kursi kerja sambil bersenandung lirih", "Membungkukkan badan ke arah meja sambil mencatat butir sambutan", "Menundukkan kepala ke arah lantai ruangan tanpa melihat layar monitor", "Berdiri menghadap ke arah jendela luar gedung tempat bekerja"], "answer": 0, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sikap Hormat Saat Lagu Kebangsaan dan Penaikan Bendera</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Saat lagu kebangsaan atau penaikan bendera, pegawai diimbau memberi hormat dengan <strong>posisi berdiri tegak dan khidmat di tempat masing-masing dengan pandangan lurus ke depan dan wajah terlihat/menghadap kamera/layar gawai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena sikap berdiri tegak dengan pandangan terarah ke lensa kamera mencerminkan penghormatan tertinggi seorang aparatur sipil terhadap kehormatan bangsa dan negara. Posisi kamera yang tetap aktif memastikan seluruh peserta berpartisipasi secara khidmat dan seragam meski terpisah lokasi fisik. Kepatuhan ini menjadi bukti disiplin nasionalisme pegawai negeri. Dengan demikian, berdiri tegak menghadap kamera adalah sikap keprotokolan yang benar.</p><p>Jawaban tepat: <strong>A. Berdiri tegak khidmat dengan pandangan lurus dan wajah menghadap kamera</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena duduk santai saat lagu kebangsaan berkumandang mencederai tata krama dan penghormatan negara.</li><li><strong>C</strong>: Kurang tepat karena menulis saat prosesi bendera menunjukkan sikap mengabaikan keutamaan momen sakral kenegaraan.</li><li><strong>D</strong>: Kurang tepat karena menunduk ke lantai menyalahi ketentuan pandangan tegak lurus ke arah simbol penghormatan.</li><li><strong>E</strong>: Kurang tepat karena membelakangi layar kamera tidak memenuhi ketentuan visual keprotokolan acara virtual.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dan khidmat di tempat masing-masing dengan pandangan lurus ke depan dan wajah</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam acara virtual pelantikan, jika pimpinan yang memimpin mengambil posisi berdiri, sikap peserta dan undangan adalah...", "options": ["Tetap duduk tertib di tempat duduk masing-masing demi efisiensi energi", "Seluruh peserta yang dilantik dan undangan ikut mengambil posisi berdiri", "Mematikan kamera monitor sejenak hingga prosesi pembacaan doa dimulai", "Berdiri secara bergantian antarsatuan kerja sesuai panggilan moderator", "Mengatur sudut sorotan lensa ke arah meja kerja pimpinan satker"], "answer": 1, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sikap Peserta dan Undangan pada Pelantikan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam pelantikan pegawai/pejabat, apabila pimpinan negara yang <strong>memimpin mengambil posisi berdiri selama acara berlangsung, maka seluruh pegawai/pejabat yang dilantik dan undangan yang hadir juga mengambil posisi berdiri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena keselarasan sikap tubuh antara pemimpin upacara, pihak yang dilantik, dan para undangan mencerminkan kesatuan rasa hormat serta keseriusan prosesi pengucapan sumpah. Mengikuti sikap berdiri pemimpin menegaskan kepatuhan hierarkis dan solidaritas korps aparatur dalam upacara resmi kenegaraan. Keseragaman posisi ini menciptakan kekhidmatan visual di layar virtual bersama. Oleh sebab itu, seluruh pihak wajib turut mengambil posisi berdiri selama acara berlangsung.</p><p>Jawaban tepat: <strong>B. Seluruh peserta yang dilantik dan undangan ikut mengambil posisi berdiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena tetap duduk saat pimpinan upacara berdiri melanggar tata kesopanan dan aturan keprotokolan.</li><li><strong>C</strong>: Kurang tepat karena menonaktifkan kamera saat prosesi penting mengaburkan kehadiran peserta upacara resmi.</li><li><strong>D</strong>: Kurang tepat karena berdiri secara bergiliran tidak sesuai dengan keserempakan tata upacara pelantikan dinas.</li><li><strong>E</strong>: Kurang tepat karena mengubah arah sudut sorot kamera mengganggu kerapian susunan visual peserta di layar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 27) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memimpin mengambil posisi berdiri selama acara berlangsung, maka seluruh</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pegawai dapat dikecualikan dari kewajiban menyalakan kamera saat acara virtual jika mengalami...", "options": ["Beban kerja mendesak yang harus diselesaikan segera", "Penugasan dinas luar kota dari atasan langsung", "Perbedaan zona waktu pelaksanaan kegiatan kedinasan resmi", "Kendala jaringan dan/atau kondisi gawai yang tidak memungkinkan", "Ketiadaan ruangan kerja privat di lingkungan kantor"], "answer": 3, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengecualian Kewajiban Kamera Saat Kendala Teknis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengecualian kewajiban visual acara virtual diberikan dalam kondisi tertentu seperti <strong>kendala jaringan dan/atau kondisi gawai yang tidak memungkinkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena ketentuan kedinasan virtual mengatur bahwa dispensasi mematikan kamera hanya diakomodasi saat aparatur menghadapi gangguan konektivitas atau keterbatasan perangkat teknis. Pegawai tetap diwajibkan mengikuti jalannya acara secara khidmat dan tertib meskipun tanpa tayangan video aktif. Ketentuan ini menjamin fleksibilitas operasional tanpa mengorbankan esensi penghormatan acara kedinasan. Dengan demikian, kendala jaringan dan keterbatasan gawai merupakan dasar resmi pengecualian visual.</p><p>Jawaban tepat: <strong>D. Kendala jaringan dan/atau kondisi gawai yang tidak memungkinkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penyelesaian tugas kedinasan rutin bukan alasan sah untuk menonaktifkan kamera saat mengikuti agenda resmi.</li><li><strong>B</strong>: Kurang tepat karena penugasan luar kota tetap menuntut kepatuhan protokol virtual selama pegawai tercatat menghadiri forum.</li><li><strong>C</strong>: Kurang tepat karena perbedaan zona waktu operasional tidak membatalkan kewajiban visual peserta pertemuan kedinasan.</li><li><strong>E</strong>: Kurang tepat karena keterbatasan ruang privat diatasi dengan pemilihan latar belakang netral, bukan mematikan kamera.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kendala jaringan dan/atau kondisi gawai yang tidak</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Langkah etis pegawai yang mengalami kendala teknis saat menghadiri acara virtual resmi adalah...", "options": ["Menghubungi pimpinan unit melalui saluran telepon seluler pribadi", "Menunggu konfirmasi presensi otomatis dari sistem kepegawaian", "Menyampaikan nota dinas permohonan dispensasi pasca-kegiatan selesai", "Menitipkan presensi kehadiran kepada rekan kerja satu ruangan", "Meminta izin/menginformasikan kondisinya kepada pembawa acara via chat"], "answer": 4, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tata Cara Penyampaian Kendala Teknis Acara Virtual</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Saat terkendala teknis, pegawai wajib <strong>meminta izin/menginformasikan kondisinya kepada</strong> pembawa/pemandu acara paling kurang melalui <strong>fitur chat dalam aplikasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena prosedur etika menuntut transparansi seketika saat pegawai tidak dapat mengaktifkan kamera atau mikrofon dalam forum virtual. Pemberitahuan langsung melalui kolom pesan instan aplikasi mempermudah pewara melakukan verifikasi status keikutsertaan tanpa menginterupsi jalannya pemaparan narasumber. Langkah proaktif ini mencerminkan tanggung jawab aparatur dalam menjaga ketertiban forum kedinasan. Oleh sebab itu, penyampaian izin tertulis melalui fitur obrolan merupakan tindakan yang paling tepat.</p><p>Jawaban tepat: <strong>E. Meminta izin/menginformasikan kondisinya kepada pembawa acara via chat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena menghubungi pimpinan secara personal saat acara berlangsung dapat mengganggu konsentrasi jalannya agenda rapat.</li><li><strong>B</strong>: Kurang tepat karena sikap menunggu tanpa konfirmasi aktif mencerminkan ketidakpedulian terhadap tata tertib forum kedinasan.</li><li><strong>C</strong>: Kurang tepat karena penyampaian keterangan susulan setelah acara usai menyalahi prinsip komunikasi real-time pada pertemuan virtual.</li><li><strong>D</strong>: Kurang tepat karena menitipkan presensi kepada rekan kerja melanggar prinsip akuntabilitas personal kehadiran aparatur sipil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>meminta izin/menginformasikan kondisinya kepada</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Aparatur diimbau menghindari koneksi internet publik gratis (free wifi) demi menjaga...", "options": ["Stabilitas koneksi dan keamanannya selama pertemuan kedinasan berlangsung", "Kecepatan unggah dokumen kerja berkapasitas besar ke peladen", "Efisiensi alokasi kuota data internet operasional instansi pemerintah", "Kepatuhan terhadap batas waktu akses jaringan lokal perkantoran", "Standarisasi alamat protokol internet pada komputer kerja kedinasan"], "answer": 0, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etika Penggunaan Akses Internet Acara Kedinasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib menghindari fasilitas free internet publik yang tidak terjamin <strong>stabilitas koneksi dan keamanannya</strong> guna mencegah risiko siber.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena panduan penggunaan sarana komunikasi kedinasan menegaskan larangan penggunaan jaringan nirkabel publik terbuka yang rentan penyadapan data. Jalur komunikasi yang stabil dan aman mutlak diperlukan agar interaksi rapat kedinasan berjalan lancar serta kerahasiaan informasi jabatan tetap terlindungi secara optimal. Keamanan digital merupakan pilar integritas dalam lingkungan kerja modern. Dengan demikian, jaminan stabilitas koneksi dan keamanan data menjadi tujuan utama arahan tersebut.</p><p>Jawaban tepat: <strong>A. Stabilitas koneksi dan keamanannya selama pertemuan kedinasan berlangsung</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena kecepatan transmisi data kapasitas besar bukan fokus utama mitigasi risiko keamanan koneksi nirkabel.</li><li><strong>C</strong>: Kurang tepat karena efisiensi kuota dinas bukan alasan keamanan siber di balik pembatasan jaringan nirkabel terbuka.</li><li><strong>D</strong>: Kurang tepat karena jam operasional jaringan lokal instansi tidak berkaitan langsung dengan bahaya koneksi nirkabel publik.</li><li><strong>E</strong>: Kurang tepat karena penyeragaman alamat protokol internet merupakan tugas administrator jaringan, bukan protokol etika pengguna.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>free internet (free wifi) yang tidak terjamin stabilitas</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tindakan awal yang wajib dilakukan pegawai saat bergabung dalam rapat virtual kedinasan adalah...", "options": ["Mengunggah bahan tayang paparan langsung ke layar utama", "Melakukan log masuk (login) lebih awal melalui verifikasi pewara", "Menyapa seluruh peserta rapat melalui pengeras suara mikrofon", "Mengatur resolusi video pada pengaturan tertinggi aplikasi pertemuan", "Membagikan tautan ruang pertemuan ke grup percakapan eksternal"], "answer": 1, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Disiplin Waktu Masuk Ruang Virtual</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setiap peserta rapat virtual kedinasan diarahkan untuk <strong>melakukan log masuk (login) lebih awal dan melalui verifikasi pewara (host)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena hadir lebih awal di ruang tunggu virtual memungkinkan penyelenggara melakukan verifikasi identitas dan unit kerja peserta sebelum agenda dimulai. Prosedur ini mencegah masuknya pihak tidak berkepentingan sekaligus memastikan kesiapan teknis audio-visual pegawai. Kepatuhan masuk ruang digital tepat waktu mencerminkan penghormatan terhadap jadwal dinas bersama. Karena itu, login lebih awal melalui proses verifikasi pewara merupakan langkah yang tepat.</p><p>Jawaban tepat: <strong>B. Melakukan log masuk (login) lebih awal melalui verifikasi pewara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pembagian materi tayang di layar utama harus menunggu instruksi resmi dari moderator atau ketua rapat.</li><li><strong>C</strong>: Kurang tepat karena menyapa lewat mikrofon secara terbuka dapat mengganggu ketenangan peserta lain yang sedang bersiap.</li><li><strong>D</strong>: Kurang tepat karena memaksimalkan resolusi video justru berisiko membebani lebar pita jaringan internet peserta.</li><li><strong>E</strong>: Kurang tepat karena menyebarkan tautan rapat ke pihak luar tanpa izin melanggar kerahasiaan dan keamanan acara kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melakukan log masuk (login) lebih awal</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sesuai panduan etika kedinasan virtual, pegawai diharapkan sudah bergabung ke aplikasi paling lambat...", "options": ["Tepat pada saat lagu kebangsaan Indonesia Raya dikumandangkan", "1 (satu) menit sebelum pembacaan susunan acara oleh pewara", "3 (tiga) menit sebelum acara pertemuan kedinasan resmi dimulai", "5 (lima) menit pasca-narasumber utama memasuki ruangan rapat", "10 (sepuluh) menit setelah sambutan pembukaan pimpinan selesai"], "answer": 2, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batas Waktu Toleransi Masuk Acara Virtual</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan resmi etika virtual mewajibkan peserta <strong>bergabung paling lambat 3 (tiga) menit sebelum acara dimulai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena batas waktu kehadiran 3 menit sebelum jadwal pembukaan menjamin ketertiban transisi acara dan kelengkapan kuorum rapat. Jeda waktu singkat tersebut dialokasikan bagi panitia untuk menertibkan nama akun, menata posisi tampilan video, dan memastikan fungsi mikrofon seluruh peserta berada dalam keadaan hening. Disiplin waktu ini mencerminkan etos kerja aparatur sipil yang menghargai waktu kerja produktif. Oleh karena itu, hadir paling lambat 3 menit sebelum agenda dibuka adalah ketentuan yang benar.</p><p>Jawaban tepat: <strong>C. 3 (tiga) menit sebelum acara pertemuan kedinasan resmi dimulai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena bergabung saat lagu kebangsaan dimulai sudah tergolong terlambat dan mengganggu kekhidmatan upacara.</li><li><strong>B</strong>: Kurang tepat karena toleransi 1 menit terlalu mepet dan berpotensi menimbulkan kendala teknis antrean ruang tunggu.</li><li><strong>D</strong>: Kurang tepat karena bergabung 5 menit setelah narasumber masuk mencerminkan tindakan indisipliner kehadiran aparatur.</li><li><strong>E</strong>: Kurang tepat karena hadir 10 menit setelah sambutan pimpinan menunjukkan kelalaian serius terhadap komitmen jadwal kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bergabung paling lambat 3 (tiga) menit</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Format penulisan identitas akun pengguna yang benar saat menghadiri rapat virtual dinas adalah...", "options": ["Menggunakan nomor induk pegawai disertai tanggal lahir resmi", "Menuliskan inisial nama lengkap beserta nomor ruangan kerja", "Menampilkan nama jabatan struktural tanpa mencantumkan nama diri", "Menggunakan nama perangkat elektronik yang digunakan saat pertemuan", "Mencantumkan nama asli dan unit kerja secara jelas dan lengkap"], "answer": 4, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Standar Identitas Akun Acara Virtual</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam mengikuti kegiatan virtual resmi, seluruh peserta wajib <strong>mencantumkan nama asli dan unit kerja</strong> pada profil pertemuan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kejelasan nama asli dan instansi/unit kerja memudahkan moderator dalam memverifikasi kehadiran serta memberikan giliran berbicara saat sesi tanggapan. Penulisan identitas yang transparan mencerminkan sikap profesional dan tanggung jawab representasi unit kerja dalam forum resmi. Kebiasaan ini juga mencegah potensi masuknya peserta gelap yang tidak memiliki otoritas kedinasan. Dengan demikian, penulisan nama lengkap dan unit kerja merupakan format baku yang ditentukan.</p><p>Jawaban tepat: <strong>E. Mencantumkan nama asli dan unit kerja secara jelas dan lengkap</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena NIP dan tanggal lahir bersifat data pribadi sensitif yang tidak efisien dibaca sebagai tanda pengenal layar.</li><li><strong>B</strong>: Kurang tepat karena penggunaan inisial nama menyulitkan panitia dan pimpinan dalam mengenali identitas penanya.</li><li><strong>C</strong>: Kurang tepat karena pencantuman jabatan tanpa nama pribadi mengaburkan akuntabilitas personal pegawai yang hadir.</li><li><strong>D</strong>: Kurang tepat karena memakai nama bawaan perangkat gawai mencerminkan kelalaian dalam menaati etika forum virtual.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mencantumkan nama asli dan unit kerja</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewajiban visual utama aparatur selama berlangsungnya kegiatan seminar atau dialog virtual adalah...", "options": ["Menampilkan wajah selama kegiatan berlangsung secara tertib", "Mengunggah pasfoto resmi ukuran kartu identitas ke papan obrolan", "Mengaktifkan filter animasi formal pada aplikasi konferensi video", "Menyorot lingkungan ruang kerja kantor secara berkesinambungan", "Menyematkan rekaman video berulang saat pemaparan materi panjang"], "answer": 0, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etika Visual dalam Acara Kedinasan Virtual</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panduan acara kedinasan virtual menginstruksikan peserta agar senantiasa <strong>menampilkan wajah selama kegiatan</strong> berlangsung.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena menyalakan kamera secara langsung memperlihatkan kehadiran, kesiapan, dan perhatian penuh pegawai terhadap substansi materi yang dibahas. Interaksi tatap layar langsung membangun konektivitas komunikasi yang setara dengan pertemuan tatap muka di ruang rapat fisik. Kepatuhan ini juga mencerminkan etika saling menghargai antara narasumber dan peserta forum. Maka dari itu, menampilkan wajah secara langsung sepanjang forum merupakan kewajiban pokok peserta.</p><p>Jawaban tepat: <strong>A. Menampilkan wajah selama kegiatan berlangsung secara tertib</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena pengunggahan pasfoto statis tidak mewakili keaktifan kehadiran fisik aparatur selama acara berjalan.</li><li><strong>C</strong>: Kurang tepat karena penerapan filter animasi tidak mencerminkan keseriusan dan martabat forum kedinasan resmi.</li><li><strong>D</strong>: Kurang tepat karena sorotan kamera difokuskan pada wajah aparatur, bukan eksplorasi tata ruang fisik kantor.</li><li><strong>E</strong>: Kurang tepat karena pemutaran rekaman video berulang merupakan tindakan manipulatif yang menciderai integritas kehadiran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak dapat menampilkan wajah karena</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penerapan disiplin audio peserta saat pimpinan atau narasumber menyampaikan materi dilakukan dengan cara...", "options": ["Menyalakan pengeras suara eksternal dengan volume suara maksimal", "Disiplin dalam mematikan mikrofon agar forum tetap tenang dan tertib", "Mengatur mikrofon pada posisi jeda otomatis setiap sepuluh menit", "Menyela paparan materi saat terdapat perbedaan sudut pandang kerja", "Mengalihkan jalur komunikasi suara ke saluran panggilan pribadi"], "answer": 1, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengelolaan Audio dalam Pertemuan Virtual</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Seluruh pegawai wajib mempraktikkan <strong>disiplin dalam mematikan mikrofon saat pimpinan/narasumber menyampaikan materi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena menonaktifkan suara mikrofon (mute) mencegah kebocoran suara latar (background noise) yang berpotensi memecah konsentrasi pembicara dan peserta lainnya. Kepatuhan ini menciptakan ekosistem rapat virtual yang tenang, profesional, dan kondusif bagi penerimaan informasi penting. Aparatur baru membuka mikrofon apabila telah dipersilakan oleh moderator atau pemimpin forum. Oleh sebab itu, mematikan mikrofon secara sadar merupakan cerminan etika audio yang benar.</p><p>Jawaban tepat: <strong>B. Disiplin dalam mematikan mikrofon agar forum tetap tenang dan tertib</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena memaksimalkan volume pengeras eksternal dapat menimbulkan gema dan dengung suara yang mengganggu.</li><li><strong>C</strong>: Kurang tepat karena pengaturan jeda otomatis tidak menjamin mikrofon tertutup saat pembicara sedang memaparkan materi.</li><li><strong>D</strong>: Kurang tepat karena memotong pembicaraan pimpinan secara mendadak menyalahi tata krama komunikasi kedinasan.</li><li><strong>E</strong>: Kurang tepat karena pengalihan sambungan suara ke ponsel pribadi tidak relevan dengan ketertiban tata suara forum virtual.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>disiplin dalam mematikan mikrofon</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Pemanfaatan kolom percakapan teks (chat) dalam aplikasi pertemuan kedinasan wajib diarahkan untuk...", "options": ["Menyampaikan promosi program kesejahteraan luar dinas", "Menyebarkan candaan informal guna mencairkan suasana rapat", "Menggunakan fasilitas obrolan secara sopan, bijaksana, dan substantif", "Mendiskusikan urusan pribadi di luar pokok bahasan pertemuan", "Menyampaikan kritik terbuka yang menyerang kepribadian rekan kerja"], "answer": 2, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etika Penggunaan Fitur Chat Virtual</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketentuan etika virtual mewajibkan pegawai <strong>menggunakan fasilitas obrolan dalam aplikasi pesan instan (chat) dengan sopan dan bijaksana</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kolom pesan dalam forum kedinasan berfungsi sebagai media interaksi formal untuk mengajukan pertanyaan, mencatat presensi, atau menyampaikan izin teknis. Penggunaan bahasa yang santun, lugas, dan relevan dengan materi bahasan menjaga wibawa institusi serta ketertiban jalannya komunikasi digital. Saluran dinas dilarang disalahgunakan untuk percakapan informal yang mengaburkan pesan utama. Maka dari itu, memanfaatkan obrolan secara bijaksana dan sopan merupakan wujud kepatuhan etika.</p><p>Jawaban tepat: <strong>C. Menggunakan fasilitas obrolan secara sopan, bijaksana, dan substantif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena mempromosikan kegiatan di luar kedinasan menyalahi peruntukan sarana komunikasi instansi.</li><li><strong>B</strong>: Kurang tepat karena candaan berlebihan dalam forum formal berpotensi menurunkan keseriusan pembahasan dinas.</li><li><strong>D</strong>: Kurang tepat karena percakapan pribadi di kolom publik mengaburkan catatan resmi jalannya persidangan rapat.</li><li><strong>E</strong>: Kurang tepat karena kritik personal yang menyerang individu melanggar nilai kesopanan dan kode perilaku aparatur sipil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menggunakan fasilitas obrolan dalam aplikasi pesan instan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Jika penyelenggara tidak menetapkan latar virtual khusus, pilihan latar layar yang tepat bagi pegawai adalah...", "options": ["Pemandangan destinasi wisata alam mancanegara yang populer", "Poster kegiatan promosi komersial organisasi kemasyarakatan", "Warna-warni abstrak cerah yang memantulkan efek cahaya bergerak", "Virtual background lain yang merepresentasikan institusi dan estetika", "Tampilan ruang pribadi santai tanpa memperhatikan kerapian visual"], "answer": 3, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Standar Latar Belakang Layar Virtual</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Apabila panitia tidak menetapkan latar khusus, peserta dapat memakai <strong>virtual background lain yang merepresentasikan institusi atau virtual background yang sesuai estetika dan etika</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena latar belakang layar digital berfungsi sebagai identitas visual instansi yang menjaga citra profesional aparatur pemerintah. Pemilihan gambar gedung kantor, logo resmi, atau latar bernuansa rapi dan teduh mencerminkan sikap hormat terhadap forum kedinasan. Standar estetika ini menjamin tampilan peserta tetap pantas dipandang masyarakat maupun pejabat negara yang hadir. Dengan demikian, latar representasi institusi yang etis adalah pilihan yang paling tepat.</p><p>Jawaban tepat: <strong>D. Virtual background lain yang merepresentasikan institusi dan estetika</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pemandangan wisata santai tidak merepresentasikan lingkungan kerja formal instansi pemerintah.</li><li><strong>B</strong>: Kurang tepat karena pemajangan poster komersial organisasi luar mencampurkan kepentingan dinas dengan urusan eksternal.</li><li><strong>C</strong>: Kurang tepat karena warna abstrak mencolok dan efek gerak berpotensi mengaburkan fokus peserta terhadap pemaparan.</li><li><strong>E</strong>: Kurang tepat karena tampilan ruang santai yang berantakan mencederai citra kerapian dan profesionalitas aparatur sipil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 28) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>merepresentasikan institusi atau virtual background</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewajiban aparatur saat menghadiri rapat virtual yang substansi materinya bersifat rahasia adalah...", "options": ["Didistribusikan secara terbatas sesuai ketentuan kerahasiaan dinas", "Membagikan salinan ringkasan kepada media massa untuk transparansi", "Menyimpan salinan paparan dalam media penyimpanan komersial terbuka", "Meneruskan dokumen materi rapat kepada seluruh rekan sejawat", "Mempublikasikan intisari rapat pada linimasa media sosial personal"], "answer": 0, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengamanan Rapat Virtual Materi Rahasia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Materi rapat yang <strong>hanya boleh didistribusikan secara terbatas dan/atau perlu dijaga kerahasiaannya</strong> wajib dijaga dengan ketat oleh seluruh peserta.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena dokumen dan isu strategis pemerintahan menuntut kepatuhan mutlak atas pembatasan akses informasi sesuai klasifikasi keamanan negara. Pegawai dilarang menyebarkan, membagikan, atau memperlihatkan bahan rapat kepada pihak luar yang tidak memiliki hak akses sah secara kedinasan. Pelanggaran terhadap kerahasiaan rapat virtual dapat menimbulkan kerugian negara dan merusak kepercayaan publik. Oleh sebab itu, pembatasan distribusi materi merupakan kewajiban yang tidak dapat ditawar.</p><p>Jawaban tepat: <strong>A. Didistribusikan secara terbatas sesuai ketentuan kerahasiaan dinas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena memberikan materi rahasia kepada pers tanpa mandat resmi merupakan bentuk kebocoran rahasia negara.</li><li><strong>C</strong>: Kurang tepat karena mengunggah dokumen terbatas ke penyimpanan awan publik berisiko tinggi terhadap peretasan data.</li><li><strong>D</strong>: Kurang tepat karena membagikan dokumen rahasia kepada rekan tanpa relevansi tugas menyalahi asas pembatasan akses.</li><li><strong>E</strong>: Kurang tepat karena mempublikasikan intisari rapat terbatas di media sosial merupakan pelanggaran berat kode etik ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>substansi/materinya hanya boleh didistribusikan secara</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Konsekuensi etis bagi pegawai yang menerima materi rapat virtual berkategori terbatas adalah...", "options": ["Berhak menggandakan materi untuk kepentingan kajian umum", "Wajib melaksanakan ketentuan pembatasan distribusi dokumen rapat", "Dapat memberikan akses materi kepada mitra kerja perorangan", "Bebas memaparkan materi dalam forum diskusi terbuka lintas instansi", "Menyusun arsip digital pribadi pada peladen di luar pengawasan dinas"], "answer": 1, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Distribusi Dokumen Rapat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setiap pegawai yang hadir <strong>wajib melaksanakan ketentuan pembatasan distribusi dan/atau kerahasiaan materi rapat</strong> sesuai arahan kedinasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena hak menerima informasi jabatan diimbangi dengan kewajiban membatasi penyebarannya hanya pada pihak berwenang. Sikap amanah dalam menjaga integritas dokumen negara mencerminkan kedewasaan kepemimpinan dan loyalitas terhadap institusi kementerian. Aparatur tidak boleh memanfaatkan data internal untuk kepentingan di luar mandat pelaksanaan tugas kedinasan. Oleh karena itu, menegakkan pembatasan distribusi adalah tindakan yang tepat.</p><p>Jawaban tepat: <strong>B. Wajib melaksanakan ketentuan pembatasan distribusi dokumen rapat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena perbanyakan dokumen rahasia untuk konsumsi umum dilarang keras tanpa persetujuan pimpinan.</li><li><strong>C</strong>: Kurang tepat karena memberikan akses informasi rahasia kepada pihak mitra perorangan berisiko kebocoran rahasia jabatan.</li><li><strong>D</strong>: Kurang tepat karena mendiskusikan materi terbatas dalam forum publik melanggar batas kepatutan informasi kedinasan.</li><li><strong>E</strong>: Kurang tepat karena pengarsipan pada peladen mandiri tanpa kendali instansi membahayakan kedaulatan data pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>wajib melaksanakan ketentuan pembatasan distribusi</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Sikap yang diimbau apabila pegawai harus mengikuti lebih dari satu acara virtual kedinasan dalam waktu bersamaan adalah...", "options": ["Menutup seluruh tautan pertemuan dan melapor kendala kepada pimpinan", "Membuka seluruh gawai secara bersamaan dengan mematikan suara mikrofon", "Memprioritaskan perhatian pada salah satu kegiatan sesuai tingkat urgensi", "Mengalihkan tanggung jawab kehadiran sepenuhnya kepada staf honorer", "Memilih acara virtual yang memiliki durasi pelaksanaan paling singkat"], "answer": 2, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etika Saat Jadwal Acara Virtual Bersamaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam situasi jadwal bentrok, pegawai diimbau agar dapat <strong>memprioritaskan perhatian pada salah satu kegiatan (sesuai tingkat urgensi dan kepentingannya)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena perhatian yang terbagi pada banyak layar dalam satu waktu menurunkan fokus analisis serta kualitas keterlibatan pegawai dalam proses pengambilan keputusan. Dengan menimbang tingkat kepentingan strategis dan urgensi penugasan, aparatur dapat berpartisipasi secara maksimal pada forum utama sambil mengomunikasikan izin ketidakhadiran di forum pendamping. Pendekatan rasional ini menjaga profesionalisme aparatur sipil. Dengan demikian, menentukan prioritas kegiatan berdasarkan urgensi adalah panduan yang tepat.</p><p>Jawaban tepat: <strong>C. Memprioritaskan perhatian pada salah satu kegiatan sesuai tingkat urgensi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena meninggalkan seluruh agenda dinas menunjukkan sikap lepas tanggung jawab atas kewajiban kedinasan.</li><li><strong>B</strong>: Kurang tepat karena membuka banyak forum secara simultan menurunkan efektivitas perhatian dan mengaburkan partisipasi nyata.</li><li><strong>D</strong>: Kurang tepat karena mengalihkan kehadiran kepada pihak yang tidak memiliki kompetensi jabatan melanggar tata kelola birokrasi.</li><li><strong>E</strong>: Kurang tepat karena durasi waktu bukan parameter objektif dalam menentukan bobot kepentingan agenda kenegaraan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memprioritaskan perhatian pada salah satu kegiatan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penghormatan protokoler virtual wajib diupayakan secara optimal terutama saat menghadiri agenda yang...", "options": ["Dilaksanakan di luar hari kerja operasional kantor pelayanan publik", "Menyediakan alokasi anggaran konsumsi dan fasilitas akomodasi", "Menghadirkan narasumber dari kalangan akademisi luar negeri", "Terutama dalam acara resmi yang melibatkan pejabat negara Republik Indonesia", "Diikuti oleh mitra kerja komersial tingkat multinasional"], "answer": 3, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penghormatan Acara Resmi Pejabat Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai berupaya tetap melakukan penghormatan <strong>terutama dalam acara resmi yang melibatkan pejabat negara</strong> sebagai wujud etika birokrasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kehadiran pemimpin lembaga tinggi negara dalam forum virtual merepresentasikan wibawa konstitusional dan integritas pemerintahan. Sikap tertib, khidmat, serta kepatuhan pada tata cara keprotokolan mencerminkan penghormatan aparatur terhadap simbol negara. Hal ini memperkuat kultur birokrasi yang tertib asas dan bermartabat tinggi di hadapan publik. Oleh sebab itu, acara resmi yang melibatkan pejabat negara menuntut prioritas penghormatan paling tinggi.</p><p>Jawaban tepat: <strong>D. Terutama dalam acara resmi yang melibatkan pejabat negara Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena waktu pelaksanaan di luar jam kantor bukan parameter utama dalam penetapan derajat keprotokolan resmi.</li><li><strong>B</strong>: Kurang tepat karena ketersediaan fasilitas materiil tidak menjadi tolok ukur kesungguhan penghormatan tata upacara dinas.</li><li><strong>C</strong>: Kurang tepat karena kehadiran pemateri luar negeri dihormati sebagai narasumber ilmiah, bukan simbol keprotokolan negara.</li><li><strong>E</strong>: Kurang tepat karena keterlibatan mitra korporasi swasta tunduk pada etika kemitraan, bukan tata upacara pejabat negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dalam acara resmi yang melibatkan pejabat negara</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Ketentuan penataan posisi pada acara virtual pelantikan pegawai yang menggunakan satu kamera bersama adalah...", "options": ["Menempatkan pejabat penilai di tengah tanpa melibatkan pegawai bersangkutan", "Mengatur sorotan video secara bergantian setiap lima menit sekali", "Menempatkan sebagian pegawai di luar jangkauan pandang layar lensa", "Memosisikan pegawai pelaksana di deretan depan menghadap arah samping", "Menampilkan kelompok pegawai yang akan dilantik dalam satu frame rapi"], "answer": 4, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tata Tertib Acara Virtual Berkelompok</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam acara pelantikan bersama, satuan kerja wajib <strong>menampilkan kelompok/beberapa pegawai yang akan dilantik dalam 1 (satu) gawai/kamera</strong> secara tertib.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena penyelenggaraan upacara resmi seperti pelantikan menuntut kerapian formasi barisan agar seluruh aparatur yang diambil sumpahnya terpantau dengan jelas oleh pejabat pengambil sumpah. Penataan posisi tubuh yang seragam, jarak yang proporsional, serta pencahayaan yang memadai mencerminkan kekhidmatan prosesi sakral kenegaraan. Hal ini membuktikan keteraturan administrasi satker di ruang digital. Maka, memposisikan seluruh pegawai dalam satu bidang pandang kamera yang proporsional adalah langkah yang benar.</p><p>Jawaban tepat: <strong>E. Menampilkan kelompok pegawai yang akan dilantik dalam satu frame rapi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena fokus utama sorotan prosesi pelantikan adalah para pejabat atau pegawai yang sedang dilantik.</li><li><strong>B</strong>: Kurang tepat karena perpindahan sudut sorotan secara manual dapat mengganggu stabilitas visual prosesi pengambilan sumpah.</li><li><strong>C</strong>: Kurang tepat karena membiarkan pegawai di luar jangkauan lensa menyalahi verifikasi keabsahan kehadiran fisik pelantikan.</li><li><strong>D</strong>: Kurang tepat karena posisi menghadap samping tidak memenuhi etika pandangan lurus ke arah pembawa sumpah jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kelompok/beberapa pegawai yang akan dilantik dalam 1 (satu) gawai/kamera</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan sistem kerja fleksibel di lingkungan birokrasi wajib berlandaskan pada...", "options": ["Ketersediaan insentif kinerja finansial tambahan bagi aparatur", "Kecepatan akses data pada aplikasi absensi sidik jari nirkabel", "Melaksanakan budaya (corporate culture) berdasarkan Nilai-Nilai Kemenkeu", "Kelonggaran pengawasan jam operasional kerja oleh pimpinan satker", "Penurunan target capaian kinerja bulanan unit kantor vertikal"], "answer": 2, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fondasi Budaya Organisasi dalam SKF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Seluruh pegawai wajib beradaptasi dan <strong>melaksanakan budaya (corporate culture) yang berdasarkan pada Nilai-Nilai Kemenkeu</strong> dalam penerapan sistem kerja fleksibel.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena transformasi pola kerja modern berbasis fleksibilitas tempat dan waktu hanya dapat berhasil apabila ditopang oleh nilai budaya organisasi yang kokoh. Nilai-nilai kementerian menjadi kompas perilaku yang menjaga standar integritas dan etos kerja aparatur meski bertugas dari kediaman masing-masing. Tanpa fondasi nilai yang dihayati, fleksibilitas berisiko menurunkan produktivitas layanan. Oleh karena itu, penerapan budaya organisasi berbasis nilai institusi adalah pilar utama.</p><p>Jawaban tepat: <strong>C. Melaksanakan budaya (corporate culture) berdasarkan Nilai-Nilai Kemenkeu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penerapan kerja fleksibel bukan didorong oleh pemberian insentif moneter tambahan.</li><li><strong>B</strong>: Kurang tepat karena kecepatan perangkat absensi digital sekadar sarana pencatatan administrasi, bukan fondasi nilai.</li><li><strong>D</strong>: Kurang tepat karena sistem kerja fleksibel tetap menghendaki akuntabilitas pengawasan yang terukur dan disiplin.</li><li><strong>E</strong>: Kurang tepat karena fleksibilitas metode kerja tidak boleh menurunkan standar capaian target kinerja instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melaksanakan budaya (corporate culture)</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Makna substantif dari nilai integritas dalam pelaksanaan pola kerja fleksibel adalah keharusan...", "options": ["Menyelesaikan dokumen administrasi anggaran tanpa koreksi atasan", "Mencapai target kepuasan pengguna jasa di atas standar minimum", "Membangun hubungan koordinasi kerja secara intensif antar-lembaga", "Berpikir, berkata, berperilaku, dan bertindak baik dan benar serta taat kode etik", "Mengembangkan sarana kerja digital termutakhir bagi pelayanan publik"], "answer": 3, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Nilai Integritas dalam SKF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Integritas berarti aparatur harus <strong>berpikir, berkata, berperilaku, dan bertindak baik dan benar serta selalu memegang teguh kode etik dan prinsip-prinsip moral</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena rumusan resmi integritas menuntut keselarasan utuh antara olah pikir, lisan, sikap batin, dan perbuatan nyata aparatur sipil negara. Dalam sistem kerja jarak jauh, nilai ini menjadi pengingat mandiri agar pegawai senantiasa berbuat jujur dan patuh pada aturan hukum kendati tanpa supervisi tatap mata langsung dari atasan. Integritas merupakan mahkota moral birokrasi berkelas dunia. Maka dari itu, konsistensi berpikir, berkata, dan bertindak benar adalah definisi hakiki integritas.</p><p>Jawaban tepat: <strong>D. Berpikir, berkata, berperilaku, dan bertindak baik dan benar serta taat kode etik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penyelesaian naskah dinas tanpa koreksi mencerminkan ketelitian teknis, bukan definisi utuh integritas.</li><li><strong>B</strong>: Kurang tepat karena capaian standar kepuasan pemangku kepentingan merupakan muatan pokok dari nilai Pelayanan.</li><li><strong>C</strong>: Kurang tepat karena penguatan koordinasi lintas sektor adalah wujud implementasi dari nilai Sinergi.</li><li><strong>E</strong>: Kurang tepat karena inovasi sarana digital modern merupakan perwujudan komitmen dari nilai Kesempurnaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Integritas, yang berarti seluruh pegawai harus berpikir</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Penerapan nyata nilai integritas bagi pegawai yang melaksanakan tugas kedinasan dari rumah (WFH) adalah...", "options": ["Menyelesaikan urusan keluarga terlebih dahulu sebelum mengisi daftar hadir", "Mendelegasikan penulisan laporan kegiatan harian kepada staf pendukung", "Menyunting lokasi presensi digital agar sesuai dengan radius kantor", "Menggunakan waktu jam kerja untuk aktivitas komersial daring pribadi", "Jujur dalam menggunakan/melaporkan waktu bekerja dan kondisi kesehatan"], "answer": 4, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Integritas Pelaporan Waktu Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Contoh perilaku integritas saat fleksibilitas kerja mencakup sikap <strong>jujur dalam menggunakan/melaporkan waktu bekerja</strong> serta tidak menyebarkan berita bohong.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena esensi akuntabilitas WFH bertumpu pada kejujuran aparatur dalam mendedikasikan waktu kerja efektifnya sesuai jam kantor kedinasan resmi. Menyampaikan laporan logbook secara faktual serta melaporkan status kesehatan riil mencerminkan kejujuran moral pegawai di hadapan institusi dan negara. Kepatuhan ini mencegah penyalahgunaan hak fleksibilitas demi kepentingan non-kedinasan. Oleh sebab itu, bersikap jujur atas pemanfaatan waktu kerja adalah wujud integritas yang tepat.</p><p>Jawaban tepat: <strong>E. Jujur dalam menggunakan/melaporkan waktu bekerja dan kondisi kesehatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena mendahulukan urusan privat di jam kerja dinas menciderai komitmen jam pelayanan aparatur sipil.</li><li><strong>B</strong>: Kurang tepat karena pembuatan laporan kinerja harian merupakan kewajiban personal yang tidak boleh dilimpahkan ke pihak lain.</li><li><strong>C</strong>: Kurang tepat karena memanipulasi koordinat lokasi presensi digital tergolong perbuatan tercela dan pelanggaran integritas.</li><li><strong>D</strong>: Kurang tepat karena berbisnis komersial selama jam dinas aktif melanggar sumpah jabatan dan ketentuan disiplin pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jujur dalam menggunakan/melaporkan waktu bekerja</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perwujudan nilai profesionalisme pegawai aparatur sipil dalam lingkungan tugas kedinasan ditandai oleh...", "options": ["Bekerja dengan tuntas dan akurat berdasarkan kompetensi terbaik", "Mengutamakan kepentingan kelompok kerja di atas kepentingan umum", "Menunggu instruksi tertulis dari atasan sebelum memproses berkas", "Membatasi penerimaan tugas baru di luar uraian jabatan pokok", "Menyerahkan penyusunan keputusan dinas kepada pihak ketiga eksternal"], "answer": 0, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Nilai Profesionalisme dalam SKF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Profesionalisme menuntut setiap aparatur <strong>bekerja dengan tuntas dan akurat berdasarkan kompetensi terbaik dan penuh tanggung jawab serta komitmen tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena profesionalisme menghendaki penguasaan keahlian teknis yang dipadukan dengan tanggung jawab penyelesaian tugas secara sempurna dan tepat waktu. Hasil kerja aparatur yang profesional memiliki akurasi data tinggi, minim galat, serta dapat dipertanggungjawabkan secara hukum dan metodologis. Sikap ini memastikan pelayanan birokrasi tetap bermutu prima tanpa terpengaruh jarak fisik ruang kerja. Dengan demikian, menuntaskan pekerjaan secara akurat sesuai keahlian adalah ciri pokok profesionalisme.</p><p>Jawaban tepat: <strong>A. Bekerja dengan tuntas dan akurat berdasarkan kompetensi terbaik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena mengutamakan kelompok kerja internal di atas kepentingan umum menyalahi asas netralitas aparatur.</li><li><strong>C</strong>: Kurang tepat karena sikap menunggu tanpa inisiatif profesional menunjukkan rendahnya komitmen dan kemandirian kerja.</li><li><strong>D</strong>: Kurang tepat karena aparatur wajib memiliki fleksibilitas dalam menerima penugasan pimpinan demi sasaran strategis.</li><li><strong>E</strong>: Kurang tepat karena penyerahan wewenang perumusan keputusan dinas ke pihak luar melanggar asas kedaulatan birokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Profesionalisme, yang berarti seluruh pegawai harus bekerja dengan tuntas</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Cerminan perilaku profesional aparatur sipil negara yang menjalankan tugas secara fleksibel dari rumah adalah...", "options": ["Menunda pengiriman laporan berkala hingga jadwal bekerja dari kantor tiba", "Meskipun WFH tugas/layanan tetap dapat dituntaskan tepat waktu dan akurat", "Meminta perpanjangan batas waktu penyerahan hasil verifikasi dokumen", "Membatasi komunikasi koordinasi dengan pimpinan kerja pada pagi hari", "Melimpahkan verifikasi berkas sulit kepada pegawai yang bertugas di kantor"], "answer": 1, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Profesionalisme Saat Bekerja dari Rumah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan nilai profesionalisme menegaskan bahwa <strong>meskipun WFH tugas/layanan tetap dapat dituntaskan tepat waktu</strong> secara mandiri.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena aparatur profesional tidak menjadikan lokasi kerja fleksibel sebagai penghalang tercapainya target keluaran kerja (output). Pengelolaan waktu yang mandiri, kedisiplinan diri yang kuat, dan fokus terhadap kualitas penyelesaian tugas membuktikan bahwa produktivitas ASN tetap terjaga tinggi di era digital. Hal ini mematahkan persepsi bahwa bekerja dari rumah menurunkan kecepatan layanan dinas. Karena itu, ketuntasan layanan tepat waktu saat WFH adalah implementasi profesionalisme yang sahih.</p><p>Jawaban tepat: <strong>B. Meskipun WFH tugas/layanan tetap dapat dituntaskan tepat waktu dan akurat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena menunda penyampaian laporan kinerja mengabaikan standar kecepatan dan ketepatan waktu birokrasi.</li><li><strong>C</strong>: Kurang tepat karena sering meminta relaksasi batas waktu mencerminkan manajemen kerja mandiri yang belum optimal.</li><li><strong>D</strong>: Kurang tepat karena membatasi koordinasi dinas menghambat aliran komunikasi penugasan mendesak dari pimpinan satker.</li><li><strong>E</strong>: Kurang tepat karena melempar tugas berbobot berat kepada rekan WFO menunjukkan lemahnya rasa tanggung jawab pribadi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 29) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>meskipun WFH tugas/layanan tetap dapat dituntaskan tepat waktu</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama penguatan kemitraan harmonis dan kerja sama internal pada nilai Sinergi adalah menghasilkan...", "options": ["Sistem birokrasi berjenjang yang menitikberatkan kepatuhan hierarkis", "Karya yang bermanfaat dan berkualitas tinggi bagi bangsa dan negara", "Efisiensi pengeluaran belanja dinas pegawai melalui pembatasan rapat", "Laporan pengawasan formal guna memenuhi kewajiban administratif satker", "Keseragaman latar belakang pendidikan seluruh pengelola keuangan"], "answer": 1, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Substantif Nilai Sinergi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Sinergi bertujuan membangun hubungan kemitraan internal dan eksternal secara produktif <strong>untuk menghasilkan karya yang bermanfaat dan berkualitas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kolaborasi lintas unit kerja dan pemangku kepentingan dirancang untuk menciptakan daya ungkit kinerja yang melampaui capaian kerja individual. Karya berkualitas dan memberi kemanfaatan luas bagi masyarakat merupakan muara akhir dari seluruh komunikasi dan koordinasi yang dibangun aparatur sipil. Dengan menyatukan beragam potensi keahlian, organisasi mampu menghasilkan solusi kebijakan publik yang kokoh. Maka, menghasilkan karya bermanfaat dan berkualitas adalah tujuan pokok sinergi.</p><p>Jawaban tepat: <strong>B. Karya yang bermanfaat dan berkualitas tinggi bagi bangsa dan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pembentukan rantai birokrasi berjenjang yang kaku justru berpotensi memperlambat proses sinergi modern.</li><li><strong>C</strong>: Kurang tepat karena efisiensi anggaran merupakan dampak samping tata kelola keuangan, bukan tujuan filosofis sinergi.</li><li><strong>D</strong>: Kurang tepat karena pemenuhan laporan administratif formal semata mengabaikan dampak kemanfaatan riil bagi publik.</li><li><strong>E</strong>: Kurang tepat karena keseragaman latar belakang akademik tidak diperlukan, keberagaman keahlian justru memperkaya sinergi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bermanfaat dan berkualitas</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Penerapan nilai sinergi dalam pelaksanaan sistem kerja fleksibel tercermin dari sikap pegawai yang...", "options": ["Menyelesaikan seluruh target pekerjaan mandiri tanpa melibatkan rekan", "Menunggu arahan tertulis dari unit pusat sebelum melakukan kontak", "Meskipun tidak ada tatap muka namun koordinasi kerja tetap berjalan optimal", "Membatasi interaksi kerja sama pada pegawai satu angkatan diklat", "Mengalihkan tanggung jawab koordinasi kepada pejabat fungsional perencana"], "answer": 2, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sinergi Kerja Jarak Jauh Tanpa Tatap Muka</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wujud sinergi pada sistem kerja fleksibel menegaskan bahwa <strong>meskipun tidak ada tatap muka namun koordinasi dan kerja sama antar rekan kerja/unit</strong> tetap dilaksanakan sebaik-baiknya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena ketiadaan interaksi fisik secara langsung tidak boleh menjadi alasan terputusnya komunikasi dan kolaborasi penuntasan target kinerja satker. Melalui pemanfaatan media digital secara aktif, proaktif menyapa rekan setim, dan saling berbagi data, koordinasi kerja tetap dapat terjalin secara tangkas dan solid. Hal ini membuktikan bahwa sinergi birokrasi melampaui batasan ruang geografis. Dengan demikian, menjaga koordinasi tanpa hambatan tatap muka adalah implementasi sinergi yang benar.</p><p>Jawaban tepat: <strong>C. Meskipun tidak ada tatap muka namun koordinasi kerja tetap berjalan optimal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena bekerja sendiri secara eksklusif tanpa berbagi informasi mencerminkan sikap ego fungsional.</li><li><strong>B</strong>: Kurang tepat karena sikap pasif menunggu perintah menghambat kelancaran koordinasi horizontal antarseksi di unit kerja.</li><li><strong>D</strong>: Kurang tepat karena membatasi pergaulan kedinasan pada kelompok tertentu merusak kerukunan dan inklusivitas organisasi.</li><li><strong>E</strong>: Kurang tepat karena koordinasi dan kemitraan kerja merupakan kewajiban kolektif seluruh jenjang pegawai tanpa terkecuali.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>koordinasi dan kerja sama antar rekan kerja/unit</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam menjaga keutuhan sinergi lingkungan kerja, aparatur negara memiliki kewajiban moral untuk...", "options": ["Menyampaikan seluruh keluhan operasional internal kepada forum terbuka", "Mendukung salah satu kubu dalam perbedaan pandangan politik praktis", "Menyebarluaskan rumor kebijakan yang belum disahkan oleh pimpinan satker", "Tidak membangun opini dan/atau menyebarkan informasi yang memecah belah", "Membuat analisis tandingan atas keputusan kedinasan yang telah berkekuatan hukum"], "answer": 3, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komitmen Sinergi Mencegah Polarisasi Informasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Komitmen sinergi mengamanatkan pegawai agar <strong>tidak membangun opini dan/atau menyebarkan informasi yang dapat memecah belah</strong> persatuan korps.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena keharmonisan organisasi birokrasi sangat bergantung pada iklim komunikasi yang sehat, objektif, dan berlandaskan fakta kedinasan. Menghembuskan narasi yang bersifat adu domba atau membagikan konten polaritatif di grup percakapan internal merusak rasa saling percaya antarpegawai dan meruntuhkan fondasi kerja tim. ASN berkewajiban menjadi perekat dan pemersatu bangsa. Oleh sebab itu, menjauhi opini pemecah belah merupakan kewajiban moral aparatur.</p><p>Jawaban tepat: <strong>D. Tidak membangun opini dan/atau menyebarkan informasi yang memecah belah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena keluhan teknis wajib disampaikan melalui mekanisme saluran evaluasi internal yang sah dan konstruktif.</li><li><strong>B</strong>: Kurang tepat karena keterlibatan dalam kubu politik praktis melanggar asas netralitas aparatur sipil negara.</li><li><strong>C</strong>: Kurang tepat karena menyebarkan desas-desus draf kebijakan dapat menimbulkan kepanikan dan disinformasi publik.</li><li><strong>E</strong>: Kurang tepat karena menyusun opini tandingan terhadap regulasi sah mencederai kepatuhan hukum dan tertib organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak membangun opini dan/atau menyebarkan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Hakikat utama dari implementasi nilai Pelayanan di lingkungan birokrasi adalah memberikan layanan demi...", "options": ["Mendapatkan penghargaan predikat kantor pelayanan teladan nasional", "Mengantisipasi catatan evaluasi dari instansi pemeriksa eksternal", "Mempercepat penyerapan anggaran belanja operasional kantor di akhir tahun", "Memenuhi target jumlah jam layanan tatap muka bagi para pegawai kantor", "Memenuhi kepuasan para pemangku kepentingan secara tulus dan berkualitas"], "answer": 4, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Nilai Pelayanan dalam SKF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelayanan diartikan sebagai komitmen aparatur untuk <strong>memenuhi kepuasan para pemangku kepentingan</strong> dan dilaksanakan dengan sepenuh hati.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena orientasi keberadaan birokrasi pemerintah adalah memberikan kemanfaatan dan kepuasan pelayanan bagi masyarakat serta seluruh pemangku kepentingan. Standar kepuasan dicapai bukan sekadar menggugurkan kewajiban formal atau mengejar piala penghargaan, melainkan memberikan solusi nyata atas kebutuhan publik secara ramah dan profesional. Ketulusan dalam melayani membangun legitimasi sosial institusi negara. Dengan demikian, memenuhi kepuasan pemangku kepentingan adalah hakikat nilai pelayanan.</p><p>Jawaban tepat: <strong>E. Memenuhi kepuasan para pemangku kepentingan secara tulus dan berkualitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena perolehan plakat penghargaan hanyalah apresiasi luaran, bukan niat dasar pelayanan publik.</li><li><strong>B</strong>: Kurang tepat karena motif menghindari audit pengawas mencerminkan kepatuhan semu yang bersifat defensif.</li><li><strong>C</strong>: Kurang tepat karena realisasi belanja anggaran berkaitan dengan pengelolaan fiskal, bukan tolok ukur kepuasan pengguna jasa.</li><li><strong>D</strong>: Kurang tepat karena pemenuhan kuota jam kerja semata mengabaikan mutu empati dan kepuasan penerima layanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memenuhi kepuasan para pemangku kepentingan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Lima karakteristik baku dalam pelaksanaan nilai Pelayanan menurut pedoman perilaku kementerian adalah...", "options": ["Dilaksanakan dengan sepenuh hati, transparan, cepat, akurat, dan aman", "Bersifat rahasia, bertahap, selektif, ekonomis, dan terencana", "Berorientasi komersial, terukur, mandiri, fleksibel, dan tertutup", "Dilakukan secara berkala, serentak, terpusat, formal, dan bersyarat", "Mengutamakan kuantitas, fleksibilitas, kenyamanan, kesetaraan, dan profit"], "answer": 0, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Pelaksanaan Nilai Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Pelayanan dilaksanakan dengan lima kriteria utama: <strong>dilaksanakan dengan sepenuh hati, transparan, cepat, akurat, dan aman</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pedoman etika pelayanan merumuskan lima kata sifat kunci yang menjadi tolok ukur interaksi aparatur dengan pengguna layanan. Keikhlasan (sepenuh hati), keterbukaan prosedur (transparan), responsivitas waktu (cepat), ketepatan solusi (akurat), serta perlindungan hukum dan fisik (aman) menjamin kualitas pelayanan berstandar unggul. Kelima unsur ini saling melengkapi dalam mewujudkan tata kelola birokrasi modern. Oleh karenanya, kelima karakter tersebut merupakan rumusan resmi yang benar.</p><p>Jawaban tepat: <strong>A. Dilaksanakan dengan sepenuh hati, transparan, cepat, akurat, dan aman</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena pelayanan publik berlandaskan keterbukaan informasi, bukan kerahasiaan dan sifat selektif tertutup.</li><li><strong>C</strong>: Kurang tepat karena orientasi komersial bertentangan langsung dengan fungsi pelayanan publik non-profit abdi negara.</li><li><strong>D</strong>: Kurang tepat karena pelayanan prima diberikan secara berkesinambungan setiap hari kerja, bukan sekadar agenda berkala.</li><li><strong>E</strong>: Kurang tepat karena mengejar keuntungan finansial (profit) menyalahi mandat konstitusi tugas pelayanan ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sepenuh hati, transparan, cepat, akurat, dan aman</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Sikap etis petugas pelayanan saat menjalankan sistem kerja fleksibel dari kediaman adalah...", "options": ["Mengarahkan seluruh pemohon izin untuk datang kembali pekan depan", "Mematikan gawai layanan interaktif setelah menyelesaikan jam kerja wajib", "Menyampaikan kepada pemohon bahwa verifikasi dokumen dialihkan ke loket", "Tetap melayani kebutuhan pemangku kepentingan tanpa menjadikan WFH kendala", "Menunda berkas permohonan yang diajukan mendekati batas jam kerja dinas"], "answer": 3, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komitmen Pelayanan Publik pada Saat WFH</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib menjaga integritas layanan dengan <strong>tidak menggunakan alasan WFH sebagai alasan untuk tidak melayani kepentingan masyarakat/stakeholders</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena penerapan sistem kerja fleksibel sama sekali tidak boleh mengurangi mutu, kecepatan, maupun ketersediaan layanan publik yang menjadi hak pemangku kepentingan. Aparatur dituntut mencari solusi kreatif dan memanfaatkan saluran layanan elektronik secara prima agar kebutuhan masyarakat tetap terakomodasi dengan baik. Dedikasi ini membuktikan profesionalisme birokrasi yang responsif terhadap situasi adaptif. Maka dari itu, pantang menjadikan status WFH sebagai alasan kelambanan layanan.</p><p>Jawaban tepat: <strong>D. Tetap melayani kebutuhan pemangku kepentingan tanpa menjadikan WFH kendala</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena menunda pemrosesan berkas masyarakat merugikan kepastian hukum dan waktu pengguna layanan.</li><li><strong>B</strong>: Kurang tepat karena memutus akses komunikasi tanpa alternatif bantuan menunjukkan rendahnya empati pelayanan dinas.</li><li><strong>C</strong>: Kurang tepat karena melempar urusan perizinan daring kembali ke loket fisik bertentangan dengan kemudahan layanan.</li><li><strong>E</strong>: Kurang tepat karena menolak berkas pemohon yang masuk dalam jam operasional menyalahi janji standar pelayanan publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berupaya mencari berbagai alternatif dalam rangka</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam memberikan pelayanan langsung kepada masyarakat pada masa penyesuaian tatanan kerja, pegawai wajib...", "options": ["Membatasi pemberian informasi teknis melalui media selebaran kantor", "Mengharuskan masyarakat membawa surat rekomendasi dari aparat lingkungan", "Menyerahkan proses penerimaan berkas kepada pihak keamanan kantor depan", "Mengalihkan operasional loket fisik sepenuhnya ke saluran surel khusus", "Menerapkan prosedur kesehatan/keselamatan dalam pelayanan secara konsisten"], "answer": 4, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Standar Keselamatan dalam Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setiap pegawai wajib <strong>menerapkan prosedur kesehatan/keselamatan dalam pelayanan</strong> demi menjaga kenyamanan bersama.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena penyelenggaraan pelayanan publik yang unggul harus menjamin perlindungan fisik, kesehatan, dan keselamatan bagi pemohon jasa maupun petugas loket. Kepatuhan pada standar higienitas dan protokol keselamatan di area kantor mencerminkan kepedulian institusi terhadap keselamatan jiwa manusia. Rasa aman ini menciptakan ketenangan interaksi dalam pemberian layanan kedinasan. Oleh sebab itu, menerapkan prosedur kesehatan dan keselamatan secara konsisten adalah tindakan yang benar.</p><p>Jawaban tepat: <strong>E. Menerapkan prosedur kesehatan/keselamatan dalam pelayanan secara konsisten</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena membatasi penjelasan lisan menurunkan kejelasan informasi yang dibutuhkan pemohon layanan.</li><li><strong>B</strong>: Kurang tepat karena menambah persyaratan surat rekomendasi liar menciptakan birokrasi berbelit yang membebani warga.</li><li><strong>C</strong>: Kurang tepat karena petugas keamanan tidak berwenang melakukan verifikasi materiil berkas permohonan dinas.</li><li><strong>D</strong>: Kurang tepat karena memungut biaya atas saluran surat elektronik melanggar asas pelayanan publik yang bebas pungli.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menerapkan prosedur kesehatan/keselamatan dalam</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Makna substantif dari nilai Kesempurnaan dalam tatanan nilai kementerian menuntut aparatur agar...", "options": ["Senantiasa melakukan upaya perbaikan di segala bidang untuk hasil terbaik", "Mempertahankan metode kerja konvensional yang dinilai telah mapan", "Mempertahankan prosedur kerja warisan masa lalu tanpa perlu evaluasi", "Merasa puas atas pencapaian kinerja tahunan yang telah melampaui target", "Membatasi diri dari partisipasi program inovasi dan perbaikan mutu"], "answer": 0, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Nilai Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Kesempurnaan menegaskan komitmen seluruh pegawai untuk <strong>senantiasa melakukan upaya perbaikan di segala bidang untuk menjadi dan memberikan yang terbaik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kesempurnaan bukanlah kondisi statis tanpa cela, melainkan semangat pantang menyerah untuk terus berevolusi melakukan continuous improvement. Aparatur yang menjiwai nilai ini selalu mengevaluasi proses kerja yang ada, menciptakan inovasi penyederhanaan layanan, dan memperbarui kompetensi diri agar relevan dengan tuntutan zaman. Nilai ini menjadi lokomotif kemajuan organisasi. Dengan demikian, senantiasa melakukan perbaikan di segala bidang adalah esensi nilai kesempurnaan.</p><p>Jawaban tepat: <strong>A. Senantiasa melakukan upaya perbaikan di segala bidang untuk hasil terbaik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena menolak inovasi baru bertentangan langsung dengan semangat adaptabilitas organisasi modern.</li><li><strong>C</strong>: Kurang tepat karena melestarikan tata cara usang tanpa perbaikan menciptakan stagnasi dan inefisiensi birokrasi.</li><li><strong>D</strong>: Kurang tepat karena cepat berpuas diri menghambat dorongan untuk mencapai keunggulan kerja yang lebih tinggi.</li><li><strong>E</strong>: Kurang tepat karena enggan berpartisipasi dalam inovasi mencerminkan resistensi terhadap budaya kemajuan institusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>perbaikan di segala bidang untuk menjadi dan memberikan yang terbaik</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Salah satu bentuk penerapan nilai Kesempurnaan dalam keseimbangan hidup aparatur saat bekerja fleksibel adalah...", "options": ["Mengutamakan hobi pribadi di atas pemenuhan target sasaran kinerja", "Mengharmonisasikan tanggung jawab dinas dan keluarga (work-life harmony)", "Membawa seluruh beban pekerjaan kantor ke dalam waktu istirahat tidur", "Mengorbankan kesehatan jasmani demi menyelesaikan tugas melampaui jam", "Menyerahkan pengasuhan keluarga sepenuhnya kepada pihak lembaga asuhan"], "answer": 1, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Work-Life Harmony dalam Kesempurnaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan nilai Kesempurnaan mencakup upaya <strong>mengintegrasikan/mengharmonisasikan tanggung jawab terhadap kantor dan tanggung terhadap keluarga/pribadi dengan baik/work-life harmony</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena keseimbangan antara tuntutan profesionalitas kantor dan kebahagiaan kehidupan rumah tangga melahirkan kesehatan mental yang prima dan energi kerja berkelanjutan. Pegawai yang mampu mengelola batasan waktu secara bijak akan bekerja dengan produktivitas tinggi saat jam dinas tanpa mengabaikan pemenuhan hak keluarga tercinta. Harmoni hidup ini mendukung performa kerja jangka panjang yang unggul. Oleh karenanya, membangun work-life harmony adalah cerminan nilai kesempurnaan.</p><p>Jawaban tepat: <strong>B. Mengharmonisasikan tanggung jawab dinas dan keluarga (work-life harmony)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena menomorsatukan hobi pribadi saat jam kerja dinas melanggar disiplin dan kontrak kinerja ASN.</li><li><strong>C</strong>: Kurang tepat karena membiarkan beban kantor mengikis waktu istirahat berisiko menimbulkan kejenuhan (burnout) kerja.</li><li><strong>D</strong>: Kurang tepat karena merusak kesehatan fisik demi lembur berlebihan bertentangan dengan prinsip keselamatan kerja terpadu.</li><li><strong>E</strong>: Kurang tepat karena melepaskan tanggung jawab domestik secara total bukan solusi bijak dalam mencapai keselarasan hidup.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tanggung terhadap keluarga/pribadi dengan baik/work-life harmony</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Puncak dari penghayatan nilai Kesempurnaan tercapai apabila setiap aparatur negara mampu...", "options": ["Mengumpulkan gelar akademik tertinggi dari perguruan tinggi ternama", "Mencapai promosi jabatan struktural pada usia karier paling muda", "Memberikan integritas, profesionalisme, sinergi, dan pelayanan terbaik", "Membatasi eksposur risiko perubahan tata kelola di unit kerja", "Mencatatkan kehadiran presensi finger print tanpa pernah terlambat"], "answer": 2, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Integrasi Holistik Nilai-Nilai Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesempurnaan mewadahi komitmen holistik: <strong>memberikan integritas yang terbaik, menunjukkan profesionalisme yang terbaik, melakukan sinergi yang terbaik, memberikan pelayanan yang terbaik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena nilai Kesempurnaan bertindak sebagai akselerator sekaligus payung mutu bagi keempat nilai dasar kementerian lainnya. Dengan bertekad memberikan standar integritas, keahlian profesional, kemitraan sinergi, dan pelayanan publik pada tingkat terbaiknya, pegawai menghidupkan budaya organisasi berkelas dunia secara utuh dan terpadu. Dedikasi total ini menjadi motor kemajuan institusi. Dengan demikian, mengoptimalkan seluruh nilai dasar ke level terbaik adalah puncak kesempurnaan.</p><p>Jawaban tepat: <strong>C. Memberikan integritas, profesionalisme, sinergi, dan pelayanan terbaik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena banyaknya gelar pendidikan formal tidak otomatis mencerminkan kualitas pengamalan nilai organisasi.</li><li><strong>B</strong>: Kurang tepat karena kecepatan promosi jabatan bersifat capaian karier individual, bukan tolok ukur nilai kesempurnaan.</li><li><strong>D</strong>: Kurang tepat karena menjauhi risiko secara ekstrem justru mematikan inovasi dan perbaikan mutu berkelanjutan.</li><li><strong>E</strong>: Kurang tepat karena ketepatan presensi mesin semata bersifat pemenuhan syarat fisik tanpa mencerminkan mutu karya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 30) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memberikan integritas yang terbaik, menunjukkan profesionalisme yang terbaik</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Bagi setiap aparatur sipil negara, etika, kode etik, dan kode perilaku berkedudukan sebagai...", "options": ["Persyaratan formal kelulusan masa prajabatan calon pegawai negeri", "Pedoman opsional yang dapat disesuaikan dengan kepentingan pribadi", "Landasan moral dan profesional yang harus dipegang teguh abdi negara", "Rangkaian sanksi administratif dalam peraturan internal kepegawaian", "Dokumen pelengkap dalam penyusunan laporan sasaran kinerja pegawai"], "answer": 2, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Etika dan Kode Etik bagi PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Etika, kode etik, dan kode perilaku merupakan <strong>landasan moral dan profesional yang harus dipegang teguh oleh setiap PNS</strong> dalam tugasnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena etika bukan sekadar pelengkap administratif, melainkan fondasi batin dan standar kompetensi moral yang menjiwai setiap pembuatan keputusan dinas. Sebagai pelayan masyarakat dan abdi negara, aparatur terikat secara etis untuk menjaga integritas pelaksanaan tugasnya dari segala bentuk penyimpangan moral. Landasan inilah yang menjaga marwah dan kepercayaan publik terhadap birokrasi pemerintah. Oleh karena itu, berkedudukan sebagai landasan moral dan profesional adalah rumusan yang tepat.</p><p>Jawaban tepat: <strong>C. Landasan moral dan profesional yang harus dipegang teguh abdi negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena etika berlaku seumur hidup selama masa bakti dinas, bukan sekadar syarat lulus masa prajabatan.</li><li><strong>B</strong>: Kurang tepat karena norma kode etik bersifat mengikat secara mutlak, bukan pedoman alternatif yang dapat ditawar.</li><li><strong>D</strong>: Kurang tepat karena etika berfokus pada panduan kebajikan internal, bukan semata daftar hukuman disiplin.</li><li><strong>E</strong>: Kurang tepat karena etika merupakan pedoman karakter hidup, bukan sekadar lampiran berkas Sasaran Kinerja Pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>merupakan landasan moral dan profesional yang harus</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pemahaman dan internalisasi nilai-nilai etika menjadi kunci utama bagi pemerintah untuk...", "options": ["Memperluas struktur organisasi kantor vertikal di seluruh wilayah daerah", "Menaikkan perolehan pendapatan negara dari sektor penerimaan non-pajak", "Mengurangi alokasi belanja modal pada sarana teknologi perkantoran", "Membentuk birokrasi yang bersih, berintegritas, dan berorientasi publik", "Membatasi interaksi komunikasi langsung antara pimpinan dan staf bawahan"], "answer": 3, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Internalisasi Nilai Etika Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Internalisasi etika menjadi kunci untuk <strong>membentuk birokrasi yang bersih, berintegritas, dan berorientasi pada kepentingan publik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena keberhasilan reformasi birokrasi tidak hanya ditentukan oleh modernisasi sarana fisik atau digital, melainkan transformasi watak manusia aparatur pelaksananya. Birokrasi yang bersih dari praktik curang, berintegritas tinggi dalam pengelolaan keuangan negara, dan mengutamakan kesejahteraan umum adalah cita-cita luhur pelayanan publik. Fondasi etika yang kuat menjadi benteng utama terwujudnya tata kelola tersebut. Oleh sebab itu, melahirkan birokrasi bersih dan berorientasi publik adalah tujuan utamanya.</p><p>Jawaban tepat: <strong>D. Membentuk birokrasi yang bersih, berintegritas, dan berorientasi publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penambahan kantor vertikal merupakan kebijakan struktur organisasi, bukan sasaran internalisasi etika.</li><li><strong>B</strong>: Kurang tepat karena target pendapatan negara merupakan fungsi teknis fiskal, bukan fokus langsung internalisasi nilai etis.</li><li><strong>C</strong>: Kurang tepat karena pemotongan anggaran teknologi informasi tidak berkaitan dengan substansi penanaman integritas moral.</li><li><strong>E</strong>: Kurang tepat karena membatasi dialog hierarki kerja bertentangan dengan prinsip keterbukaan dan kepemimpinan suportif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>membentuk birokrasi yang bersih, berintegritas, dan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pernyataan yang tepat mengenai hakikat keberadaan kode etik dan kode perilaku aparatur adalah...", "options": ["Merupakan instrumen penyeragaman busana kedinasan harian para pegawai", "Pedoman teknis penyusunan standar operasional prosedur unit kerja", "Bentuk pembatasan hak berpendapat aparatur dalam ruang publik masyarakat", "Dokumen pelengkap yang wajib diperbarui pada setiap pergantian menteri", "Kode etik dan kode perilaku bukanlah sekadar aturan formal belaka"], "answer": 4, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Kode Etik Bukan Formalitas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kode etik dan kode perilaku bukanlah sekadar aturan formal</strong>, melainkan pedoman sikap hidup yang mencerminkan nilai dasar dan budaya luhur.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena norma etik tidak boleh direduksi menjadi sekadar deretan pasal teks mati di atas kertas dokumen hukum. Kode etik menuntut internalisasi mendalam ke dalam lubuk sanubari setiap aparatur agar mewujud menjadi dorongan moral spontan dalam membedakan hal yang patut dan tidak patut dilakukan. Penghayatan ini membedakan ketaatan tulus dari kepatuhan semu. Maka, menegaskan bahwa kode etik bukan sekadar formalitas adalah pernyataan yang benar.</p><p>Jawaban tepat: <strong>E. Kode etik dan kode perilaku bukanlah sekadar aturan formal belaka</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penyeragaman seragam kerja diatur dalam regulasi tata tertib pakaian, bukan esensi kode etik.</li><li><strong>B</strong>: Kurang tepat karena penyusunan SOP merupakan instrumen ketatalaksanaan teknis proses bisnis kelembagaan.</li><li><strong>C</strong>: Kurang tepat karena kode etik membimbing tata krama komunikasi yang sehat, bukan memasung kebebasan berpendapat yang sah.</li><li><strong>D</strong>: Kurang tepat karena keluhuran norma etika bersifat universal dan abadi, tidak bergantung pada siklus pergantian pejabat politik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kode etik dan kode perilaku bukanlah sekadar aturan formal</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Fungsi mendasar kode etik dan kode perilaku adalah sebagai pedoman sikap dan perilaku yang...", "options": ["Mencerminkan nilai dasar ASN dan budaya organisasi secara utuh", "Mengatur mekanisme kenaikan pangkat otomatis bagi seluruh staf", "Menetapkan besaran tunjangan kinerja jabatan fungsional kementerian", "Membatasi mobilitas karier aparatur sipil antardaerah penugasan", "Mengatur tata cara pemberian sanksi pidana umum bagi warga sipil"], "answer": 0, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik sebagai Cerminan Nilai Dasar ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kode etik berfungsi sebagai pedoman sikap dan perilaku yang <strong>mencerminkan nilai dasar ASN dan budaya organisasi</strong> dalam melayani bangsa.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena setiap butir panduan perilaku etis diturunkan langsung dari nilai-nilai luhur korps aparatur dan budaya kerja institusi. Kode etik menjadi jembatan praktis yang mengubah filosofi nilai abstrak menjadi tindakan nyata yang dapat diamati dan dievaluasi dalam dinamika kerja kedinasan sehari-hari. Cerminan ini menegaskan identitas moral abdi negara. Oleh sebab itu, merefleksikan nilai dasar dan budaya kerja organisasi adalah fungsi pokoknya.</p><p>Jawaban tepat: <strong>A. Mencerminkan nilai dasar ASN dan budaya organisasi secara utuh</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena tata cara kenaikan pangkat diatur dalam regulasi pola karier dan manajemen kepegawaian BKN.</li><li><strong>C</strong>: Kurang tepat karena penetapan nominal tunjangan kinerja merupakan domain peraturan presiden mengenai remunerasi.</li><li><strong>D</strong>: Kurang tepat karena pola mutasi dan perpindahan tugas daerah diatur dalam manajemen talenta nasional.</li><li><strong>E</strong>: Kurang tepat karena sanksi pidana warga negara diatur dalam Kitab Undang-Undang Hukum Pidana, bukan kode etik dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mencerminkan nilai dasar ASN dan budaya organisasi</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam situasi kerja birokrasi modern yang terus berkembang dinamis, nilai-nilai etis seperti...", "options": ["Sentralisasi wewenang justru semakin digalakkan oleh jajaran pimpinan", "Kedisiplinan, tanggung jawab, dan integritas justru menjadi semakin penting", "Kepatuhan prosedural formalitas diutamakan melampaui hasil kerja", "Senioritas masa kerja menjadi pertimbangan mutlak penentuan promosi", "Kerahasiaan data kepegawaian ditiadakan demi keterbukaan publik bebas"], "answer": 1, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Relevansi Etika dalam Perubahan Zaman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Di era perkembangan situasi kerja, <strong>kedisiplinan, tanggung jawab, dan integritas justru menjadi semakin penting</strong> bagi setiap aparatur.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena perkembangan teknologi dan fleksibilitas pola kerja modern menghadirkan tantangan baru berupa berkurangnya pengawasan fisik langsung oleh atasan. Dalam kondisi otonomi kerja yang luas ini, komitmen disiplin diri, tanggung jawab hasil keluaran, dan keteguhan integritas menjadi faktor penentu utama agar integritas organisasi tidak runtuh. Nilai-nilai inilah yang menjamin produktivitas tetap prima di era digital. Karena itu, ketiga nilai tersebut menjadi semakin krusial dalam birokrasi modern.</p><p>Jawaban tepat: <strong>B. Kedisiplinan, tanggung jawab, dan integritas justru menjadi semakin penting</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena era modern menuntut desentralisasi wewenang yang tangkas (agile), bukan pemusatan kaku.</li><li><strong>C</strong>: Kurang tepat karena birokrasi kekinian berorientasi pada pencapaian dampak (outcome) nyata, bukan kekakuan tata cara semata.</li><li><strong>D</strong>: Kurang tepat karena manajemen talenta masa kini berbasis meritokrasi kompetensi kinerja, bukan urutan masa kerja.</li><li><strong>E</strong>: Kurang tepat karena data personal pegawai tetap dilindungi secara ketat oleh hukum perlindungan data pribadi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kedisiplinan, tanggung jawab, dan integritas justru menjadi semakin</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penerapan etika aparatur sipil negara yang utuh dan seimbang wajib tercermin dalam...", "options": ["Kemampuan menguasai beragam bahasa asing dan bahasa pemrograman", "Kepemilikan aset kekayaan pribadi yang dilaporkan tepat pada waktunya", "Jumlah sertifikat keikutsertaan bimbingan teknis yang dikumpulkan staf", "Kerapian tata naskah dinas persuratan yang terkirim ke unit wilayah", "Cara berpikir, bersikap, dan bertindak dalam rutinitas kerja harian"], "answer": 4, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Dimensi Penerapan Etika Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Prinsip etika kepegawaian harus <strong>tercermin dalam cara berpikir, bersikap, dan bertindak</strong> aparatur di setiap saat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena integritas moral menuntut keselarasan tiga ranah personal aparatur: kognitif (pola pikir yang objektif dan bersih), afektif (sikap batin yang penuh integritas dan empati), serta psikomotorik (tindakan nyata yang patuh pada aturan hukum). Pemisahan salah satu dimensi akan melahirkan kepatuhan semu atau kemunafikan perilaku. Keterpaduan cipta, rasa, dan karsa inilah yang membentuk karakter unggul abdi negara. Dengan demikian, tercermin dalam pola pikir, sikap, dan tindakan nyata adalah rumusan yang tepat.</p><p>Jawaban tepat: <strong>E. Cara berpikir, bersikap, dan bertindak dalam rutinitas kerja harian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penguasaan bahasa asing merupakan keahlian teknis individual, bukan cerminan langsung etika batiniah.</li><li><strong>B</strong>: Kurang tepat karena laporan harta kekayaan (LHKPN) adalah instrumen kepatuhan, bukan cerminan komprehensif seluruh ranah etika.</li><li><strong>C</strong>: Kurang tepat karena sertifikat pelatihan sekadar bukti administratif diklat tanpa menjamin perubahan sikap nyata.</li><li><strong>D</strong>: Kurang tepat karena kerapian tata naskah merupakan kompetensi kesekretariatan yang bersifat prosedural teknis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tercermin dalam cara berpikir, bersikap, dan bertindak</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ruang lingkup penegakan etika aparatur sipil negara di era transformasi digital berlaku pada...", "options": ["Baik saat berada dalam lingkungan kerja fisik maupun ruang digital", "Saat pegawai bertugas langsung di dalam gedung perkantoran dinas", "Khusus saat berlangsung audit kepatuhan oleh instansi pengawas intern", "Terbatas pada jam operasional pelayanan tatap muka dengan warga", "Saat menghadiri kegiatan seremonial peringatan hari besar nasional"], "answer": 0, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ruang Lingkup Penerapan Etika Modern</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kepatuhan etika harus dijaga secara konsisten <strong>baik saat berada dalam lingkungan kerja fisik maupun digital</strong> di setiap waktu.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kehadiran teknologi informasi tidak menghapus batasan etis perilaku seorang abdi negara. Sikap sopan, kejujuran dalam pelaporan data, kerahasiaan informasi jabatan, serta penghormatan martabat sesama berlaku setara di dalam gedung kantor maupun saat berinteraksi di ruang rapat virtual dan media sosial. Integritas aparatur melekat erat tanpa batas sekat medium komunikasi. Oleh sebab itu, berlaku pada lingkungan fisik maupun digital adalah ketetapan yang benar.</p><p>Jawaban tepat: <strong>A. Baik saat berada dalam lingkungan kerja fisik maupun ruang digital</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena membatasi etika hanya di dalam gedung kantor melegalkan perilaku tidak pantas di ruang maya.</li><li><strong>C</strong>: Kurang tepat karena penegakan etika merupakan panggilan jiwa harian, bukan sekadar respons kepatuhan saat diaudit.</li><li><strong>D</strong>: Kurang tepat karena aparatur tetap terikat kode etik dalam pelaksanaan tugas back-office di luar loket pelayanan.</li><li><strong>E</strong>: Kurang tepat karena etika bukan pakaian pesta yang hanya dikenakan saat momen seremonial upacara kenegaraan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dalam lingkungan kerja fisik maupun digital</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Bagi seorang pegawai aparatur sipil negara, derajat kepatuhan terhadap etika mencerminkan...", "options": ["Keberhasilan menempuh pendidikan penjenjangan karier pamong praja", "Ukuran kematangan pribadi dan tingkat profesionalisme seorang pegawai", "Tingginya pengaruh kekuasaan informal dalam jejaring birokrasi lokal", "Kemampuan melakukan lobi strategis demi percepatan promosi jabatan", "Besarnya jumlah relasi kerja dengan kalangan pengusaha komersial"], "answer": 1, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etika sebagai Ukuran Kematangan Pribadi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kepatuhan terhadap etika menjadi ukuran kematangan pribadi dan profesionalisme seorang pegawai</strong> dalam menjalankan amanah publik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kepatuhan etika yang konsisten membuktikan penguasaan diri yang matang serta kedewasaan emosional aparatur dalam menghadapi godaan kekuasaan atau materi. Pegawai yang profesional menempatkan kehormatan institusi dan aturan hukum di atas hawa nafsu dan kepentingan kelompok. Ukuran kematangan inilah yang membedakan aparatur berintegritas sejati dari individu yang sekadar mengejar status kedinasan. Maka dari itu, tolok ukur kematangan pribadi dan profesionalitas adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Ukuran kematangan pribadi dan tingkat profesionalisme seorang pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kelulusan diklat kepemimpinan tidak selalu berbanding lurus dengan kematangan karakter etis.</li><li><strong>C</strong>: Kurang tepat karena pengaruh lobi informal tidak mencerminkan integritas moral aparatur sesuai kaidah etika.</li><li><strong>D</strong>: Kurang tepat karena lobi politik demi promosi jabatan kilat justru menciderai prinsip meritokrasi dan netralitas.</li><li><strong>E</strong>: Kurang tepat karena keakraban dengan mitra pengusaha swasta berpotensi menimbulkan benturan kepentingan tugas kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kematangan pribadi dan profesionalisme seorang pegawai</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ajakan luhur dalam penutup modul etika mengimbau seluruh aparatur agar menjadikan etika sebagai...", "options": ["Instrumen proteksi diri dari jeratan pemeriksaan aparat penegak hukum", "Alat seleksi penentuan besaran potongan iuran korps pegawai negeri", "Kompas moral dalam bertugas dan pedoman penguat karakter birokrasi", "Media diplomasi dalam memperjuangkan kenaikan alokasi anggaran satker", "Sarana formalitas administrasi demi melengkapi dokumen akreditasi unit"], "answer": 2, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Etika sebagai Kompas Moral</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setiap abdi negara diajak untuk <strong>menjadikan etika sebagai kompas moral dalam bertugas, dan kode etik sebagai pedoman sikap yang memperkuat karakter birokrasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena metafora kompas moral menegaskan fungsi pemandu arah bagi pegawai ketika berada dalam persimpangan dilema etika atau tekanan kepentingan yang membingungkan. Berpegang pada kompas moral memastikan setiap langkah, kebijakan, dan keputusan dinas selalu mengarah pada kebenaran hukum, kemaslahatan masyarakat, dan keluhuran martabat bangsa. Hal ini memperkokoh jatidiri birokrasi berwibawa. Dengan demikian, etika sebagai kompas moral adalah rumusan yang benar.</p><p>Jawaban tepat: <strong>C. Kompas moral dalam bertugas dan pedoman penguat karakter birokrasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena etika bukan tameng hukum untuk membela diri, melainkan bimbingan pencegahan berbuat salah.</li><li><strong>B</strong>: Kurang tepat karena iuran korps diatur dalam anggaran dasar dan anggaran rumah tangga organisasi kepegawaian.</li><li><strong>D</strong>: Kurang tepat karena negosiasi pagu anggaran merupakan tata kelola keuangan negara, bukan peran hakiki etika moral.</li><li><strong>E</strong>: Kurang tepat karena mereduksi etika menjadi sekadar syarat akreditasi mengabaikan nilai keluhuran akhlak aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kompas moral dalam bertugas</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dengan menjunjung tinggi nilai-nilai etika, kementerian membangun organisasi yang tidak hanya cerdas dan adaptif, tetapi juga...", "options": ["Mampu memonopoli seluruh kewenangan regulasi ekonomi nasional", "Memiliki kekuasaan mutlak dalam mengendalikan lembaga penegak hukum", "Terbebas dari kewajiban pertanggungjawaban kepada dewan perwakilan", "Bermartabat dan dapat dipercaya oleh seluruh lapisan masyarakat luas", "Menjadi instansi dengan alokasi belanja operasional terbesar negara"], "answer": 3, "source": "Etika PNS", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Visi Birokrasi Masa Depan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan etika bertujuan membangun organisasi yang <strong>tidak hanya cerdas dan adaptif, tetapi juga bermartabat dan dapat dipercaya</strong> oleh publik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kecerdasan intelektual dan ketangkasan digital suatu institusi pemerintah menjadi tidak bernilai tanpa disertai martabat moral dan legitimasi kepercayaan publik. Kepercayaan (public trust) adalah modal sosial paling berharga bagi kementerian dalam menjalankan tugas pengelolaan fiskal dan pembangunan nasional. Dengan menjunjung etika luhur, institusi berdiri kokoh dan dihormati oleh seluruh rakyat. Oleh sebab itu, mewujudkan organisasi bermartabat dan dapat dipercaya adalah visi mulia penutup modul.</p><p>Jawaban tepat: <strong>D. Bermartabat dan dapat dipercaya oleh seluruh lapisan masyarakat luas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena memonopoli kekuasaan regulasi bertentangan dengan prinsip demokrasi dan pembagian wewenang.</li><li><strong>B</strong>: Kurang tepat karena lembaga eksekutif tidak boleh mengintervensi atau mendominasi otoritas yudikatif negara.</li><li><strong>C</strong>: Kurang tepat karena setiap instansi pemerintah wajib tunduk pada akuntabilitas pengawasan wakil rakyat.</li><li><strong>E</strong>: Kurang tepat karena besaran belanja operasional kantor bukan indikator kehormatan dan kemuliaan tata kelola birokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika PNS (Hlm. 31) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bermartabat dan dapat dipercaya</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_etika_p03_review_annot_v1_user_<?= (int)$USER->id ?>';
let reviewAnnoStore={};
// Data anotasi dimuat dari Moodle agar bisa dibuka dari perangkat berbeda.
let reviewAnnoActive=null,reviewAnnoTool='pen',reviewAnnoDrawing=false,reviewAnnoStroke=null;
const raSaveTimers={};
const raSavePending={};
let raServerReady=false;
async function raLoadServer(){
 try {
   const r=await fetch(`${LEADERBOARD_ENDPOINT}?action=anno_load`,{credentials:'same-origin',cache:'no-store'});
   const data=await r.json();
   if(!r.ok||!data.success)throw new Error(data.message||'Gagal memuat anotasi');
   reviewAnnoStore=data.annotations||{};
   raServerReady=true;
   // Gambar coretan yang sudah tersimpan SECARA OTOMATIS, tanpa menekan
   // tombol Coret/Anotasi. Canvas tetap pasif agar halaman mudah discroll.
   raRestoreAnnotations();
 }catch(e){console.error('Anotasi server:',e);alert('Anotasi belum dapat dimuat dari server. Coba buka ulang pembahasan.');}
}
async function raSendSave(i){
 if(!raServerReady)return;
 const b=new URLSearchParams();b.set('action','anno_save');b.set('sesskey',MOODLE_SESSKEY);
 b.set('question',String(i));b.set('strokesjson',JSON.stringify(raStrokes(i)));
 try {const r=await fetch(LEADERBOARD_ENDPOINT,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:b.toString()});
 const d=await r.json();if(!r.ok||!d.success)throw new Error(d.message||'Gagal menyimpan');}
 catch(e){console.error('Gagal menyimpan anotasi nomor',i+1,e);alert('Coretan nomor '+(i+1)+' belum tersimpan. Periksa koneksi dan coba coret lagi.');}
}
function raSave(i){clearTimeout(raSaveTimers[i]);raSaveTimers[i]=setTimeout(()=>{delete raSaveTimers[i];raSavePending[i]=raSendSave(i).finally(()=>{delete raSavePending[i];});},350);}

// Coretan dibuka otomatis saat review ditampilkan. Canvas yang belum
// terlihat baru diinisialisasi saat masuk viewport (hemat memori untuk 100 soal).
let raReviewObserver=null;
function raRestoreAnnotations(){
 if(raReviewObserver){raReviewObserver.disconnect();raReviewObserver=null;}
 const saved=Object.keys(reviewAnnoStore).filter(k=>Array.isArray(reviewAnnoStore[k]) && reviewAnnoStore[k].length>0);
 const makeVisible=(i)=>{
   const c=raCanvas(i);
   if(!c)return;
   if(!c.dataset.initialized)raInit(i);
   else raResize(i);
 };
 if(typeof IntersectionObserver==='undefined'){
   saved.forEach(k=>makeVisible(Number(k)));
 }else{
   raReviewObserver=new IntersectionObserver(entries=>{
     entries.forEach(entry=>{
       if(!entry.isIntersecting)return;
       const i=Number(entry.target.dataset.question);
       makeVisible(i);
       raReviewObserver.unobserve(entry.target);
     });
   },{rootMargin:'280px 0px'});
   saved.forEach(k=>{
     const c=raCanvas(Number(k));
     if(c)raReviewObserver.observe(c);
   });
 }
 document.querySelectorAll('.review-annotation-canvas[data-initialized]').forEach(c=>raResize(Number(c.dataset.question)));
}
function raStrokes(i){return reviewAnnoStore[String(i)]||(reviewAnnoStore[String(i)]=[]);}
function raCard(i){return document.getElementById('reviewQuestion-'+i);}
function raCanvas(i){return document.getElementById('reviewAnnotationCanvas-'+i);}
function raResize(i){
 const canvas=raCanvas(i),card=raCard(i);if(!canvas||!card||!canvas.dataset.initialized)return;
 const rect=card.getBoundingClientRect(),scale=Math.min(devicePixelRatio||1,2);
 if(!rect.width||!rect.height)return;
 const w=Math.round(rect.width*scale),h=Math.round(rect.height*scale);
 if(canvas.width!==w||canvas.height!==h){canvas.width=w;canvas.height=h;}
 raRedraw(i);
}
function raRedraw(i){
 const canvas=raCanvas(i);if(!canvas||!canvas.width)return;
 const ctx=canvas.getContext('2d'),w=canvas.width,h=canvas.height;
 ctx.clearRect(0,0,w,h);
 for(const stroke of raStrokes(i)){
  const pts=stroke.points||[];if(!pts.length)continue;
  ctx.save();ctx.lineCap='round';ctx.lineJoin='round';
  ctx.globalCompositeOperation=stroke.tool==='eraser'?'destination-out':'source-over';
  ctx.globalAlpha=stroke.tool==='marker'?.38:1;
  ctx.strokeStyle=stroke.tool==='marker'?'#ffcd22':'#e43d65';
  ctx.lineWidth=(stroke.tool==='eraser'?26:stroke.tool==='marker'?18:3.5)*(w/Math.max(canvas.getBoundingClientRect().width,1));
  ctx.beginPath();ctx.moveTo(pts[0][0]*w,pts[0][1]*h);
  if(pts.length===1)ctx.lineTo(pts[0][0]*w+.2,pts[0][1]*h+.2);
  for(let j=1;j<pts.length;j++)ctx.lineTo(pts[j][0]*w,pts[j][1]*h);
  ctx.stroke();ctx.restore();
 }
}
function raInit(i){
 const canvas=raCanvas(i);if(!canvas||canvas.dataset.initialized)return;
 canvas.dataset.initialized='1';
 canvas.addEventListener('pointerdown',e=>{
  if(reviewAnnoActive!==i||!raServerReady)return;
  e.preventDefault();reviewAnnoDrawing=true;
  const r=canvas.getBoundingClientRect();
  reviewAnnoStroke={tool:reviewAnnoTool,points:[[Math.max(0,Math.min(1,(e.clientX-r.left)/r.width)),Math.max(0,Math.min(1,(e.clientY-r.top)/r.height))]]};
  raStrokes(i).push(reviewAnnoStroke);
  try{canvas.setPointerCapture(e.pointerId);}catch(_){}raRedraw(i);
 });
 canvas.addEventListener('pointermove',e=>{
  if(!reviewAnnoDrawing||reviewAnnoActive!==i||!reviewAnnoStroke)return;
  e.preventDefault();const r=canvas.getBoundingClientRect();
  reviewAnnoStroke.points.push([Math.max(0,Math.min(1,(e.clientX-r.left)/r.width)),Math.max(0,Math.min(1,(e.clientY-r.top)/r.height))]);
  raRedraw(i);
 });
 const finish=()=>{if(reviewAnnoDrawing){reviewAnnoDrawing=false;reviewAnnoStroke=null;raSave(i);raToolbar(i);}};
 canvas.addEventListener('pointerup',finish);canvas.addEventListener('pointercancel',finish);canvas.addEventListener('lostpointercapture',finish);
 raResize(i);
}
function raToolbar(i){
 const card=raCard(i);if(!card)return;
 const on=reviewAnnoActive===i;card.classList.toggle('annotating',on);
 const toolbar=card.querySelector('.review-annotation-toolbar');
 const buttons=toolbar.querySelectorAll('button');
 buttons[0].textContent=on?'✅ Selesai mencoret':'✏️ Coret / Anotasi';buttons[0].classList.toggle('active',on);
 for(let j=1;j<=3;j++){buttons[j].disabled=!on;buttons[j].classList.toggle('active',on&&reviewAnnoTool===['pen','marker','eraser'][j-1]);}
 buttons[4].disabled=!on||!raStrokes(i).length;
 buttons[5].disabled=!on||!raStrokes(i).length;
 toolbar.querySelector('small').textContent=on?'Coret dengan mouse atau jari. Tekan Selesai mencoret agar halaman dapat digulir seperti biasa.':'Alat anotasi khusus pembahasan. Coretan tersimpan pada akun Stangolden dan dapat dibuka lintas perangkat.';
}
function raToggle(i){
 reviewAnnoDrawing=false;reviewAnnoStroke=null;
 const prior=reviewAnnoActive;reviewAnnoActive=prior===i?null:i;
 if(prior!==null&&prior!==i)raToolbar(prior);
 if(reviewAnnoActive===i){if(!raServerReady){alert('Menunggu data anotasi dari server. Silakan coba lagi.');reviewAnnoActive=null;}else raInit(i);}
 raToolbar(i);
}
function raTool(i,t){if(reviewAnnoActive!==i)return;reviewAnnoTool=t;raToolbar(i);}
function raUndo(i){if(reviewAnnoActive!==i)return;raStrokes(i).pop();raSave(i);raRedraw(i);raToolbar(i);}
function raClear(i){if(reviewAnnoActive!==i||!confirm('Hapus semua coretan pada pembahasan soal ini?'))return;reviewAnnoStore[String(i)]=[];raSave(i);raRedraw(i);raToolbar(i);}
function raButtons(i){return `<div class="review-annotation-toolbar">
 <button type="button" onclick="raToggle(${i})">✏️ Coret / Anotasi</button>
 <button type="button" onclick="raTool(${i},'pen')" disabled>✍️ Pena</button>
 <button type="button" onclick="raTool(${i},'marker')" disabled>🖍️ Stabilo</button>
 <button type="button" onclick="raTool(${i},'eraser')" disabled>⌫ Penghapus</button>
 <button type="button" onclick="raUndo(${i})" disabled>↶ Urungkan</button>
 <button type="button" onclick="raClear(${i})" disabled>🗑️ Hapus coretan</button>
 <small>Alat anotasi khusus pembahasan. Coretan tersimpan pada akun Stangolden dan dapat dibuka lintas perangkat.</small>
 </div>`;}
if(typeof ResizeObserver!=='undefined'){
 window.addEventListener('DOMContentLoaded',()=>{
  const target=document.getElementById('resultContent');if(!target)return;
  const observer=new ResizeObserver(()=>{if(reviewAnnoActive!==null)raResize(reviewAnnoActive);});
  observer.observe(target);
 });
}
window.addEventListener('resize',()=>{
 document.querySelectorAll('.review-annotation-canvas[data-initialized]').forEach(c=>raResize(Number(c.dataset.question)));
});

function svgBase(){return `<svg viewBox="0 0 180 140" class="fig-svg"></svg>`;}
function nsEl(tag,attrs={}){const e=document.createElementNS("http://www.w3.org/2000/svg",tag);Object.entries(attrs).forEach(([k,v])=>e.setAttribute(k,v));return e;}
function polyPts(cx,cy,r,n,rot=-90){let p=[];for(let i=0;i<n;i++){const a=(Math.PI/180)*(rot+i*360/n);p.push(`${cx+Math.cos(a)*r},${cy+Math.sin(a)*r}`)}return p.join(" ")}
function drawFig(svg,spec){
 svg.innerHTML="";
 svg.appendChild(nsEl("rect",{x:5,y:5,width:170,height:130,rx:14,fill:"#fff",stroke:"#17131f","stroke-width":3}));
 const fill=spec.fill?"#17131f":"#fff",stroke="#17131f";
 if(spec.sides===4)svg.appendChild(nsEl("rect",{x:55,y:32,width:70,height:70,rx:6,fill,stroke,"stroke-width":spec.fill?0:4}));
 else svg.appendChild(nsEl("polygon",{points:polyPts(90,67,42,spec.sides,-90),fill,stroke,"stroke-width":spec.fill?0:4}));
 const rot={up:270,right:0,down:90,left:180}[spec.dir||"right"],g=nsEl("g",{transform:`rotate(${rot} 90 67)`});
 g.appendChild(nsEl("line",{x1:72,y1:67,x2:102,y2:67,stroke:spec.fill?"#fff":"#17131f","stroke-width":6,"stroke-linecap":"round"}));
 g.appendChild(nsEl("polygon",{points:"112,67 98,57 98,77",fill:spec.fill?"#fff":"#17131f"}));svg.appendChild(g);
 const ps={top:[[75,18],[90,18],[105,18]],bottom:[[75,118],[90,118],[105,118]],left:[[20,52],[20,67],[20,82]],right:[[160,52],[160,67],[160,82]]};
 (ps[spec.dotSide]||[]).slice(0,spec.dots).forEach(([x,y])=>svg.appendChild(nsEl("circle",{cx:x,cy:y,r:4.5,fill:"#7C3FFF"})));
}



















function sourceSvgBoxV29(svgHtml,label=""){
  const box=document.createElement("div");
  box.className="series-hots-frame";
  box.style.minWidth="0";
  box.style.position="relative";
  box.style.padding="6px";
  if(label){
    const lab=document.createElement("span");
    lab.className="seq-label";
    lab.textContent=label;
    box.appendChild(lab);
  }
  const holder=document.createElement("div");
  holder.innerHTML=svgHtml;
  const svg=holder.querySelector("svg");
  if(svg){
    svg.style.width="100%";
    svg.style.height="auto";
    svg.style.display="block";
  }
  box.appendChild(holder);
  return box;
}
function renderSourceStimV29(container,q){
  container.innerHTML="";
  const note=document.createElement("div");
  note.className="fig-note";
  if(q.figMode==="series") note.textContent="Lanjutkan gambar berikut.";
  else if(q.figMode==="odd") note.textContent="Cari gambar yang paling berbeda.";
  else note.textContent="Tentukan gambar yang tepat untuk mengisi kotak kosong.";
  container.appendChild(note);

  if(q.figMode==="series"){
    const board=document.createElement("div");
    board.className="series-hots-board";
    q.stimSvg.forEach((svg,i)=>board.appendChild(sourceSvgBoxV29(svg,String(i+1))));
    container.appendChild(board);
  }else if(q.figMode==="matrix"){
    const board=document.createElement("div");
    board.className="xor-matrix-board";
    q.stimSvg.forEach((svg,i)=>{
      if(svg===null){
        const cell=document.createElement("div");
        cell.className="xor-cell missing";
        cell.innerHTML=`<span class="cell-label">${i+1}</span>?`;
        board.appendChild(cell);
      }else{
        const cell=document.createElement("div");
        cell.className="xor-cell";
        cell.style.position="relative";
        cell.innerHTML=`<span class="cell-label">${i+1}</span><div class="srcsvg"></div>`;
        cell.querySelector(".srcsvg").innerHTML=svg;
        const s=cell.querySelector("svg");
        if(s){s.style.width="100%";s.style.height="auto";s.style.display="block";}
        board.appendChild(cell);
      }
    });
    container.appendChild(board);
  }
}
function renderSourceOptionsV29(opts,q,currentIndex,answerStore,clickable=true){
  const wrap=document.createElement("div");
  wrap.className="fig-options";
  q.optionSvg.forEach((svg,i)=>{
    const box=document.createElement("div");
    box.className="fig-option"+(answerStore===i?" selected":"");
    box.innerHTML=`<span class="flabel">${letters[i]}</span><div class="srcsvg"></div>`;
    box.querySelector(".srcsvg").innerHTML=svg;
    const s=box.querySelector("svg");
    if(s){s.style.width="100%";s.style.height="auto";s.style.display="block";}
    if(clickable) box.onclick=()=>{answers[currentIndex]=i;render();};
    wrap.appendChild(box);
  });
  return wrap;
}


function renderAnalyticalOrder(container){
 container.innerHTML=`
 <div class="fig-note">Visualisasi dua blok yang harus menempati empat posisi pertama:</div>
 <div class="logic-row">
   <span>1<br><small>?</small></span>
   <span>2<br><small>?</small></span>
   <span>3<br><small>?</small></span>
   <span>4<br><small>?</small></span>
   <span class="fixed">5<br><small>T</small></span>
 </div>
 <div class="flow">
   <span>Q</span><b>tepat sebelum →</b><span>R</span>
   <span>S</span><b>tepat sebelum →</b><span>P</span>
 </div>`;
}

function render(){
 const q=questions[current];
 document.getElementById("qNo").textContent=`Soal ${current+1}`;
 document.getElementById("qText").innerHTML=q.question;
 document.getElementById("subject").textContent="";
 document.getElementById("passage").innerHTML=q.passage?`<div class="passage">${q.passage}</div>`:"";
 const figArea=document.getElementById("figArea");
 const opts=document.getElementById("options");
 figArea.innerHTML="";opts.innerHTML="";

 if(q.type==="figural_source_svg_v29"){
   renderSourceStimV29(figArea,q);
   opts.appendChild(renderSourceOptionsV29(opts,q,current,answers[current],true));
 }else{
   q.options.forEach((op,i)=>{
     const div=document.createElement("div");
     div.className="option"+(answers[current]===i?" selected":"");
     div.innerHTML=`<div class="letter">${letters[i]}</div><div>${op}</div>`;
     div.onclick=()=>{answers[current]=i;render()};
     opts.appendChild(div);
   });
 }
 document.getElementById("prevBtn").disabled=current===0;
 document.getElementById("nextBtn").textContent=current===questions.length-1?"Selesai →":"Berikutnya →";
 updateProgress();renderGrid();
}

// Peta Soal bisa disembunyikan dan dimunculkan tanpa mengubah jawaban.
function toggleQuestionMap(show){
 const grid=document.getElementById('examQuestionGrid');
 const panel=document.getElementById('questionMapPanel');
 const hide=document.getElementById('mapHideButton');
 const reopen=document.getElementById('mapReopenButton');
 if(!grid||!panel)return;
 grid.classList.toggle('map-hidden',!show);
 panel.setAttribute('aria-hidden',show?'false':'true');
 if(hide)hide.setAttribute('aria-expanded',show?'true':'false');
 if(reopen)reopen.setAttribute('aria-expanded',show?'true':'false');
 try{localStorage.setItem('stg_nk_p01_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_nk_p01_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
function renderGrid(){const g=document.getElementById("numGrid");g.innerHTML="";questions.forEach((_,i)=>{const b=document.createElement("button");b.textContent=i+1;if(i===current)b.classList.add("current");else if(answers[i]!==null)b.classList.add("answered");b.onclick=()=>{current=i;render();if(innerWidth<850)document.querySelector(".qcard").scrollIntoView({behavior:"smooth"})};g.appendChild(b)})}
function updateProgress(){const n=answers.filter(x=>x!==null).length,p=Math.round(n/questions.length*100);document.getElementById("progText").textContent=`${n} dari ${questions.length} dijawab`;document.getElementById("progPct").textContent=p+"%";document.getElementById("progFill").style.width=p+"%"}
function prevQ(){if(current>0){current--;render()}}function nextQ(){if(current<questions.length-1){current++;render()}else openSubmit()}
function openSubmit(){const empty=answers.filter(x=>x===null).length;document.getElementById("modalText").textContent=empty?`Masih ada ${empty} soal yang belum dijawab. Soal kosong tetap dihitung tidak benar.`:"Semua soal sudah dijawab. Siap lihat hasil dan pembahasannya?";document.getElementById("modal").classList.add("show")}function closeSubmit(){document.getElementById("modal").classList.remove("show")}function submitExam(){if(submitted)return;submitted=true;if(timerInterval!==null){clearInterval(timerInterval);timerInterval=null;}closeSubmit();showResult()}

function formatLeaderboardTime(totalSeconds){
  totalSeconds=Math.max(0,Number(totalSeconds)||0);
  const m=Math.floor(totalSeconds/60);
  const s=totalSeconds%60;
  return `${String(m).padStart(2,"0")}:${String(s).padStart(2,"0")}`;
}

function renderLeaderboard(rows){
  const box=document.getElementById("leaderboardContent");

  if(!Array.isArray(rows) || rows.length===0){
    box.innerHTML=`<div class="lb-empty">Belum ada nilai yang masuk. Jadilah peserta pertama di ranking 🚀</div>`;
    return;
  }

  let body="";

  rows.forEach(row=>{
    const rankClass=row.rank===1?"top1":row.rank===2?"top2":row.rank===3?"top3":"";
    body+=`
      <tr class="${row.isme?"me":""}">
        <td><span class="lb-rank ${rankClass}">${row.rank}</span></td>
        <td>
          <span class="lb-user">${escapeLeaderboardHtml(row.username)}</span>
          ${row.isme?'<div style="font-size:11px;margin-top:3px;color:#5b526f">Kamu</div>':""}
        </td>
        <td><span class="lb-score">${row.score}</span></td>
        <td>${row.correct}/80</td>
        <td>${formatLeaderboardTime(row.timetaken)}</td>
      </tr>
    `;
  });

  box.innerHTML=`
    <div class="lb-table-wrap">
      <table class="lb-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Username Stangolden</th>
            <th>Nilai</th>
            <th>Benar</th>
            <th>Waktu</th>
          </tr>
        </thead>
        <tbody>${body}</tbody>
      </table>
    </div>
  `;
}

function escapeLeaderboardHtml(value){
  return String(value)
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");
}

async function loadLeaderboard(){
  const box=document.getElementById("leaderboardContent");
  box.innerHTML=`<div class="lb-loading">Memuat ranking...</div>`;

  try{
    const response=await fetch(
      `${LEADERBOARD_ENDPOINT}?action=leaderboard`,
      {
        method:"GET",
        credentials:"same-origin",
        cache:"no-store"
      }
    );

    if(!response.ok){
      throw new Error("Gagal mengambil ranking.");
    }

    const data=await response.json();

    if(!data.success){
      throw new Error(data.message||"Ranking gagal dimuat.");
    }

    renderLeaderboard(data.leaderboard||[]);
  }catch(error){
    box.innerHTML=`<div class="lb-empty">Ranking belum dapat dimuat.<br><small>${escapeLeaderboardHtml(error.message)}</small></div>`;
  }
}

function openLeaderboard(){
  document.getElementById("leaderboardOverlay").classList.add("show");

  const modalTitle=document.getElementById("rankingModalTitle");
  const modalSub=document.getElementById("rankingModalSub");

  if(!HAS_FULL_REVIEW){
    if(modalTitle) modalTitle.textContent="🔒 Lihat Ranking";
    if(modalSub) modalSub.textContent="Ranking hanya untuk peserta terdaftar.";

    const box=document.getElementById("leaderboardContent");

    box.innerHTML=`
      <div class="ranking-locked">
        <div class="ranking-locked-icon">🔒</div>
        <h3>Ranking Khusus Peserta Starbuster 2026</h3>
        <p>Fitur Lihat Ranking tersedia khusus untuk peserta yang terdaftar pada paket Starbuster 2026.</p>

        <div class="ranking-promo">
          <a href="https://stangolden.com/elearning/bootcampupkp/starbuster.png" target="_blank" rel="noopener noreferrer">
            <img src="https://stangolden.com/elearning/bootcampupkp/starbuster.png" alt="Starbuster 2026">
          </a>
        </div>

        <a
          class="ranking-register-btn"
          href="https://wa.me/6285714369422?text=Halo%20Admin%2C%20Saya%20ingin%20mendaftar%20paket%20Persiapan%20UPKP%20Kemenkeu%20Starbuster%202026"
          target="_blank"
          rel="noopener noreferrer"
        >
          📲 Daftar Starbuster 2026
        </a>
      </div>
    `;

    return;
  }

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Paket 01";
  if(modalSub) modalSub.textContent="Semua peserta yang sudah submit ditampilkan berdasarkan hasil percobaan pertama. Jika nilai sama, waktu pengerjaan yang lebih cepat berada di atas.";

  loadLeaderboard();
}

function closeLeaderboard(){
  document.getElementById("leaderboardOverlay").classList.remove("show");
}

function leaderboardBackdrop(event){
  if(event.target.id==="leaderboardOverlay"){
    closeLeaderboard();
  }
}

async function saveScoreToLeaderboard(score,correct){
  // seconds adalah sisa waktu saat ujian selesai.
  const timeTaken=Math.max(0,Math.min(3900,3900-seconds));

  try{
    const body=new URLSearchParams();
    body.set("action","save_score");
    body.set("sesskey",MOODLE_SESSKEY);
    body.set("score",String(score));
    body.set("correct",String(correct));
    body.set("timetaken",String(timeTaken));
    body.set("answersjson",JSON.stringify(answers));

    const response=await fetch(
      LEADERBOARD_ENDPOINT,
      {
        method:"POST",
        credentials:"same-origin",
        cache:"no-store",
        headers:{
          "Content-Type":"application/x-www-form-urlencoded;charset=UTF-8"
        },
        body:body.toString()
      }
    );

    const data=await response.json();

    if(!response.ok || !data.success){
      throw new Error(data.message||"Nilai gagal disimpan.");
    }

    const status=document.getElementById("leaderboardSaveStatus");
    if(status){
      if(data.firstattemptsaved){
        if(HAS_FULL_REVIEW){
          status.innerHTML=`🏆 <b>Hasil percobaan pertamamu sudah tersimpan dan masuk ranking.</b>`;
        }else{
          status.innerHTML=`✅ <b>Hasil percobaan pertamamu sudah tersimpan.</b>`;
        }
      }else{
        if(HAS_FULL_REVIEW){
          status.innerHTML=`ℹ️ Percobaan ini tersimpan sebagai riwayat attempt dan tidak mengubah ranking karena ranking memakai hasil percobaan pertama.`;
        }else{
          status.innerHTML=`ℹ️ Percobaan ini sudah tercatat. Hasil percobaan pertama tetap menjadi nilai utama yang tersimpan.`;
        }
      }
    }

    // Setelah submit pertama, pembahasan sudah terlihat; muat anotasi dari
    // database agar tombol Pena/Stabilo/Penghapus langsung dapat digunakan.
    // Ranking tetap hanya memakai percobaan pertama.
    await raLoadServer();
    return data;
  }catch(error){
    const status=document.getElementById("leaderboardSaveStatus");
    if(status){
      status.textContent="Nilai selesai dihitung, tetapi ranking belum dapat diperbarui.";
    }
    console.error(error);
    return null;
  }
}




function renderAdminQuestionStats(stats){
  if(!IS_SITE_ADMIN || !Array.isArray(stats)) return;

  stats.forEach((row,index)=>{
    const card=document.getElementById(`reviewQuestion-${index}`);
    if(!card) return;

    let box=card.querySelector(".admin-question-stat");
    if(!box){
      box=document.createElement("div");
      box.className="admin-question-stat";

      const head=card.querySelector(".review-question-head");
      if(head && head.nextSibling){
        card.insertBefore(box,head.nextSibling);
      }else if(head){
        head.insertAdjacentElement("afterend",box);
      }else{
        card.prepend(box);
      }
    }

    if(Number(row.total)===0){
      box.innerHTML=`📊 <strong>Statistik peserta:</strong> belum ada detail jawaban yang terekam untuk soal ini.`;
      return;
    }

    box.innerHTML=`
      📊 <strong>${Number(row.correct)}/${Number(row.total)} peserta menjawab benar soal ini</strong>
      &nbsp;•&nbsp;
      <span class="stat-bad">${Number(row.wrong)} salah</span>
      &nbsp;•&nbsp;
      <span class="stat-good">${Number(row.percentage)}% benar</span>
      &nbsp;•&nbsp;
      ${Number(row.unanswered)} tidak menjawab
    `;
  });
}

async function loadAdminQuestionStats(){
  if(!IS_SITE_ADMIN) return;

  try{
    const response=await fetch(
      `${LEADERBOARD_ENDPOINT}?action=question_stats`,
      {
        method:"GET",
        credentials:"same-origin",
        cache:"no-store"
      }
    );

    const data=await response.json();

    if(!response.ok || !data.success){
      throw new Error(data.message||"Statistik soal gagal dimuat.");
    }

    renderAdminQuestionStats(data.stats||[]);
  }catch(error){
    console.error("Admin question stats:",error);
  }
}

function buildReviewFigure(q,reviewIndex,userAnswer){
 if(q.type!=="figural_source_svg_v29") return "";
 const wrapId=`reviewFigure-${reviewIndex}`;
 setTimeout(()=>{
   const host=document.getElementById(wrapId);
   if(!host)return;

   renderSourceStimV29(host,q);
   const optionsWrap=document.createElement("div");
   optionsWrap.className="fig-options";
   optionsWrap.style.marginTop="10px";

   q.optionSvg.forEach((svg,i)=>{
     const box=document.createElement("div");
     let cls="fig-option";
     if(i===q.answer)cls+=" correct-option";
     if(userAnswer===i)cls+=" user-option";
     box.className=cls;

     let note="";
     if(i===q.answer&&userAnswer===i) note='<span class="option-note">Jawaban kamu ✓</span>';
     else if(i===q.answer) note='<span class="option-note">Jawaban benar</span>';
     else if(userAnswer===i) note='<span class="option-note">Jawaban kamu</span>';

     box.innerHTML=`<span class="flabel">${letters[i]}</span><div class="srcsvg"></div>${note}`;
     box.querySelector(".srcsvg").innerHTML=svg;
     const s=box.querySelector("svg");
     if(s){s.style.width="100%";s.style.height="auto";s.style.display="block";}
     optionsWrap.appendChild(box);
   });
   host.appendChild(optionsWrap);
 },0);

 return `<div class="review-figure-wrap"><div id="${wrapId}"></div></div>`;
}
function buildReviewOptions(q,userAnswer){
  let html='<div class="review-options">';
  q.options.forEach((option,index)=>{
    const isCorrect=index===q.answer;
    const isUser=index===userAnswer;
    let cls='review-option';
    if(isCorrect){cls+=' correct-option';}
    else if(isUser){cls+=' user-wrong-option';}
    let note='';
    if(isCorrect && isUser){note='<span class="option-note">Jawaban kamu ✓</span>';}
    else if(isCorrect){note='<span class="option-note">Jawaban benar</span>';}
    else if(isUser){note='<span class="option-note">Jawaban kamu</span>';}
    html+=`<div class="${cls}"><span class="review-letter">${letters[index]}</span><span style="flex:1">${option}</span>${note}</div>`;
  });
  html+='</div>';
  return html;
}


async function reviewLastAttempt(){
  let stored=null;
  try{
    const response=await fetch(`${LEADERBOARD_ENDPOINT}?action=last_review`,{credentials:"same-origin",cache:"no-store"});
    const data=await response.json();
    if(response.ok&&data.success)stored=data.review;
  }catch(e){console.warn('Review server tidak tersedia',e);}
  if(!stored){try{stored=JSON.parse(localStorage.getItem(STG_LOCAL_KEY)||"null");}catch(e){}}
  if(!stored||!Array.isArray(stored.answers)||stored.answers.length!==questions.length){alert('Belum ada jawaban terakhir yang tersimpan.');return;}
  stored.answers.forEach((v,i)=>answers[i]=(v===null?null:Number(v)));
  submitted=true;
  if(timerInterval!==null){clearInterval(timerInterval);timerInterval=null;}
  document.getElementById("startScreen").style.display="none";
  document.getElementById("examScreen").style.display="none";
  document.getElementById("resultScreen").style.display="block";
  showResult(false,stored);
  await raLoadServer();
}
async function retakeQuiz(){
  const ok=confirm('⚠️ Retake Quiz?\n\nSeluruh anotasi pada pembahasan percobaan sebelumnya akan dihapus dari akun Stangolden dan semua perangkat. Jawaban terakhir tetap tersimpan sampai percobaan baru dikumpulkan.\n\nLanjutkan ulangi ujian?');
  if(!ok)return;
  Object.keys(raSaveTimers).forEach(k=>{clearTimeout(raSaveTimers[k]);delete raSaveTimers[k];});
  await Promise.all(Object.keys(raSavePending).map(k=>raSavePending[k]));
  try {
    const b=new URLSearchParams();b.set('action','anno_clear');b.set('sesskey',MOODLE_SESSKEY);
    const r=await fetch(LEADERBOARD_ENDPOINT,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:b.toString()});
    const d=await r.json();if(!r.ok||!d.success)throw new Error(d.message||'Gagal menghapus anotasi');
    reviewAnnoStore={};window.location.reload();
  }catch(e){alert('Coretan belum berhasil dihapus dari server. Retake dibatalkan agar catatan tidak hilang sebagian. '+e.message);}
}

function showResult(saveCurrentAttempt=true, storedMeta=null){
 if(raReviewObserver){raReviewObserver.disconnect();raReviewObserver=null;}
 raServerReady=false;
 reviewAnnoActive=null;reviewAnnoDrawing=false;reviewAnnoStroke=null;
 document.getElementById("examScreen").style.display="none";
 document.getElementById("resultScreen").style.display="block";

 let correct=0,tpa={c:0,t:0},tsk={c:0,t:0};
 questions.forEach((q,i)=>{
   const ok=answers[i]===q.answer;
   if(ok) correct++;
   const b=i<50?tpa:tsk;
   b.t++;
   if(ok) b.c++;
 });

 const score=Math.round(correct/questions.length*100);
 let review="";

 questions.forEach((q,i)=>{
   const ua=answers[i];
   const ok=ua===q.answer;

   // Peserta yang belum terdaftar Starbuster hanya dapat membuka
   // pembahasan soal nomor 1, 2, dan 3.
   if(!HAS_FULL_REVIEW && i>=3){
     review+=`
       <div class="review-locked">
         <div class="review-lock-icon">🔒</div>
         <h4>Pembahasan Soal ${i+1} Terkunci</h4>
         <p>Pembahasan lengkap nomor ini tersedia khusus peserta <b>Starbuster 2026</b>.</p>

         <div class="review-locked-promo">
           <a href="https://stangolden.com/elearning/bootcampupkp/starbuster.png" target="_blank" rel="noopener noreferrer">
             <img src="https://stangolden.com/elearning/bootcampupkp/starbuster.png" alt="Starbuster 2026">
           </a>
         </div>

         <a
           class="review-register-btn"
           href="https://wa.me/6285714369422?text=Halo%20Admin%2C%20Saya%20ingin%20mendaftar%20paket%20Persiapan%20UPKP%20Kemenkeu%20Starbuster%202026"
           target="_blank"
           rel="noopener noreferrer"
         >
           📲 Daftar Starbuster 2026
         </a>
       </div>
     `;
     return;
   }

   review+=`
     <div class="review" id="reviewQuestion-${i}">
       ${raButtons(i)}
       <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px">
         <span class="badge ${q.category==="TPA"?"tpa":"tsk"}">${q.category} • ${q.sub}</span>
         ${q.difficulty ? `<span class="badge diff">${q.difficulty}</span>` : ""}
       </div>
       <div class="review-question-head">
         <div class="review-number">${i+1}</div>
         <div class="review-question-text">${q.question}</div>
       </div>
       ${buildReviewFigure(q,i,ua)}
       <div style="margin-top:10px;font-weight:800">Pilihan Jawaban:</div>
       ${buildReviewOptions(q,ua)}
       <p>Jawaban kamu: ${
         ua===null
         ? '<span class="bad">Tidak dijawab</span>'
         : `<strong>${letters[ua]}. ${q.options[ua]}</strong>`
       }</p>
       <p class="${ok?"ok":"bad"}">${ok?"✓ BENAR":"✕ SALAH"}</p>
       ${ok?"":`<p>Jawaban benar: <strong>${letters[q.answer]}. ${q.options[q.answer]}</strong></p>`}
       <div class="explanation">${q.explanation}</div>
       <span class="source">Sumber: ${q.source||"Nilai-Nilai Kemenkeu"}</span>
       <canvas class="review-annotation-canvas" id="reviewAnnotationCanvas-${i}" data-question="${i}" aria-label="Area coretan pembahasan soal ${i+1}"></canvas>
     </div>
   `;
 });

 const accessNote=HAS_FULL_REVIEW
   ? ""
   : `<div class="lb-note" style="margin:0 0 16px">🔒 Kamu dapat membuka <b>3 pembahasan pertama</b>. Nomor 4–100 khusus peserta Starbuster 2026.</div>`;

 document.getElementById("resultContent").innerHTML=`
   <div class="card result-card">
     <div class="scorehead">
       <div class="scorebox"><strong>${score}</strong></div>
       <div>
         <h2 style="margin:0 0 8px;font-size:29px">Hasil Mini Try Out</h2>
         <p>Benar <strong>${correct} dari ${questions.length}</strong> soal.</p>
         <p style="color:var(--muted)">Gunakan breakdown di bawah untuk melihat area yang perlu diperkuat.</p>
         <div class="save-status" id="leaderboardSaveStatus">Menyimpan nilai ke ranking...</div>
       </div>
     </div>

     <div class="breakdown">
       <div class="break tpa"><strong>📗 Bagian I (No. 1–50)</strong><div style="font-size:26px;font-weight:900">${tpa.c}/${tpa.t}</div></div>
       <div class="break tsk"><strong>📘 Bagian II (No. 51–100)</strong><div style="font-size:26px;font-weight:900">${tsk.c}/${tsk.t}</div></div>
     </div>

     ${accessNote}
     <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:14px 0">
       <h3 style="font-size:22px;margin:0">Pembahasan Lengkap</h3>
       <button type="button" class="btn" onclick="openLeaderboard()">🏆 Lihat Ranking</button>
     </div>
     ${review}
   </div>
 `;

 if(saveCurrentAttempt){
   const timeTaken=Math.max(0,Math.min(3900,3900-seconds));
   try {localStorage.setItem(STG_LOCAL_KEY,JSON.stringify({answers:answers.slice(),correct,score,timetaken:timeTaken,savedAt:Date.now()}));}catch(err){}
   saveScoreToLeaderboard(score,correct);
 }else{
   const status=document.getElementById("leaderboardSaveStatus");
   if(status){
     const timeInfo=storedMeta && Number.isFinite(Number(storedMeta.timetaken))
       ? ` • Waktu ${formatLeaderboardTime(Number(storedMeta.timetaken))}`
       : "";

     status.innerHTML=`📝 <b>Menampilkan jawaban dari percobaan terakhir yang tersimpan.</b>${timeInfo}`;
   }

   if(IS_SITE_ADMIN){
     loadAdminQuestionStats();
   }
 }

 scrollTo({top:0,behavior:"smooth"});
}
function tick(){const m=String(Math.floor(seconds/60)).padStart(2,"0"),s=String(seconds%60).padStart(2,"0");document.getElementById("timer").textContent=m+":"+s;if(seconds<=0){if(!submitted){submitted=true;if(timerInterval!==null){clearInterval(timerInterval);timerInterval=null;}showResult()}return}seconds--}
let timerInterval=null;

function startExam(){
  document.getElementById("startScreen").style.display="none";
  document.getElementById("examScreen").style.display="block";

  // Tampilkan soal pertama dan baru mulai timer setelah tombol diklik.
  render();
  tick();

  if(timerInterval===null){
    timerInterval=setInterval(tick,1000);
  }

  window.scrollTo({top:0,behavior:"smooth"});
}

</script>
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

</body></html>
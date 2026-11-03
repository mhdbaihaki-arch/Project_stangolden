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
// $DB->get_record("stgnk_etika_p02_rank") => tabel fisik "mdlhv_stgnk_etika_p02_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgnk_etika_p02_rank';
const STG_REVIEW_T = 'stgnk_etika_p02_rev';
const STG_ANNO_T = 'stgnk_etika_p02_anno';
const STG_KEY = [0, 1, 2, 3, 4, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 3, 4, 0, 1, 2, 4, 0, 1, 2, 3, 0, 1, 2, 3, 4, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 3, 4, 0, 1, 2, 4, 0, 1, 2, 3, 0, 1, 2, 3, 4, 2, 3, 4, 0, 1, 1, 2, 3, 4, 0, 3, 4, 0, 1, 2, 2, 3, 4, 0, 1, 4, 0, 1, 2, 3, 3, 4, 0, 1, 2, 0, 1, 2, 3, 4, 4, 0, 1, 2, 3, 1, 2, 3, 4, 0];
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
            if(!is_array($a)||count($a)!==100||array_keys($a)!==range(0,99)) stgnk_json(['success'=>false,'message'=>'Format jawaban tidak valid.'],400);
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
            if ($q < 0 || $q >= 100) stgnk_json(['success'=>false,'message'=>'Nomor soal tidak valid.'],400);
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
            if(!is_array($a)||count($a)!==100) stgnk_json(['success'=>false,'message'=>'Data jawaban tidak valid.'],500);
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
<title>Latihan Nilai-Nilai Kemenkeu Paket 01 (Hlm. 05–14) • STANGOLDEN</title>
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
      Latihan Nilai-Nilai Kemenkeu
      <span class="series-tag">Paket 01 • Hlm. 05–14</span>
    </h1>

    <p class="start-sub">
      Ujian belum dimulai. Baca informasi singkat di bawah, lalu klik
      <b>Mulai Ujian</b> jika sudah siap.
    </p>

    <div class="start-info">
      <div class="start-info-box">
        <small>Jumlah Soal</small>
        <strong>100 Soal</strong>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 01 • Hlm. 05–14</span></h1>
</section>
<div class="status"><div class="subject" id="subject" style="display:none"></div><div class="progress-wrap"><div class="progress-meta"><span id="progText">0 dari 100 dijawab</span><span id="progPct">0%</span></div><div class="track"><div class="fill" id="progFill"></div></div></div><div class="timer" id="timer">65:00</div></div>
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
const STG_LOCAL_KEY="stg_etika_p02_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kepatuhan aparatur sipil negara terhadap kaidah kedinasan tercermin dalam sikap...", "options": ["Taat pada tuntutan khas etika birokrasi", "Mengutamakan kepentingan kolektif kelompok kerja", "Menyesuaikan target kinerja dengan arahan pimpinan", "Membatasi interaksi formal dengan pihak eksternal", "Meningkatkan pengawasan operasional harian satker"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Moralitas Pribadi Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu unsur moralitas pribadi aparatur adalah <strong>taat pada tuntutan khas etika birokrasi</strong> dalam menjalankan tugas kedinasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Sikap moral aparatur menuntut kepatuhan utuh terhadap norma khas birokrasi demi menjaga integritas jabatan. Ketentuan etika menegaskan bahwa setiap pegawai wajib taat pada tuntutan khas etika birokrasi secara konsisten. Dengan demikian, ketaatan pada etika birokrasi menjadi fondasi utama moralitas pribadi aparatur.</p><p>Jawaban tepat: <strong>A. Taat pada tuntutan khas etika birokrasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan wujud koordinasi kerja tim, bukan rumusan unsur moralitas pribadi aparatur.</li><li><strong>C</strong>: Merupakan aspek keselarasan rencana kinerja, bukan butir moralitas pribadi aparatur.</li><li><strong>D</strong>: Merupakan pembatasan relasi kerja, bukan cerminan kepatuhan etika birokrasi.</li><li><strong>E</strong>: Merupakan fungsi manajerial pengawasan, bukan dimensi moralitas personal pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>taat pada tuntutan khas etika birokrasi</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dorongan internal pegawai untuk senantiasa memperbarui kecakapan kerja diwujudkan melalui...", "options": ["Penyelarasan target kinerja dengan standar unit", "Minat dan hasrat meningkatkan kompetensi diri", "Keikutsertaan berkala dalam forum koordinasi", "Kepatuhan formal terhadap jam kerja kantor", "Pelaksanaan tugas sesuai petunjuk teknis satker"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengembangan Kompetensi Diri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Aparatur wajib memiliki <strong>minat dan hasrat untuk terus-menerus meningkatkan kompetensi dan kecakapannya</strong> dalam melayani publik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Moralitas pribadi menuntut adanya semangat belajar mandiri secara berkelanjutan demi mutu pelayanan. Sikap ini diwujudkan melalui minat dan hasrat untuk terus-menerus meningkatkan kompetensi dan kecakapannya di bidang tugas masing-masing. Dengan demikian, pengembangan kapasitas diri merupakan kewajiban etis aparatur.</p><p>Jawaban tepat: <strong>B. Minat dan hasrat meningkatkan kompetensi diri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penyusunan sasaran kinerja pegawai, bukan dorongan belajar kompetensi diri.</li><li><strong>C</strong>: Merupakan bentuk komunikasi kelembagaan, bukan hasrat peningkatan kecakapan pribadi.</li><li><strong>D</strong>: Merupakan disiplin kehadiran kerja, bukan indikator pengembangan kecakapan profesional.</li><li><strong>E</strong>: Merupakan ketertiban prosedural pelaksanaan tugas rutin harian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>meningkatkan kompetensi dan kecakapannya</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Saat melayani keluhan masyarakat terkait administrasi perizinan, tindakan aparatur yang etis adalah...", "options": ["Meneruskan keluhan kepada pimpinan unit secara tertulis", "Meminta pemohon melengkapi berkas verifikasi tambahan", "Menghormati hak semua pihak yang terkait layanan", "Mencatat keluhan pada buku register pelayanan umum", "Menyusun rekapitulasi pengaduan secara berkala"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penghormatan Hak Pemangku Kepentingan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan etika kedinasan mewajibkan aparatur senantiasa <strong>menghormati hak semua pihak yang terkait</strong> dalam interaksi pelayanan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Dalam menghadapi masyarakat pemohon layanan, aparatur wajib menjunjung hak warga secara adil tanpa diskriminasi. Ketentuan moralitas pribadi menuntut pegawai untuk menghormati hak semua pihak yang terkait. Dengan demikian, penghargaan atas hak pemangku kepentingan menjadi ukuran profesionalitas layanan.</p><p>Jawaban tepat: <strong>C. Menghormati hak semua pihak yang terkait layanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan prosedur eskalasi masalah kedinasan, bukan sikap dasar penghormatan hak.</li><li><strong>B</strong>: Merupakan pengujian kelengkapan dokumen administratif pemohon layanan.</li><li><strong>D</strong>: Merupakan penatausahaan administrasi registrasi dokumen pengaduan.</li><li><strong>E</strong>: Merupakan pelaporan manajerial pengaduan publik berkala.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menghormati hak semua pihak yang terkait</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam perwujudan suasana kerja yang etis, peran pokok seorang pemimpin adalah sebagai...", "options": ["Pengendali alokasi sarana pendukung kerja", "Perumus kebijakan strategis organisasi satker", "Penanggung jawab tunggal pengelolaan anggaran", "Panutan dan pemberi motivasi bagi bawahan", "Penyelaras hubungan kemitraan antar-instansi"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Kepemimpinan Bermutu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Seorang pemimpin memikul peran sentral sebagai <strong>panutan dan pemberi motivasi</strong> kepada para bawahan dalam menegakkan etika kerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Keberhasilan budaya etis dalam organisasi birokrasi sangat dipengaruhi oleh teladan pimpinan. Pemimpin bertindak sebagai panutan dan pemberi motivasi kepada para bawahannya agar tercipta suasana kerja yang etis. Dengan demikian, keteladanan pimpinan merupakan pendorong utama pembiasaan etika bawahan.</p><p>Jawaban tepat: <strong>D. Panutan dan pemberi motivasi bagi bawahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kewenangan logistik perlengkapan kantor, bukan peran moral kepemimpinan.</li><li><strong>B</strong>: Merupakan perencanaan kebijakan teknis organisasi satker.</li><li><strong>C</strong>: Merupakan tanggung jawab pengelolaan perbendaharaan keuangan negara.</li><li><strong>E</strong>: Merupakan diplomasi hubungan eksternal kelembagaan birokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>panutan dan pemberi motivasi</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Seorang pemimpin wajib benar-benar menguasai bidang pekerjaannya dengan tujuan utama...", "options": ["Mempercepat kenaikan jenjang kepangkatan jabatan", "Menyusun laporan kinerja unit kerja secara mandiri", "Menyusun standar operasional unit tanpa kajian data", "Memusatkan pengambilan keputusan pada pimpinan seorang diri", "Mampu mengatasi permasalahan yang terjadi di unit"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kompetensi Pemimpin Bermutu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kompetensi pemimpin menuntut penguasaan bidang kerja agar <strong>mampu mengatasi permasalahan-permasalahan yang terjadi</strong> dalam unit.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Pemimpin berintegritas harus memiliki kapasitas teknis mumpuni guna membimbing staf menyelesaikan kendala kerja. Penguasaan bidang kerja ditujukan agar pemimpin mampu mengatasi permasalahan yang timbul secara tepat dan solutif. Dengan demikian, kompetensi teknis memperkuat legitimasi kepemimpinan pimpinan.</p><p>Jawaban tepat: <strong>E. Mampu mengatasi permasalahan yang terjadi di unit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pengembangan karier administratif individu, bukan sasaran kepemimpinan mutu.</li><li><strong>B</strong>: Merupakan administrasi pelaporan kinerja organisasi satker.</li><li><strong>C</strong>: Bertentangan dengan prinsip kepemimpinan partisipatif dalam perumusan aturan.</li><li><strong>D</strong>: Bertentangan dengan prinsip pembinaan dan pemberdayaan staf bawahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mampu mengatasi</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kemampuan memimpin, berwibawa, dan memiliki ketegasan sanksi merupakan wujud pemenuhan aspek...", "options": ["Penyelarasan target kinerja organisasi", "Memastikan pelaksanaan tertib kerja kedinasan", "Pengembangan jejaring kerja sama lintas satker", "Pendelegasian seluruh wewenang teknis kerja", "Penyusunan indikator kepuasan pemangku kepentingan"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tertib Kerja Kepemimpinan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemimpin bermutu wajib <strong>memastikan pelaksanaan tertib kerja</strong> melalui kewibawaan, motivasi, dan ketegasan sanksi bagi pelanggar aturan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Aspek kepemimpinan ini menuntut adanya disiplin operasional di lingkungan unit kerja secara konsisten. Pemimpin harus berwibawa, memberi motivasi, serta tegas memberikan sanksi guna memastikan pelaksanaan tertib kerja. Dengan demikian, kedisiplinan organisasi berakar pada kepemimpinan yang tegas.</p><p>Jawaban tepat: <strong>B. Memastikan pelaksanaan tertib kerja kedinasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan sinkronisasi sasaran kerja organisasi, bukan penegakan tertib kedinasan.</li><li><strong>C</strong>: Merupakan koordinasi eksternal antar-unit birokrasi pemerintah.</li><li><strong>D</strong>: Merupakan teknik distribusi beban tugas pekerjaan kantor.</li><li><strong>E</strong>: Merupakan evaluasi mutu pelayanan masyarakat di unit kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memastikan pelaksanaan tertib kerja</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang atasan menerapkan sanksi disiplin secara setara kepada semua staf tanpa diskriminasi. Sikap atasan tersebut mencerminkan...", "options": ["Kompetensi manajerial dalam perencanaan kerja", "Pemberian motivasi kerja melalui penghargaan", "Konsistensi kepemimpinan dan tidak pilih kasih", "Keterbukaan informasi pelaksanaan tugas kedinasan", "Ketahanan emosional menghadapi dinamika kerja"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsistensi Sikap Pemimpin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Konsistensi pemimpin diwujudkan dengan menerapkan aturan dan sanksi secara adil serta <strong>tidak boleh pilih kasih terhadap bawahannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Penerapan aturan yang tidak membeda-bedakan latar belakang bawahan mencegah terjadinya rasa ketidakadilan di unit kerja. Ketentuan kepemimpinan menegaskan bahwa pemimpin harus konsisten dan tidak boleh pilih kasih dalam menjatuhkan sanksi maupun penghargaan. Dengan demikian, perlakuan adil menjaga kewibawaan pimpinan.</p><p>Jawaban tepat: <strong>C. Konsistensi kepemimpinan dan tidak pilih kasih</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penguasaan teknis penyusunan rencana operasional kerja.</li><li><strong>B</strong>: Merupakan dorongan positif melalui insentif prestasi kerja bawahan.</li><li><strong>D</strong>: Merupakan prinsip transparansi komunikasi informasi kebijakan unit.</li><li><strong>E</strong>: Merupakan kecerdasan emosional individu saat menghadapi tekanan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Seorang pemimpin harus bersikap konsisten</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Keharusan seorang pemimpin menyampaikan keputusan secara terbuka dan jelas kepada pihak terkait merupakan pemenuhan syarat...", "options": ["Kompetensi substansi bidang tugas", "Konsistensi perlakuan terhadap pegawai", "Kewibawaan formal jabatan birokrasi", "Transparansi dalam pengambilan keputusan", "Kemandirian dari pengaruh lingkungan luar"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Transparansi Keputusan Pemimpin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam kepemimpinan bermutu, aspek <strong>transparansi</strong> mewajibkan setiap keputusan pemimpin jelas dan terbuka bagi pihak-pihak terkait.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Keterbukaan alasan kebijakan menumbuhkan rasa percaya bawahan dan akuntabilitas publik. Keputusan-keputusan seorang pemimpin harus jelas dan transparan bagi pihak yang terdampak keputusan tersebut. Dengan demikian, transparansi menjadi pilar tata kelola kepemimpinan etis.</p><p>Jawaban tepat: <strong>D. Transparansi dalam pengambilan keputusan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan keahlian substantif teknis pada jabatan tertentu.</li><li><strong>B</strong>: Merupakan keadilan perlakuan reward dan punishment kepada pegawai.</li><li><strong>C</strong>: Merupakan otoritas wewenang formal struktural pimpinan satker.</li><li><strong>E</strong>: Merupakan independensi intervensi luar dalam pelaksanaan tupoksi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Transparansi</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C4 - Analisis", "question": "Berikut merupakan sifat pribadi yang wajib dimiliki seorang pemimpin agar dapat menjadi panutan bawahan, KECUALI...", "options": ["Jujur dalam menyampaikan fakta kedinasan", "Adil dalam memperlakukan setiap staf", "Cakap dalam menguasai keahlian teknis", "Komunikatif dalam membangun relasi kerja", "Fleksibel mengabaikan aturan pada kondisi tertentu"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter Keteladanan Pemimpin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sifat pemimpin teladan mencakup <strong>jujur, adil, cakap, tegas, komunikatif, dan bertanggung jawab</strong> dalam seluruh tindakan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat karena bukan sifat teladan pemimpin. Pemimpin teladan wajib berpegang teguh pada aturan yang berlaku dan tidak berkompromi terhadap pelanggaran. Ketentuan modul merinci sifat teladan mencakup jujur, adil, cakap, tegas, komunikatif, dan bertanggung jawab. Dengan demikian, pengabaian aturan bertentangan dengan integritas kepemimpinan.</p><p>Jawaban tepat: <strong>E. Fleksibel mengabaikan aturan pada kondisi tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan sifat pokok kejujuran pribadi yang wajib dimiliki pemimpin teladan.</li><li><strong>B</strong>: Merupakan sikap adil yang mencegah ketidakadilan di antara bawahan.</li><li><strong>C</strong>: Merupakan kecakapan kompetensi profesional yang menjadi syarat keteladanan.</li><li><strong>D</strong>: Merupakan kemampuan komunikasi terbuka demi keharmonisan unit kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jujur, adil, cakap, tegas, komunikatif</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dua syarat sistemik yang berpengaruh besar terhadap keberhasilan perwujudan etika dalam organisasi pemerintah adalah...", "options": ["Lingkungan kerja dan sistem pengawasan pengendalian", "Kapasitas anggaran dan kecukupan sarana kerja", "Jenjang kepangkatan dan fleksibilitas struktur kerja", "Kuantitas pegawai dan kecepatan proses pelaporan", "Tingkat pendidikan staf dan fasilitas teknologi"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Sistemik Etika Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keberhasilan perwujudan etika organisasi ditentukan oleh dua syarat sistemik, yaitu <strong>lingkungan kerja</strong> dan <strong>pengawasan/pengendalian</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Penerapan nilai etika tidak hanya bergantung pada moral individu, tetapi membutuhkan dukungan ekosistem sistemik. Dua syarat sistemik utama yang memengaruhi perwujudan etika aparatur adalah lingkungan kerja dan pengawasan/pengendalian. Dengan demikian, kedua syarat tersebut membangun suasana kerja yang kondusif.</p><p>Jawaban tepat: <strong>A. Lingkungan kerja dan sistem pengawasan pengendalian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan aspek pendukung fiskal dan logistik sarana operasional kantor.</li><li><strong>C</strong>: Merupakan desain hierarki kepegawaian dan pola struktural birokrasi.</li><li><strong>D</strong>: Merupakan rasio beban kerja pegawai dan kecepatan tata usaha pelaporan.</li><li><strong>E</strong>: Merupakan profil kualifikasi akademik dan ketersediaan perangkat gawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 14) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kondisi-kondisi sistemik • lingkungan kerja</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pengaruh mendasar lingkungan kerja terhadap perilaku moral pegawai adalah kemampuannya untuk...", "options": ["Menentukan standar besaran tunjangan kinerja", "Mengatur penempatan mutasi pegawai secara berkala", "Mendukung atau merusak watak moral pegawai", "Mempercepat penyelesaian proses audit kinerja unit", "Mengurangi beban pelaporan administrasi kedinasan"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengaruh Lingkungan Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Lingkungan kerja birokrasi memiliki daya pengaruh kuat yang dapat <strong>mendukung atau merusak watak moral seseorang</strong> dalam bertugas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Lingkungan kerja sosial membentuk kebiasaan dan cara pandang pegawai terhadap nilai integritas. Lingkungan kerja dapat mendukung atau justru merusak watak moral seseorang tergantung budaya yang dominan di unitnya. Dengan demikian, penciptaan lingkungan positif menjadi tanggung jawab bersama.</p><p>Jawaban tepat: <strong>C. Mendukung atau merusak watak moral pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kebijakan remunerasi berbasis beban kerja dan jabatan pegawai.</li><li><strong>B</strong>: Merupakan manajemen mobilitas kepegawaian internal kementerian.</li><li><strong>D</strong>: Merupakan target waktu pelaksanaan audit pengawasan akuntabilitas.</li><li><strong>E</strong>: Merupakan simplifikasi prosedur birokrasi tata laksana persuratan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mendukung atau merusak watak moral</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Etos kerja yang baik pada aparatur sipil negara hanya dapat berkembang optimal apabila...", "options": ["Pegawai diberikan kebebasan penuh tanpa batasan aturan", "Seluruh target kinerja dibebankan secara merata ke staf", "Pengawasan kedinasan ditiadakan untuk menjaga kenyamanan", "Sikap moral positifnya didukung, dihargai, dan diharapkan", "Pemberian penghargaan dilakukan tanpa penilaian objektivitas"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kondisi Perkembangan Etos Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Etos kerja yang baik berkembang subur dalam lingkungan di mana <strong>sikap-sikap moralnya yang positif didukung, dihargai, dan diharapkan</strong> rekan kerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Motivasi moral pegawai tumbuh ketika kejujuran dan dedikasi diapresiasi oleh rekan sejawat dan pimpinan. Etos kerja yang baik hanya dapat berkembang saat sikap moral positif didukung, dihargai, dan diharapkan oleh sekitarnya. Dengan demikian, penghargaan sosial memperkuat integritas kerja.</p><p>Jawaban tepat: <strong>D. Sikap moral positifnya didukung, dihargai, dan diharapkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bertentangan dengan tata tertib kepatuhan regulasi aparatur birokrasi.</li><li><strong>B</strong>: Merupakan manajemen pembagian tugas kerja, bukan faktor iklim moral.</li><li><strong>C</strong>: Bertentangan dengan prinsip pengendalian mutu dan kepatuhan aturan.</li><li><strong>E</strong>: Bertentangan dengan prinsip meritokrasi dan keadilan penghargaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>watak moral seseorang</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Ketika menerima kritik konstruktif dari masyarakat terkait layanan publik, sikap etis yang wajib diambil aparatur adalah...", "options": ["Meneruskan kritik kepada pihak berwajib untuk diteliti motifnya", "Membatasi akses layanan bagi pemohon yang mengajukan kritik", "Menyusun pembelaan diri secara tertulis di forum kedinasan", "Mengalihkan tanggung jawab penanganan kepada pimpinan satker", "Menerima pengawasan publik sebagai masukan yang positif"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengawasan Eksternal Masyarakat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengawasan organisasi tidak cukup dari internal, melainkan <strong>pengawasan/pengendalian oleh masyarakat harus diterima sebagai suatu hal yang positif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Birokrasi yang sehat memandang partisipasi masyarakat sebagai sarana evaluasi dan perbaikan kualitas layanan. Pengawasan dari luar organisasi oleh publik wajib disikapi secara terbuka dan diterima positif demi perbaikan kinerja. Dengan demikian, keterbukaan publik memperkuat akuntabilitas aparatur.</p><p>Jawaban tepat: <strong>E. Menerima pengawasan publik sebagai masukan yang positif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan sikap defensif kecurigaan yang menghambat keterbukaan informasi.</li><li><strong>B</strong>: Merupakan tindakan diskriminatif yang melanggar kode etik pelayanan.</li><li><strong>C</strong>: Merupakan respon administratif reaktif, bukan penerimaan kritik konstruktif.</li><li><strong>D</strong>: Merupakan penghindaran tanggung jawab perbaikan mutu layanan publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengawasan/pengendalian oleh masyarakat</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pedoman penerapan nilai dasar ASN dalam rangka penyelenggaraan tugas pemerintahan di Kementerian Keuangan diatur dalam...", "options": ["Surat Edaran Nomor 44/MK.01/2017", "Keputusan Menteri Keuangan Nomor 416/KMK.01/2023", "Peraturan Pemerintah Nomor 42 Tahun 2004", "Peraturan Menteri Keuangan Nomor 190/PMK.01/2018", "Undang-Undang Nomor 20 Tahun 2023"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketentuan SE-44/MK.01/2017</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan nilai dasar ASN bagi pegawai Kemenkeu diatur dalam <strong>Surat Edaran Nomor 44/MK.01/2017</strong> tentang Himbauan Penerapan Nilai Dasar ASN.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Regulasi teknis ini mengimbau seluruh aparatur kementerian untuk konsisten menjalankan nilai dasar ASN dalam birokrasi. SE Nomor 44/MK.01/2017 diterbitkan khusus untuk mengatur penerapan nilai dasar ASN di lingkungan kementerian. Dengan demikian, instrumen SE ini menjadi landasan kepatuhan dasar pegawai.</p><p>Jawaban tepat: <strong>A. Surat Edaran Nomor 44/MK.01/2017</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan KMK yang mengatur sistem kerja fleksibel di Kemenkeu.</li><li><strong>C</strong>: Merupakan PP tentang pembinaan jiwa korps dan kode etik PNS nasional.</li><li><strong>D</strong>: Merupakan PMK yang mengatur kode etik dan kode perilaku PNS Kemenkeu.</li><li><strong>E</strong>: Merupakan payung hukum Undang-Undang Aparatur Sipil Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Surat Edaran Nomor 44/MK.01/2017</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan SE Nomor 44/MK.01/2017, setiap pegawai Kementerian Keuangan diwajibkan untuk...", "options": ["Memperbarui data pribadi pada sistem kepegawaian", "Memegang teguh ideologi Pancasila dan UUD 1945", "Mengikuti kegiatan seminar peningkatan kompetensi", "Menyusun sasaran kinerja pegawai secara mandiri", "Menjalin komunikasi informal dengan mitra kerja"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Dasar Pegawai SE-44/2017</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setiap pegawai Kemenkeu diwajibkan <strong>memegang teguh ideologi Pancasila</strong> serta setia pada UUD NRI 1945 dan pemerintahan yang sah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Kesetiaan terhadap konstitusi dan dasar negara merupakan syarat mutlak bagi seluruh pegawai aparatur sipil negara. SE Nomor 44/MK.01/2017 menegaskan kewajiban memegang teguh ideologi Pancasila dan setia mempertahankan UUD 1945. Dengan demikian, loyalitas kebangsaan menjadi komitmen utama pegawai.</p><p>Jawaban tepat: <strong>B. Memegang teguh ideologi Pancasila dan UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan ketertiban administrasi kepegawaian HRIS internal satker.</li><li><strong>C</strong>: Merupakan kegiatan pengembangan pengetahuan teknis keahlian aparatur.</li><li><strong>D</strong>: Merupakan kewajiban penyusunan rencana kerja berkala tahunan pegawai.</li><li><strong>E</strong>: Merupakan pembinaan relasi kerja sama kemitraan kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Memegang teguh ideologi Pancasila</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tanggung jawab pimpinan unit organisasi terkait penerapan nilai dasar ASN menurut SE-44/MK.01/2017 adalah...", "options": ["Menetapkan alokasi tunjangan kinerja secara mandiri", "Menyusun laporan keuangan satker pada akhir tahun", "Memfasilitasi rotasi internal staf antar-seksi kerja", "Melakukan pembinaan dan pengawasan penerapan nilai dasar", "Menjalin kerja sama operasional dengan lembaga donor"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Pimpinan Unit SE-44/2017</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan unit organisasi wajib <strong>melakukan pembinaan dan pengawasan penerapan nilai dasar ASN</strong> terkait penyelenggaraan tugas pemerintahan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Keberhasilan internalisasi nilai menuntut pengawasan aktif dari para pejabat struktural di setiap tingkatan satker. Pimpinan unit diwajibkan melakukan pembinaan dan pengawasan terhadap kepatuhan nilai dasar bawahannya. Dengan demikian, fungsi manajerial melekat pada penegakan etika aparatur.</p><p>Jawaban tepat: <strong>D. Melakukan pembinaan dan pengawasan penerapan nilai dasar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kewenangan regulasi terpusat kementerian bidang remunerasi.</li><li><strong>B</strong>: Merupakan fungsi akuntansi pertanggungjawaban anggaran instansi.</li><li><strong>C</strong>: Merupakan manajemen penataan sumber daya manusia teknis satker.</li><li><strong>E</strong>: Merupakan hubungan kemitraan strategis pembiayaan pembangunan luar negeri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pembinaan dan pengawasan penerapan nilai dasar</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Apabila atasan langsung mendapati indikasi penyelewengan nilai dasar pada bawahannya, langkah wajib yang harus diambil adalah...", "options": ["Menunggu konfirmasi temuan dari aparat penegak hukum", "Membahas masalah tersebut secara terbuka di forum umum", "Menetapkan pemotongan tunjangan kinerja secara langsung", "Menghimbau pegawai lain untuk membatasi interaksi dinas", "Mengambil tindakan sesuai ketentuan perundang-undangan"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penanganan Indikasi Penyelewengan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jika terdapat indikasi penyelewengan nilai dasar, atasan langsung <strong>wajib mengambil tindakan sesuai dengan ketentuan perundang-undangan</strong> yang berlaku.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Atasan tidak boleh melakukan pembiaran ataupun bertindak di luar prosedur hukum yang telah diatur resmi. Pimpinan unit dan atasan langsung wajib segera mengambil tindakan korektif sesuai koridor ketentuan perundang-undangan. Dengan demikian, kepatuhan prosedural menjamin keadilan penegakan etika.</p><p>Jawaban tepat: <strong>E. Mengambil tindakan sesuai ketentuan perundang-undangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kelambanan tindakan disiplin administratif internal kepegawaian.</li><li><strong>B</strong>: Melanggar asas kerahasiaan pemeriksaan penegakan disiplin aparatur.</li><li><strong>C</strong>: Melanggar mekanisme resmi tata cara penjatuhan hukuman disiplin.</li><li><strong>D</strong>: Menimbulkan perpecahan hubungan kerja internal di lingkungan unit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>indikasi penyelewengan dalam penerapan nilai dasar</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Instrumen regulasi yang menggiatkan penerapan nilai Pancasila dan Bela Negara di Kementerian Keuangan adalah...", "options": ["Surat Edaran nomor SE: 12/MK.01/2019", "Keputusan Menteri Keuangan Nomor 416/2023", "Surat Edaran Nomor 44/MK.01/2017", "Peraturan Pemerintah Nomor 42 Tahun 2004", "Peraturan Menteri Keuangan Nomor 190/2018"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Pancasila dan Bela Negara SE-12/2019</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan nilai Pancasila dan Bela Negara bagi pegawai Kemenkeu ditegaskan dalam <strong>Surat Edaran nomor SE: 12/MK.01/2019</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Regulasi ini memperkuat komitmen kebangsaan seluruh aparatur sipil negara di lingkungan kementerian. SE Nomor 12/MK.01/2019 diterbitkan khusus untuk mengatur penerapan nilai Pancasila dan Bela Negara secara konsisten. Dengan demikian, pegawai memiliki pedoman konkrit dalam menjaga persatuan bangsa.</p><p>Jawaban tepat: <strong>A. Surat Edaran nomor SE: 12/MK.01/2019</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan aturan mengenai pelaksanaan sistem kerja fleksibel di Kemenkeu.</li><li><strong>C</strong>: Merupakan aturan penerapan nilai dasar ASN dalam penyelenggaraan tugas.</li><li><strong>D</strong>: Merupakan PP tentang pembinaan jiwa korps dan kode etik PNS.</li><li><strong>E</strong>: Merupakan PMK tentang kode etik dan kode perilaku PNS Kemenkeu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Surat Edaran nomor SE: 12/MK.01/2019</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Latar belakang penerbitan SE Nomor 12/MK.01/2019 menekankan fungsi aparatur sipil negara sebagai...", "options": ["Pengelola utama aset strategis perbendaharaan negara", "Perekat bangsa melalui pengamalan nilai kebangsaan", "Perencana alokasi pembiayaan anggaran pembangunan", "Pelaksana fungsi penyidikan tindak pidana perpajakan", "Penyelaras standar operasional pelayanan kepegawaian"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Pegawai sebagai Perekat Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS memikul peran strategis sebagai <strong>perekat bangsa</strong> melalui penerapan nilai-nilai Pancasila dan Bela Negara di lingkungan kerjanya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Aparatur negara berkewajiban merawat persatuan dan mencegah segala bentuk disintegrasi nasional. Dalam rangka menjalankan peran sebagai perekat bangsa, diterbitkan SE Nomor 12/MK.01/2019 sebagai panduan pengamalan nilai kebangsaan. Dengan demikian, netralitas dan kerukunan bangsa terjaga dengan baik.</p><p>Jawaban tepat: <strong>B. Perekat bangsa melalui pengamalan nilai kebangsaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tugas pokok perbendaharaan kekayaan negara kementerian.</li><li><strong>C</strong>: Merupakan tugas teknis direktorat jenderal anggaran kementerian.</li><li><strong>D</strong>: Merupakan kewenangan fungsi penegakan hukum kepatutan perpajakan.</li><li><strong>E</strong>: Merupakan standardisasi prosedur manajemen administrasi kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>perekat bangsa</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama penyelenggaraan pengawasan dan pengendalian dalam organisasi pemerintah adalah untuk...", "options": ["Mempercepat pergantian posisi jabatan struktural pegawai", "Membatasi inisiatif inovasi pelayanan staf bawahan", "Memastikan penerapan etika kerja dapat dipantau konsisten", "Membatasi partisipasi masyarakat dalam penilaian mutu layanan", "Mengurangi alokasi anggaran operasional pengawasan satker"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Pengawasan dan Pengendalian</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengawasan dan pengendalian diselenggarakan agar <strong>penerapan etika akan selalu dapat dipantau</strong> dalam seluruh aktivitas kedinasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Keberadaan sistem pengendalian memastikan setiap proses kerja berjalan sesuai koridor norma etis. Pengawasan atas pelaksanaan pekerjaan menjamin penerapan etika kerja aparatur terpantau secara berkala. Dengan demikian, potensi penyimpangan perilaku pegawai dapat dicegah sejak dini.</p><p>Jawaban tepat: <strong>C. Memastikan penerapan etika kerja dapat dipantau konsisten</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan mekanisme manajemen pola karier dan regenerasi pimpinan.</li><li><strong>B</strong>: Bertentangan dengan tujuan pemberdayaan dan dorongan kreativitas staf.</li><li><strong>D</strong>: Bertentangan dengan prinsip akuntabilitas dan keterbukaan partisipasi publik.</li><li><strong>E</strong>: Merupakan efisiensi biaya, bukan sasaran pokok sistem pengawasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengawasan/pengendalian atas pelaksanaan pekerjaan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan SE Nomor 12/MK.01/2019, salah satu unsur nilai Bela Negara yang wajib dipegang teguh pegawai adalah...", "options": ["Penguasaan strategi pertahanan militer perbatasan", "Kepemilikan izin operasional senjata dinas keamanan", "Pengawasan ketat terhadap arus barang kepabeanan", "Penolakan terhadap seluruh kerja sama internasional", "Cinta tanah air dan rela berkorban untuk bangsa"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai-Nilai Dasar Bela Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Unsur nilai <strong>Bela Negara</strong> meliputi kecintaan pada tanah air, kesadaran berbangsa, keyakinan ideologi, dan <strong>rela berkorban untuk bangsa dan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Bela negara bagi aparatur sipil negara diwujudkan melalui pengabdian tulus dan kesetiaan penuh pada bangsa. SE Nomor 12/MK.01/2019 merumuskan nilai bela negara mencakup cinta tanah air dan rela berkorban. Dengan demikian, pengabdian tanpa pamrih menjadi cerminan bela negara aparatur.</p><p>Jawaban tepat: <strong>E. Cinta tanah air dan rela berkorban untuk bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kompetensi taktis militer pertahanan kementerian pertahanan.</li><li><strong>B</strong>: Merupakan prosedur pengamanan sarana fisik satuan pengamanan tertentu.</li><li><strong>C</strong>: Merupakan tugas teknis kepabeanan dan cukai direktorat jenderal terkait.</li><li><strong>D</strong>: Bertentangan dengan prinsip kerja sama internasional yang bermanfaat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>nilai-nilai Bela Negara</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kode Etik dan Kode Perilaku Pegawai Negeri Sipil di lingkungan Kementerian Keuangan secara komprehensif diatur dalam...", "options": ["PMK nomor 190/PMK.01/2018", "Surat Edaran Nomor 12/MK.01/2019", "Keputusan Menteri Keuangan Nomor 416/2023", "Surat Edaran Nomor 44/MK.01/2017", "Peraturan Pemerintah Nomor 42 Tahun 2004"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum Kode Etik Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengaturan resmi kode etik dan kode perilaku PNS Kemenkeu tertuang dalam <strong>PMK nomor 190/PMK.01/2018</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. PMK ini menjadi pedoman perilaku induk bagi seluruh pejabat dan staf kementerian dalam kedinasan. Pengaturan kode etik dan perilaku di lingkungan Kementerian Keuangan ditetapkan melalui PMK nomor 190/PMK.01/2018. Dengan demikian, regulasi menteri ini menjadi standar perilaku pegawai.</p><p>Jawaban tepat: <strong>A. PMK nomor 190/PMK.01/2018</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan SE tentang penerapan nilai Pancasila dan Bela Negara.</li><li><strong>C</strong>: Merupakan KMK tentang penerapan sistem kerja fleksibel di Kemenkeu.</li><li><strong>D</strong>: Merupakan SE tentang himbauan penerapan nilai dasar ASN Kemenkeu.</li><li><strong>E</strong>: Merupakan PP tentang pembinaan jiwa korps dan kode etik PNS nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PMK nomor 190/PMK.01/2018</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan SE Nomor 12/MK.01/2019, pimpinan unit kerja diwajibkan mendukung penerapan nilai Pancasila dengan memberikan...", "options": ["Insentif keuangan tambahan bagi pegawai berprestasi", "Keteladanan nyata dan dukungan program internalisasi", "Pemberian dispensasi jam kerja bagi staf teladan", "Kewenangan penuh kepada staf tanpa monitoring berkala", "Penyederhanaan format laporan pertanggungjawaban dinas"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Pimpinan Internalisasi Nilai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan unit kerja wajib memberikan <strong>keteladanan dan dukungan program</strong> terkait upaya internalisasi nilai-nilai Pancasila dan bela negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Internalisasi ideologi negara berhasil jika didukung komitmen manajerial dan teladan langsung pimpinan. Pimpinan unit wajib memberikan keteladanan serta memfasilitasi program penerapan nilai kebangsaan. Dengan demikian, keteladanan mendorong kepatuhan sukarela dari bawahan.</p><p>Jawaban tepat: <strong>B. Keteladanan nyata dan dukungan program internalisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kebijakan penghargaan material yang diatur dalam regulasi terpisah.</li><li><strong>C</strong>: Bertentangan dengan disiplin jam kerja ASN yang berlaku mengikat.</li><li><strong>D</strong>: Bertentangan dengan prinsip pengawasan dan akuntabilitas kepemimpinan.</li><li><strong>E</strong>: Merupakan efisiensi tata laksana persuratan, bukan internalisasi nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>keteladanan dan dukungan program</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Upaya nyata pimpinan satker dalam mencegah penyebaran paham radikalisme di lingkungan unit kerjanya dilakukan melalui...", "options": ["Penyelidikan intelijen mandiri terhadap kehidupan pribadi staf", "Pembatasan hak beribadah pegawai pada jam istirahat kantor", "Mendorong dan melakukan pengawasan internalisasi nilai", "Penghentian seluruh kegiatan pembinaan rohani di instansi", "Pemasangan kamera pemantau di seluruh ruangan tertutup"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengawasan Internalisasi Nilai Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan unit bertanggung jawab <strong>mendorong dan melakukan pengawasan</strong> terhadap bawahan dalam rangka internalisasi nilai kebangsaan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Pencegahan sikap intoleransi dilakukan melalui edukasi pembinaan dan pengawasan manajerial yang terukur. Pimpinan satker wajib mendorong dan mengawasi pelaksanaan nilai kebangsaan di unit kerjanya. Dengan demikian, pengawasan terarah menjaga ketenteraman suasana kerja.</p><p>Jawaban tepat: <strong>C. Mendorong dan melakukan pengawasan internalisasi nilai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Melanggar privasi individu di luar koridor kewenangan kedinasan.</li><li><strong>B</strong>: Melanggar hak konstitusional pegawai dalam menjalankan ibadah agamanya.</li><li><strong>D</strong>: Bertentangan dengan prinsip pembinaan mental spiritual aparatur negara.</li><li><strong>E</strong>: Merupakan pengawasan fisik berlebihan yang tidak proporsional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mendorong dan melakukan pengawasan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Menurut PMK Nomor 190/PMK.01/2018, Kode Etik dan Kode Perilaku pada hakikatnya berkedudukan sebagai...", "options": ["Standar kelayakan penerimaan fasilitas kendaraan dinas", "Petunjuk teknis pengoperasian sistem informasi keuangan", "Pedoman penetapan sasaran target kinerja tahunan satker", "Pedoman sikap, tingkah laku, dan perbuatan pegawai", "Panduan pelaksanaan audit keuangan internal instansi"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Kode Etik PMK 190/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan PMK 190/2018, <strong>Kode Etik dan Kode Perilaku adalah pedoman</strong> sikap, tingkah laku, dan perbuatan pegawai dalam bertugas dan bergaul.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Norma kode etik menjadi kompas moral setiap tindakan aparatur baik dalam dinas maupun pergaulan sosial. Ketentuan PMK 190/2018 menegaskan bahwa kode etik adalah pedoman sikap, perilaku, dan perbuatan pegawai. Dengan demikian, pedoman ini memandu integritas aparatur secara utuh.</p><p>Jawaban tepat: <strong>D. Pedoman sikap, tingkah laku, dan perbuatan pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan standarisasi barang milik negara fasilitas jabatan dinas.</li><li><strong>B</strong>: Merupakan manual sistem teknologi informasi perbendaharaan negara.</li><li><strong>C</strong>: Merupakan instrumen pengukuran perjanjian kinerja manajerial satker.</li><li><strong>E</strong>: Merupakan standar operasional pengawasan audit inspektorat jenderal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kode Etik dan Kode Perilaku adalah pedoman</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Komitmen bela negara pegawai ditujukan untuk membangun kesadaran berbangsa serta...", "options": ["Semangat mewujudkan negara yang berdaulat dan adil", "Peningkatan pendapatan negara bukan pajak satker", "Percepatan penyelesaian proses lelang pengadaan barang", "Pembentukan serikat pegawai mandiri di kementerian", "Penyelarasan pola hubungan kerja sama luar negeri"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Nilai Bela Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Bela Negara menumbuhkan <strong>semangat mewujudkan negara yang berdaulat, adil, dan makmur</strong> melalui tugas kedinasan sehari-hari.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Rasa cinta tanah air mendorong pegawai berkontribusi optimal bagi kedaulatan bangsa. Nilai bela negara mencakup semangat mewujudkan negara yang berdaulat, adil, dan sejahtera melalui kinerja birokrasi. Dengan demikian, bela negara bermuara pada kemajuan bangsa.</p><p>Jawaban tepat: <strong>A. Semangat mewujudkan negara yang berdaulat dan adil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan target fiskal penerimaan negara pada pos anggaran tertentu.</li><li><strong>C</strong>: Merupakan kecepatan tata kelola pengadaan barang dan jasa pemerintah.</li><li><strong>D</strong>: Merupakan organisasi serikat pekerja, bukan cita-cita kebangsaan bela negara.</li><li><strong>E</strong>: Merupakan hubungan diplomatik teknis antar-lembaga negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>semangat mewujudkan negara yang berdaulat</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan rumusan nilai kebangsaan, kemampuan bela negara aparatur sipil negara mencakup dimensi...", "options": ["Kemampuan manajerial dan penguasaan bahasa asing", "Kemampuan bela negara secara psikis maupun fisik", "Keahlian forensik digital dan audit investigasi", "Kapasitas negosiasi kontrak kerja sama komersial", "Keterampilan operasional persenjataan dinas khusus"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dimensi Kemampuan Bela Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Aparatur dituntut memiliki <strong>kemampuan bela negara baik secara psikis maupun fisik</strong> guna menghadapi dinamika tugas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Kesiapsiagaan membela negara membutuhkan ketahanan mental spiritual dan kebugaran jasmani yang prima. Ketentuan SE 12/2019 menegaskan kemampuan bela negara mencakup kesiapan psikis dan fisik pegawai. Dengan demikian, kedua dimensi ini melengkapi integritas kerja aparatur.</p><p>Jawaban tepat: <strong>B. Kemampuan bela negara secara psikis maupun fisik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kompetensi keahlian komunikasi internasional aparatur.</li><li><strong>C</strong>: Merupakan keterampilan teknis audit khusus pemeriksa kejahatan keuangan.</li><li><strong>D</strong>: Merupakan keahlian perikatan hukum komersial keperdataan bisnis.</li><li><strong>E</strong>: Merupakan keterampilan taktis satuan pengamanan khusus tertentu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memiliki kemampuan bela negara</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Sikap setia aparatur terhadap dasar falsafah bangsa diwujudkan melalui keyakinan teguh bahwa...", "options": ["Pancasila dapat dimodifikasi sesuai kepentingan kelompok", "Ideologi negara hanya mengikat pejabat eselon tinggi", "Pancasila adalah ideologi dan pedoman hidup bangsa", "Norma kebangsaan bersifat sekunder terhadap efisiensi", "Kebijakan publik dapat mengesampingkan nilai kemanusiaan"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ideologi Pancasila dalam Bela Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setiap aparatur wajib memiliki sikap <strong>yakin kepada Pancasila sebagai Ideologi negara</strong> yang memandu kehidupan bernegara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Falsafah Pancasila adalah landasan moral mutlak yang tidak boleh dikompromikan oleh birokrasi. Nilai dasar bela negara mewajibkan pegawai yakin kepada Pancasila sebagai ideologi bangsa yang final. Dengan demikian, keyakinan ideologi menjadi tameng dari ajaran radikal.</p><p>Jawaban tepat: <strong>C. Pancasila adalah ideologi dan pedoman hidup bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bertentangan dengan kedudukan Pancasila sebagai kesepakatan dasar negara.</li><li><strong>B</strong>: Bertentangan dengan asas kesetaraan kewajiban bagi seluruh tingkatan ASN.</li><li><strong>D</strong>: Menempatkan efisiensi teknis di atas falsafah konstitusional bangsa.</li><li><strong>E</strong>: Bertentangan dengan sila kedua Pancasila tentang kemanusiaan yang adil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>yakin kepada Pancasila sebagai Ideologi</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Penerapan rasa cinta tanah air oleh seorang pegawai perbendaharaan dalam tugas sehari-hari tercermin dari...", "options": ["Mengutamakan barang impor untuk perlengkapan kantor", "Memilih penugasan dinas hanya di wilayah perkotaan besar", "Membatasi pelayanan bagi masyarakat di luar wilayahnya", "Menjaga integritas pengelolaan keuangan demi rakyat", "Menyimpan seluruh dana pribadi di lembaga perbankan luar"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Cinta Tanah Air</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kecintaan kepada Tanah Air</strong> diwujudkan dengan menjaga amanah keuangan negara secara bertanggung jawab demi kemakmuran rakyat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Cinta tanah air bagi pegawai birokrasi dibuktikan lewat ketulusan mengabdi dan mencegah kebocoran anggaran. Menjaga integritas tata kelola kas negara adalah bentuk nyata cinta tanah air dalam profesi keuangan. Dengan demikian, profesionalisme aparatur mencerminkan patriotisme sejati.</p><p>Jawaban tepat: <strong>D. Menjaga integritas pengelolaan keuangan demi rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bertentangan dengan kebijakan mengutamakan produk dalam negeri (P3DN).</li><li><strong>B</strong>: Bertentangan dengan kesediaan ditempatkan di seluruh wilayah NKRI.</li><li><strong>C</strong>: Merupakan pelayanan diskriminatif yang melanggar kode etik publik.</li><li><strong>E</strong>: Mencerminkan ketidakpercayaan terhadap sistem perbankan nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kecintaan kepada Tanah Air</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Di lingkungan kerja yang majemuk, penerapan sila Persatuan Indonesia oleh pegawai dilakukan dengan cara...", "options": ["Membentuk perkumpulan pegawai berdasarkan suku asal", "Mengutamakan promosi jabatan bagi rekan sedaerah", "Membatasi pergaulan kerja hanya dengan rekan satu agama", "Membatasi koordinasi hanya pada unit kerja sendiri", "Menjunjung persaudaraan tanpa membedakan suku bangsa"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sila Persatuan Indonesia dalam Kedinasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengamalan sila <strong>Persatuan Indonesia</strong> menuntut setiap pegawai merawat persaudaraan dan kebersamaan di tengah keberagaman suku dan agama.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Keberagaman aparatur harus menjadi modal sosial sinergi, bukan pemicu sekat kedaerahan. Nilai Pancasila mewajibkan pegawai menjaga persatuan tanpa diskriminasi ras, suku, maupun latar belakang budaya. Dengan demikian, kerukunan memperkuat soliditas birokrasi pemerintah.</p><p>Jawaban tepat: <strong>E. Menjunjung persaudaraan tanpa membedakan suku bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Memicu primordialisme kedaerahan yang memecah kesatuan aparatur.</li><li><strong>B</strong>: Merupakan praktik nepotisme yang melanggar sistem merit kepegawaian.</li><li><strong>C</strong>: Menunjukkan sikap eksklusif intoleran yang merusak harmoni kerja.</li><li><strong>D</strong>: Mencerminkan sikap ego sektoral yang menghambat sinergi instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 16) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Persatuan Indonesia</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama penetapan pedoman sikap dan tingkah laku pegawai menurut PMK Nomor 190/PMK.01/2018 adalah...", "options": ["Mempercepat alur koordinasi penyusunan anggaran satker", "Menjaga martabat dan kehormatan pegawai serta negara", "Menetapkan besaran sanksi finansial bagi pelanggar", "Mengurangi interaksi kerja dengan masyarakat pengguna", "Menyeragamkan seluruh sistem operasional perkantoran"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Kode Etik PMK 190/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan kode etik dan kode perilaku bertujuan untuk <strong>menjaga martabat dan kehormatan Pegawai, bangsa, dan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Kehormatan institusi publik tercermin dari perilaku beretika setiap aparatur yang bertugas di dalamnya. PMK 190/2018 secara tegas menyatakan tujuan kode etik adalah menjaga martabat pegawai, bangsa, dan negara. Dengan demikian, kehormatan aparatur menjadi benteng kepercayaan publik.</p><p>Jawaban tepat: <strong>B. Menjaga martabat dan kehormatan pegawai serta negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan efisiensi teknis administrasi perencanaan anggaran kantor.</li><li><strong>C</strong>: Merupakan materi hukum pidana ganti rugi, bukan sasaran kode etik.</li><li><strong>D</strong>: Bertentangan dengan misi pelayanan publik yang responsif dan terbuka.</li><li><strong>E</strong>: Merupakan standarisasi operasional fasilitas fisik perkantoran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kehormatan Pegawai, bangsa, dan negara</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam berperilaku sehari-hari, setiap PNS di lingkungan Kementerian Keuangan wajib berlandaskan pada...", "options": ["Arahan lisan atasan langsung dan kesepakatan tim", "Kebiasaan informal yang telah lama berjalan di unit", "Nilai dasar ASN dan Nilai-Nilai Kementerian Keuangan", "Petunjuk praktis penyelesaian target belanja operasional", "Standar operasional prosedur instansi daerah setempat"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Landasan Perilaku Pegawai Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Landasan perilaku pegawai Kemenkeu bersumber pada perpaduan <strong>nilai dasar Aparatur Sipil Negara dan Nilai-Nilai Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Perilaku kedinasan tidak boleh disandarkan pada persepsi pribadi melainkan pada kerangka nilai yang sah. Ketentuan modul menegaskan perilaku pegawai harus berlandaskan nilai dasar ASN serta Nilai-Nilai Kementerian Keuangan. Dengan demikian, standar ganda perilaku dapat dihindari.</p><p>Jawaban tepat: <strong>C. Nilai dasar ASN dan Nilai-Nilai Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan arahan operasional teknis, bukan landasan filosofis nilai perilaku.</li><li><strong>B</strong>: Berpotensi melestarikan budaya permisif yang menyimpang dari aturan.</li><li><strong>D</strong>: Merupakan petunjuk teknis keuangan, bukan pedoman moral aparatur.</li><li><strong>E</strong>: Merupakan regulasi daerah yang tidak berlaku mengikat bagi instansi vertikal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Landasan Perilaku Pegawai • nilai dasar Aparatur Sipil Negara</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Makna nilai Integritas di lingkungan Kementerian Keuangan menuntut seluruh pegawai untuk...", "options": ["Menyelesaikan seluruh beban pekerjaan dengan waktu tercepat", "Mengutamakan kepuasan pemangku kepentingan di atas aturan", "Menghasilkan karya berkualitas melalui inovasi teknologi", "Berpikir, berkata, berperilaku, dan bertindak benar", "Melakukan koordinasi intensif dengan seluruh mitra kerja"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Nilai Integritas Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai <strong>Integritas</strong> mewajibkan pegawai <strong>berpikir, berkata, berperilaku, dan bertindak dengan baik dan benar</strong> sesuai prinsip moral.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Integritas adalah keselarasan mutlak antara hati nurani, perkataan, dan perbuatan nyata pegawai. Nilai integritas Kemenkeu mewajibkan pegawai berpikir, berkata, berperilaku, dan bertindak secara benar dan jujur. Dengan demikian, konsistensi moral menjadi inti utama integritas.</p><p>Jawaban tepat: <strong>D. Berpikir, berkata, berperilaku, dan bertindak benar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan aspek kecepatan kerja pada nilai profesionalisme.</li><li><strong>B</strong>: Bertentangan dengan prinsip kepatuhan hukum pelayanan publik.</li><li><strong>C</strong>: Merupakan esensi dari nilai kesempurnaan dalam berinovasi.</li><li><strong>E</strong>: Merupakan perwujudan dari nilai sinergi dalam berkoordinasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memegang teguh Kode Etik</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Karakteristik utama aparatur yang menjunjung nilai Profesionalisme menurut standar Kementerian Keuangan adalah...", "options": ["Menjaga keharmonisan hubungan kerja antar-kelompok", "Melakukan evaluasi berkala terhadap kepuasan pelanggan", "Mengutamakan keteladanan pimpinan dalam bertindak", "Memegang teguh sumpah jabatan dan rahasia negara", "Bekerja dengan tuntas dan akurat sesuai kompetensi"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Nilai Profesionalisme Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai <strong>Profesionalisme</strong> berarti pegawai <strong>bekerja dengan tuntas dan akurat berdasarkan kompetensi terbaik</strong> dan penuh tanggung jawab.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Profesionalitas dibuktikan dengan penguasaan keahlian teknis dan ketelitian menuntaskan pekerjaan. Standar Kemenkeu mendefinisikan profesionalisme sebagai bekerja tuntas dan akurat dengan keahlian terbaik. Dengan demikian, kualitas output kerja menjadi bukti profesionalisme.</p><p>Jawaban tepat: <strong>E. Bekerja dengan tuntas dan akurat sesuai kompetensi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan substansi penting dari nilai sinergi kelembagaan.</li><li><strong>B</strong>: Merupakan indikator pemenuhan mutu pada nilai pelayanan.</li><li><strong>C</strong>: Merupakan peran kepemimpinan dalam internalisasi budaya etis.</li><li><strong>D</strong>: Merupakan butir perilaku pokok pada nilai integritas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penuh tanggung jawab</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Komitmen membangun kemitraan harmonis guna menghasilkan karya bermanfaat di Kementerian Keuangan merupakan wujud nilai...", "options": ["Sinergi", "Integritas", "Pelayanan", "Kesempurnaan", "Profesionalisme"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Nilai Sinergi Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai <strong>Sinergi</strong> diwujudkan dengan memastikan <strong>hubungan kerjasama internal yang produktif serta kemitraan yang harmonis</strong> demi karya berkualitas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Sinergi menolak ego sektoral dan mengedepankan kolaborasi produktif antar-satuan kerja. Nilai sinergi menuntut komitmen membangun kemitraan produktif untuk menghasilkan karya yang bermanfaat. Dengan demikian, keberhasilan tujuan bersama dicapai lewat kerja sama.</p><p>Jawaban tepat: <strong>A. Sinergi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Menitikberatkan pada kejujuran berpikir, berkata, dan bertindak.</li><li><strong>C</strong>: Menitikberatkan pada pemenuhan kepuasan pemangku kepentingan layanan.</li><li><strong>D</strong>: Menitikberatkan pada perbaikan berkelanjutan di segala bidang.</li><li><strong>E</strong>: Menitikberatkan pada penuntasan pekerjaan secara akurat dan kompeten.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>hubungan kerjasama internal yang produktif</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan tugas untuk memenuhi kepuasan pemangku kepentingan secara sepenuh hati, cepat, dan transparan merupakan perwujudan nilai...", "options": ["Integritas", "Sinergi", "Pelayanan", "Profesionalisme", "Kesempurnaan"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Nilai Pelayanan Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai <strong>Pelayanan</strong> menuntut pemberian layanan kepuasan bagi pemangku kepentingan secara <strong>sepenuh hati, transparan, cepat, akurat, dan aman</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Keberadaan aparatur sipil negara berorientasi utama pada pengabdian kepada masyarakat pemohon layanan. Nilai pelayanan diartikan sebagai pemberian layanan sepenuh hati yang transparan, cepat, dan akurat. Dengan demikian, kepuasan publik menjadi tolok ukur nilai ini.</p><p>Jawaban tepat: <strong>C. Pelayanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Berfokus pada keselarasan moralitas batin dan kejujuran sikap.</li><li><strong>B</strong>: Berfokus pada kerja sama kemitraan produktif antar-unit kerja.</li><li><strong>D</strong>: Berfokus pada akurasi penuntasan tugas berbasis keahlian terbaik.</li><li><strong>E</strong>: Berfokus pada inovasi dan continuous improvement di segala bidang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sepenuh hati, transparan, cepat, akurat, dan aman</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Semangat pantang berpuas diri yang mendorong aparatur melakukan inovasi dan perbaikan tanpa henti merupakan esensi dari nilai...", "options": ["Integritas", "Profesionalisme", "Pelayanan", "Kesempurnaan", "Sinergi"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Nilai Kesempurnaan Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai <strong>Kesempurnaan</strong> berarti seluruh pegawai senantiasa melakukan <strong>upaya perbaikan di segala bidang untuk menjadi dan memberikan yang terbaik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Nilai ini mendorong lahirnya terobosan kreatif yang memajukan efektivitas birokrasi pemerintahan. Kesempurnaan dimaknai sebagai upaya perbaikan berkelanjutan di segala lini demi hasil terbaik. Dengan demikian, continuous improvement menjadi jiwa nilai kesempurnaan.</p><p>Jawaban tepat: <strong>D. Kesempurnaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menekankan pada kepatuhan prinsip etika moral dan kejujuran.</li><li><strong>B</strong>: Menekankan pada penyelesaian tugas secara cermat dan bertanggung jawab.</li><li><strong>C</strong>: Menekankan pada ketulusan melayani kebutuhan para pemangku kepentingan.</li><li><strong>E</strong>: Menekankan pada keharmonisan koordinasi dan kerja sama antar-pihak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>perbaikan di segala bidang</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Saat menghadiri seminar internasional mewakili kementerian, tindakan pegawai yang mencerminkan kode etik nilai Integritas adalah...", "options": ["Menyampaikan pandangan subjektif di luar kebijakan resmi kantor", "Menerima fasilitas akomodasi mewah dari pihak penyelenggara swasta", "Membatasi komunikasi hanya dengan peserta dari negara sendiri", "Membagikan data internal keuangan negara demi popularitas riset", "Menjaga citra, harkat, dan martabat kementerian di forum publik"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Nilai Integritas: Citra Kementerian</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib <strong>menjaga citra, harkat, dan martabat Kementerian Keuangan</strong> di berbagai forum resmi maupun informal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Pegawai yang bertugas mewakili institusi memikul tanggung jawab menjaga nama baik kementerian di mata dunia. Kode etik nilai integritas mewajibkan menjaga citra, harkat, dan martabat kementerian di forum internasional. Dengan demikian, kehormatan negara tetap terlindungi.</p><p>Jawaban tepat: <strong>E. Menjaga citra, harkat, dan martabat kementerian di forum publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menimbulkan distorsi kebijakan publik resmi instansi pemerintah.</li><li><strong>B</strong>: Melanggar larangan penerimaan gratifikasi dan fasilitas non-kedinasan.</li><li><strong>C</strong>: Mencerminkan sikap eksklusif yang menghambat diplomasi kelembagaan.</li><li><strong>D</strong>: Melanggar kewajiban menjaga kerahasiaan data dan rahasia jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjaga citra, harkat, dan martabat</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan PMK 190/PMK.01/2018, kewajiban etis yang secara langsung mengikat komitmen spiritual pegawai adalah...", "options": ["Memegang teguh sumpah jabatan Pegawai Negeri Sipil", "Menghadiri rapat koordinasi unit kerja tepat waktu", "Mengikuti kegiatan senam kesegaran jasmani mingguan", "Memperbarui sertifikasi keahlian teknis perbendaharaan", "Menyusun rekapitulasi data kehadiran harian staf"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Nilai Integritas: Sumpah Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Implementasi nilai integritas mewajibkan setiap pegawai untuk <strong>memegang teguh sumpah jabatan Pegawai Negeri Sipil</strong> secara konsekuen.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Sumpah jabatan adalah janji suci di hadapan Tuhan Yang Maha Esa untuk setia pada negara dan jabatan. PMK 190/2018 menempatkan kewajiban memegang teguh sumpah jabatan sebagai butir utama nilai integritas. Dengan demikian, sumpah jabatan mengikat nurani aparatur.</p><p>Jawaban tepat: <strong>A. Memegang teguh sumpah jabatan Pegawai Negeri Sipil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan bentuk disiplin waktu kedinasan, bukan ikrar komitmen spiritual.</li><li><strong>C</strong>: Merupakan kegiatan kebugaran jasmani rutin perkantoran.</li><li><strong>D</strong>: Merupakan pemeliharaan kompetensi teknis profesional pegawai.</li><li><strong>E</strong>: Merupakan tugas tata usaha pencatatan absensi kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memegang teguh sumpah jabatan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kepatuhan aparatur sipil negara terhadap adat istiadat dan kaidah kepatutan sosial merupakan implementasi kewajiban...", "options": ["Menyusun laporan kinerja masyarakat berkala", "Menjunjung tinggi norma yang berlaku dalam masyarakat", "Memfasilitasi pembentukan forum musyawarah warga", "Membatasi interaksi sosial di luar lingkungan kantor", "Menyesuaikan jam operasional dengan kegiatan adat"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Nilai Integritas: Norma Masyarakat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Aparatur wajib <strong>menjunjung tinggi norma yang berlaku dalam masyarakat serta Kode Etik dan Kode Perilaku profesi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Perilaku aparatur di tengah masyarakat menjadi cerminan langsung dari integritas birokrasi negara. Ketentuan nilai integritas mewajibkan aparatur menjunjung norma kepatutan sosial yang hidup di masyarakat. Dengan demikian, keteladanan sosial memperkokoh kedudukan aparatur.</p><p>Jawaban tepat: <strong>B. Menjunjung tinggi norma yang berlaku dalam masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan fungsi pelaporan manajerial, bukan kepatuhan norma sosial.</li><li><strong>C</strong>: Merupakan kegiatan kemasyarakatan yang menjadi wewenang pamong praja.</li><li><strong>D</strong>: Menjadikan aparatur terisolasi dari pergaulan sosial warga sekitar.</li><li><strong>E</strong>: Jadwal operasional kantor pemerintah tunduk pada ketentuan jam dinas resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjunjung tinggi norma yang berlaku dalam masyarakat</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat pengadaan mendapati perusahaan milik kerabat dekatnya mendaftar dalam lelang tender dinas. Sikap etis pejabat tersebut adalah...", "options": ["Memberikan pembocoran harga perkiraan sendiri secara rahasia", "Membantu perusahaan kerabat melengkapi berkas administrasi", "Menetapkan persyaratan teknis yang menguntungkan kerabat", "Menghindari konflik kepentingan dengan menyatakan mundur dari proses", "Melanjutkan evaluasi tender dengan alasan objektivitas pribadi"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Integritas: Konflik Kepentingan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib tegas <strong>menghindari konflik kepentingan pribadi, kelompok maupun golongan</strong> dalam setiap pelaksanaan wewenang kedinasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Benturan kepentingan merusak keadilan proses tender publik dan menciderai integritas jabatan aparatur. Pejabat bersangkutan wajib mendeklarasikan benturan kepentingan dan mundur dari kepanitiaan tender tersebut. Dengan demikian, objektivitas dan keadilan lelang negara terjamin murni.</p><p>Jawaban tepat: <strong>D. Menghindari konflik kepentingan dengan menyatakan mundur dari proses</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tindak pidana korupsi pembocoran dokumen rahasia lelang.</li><li><strong>B</strong>: Mencerminkan sikap keberpihakan yang melanggar asas persaingan sehat.</li><li><strong>C</strong>: Merupakan manipulasi kriteria lelang demi kepentingan keluarga.</li><li><strong>E</strong>: Tetap berisiko tinggi bias keputusan dan melanggar prinsip kepatutan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menghindari konflik kepentingan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Dalam menghadapi masa kampanye pemilihan kepala daerah, perwujudan netralitas aparatur sipil negara dilakukan dengan...", "options": ["Mengunggah foto bersama pasangan calon di akun media sosial", "Memfasilitasi penggunaan aula kantor untuk kegiatan kampanye", "Menghadiri deklarasi partai politik dengan mengenakan seragam dinas", "Mengajak rekan kerja memilih figur tertentu saat rapat kedinasan", "Bersikap netral dan bebas intervensi politik praktis"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Integritas: Netralitas Pemilu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib <strong>bersikap netral dalam Pemilihan Calon Presiden/Wapres, Kepala Daerah, serta Anggota Legislatif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Netralitas politik merupakan harga mati agar aparatur tetap profesional melayani seluruh rakyat. Kode etik integritas menegaskan larangan keterlibatan maupun keberpihakan dalam kontestasi pemilu. Dengan demikian, netralitas menjaga profesionalisme birokrasi pemerintahan.</p><p>Jawaban tepat: <strong>E. Bersikap netral dan bebas intervensi politik praktis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pelanggaran netralitas digital yang dilarang undang-undang.</li><li><strong>B</strong>: Merupakan penyalahgunaan fasilitas aset negara demi kepentingan politik.</li><li><strong>C</strong>: Mencerminkan pelanggaran disiplin berat netralitas aparatur sipil negara.</li><li><strong>D</strong>: Merupakan intimidasi politik di lingkungan kerja yang dilarang keras.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bersikap netral dalam Pemilihan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Ketika menemukan konten berita bernuansa provokasi dan belum terverifikasi kebenarannya, sikap etis pegawai di media sosial adalah...", "options": ["Menggunakan media sosial dengan bijak dan menahan diri", "Meneruskan berita tersebut ke seluruh grup percakapan dinas", "Memberikan komentar spekulatif pada postingan publik", "Mengunggah ulang konten tersebut untuk mencari klarifikasi warga", "Membuat postingan tandingan yang menyerang pembuat konten"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Integritas: Etika Media Sosial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai Kementerian Keuangan diwajibkan <strong>menggunakan media sosial dengan bijak</strong> serta tidak menyebarkan kabar bohong atau provokatif.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Aktivitas di ruang siber mencerminkan kedewasaan dan integritas pribadi pegawai aparatur negara. Bijak bermedia sosial menuntut kehati-hatian dalam menyaring informasi sebelum membagikannya ke publik. Dengan demikian, ruang publik digital tetap damai dan sehat.</p><p>Jawaban tepat: <strong>A. Menggunakan media sosial dengan bijak dan menahan diri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Memperluas disinformasi yang meresahkan pegawai di lingkungan kantor.</li><li><strong>C</strong>: Menunjukkan ketidakmatangan bersikap aparatur di ruang publik maya.</li><li><strong>D</strong>: Berpotensi melanggar ketentuan hukum terkait penyebaran hoaks digital.</li><li><strong>E</strong>: Menimbulkan eskalasi konflik horizontal di media sosial masyarakat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menggunakan media sosial dengan bijak</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kewajiban etis berbicara dan bertindak secara jujur dan pantas menuntut aparatur untuk...", "options": ["Menyajikan data estimasi saat laporan realisasi disusun", "Menyampaikan informasi sesuai fakta dan kebenaran yang sah", "Menyampaikan keterangan yang menyenangkan atasan semata", "Membatasi akses berkas yang diminta oleh pemeriksa dinas", "Memanipulasi tanggal penyelesaian dokumen verifikasi anggaran"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Integritas: Berkata Jujur dan Pantas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib <strong>berbicara dan bertindak secara jujur dan pantas sesuai dengan fakta dan kebenaran sesuai ketentuan yang berlaku</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Fakta kebenaran merupakan pijakan akuntabilitas setiap kebijakan dan pelaporan perbendaharaan. Aparatur wajib berbicara dan bertindak sesuai fakta riil tanpa memutarbalikkan data kedinasan. Dengan demikian, kejujuran menjaga keabsahan dokumen negara.</p><p>Jawaban tepat: <strong>B. Menyampaikan informasi sesuai fakta dan kebenaran yang sah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kecurangan administratif yang mencederai akuntabilitas pelaporan.</li><li><strong>C</strong>: Mencerminkan sikap menjilat yang mengabaikan kebenaran fakta objektif.</li><li><strong>D</strong>: Merupakan pembangkangan terhadap fungsi pengawasan inspektorat jenderal.</li><li><strong>E</strong>: Merupakan pemalsuan dokumen kedinasan yang melanggar hukum disiplin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jujur dan pantas sesuai dengan fakta</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan kode etik nilai Profesionalisme, prioritas utama yang wajib didahulukan aparatur dalam bertugas adalah...", "options": ["Kepentingan pribadi demi peningkatan kesejahteraan keluarga", "Kepentingan golongan yang mendukung karier jabatan staf", "Kepentingan bangsa dan organisasi di atas kepentingan pribadi", "Kepentingan mitra bisnis penyedia jasa pengadaan barang", "Kepentingan serikat pegawai dalam memperoleh fasilitas kantor"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Profesionalisme: Kepentingan Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam nilai Profesionalisme, pegawai wajib <strong>mengutamakan kepentingan bangsa dan organisasi di atas kepentingan pribadi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Dedikasi aparatur sipil negara diabdikan seutuhnya bagi kemaslahatan negara dan pelayanan publik. Sikap profesional menempatkan kepentingan bangsa dan organisasi di atas kepentingan individu atau kelompok. Dengan demikian, loyalitas negara mengatasi kepentingan sektoral.</p><p>Jawaban tepat: <strong>C. Kepentingan bangsa dan organisasi di atas kepentingan pribadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mencerminkan sikap mementingkan diri sendiri yang merusak etika dinas.</li><li><strong>B</strong>: Merupakan primordialisme kelompok yang memicu konflik kepentingan.</li><li><strong>D</strong>: Menimbulkan potensi persekongkolan tender yang melanggar hukum.</li><li><strong>E</strong>: Mengalihkan fokus pengabdian publik ke tuntutan fasilitas internal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengutamakan kepentingan bangsa dan organisasi</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Saat menghadapi desakan untuk mencairkan tagihan belanja tanpa berkas verifikasi lengkap, tindakan staf perbendaharaan yang profesional adalah...", "options": ["Menerbitkan surat pencairan sambil meminta kelengkapan menyusul", "Mengembalikan berkas tagihan tanpa memberikan penjelasan perbaikan", "Meminta komisi percepatan verifikasi dokumen belanja modal", "Mengalihkan penanganan berkas kepada petugas verifikasi lainnya", "Bekerja sesuai SOP dan kewenangan jabatan yang sah"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Profesionalisme: Kepatuhan SOP</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib <strong>bekerja sesuai standar operasional prosedur dan kewenangan jabatan</strong> tanpa terpengaruh tekanan pihak manapun.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Kepatuhan pada SOP melindungi keuangan negara dari kebocoran dan melindungi aparatur dari risiko hukum. Sikap profesional menuntut pelaksanaan verifikasi belanja sesuai koridor SOP dan batas wewenang. Dengan demikian, integritas perbendaharaan terjaga sempurna.</p><p>Jawaban tepat: <strong>E. Bekerja sesuai SOP dan kewenangan jabatan yang sah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Melanggar prinsip kehati-hatian pengujian belanja perbendaharaan negara.</li><li><strong>B</strong>: Mencerminkan sikap lepas tangan yang tidak memberikan solusi pelayanan.</li><li><strong>C</strong>: Merupakan tindak pidana pemerasan dan penerimaan suap gratifikasi.</li><li><strong>D</strong>: Merupakan penghindaran tanggung jawab tugas verifikasi kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bekerja sesuai standar operasional prosedur</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang staf pengolah data perpajakan diminta data wajib pajak oleh teman dekatnya untuk kepentingan skripsi. Tindakan yang etis adalah...", "options": ["Menjaga kerahasiaan data sesuai ketentuan perundang-undangan", "Memberikan data dengan menghapus nama lengkap wajib pajak", "Mengizinkan teman mengakses komputer kantor pada malam hari", "Menjual basis data riset tersebut dengan harga pengganti cetak", "Membuat ringkasan data rahasia untuk dipublikasikan di blog"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Profesionalisme: Kerahasiaan Data</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib <strong>menjaga informasi dan data Kementerian Keuangan yang bersifat rahasia</strong> dari akses pihak yang tidak berhak.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Perlindungan data keuangan dan privasi wajib pajak dilindungi secara ketat oleh undang-undang perpajakan. Menjaga kerahasiaan informasi negara merupakan kewajiban mutlak nilai profesionalisme aparatur. Dengan demikian, kebocoran data rahasia jabatan dapat dicegah total.</p><p>Jawaban tepat: <strong>A. Menjaga kerahasiaan data sesuai ketentuan perundang-undangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Tetap melanggar kerahasiaan basis data fiskal yang dikecualikan publik.</li><li><strong>C</strong>: Merupakan pelanggaran keamanan fisik dan siber fasilitas perkantoran.</li><li><strong>D</strong>: Merupakan kejahatan perdagangan data rahasia negara secara ilegal.</li><li><strong>E</strong>: Merupakan pembocoran rahasia kedinasan ke media publik daring.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjaga informasi dan data Kementerian Keuangan yang bersifat rahasia</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Ketika menyadari adanya kekeliruan input angka dalam laporan kas yang baru saja dikirim ke atasan, tindakan kesatria pegawai adalah...", "options": ["Mengubah data di sistem secara diam-diam tanpa melapor", "Berani mengakui kesalahan dan segera memperbaikinya", "Menyalahkan staf magang yang membantu proses pengetikan", "Menunggu hingga ada audit resmi dari pihak pengawas", "Menghapus berkas cadangan laporan agar tidak dapat dilacak"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Profesionalisme: Pengakuan Kesalahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sikap profesional menuntut pegawai <strong>berani mengakui kesalahan dan bertanggung jawab atas pelaksanaan tugasnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Mengakui kekhilafan dan bersegera membetulkan data membuktikan kejujuran dan kedewasaan profesional pegawai. Kode etik profesionalisme mewajibkan sikap berani mengakui kesalahan serta mengambil tanggung jawab. Dengan demikian, validitas data pelaporan dapat segera dipulihkan.</p><p>Jawaban tepat: <strong>B. Berani mengakui kesalahan dan segera memperbaikinya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tindakan manipulasi data gelap yang menyalahi prosedur dinas.</li><li><strong>C</strong>: Merupakan sikap melempar tanggung jawab yang mencederai etika profesi.</li><li><strong>D</strong>: Membiarkan kesalahan berlanjut hingga berdampak pada keputusan salah.</li><li><strong>E</strong>: Merupakan penghilangan barang bukti administrasi yang melanggar hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berani mengakui kesalahan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perwujudan nilai Sinergi dalam menghargai keragaman latar belakang pegawai di lingkungan kementerian diwujudkan melalui...", "options": ["Pemisahan kelompok kerja berdasarkan latar belakang daerah", "Penyeragaman seluruh keyakinan agama di lingkungan kerja", "Menghormati dan menghargai perbedaan latar belakang pegawai", "Pembatasan hak berpendapat bagi pegawai dari luar pulau", "Pemberian perlakuan khusus bagi pegawai kelompok mayoritas"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Nilai Sinergi: Menghargai Keberagaman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib <strong>menghormati dan menghargai perbedaan latar belakang, ras, warna kulit, agama, asal-usul, jenis kelamin, status pernikahan, umur atau kondisi kecacatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Keberagaman aparatur adalah kekuatan bersama yang harus dirawat dengan sikap saling menghargai. Kode etik sinergi mewajibkan penghormatan terhadap perbedaan suku, agama, ras, dan kondisi fisik. Dengan demikian, suasana kerja inklusif dan harmonis dapat tercipta.</p><p>Jawaban tepat: <strong>C. Menghormati dan menghargai perbedaan latar belakang pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menciptakan segregasi sosial yang merusak kesatuan unit kerja.</li><li><strong>B</strong>: Melanggar kemerdekaan memeluk agama dan kebebasan beribadah.</li><li><strong>D</strong>: Merupakan diskriminasi kedaerahan yang melanggar hak asasi aparatur.</li><li><strong>E</strong>: Melanggar prinsip meritokrasi dan keadilan perlakuan kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menghormati dan menghargai perbedaan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Ketika unit kerja lain mengalami kendala teknis dalam rekonsiliasi data belanja, tindakan sinergi yang tepat adalah...", "options": ["Fokus menyelesaikan target seksi kerja sendiri hingga tuntas", "Melaporkan kelemahan unit kerja lain ke media massa publik", "Menuntut imbalan jasa konsultasi atas bantuan yang diberikan", "Berbagi solusi dan data sesuai kewenangan untuk masalah dinas", "Mengalihkan penanganan seluruh rekonsiliasi ke unit sendiri"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kode Etik Nilai Sinergi: Berbagi Solusi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam nilai Sinergi, pegawai wajib <strong>bersikap untuk berbagi solusi, informasi dan/atau data sesuai kewenangan untuk menyelesaikan masalah yang terkait dengan pekerjaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Kolaborasi lintas unit menuntut keterbukaan berbagi pengetahuan dan solusi demi keberhasilan institusi. Berbagi solusi dan data sesuai wewenang merupakan wujud nyata nilai sinergi dalam menyelesaikan masalah kedinasan. Dengan demikian, efektivitas penuntasan target organisasi tercapai.</p><p>Jawaban tepat: <strong>D. Berbagi solusi dan data sesuai kewenangan untuk masalah dinas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mencerminkan sikap ego sektoral yang menghambat penyelesaian tugas.</li><li><strong>B</strong>: Merusak citra kementerian dan melanggar kode etik kerahasiaan.</li><li><strong>C</strong>: Merupakan praktik pungli terselubung antar-aparatur negara.</li><li><strong>E</strong>: Melanggar pembagian tugas wewenang dan tata laksana organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 18) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bersikap untuk berbagi solusi, informasi dan/atau data</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Ketika rapat dinas yang padat bertepatan dengan waktu salat zuhur, sikap pimpinan rapat yang menjunjung nilai Sinergi adalah...", "options": ["Memberikan kesempatan menunaikan ibadah secara proporsional", "Melanjutkan rapat hingga seluruh agenda tuntas dibahas", "Meminta peserta beribadah bergantian tanpa menghentikan rapat", "Mengurangi hak istirahat peserta pada agenda rapat berikutnya", "Mewajibkan ibadah dirapel setelah seluruh rapat kedinasan usai"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Sinergi: Toleransi Beribadah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Sinergi mewajibkan pegawai <strong>memberikan kesempatan untuk menunaikan ibadah ketika rapat kerja atau tugas kedinasan sedang berlangsung</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Penghormatan terhadap kewajiban keagamaan rekan kerja adalah wujud toleransi beragama yang luhur dalam birokrasi. Ketentuan kode etik sinergi mewajibkan pimpinan memberi jeda kesempatan beribadah saat rapat kerja. Dengan demikian, hak spiritual aparatur terpenuhi secara berimbang.</p><p>Jawaban tepat: <strong>A. Memberikan kesempatan menunaikan ibadah secara proporsional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mengesampingkan hak konstitusional beribadah demi efisiensi waktu rapat.</li><li><strong>C</strong>: Mengganggu konsentrasi peserta dan efektivitas jalannya pembahasan rapat.</li><li><strong>D</strong>: Merupakan pembatasan hak rehat kedinasan yang tidak manusiawi.</li><li><strong>E</strong>: Memaksakan penundaan ibadah yang bertentangan dengan ajaran agama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memberikan kesempatan untuk menunaikan ibadah</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Standar sikap aparatur dalam memberikan pelayanan publik kepada masyarakat menurut kode etik Kementerian Keuangan adalah...", "options": ["Menjaga jarak emosional dan membatasi tutur kata formal", "Menunjukkan kepedulian, ramah, dan santun dalam melayani", "Mengutamakan layanan bagi pemohon yang membawa rekomendasi", "Mempercepat proses jika pemohon memberikan imbalan sukarela", "Menyerahkan urusan layanan kepada tenaga pembantu lapangan"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Pelayanan: Sikap Ramah dan Santun</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam kode etik nilai Pelayanan, pegawai wajib <strong>menunjukkan kepedulian, ramah, dan santun dalam memberikan pelayanan</strong> kepada pemangku kepentingan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Keramahan dan empati merupakan cerminan birokrasi yang melayani dan memanusiakan warga masyarakat. Standar nilai pelayanan menuntut aparatur selalu peduli, bersikap ramah, dan santun kepada setiap pemohon. Dengan demikian, kepuasan dan kepercayaan masyarakat meningkat.</p><p>Jawaban tepat: <strong>B. Menunjukkan kepedulian, ramah, dan santun dalam melayani</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menciptakan kesan birokrasi kaku dan tidak bersahabat bagi publik.</li><li><strong>C</strong>: Merupakan praktik diskriminasi layanan berbasis koneksi informal.</li><li><strong>D</strong>: Merupakan tindak pidana korupsi pungutan liar yang dilarang keras.</li><li><strong>E</strong>: Merupakan pelemparan tanggung jawab tugas pokok jabatan aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menunjukkan kepedulian, ramah, dan santun</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Komitmen aparatur dalam menjamin kepastian proses perizinan diwujudkan melalui pemberian...", "options": ["Jaminan kelonggaran syarat administrasi bagi pemohon tertentu", "Kemudahan akses jalur khusus melalui perantara perizinan", "Layanan yang tepat waktu, cepat, dan transparan bagi semua", "Keringanan biaya retribusi tanpa dasar regulasi yang sah", "Pemberian tanda terima informal sebelum berkas diverifikasi"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Pelayanan: Kepastian Waktu dan Transparansi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib <strong>berupaya memberikan layanan yang tepat waktu, cepat, dan transparan</strong> kepada seluruh pemangku kepentingan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Kepastian waktu dan keterbukaan informasi biaya serta alur layanan mencegah terjadinya praktik pungli. Kode etik pelayanan mewajibkan pemberian layanan tepat waktu, cepat, dan transparan. Dengan demikian, efisiensi pelayanan publik dirasakan merata oleh masyarakat.</p><p>Jawaban tepat: <strong>C. Layanan yang tepat waktu, cepat, dan transparan bagi semua</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penyimpangan prosedur yang melanggar kepatutan hukum.</li><li><strong>B</strong>: Melestarikan praktik percaloan yang merugikan masyarakat pengguna.</li><li><strong>D</strong>: Merupakan pungutan liar atau manipulasi penerimaan negara bukan pajak.</li><li><strong>E</strong>: Menyalahi tertib administrasi pencatatan dokumen resmi loket.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>layanan yang tepat waktu, cepat, dan transparan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Saat loket pelayanan penuh antrean, seorang warga lansia meminta bantuan pendaftaran. Sikap petugas loket yang etis dan adil adalah...", "options": ["Menyuruh warga lansia tersebut kembali esok hari saat antrean sepi", "Meminta warga lain mengalah secara paksa tanpa mekanisme prioritas", "Mengarahkan warga lansia mengikuti antrean reguler umum", "Melayani secara adil sesuai mekanisme loket ramah kelompok rentan", "Menyarankan warga lansia menggunakan jasa calo di luar gedung"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Pelayanan: Keadilan Layanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib <strong>tidak membeda-bedakan dan bersikap adil dalam memberikan pelayanan</strong> kepada seluruh lapisan masyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Pelayanan inklusif menyediakan fasilitas prioritas bagi kelompok rentan tanpa diskriminasi. Sikap adil ditunjukkan dengan melayani ramah sesuai fasilitas kelompok rentan yang telah diatur resmi. Dengan demikian, asas keadilan layanan publik terpenuhi secara utuh.</p><p>Jawaban tepat: <strong>D. Melayani secara adil sesuai mekanisme loket ramah kelompok rentan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menolak melayani warga rentan dan mencerminkan pelayanan buruk.</li><li><strong>B</strong>: Menimbulkan kericuhan antrean dan mengabaikan ketertiban umum.</li><li><strong>C</strong>: Menunjukkan ketiadaan empati dan kepedulian terhadap warga lansia.</li><li><strong>E</strong>: Mengarahkan masyarakat pada percaloan ilegal yang melanggar hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak membeda-bedakan dan bersikap adil</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perwujudan nilai Kesempurnaan dalam menerima evaluasi dan saran dari bawahan dicerminkan oleh sikap...", "options": ["Mempertahankan metode kerja lama demi menjaga kestabilan", "Membatasi ruang diskusi staf agar rapat berjalan cepat", "Menunda pembahasan masukan teknis dari staf pelaksana", "Menegur staf yang mengkritik prosedur kerja saat rapat", "Terbuka terhadap usulan perbaikan demi kemajuan kinerja"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Kesempurnaan: Keterbukaan Usulan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam kode etik nilai Kesempurnaan, pegawai dituntut <strong>terbuka terhadap usulan perbaikan</strong> serta gagasan baru yang konstruktif.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Kemajuan organisasi birokrasi bermula dari kerendahan hati mendengarkan masukan penyempurnaan proses kerja. Sikap terbuka terhadap usulan perbaikan mendorong lahirnya inovasi pelayanan yang lebih efektif. Dengan demikian, budaya perbaikan berkelanjutan dapat tumbuh subur.</p><p>Jawaban tepat: <strong>E. Terbuka terhadap usulan perbaikan demi kemajuan kinerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mencerminkan sikap resisten terhadap perubahan zaman yang dinamis.</li><li><strong>B</strong>: Membungkam aspirasi positif staf dan menghambat pemecahan masalah.</li><li><strong>C</strong>: Mencerminkan sikap arogan senioritas yang menghambat inovasi unit.</li><li><strong>D</strong>: Mematikan keberanian staf dalam menyampaikan kritik konstruktif dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>terbuka terhadap usulan perbaikan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang staf mengusulkan otomatisasi pengarsipan surat menggunakan aplikasi berbasis komputasi awan yang aman. Respon atasan yang mencerminkan nilai Kesempurnaan adalah...", "options": ["Meminta kajian kelayakan teknologi informasi secara berulang", "Menyimpan usulan tersebut sebagai bahan pertimbangan tahun depan", "Mendukung inovasi yang tidak bertentangan dengan regulasi yang sah", "Meminta staf menanggung seluruh biaya operasional aplikasi mandiri", "Mengharuskan staf kembali menggunakan buku agenda fisik manual"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Kesempurnaan: Dukungan Inovasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai Kesempurnaan melarang atasan <strong>menghalangi upaya inovasi yang tidak bertentangan dengan peraturan perundang-undangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Transformasi digital aparatur membutuhkan dukungan kepemimpinan adaptif yang mendorong gagasan inovatif. Atasan wajib mendukung inovasi staf sepanjang selaras dengan regulasi dan keamanan informasi. Dengan demikian, efektivitas penatausahaan persuratan meningkat pesat.</p><p>Jawaban tepat: <strong>C. Mendukung inovasi yang tidak bertentangan dengan regulasi yang sah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Membunuh inisiatif perbaikan staf yang bernilai tambah bagi instansi.</li><li><strong>B</strong>: Mencerminkan kepemimpinan pasif yang menghambat modernisasi kerja.</li><li><strong>D</strong>: Beban sarana kedinasan resmi merupakan tanggung jawab anggaran kantor.</li><li><strong>E</strong>: Kemunduran tata kelola yang bertentangan dengan prinsip efisiensi TI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak menghalangi upaya inovasi</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Upaya pencegahan pelanggaran kode etik di tingkat unit Eselon I kementerian dilakukan pimpinan melalui...", "options": ["Penyelidikan rahasia terhadap afiliasi politik seluruh staf", "Pembatasan keterlibatan pemeriksa eksternal dalam audit dinas", "Pengalihan seluruh kewenangan penindakan ke pihak kepolisian", "Pemberdayaan Unit Kepatuhan Internal di lingkungan unit", "Peniadaan forum pembinaan moral demi fokus target anggaran"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pencegahan Pelanggaran: Pemberdayaan UKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Untuk mencegah pelanggaran kode etik, pimpinan unit Eselon I wajib <strong>memberdayakan Unit Kepatuhan Internal (UKI)</strong> di lingkungannya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Keberadaan UKI berfungsi sebagai lini pertahanan kedua dalam mengawal kepatuhan dan manajemen risiko. Pimpinan Eselon I berkewajiban mengoptimalkan fungsi UKI guna mencegah timbulnya pelanggaran kode etik. Dengan demikian, pengawasan internal satker berjalan efektif.</p><p>Jawaban tepat: <strong>D. Pemberdayaan Unit Kepatuhan Internal di lingkungan unit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Melanggar batas privasi aparatur dan menyimpang dari peran manajerial.</li><li><strong>B</strong>: Menghambat transparansi dan akuntabilitas audit pemeriksaan negara.</li><li><strong>C</strong>: Pencegahan etik dan disiplin merupakan wewenang pembinaan internal.</li><li><strong>E</strong>: Mengabaikan pembinaan karakter moral pegawai yang diwajibkan aturan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memberdayakan Unit Kepatuhan Internal</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam melaksanakan pengawasan internal pencegahan pelanggaran etik, pimpinan unit kerja wajib...", "options": ["Menunjuk konsultan swasta independen untuk memeriksa pegawai", "Mengajukan penundaan jadwal audit berkala kepada pimpinan", "Menyelesaikan setiap temuan audit tanpa pelaporan ke pusat", "Membatasi akses data pemeriksaan keuangan bagi pemeriksa dinas", "Berkoordinasi dengan Inspektorat Jenderal Kementerian"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pencegahan Pelanggaran: Koordinasi Itjen</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pencegahan pelanggaran kode etik mewajibkan pimpinan unit <strong>berkoordinasi dengan Inspektorat Jenderal dalam melaksanakan pengawasan internal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Inspektorat Jenderal adalah aparat pengawasan intern pemerintah utama di lingkungan kementerian. Koordinasi intensif antara pimpinan satker dan Itjen memastikan sistem deteksi dini pelanggaran berjalan sinergis. Dengan demikian, akuntabilitas pengawasan terjamin kokoh.</p><p>Jawaban tepat: <strong>E. Berkoordinasi dengan Inspektorat Jenderal Kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemeriksaan etik dan disiplin aparatur adalah kewenangan aparat negara.</li><li><strong>B</strong>: Menunjukkan resistensi terhadap pengawasan yang melanggar regulasi.</li><li><strong>C</strong>: Menutup-nutupi temuan audit yang berisiko memperparah penyimpangan.</li><li><strong>D</strong>: Menghalang-halangi tugas pengawasan fungsional auditor pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berkoordinasi dengan Inspektorat Jenderal</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Ketentuan etika pelayanan mengatur bahwa penerimaan pihak luar yang tidak terkait pekerjaan pada jam kerja hanya boleh dilakukan...", "options": ["Dengan seizin atasan dan tidak mengganggu layanan kerja", "Apabila tamu memberikan cendera mata kenang-kenangan", "Di dalam ruangan kerja tertutup tanpa pengawasan staf lain", "Sepanjang durasi pertemuan tidak melebihi setengah jam kerja", "Jika tamu memiliki hubungan keluarga dengan pejabat unit"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Pelayanan: Menerima Tamu Kedinasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai diperbolehkan <strong>menerima pihak lain yang tidak terkait pekerjaan pada jam kerja dengan seizin atasan dan/atau tidak mengganggu pekerjaan atau layanan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Disiplin jam kerja menuntut pegawai mencurahkan waktu kedinasan demi tugas negara dan melayani publik. Menerima tamu non-kedinasan hanya diizinkan atas izin atasan dan wajib dipastikan tidak mengurangi kualitas layanan. Dengan demikian, produktivitas kantor tetap terjaga.</p><p>Jawaban tepat: <strong>A. Dengan seizin atasan dan tidak mengganggu layanan kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penerimaan cendera mata berpotensi gratifikasi yang dilarang hukum.</li><li><strong>C</strong>: Pertemuan tertutup berisiko menimbulkan kecurigaan dan benturan etik.</li><li><strong>D</strong>: Waktu kedinasan tidak boleh dikurangi sepihak tanpa persetujuan pimpinan.</li><li><strong>E</strong>: Hubungan kekerabatan tidak memberikan hak istimewa memakai jam kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menerima pihak lain yang tidak terkait</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dedikasi aparatur sipil negara untuk senantiasa memberikan hasil kerja berkualitas prima merupakan perwujudan dari...", "options": ["Keinginan memperoleh pujian lisan dari masyarakat pemohon", "Upaya memberikan kinerja dan layanan yang terbaik bagi publik", "Kewajiban menghabiskan alokasi anggaran belanja tahun berjalan", "Pencegahan agar tidak dilakukan audit kinerja oleh pengawas", "Tuntutan mempertahankan status akreditasi fasilitas kantor"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Kesempurnaan: Kinerja Terbaik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam nilai Kesempurnaan, setiap pegawai berkewajiban <strong>senantiasa berupaya untuk memberikan kinerja dan/atau layanan yang terbaik</strong> bagi pemangku kepentingan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Keunggulan layanan birokrasi lahir dari motivasi berprestasi dan perbaikan mutu yang berkesinambungan. Upaya memberikan kinerja dan layanan terbaik mencerminkan internalisasi utuh nilai kesempurnaan aparatur. Dengan demikian, citra positif kementerian terus meningkat.</p><p>Jawaban tepat: <strong>B. Upaya memberikan kinerja dan layanan yang terbaik bagi publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Motivasi pencitraan pribadi yang rapuh dan mencederai ketulusan kerja.</li><li><strong>C</strong>: Target penyerapan anggaran semata tanpa orientasi pada output mutu.</li><li><strong>D</strong>: Sikap menghindari audit pengawasan yang mencerminkan ketakutan dinas.</li><li><strong>E</strong>: Aspek administratif fasilitas fisik, bukan hakikat mutu pengabdian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>senantiasa berupaya untuk memberikan kinerja</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Upaya meningkatkan pemahaman kode etik pegawai secara terstruktur dilakukan pimpinan unit melalui...", "options": ["Pemberian teguran tertulis kepada seluruh staf setiap awal bulan", "Koordinasi dengan penyelenggara diklat dan pembina kepegawaian", "Pembatasan hak cuti tahunan bagi pegawai yang belum paham aturan", "Pengurangan tunjangan kinerja bagi staf yang tidak hadir rapat", "Pemasangan spanduk imbauan etika di setiap pintu masuk kantor"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Koordinasi Pusdiklat dan Biro SDM</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan unit wajib <strong>membangun koordinasi dengan penyelenggara pendidikan dan pelatihan serta pembina kepegawaian</strong> dalam mengupayakan pemahaman kode etik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Peningkatan kompetensi etis aparatur membutuhkan program edukasi kedinasan yang terencana dan bersertifikat. Koordinasi dengan Pusdiklat dan Biro SDM memastikan kurikulum pelatihan etika tersampaikan efektif. Dengan demikian, pemahaman pegawai terbangun secara sistematis.</p><p>Jawaban tepat: <strong>B. Koordinasi dengan penyelenggara diklat dan pembina kepegawaian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemberian sanksi tanpa pelanggaran nyata mencederai rasa keadilan kerja.</li><li><strong>C</strong>: Melanggar hak normatif pegawai terkait cuti tahunan yang sah.</li><li><strong>D</strong>: Pemotongan tunjangan kinerja wajib berbasis aturan disiplin resmi.</li><li><strong>E</strong>: Pemasangan media visual sekadar pelengkap, bukan pembinaan terstruktur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>membangun koordinasi dengan penyelenggara pendidikan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Sebagai bagian dari pencegahan pelanggaran kode etik, tindakan nyata yang wajib dilaksanakan atasan langsung adalah...", "options": ["Melakukan rotasi staf secara mendadak tanpa pemberitahuan resmi", "Menyusun evaluasi kinerja bawahan berdasarkan pertimbangan pribadi", "Memberikan keteladanan, pengawasan, dan pembinaan bawahan", "Menyerahkan urusan kedisiplinan kepada satuan pengamanan kantor", "Mengutamakan pendekatan persuasif informal tanpa catatan evaluasi"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Atasan Langsung Pencegahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Atasan langsung berkewajiban melakukan pencegahan pelanggaran etik dengan <strong>memberikan keteladanan, melakukan pengawasan, dan pembinaan terhadap bawahannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Pembinaan atasan langsung menjadi benteng pertahanan moral pertama bagi perilaku kerja bawahan. Ketentuan regulasi menegaskan atasan wajib memberi teladan, mengawasi, dan membina stafnya secara kontinu. Dengan demikian, potensi penyimpangan dapat diantisipasi sejak dini.</p><p>Jawaban tepat: <strong>C. Memberikan keteladanan, pengawasan, dan pembinaan bawahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menimbulkan keresahan psikologis staf yang mengganggu produktivitas.</li><li><strong>B</strong>: Melanggar asas objektivitas penilaian kinerja pegawai berbasis data.</li><li><strong>D</strong>: Pengawasan disiplin etik adalah tanggung jawab melekat pejabat atasan.</li><li><strong>E</strong>: Pembiaran pelanggaran demi popularitas merusak wibawa kepemimpinan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melakukan pengawasan, dan pembinaan terhadap bawahannya</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Instrumen regulasi yang mengatur penerapan nilai-nilai kementerian dan kode etik sebagai Early Warning System adalah...", "options": ["Surat Edaran Nomor 44/MK.01/2017", "Peraturan Pemerintah Nomor 42 Tahun 2004", "Keputusan Menteri Keuangan Nomor 416/2023", "Surat Edaran nomor SE: 12/MK.01/2018", "Peraturan Menteri Keuangan Nomor 190/2018"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Early Warning System SE-12/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan nilai dan kode etik sebagai sistem peringatan dini diatur dalam <strong>Surat Edaran nomor SE: 12/MK.01/2018</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Regulasi ini dirancang agar potensi penyimpangan hukum dan disiplin dapat dicegah sebelum menimbulkan kerugian negara. SE Nomor 12/MK.01/2018 memuat pedoman Early Warning System bagi seluruh jajaran kementerian. Dengan demikian, kepatuhan etika aparatur terpelihara proaktif.</p><p>Jawaban tepat: <strong>D. Surat Edaran nomor SE: 12/MK.01/2018</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan SE tentang himbauan penerapan nilai dasar ASN Kemenkeu.</li><li><strong>B</strong>: Merupakan PP tentang pembinaan jiwa korps dan kode etik PNS nasional.</li><li><strong>C</strong>: Merupakan KMK tentang penerapan sistem kerja fleksibel di Kemenkeu.</li><li><strong>E</strong>: Merupakan PMK tentang kode etik dan kode perilaku PNS Kemenkeu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Surat Edaran nomor SE: 12/MK.01/2018</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan SE Nomor 12/MK.01/2018, setiap pegawai Kementerian Keuangan diimbau secara tegas untuk menghindari...", "options": ["Penggunaan bahasa asing dalam penyusunan karya ilmiah dinas", "Penyampaian kritik terhadap prosedur operasional kantor satker", "Perbedaan pandangan teknis saat rapat pembahasan anggaran", "Keikutsertaan dalam perlombaan olahraga antardirektorat jenderal", "Perilaku yang mengarah pada ujaran kebencian dan radikalisme"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Radikalisme SE-12/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setiap pegawai diimbau untuk tegas <strong>menghindari perilaku-perilaku yang mengarah pada ujaran kebencian dan radikalisme</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Aparatur sipil negara bertugas menjaga persatuan dan keutuhan Negara Kesatuan Republik Indonesia. Ketentuan SE 12/2018 melarang keras keterlibatan pegawai dalam ujaran kebencian, intoleransi, dan radikalisme. Dengan demikian, netralitas kebangsaan aparatur tetap terjaga.</p><p>Jawaban tepat: <strong>E. Perilaku yang mengarah pada ujaran kebencian dan radikalisme</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penguasaan bahasa asing justru mendukung kompetensi global aparatur.</li><li><strong>B</strong>: Kritik konstruktif terhadap SOP merupakan wujud nilai kesempurnaan.</li><li><strong>C</strong>: Perbedaan teknis wajar dalam perumusan kebijakan yang komprehensif.</li><li><strong>D</strong>: Kegiatan olahraga antardirektorat memperkuat tali silaturahmi sinergi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ujaran kebencian</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan pedoman Early Warning System, atasan langsung diimbau melakukan pemantauan terhadap bawahan dengan tujuan...", "options": ["Mencegah terjadinya pelanggaran kode etik, disiplin, dan hukum", "Mengetahui rahasia kehidupan rumah tangga pribadi bawahan", "Membatasi pergaulan sosial bawahan di lingkungan masyarakat", "Memastikan bawahan memilih partai politik yang sama saat pemilu", "Menyusun peringkat kekayaan pribadi seluruh staf di unit kerja"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengawasan Atasan Langsung SE-12/2018</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Atasan langsung wajib melakukan pemantauan dan pengawasan guna <strong>mencegah terjadinya pelanggaran kode etik, pelanggaran disiplin, dan pelanggaran hukum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Fungsi pengawasan atasan ditujukan semata-mata demi menjaga integritas kedinasan dan kepatuhan hukum. Pemantauan berkala bertujuan mencegah potensi pelanggaran etika dan disiplin pegawai sedini mungkin. Dengan demikian, risiko sanksi berat dapat dihindari.</p><p>Jawaban tepat: <strong>A. Mencegah terjadinya pelanggaran kode etik, disiplin, dan hukum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Melanggar batas privasi personal yang dilindungi oleh hukum.</li><li><strong>C</strong>: Menghambat interaksi sosial wajar pegawai di tengah masyarakat.</li><li><strong>D</strong>: Melanggar asas netralitas aparatur sipil negara dalam pemilu.</li><li><strong>E</strong>: Laporan harta kekayaan memiliki mekanisme pelaporan resmi LHKPN/LHKASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pembinaan, pemantauan dan pengawasan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Ketika menerima pengaduan masyarakat mengenai dugaan pungli oleh stafnya, tindakan pertama yang wajib dilakukan atasan langsung adalah...", "options": ["Meminta staf bersangkutan segera mengajukan permohonan mutasi", "Menghapus berkas pengaduan agar tidak diketahui oleh pimpinan unit", "Memberikan sanksi pemecatan langsung tanpa meminta klarifikasi", "Melakukan pemeriksaan dan penelitian atas kebenaran pengaduan", "Mengumumkan nama terlapor di papan pengumuman kantor satker"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemeriksaan Awal oleh Atasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Atasan langsung berkewajiban <strong>melakukan pemeriksaan atas dugaan pelanggaran yang dilakukan oleh bawahannya yang diperoleh dari pengaduan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Prinsip praduga tak bersalah dan ketelitian menuntut klarifikasi objektif sebelum penjatuhan sanksi. Atasan wajib meneliti keabsahan bukti dan memeriksa staf terkait sesuai prosedur kedinasan. Dengan demikian, keputusan disiplin diambil berdasarkan fakta yang benar.</p><p>Jawaban tepat: <strong>D. Melakukan pemeriksaan dan penelitian atas kebenaran pengaduan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mutasi bukan penyelesaian perkara pelanggaran disiplin kedinasan.</li><li><strong>B</strong>: Merupakan tindakan penghilangan bukti yang melanggar kode etik.</li><li><strong>C</strong>: Menyalahi asas keadilan peradilan dan melanggar hukum acara disiplin.</li><li><strong>E</strong>: Melanggar asas kerahasiaan identitas dan mencemarkan nama baik staf.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pemeriksaan atas dugaan pelanggaran</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam penyelenggaraan pemerintahan, pegawai Kementerian Keuangan wajib berpedoman pada etika bernegara, yang tercermin dari...", "options": ["Penyusunan anggaran kantor berdasarkan selera kelompok staf", "Kepatuhan pada kepentingan partai politik pendukung pemerintah", "Pengutamaan kepentingan investor asing di atas kepentingan lokal", "Pelaksanaan tugas dinas hanya saat diawasi oleh pimpinan satker", "Kesetiaan pada NKRI dan pelayanan adil bagi seluruh warganegara"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etika Bernegara Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai wajib berpedoman pada <strong>etika bernegara dalam penyelenggaraan pemerintahan</strong> dengan menjunjung persatuan dan keadilan pelayanan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Aparatur negara berkedudukan sebagai pelayan publik yang mengabdi pada kepentingan nasional. Etika bernegara mewajibkan kesetiaan teguh pada bangsa serta pelayanan prima tanpa membedakan latar belakang warga. Dengan demikian, kewibawaan negara terjaga dengan baik.</p><p>Jawaban tepat: <strong>E. Kesetiaan pada NKRI dan pelayanan adil bagi seluruh warganegara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penganggaran negara wajib tunduk pada undang-undang keuangan negara.</li><li><strong>B</strong>: Bertentangan dengan asas netralitas birokrasi dari partai politik.</li><li><strong>C</strong>: Mengesampingkan kedaulatan ekonomi rakyat dan keadilan sosial.</li><li><strong>D</strong>: Integritas menuntut kedisiplinan kerja baik diawasi maupun tidak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>etika bernegara dalam penyelenggaraan pemerintahan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tujuan pimpinan unit kerja menginternalisasi nilai-nilai kementerian kepada seluruh jajaran adalah agar...", "options": ["Nilai kementerian dipahami dan menjadi pedoman perilaku nyata", "Seluruh pegawai dapat menghafal teks nilai saat ujian berkala", "Instansi memenuhi syarat administratif akreditasi kementerian", "Pimpinan memperoleh penilaian kepemimpinan terbaik dari pusat", "Pegawai tidak menuntut kenaikan fasilitas tunjangan kinerja"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Internalisasi Nilai oleh Pimpinan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan unit bertugas <strong>menginternalisasi Nilai-Nilai Kementerian Keuangan dan ketentuan penegakan kode etik kepada Pegawai di lingkungan kerjanya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Internalisasi nilai bertujuan mengubah paradigma berpikir dan tindakan nyata pegawai dalam melayani. Nilai organisasi bukan sekadar pajangan dinding melainkan standar hidup perilaku kerja sehari-hari. Dengan demikian, integritas institusi terbangun kokoh dari dalam.</p><p>Jawaban tepat: <strong>A. Nilai kementerian dipahami dan menjadi pedoman perilaku nyata</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penghafalan verbal tanpa pengamalan perbuatan tidak bernilai etis.</li><li><strong>C</strong>: Merupakan formalitas dokumen sertifikasi semata tanpa substansi.</li><li><strong>D</strong>: Orientasi pamrih pencitraan pribadi yang mencederai keikhlasan tugas.</li><li><strong>E</strong>: Tunjangan kinerja merupakan hak pegawai yang diatur perundang-undangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menginternalisasi Nilai-Nilai Kementerian Keuangan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan pedoman Early Warning System, sumber informasi awal dugaan pelanggaran yang diterima atasan dapat berasal dari...", "options": ["Kabar burung tanpa kejelasan identitas pihak penyampai", "Pengaduan resmi, temuan internal, dan laporan pihak terkait", "Opini bebas masyarakat di kolom komentar media sosial tidak resmi", "Ramalan pihak luar mengenai potensi kerugian keuangan negara", "Surat kaleng yang tidak memuat keterangan peristiwa nyata"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sumber Informasi Dugaan Pelanggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemeriksaan atasan dilakukan atas dugaan pelanggaran yang diperoleh dari <strong>pengaduan, temuan, dan laporan pihak terkait</strong> yang dapat dipertanggungjawabkan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Penegakan disiplin wajib bertumpu pada sumber informasi yang sah dan memiliki substansi jelas. Atasan mendasarkan penelitian pada pengaduan masyarakat, temuan pengawas, serta laporan resmi pihak terkait. Dengan demikian, penanganan perkara terhindar dari fitnah.</p><p>Jawaban tepat: <strong>B. Pengaduan resmi, temuan internal, dan laporan pihak terkait</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kabar burung tidak memiliki dasar pembuktian yang dapat diuji hukum.</li><li><strong>C</strong>: Komentar anonim media sosial bukan saluran pengaduan resmi kedinasan.</li><li><strong>D</strong>: Tindakan hukum harus bertumpu pada fakta empiris dan alat bukti sah.</li><li><strong>E</strong>: Surat kaleng tanpa fakta tidak memenuhi syarat formil pengaduan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>laporan pihak</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Fungsi utama penerapan Early Warning System kode etik di lingkungan Kementerian Keuangan adalah...", "options": ["Memperbanyak jumlah pegawai yang dijatuhi hukuman disiplin berat", "Menggantikan seluruh proses audit kepatuhan oleh auditor eksternal", "Mendeteksi dan mencegah potensi pelanggaran disiplin dan hukum", "Mengurangi alokasi anggaran sosialisasi peraturan kepegawaian", "Memusatkan seluruh wewenang pemeriksaan hanya pada pimpinan satker"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pokok Early Warning System</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan diterbitkannya SE 12/2018 adalah menerapkan kode etik sebagai <strong>Early Warning System guna mendeteksi dan mencegah potensi perbuatan yang mengarah pada pelanggaran disiplin dan hukum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Pencegahan dini jauh lebih berharga daripada penindakan setelah terjadinya kebocoran anggaran negara. Sistem peringatan dini memetakan risiko perilaku menyimpang agar dapat dikoreksi segera. Dengan demikian, keselamatan pegawai dan marwah instansi terlindungi.</p><p>Jawaban tepat: <strong>C. Mendeteksi dan mencegah potensi pelanggaran disiplin dan hukum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sasaran sistem adalah pencegahan, bukan mengejar kuantitas hukuman.</li><li><strong>B</strong>: Sistem ini melengkapi, bukan meniadakan peran audit eksternal BPK.</li><li><strong>D</strong>: Sosialisasi aturan justru menjadi pilar utama edukasi pencegahan dini.</li><li><strong>E</strong>: Pemeriksaan perkara melibatkan berbagai unsur pengawas dan majelis etik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Early Warning System</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kewajiban atasan langsung saat menerima laporan hasil pemeriksaan dari Inspektorat Jenderal atau UKI adalah...", "options": ["Menyimpan laporan tersebut sebagai arsip rahasia tanpa tindakan", "Mengajukan permohonan dispensasi sanksi kepada inspektorat", "Menindaklanjuti hasil pemeriksaan sebagai acuan penindakan", "Mengajukan peninjauan kembali ke pengadilan tata usaha negara", "Menyebarluaskan isi laporan hasil audit kepada media umum"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tindak Lanjut Hasil Pemeriksaan Itjen/UKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Atasan langsung wajib <strong>menindaklanjuti hasil pemeriksaan oleh Inspektorat Jenderal atau Unit Kepatuhan Internal (UKI) serta menjadikannya acuan pemeriksaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Rekomendasi aparat pengawas intern pemerintah bersifat mengikat untuk segera dieksekusi demi perbaikan. Atasan langsung wajib menindaklanjuti rekomendasi hasil audit sebagai dasar pembinaan staf. Dengan demikian, kepastian hukum dan perbaikan sistem berjalan efektif.</p><p>Jawaban tepat: <strong>C. Menindaklanjuti hasil pemeriksaan sebagai acuan penindakan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pembiaran temuan audit yang melanggar kewajiban jabatan.</li><li><strong>B</strong>: Menolak rekomendasi pengawas merupakan bentuk pembangkangan aturan.</li><li><strong>D</strong>: Ranah internal kepegawaian diselesaikan melalui jalur pembinaan dinas.</li><li><strong>E</strong>: Melanggar asas kerahasiaan dokumen pemeriksaan internal aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menindaklanjuti hasil pemeriksaaan oleh Inspektorat Jenderal</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Seluruh pimpinan unit kerja di lingkungan kementerian diimbau bertanggung jawab penuh terhadap...", "options": ["Penyelesaian seluruh sengketa perdata masyarakat sekitar kantor", "Pengadaan langsung logistik kantor tanpa prosedur lelang resmi", "Pemberian dispensasi kehadiran dinas bagi staf berprestasi", "Penerapan tata kelola, manajemen risiko, dan pengendalian intern", "Penyelenggaraan kegiatan sosial kemasyarakatan di luar kedinasan"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Pimpinan Unit Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pimpinan unit kerja bertanggung jawab terhadap <strong>tata kelola, manajemen resiko dan pengendalian intern dalam menjalankan tugas dan fungsinya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Tata kelola yang baik menuntut pengendalian risiko operasional pada setiap lini birokrasi pemerintahan. Pimpinan unit bertanggung jawab memastikan tata kelola, manajemen risiko, dan SPI terlaksana optimal. Dengan demikian, tujuan strategis kementerian dapat dicapai akuntabel.</p><p>Jawaban tepat: <strong>D. Penerapan tata kelola, manajemen risiko, dan pengendalian intern</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sengketa keperdataan masyarakat adalah yurisdiksi pengadilan umum.</li><li><strong>B</strong>: Pengadaan logistik wajib patuh pada perpres pengadaan barang/jasa.</li><li><strong>C</strong>: Dispensasi kehadiran dinas memiliki ketentuan izin yang diatur ketat.</li><li><strong>E</strong>: Kegiatan sosial kemasyarakatan merupakan aktivitas non-kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tata kelola, manajemen resiko dan pengendalian intern</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Fokus pembahasan pada bagian Penegakan Kode Etik dan Kode Perilaku PNS di Kementerian Keuangan menitikberatkan pada...", "options": ["Tata cara pengangkatan pejabat fungsional perbendaharaan negara", "Penetapan besaran gaji dan tunjangan pokok seluruh pegawai", "Mekanisme pengadaan sarana transportasi pimpinan unit kerja", "Penyusunan kurikulum pelatihan teknis keuangan internasional", "Penanganan pelanggaran etika dan tata cara sidang majelis kode etik"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ruang Lingkup Penegakan Etik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penegakan kode etik mengatur <strong>pelanggaran kode etik, tata cara penanganan pengaduan dan temuan, serta pembentukan dan sidang majelis kehormatan kode etik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Regulasi penegakan memastikan setiap dugaan pelanggaran diproses secara adil, tertib, dan transparan. Bagian ini merumuskan penanganan pelanggaran, pengaduan, dan tata sidang majelis etik aparatur. Dengan demikian, martabat dan wibawa kementerian tetap terjaga luhur.</p><p>Jawaban tepat: <strong>E. Penanganan pelanggaran etika dan tata cara sidang majelis kode etik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan regulasi jabatan fungsional pembina kepegawaian pusat.</li><li><strong>B</strong>: Merupakan kebijakan fiskal remunerasi nasional aparatur sipil negara.</li><li><strong>C</strong>: Merupakan manajemen aset perlengkapan sarana mobilitas kantor.</li><li><strong>D</strong>: Merupakan penyusunan program diklat pada badan pendidikan pelatihan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penegakan Kode Etik dan Kode Perilaku Pegawai</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pelanggaran Kode Etik dan Kode Perilaku PNS pada hakikatnya didefinisikan sebagai...", "options": ["Segala ucapan, tulisan, atau perbuatan yang bertentangan dengan kode etik", "Keterlambatan penyelesaian tugas akibat kerusakan sistem jaringan komputer", "Perbedaan pendapat teknis saat musyawarah perumusan rencana kerja satker", "Kegagalan mencapai target penerimaan anggaran karena krisis ekonomi global", "Pengajuan cuti tahunan yang bertepatan dengan agenda rapat pimpinan kantor"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Pelanggaran Kode Etik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelanggaran kode etik adalah <strong>segala ucapan, tulisan, atau perbuatan pegawai yang bertentangan dengan ketentuan kode etik dan kode perilaku</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Batasan pelanggaran mencakup ranah lisan, tulisan di ruang publik/digital, maupun tindakan konkret kedinasan. Pelanggaran kode etik didefinisikan sebagai tindakan menyimpang dari standar perilaku resmi. Dengan demikian, setiap perbuatan aparatur memiliki konsekuensi moral etis.</p><p>Jawaban tepat: <strong>A. Segala ucapan, tulisan, atau perbuatan yang bertentangan dengan kode etik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan kendala teknis sarana prasarana, bukan pelanggaran etik.</li><li><strong>C</strong>: Musyawarah perbedaan pendapat dilindungi dalam iklim kerja sehat.</li><li><strong>D</strong>: Merupakan faktor makroekonomi eksternal di luar kuasa pegawai.</li><li><strong>E</strong>: Pengajuan cuti adalah hak normatif yang disetujui pejabat berwenang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pelanggaran Kode Etik Pegawai Negeri Sipil</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Informasi terjadinya dugaan Pelanggaran Kode Etik dan Kode Perilaku pegawai secara resmi diperoleh melalui dua saluran, yaitu...", "options": ["Surat kaleng anonim dan isu perbincangan informal di kantin kantor", "Pengaduan masyarakat/pegawai dan Temuan pengawasan resmi", "Pemberitaan sensasional media daring dan spekulasi publik", "Laporan evaluasi absensi harian dan rekapitulasi data lembur", "Hasil angket kepuasan pegawai dan survei iklim organisasi satker"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Jalur Informasi Pelanggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Informasi dugaan pelanggaran kode etik secara sah diperoleh melalui saluran <strong>Pengaduan dan/atau Temuan</strong> pengawasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Penegakan etik membutuhkan dasar informasi kredibel yang dapat ditindaklanjuti secara hukum acara. PMK 190/2018 menetapkan bahwa informasi pelanggaran bersumber dari pengaduan dan temuan pengawas. Dengan demikian, proses penegakan disiplin memiliki kepastian hukum.</p><p>Jawaban tepat: <strong>B. Pengaduan masyarakat/pegawai dan Temuan pengawasan resmi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Isu informal dan surat kaleng tidak dapat dijadikan dasar pemeriksaan sah.</li><li><strong>C</strong>: Berita media massa harus diverifikasi terlebih dahulu keabsahan buktinya.</li><li><strong>D</strong>: Rekap absensi merupakan administrasi kehadiran, bukan aduan pelanggaran etik.</li><li><strong>E</strong>: Survei iklim kerja mengukur persepsi kenyamanan, bukan perkara etik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengaduan dan/ atau Temuan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan PMK Nomor 190/PMK.01/2018, pengaduan dugaan pelanggaran kode etik dapat diajukan oleh...", "options": ["Hanya pejabat pimpinan tinggi kementerian yang memiliki wewenang", "Hanya aparat penegak hukum luar yang sedang melakukan penyidikan", "Pegawai asing dari lembaga keuangan internasional mitra kerja", "Penyedia barang dan jasa yang memenangkan tender lelang proyek dinas", "Pegawai Kementerian Keuangan dan/atau masyarakat luas"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pihak Pelapor Pengaduan Etik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pengaduan berasal dari Pegawai dan/atau pengaduan yang berasal dari masyarakat</strong> yang disampaikan secara tertulis melalui kanal resmi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Partisipasi publik dan pengawasan rekan sejawat dijamin demi mewujudkan birokrasi yang bersih. Regulasi membuka hak pelaporan pelanggaran etik baik bagi kalangan internal pegawai maupun warga masyarakat. Dengan demikian, ruang pelaporan terbuka luas bagi para pencari keadilan.</p><p>Jawaban tepat: <strong>E. Pegawai Kementerian Keuangan dan/atau masyarakat luas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Membatasi pengawasan hanya pada tingkatan pejabat struktural atas.</li><li><strong>B</strong>: Penegakan kode etik internal mandiri dari kewenangan penyidikan pidana.</li><li><strong>C</strong>: Pengaduan formal terbuka bagi warga negara dan pengguna layanan publik.</li><li><strong>D</strong>: Membatasi pelapor hanya pada rekanan mitra bisnis komersial tertentu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengaduan berasal dari Pegawai</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 16 PP No. 42 Tahun 2004, sanksi moral yang dijatuhkan kepada PNS dibuat tertulis dan dapat berupa...", "options": ["Pernyataan secara tertutup atau pernyataan secara terbuka", "Penurunan pangkat setingkat lebih rendah selama satu tahun", "Pemindahan tugas dalam rangka penurunan jabatan struktural", "Pemberian teguran lisan di hadapan seluruh pegawai satker", "Penundaan kenaikan gaji berkala selama enam bulan dinas"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Sanksi Moral Pasal 16 PP 42/2004</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan Pasal 16 PP 42/2004, <strong>Sanksi moral dibuat secara tertulis</strong> dan dapat berupa <strong>pernyataan secara tertutup atau pernyataan secara terbuka</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Sanksi moral dirancang untuk memulihkan martabat korps aparatur melalui pernyataan resmi yang mengikat. Ketentuan pasal 16 PP 42/2004 menegaskan sanksi moral berbentuk pernyataan tertutup atau terbuka. Dengan demikian, sanksi moral memberikan efek edukatif bagi terlapor.</p><p>Jawaban tepat: <strong>A. Pernyataan secara tertutup atau pernyataan secara terbuka</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan jenis hukuman disiplin sedang menurut regulasi disiplin PNS.</li><li><strong>C</strong>: Merupakan jenis hukuman disiplin berat berupa demosi jabatan.</li><li><strong>D</strong>: Teguran lisan merupakan hukuman disiplin ringan, bukan format sanksi moral tertulis.</li><li><strong>E</strong>: Penundaan kenaikan gaji berkala adalah hukuman disiplin sedang kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sanksi moral dibuat secara tertulis</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "PNS yang melanggar kode etik dan terbukti melanggar ketentuan disiplin dapat dikenakan tindakan administratif berupa...", "options": ["Pemberhentian pembayaran seluruh hak pensiun hari tua", "Hukuman disiplin PNS yang diatur dalam PP 94/2021", "Kewajiban mengganti seluruh biaya operasional sidang majelis", "Penahanan surat keputusan pengangkatan pegawai negeri sipil", "Pemberhentian tidak dengan hormat sebagai pegawai negeri"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tindakan Administratif Disiplin PP 94/2021</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai yang melanggar kode etik dan melanggar peraturan disiplin dapat dikenakan <strong>hukuman disiplin PNS yang diatur PP 94/2021</strong> atas rekomendasi Majelis.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Pelanggaran etika yang berbarengan dengan pelanggaran aturan kedinasan berkonsekuensi pada penjatuhan hukuman disiplin. Regulasi menegaskan selain sanksi moral dapat dijatuhkan hukuman disiplin PP 94/2021 jika melanggar disiplin. Dengan demikian, penegakan hukum dan etika berjalan seiring.</p><p>Jawaban tepat: <strong>B. Hukuman disiplin PNS yang diatur dalam PP 94/2021</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemberhentian hak pensiun hanya dapat terjadi pada tindak pidana kejahatan jabatan tertentu.</li><li><strong>C</strong>: Biaya sidang majelis dibebankan pada anggaran dinas resmi instansi.</li><li><strong>D</strong>: Penahanan SK pengangkatan bukan jenis sanksi disiplin yang sah menurut hukum.</li><li><strong>E</strong>: Pencabutan kewarganegaraan adalah kewenangan peradilan pidana luar biasa negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>hukuman disiplin PNS</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Selain laporan pengaduan, sumber informasi pelanggaran kode etik dapat berupa temuan hasil pengawasan dari...", "options": ["Organisasi kemasyarakatan yang melakukan unjuk rasa di depan kantor", "Lembaga survei komersial yang merilis indeks kepuasan publik", "Atasan Terlapor, Unit Kepatuhan Internal, dan Inspektorat Jenderal", "Media massa cetak yang memuat opini pembaca tentang pelayanan", "Asosiasi advokat yang menangani perkara perdata pihak swasta"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kategori Temuan Pengawasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Temuan pelanggaran kode etik secara resmi terdiri atas: <strong>Temuan Atasan Terlapor, Temuan Unit Kepatuhan Internal, dan/atau Temuan Inspektorat Jenderal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Aparat pengawas internal memiliki instrumen audit kepatuhan untuk menemukan penyimpangan secara mandiri. Temuan resmi bersumber dari tiga lini: atasan terlapor, UKI, serta Inspektorat Jenderal. Dengan demikian, pengawasan aktif tidak semata menunggu laporan warga.</p><p>Jawaban tepat: <strong>C. Atasan Terlapor, Unit Kepatuhan Internal, dan Inspektorat Jenderal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aspirasi unjuk rasa merupakan masukan publik yang perlu diteliti buktinya.</li><li><strong>B</strong>: Rilis indeks kepuasan publik mengukur persepsi umum, bukan temuan kasus.</li><li><strong>D</strong>: Opini pembaca surat kabar bukan produk audit pemeriksaan aparat negara.</li><li><strong>E</strong>: Asosiasi profesi swasta tidak berwenang melakukan audit internal instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengaduan dan/ atau Temuan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pemberdayaan Unit Kepatuhan Internal di lingkungan satker kerja kementerian ditujukan secara spesifik untuk...", "options": ["Mengambil alih fungsi penagihan utang pajak dari jurusita", "Menyusun pembelaan hukum pimpinan unit saat terjadi gugatan perdata", "Mengurangi alokasi belanja modal operasional pengadaan barang", "Meningkatkan efektivitas tata kelola, manajemen risiko, dan SPI", "Melakukan seleksi penerimaan calon pegawai negeri sipil baru"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pemberdayaan UKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemberdayaan UKI bertujuan <strong>meningkatkan efektivitas penerapan tata kelola, manajemen resiko dan pengendalian intern</strong> di unit kerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. UKI memegang fungsi strategis dalam memastikan kepatuhan prosedur dan pengendalian mutu operasional. Pimpinan memberdayakan UKI guna memastikan tata kelola, manajemen risiko, dan pengendalian intern berjalan efektif. Dengan demikian, potensi penyimpangan dapat ditekan minimal.</p><p>Jawaban tepat: <strong>D. Meningkatkan efektivitas tata kelola, manajemen risiko, dan SPI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tugas jurusita pajak diatur khusus dalam undang-undang perpajakan.</li><li><strong>B</strong>: Bantuan hukum instansi diselenggarakan oleh biro hukum kementerian.</li><li><strong>C</strong>: Efisiensi anggaran modal bukan sasaran pokok keberadaan fungsi UKI.</li><li><strong>E</strong>: Seleksi CPNS diselenggarakan secara nasional oleh Panselnas BKN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 21) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>meningkatkan efektivitas penerapan tata kelola</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Agar dapat ditindaklanjuti secara resmi, surat pengaduan dugaan pelanggaran kode etik paling sedikit wajib memuat...", "options": ["Keterangan latar belakang keluarga dan riwayat pendidikan pelapor", "Rekomendasi jenis hukuman disiplin berat yang dikehendaki pelapor", "Surat kuasa khusus dari lembaga bantuan hukum pendamping pelapor", "Waktu kejadian, bukti/saksi, dan identitas pelapor serta terlapor", "Hasil analisis forensik digital dari laboratorium independen swasta"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Minimal Berkas Pengaduan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengaduan resmi paling sedikit memuat: <strong>1) Waktu dan tempat kejadian; 2) Bukti dan/atau saksi; dan 3) Identitas Pelapor dan Terlapor</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Kejelasan unsur peristiwa dan alat bukti mencegah pengaduan fitnah yang merugikan nama baik pegawai. Ketentuan PMK 190/2018 merumuskan syarat minimal mencakup waktu-tempat, bukti/saksi, serta identitas pihak. Dengan demikian, pemeriksaan dapat dilaksanakan secara akurat dan objektif.</p><p>Jawaban tepat: <strong>D. Waktu kejadian, bukti/saksi, dan identitas pelapor serta terlapor</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Riwayat keluarga pelapor tidak relevan dengan substansi peristiwa pelanggaran.</li><li><strong>B</strong>: Penetapan jenis hukuman merupakan wewenang mutlak majelis dan pejabat pembina.</li><li><strong>C</strong>: Pengaduan dapat diajukan langsung oleh warga tanpa kewajiban kuasa hukum.</li><li><strong>E</strong>: Uji forensik digital merupakan tahap pembuktian lanjutan jika diperlukan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Waktu dan tempat kejadian</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Setelah menerima laporan pengaduan yang memenuhi syarat formal, langkah pertama yang wajib dieksekusi atasan langsung adalah...", "options": ["Mengusulkan pemberhentian sementara terlapor kepada menteri", "Melakukan konferensi pers untuk menjelaskan duduk perkara pengaduan", "Menyita seluruh barang pribadi milik terlapor yang ada di ruangan", "Membatalkan seluruh hasil kerja yang pernah diselesaikan oleh terlapor", "Melakukan penelitian atas temuan atau pengaduan secara saksama"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tindakan Awal Atasan Langsung</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Atasan langsung terlapor wajib <strong>melakukan penelitian atas temuan dan/atau pengaduan dan menjaga kerahasiaan identitas Pelapor</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Penelitian awal bertujuan memverifikasi apakah materi laporan benar merupakan ranah pelanggaran kode etik. Atasan wajib meneliti secara cermat bukti dan keterangan yang disampaikan sebelum melangkah lebih jauh. Dengan demikian, asas praduga tak bersalah terlindungi baik.</p><p>Jawaban tepat: <strong>E. Melakukan penelitian atas temuan atau pengaduan secara saksama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemberhentian sementara memerlukan penetapan resmi pejabat pembina kepegawaian.</li><li><strong>B</strong>: Konferensi pers melanggar asas kerahasiaan pemeriksaan disiplin aparatur.</li><li><strong>C</strong>: Penyitaan barang pribadi adalah kewenangan yudisial aparat penegak hukum.</li><li><strong>D</strong>: Hasil kerja kedinasan tetap sah kecuali terbukti ada manipulasi substantif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pelanggaran Kode Etik dan Kode Perilaku harus melakukan penelitian</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pejabat yang berwenang secara hukum menetapkan pembentukan Majelis Kehormatan Kode Etik pada instansi adalah...", "options": ["Pejabat Pembina Kepegawaian yang bersangkutan", "Kepala Badan Kepegawaian Negara Republik Indonesia", "Kepala Badan Pengawasan Keuangan dan Pembangunan", "Ketua Pengadilan Negeri di wilayah hukum satker kerja", "Pimpinan organisasi korps profesi pegawai setempat"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penetapan Majelis Kode Etik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Untuk menegakkan kode etik, pada setiap instansi dibentuk <strong>Majelis Kehormatan Kode Etik yang ditetapkan oleh Pejabat Pembina Kepegawaian (PPK)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Pembina Kepegawaian (Menteri) memegang mandat tertinggi tata kelola manajemen aparatur sipil kementerian. Penetapan majelis kehormatan kode etik merupakan wewenang atributif Pejabat Pembina Kepegawaian. Dengan demikian, legalitas majelis berpijak pada legitimasi hukum yang sah.</p><p>Jawaban tepat: <strong>A. Pejabat Pembina Kepegawaian yang bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: KPK berwenang dalam ranah tindak pidana korupsi, bukan sidang majelis instansi.</li><li><strong>C</strong>: BPKP menjalankan fungsi audit pengawasan keuangan dan akuntabilitas negara.</li><li><strong>D</strong>: Pengadilan negeri menangani perkara yudisial peradilan umum pidana-perdata.</li><li><strong>E</strong>: Korps pegawai merupakan wadah keanggotaan, bukan penetap majelis etik instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Majelis Kehormatan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan PMK Nomor 190/PMK.01/2018, Majelis Kode Etik di lingkungan Kementerian Keuangan dibentuk sebagai tim yang bersifat...", "options": ["Permanen struktural setingkat unit eselon dua kementerian", "Tidak tetap (ad hoc) untuk menangani kasus tertentu", "Independen di luar struktur birokrasi pemerintahan negara", "Tertutup tanpa kewajiban melaporkan hasil kepada menteri", "Sukarela yang beranggotakan perwakilan masyarakat luar"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sifat Kelembagaan Majelis Etik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Majelis Kode Etik adalah <strong>tim yang bersifat tidak tetap (ad hoc) yang dibentuk di lingkungan Kementerian Keuangan</strong> untuk menegakkan etika.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Sifat ad hoc berarti majelis hanya dibentuk saat ada kasus dugaan pelanggaran dan dibubarkan setelah tugas selesai. PMK 190/2018 menegaskan sifat kelembagaan majelis etik adalah tidak tetap (ad hoc). Dengan demikian, struktur organisasi birokrasi tetap ramping dan fleksibel.</p><p>Jawaban tepat: <strong>B. Tidak tetap (ad hoc) untuk menangani kasus tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Majelis bukan unit struktural permanen yang membebani formasi birokrasi.</li><li><strong>C</strong>: Majelis berada di bawah naungan internal kementerian perbendaharaan negara.</li><li><strong>D</strong>: Majelis wajib menyampaikan rekomendasi putusan kepada pejabat pembina menteri.</li><li><strong>E</strong>: Keanggotaan majelis terdiri atas pejabat aparatur sipil negara yang berwenang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tim yang bersifat tidak tetap (ad hoc)</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam melaksanakan penegakan atas pelanggaran kode etik, Majelis Kode Etik wajib berpegang pada asas...", "options": ["Kecepatan dan pengabaian formalitas pembuktian", "Kerahasiaan mutlak dari pengawasan pimpinan kementerian", "Kejujuran dan keadilan dalam memeriksa fakta", "Keringanan sanksi bagi pegawai berstatus masa kerja lama", "Penyelarasan keputusan dengan kehendak opini publik"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Kerja Majelis Etik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Majelis bertugas melakukan penegakan atas pelanggaran Kode Etik dan Kode Perilaku <strong>berdasarkan asas kejujuran dan keadilan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Pemeriksaan kode etik menuntut keadilan berimbang dan penelusuran fakta secara jujur dan objektif. Asas kejujuran dan keadilan menjadi panduan moral majelis dalam menilai pembuktian perkara. Dengan demikian, hak-hak terlapor terlindungi dari putusan sewenang-wenang.</p><p>Jawaban tepat: <strong>C. Kejujuran dan keadilan dalam memeriksa fakta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengabaikan pembuktian formal melanggar asas due process of law.</li><li><strong>B</strong>: Majelis bertanggung jawab memberikan laporan rekomendasi kepada menteri.</li><li><strong>D</strong>: Masa kerja lama bukan alasan pembenar pembedaan perlakuan hukum etik.</li><li><strong>E</strong>: Putusan etik berpijak pada fakta hukum dan aturan, bukan tekanan opini luar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>asas kejujuran dan keadilan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Susunan kepengurusan pokok dalam keanggotaan Majelis Kode Etik terdiri dari...", "options": ["1 orang ketua merangkap anggota, 1 sekretaris, dan minimal 3 anggota", "2 orang ketua bersama, 1 orang panitera, dan 2 orang penasihat hukum", "1 orang ketua tanpa hak suara, notaris, dan 4 anggota ahli", "3 orang pimpinan kolektif, 1 orang sekretaris, dan 1 orang penuntut", "1 orang koordinator independen dan seluruh kepala seksi di satker"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Susunan Pokok Keanggotaan Majelis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keanggotaan Majelis terdiri dari <strong>1 (satu) orang ketua merangkap anggota, satu orang sekretaris merangkap anggota, dan sekurang-kurangnya 3 (tiga) orang anggota</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Komposisi ini menjamin keterwakilan pejabat yang berimbang dalam memeriksa dugaan pelanggaran etik. Ketentuan keanggotaan menetapkan struktur ketua, sekretaris, dan minimal tiga anggota. Dengan demikian, majelis memiliki legitimasi kuorum yang kuat dalam bersidang.</p><p>Jawaban tepat: <strong>A. 1 orang ketua merangkap anggota, 1 sekretaris, dan minimal 3 anggota</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Format dua ketua bersama tidak dikenal dalam tata sidang majelis etik ASN.</li><li><strong>C</strong>: Ketua majelis memiliki hak suara penuh dalam musyawarah keputusan.</li><li><strong>D</strong>: Merupakan struktur peradilan pidana, bukan majelis kehormatan kode etik.</li><li><strong>E</strong>: Keanggotaan majelis ditetapkan khusus melalui surat keputusan pejabat pembina.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>1 (satu) orang ketua merangkap anggota</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Apabila anggota Majelis Kode Etik ditetapkan lebih dari 5 orang, ketetapan jumlah anggota tersebut harus ganjil dengan alasan...", "options": ["Memenuhi syarat mistis kepatutan tradisi persidangan masa lampau", "Mencegah terjadinya hasil seri (deadlock) saat pemungutan suara", "Mempercepat waktu penyelesaian musyawarah pemeriksaan perkara", "Memudahkan pembagian tugas pemeriksaan berkas saksi di daerah", "Menyesuaikan jumlah kursi persidangan yang tersedia di aula dinas"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketentuan Jumlah Ganjil Anggota Majelis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jika anggota Majelis Kode Etik lebih dari 5 (lima) orang, <strong>jumlahnya harus ganjil agar jika terjadi pemungutan suara, tidak terjadi deadlock</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Jumlah ganjil memastikan pengambilan keputusan tetap menghasilkan suara terbanyak saat mufakat buntu. Ketentuan PMK 190/2018 mengatur jumlah ganjil demi menghindari kebuntuan putusan (deadlock). Dengan demikian, kepastian hukum perkara dapat segera dicapai.</p><p>Jawaban tepat: <strong>B. Mencegah terjadinya hasil seri (deadlock) saat pemungutan suara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Persidangan birokrasi berlandaskan pada hukum positif rasional modern.</li><li><strong>C</strong>: Kecepatan sidang bergantung pada kecukupan bukti, bukan bilangan ganjil.</li><li><strong>D</strong>: Pembagian tugas pemeriksaan teknis diatur oleh ketua majelis tersendiri.</li><li><strong>E</strong>: Fasilitas sarana fisik ruang sidang menyesuaikan formasi resmi majelis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jumlahnya harus ganjil</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Persyaratan pangkat dan jabatan yang wajib dipenuhi oleh setiap anggota Majelis Kode Etik terhadap pegawai terlapor adalah...", "options": ["Boleh lebih rendah asalkan memiliki sertifikasi keahlian hukum", "Wajib menduduki jenjang jabatan pimpinan tinggi madya pusat", "Tidak boleh lebih rendah dari jabatan dan pangkat terlapor", "Harus berasal dari unit kerja yang sama persis dengan terlapor", "Wajib memiliki masa kerja minimal dua puluh tahun di kementerian"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Pangkat Anggota Majelis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan dan pangkat Anggota Majelis Kode Etik <strong>tidak boleh lebih rendah dari jabatan dan pangkat Pegawai Negeri Sipil yang diperiksa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Prinsip ini menjaga wibawa persidangan dan independensi anggota majelis dalam memeriksa pihak terlapor. Anggota majelis dilarang berpangkat lebih rendah dari pegawai yang disangka melanggar aturan. Dengan demikian, relasi hierarki birokrasi persidangan tetap terjaga tertib.</p><p>Jawaban tepat: <strong>C. Tidak boleh lebih rendah dari jabatan dan pangkat terlapor</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keahlian hukum tidak meniadakan syarat kepangkatan minimal kepegawaian.</li><li><strong>B</strong>: Tidak seluruh majelis harus beranggotakan pejabat pimpinan tinggi madya.</li><li><strong>D</strong>: Anggota majelis justru dapat berasal dari luar unit demi netralitas.</li><li><strong>E</strong>: Masa kerja lama bukan syarat formil pengangkatan anggota majelis etik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tidak boleh lebih rendah dari</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Surat pemanggilan pertama secara tertulis kepada Terlapor wajib disampaikan oleh Majelis Kode Etik paling singkat...", "options": ["3 hari kalender sebelum jadwal sidang dilaksanakan", "5 hari kerja sebelum tanggal pelaksanaan musyawarah", "14 hari kerja sebelum pemeriksaan berkas saksi selesai", "7 hari kerja sebelum tanggal pemeriksaan oleh Majelis", "30 hari kalender setelah pengaduan pertama diterima"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tenggat Waktu Pemanggilan Pertama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Majelis melakukan pemanggilan pertama secara tertulis kepada Terlapor <strong>paling singkat 7 (tujuh) hari kerja sebelum tanggal pemeriksaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Tenggat waktu 7 hari kerja memberikan kesempatan wajar bagi terlapor untuk menyiapkan pembelaan dan bukti. Aturan menegaskan pemanggilan pertama wajib dilayangkan minimal 7 hari kerja sebelum sidang. Dengan demikian, hak pembelaan diri terlapor dihormati penuh.</p><p>Jawaban tepat: <strong>D. 7 hari kerja sebelum tanggal pemeriksaan oleh Majelis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jangka waktu 3 hari kalender terlalu sempit dan melanggar aturan formil.</li><li><strong>B</strong>: Batas 5 hari kerja tidak sesuai dengan ketentuan resmi PMK 190/2018.</li><li><strong>C</strong>: Tenggat 14 hari kerja bukan batas pemanggilan pertama terlapor.</li><li><strong>E</strong>: Tenggat 30 hari kalender adalah durasi umum penanganan pengaduan publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>7 (tujuh) hari kerja sebelum tanggal pemeriksaan</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Apabila Terlapor tidak hadir memenuhi surat panggilan pertama tanpa alasan sah, langkah prosedural Majelis adalah melayangkan...", "options": ["Surat pemberitahuan penjatuhan hukuman disiplin berat secara langsung", "Surat permohonan penjemputan paksa kepada aparat kepolisian setempat", "Surat pembatalan perkara pemeriksaan dugaan pelanggaran kode etik", "Surat teguran lisan di hadapan seluruh pegawai pada apel pagi", "Pemanggilan kedua dengan jangka waktu 7 hari kerja berikutnya"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prosedur Pemanggilan Kedua</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam hal terlapor mangkir pada panggilan pertama, dilakukan <strong>pemanggilan kedua dengan jangka waktu 7 (tujuh) hari kerja sejak tanggal seharusnya hadir</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Hukum acara penegakan disiplin memberikan kesempatan kedua sebelum sanksi sepihak direkomendasikan. Majelis melayangkan surat panggilan kedua dengan tenggat 7 hari kerja dari jadwal panggilan pertama. Dengan demikian, prosedur hukum acara terlaksana tertib dan sah.</p><p>Jawaban tepat: <strong>E. Pemanggilan kedua dengan jangka waktu 7 hari kerja berikutnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penjatuhan hukuman langsung melanggar prosedur pemeriksaan panggilan kedua.</li><li><strong>B</strong>: Majelis kode etik ASN tidak memiliki kewenangan upaya paksa kepolisian.</li><li><strong>C</strong>: Perkara tidak boleh dibatalkan hanya karena terlapor mangkir bersidang.</li><li><strong>D</strong>: Teguran lisan apel bukan bagian dari mekanisme pemanggilan majelis etik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 22) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jangka waktu 7 (tujuh) hari kerja</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang pegawai terlapor tidak bersedia memenuhi surat panggilan kedua dari Majelis Kode Etik tanpa alasan sah. Konsekuensi hukumnya adalah...", "options": ["Pemeriksaan perkara dihentikan demi hukum dan terlapor dinyatakan bebas", "Majelis meminta bantuan kejaksaan negeri untuk melakukan penjemputan paksa", "Terlapor diberikan kesempatan mengajukan permohonan dispensasi panggilan ketiga", "Pemeriksaan ditunda hingga pegawai terlapor bersedia hadir secara sukarela", "Majelis merekomendasikan sanksi moral berdasarkan alat bukti yang ada"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsekuensi Mangkir Panggilan Kedua</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jika terlapor menolak hadir pada panggilan kedua tanpa alasan sah, <strong>majelis merekomendasikan sanksi moral berdasarkan alat bukti dan keterangan yang ada tanpa pemeriksaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Ketidakhadiran terlapor tanpa alasan sah dipandang sebagai pelepasan hak pembelaan diri di hadapan majelis. Majelis berwenang mengambil keputusan rekomendasi sanksi moral berbasis bukti yang telah dikumpulkan. Dengan demikian, penegakan etika tidak dapat dihambat oleh ketidakhadiran pihak.</p><p>Jawaban tepat: <strong>E. Majelis merekomendasikan sanksi moral berdasarkan alat bukti yang ada</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mangkir dari panggilan persidangan bukan alasan pembebasan dari tuduhan.</li><li><strong>B</strong>: Sidang etik instansi tidak memiliki mekanisme pemanggilan paksa kejaksaan.</li><li><strong>C</strong>: Regulasi penegakan kode etik tidak mengenal adanya pemanggilan ketiga.</li><li><strong>D</strong>: Penundaan tanpa batas waktu melumpuhkan fungsi penegakan hukum disiplin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>majelis merekomendasikan sanksi moral berdasarkan alat bukti</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam hal Terlapor memenuhi panggilan, pemeriksaan terhadap Terlapor diselenggarakan dalam forum...", "options": ["Sidang tertutup yang dihadiri oleh seluruh anggota Majelis", "Sidang terbuka untuk umum yang disiarkan daring di saluran instansi", "Wawancara informal empat mata antara ketua majelis dan terlapor", "Rapat pleno terbuka bersama seluruh rekan kerja di unit satker", "Musyawarah pembinaan keluarga yang dihadiri tokoh masyarakat luar"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sifat Kerahasiaan Sidang Majelis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemeriksaan terhadap terlapor dilakukan dalam <strong>sidang tertutup yang dihadiri oleh seluruh anggota Majelis</strong> secara lengkap.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Kerahasiaan sidang melindungi kehormatan pribadi terlapor dan menjaga objektivitas penilaian majelis. Ketentuan PMK 190/2018 mengatur sidang pemeriksaan wajib tertutup dan dihadiri seluruh anggota. Dengan demikian, integritas proses pemeriksaan terjaga maksimal.</p><p>Jawaban tepat: <strong>A. Sidang tertutup yang dihadiri oleh seluruh anggota Majelis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sidang terbuka melanggar asas perlindungan martabat dan rahasia jabatan.</li><li><strong>C</strong>: Pemeriksaan wajib bersifat kolegial majelis, bukan wawancara pribadi.</li><li><strong>D</strong>: Rapat terbuka melibatkan rekan kerja berpotensi menimbulkan prasangka buruk.</li><li><strong>E</strong>: Pemeriksaan perkara etik adalah proses kedinasan resmi aparatur negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sidang tertutup yang dihadiri oleh seluruh anggota</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Prinsip utama yang wajib didahulukan Majelis Kode Etik dalam menetapkan keputusan rekomendasi persidangan adalah...", "options": ["Mengikuti instruksi lisan dari atasan langsung terlapor", "Pengambilan keputusan secara musyawarah mufakat", "Menetapkan keputusan berdasarkan kehendak tunggal ketua", "Menyerahkan keputusan pada hasil polling pegawai di kantor", "Menggunakan metode pengundian tertutup di hadapan saksi"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mekanisme Pengambilan Keputusan Majelis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Keputusan Majelis diambil secara musyawarah mufakat</strong>, dan apabila tidak tercapai, keputusan diambil berdasarkan suara terbanyak.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Asas musyawarah mufakat mencerminkan nilai luhur Pancasila dalam permusyawaratan peradilan etika birokrasi. Majelis wajib mengutamakan kesepakatan bulat melalui musyawarah sebelum opsi voting. Dengan demikian, bobot pertimbangan putusan memiliki legitimasi moral tinggi.</p><p>Jawaban tepat: <strong>B. Pengambilan keputusan secara musyawarah mufakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Majelis bertindak independen dan tidak boleh diintervensi atasan terlapor.</li><li><strong>C</strong>: Keputusan majelis bersifat kolegial, bukan kehendak sepihak ketua.</li><li><strong>D</strong>: Penetapan sanksi hukum etik tidak boleh disandarkan pada jajak pendapat.</li><li><strong>E</strong>: Pengundian nasib bertentangan dengan asas pembuktian rasional persidangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Keputusan Majelis diambil secara musyawarah mufakat</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan PMK Nomor 190/PMK.01/2018, rekomendasi akhir yang dihasilkan oleh Majelis Kode Etik terdiri atas...", "options": ["Hukuman kurungan penjara atau denda ganti rugi keuangan negara", "Penurunan pangkat otomatis atau pemindahan tugas ke instansi lain", "Penjatuhan sanksi moral atau pernyataan tidak bersalah", "Pemberian surat peringatan keras atau penghentian tunjangan makan", "Penyitaan seluruh aset pribadi atau pembekuan rekening bank dinas"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Rekomendasi Putusan Majelis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keputusan Majelis berupa rekomendasi yang terdiri atas: <strong>penjatuhan sanksi moral, atau pernyataan tidak bersalah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Penegakan kode etik berfokus pada pertanggungjawaban moral dan kehormatan korps aparatur negara. Putusan akhir majelis bermuara pada dua opsi: penjatuhan sanksi moral atau rehabilitasi tidak bersalah. Dengan demikian, ranah etik terbedakan jelas dari sanksi pidana murni.</p><p>Jawaban tepat: <strong>C. Penjatuhan sanksi moral atau pernyataan tidak bersalah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pidana kurungan dan denda kerugian negara merupakan vonis pengadilan umum.</li><li><strong>B</strong>: Penurunan pangkat adalah jenis hukuman disiplin sedang/berat tersendiri.</li><li><strong>D</strong>: Surat peringatan dan tunjangan makan bukan format putusan majelis etik.</li><li><strong>E</strong>: Penyitaan aset merupakan tindakan pro-justitia penegak hukum kejaksaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penjatuhan sanksi moral</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Landasan hukum resmi yang mengatur Sistem Kerja Fleksibel di lingkungan Kementerian Keuangan adalah...", "options": ["Surat Edaran Nomor 12/MK.01/2019", "Peraturan Pemerintah Nomor 42 Tahun 2004", "Peraturan Menteri Keuangan Nomor 190/2018", "Keputusan Menteri Keuangan Nomor 416/KMK.01/2023", "Undang-Undang Nomor 20 Tahun 2023"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi Sistem Kerja Fleksibel (SKF)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sistem Kerja Fleksibel di lingkungan kementerian diatur secara resmi melalui <strong>Keputusan Menteri Keuangan Nomor 416/KMK.01/2023</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. KMK ini menetapkan pedoman adaptasi kerja modern yang memadukan fleksibilitas dan kinerja tinggi. Pengaturan komprehensif SKF kementerian dituangkan dalam KMK Nomor 416/KMK.01/2023. Dengan demikian, pelaksanaan tugas kerja jarak jauh memiliki payung hukum sah.</p><p>Jawaban tepat: <strong>D. Keputusan Menteri Keuangan Nomor 416/KMK.01/2023</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan SE tentang penerapan nilai Pancasila dan Bela Negara.</li><li><strong>B</strong>: Merupakan PP tentang pembinaan jiwa korps dan kode etik PNS nasional.</li><li><strong>C</strong>: Merupakan PMK tentang kode etik dan kode perilaku PNS Kemenkeu.</li><li><strong>E</strong>: Merupakan payung hukum Undang-Undang Aparatur Sipil Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Keputusan Menteri Keuangan Nomor 416/KMK.01/2023</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Prinsip dasar yang ditegaskan dalam pelaksanaan Sistem Kerja Fleksibel bagi aparatur sipil negara adalah bahwa...", "options": ["Pegawai dibebaskan sepenuhnya dari kewajiban pelaporan kinerja", "Fleksibilitas kerja bukan berarti kebebasan tanpa batas", "Pengawasan kedinasan dihapus demi kenyamanan kerja di rumah", "Target capaian kinerja diturunkan selama pelaksanaan kerja jarak jauh", "Pegawai boleh mengalihkan pekerjaan dinas kepada pihak keluarga"], "answer": 1, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Fleksibilitas dalam SKF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam pelaksanaan SKF, ditegaskan bahwa <strong>fleksibilitas bukan berarti bebas tanpa batas</strong>, melainkan menuntut tanggung jawab etis tinggi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Bekerja di luar kantor menuntut integritas pribadi yang lebih tinggi karena minim pengawasan fisik langsung. Konsep SKF menegaskan bahwa fleksibilitas ruang dan waktu tetap terikat pada target akuntabilitas kerja. Dengan demikian, profesionalitas pegawai tetap terjaga prima.</p><p>Jawaban tepat: <strong>B. Fleksibilitas kerja bukan berarti kebebasan tanpa batas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pelaporan hasil kerja harian justru menjadi syarat mutlak sistem SKF.</li><li><strong>C</strong>: Pengawasan digital tetap berjalan aktif melalui sistem monitoring aplikasi.</li><li><strong>D</strong>: Standar mutu dan kuantitas target capaian kinerja tetap sama persis.</li><li><strong>E</strong>: Tugas kedinasan melekat personal pada aparatur dan haram dialihkan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penting untuk menegaskan bahwa fleksibilitas</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Tantangan moral utama yang dihadapi pegawai saat menjalankan tugas kedinasan dari rumah (WFH) berakar pada kondisi...", "options": ["Ketiadaan akses terhadap perangkat komputer jinjing dinas", "Tingginya biaya langganan jaringan internet di rumah tinggal", "Situasi kerja yang tidak selalu diawasi secara langsung", "Larangan berkomunikasi dengan rekan kerja melalui media daring", "Perubahan jam kerja kantor menjadi waktu kerja malam hari"], "answer": 2, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pentingnya Integritas pada Situasi Kerja Fleksibel</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada era SKF, integritas pegawai menjadi sangat krusial karena pegawai <strong>tidak selalu berada dalam pengawasan langsung</strong> oleh atasan di ruangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Keberhasilan sistem kerja fleksibel bertumpu pada kejujuran moral pegawai dalam memanfaatkan jam kerja dinas. Ketiadaan pengawasan fisik langsung menuntut komitmen etis mandiri dalam menyelesaikan tugas kantor. Dengan demikian, integritas individu menjadi kunci sukses kerja fleksibel.</p><p>Jawaban tepat: <strong>C. Situasi kerja yang tidak selalu diawasi secara langsung</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Fasilitas sarana laptop dinas disediakan kantor bagi pegawai yang berhak.</li><li><strong>B</strong>: Biaya operasional komunikasi dinas didukung alokasi uang paket data.</li><li><strong>D</strong>: Komunikasi virtual justru diwajibkan secara intensif dalam koordinasi WFH.</li><li><strong>E</strong>: Jam kerja kedinasan WFH tetap mengacu pada jam kerja dinas normal kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengawasan langsung</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan KMK Nomor 416/KMK.01/2023, Sistem Kerja Fleksibel di Kementerian Keuangan mencakup fleksibilitas pada aspek...", "options": ["Keringanan kualifikasi keahlian teknis pegawai dalam jabatan", "Penetapan besaran standar biaya masukan anggaran belanja kantor", "Kewenangan mengubah struktur organisasi eselon satker secara mandiri", "Fleksibilitas dari sisi tempat bekerja dan/atau waktu bekerja", "Pemberian dispensasi pemenuhan target kinerja tahunan dinas"], "answer": 3, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dimensi Fleksibilitas SKF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sistem Kerja Fleksibel memungkinkan pegawai melaksanakan tugas dengan <strong>fleksibilitas dari sisi tempat dan/atau waktu</strong> dengan tetap menjaga produktivitas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Kebijakan SKF memberikan keleluasaan lokasi (WFO, WFH, WFA) serta penyesuaian jam kerja (FWH) yang dinamis. KMK 416/2023 menegaskan dua pilar SKF adalah fleksibilitas tempat dan waktu kerja kedinasan. Dengan demikian, adaptabilitas pegawai meningkat tanpa mengurangi output kerja.</p><p>Jawaban tepat: <strong>D. Fleksibilitas dari sisi tempat bekerja dan/atau waktu bekerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kualifikasi standar kompetensi jabatan tetap berlaku baku mengikat.</li><li><strong>B</strong>: Standar biaya masukan diatur secara terpusat oleh peraturan menteri keuangan.</li><li><strong>C</strong>: Perubahan nomenklatur struktur organisasi adalah wewenang kementerian PANRB.</li><li><strong>E</strong>: Target kinerja tahunan tetap wajib dipenuhi sesuai perjanjian kinerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>secara fleksibel dari sisi tempat</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tujuan pokok penerapan kode etik dan kode perilaku aparatur pada pelaksanaan Sistem Kerja Fleksibel adalah...", "options": ["Mempercepat penjualan barang milik negara yang tidak terpakai", "Mengurangi alokasi anggaran belanja pegawai secara permanen", "Membatasi keterlibatan staf bawahan dalam forum koordinasi pusat", "Meniadakan kewajiban absensi sidik jari pada jam masuk kerja", "Menjaga profesionalisme, integritas, dan nama baik instansi"], "answer": 4, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Etika pada Era Fleksibel</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Implementasi kode etik pada era SKF sangat krusial guna <strong>menjaga profesionalisme, integritas, serta nama baik instansi</strong> di mata publik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Bekerja fleksibel tidak boleh menurunkan wibawa kementerian dan mutu pelayanan kepada masyarakat. Penerapan etika menjamin pegawai tetap profesional, jujur, serta menjaga nama baik instansi di ruang publik. Dengan demikian, kepercayaan masyarakat terhadap aparatur tetap kokoh.</p><p>Jawaban tepat: <strong>E. Menjaga profesionalisme, integritas, dan nama baik instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penghapusan aset BMN tunduk pada tata kelola pengelolaan kekayaan negara.</li><li><strong>B</strong>: Kebijakan SKF bukan sarana pemotongan hak finansial gaji pegawai.</li><li><strong>C</strong>: Kerja fleksibel justru menuntut koordinasi virtual yang lebih inklusif.</li><li><strong>D</strong>: Perekaman presensi kehadiran tetap wajib dilaksanakan via aplikasi HRIS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjaga nama baik institusi</strong></div></div>"}, {"category": "ETIKA", "sub": "Etika PNS • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dengan menjunjung tinggi etika kedinasan dalam era fleksibel, aparatur turut mewujudkan birokrasi yang bersih dan berorientasi pada...", "options": ["Pelayanan publik yang unggul dan bermartabat bagi bangsa", "Peningkatan keuntungan komersial instansi pengelola keuangan", "Kemudahan perolehan izin dispensasi tugas bagi staf pimpinan", "Pemusatan seluruh layanan pemerintahan hanya di kota besar", "Penghapusan seluruh mekanisme pemeriksaan pengawasan audit"], "answer": 0, "source": "Etika Pegawai Negeri Sipil", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cita-Cita Akhir Budaya Etis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan etika kerja fleksibel bermuara pada terbentuknya <strong>birokrasi yang bersih dan berorientasi pada pelayanan publik yang unggul</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Cita-cita akhir reformasi birokrasi adalah menghadirkan pelayanan publik terbaik dan bersih dari korupsi. Menjunjung etika dalam bekerja fleksibel memastikan birokrasi berorientasi pada pelayanan unggul bagi rakyat. Dengan demikian, tujuan luhur pengabdian aparatur tercapai sempurna.</p><p>Jawaban tepat: <strong>A. Pelayanan publik yang unggul dan bermartabat bagi bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Instansi pemerintah bukan entitas bisnis komersial yang mengejar laba.</li><li><strong>C</strong>: Pelayanan publik menolak segala bentuk privilese perlakuan istimewa.</li><li><strong>D</strong>: Pelayanan pemerintah harus merata hingga ke seluruh pelosok daerah.</li><li><strong>E</strong>: Pengawasan audit akuntabilitas mutlak diperlukan dalam tata kelola modern.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Etika Pegawai Negeri Sipil (Hlm. 23) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pelayanan publik yang unggul</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_etika_p02_review_annot_v1_user_<?= (int)$USER->id ?>';
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
        <td>${row.correct}/100</td>
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
<?php
/**
 * STANGOLDEN.exam • Tata Aturan Kepegawaian Paket 04 (Halaman 42–51)
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
// $DB->get_record("stgtak_p04_rank") => tabel fisik "mdlhv_stgtak_p04_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgtak_p04_rank';
const STG_REVIEW_T = 'stgtak_p04_rev';
const STG_ANNO_T = 'stgtak_p04_anno';
const STG_KEY = [0, 1, 2, 3, 4, 1, 0, 3, 2, 4, 2, 3, 4, 0, 1, 3, 2, 4, 0, 1, 1, 0, 3, 2, 4, 0, 2, 1, 4, 3, 4, 3, 2, 1, 0, 2, 4, 0, 3, 1, 0, 2, 1, 4, 3, 1, 3, 0, 4, 2, 3, 4, 0, 2, 1, 4, 0, 3, 1, 2, 2, 1, 4, 3, 0, 3, 0, 2, 4, 1, 1, 4, 3, 0, 2, 0, 1, 4, 2, 3, 4, 0, 2, 1, 3, 2, 4, 1, 3, 0, 3, 2, 1, 4, 0, 4, 3, 1, 0, 2];
// Tabel belum ada dibuat otomatis pada submit pertama (bukan saat membuka halaman).
function stgtak_table(string $name, bool $review): void {
    global $DB, $CFG;
    require_once($CFG->libdir . '/ddllib.php');
    if (!$DB->get_manager()->table_exists(new xmldb_table($name))) {
        throw new RuntimeException('Tabel latihan belum disiapkan oleh administrator.');
    }
}
function stgtak_create_table(string $name, bool $review): void {
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
function stgtak_create_annotation_table(): void {
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
function stgtak_annotation_exists(): void {
    global $CFG, $DB;
    require_once($CFG->libdir . '/ddllib.php');
    if (!$DB->get_manager()->table_exists(new xmldb_table(STG_ANNO_T))) {
        throw new RuntimeException('Tabel anotasi belum disiapkan oleh administrator.');
    }
}
function stgtak_json(array $data,int $status=200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
}
$stgaction=optional_param('action','',PARAM_ALPHANUMEXT);

if ($stgaction === 'admin_save_question') {
    if (!is_siteadmin($USER)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Hanya administrator yang diizinkan mengedit soal.'], JSON_UNESCAPED_UNICODE);
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
            stgtak_create_table(STG_LB_T, false);
            stgtak_create_table(STG_REVIEW_T, true);
            stgtak_create_annotation_table();
            echo '<h2>✅ Tabel ranking, review dan anotasi berhasil disiapkan.</h2><p>Database Moodle memakai prefix dari config.php (contoh: mdlhv_). Tidak perlu membuat tabel tambahan apabila tiga tabel sudah ada.</p><p><a href="' . htmlspecialchars((new moodle_url('/my/'))->out(false), ENT_QUOTES, 'UTF-8') . '">Kembali ke dashboard</a></p>';
        } catch (Throwable $e) {
            error_log('STANGOLDEN TAK P04 HLM42-51 SETUP: ' . $e->getMessage());
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
            if($_SERVER['REQUEST_METHOD']!=='POST') stgtak_json(['success'=>false,'message'=>'Metode tidak valid.'],405);
            require_sesskey();
            $raw=required_param('answersjson',PARAM_RAW);
            $a=json_decode($raw,true);
            if(!is_array($a)||count($a)!==100||array_keys($a)!==range(0,99)) stgtak_json(['success'=>false,'message'=>'Format jawaban tidak valid.'],400);
            $clean=[];$correct=0;
            foreach($a as $i=>$v){
                if($v===null){$clean[]=-1;continue;}
                if(!is_int($v)||$v<0||$v>4) stgtak_json(['success'=>false,'message'=>'Pilihan jawaban tidak valid.'],400);
                $clean[]=$v;
                if($v===STG_KEY[$i]) $correct++;
            }
            $score=$correct;
            $time=max(0,min(3900,required_param('timetaken',PARAM_INT)));
            // Buat tabel ranking, review, dan anotasi secara otomatis hanya ketika submit.
            // Moodle menangani prefix database berdasarkan konfigurasi $CFG->prefix.
            stgtak_create_table(STG_LB_T, false);
            stgtak_create_table(STG_REVIEW_T, true);
            stgtak_create_annotation_table();
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
            stgtak_json(['success'=>true,'firstattemptsaved'=>$first]);
        }
        if ($stgaction === 'anno_load') {
            if (!$DB->get_manager()->table_exists(new xmldb_table(STG_ANNO_T))) {
                stgtak_json(['success'=>true,'annotations'=>new stdClass()]);
            }
            $rows = $DB->get_records(STG_ANNO_T, ['userid'=>(int)$USER->id]);
            $out = new stdClass();
            foreach ($rows as $r) {
                $q = (int)$r->questionnum;
                if ($q < 0 || $q > 99) { continue; }
                $strokes = json_decode((string)$r->strokesjson, true);
                $out->{(string)$q} = is_array($strokes) ? $strokes : [];
            }
            stgtak_json(['success'=>true,'annotations'=>$out]);
        }
        if ($stgaction === 'anno_save') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') stgtak_json(['success'=>false,'message'=>'Metode tidak valid.'],405);
            require_sesskey();
            stgtak_annotation_exists();
            // Anotasi hanya boleh disimpan setelah peserta punya review yang tersimpan.
            stgtak_table(STG_REVIEW_T,true);
            if (!$DB->record_exists(STG_REVIEW_T, ['userid'=>(int)$USER->id])) {
                stgtak_json(['success'=>false,'message'=>'Selesaikan ujian dahulu.'],403);
            }
            $q = required_param('question', PARAM_INT);
            if ($q < 0 || $q >= 100) stgtak_json(['success'=>false,'message'=>'Nomor soal tidak valid.'],400);
            $raw = required_param('strokesjson', PARAM_RAW);
            if (strlen($raw) > 250000) stgtak_json(['success'=>false,'message'=>'Coretan terlalu besar.'],413);
            $strokes = json_decode($raw, true);
            if (!is_array($strokes) || count($strokes)>350) stgtak_json(['success'=>false,'message'=>'Data coretan tidak valid.'],400);
            foreach ($strokes as $stroke) {
                if (!is_array($stroke) || !isset($stroke['tool'], $stroke['points']) ||
                    !in_array($stroke['tool'], ['pen','marker','eraser'], true) || !is_array($stroke['points']) || count($stroke['points']) > 3000) {
                    stgtak_json(['success'=>false,'message'=>'Format coretan tidak valid.'],400);
                }
                foreach ($stroke['points'] as $point) {
                    if (!is_array($point) || count($point)!==2 || !is_numeric($point[0]) || !is_numeric($point[1]) ||
                        $point[0]<0 || $point[0]>1 || $point[1]<0 || $point[1]>1) {
                        stgtak_json(['success'=>false,'message'=>'Koordinat coretan tidak valid.'],400);
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
            stgtak_json(['success'=>true]);
        }
        if ($stgaction === 'anno_clear') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') stgtak_json(['success'=>false,'message'=>'Metode tidak valid.'],405);
            require_sesskey();
            stgtak_annotation_exists();
            $DB->delete_records(STG_ANNO_T, ['userid'=>(int)$USER->id]);
            stgtak_json(['success'=>true]);
        }
        if($stgaction==='leaderboard') {
            if (!$DB->get_manager()->table_exists(new xmldb_table(STG_LB_T))) {
                stgtak_json(['success'=>true,'leaderboard'=>[]]);
            }
            $records=$DB->get_records(STG_LB_T,null,'score DESC, timetaken ASC, timecreated ASC');
            $rows=[];$rank=0;
            foreach($records as $r){$rank++;$rows[]=['rank'=>$rank,'username'=>$r->username,'score'=>(int)$r->score,'correct'=>(int)$r->correct,'timetaken'=>(int)$r->timetaken,'isme'=>((int)$r->userid===(int)$USER->id)];}
            stgtak_json(['success'=>true,'leaderboard'=>$rows]);
        }
        if($stgaction==='last_review') {
            if (!$DB->get_manager()->table_exists(new xmldb_table(STG_REVIEW_T))) {
                stgtak_json(['success'=>false,'message'=>'Belum ada jawaban terakhir.'],404);
            }
            $r=$DB->get_record(STG_REVIEW_T,['userid'=>(int)$USER->id]);
            if(!$r) stgtak_json(['success'=>false,'message'=>'Belum ada jawaban terakhir.'],404);
            $a=json_decode($r->answersjson,true);
            if(!is_array($a)||count($a)!==100) stgtak_json(['success'=>false,'message'=>'Data jawaban tidak valid.'],500);
            stgtak_json(['success'=>true,'review'=>['answers'=>array_map(static function($v) { return ((int)$v < 0) ? null : (int)$v; }, $a),'score'=>(int)$r->score,'correct'=>(int)$r->correct,'timetaken'=>(int)$r->timetaken]]);
        }
        stgtak_json(['success'=>false,'message'=>'Aksi tidak dikenal.'],400);
    }catch(Throwable $e){error_log('STANGOLDEN TAK P04 HLM42-51: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Tata Aturan Kepegawaian Paket 04 (Hlm. 42–51) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 04 • Hlm. 42–51</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 04 • Hlm. 42–51</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Tata Aturan Kepegawaian Paket 04</h2>
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
const STG_LOCAL_KEY="stg_tak_p04_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Bagi organisasi Kementerian Keuangan, salah satu tujuan penerapan Manajemen Talenta adalah...", "options": ["Membangun kepercayaan dan meningkatkan engagement pegawai", "Menyeragamkan seluruh latar belakang pendidikan aparatur", "Membatasi pengisian formasi pada tingkat pejabat eselon", "Membatasi mobilitas karier pada satu direktorat jenderal", "Menetapkan besaran tunjangan kinerja secara mandiri"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tujuan Manajemen Talenta bagi Organisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Tujuan <strong>Manajemen Talenta</strong> bagi organisasi Kementerian Keuangan antara lain <strong>membangun kepercayaan dan meningkatkan engagement Pegawai</strong> kepada organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena peraturan kepegawaian menegaskan bahwa penerapan manajemen talenta bertujuan menumbuhkan kepercayaan serta keterikatan pegawai terhadap organisasi Kementerian Keuangan. Keterikatan yang tinggi berdampak positif pada produktivitas dan loyalitas aparatur. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Membangun kepercayaan dan meningkatkan engagement pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bukan tujuan manajemen talenta karena keberagaman latar belakang pendidikan tetap dihargai.</li><li><strong>C</strong>: Pola karier terbuka menjamin kesempatan yang sama bagi seluruh aparatur yang memenuhi kualifikasi.</li><li><strong>D</strong>: Salah karena manajemen talenta justru membuka mobilitas karier lintas unit dan instansi.</li><li><strong>E</strong>: Keliru karena penentuan besaran tunjangan kinerja diatur melalui regulasi remunerasi nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>meningkatkan engagement Pegawai • kepercayaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Manfaat penerapan Manajemen Talenta Kementerian Keuangan bagi para pegawai adalah memberikan...", "options": ["Jaminan bebas dari seluruh sanksi hukuman kedinasan", "Kesempatan yang sama dan sarana aktualisasi diri", "Kenaikan pangkat otomatis setiap pergantian semester kerja", "Kewenangan memilih lokasi penempatan dinas berdasar domisili", "Pembebasan penuh dari kewajiban kontrak kinerja tahunan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Manfaat Manajemen Talenta bagi Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Manfaat <strong>Manajemen Talenta</strong> bagi para pegawai adalah memberikan <strong>kesempatan yang sama dalam mengembangkan karier dan sarana perwujudan aktualisasi diri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena manajemen talenta menjamin asas kesetaraan peluang bagi seluruh pegawai berprestasi untuk mengaktualisasikan potensinya. Skema ini menghadirkan transparansi jalur peningkatan karier berbasis kualifikasi dan rekam jejak. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Kesempatan yang sama dan sarana aktualisasi diri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena setiap pegawai tetap tunduk pada ketentuan disiplin aparatur sipil negara.</li><li><strong>C</strong>: Bukan manfaat yang diatur karena kenaikan pangkat mensyaratkan kriteria kinerja tertentu.</li><li><strong>D</strong>: Penempatan PNS diatur sepenuhnya berdasarkan analisis kebutuhan organisasi, bukan pemilihan bebas pegawai.</li><li><strong>E</strong>: Tidak tepat karena setiap pegawai wajib menyusun dan memenuhi kontrak kinerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sarana perwujudan aktualisasi\ndiri • kesempatan yang\nsama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pola karier pegawai di lingkungan Kementerian Keuangan didefinisikan sebagai skema yang berisi...", "options": ["Rincian alokasi anggaran belanja pegawai unit perbendaharaan", "Daftar absensi kehadiran kerja harian pada kantor pelayanan", "Sekumpulan norma/petunjuk penempatan dan pemindahan PNS", "Pedoman pengadaan barang dan jasa pemerintah daerah", "Standar operasional prosedur pengelolaan kas negara"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Definisi Pola Karier</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Pola karier</strong> merupakan <strong>skema yang berisi sekumpulan norma/petunjuk yang perlu diperhatikan dalam penempatan dan/atau pemindahan PNS</strong> sejak diangkat pertama kali sampai dengan pensiun atau pemberhentian.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena secara konseptual pola karier memuat panduan normatif yang terstruktur mengenai siklus penempatan dan pergerakan jabatan pegawai. Norma ini menjadi pedoman pembinaan karier yang terukur dan adil sepanjang masa dinas. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Sekumpulan norma/petunjuk penempatan dan pemindahan PNS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan pengertian pola karier melainkan dokumen perencanaan anggaran belanja aparatur.</li><li><strong>B</strong>: Merupakan instrumen pengawasan jam kerja pegawai, bukan skema pola karier.</li><li><strong>D</strong>: Merupakan ruang lingkup pengadaan pemerintah, bukan tata kelola karier kepegawaian.</li><li><strong>E</strong>: Merupakan domain perbendaharaan negara, bukan manajemen pola karier aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pola karier merupakan skema • sekumpulan norma/petunjuk</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Ruang lingkup skema pola karier PNS Kementerian Keuangan berlaku sejak...", "options": ["Mengikuti seleksi masuk sekolah kedinasan keuangan", "Menyelesaikan pendidikan dasar militer aparatur", "Menerima penetapan kelulusan ujian penyetaraan ijazah", "Diangkat pertama kali sebagai PNS sampai pensiun", "Menduduki jabatan pimpinan tinggi madya pertama kali"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Rentang Waktu Pola Karier Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Skema pola karier mencakup masa dinas <strong>sejak diangkat pertama kali sebagai PNS Kemenkeu sampai dengan pensiun dan/atau pemberhentian</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena ketentuan menegaskan bahwa perjalanan pola karier berlangsung komprehensif mulai dari status pengangkatan pertama hingga mencapai batas usia pensiun atau pemberhentian. Masa ini menjamin kepastian arah pembinaan profesionalisme aparatur. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Diangkat pertama kali sebagai PNS sampai pensiun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tahapan ini merupakan proses seleksi calon taruna/mahasiswa, belum pengangkatan PNS.</li><li><strong>B</strong>: Bukan batas awal pola karier karena latihan dasar hanya bagian dari tahapan prajabatan.</li><li><strong>C</strong>: Merupakan peningkatan kualifikasi pendidikan, bukan awal mula skema pola karier.</li><li><strong>E</strong>: Terlalu sempit karena pola karier telah berjalan jauh sebelum menduduki jabatan pimpinan tinggi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sampai dengan pensiun • Pola karier</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Skema pola karier pegawai Kementerian Keuangan terdiri atas tiga unsur pokok yaitu...", "options": ["Masa orientasi, penetapan tunjangan, dan batas usia", "Penetapan pangkat, sistem penggajian, dan cuti pegawai", "Analisis beban kerja, evaluasi jabatan, dan audit kinerja", "Uji kompetensi, masa percobaan, dan penjatuhan disiplin", "Tahapan karier, bentuk pola karier, dan jalur karier"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Unsur Pembentuk Skema Pola Karier</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Skema pola karier terdiri atas tahapan karier, bentuk pola karier, dan jalur karier</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena ketentuan PMK 224/PMK.01/2020 merumuskan tiga pilar utama pembangun pola karier yaitu tahapan, bentuk, dan jalur karier pegawai. Ketiga elemen ini membentuk arsitektur pembinaan SDM yang terpadu. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Tahapan karier, bentuk pola karier, dan jalur karier</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan komponen skema pola karier melainkan bagian parsial administrasi kepegawaian.</li><li><strong>B</strong>: Merupakan hak dan status kepegawaian umum, bukan pilar pembentuk skema pola karier.</li><li><strong>C</strong>: Merupakan bagian dari analisis organisasi dan kelembagaan.</li><li><strong>D</strong>: Merupakan instrumen penilaian dan pembinaan aparatur, bukan pilar skema pola karier.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tahapan karier,\nbentuk pola karier, dan jalur karier • Skema pola karier terdiri atas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pola karier di lingkungan Kementerian Keuangan diatur secara komprehensif melalui regulasi...", "options": ["Peraturan Menteri Keuangan Nomor 100/PMK.01/2018", "Peraturan Menteri Keuangan Nomor 224/PMK.01/2020", "Peraturan Pemerintah Nomor 11 Tahun 2017", "Keputusan Menteri Keuangan Nomor 300/KMK.01/2022", "Undang-Undang Nomor 20 Tahun 2023"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Regulasi Pola Karier di Lingkungan Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pola karier di Kementerian Keuangan diatur dalam <strong>Peraturan Menteri Keuangan Nomor 224/PMK.01/2020</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena PMK Nomor 224/PMK.01/2020 merupakan payung hukum khusus yang mengatur manajemen talenta dan pola karier aparatur di Kementerian Keuangan. Aturan ini menjadi acuan operasional mutasi, promosi, dan penempatan. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Peraturan Menteri Keuangan Nomor 224/PMK.01/2020</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan regulasi pola karier Kementerian Keuangan.</li><li><strong>C</strong>: Merupakan peraturan pemerintah tentang manajemen PNS nasional secara umum.</li><li><strong>D</strong>: Merupakan KMK tentang manajemen kinerja pegawai di Kementerian Keuangan.</li><li><strong>E</strong>: Merupakan undang-undang aparatur sipil negara tingkat nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peraturan\nMenteri\nKeuangan\nNomor\n224/PMK.01/2020 • pola karier di Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Biro SDM merancang pemetaan talenta guna memperkuat keterikatan aparatur. Hal tersebut mewujudkan tujuan...", "options": ["Membangun kepercayaan dan engagement terhadap organisasi", "Pemberian dispensasi jam masuk kerja pegawai daerah", "Pengurangan frekuensi rotasi kerja aparatur fungsional", "Penerbitan kartu identitas pegawai kementerian secara massal", "Penghapusan evaluasi sasaran kinerja pegawai triwulanan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penerapan Konsep Engagement Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penerapan pemetaan talenta ditujukan untuk <strong>membangun kepercayaan dan meningkatkan engagement Pegawai kepada organisasi Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena upaya meningkatkan keterikatan kerja (engagement) dan kepercayaan langsung merefleksikan sasaran strategis manajemen talenta. Pegawai yang merasa dihargai potensinya akan menunjukkan dedikasi optimal bagi kementerian. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Membangun kepercayaan dan engagement terhadap organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bukan tujuan talenta karena dispensasi jam kerja diatur dalam aturan kehadiran kedinasan.</li><li><strong>C</strong>: Keliru karena rotasi kerja tetap dijalankan demi penyegaran organisasi dan pencegahan risiko.</li><li><strong>D</strong>: Merupakan urusan teknis tata usaha kepegawaian, bukan inti penguatan keterikatan talenta.</li><li><strong>E</strong>: Salah karena evaluasi sasaran kinerja wajib dilakukan secara berkala.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>meningkatkan engagement Pegawai\nkepada organisasi • membangun kepercayaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Seorang pegawai memanfaatkan program talenta untuk memperluas kompetensi kepemimpinan. Hal ini mencerminkan fungsi...", "options": ["Keringanan sanksi atas ketidakhadiran kerja berkala", "Jaminan mutlak menduduki jabatan eselon tertinggi", "Pembebasan penugasan mendadak dari pimpinan kantor", "Sarana aktualisasi diri dan pengembangan karier", "Penggantian tugas utama dengan penugasan sosial"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Implementasi Aktualisasi Diri Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Bagi pegawai, manajemen talenta berfungsi sebagai sarana untuk <strong>mengembangkan karier dan sarana perwujudan aktualisasi diri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pemanfaatan peluang belajar dan pengembangan diri oleh pegawai berorientasi pada aktualisasi kapasitas kepemimpinan dan kemajuan karier. Langkah ini sesuai dengan prinsip manajemen talenta yang memberdayakan potensi individu aparatur. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Sarana aktualisasi diri dan pengembangan karier</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Salah karena sanksi disiplin kehadiran tidak dapat diringankan lewat keikutsertaan talenta.</li><li><strong>B</strong>: Tidak ada jaminan mutlak karena pengisian jabatan harus melalui persaingan talenta objektif.</li><li><strong>C</strong>: Keliru karena kewajiban kedinasan atas perintah pimpinan tetap melekat penuh.</li><li><strong>E</strong>: Bukan fungsi talenta karena tugas substantif kedinasan tetap prioritas utama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sarana perwujudan aktualisasi\ndiri • mengembangkan karier</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Dalam skema pola karier Kemenkeu, unsur yang BUKAN merupakan bagian dari skema tersebut adalah...", "options": ["Jalur promosi vertikal", "Jalur mutasi horizontal", "Alokasi anggaran pensiun", "Jalur mobilitas diagonal", "Jalur penugasan instansi"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pembedaan Elemen Skema Pola Karier</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Skema pola karier Kemenkeu hanya terdiri atas <strong>tahapan karier, bentuk pola karier, dan jalur karier</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat sebagai pengecualian karena alokasi anggaran pensiun merupakan domain tata kelola keuangan perbendaharaan dan jaminan sosial, bukan pilar pembentuk skema pola karier. Sementara itu bentuk, tahapan, dan jalur merupakan komponen sahih pola karier. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Alokasi anggaran pensiun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan salah satu unsur pokok skema pola karier.</li><li><strong>B</strong>: Merupakan salah satu unsur pokok skema pola karier.</li><li><strong>D</strong>: Merupakan salah satu unsur pokok skema pola karier.</li><li><strong>E</strong>: Merupakan bagian dari rincian bentuk pola karier yang diakui.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tahapan karier,\nbentuk pola karier, dan jalur karier</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Esensi utama dimasukkannya norma dan petunjuk penempatan ke dalam pola karier adalah untuk...", "options": ["Mewajibkan mutasi antarwilayah dilakukan setiap enam bulan", "Membatasi kewenangan pimpinan unit kerja dalam evaluasi bawahan", "Memastikan kenaikan kelas jabatan diberikan setiap tahun dinas", "Mengurangi alokasi anggaran operasional kantor vertikal daerah", "Menjamin transparansi serta kepastian penempatan kerja pegawai"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Esensi Norma Penempatan Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Norma dan petunjuk dalam pola karier berfungsi mengarahkan <strong>penempatan dan/atau pemindahan PNS</strong> secara transparan dan berkeadilan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena keberadaan norma baku memberikan kepastian hukum, transparansi, serta objektivitas dalam pemindahan maupun promosi jabatan. Hal ini mencegah keputusan mutasi yang bersifat subjektif. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Menjamin transparansi serta kepastian penempatan kerja pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tidak tepat karena masa penugasan memiliki batas minimal kelayakan waktu tersendiri.</li><li><strong>B</strong>: Pimpinan unit kerja memegang peranan krusial dalam pembinaan dan penilaian sasaran kinerja pegawai.</li><li><strong>C</strong>: Keliru karena kenaikan kelas jabatan bergantung pada ketersediaan formasi dan kompetensi.</li><li><strong>D</strong>: Bukan esensi pola karier karena penghematan anggaran operasional merupakan hal terpisah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penempatan dan/atau pemindahan PNS • norma/petunjuk</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam tahapan pola karier, masa percobaan didefinisikan sebagai periode ketika...", "options": ["Pegawai memasuki satu tahun menjelang batas usia pensiun", "Pegawai menyelesaikan program magang penugasan luar negeri", "CPNS Kemenkeu belum diangkat sebagai Pegawai Negeri Sipil", "PNS Kemenkeu menduduki jabatan pimpinan tinggi madya", "Pegawai sedang menjalani pembebasan tugas untuk studi lanjut"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pengertian Masa Percobaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Masa percobaan</strong> yaitu saat <strong>CPNS Kemenkeu belum diangkat sebagai PNS Kemenkeu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena status masa percobaan diperuntukkan secara khusus bagi calon PNS sebelum resmi disumpah dan diangkat menjadi PNS penuh. Periode ini menjadi sarana pengujian integritas, kompetensi, dan kedisiplinan calon aparatur. Oleh karenanya, opsi B adalah jawaban yang benar. Dengan demikian, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. CPNS Kemenkeu belum diangkat sebagai Pegawai Negeri Sipil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan definisi dari masa menjelang pensiun.</li><li><strong>B</strong>: Merupakan penugasan khusus dalam pengembangan kapasitas, bukan masa percobaan.</li><li><strong>D</strong>: Merupakan fase lanjutan dalam jenjang karier struktural aparatur.</li><li><strong>E</strong>: Merupakan status tugas belajar kedinasan, bukan masa percobaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Masa percobaan • saat CPNS Kemenkeu belum diangkat sebagai\nPNS</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Masa branding dalam tahapan pola karier pegawai berlangsung selama...", "options": ["Enam bulan sejak awal masuk menjadi calon pegawai", "Satu tahun sebelum memasuki batas usia pensiun dinas", "Tiga tahun berturut-turut pada unit penempatan pertama", "Dua tahun setelah CPNS diangkat menjadi PNS Kemenkeu", "Lima tahun penuh sebelum diperbolehkan promosi jabatan"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Jangka Waktu Masa Branding</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Masa branding</strong> yaitu masa <strong>dua tahun setelah CPNS Kemenkeu diangkat sebagai PNS Kemenkeu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena aturan menegaskan durasi masa branding adalah tepat dua tahun pascapengangkatan sebagai PNS. Kurun waktu ini difokuskan pada internalisasi nilai dan pembentukan karakter aparatur. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Dua tahun setelah CPNS diangkat menjadi PNS Kemenkeu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Terlalu singkat dan merujuk pada masa prajabatan/orientasi awal.</li><li><strong>B</strong>: Merupakan batasan waktu untuk masa menjelang batas usia pensiun.</li><li><strong>C</strong>: Keliru karena durasi branding yang ditetapkan regulasi adalah dua tahun.</li><li><strong>E</strong>: Salah karena lima tahun merujuk pada rentang rencana pengembangan karier jangka menengah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Masa branding • dua tahun setelah CPNS Kemenkeu\ndiangkat sebagai PNS</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama ditetapkannya masa branding bagi PNS baru Kementerian Keuangan adalah...", "options": ["Membebaskan pegawai dari pengawasan kode etik kedinasan", "Menyiapkan berkas pengusulan kenaikan pangkat pilihan", "Mempercepat pemberhentian dari jabatan administrasi lama", "Memberikan hak mengajukan mutasi mandiri antarprovinsi", "Membentuk pengetahuan, sikap, dan ciri profesionalitas diri"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tujuan Pelaksanaan Masa Branding</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Masa branding bertujuan untuk <strong>membentuk pengetahuan, sikap, dan perilaku yang akan menjadi ciri khas/kekuatan professional diri PNS Kemenkeu</strong> yang bersangkutan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena masa branding dirancang khusus untuk menanamkan kompetensi inti, perilaku kerja unggul, dan integritas tinggi sebagai fondasi profesionalitas. Pembentukan jati diri ini menjadi modal keberhasilan penugasan jangka panjang. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Membentuk pengetahuan, sikap, dan ciri profesionalitas diri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sangat keliru karena pengawasan kode etik justru diperketat pada masa ini.</li><li><strong>B</strong>: Bukan tujuan masa branding karena kenaikan pangkat memiliki mekanisme tersendiri.</li><li><strong>C</strong>: Salah arah karena masa branding ditujukan untuk pembinaan, bukan pemberhentian.</li><li><strong>D</strong>: Keliru karena pegawai baru belum memenuhi syarat minimal masa dinas untuk mutasi mandiri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Masa branding bertujuan untuk • kekuatan professional diri</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Program pengembangan SDM pada masa branding paling kurang meliputi penguatan...", "options": ["Nilai kementerian, kode etik, perilaku, dan disiplin", "Kemitraan bisnis dengan badan usaha milik daerah", "Strategi investasi portofolio keuangan internasional", "Keterampilan negosiasi perdagangan ekspor-impor swasta", "Metode penyusunan peraturan perundang-undangan pidana"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Muatan Pengembangan SDM Masa Branding</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Program pengembangan SDM masa branding paling kurang meliputi: <strong>penguatan nilai-nilai Kementerian Keuangan, kode etik dan kode perilaku, dan disiplin PNS Kemenkeu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena fondasi awal pembinaan aparatur menitikberatkan pada penanaman nilai-nilai organisasi, kode etik, dan kepatuhan disiplin kerja. Materi ini menjamin integritas moral dan profesionalisme aparatur sejak dini. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Nilai kementerian, kode etik, perilaku, dan disiplin</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bukan materi inti pembinaan SDM masa branding aparatur kementerian.</li><li><strong>C</strong>: Merupakan keahlian sektoral khusus, bukan muatan wajib dasar masa branding.</li><li><strong>D</strong>: Merupakan domain sektor perdagangan, bukan substansi etika kepegawaian.</li><li><strong>E</strong>: Merupakan bidang legislasi pidana nasional, bukan muatan etika pegawai Kemenkeu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Penguatan nilai-nilai Kementerian Keuangan • Program pengembangan SDM Kemenkeu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Masa pengembangan karier seorang pegawai Kementerian Keuangan dimulai setelah...", "options": ["Menuntaskan seluruh kewajiban masa percobaan calon pegawai", "Menyelesaikan masa branding hingga satu tahun jelang pensiun", "Lolos evaluasi rekam jejak pada asesmen nasional pertama", "Menerima surat keputusan kenaikan pangkat pembina tingkat satu", "Diberhentikan sementara dari penugasan jabatan fungsional"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Awal Mula Masa Pengembangan Karier</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Masa pengembangan karier</strong> yaitu masa <strong>setelah PNS Kemenkeu menyelesaikan masa branding sampai dengan paling kurang satu tahun menjelang batas usia pensiun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena tahapan pengembangan karier secara resmi berlangsung begitu masa branding 2 tahun selesai hingga ambang batas satu tahun sebelum masa purnatugas. Rentang waktu ini menjadi wadah aktualisasi promosi dan mutasi. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Menyelesaikan masa branding hingga satu tahun jelang pensiun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Setelah masa percobaan, pegawai baru memasuki masa branding, belum masa pengembangan karier.</li><li><strong>C</strong>: Asesmen kompetensi merupakan alat ukur, bukan tonggak pembatas tahapan karier.</li><li><strong>D</strong>: Pangkat pembina merupakan jenjang lanjutan, bukan titik awal pengembangan karier.</li><li><strong>E</strong>: Pemberhentian sementara merupakan status pembatasan, bukan awal pengembangan karier.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Masa pengembangan karier • menyelesaikan masa branding</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pegawai Kementerian Keuangan yang berada pada box IX dalam pemetaan talenta dapat...", "options": ["Memperoleh pembebasan tugas kedinasan selama dua tahun", "Mendapatkan kenaikan pangkat istimewa tanpa penilaian atasan", "Diberikan kebebasan memilih formasi jabatan di kantor pusat", "Diangkat jabatan lebih tinggi secara vertikal atau diagonal", "Menempati jabatan fungsional ahli utama secara otomatis"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Ketentuan Promosi Pegawai pada Box IX</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS Kemenkeu yang berada pada <strong>box IX dapat diangkat dalam Jabatan yang lebih tinggi dengan pola karier berbentuk vertikal, diagonal, atau semi vertikal melalui mekanisme Manajemen talenta</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pegawai pada boks IX (kualifikasi talenta tertinggi) berhak diusulkan menduduki jabatan lebih tinggi melalui jalur vertikal, diagonal, maupun semi vertikal via manajemen talenta. Ini merupakan bentuk apresiasi atas kinerja dan kompetensi optimal. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Diangkat jabatan lebih tinggi secara vertikal atau diagonal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan fasilitas boks IX karena pembebasan tugas hanya berlaku untuk cuti tertentu/studi.</li><li><strong>B</strong>: Keliru karena proses promosi tetap melalui pertimbangan tim penilai dan pimpinan.</li><li><strong>C</strong>: Pola mutasi nasional menuntut kesiapan bertugas di daerah penugasan mana pun di seluruh wilayah NKRI.</li><li><strong>E</strong>: Tidak otomatis karena jabatan ahli utama mensyaratkan formasi dan keppres tersendiri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>box IX dapat diangkat dalam • pola karier berbentuk vertikal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Seorang CPNS yang baru diangkat PNS diikutsertakan dalam pelatihan teknis bendahara. Pelatihan ini merupakan pemenuhan...", "options": ["Persyaratan pengajuan pensiun dini atas permintaan sendiri", "Kewajiban menjalani masa bebas tugas satu tahun dinas", "Program pengembangan SDM pada masa branding pegawai", "Hukuman disiplin tingkat sedang akibat kelalaian pembukuan", "Penilaian kinerja tahunan akhir batas usia kedinasan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penerapan Pembekalan Teknis Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Program pengembangan SDM pada masa branding paling kurang mencakup <strong>pembekalan terkait teknis Jabatan</strong> bagi pegawai baru.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pelatihan teknis jabatan bagi PNS baru merupakan salah satu komponen wajib masa branding untuk memastikan pegawai menguasai keterampilan operasional tugasnya. Ini membekali profesionalitas kerja sejak awal dinas. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Program pengembangan SDM pada masa branding pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sangat keliru karena pegawai baru masih di awal masa bakti, bukan pensiun dini.</li><li><strong>B</strong>: Bukan masa bebas tugas melainkan periode aktif penguatan kompetensi kerja.</li><li><strong>D</strong>: Pelatihan teknis merupakan hak pengembangan, bukan bentuk penjatuhan hukuman disiplin.</li><li><strong>E</strong>: Bukan penilaian purnatugas karena konteksnya adalah pegawai baru diangkat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pembekalan terkait teknis Jabatan • pengembangan SDM Kemenkeu paling kurang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Dalam bimbingan kerja, mentor meminta PNS baru menyusun rencana target kompetensi 3 tahun ke depan. Hal ini adalah...", "options": ["Penetapan hukuman disiplin pemotongan tunjangan kinerja", "Pemberian apresiasi purna karya atas dedikasi aparatur", "Proses seleksi terbuka calon sekretaris jenderal kementerian", "Pemeriksaan khusus atas dugaan pelanggaran kode etik", "Pembekalan penyusunan rencana pengembangan karier individu"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Implementasi Rencana Pengembangan Karier Individu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Masa branding mencakup <strong>pembekalan penyusunan rencana pengembangan karier individu</strong> agar pegawai memiliki arah kemajuan karier yang jelas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena penyusunan rencana target kompetensi merupakan implementasi langsung dari materi wajib masa branding, yaitu perencanaan karier mandiri yang terstruktur. Ini mendorong proaktivitas pegawai dalam memetakan capaian profesionalnya. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Pembekalan penyusunan rencana pengembangan karier individu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan sanksi disiplin melainkan hak pembinaan pegawai.</li><li><strong>B</strong>: Merupakan bentuk penghargaan purna dinas dan bukan kriteria penilaian talenta.</li><li><strong>C</strong>: Keliru karena seleksi JPT Madya diperuntukkan bagi pejabat senior berkualifikasi tinggi.</li><li><strong>D</strong>: Bukan proses audit investigasi etika melainkan bimbingan pengembangan kapasitas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>rencana pengembangan karier individu • Pembekalan penyusunan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Perbedaan utama perlakuan karier antara pegawai boks IX dengan boks VII dan VIII terletak pada...", "options": ["Boks IX dapat langsung dipromosikan ke jabatan lebih tinggi", "Boks VII dan VIII dibebaskan dari kewajiban kontrak kinerja", "Boks IX tidak dapat ditugaskan pada instansi di luar Kemenkeu", "Boks VII dan VIII diprioritaskan mutasi ke peringkat lebih rendah", "Boks IX diwajibkan mengulang masa percobaan calon pegawai"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pembedaan Perlakuan Talenta Boks VII, VIII, dan IX</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pegawai pada <strong>box IX dapat diangkat dalam Jabatan yang lebih tinggi</strong> (promosi), sedangkan boks VII, VIII, dan IX sama-sama diprioritaskan mutasi setara atau penugasan keluar.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena hak istimewa promosi vertikal/diagonal secara langsung melalui manajemen talenta dikhususkan bagi penghuni boks IX (kinerja dan potensial terbaik). Sementara boks VII dan VIII diprioritaskan untuk mutasi setara atau penugasan luar. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Boks IX dapat langsung dipromosikan ke jabatan lebih tinggi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Salah karena semua pegawai tanpa terkecuali wajib menyusun kontrak kinerja.</li><li><strong>C</strong>: Keliru karena boks IX justru dapat diberikan penugasan di luar Kemenkeu.</li><li><strong>D</strong>: Tidak tepat karena mutasi diarahkan ke peringkat yang setara atau lebih tinggi.</li><li><strong>E</strong>: Sangat keliru karena masa percobaan hanya dijalani satu kali saat CPNS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Masa pengembangan karier • PNS Kemenkeu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Unsur yang BUKAN merupakan materi minimal program pengembangan SDM pada masa branding adalah...", "options": ["Penguatan nilai kementerian, kode etik, dan disiplin", "Penyusunan berkas permohonan pensiun dipercepat", "Pembekalan penyusunan rencana pengembangan karier individu", "Pembekalan terkait penguasaan teknis jabatan pegawai", "Pengenalan tata nilai dan budaya kerja organisasi"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Pengecualian Muatan Masa Branding</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Muatan pengembangan masa branding hanya meliputi <strong>penguatan nilai/etika/disiplin, pembekalan teknis jabatan, dan pembekalan rencana karier individu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat sebagai pengecualian karena masa branding adalah masa awal pembinaan PNS muda (2 tahun pertama), sehingga materi pensiun dini tidak relevan dan bukan bagian dari kurikulum masa branding. Poin lainnya merupakan muatan resmi di peraturan kepegawaian. Oleh karenanya, opsi D adalah jawaban benar. Dengan demikian, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Penyusunan berkas permohonan pensiun dipercepat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan salah satu muatan wajib masa branding.</li><li><strong>C</strong>: Merupakan salah satu muatan wajib masa branding.</li><li><strong>D</strong>: Merupakan salah satu muatan wajib masa branding.</li><li><strong>E</strong>: Merupakan bagian integral dari penguatan nilai dan budaya kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Program pengembangan SDM Kemenkeu paling kurang\nmeliputi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Masa menjelang pensiun dalam tahapan pola karier pegawai dihitung sejak...", "options": ["Jaminan pemeliharaan purna jabatan bagi pejabat kementerian", "Paling kurang satu tahun sebelum batas usia pensiun", "Usia pegawai genap mencapai lima puluh tahun kalender", "Dua tahun setelah pegawai menerima kenaikan pangkat terakhir", "Enam bulan sebelum penetapan surat keputusan pensiun"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Definisi Masa Menjelang Pensiun</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Masa menjelang pensiun</strong>, yaitu <strong>masa paling kurang satu tahun sebelum batas usia pensiun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena ketentuan secara definitif menetapkan kurun waktu minimal satu tahun sebelum mencapai batas usia pensiun sebagai masa persiapan pensiun. Periode ini dimanfaatkan untuk pembekalan masa purnabakti. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Paling kurang satu tahun sebelum batas usia pensiun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan jaminan perlindungan purna dinas dan bukan instrumen manajemen talenta.</li><li><strong>C</strong>: Batas usia 50 tahun bukan patokan masa menjelang pensiun dalam pola karier.</li><li><strong>D</strong>: Tidak terkait dengan tanggal kenaikan pangkat berkala.</li><li><strong>E</strong>: Terlalu singkat dari batas minimal satu tahun yang digariskan aturan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Masa menjelang pensiun • paling kurang satu tahun sebelum\nbatas usia pensiun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Bentuk pola karier horizontal didefinisikan sebagai perpindahan jabatan ke posisi yang...", "options": ["Setara, baik dalam satu kelompok maupun antarkelompok", "Lebih tinggi di dalam satu kelompok jabatan fungsional", "Lebih tinggi antarkelompok jabatan administrasi dan JPT", "Lebih rendah akibat penerapan hukuman penurunan jabatan", "Bersifat sementara pada badan usaha milik negara"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pengertian Bentuk Pola Karier Horizontal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Bentuk <strong>horizontal</strong>, yaitu <strong>perpindahan dari satu posisi Jabatan ke posisi Jabatan lain yang setara, baik dalam satu kelompok maupun antar-kelompok JA, JF, atau JPT</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena perpindahan horizontal menekankan kesetaraan level jabatan antarunit atau antarkelompok profesi (misalnya mutasi setara antar-JA atau antar-JF). Hal ini bertujuan memperkaya pengalaman kerja aparatur tanpa mengubah eselon. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Setara, baik dalam satu kelompok maupun antarkelompok</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan definisi pola karier vertikal di dalam satu kelompok jabatan.</li><li><strong>C</strong>: Merupakan definisi pola karier diagonal antarkelompok jabatan.</li><li><strong>D</strong>: Merupakan bentuk sanksi penurunan pangkat/jabatan disiplin berat.</li><li><strong>E</strong>: Merupakan status penugasan khusus, bukan definisi pola horizontal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Horizontal, yaitu perpindahan • posisi Jabatan\nlain yang setara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Bentuk pola karier diagonal merupakan perpindahan posisi jabatan yang...", "options": ["Setara di dalam kelompok jabatan administrasi yang sama", "Lebih rendah dari kelompok jabatan pimpinan tinggi ke JF", "Bersifat rangkap jabatan pada instansi penegak hukum", "Evaluasi berkala kepatuhan operasional satker", "Penetapan formasi kebutuhan talenta strategis"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pengertian Bentuk Pola Karier Diagonal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Bentuk <strong>diagonal</strong>, yaitu <strong>perpindahan dari satu posisi Jabatan ke posisi Jabatan lain yang lebih tinggi antar-kelompok JA, JF, atau JPT</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pola diagonal mensyaratkan dua unsur sekaligus: adanya kenaikan jenjang (lebih tinggi) dan perpindahan lintas rumpun/kelompok jabatan (antar-JA, JF, atau JPT). Ini membuka jalur mobilitas meritokratis lintas fungsional. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Evaluasi berkala kepatuhan operasional satker</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pola karier horizontal dalam kelompok yang sama.</li><li><strong>B</strong>: Bukan pola diagonal karena diagonal mensyaratkan jenjang yang lebih tinggi.</li><li><strong>C</strong>: Dilarang oleh regulasi aparatur karena asas larangan rangkap jabatan.</li><li><strong>E</strong>: Bukan pola karier melainkan alih status kepegawaian yang berbeda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Diagonal, yaitu perpindahan • antar-kelompok JA, JF, atau JPT</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Tambahan bentuk pola karier yang diatur khusus dalam PMK Nomor 224/PMK.01/2020 adalah...", "options": ["Matriks berjenjang dan linear fungsional", "Sirkuler dan konsentris regional", "Semi horizontal dan semi vertikal", "Rotasi diagonal ganda dan parsial", "Tunggal berulang dan berputar periodik"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tambahan Bentuk Pola Karier di Lingkungan Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Tambahan pola karier di lingkungan Kemenkeu sebagaimana diatur dalam PMK Nomor 224/PMK.01/2020 pasal 24 yaitu <strong>semi horizontal dan semi vertikal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena regulasi kepegawaian Kementerian Keuangan memperkaya bentuk karier baku dengan mengenalkan bentuk semi horizontal dan semi vertikal guna mewadahi kebutuhan organisasi modern dan unit non-eselon. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Semi horizontal dan semi vertikal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan konsep struktur organisasi, bukan bentuk pola karier resmi.</li><li><strong>B</strong>: Bukan istilah pola karier yang diakui dalam regulasi kepegawaian Kemenkeu.</li><li><strong>D</strong>: Bukan nomenklatur pola karier Kementerian Keuangan.</li><li><strong>E</strong>: Istilah karangan yang tidak ada dalam peraturan perundang-undangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>semi horizontal dan semi\nvertikal • PMK Nomor 224/PMK.01/2020 yaitu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Rencana pengembangan karier pegawai disusun untuk jangka waktu...", "options": ["Satu tahun dengan evaluasi per semester", "Dua tahun sesuai periode masa branding", "Tiga tahun mengikuti siklus anggaran kementerian", "Sepuluh tahun mencakup proyeksi pensiun massal", "Lima tahun dengan perincian setiap tahun"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Periode Rencana Pengembangan Karier</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Rencana pengembangan karier disusun untuk jangka waktu 5 (lima) tahun dengan perincian setiap tahun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena ketentuan menegaskan rencana pengembangan karier memiliki horizon waktu menengah selama 5 tahun yang dirinci ke dalam target tahunan. Hal ini menjamin keselarasan dengan rencana strategis (Renstra) kementerian. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Lima tahun dengan perincian setiap tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Satu tahun merupakan perincian tahunannya, bukan total horizon rencana karier.</li><li><strong>B</strong>: Dua tahun merupakan durasi masa branding pegawai baru.</li><li><strong>C</strong>: Tiga tahun bukan siklus rencana pengembangan karier di Kemenkeu.</li><li><strong>D</strong>: Sepuluh tahun terlalu panjang dan tidak diatur dalam dokumen kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>jangka waktu 5 (lima) tahun • Rencana pengembangan karier disusun untuk</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Rencana pengembangan karier yang disusun unit JPT Madya wajib disampaikan kepada...", "options": ["Sekretariat Jenderal c.q. Biro Sumber Daya Manusia", "Badan Pemeriksa Keuangan perwakilan wilayah", "Kantor Pelayanan Perbendaharaan Negara setempat", "Lembaga Administrasi Negara c.q. Pusat Pelatihan", "Penataan kompetensi teknis pada unit kerja dinas"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penyampaian Rencana Pengembangan Karier</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Rencana pengembangan karier disusun oleh masing-masing unit JPT Madya dan <strong>disampaikan kepada Sekretariat Jenderal c.q. Biro Sumber Daya Manusia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Biro SDM di bawah Sekretariat Jenderal bertindak sebagai pengelola dan pengoordinasi utama manajemen SDM di lingkungan kementerian. Dokumen 5 tahunan dari setiap unit eselon I dihimpun untuk integrasi nasional kementerian. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Sekretariat Jenderal c.q. Biro Sumber Daya Manusia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: BPK merupakan auditor eksternal keuangan negara, bukan pembina SDM internal.</li><li><strong>C</strong>: KPPN adalah unit pelaksana perbendaharaan, bukan pengelola karier kementerian.</li><li><strong>D</strong>: LAN adalah instrumen pelatihan aparatur nasional, bukan penerima rencana internal Kemenkeu.</li><li><strong>E</strong>: BPKP berkedudukan sebagai pengawas intern keuangan dan bukan tim pengarah manajemen talenta Kemenkeu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>disampaikan kepada Sekretariat\nJenderal c.q. Biro Sumber Daya Manusia • unit JPT Madya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Seorang Pemeriksa Pajak Pertama dipromosikan menjadi Pemeriksa Pajak Muda. Perpindahan ini menerapkan pola karier...", "options": ["Horizontal", "Diagonal", "Vertikal", "Semi horizontal", "Sirkuler"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Implementasi Pola Karier Vertikal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Bentuk <strong>vertikal</strong>, yaitu perpindahan dari satu posisi Jabatan ke posisi Jabatan yang lain yang lebih tinggi, di dalam satu kelompok JA, JF, atau JPT.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena promosi dari jenjang Pertama ke Muda berlangsung di dalam rumpun jabatan fungsional yang sama (JF Pemeriksa Pajak) ke jenjang yang lebih tinggi. Ini secara presisi memenuhi definisi pola karier vertikal. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Vertikal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Salah karena horizontal tidak mengalami kenaikan jenjang (tetap setara).</li><li><strong>B</strong>: Bukan diagonal karena tidak berpindah antar-kelompok jabatan (tetap di rumpun JF).</li><li><strong>D</strong>: Bukan semi horizontal karena ada kenaikan jenjang struktural/fungsional.</li><li><strong>E</strong>: Sirkuler bukan bentuk pola karier yang diakui regulasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>posisi Jabatan\nyang lain yang lebih tinggi, di dalam satu kelompok • Vertikal, yaitu perpindahan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Seorang Pejabat Pengawas (JA) yang berprestasi lolos seleksi menjadi Administrator (JF Madya/Eselon III). Pola ini adalah...", "options": ["Horizontal", "Diagonal", "Semi horizontal", "Masa percobaan", "Tugas perbantuan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penerapan Pola Karier Diagonal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pola karier <strong>diagonal</strong> adalah perpindahan dari satu posisi ke posisi lain yang lebih tinggi <strong>antar-kelompok JA, JF, atau JPT</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena promosi tersebut melibatkan kenaikan level jabatan sekaligus perpindahan antarkelompok, yaitu dari kelompok Jabatan Administrasi (Pengawas) ke kelompok Jabatan Fungsional jenjang Madya yang setara Administrator. Ini adalah bentuk pola karier diagonal. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Diagonal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan horizontal karena ada kenaikan jenjang tanggung jawab yang lebih tinggi.</li><li><strong>C</strong>: Bukan semi horizontal karena terjadi promosi kenaikan kelas jabatan lintas kelompok.</li><li><strong>D</strong>: Bukan masa percobaan karena pejabat bersangkutan telah menjadi PNS definitif.</li><li><strong>E</strong>: Bukan tugas perbantuan karena merupakan mutasi/promosi jabatan definitif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>perpindahan dari satu posisi Jabatan ke posisi Jabatan\nlain yang lebih tinggi antar-kelompok • Diagonal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Berikut ini merupakan jalur karier resmi di Kementerian Keuangan, KECUALI...", "options": ["Pemberian tugas belajar pendidikan kedinasan", "Pelaksanaan penugasan magang antar-kementerian", "Pelaksanaan program pendampingan mentor dinas", "Pelatihan kepemimpinan manajerial tingkat lanjut", "Penempatan pada proyek strategis kementerian"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Jalur Karier Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Jalur karier meliputi: <strong>1) JA dan JPT; 2) JF; 3) perpindahan JA/JPT dengan JF; 4) penugasan Unit non Eselon atau instansi luar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat sebagai pengecualian karena rangkap jabatan komisioner BUMN dilarang oleh asas integritas aparatur dan bukan merupakan jalur karier yang diakui dalam regulasi kepegawaian. Keempat jalur lainnya merupakan jalur resmi pembinaan karier di Kemenkeu. Oleh karenanya, opsi D benar. Dengan demikian, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Penempatan pada proyek strategis kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan salah satu dari empat jalur karier resmi.</li><li><strong>B</strong>: Merupakan salah satu dari empat jalur karier resmi.</li><li><strong>C</strong>: Merupakan salah satu dari empat jalur karier resmi.</li><li><strong>D</strong>: Merupakan salah satu dari empat jalur karier resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Jalur karier, meliputi • Unit non Eselon</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Perbedaan pokok antara bentuk pola karier horizontal dan pola karier vertikal terletak pada aspek...", "options": ["Lokasi geografis kantor pelayanan tempat penugasan", "Hak penerimaan jaminan pemeliharaan kesehatan hari tua", "Kewajiban kepemilikan sertifikat bahasa asing pegawai", "Tingkat/ketinggian posisi jabatan yang dituju", "Masa kerja minimal kelulusan sekolah dasar kedinasan"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pembedaan Horizontal dan Vertikal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pola <strong>horizontal</strong> menuju ke posisi jabatan yang <strong>setara</strong>, sedangkan pola <strong>vertikal</strong> menuju ke posisi jabatan yang <strong>lebih tinggi</strong> dalam satu kelompok.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena faktor pembeda mendasar adalah apakah jabatan baru berada pada jenjang yang setara (horizontal) atau naik ke jenjang yang lebih tinggi (vertikal). Aspek hierarki ini menentukan hak, wewenang, dan tanggung jawab baru. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Tingkat/ketinggian posisi jabatan yang dituju</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan penentu bentuk pola karier karena mutasi horizontal maupun vertikal bisa lintas lokasi.</li><li><strong>B</strong>: Hak kesehatan melekat pada seluruh pegawai tanpa membedakan pola pergerakan karier.</li><li><strong>C</strong>: Merupakan syarat kualifikasi tertentu, bukan faktor pembeda esensial bentuk pola karier.</li><li><strong>E</strong>: Tidak membedakan konsep horizontal dan vertikal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Vertikal, yaitu perpindahan • Horizontal, yaitu perpindahan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Rencana pengembangan karier unit kerja paling kurang wajib memuat komponen...", "options": ["Nomor rekening tabungan perumahan seluruh pegawai", "Laporan pengeluaran uang persediaan bendahara", "Daftar inventaris barang milik negara rusak berat", "Jadwal penagihan piutang perpajakan daerah", "Bentuk pengembangan karier dan waktu pelaksanaan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Isi Rencana Pengembangan Karier</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Rencana pengembangan karier paling kurang memuat: PNS yang dikembangkan, <strong>rencana penempatan, bentuk pengembangan, waktu pelaksanaan, dan prosedur mekanisme pengisian Jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena dokumen perencanaan karier wajib memuat secara eksplisit bentuk pembinaan karier serta jadwal waktu eksekusinya. Kejelasan komponen ini menjamin akuntabilitas pengisian jabatan di masa depan. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Bentuk pengembangan karier dan waktu pelaksanaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan data administrasi perbankan pribadi, bukan substansi rencana karier.</li><li><strong>B</strong>: Merupakan dokumen pertanggungjawaban bendahara, bukan rencana karier.</li><li><strong>C</strong>: Merupakan domain tata kelola BMN, bukan dokumen kepegawaian.</li><li><strong>D</strong>: Merupakan rencana operasional pemeriksaan pajak, bukan perencanaan karier SDM.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS Kemenkeu yang akan dikembangkan • rencana penempatan PNS Kemenkeu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Tahap pertama dalam penyusunan rencana pengembangan karier pegawai adalah...", "options": ["Pemberian surat keputusan pensiun dini bagi pegawai", "Penetapan besaran tunjangan kemahalan wilayah terpencil", "Pelaksanaan pelantikan serentak pada unit vertikal", "Identifikasi Jabatan Target Karier dan ketersediaan kandidat", "Penjatuhan hukuman disiplin pemindahan jabatan pelaksana"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tahap Awal Rencana Pengembangan Karier</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Tahapan penyusunan rencana dimulai dari: <strong>identifikasi Jabatan Target Karier dan ketersediaan kandidat</strong>, dilanjutkan analisis kesesuaian dan penyusunan rencana.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena langkah awal yang mutlak dilakukan adalah memetakan jabatan kosong/strategis yang dituju (Target Karier) sekaligus mendata stok kandidat talenta yang tersedia di unit. Tanpa pemetaan awal ini perencanaan tidak dapat dijalankan. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Identifikasi Jabatan Target Karier dan ketersediaan kandidat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan langkah perencanaan pengembangan karier.</li><li><strong>B</strong>: Merupakan kebijakan remunerasi nasional, bukan langkah awal rencana karier.</li><li><strong>C</strong>: Pelantikan adalah tahap akhir setelah proses mutasi/promosi disetujui, bukan tahap pertama.</li><li><strong>E</strong>: Merupakan bentuk sanksi disiplin, bukan tahapan rencana karier.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>identifikasi Jabatan Target Karier dan ketersediaan kandidat • tahapan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Tujuan dilaksanakannya analisis kesesuaian kandidat dalam pengembangan karier adalah untuk...", "options": ["Menghitung kebutuhan belanja modal pengadaan kendaraan dinas", "Menetapkan besaran tarif retribusi pelayanan kepabeanan", "Memperoleh kualifikasi dan kompetensi yang sesuai jabatan", "Mengurangi hak cuti tahunan pegawai yang berprestasi", "Menyusun pembelaan hukum atas sengketa perdata kantor"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Fungsi Analisis Kesesuaian Kandidat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Analisis kesesuaian kandidat dilaksanakan untuk <strong>memperoleh kualifikasi dan kompetensi PNS Kemenkeu yang sesuai untuk menduduki Jabatan Target Karier</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena analisis kesesuaian bertujuan menyandingkan profil kompetensi, rekam jejak, dan kualifikasi calon dengan standar kompetensi jabatan yang dipersyaratkan. Hal ini menjamin prinsip the right man on the right place. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Memperoleh kualifikasi dan kompetensi yang sesuai jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan urusan pengadaan sarana dan prasarana kantor.</li><li><strong>B</strong>: Merupakan penetapan tarif penerimaan negara bukan pajak (PNBP).</li><li><strong>D</strong>: Sangat keliru karena hak cuti diatur terpisah dan tidak boleh dikurangi sepihak.</li><li><strong>E</strong>: Merupakan ranah bantuan hukum dinas, bukan analisis kesesuaian talenta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>analisis\nkesesuaian\nkandidat • kualifikasi dan kompetensi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan mutasi di Kementerian Keuangan dapat dilakukan dalam bentuk...", "options": ["Vertikal dan diagonal terbuka", "Horizontal atau semi horizontal", "Sirkuler bebas dan terpusat", "Tingkat banding dan kasasi", "Struktural berjenjang penuh"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Bentuk Pola Karier Pelaksanaan Mutasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Mutasi merupakan bentuk pola karier yang dapat dilakukan secara horizontal atau semi horizontal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena mutasi pada hakikatnya adalah pergeseran jabatan yang setara, sehingga di Kementerian Keuangan diwadahi melalui pola horizontal atau semi horizontal (misalnya mutasi ke unit non-eselon). Pola vertikal dan diagonal merupakan ranah promosi. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Horizontal atau semi horizontal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pola vertikal dan diagonal merupakan bentuk untuk promosi jabatan, bukan mutasi.</li><li><strong>C</strong>: Istilah karangan yang tidak ada dalam peraturan kepegawaian.</li><li><strong>D</strong>: Merupakan istilah proses peradilan hukum, bukan bentuk mutasi kepegawaian.</li><li><strong>E</strong>: Bukan nomenklatur resmi bentuk pola karier mutasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Mutasi merupakan bentuk pola karier yang dapat dilakukan secara • horizontal atau semi horizontal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan mutasi dapat dilakukan terhadap PNS yang telah menduduki jabatan paling singkat...", "options": ["Dua tahun sesuai kebutuhan organisasi", "Satu tahun masa dinas aktif", "Enam bulan sejak pelantikan", "Tiga tahun berturut-turut di wilayah", "Lima tahun penuh tanpa cuti kerja"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Syarat Masa Jabatan Minimal untuk Mutasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pelaksanaan mutasi dilakukan terhadap PNS yang <strong>telah menduduki Jabatan atau penugasan paling singkat 2 tahun</strong> sesuai kebutuhan organisasi dan ketentuan berlaku.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena batas masa penugasan minimal 2 tahun ditetapkan agar pegawai memiliki waktu yang memadai untuk beradaptasi, menguasai substansi pekerjaan, dan memberikan kinerja optimal sebelum dipindahkan. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Dua tahun sesuai kebutuhan organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Satu tahun bukan batas umum minimal penugasan mutasi dalam regulasi ini.</li><li><strong>C</strong>: Enam bulan terlalu singkat dan belum memberikan kematangan kinerja tugas.</li><li><strong>D</strong>: Tiga tahun bukan batas minimal mutasi baku dalam dokumen kepegawaian Kemenkeu.</li><li><strong>E</strong>: Lima tahun merupakan batas horizon perencanaan karier, bukan syarat minimal mutasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>paling singkat 2\ntahun • telah menduduki Jabatan atau penugasan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam pelaksanaan mutasi pegawai, salah satu hal yang wajib diperhatikan adalah...", "options": ["Jarak tempat tinggal pribadi ke bandara terdekat", "Ketersediaan fasilitas mobil operasional dinas", "Prinsip larangan konflik kepentingan", "Kepemilikan aset properti di unit tujuan", "Jumlah tanggungan pinjaman keuangan aparatur"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pertimbangan Larangan Konflik Kepentingan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pelaksanaan mutasi dilaksanakan dengan memperhatikan: komposisi kebutuhan, <strong>prinsip larangan konflik kepentingan</strong>, kategori jabatan, boks pemetaan, dll.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena penempatan mutasi dilarang menimbulkan benturan kepentingan pribadi, keluarga, atau relasi bisnis demi menjaga integritas dan objektivitas pelaksanaan tugas kedinasan. Ini menjadi syarat penting dalam manajemen ASN Kemenkeu. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Prinsip larangan konflik kepentingan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pertimbangan geografis mengacu pada zona kerja resmi, bukan jarak ke bandara.</li><li><strong>B</strong>: Fasilitas dinas merupakan sarana penunjang, bukan pertimbangan penempatan pegawai.</li><li><strong>D</strong>: Kepemilikan aset properti pribadi bukan dasar pertimbangan mutasi dinas.</li><li><strong>E</strong>: Kondisi utang piutang pribadi bukan kriteria formal penempatan mutasi pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>prinsip larangan konflik kepentingan • memperhatikan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Seorang staf mengajukan pindah kerja mengikuti penugasan pasangan dinasnya. Berdasarkan inisiatifnya, mutasi ini tergolong...", "options": ["Pelepasan hak pensiun masa depan", "Mutasi penugasan paksa kedinasan", "Pemberhentian sementara sebagai PNS", "Penurunan jabatan setingkat lebih rendah", "Mutasi atas permintaan sendiri"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penerapan Inisiatif Mutasi Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Mutasi di Kemenkeu dapat dilakukan berdasarkan penugasan organisasi atau <strong>permintaan sendiri</strong> (sesuai ketentuan yang berlaku).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena inisiatif permohonan mutasi yang diajukan oleh pegawai bersangkutan untuk kepentingan pribadi/keluarga secara sah diklasifikasikan sebagai mutasi atas permintaan sendiri. Mekanisme ini diakomodasi regulasi dengan syarat tertentu. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Mutasi atas permintaan sendiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hak pensiun tidak hilang selama masa kerja dan kriteria dipenuhi.</li><li><strong>B</strong>: Bukan penugasan paksa karena permohonan lahir murni dari inisiatif pegawai.</li><li><strong>C</strong>: Status PNS tetap aktif penuh dan tidak diberhentikan sementara.</li><li><strong>D</strong>: Bukan penjatuhan sanksi penurunan jabatan disiplin aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penugasan organisasi atau permintaan sendiri • Mutasi dapat dilakukan berdasarkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Seorang pelaksana baru bertugas selama 8 bulan di kantor daerah dan meminta dimutasi. Kebijakan yang tepat adalah...", "options": ["Memohon penundaan mutasi karena masa tugas belum mencapai 2 tahun", "Langsung menyetujui mutasi demi menjaga kepuasan aparatur", "Menjatuhkan sanksi disiplin berat berupa pembebasan jabatan", "Mengusulkan pemberhentian tidak dengan hormat sebagai pegawai", "Memindahkan pegawai langsung ke kantor perwakilan luar negeri"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penerapan Syarat Waktu Masa Penugasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pelaksanaan mutasi dilakukan terhadap PNS yang <strong>telah menduduki Jabatan atau penugasan paling singkat 2 tahun</strong> sesuai kebutuhan organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pegawai baru menjalani penugasan selama 8 bulan, sehingga belum mencapai syarat minimal 2 tahun masa bakti pada jabatan/unit tersebut. Permohonan mutasi harus ditolak agar proses pembinaan berjalan sesuai regulasi. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Memohon penundaan mutasi karena masa tugas belum mencapai 2 tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Menyetujui mutasi melanggar batas waktu minimal 2 tahun masa dinas.</li><li><strong>C</strong>: Mengajukan mutasi bukan pelanggaran disiplin berat yang dijatuhi pembebasan tugas.</li><li><strong>D</strong>: Sangat keliru karena pengajuan mutasi adalah hak administrasi yang dapat ditolak wajar.</li><li><strong>E</strong>: Penugasan luar negeri mensyaratkan kualifikasi senioritas dan seleksi ketat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>telah menduduki Jabatan atau penugasan paling singkat 2\ntahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Tahap penyusunan rencana pengembangan karier mencakup perencanaan atas kelompok pegawai berikut, KECUALI...", "options": ["PNS Kemenkeu yang akan dilakukan mutasi", "PNS dijadikan calon Talent kandidat promosi", "PNS kandidat untuk diberikan penugasan luar instansi", "PNS yang dijatuhi hukuman pidana penjara seumur hidup", "PNS yang disiapkan untuk suksesi jabatan target"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Muatan Penyusunan Rencana Karier</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penyusunan rencana karier mencakup: <strong>PNS yang dimutasi, calon Talent kandidat promosi, dan kandidat penugasan di luar instansi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat sebagai pengecualian karena pegawai yang dipidana penjara seumur hidup diberhentikan tidak dengan hormat dari status PNS, sehingga tidak masuk dalam perencanaan pengembangan karier. Opsi lainnya adalah sasaran resmi perencanaan talenta. Oleh karenanya, opsi D benar.</p><p>Jawaban tepat: <strong>D. PNS yang dijatuhi hukuman pidana penjara seumur hidup</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kelompok pegawai yang sah masuk perencanaan mutasi.</li><li><strong>B</strong>: Merupakan target pembinaan kelompok promosi talenta.</li><li><strong>C</strong>: Merupakan sasaran penugasan strategis di luar instansi kementerian.</li><li><strong>E</strong>: Merupakan bagian dari kandidat suksesi jabatan yang direncanakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penyusunan rencana pengembangan karier, mencakup • calon Talent</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Keterkaitan utama antara posisi dalam boks pemetaan pegawai dengan kebijakan mutasi adalah...", "options": ["Pemberian fasilitas pembebasan evaluasi kinerja berkala tahunan", "Menentukan kesiapan penempatan pada jabatan yang sesuai kompetensi", "Penyederhanaan verifikasi administrasi kelayakan promosi staf", "Memastikan pegawai boks terendah dipindahkan ke kantor pusat", "Menetapkan besaran tunjangan keluarga aparatur secara otomatis"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Keterkaitan Boks Pemetaan dengan Mutasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Posisi dalam <strong>boks pemetaan PNS Kemenkeu dan/atau nilai kompetensi dan Kinerja PNS</strong> menjadi dasar pertimbangan penempatan mutasi yang tepat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena boks pemetaan mencerminkan matriks antara potensi/kompetensi dan capaian kinerja pegawai. Hasil pemetaan ini menjadi instrumen objektif penempatan jabatan agar beban tugas selaras dengan kapasitas aparatur. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Menentukan kesiapan penempatan pada jabatan yang sesuai kompetensi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Evaluasi kinerja berkala merupakan kewajiban mutlak seluruh aparatur dalam sistem merit.</li><li><strong>C</strong>: Verifikasi dokumen integritas dan rekam jejak tetap dilaksanakan secara ketat tanpa pengecualian.</li><li><strong>D</strong>: Penempatan di kantor pusat didasarkan pada kompetensi tinggi, bukan boks terendah.</li><li><strong>E</strong>: Tunjangan keluarga diatur berdasarkan jumlah tanggungan resmi, bukan boks pemetaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>posisi dalam boks pemetaan PNS Kemenkeu • Kinerja PNS</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan promosi pegawai di Kementerian Keuangan dapat berbentuk...", "options": ["Vertikal, diagonal, atau semi vertikal", "Horizontal murni atau rotasi setara", "Semi horizontal dan penundaan pangkat", "Konsentris terpusat dan perbantuan", "Pemberhentian sementara dan demosi"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Bentuk Pola Karier Pelaksanaan Promosi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Promosi merupakan bentuk pola karier yang dapat berbentuk vertikal, diagonal, atau semi vertikal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena esensi promosi adalah kenaikan ke jenjang yang lebih tinggi, yang dapat diwujudkan secara vertikal (satu rumpun), diagonal (lintas rumpun), atau semi vertikal (ke unit non-eselon yang lebih tinggi). Bentuk horizontal merupakan mutasi. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Vertikal, diagonal, atau semi vertikal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan bentuk pola karier untuk mutasi, bukan promosi.</li><li><strong>C</strong>: Semi horizontal adalah bentuk mutasi, dan penundaan pangkat adalah sanksi.</li><li><strong>D</strong>: Bukan nomenklatur pola karier resmi di Kementerian Keuangan.</li><li><strong>E</strong>: Demosi dan pemberhentian sementara adalah status penurunan/pembatasan jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Promosi merupakan bentuk pola karier yang dapat berbentuk • vertikal,\ndiagonal, atau semi vertikal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan promosi jabatan di lingkungan kementerian dilaksanakan setelah mendapat pertimbangan...", "options": ["Dewan Pengawas Perbankan Swasta Nasional", "Asosiasi Advokat dan Konsultan Hukum Wilayah", "Tim Penilai Kinerja dan/atau Panitia Seleksi", "Lembaga Swadaya Masyarakat Bidang Anggaran", "Serikat Pekerja Sektor Perdagangan Umum"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pertimbangan Lembaga Penilai Promosi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Promosi dilaksanakan <strong>setelah mendapat pertimbangan Tim Penilai Kinerja dan/atau Panitia Seleksi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena penilaian kelayakan promosi melibatkan Tim Penilai Kinerja (d/h Baperjakat) atau Panitia Seleksi (Pansel) terbuka untuk menjamin objektivitas, meritokrasi, dan kesesuaian rekam jejak kandidat. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Tim Penilai Kinerja dan/atau Panitia Seleksi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Lembaga perbankan tidak memiliki wewenang dalam promosi ASN Kemenkeu.</li><li><strong>B</strong>: Asosiasi hukum swasta tidak berwenang dalam penilaian kinerja jabatan kementerian.</li><li><strong>D</strong>: LSM tidak memiliki otoritas dalam mekanisme promosi pegawai negeri sipil.</li><li><strong>E</strong>: Serikat pekerja swasta berada di luar sistem kepegawaian aparatur negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pertimbangan Tim Penilai Kinerja • Panitia Seleksi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan promosi pegawai diwajibkan selaras dengan ketentuan perundang-undangan mengenai...", "options": ["Kepailitan Badan Usaha", "Manajemen Talenta", "Perdagangan Komoditas Ekspor", "Penerbitan Surat Utang Swasta", "Pengelolaan Limbah Industri"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Landasan Regulasi Promosi Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Promosi dilaksanakan <strong>sesuai ketentuan peraturan perundang-undangan mengenai Manajemen Talenta</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena promosi modern di lingkungan birokrasi wajib berlandaskan sistem manajemen talenta yang berbasis meritokrasi, kualifikasi, dan rekam jejak kompetensi terukur. Hal ini mencegah praktik kolusi penempatan jabatan. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Manajemen Talenta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan domain hukum niaga swasta, bukan kepegawaian aparatur.</li><li><strong>C</strong>: Merupakan bidang perdagangan internasional, bukan manajemen SDM.</li><li><strong>D</strong>: Merupakan sektor pasar modal, bukan aturan promosi jabatan PNS.</li><li><strong>E</strong>: Merupakan regulasi lingkungan hidup, bukan tata aturan kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Manajemen Talenta • ketentuan peraturan perundang-undangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Salah satu hal yang wajib diperhatikan secara ketat dalam proses promosi jabatan adalah...", "options": ["Koleksi aset perhiasan dan logam mulia aparatur", "Tingkat kedekatan personal dengan pejabat eselon I", "Jumlah pengikut akun media sosial pribadi pegawai", "Afiliasi keanggotaan klub olahraga tertentu di daerah", "Riwayat penjatuhan sanksi kode etik dan hukuman disiplin"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pertimbangan Disiplin dalam Promosi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Promosi dilakukan dengan memperhatikan: <strong>riwayat penjatuhan sanksi kode etik dan hukuman disiplin</strong>, zona kerja, waktu pelaksanaan, dll.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena integritas moral merupakan prasyarat mutlak dalam promosi. Pegawai yang memiliki catatan pelanggaran kode etik atau hukuman disiplin aktif dapat ditunda atau dibatalkan promosinya sesuai Pasal 52 PMK 224/PMK.01/2020. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Riwayat penjatuhan sanksi kode etik dan hukuman disiplin</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aset pribadi dilaporkan dalam LHKPN, bukan kriteria promosi kepemimpinan.</li><li><strong>B</strong>: Kedekatan personal dilarang keras karena bertentangan dengan prinsip meritokrasi.</li><li><strong>C</strong>: Popularitas media sosial bukan tolak ukur kompetensi jabatan birokrasi.</li><li><strong>D</strong>: Hobi atau keanggotaan klub olahraga tidak relevan dengan promosi jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>riwayat penjatuhan sanksi kode etik dan hukuman disiplin • memperhatikan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pemberhentian dari jabatan didefinisikan sebagai kondisi di mana pegawai tidak lagi bekerja pada satuan organisasi negara, namun...", "options": ["Kehilangan seluruh hak kewarganegaraan Indonesia", "Dilarang memasuki seluruh gedung kantor kementerian", "Wajib mengembalikan seluruh gaji yang pernah diterima", "Masih berkedudukan sebagai Pegawai Negeri Sipil", "Diberikan kompensasi pesangon pensiun seketika"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Konsep Pemberhentian dari Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pemberhentian dari Jabatan menyebabkan pegawai bersangkutan tidak lagi bekerja pada satuan organisasi Negara, <strong>tetapi masih berkedudukan sebagai Pegawai Negeri Sipil</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pemberhentian dari jabatan (misalnya karena CLTN atau tugas belajar) hanya mencabut tugas jabatan tertentu, sementara status hakiki sebagai PNS tetap melekat pada diri aparatur. Status PNS baru terlepas jika terjadi pemberhentian sebagai PNS. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Masih berkedudukan sebagai Pegawai Negeri Sipil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Status kewarganegaraan tidak terpengaruh oleh pencopotan tugas jabatan.</li><li><strong>B</strong>: Bukan larangan fisik memasuki fasilitas negara.</li><li><strong>C</strong>: Gaji yang telah diterima secara sah atas pekerjaan masa lalu tidak perlu dikembalikan.</li><li><strong>E</strong>: Pemberhentian jabatan bukan pemutusan hubungan kerja yang diberi pesangon instan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemberhentian dari Jabatan, merupakan pemberhentian yang • Promosi merupakan bentuk pola karier</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Ketentuan operasional mengenai mekanisme promosi pegawai di Kemenkeu diatur secara khusus dalam...", "options": ["Pasal 1 s.d. 5 Peraturan Pemerintah Nomor 94 Tahun 2021", "Pasal 39 s.d. 43 PMK Nomor 224/PMK.01/2020", "Pasal 10 s.d. 15 PMK Nomor 200/PMK.01/2022", "Keputusan Menteri Keuangan Nomor 300/KMK.01/2022", "Peraturan Pemerintah Nomor 35 Tahun 2010"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pasal Regulasi Promosi Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Ketentuan mengenai mekanisme promosi pegawai di lingkungan Kementerian Keuangan diatur secara rinci dalam <strong>Pasal 39 s.d. 43 PMK Nomor 224/PMK.01/2020</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. PMK Nomor 224/PMK.01/2020 mengatur tata kelola manajemen karier di Kemenkeu. Pasal 39 sampai 43 secara spesifik menguraikan persyaratan, tahapan seleksi, serta pertimbangan kualifikasi bagi pegawai yang dipromosikan.</p><p>Jawaban tepat: <strong>B. Pasal 39 s.d. 43 PMK Nomor 224/PMK.01/2020</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PP 94/2021 mengatur tata tertib kewajiban dan larangan disiplin pegawai.</li><li><strong>C</strong>: Pasal 10 PMK 200/2022 mengatur pemeringkatan jabatan fungsional.</li><li><strong>D</strong>: KMK 300/2022 mengatur pedoman teknis pengelolaan manajemen kinerja.</li><li><strong>E</strong>: PP 35/2010 mengatur pedoman tanda kehormatan dan gelar kenegaraan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>ketentuan mengenai promosi di Kemenkeu • PMK Nomor 224/PMK.01/2020</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Panitia Seleksi mempertimbangkan domisili tugas dan pola rotasi wilayah kandidat. Hal ini mencerminkan pertimbangan...", "options": ["Pemberian cuti sakit jangka panjang", "Toleransi atas ketidakhadiran berkala", "Pembebasan penugasan akhir tahun", "Zona kerja dalam mekanisme promosi", "Pengurangan jam kerja harian kantor"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penerapan Kriteria Zona Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Promosi dilakukan dengan memperhatikan faktor penempatan wilayah melalui pertimbangan <strong>zona kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pertimbangan pola rotasi kewilayahan dan domisili penugasan merupakan perwujudan langsung dari kriteria zona kerja dalam proses promosi jabatan. Hal ini bertujuan menjaga pemerataan talenta di seluruh penjuru Indonesia. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Zona kerja dalam mekanisme promosi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cuti sakit diajukan atas alasan medis, bukan instrumen promosi jabatan.</li><li><strong>B</strong>: Ketidakhadiran kerja tidak ditoleransi dalam promosi jabatan struktural/fungsional.</li><li><strong>C</strong>: Beban kerja akhir tahun tetap berlaku penuh bagi seluruh pegawai yang dipromosikan.</li><li><strong>E</strong>: Jam kerja instansi pemerintah bersifat baku dan seragam nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>zona kerja • waktu pelaksanaan promosi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Kandidat promosi memiliki nilai kompetensi unggul namun sedang menjalani sanksi pelanggaran kode etik. Tindakan pansel adalah...", "options": ["Menjadikan rekam jejak sanksi sebagai bahan pertimbangan pembatalan", "Mengesampingkan rekam jejak sanksi etika berdasar nilai kompetensi", "Memberikan pemulihan status integritas sebelum masa rehabilitasi", "Memberikan kenaikan pangkat dua tingkat lebih cepat", "Menyerahkan keputusan sepenuhnya kepada pilihan pribadi kandidat"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penerapan Aspek Kepatuhan Kode Etik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Promosi wajib memperhatikan <strong>riwayat penjatuhan sanksi kode etik dan hukuman disiplin</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kepatuhan moral dan etika adalah syarat mutlak promosi. Adanya hukuman etik yang masih berlaku menjadi faktor penentu untuk menunda atau membatalkan usulan promosi kandidat demi menjaga kehormatan instansi. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Menjadikan rekam jejak sanksi sebagai bahan pertimbangan pembatalan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Rekam jejak sanksi kode etik menjadi faktor pertimbangan wajib dalam keputusan promosi pegawai.</li><li><strong>C</strong>: Pemulihan hak promosi terikat masa tunggu pembinaan sesuai bobot sanksi yang telah dijalani.</li><li><strong>D</strong>: Sangat keliru karena pegawai bermasalah dilarang memperoleh kenaikan pangkat istimewa.</li><li><strong>E</strong>: Keputusan promosi berada di ranah pejabat yang berwenang, bukan kehendak kandidat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>riwayat penjatuhan sanksi kode etik • memperhatikan hal-hal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Berdasarkan regulasi Kemenkeu, perbedaan mendasar bentuk pergerakan antara promosi dan mutasi adalah...", "options": ["Mutasi dilarang dilakukan antar-unit eselon I di lingkungan kementerian", "Promosi diprioritaskan bagi pegawai yang menduduki jabatan struktural", "Mutasi selalu melibatkan kenaikan kelas jabatan dua tingkat", "Promosi tidak memerlukan persetujuan pejabat pembina kepegawaian", "Promosi mencakup pola vertikal, sedangkan mutasi berpola horizontal"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Perbedaan Bentuk Promosi dan Mutasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Promosi</strong> mencakup pola <strong>vertikal, diagonal, atau semi vertikal</strong> (kenaikan jenjang), sedangkan <strong>mutasi</strong> berbentuk <strong>horizontal atau semi horizontal</strong> (pergeseran setara).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena secara prinsipil promosi menuntut kenaikan tingkat tanggung jawab jabatan (vertikal/diagonal), sementara mutasi berfokus pada perpindahan setara antar-posisi (horizontal). Ini adalah pembeda struktural utama antara keduanya. Oleh karenanya, opsi A adalah analisis yang tepat. Dengan demikian, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Promosi mencakup pola vertikal, sedangkan mutasi berpola horizontal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mutasi antar-unit JPT Madya (eselon I) diperbolehkan sesuai kebutuhan dinas.</li><li><strong>B</strong>: Promosi terbuka bagi seluruh rumpun jabatan manajerial maupun fungsional berbasis kinerja.</li><li><strong>C</strong>: Mutasi tidak menaikkan kelas jabatan karena pergerakannya setara (horizontal).</li><li><strong>D</strong>: Promosi justru mewajibkan persetujuan Tim Penilai Kinerja/Pansel dan PPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>vertikal,\ndiagonal, atau semi vertikal • Promosi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Dalam tata kelola promosi di Kemenkeu, hal yang BUKAN merupakan kriteria pertimbangan promosi adalah...", "options": ["Komposisi kebutuhan pegawai pada unit yang dituju", "Posisi dalam boks pemetaan PNS dan nilai kinerja", "Kepemilikan garis keturunan pejabat pimpinan tinggi", "Prinsip pencegahan benturan dan konflik kepentingan", "Riwayat penjatuhan kode etik dan hukuman disiplin"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Faktor Pertimbangan Promosi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pertimbangan promosi meliputi <strong>komposisi kebutuhan, konflik kepentingan, kategori jabatan, boks pemetaan, riwayat etik/disiplin, rencana karier individu, zona kerja, waktu pelaksanaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat sebagai pengecualian karena pertimbangan kekeluargaan/nepotisme dilarang keras dalam manajemen ASN modern berbasis meritokrasi. Keempat kriteria lainnya tercantum secara eksplisit dalam ketentuan promosi PMK 224/PMK.01/2020. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Kepemilikan garis keturunan pejabat pimpinan tinggi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kriteria resmi pertimbangan formasi promosi.</li><li><strong>B</strong>: Merupakan kriteria inti penilaian kompetensi dan kinerja promosi.</li><li><strong>D</strong>: Merupakan prinsip integritas wajib dalam penempatan promosi.</li><li><strong>E</strong>: Merupakan syarat mutlak penelusuran rekam jejak moral aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>persyaratan lain yang ditentukan oleh pimpinan unit JPT Madya • Promosi juga dilakukan dengan memperhatikan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pemberhentian dari jabatan di lingkungan birokrasi aparatur sipil negara terdiri atas...", "options": ["Pemberhentian status kepemilikan aset tabungan", "Pemberhentian hak kewarganegaraan dan domisili", "Pemberhentian penerimaan jaminan kesehatan keluarga", "Pemberhentian dari JA, JF, dan JPT", "Pemberhentian keanggotaan koperasi perumahan dinas"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Klasifikasi Pemberhentian dari Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Pemberhentian dari Jabatan terdiri atas pemberhentian dari JA, pemberhentian dari JF, dan pemberhentian dari JPT</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kelompok jabatan aparatur mencakup Jabatan Administrasi (JA), Jabatan Fungsional (JF), dan Jabatan Pimpinan Tinggi (JPT). Ketiga rumpun inilah yang menjadi objek pemberhentian dari jabatan kedinasan. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Pemberhentian dari JA, JF, dan JPT</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kepemilikan aset pribadi tidak berkaitan dengan status jabatan kedinasan.</li><li><strong>B</strong>: Kewarganegaraan dilindungi undang-undang dasar dan tidak dicabut karena urusan jabatan.</li><li><strong>C</strong>: Jaminan kesehatan merupakan hak perlindungan sosial pegawai dan keluarga.</li><li><strong>E</strong>: Keanggotaan koperasi bersifat sukarela dan bukan lingkup jabatan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemberhentian dari Jabatan terdiri atas • pemberhentian dari\nJA, pemberhentian dari JF</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "PNS diberhentikan dari jabatannya apabila menjalani tugas belajar kedinasan selama...", "options": ["Paling singkat satu bulan masa orientasi", "Tiga bulan masa pembekalan teknis", "Dua tahun berturut-turut di luar negeri", "Satu tahun penuh tanpa cuti akademik", "Lebih dari 6 (enam) bulan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kriteria Pemberhentian Tugas Belajar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS diberhentikan dari jabatan apabila: <strong>menjalani tugas belajar lebih dari 6 (enam) bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena regulasi menetapkan batas waktu tugas belajar di atas 6 bulan mewajibkan pelepasan jabatan struktural/fungsional agar pegawai fokus pada studi dan posisinya dapat diisi pejabat lain. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Lebih dari 6 (enam) bulan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Satu bulan merupakan diklat singkat (kursus), tidak menyebabkan pemberhentian jabatan.</li><li><strong>B</strong>: Tiga bulan masih tergolong pelatihan jangka pendek tanpa pelepasan jabatan.</li><li><strong>C</strong>: Dua tahun merupakan masa studi magister, tetapi pelepasan jabatan berlaku sejak >6 bulan.</li><li><strong>D</strong>: Batas minimal pelepasan jabatan adalah lebih dari 6 bulan, bukan 1 tahun.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tugas belajar lebih dari 6 (enam) bulan • PNS\ndiberhentikan dari jabatan apabila</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "PNS yang diberhentikan karena tugas belajar dapat diangkat kembali pada jenjang jabatan terakhir jika...", "options": ["Tersedia lowongan jabatan yang bersesuaian", "Mendapat persetujuan tertulis seluruh rekan kerja", "Membayar uang administrasi pengaktifan kembali", "Memiliki masa kerja dinas di atas tiga puluh tahun", "Menyerahkan sertifikat kelulusan uji balistik"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Syarat Pengangkatan Kembali ke Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS yang diberhentikan karena tugas belajar >6 bulan <strong>dapat diangkat kembali sesuai dengan jenjang JA dan JF terakhir apabila tersedia lowongan jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pengangkatan kembali aparatur pascatugas belajar atau cuti di luar tanggungan negara mutlak mensyaratkan ketersediaan lowongan formasi jabatan yang sah dalam peta jabatan organisasi. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Tersedia lowongan jabatan yang bersesuaian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Persetujuan rekan kerja bukan dasar yuridis pengangkatan kembali ke dalam jabatan.</li><li><strong>C</strong>: Pengangkatan aparatur bebas dari segala bentuk pungutan uang administrasi.</li><li><strong>D</strong>: Masa kerja 30 tahun bukan syarat pengangkatan kembali pascatugas belajar.</li><li><strong>E</strong>: Uji balistik tidak relevan dengan kualifikasi formasi jabatan sipil kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dapat diangkat\nkembali sesuai dengan jenjang JA dan JF terakhir • apabila tersedia\nlowongan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Ketentuan mutasi dan promosi terhadap PNS yang dijatuhi sanksi disiplin diatur khusus dalam...", "options": ["Pasal 25 PMK Nomor 200/PMK.01/2022", "Pasal 12 Peraturan Pemerintah Nomor 30 Tahun 2019", "Pasal 52 PMK Nomor 224/PMK.01/2020", "Keputusan Menteri Keuangan Nomor 325/KMK.01/2022", "Peraturan Pemerintah Nomor 35 Tahun 2010"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Ketentuan Mutasi dan Promosi PNS Bersanksi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Ketentuan mengenai mutasi dan promosi terhadap PNS yang mendapat sanksi kode etik atau hukuman disiplin diatur dalam <strong>Pasal 52 PMK Nomor 224/PMK.01/2020</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Pasal 52 PMK 224/PMK.01/2020 memberikan rambu-rambu hukum perlakuan karier bagi pegawai yang sedang atau pernah menjalani hukuman disiplin guna menjaga asas keadilan dan integritas birokrasi di Kementerian Keuangan.</p><p>Jawaban tepat: <strong>C. Pasal 52 PMK Nomor 224/PMK.01/2020</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pasal 25 PMK 200/2022 mengatur penetapan angka kredit jabatan fungsional.</li><li><strong>B</strong>: Pasal 12 PP 30/2019 mengatur penyusunan target rencana kerja pegawai.</li><li><strong>D</strong>: KMK 325/2022 mengatur sistem penghargaan dan apresiasi prestasi kerja.</li><li><strong>E</strong>: PP 35/2010 mengatur syarat penganugerahan gelar tanda jasa kehormatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mutasi dan promosi terhadap PNS yang mendapat sanksi kode etik • dan/atau hukuman disiplin di Kemenkeu diatur dalam pasal 52 PMK</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pemantauan dan evaluasi penempatan jabatan dilakukan setiap tahun dengan tujuan utama...", "options": ["Mempercepat pengurangan pegawai pada kantor pelayanan", "Menjamin ketepatan pengisian jabatan secara akuntabel", "Membatasi peran atasan langsung dalam proses penilaian", "Mengutamakan aspek senioritas masa kerja dalam penempatan", "Memusatkan seluruh penugasan kerja di ibu kota negara"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tujuan Pemantauan dan Evaluasi Tahunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pemantauan dan Evaluasi dilakukan setiap tahun untuk: <strong>menjamin ketepatan pengisian dan penempatan PNS dalam Jabatan secara terencana, transparan, dan akuntabel</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena evaluasi rutin tahunan berfungsi memastikan proses distribusi pegawai sesuai dengan rencana kebutuhan organisasi dan asas transparansi. Ini mencegah distorsi penempatan aparatur. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Menjamin ketepatan pengisian jabatan secara akuntabel</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan untuk mengurangi pegawai secara sewenang-wenang melainkan optimalisasi.</li><li><strong>C</strong>: Atasan langsung bertindak sebagai penilai kinerja utama dalam manajemen talenta.</li><li><strong>D</strong>: Penempatan berbasis sistem merit mendasarkan pada kompetensi objektif dan bukan senioritas murni.</li><li><strong>E</strong>: Penempatan diarahkan pada seluruh unit vertikal di tanah air sesuai kebutuhan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemantauan dan Evaluasi dilakukan setiap tahun untuk • secara terencana, transparan, dan akuntabel</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat fungsional disetujui mengambil Cuti di Luar Tanggungan Negara (CLTN). Status jabatannya adalah...", "options": ["Ditempatkan pada unit penunjang administratif sementara", "Diberhentikan dengan tidak hormat dari dinas negara", "Dipromosikan ke jabatan struktural yang lebih tinggi", "Dikenai pemotongan tunjangan kinerja sebesar 25 persen", "Diberhentikan dari jabatan namun tetap berstatus PNS"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemberhentian Jabatan karena CLTN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS yang menjalani Cuti di Luar Tanggungan Negara (CLTN) <strong>diberhentikan dari Jabatan, tetapi masih berkedudukan sebagai Pegawai Negeri Sipil</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pengambilan CLTN mewajibkan pencopotan jabatan kedinasan demi efektivitas organisasi, namun status kedudukan sebagai pegawai negeri sipil tidak hilang. Setelah masa cuti selesai, pegawai bersangkutan dapat melapor untuk diaktifkan kembali. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Diberhentikan dari jabatan namun tetap berstatus PNS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pegawai yang terdampak sanksi dialihkan tugasnya sesuai koridor regulasi disiplin yang sah.</li><li><strong>B</strong>: Bukan pemberhentian tidak hormat karena cuti diambil secara sah atas persetujuan PPK.</li><li><strong>C</strong>: Salah karena CLTN justru membekukan aktivitas jabatan dan karier kedinasan.</li><li><strong>D</strong>: Pemotongan tunjangan merupakan sanksi disiplin, bukan perlakuan bagi pemohon cuti sah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>menjalani cuti di luar tanggungan negara • PNS\ndiberhentikan dari jabatan apabila</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat eselon III secara berturut-turut tidak memenuhi target kontrak kinerja. Tindakan kepegawaian yang sah adalah...", "options": ["Pemberhentian dari jabatan sesuai ketentuan kinerja", "Pemberian kenaikan pangkat anumerta secara langsung", "Pemindahan tugas ke luar negeri dengan promosi ganda", "Penghapusan seluruh data rekam jejak kepangkatan", "Pemberian dispensasi bebas kerja selama satu tahun"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemberhentian Jabatan karena Target Kinerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS juga dapat <strong>diberhentikan dari jabatan apabila tidak memenuhi target kinerja dengan kriteria tertentu sesuai peraturan perundang-undangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kegagalan berulang dalam mencapai target kinerja yang telah diperjanjikan merupakan dasar hukum sah untuk mencopot pegawai dari jabatannya. Langkah ini merupakan penegakan akuntabilitas birokrasi berorientasi hasil. Oleh karenanya, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>A. Pemberhentian dari jabatan sesuai ketentuan kinerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pangkat anumerta hanya diberikan kepada pegawai yang tewas dalam tugas kedinasan.</li><li><strong>C</strong>: Sangat keliru karena kegagalan kinerja tidak boleh dihadiahi promosi penugasan luar negeri.</li><li><strong>D</strong>: Rekam jejak kepegawaian wajib tersimpan permanen dalam sistem informasi kepegawaian.</li><li><strong>E</strong>: Bebas kerja tanpa alasan sah melanggar disiplin aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tidak memenuhi target kinerja\ndengan kriteria tertentu • PNS juga dapat diberhentikan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Seorang auditor fungsional mengajukan surat permohonan berhenti dari tugas auditnya atas kehendak sendiri. Ini tergolong...", "options": ["Pemberhentian sementara akibat penahanan tindak pidana", "Pemberhentian tidak dengan hormat karena makar", "Hukuman disiplin berat penurunan jabatan setingkat", "Pemberhentian jabatan karena mengundurkan diri", "Pelaksanaan tugas perbantuan pada lembaga swadaya"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penerapan Alasan Pengunduran Diri Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS diberhentikan dari Jabatan apabila: <strong>a) mengundurkan diri dari Jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena permohonan berhenti dari tugas yang disampaikan secara sukarela dan resmi oleh pegawai merupakan pelaksanaan hak pengunduran diri dari jabatan. Pegawai tetap berstatus PNS dan dapat dialihkan ke posisi lain. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Pemberhentian jabatan karena mengundurkan diri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan pemberhentian sementara karena tidak ada keterlibatan dalam kasus penahanan pidana.</li><li><strong>B</strong>: Keliru karena pengunduran diri jabatan adalah hak normatif, bukan tindak pidana makar.</li><li><strong>C</strong>: Bukan penjatuhan sanksi hukuman disiplin dari atasan.</li><li><strong>E</strong>: Bukan tugas perbantuan melainkan pelepasan jabatan definitif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mengundurkan diri dari Jabatan • PNS\ndiberhentikan dari jabatan apabila</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Kondisi berikut yang BUKAN merupakan alasan sah pemberhentian PNS dari jabatannya adalah...", "options": ["Mengundurkan diri dari jabatan", "Mengikuti kegiatan keagamaan rutin akhir pekan", "Menjalani cuti di luar tanggungan negara", "Menjalani tugas belajar lebih dari 6 bulan", "Diberhentikan sementara sebagai PNS"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Alasan Pemberhentian Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Alasan pemberhentian jabatan: <strong>mengundurkan diri, diberhentikan sementara sbg PNS, CLTN, tugas belajar >6 bulan, ditugaskan penuh di luar jabatan, tidak memenuhi syarat/target kinerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat sebagai pengecualian karena menjalankan ibadah keagamaan di akhir pekan merupakan hak asasi setiap warga negara dan aparatur, sama sekali bukan dasar pemberhentian dari jabatan. Kelima poin lainnya tercantum jelas dalam regulasi kepegawaian. Oleh karenanya, opsi E benar. Dengan demikian, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Mengikuti kegiatan keagamaan rutin akhir pekan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan alasan resmi pemberhentian dari jabatan.</li><li><strong>C</strong>: Merupakan alasan resmi pemberhentian dari jabatan.</li><li><strong>D</strong>: Merupakan alasan resmi pemberhentian dari jabatan.</li><li><strong>E</strong>: Merupakan alasan resmi pemberhentian dari jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS\ndiberhentikan dari jabatan apabila • mengundurkan diri dari Jabatan;</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Perbedaan perlakuan antara PNS yang diberhentikan karena tugas belajar dengan yang tidak memenuhi syarat jabatan adalah...", "options": ["Tugas belajar wajib mengembalikan seluruh tunjangan pensiun", "Tidak memenuhi syarat langsung diangkat ke jenjang lebih tinggi", "Tugas belajar dapat diangkat kembali jika formasi tersedia", "Tidak memenuhi syarat dibebaskan dari kontrak kinerja", "Tugas belajar dilarang kembali bekerja di lingkungan instansi"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Perbedaan Hak Pengangkatan Kembali</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pemberhentian karena alasan poin (b) s.d. (e), termasuk tugas belajar >6 bulan, <strong>dapat diangkat kembali sesuai jenjang terakhir apabila tersedia lowongan jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena regulasi memberikan perlindungan hak pemulihan jabatan bagi aparatur yang diberhentikan karena penugasan dinas/studi (poin b-e) begitu studinya usai, asalkan formasi tersedia. Sebaliknya, pegawai yang tidak memenuhi syarat jabatan dicopot secara permanen dari posisi tersebut. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Tugas belajar dapat diangkat kembali jika formasi tersedia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tunjangan pensiun tidak dipengaruhi oleh penugasan tugas belajar yang sah.</li><li><strong>B</strong>: Sangat keliru karena ketidakmampuan memenuhi syarat jabatan menghalangi promosi.</li><li><strong>D</strong>: Seluruh pegawai aktif wajib memiliki target dan kontrak kinerja terukur.</li><li><strong>E</strong>: Salah karena pascatugas belajar pegawai justru kembali mengabdi di kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>poin (b) s.d. (e) di atas dapat diangkat\nkembali • apabila tersedia\nlowongan jabatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penilaian kinerja PNS dapat mempertimbangkan pendapat rekan kerja dan bawahan berdasarkan...", "options": ["Pasal 5 Peraturan Pemerintah Nomor 94 Tahun 2021", "Pasal 15 Undang-Undang Nomor 20 Tahun 2009", "Pasal 37 ayat (3) Peraturan Pemerintah Nomor 30 Tahun 2019", "Keputusan Menteri Keuangan Nomor 325/KMK.01/2022", "Peraturan Pemerintah Nomor 35 Tahun 2010"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Dasar Hukum Penilaian Mempertimbangkan Rekan & Bawahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penilaian kinerja PNS dapat mempertimbangkan pendapat rekan kerja setingkat dan bawahannya berdasarkan <strong>Pasal 37 ayat (3) Peraturan Pemerintah Nomor 30 Tahun 2019</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena PP Nomor 30 Tahun 2019 tentang Penilaian Kinerja PNS pada Pasal 37 ayat (3) secara eksplisit memberikan ruang untuk menjaring masukan dari rekan sejawat (peer) dan bawahan guna menghasilkan penilaian komprehensif. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Pasal 37 ayat (3) Peraturan Pemerintah Nomor 30 Tahun 2019</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengatur larangan-larangan disiplin aparatur sipil negara.</li><li><strong>B</strong>: Mengatur tentang gelar, tanda jasa, dan tanda kehormatan nasional.</li><li><strong>D</strong>: Mengatur tentang sistem pemberian penghargaan di Kementerian Keuangan.</li><li><strong>E</strong>: Mengatur tentang pelaksanaan penganugerahan tanda kehormatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pasal 37 ayat (3) Peraturan Pemerintah Nomor\n30 Tahun 2019 • rekan kerja\nsetingkat dan bawahannya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penerapan metode penilaian kinerja 360 derajat di Kementerian Keuangan mencakup penilaian dari...", "options": ["Pengurus partai politik dan perwakilan pemilih daerah", "Atasan, bawahan, dan rekan kerja setingkat (peer)", "Masyarakat umum pengguna jalan raya di sekitar kantor", "Nasabah perbankan komersial di luar kementerian", "Pengusaha importir dan penyedia jasa ekspedisi swasta"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Metode Penilaian 360 Derajat di Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penilaian Kinerja di Kementerian Keuangan sudah <strong>menerapkan 360 derajat (penilaian dari atasan, bawahan dan peer)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena konsep penilaian 360 derajat mengintegrasikan evaluasi multidimensi dari tiga sudut pandang interaksi kerja utama: atasan langsung, bawahan yang dipimpin, dan rekan kerja setingkat. Hal ini menghindarkan subjektivitas penilaian sepihak. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Atasan, bawahan, dan rekan kerja setingkat (peer)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Partai politik dilarang mengintervensi manajemen kepegawaian ASN yang netral.</li><li><strong>C</strong>: Masyarakat umum bukan penilai kinerja internal pegawai aparatur.</li><li><strong>D</strong>: Nasabah bank swasta tidak berwenang mengevaluasi kinerja birokrasi kementerian.</li><li><strong>E</strong>: Pelaku usaha eksternal tidak menjadi penilai 360 derajat kepegawaian internal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>menerapkan 360 derajat • atasan, bawahan dan peer</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Regulasi khusus yang mengatur manajemen kinerja di lingkungan Kementerian Keuangan adalah...", "options": ["Instruksi Presiden Nomor 7 Tahun 1999", "Peraturan Pemerintah Nomor 30 Tahun 2019", "Peraturan Menteri Keuangan Nomor 224/PMK.01/2020", "Keputusan Menteri Keuangan Nomor 200/KMK.01/2020", "Keputusan Menteri Keuangan Nomor 300/KMK.01/2022"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Regulasi Manajemen Kinerja Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kementerian Keuangan telah menetapkan <strong>Keputusan Menteri Keuangan Nomor 300/KMK.01/2022</strong> tentang Manajemen Kinerja di Lingkungan Kementerian Keuangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. KMK Nomor 300/KMK.01/2022 menjadi regulasi teknis internal yang mengatur siklus penilaian kinerja aparatur Kemenkeu secara modern. Ketentuan ini memadukan dialog kinerja, umpan balik berkelanjutan, dan pemeringkatan kuadran pegawai.</p><p>Jawaban tepat: <strong>E. Keputusan Menteri Keuangan Nomor 300/KMK.01/2022</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Inpres 7/1999 mengatur sistem akuntabilitas kinerja instansi pemerintah.</li><li><strong>B</strong>: PP 30/2019 adalah payung regulasi nasional bukan peraturan khusus internal.</li><li><strong>C</strong>: PMK 224/2020 mengatur manajemen pengembangan pola karier pegawai.</li><li><strong>D</strong>: KMK 200/2020 mengatur pedoman penataan jabatan kerja kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keputusan Menteri Keuangan Nomor 300/KMK.01/2022 • Manajemen Kinerja di Lingkungan Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Komponen penilaian kinerja pegawai di lingkungan Kementerian Keuangan mencakup...", "options": ["Jumlah kredit bank dan kepemilikan saham perusahaan", "Laba bersih operasional dan neraca rugi laba kantor", "Daftar hadir harian dan laporan kesehatan dokter dinas", "Capaian Kinerja Pegawai (CKP) dan Nilai Perilaku (NP)", "Tingkat kepuasan wajib pajak dan persentase belanja barang"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Komponen Penilaian Kinerja Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Komponen penilaian kinerja pegawai mencakup <strong>Capaian Kinerja Pegawai (CKP) yaitu capaian IKU pada Kontrak Kinerja, dan Nilai Perilaku (NP)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena evaluasi pegawai mengombinasikan aspek output pencapaian sasaran tugas (CKP) dengan standar sikap kerja moral kedinasan (NP). Kedua komponen ini kemudian dibobotkan untuk menentukan Nilai Kinerja Pegawai (NKP). Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Capaian Kinerja Pegawai (CKP) dan Nilai Perilaku (NP)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keuangan pribadi bukan komponen penilaian kinerja pegawai kementerian.</li><li><strong>B</strong>: Merupakan laporan keuangan korporasi, bukan penilaian kinerja individu ASN.</li><li><strong>C</strong>: Absensi dan kesehatan adalah data penunjang, bukan dua pilar rumus kinerja pegawai.</li><li><strong>E</strong>: Merupakan IKU tingkat unit organisasi, bukan komponen baku nilai kinerja pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Capaian Kinerja\nPegawai (CKP) • Nilai Perilaku\n(NP)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Nilai Perilaku (NP) pegawai diukur berdasarkan enam aspek penilaian yaitu...", "options": ["Orientasi pelayanan, integritas, komitmen, disiplin, kerjasama, kepemimpinan", "Kekayaan, popularitas, kecantikan, kekerabatan, senioritas, kekuatan", "Kecepatan berkendara, kepatuhan pajak, keterampilan renang, pidato, seni", "Kemandirian finansial, investasi reksadana, tabungan emas, deposito, giro", "Kelulusan bahasa, sertifikasi bela diri, keanggotaan ormas, lisensi pilot"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Enam Aspek Penilaian Nilai Perilaku</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Keenam aspek Nilai Perilaku adalah <strong>orientasi pelayanan, integritas, komitmen, disiplin, kerjasama, dan kepemimpinan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena keenam dimensi ini mencerminkan standar perilaku profesional yang dituntut dari aparatur negara untuk mendukung keberhasilan kinerjanya. Aspek ini dievaluasi secara berkala pada setiap semester. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Orientasi pelayanan, integritas, komitmen, disiplin, kerjasama, kepemimpinan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sangat keliru karena faktor fisik dan materi dilarang dalam penilaian birokrasi.</li><li><strong>C</strong>: Bukan aspek perilaku kerja yang diatur dalam regulasi kepegawaian.</li><li><strong>D</strong>: Merupakan instrumen investasi pribadi, bukan dimensi perilaku ASN.</li><li><strong>E</strong>: Keahlian di luar dinas bukan parameter utama Nilai Perilaku pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>enam aspek penilaian atas perilaku • orientasi pelayanan, integritas, komitmen, disiplin, kerjasama, dan\nkepemimpinan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penilaian perilaku pegawai untuk periode semester I dilaksanakan dalam kurun waktu...", "options": ["1 April sampai dengan 30 September", "1 Februari sampai dengan 31 Juli", "1 Maret sampai dengan 31 Agustus", "1 Januari sampai dengan 30 Juni", "1 Mei sampai dengan 31 Oktober"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Jadwal Periode Penilaian Perilaku Semester I</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Periode penilaian semester I, yaitu dari tanggal 1 Januari sampai dengan 30 Juni</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena siklus penilaian perilaku pegawai dibagi menjadi dua semester tahun kalender, di mana semester pertama mencakup aktivitas kerja dari 1 Januari hingga 30 Juni. Jadwal ini seragam di seluruh kementerian. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. 1 Januari sampai dengan 30 Juni</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kurun triwulanan ganjil, bukan batas semester I.</li><li><strong>B</strong>: Bukan pembagian semester kalender dinas yang baku.</li><li><strong>C</strong>: Jadwal meleset dari ketentuan baku 1 Januari s.d. 30 Juni.</li><li><strong>E</strong>: Bukan periode evaluasi semesteran kepegawaian kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Periode penilaian semester I, yaitu dari tanggal 1 Januari sampai dengan • Nilai Perilaku</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Seorang analis kepegawaian menghitung nilai akhir tahun pegawai dengan menggabungkan CKP dan NP. Nilai tersebut adalah...", "options": ["Nilai Kinerja Pegawai (NKP)", "Indeks Integritas Eksternal (IIE)", "Pagu Alokasi Anggaran Satker (PAAS)", "Skor Uji Kemampuan Fisik (SUKF)", "Angka Kredit Kumulatif Fungsional (AKKF)"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penerapan Pembobotan Rumus NKP</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Nilai Kinerja Pegawai (NKP)</strong> merupakan penjumlahan antara <strong>Capaian Kinerja Pegawai (CKP)</strong> dan <strong>Nilai Perilaku (NP)</strong> dengan sebelumnya dilakukan pembobotan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena gabungan tertimbang dari capaian sasaran kinerja (CKP) dan evaluasi sikap kerja (NP) secara definisi menghasilkan Nilai Kinerja Pegawai (NKP). Skor akhir inilah yang menentukan predikat kinerja tahunan aparatur. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Nilai Kinerja Pegawai (NKP)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Indeks integritas eksternal merupakan survei persepsi korupsi instansi.</li><li><strong>C</strong>: Merupakan batas anggaran belanja kantor, bukan indikator kinerja pegawai.</li><li><strong>D</strong>: Bukan instrumen evaluasi manajemen kinerja aparatur Kementerian Keuangan.</li><li><strong>E</strong>: Angka kredit merupakan akumulasi butir kegiatan khusus fungsional, bukan NKP.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penjumlahan antara CKP dan\nNP • Nilai Kinerja Pegawai (NKP)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Petugas loket perbendaharaan bersikap ramah, solutif, dan cepat menuntaskan kendala satuan kerja. Hal ini mencerminkan aspek...", "options": ["Pemberian sanksi administratif", "Penundaan mutasi berkala", "Orientasi pelayanan", "Penolakan tugas pembukuan", "Pengabaian koordinasi tim"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Implementasi Aspek Orientasi Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Aspek <strong>orientasi pelayanan</strong> menilai perilaku pegawai dalam memberikan layanan terbaik, ramah, dan solutif demi kepuasan pemangku kepentingan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kesigapan menyelesaikan kendala pengguna layanan dengan sikap santun dan tepat waktu adalah wujud nyata dari dimensi orientasi pelayanan dalam penilaian Nilai Perilaku pegawai. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Orientasi pelayanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sanksi administratif adalah hukuman, bukan sikap positif melayani.</li><li><strong>B</strong>: Penundaan mutasi bukan aspek perilaku dalam melayani satuan kerja.</li><li><strong>D</strong>: Penolakan tugas melanggar disiplin aparatur sipil negara.</li><li><strong>E</strong>: Pengabaian koordinasi bertentangan dengan prinsip nilai sinergi dan perilaku kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>orientasi pelayanan • mendukung kinerjanya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Di antara opsi berikut, elemen yang BUKAN merupakan salah satu dari enam aspek Nilai Perilaku (NP) pegawai adalah...", "options": ["Orientasi pelayanan", "Integritas kedinasan", "Kerjasama antartim", "Disiplin jam kerja", "Keahlian transaksi saham pribadi"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Aspek Nilai Perilaku Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Enam aspek perilaku pegawai mencakup <strong>orientasi pelayanan, integritas, komitmen, disiplin, kerjasama, dan kepemimpinan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat sebagai pengecualian karena keterampilan bermain saham komersial pribadi bukan bagian dari aspek evaluasi perilaku kerja aparatur negara. Keempat opsi lainnya merupakan pilar baku penilaian perilaku aparatur Kementerian Keuangan. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Keahlian transaksi saham pribadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan salah satu dari enam aspek perilaku kerja resmi.</li><li><strong>B</strong>: Merupakan salah satu dari enam aspek perilaku kerja resmi.</li><li><strong>C</strong>: Merupakan salah satu dari enam aspek perilaku kerja resmi.</li><li><strong>D</strong>: Merupakan salah satu dari enam aspek perilaku kerja resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>enam aspek penilaian atas perilaku • kepemimpinan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Keterkaitan substantif antara penilaian kinerja organisasi dan penilaian kinerja pegawai adalah...", "options": ["Kinerja organisasi dihitung murni dari absensi kehadiran fisik pegawai", "Capaian kinerja pegawai diturunkan langsung dari sasaran strategis organisasi", "Kinerja pegawai tidak memiliki korelasi dengan target kontrak instansi", "Capaian target organisasi yang rendah berdampak pada evaluasi tunjangan kinerja", "Penilaian kinerja organisasi difokuskan pada pemenuhan target program kerja"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Ruang Lingkup Penilaian Kinerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penilaian kinerja meliputi <strong>penilaian kinerja organisasi dan kinerja pegawai</strong>, di mana sasaran individu (CKP) bersumber dari IKU Kontrak Kinerja organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena sistem manajemen kinerja kementerian menerapkan metode penyelarasan (cascading), di mana capaian IKU organisasi dijabarkan menjadi target kontrak kinerja pegawai secara terpadu. Keduanya saling mengunci keberhasilan instansi. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Capaian kinerja pegawai diturunkan langsung dari sasaran strategis organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kinerja organisasi diukur dari pencapaian IKU strategis, bukan sekadar absensi.</li><li><strong>C</strong>: Kinerja individu justru terhubung langsung dengan sasaran strategis organisasi.</li><li><strong>D</strong>: Evaluasi organisasi mengukur keberhasilan program makro dan bukan memotong hak gaji pokok.</li><li><strong>E</strong>: Penilaian kinerja mencakup aspek kualitas tata kelola, bukan hanya target program kerja fisik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penilaian kinerja organisasi dan penilaian kinerja\npegawai • Penilaian kinerja meliputi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Periode penilaian perilaku semester II dilaksanakan dalam rentang waktu...", "options": ["1 Juni sampai dengan 30 November", "1 Juli sampai dengan 31 Desember", "1 Agustus sampai dengan 31 Januari", "1 September sampai dengan 28 Februari", "1 Oktober sampai dengan 31 Maret"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Jadwal Periode Penilaian Perilaku Semester II</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Periode penilaian semester II, yaitu dari tanggal 1 Juli sampai dengan 31 Desember</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pembagian masa evaluasi perilaku paruh kedua tahun kalender dinas berlangsung sejak awal Juli hingga akhir Desember. Rentang waktu ini melengkapi siklus penilaian tahunan penuh. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. 1 Juli sampai dengan 31 Desember</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jadwal tidak sesuai dengan batas penutupan tahun anggaran 31 Desember.</li><li><strong>C</strong>: Bukan batas semester resmi kalender pemerintah.</li><li><strong>D</strong>: Mencakup peralihan tahun anggaran baru secara keliru.</li><li><strong>E</strong>: Bukan kurun waktu evaluasi semester II kepegawaian kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Periode penilaian semester II, yaitu dari tanggal 1 Juli sampai dengan 31\nDesember</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Rekomendasi kenaikan dan penurunan jabatan serta peringkat dilakukan berdasarkan acuan...", "options": ["Peraturan Presiden Nomor 16 Tahun 2018", "Peraturan Pemerintah Nomor 11 Tahun 2017", "Keputusan Bersama Menteri Dalam Negeri dan Keuangan", "Undang-Undang Nomor 17 Tahun 2003", "Peraturan Menteri Keuangan Nomor 200/PMK.01/2022"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Regulasi Rekomendasi Perubahan Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Rekomendasi Kenaikan dan Penurunan Jabatan dan Peringkat dilakukan berdasarkan <strong>Peraturan Menteri Keuangan Nomor 200/PMK.01/2022</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena PMK 200/PMK.01/2022 memuat petunjuk teknis mengenai evaluasi peringkat jabatan, rekomendasi promosi kenaikan kelas, maupun penyesuaian penurunan peringkat aparatur di Kemenkeu. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Peraturan Menteri Keuangan Nomor 200/PMK.01/2022</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan Perpres Pengadaan Barang dan Jasa Pemerintah.</li><li><strong>B</strong>: Merupakan PP tentang manajemen PNS nasional secara makro.</li><li><strong>C</strong>: Bukan dasar hukum internal kenaikan peringkat jabatan di Kemenkeu.</li><li><strong>D</strong>: Merupakan UU Keuangan Negara, bukan aturan teknis peringkat jabatan pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peraturan Menteri Keuangan Nomor 200/PMK.01/2022 • Rekomendasi Kenaikan dan Penurunan Jabatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Rekomendasi kenaikan dan penurunan jabatan serta peringkat bagi pegawai diatur dalam...", "options": ["Peraturan Pemerintah Nomor 94 Tahun 2021", "Keputusan Menteri Keuangan Nomor 300 Tahun 2022", "Surat Edaran Sekretaris Jenderal Nomor 15 Tahun 2020", "Peraturan Menteri Keuangan Nomor 200/PMK.01/2022", "Instruksi Presiden Republik Indonesia Nomor 7 Tahun 1999"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Fasilitas dan Tunjangan Berdasarkan UU ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Rekomendasi Kenaikan dan Penurunan Jabatan dan Peringkat pegawai di lingkungan Kemenkeu dilakukan berdasarkan <strong>Peraturan Menteri Keuangan Nomor 200/PMK.01/2022</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Berdasarkan materi tata kelola kepegawaian, PMK Nomor 200/PMK.01/2022 menjadi landasan yuridis teknis dalam memberikan rekomendasi perubahan jabatan maupun penyesuaian peringkat bagi pegawai Kementerian Keuangan.</p><p>Jawaban tepat: <strong>D. Peraturan Menteri Keuangan Nomor 200/PMK.01/2022</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PP 94/2021 mengatur penegakan kewajiban dan hukuman disiplin PNS.</li><li><strong>B</strong>: KMK 300/2022 mengatur tata kelola instrumen penilaian kinerja pegawai.</li><li><strong>C</strong>: SE Sesjen 15/2020 mengatur petunjuk operasional jam kerja kedinasan.</li><li><strong>E</strong>: Inpres 7/1999 mengatur sistem akuntabilitas kinerja instansi pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Rekomendasi Kenaikan dan Penurunan Jabatan • Peraturan Menteri Keuangan Nomor 200/PMK.01/2022</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Regulasi yang menjadi dasar teknis pelaksanaan pemberian penghargaan tanda kehormatan adalah...", "options": ["Peraturan Pemerintah Nomor 35 Tahun 2010", "Keputusan Menteri Keuangan Nomor 325 Tahun 2022", "Peraturan Menteri Keuangan Nomor 200 Tahun 2022", "Surat Edaran Menteri PANRB Nomor 20 Tahun 2021", "Peraturan Presiden Nomor 12 Tahun 2021"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Ragam Bentuk Penghargaan Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penghargaan berupa pemberian tanda kehormatan diatur dalam <strong>Peraturan Pemerintah Nomor 35 Tahun 2010</strong> tentang Pelaksanaan UU Nomor 20 Tahun 2009 tentang Gelar, Tanda Jasa, dan Tanda Kehormatan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. regulasi menegaskan bahwa pemberian tanda kehormatan negara bagi aparatur yang berprestasi berpedoman pada PP Nomor 35 Tahun 2010. Tanda kehormatan tersebut dianugerahkan langsung oleh Presiden Republik Indonesia.</p><p>Jawaban tepat: <strong>A. Peraturan Pemerintah Nomor 35 Tahun 2010</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: KMK 325/2022 mengatur sistem penghargaan internal kementerian.</li><li><strong>C</strong>: PMK 200/2022 mengatur pedoman jabatan fungsional kementerian.</li><li><strong>D</strong>: SE MenPANRB 20/2021 mengatur core values BerAKHLAK aparatur.</li><li><strong>E</strong>: Perpres 12/2021 mengatur pengadaan barang dan jasa pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemberian tanda kehormatan • Gelar, Tanda Jasa, dan Tanda Kehormatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Tanda kehormatan sebagai penghargaan tertinggi atas darmabakti dan kesetiaan luar biasa dianugerahkan oleh...", "options": ["Menteri Keuangan Republik Indonesia", "Kepala Badan Kepegawaian Negara", "Pejabat Pembina Kepegawaian Instansi", "Sekretaris Jenderal Kementerian", "Inspektur Jenderal Kementerian"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Otoritas Pemberian Tanda Kehormatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Tanda kehormatan merupakan penghargaan negara yang diberikan Presiden</strong> kepada seseorang, kesatuan, institusi pemerintah, atau organisasi atas darmabakti dan kesetiaan yang luar biasa.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena berdasarkan UU 20/2009 dan PP 35/2010, Presiden selaku Kepala Negara memegang hak prerogatif tunggal untuk menganugerahkan tanda kehormatan negara (seperti Satyalancana Karya Satya). Oleh karenanya, opsi A adalah jawaban yang benar. Dengan demikian, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Pejabat Pembina Kepegawaian Instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Camat tidak memiliki wewenang hukum kenegaraan dalam penganugerahan bintang jasa.</li><li><strong>B</strong>: Kepala KPP hanya pejabat administrator daerah, bukan pemberi tanda kehormatan negara.</li><li><strong>D</strong>: Perhimpunan profesi bukan bagian dari unsur pejabat pembina kepegawaian instansi.</li><li><strong>E</strong>: Asosiasi swasta berada di luar ranah kehormatan kenegaraan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>diberikan\nPresiden kepada seseorang • Tanda kehormatan merupakan penghargaan negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Sistem penghargaan bagi para pegawai di lingkungan internal Kementerian Keuangan diatur melalui...", "options": ["Transparan, objektif, kompetitif, dan berkeadilan", "Sentralistik, tertutup, rahasia, dan otoritatif", "Fleksibel, mandiri, bebas dinas, dan kolegial", "Hierarkis, komando, struktural, dan birokratis", "Periodik, massal, serentak, dan administratif"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Regulasi Sistem Penghargaan Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kementerian Keuangan telah menetapkan <strong>Keputusan Menteri Keuangan Nomor 325/KMK.01/2022 tentang Sistem Penghargaan di Lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena KMK Nomor 325/KMK.01/2022 menjadi pedoman komprehensif penataan, nominasi, dan pemberian penghargaan internal (seperti Nagara Dana Rakca) bagi pegawai Kemenkeu yang berprestasi. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Transparan, objektif, kompetitif, dan berkeadilan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mengatur tentang hukuman dan tata tertib disiplin aparatur sipil negara.</li><li><strong>C</strong>: Bukan regulasi operasional sistem penghargaan internal kementerian.</li><li><strong>D</strong>: Instruksi masa lalu yang tidak memuat sistem penghargaan modern 2022.</li><li><strong>E</strong>: Surat edaran teknis anggaran sektoral, bukan pedoman penghargaan pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keputusan Menteri Keuangan\nNomor 325/KMK.01/2022 • Sistem Penghargaan di Lingkungan\nKementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Dua pegawai dengan pangkat dan golongan sama menerima tunjangan kinerja berbeda karena faktor...", "options": ["Perbedaan usia biologis dan tahun kelahiran aparatur", "Pencapaian kinerja dan realisasi kontrak kerja masing-masing", "Kegiatan pertemuan informal perhimpunan pegawai kementerian", "Jarak kilometer perjalanan harian dari rumah ke kantor", "Kepemilikan rekening tabungan pada bank BUMN tertentu"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penerapan Pembayaran Tunjangan Berbasis Kinerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Tunjangan kinerja di Kementerian Keuangan <strong>diterapkan dan dibayarkan sesuai dengan pencapaian kinerja masing-masing</strong> pegawai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena prinsip utama tunjangan kinerja adalah berbasis capaian hasil (pay for performance). Pegawai yang mencapai nilai capaian kinerja (CKP) lebih tinggi menerima hak remunerasi yang lebih optimal. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Pencapaian kinerja dan realisasi kontrak kerja masing-masing</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Usia biologis tidak menentukan besaran persentase tunjangan kinerja.</li><li><strong>C</strong>: Pertemuan informal pegawai bukan bagian dari indikator penilaian kinerja aparatur.</li><li><strong>D</strong>: Jarak tempat tinggal tidak menjadi variabel penentu rumus tunjangan kinerja.</li><li><strong>E</strong>: Nama bank mitra penyalur tidak mempengaruhi formula nominal tunjangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pencapaian kinerja masing-masing • Tunjangan di Kementerian Keuangan telah diterapkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Seorang pegawai menciptakan sistem keamanan siber yang menyelamatkan aset negara dari serangan masif. Ia berhak diusulkan...", "options": ["Pemberhentian sementara sebagai pegawai kementerian", "Penurunan kelas jabatan menjadi fungsional terampil", "Pembebasan dari seluruh kewajiban kedinasan permanen", "Pemberian dispensasi pembayaran retribusi daerah", "Kenaikan pangkat istimewa atas keahlian luar biasa"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Implementasi Kenaikan Pangkat Istimewa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penghargaan berupa <strong>kenaikan pangkat istimewa diberikan kepada PNS berdasarkan pada penilaian kinerja dan keahlian luar biasa dalam menjalankan tugas Jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena inovasi luar biasa yang melindungi aset strategis negara merupakan bukti nyata dari keahlian luar biasa yang berdampak besar bagi institusi. Hal ini memenuhi syarat penganugerahan kenaikan pangkat istimewa. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Kenaikan pangkat istimewa atas keahlian luar biasa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemberhentian sementara dijatuhkan jika tersangkut perkara pidana, bukan prestasi.</li><li><strong>B</strong>: Penurunan jabatan adalah bentuk sanksi hukuman disiplin berat.</li><li><strong>C</strong>: Pegawai berprestasi tetap menjalankan tugas pengabdian aktif.</li><li><strong>D</strong>: Pemerintah pusat tidak berwenang menghapus retribusi pemerintah daerah setempat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kenaikan pangkat istimewa • keahlian luar biasa\ndalam menjalankan tugas Jabatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Pegawai dengan nilai kinerja sangat baik serta loyalitas tinggi diprioritaskan beasiswa luar negeri. Ini merupakan perwujudan...", "options": ["Pemotongan tunjangan kinerja sebesar 25 persen", "Pelepasan tanggung jawab pengabdian bagi negara", "Penghargaan berupa kesempatan pengembangan kompetensi", "Penetapan masa percobaan bagi pegawai berprestasi", "Pemberian izin cuti di luar tanggungan negara paksa"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penerapan Hak Kesempatan Pengembangan Kompetensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penghargaan berupa <strong>kesempatan tambahan untuk pengembangan kompetensi diberikan kepada PNS yang mempunyai nilai kinerja sangat baik, berdedikasi, dan loyalitas tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena prioritas beasiswa dan pelatihan lanjutan merupakan bentuk penghargaan resmi atas dedikasi dan kinerja prima aparatur. Langkah ini mendorong kesinambungan peningkatan kapasitas SDM unggul. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Penghargaan berupa kesempatan pengembangan kompetensi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemotongan tunjangan adalah sanksi disiplin sedang, bukan penghargaan.</li><li><strong>B</strong>: Pegawai penerima beasiswa terikat kewajiban pengabdian kembali (ikatan dinas).</li><li><strong>D</strong>: Masa percobaan hanya dilalui calon pegawai, bukan pegawai berprestasi tinggi.</li><li><strong>E</strong>: CLTN merupakan hak cuti atas permohonan pribadi, bukan beasiswa kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kesempatan tambahan untuk pengembangan\nkompetensi • dedikasi, dan loyalitas yang tinggi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Regulasi khusus yang mengatur sistem penghargaan bagi pegawai di lingkungan Kementerian Keuangan adalah...", "options": ["Peraturan Menteri Keuangan Nomor 224 Tahun 2020", "Peraturan Pemerintah Nomor 94 Tahun 2021", "Keputusan Menteri Keuangan Nomor 300 Tahun 2022", "Keputusan Menteri Keuangan Nomor 325/KMK.01/2022", "Surat Edaran Sekretaris Jenderal Nomor 12 Tahun 2018"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Syarat Penghargaan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kementerian Keuangan memberikan penghargaan kepada PNS berdasarkan <strong>Keputusan Menteri Keuangan Nomor 325/KMK.01/2022</strong> tentang Sistem Penghargaan di Lingkungan Kementerian Keuangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Untuk mengapresiasi kinerja luar biasa dan dedikasi aparatur, Menteri Keuangan menetapkan KMK Nomor 325/KMK.01/2022. Regulasi ini mengatur berbagai bentuk penghargaan formal dan kesempatan pengembangan bagi pegawai berprestasi.</p><p>Jawaban tepat: <strong>D. Keputusan Menteri Keuangan Nomor 325/KMK.01/2022</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PMK 224/2020 mengatur tata pola karier mutasi dan promosi pegawai.</li><li><strong>B</strong>: PP 94/2021 mengatur sanksi dan tata tertib kedisiplinan aparatur.</li><li><strong>C</strong>: KMK 300/2022 mengatur siklus pengelolaan manajemen kinerja pegawai.</li><li><strong>E</strong>: SE Sesjen 12/2018 mengatur pedoman etika berpakaian dan perilaku dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Nomor 325/KMK.01/2022 tentang Sistem Penghargaan di Lingkungan • Kementerian Keuangan memberikan penghargaan kepada PNS atas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Salah satu kategori penghargaan resmi di lingkungan Kementerian Keuangan adalah...", "options": ["Penghargaan Upakarti Industri Kreatif", "Penghargaan Adipura Kebersihan Kota", "Penghargaan Kalpataru Kelestarian Hutan", "Penghargaan Bintang Mahaputera Utama", "Penghargaan Nagara Dana Rakca"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kategori Penghargaan Kemenkeu Nagara Dana Rakca</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penghargaan di Kementerian Keuangan terdiri atas: <strong>1) Nagara Dana Rakca; 2) Nagara Dana Ksatria; 3) Nagara Dana Abyakta; 4) Tingkat Nasional; 5) Nagara Dana Abisatya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Penghargaan Nagara Dana Rakca merupakan tanda kehormatan tertinggi di lingkungan internal Kementerian Keuangan untuk mengapresiasi kinerja dan darmabakti istimewa pegawai. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Penghargaan Nagara Dana Rakca</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penghargaan nasional di bidang industri kerajinan rakyat.</li><li><strong>B</strong>: Merupakan penghargaan bidang kebersihan lingkungan hidup kota.</li><li><strong>C</strong>: Merupakan penghargaan bagi perintis kelestarian lingkungan hidup.</li><li><strong>D</strong>: Merupakan bintang tanda kehormatan sipil kenegaraan tingkat nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Penghargaan Nagara Dana Rakca • Penghargaan terdiri atas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kategori penghargaan di Kementerian Keuangan yang diperuntukkan bagi pegawai berprestasi mencakup...", "options": ["Nagara Dana Ksatria, Abyakta, dan Abisatya", "Dharma Karya Pertambangan dan Geologi", "Lencana Teladan Gerakan Pramuka Daerah", "Satyalancana Saptamarga Angkatan Bersenjata", "Bintang Kartika Eka Paksi Militer"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Ragam Kategori Penghargaan Internal Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penghargaan internal Kemenkeu meliputi <strong>Nagara Dana Rakca, Nagara Dana Ksatria, Nagara Dana Abyakta, Tingkat Nasional, dan Nagara Dana Abisatya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena memuat ketiga sebutan resmi penganugerahan penghargaan internal Kementerian Keuangan yang didesain secara khusus untuk mengapresiasi integritas dan dedikasi aparatur pengelola keuangan negara. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Nagara Dana Ksatria, Abyakta, dan Abisatya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan penghargaan di sektor energi dan pertambangan nasional.</li><li><strong>C</strong>: Merupakan lencana organisasi kepanduan pramuka.</li><li><strong>D</strong>: Merupakan tanda jasa kehormatan jajaran militer.</li><li><strong>E</strong>: Merupakan tanda kehormatan kemiliteran Angkatan Darat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Penghargaan Nagara Dana Abyakta • Penghargaan Nagara Dana Abisatya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Di lingkungan Kementerian Keuangan, perbuatan fraud (kecurangan/korupsi) terancam dikenai sanksi...", "options": ["Peringatan ringan tanpa pencatatan", "Teguran lisan secara kekeluargaan", "Hukuman disiplin tingkat berat", "Pemberian dispensasi penugasan baru", "Kenaikan kelas jabatan dua peringkat"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Ancaman Hukuman Disiplin bagi Pelaku Fraud</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penegakan disiplin di Kemenkeu dilaksanakan sesuai PP 94/2021, di mana <strong>untuk fraud terancam dikenai hukuman disiplin tingkat berat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Kementerian Keuangan menerapkan prinsip toleransi nol (zero tolerance) terhadap tindak kecurangan dan fraud. Setiap pelanggaran integritas keuangan diancam langsung dengan hukuman berat hingga pemberhentian. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Hukuman disiplin tingkat berat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Setiap pelanggaran fraud wajib diproses investigasi dan dicatat dalam rekam jejak.</li><li><strong>B</strong>: Teguran lisan hanya untuk pelanggaran disiplin ringan, tidak berlaku bagi fraud.</li><li><strong>D</strong>: Pelaku fraud tidak diberi dispensasi melainkan penindakan tegas.</li><li><strong>E</strong>: Sangat keliru karena pelaku kejahatan keuangan wajib dihukum, bukan dipromosikan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Untuk fraud terancam\ndikenai hukuman disiplin tingkat berat • Penegakan disiplin di Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan ketentuan PP Nomor 94 Tahun 2021, aturan disiplin aparatur sipil negara berlaku juga bagi...", "options": ["Pekerja sektor swasta pergudangan internasional", "Calon Pegawai Negeri Sipil (CPNS)", "Warga negara asing yang menjadi wisatawan", "Pelaku usaha perseorangan di luar negeri", "Anggota dewan perwakilan rakyat daerah"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Keberlakuan PP Disiplin bagi Calon PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Ketentuan Peraturan Pemerintah Nomor 94 Tahun 2021 tentang Disiplin PNS <strong>berlaku juga bagi calon PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena calon aparatur (CPNS) yang telah menerima SK penugasan telah terikat secara hukum pada seluruh kewajiban dan larangan disiplin birokrasi sebagaimana halnya PNS definitif. Pelanggaran dapat menggugurkan statusnya. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Calon Pegawai Negeri Sipil (CPNS)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Karyawan swasta tunduk pada undang-undang ketenagakerjaan, bukan PP Disiplin PNS.</li><li><strong>C</strong>: Wisatawan asing tunduk pada hukum keimigrasian dan hukum pidana umum.</li><li><strong>D</strong>: Pelaku usaha mandiri swasta tidak terikat peraturan disiplin pegawai negeri.</li><li><strong>E</strong>: Anggota dewan legislatif terikat pada tata tertib lembaga parlemen.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Ketentuan Peraturan Pemerintah ini\nberlaku juga bagi calon PNS • Peraturan Pemerintah Nomor 94 Tahun 2021</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Disiplin Pegawai Negeri Sipil didefinisikan sebagai kesanggupan PNS untuk...", "options": ["Mengikuti setiap pandangan politik praktis atasan", "Mengumpulkan sumbangan dana sukarela bagi pimpinan", "Bekerja secara terus-menerus tanpa mengambil hak istirahat", "Menaati seluruh kewajiban serta standar perilaku yang ditentukan", "Memohon izin dispensasi tugas dinas ke luar wilayah kantor"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Definisi Disiplin PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Disiplin PNS adalah kesanggupan Pegawai Negeri Sipil untuk menaati kewajiban dan menghindari larangan yang ditentukan</strong> dalam peraturan perundang-undangan dan/atau peraturan kedinasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena inti konseptual disiplin ASN adalah ketaatan mutlak terhadap norma kewajiban dan kehati-hatian menjauhi larangan yang tertuang dalam regulasi kedinasan. Pelanggarannya berakibat penjatuhan hukuman disiplin. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Menaati seluruh kewajiban serta standar perilaku yang ditentukan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PNS diwajibkan netral dan dilarang terlibat dalam kegiatan politik praktis.</li><li><strong>B</strong>: Pungutan liar berkedok sumbangan dilarang oleh peraturan kedinasan.</li><li><strong>C</strong>: PNS berhak atas waktu istirahat dan cuti yang sah demi kesehatan kerja.</li><li><strong>E</strong>: Pengajuan dispensasi merupakan urusan administrasi kehadiran rutin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Disiplin Pegawai Negeri Sipil adalah kesanggupan Pegawai Negeri Sipil • menaati kewajiban dan menghindari larangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pelanggaran disiplin adalah setiap ucapan, tulisan, atau perbuatan PNS yang melanggar aturan, baik yang dilakukan...", "options": ["Di dalam lingkungan ruang pelayanan terpadu kantor", "Pada saat menghadiri agenda rapat koordinasi kedinasan", "Di dalam maupun di luar jam kerja kedinasan", "Khusus saat melaksanakan perjalanan dinas luar negeri", "Pada saat disaksikan oleh pejabat fungsional kepegawaian"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Wujud Pelanggaran Disiplin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pelanggaran disiplin adalah setiap ucapan, tulisan, atau perbuatan PNS yang melanggar ketentuan disiplin, <strong>baik yang dilakukan di dalam maupun di luar jam kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena martabat dan kode etik aparatur sipil negara melekat secara berkelanjutan pada diri pegawai selama 24 jam penuh, bukan hanya saat jam kerja di kantor. Perbuatan tercela di luar jam dinas tetap dapat dijatuhi hukuman disiplin. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Di dalam maupun di luar jam kerja kedinasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penerapan kode etik dan perilaku berlaku di dalam maupun di luar lingkungan kantor kedinasan.</li><li><strong>B</strong>: Kode etik mengikat setiap saat, tidak terbatas pada momentum rapat dinas tertentu.</li><li><strong>D</strong>: Berlaku di mana pun aparatur berada, baik di dalam maupun di luar negeri.</li><li><strong>E</strong>: Kewajiban menjaga integritas melekat secara mandiri tanpa mensyaratkan saksi pejabat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>setiap ucapan, tulisan, atau perbuatan PNS • Pelanggaran disiplin adalah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Seorang petugas bea cukai menggagalkan penyelundupan narkotika bersenjata di perbatasan dan dianugerahi penghargaan kementerian. Bentuknya adalah...", "options": ["Pemindahan tugas ke bagian gudang", "Peringatan tertulis tidak puas", "Penurunan jabatan selama setahun", "Pemberian sanksi etika sedang", "Penghargaan Nagara Dana Ksatria"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penerapan Kategori Penghargaan Nagara Dana Ksatria</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kementerian Keuangan memberikan penghargaan internal antara lain <strong>Penghargaan Nagara Dana Ksatria</strong> atas keberanian dan jasa luar biasa dalam tugas pengamanan penerimaan negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena keberanian heroik dalam menggagalkan penyelundupan berbahaya selaras dengan predikat kesatria pengawal keuangan negara (Nagara Dana Ksatria). Ini merupakan wujud apresiasi kementerian. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Penghargaan Nagara Dana Ksatria</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemindahan tugas sebagai hukuman tidak boleh diberikan kepada pahlawan penegak hukum.</li><li><strong>B</strong>: Peringatan tidak puas adalah sanksi hukuman disiplin ringan, bukan apresiasi.</li><li><strong>C</strong>: Penurunan jabatan adalah hukuman disiplin berat bagi pelaku pelanggaran.</li><li><strong>D</strong>: Sanksi etika dijatuhkan atas perbuatan tercela, bukan tindakan kepahlawanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Penghargaan Nagara Dana Ksatria • Penghargaan terdiri atas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Seorang PNS membuat unggahan di media sosial yang menghina institusi negara dan menyebarkan kebencian. Pegawai tersebut terbukti melakukan...", "options": ["Pengembangan kapasitas komunikasi digital aparatur", "Pelanggaran disiplin dalam bentuk tulisan di luar jam kerja", "Pelaksanaan tugas kehumasan kantor kementerian", "Penegakan asas transparansi dan keterbukaan informasi", "Pemberian masukan konstruktif yang dilindungi mutlak"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penerapan Larangan Ucapan/Tulisan yang Melanggar Disiplin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pelanggaran disiplin mencakup <strong>setiap ucapan, tulisan, atau perbuatan</strong> yang melanggar ketentuan disiplin, <strong>baik di dalam maupun di luar jam kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena unggahan tertulis di ruang digital yang menyebarkan kebencian memenuhi unsur pelanggaran disiplin melalui media tulisan. Meskipun dilakukan di luar jam kerja, pegawai tetap wajib menjaga kehormatan negara dan korps ASN. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Pelanggaran disiplin dalam bentuk tulisan di luar jam kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ujaran kebencian bukan bentuk pengembangan kompetensi melainkan pelanggaran hukum.</li><li><strong>C</strong>: Kehumasan pemerintah mengedepankan etika komunikasi publik yang santun dan resmi.</li><li><strong>D</strong>: Keterbukaan informasi tidak melegalkan fitnah atau ujaran kebencian publik.</li><li><strong>E</strong>: Kritik konstruktif disampaikan melalui saluran resmi internal, bukan caci maki publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>ucapan, tulisan, atau perbuatan PNS • tidak menaati kewajiban dan/atau melanggar larangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Berikut ini merupakan lima kategori penghargaan yang diatur dalam dokumen kepegawaian Kemenkeu, KECUALI...", "options": ["Penghargaan Nagara Dana Rakca", "Penghargaan Nagara Dana Ksatria", "Penghargaan Nagara Dana Abyakta", "Penghargaan Nagara Dana Mahaputra Utama", "Penghargaan Nagara Dana Abisatya"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Daftar 5 Penghargaan Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Lima penghargaan Kemenkeu: <strong>1) Nagara Dana Rakca; 2) Nagara Dana Ksatria; 3) Nagara Dana Abyakta; 4) Tingkat Nasional; 5) Nagara Dana Abisatya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat sebagai pengecualian karena Mahaputera Utama adalah nama bintang tanda kehormatan negara RI, bukan nomenklatur kategori penghargaan internal kementerian. Keempat opsi lainnya adalah nama penghargaan resmi internal Kemenkeu. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Penghargaan Nagara Dana Mahaputra Utama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kategori penghargaan internal resmi Kemenkeu.</li><li><strong>B</strong>: Merupakan kategori penghargaan internal resmi Kemenkeu.</li><li><strong>C</strong>: Merupakan kategori penghargaan internal resmi Kemenkeu.</li><li><strong>E</strong>: Merupakan kategori penghargaan internal resmi Kemenkeu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Penghargaan terdiri atas: • Penghargaan Tingkat Nasional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "PNS dijatuhi hukuman disiplin apabila tidak menaati ketentuan kewajiban dan larangan yang tercantum dalam PP 94/2021 pada...", "options": ["Pasal 3 sampai dengan Pasal 5", "Pasal 10 sampai dengan Pasal 12", "Pasal 15 sampai dengan Pasal 18", "Pasal 20 sampai dengan Pasal 24", "Pasal 30 sampai dengan Pasal 35"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Landasan Kewajiban dan Larangan Disiplin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS yang tidak menaati ketentuan sebagaimana dimaksud dalam <strong>Pasal 3 sampai dengan Pasal 5 Peraturan Pemerintah Nomor 94 Tahun 2021 dijatuhi hukuman disiplin</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Pasal 3, 4, dan 5 PP 94/2021 merupakan pasal-pasal kunci yang memuat daftar lengkap kewajiban yang harus ditaati serta larangan yang pantang dilanggar oleh aparatur. Pelanggaran atas pasal ini menjadi dasar penjatuhan sanksi. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Pasal 3 sampai dengan Pasal 5</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pasal 10-12 memuat rincian jenis tingkatan sanksi hukuman disiplin.</li><li><strong>C</strong>: Pasal 15-18 mengatur tentang pejabat yang berwenang menghukum.</li><li><strong>D</strong>: Pasal 20-24 mengatur prosedur pemanggilan dan pemeriksaan pelanggaran.</li><li><strong>E</strong>: Pasal 30-35 mengatur ketentuan penjatuhan dan penyampaian keputusan hukuman.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pasal 3 sampai dengan Pasal 5 Peraturan\nPemerintah Nomor 94 Tahun 2021 • hukuman disiplin</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Teguran lisan dari atasan BUKAN merupakan hukuman disiplin apabila...", "options": ["Disertai dengan pencabutan kartu tanda pengenal kerja", "Disampaikan di hadapan seluruh pegawai kantor", "Diberikan bersamaan dengan surat perintah tugas baru", "Tidak dinyatakan secara tegas sebagai hukuman disiplin", "Dilakukan pada awal pergantian tahun anggaran dinas"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Syarat Yuridis Teguran Lisan sebagai Hukuman Disiplin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Apabila seorang atasan menegur bawahannya, <strong>tetapi tidak dinyatakan secara tegas sebagai hukuman disiplin, bukan dinyatakan sebagai hukuman disiplin</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena teguran lisan baru memiliki kekuatan hukum sebagai sanksi disiplin resmi jika pejabat berwenang menghukum menyatakannya secara eksplisit dan tegas sebagai sanksi hukuman disiplin. Teguran nasehat kerja biasa tidak dihitung sebagai hukuman. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Tidak dinyatakan secara tegas sebagai hukuman disiplin</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pencabutan kartu identitas bukan prosedur teguran lisan.</li><li><strong>B</strong>: Lokasi teguran tidak menentukan status hukum sanksi disiplin resmi.</li><li><strong>C</strong>: Surat perintah tugas adalah penugasan terpisah.</li><li><strong>E</strong>: Waktu tahun anggaran tidak mempengaruhi syarat keabsahan sanksi teguran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Teguran Lisan • bukan\ndinyatakan sebagai hukuman disiplin</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Jenis hukuman disiplin tingkat ringan terdiri atas tiga bentuk yaitu...", "options": ["Penurunan pangkat, pencabutan hak cuti, kerja sosial", "Pemotongan tunjangan kinerja, penundaan gaji, denda kas", "Teguran lisan, teguran tertulis, pernyataan tidak puas tertulis", "Pemberhentian sementara, mutasi daerah, penurunan eselon", "Pembebasan jabatan, penahanan ijazah, penundaan pensiun"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Ragam Jenis Hukuman Disiplin Ringan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Jenis Hukuman disiplin ringan terdiri dari: <strong>(1) Teguran Lisan; (2) Teguran Tertulis; (3) Pernyataan Tidak Puas Secara Tertulis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena PP Nomor 94 Tahun 2021 secara limitatif merumuskan tiga bentuk sanksi ringan ini untuk membina aparatur atas pelanggaran berkadar ringan tanpa pemotongan hak finansial. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Teguran lisan, teguran tertulis, pernyataan tidak puas tertulis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penurunan pangkat/jabatan merupakan sanksi disiplin tingkat berat.</li><li><strong>B</strong>: Pemotongan tunjangan kinerja merupakan hukuman disiplin tingkat sedang.</li><li><strong>D</strong>: Penurunan eselon dan mutasi sanksi masuk kategori hukuman berat.</li><li><strong>E</strong>: Pembebasan jabatan merupakan jenis hukuman disiplin tingkat berat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Teguran Lisan • Teguran Tertulis • Pernyataan Tidak Puas Secara Tertulis</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan PP 94/2021, bentuk hukuman disiplin tingkat sedang adalah pemotongan tunjangan kinerja sebesar...", "options": ["10% selama 3, 6, atau 12 bulan", "25% selama 6, 9, atau 12 bulan", "50% selama 1, 2, atau 3 bulan", "75% selama 1, 3, atau 6 bulan", "100% penuh selama 1 tahun anggaran"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Bentuk Sanksi Hukuman Disiplin Sedang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Hukuman disiplin sedang berupa <strong>pemotongan tunjangan kinerja sebesar 25% (dua puluh lima persen) selama 6 bulan, 9 bulan, atau 12 bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena dalam rezim PP Nomor 94 Tahun 2021, seluruh hukuman sedang dikonversi ke dalam pemotongan tunjangan kinerja dengan persentase tunggal 25% yang dibedakan durasinya (6, 9, atau 12 bulan). Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. 25% selama 6, 9, atau 12 bulan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Besaran persentase salah, yang benar adalah 25%.</li><li><strong>C</strong>: Persentase 50% bukan formula hukuman disiplin sedang PP 94/2021.</li><li><strong>D</strong>: Persentase 75% tidak ada dalam klasifikasi sanksi disiplin PP 94/2021.</li><li><strong>E</strong>: Pemotongan 100% bukan bentuk hukuman disiplin sedang aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemotongan tunjangan kinerja sebesar 25% • Jenis hukuman disiplin sedang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Hukuman disiplin berat berupa penurunan jabatan setingkat lebih rendah dijatuhkan untuk jangka waktu...", "options": ["6 (enam) bulan kalender kerja", "9 (sembilan) bulan kalender dinas", "36 (tiga puluh enam) bulan dinas", "24 (dua puluh empat) bulan penuh", "12 (dua belas) bulan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Durasi Penurunan Jabatan Disiplin Berat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Hukuman disiplin berat mencakup <strong>penurunan jabatan setingkat lebih rendah selama 12 (dua belas) bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena jangka waktu penurunan jabatan setingkat lebih rendah dipatok tepat 12 bulan (1 tahun) sebelum pegawai bersangkutan dapat dievaluasi kembali kelayakannya untuk promosi. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. 12 (dua belas) bulan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Enam bulan merupakan durasi minimal pemotongan tunjangan pada sanksi sedang.</li><li><strong>B</strong>: Sembilan bulan merupakan durasi pertengahan sanksi disiplin sedang.</li><li><strong>C</strong>: Tiga puluh enam bulan merupakan durasi sanksi masa lalu yang sudah tidak berlaku.</li><li><strong>D</strong>: Dua puluh empat bulan terlalu lama dan bertentangan dengan ketentuan PP 94/2021.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penurunan jabatan setingkat lebih rendah selama 12 (dua\nbelas) bulan • Jenis hukuman disiplin berat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Hukuman disiplin berat pembebasan dari jabatannya menjadi jabatan pelaksana diberlakukan selama...", "options": ["12 (dua belas) bulan", "6 (enam) bulan", "3 (tiga) bulan", "18 (delapan belas) bulan", "Permanen seumur hidup"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Hukuman Pembebasan Jabatan Menjadi Pelaksana</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Hukuman berat kedua adalah <strong>pembebasan dari jabatannya menjadi jabatan pelaksana selama 12 (dua belas) bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pejabat struktural/fungsional yang melakukan pelanggaran berat dapat dicopot dari jabatannya dan dialihkan menjadi staf pelaksana selama durasi 12 bulan sebagai bentuk pembinaan keras. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. 12 (dua belas) bulan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Enam bulan merupakan opsi durasi pemotongan tunjangan hukuman sedang.</li><li><strong>C</strong>: Tiga bulan bukan durasi resmi penjatuhan hukuman disiplin berat.</li><li><strong>D</strong>: Delapan belas bulan tidak dikenal dalam nomenklatur PP 94/2021.</li><li><strong>E</strong>: Bukan permanen seumur hidup karena statusnya dievaluasi setelah 12 bulan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pembebasan dari jabatannya menjadi jabatan pelaksana\nselama 12 (dua belas) bulan • berat terdiri dari:</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Puncak jenis hukuman disiplin tingkat berat yang tercantum pada PP Nomor 94 Tahun 2021 adalah...", "options": ["Kewajiban kerja paksa di proyek infrastruktur perbatasan", "Penahanan fisik di rumah tahanan militer kementerian", "Pemberian hak keistimewaan penugasan tanpa uji kompetensi", "Pencabutan status kewarganegaraan Republik Indonesia", "Pemberhentian dengan hormat tidak atas permintaan sendiri sebagai PNS"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Hukuman Pemberhentian Disiplin Terberat PP 94/2021</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Jenis hukuman disiplin berat ketiga adalah <strong>pemberhentian dengan hormat tidak atas permintaan sendiri sebagai PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pemberhentian dari korps PNS merupakan sanksi administratif tertinggi dalam ranah hukum disiplin ASN bagi aparatur yang melakukan pelanggaran berat tanpa toleransi. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Pemberhentian dengan hormat tidak atas permintaan sendiri sebagai PNS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kerja paksa bertentangan dengan hak asasi manusia dan undang-undang ketenagakerjaan.</li><li><strong>B</strong>: Penahanan fisik ranah hukum pidana peradilan umum, bukan sanksi disiplin ASN.</li><li><strong>C</strong>: Penugasan aparatur wajib melalui uji kualifikasi dan bukan hak keistimewaan otomatis.</li><li><strong>D</strong>: Status kewarganegaraan tidak dapat dicabut melalui sanksi disiplin kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemberhentian dengan hormat tidak atas permintaan sendiri\nsebagai PNS • Jenis hukuman disiplin berat terdiri dari:</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Pegawai melalaikan tenggat penyusunan laporan berulang kali dan dijatuhi surat pernyataan tidak puas oleh atasan berwenang. Ini adalah...", "options": ["Sanksi pidana kurungan", "Hukuman disiplin sedang", "Hukuman disiplin berat", "Hukuman disiplin ringan", "Tindakan ganti rugi perdata"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penerapan Hukuman Pernyataan Tidak Puas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Pernyataan Tidak Puas Secara Tertulis</strong> merupakan salah satu bentuk dari <strong>jenis hukuman disiplin ringan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pernyataan tidak puas tertulis berada pada hierarki tertinggi dalam kategori sanksi ringan (setelah teguran lisan dan teguran tertulis). Surat resmi ini dicatat dalam berkas kepegawaian aparatur. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Hukuman disiplin ringan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pidana kurungan dijatuhkan oleh putusan vonis hakim pengadilan pidana.</li><li><strong>B</strong>: Hukuman disiplin sedang selalu melibatkan pemotongan tunjangan kinerja 25%.</li><li><strong>C</strong>: Hukuman berat melibatkan penurunan jabatan, pembebasan jabatan, atau pemecatan.</li><li><strong>E</strong>: Ganti rugi perdata terkait pengembalian kerugian materi keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pernyataan Tidak Puas Secara Tertulis • disampaikan secara tertulis</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Seorang staf satker terbukti melanggar jam kerja secara kumulatif dan dijatuhi sanksi disiplin sedang minimal. Sanksinya adalah...", "options": ["Pemotongan tukin 50% selama 3 bulan", "Pemotongan tukin 25% selama 6 bulan", "Penurunan jabatan setingkat lebih rendah 12 bulan", "Pemberhentian pembayaran gaji pokok selama 1 tahun", "Teguran lisan dari atasan langsung"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Implementasi Sanksi Disiplin Sedang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Hukuman disiplin sedang tingkat pertama adalah <strong>pemotongan tunjangan kinerja sebesar 25% selama 6 (enam) bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena durasi 6 bulan dengan besaran pemotongan tukin 25% merupakan anak tangga pertama dalam kategori hukuman disiplin tingkat sedang berdasarkan PP 94/2021. Hal ini tepat diterapkan pada kasus tersebut. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Pemotongan tukin 25% selama 6 bulan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Formula pemotongan 50% tidak ada dalam klasifikasi sanksi PP 94/2021.</li><li><strong>C</strong>: Penurunan jabatan merupakan sanksi hukuman disiplin tingkat berat.</li><li><strong>D</strong>: Gaji pokok tidak dipotong sebagai sanksi hukuman disiplin sedang.</li><li><strong>E</strong>: Teguran lisan merupakan jenis hukuman disiplin ringan, bukan sedang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemotongan tunjangan kinerja sebesar 25% (dua puluh lima\npersen) selama 6 (enam) bulan • sedang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Perbedaan pokok antara hukuman penurunan jabatan dengan pembebasan jabatan menjadi pelaksana terletak pada...", "options": ["Penurunan jabatan turun satu tingkat, pembebasan langsung ke pelaksana", "Penurunan jabatan berdurasi tiga tahun, pembebasan berlaku sementara", "Penempatan tugas aparatur pada unit perwakilan kementerian di luar negeri", "Pembebasan jabatan diberikan kepada CPNS, penurunan kepada pensiunan", "Penurunan jabatan wewenang bupati, pembebasan wewenang kepala desa"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pembedaan Bentuk Hukuman Disiplin Berat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Penurunan jabatan</strong> setingkat lebih rendah hanya menurunkan satu level, sedangkan <strong>pembebasan jabatan</strong> mencopot pejabat struktural/fungsional langsung menjadi <strong>jabatan pelaksana</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pejabat eselon III yang dijatuhi penurunan jabatan hanya turun ke eselon IV. Namun jika dijatuhi pembebasan jabatan, ia langsung dilepas ke posisi jabatan pelaksana (staf biasa). Keduanya sama-sama berdurasi 12 bulan. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Penurunan jabatan turun satu tingkat, pembebasan langsung ke pelaksana</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Jangka waktu hukuman penurunan jabatan setingkat lebih rendah ditetapkan selama 12 bulan.</li><li><strong>C</strong>: Perbedaan mendasar terletak pada perubahan eselon atau status jabatan yang diemban pegawai.</li><li><strong>D</strong>: CPNS dan pensiunan tidak menduduki jabatan struktural kepemimpinan.</li><li><strong>E</strong>: Kewenangan penjatuhan hukuman di Kemenkeu berada pada pejabat pembina kepegawaian pusat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penurunan jabatan setingkat lebih rendah • pembebasan dari jabatannya menjadi jabatan pelaksana</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 04", "difficulty": "C4 - Analisis", "question": "Berdasarkan PP 94/2021, sanksi yang BUKAN merupakan klasifikasi hukuman disiplin sedang adalah...", "options": ["Pemotongan tunjangan kinerja 25% selama 6 bulan", "Pemotongan tunjangan kinerja 25% selama 9 bulan", "Penurunan pangkat setingkat lebih rendah selama 3 tahun", "Pemotongan tunjangan kinerja 25% selama 12 bulan", "Pemotongan tunjangan prestasi kerja satu semester"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Analisis Pengelompokan Hierarki Hukuman Disiplin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Hukuman disiplin sedang hanya terdiri atas <strong>pemotongan tunjangan kinerja 25% selama 6 bulan, 9 bulan, atau 12 bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat sebagai pengecualian karena 'penurunan pangkat setingkat lebih rendah selama 3 tahun' merupakan jenis hukuman disiplin berat di bawah aturan lama (PP 53/2010) yang sudah dihapus dan digantikan dalam PP 94/2021. pilihan C bukan lagi bagian dari skema hukuman disiplin saat ini. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Penurunan pangkat setingkat lebih rendah selama 3 tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan jenis resmi hukuman disiplin tingkat sedang PP 94/2021.</li><li><strong>B</strong>: Merupakan jenis resmi hukuman disiplin tingkat sedang PP 94/2021.</li><li><strong>D</strong>: Merupakan jenis resmi hukuman disiplin tingkat sedang PP 94/2021.</li><li><strong>E</strong>: Pemotongan satu semester (6 bulan) merupakan implementasi dari hukuman sedang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tingkat dan jenis hukuman disiplin • ringan • sedang • berat</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_tak_p04_review_annot_v1_user_<?= (int)$USER->id ?>';
let reviewAnnoStore={};
// Cadangan lokal HANYA untuk anotasi yang belum dikonfirmasi tersimpan di Moodle.
// Kunci mencakup identitas akun + paket soal sehingga tidak tercampur dengan ujian lain.
const RA_DRAFT_KEY=STG_LOCAL_KEY+'_anno_pending_v1';
let raLocalDrafts={};
try {
 const drafts=JSON.parse(localStorage.getItem(RA_DRAFT_KEY)||'{}');
 if(drafts && typeof drafts==='object' && !Array.isArray(drafts)){
  Object.keys(drafts).forEach(k=>{
   if(/^(0|[1-9][0-9]?)$/.test(k) && Array.isArray(drafts[k]))raLocalDrafts[k]=drafts[k];
  });
 }
}catch(e){console.warn('Cadangan anotasi tidak dapat dibaca',e);}
function raPersistDrafts(){
 try {
  if(Object.keys(raLocalDrafts).length){localStorage.setItem(RA_DRAFT_KEY,JSON.stringify(raLocalDrafts));}
  else{localStorage.removeItem(RA_DRAFT_KEY);}
 }catch(e){console.warn('Cadangan lokal anotasi tidak dapat ditulis',e);}
}
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
   // Jangan menimpa coretan yang sudah dibuat, tetapi masih dalam antrean kirim.
   Object.keys(raLocalDrafts).forEach(k=>{
    if(Array.isArray(raLocalDrafts[k]))reviewAnnoStore[k]=raLocalDrafts[k];
   });
   raServerReady=true;
   raRestoreAnnotations();
   // Sinkronkan ulang cadangan yang tertinggal akibat koneksi putus/halaman ditutup.
   Object.keys(raLocalDrafts).forEach(k=>{void raSendSave(Number(k));});
 }catch(e){
   console.error('Anotasi server:',e);
   // Cadangan tetap bisa terlihat walau server sementara tidak dapat diakses.
   Object.keys(raLocalDrafts).forEach(k=>{reviewAnnoStore[k]=raLocalDrafts[k];});
   raRestoreAnnotations();
   alert('Anotasi server belum dapat dimuat. Coretan yang belum tersinkron tetap tersimpan sementara pada browser ini.');
 }
}
// Satu antrean request per soal agar respons lambat tidak menimpa coretan terbaru.
function raSendSave(i){
 const k=String(i);
 if(!raServerReady||!Object.prototype.hasOwnProperty.call(raLocalDrafts,k))return Promise.resolve(false);
 if(raSavePending[k])return raSavePending[k];
 const task=(async()=>{
  while(raServerReady&&Object.prototype.hasOwnProperty.call(raLocalDrafts,k)){
   const snapshot=JSON.stringify(raLocalDrafts[k]);
   const b=new URLSearchParams();b.set('action','anno_save');b.set('sesskey',MOODLE_SESSKEY);
   b.set('question',k);b.set('strokesjson',snapshot);
   const r=await fetch(LEADERBOARD_ENDPOINT,{
    method:'POST',credentials:'same-origin',cache:'no-store',
    headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
    // Bantu menuntaskan request kecil bila peserta langsung menutup halaman.
    keepalive:b.toString().length<48000,
    body:b.toString()
   });
   const d=await r.json();
   if(!r.ok||!d.success)throw new Error(d.message||'Gagal menyimpan anotasi');
   // Hapus cadangan hanya jika TIDAK ada perubahan baru saat request berlangsung.
   if(JSON.stringify(raLocalDrafts[k])===snapshot){
    delete raLocalDrafts[k];
    raPersistDrafts();
   }
  }
  return true;
 })();
 raSavePending[k]=task;
 task.then(()=>{},e=>{
  console.error('Gagal menyimpan anotasi nomor',i+1,e);
  alert('Coretan nomor '+(i+1)+' belum tersinkron ke Moodle. Cadangan tersimpan di browser ini dan akan dikirim ulang saat review dibuka.');
 }).then(()=>{if(raSavePending[k]===task)delete raSavePending[k];});
 return task;
}
function raSave(i){
 const k=String(i);
 // Simpan cadangan secara sinkron SEBELUM browser bisa berpindah/ditutup.
 raLocalDrafts[k]=JSON.parse(JSON.stringify(raStrokes(i)));
 raPersistDrafts();
 // Coretan, undo, dan hapus langsung disinkronkan tanpa menunggu timer 350 ms.
 if(raServerReady)void raSendSave(i);
}

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
 try{localStorage.setItem('stg_tak_p04_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_tak_p04_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Tata Aturan Kepegawaian Paket 04";
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
  const ok=confirm('🔄 Retake Quiz?\n\nSeluruh anotasi pada pembahasan tetap tersimpan di akun Stangolden dan tidak akan dihapus saat mengulang ujian. Jawaban terakhir tetap tersimpan sampai percobaan baru dikumpulkan.\n\nLanjutkan ulangi ujian?');
  if(!ok)return;
  Object.keys(raSaveTimers).forEach(k=>{clearTimeout(raSaveTimers[k]);delete raSaveTimers[k];});
  // Pastikan coretan yang belum sinkron dikirim sebelum memulai ulang.
  // Jika koneksi gagal, cadangan lokal tetap disimpan dan disinkronkan kembali saat review dibuka.
  if(raServerReady){
    await Promise.allSettled(Object.keys(raLocalDrafts).map(k=>raSendSave(Number(k))));
  }
  await Promise.allSettled(Object.keys(raSavePending).map(k=>raSavePending[k]));
  // Retake hanya mengulang tes. TIDAK menghapus tabel anotasi, coretan server, atau cadangan lokal.
  window.location.reload();
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
<?php if (is_siteadmin($USER)): ?>
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
  backdrop-filter: blur(3px);
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
  max-width: 840px;
  max-height: 92vh;
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
  gap: 8px;
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
          <label style="font-weight:800;font-size:14px;">Pilih Soal:</label>
          <select id="stgAdminQSelect" onchange="stgLoadAdminQ(parseInt(this.value, 10))"></select>
        </div>
        <button type="button" onclick="stgNavAdminQ(1)">Selanjutnya ▶</button>
      </div>

      <div class="stg-form-group">
        <label>Pertanyaan (Pokok Soal):</label>
        <textarea id="stgAdminQText" class="stg-form-control" rows="3" placeholder="Tuliskan pertanyaan soal..."></textarea>
      </div>

      <div class="stg-form-group">
        <label>Pilihan Jawaban & Kunci (Pilih radio untuk menetapkan kunci):</label>
        <?php foreach (['A', 'B', 'C', 'D', 'E'] as $idx => $ltr): ?>
        <div class="stg-opt-row">
          <label class="stg-opt-radio-label">
            <input type="radio" name="stgAdminKunciRadio" value="<?= $idx ?>">
            <span>Opsi <?= $ltr ?></span>
          </label>
          <input type="text" id="stgAdminOpt<?= $idx ?>" class="stg-form-control" placeholder="Teks opsi <?= $ltr ?>...">
        </div>
        <?php endforeach; ?>
      </div>

      <div class="stg-form-group">
        <label>Pembahasan Lengkap (Format HTML Kartu Belajar):</label>
        <textarea id="stgAdminExplanation" class="stg-form-control" rows="8" style="font-family:monospace;font-size:12px;line-height:1.4;" placeholder="HTML explanation..."></textarea>
        <small style="color:#665E7C;font-size:11px;margin-top:4px;display:block;">
          💡 Format pembahasan menggunakan HTML terstruktur (Intisari Materi, Alasan AREL, dan Bedah Opsi Lainnya).
        </small>
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
  document.getElementById("stgAdminExplanation").value = q.explanation || "";

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
  for (let i = 0; i < 5; i++) {
    const v = document.getElementById(`stgAdminOpt${i}`).value.trim();
    if (!v) {
      alertBox.textContent = `Pilihan opsi ${letters[i] || (i+1)} tidak boleh kosong.`;
      alertBox.className = "stg-admin-alert error";
      alertBox.style.display = "block";
      return;
    }
    opts.push(v);
  }

  let answerKey = 0;
  const radios = document.getElementsByName("stgAdminKunciRadio");
  for (let r of radios) {
    if (r.checked) {
      answerKey = parseInt(r.value, 10);
      break;
    }
  }

  const expl = document.getElementById("stgAdminExplanation").value.trim();

  const payload = {
    index: stgAdminCurIdx,
    question: qText,
    options: opts,
    answer: answerKey,
    explanation: expl
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
      if (expl) questions[stgAdminCurIdx].explanation = expl;

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
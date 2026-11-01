<?php
/**
 * STANGOLDEN.exam • Wawasan Kebangsaan Paket 04 (Halaman 36–45)
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
// $DB->get_record("stgwk_p04_rank") => tabel fisik "mdlhv_stgwk_p04_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgwk_p04_rank';
const STG_REVIEW_T = 'stgwk_p04_rev';
const STG_ANNO_T = 'stgwk_p04_anno';
const STG_KEY = [0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 0, 2, 4, 1, 3, 0, 2, 4, 1, 3, 1, 3, 0, 2, 4, 1, 3, 0, 2, 4, 2, 4, 1, 3, 0, 2, 4, 1, 3, 0, 3, 0, 2, 4, 1, 3, 0, 2, 4, 1, 4, 1, 3, 0, 2, 4, 1, 3, 0, 2];
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
            error_log('STANGOLDEN WK P04 HLM36-45 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN WK P04 HLM36-45: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Wawasan Kebangsaan Paket 04 (Hlm. 36–45) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 04 • Hlm. 36–45</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 04 • Hlm. 36–45</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Wawasan Kebangsaan Paket 04</h2>
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
const STG_LOCAL_KEY="stg_wk_p04_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam menjalankan wewenang legislasi dan anggaran, Presiden wajib memperoleh persetujuan DPR untuk...", "options": ["Membentuk undang-undang dan menetapkan anggaran pendapatan belanja", "Mengangkat duta konsul perwakilan pada organisasi bangsa", "Menunjuk jajaran direksi utama badan usaha milik negara", "Menetapkan struktur organisasi eselon pada satuan kerja kementerian", "Memberikan persetujuan izin cuti tahunan pegawai aparatur sipil"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Persetujuan DPR dalam Pembentukan UU dan APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden harus mendapat persetujuan DPR untuk <strong>membentuk undang-undang (gesetzgebung) dan untuk menetapkan Anggaran Pendapatan dan Belanja Negara (Staatsbegrooting)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pembentukan undang-undang dan penetapan APBN menyangkut regulasi publik dan penggunaan uang rakyat. DPR yang dipilih langsung oleh rakyat memegang mandat pengawasan dan persetujuan legislasi. Ketentuan ini mencegah kesewenang-wenangan eksekutif dalam menjalankan kekuasaan negara.</p><p>Jawaban tepat: <strong>A. Membentuk undang-undang dan menetapkan anggaran pendapatan belanja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pengangkatan duta konsul memperhatikan pertimbangan DPR, bukan persetujuan undang-undang.</li><li><strong>C</strong>: Pengangkatan direksi BUMN merupakan ranah kewenangan eksekutif kementerian teknis terkait.</li><li><strong>D</strong>: Penataan struktur eselon birokrasi diatur melalui peraturan presiden dan kementerian PANRB.</li><li><strong>E</strong>: Pemberian izin cuti pegawai adalah wewenang pejabat pembina kepegawaian internal instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>undang-undang (gesetzgebung) • menetapkan Anggaran Pendapatan dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Latar belakang filosofis kewajiban Presiden memperoleh persetujuan DPR dalam pembentukan UU adalah...", "options": ["Adanya keharusan menyelaraskan produk hukum dengan traktat sekutu", "Pemerintahan Indonesia adalah pemerintahan demokratis perwakilan rakyat", "Keinginan memperpanjang masa perdebatan di forum komisi dewan", "Instruksi khusus dari lembaga penegak akuntabilitas aparatur", "Tuntutan pembagian dana bagi hasil pendapatan sektor perpajakan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Latar Belakang Perlunya Persetujuan DPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Latar belakang dari prinsip tersebut adalah bahwa <strong>pemerintahan Indonesia adalah suatu pemerintahan yang demokratis dan berdasarkan perwakilan karena DPR dipilih rakyat melalui pemilu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena dalam sistem demokrasi perwakilan, aspirasi dan kehendak rakyat disalurkan melalui anggota dewan terpilih. Setiap undang-undang dan anggaran yang mengikat masyarakat harus mendapat legitimasi dari wakil-wakil rakyat tersebut. Asas ini menegakkan prinsip kedaulatan rakyat.</p><p>Jawaban tepat: <strong>B. Pemerintahan Indonesia adalah pemerintahan demokratis perwakilan rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hukum nasional berakar pada kedaulatan bangsa sendiri, bukan arahan negara sekutu.</li><li><strong>C</strong>: Proses legislasi dirancang untuk menghasilkan kepastian hukum, bukan sengaja memperlambat.</li><li><strong>D</strong>: KPK bertugas pada penegakan hukum korupsi dan bukan lembaga pembentuk dasar undang-undang.</li><li><strong>E</strong>: Dana bagi hasil adalah materi teknis perimbangan keuangan pusat dan daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Latar belakang dari prinsip di atas ialah • pemerintahan Indonesia adalah suatu pemerintahan yang demokratis</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pasal 20A ayat (1) UUD 1945 menegaskan bahwa DPR memiliki fungsi konstitusional berupa...", "options": ["Fungsi memimpin operasi penegakan hukum peradilan pidana khusus", "Fungsi menetapkan vonis hukuman bersalah bagi pelanggar disiplin", "Fungsi pengawasan terhadap jalannya pemerintahan oleh presiden", "Fungsi menerbitkan peraturan pemerintah pengganti undang-undang darurat", "Fungsi mengelola pencairan rekening kas umum perbendaharaan negara"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal 20A Ayat (1) Fungsi Pengawasan DPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 20A ayat (1) UUD 1945 menyatakan bahwa <strong>DPR memiliki fungsi pengawasan terhadap jalannya pemerintahan negara yang dilaksanakan presiden</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena selain fungsi legislasi dan anggaran, DPR mengemban fungsi pengawasan (control function). Fungsi ini memastikan bahwa kebijakan dan pelaksanaan anggaran oleh eksekutif berjalan sesuai undang-undang dan rencana pembangunan nasional. Pengawasan DPR menjaga keseimbangan kekuasaan.</p><p>Jawaban tepat: <strong>C. Fungsi pengawasan terhadap jalannya pemerintahan oleh presiden</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Operasi penegakan hukum merupakan wewenang aparat kepolisian dan kejaksaan agung.</li><li><strong>B</strong>: Penjatuhan vonis peradilan adalah wewenang mutlak majelis hakim di lembaga peradilan.</li><li><strong>D</strong>: Penerbitan Perppu merupakan kewenangan kegentingan memaksa yang berada di tangan presiden.</li><li><strong>E</strong>: Pengelolaan kas negara adalah mandat bendahara umum negara pada kementerian keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pasal 20A ayat (1) UUD 1945 menyatakan bahwa DPR memiliki • terhadap jalannya pemerintahan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Makna kemerdekaan kekuasaan kehakiman dalam Pasal 24 ayat (1) UUD 1945 adalah...", "options": ["Kebebasan bagi hakim untuk mengesampingkan teks undang-undang tertulis", "Wewenang membatalkan hasil pemilihan umum kepala daerah secara langsung", "Hak aparatur peradilan untuk memungut retribusi sengketa perkara", "Kekuasaan yang merdeka dan terlepas dari pengaruh pemerintah", "Kewenangan mengelola seluruh aset rampasan barang sitaan perdata"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kemandirian Kekuasaan Kehakiman Pasal 24 Ayat (1)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekuasaan kehakiman ialah kekuasaan yang merdeka sebagaimana diatur dalam Pasal 24 ayat (1) UUD 1945 <strong>artinya terlepas dari pengaruh kekuasaan pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena independensi peradilan (judicial independence) menjamin proses penegakan hukum berlangsung secara jujur, adil, dan objektif tanpa tekanan dari eksekutif maupun kekuasaan lain. Hakim bebas menjatuhkan putusan demi keadilan berdasarkan Ketuhanan Yang Maha Esa. Prinsip ini melindungi hak warga negara.</p><p>Jawaban tepat: <strong>D. Kekuasaan yang merdeka dan terlepas dari pengaruh pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hakim terikat pada koridor hukum tertulis dan nilai keadilan yang hidup di masyarakat.</li><li><strong>B</strong>: Sengketa hasil pemilu diperiksa dan diputus oleh Mahkamah Konstitusi sesuai undang-undang.</li><li><strong>C</strong>: Aparatur peradilan dilarang memungut biaya di luar ketentuan penerimaan negara bukan pajak.</li><li><strong>E</strong>: Pengelolaan barang rampasan negara dilakukan oleh instansi rumah penyimpanan benda sitaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kekuasaan kehakiman ialah kekuasaan yang merdeka • terlepas dari pengaruh kekuasaan pemerintah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 24 ayat (2) UUD 1945, lembaga pemegang kekuasaan kehakiman di Indonesia adalah...", "options": ["Komisi Yudisial dan dewan penasihat hukum kepresidenan kabinet", "Kejaksaan Agung bersama lembaga bantuan hukum advokat masyarakat", "Badan Pemeriksa Keuangan dan aparat inspektorat jenderal kementerian", "Dewan Perwakilan Daerah bersama komisi peradilan militer khusus", "Mahkamah Agung beserta jajaran peradilannya dan Mahkamah Konstitusi"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelaksana Kekuasaan Kehakiman Pasal 24 Ayat (2)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan Pasal 24 ayat (2) UUD 1945, kekuasaan kehakiman dilaksanakan oleh <strong>Mahkamah Agung dan badan-badan peradilan di bawahnya serta Mahkamah Konstitusi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena cabang kekuasaan yudikatif terbagi dalam dua puncak peradilan: Mahkamah Agung (membawahi lingkungan peradilan umum, agama, militer, dan tata usaha negara) serta Mahkamah Konstitusi yang menguji kesesuaian undang-undang terhadap UUD. Pembagian ini menjamin penegakan keadilan hukum secara menyeluruh.</p><p>Jawaban tepat: <strong>E. Mahkamah Agung beserta jajaran peradilannya dan Mahkamah Konstitusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Komisi Yudisial bertugas menjaga kehormatan hakim dan bukan pelaksana persidangan peradilan.</li><li><strong>B</strong>: Kejaksaan menjalankan fungsi penuntutan pidana dan bukan pemutus vonis peradilan.</li><li><strong>C</strong>: BPK mengaudit keuangan negara dan bukan lembaga pelaksana kekuasaan kehakiman.</li><li><strong>D</strong>: DPD adalah lembaga legislatif perwakilan daerah yang tidak menjalankan fungsi yudisial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dilaksanakan oleh Mahkamah Agung dan badan-badan dibawahnya • dan Mahkamah Konstitusi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Empat lingkungan peradilan yang berpuncak pada Mahkamah Agung sebagaimana disebut dalam modul adalah...", "options": ["Pengadilan Umum, Pengadilan Agama, Pengadilan Militer, dan TUN", "Peradilan Niaga, Peradilan Hak Asasi Manusia, Peradilan Tipikor, dan Adat", "Pengadilan Pajak, Pengadilan Arbitrase, Pengadilan Konsuler, dan Pasar", "Pengadilan Perburuhan, Pengadilan Maritim, Pengadilan Agraria, dan Siber", "Pengadilan Bea Cukai, Pengadilan Lingkungan, Pengadilan Anak, dan Desa"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Empat Lingkungan Peradilan di Bawah MA</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Badan-badan peradilan di bawah Mahkamah Agung mencakup <strong>Pengadilan Umum, Pengadilan Agama, Pengadilan Militer, dan Pengadilan Tata Usaha Negara (TUN)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena konstitusi menetapkan empat yurisdiksi utama peradilan yang berada di bawah pengawasan Mahkamah Agung. Pengadilan umum menangani perkara perdata dan pidana umum, peradilan agama menangani hukum keluarga muslim, militer menangani tindak prajurit, dan TUN menangani sengketa keputusan pejabat publik.</p><p>Jawaban tepat: <strong>A. Pengadilan Umum, Pengadilan Agama, Pengadilan Militer, dan TUN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Peradilan Niaga dan Tipikor merupakan pengadilan khusus di lingkungan peradilan umum.</li><li><strong>C</strong>: Pengadilan pajak berada di bawah MA namun bukan rumpun empat lingkungan utama konstitusi.</li><li><strong>D</strong>: Perburuhan dan agraria adalah kamar khusus dalam lingkungan peradilan umum/TUN.</li><li><strong>E</strong>: Pengadilan anak adalah kamar peradilan khusus dalam lingkup peradilan umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Umum, Agama, Militer, dan TUN) • Mahkamah Agung</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Prinsip pembagian wilayah administrasi pemerintahan daerah berdasarkan Pasal 18 UUD 1945 adalah...", "options": ["NKRI dibagi menjadi negara-negara bagian yang memiliki parlemen otonom", "NKRI dibagi atas daerah provinsi, dan provinsi dibagi atas kabupaten kota", "NKRI dibagi menjadi daerah karesidenan di bawah komando gubernur jenderal", "NKRI dibagi menjadi distrik perkebunan niaga dengan hak otonomi khusus", "NKRI dibagi menjadi wilayah swatantra kekaisaran adat tanpa ikatan pusat"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengaturan Pemerintah Daerah Pasal 18 UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Negara Kesatuan Republik Indonesia <strong>dibagi dalam daerah provinsi, dan daerah provinsi dibagi pula dalam kabupaten dan kota (Pasal 18 ayat 1)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena rumusan 'dibagi atas' menegaskan kesatuan kedaulatan negara Republik Indonesia yang terdistribusi secara administratif dan politis melalui pemerintahan daerah. Pemerintah daerah provinsi, kabupaten, dan kota mengatur urusannya sendiri berdasarkan asas otonomi seluas-luasnya. Pembagian ini menjaga keutuhan NKRI.</p><p>Jawaban tepat: <strong>B. NKRI dibagi atas daerah provinsi, dan provinsi dibagi atas kabupaten kota</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembagian menjadi negara bagian adalah ciri negara federal serikat, bukan NKRI.</li><li><strong>C</strong>: Sistem karesidenan kolonial telah dihapuskan seiring kemerdekaan Indonesia.</li><li><strong>D</strong>: Distrik perkebunan adalah unit usaha ekonomi dan bukan pembagian wilayah pemerintahan.</li><li><strong>E</strong>: Pemerintahan daerah berada dalam naungan tunggal Negara Kesatuan Republik Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemerintah daerah diatur dalam pasal 18, 18A, dan 18B • provinsi dibagi pula dalam kabupaten dan kota</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Komitmen konstitusi terhadap daerah yang memiliki sejarah keistimewaan dalam Pasal 18B ayat (1) adalah...", "options": ["Menghapuskan hak otonomi daerah tersebut agar seragam secara nasional", "Mengubah bentuk daerah istimewa menjadi pangkalan militer pertahanan", "Mengakui dan menghormati satuan pemda yang bersifat khusus atau istimewa", "Menyerahkan pengelolaan perpajakan daerah kepada kantor swasta asing", "Mewajibkan pergantian gelar pemimpin adat menjadi bupati seragam"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penghormatan Daerah Khusus dan Istimewa Pasal 18B Ayat (1)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Negara Kesatuan Republik Indonesia <strong>mengakui dan menghormati satuan-satuan pemerintah daerah yang bersifat khusus dan atau bersifat istimewa</strong> sebagaimana diatur dalam Pasal 18B ayat (1) UUD 1945.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena konstitusi menghargai latar belakang historis dan sosiokultural daerah seperti Aceh, DI Yogyakarta, Jakarta, dan Papua. Pengakuan ini memberikan dasar hukum bagi pemberian otonomi khusus tanpa mengurangi status mereka sebagai bagian tak terpisahkan dari NKRI. Ini bukti fleksibilitas konstitusi.</p><p>Jawaban tepat: <strong>C. Mengakui dan menghormati satuan pemda yang bersifat khusus atau istimewa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyeragaman kaku melanggar penghormatan atas hak asal-usul kearifan lokal daerah.</li><li><strong>B</strong>: Daerah istimewa memiliki pemerintahan sipil otonom yang dihormati secara konstitusional.</li><li><strong>D</strong>: Pajak daerah dikelola oleh badan pendapatan daerah resmi sesuai perundang-undangan.</li><li><strong>E</strong>: Gelar adat pemimpin tradisi diakui eksistensinya dalam struktur kepemimpinan daerah istimewa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>satuan-satuan pemerintah daerah yang bersifat • khusus dan atau bersifat istimewa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama penyelenggaraan otonomi daerah di seluruh wilayah nusantara adalah...", "options": ["Mendorong pemisahan wilayah kepulauan menjadi negara merdeka mandiri", "Memusatkan seluruh perizinan usaha perikanan di kantor kementerian", "Menambah beban pungutan retribusi kepada masyarakat lapisan miskin", "Mewujudkan pemerataan pembangunan dan peningkatan kesejahteraan rakyat", "Membatasi mobilitas perpindahan warga antarwilayah provinsi kepulauan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pelaksanaan Otonomi Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerataan pembangunan di seluruh wilayah Indonesia <strong>dilakukan melalui pelaksanaan otonomi daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena desentralisasi kekuasaan memungkinkan pemerintah daerah merespons kebutuhan masyarakat lokal secara cepat, tepat, dan efisien. Pembangunan tidak lagi tersentralisasi di ibu kota saja, melainkan tersebar merata ke pelosok daerah. Hal ini mempercepat terwujudnya keadilan sosial.</p><p>Jawaban tepat: <strong>D. Mewujudkan pemerataan pembangunan dan peningkatan kesejahteraan rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Otonomi daerah bertujuan memperkokoh integrasi NKRI dan bukan mendorong separatisme.</li><li><strong>B</strong>: Otonomi daerah justru mendelegasikan perizinan ke daerah demi memangkas birokrasi.</li><li><strong>C</strong>: Pungutan daerah diatur secara tertib untuk membiayai pelayanan publik, bukan membebani warga.</li><li><strong>E</strong>: Setiap warga negara memiliki kebebasan bergerak dan bertempat tinggal di seluruh NKRI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mewujudkan pemerataan pembangunan di seluruh wilayah • dilakukan melalui otonomi daerah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Prinsip 'Kekuasaan Presiden Tidak Tak Terbatas' dalam sistem ketatanegaraan Indonesia berarti bahwa...", "options": ["Presiden dapat memerintah seumur hidup tanpa proses pemilihan berkala", "Kekuasaan Presiden berada di atas hierarki seluruh undang-undang dasar", "Presiden bebas membubarkan dewan parlemen bila usulannya ditolak dewan", "Presiden kebal dari segala bentuk pemeriksaan pelanggaran hukum pidana", "Kekuasaan Presiden dibatasi oleh ketentuan UUD dan pengawasan DPR"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Kekuasaan Presiden Terbatas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekuasaan Presiden <strong>tidak tak terbatas</strong>, melainkan dibatasi secara tegas oleh konstitusi UUD 1945 dan diawasi oleh DPR.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena prinsip negara hukum dan konstitusionalisme menolak absolutisme kekuasaan eksekutif. Presiden wajib menjalankan wewenang dalam koridor hukum yang telah ditentukan dan bertanggung jawab kepada rakyat. Keseimbangan kekuasaan ini menjamin jalannya pemerintahan yang demokratis.</p><p>Jawaban tepat: <strong>E. Kekuasaan Presiden dibatasi oleh ketentuan UUD dan pengawasan DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masa jabatan presiden dibatasi maksimal dua periode sebagaimana diatur Pasal 7 UUD 1945.</li><li><strong>B</strong>: Presiden tunduk pada UUD 1945 sebagai hukum dasar tertulis tertinggi di Indonesia.</li><li><strong>C</strong>: Pasal 7C UUD 1945 secara tegas melarang presiden membekukan atau membubarkan DPR.</li><li><strong>D</strong>: Presiden dapat diberhentikan melalui proses pemakzulan apabila terbukti melanggar hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kekuasaan Presiden • Tidak Tak Terbatas</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal II Aturan Tambahan, naskah resmi UUD 1945 terdiri atas...", "options": ["Naskah Pembukaan, Batang Tubuh, dan Lampiran Penjelasan Resmi", "Keseluruhan naskah yang terdiri dari Pembukaan dan pasal-pasal", "Dekrit Presiden, Naskah Pembukaan, dan Keputusan Majelis Permusyawaratan", "Teks Proklamasi Kemerdekaan dan Rincian Pasal Peralihan Konstitusi", "Risalah Sidang Badan Persiapan dan Naskah Undang-Undang Tertulis"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sistematika Naskah UUD 1945 Pasca-Amandemen</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UUD 1945 yang dimaksud adalah naskah yang terdiri dari <strong>Pembukaan dan pasal-pasal (Pasal II Aturan Tambahan)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pasca-amandemen keempat tahun 2002, bagian Penjelasan UUD 1945 tidak lagi menjadi bagian dari naskah konstitusi. Muatan-muatan normatif dalam penjelasan telah diintegrasikan langsung ke dalam pasal-pasal batang tubuh. Oleh karena itu, naskah resmi UUD 1945 hanya terdiri atas Pembukaan dan pasal-pasal.</p><p>Jawaban tepat: <strong>B. Keseluruhan naskah yang terdiri dari Pembukaan dan pasal-pasal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penjelasan resmi telah dicabut statusnya sebagai kesatuan naskah konstitusi UUD 1945.</li><li><strong>C</strong>: Dekrit Presiden 5 Juli 1959 adalah peristiwa hukum sejarah, bukan bagian sistematika teks UUD.</li><li><strong>D</strong>: Teks Proklamasi merupakan dokumen terpisah dari naskah undang-undang dasar negara.</li><li><strong>E</strong>: Risalah sidang BPUPKI adalah dokumen catatan sejarah perumusan, bukan naskah UUD.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>keseluruhan naskah yang terdiri dari • Pembukaan dan pasal-pasal (Pasal II Aturan Tambahan)</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Konstitusi berkedudukan sebagai hukum yang lebih tinggi dan fundamental karena berfungsi sebagai...", "options": ["Satu-satunya dokumen tertulis yang mengatur tata tertib persidangan kabinet", "Pedoman penetapan harga pasar barang kebutuhan pokok masyarakat", "Sumber legitimasi atau landasan otorisasi bentuk peraturan hukum lainnya", "Instrumen penetapan besaran tarif retribusi izin mendirikan bangunan", "Buku petunjuk teknis pelaksanaan administrasi kantor kementerian luar negeri"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Konstitusi sebagai Hukum Tertinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Konstitusi merupakan <strong>hukum yang lebih tinggi dan paling fundamental sifatnya karena merupakan sumber legitimasi atau landasan otorisasi bentuk peraturan perundang-undangan lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena seluruh produk peraturan di bawah undang-undang dasar (seperti UU, PP, Perpres, Perda) memperoleh kekuatan hukum mengikatnya dari kewenangan yang diberikan oleh konstitusi. Tidak boleh ada norma hukum yang bertentangan dengan UUD. Konstitusi adalah batu uji keabsahan hukum.</p><p>Jawaban tepat: <strong>C. Sumber legitimasi atau landasan otorisasi bentuk peraturan hukum lainnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tata tertib kabinet diatur dalam peraturan presiden dan bukan isi utama konstitusi.</li><li><strong>B</strong>: Harga pasar barang pokok ditentukan mekanisme ekonomi dan kebijakan menteri perdagangan.</li><li><strong>D</strong>: Tarif retribusi IMB/PBG diatur dalam peraturan daerah kabupaten/kota teknis.</li><li><strong>E</strong>: Petunjuk teknis kementerian adalah pedoman operasional internal aparatur dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Konstitusi merupakan hukum yang lebih tinggi • sumber legitimasi atau landasan otorisasi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Syarat universal agar suatu peraturan perundang-undangan di bawah UUD dapat berlaku sah adalah...", "options": ["Mendapat ratifikasi persetujuan dari perwakilan lembaga perbankan asing", "Diterbitkan dalam lembaran berita militer komando pertahanan daerah", "Disetujui secara aklamasi oleh pimpinan asosiasi pengusaha swasta", "Tidak boleh bertentangan dengan peraturan hukum yang lebih tinggi tingkatannya", "Memuat pasal-pasal kekebalan hukum bagi para pembuat rancangannya"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Preferensi Hierarki Peraturan Hukum</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai prinsip hukum universal, agar peraturan di bawah UUD dapat berlaku, <strong>peraturan itu tidak boleh bertentangan dengan hukum yang lebih tinggi (lex superior derogat legi inferiori)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena hierarki hukum menuntut konsistensi vertikal di mana peraturan yang lebih rendah harus bersumber dan tunduk pada norma yang lebih tinggi. Bila terjadi pertentangan norma, maka peraturan yang lebih rendah dapat dibatalkan melalui uji materiil di Mahkamah Agung atau Mahkamah Konstitusi. Ini menjaga kepastian hukum.</p><p>Jawaban tepat: <strong>D. Tidak boleh bertentangan dengan peraturan hukum yang lebih tinggi tingkatannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Lembaga keuangan internasional tidak memiliki wewenang ratifikasi hukum positif nasional.</li><li><strong>B</strong>: Publikasi resmi peraturan negara dimuat dalam Lembaran Negara RI, bukan berita militer.</li><li><strong>C</strong>: Asosiasi swasta tidak memegang hak pembentukan atau pengesahan peraturan perundang-undangan.</li><li><strong>E</strong>: Konstitusi menjamin kesetaraan hukum dan menentang kekebalan hukum sewenang-wenang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>agar peraturan yang tingkatannya berada di bawah undang-undang • tidak boleh bertentangan dengan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Hierarki peraturan hukum yang ketat dalam konstitusi dirancang dengan tujuan utama agar kekuasaan...", "options": ["Dapat dipusatkan secara permanen pada tangan satu kelompok elite penguasa", "Dikecualikan dari kewajiban pemeriksaan laporan pertanggungjawaban dana", "Bebas digunakan untuk menindak siapa saja yang berbeda haluan politik", "Kelompok kerabat tertentu yang memiliki relasi kedinasan", "Dapat dibatasi dan dikendalikan sebagaimana mestinya demi mencegah tirani"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pengendalian Kekuasaan Pemerintahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengaturan konstitusi sedemikian rupa menjadikan <strong>dinamika kekuasaan dalam proses penyelenggaraan pemerintahan dan negara dapat dibatasi dan dikendalikan sebagaimana mestinya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena hakikat konstitusionalisme adalah membatasi wewenang penguasa (limited government) agar tidak menyalahgunakan wewenang (abuse of power). Pembatasan ini memberikan jaminan perlindungan terhadap hak-hak dasar warga negara dari kesewenang-wenangan negara. Keadilan hukum ditegakkan secara objektif.</p><p>Jawaban tepat: <strong>E. Dapat dibatasi dan dikendalikan sebagaimana mestinya demi mencegah tirani</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemusatan kekuasaan pada satu elite melahirkan kediktatoran yang ditentang konstitusi.</li><li><strong>B</strong>: Penggunaan anggaran negara wajib diaudit secara transparan dan akuntabel oleh BPK.</li><li><strong>C</strong>: Kekuasaan negara tunduk pada hak asasi dan tidak boleh dijadikan alat represi politik.</li><li><strong>D</strong>: Sistem republik menegakkan meritokrasi dan menolak sistem pewarisan kekuasaan keluarga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>proses penyelenggaraan pemerintahan dan negara dapat • dan dikendalikan sebagaimana mestinya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam sejarah perkembangan ketatanegaraan modern, kedudukan konstitusi diakui sebagai...", "options": ["Naskah legitimasi pengakuan terhadap paham kedaulatan rakyat", "Maklumat penaklukan kedaulatan warga oleh penguasa monarki mutlak", "Surat perjanjian kerja sama monopoli dagang antarpelabuhan maritim", "Piagam pelepasan tanggung jawab negara atas kesejahteraan sosial", "Instruksi dinas seremonial yang dikhususkan pada masa damai"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konstitusi sebagai Legitimasi Kedaulatan Rakyat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Konstitusi dalam sejarah perkembangannya membawa pengakuan akan pemerintahan rakyat dan <strong>merupakan naskah legitimasi paham kedaulatan rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena konstitusi lahir dari tuntutan rakyat untuk membatasi kesewenang-wenangan raja dan menegaskan bahwa pemegang kekuasaan sejati adalah rakyat. Konstitusi mendokumentasikan pelimpahan mandat rakyat kepada penyelenggara negara secara terukur dan terbatas. Dokumen ini menjadi piagam kebebasan rakyat.</p><p>Jawaban tepat: <strong>A. Naskah legitimasi pengakuan terhadap paham kedaulatan rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Konstitusi bertujuan meruntuhkan tirani monarki mutlak, bukan memperkuat penaklukan.</li><li><strong>C</strong>: Perjanjian dagang adalah traktat perniagaan dan bukan konstitusi dasar suatu negara bangsa.</li><li><strong>D</strong>: Konstitusi modern justru mewajibkan negara menjamin kesejahteraan dan hak sosial warganya.</li><li><strong>E</strong>: Konstitusi berlaku secara permanen dan berkelanjutan baik dalam masa damai maupun darurat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Konstitusi merupakan naskah legitimasi • paham kedaulatan rakyat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Konstitusi dipandang sebagai sebuah 'kontrak sosial' bermakna bahwa konstitusi...", "options": ["Perjanjian sewa menyewa tanah perladangan antara petani dan bangsawan", "Kesepakatan luhur yang mengikat setiap warga dalam membangun kedaulatan", "Dokumen transaksi jual beli komoditas ekspor antarnegara kawasan", "Kontrak kerja outsourcing pegawai administrasi satuan kerja birokrasi", "Surat pernyataan kesanggupan membayar pinjaman utang perbankan daerah"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konstitusi sebagai Kontrak Sosial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Naskah konstitusi merupakan <strong>kontrak sosial yang mengikat setiap warga dalam membangun paham kedaulatan rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena teori kontrak sosial (social contract) memandang negara terbentuk atas dasar kesepakatan bersama antarindividu untuk hidup rukun dalam tatanan hukum yang disepakati. Konstitusi merumuskan hak, kewajiban, dan pembatasan kekuasaan demi kebaikan bersama. Seluruh warga dan pemimpin terikat mematuhinya.</p><p>Jawaban tepat: <strong>B. Kesepakatan luhur yang mengikat setiap warga dalam membangun kedaulatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sewa menyewa tanah merupakan akad perdata perseorangan, bukan kontrak sosial kenegaraan.</li><li><strong>C</strong>: Transaksi komoditas adalah perikatan bisnis dagang internasional di luar substansi konstitusi.</li><li><strong>D</strong>: Kontrak kerja kepegawaian adalah urusan ketenagakerjaan teknis di bawah undang-undang.</li><li><strong>E</strong>: Perjanjian pinjaman bank adalah ranah hukum perikatan perdata, bukan kontrak sosial politik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Naskah dimaksud merupakan • kontrak sosial yang mengikat setiap warga</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam hierarki tata urutan hukum Republik Indonesia, Undang-Undang Dasar 1945 berkedudukan sebagai...", "options": ["Aturan pelengkap yang kedudukannya sejajar dengan peraturan bupati", "Hukum perdata khusus yang spesifik mengikat pimpinan satuan kerja dinas", "Tingkatan peraturan perundang-undangan tertinggi dalam tertib hukum negara", "Peraturan peralihan yang masa berlakunya dibatasi setiap lima tahunan", "Pedoman kerja teknis yang dapat disimpangi demi efisiensi operasional"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • UUD sebagai Peraturan Tertinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Undang-Undang Dasar menempati <strong>tata urutan peraturan perundang-undangan tertinggi dalam negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena UUD 1945 menempati puncak piramida peraturan hukum (grundnorm operasional) di Indonesia. Tidak ada undang-undang atau peraturan pemerintah yang boleh berada di atas atau bertentangan dengan UUD 1945. Kedudukan tertinggi ini menjamin supremasi konstitusi dalam kehidupan bernegara.</p><p>Jawaban tepat: <strong>C. Tingkatan peraturan perundang-undangan tertinggi dalam tertib hukum negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Peraturan bupati berada pada tingkat hierarki terendah dalam tata perundangan nasional.</li><li><strong>B</strong>: UUD 1945 adalah hukum publik tata negara yang mengikat segenap tumpah darah Indonesia.</li><li><strong>D</strong>: UUD 1945 berlaku permanen dan bukan peraturan sementara yang diperbarui setiap 5 tahun.</li><li><strong>E</strong>: UUD 1945 bersifat memaksa dan mengikat mutlak, haram disimpangi demi dalih efisiensi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Undang-Undang Dasar menempati tata urutan • tertinggi dalam negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Materi muatan fundamental yang ditetapkan dalam sebuah konstitusi negara mencakup...", "options": ["Standar operasional prosedur pengarsipan surat dinas harian kantor", "Tata tertib pemilihan pengurus rukun warga dan lembaga ketetanggaan", "Daftar harga sewa aset gedung pertemuan milik pemerintah daerah", "Bentuk negara, bentuk pemerintahan, struktur lembaga, dan hak rakyat", "Pedoman penetapan tunjangan uang lembur pegawai aparatur sipil negara"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Materi Muatan Pokok Konstitusi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam konteks institusi negara, konstitusi bermakna penetapan <strong>pemegang kedaulatan, struktur negara, bentuk negara, bentuk pemerintahan, kekuasaan legislatif, peradilan, berbagai lembaga negara serta hak-hak rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena konstitusi mengatur cetak biru arsitektur negara, mencakup bagaimana kekuasaan dibagi dan dijalankan serta bagaimana hak-hak dasar warga negara dilindungi dari kesewenang-wenangan. Hal-hal teknis administratif diatur dalam peraturan pelaksana di bawahnya. Inilah materi pokok konstitusi.</p><p>Jawaban tepat: <strong>D. Bentuk negara, bentuk pemerintahan, struktur lembaga, dan hak rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: SOP pengarsipan surat diatur dalam peraturan menteri atau keputusan kepala lembaga teknis.</li><li><strong>B</strong>: Tata tertib RW diatur dalam peraturan walikota/bupati atau peraturan desa setempat.</li><li><strong>C</strong>: Tarif sewa aset pemda diatur melalui peraturan daerah tentang pengelolaan barang milik daerah.</li><li><strong>E</strong>: Tunjangan lembur pegawai diatur dalam standar biaya masukan kementerian keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kekuasaan legislatif, kekuasaan peradilan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Letak resmi rumusan falsafah dasar negara Pancasila dalam naskah konstitusi berada pada...", "options": ["Alinea pertama naskah deklarasi kemerdekaan proklamasi", "Bab penjelasan umum mengenai hak kekuasaan kepala negara", "Pasal 1 ayat (1) batang tubuh bentuk negara kesatuan", "Pasal aturan peralihan penyerahan inventarisasi kantor", "Alinea keempat Pembukaan Undang-Undang Dasar 1945"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Rumusan Pancasila dalam Sistematika UUD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembukaan terdiri atas 4 alinea, yang <strong>di dalam alinea keempat terdapat rumusan dari Pancasila</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena alinea keempat memuat tujuan negara, perintah pembentukan undang-undang dasar, bentuk republik berkedaulatan rakyat, serta rumusan resmi kelima sila Pancasila. Alinea keempat merupakan intisari filosofis dan yuridis berdirinya Negara Kesatuan Republik Indonesia. Kedudukannya tidak dapat diubah.</p><p>Jawaban tepat: <strong>E. Alinea keempat Pembukaan Undang-Undang Dasar 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Alinea pertama menegaskan prinsip hak kemerdekaan dan penolakan terhadap kolonialisme.</li><li><strong>B</strong>: Penjelasan umum telah dihapus dari sistematika resmi naskah UUD 1945 amandemen.</li><li><strong>C</strong>: Pasal 1 ayat (1) mengatur bentuk negara kesatuan yang berbentuk republik merdeka.</li><li><strong>D</strong>: Aturan peralihan mengatur kontinuitas peraturan lama sebelum terbentuknya aturan baru.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>alinea, yang di dalam alinea keempat • terdapat rumusan dari Pancasila</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Jumlah bab yang tersusun dalam pasal-pasal batang tubuh UUD 1945 pasca-amandemen adalah...", "options": ["20 Bab yang terbentang dari Bab I sampai Bab XVI aktif", "16 Bab yang terbagi dalam tiga puluh tujuh pasal naskah", "25 Bab yang memuat lima puluh butir pedoman operasional", "12 Bab yang memfokuskan pengaturan pada dewan pertimbangan", "18 Bab yang menghapuskan bab mengenai kekuasaan kehakiman"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jumlah Bab dalam Batang Tubuh UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal-pasal Undang-Undang Dasar 1945 <strong>terdiri dari 20 Bab</strong> (melalui penomoran sisipan Bab VIIA, VIIB, dsb. dengan Bab IV dihapus).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena amandemen UUD 1945 memperluas struktur bab untuk menampung lembaga baru (seperti DPD, MK, KY, Pemilu, BPK) dan bab khusus Hak Asasi Manusia. Meskipun nomor bab formal berakhir di Bab XVI, jumlah fisik bab keseluruhan mencapai 20 Bab setelah bab DPA dihapus dan bab sisipan ditambahkan. Struktur ini mencerminkan dinamika ketatanegaraan modern.</p><p>Jawaban tepat: <strong>A. 20 Bab yang terbentang dari Bab I sampai Bab XVI aktif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: 16 Bab adalah jumlah bab naskah asli sebelum dilakukan rangkaian amandemen 1999-2002.</li><li><strong>C</strong>: 25 Bab bukan jumlah resmi yang tertera dalam sistematika UUD 1945 pasca-amandemen.</li><li><strong>D</strong>: 12 Bab bukan klasifikasi sistematika konstitusi; Bab IV tentang DPA justru telah dihapuskan.</li><li><strong>E</strong>: Kekuasaan kehakiman tetap diatur dalam Bab IX dan tidak pernah dihapuskan dari konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pembukaan dan pasal-pasal</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Komposisi pasal dalam batang tubuh UUD 1945 pasca-amandemen terdiri atas...", "options": ["37 pasal inti tanpa penambahan aturan peralihan dan aturan tambahan", "100 pasal yang memuat ketentuan sanksi pidana dan denda administratif", "73 pasal ditambah 3 pasal Aturan Peralihan dan 2 pasal Aturan Tambahan", "50 pasal ditambah 5 pasal Aturan Khusus otonomi daerah kepulauan", "60 pasal yang mengatur kewenangan lembaga pengawas keuangan negara"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komposisi Pasal Batang Tubuh UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal-pasal UUD 1945 pasca-amandemen terdiri dari <strong>73 pasal (Pasal 1 sampai Pasal 37), ditambah 3 pasal Aturan Peralihan dan 2 pasal Aturan Tambahan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena rangkaian amandemen 1999-2002 menambahkan pasal-pasal baru menggunakan penomoran alfabetis (seperti Pasal 28A hingga 28J) sehingga jumlah keseluruhan pasal melonjak menjadi 73 pasal dari asalnya 37 pasal. Ditambah 3 pasal aturan peralihan dan 2 aturan tambahan, struktur ini menjadi pedoman ketatanegaraan modern.</p><p>Jawaban tepat: <strong>C. 73 pasal ditambah 3 pasal Aturan Peralihan dan 2 pasal Aturan Tambahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: 37 pasal adalah jumlah penomoran pokok naskah asli sebelum amandemen.</li><li><strong>B</strong>: 100 pasal bukan jumlah ketentuan resmi dalam naskah konstitusi UUD 1945.</li><li><strong>D</strong>: Aturan khusus otonomi daerah diatur dalam undang-undang tersendiri, bukan pasal UUD.</li><li><strong>E</strong>: Ketentuan BPK diatur dalam Pasal 23E s/d 23G, bukan 60 pasal konstitusi tersendiri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>73 pasal (Pasal 1 sampai • 3 pasal Aturan Peralihan dan 2 pasal Aturan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Perubahan kelembagaan dan sistematika yang diputuskan dalam Amandemen Keempat UUD 1945 adalah...", "options": ["Pembentukan kementerian koordinator baru dan pembubaran mahkamah konstitusi", "Penetapan masa jabatan presiden menjadi tidak terbatas melalui dekrit", "Penggabungan lembaga peradilan agama ke dalam departemen kementerian", "Penghapusan Bab IV tentang DPA dan pencabutan status naskah penjelasan", "Pemisahan kepulauan timur menjadi wilayah teritorial administrasi serikat"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penghapusan DPA dan Status Penjelasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam amandemen keempat, <strong>Bab IV tentang DPA dihapus dan Penjelasan UUD 1945 tidak lagi merupakan kesatuan UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena fungsi pertimbangan diserahkan kepada dewan pertimbangan yang dibentuk langsung oleh presiden (Pasal 16), sehingga Dewan Pertimbangan Agung (DPA) resmi dibubarkan. Selain itu, Penjelasan UUD 1945 ditiadakan agar tidak menimbulkan dualisme tafsir hukum antara pasal dan penjelasan.</p><p>Jawaban tepat: <strong>D. Penghapusan Bab IV tentang DPA dan pencabutan status naskah penjelasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mahkamah Konstitusi justru dibentuk melalui amandemen ketiga UUD 1945.</li><li><strong>B</strong>: Masa jabatan presiden dibatasi secara tegas maksimal dua periode (Pasal 7).</li><li><strong>C</strong>: Peradilan agama tetap berada di bawah kekuasaan kehakiman Mahkamah Agung.</li><li><strong>E</strong>: NKRI berpegang teguh pada bentuk negara kesatuan yang tidak dapat diubah (Pasal 37 ayat 5).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Bab IV tentang DPA dihapus • UUD 1945, tidak lagi merupakan kesatuan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Naskah resmi UUD 1945 pada awal kemerdekaan disiarkan pemerintah dalam penerbitan resmi...", "options": ["Lembaran Pengumuman Markas Besar Militer Daerah tertanggal 17 Agustus 1945", "Surat Edaran Perdana Menteri Sutan Sjahrir tertanggal 20 November 1945", "Harian Suara Merdeka Keresidenan Jawa Tengah terbit tanggal 18 Agustus 1945", "Risalah Steno Parlemen Perserikatan Bangsa tertanggal 1 Januari 1946", "Berita Republik Indonesia Tahun II No. 7 terbit tanggal 15 Februari 1946"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Publikasi Perdana Naskah Resmi UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada awalnya naskah resmi UUD 1945 dimuat dan disiarkan dalam <strong>'Berita Republik Indonesia' Tahun II No. 7 yang terbit tanggal 15 Februari 1946</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Berita Republik Indonesia merupakan media resmi publikasi perundang-undangan (lembaran negara pertama) milik Pemerintah RI saat revolusi fisik. Pemugaran naskah dalam media ini memberikan kepastian hukum dan otentisitas teks UUD 1945 yang telah disahkan oleh PPKI pada 18 Agustus 1945.</p><p>Jawaban tepat: <strong>E. Berita Republik Indonesia Tahun II No. 7 terbit tanggal 15 Februari 1946</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengumuman militer bukan media publikasi resmi peraturan ketatanegaraan pemerintah.</li><li><strong>B</strong>: Surat edaran perdana menteri mengatur maklumat kabinet dan bukan naskah UUD 1945.</li><li><strong>C</strong>: Surat kabar daerah memberitakan proklamasi, bukan naskah lengkap UUD 1945 resmi.</li><li><strong>D</strong>: PBB belum menerbitkan risalah undang-undang dasar negara Indonesia pada masa itu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Indonesia” Tahun II No. 7 • terbit tanggal 15 Februari 1946</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Lembaga pendiri negara yang menetapkan berlakunya UUD 1945 pada 18 Agustus 1945 adalah...", "options": ["Panitia Persiapan Kemerdekaan Indonesia dalam sidang pertamanya", "Badan Penyelidik Usaha-usaha Persiapan Kemerdekaan Indonesia", "Komite Nasional Indonesia Pusat perwakilan para pemuda pejuang", "Dewan Perancang Nasional bentukan kabinet perdana kementerian", "Panitia Sembilan perumus naskah mukadimah hukum dasar negara"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengesahan dan Mulai Berlakunya UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Undang-Undang Dasar 1945 <strong>ditetapkan oleh Panitia Persiapan Kemerdekaan Indonesia (PPKI) dan mulai berlaku pada tanggal 18 Agustus 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena BPUPKI hanya bertugas merancang draf naskah undang-undang dasar, sedangkan wewenang pengesahan yuridis formal sebagai konstitusi negara merdeka dilakukan oleh PPKI pada 18 Agustus 1945. Pengesahan ini menjadi dasar hukum berdirinya tata negara Republik Indonesia.</p><p>Jawaban tepat: <strong>A. Panitia Persiapan Kemerdekaan Indonesia dalam sidang pertamanya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: BPUPKI telah dibubarkan pada 7 Agustus 1945 setelah tugas perancangannya selesai.</li><li><strong>C</strong>: KNIP baru dibentuk pada tanggal 22 Agustus 1945 sebagai pembantu presiden.</li><li><strong>D</strong>: Depernas adalah lembaga perencanaan pembangunan era Demokrasi Terpimpin tahun 1959.</li><li><strong>E</strong>: Panitia Sembilan adalah panitia kecil ad hoc BPUPKI yang merumuskan Piagam Jakarta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>ditetapkan oleh Panitia Persiapan Kemerdekaan Indoneisa (PPKI) • pada tanggal 18 Agustus 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Paham konstitusionalisme dalam tata negara berawal dari gagasan mendasar bahwa...", "options": ["Negara harus memperluas wilayah kekuasaannya melalui penaklukan maritim", "Konstitusi dipergunakan sebagai hukum utama penyelenggaraan negara", "Kekuasaan penguasa tidak boleh dibatasi oleh dokumen hukum tertulis", "Pemerintahan dijalankan berdasarkan kehendak subjektif seorang raja", "Seluruh kebijakan dinas wajib diserahkan kepada perwakilan serikat buruh"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asal Mula Paham Konstitusionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Paham konstitusionalisme bermula dari <strong>dipergunakannya konstitusi sebagai hukum dalam penyelenggaraan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena konstitusionalisme menempatkan konstitusi sebagai hukum tertinggi yang mengatur dan membatasi kekuasaan para penguasa. Penyelenggaraan pemerintahan tidak boleh bersandar pada kemauan mutlak individu melainkan harus berdasar pada aturan hukum. Gagasan ini melahirkan negara hukum modern.</p><p>Jawaban tepat: <strong>B. Konstitusi dipergunakan sebagai hukum utama penyelenggaraan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ekspansi teritorial merupakan ciri imperialisme klasik dan bertentangan dengan hukum dasar.</li><li><strong>C</strong>: Gagasan kekuasaan tanpa batas adalah watak monarki absolut yang ditentang konstitusi.</li><li><strong>D</strong>: Pemerintahan berbasis kehendak subjektif raja adalah kesewenang-wenangan nonkonstitusional.</li><li><strong>E</strong>: Sindikalisme serikat buruh adalah paham ekonomi politik, bukan asal mula konstitusionalisme.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>konstitusionalisme • dipergunakannya konstitusi sebagai hukum dalam penyelenggaraan negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Fungsi konstitusionalisme dalam mengatur prinsip supremasi hukum (rule of law) adalah...", "options": ["Memberikan hak istimewa imunitas penuntutan pidana kepada aparat dinas", "Memperbolehkan penahanan warga negara tanpa melalui prosedur peradilan", "Mengatur pelaksanaan supremasi hukum dalam hubungan individu dan pemerintah", "Kebijakan diskresioner aparatur di luar kerangka hukum", "Membatasi hak warga untuk mengakses dokumen informasi publik kementerian"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konstitusionalisme dan Rule of Law</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Konstitusionalisme <strong>mengatur pelaksanaan rule of law (supremasi hukum) dalam hubungan individu dengan pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena rule of law memastikan bahwa hubungan antara rakyat dan penguasa diatur oleh hukum yang adil dan objektif, bukan oleh kesewenang-wenangan kekuasaan. Hak-hak individu dilindungi dari kesewenang-wenangan aparat negara. Inilah inti tegaknya kepastian dan keadilan hukum.</p><p>Jawaban tepat: <strong>C. Mengatur pelaksanaan supremasi hukum dalam hubungan individu dan pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Imunitas mutlak aparat bertentangan dengan prinsip kesetaraan di hadapan hukum.</li><li><strong>B</strong>: Penahanan tanpa proses hukum (due process of law) melanggar hak asasi konstitusional.</li><li><strong>D</strong>: Tuntutan kepatuhan buta pada penguasa adalah watak negara kekuasaan (machtsstaat).</li><li><strong>E</strong>: Keterbukaan informasi publik dijamin dalam negara hukum yang akuntabel dan transparan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Konstitusionalisme mengatur pelaksanaan rule of law (supremasi hukum) • hubungan individu dengan pemerintah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Mengapa konstitusionalisme mampu menghadirkan rasa aman bagi segenap warga negara?", "options": ["Karena tersedianya persenjataan militer lengkap di setiap pos lingkungan", "Karena adanya jaminan penghasilan tetap tanpa keharusan bekerja dinas", "Karena dihapuskannya seluruh kewajiban membayar pajak dan bea retribusi", "Karena adanya pembatasan terhadap wewenang pemerintah yang telah ditentukan", "Karena tertutupnya akses komunikasi masyarakat dengan dunia internasional"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rasa Aman dari Pembatasan Wewenang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Konstitusionalisme menghadirkan situasi yang dapat <strong>memupuk rasa aman, karena adanya pembatasan terhadap wewenang pemerintah yang telah ditentukan terlebih dahulu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena ketika wewenang penguasa dibatasi secara tegas dalam konstitusi, masyarakat terbebas dari ketakutan akan kesewenang-wenangan, penyitaan sepihak, atau penindasan hak. Warga dapat menjalani kehidupan secara tenang dalam koridor kepastian hukum. Pembatasan kekuasaan melahirkan rasa aman yang sejati.</p><p>Jawaban tepat: <strong>D. Karena adanya pembatasan terhadap wewenang pemerintah yang telah ditentukan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rasa aman konstitusional bersumber dari perlindungan hukum, bukan persenjataan sipil.</li><li><strong>B</strong>: Jaminan penghasilan tanpa bekerja bertentangan dengan etos kerja dan pembangunan nasional.</li><li><strong>C</strong>: Pajak merupakan kewajiban konstitusional warga untuk mendanai pembangunan negara.</li><li><strong>E</strong>: Isolasi hubungan internasional justru merugikan pergaulan bangsa di tengah peradaban dunia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>yang dapat memupuk rasa aman • adanya pembatasan terhadap wewenang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Konsep 'the limited state' (negara terbatas) yang diemban konstitusionalisme bermakna...", "options": ["Jumlah penduduk warga negara dibatasi secara ketat oleh regulasi dinas", "Jumlah partai politik peserta pemilu dibatasi maksimal dua partai saja", "Luas wilayah teritorial negara dibatasi eksklusif pada gugusan pulau utama", "Anggaran belanja kementerian dibatasi pada pos pembiayaan aparatur kantor", "Penyelenggaraan kekuasaan diatur tegas agar tidak bertindak sewenang-wenang"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep The Limited State</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Konstitusionalisme mengemban <strong>the limited state (negara terbatas) agar penyelenggaraan negara dan pemerintahan tidak sewenang-wenang dan hal dimaksud diatur secara tegas dalam konstitusi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena 'limited state' berarti kekuasaan negara dibatasi oleh hukum dan konstitusi, tidak mutlak atau tak terbatas. Negara hanya boleh bertindak sejauh kewenangan yang diberikan oleh aturan hukum. Doktrin ini menjadi pilar utama perlindungan kemerdekaan rakyat.</p><p>Jawaban tepat: <strong>E. Penyelenggaraan kekuasaan diatur tegas agar tidak bertindak sewenang-wenang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Limited state merujuk pada pembatasan wewenang kekuasaan, bukan pengendalian demografi.</li><li><strong>B</strong>: Sistem kepartaian diatur dalam undang-undang pemilu dan bukan definisi 'limited state'.</li><li><strong>C</strong>: Kedaulatan wilayah NKRI mencakup seluruh kepulauan nusantara tanpa pembatasan pulau.</li><li><strong>D</strong>: Anggaran dialokasikan untuk sebesar-besar kemakmuran rakyat, bukan terbatas pada gaji.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>the limited state (negara terbatas) • tidak sewenang-wenang dan hal dimaksud dinyatakan serta diatur secara tegas</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan bentuk fisiknya dalam kajian ilmu tata negara, konstitusi terbagi ke dalam...", "options": ["Hukum dasar tertulis (Undang-Undang Dasar) dan hukum dasar tidak tertulis", "Hukum pidana materiil publik dan hukum acara perdata perseorangan", "Hukum adat peninggalan kerajaan dan hukum dagang maritim antarpulau", "Peraturan dinas internal kementerian dan maklumat seruan bupati daerah", "Perjanjian perikatan dagang komersial dan piagam traktat persahabatan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Bentuk Hukum Dasar Konstitusi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Konstitusi adalah hukum dasar yang dijadikan pegangan, yang <strong>dapat berupa hukum dasar tertulis yang lazim disebut Undang-Undang Dasar, dan dapat pula tidak tertulis (konvensi)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena istilah konstitusi mencakup hukum dasar tertulis (UUD) yang dibukukan dalam dokumen resmi dan hukum dasar tidak tertulis berupa kebiasaan ketatanegaraan (konvensi) yang dipelihara dalam praktik bernegara. Keduanya saling melengkapi dalam tertib ketatanegaraan.</p><p>Jawaban tepat: <strong>A. Hukum dasar tertulis (Undang-Undang Dasar) dan hukum dasar tidak tertulis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Hukum pidana dan perdata adalah cabang hukum positif substantif di bawah konstitusi.</li><li><strong>C</strong>: Hukum adat dan hukum dagang adalah ranah hukum khusus perdata dan kebiasaan lokal.</li><li><strong>D</strong>: Peraturan dinas adalah regulasi pelaksana administratif, bukan klasifikasi konstitusi.</li><li><strong>E</strong>: Perjanjian perikatan perdata dan traktat luar negeri bukan pembagian bentuk konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>hukum dasar yang dijadikan • dasar tertulis yang lazim disebut Undang-Undang Dasar, dan dapat pula tidak tertulis</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam konteks institusi negara berdaulat, konstitusi diposisikan sebagai...", "options": ["Saran pertimbangan dari perhimpunan sarjana hukum tata usaha negara", "Permakluman tertinggi yang menetapkan struktur negara dan kedaulatan", "Rencana kerja tahunan operasional pelayanan publik satuan kerja dinas", "Panduan tata cara pembukuan rekening perbendaharaan kas kementerian", "Pedoman pelaksanaan mutasi promosi jabatan aparatur sipil birokrasi"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konstitusi sebagai Permakluman Tertinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam konteks institusi negara, konstitusi bermakna <strong>permakluman tertinggi yang menetapkan pemegang kedaulatan, struktur negara, bentuk pemerintahan, kekuasaan lembaga negara, dan hak-hak rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena konstitusi adalah deklarasi agung kedaulatan rakyat yang mendirikan bangunan negara dan menetapkan aturan main hubungan kekuasaan. Konstitusi menjadi dasar ontologis bagi berdirinya suatu bangsa merdeka. Tidak ada dokumen institusional lain yang menandingi wibawa konstitusi.</p><p>Jawaban tepat: <strong>B. Permakluman tertinggi yang menetapkan struktur negara dan kedaulatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Saran sarjana hukum adalah doktrin akademis dan tidak berkedudukan sebagai permakluman negara.</li><li><strong>C</strong>: Rencana kerja tahunan adalah dokumen perencanaan anggaran operasional kementerian.</li><li><strong>D</strong>: Pembukuan kas perbendaharaan diatur dalam modul akuntansi dan perbendaharaan negara.</li><li><strong>E</strong>: Mutasi jabatan adalah materi manajemen kepegawaian aparatur di bawah undang-undang ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>permakluman tertinggi yang menetapkan • pemegang kedaulatan tertinggi,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan daya ikatnya sebagai hukum dasar tertulis, UUD 1945 secara konstitusional mengikat...", "options": ["Pemerintah pusat saja tanpa mengikat warga negara yang bermukim di daerah", "Para pejabat pimpinan dewan perwakilan tanpa mengikat lembaga peradilan", "Warga negara di perkotaan saja tanpa mengikat aparatur birokrasi perbatasan", "Pemerintah, lembaga negara, lembaga masyarakat, dan setiap warga negara", "Perusahaan badan usaha swasta saja demi kepatuhan pembayaran perpajakan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Daya Ikat Subjektif UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UUD 1945 <strong>mengikat pemerintah, lembaga-lembaga negara, lembaga masyarakat, setiap warga negara Indonesia dimanapun berada, dan setiap penduduk di wilayah RI</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena hukum dasar tertulis memiliki daya ikat universal terhadap seluruh subjek hukum di dalam wilayah kedaulatan negara. Tidak ada satu pun lembaga atau individu yang berada di luar jangkauan norma UUD 1945. Kepatuhan total terhadap UUD 1945 menjadi landasan ketertiban sosial.</p><p>Jawaban tepat: <strong>D. Pemerintah, lembaga negara, lembaga masyarakat, dan setiap warga negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: UUD 1945 mengikat segenap warga baik di pusat maupun daerah tanpa pengecualian.</li><li><strong>B</strong>: Lembaga peradilan terikat secara mutlak untuk menegakkan hukum berdasarkan UUD 1945.</li><li><strong>C</strong>: UUD 1945 mengikat seluruh warga dan aparatur di seluruh teritorial tanah air.</li><li><strong>E</strong>: UUD 1945 mengatur seluruh tatanan hidup berbangsa dan bukan hanya regulasi swasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>1945 mengikat pemerintah, lembaga-lembaga negara • mengikat setiap warga negara Indonesia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Daya ikat UUD 1945 terhadap setiap penduduk yang berada di wilayah Republik Indonesia mengandung arti...", "options": ["Warga negara asing di Indonesia bebas dari kewajiban mematuhi hukum negara", "Tamu diplomatik berwenang mengganti pasal-pasal undang-undang dasar negara", "Penduduk non-warga negara berhak mendirikan partai politik berkuasa", "Aturan konstitusi secara khusus mengikat penduduk pemilik hak pilih pemilu", "Setiap orang yang bermukim di wilayah NKRI wajib tunduk pada aturan konstitusi"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Daya Ikat UUD 1945 terhadap Penduduk</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UUD 1945 juga <strong>mengikat setiap penduduk yang berada di wilayah negara Republik Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena prinsip kedaulatan teritorial menetapkan bahwa siapa pun yang berada di dalam wilayah hukum Indonesia—baik WNI maupun orang asing berstatus penduduk—wajib mematuhi hukum yang berlaku. Ketertiban umum tidak dapat ditegakkan bila ada kelompok yang kebal aturan konstitusi.</p><p>Jawaban tepat: <strong>E. Setiap orang yang bermukim di wilayah NKRI wajib tunduk pada aturan konstitusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: WNA wajib patuh pada hukum dan peraturan perundang-undangan Indonesia selama berada di NKRI.</li><li><strong>B</strong>: Korps diplomatik tunduk pada konvensi wina dan sama sekali dilarang mencampuri hukum domestik.</li><li><strong>C</strong>: Hak mendirikan partai politik dibatasi hanya bagi warga negara Indonesia sesuai undang-undang.</li><li><strong>D</strong>: Konstitusi mengikat seluruh manusia yang berada di wilayah hukum, termasuk yang belum berhak pilih.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mengikat setiap penduduk yang • berada di wilayah negara Republik Indonesia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Perbedaan mendasar antara Undang-Undang Dasar dengan undang-undang biasa adalah bahwa UUD...", "options": ["Merupakan hukum dasar yang menjadi sumber dan landasan bagi hukum biasa", "Dapat dibatalkan sewaktu-waktu melalui keputusan menteri koordinator", "Bersifat limitatif memuat sanksi denda tindak pidana pelanggaran ringan", "Disusun dan disahkan secara mandiri oleh asosiasi advokat pengadilan", "Berlaku efektif apabila disetujui oleh seluruh kepala daerah provinsi"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perbedaan Hukum Biasa dan Hukum Dasar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Undang-Undang Dasar bukanlah hukum biasa, melainkan <strong>hukum dasar tertulis yang menjadi sumber dari keseluruhan produk hukum di Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena hukum biasa (seperti UU, PP) dibentuk untuk menjabarkan hal-hal operasional tertentu, sedangkan hukum dasar memuat prinsip-prinsip fundamental bernegara yang menjadi induk dari seluruh peraturan perundang-undangan. Hukum biasa tidak boleh menyimpang dari hukum dasar.</p><p>Jawaban tepat: <strong>A. Merupakan hukum dasar yang menjadi sumber dan landasan bagi hukum biasa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keputusan menteri berada jauh di bawah UUD dan tidak dapat membatalkan konstitusi.</li><li><strong>C</strong>: Ketentuan pidana diatur dalam undang-undang biasa, bukan muatan pokok teks konstitusi dasar.</li><li><strong>D</strong>: UUD 1945 disusun oleh pembentuk negara dan diubah oleh MPR, bukan organisasi advokat.</li><li><strong>E</strong>: Keberlakuan konstitusi bersifat mutlak nasional tanpa menunggu persetujuan kepala daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Undang-Undang Dasar bukanlah hukum biasa, melainkan • dasar, yaitu hukum dasar yang tertulis</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Konsekuensi asas hierarki hukum terhadap pembentukan Peraturan Pemerintah dan Perpres adalah...", "options": ["Bebas memuat materi yang bertentangan dengan undang-undang", "Wajib berlandaskan dan bersumber pada peraturan perundangan yang lebih tinggi", "Dapat mengesampingkan pasal hak asasi demi percepatan modal", "Ditetapkan secara tertutup tanpa perlu diundangkan dalam lembaran berita negara", "Dikecualikan dari pengujian materiil oleh lembaga peradilan mahkamah agung"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Produk Hukum Bersumber pada Norma Lebih Tinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setiap produk hukum seperti undang-undang, peraturan pemerintah, peraturan presiden <strong>haruslah berlandaskan dan bersumber pada peraturan yang lebih tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena tata urutan peraturan perundang-undangan menganut asas subordinasi normatif. Peraturan yang lebih rendah berfungsi menjalankan peraturan yang lebih tinggi dan tidak boleh membuat norma baru yang melampaui atau bertentangan dengan undang-undang di atasnya. Asas ini menjaga keteraturan hukum.</p><p>Jawaban tepat: <strong>B. Wajib berlandaskan dan bersumber pada peraturan perundangan yang lebih tinggi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Peraturan pemerintah yang bertentangan dengan UU batal demi hukum melalui uji materiil.</li><li><strong>C</strong>: Hak asasi manusia dijamin konstitusi dan tidak boleh dilanggar oleh peraturan turunan.</li><li><strong>D</strong>: Setiap peraturan perundang-undangan wajib diundangkan dalam Lembaran Negara agar berkekuatan mengikat.</li><li><strong>E</strong>: Mahkamah Agung berwenang penuh menguji materiil peraturan di bawah UU terhadap UU.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>produk hukum seperti undang-undang, peraturan pemerintah, • haruslah berlandaskan dan bersumber pada peraturan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 2 UU 12/2011 jo UU 13/2022, muara dan sumber dari segala sumber hukum negara adalah...", "options": ["Hukum perdata internasional peninggalan masa kolonial barat", "Ketetapan majelis arbitrase perserikatan bangsa-bangsa dunia", "Pancasila sebagai dasar falsafah dan ideologi negara Indonesia", "Perjanjian bilateral pinjaman dana moneter luar negeri", "Instruksi khusus pimpinan kamar dagang dan industri kawasan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pancasila Sumber Segala Sumber Hukum Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Muaranya adalah <strong>Pancasila sebagai sumber dari segala sumber hukum negara (Pasal 2 UU No. 12 Tahun 2011 jo UU No. 13 Tahun 2022)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Pancasila berkedudukan sebagai cita hukum (rechtsidee) tertinggi yang menjiwai seluruh peraturan perundang-undangan di Indonesia. Seluruh materi muatan undang-undang dan peraturan daerah tidak boleh menyimpang dari nilai ketuhanan, kemanusiaan, persatuan, kerakyatan, dan keadilan sosial. Pancasila adalah pedoman moral dan filosofis bangsa.</p><p>Jawaban tepat: <strong>C. Pancasila sebagai dasar falsafah dan ideologi negara Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hukum kolonial sedang didekolonisasi dan bukan sumber segala sumber hukum negara.</li><li><strong>B</strong>: Arbitrase internasional menangani sengketa perdata niaga dan bukan cita hukum negara.</li><li><strong>D</strong>: Perjanjian utang adalah perikatan finansial yang tunduk pada hukum, bukan pembentuk hukum.</li><li><strong>E</strong>: Asosiasi kamar dagang adalah pelaku usaha dan bukan sumber pembentukan tata hukum nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sebagai sumber dari segala sumber hukum negara • Pasal 2 UU No.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Fungsi UUD 1945 sebagai alat kontrol (control function) dalam praktik hukum berarti mengontrol...", "options": ["Peredaran arus mata uang asing di pasar modal perbankan nasional", "Kelayakan pengoperasian armada kapal angkutan niaga antarpulau", "Taraf kepatuhan pembayaran pajak kendaraan bermotor dinas daerah", "Kesesuaian norma hukum yang lebih rendah terhadap ketentuan UUD 1945", "Jumlah penerimaan peserta didik baru pada sekolah kedinasan negara"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi UUD 1945 sebagai Alat Kontrol</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UUD 1945 berfungsi sebagai <strong>alat kontrol, dalam pengertian mengontrol apakah norma hukum yang lebih rendah sesuai atau tidak dengan ketentuan UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena fungsi kontrol konstitusi memastikan bahwa tidak ada undang-undang atau peraturan eksekutif yang melanggar hak-hak rakyat atau melampaui kewenangan yang digariskan UUD 1945. Bila ditemukan pertentangan, undang-undang tersebut dapat diuji dan dibatalkan oleh Mahkamah Konstitusi. Ini menjaga kemurnian konstitusi.</p><p>Jawaban tepat: <strong>D. Kesesuaian norma hukum yang lebih rendah terhadap ketentuan UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Peredaran valuta asing dikontrol oleh Bank Indonesia dan Otoritas Jasa Keuangan.</li><li><strong>B</strong>: Kelaikan kapal laut dikontrol oleh kesyahbandaran kementerian perhubungan.</li><li><strong>C</strong>: Pajak kendaraan bermotor diawasi oleh dinas pendapatan daerah dan kepolisian.</li><li><strong>E</strong>: Penerimaan sekolah kedinasan diatur oleh panitia seleksi nasional kementerian PANRB.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>UUD 1945 juga mempunyai fungsi sebagai alat kontrol • apakah norma hukum yang lebih</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Undang-undang yang mengatur tata cara pembentukan peraturan perundang-undangan di Indonesia adalah...", "options": ["UU Nomor 17 Tahun 2003 tentang Tata Cara Pengelolaan Keuangan Negara", "UU Nomor 15 Tahun 2004 tentang Tata Cara Pemeriksaan Keuangan Negara", "UU Nomor 30 Tahun 2014 tentang Pelaksanaan Administrasi Pemerintahan", "UU Nomor 1 Tahun 2004 tentang Tata Tertib Perbendaharaan Negara", "UU Nomor 12 Tahun 2011 juncto UU Nomor 13 Tahun 2022"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Regulasi Pembentukan Peraturan Perundang-Undangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembentukan peraturan perundang-undangan diatur dalam <strong>Undang-Undang Nomor 12 Tahun 2011 sebagaimana telah diubah dengan Undang-Undang Nomor 13 Tahun 2022</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena UU 12/2011 jo UU 13/2022 merupakan payung hukum yang mengatur tahapan perencanaan, penyusunan, pembahasan, pengesahan, dan pengundangan seluruh peraturan perundang-undangan di Indonesia. Undang-undang ini juga mengatur jenis dan hierarki peraturan perundang-undangan secara baku.</p><p>Jawaban tepat: <strong>E. UU Nomor 12 Tahun 2011 juncto UU Nomor 13 Tahun 2022</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: UU 17/2003 mengatur pengelolaan keuangan negara dan penyusunan APBN/APBD.</li><li><strong>B</strong>: UU 15/2004 mengatur pemeriksaan pengelolaan dan tanggung jawab keuangan oleh BPK.</li><li><strong>C</strong>: UU 30/2014 mengatur keputusan dan tindakan administratif pejabat birokrasi pemerintahan.</li><li><strong>D</strong>: UU 1/2004 mengatur perbendaharaan negara dan tata kelola barang milik negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Nomor 13 Tahun 2022 • Undang-Undang Nomor 12 Tahun 2011</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kedudukan UUD 1945 sebagai sumber hukum tertinggi mengandung konsekuensi bahwa...", "options": ["Seluruh peraturan perundang-undangan di bawahnya harus bersumber padanya", "Presiden berwenang menunda putusan mahkamah dalam perselisihan hukum", "Peraturan menteri dapat merevisi bunyi pasal konstitusi tanpa persetujuan", "Ketetapan dewan parlemen dapat berdiri sendiri tanpa merujuk pada konstitusi", "Setiap pemerintah daerah bebas membuat undang-undang dasar provinsinya"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan UUD 1945 sebagai Sumber Hukum Tertinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebagai hukum dasar, <strong>UUD 1945 merupakan sumber hukum tertinggi dari keseluruhan produk hukum di Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kedudukan UUD 1945 sebagai puncak piramida hukum mengharuskan seluruh norma di bawahnya bersumber, berakar, dan tidak boleh bertentangan dengannya. Seluruh peraturan perundang-undangan merupakan derivasi normatif dari UUD 1945. Ini menjamin kesatuan sistem hukum nasional.</p><p>Jawaban tepat: <strong>A. Seluruh peraturan perundang-undangan di bawahnya harus bersumber padanya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Presiden wajib mematuhi putusan peradilan yang berkekuatan hukum tetap (inkracht).</li><li><strong>C</strong>: Peraturan menteri tidak memiliki derajat hukum untuk mengubah materi konstitusi UUD.</li><li><strong>D</strong>: Ketetapan dewan tunduk pada konstitusi dan tidak boleh berdiri sendiri secara bebas.</li><li><strong>E</strong>: Pemerintah daerah tidak memiliki kewenangan membentuk konstitusi terpisah di NKRI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sebagai hukum dasar, UUD 1945 merupakan • hukum tertinggi dari keseluruhan produk hukum</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Karakteristik norma-norma yang terkandung dalam UUD 1945 bersifat mengikat karena...", "options": ["Membentuk kelompok kedaerahan eksklusif di lingkungan kantor", "Merupakan aturan dasar yang wajib ditaati dan dilaksanakan semua komponen", "Bersifat anjuran sukarela yang pelaksanaannya diserahkan pada individu", "Berlaku efektif apabila didukung oleh konsensus seluruh angkatan bersenjata", "Dapat dikesampingkan apabila bertentangan dengan kepentingan partai politik"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Norma UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebagai hukum dasar, UUD 1945 <strong>berisi norma-norma dan aturan-aturan yang harus ditaati dan dilaksanakan oleh semua komponen</strong> bangsa.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena norma konstitusi memiliki sifat imperatif (memaksa) dan mengikat secara hukum bagi seluruh penyelenggara negara dan warga negara. Pelanggaran terhadap norma UUD 1945 merupakan pelanggaran konstitusional (unconstitutional action) yang dapat dibatalkan atau dijatuhi sanksi hukum. Ketaatan ini menjaga tegaknya negara.</p><p>Jawaban tepat: <strong>B. Merupakan aturan dasar yang wajib ditaati dan dilaksanakan semua komponen</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: UUD 1945 disahkan secara formal oleh badan pembentuk konstitusi resmi negara.</li><li><strong>C</strong>: Norma konstitusi bersifat perintah hukum mengikat, bukan sekadar anjuran sukarela.</li><li><strong>D</strong>: Kepatuhan konstitusi bersandar pada legitimasi hukum kedaulatan rakyat, bukan militeristik.</li><li><strong>E</strong>: Kepentingan partai politik wajib tunduk pada undang-undang dasar, bukan sebaliknya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>UUD 1945 berisi norma-norma dan aturan-aturan yang harus • dan dilaksanakan oleh semua komponen</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penempatan UUD 1945 pada puncak hierarki perundang-undangan di Indonesia bermakna bahwa...", "options": ["UUD 1945 dapat diselaraskan dengan keputusan kepala kantor perizinan", "UUD 1945 khusus berlaku bagi pejabat eselon struktural kementerian", "UUD 1945 menjadi tolok ukur pengujian keabsahan seluruh peraturan hukum", "UUD 1945 dapat digantikan oleh peraturan pemerintah pada keadaan darurat", "UUD 1945 tidak memerlukan peraturan turunan dalam penerapannya di daerah"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Piramida Tata Urutan Hukum Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UUD 1945 dalam hierarki peraturan perundangan menempati <strong>kedudukan yang tertinggi dan berfungsi mengontrol keabsahan norma hukum di bawahnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena seluruh produk legislasi dan regulasi teknis di Indonesia diuji keabsahannya dengan menggunakan UUD 1945 sebagai batu uji (constitutional review). Jika suatu undang-undang melanggar hak konstitusional warga negara, Mahkamah Konstitusi berwenang menyatakan undang-undang tersebut tidak memiliki kekuatan hukum mengikat.</p><p>Jawaban tepat: <strong>C. UUD 1945 menjadi tolok ukur pengujian keabsahan seluruh peraturan hukum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keputusan perizinan berada pada hierarki bawah dan wajib tunduk pada UUD 1945.</li><li><strong>B</strong>: UUD 1945 mengikat seluruh tumpah darah dan warga negara dari segala lapisan tingkatan.</li><li><strong>D</strong>: Peraturan pemerintah tidak dapat menggantikan kedudukan undang-undang dasar negara.</li><li><strong>E</strong>: UUD 1945 memuat norma dasar yang memerlukan undang-undang organik operasional pelaksana.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>hierarki peraturan perundangan di • menempati kedudukan yang tertinggi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Regulasi historis yang pertama kali mengatur tata urutan peraturan perundangan di Indonesia adalah...", "options": ["Maklumat Presiden Nomor X Tahun 1945 tentang Partai Politik", "Peraturan Pemerintah Pengganti Undang-Undang Nomor 1 Tahun 1960", "Ketetapan MPR No. II/MPR/1978 tentang Pedoman Penghayatan Pancasila", "Undang-Undang Nomor 10 Tahun 2004 tentang Pembentukan Peraturan", "Ketetapan MPRS No. XX/MPRS/1966 tentang Tata Urutan Perundangan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sejarah Regulasi Tata Urutan Perundang-Undangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mengenai tata urutan peraturan perundang-undangan pertama kali diatur dalam <strong>Ketetapan MPRS No. XX/MPRS/1966, kemudian diperbaharui dengan Ketetapan MPR No. III/MPR/2000, dan terakhir diatur dengan UU No. 12 Tahun 2011 jo UU No. 13 Tahun 2022</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Tap MPRS No. XX/MPRS/1966 meletakkan fondasi hierarki tata perundangan resmi pertama pasca-Dekrit 1959. Regulasi ini kemudian disempurnakan pada era reformasi melalui Tap MPR No. III/MPR/2000 sebelum akhirnya diundangkan dalam bentuk undang-undang pembentukan peraturan perundang-undangan.</p><p>Jawaban tepat: <strong>E. Ketetapan MPRS No. XX/MPRS/1966 tentang Tata Urutan Perundangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Maklumat No. X Tahun 1945 mengatur pembentukan sistem banyak partai di awal kemerdekaan.</li><li><strong>B</strong>: Perppu 1/1960 adalah peraturan darurat dan bukan ketetapan tata urutan hukum nasional.</li><li><strong>C</strong>: Tap MPR II/MPR/1978 mengatur penataran P4 era Orde Baru dan telah dicabut.</li><li><strong>D</strong>: UU 10/2004 adalah undang-undang transisi sebelum digantikan oleh UU 12/2011.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Ketetapan MPRS No. XX/MPRS/1966, yang kemudian diperbaharui dengan • MPR No. III/MPR/2000</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 7 UU 12/2011 jo UU 13/2022, urutan hierarki peraturan perundang-undangan yang tepat adalah...", "options": ["UUD 1945, Tap MPR, UU/Perppu, PP, Perpres, Perda Prov, Perda Kab/Kota", "UUD 1945, Perpres, PP, Undang-Undang/Perppu, dan Peraturan Daerah", "Ketetapan MPR, UUD 1945, Undang-Undang, Perpres, Peraturan Pemerintah, dan Perda", "Pancasila, UUD 1945, Peraturan Pemerintah, Undang-Undang, Perpres, dan Perda", "UUD 1945, Undang-Undang, Ketetapan MPR, Perpres, Peraturan Menteri, dan Perda"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hierarki Peraturan Perundang-Undangan Pasal 7</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hierarki peraturan perundang-undangan berdasarkan Pasal 7 adalah: <strong>1. UUD 1945; 2. Ketetapan MPR; 3. UU/Perppu; 4. PP; 5. Perpres; 6. Perda Provinsi; 7. Perda Kabupaten/Kota</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena urutan 7 tingkatan tersebut bersifat baku dan mencerminkan kekuatan hukum masing-masing jenis peraturan dari puncak tertinggi hingga tingkat daerah. Ketetapan MPR berada tepat di bawah UUD 1945, diikuti oleh UU/Perppu dan peraturan pelaksana lainnya secara berjenjang.</p><p>Jawaban tepat: <strong>A. UUD 1945, Tap MPR, UU/Perppu, PP, Perpres, Perda Prov, Perda Kab/Kota</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Perpres berada di bawah Peraturan Pemerintah dan bukan di atas Undang-Undang.</li><li><strong>C</strong>: Ketetapan MPR berada di bawah UUD 1945, bukan di atas Undang-Undang Dasar.</li><li><strong>D</strong>: Pancasila adalah sumber segala sumber hukum negara, bukan teks hierarki Pasal 7.</li><li><strong>E</strong>: Ketetapan MPR berada di atas UU/Perppu dan Peraturan Menteri tidak masuk nomor hierarki pokok.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pasal 7 diatur mengenai jenis dan hierarki • Peraturan Perundang-undangan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam hierarki Pasal 7 UU 12/2011 jo UU 13/2022, posisi Peraturan Pemerintah Pengganti Undang-Undang (Perppu) adalah...", "options": ["Berada pada tingkatan di bawah Peraturan Pemerintah dan di atas Perpres", "Setingkat dengan Undang-Undang dan berada di atas Peraturan Pemerintah", "Berada di atas Ketetapan MPR karena diterbitkan presiden saat kegentingan", "Setara dengan Peraturan Daerah Provinsi dalam lingkup otonomi wilayah", "Tunduk dan tidak boleh membatalkan ketentuan teknis Peraturan Menteri"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Setara UU dan Perppu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Undang-Undang dan Peraturan Pemerintah Pengganti Undang-Undang (Perppu) <strong>menempati hierarki yang setingkat (urutan ke-3), di atas Peraturan Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Perppu diterbitkan oleh presiden dalam hal ihwal kegentingan yang memaksa untuk mengatasi kekosongan hukum yang setingkat undang-undang. Oleh karena itu, materi muatan Perppu sama dengan materi muatan undang-undang dan memiliki kekuatan hukum yang setara dengan undang-undang.</p><p>Jawaban tepat: <strong>B. Setingkat dengan Undang-Undang dan berada di atas Peraturan Pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perppu berada di atas Peraturan Pemerintah, bukan di bawahnya.</li><li><strong>C</strong>: Ketetapan MPR menempati urutan ke-2, berada di atas tingkat UU/Perppu.</li><li><strong>D</strong>: Perda Provinsi menempati urutan ke-6, berada jauh di bawah tingkat UU/Perppu.</li><li><strong>E</strong>: Perppu berkekuatan undang-undang dan dapat mencabut atau mengesampingkan peraturan menteri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Undang-Undang/Peraturan Pemerintah Pengganti Undang-Undang • Peraturan Pemerintah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Makna dari prinsip 'kekuatan hukum peraturan perundang-undangan sesuai dengan hierarki' adalah...", "options": ["Peraturan baru otomatis membatalkan seluruh undang-undang dasar", "Keputusan kepala desa dapat mengesampingkan peraturan pemerintah", "Peraturan yang lebih tinggi derajatnya mengalahkan yang rendah", "Peraturan daerah berkedudukan tertinggi dalam batas otonominya", "Peraturan menteri dapat mengubah materi undang-undang asalkan disetujui presiden"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Kekuatan Hukum Sesuai Hierarki</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekuatan hukum peraturan perundang-undangan <strong>sesuai dengan hierarkinya</strong>, di mana norma yang lebih rendah tidak boleh bertentangan dengan norma yang lebih tinggi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kekuatan mengikat dan validitas norma hukum bergantung pada posisinya dalam piramida peraturan perundang-undangan. Peraturan pada tingkat lebih tinggi mengesampingkan peraturan pada tingkat lebih rendah (lex superior derogat legi inferiori). Hal ini menjamin tata kepatuhan hukum.</p><p>Jawaban tepat: <strong>C. Peraturan yang lebih tinggi derajatnya mengalahkan yang rendah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Asas undang-undang baru (lex posterior) hanya berlaku untuk peraturan yang setingkat derajadnya.</li><li><strong>B</strong>: Keputusan kepala desa tunduk mutlak pada peraturan perundang-undangan di atasnya.</li><li><strong>D</strong>: Perda tunduk pada peraturan perundang-undangan tingkat nasional dan bukan hukum tertinggi.</li><li><strong>E</strong>: Peraturan menteri tidak memiliki wewenang hukum untuk mengubah isi undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Adapun kekuatan hukum peraturan perundang-undangan • sesuai dengan hierarki</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Peraturan yang diakui keberadaannya di luar 7 tingkatan hierarki Pasal 7 antara lain peraturan yang ditetapkan oleh...", "options": ["Asosiasi pedagang perantara ekspor komoditas hasil bumi daerah", "Pengurus serikat buruh pabrik manufaktur kawasan industri swasta", "Dewan komisaris perusahaan konsorsium penanaman modal asing", "Mahkamah Agung, Mahkamah Konstitusi, BPK, Komisi Yudisial, dan Bank Indonesia", "Konsulat jenderal kantor perwakilan diplomasi negara tetangga"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peraturan Perundang-Undangan Lain di Luar Hierarki Pasal 7</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jenis peraturan selain Pasal 7 mencakup peraturan yang ditetapkan oleh <strong>MPR, DPR, DPD, MA, MK, BPK, KY, Bank Indonesia, menteri, badan/lembaga/komisi setingkat yang dibentuk dengan UU atau pemerintah atas perintah UU</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena lembaga-lembaga negara dan badan independen yang dibentuk oleh konstitusi atau undang-undang memiliki wewenang membentuk peraturan kelembagaan untuk menyelenggarakan tugas operasionalnya. Peraturan tersebut diakui secara sah dalam tata hukum Indonesia.</p><p>Jawaban tepat: <strong>D. Mahkamah Agung, Mahkamah Konstitusi, BPK, Komisi Yudisial, dan Bank Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Asosiasi pedagang adalah entitas perdata dan tidak memiliki wewenang regulasi publik.</li><li><strong>B</strong>: Serikat buruh menetapkan anggaran dasar organisasi dan bukan hukum perundang-undangan.</li><li><strong>C</strong>: Dewan komisaris menerbitkan kebijakan internal korporasi dan bukan peraturan negara.</li><li><strong>E</strong>: Konsulat jenderal terikat pada hukum negaranya sendiri dan hukum internasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Jenis peraturan perundang-undangan selain itu mencakup • peraturan yang ditetapkan oleh</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Peraturan yang ditetapkan oleh menteri atau lembaga diakui dan berkekuatan hukum mengikat dengan syarat...", "options": ["Mendapat persetujuan pemungutan suara terbanyak dari masyarakat pemilih", "Diserahkan pemeriksaannya kepada kantor perwakilan kamar dagang asing", "Diterbitkan dalam waktu kurang dari satu pekan setelah pergantian menteri", "Memuat ketentuan pembebasan biaya retribusi daerah secara menyeluruh", "Diperintahkan oleh peraturan lebih tinggi atau dibentuk berdasar kewenangan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Pengakuan Peraturan Lain</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Peraturan tersebut diakui dan mempunyai kekuatan hukum mengikat <strong>sepanjang diperintahkan oleh peraturan perundang-undangan yang lebih tinggi atau dibentuk berdasarkan kewenangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena asas atribusi dan delegasi wewenang mengharuskan setiap penerbitan peraturan memiliki landasan legal. Peraturan lembaga dapat lahir karena mandat langsung undang-undang/PP (delegasi) atau karena kewenangan atributif dalam urusan pemerintahan yang menjadi lingkup tugasnya.</p><p>Jawaban tepat: <strong>E. Diperintahkan oleh peraturan lebih tinggi atau dibentuk berdasar kewenangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Regulasi menteri tidak memerlukan referendum pemungutan suara warga negara.</li><li><strong>B</strong>: Kamar dagang asing tidak memiliki hak otorisasi terhadap regulasi kementerian negara.</li><li><strong>C</strong>: Waktu penerbitan tidak menjadi syarat legalitas keabsahan produk peraturan perundang-undangan.</li><li><strong>D</strong>: Pembebasan retribusi adalah kebijakan substansi teknis fiskal dan bukan syarat keabsahan hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mempunyai kekuatan hukum mengikat • perundang-undangan yang lebih tinggi atau dibentuk berdasarkan kewenangan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam sistem peraturan perundang-undangan di Indonesia, definisi resmi dari 'Peraturan Menteri' adalah peraturan yang...", "options": ["Ditetapkan menteri berdasarkan materi muatan penyelenggaraan urusan tertentu", "Diterbitkan presiden untuk mendelegasikan seluruh wewenang kepala negara", "Disusun staf ahli kementerian untuk disahkan oleh ketua dewan perwakilan", "Mengatur sanksi hukuman penjara bagi pelanggar jam kerja kantor dinas", "Mengatur penetapan susunan pimpinan partai politik pendukung pemerintah"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Peraturan Menteri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Yang dimaksud dengan 'Peraturan Menteri' adalah <strong>peraturan yang ditetapkan oleh menteri berdasarkan materi muatan dalam rangka penyelenggaraan urusan tertentu dalam pemerintahan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Peraturan Menteri (Permen) merupakan instrumen pengaturan teknis administratif di bidang urusan kementerian masing-masing (seperti keuangan, pendidikan, kesehatan). Permen mengikat jajaran kementerian dan masyarakat sesuai lingkup materi muatan yang diperintahkan.</p><p>Jawaban tepat: <strong>A. Ditetapkan menteri berdasarkan materi muatan penyelenggaraan urusan tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pendelegasian urusan kepala negara dilakukan melalui Peraturan Presiden, bukan Permen.</li><li><strong>C</strong>: Permen disahkan sendiri oleh menteri bersangkutan, bukan oleh ketua dewan perwakilan.</li><li><strong>D</strong>: Sanksi pidana penjara hanya boleh dimuat dalam undang-undang atau perda, bukan permen.</li><li><strong>E</strong>: Kementerian negara bersikap netral dan dilarang mengurus kepengurusan partai politik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Yang dimaksud dengan “Peraturan Menteri” adalah peraturan • menteri berdasarkan materi muatan dalam rangka penyelenggaraan urusan tertentu</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Hubungan hierarki antara Peraturan Daerah Provinsi dengan Perda Kabupaten/Kota adalah...", "options": ["Perda Kabupaten/Kota menempati hierarki lebih tinggi daripada Perda Provinsi", "Perda Provinsi menempati urutan ke-6, berada di atas Perda Kabupaten/Kota", "Kedua peraturan daerah tersebut memiliki kedudukan yang sejajar dan saling uji", "Perda Kabupaten/Kota dapat membatalkan ketentuan materi Perda Provinsi", "Perda Provinsi secara spesifik berlaku bagi aparatur daerah tingkat dua"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perbedaan Hierarki Perda Provinsi dan Perda Kab/Kota</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam hierarki Pasal 7, <strong>Peraturan Daerah Provinsi menempati urutan ke-6, sedangkan Peraturan Daerah Kabupaten/Kota menempati urutan ke-7</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Perda Provinsi mencakup wilayah dan kepentingan yang lebih luas dalam satu provinsi. Oleh karena itu, materi muatan Perda Kabupaten/Kota tidak boleh bertentangan dengan Perda Provinsi yang berada pada tingkat hierarki di atasnya. Kepatuhan bertingkat ini menjaga harmonisasi regulasi daerah.</p><p>Jawaban tepat: <strong>B. Perda Provinsi menempati urutan ke-6, berada di atas Perda Kabupaten/Kota</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perda Kabupaten/Kota menempati hierarki ke-7, berada di bawah Perda Provinsi ke-6.</li><li><strong>C</strong>: Keduanya tidak sejajar; Perda Provinsi berkedudukan lebih tinggi dari Perda Kabupaten/Kota.</li><li><strong>D</strong>: Perda tingkat kabupaten/kota tidak memiliki wewenang hukum membatalkan Perda provinsi.</li><li><strong>E</strong>: Perda Provinsi berlaku bagi seluruh warga dan pemda di wilayah provinsi bersangkutan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peraturan Daerah Provinsi; dan • Peraturan Daerah Kabupaten/ Kota</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penyisipan Ketetapan MPR pada nomor urut ke-2 dalam hierarki UU 12/2011 bermakna bahwa...", "options": ["MPR berwenang kembali menerbitkan Garis-Garis Besar Haluan Negara tahunan", "Seluruh undang-undang yang ada secara otomatis tidak berlaku lagi di negara", "Ketetapan MPRS/MPR yang masih berlaku diakui berada di bawah UUD 1945", "MPR menjadi lembaga tertinggi negara pemegang kekuasaan mutlak pemerintahan", "Presiden bertanggung jawab dan wajib memberikan mandat kepada ketua MPR"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Ketetapan MPR dalam Hierarki</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketetapan MPR berada pada urutan ke-2 <strong>untuk mewadahi ketetapan MPRS/MPR yang dinyatakan masih berlaku oleh Pasal I Aturan Tambahan UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pencantuman Ketetapan MPR dalam hierarki bertujuan memberikan kepastian hukum bagi ketetapan-ketetapan MPR yang masih mengikat secara hukum (misalnya Tap MPRS XXV/1966). Ketetapan ini berkedudukan di bawah UUD 1945 namun berada di atas undang-undang.</p><p>Jawaban tepat: <strong>C. Ketetapan MPRS/MPR yang masih berlaku diakui berada di bawah UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: MPR pasca-amandemen tidak lagi berwenang menyusun GBHN bagi presiden terpilih.</li><li><strong>B</strong>: Undang-undang tetap berlaku sah pada hierarki ke-3 di bawah Ketetapan MPR.</li><li><strong>D</strong>: MPR bukan lagi lembaga tertinggi negara melainkan lembaga tinggi yang setara dengan lainnya.</li><li><strong>E</strong>: Presiden bertanggung jawab langsung kepada rakyat dan tidak bertanggung jawab pada MPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Ketetapan Majelis Permusyawaratan Rakyat • Undang-Undang Dasar Negara Republik Indonesia Tahun 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Secara hierarkis, posisi Undang-Undang Dasar 1945 dalam tata urutan perundangan nasional adalah...", "options": ["Menempati urutan ke-3 sejajar dengan undang-undang dan peraturan pemerintah", "Dapat dikesampingkan oleh peraturan presiden dalam keadaan bencana", "Berada di bawah ketetapan majelis permusyawaratan rakyat yang baru", "Menempati urutan ke-1 sebagai dasar hukum tertulis tertinggi di Indonesia", "Secara spesifik mengikat pada lingkungan peradilan mahkamah konstitusi"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Tertinggi UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam tata urutan perundang-undangan Republik Indonesia, <strong>UUD 1945 menempati urutan pertama sebagai hukum dasar tertulis tertinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena UUD 1945 merupakan dasar dan sumber hukum bagi seluruh produk hukum lainnya. Tidak ada satu pun tingkatan peraturan perundang-undangan yang menempati posisi lebih tinggi daripada UUD 1945. Penempatan di nomor urut 1 menegaskan supremasi konstitusi.</p><p>Jawaban tepat: <strong>D. Menempati urutan ke-1 sebagai dasar hukum tertulis tertinggi di Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Urutan ke-3 adalah posisi UU/Perppu, bukan Undang-Undang Dasar 1945.</li><li><strong>B</strong>: Perpres berada pada urutan ke-5 dan tidak dapat mengesampingkan UUD 1945.</li><li><strong>C</strong>: Ketetapan MPR berada di urutan ke-2 di bawah UUD 1945, bukan di atasnya.</li><li><strong>E</strong>: UUD 1945 mengikat seluruh warga negara dan lembaga negara tanpa kecuali.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dimanakah kedudukan UUD 1945 dalam tata urutan perundangan • Republik Indonesia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pengertian pembentukan peraturan 'berdasarkan kewenangan' sebagaimana dijelaskan dalam modul adalah...", "options": ["Penyelenggaraan urusan tertentu pemerintahan sesuai ketentuan peraturan hukum", "Kewenangan diskresi pejabat untuk menerbitkan peraturan di luar urusan dinasnya", "Hak pimpinan satuan kerja untuk membuat aturan peradilan pidana sendiri", "Wewenang kepala daerah menetapkan mata uang baru bagi wilayahnya", "Pemberian kuasa dari dewan perwakilan rakyat kepada partai politik"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Pembentukan Berdasarkan Kewenangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Yang dimaksud dengan 'berdasarkan kewenangan' adalah <strong>penyelenggaraan urusan tertentu pemerintahan sesuai dengan ketentuan peraturan perundang-undangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pembentukan peraturan berdasarkan kewenangan (atribusi) melekat pada mandat kelembagaan suatu instansi untuk mengurus bidang tugasnya secara sah menurut undang-undang. Pejabat tidak boleh membentuk peraturan di luar lingkup kewenangan urusan pemerintahannya.</p><p>Jawaban tepat: <strong>A. Penyelenggaraan urusan tertentu pemerintahan sesuai ketentuan peraturan hukum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Diskresi dibatasi oleh asas-asas umum pemerintahan yang baik dan tidak boleh melanggar undang-undang.</li><li><strong>C</strong>: Peraturan pidana hanya boleh dibuat melalui undang-undang bersama lembaga legislatif.</li><li><strong>D</strong>: Mata uang tunggal rupiah merupakan wewenang absolut negara yang tidak boleh dibuat pemda.</li><li><strong>E</strong>: DPR menjalankan fungsi legislasi negara dan bukan memberikan kuasa pada partai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dimaksud • penyelenggaraan urusan tertentu pemerintahan sesuai dengan ketentuan peraturan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Aturan dasar yang timbul dan terpelihara dalam praktik penyelenggaraan negara meskipun tidak tertulis disebut...", "options": ["Yurisprudensi peradilan perdata sengketa dagang", "Traktat perjanjian perbatasan wilayah laut teritorial", "Konvensi ketatanegaraan yang menjadi hukum dasar tidak tertulis", "Doktrin pendapat ahli hukum tata usaha kementerian", "Piagam kesepakatan damai gencatan senjata militer"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Konvensi Ketatanegaraan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hukum dasar yang tidak tertulis merupakan aturan dasar yang timbul dan terpelihara dalam praktik penyelenggaraan negara yang dikenal dengan nama <strong>'Konvensi'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena konvensi ketatanegaraan (constitutional conventions) adalah kebiasaan berulang dalam ketatanegaraan yang diterima dan dipatuhi oleh para pelaku negara sebagai norma yang mengikat secara etika politik. Konvensi melengkapi kelenturan konstitusi tertulis dalam menghadapi dinamika zaman.</p><p>Jawaban tepat: <strong>C. Konvensi ketatanegaraan yang menjadi hukum dasar tidak tertulis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Yurisprudensi adalah putusan hakim terdahulu yang diikuti hakim sesudahnya dalam peradilan.</li><li><strong>B</strong>: Traktat adalah perjanjian tertulis antarnegara yang tunduk pada hukum internasional.</li><li><strong>D</strong>: Doktrin adalah ajaran atau pendapat para sarjana hukum terkemuka dalam literatur.</li><li><strong>E</strong>: Piagam gencatan senjata adalah perikatan militer untuk menghentikan kontak senjata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Hukum dasar yang tidak tertulis tersebut</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Batasan mutlak keberlakuan konvensi ketatanegaraan dalam sistem hukum Indonesia adalah...", "options": ["Wajib diundangkan dalam lembaran berita resmi setiap awal tahun", "Wajib disertai dengan sanksi kurungan pidana bagi pelanggar etika", "Harus mendapatkan persetujuan tertulis dari dewan pimpinan partai", "Dibatasi masa berlakunya sebatas rentang masa jabatan satu kepala negara", "Tidak boleh bertentangan dengan ketentuan Undang-Undang Dasar 1945"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batasan Konvensi Ketatanegaraan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Meskipun Konvensi merupakan hukum dasar tidak tertulis, <strong>ia tidaklah boleh bertentangan dengan UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena konvensi berkedudukan sebagai pengisi kekosongan dan pelengkap (supplement) hukum tertulis. Konvensi tidak boleh menggantikan atau bertentangan dengan teks eksplisit UUD 1945. Bila terjadi pertentangan antara konvensi dan UUD 1945, maka UUD 1945 yang wajib dimenangkan secara mutlak.</p><p>Jawaban tepat: <strong>E. Tidak boleh bertentangan dengan ketentuan Undang-Undang Dasar 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Konvensi bersifat tidak tertulis sehingga tidak diundangkan dalam Lembaran Negara.</li><li><strong>B</strong>: Sanksi konvensi bersifat politis dan moralitas ketatanegaraan, bukan sanksi pidana kurungan.</li><li><strong>C</strong>: Konvensi tumbuh dari praktik kenegaraan resmi, bukan persetujuan partai politik.</li><li><strong>D</strong>: Konvensi dapat dipelihara melintasi berbagai periode masa jabatan kepresidenan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>juga merupakan hukum dasar (tidak tertulis) • ia tidaklah boleh bertentangan dengan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Fungsi utama konvensi ketatanegaraan dalam praktik bernegara di Indonesia adalah...", "options": ["Mengubah hierarki tata urutan peraturan di luar undang-undang", "Sebagai aturan pelengkap atau pengisi kekosongan hukum dalam praktik", "Menghapuskan fungsi pengawasan lembaga dewan perwakilan rakyat", "Memberikan hak veto kepada presiden terhadap putusan mahkamah", "Mengatur tata cara pembagian laba badan usaha milik negara swasta"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Konvensi Mengisi Kekosongan Hukum</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Konvensi merupakan <strong>aturan pelengkap atau pengisi kekosongan hukum yang timbul dan terpelihara dalam praktik penyelenggaraan ketatanegaraan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena UUD 1945 hanya memuat aturan-aturan pokok secara garis besar, sehingga terdapat celah prosedur atau kebiasaan teknis kenegaraan yang belum tertulis secara eksplisit. Konvensi hadir mengisi kekosongan hukum tersebut secara elegan demi kelancaran operasional tata kelola pemerintahan.</p><p>Jawaban tepat: <strong>B. Sebagai aturan pelengkap atau pengisi kekosongan hukum dalam praktik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Konvensi tidak memiliki wewenang mengubah piramida hierarki undang-undang.</li><li><strong>C</strong>: Fungsi pengawasan DPR diatur dalam konstitusi dan tidak dapat dihapus oleh konvensi.</li><li><strong>D</strong>: Presiden Indonesia tidak memiliki hak veto terhadap putusan lembaga peradilan.</li><li><strong>E</strong>: Pembagian laba BUMN diatur dalam undang-undang perseroan dan rapat umum pemegang saham.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Konvensi merupakan aturan pelengkap atau pengisi kekosongan hukum • Konvensi tidak terdapat dalam UUD 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Contoh konvensi ketatanegaraan yang terpelihara secara rutin dalam praktik ketatanegaraan Indonesia adalah...", "options": ["Upacara peringatan hari pahlawan di makam pahlawan setiap bulan November", "Pemberian penghargaan tanda jasa bintang mahaputera pada hari ulang tahun", "Penyelenggaraan rapat terbatas kabinet di istana presiden setiap hari rabu", "Pidato kenegaraan Presiden RI setiap 16 Agustus di hadapan sidang DPR", "Pemasangan bendera merah putih di kantor kementerian sepanjang bulan agustus"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Contoh Nyata Konvensi Ketatanegaraan di Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Contoh konvensi yang dipelihara adalah <strong>setiap tanggal 16 Agustus, Presiden RI menyampaikan pidato kenegaraan di hadapan sidang Dewan Perwakilan Rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena tradisi penyampaian pidato kenegaraan dan penyampaian RUU APBN serta Nota Keuangan pada 16 Agustus tidak diatur secara eksplisit dalam pasal UUD 1945, namun konsisten dilaksanakan sebagai kebiasaan tata negara yang ditaati seluruh lembaga. Ini adalah contoh klasik konvensi Indonesia.</p><p>Jawaban tepat: <strong>D. Pidato kenegaraan Presiden RI setiap 16 Agustus di hadapan sidang DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Upacara hari pahlawan adalah tradisi peringatan nasional, bukan konvensi lembaga tinggi.</li><li><strong>B</strong>: Pemberian tanda jasa diatur dalam undang-undang gelar dan tanda kehormatan negara.</li><li><strong>C</strong>: Jadwal rapat kabinet adalah urusan fleksibilitas kepresidenan dan bukan konvensi UUD.</li><li><strong>E</strong>: Pemasangan bendera diatur dalam UU No. 24 Tahun 2009 tentang Bendera dan Bahasa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>setiap tanggal 16 Agustus, • di hadapan sidang Dewan Perwakilan Rakyat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Hubungan substansial antara Pembukaan dan pasal-pasal UUD 1945 ditegaskan oleh fakta bahwa...", "options": ["Pokok pikiran dalam Pembukaan diciptakan dan dijelmakan dalam pasal-pasal", "Pasal-pasal UUD 1945 dapat membatalkan rumusan empat alinea pembukaan", "Pembukaan UUD 1945 disusun setelah seluruh pembahasan pasal selesai disahkan", "Pasal-pasal UUD 1945 memuat sanksi pidana atas pelanggaran pembukaan", "Pembukaan UUD 1945 dapat diamandemen secara berkala bersama dewan perwakilan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penjelmaan Pokok Pikiran Pembukaan ke Batang Tubuh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembukaan UUD 1945 mempunyai hubungan langsung dengan pasal-pasal UUD 1945 karena <strong>mengandung pokok-pokok pikiran yang diciptakan dan dijelmakan dalam Pasal-pasal UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Pembukaan berkedudukan sebagai asas kerohanian negara (suasana kebatinan) yang kemudian dijabarkan secara rinci ke dalam norma-norma pasal batang tubuh konstitusi. Keduanya merupakan rangkaian kesatuan yang saling menjiwai. Pasal-pasal adalah manifestasi konkret Pembukaan.</p><p>Jawaban tepat: <strong>A. Pokok pikiran dalam Pembukaan diciptakan dan dijelmakan dalam pasal-pasal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pasal-pasal tidak boleh bertentangan atau membatalkan nilai-nilai luhur Pembukaan.</li><li><strong>C</strong>: Rancangan Pembukaan (Piagam Jakarta) dirumuskan lebih awal mendahului pasal-pasal.</li><li><strong>D</strong>: Konstitusi tidak memuat norma hukum pidana langsung melainkan norma ketatanegaraan.</li><li><strong>E</strong>: Terdapat kesepakatan dasar bahwa Pembukaan UUD 1945 tidak dapat diubah (permanen).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pembukaan Undang-Undang Dasar 1945 itu mengandung pokok-pokok pikiran • diciptakan dan dijelmakan dalam Pasal-pasal Undang-Undang Dasar 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Bunyi lengkap Pokok Pikiran Pertama yang terkandung dalam Pembukaan UUD 1945 adalah...", "options": ["Negara hendak mewujudkan keadilan sosial bagi seluruh masyarakat nusantara", "Negara yang berkedaulatan rakyat berdasar kerakyatan permusyawaratan", "Negara melindungi segenap bangsa dan tumpah darah berdasar atas persatuan", "Negara berdasar atas Ketuhanan Yang Maha Esa menurut dasar kemanusiaan", "Negara menjamin kemerdekaan tiap-tiap penduduk memeluk agama dan ibadahnya"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bunyi Pokok Pikiran Pertama Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok pikiran pertama berbunyi: <strong>'Negara ... melindungi segenap bangsa Indonesia dan seluruh tumpah darah Indonesia dengan berdasar atas persatuan dengan mewujudkan keadilan sosial bagi seluruh rakyat Indonesia'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pokok pikiran pertama menegaskan paham negara persatuan (sila ketiga). Negara memegang kewajiban utama melindungi segenap bangsa dan wilayah tumpah darah dari ancaman disintegrasi. Paham ini menolak pemikiran golongan atau perseorangan yang memecah belah bangsa.</p><p>Jawaban tepat: <strong>C. Negara melindungi segenap bangsa dan tumpah darah berdasar atas persatuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Negara hendak mewujudkan keadilan sosial adalah bunyi Pokok Pikiran Kedua.</li><li><strong>B</strong>: Negara yang berkedaulatan rakyat adalah bunyi rumusan Pokok Pikiran Ketiga.</li><li><strong>D</strong>: Negara berdasar Ketuhanan Yang Maha Esa adalah bunyi rumusan Pokok Pikiran Keempat.</li><li><strong>E</strong>: Jaminan kemerdekaan beragama adalah muatan materi Pasal 29 ayat (2) UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pokok pikiran pertama; \"Negara • Indonesia dan seluruh tumpah darah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Konsekuensi dari diterimanya aliran pengertian 'negara persatuan' dalam pokok pikiran pertama adalah...", "options": ["Pemberian hak veto khusus bagi suku yang berpenduduk paling banyak", "Pemisahan hak-hak sipil bagi masyarakat pendatang antarwilayah pulau", "Penyerahan kewenangan militer kepada organisasi kemasyarakatan pemuda", "Kewajiban seluruh warga negara meninggalkan tradisi kebudayaan daerah", "Negara mengatasi segala paham golongan dan mengatasi segala paham perseorangan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Aliran Pengertian Negara Persatuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam pembukaan diterima <strong>aliran pengertian negara persatuan, yaitu negara mengatasi segala paham golongan dan mengatasi segala paham perseorangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena negara persatuan tidak didirikan untuk membela kepentingan kelompok tertentu (baik mayoritas agama, etnis, maupun kaum borjuis modal). Negara berdiri di atas semua golongan untuk memayungi segenap rakyat Indonesia. Asas ini menjadi benteng integrasi nasional.</p><p>Jawaban tepat: <strong>E. Negara mengatasi segala paham golongan dan mengatasi segala paham perseorangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sistem hak veto suku bertentangan dengan prinsip kesetaraan dan kebersamaan bangsa.</li><li><strong>B</strong>: Diskriminasi hak sipil pendatang bertentangan dengan persamaan kedudukan hukum.</li><li><strong>C</strong>: Kewenangan pertahanan militer berada di tangan institusi profesional resmi TNI.</li><li><strong>D</strong>: Kebudayaan daerah dilindungi dan dipelihara kelestariannya oleh negara (Pasal 32).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>diterima aliran pengertian negara persatuan • segala paham golongan, mengatasi segala paham perseorangan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Wujud pengamalan Pokok Pikiran Persatuan oleh aparatur birokrasi pemerintahan adalah...", "options": ["Enggan bertugas lembur saat penanganan kedaruratan bencana nasional", "Mengutamakan kepentingan negara dan rakyat di atas kepentingan golongan", "Mendahulukan penerbitan izin usaha bagi rekan satu kelompok alumni", "Mengalokasikan bantuan sosial berdasarkan kedekatan relasi persaudaraan", "Menyebarluaskan dokumen rahasia instansi kepada pihak pers komersial"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Mengutamakan Kepentingan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyelenggara negara dan setiap warga negara <strong>wajib mengutamakan kepentingan negara di atas kepentingan golongan ataupun perorangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena aparatur sipil negara disumpah untuk setia kepada bangsa dan negara. Dalam pengambilan keputusan pelayanan publik, kepentingan umum masyarakat banyak harus ditempatkan di atas kepentingan pribadi, kelompok bisnis, atau golongan politik. Sikap ini adalah wujud patriotisme birokrasi.</p><p>Jawaban tepat: <strong>B. Mengutamakan kepentingan negara dan rakyat di atas kepentingan golongan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menolak tugas darurat kemanusiaan mencerminkan egoisme dan melanggar sumpah jabatan.</li><li><strong>C</strong>: Memfasilitasi kelompok alumni adalah bentuk nepotisme yang merusak profesionalisme.</li><li><strong>D</strong>: Penyaluran bansos secara diskriminatif melanggar asas keadilan dan kejujuran aparatur.</li><li><strong>E</strong>: Membocorkan rahasia dinas negara melanggar hukum pidana dan loyalitas kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penyelenggara • mengutamakan kepentingan negara di atas kepentingan golongan ataupun</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pernyataan 'Inilah suatu dasar negara yang tidak boleh dilupakan' pada pokok pikiran pertama menegaskan bahwa...", "options": ["Setiap undang-undang wajib diperbarui setiap tahun sesuai tren perdagangan", "Negara harus mengubah bentuk republik menjadi monarki serikat kepulauan", "Pemerintah pusat berhak membatasi penggunaan bahasa daerah di ruang publik", "Negara Indonesia berdiri kokoh manakala keutuhan persatuan senantiasa dijaga", "Pembangunan sarana transportasi wajib dipusatkan di pulau terpadat penduduk"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Urgensi Dasar Negara Persatuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Persatuan adalah <strong>dasar negara yang tidak boleh dilupakan</strong>, karena tanpa persatuan, keutuhan dan keberlanjutan eksistensi bangsa akan runtuh.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena para pendiri bangsa menyadari bahwa Indonesia adalah negara majemuk dengan ribuan pulau dan ratusan suku. Bila persatuan diabaikan, benih perpecahan dan konflik primordial dapat menghancurkan kemerdekaan yang telah ditebus dengan darah para pejuang. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Negara Indonesia berdiri kokoh manakala keutuhan persatuan senantiasa dijaga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Undang-undang dibuat sesuai kebutuhan hukum masyarakat, bukan berganti tanpa arah.</li><li><strong>B</strong>: Bentuk negara kesatuan republik adalah pilihan final yang tidak dapat diubah (Pasal 37).</li><li><strong>C</strong>: Bahasa daerah dihormati dan dipelihara kelestariannya sebagai khazanah budaya bangsa.</li><li><strong>E</strong>: Pembangunan transportasi diamanatkan merata di seluruh nusantara demi konektivitas persatuan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Inilah suatu dasar negara yang tidak • boleh dilupakan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Substansi Pokok Pikiran Kedua yang termaktub dalam Pembukaan UUD 1945 berbunyi...", "options": ["Negara berdasar atas hukum dan kedaulatan peradilan bebas merdeka", "Negara hendak mewujudkan keadilan sosial bagi seluruh rakyat Indonesia", "Negara menjamin kebebasan berniaga bagi para pelaku usaha korporasi", "Negara memprioritaskan pembiayaan belanja aparatur kementerian ibu kota", "Negara mewajibkan pembentukan perserikatan dagang antardaerah pulau"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bunyi Pokok Pikiran Kedua Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok pikiran kedua berbunyi: <strong>'Negara hendak mewujudkan keadilan sosial bagi seluruh rakyat Indonesia'</strong>, yang merupakan pokok pikiran keadilan sosial.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pokok pikiran kedua meletakkan tujuan luhur negara untuk menciptakan tatanan masyarakat yang adil dan makmur lahir batin. Keadilan sosial menuntut negara mengentaskan kemiskinan, memajukan kesejahteraan umum, dan mencegah penumpukan kekayaan pada segelintir orang. Ini adalah perwujudan sila kelima.</p><p>Jawaban tepat: <strong>B. Negara hendak mewujudkan keadilan sosial bagi seluruh rakyat Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Negara hukum merupakan prinsip konstitusional yang diatur dalam Pasal 1 ayat (3).</li><li><strong>C</strong>: Perekonomian nasional berdasar atas asas kekeluargaan, bukan kebebasan pasar liberal.</li><li><strong>D</strong>: Anggaran negara ditujukan untuk kemakmuran rakyat, bukan pengutamaan belanja aparatur.</li><li><strong>E</strong>: Kerja sama antardaerah berorientasi pemerataan kemakmuran, bukan kartel dagang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pokok pikiran kedua, \"Negara hendak mewujudkan • keadilan sosial bagi seluruh</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pokok pikiran keadilan sosial yang hendak diwujudkan oleh negara didasarkan pada landasan...", "options": ["Tuntutan persyaratan administratif dari lembaga perbankan luar negeri", "Instruksi khusus komandan pertahanan keamanan tentara sekutu", "Keinginan menyaingi kemajuan ekonomi bangsa-bangsa di benua eropa", "Kesadaran yang sama untuk menciptakan keadilan sosial dalam masyarakat", "Perjanjian perikatan dagang komersial antarperusahaan multinasional"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Landasan Kesadaran Pokok Pikiran Keadilan Sosial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok pikiran keadilan sosial <strong>didasarkan pada kesadaran yang sama untuk menciptakan keadilan sosial dalam kehidupan masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena keadilan sosial bukan komoditas impor atau tuntutan pihak luar, melainkan kesadaran moral mendalam seluruh bangsa Indonesia yang telah lama menderita di bawah ketidakadilan penjajahan. Seluruh komponen bangsa sepakat menghadirkan keadilan sejati dalam tatanan hidup bersama.</p><p>Jawaban tepat: <strong>D. Kesadaran yang sama untuk menciptakan keadilan sosial dalam masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembangunan keadilan sosial berakar pada Pancasila, bukan syarat kreditor asing.</li><li><strong>B</strong>: Tentara Sekutu tidak memiliki kaitan dengan perumusan dasar keadilan sosial bangsa.</li><li><strong>C</strong>: Tujuan pembangunan adalah kemaslahatan rakyat sendiri, bukan persaingan gengsi bangsa.</li><li><strong>E</strong>: Perikatan bisnis dagang tunduk pada hukum nasional dan bukan pembentuk cita keadilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>didasarkan pada kesadaran yang sama untuk • menciptakan keadilan sosial dalam</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Substansi Pokok Pikiran Ketiga yang terkandung dalam Pembukaan UUD 1945 menegaskan bahwa...", "options": ["Negara berkedaulatan rakyat berdasar kerakyatan permusyawaratan perwakilan", "Kekuasaan tertinggi negara berada di bawah komando panglima militer", "Pemerintahan dijalankan melalui persekutuan raja-raja kepulauan daerah", "Kedaulatan negara diserahkan kepada dewan perwakilan saudagar asing", "Keputusan kebijakan ditentukan oleh pemungutan suara terbanyak pasar modal"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bunyi Pokok Pikiran Ketiga Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok pikiran ketiga ialah: <strong>'Negara yang berkedaulatan rakyat berdasar atas kerakyatan dan permusyawaratan perwakilan'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pokok pikiran ini merupakan perwujudan langsung dari sila keempat Pancasila. Sistem kenegaraan Indonesia dibangun di atas asas kedaulatan rakyat, di mana kekuasaan berasal dari rakyat, dijalankan oleh wakil-wakil rakyat melalui permusyawaratan, dan ditujukan bagi kemaslahatan seluruh rakyat.</p><p>Jawaban tepat: <strong>A. Negara berkedaulatan rakyat berdasar kerakyatan permusyawaratan perwakilan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Militer bertugas pada pertahanan kedaulatan, bukan pemegang kedaulatan negara.</li><li><strong>C</strong>: Sistem persekutuan raja adalah feodalisme masa lampau yang telah ditinggalkan.</li><li><strong>D</strong>: Kedaulatan adalah hak mutlak rakyat Indonesia dan haram diserahkan kepada asing.</li><li><strong>E</strong>: Pasar modal adalah instrumen investasi finansial dan bukan penentu kedaulatan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pokok pikiran ketiga, yang terkandung dalam Pembukaan • ialah \"Negara yang berkedaulatan rakyat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Sistem demokrasi permusyawaratan/perwakilan dipilih para pendiri bangsa karena dinilai...", "options": ["Paling mudah dikendalikan oleh para menteri di kabinet pemerintahan", "Mampu meniadakan seluruh perbedaan pandangan politik secara paksa", "Paling sesuai dengan sifat, kepribadian, dan corak masyarakat Indonesia", "Mempercepat pembagian portofolio kementerian kepada para pemodal", "Merupakan syarat mutlak yang diwajibkan oleh dewan keamanan dunia"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesesuaian Demokrasi Permusyawaratan dengan Sifat Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sistem negara harus berdasar atas kedaulatan rakyat dan permusyawaratan perwakilan karena <strong>aliran ini memang sesuai dengan sifat masyarakat Indonesia</strong> yang menjunjung musyawarah dan gotong royong.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena tradisi rembuk desa, musyawarah adat, dan kekeluargaan telah mengakar kuat dalam peradaban nusantara. Demokrasi Indonesia bukan demokrasi liberal adu kekuatan angka mayoritas, melainkan musyawarah yang dipimpin oleh hikmat kebijaksanaan demi mufakat bersama. Ini adalah jati diri bangsa.</p><p>Jawaban tepat: <strong>C. Paling sesuai dengan sifat, kepribadian, dan corak masyarakat Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sistem perwakilan dibentuk untuk mengawasi eksekutif, bukan demi kemudahan menteri.</li><li><strong>B</strong>: Musyawarah menghargai perbedaan pandangan dan menyatukannya dalam konsensus mufakat.</li><li><strong>D</strong>: Demokrasi permusyawaratan menolak politik transaksional jual beli jabatan kabinet.</li><li><strong>E</strong>: Pilihan sistem tata negara adalah hak kedaulatan internal bangsa Indonesia sendiri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sistem negara yang terbentuk dalam Undang-Undang Dasar harus • berdasarkan atas permusyawaratan/</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Substansi Pokok Pikiran Keempat yang termaktub dalam Pembukaan UUD 1945 menegaskan bahwa...", "options": ["Negara mengutamakan kemakmuran materiil di atas pembinaan mental spiritual", "Kewenangan peradilan agama dihapuskan dan disatukan ke peradilan umum", "Negara memisahkan urusan etika keagamaan dari seluruh kebijakan publik", "Pemerintah secara eksklusif mengakui satu aliran keyakinan dalam birokrasi", "Negara berdasar Ketuhanan YME menurut dasar kemanusiaan yang adil beradab"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bunyi Pokok Pikiran Keempat Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok pikiran keempat adalah: <strong>'Negara berdasarkan Ketuhanan Yang Maha Esa, menurut dasar kemanusiaan yang adil dan beradab'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pokok pikiran ini memadukan nilai vertikal ketuhanan (sila pertama) dan nilai horizontal kemanusiaan (sila kedua). Negara Indonesia adalah negara yang berketuhanan yang mewajibkan segenap aparatur dan rakyat menjunjung tinggi budi pekerti kemanusiaan luhur. Inilah landasan moral konstitusi.</p><p>Jawaban tepat: <strong>E. Negara berdasar Ketuhanan YME menurut dasar kemanusiaan yang adil beradab</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pancasila menghendaki keseimbangan lahir dan batin, bukan materialisme semata.</li><li><strong>B</strong>: Peradilan agama tetap diakui eksistensinya sebagai salah satu lingkungan peradilan konstitusional.</li><li><strong>C</strong>: Indonesia bukan negara sekuler dan senantiasa menjiwai hukum dengan nilai keagamaan.</li><li><strong>D</strong>: Pancasila melindungi kemajemukan agama dan kepercayaan yang sah di Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dasar 1945 adalah \"Negara berdasarkan Ketuhanan Yang Maha Esa • dasar kemanusiaan yang adil dan beradab”</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kewajiban konstitusional yang dibebankan kepada pemerintah dalam Pokok Pikiran Keempat adalah...", "options": ["Menerbitkan instruksi perburuan senjata canggih dari negara-negara sekutu", "Memelihara budi pekerti luhur dan memegang teguh cita-cita moral rakyat", "Memungut pajak penghasilan setinggi-tingginya demi menutup defisit kas", "Membatasi hak kebebasan berserikat bagi pegawai aparatur sipil negara", "Merahasiakan berkas pengeluaran anggaran dinas dari audit badan pemeriksa"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Moral Penyelenggara Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Undang-Undang Dasar harus mewajibkan pemerintah dan penyelenggara negara untuk <strong>memelihara budi pekerti kemanusiaan yang luhur dan memegang teguh cita-cita moral rakyat yang luhur</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena penyelenggaraan pemerintahan tidak boleh kering dari moralitas. Pejabat publik memegang amanah rakyat yang wajib dijalankan dengan integritas tinggi, kejujuran, kerendahan hati, dan keadilan kemanusiaan. Cita-cita moral luhur rakyat menjadi kompas etika kepemimpinan nasional.</p><p>Jawaban tepat: <strong>B. Memelihara budi pekerti luhur dan memegang teguh cita-cita moral rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengadaan persenjataan adalah urusan teknis pertahanan, bukan pokok pikiran moral UUD.</li><li><strong>C</strong>: Pajak diatur secara adil berdasarkan undang-undang, bukan pemungutan semena-mena.</li><li><strong>D</strong>: Hak berserikat dan berkumpul dijamin secara konstitusional dalam Pasal 28 UUD 1945.</li><li><strong>E</strong>: Transparansi anggaran kepada BPK adalah kewajiban konstitusional mutlak pengelola keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemerintah dan lain-lain penyelenggara negara untuk • memelihara budi pekerti</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Hakikat hakiki dari empat pokok pikiran yang terkandung dalam Pembukaan UUD 1945 adalah...", "options": ["Kutipan langsung dari piagam hak asasi manusia perserikatan bangsa", "Rangkuman kesepakatan komisi ekonomi penyerahan kedaulatan 1949", "Doktrin tata kelola monarki parlementer peninggalan persemakmuran", "Pancaran luhur dari kelima sila falsafah dasar negara Pancasila", "Peraturan teknis pembagian kewenangan menteri kabinet pertama"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keempat Pokok Pikiran Pancaran dari Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keempat pokok pikiran tersebut tidak lain adalah <strong>pancaran dari falsafah negara, Pancasila</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Pokok Pikiran I memancarkan Sila III (Persatuan), Pokok Pikiran II memancarkan Sila V (Keadilan Sosial), Pokok Pikiran III memancarkan Sila IV (Kedaulatan Rakyat), dan Pokok Pikiran IV memancarkan Sila I dan II (Ketuhanan dan Kemanusiaan). Dengan demikian, Pembukaan UUD menjiwai dan dijiwai oleh Pancasila.</p><p>Jawaban tepat: <strong>D. Pancaran luhur dari kelima sila falsafah dasar negara Pancasila</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Deklarasi HAM PBB (UDHR) baru diterbitkan pada 1948, tiga tahun setelah UUD 1945 disahkan.</li><li><strong>B</strong>: KMB 1949 terjadi empat tahun kemudian dalam konteks diplomatik penyerahan kedaulatan.</li><li><strong>C</strong>: Indonesia menolak doktrin monarki persemakmuran demi menegakkan republik merdeka.</li><li><strong>E</strong>: Pokok pikiran adalah kaidah fundamental negara, bukan aturan teknis portofolio menteri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keempat pokok pikiran ini tampak bahwa tidak lain • pancaran dari falsafah negara, Pancasila</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Peran cita hukum (rechtsidee) yang bersumber dari Pancasila dalam perumusan pasal-pasal UUD adalah...", "options": ["Menjiwai dan dijelmakan secara konkret ke dalam pasal-pasal UUD 1945", "Sebagai pembenaran tindakan aparat dinas untuk menyita aset warga", "Membatasi hak lembaga peradilan dalam memeriksa sengketa tata usaha", "Menjadikan hukum positif Indonesia identik dengan hukum negara asing", "Mewajibkan perubahan berkala atas dasar negara setiap pergantian presiden"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cita Hukum yang Dijelmakan dalam Batang Tubuh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok-pokok pikiran merupakan cita-cita nasional dan cita hukum yang <strong>dijelmakan dalam Pasal-pasal UUD 1945, dan cita hukum UUD 1945 bersumber serta dijiwai oleh falsafah Pancasila</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena cita hukum Pancasila menjadi pemandu materiil bagi penyusunan norma-norma konstitusi. Setiap pasal yang mengatur hak warga, kekuasaan presiden, lembaga perwakilan, perekonomian, hingga pendidikan harus mencerminkan jiwa Pancasila. Inilah fungsi direktif Pancasila terhadap hukum.</p><p>Jawaban tepat: <strong>A. Menjiwai dan dijelmakan secara konkret ke dalam pasal-pasal UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penyitaan sewenang-wenang dilarang oleh hukum dan bertentangan dengan keadilan.</li><li><strong>C</strong>: Peradilan TUN bertugas mengontrol keputusan pejabat dan dijamin oleh undang-undang.</li><li><strong>D</strong>: Hukum nasional berkarakter kepribadian bangsa sendiri dan bukan duplikasi hukum asing.</li><li><strong>E</strong>: Dasar negara Pancasila berkedudukan permanen dan tidak boleh diubah oleh presiden.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>cita-cita nasional dan cita hukum kita • dijelmakan dalam Pasal-pasal UUD 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Arti hakiki fungsi Pancasila sebagai dasar negara dalam konstruksi konstitusi adalah...", "options": ["Pancasila sebagai lambang dekorasi seremonial yang dipajang di gedung pertemuan", "Pancasila sebagai pedoman perundingan pinjaman dana pembangunan luar negeri", "Pancasila sebagai asas kerohanian yang menjiwai seluruh hukum dan tata negara", "Pancasila sebagai instrumen pembatasan hak berorganisasi bagi masyarakat sipil", "Pancasila sebagai dokumen politik yang masa berlakunya dapat ditinjau ulang"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Pancasila sebagai Dasar Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Fungsi Pancasila sebagai dasar negara berarti <strong>Pancasila menjadi sumber kerohanian dan cita hukum yang menjiwai konstitusi dan seluruh tatanan hukum Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena sebagai dasar negara (fundamental norm), Pancasila menjadi dasar keabsahan, arah tujuan, dan standar moral bagi berdirinya Negara Kesatuan Republik Indonesia. Tanpa Pancasila, bangunan ketatanegaraan kehilangan pijakan filosofisnya. Seluruh hukum positif wajib mengacu kepadanya.</p><p>Jawaban tepat: <strong>C. Pancasila sebagai asas kerohanian yang menjiwai seluruh hukum dan tata negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pancasila bukan sekadar pajangan dinding formalitas, melainkan norma hukum operasional.</li><li><strong>B</strong>: Hubungan ekonomi luar negeri tunduk pada kemaslahatan nasional berdasar Pancasila.</li><li><strong>D</strong>: Pancasila menjunjung tinggi hak asasi warga negara, termasuk kemerdekaan berserikat.</li><li><strong>E</strong>: Pancasila adalah pilihan final bangsa berdaulat yang berlaku abadi sepanjang masa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>arti fungsi Pancasila sebagai • dasar negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Hubungan langsung antara Pembukaan dengan pasal-pasal UUD 1945 bermakna bahwa...", "options": ["Pasal-pasal UUD 1945 dapat membatalkan rumusan pembukaan sewaktu-waktu", "Pembukaan UUD 1945 baru berkekuatan bila disahkan berulang oleh dewan", "Pembukaan UUD 1945 tidak memiliki nilai hukum mengikat bagi warga negara", "Pasal-pasal UUD 1945 disusun terpisah tanpa mengacu pada dasar pembukaan", "Pasal-pasal UUD 1945 merupakan penjabaran normatif pokok pikiran pembukaan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Langsung Pembukaan dan Batang Tubuh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembukaan mempunyai hubungan langsung dengan pasal-pasal UUD 1945 karena <strong>pasal-pasal merupakan penjabaran dan penjelmaan lebih lanjut dari pokok-pokok pikiran Pembukaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Pembukaan UUD 1945 memuat norma fundamental negara (staatsfundamentalnorm) yang kemudian dijabarkan menjadi pasal-pasal aturan tata negara (staatsgrundgesetz). Keduanya membentuk satu kebulatan konstitusi yang tak terpisahkan. Pasal-pasal mengoperasionalkan cita-cita Pembukaan.</p><p>Jawaban tepat: <strong>E. Pasal-pasal UUD 1945 merupakan penjabaran normatif pokok pikiran pembukaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pasal-pasal tidak boleh bertentangan atau membatalkan norma dasar Pembukaan UUD.</li><li><strong>B</strong>: Pembukaan disahkan PPKI secara permanen dan tidak memerlukan persetujuan ulang parlemen.</li><li><strong>C</strong>: Pembukaan UUD 1945 berkekuatan hukum tertinggi dan mengikat mutlak seluruh bangsa.</li><li><strong>D</strong>: Pasal-pasal disusun dengan mengacu penuh pada amanat luhur pokok pikiran Pembukaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 42) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pembukaan Undang-Undang Dasar 1945 • langsung dengan Pasal-pasal Undang-Undang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Permasalahan paling pokok yang harus disepakati saat mendirikan suatu negara merdeka adalah...", "options": ["Menentukan warna seragam dinas harian pegawai kementerian lembaga", "Menetapkan besaran gaji awal untuk jabatan menteri kabinet pertama", "Menentukan bentuk negara yang dikehendaki untuk didirikan bersama", "Memilih lokasi kantor cabang perwakilan kedutaan besar di luar negeri", "Membeli persenjataan laras panjang buatan pabrik industri pertahanan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Masalah Pokok Pendirian Suatu Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Permasalahan yang paling pokok di dalam mendirikan suatu negara adalah <strong>bagaimanakah bentuk negara yang dikehendaki untuk didirikan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena bentuk negara merupakan fondasi arsitektur kekuasaan yang menentukan sistem pemerintahan, hubungan pusat dan daerah, kepala negara, serta susunan kabinet. Kesepakatan atas bentuk negara menjadi syarat awal penataan seluruh aspek ketatanegaraan selanjutnya. Hal ini mendasari pilihan para pendiri bangsa.</p><p>Jawaban tepat: <strong>C. Menentukan bentuk negara yang dikehendaki untuk didirikan bersama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Warna seragam dinas adalah detail teknis administratif yang tidak mendasar.</li><li><strong>B</strong>: Penetapan gaji pejabat diatur kemudian setelah struktur birokrasi terbentuk resmi.</li><li><strong>D</strong>: Penetapan pos kedutaan luar negeri merupakan kebijakan diplomasi internasional lanjutan.</li><li><strong>E</strong>: Pengadaan persenjataan adalah urusan teknis pertahanan pascakemerdekaan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Permasalahan yang paling pokok di dalam mendirikan suatu negara adalah • bagaimanakah bentuk negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan contoh perbandingan modul, bentuk negara Amerika Serikat dan Inggris berturut-turut adalah...", "options": ["Republik kesatuan dan persemakmuran distrik kepulauan", "Negara kota demokrasi dan keamiran protektorat dagang", "Konfederasi otonom dan kesultanan militer maritim", "Uni personal bangsawan dan kekaisaran terpimpin", "Negara serikat (federal) dan monarki (kerajaan)"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Contoh Bentuk Negara di Dunia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Contoh bentuk negara di dunia antara lain: <strong>Amerika Serikat berbentuk negara serikat (federal) yang terdiri dari negara-negara bagian, Inggris berbentuk monarki (kerajaan), dan Filipina berbentuk republik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena AS mengadopsi federasi di mana negara-negara bagian melimpahkan sebagian kedaulatan kepada pemerintah federal, sedangkan Inggris mempertahankan monarki konstitusional dengan raja/ratu sebagai kepala negara. Perbandingan ini memperjelas pemahaman para pendiri bangsa saat menentukan bentuk NKRI.</p><p>Jawaban tepat: <strong>E. Negara serikat (federal) dan monarki (kerajaan)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: AS bukan negara kesatuan dan Inggris bukan persemakmuran distrik semata.</li><li><strong>B</strong>: Negara kota adalah polis kuno dan keamiran protektorat adalah bentuk wilayah subordinat.</li><li><strong>C</strong>: Konfederasi adalah gabungan longgar dan kesultanan militer bukan bentuk negara Inggris.</li><li><strong>D</strong>: Uni personal adalah bentuk gabungan masa lalu dan bukan bentuk negara AS modern.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Amerika Serikat yang berbentuk negara serikat yang terdiri dari beberapa • Inggris yang berbentuk monarkhi (kerajaan)</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam modul pembanding bentuk negara kawasan regional Asia Tenggara, Filipina dicontohkan berbentuk...", "options": ["Monarki absolut di bawah kekuasaan mutlak seorang sultan", "Republik dengan kepala negara dipimpin oleh seorang presiden", "Federasi serikat yang terbagi dalam persekutuan pulau pulau", "Keemiran teokrasi di bawah arahan majelis ulama agung", "Negara protektorat militer bentukan pakta pertahanan sekutu"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Negara Filipina dalam Perbandingan Modul</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam kajian perbandingan bentuk negara, <strong>Filipina dicontohkan sebagai negara yang berbentuk republik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Filipina menganut sistem pemerintahan presidensial republik di mana kepala negara dan pemerintahan dipimpin oleh presiden terpilih. Contoh ini dihadirkan dalam teks modul untuk melengkapi variasi bentuk negara di dunia (selain serikat AS dan monarki Inggris).</p><p>Jawaban tepat: <strong>B. Republik dengan kepala negara dipimpin oleh seorang presiden</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Monarki absolut tidak dianut oleh Filipina melainkan beberapa kesultanan teluk.</li><li><strong>C</strong>: Filipina menganut negara kesatuan berbentuk republik, bukan negara serikat federal.</li><li><strong>D</strong>: Keemiran teokrasi adalah bentuk negara di Timur Tengah, bukan di Asia Tenggara.</li><li><strong>E</strong>: Filipina adalah negara berdaulat merdeka dan bukan negara protektorat asing.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Filipina yang • dan lain-lainnya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pilihan bentuk negara kesatuan yang ditetapkan para pendiri bangsa didasari oleh kehendak...", "options": ["Memudahkan mobilisasi paksa tenaga kerja untuk kepentingan industri", "Memusatkan seluruh kepemilikan aset tanah ke dalam perbendaharaan", "Menghapuskan eksistensi suku-suku daerah demi penyeragaman budaya", "Membentuk negara yang bersatu dalam bingkai pokok pikiran persatuan", "Mewajibkan pendirian pangkalan militer pertahanan di setiap kecamatan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kehendak Awal Pendiri Bangsa atas Negara Kesatuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Para pendiri bangsa (*founding fathers*) sejak semula menghendaki terbentuknya <strong>suatu negara kesatuan, negara yang bersatu dalam bingkai Negara Kesatuan Republik Indonesia (pokok pikiran persatuan)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kesadaran akan nasib bersama dan penderitaan di bawah penjajahan melahirkan cita-cita kebangsaan untuk bersatu padu dalam satu wadah negara merdeka. Bentuk kesatuan dipilih untuk mencegah politik devide et impera dan memperkokoh solidaritas kebangsaan seluruh rakyat nusantara.</p><p>Jawaban tepat: <strong>D. Membentuk negara yang bersatu dalam bingkai pokok pikiran persatuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Indonesia merdeka menghapuskan kerja paksa (romusha) peninggalan era pendudukan.</li><li><strong>B</strong>: Tanah air dimiliki bersama oleh rakyat dan dikelola negara untuk kemakmuran umum.</li><li><strong>C</strong>: Pancasila melindungi keragaman budaya daerah dalam semboyan Bhinneka Tunggal Ika.</li><li><strong>E</strong>: Penataan pertahanan diatur secara profesional oleh komando pertahanan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>para founding fathers kita sejak semula • suatu negara kesatuan, negara yang bersatu</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Bunyi eksplisit ketentuan Pasal 1 ayat (1) UUD 1945 mengenai bentuk negara Indonesia adalah...", "options": ["Negara Indonesia ialah Negara Kesatuan, yang berbentuk Republik", "Negara Indonesia ialah Negara Serikat yang berbentuk Persemakmuran", "Negara Indonesia adalah negara hukum yang berkedaulatan rakyat merata", "Negara Indonesia adalah negara kepulauan yang berdaulat secara maritim", "Negara Indonesia berdasar atas Ketuhanan Yang Maha Esa dan Keadilan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penjabaran Bentuk Negara Pasal 1 Ayat (1)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 1 ayat (1) UUD 1945 berbunyi: <strong>'Negara Indonesia ialah Negara Kesatuan, yang berbentuk Republik'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pasal pertama UUD 1945 secara tegas mengunci dua prinsip fundamental tata negara Indonesia: bentuk susunan negaranya adalah kesatuan (unitaris), dan bentuk pemerintahannya adalah republik (res publica). Ketentuan ini menjadi identitas permanen ketatanegaraan Indonesia.</p><p>Jawaban tepat: <strong>A. Negara Indonesia ialah Negara Kesatuan, yang berbentuk Republik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bentuk serikat pernah dipaksakan masa RIS 1949 dan ditolak rakyat kembali ke NKRI.</li><li><strong>C</strong>: Negara hukum adalah bunyi Pasal 1 ayat (3) dan kedaulatan rakyat adalah Pasal 1 ayat (2).</li><li><strong>D</strong>: Negara kepulauan diatur dalam Pasal 25A mengenai batas wilayah Nusantara.</li><li><strong>E</strong>: Negara berdasar Ketuhanan Yang Maha Esa adalah bunyi rumusan Pasal 29 ayat (1) UUD.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>UUD 1945 yang berbunyi: “Negara Indonesia ialah • Negara Kesatuan, yang berbentuk</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Hubungan antara dasar falsafah Pancasila dan pasal-pasal UUD 1945 digambarkan sebagai...", "options": ["Dua dokumen terpisah yang saling berkompetisi memperebutkan wibawa hukum", "Aturan sementara yang dapat dibatalkan sewaktu-waktu oleh presiden", "Rangkaian kesatuan nilai dan norma terpadu yang tak dapat dipisahkan", "Koleksi manuskrip sejarah masa lalu yang tidak mengikat secara operasional", "Pedoman kerja informal yang penerapannya diserahkan kepada kepala dinas"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterpaduan Nilai dan Norma dalam UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UUD 1945 yang memuat dasar falsafah Pancasila dengan pasal-pasal UUD 1945 <strong>merupakan satu kesatuan yang tak dapat dipisahkan, bahkan merupakan rangkaian kesatuan nilai dan norma yang terpadu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Pancasila adalah substansi nilai (substance of values) dan pasal-pasal UUD 1945 adalah bentuk norma hukumnya (legal norms). Keduanya menyatu membentuk satu sistem konstitusi yang utuh dan koheren. Memisahkan Pancasila dari pasal-pasal UUD akan melumpuhkan bangunan tata negara.</p><p>Jawaban tepat: <strong>C. Rangkaian kesatuan nilai dan norma terpadu yang tak dapat dipisahkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pancasila dan UUD 1945 saling menopang secara harmonis, tidak pernah berkompetisi.</li><li><strong>B</strong>: UUD 1945 dan Pancasila berkedudukan permanen dan mengikat secara hukum bagi seluruh warga.</li><li><strong>D</strong>: Keduanya adalah hukum positif tertinggi yang hidup dan wajib diamalkan sehari-hari.</li><li><strong>E</strong>: Kepatuhan konstitusi bersifat imperatif mengikat, bukan pedoman informal kepala dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>merupakan satu kesatuan yang tak dapat • rangkaian kesatuan nilai dan norma yang terpadu</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Secara hierarkis dan materiil, pasal-pasal batang tubuh UUD 1945 berfungsi sebagai...", "options": ["Alat pembatal bagi rumusan pokok-pokok pikiran pembukaan yang ada", "Daftar rincian anggaran belanja operasional satuan kerja kementerian", "Satu-satunya dokumen yang memuat hak imunitas mutlak bagi pimpinan partai", "Pedoman kerja operasional yang dapat dikesampingkan pada keadaan perang", "Perwujudan konkret dari pokok-pokok pikiran yang terkandung dalam Pembukaan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal Batang Tubuh Perwujudan Pokok Pikiran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal-pasal UUD 1945 <strong>merupakan perwujudan dari pokok-pokok pikiran yang terkandung dalam Pembukaan UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pokok-pokok pikiran Pembukaan dijabarkan ke dalam pasal-pasal tata negara (misal persatuan dijabarkan dalam Pasal 1, 35, 36; keadilan sosial dijabarkan dalam Pasal 33, 34; kedaulatan rakyat dijabarkan dalam Pasal 1 ayat 2, 2, 19; ketuhanan dijabarkan dalam Pasal 29).</p><p>Jawaban tepat: <strong>E. Perwujudan konkret dari pokok-pokok pikiran yang terkandung dalam Pembukaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pasal-pasal batang tubuh tunduk pada prinsip Pembukaan dan tidak dapat membatalkannya.</li><li><strong>B</strong>: Rincian anggaran belanja dimuat dalam undang-undang APBN, bukan pasal batang tubuh UUD.</li><li><strong>C</strong>: Konstitusi menjunjung kesetaraan hukum dan menolak hak imunitas mutlak pimpinan partai.</li><li><strong>D</strong>: Konstitusi mengatur hukum tata negara darurat dan tetap berlaku dalam keadaan perang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pasal-pasal Undang-Undang Dasar 1945 merupakan perwujudan • pikiran yang terkandung dalam Pembukaan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pokok-pokok pikiran Persatuan, Keadilan Sosial, Kedaulatan Rakyat, dan Ketuhanan berakar dari...", "options": ["Prinsip-prinsip ekonomi pasar modal perserikatan niaga eropa", "Pancaran luhur dari kelima sila dasar negara falsafah Pancasila", "Peraturan pemerintah masa pendudukan tentara militer belanda", "Ketetapan perundingan meja bundar pengakuan kedaulatan bangsa", "Manifesto partai politik pemenang pemilihan umum pertama"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Pokok Pikiran dan Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok-pokok pikiran Pembukaan UUD 1945 tidak lain <strong>adalah pancaran dari Pancasila</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Pancasila merupakan jiwa (volksgeist) dan sumber nilai dari seluruh pokok pikiran konstitusi. Seluruh prinsip kebangsaan, keadilan, demokrasi permusyawaratan, dan ketuhanan yang beradab berakar langsung pada sila-sila Pancasila. Keterikatan ini bersifat mutlak dan abadi.</p><p>Jawaban tepat: <strong>B. Pancaran luhur dari kelima sila dasar negara falsafah Pancasila</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Prinsip pasar modal kapitalis bertentangan dengan keadilan sosial ekonomi Pancasila.</li><li><strong>C</strong>: Hukum kolonial Belanda bukan sumber nilai moral pokok pikiran konstitusi Indonesia.</li><li><strong>D</strong>: KMB adalah perundingan diplomatik 1949 dan bukan perumus pokok pikiran UUD 1945.</li><li><strong>E</strong>: Manifesto partai adalah produk partisan golongan, bukan jiwa pokok pikiran konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pikiran tersebut tidak lain adalah • pancaran dari Pancasila</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Kewajiban setiap insan Indonesia terhadap kesatuan nilai Pancasila dan pasal-pasal UUD adalah...", "options": ["Mempelajari sejarahnya sebatas saat menghadapi ujian promosi kedinasan", "Membatasi pengamalannya pada saat perayaan hari ulang tahun kemerdekaan", "Menyusun draf perubahan undang-undang dasar mengikuti kecenderungan tren luar", "Mengetahui, memahami, dan menghayati kesatuan semangat nilai tersebut", "Menyerahkan tanggung jawab penegakan konstitusi kepada aparat penegak hukum"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Penghayatan Insan Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesatuan serta semangat yang demikian itulah yang <strong>harus diketahui, dipahami, dan dihayati oleh setiap insan Indonesia</strong> dalam kehidupan bermasyarakat dan bernegara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena konstitusi dan Pancasila bukan sekadar dokumen mati di atas kertas melainkan pedoman hidup kebangsaan. Setiap warga negara wajib menumbuhkan kesadaran konstitusional melalui pemahaman dan penghayatan aktif agar nilai-nilai luhur tersebut hidup dalam praktik peradaban sehari-hari.</p><p>Jawaban tepat: <strong>D. Mengetahui, memahami, dan menghayati kesatuan semangat nilai tersebut</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penghayatan konstitusi berlaku sepanjang hayat, bukan sebatas saat menghadapi ujian dinas.</li><li><strong>B</strong>: Nilai konstitusi diamalkan setiap hari dalam bertindak, bukan setahun sekali saat upacara.</li><li><strong>C</strong>: Konstitusi berakar pada kepribadian bangsa dan tidak boleh goyah oleh tren asing.</li><li><strong>E</strong>: Bela negara dan menjaga konstitusi adalah kewajiban seluruh warga, bukan aparat semata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>yang demikian itulah yang harus • diketahui, dipahami, dan dihayati oleh setiap insan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Perwujudan pokok pikiran adanya negara persatuan dalam pembentukan pemerintah negara adalah...", "options": ["Pemerintah melindungi segenap bangsa dan seluruh tumpah darah Indonesia", "Pemberian hak otonomi berdaulat penuh kepada setiap perkumpulan suku", "Kewajiban seluruh warga negara meninggalkan dialek bahasa daerah asalnya", "Pemusatan seluruh kantor pelayanan publik di pulau Jawa dan Madura", "Pemberian hak kepemilikan tambang emas kepada perusahaan modal asing"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Pokok Pikiran Negara Persatuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pokok pikiran negara persatuan termanifestasi dalam mandat pemerintah untuk <strong>melindungi segenap bangsa Indonesia dan seluruh tumpah darah Indonesia</strong> dalam bingkai NKRI.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena perlindungan terhadap segenap tumpah darah dan bangsa menegaskan bahwa kedaulatan teritorial dan keselamatan seluruh rakyat adalah kesatuan utuh. Negara hadir membela seluruh warganya di seluruh pelosok nusantara tanpa membeda-bedakan suku atau daerah asal.</p><p>Jawaban tepat: <strong>A. Pemerintah melindungi segenap bangsa dan seluruh tumpah darah Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kedaulatan berada di tangan rakyat dan negara kesatuan, bukan otonomi suku berdaulat.</li><li><strong>C</strong>: Bahasa daerah dipelihara kelestariannya dan dihormati oleh negara (Pasal 32).</li><li><strong>D</strong>: Pelayanan publik didorong terdistribusi merata ke seluruh daerah melalui otonomi daerah.</li><li><strong>E</strong>: Kekayaan alam dikuasai negara untuk sebesar-besar kemakmuran rakyat (Pasal 33 ayat 3).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 43) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pokok pikiran • adanya negara persatuan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penetapan Indonesia sebagai negara hukum berdasarkan Pasal 1 ayat (3) UUD 1945 menegaskan prinsip bahwa...", "options": ["Pelaksanaan kewenangan daerah menyimpang dari sinkronisasi aturan nasional", "Keputusan pejabat eksekutif memegang hierarki perundang-undangan tertinggi", "Kebijakan birokrasi ditentukan sepenuhnya oleh diskresi kepala pemerintahan", "Penyelenggaraan kekuasaan negara wajib bertumpu pada supremasi hukum", "Penegakan peraturan perundangan bersumber dari kesepakatan politik temporer"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Negara Hukum Pasal 1 Ayat 3</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 1 ayat (3) UUD 1945 menegaskan bahwa Negara Indonesia adalah negara hukum (<strong>rechtsstaat</strong>), yang bermakna <strong>bukan negara berdasarkan kekuasaan (machstaat)</strong> sehingga seluruh penyelenggaraan kekuasaan wajib tunduk pada hukum.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena prinsip negara hukum menempatkan supremasi hukum sebagai landasan utama operasional penyelenggaraan negara. Hal ini secara tegas membedakan sistem tata negara Indonesia dari negara kekuasaan. Seluruh tindakan aparatur pemerintah dan warga negara dibatasi serta diarahkan oleh koridor hukum formal yang berlaku adil.</p><p>Jawaban tepat: <strong>D. Penyelenggaraan kekuasaan negara wajib bertumpu pada supremasi hukum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kewenangan daerah wajib terikat dalam kerangka sinkronisasi aturan nasional dan konstitusi.</li><li><strong>B</strong>: Kurang tepat karena keputusan pejabat eksekutif tidak memegang hierarki tertinggi melainkan berada di bawah konstitusi dan undang-undang.</li><li><strong>C</strong>: Kurang tepat karena diskresi birokrasi dibatasi oleh asas-asas umum pemerintahan yang baik dan tidak dapat dijalankan secara mutlak.</li><li><strong>E</strong>: Kurang tepat karena sumber penegakan hukum berasal dari norma hukum positif yang baku, bukan sekadar kesepakatan politik temporer.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>bukan negara berdasarkan kekuasaan (machstaat)</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Ketentuan Pasal 1 ayat (2) UUD 1945 yang menyatakan kedaulatan berada di tangan rakyat mengandung makna yuridis bahwa...", "options": ["Penyelenggaraan negara berlandaskan pada sistem konstitusional yang sah", "Segala keputusan peradilan tunduk pada tuntutan massa secara langsung", "Lembaga perwakilan rakyat memiliki kekuasaan mutlak tanpa batas aturan", "Rakyat memegang wewenang eksekutif dalam membentuk produk perundangan", "Kedaulatan diartikan sebagai kebebasan sipil tanpa pembatasan undang-undang"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedaulatan Rakyat Menurut Pasal 1 Ayat 2</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketentuan Pasal 1 ayat (2) UUD 1945 menegaskan kedaulatan berada di tangan rakyat dan dilaksanakan menurut UUD, yang membuktikan bahwa negara Republik Indonesia <strong>menganut sistem konstitusional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kedaulatan rakyat pasca-perubahan UUD 1945 dilaksanakan berdasarkan aturan-aturan yang tertuang dalam konstitusi. Hal ini menegaskan berjalannya demokrasi konstitusional di mana kekuasaan rakyat disalurkan lewat lembaga-lembaga formal negara sesuai pembagian kewenangan konstitusional, bukan melalui anarki atau tirani mayoritas.</p><p>Jawaban tepat: <strong>A. Penyelenggaraan negara berlandaskan pada sistem konstitusional yang sah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena keputusan peradilan bersifat mandiri dan merdeka dari pengaruh atau tekanan massa secara langsung.</li><li><strong>C</strong>: Kurang tepat karena lembaga perwakilan rakyat dibatasi kewenangannya oleh ketentuan pasal-pasal konstitusi.</li><li><strong>D</strong>: Kurang tepat karena kewenangan eksekutif dipegang oleh Presiden dan pembentukan undang-undang dijalankan bersama DPR.</li><li><strong>E</strong>: Kurang tepat karena hak dan kebebasan sipil senantiasa diatur dan dibatasi oleh undang-undang demi ketertiban umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>menganut sistem</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Perubahan rumusan kedaulatan rakyat dalam Pasal 1 ayat (2) UUD 1945 membawa implikasi ketatanegaraan mendasar yaitu...", "options": ["Majelis Permusyawaratan Rakyat memegang monopoli seluruh cabang kekuasaan", "Dewan Perwakilan Daerah memiliki kedudukan lebih tinggi dibanding DPR", "Kekuasaan tertinggi di tangan rakyat dan tidak lagi di tangan MPR", "Lembaga kepresidenan berubah kedudukan menjadi pemegang mandat penuh MPR", "Pemerintah pusat memegang kendali penuh atas putusan peradilan konstitusi"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pergeseran Posisi Kekuasaan Tertinggi Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perubahan Pasal 1 ayat (2) UUD 1945 membawa perubahan konsep ketatanegaraan bahwa kekuasaan negara tertinggi berada di tangan rakyat dan <strong>tidak lagi di tangan MPR</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena sebelum perubahan UUD 1945, MPR berkedudukan sebagai lembaga tertinggi negara pemegang mandat kedaulatan rakyat secara penuh. Setelah perubahan UUD 1945, MPR diposisikan setara dengan lembaga negara lainnya, dan kedaulatan rakyat didistribusikan ke berbagai cabang kekuasaan menurut konstitusi.</p><p>Jawaban tepat: <strong>C. Kekuasaan tertinggi di tangan rakyat dan tidak lagi di tangan MPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena MPR tidak memonopoli kekuasaan negara melainkan menjalankan wewenang spesifik yang diatur konstitusi.</li><li><strong>B</strong>: Kurang tepat karena DPD dan DPR merupakan mitra legislatif yang memiliki kewenangan komplementer, bukan hierarkis bertingkat.</li><li><strong>D</strong>: Kurang tepat karena Presiden tidak lagi berstatus mandataris MPR melainkan dipilih langsung oleh rakyat.</li><li><strong>E</strong>: Kurang tepat karena kekuasaan peradilan konstitusi merdeka dan lepas dari campur tangan pemerintah pusat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tidak lagi di tangan MPR</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 4 ayat (1) UUD 1945, prinsip pokok penyelenggaraan pemerintahan negara Indonesia ditegaskan melalui wewenang bahwa...", "options": ["Perdana menteri memimpin operasional kabinet pemerintahan sehari-hari", "Pemerintah daerah mengatur urusan luar negeri secara mandiri dan otonom", "Parlemen berhak membubarkan jajaran kabinet melalui mosi ketidakpercayaan", "Mahkamah Agung mengendalikan kebijakan administratif kementerian negara", "Presiden Republik Indonesia memegang kekuasaan pemerintahan menurut UUD"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kekuasaan Pemerintahan Negara Berdasarkan Pasal 4 Ayat 1</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan Pasal 4 ayat (1) UUD 1945, Presiden Republik Indonesia memegang kekuasaan pemerintahan menurut UUD, yang menegaskan bahwa <strong>penyelenggara pemerintahan yang dilaksanakan oleh Presiden</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena ketentuan pasal tersebut mencerminkan format sistem presidensial murni. Presiden bertindak sekaligus sebagai kepala negara dan kepala pemerintahan, memegang kewenangan eksekutif tertinggi yang bersumber langsung dari konstitusi.</p><p>Jawaban tepat: <strong>E. Presiden Republik Indonesia memegang kekuasaan pemerintahan menurut UUD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena dalam sistem presidensial Indonesia tidak terdapat jabatan perdana menteri.</li><li><strong>B</strong>: Kurang tepat karena urusan luar negeri merupakan urusan pemerintahan absolut milik pemerintah pusat.</li><li><strong>C</strong>: Kurang tepat karena DPR tidak berwenang menjatuhkan kabinet lewat mosi tidak percaya dalam sistem pemerintahan presidensial.</li><li><strong>D</strong>: Kurang tepat karena MA menjalankan kekuasaan kehakiman dan tidak mencampuri kebijakan administratif kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penyelenggara pemerintahan yang dilaksanakan oleh</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Ketentuan Pasal 7 UUD 1945 mengatur pembatasan kekuasaan eksekutif secara tegas melalui aturan bahwa...", "options": ["Presiden dapat dipilih kembali tanpa batas periode melalui persetujuan parlemen", "Kekuasaan Presiden dibatasi maksimal dua kali masa jabatan lima tahunan", "Masa jabatan eksekutif dapat diperpanjang langsung saat stabilitas terganggu", "Masa bakti kepala negara ditetapkan seumur hidup atas usulan koalisi partai", "Pemilihan Presiden diselenggarakan tiap tiga tahun sekali secara bergantian"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembatasan Masa Jabatan Presiden Berdasarkan Pasal 7</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 7 UUD 1945 menentukan bahwa Presiden dan Wakil Presiden memegang jabatan selama lima tahun dan dapat dipilih kembali dalam jabatan yang sama untuk satu kali masa jabatan, sehingga kekuasaan dibatasi <strong>maksimal hanya dua kali masa jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena sebelum amandemen pasal ini menimbulkan tafsir masa jabatan tanpa batas. Amandemen UUD 1945 memberikan kepastian hukum dan mencegah absolutisme kekuasaan dengan mematok batas masa jabatan maksimal sepuluh tahun (dua periode berturut-turut maupun berselang).</p><p>Jawaban tepat: <strong>B. Kekuasaan Presiden dibatasi maksimal dua kali masa jabatan lima tahunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Presiden dibatasi secara ketat maksimal dua periode masa jabatan.</li><li><strong>C</strong>: Kurang tepat karena perpanjangan masa jabatan tidak dapat diputuskan sepihak melainkan tunduk pada regulasi ketatanegaraan.</li><li><strong>D</strong>: Kurang tepat karena jabatan kepala negara seumur hidup bertentangan dengan prinsip dasar republik dan konstitusi.</li><li><strong>E</strong>: Kurang tepat karena periode masa bakti reguler ditetapkan lima tahun, bukan tiga tahun.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>maksimal hanya dua kali masa jabatan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Apabila DPR menduga adanya tindak pidana berat oleh Presiden, langkah konstitusional yang wajib ditempuh DPR sebelum menyampaikan usul pemakzulan ke MPR adalah...", "options": ["Meminta fatwa hukum Mahkamah Agung terkait pemakzulan Presiden", "Menerbitkan nota pemakzulan langsung untuk dieksekusi kepolisian", "Menyelenggarakan referendum nasional guna meminta mandat pemilih", "Mengajukan permintaan ke MK guna memeriksa dan memutus pendapat DPR", "Membentuk komite etik guna memberhentikan Presiden dari jabatannya"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mekanisme Pemakzulan Berdasarkan Pasal 7B Ayat 1</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 7B ayat (1) UUD 1945 menegaskan usul pemberhentian diajukan DPR kepada MPR dengan terlebih dahulu mengajukan permintaan kepada Mahkamah Konstitusi untuk <strong>memeriksa, mengadili, dan memutus pendapat DPR</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena proses pemberhentian Presiden/Wapres dalam masa jabatannya melalui jalur *impeachment* memadukan proses politik dan hukum (*judicial review*). Pendapat DPR terkait dugaan pelanggaran hukum wajib diuji keabsahannya secara peradilan di Mahkamah Konstitusi sebelum MPR dapat mengambil keputusan politik.</p><p>Jawaban tepat: <strong>D. Mengajukan permintaan ke MK guna memeriksa dan memutus pendapat DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pengujian pendapat DPR merupakan kewenangan absolut Mahkamah Konstitusi, bukan Mahkamah Agung.</li><li><strong>B</strong>: Kurang tepat karena DPR tidak berwenang menerbitkan eksekusi sepihak tanpa putusan peradilan konstitusi dan sidang MPR.</li><li><strong>C</strong>: Kurang tepat karena mekanisme referendum pemakzulan tidak dikenal dalam tata cara konstitusi UUD 1945.</li><li><strong>E</strong>: Kurang tepat karena komite internal parlemen tidak memiliki wewenang yuridis untuk memberhentikan kepala negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>memeriksa, mengadili, dan memutus pendapat DPR</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C4 - Analisis", "question": "Berdasarkan Pasal 7B ayat (1) UUD 1945, pemberhentian Presiden atau Wakil Presiden dalam masa jabatannya secara limitatif didasarkan pada alasan...", "options": ["Pelanggaran hukum pengkhianatan negara, kejahatan berat, atau perbuatan tercela", "Penurunan kinerja pertumbuhan ekonomi nasional di bawah rata-rata target APBN", "Perbedaan visi program pembangunan antara Presiden dengan pimpinan fraksi partai", "Penolakan terhadap usulan pembentukan kementerian baru yang disodorkan parlemen", "Keputusan perombakan kabinet di luar persetujuan komisi terkait di DPR"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Alasan Pemakzulan Berdasarkan Pasal 7B Ayat 1</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Alasan konstitusional pemakzulan menurut Pasal 7B ayat (1) adalah <strong>pengkhianatan terhadap negara, korupsi, penyuapan, tindak pidana berat</strong> lainnya, perbuatan tercela, atau terbukti tidak lagi memenuhi syarat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena konstitusi menegaskan bahwa alasan pemakzulan bersifat pelanggaran hukum serius (*legal grounds*) dan kegagalan syarat kualifikasi dasar, bukan karena motif ketidakcocokan politik atau dinamika kebijakan ekonomi tahunan.</p><p>Jawaban tepat: <strong>A. Pelanggaran hukum pengkhianatan negara, kejahatan berat, atau perbuatan tercela</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena fluktuasi target makroekonomi bukan kategori pelanggaran hukum atau perbuatan tercela menurut konstitusi.</li><li><strong>C</strong>: Kurang tepat karena perbedaan pandangan politik atau visi program bukan merupakan alasan hukum pemberhentian presiden.</li><li><strong>D</strong>: Kurang tepat karena penataan struktur kementerian merupakan hak prerogatif presiden sesuai undang-undang.</li><li><strong>E</strong>: Kurang tepat karena pengangkatan dan perombakan menteri sepenuhnya merupakan hak prerogatif kepala pemerintahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pengkhianatan terhadap negara, korupsi, penyuapan, tindak pidana berat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Sistem presidensial di Indonesia ditegaskan melalui ketentuan Pasal 17 UUD 1945 yang mengatur bahwa...", "options": ["Menteri negara bertanggung jawab secara kolektif kepada komisi-komisi di DPR", "Pengangkatan jajaran kabinet wajib melalui persetujuan mutlak pimpinan parlemen", "Menteri diangkat dan diberhentikan serta bertanggung jawab ke Presiden", "Pemberhentian menteri harus didasarkan pada hasil sidang paripurna istimewa MPR", "Menteri memegang mandat otonom yang tidak dapat dipengaruhi kebijakan Presiden"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Menteri Berdasarkan Pasal 17</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 17 UUD 1945 menyatakan menteri diangkat dan diberhentikan oleh Presiden, sehingga <strong>menteri-menteri itu diangkat dan diberhentikan oleh Presiden</strong> serta bertanggung jawab penuh kepada Presiden, bukan kepada DPR.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena ciri utama sistem presidensial adalah menteri merupakan pembantu presiden (*the president's assistants*). Para menteri menjalankan kekuasaan eksekutif di bawah arahan kepala negara dan tidak bergantung pada dukungan mayoritas kursi parlemen demi kelangsungan masa baktinya.</p><p>Jawaban tepat: <strong>C. Menteri diangkat dan diberhentikan serta bertanggung jawab ke Presiden</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena menteri tidak bertanggung jawab kepada komisi DPR melainkan kepada Presiden.</li><li><strong>B</strong>: Kurang tepat karena penunjukan menteri tidak memerlukan persetujuan mutlak parlemen melainkan hak prerogatif presiden.</li><li><strong>D</strong>: Kurang tepat karena MPR tidak berwenang memberhentikan menteri kabinet.</li><li><strong>E</strong>: Kurang tepat karena menteri merupakan pembantu presiden yang wajib tunduk pada arahan kebijakan kepala negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>menteri-menteri itu diangkat dan diberhentikan oleh</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Bentuk negara Republik dipilih oleh bangsa Indonesia karena mengandung filosofi pokok untuk...", "options": ["Menjamin pemusatan modal ekonomi pada kelompok birokrat pemegang kekuasaan", "Membatasi partisipasi masyarakat sipil dalam pengawasan operasional birokrasi", "Mewujudkan dominasi kelompok mayoritas dalam pengelolaan sumber daya negara", "Menjadikan tata kelola pemerintahan berpusat pada dinasti keluarga penguasa", "Mengutamakan pencapaian kepentingan umum (res publica) di atas golongan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Bentuk Republik dan Kepentingan Umum</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bangsa Indonesia memilih bentuk Republik yang merupakan pola yang <strong>mengutamakan pencapaian kepentingan umum (res publica)</strong> dan bukan kepentingan perseorangan atau golongan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kata republik berasal dari bahasa Latin *res publica* yang berarti urusan publik atau kemaslahatan umum. Negara didirikan untuk melayani seluruh warga negara secara adil dan mewujudkan kesejahteraan bersama, bukan melayani kepentingan dinasti monarki atau oligarki kelompok tertentu.</p><p>Jawaban tepat: <strong>E. Mengutamakan pencapaian kepentingan umum (res publica) di atas golongan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pemusatan modal pada birokrat bertentangan dengan asas pemerataan dan kemaslahatan umum.</li><li><strong>B</strong>: Kurang tepat karena republik menjunjung tinggi partisipasi publik dalam mengawal akuntabilitas negara.</li><li><strong>C</strong>: Kurang tepat karena republik berdiri untuk melindungi segenap bangsa tanpa hegemoni kelompok mayoritas.</li><li><strong>D</strong>: Kurang tepat karena republik menolak sistem dinasti monarki yang menempatkan kekuasaan berdasarkan warisan keturunan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mengutamakan pencapaian kepentingan umum (res publica)</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Ketentuan Pasal 37 ayat (5) UUD 1945 memberikan batasan tegas terhadap wewenang perubahan konstitusi, yaitu...", "options": ["Struktur kelembagaan kementerian dapat dibubarkan secara mandiri oleh parlemen", "Bentuk Negara Kesatuan Republik Indonesia tidak dapat diubah", "Mekanisme pemilihan kepala daerah tidak dapat disesuaikan dengan undang-undang", "Batas wilayah otonomi daerah dilarang mengalami pemekaran atau penggabungan", "Pengaturan jumlah lembaga peradilan tata usaha negara bersifat mutlak permanen"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk NKRI Berdasarkan Pasal 37 Ayat 5</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 37 ayat (5) UUD 1945 menegaskan ketentuan klausul kekal (*entrenchment clause*) bahwa <strong>khusus tentang bentuk Negara Kesatuan Republik Indonesia tidak dapat dilakukan perubahan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Pasal 37 ayat (5) UUD 1945 mengunci bentuk NKRI sebagai kesepakatan final pendiri bangsa yang tidak dapat diubah oleh lembaga pembuat konstitusi sekalipun. Ini menjadi benteng yuridis keutuhan wilayah dan kedaulatan negara Indonesia.</p><p>Jawaban tepat: <strong>B. Bentuk Negara Kesatuan Republik Indonesia tidak dapat diubah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena struktur kementerian diatur secara fleksibel melalui undang-undang kementerian negara.</li><li><strong>C</strong>: Kurang tepat karena tata cara pemilihan kepala daerah dapat disesuaikan menurut undang-undang perundang-undangan.</li><li><strong>D</strong>: Kurang tepat karena pemekaran dan penggabungan daerah otonom diperbolehkan menurut mekanisme regulasi perundangan.</li><li><strong>E</strong>: Kurang tepat karena jumlah dan yurisdiksi pengadilan diatur serta dapat diubah melalui instrumen undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 44) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kesatuan Republik Indonesia tidak dapat dilakukan perubahan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Salah satu perubahan struktural pada kelembagaan negara Indonesia setelah rangkaian amandemen UUD 1945 adalah...", "options": ["Badan Pemeriksa Keuangan dialihkan kedudukannya di bawah naungan kementerian", "Komisi Yudisial memegang kendali penuntutan pada ranah peradilan tindak pidana", "Mahkamah Konstitusi diposisikan di bawah yurisdiksi teknis Mahkamah Agung", "Dewan Perwakilan Daerah mengambil alih fungsi anggaran sepenuhnya dari DPR", "Dewan Pertimbangan Agung (DPA) telah dihapus dari struktur kelembagaan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penataan Kelembagaan Negara Pasca-Amandemen</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setelah UUD 1945 diamandemen sebanyak empat kali, lembaga <strong>Dewan Pertimbangan Agung (DPA) telah dihapus</strong>, dan dibentuk lembaga baru seperti DPD, MK, serta KY.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena dalam penataan struktur ketatanegaraan pasca-amandemen, lembaga DPA dibubarkan karena fungsinya dianggap dapat diakomodasi langsung melalui dewan pertimbangan yang berkedudukan di bawah ranah kepresidenan (seperti Wantimpres), sehingga struktur negara lebih ramping dan efisien.</p><p>Jawaban tepat: <strong>E. Dewan Pertimbangan Agung (DPA) telah dihapus dari struktur kelembagaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena BPK tetap berkedudukan sebagai lembaga negara mandiri dan bebas, bukan di bawah kementerian.</li><li><strong>B</strong>: Kurang tepat karena KY berwenang mengusulkan hakim agung dan menjaga etika hakim, bukan menjalankan penuntutan pidana.</li><li><strong>C</strong>: Kurang tepat karena MK berkedudukan setara dan mandiri berdampingan dengan MA sebagai pemegang kekuasaan kehakiman.</li><li><strong>D</strong>: Kurang tepat karena wewenang pengesahan anggaran (APBN) tetap berada pada kewenangan DPR bersama Presiden.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dewan Pertimbangan Agung (DPA) telah dihapus</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Lembaga-lembaga negara yang baru dibentuk dalam kerangka ketatanegaraan Indonesia pasca-amandemen UUD 1945 adalah...", "options": ["Dewan Pengawas Keuangan, Komisi Pengawas Persaingan, dan Mahkamah Agung", "Dewan Perwakilan Daerah, Mahkamah Konstitusi, dan Komisi Yudisial", "Dewan Pertimbangan Agung, Kejaksaan Agung, dan Badan Arbitrase Nasional", "Badan Pemeriksa Keuangan, Ombudsman Republik Indonesia, dan DPR", "Komisi Pemilihan Umum, Lembaga Administrasi Negara, dan Majelis Permusyawaratan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Lembaga Negara Baru Pasca-Amandemen UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Lembaga negara yang <strong>baru dibentuk dalam UUD 1945 amandemen yaitu DPD, MK dan KY</strong> untuk memperkuat mekanisme saling mengawasi dan mengimbangi (*checks and balances*).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena amandemen UUD 1945 melahirkan DPD untuk menampung representasi daerah, MK untuk mengawal kemurnian konstitusi, dan KY untuk mengawal integritas peradilan kehakiman. Ketiga lembaga ini sebelumnya tidak tercantum dalam naskah asli UUD 1945.</p><p>Jawaban tepat: <strong>B. Dewan Perwakilan Daerah, Mahkamah Konstitusi, dan Komisi Yudisial</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Mahkamah Agung sudah ada sejak naskah awal UUD 1945.</li><li><strong>C</strong>: Kurang tepat karena Dewan Pertimbangan Agung merupakan lembaga yang telah resmi dihapus.</li><li><strong>D</strong>: Kurang tepat karena BPK dan DPR sudah ada dalam rumusan asli UUD 1945.</li><li><strong>E</strong>: Kurang tepat karena Majelis Permusyawaratan Rakyat sudah ada sejak penyusunan naskah awal UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>baru dibentuk dalam UUD 1945 amandemen yaitu DPD, MK dan KY</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pasca-amandemen UUD 1945, susunan keanggotaan Majelis Permusyawaratan Rakyat (MPR) terdiri atas...", "options": ["Perwakilan organisasi masyarakat dan utusan golongan yang ditunjuk kepala negara", "Seluruh menteri koordinator bersama para pimpinan lembaga tinggi pemerintahan", "Perwira militer aktif beserta pimpinan partai pemenang pemilu", "Seluruh anggota DPR dan DPD yang dipilih secara langsung lewat pemilihan umum", "Gubernur seluruh provinsi dan delegasi rektor perguruan tinggi"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Susunan Keanggotaan MPR Berdasarkan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kedudukan MPR sebagai lembaga negara memiliki susunan keanggotaan yang terdiri dari <strong>anggota DPR dan DPD hasil pemilihan umum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena sistem bikameral *soft* di Indonesia menyatukan wakil rakyat berbasis politik (DPR) dan wakil daerah berbasis teritorial provinsi (DPD) menjadi anggota MPR. Sistem pengangkatan utusan daerah dan utusan golongan tanpa pemilu telah ditiadakan.</p><p>Jawaban tepat: <strong>D. Seluruh anggota DPR dan DPD yang dipilih secara langsung lewat pemilihan umum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena unsur utusan golongan yang ditunjuk presiden telah dihapuskan dari struktur MPR.</li><li><strong>B</strong>: Kurang tepat karena menteri koordinator merupakan pejabat eksekutif pembantu presiden, bukan anggota MPR.</li><li><strong>C</strong>: Kurang tepat karena perwira militer aktif tidak memiliki alokasi kursi otomatis di parlemen pasca-reformasi.</li><li><strong>E</strong>: Kurang tepat karena gubernur memegang kekuasaan eksekutif daerah dan rektor bukan unsur representasi MPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dan DPD hasil pemilihan umum</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kedudukan MPR dalam sistem ketatanegaraan Indonesia pasca-amandemen diposisikan sebagai...", "options": ["Sebagai pelaksana fungsi konstitutif dalam hierarki kelembagaan negara", "Pusat pengendali kebijakan operasional kementerian koordinator bidang ekonomi", "Pelaksana kekuasaan yudikatif tertinggi peradilan sengketa tata kelola pemilu", "Pemegang mandat tunggal pengesahan anggaran pendapatan belanja kementerian", "Lembaga penegak disiplin etik bagi aparatur sipil negara di seluruh daerah"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan MPR Sebagai Pelaksana Fungsi Konstitutif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kedudukan MPR pasca-perubahan UUD 1945 berfungsi <strong>sebagai pelaksana fungsi konstitutif</strong>, yaitu fungsi pengubahan dan penetapan konstitusi negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena fungsi konstitutif adalah wewenang mengubah dan menetapkan undang-undang dasar. MPR tidak lagi memegang fungsi eksekutif atau legislasi sehari-hari pembuat undang-undang organik biasa, melainkan menjalankan wewenang tertinggi penetapan hukum dasar negara.</p><p>Jawaban tepat: <strong>A. Sebagai pelaksana fungsi konstitutif dalam hierarki kelembagaan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena kebijakan operasional kementerian berada di bawah kendali Presiden.</li><li><strong>C</strong>: Kurang tepat karena peradilan perselisihan hasil pemilu merupakan wewenang Mahkamah Konstitusi.</li><li><strong>D</strong>: Kurang tepat karena mandat pengesahan APBN berada di tangan DPR bersama Presiden.</li><li><strong>E</strong>: Kurang tepat karena disiplin etik ASN berada di bawah tata kelola instansi pembina kepegawaian dan BKN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sebagai pelaksana fungsi konstitutif</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan UUD 1945, kewajiban bersidang bagi Majelis Permusyawaratan Rakyat diatur secara berkala yaitu...", "options": ["Mengadakan rapat koordinasi umum tiap akhir tahun anggaran belanja", "Menyelenggarakan sidang paripurna rutin tiap tiga bulan secara wajib", "Bersidang sedikitnya sekali dalam lima tahun di ibu kota negara", "Melaksanakan sidang pleno satu kali tiap permulaan masa reses dewan", "Berkumpul secara periodik dua tahun sekali guna evaluasi kinerja kabinet"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Periodesasi Sidang Majelis Permusyawaratan Rakyat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu tugas dan wewenang MPR yang diatur dalam konstitusi adalah <strong>bersidang sedikitnya sekali dalam lima tahun</strong> di ibu kota negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena MPR merupakan lembaga perwakilan yang bersidang secara periodik untuk agenda-agenda konstitusional (seperti pelantikan Presiden/Wapres terpilih di awal periode), serta dapat bersidang sewaktu-waktu jika ada agenda konstitutif mendesak.</p><p>Jawaban tepat: <strong>C. Bersidang sedikitnya sekali dalam lima tahun di ibu kota negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena evaluasi akhir tahun anggaran merupakan lingkup rapat kerja berkala DPR dan pemerintah.</li><li><strong>B</strong>: Kurang tepat karena sidang rutin tiap tiga bulan merupakan pola masa sidang DPR, bukan MPR.</li><li><strong>D</strong>: Kurang tepat karena masa reses berkaitan erat dengan aktivitas kedewanan DPR dan DPD di dapil masing-masing.</li><li><strong>E</strong>: Kurang tepat karena evaluasi kabinet bukan merupakan fungsi MPR dalam skema sistem presidensial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Bersidang sedikitnya sekali dalam lima tahun</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Sesuai Pasal 37 UUD 1945, usul perubahan pasal-pasal Undang-Undang Dasar secara tertulis wajib diajukan oleh...", "options": ["Sekurang-kurangnya separuh dari gabungan fraksi-fraksi di dewan", "Seluruh pimpinan partai politik pemilik kursi terbanyak di parlemen", "Paling sedikit seperempat dari jumlah total perwakilan DPD provinsi", "Mayoritas mutlak anggota komisi hukum dan perundang-undangan DPR", "Sekurang-kurangnya 1/3 dari jumlah anggota MPR secara tertulis"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Pengajuan Usul Perubahan Pasal UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Usul perubahan pasal-pasal UUD diajukan secara tertulis oleh <strong>sekurang-kurangnya 1/3 dari jumlah anggota MPR</strong> dengan menunjukkan secara jelas bagian yang diusulkan untuk diubah beserta alasannya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena konstitusi menetapkan syarat formal yang ketat bagi inisiasi perubahan konstitusi guna menjamin stabilitas hukum nasional. Ambang batas 1/3 anggota MPR mencegah inisiasi amandemen yang sembrono atau didorong oleh kepentingan sesaat.</p><p>Jawaban tepat: <strong>E. Sekurang-kurangnya 1/3 dari jumlah anggota MPR secara tertulis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena ambang batas pengajuan awal adalah 1/3 jumlah anggota, bukan separuh fraksi.</li><li><strong>B</strong>: Kurang tepat karena pengajuan konstitusional dilakukan oleh anggota MPR resmi, bukan pimpinan partai di luar dewan.</li><li><strong>C</strong>: Kurang tepat karena pengusulan harus mengatasnamakan anggota MPR secara keseluruhan, bukan seperempat DPD.</li><li><strong>D</strong>: Kurang tepat karena inisiatif pengusulan bukan monopoli komisi hukum DPR melainkan hak anggota MPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sekurang-kurangnya 1/3 dari jumlah anggota MPR</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Dalam sidang paripurna MPR untuk memutuskan usul perubahan pasal-pasal UUD, putusan sah apabila disetujui oleh...", "options": ["Sekurang-kurangnya dua pertiga dari anggota dewan yang hadir di persidangan", "Sekurang-kurangnya lima puluh persen ditambah satu dari seluruh anggota MPR", "Suara bulat penuh dari seluruh delegasi utusan wilayah daerah kepulauan", "Tiga perempat perwakilan partai politik peraih suara terbanyak hasil pemilu", "Mayoritas relatif dari anggota yang menandatangani daftar hadir rapat komisi"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kuorum dan Pengambilan Putusan Perubahan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sidang MPR untuk mengubah pasal UUD dihadiri sekurang-kurangnya 2/3 anggota MPR, dan putusan dilakukan dengan persetujuan <strong>sekurang-kurangnya lima puluh persen ditambah satu dari seluruh anggota MPR</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pengambilan keputusan perubahan UUD 1945 mengacu pada total seluruh anggota MPR (bukan sekadar anggota yang hadir), yakni formula 50% + 1 (mayoritas absolut konstitusional). Ini menunjukkan sifat UUD sebagai konstitusi derajat tinggi (*rigid constitution*).</p><p>Jawaban tepat: <strong>B. Sekurang-kurangnya lima puluh persen ditambah satu dari seluruh anggota MPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena 2/3 merupakan syarat kuorum kehadiran sidang, bukan ambang persetujuan putusan akhir.</li><li><strong>C</strong>: Kurang tepat karena aturan konstitusi tidak mensyaratkan suara bulat mutlak (aklamasi) delegasi kepulauan.</li><li><strong>D</strong>: Kurang tepat karena syarat persetujuan adalah 50%+1 seluruh anggota MPR, bukan 3/4 fraksi partai.</li><li><strong>E</strong>: Kurang tepat karena mayoritas relatif daftar hadir rapat komisi tidak sah untuk mengubah konstitusi negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>lima puluh persen ditambah satu dari</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Wewenang konstitusional MPR terkait suksesi kepemimpinan nasional pasca-pemilihan umum presiden adalah...", "options": ["Menetapkan garis-garis besar haluan negara sebagai acuan kerja mandataris", "Menentukan pasangan kepala negara melalui pemungutan suara tertutup dewan", "Mengesahkan susunan menteri kabinet yang diajukan oleh pasangan terpilih", "Melantik Presiden dan Wakil Presiden berdasarkan hasil pemilihan umum", "Menguji kelayakan administratif berkas pencalonan pimpinan tingkat nasional"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wewenang MPR Melantik Pasangan Presiden Terpilih</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas dan wewenang MPR salah satunya adalah <strong>melantik Presiden dan Wakil Presiden berdasarkan hasil pemilihan umum</strong> dalam Sidang Paripurna MPR.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Presiden dan Wapres dipilih langsung oleh rakyat melalui pemilu demokratis. Peran MPR bertindak sebagai lembaga konstitusional yang mengambil sumpah/janji serta melantik pasangan terpilih secara resmi di hadapan sidang paripurna.</p><p>Jawaban tepat: <strong>D. Melantik Presiden dan Wakil Presiden berdasarkan hasil pemilihan umum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kewenangan menyusun GBHN telah dihapuskan sejak amandemen UUD 1945.</li><li><strong>B</strong>: Kurang tepat karena MPR tidak lagi memilih pasangan presiden dalam kondisi normal pasca-pemilu langsung.</li><li><strong>C</strong>: Kurang tepat karena penyusunan jajaran menteri kabinet merupakan hak prerogatif presiden terpilih.</li><li><strong>E</strong>: Kurang tepat karena pengujian berkas syarat administrasi pasangan calon merupakan wewenang KPU.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Melantik Presiden dan Wakil Presiden berdasarkan hasil pemilihan umum</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C3 - Aplikasi", "question": "Setelah menerima usul pemakzulan Presiden dari DPR yang telah dibenarkan oleh Mahkamah Konstitusi, MPR wajib menyelenggarakan sidang paling lambat...", "options": ["Paling lambat tiga puluh hari sejak diterimanya usul tersebut oleh MPR", "Empat belas hari kalender sejak naskah putusan pengadilan dibacakan", "Enam puluh hari kerja setelah berkas pemeriksaan dinyatakan lengkap", "Sembilan puluh hari kalender terhitung sejak sidang paripurna dewan", "Dua puluh satu hari kerja sejak penetapan ketua komisi konstitusi"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batas Waktu Sidang Paripurna MPR Soal Usul Pemakzulan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>MPR wajib menyelenggarakan sidang untuk memutuskan usul DPR terkait pemberhentian Presiden/Wapres <strong>paling lambat tiga puluh hari sejak diterimanya usul tersebut</strong> setelah Presiden/Wapres diberi kesempatan menyampaikan penjelasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena konstitusi menetapkan batas waktu maksimal tiga puluh hari agar tercipta kepastian hukum tata kelola negara serta mencegah kekosongan atau ketidakstabilan pemerintahan yang berlarut-larut.</p><p>Jawaban tepat: <strong>A. Paling lambat tiga puluh hari sejak diterimanya usul tersebut oleh MPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena batas waktu konstitusional adalah tiga puluh hari, bukan empat belas hari.</li><li><strong>C</strong>: Kurang tepat karena enam puluh hari merupakan batas waktu untuk pemilihan pengisian kekosongan jabatan wakil presiden.</li><li><strong>D</strong>: Kurang tepat karena sembilan puluh hari merupakan batas waktu pemeriksaan di MK, bukan penyelenggaraan sidang MPR.</li><li><strong>E</strong>: Kurang tepat karena jangka waktu 21 hari kerja tidak dikenal dalam tahapan sidang pemakzulan MPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>lambat tiga puluh hari sejak diterimanya usul</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam hal terjadi kekosongan jabatan Wakil Presiden dalam masa jabatannya, MPR wajib menyelenggarakan sidang untuk memilih Wakil Presiden...", "options": ["Dari tiga calon pimpinan partai politik dalam tempo tiga puluh hari", "Melalui proses pemilihan umum darurat yang digelar dalam tempo satu tahun", "Selambat-lambatnya dalam waktu enam puluh hari dari dua calon usulan Presiden", "Berdasarkan usulan komisi kepegawaian dalam batas waktu sembilan puluh hari", "Dengan mengangkat menteri koordinator senior secara permanen otomatis"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengisian Kekosongan Jabatan Wakil Presiden</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>MPR bertugas memilih Wakil Presiden dari dua calon yang diajukan Presiden, apabila terjadi kekosongan jabatan Wakil Presiden dalam masa jabatan <strong>selambat-lambatnya dalam waktu enam puluh hari</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena UUD 1945 menggariskan tata cara pengisian posisi wapres yang lowong: Presiden mengajukan dua nama calon kepada MPR, kemudian MPR bersidang dan memilih salah satunya dalam rentang waktu maksimal enam puluh hari.</p><p>Jawaban tepat: <strong>C. Selambat-lambatnya dalam waktu enam puluh hari dari dua calon usulan Presiden</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena calon yang diusulkan berjumlah dua calon dari Presiden, bukan tiga calon pimpinan partai.</li><li><strong>B</strong>: Kurang tepat karena pengisian kekosongan wapres dilakukan melalui sidang MPR, bukan pemilu darurat.</li><li><strong>D</strong>: Kurang tepat karena komisi kepegawaian tidak memiliki otoritas dalam pengisian posisi pejabat politik negara.</li><li><strong>E</strong>: Kurang tepat karena menteri koordinator tidak otomatis menjadi wakil presiden definitif secara permanen.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 45) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>waktu enam puluh hari</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_wk_p04_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_wk_p04_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_wk_p04_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Wawasan Kebangsaan Paket 04";
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
<?php
/**
 * STANGOLDEN.exam • Wawasan Kebangsaan Paket 05 (Halaman 46–55)
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
// $DB->get_record("stgwk_p05_rank") => tabel fisik "mdlhv_stgwk_p05_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgwk_p05_rank';
const STG_REVIEW_T = 'stgwk_p05_rev';
const STG_ANNO_T = 'stgwk_p05_anno';
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
            error_log('STANGOLDEN WK P05 HLM46-55 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN WK P05 HLM46-55: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Wawasan Kebangsaan Paket 05 (Hlm. 46–55) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 05 • Hlm. 46–55</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 05 • Hlm. 46–55</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Wawasan Kebangsaan Paket 05</h2>
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
const STG_LOCAL_KEY="stg_wk_p05_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Apabila Presiden dan Wakil Presiden mangkat atau berhenti secara bersamaan dalam masa jabatannya, MPR wajib menyelenggarakan sidang suksesi dalam batas waktu...", "options": ["Selambat-lambatnya tiga puluh hari dari dua paket calon terbanyak", "Paling lambat empat belas hari kerja sejak pengunduran diri", "Maksimal enam puluh hari kalender sejak penetapan panitia", "Dalam tenggat sembilan puluh hari setelah verifikasi berkas", "Tepat seratus dua puluh hari kerja terhitung sejak rapat pleno paripurna pertama"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemilihan Presiden dan Wapres Pengganti oleh MPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>MPR bertugas memilih Presiden dan Wakil Presiden apabila keduanya berhenti secara bersamaan dari dua paket calon yang diajukan partai politik peraih suara terbanyak pertama dan kedua pada pemilu sebelumnya, <strong>selambat-lambatnya dalam waktu tiga puluh hari</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena konstitusi membatasi durasi suksesi darurat maksimal tiga puluh hari agar pemerintahan definitif segera terbentuk kembali. Calon yang dapat dipilih berasal dari paket pasangan peraih suara terbanyak pertama dan kedua pada pemilu terakhir.</p><p>Jawaban tepat: <strong>A. Selambat-lambatnya tiga puluh hari dari dua paket calon terbanyak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena batas waktu konstitusional adalah tiga puluh hari, bukan empat belas hari kerja.</li><li><strong>C</strong>: Kurang tepat karena enam puluh hari merupakan batas waktu pengisian kekosongan posisi wakil presiden saja.</li><li><strong>D</strong>: Kurang tepat karena sembilan puluh hari merupakan batas waktu pemeriksaan sengketa pemakzulan di MK.</li><li><strong>E</strong>: Kurang tepat karena durasi 120 hari tidak dikenal dalam prosedur suksesi darurat kepresidenan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dalam waktu tiga puluh hari</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dalam sistem ketatanegaraan Indonesia pasca-amandemen UUD 1945, kedudukan Presiden Republik Indonesia dirumuskan sebagai...", "options": ["Pelaksana kekuasaan yudikatif tertinggi peradilan kasasi perkara tata usaha", "Kepala pemerintahan (eksekutif dan legislatif) dan kepala negara", "Pemimpin tunggal lembaga legislatif yang berwenang membubarkan parlemen", "Mandataris mutlak majelis yang bertanggung jawab penuh kepada pimpinan fraksi", "Ketua dewan pertimbangan yang menjalankan fungsi peradilan sengketa pemilu"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Ganda Lembaga Kepresidenan Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden sebagai pengemban amanat rakyat memiliki kedudukan <strong>selaku kepala pemerintahan (fungsi eksekutif dan fungsi legislatif) dan kepala negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena dalam sistem presidensial Indonesia, jabatan kepala negara (*head of state*) dan kepala pemerintahan (*head of government*) terintegrasi dalam diri Presiden. Di samping memegang kekuasaan eksekutif, Presiden juga memegang fungsi legislatif bersama DPR dalam membentuk undang-undang.</p><p>Jawaban tepat: <strong>B. Kepala pemerintahan (eksekutif dan legislatif) dan kepala negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kekuasaan peradilan kasasi tertinggi dipegang oleh Mahkamah Agung.</li><li><strong>C</strong>: Kurang tepat karena Presiden dilarang membekukan atau membubarkan Dewan Perwakilan Rakyat.</li><li><strong>D</strong>: Kurang tepat karena Presiden bukan mandataris MPR pasca-amandemen melainkan dipilih langsung oleh rakyat.</li><li><strong>E</strong>: Kurang tepat karena peradilan perselisihan hasil pemilu merupakan wewenang Mahkamah Konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Selaku kepala pemerintahan (fungsi eksekutif dan fungsi legislatif) dan kepala</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Sumber legitimasi kekuasaan Presiden dan Wakil Presiden Republik Indonesia ditegaskan melalui asas bahwa pasangan kepresidenan...", "options": ["Ditetapkan secara bergantian oleh koalisi partai politik pemenang pemilu", "Diangkat oleh sidang gabungan seluruh perwakilan kepala daerah provinsi", "Dipilih secara langsung oleh rakyat dalam suatu pemilihan umum", "Ditetapkan melalui pemungutan suara berjenjang utusan golongan masyarakat", "Diputuskan oleh musyawarah pimpinan majelis permusyawaratan dewan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Legitimasi Presiden Berdasarkan Pemilihan Umum Langsung</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasca-amandemen UUD 1945, Presiden dan Wakil Presiden <strong>dipilih secara langsung oleh rakyat dalam suatu pemilihan umum</strong> sehingga memiliki legitimasi mandat rakyat yang sangat kuat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pemilihan umum secara langsung oleh rakyat memutus mata rantai subordinasi Presiden terhadap parlemen. Rakyat pemilih berdaulat penuh menentukan pemimpin nasional melalui pesta demokrasi lima tahunan.</p><p>Jawaban tepat: <strong>C. Dipilih secara langsung oleh rakyat dalam suatu pemilihan umum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena presiden tidak ditetapkan bergantian oleh partai melainkan dipilih langsung oleh rakyat.</li><li><strong>B</strong>: Kurang tepat karena kepala daerah tidak memiliki kewenangan memilih pasangan presiden definitif.</li><li><strong>D</strong>: Kurang tepat karena utusan golongan telah ditiadakan dari sistem pemilihan ketatanegaraan Indonesia.</li><li><strong>E</strong>: Kurang tepat karena MPR tidak lagi memilih presiden dalam kondisi normal pasca-pemilu langsung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dipilih secara langsung oleh rakyat dalam suatu pemilihan umum</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pembatasan masa pengabdian kepala pemerintahan dalam konstitusi Indonesia diatur secara definitif bahwa Presiden...", "options": ["Dapat memegang kekuasaan seumur hidup manakala didukung oleh konsensus dewan", "Wajib meletakkan jabatan setelah tiga tahun pengabdian guna rotasi kabinet", "Dapat dipilih berulang kali tanpa batasan periode asalkan disetujui mahkamah", "Menjabat lima tahun dan dapat dipilih kembali untuk satu kali", "Diberikan kewenangan memperpanjang mandat secara mandiri saat situasi krisis"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batasan Masa Jabatan Eksekutif Berdasarkan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden memegang jabatan selama lima tahun dan sesudahnya <strong>dapat dipilih kembali dalam jabatan yang sama untuk satu kali</strong> masa jabatan (maksimal sepuluh tahun).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena ketentuan ini dirumuskan pasca-reformasi untuk mencegah otoritarianisme dan personifikasi kekuasaan mutlak. Masa bakti maksimal dibatasi dua periode berturut-turut maupun berselang.</p><p>Jawaban tepat: <strong>D. Menjabat lima tahun dan dapat dipilih kembali untuk satu kali</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena masa jabatan seumur hidup bertentangan dengan prinsip demokrasi konstitusional.</li><li><strong>B</strong>: Kurang tepat karena masa jabatan reguler yang sah adalah lima tahun, bukan tiga tahun.</li><li><strong>C</strong>: Kurang tepat karena pemilihan tanpa batas periode telah dihapus oleh amandemen UUD 1945.</li><li><strong>E</strong>: Kurang tepat karena presiden tidak berhak memperpanjang masa jabatannya secara sekehendak sendiri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dalam jabatan yang sama untuk satu kali</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Dalam hal Presiden terbukti melakukan pelanggaran hukum berat, prosedur konstitusional pemberhentiannya adalah...", "options": ["DPR memberhentikan Presiden secara langsung melalui rapat pleno internal komisi", "Badan Pemeriksa Keuangan mencopot jabatan kepala negara atas dasar temuan audit", "Mahkamah Agung mengeksekusi pemberhentian tanpa sidang majelis", "Kabinet kementerian mengambil alih kendali kekuasaan melalui mosi tidak percaya", "Diberhentikan MPR atas usul DPR berdasar putusan Mahkamah Konstitusi"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mekanisme Pemberhentian Presiden Berdasarkan Putusan MK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden <strong>dapat diberhentikan dari jabatannya oleh MPR atas usul DPR berdasarkan putusan Mahkamah Konstitusi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena proses *impeachment* memadukan mekanisme pengawasan politik dewan (DPR), verifikasi peradilan hukum yang adil (*due process of law* di MK), dan penetapan putusan politik tertinggi di forum MPR.</p><p>Jawaban tepat: <strong>E. Diberhentikan MPR atas usul DPR berdasar putusan Mahkamah Konstitusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena DPR tidak berwenang memberhentikan presiden secara langsung.</li><li><strong>B</strong>: Kurang tepat karena BPK bertugas memeriksa keuangan negara dan tidak berwenang mencopot pejabat politik.</li><li><strong>C</strong>: Kurang tepat karena MA tidak mengadili pemakzulan presiden melainkan MK yang memutus pendapat DPR.</li><li><strong>D</strong>: Kurang tepat karena kabinet menteri merupakan pembantu presiden dan tidak mengenal mekanisme mosi tidak percaya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dapat diberhentikan dari jabatannya oleh MPR atas usul DPR</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Salah satu jaminan ketatanegaraan yang memperkuat kedudukan lembaga legislatif di hadapan eksekutif adalah aturan bahwa Presiden...", "options": ["Tidak dapat membekukan atau membubarkan DPR dalam situasi ketatanegaraan apa pun", "Berhak menunda persidangan dewan apabila rancangan undang-undang ditangguhkan", "Dapat membubarkan parlemen dengan persetujuan dewan pertimbangan agung", "Memiliki wewenang mencopot pimpinan fraksi partai yang berbeda haluan kebijakan", "Dapat menetapkan anggota legislatif pengganti antarwaktu secara otoritatif"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Kesetaraan Hubungan Presiden dan Parlemen</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 7C UUD 1945, Presiden <strong>tidak dapat membekukan atau membubarkan DPR</strong>, yang menjamin independensi cabang kekuasaan legislatif.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena ketentuan ini menjamin prinsip *checks and balances*. Presiden tidak dapat membubarkan DPR seperti dalam sistem parlementer atau maklumat masa lalu (Dekrit 1959), sehingga parlemen bebas menjalankan fungsi pengawasan tanpa ancaman pembubaran eksekutif.</p><p>Jawaban tepat: <strong>A. Tidak dapat membekukan atau membubarkan DPR dalam situasi ketatanegaraan apa pun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena Presiden tidak berwenang menunda jadwal masa sidang dewan.</li><li><strong>C</strong>: Kurang tepat karena DPA telah dibubarkan dan presiden dilarang membubarkan dewan dalam bentuk apa pun.</li><li><strong>D</strong>: Kurang tepat karena pergantian pimpinan fraksi merupakan ranah internal otonom partai politik.</li><li><strong>E</strong>: Kurang tepat karena PAW anggota dewan tunduk pada undang-undang pemilu dan mekanisme internal partai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tidak dapat membekukan atau membubarkan DPR</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Apabila Presiden Republik Indonesia mangkat, berhenti, atau diberhentikan dalam masa jabatannya, ketentuan konstitusional menetapkan bahwa Presiden...", "options": ["Digantikan oleh pimpinan majelis permusyawaratan secara ad interim", "Diganti Wakil Presiden sampai habis masa jabatannya secara definitif", "Digantikan oleh koalisi tiga menteri koordinator hingga pemilu ulang", "Diganti oleh ketua mahkamah agung selaku pejabat pelaksana harian", "Digantikan oleh perwakilan partai politik pemenang kursi terbanyak"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Suksesi Jabatan Presiden Apabila Berhalangan Tetap</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 8 ayat (1) UUD 1945 menentukan jika Presiden mangkat, berhenti, diberhentikan, atau tidak dapat melakukan kewajibannya, maka <strong>diganti Wakil Presiden sampai habis masa jabatannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Wakil Presiden merupakan pasangan dwitunggal yang terpilih bersamaan dalam pemilu. Saat kursi kepresidenan lowong karena halangan tetap, Wakil Presiden otomatis dilantik menjadi Presiden definitif guna menuntaskan sisa periode berjalan.</p><p>Jawaban tepat: <strong>B. Diganti Wakil Presiden sampai habis masa jabatannya secara definitif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pimpinan majelis tidak berkedudukan menggantikan presiden dalam kondisi ini.</li><li><strong>C</strong>: Kurang tepat karena menteri koordinator bukan pelaksana tugas suksesi tunggal jika wapres masih ada.</li><li><strong>D</strong>: Kurang tepat karena ketua MA memegang kekuasaan kehakiman dan dilarang memangku jabatan eksekutif.</li><li><strong>E</strong>: Kurang tepat karena pergantian presiden terikat pada garis suksesi konstitusi, bukan penunjukan partai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>diganti Wakil Presiden sampai habis masa jabatannya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Apabila Presiden dan Wakil Presiden mangkat atau berhalangan tetap secara bersamaan dalam masa jabatannya, pelaksana tugas kepresidenan diemban oleh...", "options": ["Panglima TNI, Kepala Kepolisian Negara, dan Kepala Badan Intelijen Negara", "Menteri Koordinator Bidang Polhukam, Menteri Keuangan, dan Jaksa Agung", "Menteri Luar Negeri, Dalam Negeri, dan Pertahanan bersama-sama", "Ketua MPR, Ketua DPR, serta Ketua Dewan Perwakilan Daerah secara kolegial", "Ketua Mahkamah Agung, Ketua Mahkamah Konstitusi, dan Ketua Komisi Yudisial"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelaksana Tugas Kepresidenan Bersama (Triumvirat)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jika Presiden dan Wapres mangkat bersamaan, maka Pelaksana Tugas Kepresidenan adalah <strong>Menteri Luar Negeri, Menteri Dalam Negeri, dan Menteri Pertahanan secara bersama-sama</strong> (*triumvirat*).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena ketiga kementerian ini merepresentasikan pilar dasar stabilitas kedaulatan negara (diplomasi luar negeri, administrasi pemerintahan dalam negeri, dan pertahanan teritorial) yang bertugas mengawal transisi hingga MPR memilih presiden definitif.</p><p>Jawaban tepat: <strong>C. Menteri Luar Negeri, Dalam Negeri, dan Pertahanan bersama-sama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena aparat keamanan dan intelijen bertindak sebagai pelaksana teknis pertahanan keamanan di bawah komando pemerintah.</li><li><strong>B</strong>: Kurang tepat karena komposisi triumvirat konstitusional secara baku menetapkan Menlu, Mendagri, dan Menhan.</li><li><strong>D</strong>: Kurang tepat karena pimpinan lembaga legislatif bertugas memimpin sidang pemilihan presiden definitif di MPR.</li><li><strong>E</strong>: Kurang tepat karena pimpinan lembaga yudikatif menjaga independensi kekuasaan kehakiman dan tidak menjalankan eksekutif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menteri Luar Negeri, Menteri Dalam Negeri, dan Menteri</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Salah satu hak konstitusional yang dimiliki oleh anggota Majelis Permusyawaratan Rakyat dalam menjalankan tugasnya adalah...", "options": ["Menerbitkan dekrit darurat pembubaran lembaga-lembaga tinggi negara", "Membatalkan putusan peradilan tingkat banding perkara kejahatan perpajakan", "Menetapkan besaran tarif retribusi daerah tanpa koordinasi pemerintah daerah", "Usul perubahan pasal diajukan sekurang-kurangnya 1/3 anggota MPR", "Menyetujui pengangkatan pimpinan perusahaan milik negara secara administratif"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Anggota MPR Mengajukan Perubahan UUD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hak anggota MPR meliputi: <strong>mengajukan usul perubahan pasal-pasal Undang-Undang Dasar oleh sekurang-kurangnya 1/3 dari jumlah anggota MPR</strong>, memilih dan dipilih, membela diri, imunitas, protokoler, serta keuangan dan administratif.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena hak inisiatif amandemen merupakan wewenang substantif anggota MPR yang dijamin oleh UUD 1945 asalkan memenuhi kuorum usulan formal sekurang-kurangnya sepertiga anggota secara tertulis.</p><p>Jawaban tepat: <strong>D. Usul perubahan pasal diajukan sekurang-kurangnya 1/3 anggota MPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena anggota MPR tidak berwenang menerbitkan dekrit pembubaran lembaga negara.</li><li><strong>B</strong>: Kurang tepat karena pembatalan putusan peradilan merupakan wewenang hierarki badan yudikatif (kasasi di MA).</li><li><strong>C</strong>: Kurang tepat karena penetapan retribusi daerah merupakan wewenang DPRD bersama kepala daerah.</li><li><strong>E</strong>: Kurang tepat karena pengangkatan direksi BUMN merupakan ranah kementerian BUMN dan rapat pemegang saham.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Mengajukan usul perubahan pasal-pasal Undang-Undang Dasar oleh sekurangkurangnya 1/3</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Selain hak mengajukan usul perubahan UUD dan hak membela diri, anggota MPR dilengkapi dengan hak...", "options": ["Veto mutlak terhadap regulasi menteri, eksekutif daerah, dan kejaksaan", "Penyidikan langsung terhadap pelanggaran perizinan lingkungan hidup daerah", "Pemberian grasi massal bagi narapidana tindak pidana kepabeanan negara", "Penggeledahan mandiri terhadap sarana kantor pemerintahan tingkat wilayah", "Imunitas, protokoler, serta hak keuangan dan administratif kedinasan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Proteksi dan Kelengkapan Anggota MPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hak-hak anggota MPR menurut UUD 1945 mencakup hak memilih dan dipilih, membela diri, <strong>imunitas, protokoler, dan keuangan dan administratif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena hak imunitas melindungi anggota dewan dari tuntutan hukum atas pernyataan yang disampaikan dalam sidang kedewanan, hak protokoler menjamin tata kehormatan kedudukan negara, dan hak keuangan/administratif menunjang operasional pengabdian kedewanan.</p><p>Jawaban tepat: <strong>E. Imunitas, protokoler, serta hak keuangan dan administratif kedinasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena anggota MPR tidak memiliki hak veto terhadap peraturan menteri atau eksekutif daerah.</li><li><strong>B</strong>: Kurang tepat karena penyidikan lingkungan hidup merupakan wewenang PPNS lingkungan hidup dan kepolisian.</li><li><strong>C</strong>: Kurang tepat karena pemberian grasi merupakan wewenang presiden atas pertimbangan Mahkamah Agung.</li><li><strong>D</strong>: Kurang tepat karena penggeledahan merupakan wewenang aparat penegak hukum pro justitia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 46) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keuangan dan administratif</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Tugas dan wewenang pokok Presiden Republik Indonesia selaku Kepala Pemerintahan dalam bidang eksekutif adalah...", "options": ["Menyelenggarakan penuntutan perkara kejahatan korporasi di peradilan tinggi", "Menjalankan kekuasaan pemerintahan negara menurut Undang-undang Dasar", "Meninjau kembali putusan sengketa hasil pemilihan kepala daerah provinsi", "Mengadili pelanggaran kode etik para hakim agung di lingkungan peradilan", "Mengesahkan kepengurusan partai politik oposisi secara mutlak otoritatif"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tugas Presiden Menjalankan Pemerintahan Menurut UUD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas dan wewenang Presiden selaku Kepala Pemerintahan nomor satu adalah <strong>menjalankan kekuasaan pemerintahan negara menurut Undang-undang Dasar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Pasal 4 ayat (1) UUD 1945 memberikan mandat kekuasaan eksekutif kepada Presiden untuk memimpin birokrasi dan administrasi negara dengan bersandar teguh pada koridor konstitusi.</p><p>Jawaban tepat: <strong>B. Menjalankan kekuasaan pemerintahan negara menurut Undang-undang Dasar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penuntutan pidana dijalankan oleh lembaga Kejaksaan Republik Indonesia.</li><li><strong>C</strong>: Kurang tepat karena pengujian sengketa hasil pemilihan umum merupakan wewenang Mahkamah Konstitusi.</li><li><strong>D</strong>: Kurang tepat karena pengawasan etik hakim agung merupakan ranah wewenang Komisi Yudisial.</li><li><strong>E</strong>: Kurang tepat karena pengesahan badan hukum parpol tunduk pada aturan administrasi kementerian hukum, bukan keputusan personal presiden.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menjalankan kekuasaan pemerintahan negara menurut Undang-undang Dasar</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 5 ayat (2) UUD 1945, wewenang regulasi Presiden dalam rangka implementasi undang-undang adalah...", "options": ["Menerbitkan fatwa hukum yang mengikat jalannya persidangan perkara perdata", "Mengubah substansi pasal undang-undang secara langsung lewat surat edaran", "Menetapkan PP untuk menjalankan undang-undang sebagaimana mestinya", "Menetapkan traktat bilateral tanpa memerlukan koordinasi perwakilan kementerian", "Menginstruksikan pembatalan vonis hakim pengadilan umum demi stabilitas"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewenangan Menetapkan Peraturan Pemerintah (PP)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 5 ayat (2) UUD 1945, Presiden bertugas <strong>menetapkan Peraturan Pemerintah untuk menjalankan undang-undang sebagaimana mestinya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Peraturan Pemerintah (PP) merupakan instrumen pelaksana yang menjabarkan norma undang-undang agar dapat diterapkan secara teknis operasional oleh kementerian dan lembaga.</p><p>Jawaban tepat: <strong>C. Menetapkan PP untuk menjalankan undang-undang sebagaimana mestinya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penerbitan fatwa peradilan merupakan kompetensi internal lembaga peradilan.</li><li><strong>B</strong>: Kurang tepat karena surat edaran tidak berkedudukan sederajat dengan UU dan dilarang mengubah isi pasal UU.</li><li><strong>D</strong>: Kurang tepat karena traktat bilateral wajib melalui mekanisme perundingan dan ratifikasi diplomatik.</li><li><strong>E</strong>: Kurang tepat karena eksekutif dilarang mengintervensi atau membatalkan vonis peradilan yang merdeka.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menetapkan Peraturan Pemerintah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Keterlibatan Presiden Republik Indonesia dalam pelaksanaan fungsi legislasi diatur dalam konstitusi melalui hak untuk...", "options": ["Menunda pengundangan naskah undang-undang yang telah disetujui bersama dewan", "Mengesahkan undang-undang tanpa melibatkan pembahasan dengan komisi dewan", "Membatalkan langsung undang-undang yang diterbitkan pada masa pemerintahan lalu", "Mengajukan dan membahas rancangan undang-undang bersama DPR guna persetujuan", "Menetapkan sanksi pidana dalam keputusan presiden di luar ketentuan UU"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewenangan Legislatif Presiden Bersama DPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Fungsi legislatif Presiden selaku Kepala Pemerintahan tercermin dalam hak untuk <strong>mengajukan dan membahas rancangan undang-undang bersama DPR</strong> guna mendapatkan persetujuan bersama.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena proses pembentukan undang-undang di Indonesia melibatkan kerja sama setara antara Presiden dan DPR. Presiden berhak mengusulkan RUU inisiatif pemerintah serta wajib membahasnya bersama DPR.</p><p>Jawaban tepat: <strong>D. Mengajukan dan membahas rancangan undang-undang bersama DPR guna persetujuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena UU yang telah disetujui bersama otomatis sah berlaku setelah 30 hari meskipun tidak ditandatangani.</li><li><strong>B</strong>: Kurang tepat karena pembentukan UU wajib dibahas bersama DPR untuk memperoleh persetujuan bersama.</li><li><strong>C</strong>: Kurang tepat karena pembatalan UU merupakan wewenang pengujian yudisial di Mahkamah Konstitusi.</li><li><strong>E</strong>: Kurang tepat karena penetapan sanksi pidana merupakan materi muatan undang-undang atau perda, bukan Keppres.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Mengajukan dan membahas rancangan undang-undang bersama DPR</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Dalam hal ihwal kegentingan yang memaksa dan menuntut payung hukum segera, instrumen regulasi darurat yang berhak diterbitkan Presiden adalah...", "options": ["Surat keputusan kementerian koordinator bidang perekonomian negara", "Nota kesepahaman antarfraksi partai politik pemilik kursi mayoritas", "Instruksi gubernur militer untuk membekukan aktivitas lembaga daerah", "Naskah perjanjian perbatasan wilayah dengan negara sahabat tetangga", "Menetapkan Peraturan Pemerintah Pengganti Undang-undang (Perpu) secara mandiri"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewenangan Menetapkan Perpu dalam Kegentingan Memaksa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 22 UUD 1945, dalam hal ihwal kegentingan yang memaksa, Presiden berhak <strong>menetapkan Peraturan Pemerintah Pengganti Undang-undang (Perpu)</strong> yang kemudian harus mendapat persetujuan DPR pada masa sidang berikutnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Perpu memberikan kewenangan darurat kepada Presiden untuk segera mengatasi situasi darurat tanpa harus menunggu proses legislasi biasa di DPR yang memakan waktu panjang.</p><p>Jawaban tepat: <strong>E. Menetapkan Peraturan Pemerintah Pengganti Undang-undang (Perpu) secara mandiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena keputusan menteri koordinator tidak berderajat setingkat undang-undang.</li><li><strong>B</strong>: Kurang tepat karena nota kesepahaman antarfraksi partai bukan merupakan produk peraturan perundangan negara.</li><li><strong>C</strong>: Kurang tepat karena gubernur militer tidak berwenang menerbitkan produk hukum berderajat undang-undang.</li><li><strong>D</strong>: Kurang tepat karena perjanjian perbatasan memerlukan persetujuan DPR dan ratifikasi undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menetapkan Peraturan Pemerintah Pengganti Undang-undang (Perpu)</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kewenangan fiskal Presiden Republik Indonesia selaku Kepala Pemerintahan dalam pengelolaan anggaran negara diwujudkan melalui tugas untuk...", "options": ["Mengajukan dan membahas usul RAPBN bersama DPR dengan pertimbangan DPD", "Mengesahkan penarikan pinjaman luar negeri tanpa keterlibatan dewan perwakilan", "Menentukan alokasi pagu dana kementerian secara tertutup tanpa audit publik", "Mengalihkan surplus kas perbendaharaan negara ke rekening pribadi pimpinan", "Menunda audit pertanggungjawaban belanja yang diajukan oleh auditor BPK"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wewenang Presiden Dalam Penganggaran Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas Presiden selaku Kepala Pemerintahan nomor 5 adalah <strong>mengajukan dan membahas usul RAPBN bersama DPR</strong> dengan memperhatikan pertimbangan DPD.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penyusunan anggaran negara (APBN) merupakan tanggung jawab eksekutif yang diajukan oleh Presiden setiap tahun (melalui Pidato Pengantar Nota Keuangan) untuk dibahas dan disetujui bersama DPR.</p><p>Jawaban tepat: <strong>A. Mengajukan dan membahas usul RAPBN bersama DPR dengan pertimbangan DPD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena pembuatan perjanjian utang negara wajib tunduk pada ketentuan undang-undang dan pengawasan DPR.</li><li><strong>C</strong>: Kurang tepat karena alokasi anggaran dibahas terbuka dan transparan di komisi dan badan anggaran dewan.</li><li><strong>D</strong>: Kurang tepat karena kas perbendaharaan negara dikelola melalui Kas Umum Negara yang terpisah dari urusan pribadi.</li><li><strong>E</strong>: Kurang tepat karena pemerintah wajib membuka akses pertanggungjawaban belanja kepada auditor BPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Mengajukan dan membahas usul RAPBN bersama DPR</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 10 UUD 1945, wewenang Presiden Republik Indonesia dalam kapasitasnya sebagai Kepala Negara adalah...", "options": ["Memimpin peradilan mahkamah militer agung dalam pengadilan desersi pasukan", "Memegang kekuasaan tertinggi atas Angkatan Darat, Laut, dan Udara", "Menetapkan pangkat perwira menengah di daerah tanpa melalui seleksi dinas", "Menginstruksikan mobilisasi militer untuk kepentingan kampanye pemilihan umum", "Menyita sarana transportasi sipil tanpa kompensasi pada waktu kondisi damai"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wewenang Panglima Tertinggi Angkatan Bersenjata</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden sebagai kepala negara bertugas <strong>memegang kekuasaan tertinggi atas Angkatan Darat, Angkatan Laut, dan Angkatan Udara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Presiden merupakan Panglima Tertinggi (*Supreme Commander*) Tentara Nasional Indonesia yang memegang supremasi sipil atas kekuatan militer dalam kerangka pertahanan negara.</p><p>Jawaban tepat: <strong>B. Memegang kekuasaan tertinggi atas Angkatan Darat, Laut, dan Udara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena peradilan militer dipimpin oleh hakim militer di bawah yurisdiksi teknis Mahkamah Agung.</li><li><strong>C</strong>: Kurang tepat karena kepangkatan perwira menengah diatur menurut mekanisme meritokrasi dan regulasi internal TNI.</li><li><strong>D</strong>: Kurang tepat karena TNI bersikap netral dalam politik praktis dan pemilu.</li><li><strong>E</strong>: Kurang tepat karena penyitaan tanpa kompensasi bertentangan dengan jaminan hak kebendaan warga negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Memegang kekuasaan tertinggi atas Angkatan Darat, Angkatan Laut, dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kewenangan Presiden dalam Pasal 11 UUD 1945 terkait pernyataan perang dan perjanjian internasional wajib dilaksanakan melalui syarat...", "options": ["Meminta pertimbangan tertulis dari dewan pimpinan mahkamah konstitusi", "Melakukan konsultasi tertutup dengan komite pertahanan negara tetangga", "Menyatakan perang dan membuat perdamaian atas persetujuan DPR", "Menunggu persetujuan aklamasi dari seluruh pimpinan satuan kerja dinas", "Mendapatkan rekomendasi mandiri dari dewan pertimbangan daerah provinsi"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pernyataan Perang dan Hubungan Internasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden selaku Kepala Negara berwenang: <strong>dengan persetujuan DPR, menyatakan perang, membuat perdamaian dan perjanjian internasional dengan negara lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena keputusan menyatakan perang dan perjanjian yang berdampak luas bagi kehidupan rakyat menyangkut beban keuangan negara serta kedaulatan, sehingga mutlak membutuhkan persetujuan wakil rakyat di DPR.</p><p>Jawaban tepat: <strong>C. Menyatakan perang dan membuat perdamaian atas persetujuan DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena persetujuan konstitusional berasal dari DPR, bukan dari Mahkamah Konstitusi.</li><li><strong>B</strong>: Kurang tepat karena konsultasi dengan dewan parlemen nasional adalah keharusan, bukan dengan komite asing.</li><li><strong>D</strong>: Kurang tepat karena persetujuan pimpinan satuan kerja dinas bukan syarat ketatanegaraan hubungan luar negeri.</li><li><strong>E</strong>: Kurang tepat karena DPD memberikan pertimbangan di bidang legislasi otonomi daerah, bukan persetujuan perang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dengan persetujuan DPR, menyatakan perang, membuat perdamaian dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dalam mengangkat duta dan konsul untuk perwakilan diplomatik di negara sahabat, Presiden wajib menempuh prosedur...", "options": ["Menyerahkan proses seleksi sepenuhnya kepada organisasi profesi advokat", "Meminta pengujian kepatutan administratif kepada badan pemeriksa keuangan", "Mendapatkan rekomendasi mutlak dari komisi yudisial bidang pengawasan hakim", "Mengangkat duta dan konsul dengan memperhatikan pertimbangan DPR", "Mengangkat calon secara mandiri tanpa memerlukan pertimbangan lembaga dewan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengangkatan Duta dan Konsul Berdasarkan Pasal 13</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 13 UUD 1945 mengatur bahwa dalam mengangkat duta dan konsul, Presiden bertindak <strong>dengan memperhatikan pertimbangan Dewan Perwakilan Rakyat</strong>, serta menerima penempatan duta negara lain dengan memperhatikan pertimbangan DPR.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena perwakilan diplomatik mencerminkan wibawa dan kepentingan strategis bangsa di luar negeri, sehingga proses penempatannya melibatkan pertimbangan komisi terkait di DPR demi menjamin integritas dan kompetensi calon.</p><p>Jawaban tepat: <strong>D. Mengangkat duta dan konsul dengan memperhatikan pertimbangan DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penunjukan diplomat negara merupakan kewenangan eksekutif di bidang luar negeri.</li><li><strong>B</strong>: Kurang tepat karena BPK bertugas memeriksa keuangan dan tidak menguji kompetensi diplomat.</li><li><strong>C</strong>: Kurang tepat karena Komisi Yudisial bertugas di ranah kekuasaan kehakiman, bukan diplomatik.</li><li><strong>E</strong>: Kurang tepat karena konstitusi mewajibkan Presiden memperhatikan pertimbangan DPR dalam mengangkat duta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dengan memperhatikan pertimbangan Dewan Perwakilan Rakyat, mengangkat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 14 ayat (1) UUD 1945, wewenang Presiden dalam memberikan pengampunan hukum berupa grasi dan rehabilitasi dilaksanakan...", "options": ["Secara mandiri tanpa memerlukan pertimbangan lembaga peradilan manapun", "Melalui referendum umum masyarakat pemilih di wilayah terjadinya pidana", "Atas instruksi tertulis dari komite etik asosiasi advokat seluruh wilayah", "Setelah mendapatkan persetujuan tertulis dari seluruh anggota parlemen", "Memberi grasi dan rehabilitasi dengan pertimbangan Mahkamah Agung"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Prerogatif Hukum Grasi dan Rehabilitasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden selaku Kepala Negara berwenang: <strong>dengan memperhatikan pertimbangan Mahkamah Agung, memberi grasi, dan rehabilitasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena grasi (pengampunan hukuman) dan rehabilitasi (pemulihan nama baik) berkaitan erat dengan putusan peradilan berkekuatan hukum tetap, sehingga pertimbangan yuridis Mahkamah Agung mutlak diperhatikan oleh Presiden sebelum mengambil keputusan.</p><p>Jawaban tepat: <strong>E. Memberi grasi dan rehabilitasi dengan pertimbangan Mahkamah Agung</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena konstitusi secara eksplisit mensyaratkan pertimbangan dari Mahkamah Agung.</li><li><strong>B</strong>: Kurang tepat karena mekanisme referendum grasi tidak dikenal dalam sistem ketatanegaraan Indonesia.</li><li><strong>C</strong>: Kurang tepat karena asosiasi advokat tidak memiliki kewenangan konstitusional dalam pemberian grasi.</li><li><strong>D</strong>: Kurang tepat karena persetujuan/pertimbangan DPR dipersyaratkan untuk pemberian amnesti dan abolisi, bukan grasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dengan memperhatikan pertimbangan Mahkamah Agung, memberi grasi, dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Ketentuan Pasal 14 ayat (2) UUD 1945 menegaskan bahwa pemberian pengampunan massal berupa amnesti dan abolisi oleh Presiden dilakukan...", "options": ["Memberi amnesti dan abolisi dengan memperhatikan pertimbangan DPR", "Melalui penetapan keputusan pengadilan tinggi tata usaha negara", "Atas pertimbangan mutlak ketua komisi pemilihan umum pusat", "Berdasarkan permohonan tertulis dari majelis hakim mahkamah agung", "Secara mandiri tanpa melibatkan pandangan dari dewan perwakilan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemberian Amnesti dan Abolisi Berdasarkan Pasal 14 Ayat 2</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden berwenang: <strong>dengan memperhatikan pertimbangan Dewan Perwakilan Rakyat, memberi amnesti dan abolisi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena amnesti (penghapusan akibat hukum tindak pidana) dan abolisi (peniadaan penuntutan pidana) biasanya berkaitan dengan tindak pidana berdimensi politik (seperti rekonsiliasi pasca-konflik). Oleh karena itu, pertimbangan Dewan Perwakilan Rakyat sebagai representasi politik rakyat sangat esensial.</p><p>Jawaban tepat: <strong>A. Memberi amnesti dan abolisi dengan memperhatikan pertimbangan DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena amnesti dan abolisi bukan produk putusan peradilan PTUN.</li><li><strong>C</strong>: Kurang tepat karena KPU bertugas menyelenggarakan pemilu, bukan mengurusi amnesti pidana.</li><li><strong>D</strong>: Kurang tepat karena MA memberikan pertimbangan untuk grasi dan rehabilitasi, sedangkan amnesti/abolisi ke DPR.</li><li><strong>E</strong>: Kurang tepat karena pemberian amnesti/abolisi dilarang dilakukan tanpa memperhatikan pertimbangan DPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 47) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dengan memperhatikan pertimbangan Dewan Perwakilan Rakyat, memberi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan UUD 1945, jaminan kemandirian lembaga Dewan Perwakilan Rakyat ditegaskan melalui asas bahwa...", "options": ["Pimpinan DPR berhak membubarkan kabinet kementerian secara administratif", "Anggota dewan memegang kekuasaan peradilan banding perkara perdata daerah", "DPR tidak dapat dibekukan atau dibubarkan oleh Presiden dalam kondisi apa pun", "Seluruh keputusan komisi legislatif bersifat mutlak tanpa pengujian konstitusi", "DPR berwenang menetapkan peraturan perundang-undangan tanpa tandatangan Presiden"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jaminan Konstitusional Kedudukan DPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu kedudukan pokok DPR dalam konstitusi adalah <strong>DPR tidak dapat dibekukan atau dibubarkan oleh Presiden</strong>, yang menegaskan kedudukan parlemen yang kokoh dan independen.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena konstitusi melarang Presiden membekukan parlemen demi menjaga kemurnian sistem pemerintahan presidensial dan mencegah kesewenang-wenangan eksekutif seperti peristiwa pembubaran DPR pada era demokrasi terpimpin.</p><p>Jawaban tepat: <strong>C. DPR tidak dapat dibekukan atau dibubarkan oleh Presiden dalam kondisi apa pun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena DPR tidak berwenang membubarkan jajaran kabinet menteri.</li><li><strong>B</strong>: Kurang tepat karena kekuasaan peradilan banding merupakan wewenang pengadilan tinggi di bawah Mahkamah Agung.</li><li><strong>D</strong>: Kurang tepat karena produk legislasi dewan tetap dapat diuji konstitusionalitasnya di Mahkamah Konstitusi.</li><li><strong>E</strong>: Kurang tepat karena pembentukan undang-undang mensyaratkan pembahasan bersama antara DPR dan Presiden.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>DPR tidak dapat dibekukan atau dibubarkan oleh Presiden</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Tiga fungsi konstitusional yang diemban oleh Dewan Perwakilan Rakyat menurut Pasal 20A ayat (1) UUD 1945 adalah...", "options": ["Fungsi peradilan, penuntutan pidana, dan administrasi kepegawaian nasional", "Fungsi penyelidikan etik, audit perbankan, dan pengangkatan perwira militer", "Fungsi eksekutif, koordinasi moneter, serta diplomasi pertahanan kawasan", "Fungsi legislasi, fungsi anggaran, dan fungsi pengawasan terhadap pemerintah", "Fungsi konstitutif, penegakan disiplin birokrasi, dan bantuan hukum daerah"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Fungsi Utama Dewan Perwakilan Rakyat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dewan Perwakilan Rakyat (DPR) memiliki tiga fungsi pokok yaitu fungsi <strong>legislasi, anggaran, dan pengawasan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena fungsi legislasi berfokus pada pembentukan undang-undang, fungsi anggaran berfokus pada penetapan APBN, dan fungsi pengawasan berfokus pada kontrol atas jalannya undang-undang serta kebijakan pemerintah.</p><p>Jawaban tepat: <strong>D. Fungsi legislasi, fungsi anggaran, dan fungsi pengawasan terhadap pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena peradilan dan penuntutan merupakan wewenang MA dan Kejaksaan.</li><li><strong>B</strong>: Kurang tepat karena audit perbankan dijalankan OJK dan BI, bukan DPR.</li><li><strong>C</strong>: Kurang tepat karena fungsi eksekutif dan moneter dijalankan oleh Presiden dan Bank Indonesia.</li><li><strong>E</strong>: Kurang tepat karena fungsi konstitutif merupakan kewenangan MPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Fungsi DPR: • Legislasi;</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kewajiban persidangan reguler bagi Dewan Perwakilan Rakyat diatur dalam konstitusi secara tegas yaitu...", "options": ["Bersidang satu kali setiap triwulan di kantor perwakilan tingkat daerah", "Berkumpul secara berkala enam bulan sekali atas undangan menteri keuangan", "Mengadakan rapat kerja setiap permulaan semester anggaran belanja negara", "Menyelenggarakan sidang pleno lima tahun sekali bersama pasangan kepresidenan", "Bersidang sedikitnya sekali dalam setahun untuk melaksanakan fungsi dewan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Periodesasi Sidang Wajib Dewan Perwakilan Rakyat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas dan wewenang DPR nomor satu adalah <strong>bersidang sedikitnya sekali dalam setahun</strong> di ibu kota negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Pasal 19 ayat (3) UUD 1945 menggariskan bahwa DPR bersidang sedikitnya sekali dalam setahun guna memastikan kontinuitas fungsi pengawasan, legislasi, dan anggaran.</p><p>Jawaban tepat: <strong>E. Bersidang sedikitnya sekali dalam setahun untuk melaksanakan fungsi dewan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena aturan konstitusi menetapkan frekuensi sidang sedikitnya sekali setahun di ibu kota.</li><li><strong>B</strong>: Kurang tepat karena sidang dewan dijalankan mandiri berdasarkan jadwal masa sidang parlemen.</li><li><strong>C</strong>: Kurang tepat karena rapat kerja semesteran diatur dalam tata tertib operasional fraksi/komisi.</li><li><strong>D</strong>: Kurang tepat karena sidang lima tahun sekali adalah ketentuan persidangan MPR, bukan DPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Bersidang sedikitnya sekali dalam setahun</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan wewenang legislasi oleh Dewan Perwakilan Rakyat diwujudkan melalui mekanisme konstitusional yaitu...", "options": ["Membentuk undang-undang yang dibahas bersama Presiden", "Menetapkan peraturan pemerintah secara mandiri saat situasi keamanan kondusif", "Mengesahkan surat edaran kementerian menjadi undang-undang organik negara", "Mengubah tata urutan perundang-undangan melalui keputusan pimpinan komisi", "Membatalkan undang-undang yang sedang diuji materiil di mahkamah konstitusi"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wewenang Legislasi DPR dan Persetujuan Bersama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>DPR memegang kekuasaan membentuk undang-undang, yaitu <strong>membentuk undang-undang yang dibahas dengan Presiden untuk mendapat persetujuan bersama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Pasal 20 ayat (1) dan (2) UUD 1945 menegaskan kekuasaan pembentukan undang-undang berada pada DPR dan setiap rancangan undang-undang wajib dibahas bersama Presiden untuk mendapat persetujuan bersama.</p><p>Jawaban tepat: <strong>A. Membentuk undang-undang yang dibahas bersama Presiden</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena penetapan Peraturan Pemerintah merupakan wewenang Presiden.</li><li><strong>C</strong>: Kurang tepat karena surat edaran menteri bukan materi rancangan undang-undang.</li><li><strong>D</strong>: Kurang tepat karena hierarki perundang-undangan diatur dalam undang-undang pembentukan peraturan.</li><li><strong>E</strong>: Kurang tepat karena pembatalan undang-undang yang bertentangan dengan UUD adalah kewenangan MK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Membentuk undang-undang yang dibahas dengan Presiden untuk mendapat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C3 - Aplikasi", "question": "Ketika Presiden menetapkan Peraturan Pemerintah Pengganti Undang-Undang (Perpu), wewenang yang wajib dijalankan DPR pada masa sidang berikutnya adalah...", "options": ["Menyerahkan naskah Perpu tersebut kepada kejaksaan agung untuk penuntutan", "Membahas dan memberi persetujuan atau penolakan atas Perppu", "Menyusun permohonan pembatalan Perpu kepada majelis hakim pengadilan tinggi", "Menginstruksikan aparatur daerah guna memboikot pelaksanaan isi naskah perpu", "Menetapkan sanksi administratif langsung terhadap menteri penerbit naskah perpu"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Persetujuan Terhadap Perpu Oleh Dewan Perwakilan Rakyat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas DPR mencakup <strong>membahas dan memberikan persetujuan peraturan pemerintah pengganti undang-undang (Perpu)</strong> pada masa persidangan berikutnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Perpu merupakan produk darurat eksekutif yang memerlukan kontrol legislatif. Jika DPR memberikan persetujuan, Perpu ditetapkan menjadi Undang-Undang; jika tidak disetujui, Perpu wajib dicabut.</p><p>Jawaban tepat: <strong>B. Membahas dan memberi persetujuan atau penolakan atas Perppu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kejaksaan tidak berwenang menguji keabsahan regulasi perpu.</li><li><strong>C</strong>: Kurang tepat karena pengadilan tinggi menangani sengketa peradilan umum, bukan legislasi darurat.</li><li><strong>D</strong>: Kurang tepat karena perpu yang berlaku sah mengikat seluruh aparatur sebelum dicabut resmi.</li><li><strong>E</strong>: Kurang tepat karena penerbitan perpu merupakan hak konstitusional presiden dan bukan pelanggaran menteri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>memberikan persetujuan peraturan pemerintah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dalam pembahasan RUU APBN serta RUU yang berkaitan dengan pajak, pendidikan, dan agama, DPR secara konstitusional bertugas...", "options": ["Mendelegasikan wewenang persetujuan akhir sepenuhnya kepada komite eksekutif", "Menyusun rancangan belanja daerah tanpa berkoordinasi dengan pemerintah pusat", "Memperhatikan pertimbangan DPD atas RUU APBN dan RUU bidang tertentu tersebut", "Menetapkan besaran tarif cukai komoditas impor secara rahasia tanpa persetujuan", "Mengalihkan belanja pendidikan menjadi pos bantuan modal swasta"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pertimbangan DPD Dalam Pembahasan RUU APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>DPR bertugas <strong>memperhatikan pertimbangan DPD atas RUU APBN</strong> dan RUU yang berkaitan dengan pajak, pendidikan, dan agama sebelum disahkan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena DPD mewakili aspirasi kepentingan wilayah daerah kepulauan. Pertimbangan DPD memberikan masukan substantif mengenai kebutuhan riil keuangan dan pembangunan daerah dalam postur anggaran nasional.</p><p>Jawaban tepat: <strong>C. Memperhatikan pertimbangan DPD atas RUU APBN dan RUU bidang tertentu tersebut</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kewenangan persetujuan akhir APBN tetap berada di tangan DPR bersama Presiden.</li><li><strong>B</strong>: Kurang tepat karena penyusunan belanja daerah (APBD) merupakan wewenang DPRD dan Pemda.</li><li><strong>D</strong>: Kurang tepat karena kebijakan fiskal dan tarif cukai dibahas secara terbuka dalam kerangka UU APBN.</li><li><strong>E</strong>: Kurang tepat karena anggaran pendidikan dijamin minimal 20% oleh konstitusi dan dilarang dialihkan serampangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Memperhatikan pertimbangan DPD atas RUU APBN</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Proses penetapan Anggaran Pendapatan dan Belanja Negara (APBN) menurut UUD 1945 dilaksanakan melalui kewenangan DPR untuk...", "options": ["Menerbitkan surat utang negara secara mandiri guna menutup defisit kas", "Menghentikan seluruh pencairan gaji pegawai negeri sipil di satuan kerja", "Mengesahkan laporan keuangan kementerian tanpa melalui telaah hasil BPK", "Menetapkan APBN bersama Presiden dengan memperhatikan pertimbangan DPD", "Menetapkan target penerimaan pajak daerah tanpa persetujuan kepala dinas"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penetapan Anggaran Pendapatan dan Belanja Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas DPR dalam fungsi anggaran adalah <strong>menetapkan APBN bersama Presiden dengan memperhatikan pertimbangan DPD</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena APBN merupakan wujud pengelolaan keuangan negara yang ditetapkan setiap tahun dengan undang-undang. DPR memegang hak anggaran untuk menyetujui atau menolak rancangan anggaran yang diajukan pemerintah.</p><p>Jawaban tepat: <strong>D. Menetapkan APBN bersama Presiden dengan memperhatikan pertimbangan DPD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penerbitan surat berharga negara merupakan kewenangan eksekutif kementerian keuangan.</li><li><strong>B</strong>: Kurang tepat karena belanja pegawai dijamin dalam alokasi pos belanja rutin APBN yang sah.</li><li><strong>C</strong>: Kurang tepat karena audit laporan pertanggungjawaban APBN wajib diperiksa oleh BPK.</li><li><strong>E</strong>: Kurang tepat karena pajak daerah diatur dalam perda dan kewenangan pemda setempat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menetapkan APBN bersama Presiden dengan memperhatikan pertimbangan DPD</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Ruang lingkup pelaksanaan fungsi pengawasan oleh Dewan Perwakilan Rakyat mencakup kegiatan untuk...", "options": ["Mengintervensi jalannya pemeriksaan perkara pidana di tingkat penyidikan", "Mengawasi tata peribadatan keagamaan masyarakat dalam kehidupan sehari-hari", "Memeriksa secara langsung pembukuan kas bendahara umum di daerah terpencil", "Menjatuhkan sanksi kurungan pidana kepada pimpinan lembaga swadaya masyarakat", "Mengawasi pelaksanaan UU, APBN, serta kebijakan pemerintah"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Pengawasan Dewan Perwakilan Rakyat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas pengawasan DPR adalah <strong>melaksanakan pengawasan terhadap pelaksanaan UU, APBN, serta kebijakan pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena fungsi pengawasan dewan ditujukan untuk memastikan eksekutif menjalankan undang-undang dan mengelola anggaran sesuai target yang telah ditetapkan dalam APBN secara transparan dan akuntabel.</p><p>Jawaban tepat: <strong>E. Mengawasi pelaksanaan UU, APBN, serta kebijakan pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena parlemen dilarang mengintervensi proses hukum pro justitia kepolisian dan kejaksaan.</li><li><strong>B</strong>: Kurang tepat karena kemerdekaan beribadah dijamin konstitusi dan bukan objek pengawasan politik DPR.</li><li><strong>C</strong>: Kurang tepat karena pemeriksaan pembukuan kas keuangan negara secara fisik merupakan tugas auditor BPK.</li><li><strong>D</strong>: Kurang tepat karena penjatuhan sanksi pidana merupakan kewenangan mutlak majelis hakim pengadilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Melaksanakan pengawasan terhadap pelaksanaan UU, APBN, serta kebijakan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Sesuai Pasal 23F UUD 1945, wewenang DPR dalam suksesi keanggotaan Badan Pemeriksa Keuangan adalah...", "options": ["Memilih anggota Badan Pemeriksa Keuangan dengan memperhatikan pertimbangan DPD", "Mengangkat langsung kerabat pimpinan komisi menjadi dewan pemeriksa pusat", "Mendelegasikan pemilihan auditor utama keuangan kepada asosiasi akuntan", "Meminta rekomendasi mutlak dari dewan pertimbangan kepresidenan negara", "Menetapkan susunan pimpinan BPK tanpa melalui tahapan uji kepatutan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemilihan Anggota Badan Pemeriksa Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas DPR dalam rekrutmen pimpinan lembaga negara adalah <strong>memilih anggota Badan Pemeriksa Keuangan dengan memperhatikan pertimbangan DPD</strong> untuk kemudian diresmikan oleh Presiden.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena keanggotaan BPK dipilih oleh DPR dengan masukan tertulis dari DPD guna memastikan integritas, kapasitas profesionalisme, dan independensi lembaga pemeriksa keuangan negara.</p><p>Jawaban tepat: <strong>A. Memilih anggota Badan Pemeriksa Keuangan dengan memperhatikan pertimbangan DPD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena pemilihan anggota BPK wajib melalui proses seleksi kepatutan yang objektif dan meritokratis.</li><li><strong>C</strong>: Kurang tepat karena mandat pemilihan anggota BPK dipegang oleh DPR, bukan asosiasi profesi.</li><li><strong>D</strong>: Kurang tepat karena DPD yang memberikan pertimbangan, bukan dewan pertimbangan kepresidenan.</li><li><strong>E</strong>: Kurang tepat karena calon anggota BPK wajib menjalani uji kepatutan dan kelayakan di komisi DPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dengan memperhatikan pertimbangan DPD</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Wewenang DPR dalam mendukung pembentukan lembaga Komisi Yudisial menurut UUD 1945 adalah...", "options": ["Memimpin sidang pemeriksaan etik hakim yang diadukan oleh masyarakat umum", "Menyetujui usulan pengangkatan dan pemberhentian anggota KY", "Menunjuk secara mandiri calon anggota komisi tanpa proses panitia seleksi", "Mengesahkan tata cara persidangan peradilan kasasi di lingkungan mahkamah", "Membatalkan berkas rekomendasi sanksi yang diajukan komisi ke mahkamah"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Persetujuan Keanggotaan Komisi Yudisial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas DPR nomor 11 adalah <strong>memberikan persetujuan kepada Presiden atas pengangkatan dan pemberhentian anggota Komisi Yudisial</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena anggota Komisi Yudisial diangkat dan diberhentikan oleh Presiden dengan persetujuan DPR. Keterlibatan DPR menjamin representasi publik dalam memilih figur penjaga etika peradilan kehakiman.</p><p>Jawaban tepat: <strong>B. Menyetujui usulan pengangkatan dan pemberhentian anggota KY</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena sidang pemeriksaan etik hakim dipimpin langsung oleh majelis kehormatan KY/MA.</li><li><strong>C</strong>: Kurang tepat karena seleksi awal dilakukan oleh panitia seleksi pemerintah sebelum diajukan ke DPR.</li><li><strong>D</strong>: Kurang tepat karena tata cara persidangan kasasi diatur dalam undang-undang MA dan hukum acara.</li><li><strong>E</strong>: Kurang tepat karena rekomendasi sanksi KY disampaikan kepada MA, bukan kepada DPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 48) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Memberikan persetujuan kepada Presiden atas pengangkatan dan pemberhentian</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Keterlibatan DPR dalam proses seleksi kepemimpinan puncak kekuasaan kehakiman diwujudkan melalui wewenang untuk...", "options": ["Menyelenggarakan ujian kelulusan teknis yudisial bagi para calon hakim agung", "Menjatuhkan vonis peradilan pidana khusus terhadap mantan menteri negara", "Menetapkan komposisi susunan majelis hakim kasasi perkara niaga nasional", "Memberikan persetujuan calon hakim agung yang diusulkan Komisi Yudisial", "Mengangkat calon hakim tingkat banding di seluruh wilayah peradilan militer"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Persetujuan Pengangkatan Hakim Agung Oleh DPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>DPR bertugas <strong>memberikan persetujuan calon hakim agung yang diusulkan Komisi Yudisial untuk ditetapkan sebagai hakim agung oleh Presiden</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena proses rekrutmen Hakim Agung melibatkan tiga cabang kekuasaan: KY menyaring calon, DPR memberikan persetujuan, dan Presiden menetapkannya secara administratif.</p><p>Jawaban tepat: <strong>D. Memberikan persetujuan calon hakim agung yang diusulkan Komisi Yudisial</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena seleksi teknis dan uji kelayakan substantif dijalankan oleh Komisi Yudisial.</li><li><strong>B</strong>: Kurang tepat karena penjatuhan vonis pidana murni merupakan wewenang majelis hakim pengadilan.</li><li><strong>C</strong>: Kurang tepat karena pembagian komposisi majelis hakim kasasi merupakan kewenangan Ketua Mahkamah Agung.</li><li><strong>E</strong>: Kurang tepat karena pengangkatan hakim tingkat banding merupakan kewenangan internal MA.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Memberikan persetujuan calon hakim agung yang diusulkan Komisi Yudisial</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dalam rangka pembentukan keanggotaan Mahkamah Konstitusi, wewenang konstitusional yang dijalankan DPR adalah...", "options": ["Menunjuk seluruh sembilan orang hakim konstitusi dari kalangan pimpinan partai", "Memberhentikan secara mendadak hakim konstitusi yang berbeda pendapat hukum", "Menguji kelayakan putusan peradilan konstitusi mengenai pengujian undang-undang", "Membatalkan hasil putusan sengketa pemilihan umum yang ditetapkan oleh mahkamah", "Memilih tiga calon hakim konstitusi dan mengajukannya ke Presiden"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengusulan Hakim Konstitusi Oleh DPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mahkamah Konstitusi mempunyai 9 orang hakim konstitusi, di mana DPR berwenang <strong>memilih tiga orang calon anggota hakim konstitusi dan mengajukannya kepada Presiden untuk ditetapkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena komposisi 9 hakim MK mencerminkan representasi trias politika yang seimbang: 3 orang diajukan oleh DPR, 3 orang diajukan oleh Presiden, dan 3 orang diajukan oleh Mahkamah Agung.</p><p>Jawaban tepat: <strong>E. Memilih tiga calon hakim konstitusi dan mengajukannya ke Presiden</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena DPR hanya mengajukan tiga orang calon, bukan sembilan orang.</li><li><strong>B</strong>: Kurang tepat karena pemberhentian hakim konstitusi diatur ketat oleh undang-undang melalui majelis kehormatan MK.</li><li><strong>C</strong>: Kurang tepat karena putusan MK bersifat final dan mengikat serta tidak dapat diuji ulang oleh DPR.</li><li><strong>D</strong>: Kurang tepat karena putusan sengketa hasil pemilu di MK tidak dapat dibatalkan oleh parlemen.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Memilih tiga orang calon anggota hakim konstitusi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 20A ayat (2) UUD 1945, hak-hak yang dimiliki oleh lembaga DPR dalam rangka pelaksanaan fungsi pengawasan adalah...", "options": ["Hak interpelasi, hak angket, dan hak menyatakan pendapat kepada pemerintah", "Hak veto, hak amnesti massal, dan hak penuntutan kejahatan politik luar negeri", "Hak banding, hak kasasi yudisial, serta hak peninjauan kembali perkara pajak", "Hak prerogatif, hak grasi kepala negara, serta hak pengangkatan menteri", "Hak eksekusi aset daerah, hak audit mandiri, dan hak pembekuan korporasi"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak-Hak Kelembagaan Dewan Perwakilan Rakyat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam menjalankan fungsinya, lembaga DPR mempunyai hak: <strong>interpelasi, angket, dan menyatakan pendapat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena hak interpelasi adalah hak meminta keterangan kepada pemerintah, hak angket adalah hak menyelidiki kebijakan strategis pemerintah yang diduga melanggar undang-undang, dan hak menyatakan pendapat adalah hak menyampaikan sikap resmi lembaga dewan.</p><p>Jawaban tepat: <strong>A. Hak interpelasi, hak angket, dan hak menyatakan pendapat kepada pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena hak veto dan amnesti bukan merupakan hak kelembagaan DPR melainkan ranah eksekutif.</li><li><strong>C</strong>: Kurang tepat karena banding dan kasasi merupakan jalur hukum peradilan di badan yudikatif.</li><li><strong>D</strong>: Kurang tepat karena hak prerogatif grasi dan pengangkatan kabinet merupakan hak presiden.</li><li><strong>E</strong>: Kurang tepat karena eksekusi aset dan audit keuangan bukan instrumen hak kelembagaan DPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>DPR mempunyai hak: • Interpelasi;</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Setiap anggota Dewan Perwakilan Rakyat secara perorangan dibekali oleh konstitusi dengan hak...", "options": ["Menetapkan vonis perkara kejahatan, membubarkan parpol, dan hak menyita aset", "Mengajukan usul RUU, mengajukan pertanyaan, menyampaikan pendapat, dan imunitas", "Membekukan organisasi kemasyarakatan, hak menyadap telepon, dan hak protokoler", "Mereshuffle kabinet menteri, hak grasi, serta hak pembentukan daerah otonom", "Membatalkan perjanjian internasional, hak ekstradisi, dan hak suaka politik"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak-Hak Individu Anggota Dewan Perwakilan Rakyat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Anggota DPR mempunyai hak individu yang dijamin UUD 1945, yaitu: <strong>mengajukan usul RUU, mengajukan pertanyaan, menyampaikan usul dan pendapat, dan imunitas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena hak-hak ini menjamin setiap wakil rakyat dapat menyalurkan aspirasi daerah pemilihannya secara vokal dan kritis tanpa khawatir dikriminalisasi berkat adanya perlindungan hak imunitas.</p><p>Jawaban tepat: <strong>B. Mengajukan usul RUU, mengajukan pertanyaan, menyampaikan pendapat, dan imunitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena vonis pidana dan pembubaran parpol merupakan ranah kekuasaan peradilan.</li><li><strong>C</strong>: Kurang tepat karena pembekuan ormas dan penyadapan merupakan ranah penegak hukum dan pengadilan.</li><li><strong>D</strong>: Kurang tepat karena perombakan kabinet merupakan hak prerogatif presiden.</li><li><strong>E</strong>: Kurang tepat karena ekstradisi dan suaka politik merupakan kewenangan hubungan luar negeri pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Anggota DPR mempunyai hak: • Imunitas.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dalam struktur ketatanegaraan Indonesia pasca-amandemen UUD 1945, Dewan Perwakilan Daerah (DPD) diposisikan sebagai...", "options": ["Badan otonom eksekutif yang membawahi seluruh kepala dinas di provinsi", "Komisi pembantu kepresidenan dalam penetapan anggaran belanja militer", "Lembaga perwakilan daerah yang berkedudukan sebagai lembaga negara", "Lembaga peradilan adat tertinggi bagi penyelesaian sengketa tanah ulayat", "Majelis perwakilan partai politik tingkat wilayah luar pulau Jawa"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Dewan Perwakilan Daerah (DPD)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kedudukan DPD dalam UUD 1945 ditegaskan bahwa <strong>DPD merupakan lembaga perwakilan daerah yang berkedudukan sebagai lembaga negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena DPD dibentuk untuk memperkuat representasi teritorial daerah dalam pengambilan kebijakan di tingkat nasional, berstatus sejajar sebagai lembaga tinggi negara bersama lembaga lainnya.</p><p>Jawaban tepat: <strong>C. Lembaga perwakilan daerah yang berkedudukan sebagai lembaga negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena DPD adalah lembaga perwakilan legislatif, bukan bagian dinas eksekutif daerah.</li><li><strong>B</strong>: Kurang tepat karena DPD bukan komisi pembantu kepresidenan bidang militer.</li><li><strong>D</strong>: Kurang tepat karena DPD tidak berwenang mengadili perkara peradilan adat ulayat.</li><li><strong>E</strong>: Kurang tepat karena anggota DPD mewakili daerah provinsi secara independen non-partisan, bukan mewakili partai politik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>lembaga perwakilan daerah yang berkedudukan sebagai</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Anggota Dewan Perwakilan Daerah (DPD) memperoleh mandat keterwakilan melalui tata cara...", "options": ["Penunjukan langsung oleh gubernur dan dewan perwakilan rakyat daerah", "Musyawarah utusan adat kesultanan dan tokoh masyarakat di daerah masing-masing", "Seleksi berkas administrasi yang diselenggarakan oleh kementerian dalam negeri", "Dipilih dari setiap provinsi melalui pemilihan umum secara perseorangan", "Penugasan dari pimpinan fraksi partai politik peserta pemilu legislatif"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sistem Pemilihan Anggota Dewan Perwakilan Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 22C UUD 1945, anggota DPD <strong>dipilih dari setiap provinsi melalui pemilihan umum</strong> dari jalur perseorangan non-partisan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena legitimasi anggota DPD bertumpu langsung pada suara pemilih di tiap provinsi, berbeda dengan anggota DPR yang diajukan oleh partai politik.</p><p>Jawaban tepat: <strong>D. Dipilih dari setiap provinsi melalui pemilihan umum secara perseorangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena gubernur dan DPRD tidak berwenang menunjuk anggota DPD.</li><li><strong>B</strong>: Kurang tepat karena penetapan melalui musyawarah adat utusan golongan tidak lagi berlaku dalam UUD 1945 pasca-amandemen.</li><li><strong>C</strong>: Kurang tepat karena anggota DPD adalah pejabat politik hasil pemilu, bukan pegawai seleksi Kemendagri.</li><li><strong>E</strong>: Kurang tepat karena calon anggota DPD bukan merupakan utusan atau perwakilan fraksi partai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dipilih dari setiap provinsi melalui pemilihan umum</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Prinsip kesetaraan perwakilan wilayah dalam pembentukan Dewan Perwakilan Daerah ditegaskan melalui aturan bahwa...", "options": ["Provinsi dengan populasi penduduk terpadat memperoleh alokasi kursi terbanyak", "Ibu kota negara memiliki hak monopoli menentukan separuh dari total kursi dewan", "Provinsi kepulauan diberi kuota perwakilan dua kali lipat daratan", "Jumlah kursi dewan ditentukan besaran kontribusi pajak ke APBN", "Jumlah anggota DPD setiap provinsi sama dan berkeadilan merata"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Kesetaraan Keterwakilan Anggota DPD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketentuan konstitusi menegaskan bahwa <strong>jumlah anggota DPD di setiap provinsi sama</strong>, masing-masing berjumlah 4 orang anggota per provinsi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena representasi DPD menganut prinsip kesetaraan teritorial (*equal representation of regions*). Setiap provinsi, baik yang berpenduduk padat maupun jarang, memiliki bobot suara keterwakilan yang persis sama di forum DPD.</p><p>Jawaban tepat: <strong>E. Jumlah anggota DPD setiap provinsi sama dan berkeadilan merata</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena proporsionalitas jumlah penduduk diterapkan pada pemilihan DPR, bukan DPD.</li><li><strong>B</strong>: Kurang tepat karena ibu kota negara tidak memiliki hak istimewa memonopoli kursi keterwakilan dewan.</li><li><strong>C</strong>: Kurang tepat karena seluruh provinsi memperoleh kuota kursi yang setara tanpa diskriminasi geografis.</li><li><strong>D</strong>: Kurang tepat karena kontribusi pajak daerah bukan dasar penentuan alokasi kursi perwakilan politik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Jumlah anggota DPD di setiap provinsi sama</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 22C ayat (2) UUD 1945, batasan kuota seluruh anggota DPD secara nasional diatur agar...", "options": ["Jumlah seluruh anggota DPD paling banyak 1/3 jumlah anggota DPR", "Tepat menyamai separuh dari total pejabat struktural eselon satu kementerian", "Mencapai sekurang-kurangnya dua pertiga dari jumlah anggota parlemen nasional", "Dibatasi paling banyak seratus orang perwakilan dari pulau-pulau besar", "Ditetapkan secara bebas oleh dewan perwakilan rakyat daerah di setiap provinsi"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ambang Batas Maksimal Jumlah Anggota DPD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UUD 1945 mengatur bahwa jumlah anggota DPD dari setiap provinsi sama dan <strong>jumlah seluruh anggota DPD tidak boleh lebih dari 1/3 dari jumlah anggota DPR</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pembatasan rasio sepertiga ini dimaksudkan agar dalam forum persidangan gabungan MPR, komposisi perwakilan rakyat berbasis partai (DPR) dan perwakilan daerah (DPD) tetap berada dalam proporsi yang berimbang dan stabil.</p><p>Jawaban tepat: <strong>A. Jumlah seluruh anggota DPD paling banyak 1/3 jumlah anggota DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena jumlah anggota DPD tidak dikaitkan dengan jumlah pejabat struktural eselon.</li><li><strong>C</strong>: Kurang tepat karena ambang batas konstitusional adalah tidak lebih dari sepertiga (1/3), bukan 2/3.</li><li><strong>D</strong>: Kurang tepat karena kuota dihitung per provinsi (4 orang per provinsi), bukan dibatasi 100 orang pulau besar.</li><li><strong>E</strong>: Kurang tepat karena jumlah kuota diatur secara nasional dalam konstitusi dan undang-undang pemilu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tidak boleh lebih dari 1/3 dari jumlah anggota DPR</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Keterikatan kelembagaan antara Dewan Perwakilan Daerah dengan Majelis Permusyawaratan Rakyat ditegaskan melalui asas bahwa...", "options": ["Pimpinan DPD secara bergantian memegang jabatan sebagai perdana menteri", "Seluruh anggota DPD adalah anggota MPR bersama-sama dengan anggota DPR", "Anggota DPD baru dapat menghadiri sidang paripurna apabila diundang presiden", "Anggota DPD memiliki status peninjau tanpa hak suara dalam sidang UUD", "Keanggotaan DPD gugur otomatis manakala terpilih menjadi pimpinan komisi majelis"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Integrasi Anggota DPD Dalam Keanggotaan MPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kedudukan DPD dalam struktur perwakilan menegaskan bahwa <strong>seluruh anggota DPD adalah anggota MPR</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena MPR pasca-amandemen terdiri atas anggota DPR dan anggota DPD. Dengan demikian, setiap anggota DPD secara otomatis menyandang status dan hak penuh sebagai anggota MPR dalam menjalankan fungsi konstitutif.</p><p>Jawaban tepat: <strong>B. Seluruh anggota DPD adalah anggota MPR bersama-sama dengan anggota DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Indonesia tidak mengenal jabatan perdana menteri dalam sistem presidensial.</li><li><strong>C</strong>: Kurang tepat karena anggota DPD adalah anggota sah MPR yang berhak hadir penuh tanpa undangan presiden.</li><li><strong>D</strong>: Kurang tepat karena anggota DPD memiliki hak bicara dan hak suara yang setara di sidang majelis.</li><li><strong>E</strong>: Kurang tepat karena terpilih menjadi pimpinan majelis justru merupakan amanah penugasan kedewanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Seluruh anggota DPD adalah anggota MPR</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Ketentuan mengenai pemberhentian anggota Dewan Perwakilan Daerah dari masa jabatannya menurut UUD 1945 berlaku aturan bahwa...", "options": ["Pemberhentian diputuskan sekehendak pimpinan organisasi kemasyarakatan daerah", "Gubernur berwenang memecat anggota DPD yang berbeda pandangan pembangunan daerah", "Pemberhentian anggota DPD diatur syarat dan tata caranya dalam UU", "Pemberhentian wajib disetujui melalui pemungutan suara tertutup oleh kementerian", "Anggota dewan tidak dapat diberhentikan dalam kondisi apa pun hingga akhir hayat"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mekanisme Pemberhentian Anggota DPD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 22D ayat (4) UUD 1945 menetapkan anggota DPD dapat diberhentikan dari jabatannya, yang <strong>syarat-syarat dan tata caranya diatur dalam undang-undang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena konstitusi mendelegasikan pengaturan teknis pemberhentian anggota dewan (seperti karena tindak pidana, pelanggaran kode etik berat, atau berhalangan tetap) kepada undang-undang lembaga perwakilan (UU MD3).</p><p>Jawaban tepat: <strong>C. Pemberhentian anggota DPD diatur syarat dan tata caranya dalam UU</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena ormas daerah tidak memiliki kewenangan yuridis memberhentikan pejabat negara.</li><li><strong>B</strong>: Kurang tepat karena gubernur memegang kekuasaan eksekutif dan tidak berwenang memberhentikan wakil legislatif.</li><li><strong>D</strong>: Kurang tepat karena pemberhentian pejabat politik legislatif bukan ranah pemungutan suara kementerian.</li><li><strong>E</strong>: Kurang tepat karena anggota dewan dapat diberhentikan sesuai mekanisme hukum perundang-undangan yang sah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 49) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>caranya diatur dalam undang-undang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kewenangan legislatif yang dimiliki oleh Dewan Perwakilan Daerah menurut Pasal 22D ayat (1) UUD 1945 mencakup hak untuk...", "options": ["Mengesahkan undang-undang perpajakan tanpa melibatkan persetujuan dari DPR", "Menerbitkan obligasi daerah otonom tanpa melalui pengawasan kementerian keuangan", "Menetapkan struktur tarif bea masuk komoditas perdagangan lintas kepulauan", "Membatalkan peraturan daerah secara mandiri tanpa izin menteri", "Mengajukan RUU bidang otonomi dan perimbangan keuangan ke DPR"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Inisiatif Legislasi DPD Bidang Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas dan wewenang DPD antara lain <strong>dapat mengajukan kepada DPR RUU yang berkaitan dengan otonomi daerah</strong>, hubungan pusat dan daerah, pembentukan, pemekaran, dan penggabungan daerah, pengelolaan SDA, serta perimbangan keuangan pusat dan daerah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena DPD memiliki hak inisiatif legislasi khusus untuk materi muatan yang berkaitan langsung dengan kepentingan dan pembangunan daerah otonom di seluruh wilayah Indonesia.</p><p>Jawaban tepat: <strong>E. Mengajukan RUU bidang otonomi dan perimbangan keuangan ke DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pengesahan undang-undang merupakan wewenang DPR bersama Presiden.</li><li><strong>B</strong>: Kurang tepat karena penerbitan obligasi daerah wajib memenuhi kriteria ketat regulasi Kemenkeu.</li><li><strong>C</strong>: Kurang tepat karena penetapan bea masuk merupakan kewenangan pemerintah pusat bidang fiskal.</li><li><strong>D</strong>: Kurang tepat karena pembatalan perda tunduk pada mekanisme pengujian yudisial di Mahkamah Agung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dapat mengajukan kepada DPR RUU yang berkaitan dengan otonomi daerah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Terkait rancangan undang-undang bidang kedaerahan, keterlibatan DPD dalam proses pembentukan regulasi diatur melalui wewenang untuk...", "options": ["Membahas RUU bidang kedaerahan bersama DPR sebelum dengan pemerintah", "Memegang hak veto mutlak terhadap keputusan akhir sidang paripurna DPR", "Menetapkan sanksi pidana kurungan bagi kepala daerah yang tidak hadir rapat", "Mengubah susunan fraksi politik dewan perwakilan secara mandiri di parlemen", "Mewajibkan presiden mengundangkan rancangan undang-undang dalam tempo 24 jam"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterlibatan DPD Dalam Pembahasan RUU Tertentu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>DPD bertugas <strong>membahas RUU pada bidang kedaerahan tersebut bersama-sama DPR</strong> atas undangan DPR sesuai tata tertib dewan sebelum dibahas bersama pemerintah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena DPD dilibatkan dalam pembahasan tingkat awal (tripartit) bersama DPR untuk menyelaraskan substansi RUU terkait kedaerahan sebelum pemerintah dan DPR melakukan pembahasan persetujuan bersama.</p><p>Jawaban tepat: <strong>A. Membahas RUU bidang kedaerahan bersama DPR sebelum dengan pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena DPD tidak memiliki hak veto terhadap keputusan persetujuan RUU di DPR.</li><li><strong>C</strong>: Kurang tepat karena DPD tidak memiliki wewenang yuridis menjatuhkan sanksi kurungan pidana.</li><li><strong>D</strong>: Kurang tepat karena DPD tidak memiliki kaitan struktural dengan susunan fraksi partai di DPR.</li><li><strong>E</strong>: Kurang tepat karena pengundangan undang-undang memiliki tenggat waktu konstitusional 30 hari.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Membahas RUU pada huruf b tersebut bersama-sama DPR</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan fungsi pengawasan oleh Dewan Perwakilan Daerah menurut UUD 1945 diwujudkan melalui kewenangan untuk...", "options": ["Menyelenggarakan penyidikan tindak pidana penyalahgunaan keuangan desa", "Mengawasi pelaksanaan undang-undang mengenai otonomi daerah bagi DPR", "Menyita berkas laporan pertanggungjawaban dinas perpajakan tingkat kota", "Menonaktifkan bupati atau walikota yang terlambat menyampaikan laporan APBD", "Membekukan operasional badan usaha milik daerah yang mengalami kerugian kas"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Pengawasan DPD Terhadap Pelaksanaan UU</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>DPD bertugas <strong>melakukan pengawasan sebagai pertimbangan DPR atas pelaksanaan undang-undang mengenai otonomi daerah</strong>, perimbangan keuangan, SDA, pajak, pendidikan, dan agama.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena hasil pengawasan DPD terhadap implementasi otonomi daerah diserahkan kepada DPR sebagai bahan pertimbangan bagi DPR untuk menindaklanjuti kebijakan pengawasan nasional.</p><p>Jawaban tepat: <strong>B. Mengawasi pelaksanaan undang-undang mengenai otonomi daerah bagi DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penyidikan pidana keuangan merupakan wewenang aparat kepolisian dan kejaksaan.</li><li><strong>C</strong>: Kurang tepat karena penyitaan dokumen merupakan tindakan hukum pro justitia aparat penyidik.</li><li><strong>D</strong>: Kurang tepat karena pemberhentian kepala daerah diatur lewat mekanisme DPRD dan Kemendagri.</li><li><strong>E</strong>: Kurang tepat karena pengelolaan BUMD berada di bawah ranah pemerintah daerah dan RUPS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Melakukan pengawasan sebagai pertimbangan DPR atas pelaksanaan:</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Wewenang konstitusional DPD dalam proses pembentukan keanggotaan Badan Pemeriksa Keuangan (BPK) adalah...", "options": ["Menetapkan susunan auditor pelaksana audit forensik di seluruh provinsi", "Mengesahkan anggaran operasional badan pemeriksa secara mandiri dan final", "Memberikan pertimbangan kepada DPR dalam pemilihan anggota BPK", "Menjatuhkan putusan etik profesi akuntan terhadap pimpinan lembaga pemeriksa", "Memimpin sidang pleno penyerahan laporan hasil pemeriksaan anggaran tahunan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran DPD Dalam Pemilihan Anggota BPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas DPD salah satunya adalah <strong>memberikan pertimbangan kepada DPR dalam pemilihan anggota Badan Pemeriksa Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena BPK bertugas memeriksa keuangan pusat dan daerah. Pertimbangan tertulis dari DPD membantu DPR menyaring kandidat anggota BPK yang memiliki komitmen integritas terhadap pengawasan perbendaharaan negara di daerah.</p><p>Jawaban tepat: <strong>C. Memberikan pertimbangan kepada DPR dalam pemilihan anggota BPK</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penugasan tim audit internal BPK merupakan kewenangan operasional pimpinan BPK.</li><li><strong>B</strong>: Kurang tepat karena anggaran operasional BPK ditetapkan dalam APBN bersama DPR dan Presiden.</li><li><strong>D</strong>: Kurang tepat karena pengawasan etik auditor BPK dijalankan oleh Majelis Kehormatan Kode Etik BPK.</li><li><strong>E</strong>: Kurang tepat karena sidang penyerahan LHP dipimpin oleh pimpinan dewan perwakilan terkait.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Memberikan pertimbangan kepada DPR dalam pemilihan anggota Badan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kedudukan Mahkamah Agung berdasarkan Pasal 24 ayat (1) UUD 1945 diposisikan sebagai...", "options": ["Badan pendukung eksekutif dalam penertiban administrasi perpajakan daerah", "Lembaga penasihat hukum resmi bagi jajaran kementerian koordinator pemerintahan", "Komisi penyelidik independen perkara kejahatan lintas batas kedaulatan negara", "Pelaksana kekuasaan kehakiman yang merdeka demi hukum dan keadilan", "Pengawas kedisiplinan operasional bagi aparatur sipil negara di kementerian"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Kemerdekaan Kekuasaan Kehakiman Pada MA</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mahkamah Agung (MA) berkedudukan sebagai pemegang kekuasaan kehakiman dan <strong>penyelenggara peradilan yang merdeka untuk menegakkan hukum dan keadilan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena independensi badan peradilan (*judicial independence*) merupakan syarat mutlak negara hukum guna menjamin tegaknya keadilan tanpa adanya campur tangan eksekutif maupun kekuatan politik mana pun.</p><p>Jawaban tepat: <strong>D. Pelaksana kekuasaan kehakiman yang merdeka demi hukum dan keadilan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penertiban administrasi pajak merupakan kewenangan dirjen pajak kementerian keuangan.</li><li><strong>B</strong>: Kurang tepat karena penasihat hukum pemerintah disediakan oleh Jaksa Agung Muda Perdata dan TUN.</li><li><strong>C</strong>: Kurang tepat karena penyelidikan kejahatan transnasional merupakan ranah kepolisian dan interpol.</li><li><strong>E</strong>: Kurang tepat karena disiplin pegawai ASN berada di bawah kewenangan BKN dan pejabat pembina kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>merdeka untuk menegakkan hukum dan keadilan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Fungsi Mahkamah Agung sebagai puncak hierarki peradilan di Indonesia ditegaskan melalui status yuridis bahwa MA merupakan...", "options": ["Mitra koordinasi penuntutan umum di bawah instruksi menteri koordinator", "Lembaga banding pertama tindak pidana pelanggaran pilkada daerah", "Instansi pembina teknis bagi kepolisian resor dan kejaksaan negeri di daerah", "Pengadilan arbitrase dagang khusus untuk penyelesaian utang korporasi asing", "Pengadilan tertinggi bagi semua peradilan yang merdeka dan mandiri"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan MA Sebagai Pengadilan Tertinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kedudukan MA adalah sebagai lembaga negara yang berfungsi sebagai <strong>pengadilan tertinggi bagi semua peradilan terlepas dari pengaruh Pemerintah dan pengaruh-pengaruh lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena MA membawahi empat lingkungan peradilan (umum, agama, militer, dan tata usaha negara) serta memegang otoritas kasasi tertinggi guna menjaga kesatuan penerapan hukum nasional (*unity of jurisprudence*).</p><p>Jawaban tepat: <strong>E. Pengadilan tertinggi bagi semua peradilan yang merdeka dan mandiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena MA bersifat bebas dan mandiri serta terlepas dari arahan kementerian koordinator.</li><li><strong>B</strong>: Kurang tepat karena sengketa hasil pemilu diadili langsung oleh Mahkamah Konstitusi.</li><li><strong>C</strong>: Kurang tepat karena kepolisian dan kejaksaan berada di ranah cabang eksekutif penegak hukum.</li><li><strong>D</strong>: Kurang tepat karena arbitrase dagang merupakan mekanisme penyelesaian sengketa di luar peradilan umum (BANI).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>peradilan terlepas dari pengaruh Pemerintah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan UUD 1945, tata cara seleksi dan pengusulan calon Hakim Agung diatur melalui mekanisme bahwa...", "options": ["Calon Hakim Agung diusulkan Komisi Yudisial kepada DPR untuk disetujui", "Calon dipilih melalui musyawarah tertutup oleh seluruh hakim agung", "Presiden mengangkat langsung para hakim tinggi tanpa dewan", "Komisi ASN menyelenggarakan tes komputerisasi serentak daerah", "Menteri hukum mengajukan daftar nama calon kepada pimpinan lembaga kejaksaan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mekanisme Pengusulan Calon Hakim Agung</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Prosedur pengangkatan Hakim Agung menetapkan bahwa <strong>Calon Hakim Agung diusulkan oleh Komisi Yudisial kepada DPR untuk mendapatkan persetujuan dan ditetapkan sebagai Hakim Agung oleh Presiden</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena proses ini memadukan independensi seleksi profesional oleh KY, persetujuan keterwakilan rakyat oleh DPR, dan legitimasi penetapan administratif oleh Kepala Negara.</p><p>Jawaban tepat: <strong>A. Calon Hakim Agung diusulkan Komisi Yudisial kepada DPR untuk disetujui</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena seleksi calon hakim agung dilakukan oleh lembaga eksternal independen (Komisi Yudisial).</li><li><strong>C</strong>: Kurang tepat karena pengangkatan hakim agung wajib melalui proses usulan KY dan persetujuan DPR.</li><li><strong>D</strong>: Kurang tepat karena KASN/BKN mengurusi ASN birokrasi, bukan seleksi hakim agung peradilan.</li><li><strong>E</strong>: Kurang tepat karena menteri hukum dan kejaksaan tidak berwenang mengusulkan nama calon hakim agung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Calon Hakim Agung diusulkan oleh Komisi Yudisial kepada DPR</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Asas demokrasi internal dalam kepemimpinan puncak badan peradilan tercermin dalam aturan bahwa...", "options": ["Ketua MA ditunjuk secara bergantian oleh pimpinan fraksi partai politik parlemen", "Ketua dan Wakil Ketua MA dipilih dari dan oleh Hakim Agung", "Presiden menetapkan pimpinan pengadilan tertinggi berdasarkan urutan masa dinas", "Pimpinan MA disahkan melalui dengar pendapat komisi kepolisian", "Menteri koordinator menugaskan pejabat eselon utama menduduki kursi kepaniteraan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemilihan Pimpinan Mahkamah Agung</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 24A ayat (4) UUD 1945, <strong>Ketua dan Wakil Ketua Mahkamah Agung dipilih dari dan oleh Hakim Agung</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pemilihan dari dan oleh hakim agung sendiri menjamin independensi kepemimpinan kehakiman dari intervensi kekuasaan eksekutif maupun legislatif.</p><p>Jawaban tepat: <strong>B. Ketua dan Wakil Ketua MA dipilih dari dan oleh Hakim Agung</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pimpinan fraksi partai dilarang mengintervensi pemilihan pimpinan peradilan.</li><li><strong>C</strong>: Kurang tepat karena Presiden tidak berwenang menunjuk atau mendikte pimpinan Mahkamah Agung.</li><li><strong>D</strong>: Kurang tepat karena komisi kepolisian tidak memiliki fungsi dalam suksesi kepemimpinan badan peradilan.</li><li><strong>E</strong>: Kurang tepat karena kepaniteraan MA merupakan unit fungsional di bawah hierarki internal pengadilan tertinggi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Ketua dan Wakil Ketua Mahkamah Agung dipilih dari dan oleh Hakim Agung</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Materi muatan rancangan undang-undang yang menjadi kompetensi DPD untuk diajukan kepada DPR meliputi bidang...", "options": ["Hukum pidana militer, diplomasi internasional, dan standardisasi persenjataan", "Regulasi kepabeanan ekspor impor, perizinan frekuensi radio, dan antariksa", "Hubungan pusat dan daerah, pembentukan dan pemekaran, dan penggabungan daerah", "Perjanjian pertahanan bilateral kawasan, ekstradisi tahanan, dan suaka politik", "Sanksi tindak pidana terorisme, intelijen strategis, dan komando pasukan khusus"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Lingkup Materi RUU Usulan DPD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>RUU yang dapat diajukan DPD berkaitan dengan otonomi daerah, <strong>hubungan pusat dan daerah, pembentukan dan pemekaran, dan penggabungan daerah</strong>, pengelolaan SDA, serta perimbangan keuangan pusat dan daerah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pasal 22D UUD 1945 membatasi lingkup inisiatif DPD pada urusan yang bersinggungan langsung dengan tata kelola wilayah dan relasi daerah dengan pemerintah pusat.</p><p>Jawaban tepat: <strong>C. Hubungan pusat dan daerah, pembentukan dan pemekaran, dan penggabungan daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena urusan militer dan diplomasi merupakan kewenangan pemerintah pusat.</li><li><strong>B</strong>: Kurang tepat karena kepabeanan dan antariksa merupakan urusan perdagangan dan sains kementerian pusat.</li><li><strong>D</strong>: Kurang tepat karena perjanjian pertahanan bilateral merupakan ranah eksekutif dan persetujuan DPR.</li><li><strong>E</strong>: Kurang tepat karena intelijen dan terorisme merupakan urusan keamanan nasional pemerintah pusat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>hubungan pusat dan daerah, pembentukan dan pemekaran, dan penggabungan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Fungsi pemberian pertimbangan oleh Dewan Perwakilan Daerah kepada DPR difokuskan pada pembahasan...", "options": ["RUU kepegawaian dinas intelijen dan penegakan hukum peradilan", "Perjanjian bantuan hibah militer luar negeri dengan perwakilan negara sahabat", "Rancangan Perppu tentang status keadaan bahaya perang nasional", "RUU APBN dan RUU yang berkaitan dengan pajak, pendidikan, dan agama", "Nota kesepahaman antara kementerian negara dengan swasta asing"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pertimbangan Anggaran dan Kebijakan Sosial DPD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>DPD bertugas: <strong>memberikan pertimbangan kepada DPR atas RUU APBN dan RUU yang berkaitan dengan pajak, pendidikan, dan agama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kebijakan APBN, perpajakan, pendidikan, dan keagamaan memiliki dampak langsung pada kesejahteraan sosial dan alokasi transfer dana bagi masyarakat daerah.</p><p>Jawaban tepat: <strong>D. RUU APBN dan RUU yang berkaitan dengan pajak, pendidikan, dan agama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena RUU dinas intelijen dibahas oleh komisi pertahanan DPR bersama pemerintah.</li><li><strong>B</strong>: Kurang tepat karena bantuan hibah militer merupakan urusan kerja sama pertahanan pemerintah pusat.</li><li><strong>C</strong>: Kurang tepat karena persetujuan Perpu kegentingan perang merupakan ranah DPR.</li><li><strong>E</strong>: Kurang tepat karena MoU korporasi bukan objek pembahasan rancangan regulasi dewan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 50) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Memberikan pertimbangan kepada DPR atas RUU APBN dan RUU yang berkaitan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Salah satu tugas dan wewenang yudisial Mahkamah Agung dalam penegakan kepastian hukum adalah...", "options": ["Memutus permohonan PK pada tingkat pertama dan terakhir", "Mengadili perkara perselisihan hasil penghitungan suara pemilihan kepala daerah", "Meninjau keabsahan konstitusional undang-undang terhadap UUD", "Memutuskan pembubaran organisasi partai politik peserta pemilu legislatif", "Memberhentikan secara langsung jaksa penuntut umum yang mangkir dalam sidang"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wewenang Peninjauan Kembali (PK) Oleh MA</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas MA nomor 1 adalah <strong>memeriksa dan memutus permohonan peninjauan kembali pada tingkat pertama dan terakhir atas putusan pengadilan yang telah memperoleh kekuatan hukum tetap</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Peninjauan Kembali (PK) merupakan upaya hukum luar biasa yang diadili langsung oleh Mahkamah Agung apabila ditemukan bukti baru (*novum*) atau kekhilafan hakim dalam putusan inkrah terdahulu.</p><p>Jawaban tepat: <strong>A. Memutus permohonan PK pada tingkat pertama dan terakhir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena sengketa hasil pemilihan kepala daerah diadili oleh Mahkamah Konstitusi.</li><li><strong>C</strong>: Kurang tepat karena pengujian undang-undang terhadap UUD merupakan kewenangan MK.</li><li><strong>D</strong>: Kurang tepat karena pembubaran partai politik merupakan wewenang Mahkamah Konstitusi.</li><li><strong>E</strong>: Kurang tepat karena disiplin jaksa penuntut umum diatur internal oleh kejaksaan agung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Memeriksa dan memutus permohonan peninjauan kembali pada tingkat pertama</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kewenangan Mahkamah Agung dalam memeriksa penerapan hukum pada putusan peradilan di bawahnya diwujudkan melalui tugas untuk...", "options": ["Menerbitkan surat paksa bayar pajak bagi wajib pajak perorangan yang mangkir", "Mengaudit laporan keuangan badan amil zakat tingkat kabupaten secara periodik", "Memutus kasasi putusan pengadilan tingkat banding semua peradilan", "Mengesahkan kepengurusan organisasi advokat nasional administratif", "Menyita barang bukti tindak pidana umum sebelum dimulainya proses persidangan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wewenang Kasasi Mahkamah Agung</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas MA nomor 2 adalah <strong>memutus permohonan kasasi terhadap putusan pengadilan tingkat banding atau tingkat terakhir dari semua lingkungan peradilan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena peradilan kasasi di Mahkamah Agung memeriksa apakah pengadilan bawahan telah menerapkan hukum dengan benar (*judex juris*), bukan memeriksa fakta peristiwa (*judex facti*).</p><p>Jawaban tepat: <strong>C. Memutus kasasi putusan pengadilan tingkat banding semua peradilan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penerbitan surat paksa pajak merupakan kewenangan jurusita pajak kementerian keuangan.</li><li><strong>B</strong>: Kurang tepat karena audit badan amil zakat dijalankan oleh akuntan publik dan Baznas.</li><li><strong>D</strong>: Kurang tepat karena pengesahan badan hukum organisasi profesi dilakukan oleh kementerian hukum.</li><li><strong>E</strong>: Kurang tepat karena penyitaan barang bukti pra-sidang merupakan kewenangan penyidik kepolisian/kejaksaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Memutus permohonan kasasi terhadap putusan pengadilan tingkat banding</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 24A ayat (1) UUD 1945, wewenang Mahkamah Agung dalam pengujian peraturan perundang-undangan adalah...", "options": ["Menguji kesesuaian undang-undang terhadap naskah pembukaan undang-undang dasar", "Menilai keabsahan instruksi menteri terhadap aturan tata tertib internal dewan", "Membatalkan undang-undang yang dinilai bertentangan dengan ketentuan konvensi", "Menguji ketetapan majelis permusyawaratan terhadap asas umum pemerintahan", "Menguji materiil peraturan di bawah undang-undang terhadap UU"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengujian Peraturan Di Bawah Undang-Undang Oleh MA</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas MA nomor 3 adalah <strong>menguji secara materil terhadap peraturan perundangan di bawah undang-undang terhadap undang-undang</strong> (*judicial review* regulasi di bawah UU).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kewenangan pengujian perundang-undangan di Indonesia terbagi dua: Mahkamah Konstitusi menguji UU terhadap UUD, sedangkan Mahkamah Agung menguji peraturan di bawah UU (seperti PP, Perpres, Perda) terhadap UU.</p><p>Jawaban tepat: <strong>E. Menguji materiil peraturan di bawah undang-undang terhadap UU</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pengujian undang-undang terhadap UUD merupakan kompetensi Mahkamah Konstitusi.</li><li><strong>B</strong>: Kurang tepat karena tata tertib dewan merupakan aturan internal dewan legislatif.</li><li><strong>C</strong>: Kurang tepat karena MA tidak berwenang membatalkan undang-undang.</li><li><strong>D</strong>: Kurang tepat karena ketetapan MPR bukan objek pengujian materiil di Mahkamah Agung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menguji secara materil terhadap peraturan perundangan di bawah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Peran konstitusional Mahkamah Agung dalam pelaksanaan hak prerogatif kepala negara diwujudkan melalui kewajiban untuk...", "options": ["Mengesahkan penunjukan menteri pertahanan dalam situasi kedaruratan nasional", "Memberikan pertimbangan kepada Presiden dalam memberikan grasi dan rehabilitasi", "Menyetujui ratifikasi perjanjian batas wilayah negara dengan negara tetangga", "Menentukan nama-nama duta besar yang akan ditempatkan di negara mitra asing", "Menyusun teks pidato kenegaraan tahunan pada sidang paripurna dewan perwakilan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pertimbangan MA Dalam Pemberian Grasi dan Rehabilitasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas MA nomor 4 adalah <strong>memberikan pertimbangan kepada Presiden dalam memberikan grasi dan rehabilitasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Presiden wajib memperhatikan pertimbangan yuridis dari Mahkamah Agung sebelum memutuskan mengabulkan atau menolak permohonan grasi maupun rehabilitasi yang diajukan narapidana.</p><p>Jawaban tepat: <strong>B. Memberikan pertimbangan kepada Presiden dalam memberikan grasi dan rehabilitasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penunjukan menteri merupakan hak prerogatif presiden tanpa keterlibatan MA.</li><li><strong>C</strong>: Kurang tepat karena persetujuan perjanjian internasional berada pada kewenangan DPR.</li><li><strong>D</strong>: Kurang tepat karena pertimbangan penempatan duta besar diberikan oleh DPR, bukan MA.</li><li><strong>E</strong>: Kurang tepat karena penyusunan pidato kenegaraan merupakan tugas kesekretariatan kepresidenan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Memberikan pertimbangan kepada Presiden dalam memberikan grasi dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kedudukan Komisi Yudisial dalam sistem ketatanegaraan Indonesia pasca-amandemen UUD 1945 dicirikan oleh sifat bahwa lembaga ini...", "options": ["Berada di bawah koordinasi administratif kementerian hukum dan hak asasi", "Berwenang memutus perkara kasasi tindak pidana khusus pada tingkat pertama", "Sub-bagian teknis kepaniteraan peradilan lingkungan mahkamah agung", "Mandiri, serta diangkat dan diberhentikan Presiden atas persetujuan DPR", "Memiliki hierarki struktural kepengurusan berjenjang hingga tingkat kecamatan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan dan Sifat Komisi Yudisial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kedudukan Komisi Yudisial (KY) ditegaskan dalam UUD 1945: <strong>bersifat mandiri, diangkat dan diberhentikan oleh Presiden dengan persetujuan DPR</strong>, serta susunan kedudukannya diatur dengan undang-undang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena sifat mandiri (*independent*) menjamin KY dapat melakukan rekrutmen hakim agung dan pengawasan perilaku etik hakim tanpa tekanan dari pihak internal badan peradilan maupun eksekutif.</p><p>Jawaban tepat: <strong>D. Mandiri, serta diangkat dan diberhentikan Presiden atas persetujuan DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena KY bukan bagian dari struktur kementerian eksekutif.</li><li><strong>B</strong>: Kurang tepat karena KY tidak berwenang mengadili perkara pidana peradilan umum.</li><li><strong>C</strong>: Kurang tepat karena KY merupakan lembaga negara mandiri di luar Mahkamah Agung.</li><li><strong>E</strong>: Kurang tepat karena KY berkedudukan di ibu kota negara dan tidak memiliki kantor kecamatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Bersifat mandiri; • Diangkat dan diberhentikan oleh Presiden dengan persetujuan DPR</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Tugas dan wewenang utama Komisi Yudisial yang secara tegas dimandatkan oleh Pasal 24B ayat (1) UUD 1945 adalah...", "options": ["Mengusulkan pengangkatan Hakim Agung kepada DPR demi persetujuan bersama", "Menyelenggarakan penuntutan pidana terhadap mantan pejabat tinggi birokrasi", "Menguji kesesuaian materi muatan peraturan daerah terhadap undang-undang", "Menjatuhkan vonis eksekusi hukuman badan bagi pelanggar disiplin kerja", "Menyusun naskah rancangan anggaran belanja pengadilan negeri di daerah"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wewenang Pokok Komisi Yudisial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas dan wewenang KY nomor 1 adalah <strong>mengusulkan pengangkatan Hakim Agung</strong> kepada DPR untuk mendapatkan persetujuan dan ditetapkan oleh Presiden.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pembentukan KY pasca-reformasi ditujukan untuk memutus mata rantai nepotisme dan korporatisme internal dalam suksesi kepemimpinan hakim agung di Mahkamah Agung.</p><p>Jawaban tepat: <strong>A. Mengusulkan pengangkatan Hakim Agung kepada DPR demi persetujuan bersama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena penuntutan pidana merupakan tugas institusi kejaksaan.</li><li><strong>C</strong>: Kurang tepat karena pengujian materiil perda terhadap UU merupakan wewenang Mahkamah Agung.</li><li><strong>D</strong>: Kurang tepat karena KY tidak berwenang menjatuhkan sanksi vonis hukuman pidana.</li><li><strong>E</strong>: Kurang tepat karena penganggaran pengadilan dikelola satu atap oleh Mahkamah Agung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Mengusulkan pengangkatan Hakim Agung</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Selain mengusulkan calon Hakim Agung, kewenangan lain yang dimiliki Komisi Yudisial menurut konstitusi adalah dalam rangka...", "options": ["Mengatur kenaikan pangkat administratif bagi seluruh panitera pengganti", "Membatalkan vonis peradilan tingkat kasasi yang dinilai menciderai publik", "Menjaga dan menegakkan kehormatan, keluhuran martabat, serta perilaku hakim", "Mengadili permohonan dispensasi usia pernikahan di pengadilan tingkat pertama", "Menetapkan besaran tunjangan kinerja pejabat struktural badan kepegawaian"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wewenang Etik Komisi Yudisial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wewenang lain Komisi Yudisial menurut UUD 1945 adalah <strong>memiliki wewenang lain dalam rangka menjaga dan menegakkan kehormatan, keluhuran martabat, serta perilaku hakim</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena KY bertindak sebagai lembaga pengawas eksternal kode etik dan pedoman perilaku hakim (*code of judicial conduct*) demi menjaga integritas moral seluruh aparat kehakiman di Indonesia.</p><p>Jawaban tepat: <strong>C. Menjaga dan menegakkan kehormatan, keluhuran martabat, serta perilaku hakim</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena urusan kepegawaian panitera dikelola internal oleh sekretariat MA.</li><li><strong>B</strong>: Kurang tepat karena KY tidak berwenang mencampuri pertimbangan hukum putusan peradilan (*substantive justice*).</li><li><strong>D</strong>: Kurang tepat karena dispensasi perkawinan diadili oleh pengadilan agama atau pengadilan negeri.</li><li><strong>E</strong>: Kurang tepat karena tunjangan kinerja birokrasi diatur melalui regulasi kementerian terkait.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>keluhuran martabat, serta perilaku hakim</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Susunan keanggotaan Mahkamah Konstitusi berdasarkan ketentuan Pasal 24C ayat (3) UUD 1945 terdiri atas...", "options": ["Tujuh orang hakim yang ditunjuk langsung oleh dewan perwakilan rakyat daerah", "Lima belas orang delegasi fakultas hukum negeri seluruh wilayah kepulauan", "Sebelas orang pakar hukum tata negara yang diangkat seumur hidup oleh majelis", "Lima orang pimpinan lembaga tinggi negara perwakilan kementerian koordinator", "Sembilan hakim konstitusi yang diusulkan Presiden, DPR, dan MA"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komposisi Keanggotaan Mahkamah Konstitusi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kedudukan MK menetapkan bahwa MK <strong>mempunyai sembilan orang anggota Hakim Konstitusi yang diusulkan oleh masing-masing Presiden tiga orang, DPR tiga orang, dan Mahkamah Agung tiga orang</strong>, kemudian ditetapkan oleh Presiden.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena komposisi tripartit (3 Presiden, 3 DPR, 3 MA) menjamin kemandirian dan perimbangan representasi dari tiga cabang kekuasaan negara tanpa ada cabang yang mendominasi.</p><p>Jawaban tepat: <strong>E. Sembilan hakim konstitusi yang diusulkan Presiden, DPR, dan MA</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena jumlah hakim MK adalah sembilan orang, bukan tujuh orang usulan DPRD.</li><li><strong>B</strong>: Kurang tepat karena anggota MK berjumlah sembilan orang dan bukan delegasi otomatis fakultas hukum.</li><li><strong>C</strong>: Kurang tepat karena hakim konstitusi menjabat dengan batas masa jabatan menurut undang-undang, bukan seumur hidup.</li><li><strong>D</strong>: Kurang tepat karena hakim konstitusi adalah figur negarawan pengawal konstitusi, bukan menteri koordinator.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Mempunyai sembilan orang anggota Hakim Konstitusi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Tugas dan wewenang pertama Mahkamah Konstitusi menurut Pasal 24C ayat (1) UUD 1945 adalah...", "options": ["Menyelenggarakan peninjauan kembali perkara tindak pidana korporasi swasta", "Menguji undang-undang terhadap Undang-Undang Dasar Negara 1945", "Menguji keabsahan peraturan pemerintah terhadap norma undang-undang pembentukan", "Menjatuhkan putusan kasasi perkara perselisihan ketenagakerjaan kementerian", "Mengadili pelanggaran disiplin aparatur sipil negara di lingkungan birokrasi"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wewenang Pengujian Undang-Undang Oleh MK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas dan wewenang MK nomor 1 adalah <strong>menguji undang-undang terhadap Undang-Undang Dasar Negara Republik Indonesia 1945</strong> (*judicial review* konstitusionalitas undang-undang).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Mahkamah Konstitusi berfungsi sebagai penjaga konstitusi (*the guardian of the constitution*). Jika materi undang-undang bertentangan dengan UUD 1945, MK berwenang membatalkannya dengan putusan yang bersifat final dan mengikat.</p><p>Jawaban tepat: <strong>B. Menguji undang-undang terhadap Undang-Undang Dasar Negara 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena permohonan peninjauan kembali diadili oleh Mahkamah Agung.</li><li><strong>C</strong>: Kurang tepat karena pengujian peraturan di bawah UU terhadap UU merupakan wewenang Mahkamah Agung.</li><li><strong>D</strong>: Kurang tepat karena kasasi ketenagakerjaan diadili oleh Mahkamah Agung (Pengadilan Hubungan Industrial).</li><li><strong>E</strong>: Kurang tepat karena pelanggaran disiplin ASN diproses oleh instansi pembina kepegawaian dan BKN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menguji undang-undang terhadap Undang-Undang Dasar Negara Republik</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Apabila terjadi perselisihan kewenangan antara dua lembaga negara yang mandatnya bersumber langsung dari konstitusi, forum peradilan yang berwenang memutus perkara tersebut adalah...", "options": ["Rapat koordinasi menteri koordinator bidang politik hukum dan keamanan", "Majelis peradilan tata usaha negara tingkat pertama di ibu kota provinsi", "Sidang khusus dewan kehormatan penyelenggara pemilihan umum pusat", "Memutus sengketa kewenangan lembaga negara yang diberikan UUD", "Forum musyawarah pimpinan komisi pemilihan umum bersama badan adhoc"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wewenang Memutus SKLN Oleh Mahkamah Konstitusi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas dan wewenang MK nomor 2 adalah <strong>memutus sengketa kewenangan lembaga negara yang kewenangannya diberikan oleh Undang-Undang Dasar Negara Republik Indonesia 1945</strong> (SKLN).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena MK berfungsi sebagai penafsir akhir konstitusi (*the final interpreter of the constitution*). Apabila terjadi benturan wewenang antarlembaga konstitusional, putusan peradilan MK menjadi penyelesaian hukum definitif demi kepastian tata negara.</p><p>Jawaban tepat: <strong>D. Memutus sengketa kewenangan lembaga negara yang diberikan UUD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Menko Polhukam tidak memiliki kewenangan yudisial memutus sengketa antarlembaga negara.</li><li><strong>B</strong>: Kurang tepat karena PTUN mengadili keputusan pejabat tata usaha negara, bukan sengketa konstitusional antarlembaga tinggi.</li><li><strong>C</strong>: Kurang tepat karena DKPP hanya mengadili pelanggaran kode etik penyelenggara pemilu.</li><li><strong>E</strong>: Kurang tepat karena forum KPU bukan badan peradilan pemutus perselisihan antarlembaga negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 51) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Memutus sengketa kewenangan lembaga negara yang kewenangannya diberikan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kewenangan yudisial Mahkamah Konstitusi dalam mengawal kemurnian suara kedaulatan rakyat ditegaskan melalui wewenang untuk...", "options": ["Menetapkan perolehan suara resmi secara mendahului sebelum rekapitulasi tuntas", "Memutus perselisihan hasil pemilihan umum dengan putusan final dan mengikat", "Menjatuhkan vonis kurungan pidana penjara bagi pelaku kecurangan pemilu", "Membekukan kepengurusan komisi pemilihan umum di seluruh wilayah kepulauan", "Membatalkan seluruh tahapan pemilu secara nasional saat terjadi ketegangan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wewenang MK Memutus Perselisihan Pemilu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu wewenang Mahkamah Konstitusi menurut Pasal 24C ayat (1) UUD 1945 adalah <strong>memutus perselisihan hasil pemilihan umum</strong> (PHPU).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena MK merupakan peradilan tingkat pertama dan terakhir yang putusannya bersifat final dan mengikat (*final and binding*) dalam mengadili perselisihan penetapan perolehan suara pemilu legislatif, pemilu presiden, maupun pilkada.</p><p>Jawaban tepat: <strong>B. Memutus perselisihan hasil pemilihan umum dengan putusan final dan mengikat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penetapan perolehan suara resmi dilakukan oleh Komisi Pemilihan Umum (KPU).</li><li><strong>C</strong>: Kurang tepat karena tindak pidana pemilu diproses oleh Sentra Gakkumdu dan diadili pengadilan negeri.</li><li><strong>D</strong>: Kurang tepat karena penegakan etik komisioner KPU diputus oleh Dewan Kehormatan Penyelenggara Pemilu (DKPP).</li><li><strong>E</strong>: Kurang tepat karena MK mengadili sengketa perselisihan angka hasil pemilu, bukan membatalkan pemilu secara serampangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 52) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Memutus perselisihan hasil pemilihan umum</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan UUD 1945, Mahkamah Konstitusi wajib memeriksa, mengadili, dan memutus pendapat DPR mengenai dugaan pelanggaran hukum oleh Presiden dalam batas waktu...", "options": ["Tiga puluh hari kalender sejak berkas usulan diterima panitera", "Seratus dua puluh hari kalender setelah verifikasi dokumen dinyatakan lengkap", "Empat puluh lima hari kerja terhitung sejak sidang pleno permulaan", "Paling lama sembilan puluh hari setelah permintaan DPR tersebut diterima oleh MK", "Enam puluh hari kerja sejak penunjukan susunan majelis hakim peradilan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tenggat Waktu Pemeriksaan Pemakzulan di MK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mahkamah Konstitusi wajib memeriksa, mengadili, dan memutus dengan seadil-adilnya terhadap pendapat DPR mengenai dugaan pelanggaran oleh Presiden/Wapres <strong>paling lama sembilan puluh hari</strong> setelah permintaan DPR diterima.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena konstitusi membatasi durasi peradilan pemakzulan maksimal sembilan puluh hari guna menjamin kepastian hukum ketatanegaraan serta mencegah kevakuman atau ketidakpastian kepemimpinan nasional.</p><p>Jawaban tepat: <strong>D. Paling lama sembilan puluh hari setelah permintaan DPR tersebut diterima oleh MK</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena tiga puluh hari merupakan batas waktu penyelenggaraan sidang paripurna MPR pasca-putusan MK.</li><li><strong>B</strong>: Kurang tepat karena jangka waktu 120 hari tidak diatur dalam mekanisme konstitusi pasal 7B.</li><li><strong>C</strong>: Kurang tepat karena batas waktu konstitusional adalah sembilan puluh hari, bukan 45 hari kerja.</li><li><strong>E</strong>: Kurang tepat karena durasi 60 hari berlaku untuk pengisian kekosongan posisi wakil presiden di MPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 52) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>paling lama sembilan puluh hari</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Kedudukan konstitusional Badan Pemeriksa Keuangan (BPK) menurut Pasal 23E ayat (1) UUD 1945 dicirikan oleh status bahwa lembaga ini...", "options": ["Bebas dan mandiri dalam memeriksa pengelolaan dan tanggung jawab keuangan negara", "Merupakan sub-bagian teknis pembukuan kas di bawah kementerian keuangan", "Berada di bawah kendali pimpinan komisi perbankan dan anggaran parlemen", "Bertanggung jawab langsung secara struktural kepada kantor kepresidenan", "Merupakan lembaga penuntut umum dalam perkara tindak pidana perpajakan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Bebas dan Mandiri Badan Pemeriksa Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kedudukan BPK ditegaskan dalam UUD 1945 sebagai lembaga negara yang <strong>bebas dan mandiri dalam memeriksa pengelolaan dan tanggung jawab keuangan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena status bebas (*free*) dan mandiri (*independent*) menjamin bahwa auditor BPK dapat melakukan pemeriksaan keuangan negara tanpa intimidasi, intervensi, atau pengaruh dari pihak yang diperiksa (eksekutif maupun legislatif).</p><p>Jawaban tepat: <strong>A. Bebas dan mandiri dalam memeriksa pengelolaan dan tanggung jawab keuangan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena BPK berkedudukan di luar kementerian keuangan dan memeriksa kementerian keuangan itu sendiri.</li><li><strong>C</strong>: Kurang tepat karena BPK bersifat mandiri dan tidak berada di bawah kendali komisi DPR.</li><li><strong>D</strong>: Kurang tepat karena BPK sejajar dengan Presiden sebagai lembaga tinggi negara, bukan bawahan kepresidenan.</li><li><strong>E</strong>: Kurang tepat karena fungsi penuntutan pidana merupakan wewenang kejaksaan, bukan BPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 52) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pengelolaan dan tanggung jawab keuangan negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dalam tata kelola pengawasan keuangan negara, Badan Pemeriksa Keuangan menjalankan fungsi pokok sebagai...", "options": ["Pelaksana fungsi eksekutif, pembentukan regulasi, dan peradilan perdata", "Penyelenggara fungsi moneter, pencetakan uang tunai, dan penjaminan simpanan", "Pelaksana fungsi auditif, operatif, dan rekomendatif atas keuangan negara", "Pengelola investasi dana pensiun aparatur dan penerbitan surat berharga", "Penyidik intelijen keuangan dan penindak sengketa persaingan usaha"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Fungsi Pokok Badan Pemeriksa Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kedudukan BPK salah satunya berfungsi <strong>sebagai pelaksana fungsi auditif, operatif, dan rekomendatif</strong> terhadap keuangan negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena fungsi auditif adalah memeriksa keuangan, fungsi operatif adalah melakukan pemeriksaan lapangan dan investigasi, serta fungsi rekomendatif adalah memberikan rekomendasi perbaikan sistem dan tata kelola perbendaharaan kepada pemerintah dan DPR.</p><p>Jawaban tepat: <strong>C. Pelaksana fungsi auditif, operatif, dan rekomendatif atas keuangan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena BPK bukan pelaksana fungsi eksekutif atau peradilan perdata.</li><li><strong>B</strong>: Kurang tepat karena fungsi moneter dan pencetakan uang dijalankan oleh Bank Indonesia.</li><li><strong>D</strong>: Kurang tepat karena pengelolaan investasi dan surat berharga merupakan ranah kementerian keuangan.</li><li><strong>E</strong>: Kurang tepat karena intelijen keuangan dijalankan oleh PPATK dan persaingan usaha oleh KPPU.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 52) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pelaksana fungsi auditif, operatif, dan rekomendatif</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Jangkauan kelembagaan Badan Pemeriksa Keuangan dalam mengawal perbendaharaan di seluruh wilayah Indonesia diwujudkan melalui ketentuan bahwa BPK...", "options": ["Memusatkan seluruh auditornya di kantor pusat tanpa membuka kantor wilayah", "Mengalihkan operasional pengawasan kepada kantor perwakilan bank sentral", "Membentuk kantor cabang tetap di setiap kantor distrik setingkat kecamatan", "Menitipkan tugas pemeriksaan keuangan kepada dinas pendapatan daerah", "Berkedudukan di ibukota negara dan memiliki perwakilan di setiap provinsi"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Struktur Domisili Badan Pemeriksa Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 23G ayat (1) UUD 1945, BPK <strong>berkedudukan di ibukota negara dan memiliki perwakilan di setiap provinsi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena keberadaan kantor perwakilan BPK di setiap provinsi memastikan bahwa pengelolaan APBD pemerintah daerah dan instansi vertikal di daerah dapat diperiksa secara efektif, cepat, dan berkesinambungan.</p><p>Jawaban tepat: <strong>E. Berkedudukan di ibukota negara dan memiliki perwakilan di setiap provinsi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena BPK tidak hanya di kantor pusat melainkan memiliki perwakilan di tiap provinsi.</li><li><strong>B</strong>: Kurang tepat karena Bank Indonesia menjalankan otoritas moneter dan bukan pemeriksa anggaran negara.</li><li><strong>C</strong>: Kurang tepat karena perwakilan BPK berada di tingkat provinsi, bukan di tingkat kecamatan.</li><li><strong>D</strong>: Kurang tepat karena BPK merupakan lembaga eksternal yang mandiri dan tidak menitipkan tugas ke pemda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 52) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Berkedudukan di ibukota negara dan memiliki perwakilan di setiap provinsi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Setelah menuntaskan audit keuangan negara, kewajiban konstitusional yang wajib dilaksanakan oleh BPK adalah...", "options": ["Menjual dokumen temuan audit kepada lembaga riset swasta internasional", "Menyerahkan hasil audit keuangan negara kepada DPR, DPD, dan DPRD", "Menyita secara mandiri gedung kantor pemerintah yang memiliki temuan belanja", "Menjatuhkan putusan pemecatan seketika kepada pimpinan instansi terperiksa", "Mengubah susunan anggaran kementerian secara otoritatif di luar dewan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyerahan Laporan Hasil Pemeriksaan (LHP) BPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>BPK bertugas <strong>menyerahkan hasil pemeriksaan atas pengelolaan dan tanggung jawab keuangan negara kepada DPR, DPD, dan DPRD sesuai dengan kewenangannya</strong> untuk ditindaklanjuti.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena DPR menerima LHP keuangan pusat, DPD menerima LHP terkait kedaerahan, dan DPRD menerima LHP keuangan daerah guna menjalankan fungsi pengawasan dan tindak lanjut rekomendasi anggaran.</p><p>Jawaban tepat: <strong>B. Menyerahkan hasil audit keuangan negara kepada DPR, DPD, dan DPRD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena hasil audit BPK disampaikan resmi kepada lembaga perwakilan rakyat sesuai konstitusi.</li><li><strong>C</strong>: Kurang tepat karena BPK tidak berwenang menyita aset melainkan merekomendasikan pengembalian kas negara.</li><li><strong>D</strong>: Kurang tepat karena penjatuhan sanksi pemecatan pegawai merupakan kewenangan pejabat pembina kepegawaian.</li><li><strong>E</strong>: Kurang tepat karena BPK tidak berwenang merevisi anggaran APBN/APBD melainkan memeriksa pertanggungjawabannya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 52) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>menyerahkan hasil pemeriksaan atas pengelolaan dan tanggung jawab</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 22E ayat (5) UUD 1945, lembaga penyelenggara pemilihan umum di Indonesia dicirikan oleh sifat yuridis bahwa KPU...", "options": ["Bersifat kedaerahan, temporer, dan tunduk pada arahan partai penguasa", "Berada di bawah koordinasi operasional kepolisian daerah di setiap wilayah", "Merupakan badan ad-hoc eksekutif yang dibentuk dan dibubarkan setiap masa pemilu", "Merupakan suatu Komisi Pemilihan Umum yang bersifat nasional, tetap, dan mandiri", "Bertanggung jawab administratif kepada pimpinan koalisi partai politik dewan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter Lembaga Komisi Pemilihan Umum</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 22E ayat (5) UUD 1945, pemilihan umum diselenggarakan oleh suatu <strong>Komisi Pemilihan Umum (KPU) yang bersifat nasional, tetap, dan mandiri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena sifat nasional mencakup seluruh wilayah NKRI, sifat tetap berarti kelembagaan bekerja secara berkesinambungan (bukan ad-hoc), dan sifat mandiri berarti bebas dari pengaruh eksekutif maupun kepentingan partisan peserta pemilu.</p><p>Jawaban tepat: <strong>D. Merupakan suatu Komisi Pemilihan Umum yang bersifat nasional, tetap, dan mandiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena KPU berskala nasional dan independen dari partai penguasa.</li><li><strong>B</strong>: Kurang tepat karena KPU tidak berada di bawah komando kepolisian melainkan lembaga penyelenggara mandiri.</li><li><strong>C</strong>: Kurang tepat karena sifat KPU adalah kelembagaan tetap (*permanent*), bukan badan ad-hoc yang dibubarkan.</li><li><strong>E</strong>: Kurang tepat karena KPU tidak bertanggung jawab kepada koalisi fraksi partai politik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 52) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>yang bersifat nasional, tetap, dan mandiri</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Prinsip pelaksanaan pemilihan umum di Indonesia menurut Pasal 22E ayat (1) UUD 1945 diselenggarakan secara...", "options": ["Langsung, umum, bebas, rahasia, jujur, dan adil setiap lima tahun sekali", "Terbuka perwakilan, bertahap, fleksibel, dan terjadwal setiap tiga tahun sekali", "Kolektif konsensus, tertutup perwakilan, dan saat kas APBN surplus", "Berjenjang melalui delegasi utusan adat tanpa jadwal periodisasi yang baku", "Mandatoris partai, terpusat di ibu kota, dan digelar sewaktu-waktu atas dekrit"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas dan Periodesasi Pemilihan Umum</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemilihan umum dilakukan secara <strong>langsung, umum, bebas, rahasia, jujur, dan adil setiap lima tahun sekali</strong> untuk memilih anggota DPR, DPD, Presiden dan Wapres, serta DPRD.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena asas LUBER dan JURDIL merupakan fondasi demokrasi Pancasila yang menjamin kebebasan hak pilih setiap warga negara dan perlakuan yang setara bagi seluruh kontestan pemilu.</p><p>Jawaban tepat: <strong>A. Langsung, umum, bebas, rahasia, jujur, dan adil setiap lima tahun sekali</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena pemilu digelar secara langsung dan reguler tiap lima tahun, bukan tiga tahun.</li><li><strong>C</strong>: Kurang tepat karena pemilu tidak boleh ditunda dengan alasan defisit APBN.</li><li><strong>D</strong>: Kurang tepat karena pemilu langsung dan berjadwal pasti setiap lima tahun sekali.</li><li><strong>E</strong>: Kurang tepat karena pemilu merupakan hak rakyat dan tidak dapat digelar sewaktu-waktu tanpa undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 52) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dan adil setiap lima tahun sekali</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Ketentuan Pasal 22E ayat (3) UUD 1945 menetapkan kontestan pemilihan umum legislatif DPR dan DPRD adalah...", "options": ["Perseorangan warga negara yang mendapatkan izin khusus dari kementerian", "Gabungan organisasi massa buruh dan serikat pekerja tingkat daerah", "Peserta pemilu untuk memilih anggota DPR dan anggota DPRD adalah partai politik", "Kamar dagang dan industri bersama asosiasi korporasi berbadan hukum", "Yayasan sosial kemasyarakatan dan dewan kelurahan se-Indonesia"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peserta Pemilu Legislatif Dewan Perwakilan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 22E ayat (3) UUD 1945 menyatakan bahwa <strong>peserta pemilu untuk memilih anggota DPR dan anggota DPRD adalah partai politik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena partai politik diposisikan sebagai pilar demokrasi perwakilan yang mengagregasikan aspirasi masyarakat secara terorganisasi dan mencalonkan wakil rakyat di parlemen tingkat pusat dan daerah.</p><p>Jawaban tepat: <strong>C. Peserta pemilu untuk memilih anggota DPR dan anggota DPRD adalah partai politik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena calon perseorangan adalah peserta pemilu untuk memilih anggota DPD, bukan DPR/DPRD.</li><li><strong>B</strong>: Kurang tepat karena ormas dan serikat buruh bukan badan peserta resmi pemilu legislatif.</li><li><strong>D</strong>: Kurang tepat karena asosiasi bisnis dan industri bukan badan hukum peserta pemilihan umum.</li><li><strong>E</strong>: Kurang tepat karena yayasan kemasyarakatan bukan kontestan pemilu dewan perwakilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 52) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peserta pemilu untuk memilih anggota DPR dan anggota DPRD adalah parpol</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Perbedaan mendasar antara peserta pemilu DPR dengan pemilu DPD menurut Pasal 22E ayat (4) UUD 1945 terletak pada aturan bahwa...", "options": ["Peserta pemilu DPD wajib mendapatkan rekomendasi tertulis dari pimpinan partai", "Peserta pemilu DPD harus berstatus sebagai perwira purnawirawan dinas", "Peserta pemilu DPD diajukan oleh koalisi fraksi dewan perwakilan rakyat", "Peserta pemilu DPD diwajibkan menjabat sebagai pimpinan dinas di daerah", "Peserta pemilu untuk memilih anggota DPD adalah perseorangan warga negara"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jalur Perseorangan Dalam Pemilu Anggota DPD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 22E ayat (4) UUD 1945 menyatakan bahwa <strong>peserta pemilu untuk memilih anggota DPD adalah perseorangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena DPD dirancang sebagai representasi teritorial non-partisan. Calon anggota DPD maju sebagai perseorangan yang didukung oleh sejumlah KTP pemilih di daerahnya, bukan atas nama partai politik.</p><p>Jawaban tepat: <strong>E. Peserta pemilu untuk memilih anggota DPD adalah perseorangan warga negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena calon DPD tidak memerlukan rekomendasi dari partai politik.</li><li><strong>B</strong>: Kurang tepat karena calon DPD terbuka bagi setiap warga negara yang memenuhi syarat undang-undang.</li><li><strong>C</strong>: Kurang tepat karena koalisi fraksi DPR tidak memiliki hak mengusung calon anggota DPD.</li><li><strong>D</strong>: Kurang tepat karena calon DPD tidak disyaratkan menjabat pimpinan dinas eksekutif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 52) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peserta pemilu untuk memilih anggota DPD adalah perseorangan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 18 ayat (2) UUD 1945, wewenang pemerintahan daerah provinsi, kabupaten, dan kota diselenggarakan melalui prinsip untuk...", "options": ["Menerapkan seluruh instruksi menteri secara mutlak tanpa penyesuaian lokal", "Membentuk mata uang regional guna membiayai belanja operasional kantor", "Mengatur urusan pemerintahan berdasar asas otonomi dan pembantuan", "Menetapkan traktat perbatasan dengan otoritas perwakilan negara tetangga", "Membubarkan satuan peradilan umum yang berada di wilayah teritorial daerah"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Otonomi Daerah dan Tugas Pembantuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintahan daerah provinsi, kabupaten, dan kota <strong>mengatur dan mengurus sendiri urusan pemerintahan menurut asas otonomi dan tugas pembantuan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena asas otonomi memberikan hak, wewenang, dan kewajiban bagi daerah untuk memprakarsai pembangunan sesuai potensi daerahnya, didampingi asas tugas pembantuan (*medebewind*) dari pemerintah tingkat atas.</p><p>Jawaban tepat: <strong>C. Mengatur urusan pemerintahan berdasar asas otonomi dan pembantuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pemda memiliki diskresi otonomi dalam batas kewenangannya untuk menyesuaikan kebutuhan daerah.</li><li><strong>B</strong>: Kurang tepat karena mata uang merupakan kewenangan absolut pemerintah pusat bidang moneter.</li><li><strong>D</strong>: Kurang tepat karena traktat perbatasan merupakan kewenangan eksklusif pemerintah pusat bidang politik luar negeri.</li><li><strong>E</strong>: Kurang tepat karena peradilan umum berada di bawah kekuasaan kehakiman Mahkamah Agung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 53) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mengurus sendiri urusan pemerintahan menurut asas otonomi dan tugas</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Prinsip kedaulatan rakyat dalam penyelenggaraan pemerintahan daerah menurut UUD 1945 diwujudkan melalui ketentuan bahwa pemerintah daerah...", "options": ["Dipimpin oleh dewan penasihat yang ditunjuk langsung oleh kementerian", "Dikelola sepenuhnya oleh aparat birokrasi karier tanpa perwakilan dewan", "Diawasi oleh komite pengawas swasta yang berkedudukan di ibu kota", "Menyerahkan fungsi anggaran kepada majelis pertimbangan kesultanan", "Memiliki DPRD yang anggota-anggotanya dipilih melalui pemilu legislatif"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Lembaga Perwakilan Rakyat Daerah (DPRD)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 18 ayat (3) UUD 1945 menetapkan pemerintahan daerah provinsi, daerah kabupaten, dan kota <strong>memiliki DPRD yang anggota-anggotanya dipilih melalui pemilu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena DPRD berkedudukan sebagai mitra sejajar kepala daerah dalam menjalankan fungsi legislasi daerah (Perda), penganggaran (APBD), dan pengawasan jalannya roda pemerintahan daerah.</p><p>Jawaban tepat: <strong>E. Memiliki DPRD yang anggota-anggotanya dipilih melalui pemilu legislatif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena DPRD bukan dewan penasihat penunjukan melainkan wakil rakyat terpilih lewat pemilu.</li><li><strong>B</strong>: Kurang tepat karena pemerintahan daerah melibatkan unsur perwakilan politik rakyat melalui DPRD.</li><li><strong>C</strong>: Kurang tepat karena pengawasan politik dijalankan oleh DPRD dan pengawasan keuangan oleh BPK.</li><li><strong>D</strong>: Kurang tepat karena hak anggaran daerah berada pada wewenang DPRD bersama kepala daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 53) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>anggota-anggotanya dipilih melalui pemilu</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 18 ayat (4) UUD 1945, suksesi kepemimpinan eksekutif di tingkat daerah dilaksanakan melalui ketentuan bahwa...", "options": ["Kepala daerah diangkat mutlak oleh menteri atas dasar loyalitas", "Gubernur, Bupati, Walikota dipilih demokratis sesuai undang-undang", "Jabatan kepala daerah diwariskan kepada garis keluarga penguasa", "Kepala dinas senior secara otomatis dilantik menduduki kursi bupati dan walikota", "Pemilihan kepala daerah ditentukan melalui lelang kontribusi modal"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemilihan Kepala Daerah Secara Demokratis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UUD 1945 menetapkan bahwa <strong>Gubernur, Bupati, Walikota masing-masing sebagai kepala pemerintah daerah provinsi, kabupaten, dan kota dipilih secara demokratis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena rumusan 'dipilih secara demokratis' memberikan landasan konstitusional bagi pemilihan kepala daerah, baik melalui pemilihan langsung oleh rakyat (Pilkada) maupun sistem demokratis khusus yang diatur dalam undang-undang bagi daerah istimewa.</p><p>Jawaban tepat: <strong>B. Gubernur, Bupati, Walikota dipilih demokratis sesuai undang-undang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kepala daerah tidak diangkat sepihak oleh menteri melainkan dipilih secara demokratis.</li><li><strong>C</strong>: Kurang tepat karena suksesi kepemimpinan monarki dinasti bertentangan dengan prinsip demokrasi republik.</li><li><strong>D</strong>: Kurang tepat karena kepala daerah merupakan jabatan politik hasil pemilihan demokratis, bukan promosi karier PNS.</li><li><strong>E</strong>: Kurang tepat karena pemilihan kepala daerah bertumpu pada mandat suara kedaulatan rakyat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 53) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Gubernur, Bupati, Walikota masing-masing sebagai kepala pemerintah daerah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Prinsip pembagian kewenangan pemerintahan antara pusat dan daerah menurut Pasal 18 ayat (5) UUD 1945 diatur bahwa...", "options": ["Daerah berwenang mengendalikan kebijakan moneter dan mencetak uang lokal", "Seluruh urusan publik wajib diputuskan terpusat di kementerian", "Pemerintah pusat melimpahkan wewenang militer dan peradilan ke daerah", "Menjalankan otonomi seluasnya, kecuali urusan yang ditentukan pusat", "Pemerintah daerah tidak memiliki kewenangan mengatur tata kelola fasilitas umum"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Otonomi Seluas-Luasnya dan Urusan Pusat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 18 ayat (5) UUD 1945, <strong>pemerintahan daerah menjalankan otonomi seluas-luasnya, kecuali urusan pemerintahan yang oleh undang-undang ditentukan sebagai urusan pemerintah pusat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena daerah diberi keleluasaan luas untuk mengurus urusan konkuren dan lokal, sedangkan urusan absolut (politik luar negeri, pertahanan, keamanan, moneter, yustisi, dan agama) tetap menjadi wewenang eksklusif pemerintah pusat.</p><p>Jawaban tepat: <strong>D. Menjalankan otonomi seluasnya, kecuali urusan yang ditentukan pusat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kebijakan moneter merupakan urusan pemerintah pusat (Bank Indonesia).</li><li><strong>B</strong>: Kurang tepat karena sentralisasi pelayanan publik bertentangan dengan semangat otonomi seluas-luasnya.</li><li><strong>C</strong>: Kurang tepat karena peradilan dan pertahanan militer merupakan wewenang absolut pusat.</li><li><strong>E</strong>: Kurang tepat karena pengelolaan fasilitas umum daerah merupakan urusan otonomi pemda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 53) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemerintahan yang oleh undang-undang ditentukan sebagai urusan pemerintah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dalam mengatur hubungan wewenang antara pemerintah pusat dan daerah, UUD 1945 mewajibkan adanya prinsip untuk...", "options": ["Memperhatikan kekhususan dan keragaman daerah dalam kerangka kesatuan negara", "Standardisasi tata kelola seragam tanpa mempedulikan budaya lokal", "Menghapuskan kearifan lokal yang telah berkembang di tengah masyarakat suku", "Memberikan hak pemisahan wilayah bagi daerah yang berpenghasilan tambang tinggi", "Membatasi komunikasi resmi antara kepala daerah dengan jajaran kementerian"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Kewenangan Pusat dan Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hubungan wewenang antara pusat dan pemda provinsi, kabupaten, dan kota diatur dengan undang-undang dengan <strong>memperhatikan kekhususan dan keragaman daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Indonesia memiliki keragaman geografis, sosial, budaya, dan historis. Hubungan kewenangan pusat-daerah harus fleksibel dan akomodatif terhadap pluralitas daerah guna menjaga keharmonisan nasional.</p><p>Jawaban tepat: <strong>A. Memperhatikan kekhususan dan keragaman daerah dalam kerangka kesatuan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena penyeragaman birokrasi kaku bertentangan dengan pengakuan keragaman daerah.</li><li><strong>C</strong>: Kurang tepat karena kearifan lokal dihormati dan dilindungi oleh konstitusi negara.</li><li><strong>D</strong>: Kurang tepat karena hak pemisahan diri (secession) dilarang dalam kerangka NKRI.</li><li><strong>E</strong>: Kurang tepat karena komunikasi koordinasi pusat-daerah wajib terjalin intensif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 53) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>memperhatikan kekhususan dan keragaman daerah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 18B ayat (1) UUD 1945, sikap konstitusional negara terhadap daerah yang memiliki keistimewaan historis ditegaskan bahwa...", "options": ["Pemerintah pusat menghapus otonomi khusus demi unifikasi tata kelola wilayah", "Daerah istimewa dibebaskan dari kewajiban mematuhi konstitusi", "Negara mengakui satuan pemda yang bersifat khusus atau istimewa", "Wilayah khusus diwajibkan membentuk angkatan bersenjata mandiri tingkat provinsi", "Status keistimewaan daerah dicabut apabila terjadi pergantian kepemimpinan pusat"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengakuan Daerah Khusus dan Istimewa Pasal 18B Ayat 1</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 18B ayat (1) UUD 1945, <strong>Negara mengakui dan menghormati satuan-satuan pemda yang bersifat khusus atau bersifat istimewa diatur dengan undang-undang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena konstitusi menghargai hak asal-usul dan jasa historis daerah istimewa (seperti Aceh dan DIY) serta daerah berstatus otonomi khusus (seperti DKI Jakarta dan Papua) yang diatur dengan undang-undang spesifik.</p><p>Jawaban tepat: <strong>C. Negara mengakui satuan pemda yang bersifat khusus atau istimewa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena negara mengakui dan menghormati status khusus tersebut, bukan menghapusnya.</li><li><strong>B</strong>: Kurang tepat karena daerah istimewa tetap terikat dalam kerangka kedaulatan UUD 1945 dan NKRI.</li><li><strong>D</strong>: Kurang tepat karena pertahanan militer tetap tunggal di bawah Tentara Nasional Indonesia.</li><li><strong>E</strong>: Kurang tepat karena status keistimewaan dijamin permanen oleh undang-undang dan konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 53) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Negara mengakui dan menghormati satuan-satuan pemda yang bersifat khusus</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Perlindungan konstitusional terhadap kearifan lokal diatur dalam Pasal 18B ayat (2) UUD 1945 melalui ketentuan bahwa...", "options": ["Hukum adat dinyatakan gugur demi penegakan kodifikasi hukum perdata modern", "Masyarakat adat dilarang menggunakan bahasa daerah dalam pergaulan sehari-hari", "Tanah ulayat adat diambil alih secara mutlak oleh korporasi tanpa ganti rugi", "Lembaga peradilan adat memegang wewenang memvonis hukuman pidana mati nasional", "Negara mengakui kesatuan masyarakat adat beserta hak tradisionalnya"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengakuan Masyarakat Hukum Adat Pasal 18B Ayat 2</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UUD 1945 menegaskan: <strong>Negara mengakui dan menghormati kesatuan-kesatuan masyarakat hukum adat beserta hak-hak tradisionalnya sepanjang masih hidup dan sesuai dengan perkembangan masyarakat dan prinsip NKRI</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena masyarakat hukum adat dan hak tradisionalnya (seperti hak ulayat) merupakan bagian tak terpisahkan dari kekayaan peradaban bangsa yang dijamin eksistensinya oleh hukum negara.</p><p>Jawaban tepat: <strong>E. Negara mengakui kesatuan masyarakat adat beserta hak tradisionalnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena hukum adat tetap diakui eksistensinya berdampingan dengan hukum positif.</li><li><strong>B</strong>: Kurang tepat karena bahasa daerah dilindungi dan dipelihara oleh negara sesuai Pasal 32 UUD 1945.</li><li><strong>C</strong>: Kurang tepat karena pengambilan tanah adat sewenang-wenang melanggar jaminan hak konstitusional.</li><li><strong>D</strong>: Kurang tepat karena vonis pidana mati hanya dapat dijatuhkan oleh pengadilan peradilan umum berkekuatan tetap.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 53) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Negara mengakui dan menghormati kesatuan-kesatuan masyarakat hukum adat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pengakuan terhadap hak tradisional kesatuan masyarakat hukum adat dalam Pasal 18B ayat (2) UUD 1945 dibatasi oleh syarat konstitusional yaitu...", "options": ["Wajib memiliki kepengurusan formal yang disahkan oleh pimpinan dewan perwakilan", "Sepanjang masih hidup dan sesuai dengan perkembangan masyarakat dan prinsip NKRI", "Wajib menyetorkan pungutan retribusi adat ke kas perbendaharaan pusat", "Dibatasi pada wilayah teritorial yang berpenduduk lebih dari satu juta jiwa", "Wajib menggunakan bahasa pengantar asing dalam pertemuan musyawarah adat"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Pengakuan Kesatuan Masyarakat Hukum Adat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengakuan masyarakat adat berlaku bersyarat: <strong>sepanjang masih hidup dan sesuai dengan perkembangan masyarakat dan prinsip NKRI, yang diatur dengan undang-undang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena persyaratan ini menjamin bahwa praktik hukum adat yang diakui tidak bertentangan dengan prinsip hak asasi manusia modern, keutuhan kedaulatan teritorial NKRI, dan nilai-nilai Pancasila.</p><p>Jawaban tepat: <strong>B. Sepanjang masih hidup dan sesuai dengan perkembangan masyarakat dan prinsip NKRI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena legitimasi masyarakat adat bertumpu pada hukum adat turun-temurun, bukan pengesahan dewan.</li><li><strong>C</strong>: Kurang tepat karena pengakuan hak adat tidak dikaitkan dengan upeti hasil panen.</li><li><strong>D</strong>: Kurang tepat karena jumlah populasi bukan syarat pengakuan kesatuan masyarakat hukum adat.</li><li><strong>E</strong>: Kurang tepat karena bahasa pengantar adat adalah bahasa daerah masing-masing.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 53) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sepanjang masih hidup dan sesuai dengan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 18 ayat (6) UUD 1945, pembentukan kelembagaan serta operasional pemerintahan daerah diatur melalui amanat bahwa...", "options": ["Tata kelola daerah ditentukan kesepakatan asosiasi kepala desa", "Tata kerja dinas daerah diserahkan kepada dewan arbitrase perdagangan kawasan", "Struktur satuan kerja perangkat daerah ditetapkan sekehendak pimpinan komisi", "Susunan dan tata cara pemerintahan daerah diatur dalam undang-undang", "Birokrasi daerah berhak menyimpang dari regulasi presiden"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Landasan Pengaturan Pemerintahan Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UUD 1945 menegaskan: <strong>Susunan dan tata cara penyelenggaraan pemerintahan daerah diatur dalam undang-undang</strong> (yaitu Undang-Undang Pemerintahan Daerah).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena undang-undang menjadi instrumen hukum yang menyeragamkan tata kelola dasar, pembagian urusan, sistem pengawasan, dan tata tertib hubungan eksekutif-legislatif daerah secara nasional.</p><p>Jawaban tepat: <strong>D. Susunan dan tata cara pemerintahan daerah diatur dalam undang-undang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penyelenggaraan pemerintahan daerah bersumber dari undang-undang formal negara.</li><li><strong>B</strong>: Kurang tepat karena dewan arbitrase mengurusi perdata sengketa dagang, bukan birokrasi daerah.</li><li><strong>C</strong>: Kurang tepat karena struktur perangkat daerah diatur berdasarkan pedoman regulasi undang-undang dan PP.</li><li><strong>E</strong>: Kurang tepat karena birokrasi daerah wajib patuh pada hierarki peraturan perundang-undangan nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 53) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Susunan dan tata cara penyelenggaraan pemerintahan daerah diatur dalam</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Konsep pembagian wilayah Negara Kesatuan Republik Indonesia menurut Pasal 18 ayat (1) UUD 1945 dirumuskan dengan ketentuan bahwa...", "options": ["NKRI dibagi atas daerah provinsi, dan provinsi dibagi kabupaten/kota", "Indonesia merupakan federasi dari negara-negara bagian otonom", "Wilayah nasional disatukan dalam satu karesidenan kendali presiden", "Daerah kabupaten memegang kedaulatan mutlak yang setara dengan pemerintah pusat", "Batas teritorial daerah ditetapkan berdasar persebaran modal swasta"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Struktur Pembagian Wilayah Negara Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 18 ayat (1) UUD 1945 menegaskan: <strong>Negara Kesatuan Republik Indonesia dibagi atas daerah-daerah provinsi dan daerah provinsi itu dibagi atas kabupaten dan kota, yang masing-masing mempunyai pemerintahan daerah yang diatur dengan undang-undang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena frasa 'dibagi atas' (bukan 'terdiri atas') membuktikan secara yuridis bahwa Indonesia adalah negara kesatuan (*unitary state*), di mana kedaulatan tidak terbagi ke negara-negara bagian melainkan didelegasikan melalui pembagian wilayah administratif berpemerintahan sendiri.</p><p>Jawaban tepat: <strong>A. NKRI dibagi atas daerah provinsi, dan provinsi dibagi kabupaten/kota</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena Indonesia adalah negara kesatuan, bukan negara federasi.</li><li><strong>C</strong>: Kurang tepat karena sistem karesidenan merupakan warisan kolonial dan tidak dikenal dalam UUD 1945.</li><li><strong>D</strong>: Kurang tepat karena kedaulatan berada di tangan rakyat dan negara kesatuan, bukan di kabupaten.</li><li><strong>E</strong>: Kurang tepat karena batas teritorial daerah ditetapkan atas dasar administratif dan geografis perundang-undangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 53) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemerintahan daerah provinsi, daerah kabupaten, dan kota</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dalam sejarah konstitusi Indonesia, periode kurun waktu pertama berlakunya UUD 1945 berlangsung pada rentang...", "options": ["Mulai tanggal 17 Agustus 1945 hingga tanggal 17 Agustus 1950 secara penuh", "Terhitung sejak 27 Desember 1949 hingga terbitnya dekrit presiden 1959", "Sejak pengesahan piagam jakarta hingga pembentukan dewan perancang nasional", "Kurun waktu pertama dari 18 Agustus 1945 hingga 27 Desember 1949", "Mulai 5 Juli 1959 hingga rangkaian sidang umum majelis tahun 1965"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kurun Waktu Pertama Berlakunya UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UUD 1945 berlaku dalam dua kurun waktu. Berlakunya UUD 1945 dalam <strong>kurun waktu pertama dari tanggal 18 Agustus 1945 hingga tanggal 27 Desember 1949</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kurun waktu pertama dimulai sehari setelah Proklamasi Kemerdekaan saat PPKI mengesahkan UUD 1945 pada 18 Agustus 1945, dan berakhir saat kedaulatan diserahkan kepada Republik Indonesia Serikat (RIS) pada 27 Desember 1949.</p><p>Jawaban tepat: <strong>D. Kurun waktu pertama dari 18 Agustus 1945 hingga 27 Desember 1949</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pada 27 Desember 1949 diberlakukan Konstitusi RIS.</li><li><strong>B</strong>: Kurang tepat karena rentang 1949–1959 merupakan periode berlakunya Konstitusi RIS dan UUDS 1950.</li><li><strong>C</strong>: Kurang tepat karena Piagam Jakarta disusun pada 22 Juni 1945 sebelum kemerdekaan.</li><li><strong>E</strong>: Kurang tepat karena kurun waktu pasca-Dekrit 5 Juli 1959 merupakan kurun waktu kedua berlakunya UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 54) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pertama dari tanggal 18 Agustus 1945 hingga tanggal 27 Desember 1949</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Karena lembaga tinggi negara (MPR, DPR, DPA) belum dapat dibentuk pada awal kemerdekaan, langkah yang diambil PPKI adalah...", "options": ["PPKI menetapkan Komite Nasional pembantu Presiden jalankan negara", "Menyerahkan kekuasaan administratif kepada kantor perwakilan sekutu", "Membentuk dewan militer darurat guna mengambil alih kendali pemerintahan", "Menunjuk gubernur jenderal sebagai kepala pemerintahan sementara waktu", "Membekukan seluruh aktivitas birokrasi kementerian di tingkat ibu kota"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penetapan Komite Nasional Oleh PPKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Karena lembaga-lembaga negara (MPR, DPR, DPA) belum dapat dibentuk, <strong>PPKI menetapkan Komite Nasional sebagai pembantu Presiden</strong>, untuk pembenarannya diberlakukan Pasal IV Aturan Peralihan UUD 1945.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena situasi revolusi fisik dan agresi militer tidak memungkinkan diselenggarakannya pemilu untuk membentuk MPR dan DPR. Komite Nasional Indonesia Pusat (KNIP) dibentuk sebagai wadah darurat penopang jalannya pemerintahan Presiden Soekarno.</p><p>Jawaban tepat: <strong>A. PPKI menetapkan Komite Nasional pembantu Presiden jalankan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena para pendiri bangsa mempertahankan kedaulatan merdeka penuh dari pihak asing.</li><li><strong>C</strong>: Kurang tepat karena kepemimpinan negara tetap berada pada supremasi sipil dwitunggal Soekarno-Hatta.</li><li><strong>D</strong>: Kurang tepat karena jabatan gubernur jenderal merupakan struktur kolonial yang telah runtuh.</li><li><strong>E</strong>: Kurang tepat karena roda birokrasi kementerian tetap digerakkan untuk melayani rakyat merdeka.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 54) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PPKI menetapkan Komite Nasional sebagai pembantu Presiden</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Penyimpangan praktik ketatanegaraan terhadap teks konstitusi yang terjadi pada kurun waktu pertama kemerdekaan adalah...", "options": ["Penghapusan status kedaulatan teritorial negara kesatuan oleh pemerintah pusat", "Pembentukan dewan moneter yang mencetak mata uang gulden secara massal", "Sistem Presidensial menurut UUD 1945 diubah menjadi Parlementer", "Pengalihan wewenang panglima angkatan perang kepada pimpinan kepolisian", "Pemberian hak veto kepada delegasi korporasi swasta dalam sidang kabinet"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peralihan Sistem Kabinet Presidensial ke Parlementer</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam kurun waktu pertama, <strong>sistem Kabinet Presidensial yang harus dilaksanakan menurut UUD 1945 diubah menjadi Kabinet Parlementer</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Maklumat Pemerintah 14 November 1945 menggeser kepemimpinan kabinet dari Presiden ke Perdana Menteri (Sutan Sjahrir) dan menteri bertanggung jawab kepada KNIP. Langkah politis ini diambil untuk meyakinkan sekutu bahwa Indonesia adalah negara demokratis, bukan bentukan fasis Jepang.</p><p>Jawaban tepat: <strong>C. Sistem Presidensial menurut UUD 1945 diubah menjadi Parlementer</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena status kedaulatan negara kesatuan senantiasa dipertahankan dengan gigih.</li><li><strong>B</strong>: Kurang tepat karena Indonesia menerbitkan mata uang sendiri (ORI) pada Oktober 1946.</li><li><strong>D</strong>: Kurang tepat karena panglima angkatan perang (Jenderal Soedirman) memimpin struktur militer mandiri.</li><li><strong>E</strong>: Kurang tepat karena kabinet tidak memberikan hak veto kepada korporasi swasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 54) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sistem Kabinet Presidensial yang harus dilaksanakan menurut UUD 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Konstitusi Republik Indonesia Serikat (RIS) diberlakukan di Indonesia pada masa transisi ketatanegaraan yaitu...", "options": ["Terhitung sejak 17 Agustus 1945 hingga Konferensi Meja Bundar disepakati", "Sepanjang masa agresi militer belanda pertama di wilayah kepulauan jawa", "Mulai pembacaan teks proklamasi hingga penyerahan mandat perdana menteri", "Sejak 17 Agustus 1950 hingga penetapan dekrit presiden 5 Juli 1959", "Berlaku Konstitusi RIS dari 27 Desember 1949 hingga 17 Agustus 1950"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Periode Berlakunya Konstitusi RIS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Antara kurun waktu pertama dan kurun waktu kedua berlakunya UUD 1945, <strong>berlaku konstitusi RIS dari tanggal 27 Desember 1949 hingga tanggal 17 Agustus 1950</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena berlakunya Konstitusi RIS merupakan konsekuensi dari hasil Konferensi Meja Bundar (KMB) di Den Haag, di mana Belanda mengakui kedaulatan Indonesia dalam bentuk negara federasi RIS yang berlangsung selama kurang lebih delapan bulan.</p><p>Jawaban tepat: <strong>E. Berlaku Konstitusi RIS dari 27 Desember 1949 hingga 17 Agustus 1950</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pada 1945–1949 yang berlaku secara de jure adalah UUD 1945.</li><li><strong>B</strong>: Kurang tepat karena Agresi Militer Belanda I terjadi pada tahun 1947 di era UUD 1945 kurun pertama.</li><li><strong>C</strong>: Kurang tepat karena pembacaan teks proklamasi mengawali berlakunya UUD 1945 kurun pertama.</li><li><strong>D</strong>: Kurang tepat karena rentang 1950–1959 merupakan periode berlakunya UUDS 1950.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 54) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>konstitusi RIS dari tanggal 27 Desember 1949 hingga tanggal 17 Agustus 1950</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Meskipun Konstitusi RIS diberlakukan secara federal, status UUD 1945 pada kurun 1949–1950 tetap dipertahankan oleh...", "options": ["Seluruh negara bagian boneka bentukan van Mook di wilayah timur", "Negara bagian RI ibukota Jogjakarta yang tetap berlakukan UUD 1945", "Pemerintah distrik militer di kepulauan riau dan sekitarnya", "Dewan perwakilan persekutuan dagang di pelabuhan tanjung priok", "Kantor administrasi komisi jasa baik perserikatan bangsa-bangsa"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Eksistensi RI Yogyakarta Selama Periode RIS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Konstitusi RIS tidak berlaku di <strong>negara Republik Indonesia yang beribukota Jogjakarta yang tetap memberlakukan UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena dalam struktur federasi RIS, Republik Indonesia (dengan Presiden Mr. Assaat dan ibukota Yogyakarta) merupakan salah satu negara bagian yang tetap setia berpegang teguh pada UUD 1945 hingga pembubaran RIS.</p><p>Jawaban tepat: <strong>B. Negara bagian RI ibukota Jogjakarta yang tetap berlakukan UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena negara bagian bentukan Belanda tunduk pada Konstitusi RIS.</li><li><strong>C</strong>: Kurang tepat karena distrik militer terikat pada struktur komando angkatan perang persekutuan.</li><li><strong>D</strong>: Kurang tepat karena persekutuan dagang bukan entitas negara bagian ketatanegaraan.</li><li><strong>E</strong>: Kurang tepat karena KTN/UNCI merupakan komisi penengah internasional PBB.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 54) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Republik Indonesia yang beribukota Jogjakarta</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Setelah negara federal RIS dibubarkan dan kembali ke bentuk kesatuan, hukum dasar yang digunakan Indonesia adalah...", "options": ["Rancangan konstitusi piagam jakarta yang disempurnakan panitia sembilan", "Kitab hukum tata negara transisi yang disahkan oleh komite arbitrase", "Naskah undang-undang dasar persemakmuran bentukan konferensi den haag", "Sejak 17 Agustus 1950 hingga 5 Juli 1959 berlaku naskah UUDS 1950", "Maklumat dewan perancang undang-undang darurat perang tahun 1950"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Periode Berlakunya UUDS 1950</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Selanjutnya, <strong>sejak tanggal 17 Agustus 1950 hingga tanggal 5 Juli 1959 berlaku Undang-Undang Dasar Sementara 1950 (UUDS 1950)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena piagam persetujuan RIS dan RI pada 19 Mei 1950 menyepakati pembentukan NKRI dengan memberlakukan konstitusi baru berkarakter parlementer liberal yang dinamakan UUDS 1950 hingga konstitusi definitif selesai dirumuskan oleh Konstituante.</p><p>Jawaban tepat: <strong>D. Sejak 17 Agustus 1950 hingga 5 Juli 1959 berlaku naskah UUDS 1950</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Piagam Jakarta adalah dokumen perumusan naskah pra-kemerdekaan 1945.</li><li><strong>B</strong>: Kurang tepat karena hukum dasar formal yang berlaku adalah UUDS 1950.</li><li><strong>C</strong>: Kurang tepat karena naskah Konstitusi RIS digantikan oleh UUDS 1950 pada 17 Agustus 1950.</li><li><strong>E</strong>: Kurang tepat karena UUDS 1950 ditetapkan melalui UU No. 7 Tahun 1950, bukan maklumat darurat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 54) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sejak tanggal 17 Agustus 1950 hingga tanggal 5 Juli 1959 berlaku</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Landasan yuridis yang mengembalikan pemberlakuan UUD 1945 untuk kurun waktu kedua di Indonesia adalah...", "options": ["Dekrit Presiden 5 Juli 1959 dasar hukum UUD 1945 kurun waktu kedua", "Resolusi majelis umum dewan perdamaian internasional perserikatan bangsa", "Putusan bersama antara pimpinan fraksi parlemen dan mahkamah agung", "Instruksi kepala staf komando pemulihan keamanan dan ketertiban nasional", "Hasil kesepakatan pemungutan suara bulat seluruh anggota konstituante"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dekrit Presiden 5 Juli 1959</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ditetapkan <strong>Dekrit Presiden tanggal 5 Juli 1959 merupakan dasar hukum berlakunya UUD 1945 dalam kurun waktu kedua hingga sekarang (sebelum diamandemen)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kebuntuan (*deadlock*) di badan Konstituante untuk menetapkan UUD baru mendorong Presiden Soekarno mengeluarkan Dekrit 5 Juli 1959 berdasarkan hukum keadaan darurat negara (*staatsnoodrecht*), yang membubarkan Konstituante dan memberlakukan kembali UUD 1945.</p><p>Jawaban tepat: <strong>A. Dekrit Presiden 5 Juli 1959 dasar hukum UUD 1945 kurun waktu kedua</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena kembalinya UUD 1945 merupakan tindakan hukum internal kedaulatan bangsa Indonesia.</li><li><strong>C</strong>: Kurang tepat karena Dekrit 5 Juli 1959 merupakan tindakan unilateral Presiden Soekarno.</li><li><strong>D</strong>: Kurang tepat karena Kopkamtib baru dibentuk pada tahun 1965 pasca-G30S.</li><li><strong>E</strong>: Kurang tepat karena Konstituante gagal mencapai kuorum 2/3 dalam pemungutan suara kembali ke UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 54) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Presiden tanggal 5 Juli 1959 merupakan dasar hukum berlakunya UUD 1945 dalam</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Penyebab utama UUD 1945 belum dapat dilaksanakan secara tertib dan sempurna pada kurun waktu 1945–1949 adalah...", "options": ["Terjadinya krisis pertikaian perebutan batas perairan maritim antarprovinsi", "Adanya penolakan massal terhadap naskah pembukaan undang-undang dasar", "Memusatkan upaya pertahankan kemerdekaan dari agresi kolonial", "Ketiadaan tenaga ahli sarjana hukum perundang-undangan di seluruh daerah", "Kegagalan perbankan nasional dalam menstabilkan cadangan emas perbendaharaan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Perjuangan Indonesia Kurun 1945-1949</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam kurun waktu tahun 1945-1949 Indonesia <strong>memusatkan segala upaya untuk mempertahankan kemerdekaan</strong>, karena pihak kolonial Belanda ingin menjajah kembali Indonesia yang sudah merdeka.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena perang revolusi fisik mempertahankan kemerdekaan menyerap seluruh energi bangsa. Prioritas negara adalah perjuangan diplomasi dan bersenjata menghadapi agresi militer Belanda I dan II, sehingga agenda pembentukan lembaga formal pemilu terpaksa ditunda.</p><p>Jawaban tepat: <strong>C. Memusatkan upaya pertahankan kemerdekaan dari agresi kolonial</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pertikaian antardaerah bukan faktor dominan melainkan agresi kolonial Belanda.</li><li><strong>B</strong>: Kurang tepat karena naskah Pembukaan UUD 1945 diterima penuh sebagai konsensus luhur bangsa.</li><li><strong>D</strong>: Kurang tepat karena para pendiri bangsa merupakan tokoh-tokoh berpendidikan tinggi dan sarjana hukum mumpuni.</li><li><strong>E</strong>: Kurang tepat karena masalah moneter merupakan akibat perang, bukan penyebab utama kendala konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 54) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>memusatkan segala upaya untuk mempertahankan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Dasar yuridis yang digunakan untuk melegitimasi pemusatan wewenang pemerintahan darurat pada awal kemerdekaan adalah...", "options": ["Pemberlakuan pasal aturan tambahan mengenai tata cara perubahan pasal UUD", "Dekrit penyerahan kedaulatan sementara kepada komite palang merah internasional", "Penerapan instruksi darurat sipil yang disahkan oleh perwakilan negara sekutu", "Perjanjian bantuan administrasi militer antara kementerian dengan milisi rakyat", "Diberlakukan pasal IV Aturan Peralihan UUD 1945 dalam kekuasaan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemberlakuan Pasal IV Aturan Peralihan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebagai landasan ketatanegaraan darurat, <strong>diberlakukan pasal IV Aturan Peralihan UUD 1945 (asli)</strong> yang menjadi dasar hukum pelaksanaan wewenang lembaga tinggi negara oleh Presiden dibantu Komite Nasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pasal IV Aturan Peralihan UUD 1945 dirancang secara visioner oleh PPKI untuk menjembatani kevakuman kelembagaan sebelum lembaga-lembaga permanen (MPR, DPR, DPA) dapat dibentuk melalui pemilu demokratis.</p><p>Jawaban tepat: <strong>E. Diberlakukan pasal IV Aturan Peralihan UUD 1945 dalam kekuasaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena aturan tambahan mengatur status naskah UUD, bukan kewenangan eksekutif darurat.</li><li><strong>B</strong>: Kurang tepat karena Indonesia berdaulat penuh dan tidak menyerahkan kekuasaan kepada PMI.</li><li><strong>C</strong>: Kurang tepat karena Indonesia menolak tunduk pada instruksi sekutu dan NICA.</li><li><strong>D</strong>: Kurang tepat karena laskar rakyat berada dalam koordinasi pertahanan, bukan badan hukum administrasi negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 54) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Diperlakukan pasal IV Aturan Peralihan UUD 1945 (asli)</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Realitas ketatanegaraan yang terjadi pada hari-hari pertama pasca-proklamasi kemerdekaan adalah...", "options": ["Seluruh anggota parlemen telah dilantik secara paripurna di gedung dewan", "Lembaga-lembaga negara belum dapat dibentuk berdasarkan ketentuan UUD 1945", "Sistem peradilan kasasi telah beroperasi penuh di seluruh pelosok keresidenan", "Badan pemeriksa keuangan telah menyelesaikan audit neraca penutupan kas kolonial", "Komisi pemilihan umum telah mengumumkan jadwal pemungutan suara nasional"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kondisi Kelembagaan Negara Awal Kemerdekaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam catatan kurun waktu 1945-1949: <strong>Lembaga-lembaga negara belum dapat dibentuk berdasarkan ketentuan UUD 1945, karena kondisi dan situasi yang tidak memungkinkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena negara Indonesia yang baru merdeka belum memiliki perangkat peraturan pelaksana pemilu, struktur logistik, maupun keamanan yang stabil. Akibatnya, fungsi kelembagaan negara dijalankan oleh Presiden dengan bantuan Komite Nasional.</p><p>Jawaban tepat: <strong>B. Lembaga-lembaga negara belum dapat dibentuk berdasarkan ketentuan UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena parlemen hasil pemilu baru terbentuk pada tahun 1955.</li><li><strong>C</strong>: Kurang tepat karena operasional MA dan peradilan nasional baru dalam tahap perintisan unifikasi.</li><li><strong>D</strong>: Kurang tepat karena BPK baru didirikan pada tanggal 1 Januari 1947 di Magelang.</li><li><strong>E</strong>: Kurang tepat karena KPU pemilu pertama baru dibentuk pada dekade 1950-an.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 54) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Lembaga-lembaga negara belum dapat dibentuk berdasarkan ketentuan UUD</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Rumusan yuridis Pasal IV Aturan Peralihan UUD 1945 naskah asli menegaskan bunyi ketentuan bahwa...", "options": ["Kekuasaan presiden berakhir seketika apabila dewan pertimbangan agung terbentuk", "Peradilan kehakiman dipimpin langsung oleh komandan militer", "Kedaulatan diserahkan kepada komisi jasa baik perserikatan bangsa", "Presiden dilarang membentuk kabinet sebelum izin komite asing", "Sebelum MPR/DPR/DPA dibentuk, kekuasaan dijalankan Presiden dan KNIP"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bunyi Pasal IV Aturan Peralihan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal IV Aturan Peralihan UUD 1945 berbunyi: <strong>'Sebelum MPR, DPR, DPA dibentuk menurut UUD ini, segala kekuasaannya dijalankan oleh Presiden dengan bantuan Komite Nasional'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pasal ini merupakan ketentuan transisi yang memberikan mandat legitimasi penuh bagi Presiden Soekarno untuk menjalankan fungsi kepresidenan sekaligus fungsi legislatif dan konsultatif selama masa revolusi kemerdekaan.</p><p>Jawaban tepat: <strong>E. Sebelum MPR/DPR/DPA dibentuk, kekuasaan dijalankan Presiden dan KNIP</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena DPA merupakan dewan penasihat yang tidak mengakhiri kekuasaan eksekutif presiden.</li><li><strong>B</strong>: Kurang tepat karena peradilan tetap berpedoman pada undang-undang dan hakim independen.</li><li><strong>C</strong>: Kurang tepat karena kedaulatan negara berada di tangan rakyat dan dijalankan Presiden, bukan komisi PBB.</li><li><strong>D</strong>: Kurang tepat karena pembentukan kabinet adalah hak prerogatif presiden merdeka.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 55) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dibentuk menurut UUD ini, segala kekuasaannya dijalankan oleh Presiden</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Konsekuensi ketatanegaraan dari penerapan Pasal IV Aturan Peralihan UUD 1945 pada awal kemerdekaan adalah...", "options": ["Kekuasaan eksekutif terbagi habis ke berbagai dewan pertimbangan daerah otonom", "Kekuasaan Presiden sangat besar meliputi kewenangan semua lembaga", "Presiden kehilangan kendali operasional atas jajaran perwira tentara nasional", "Parlemen memegang kendali pencopotan dwitunggal proklamator", "Birokrasi pemerintahan dijalankan sepenuhnya oleh penasihat ekonomi asing"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsentrasi Kekuasaan Presiden Awal Kemerdekaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan pasal IV Aturan Peralihan UUD 1945, <strong>kekuasaan Presiden sangat besar karena meliputi kewenangan semua lembaga-lembaga tinggi negara</strong>, sedangkan Komite Nasional hanya berfungsi sebagai pembantu Presiden.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Presiden bertindak sebagai pemegang kekuasaan eksekutif sekaligus pemegang kekuasaan legislatif pembentuk undang-undang, sehingga kekuasaan negara terpusat di tangan kepala negara demi kecepatan pengambilan keputusan darurat.</p><p>Jawaban tepat: <strong>B. Kekuasaan Presiden sangat besar meliputi kewenangan semua lembaga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kekuasaan justru memusat di tangan presiden, bukan terbagi habis ke daerah.</li><li><strong>C</strong>: Kurang tepat karena Presiden memegang kekuasaan tertinggi atas angkatan perang.</li><li><strong>D</strong>: Kurang tepat karena pada saat itu MPR dan DPR belum terbentuk.</li><li><strong>E</strong>: Kurang tepat karena birokrasi dipimpin langsung oleh para menteri pejuang bangsa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 55) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>karena meliputi kewenangan semua lembaga-lembaga tinggi negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Perubahan status dan fungsi Komite Nasional Indonesia Pusat (KNIP) menjadi lembaga legislatif ditetapkan melalui...", "options": ["Dekrit kepala staf angkatan bersenjata tentang tata tertib persidangan darurat", "Resolusi bersama dewan perwakilan serikat buruh dan tani seluruh indonesia", "Surat keputusan kementerian penerangan mengenai susunan fraksi politik dewan", "Maklumat Wakil Presiden No. X tanggal 16 Oktober 1945 oleh Hatta", "Instruksi tertulis pimpinan kesultanan jogjakarta kepada jajaran pamong praja"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Maklumat Wakil Presiden No. X Tahun 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>KNIP yang semula berstatus pembantu Presiden berubah menjadi badan pemegang kekuasaan legislatif dan ikut menetapkan GBHN berdasarkan <strong>maklumat Wakil Presiden No. X tanggal 16 Oktober 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Maklumat No. X yang dikeluarkan oleh Wakil Presiden Mohammad Hatta merupakan salah satu konvensi ketatanegaraan terpenting yang mengubah fungsi KNIP dari sekadar pembantu presiden menjadi badan legislatif (DPR sementara) sebelum pemilu terselenggara.</p><p>Jawaban tepat: <strong>D. Maklumat Wakil Presiden No. X tanggal 16 Oktober 1945 oleh Hatta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena perubahan fungsi lembaga perwakilan sipil bukan ranah dekrit staf angkatan bersenjata.</li><li><strong>B</strong>: Kurang tepat karena serikat buruh bukan otoritas hukum penetapan konstitusi negara.</li><li><strong>C</strong>: Kurang tepat karena kementerian penerangan tidak berwenang mengubah kedudukan lembaga tinggi negara.</li><li><strong>E</strong>: Kurang tepat karena Maklumat No. X berlaku nasional dan ditandatangani Wapres Mohammad Hatta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 55) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>maklumat Wakil Presiden No. X</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Maklumat No. X tanggal 16 Oktober 1945, wewenang baru yang diberikan kepada Komite Nasional Pusat adalah...", "options": ["Memegang kekuasaan legislatif dan ikut menetapkan haluan negara", "Menyelenggarakan peradilan kasasi sengketa perdata waris di daerah", "Mengangkat dan memberhentikan para menteri koordinator secara independen", "Menetapkan besaran tarif cukai tembakau impor dari pelabuhan luar negeri", "Menyita sarana transportasi perkeretaapian milik administrasi kolonial"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Baru Komite Nasional Indonesia Pusat (KNIP)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan Maklumat Wapres No. X, Komite Nasional Pusat berubah menjadi <strong>badan yang memegang kekuasaan legislatif dan ikut menetapkan Garis-Garis Besar daripada Haluan Negara</strong> (GBHN).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pemberian wewenang legislatif dan penetapan haluan negara ini menjadikan KNIP menjalankan fungsi ganda yang semestinya dimiliki oleh DPR dan MPR sebelum lembaga hasil pemilu terbentuk.</p><p>Jawaban tepat: <strong>A. Memegang kekuasaan legislatif dan ikut menetapkan haluan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena peradilan kasasi merupakan ranah Mahkamah Agung.</li><li><strong>C</strong>: Kurang tepat karena pengangkatan menteri tetap berada pada ranah kekuasaan eksekutif pemerintah.</li><li><strong>D</strong>: Kurang tepat karena regulasi tarif cukai merupakan wewenang operasional kementerian keuangan.</li><li><strong>E</strong>: Kurang tepat karena nasionalisasi aset dilakukan oleh pemerintah dan serikat buruh kereta api.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 55) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>memegang kekuasaan legislatif dan ikut menetapkan Garis-Garis</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pergeseran sistem kabinet presidensial menuju kabinet parlementer pada November 1945 diawali oleh peristiwa...", "options": ["Gugatan peradilan tata usaha negara dari kalangan pegawai pamong praja", "Tuntutan resmi dari perwakilan korps diplomatik komisi pengawasan sekutu", "Usul Badan Pekerja KNIP (BPKNIP) pada tanggal 11 Nopember 1945", "Mosi tidak percaya serentak dari gabungan pimpinan militer di yogyakarta", "Hasil referendum pemilih yang diselenggarakan serentak di lima provinsi"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Inisiatif Pembentukan Kabinet Parlementer Oleh BPKNIP</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perubahan sistem kabinet berakar dari <strong>usul Badan Pekerja Komite Nasional Indonesia Pusat (BPKNIP) pada tanggal 11 Nopember 1945</strong>, yang disetujui Presiden dan diumumkan lewat Maklumat Pemerintah 14 November 1945.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena BPKNIP (yang dipimpin oleh Sutan Sjahrir) menginginkan para menteri bertanggung jawab secara politis kepada badan perwakilan rakyat, bukan sekadar menjadi pegawai pembantu presiden.</p><p>Jawaban tepat: <strong>C. Usul Badan Pekerja KNIP (BPKNIP) pada tanggal 11 Nopember 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena PTUN belum ada pada awal kemerdekaan.</li><li><strong>B</strong>: Kurang tepat karena usulan perubahan kabinet berasal dari inisiatif internal wakil rakyat BPKNIP.</li><li><strong>D</strong>: Kurang tepat karena militer tidak mengajukan mosi tidak percaya pada 11 November 1945.</li><li><strong>E</strong>: Kurang tepat karena Indonesia tidak menyelenggarakan referendum pada masa revolusi fisik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 55) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>usul Badan Pekerja Komite Nasional Indonesia Pusat (BPKNIP)</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Landasan resmi berlakunya sistem pemerintahan parlementer di awal sejarah Republik Indonesia adalah...", "options": ["Surat perintah pelimpahan wewenang darurat militer kepada pimpinan laskar", "Undang-undang darurat pembentukan susunan kepresidenan federal 1945", "Ketetapan majelis permusyawaratan darurat tentang struktur kementerian", "Piagam kesepakatan damai antara pimpinan perundingan dengan utusan belanda", "Maklumat Pemerintah tanggal 14 Nopember 1945 tentang perubahan sistem kabinet"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Maklumat Pemerintah 14 November 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perubahan sistem kabinet presidensial menjadi sistem kabinet parlementer diumumkan secara resmi melalui <strong>Maklumat Pemerintah tanggal 14 Nopember 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Maklumat 14 November 1945 menandai lahirnya jabatan Perdana Menteri pertama Indonesia (Kabinet Sjahrir I) dan menandai dimulainya praktik parlementer di mana kabinet bertanggung jawab kepada KNIP.</p><p>Jawaban tepat: <strong>E. Maklumat Pemerintah tanggal 14 Nopember 1945 tentang perubahan sistem kabinet</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena sistem parlementer merupakan urusan kepemimpinan politik pemerintahan, bukan darurat militer.</li><li><strong>B</strong>: Kurang tepat karena undang-undang darurat federal baru muncul pada era Konstitusi RIS 1949.</li><li><strong>C</strong>: Kurang tepat karena MPR darurat belum pernah terbentuk pada tahun 1945.</li><li><strong>D</strong>: Kurang tepat karena Maklumat 14 November 1945 diterbitkan sebelum perundingan damai Linggajati.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 55) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Maklumat Pemerintah tanggal 14 Nopember 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Perubahan mendasar dalam struktur eksekutif sejak tanggal 14 November 1945 ditandai oleh kondisi bahwa...", "options": ["Presiden membubarkan KNIP dan memimpin seluruh sidang kementerian sendirian", "Kekuasaan pemerintah dipegang oleh Perdana Menteri sebagai pimpinan kabinet", "Para menteri bertanggung jawab langsung secara personal kepada ratu belanda", "Seluruh keputusan kabinet wajib diratifikasi oleh komite militer gabungan", "Jabatan presiden ditiadakan dan digantikan oleh gubernur jenderal sipil"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Kabinet Parlementer 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sejak tanggal 14 Nopember 1945, <strong>kekuasaan pemerintah tidak dipegang oleh Presiden, tetapi dipegang oleh Perdana Menteri sebagai pimpinan kabinet</strong> dengan para menteri sebagai anggota kabinet.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Presiden berkedudukan sebagai Kepala Negara yang bersifat simbolis (*head of state*), sedangkan kendali eksekutif operasional pemerintahan sehari-hari beralih ke tangan Perdana Menteri (*head of government*).</p><p>Jawaban tepat: <strong>B. Kekuasaan pemerintah dipegang oleh Perdana Menteri sebagai pimpinan kabinet</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena KNIP tidak dibubarkan melainkan bertindak sebagai parlemen pengawas kabinet.</li><li><strong>C</strong>: Kurang tepat karena menteri bertanggung jawab kepada KNIP, bukan kepada ratu Belanda.</li><li><strong>D</strong>: Kurang tepat karena kabinet parlementer dipimpin oleh figur sipil tanpa ratifikasi komite militer.</li><li><strong>E</strong>: Kurang tepat karena Presiden Soekarno tetap menjabat sebagai Kepala Negara Republik Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 55) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Presiden, tetapi dipegang oleh Perdana Menteri</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Pola pertanggungjawaban politik dalam sistem kabinet parlementer pasca-Maklumat 14 November 1945 diatur bahwa...", "options": ["Menteri bertanggung jawab secara eksklusif kepada pimpinan partai pendukung", "Perdana menteri dapat mencopot anggota dewan perwakilan secara otoritatif", "Pemerintah bertanggung jawab kepada majelis permusyawaratan tingkat karesidenan", "Para menteri dan perdana menteri bertanggung jawab kepada KNIP", "Kabinet sekadar menyampaikan laporan kinerja kepada komisi pengawas sekutu"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akuntabilitas Kabinet Kepada KNIP</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam sistem parlementer 1945, <strong>para menteri dan perdana menteri bertanggung jawab kepada KNIP yang berfungsi sebagai DPR</strong>, baik secara bersama-sama maupun sendiri-sendiri.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena ciri khas sistem parlementer adalah kelangsungan kabinet bergantung pada mosi dukungan parlemen. KNIP sewaktu-waktu dapat menjatuhkan kabinet jika mosi tidak percaya diajukan oleh mayoritas anggota dewan.</p><p>Jawaban tepat: <strong>D. Para menteri dan perdana menteri bertanggung jawab kepada KNIP</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pertanggungjawaban disampaikan resmi ke forum perwakilan dewan (KNIP).</li><li><strong>B</strong>: Kurang tepat karena perdana menteri tidak berwenang mencopot anggota parlemen KNIP.</li><li><strong>C</strong>: Kurang tepat karena majelis karesidenan bukan lembaga perwakilan tingkat nasional.</li><li><strong>E</strong>: Kurang tepat karena akuntabilitas kabinet ditujukan kepada wakil rakyat Indonesia sendiri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 55) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Para menteri dan perdana menteri bertanggung jawab kepada KNIP yang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Durasi keberadaan bentuk negara federal Republik Indonesia Serikat (RIS) dalam lintasan sejarah Indonesia adalah...", "options": ["Berlangsung delapan bulan sejak 27 Desember 1949 hingga 17 Agustus 1950", "Bertahan selama lima tahun penuh hingga terselenggaranya pemilu demokratis 1955", "Berjalan tiga dasawarsa sebelum diamandemen oleh dewan perwakilan", "Mencapai sepuluh tahun transisi di bawah komisi perserikatan bangsa", "Berlangsung selama dua tahun kalender terhitung sejak pembacaan dekrit presiden"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Masa Berlakunya Negara Federal RIS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Negara federal RIS <strong>berlangsung sangat singkat hanya 8 bulan yaitu dari tanggal 27 Desember 1949 hingga tanggal 17 Agustus 1950</strong> sebelum kembali menjadi Negara Kesatuan Republik Indonesia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena bentuk federal RIS tidak berakar dari kehendak murni rakyat melainkan kompromi diplomatik KMB. Dalam hitungan bulan, arus unifikasi rakyat di berbagai negara bagian menuntut pembubaran federasi dan integrasi kembali ke NKRI.</p><p>Jawaban tepat: <strong>A. Berlangsung delapan bulan sejak 27 Desember 1949 hingga 17 Agustus 1950</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena pemilu 1955 berlangsung pada era UUDS 1950, bukan pada era RIS.</li><li><strong>C</strong>: Kurang tepat karena RIS hanya bertahan 8 bulan, bukan tiga dasawarsa.</li><li><strong>D</strong>: Kurang tepat karena durasi RIS kurang dari satu tahun.</li><li><strong>E</strong>: Kurang tepat karena Dekrit Presiden terjadi pada tahun 1959, jauh setelah bubarnya RIS pada 1950.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 55) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>berlangsung sangat singkat hanya 8 bulan yaitu dari tanggal 27</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 05", "difficulty": "C2 - Pemahaman", "question": "Faktor pendorong utama yang menyebabkan runtuhnya bentuk negara federal RIS dan kembalinya bentuk NKRI pada 17 Agustus 1950 adalah...", "options": ["Instruksi militer asing agar seluruh daerah lebur ke persemakmuran", "Kegagalan perjanjian utang luar negeri dengan perbankan eropa", "Keinginan negara bagian RIS bentukan Belanda bergabung kembali ke RI", "Penolakan mahkamah internasional atas keanggotaan federasi", "Keputusan mandiri parlemen federal memecat perdana menteri bagian"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Runtuhnya Negara Federal RIS Menuju NKRI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sejak berdirinya RIS, <strong>timbul suatu keinginan dari negara-negara bagian RIS buatan Belanda yang merasa tidak cocok atas terbentuknya RIS hasil KMB, dan ingin bergabung dengan negara bagian Republik Indonesia yang beribukota Jogjakarta</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena negara-negara bagian bentukan van Mook (seperti Negara Madura, Pasundan, Sumatera Timur) menyadari bahwa federasi merupakan strategi politik pecah belah (*devide et impera*). Rakyat dan parlemen negara-negara bagian tersebut secara sukarela membubarkan diri dan bergabung kembali ke Republik Indonesia.</p><p>Jawaban tepat: <strong>C. Keinginan negara bagian RIS bentukan Belanda bergabung kembali ke RI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kembalinya ke NKRI didorong oleh kehendak murni rakyat Indonesia sendiri.</li><li><strong>B</strong>: Kurang tepat karena masalah utang KMB bukan faktor pendorong utama kembalinya integrasi NKRI.</li><li><strong>D</strong>: Kurang tepat karena Mahkamah Internasional tidak mengintervensi bentuk internal ketatanegaraan RIS.</li><li><strong>E</strong>: Kurang tepat karena pembubaran negara bagian dilakukan melalui prosedur resolusi perwakilan daerah masing-masing.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 55) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>timbul suatu keinginan dari negara-negara bagian RIS buatan Belanda</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_wk_p05_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_wk_p05_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_wk_p05_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Wawasan Kebangsaan Paket 05";
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
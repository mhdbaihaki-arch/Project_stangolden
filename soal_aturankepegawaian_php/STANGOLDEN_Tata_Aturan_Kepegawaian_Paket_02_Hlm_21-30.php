<?php
/**
 * STANGOLDEN.exam • Tata Aturan Kepegawaian Paket 02 (Halaman 21–30)
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
// $DB->get_record("stgtak_p02_rank") => tabel fisik "mdlhv_stgtak_p02_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgtak_p02_rank';
const STG_REVIEW_T = 'stgtak_p02_rev';
const STG_ANNO_T = 'stgtak_p02_anno';
const STG_KEY = [0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 4, 2, 3, 4, 0, 1, 0, 4, 0, 1, 0, 1, 2, 3, 4, 0, 1, 0, 2, 3, 0, 2, 4, 1, 3, 0, 2, 4, 1, 3, 1, 3, 0, 2, 4, 1, 2, 3, 4, 2, 4, 4, 1, 3, 0, 2, 1, 3, 0, 4, 3, 0, 2, 4, 1, 3, 2, 2, 1, 3, 4, 1, 3, 0, 2, 4, 1, 3, 0, 2];
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
            error_log('STANGOLDEN TAK P02 HLM21-30 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN TAK P02 HLM21-30: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Tata Aturan Kepegawaian Paket 02 (Hlm. 21–30) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 02 • Hlm. 21–30</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 02 • Hlm. 21–30</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Tata Aturan Kepegawaian Paket 02</h2>
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
const STG_LOCAL_KEY="stg_tak_p02_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Standar materi yang diujikan dalam Seleksi Kompetensi Dasar (SKD) bagi calon PNS meliputi...", "options": ["Karakteristik pribadi, intelegensia umum, dan wawasan kebangsaan", "Keterampilan teknis jabatan, regulasi instansi, dan manajemen kas", "Psikotes lanjutan, wawancara kepemimpinan, dan uji studi kasus", "Penguasaan teknologi digital, portofolio kerja, dan uji terapan", "Keahlian riset substantif, kemampuan bahasa, dan tata naskah"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Materi Standar Seleksi Kompetensi Dasar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Materi SKD meliputi: <strong>a. tes wawasan kebangsaan (TWK)</strong>; <strong>b. tes intelegensia umum (TIU)</strong>; dan <strong>c. tes karakteristik pribadi (TKP)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan materi baku pengujian Seleksi Kompetensi Dasar (SKD) calon aparatur negara. Seleksi awal ini ditujukan untuk menyaring profil kepribadian, kapabilitas kognitif umum, serta komitmen wawasan kebangsaan peserta seleksi. Ketentuan perundang-undangan menetapkan bahwa materi SKD meliputi karakteristik pribadi, intelegensia umum, dan wawasan kebangsaan. Dengan demikian, opsi A merupakan materi yang tepat.</p><p>Jawaban tepat: <strong>A. Karakteristik pribadi, intelegensia umum, dan wawasan kebangsaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan rumpun materi kompetensi bidang keahlian perbendaharaan dan bukan materi SKD.</li><li><strong>C</strong>: Merupakan instrumen tahapan seleksi kompetensi lanjutan dan bukan komponen dasar SKD.</li><li><strong>D</strong>: Merupakan cakupan uji praktik kerja teknis pada tahapan Seleksi Kompetensi Bidang.</li><li><strong>E</strong>: Merupakan instrumen pengujian keahlian jabatan fungsional tertentu pada tahapan SKB.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Seleksi kompetensi dasar dilakukan untuk • karakteristik pribadi, intelegensia umum, dan wawasan kebangsaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama diselenggarakannya Seleksi Kompetensi Bidang (SKB) bagi pelamar CPNS adalah...", "options": ["Mengetahui latar belakang riwayat kehidupan sosial keluarga pelamar", "Menilai kesesuaian kompetensi bidang pelamar dengan kebutuhan jabatan", "Mengukur ketahanan fisik pelamar dalam menjalankan penugasan dinas", "Menentukan besaran indeks tunjangan kinerja awal pelamar di kementerian", "Memverifikasi keabsahan berkas administrasi kewarganegaraan secara hukum"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tujuan Seleksi Kompetensi Bidang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Seleksi kompetensi bidang</strong> dilakukan untuk <strong>menilai kesesuaian antara kompetensi bidang yang dimiliki oleh pelamar dengan standar kompetensi bidang sesuai dengan kebutuhan Jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan fungsi pokok pelaksanaan tahapan SKB dalam pengadaan pegawai. Penilaian spesifik diperlukan guna menjamin pegawai yang ditempatkan menguasai keahlian teknis sesuai formasi jabatannya. Aturan menegaskan SKB bertujuan menguji kesesuaian kompetensi pelamar dengan standar formasi jabatan. Oleh karena itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Menilai kesesuaian kompetensi bidang pelamar dengan kebutuhan jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan materi penelusuran rekam jejak pribadi dan bukan esensi uji SKB.</li><li><strong>C</strong>: Merupakan bagian dari pemeriksaan kesehatan jasmani khusus instansi tertentu.</li><li><strong>D</strong>: Merupakan hak administratif kepegawaian pasca pengangkatan resmi pegawai.</li><li><strong>E</strong>: Merupakan ruang lingkup tahapan seleksi administrasi berkas pendaftaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Seleksi kompetensi bidang dilakukan untuk • kompetensi bidang sesuai dengan kebutuhan Jabatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Masa percobaan wajib yang harus dijalani oleh Calon Pegawai Negeri Sipil dilaksanakan selama...", "options": ["Enam bulan kalender berturut-turut", "Sembilan bulan masa penugasan satker", "Satu tahun masa pembinaan kedinasan", "Dua tahun masa penyesuaian kementerian", "Tiga tahun masa orientasi kelembagaan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Jangka Waktu Masa Percobaan CPNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Masa percobaan</strong> bagi calon PNS <strong>dilaksanakan selama 1 (satu) tahun</strong> melalui proses pendidikan dan pelatihan terintegrasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jangka waktu masa prajabatan CPNS yang sah secara hukum. Periode satu tahun disiapkan sebagai sarana evaluasi kelayakan moral, kepribadian, dan kecakapan kerja sebelum diangkat definitif. Regulasi menegaskan masa percobaan calon PNS berlangsung tepat selama 1 (satu) tahun. Dengan demikian, opsi C adalah ketetapan yang benar.</p><p>Jawaban tepat: <strong>C. Satu tahun masa pembinaan kedinasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan batas minimal waktu evaluasi berkala dan bukan masa percobaan CPNS.</li><li><strong>B</strong>: Merupakan kurun waktu telaah penempatan unit kerja dan bukan durasi resmi.</li><li><strong>D</strong>: Merupakan batas toleransi masa penundaan pengangkatan luar biasa.</li><li><strong>E</strong>: Merupakan durasi evaluasi peninjauan masa kerja fungsional keahlian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Masa percobaan sebagaimana dimaksud bagi calon PNS • selama 1 (satu) tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dua syarat mutlak yang wajib dipenuhi oleh CPNS untuk dapat diangkat menjadi PNS definitif adalah...", "options": ["Memiliki kualifikasi sarjana dan sertifikat keahlian bahasa", "Menyelesaikan penugasan orientasi dinas serta bebas catatan", "Mencapai target kinerja istimewa dan rekomendasi atasan unit", "Lulus pendidikan dan pelatihan serta sehat jasmani dan rohani", "Memiliki masa pengabdian tugas paling singkat lima tahun kerja"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Syarat Pengangkatan CPNS Menjadi PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Calon PNS yang memenuhi persyaratan: <strong>a. lulus pendidikan dan pelatihan; dan b. sehat jasmani dan rohani</strong>, diangkat menjadi PNS oleh PPK.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan persyaratan kumulatif pengangkatan calon pegawai menjadi pegawai negeri sipil penuh. CPNS wajib membuktikan kelulusan pelatihan dasar kedinasan terpadu sekaligus kesiapan fisik dan mental melalui uji kesehatan resmi. Ketentuan perundang-undangan menetapkan syarat mutlak pengangkatan PNS adalah lulus diklat serta sehat jasmani dan rohani. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Lulus pendidikan dan pelatihan serta sehat jasmani dan rohani</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kualifikasi pendidikan merupakan syarat awal pada tahapan pendaftaran pelamaran CPNS.</li><li><strong>B</strong>: Orientasi dinas merupakan masa adaptasi awal dan bukan syarat formal yuridis pengangkatan PNS.</li><li><strong>C</strong>: Target kinerja istimewa merupakan kriteria promosi dan bukan syarat pengangkatan CPNS.</li><li><strong>E</strong>: Masa kerja lima tahun melampaui masa percobaan CPNS yang dibatasi maksimal satu tahun.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Calon PNS yang diangkat menjadi PNS harus memenuhi persyaratan • Lulus pendidikan dan pelatihan • Sehat jasmani dan rohani</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan peraturan manajemen PNS, pendidikan dan pelatihan dasar bagi calon PNS hanya dapat diikuti sebanyak...", "options": ["Tiga kali kesempatan mengulang", "Dua kali kesempatan mengulang", "Empat kali agenda terjadwal", "Lima kali masa pembinaan", "Satu kali kesempatan pelatihan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Ketentuan Kesempatan Pelatihan Dasar CPNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Dalam PP 11/2017 stdd PP 17/2020 ditegaskan bahwa <strong>pendidikan dan pelatihan dasar bagi calon PNS hanya dapat diikuti sebanyak satu kali</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan pembatasan ketat kesempatan diklat prajabatan CPNS. Ketentuan tunggal ini menuntut kesungguhan penuh peserta agar berhasil menuntaskan seluruh kurikulum pelatihan dalam sekali kesempatan. Aturan menetapkan diklat dasar CPNS hanya dapat diikuti satu kali. Dengan demikian, opsi E adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Satu kali kesempatan pelatihan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan asumsi toleransi ujian remedial akademik umum dan tidak berlaku di latsar.</li><li><strong>B</strong>: Merupakan kesempatan ujian dinas kenaikan pangkat dan bukan diklat dasar CPNS.</li><li><strong>C</strong>: Merupakan frekuensi ujian sertifikasi keahlian berkala tenaga fungsional.</li><li><strong>D</strong>: Merupakan skema uji berkala kompetensi tahunan pejabat manajerial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pendidikan dan pelatihan dasar bagi calon PNS • hanya dapat diikuti</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang CPNS memilih mengundurkan diri saat baru menjalani masa percobaan enam bulan. Sanksi administratif yang dijatuhkan adalah...", "options": ["Tidak boleh mengikuti seleksi pengadaan PNS untuk jangka waktu tertentu", "Kewajiban menjalani masa kerja sosial pada instansi pemerintah daerah", "Pencabutan status hak pilih aktif dalam pemilihan umum kepala daerah", "Penahanan ijazah pendidikan formal oleh kementerian selama lima tahun", "Larangan bepergian ke luar negeri untuk seluruh urusan perjalanan dinas"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Sanksi Pengunduran Diri CPNS Masa Percobaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Calon PNS yang mengundurkan diri pada saat menjalani masa percobaan dikenakan sanksi tidak boleh mengikuti seleksi pengadaan PNS untuk jangka waktu tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan konsekuensi hukum bagi pembatalan komitmen sepihak CPNS. Negara telah mengalokasikan anggaran formasi dan diklat sehingga pengunduran diri di masa percobaan dikenakan penalti seleksi masa depan. Aturan menegaskan sanksinya berupa larangan ikut seleksi pengadaan PNS untuk waktu tertentu. Oleh karena itu, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>A. Tidak boleh mengikuti seleksi pengadaan PNS untuk jangka waktu tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan bentuk sanksi pidana kurungan alternatif dan bukan sanksi kepegawaian.</li><li><strong>C</strong>: Merupakan vonis pidana pencabutan hak politik oleh majelis hakim peradilan.</li><li><strong>D</strong>: Merupakan tindakan penahanan dokumen pribadi yang dilarang oleh hukum.</li><li><strong>E</strong>: Merupakan tindakan keimigrasian dalam proses penyidikan perkara pidana.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>calon PNS yang mengundurkan diri pada saat menjalani masa • tidak boleh mengikuti seleksi pengadaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kewajiban formal yang harus dilaksanakan oleh Calon PNS segera setelah resmi diangkat menjadi PNS adalah...", "options": ["Menyerahkan seluruh laporan harta kekayaan kepada inspektorat", "Mengucapkan sumpah atau janji PNS menurut keyakinan agamanya", "Membuat karya tulis ilmiah tentang tata kelola instansi pusat", "Membuka rekening simpanan wajib pegawai pada bank milik negara", "Menyusun peta rencana alih tugas jabatan menuju satker daerah"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kewajiban Pengucapan Sumpah Janji PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Ketentuan kepegawaian menegaskan bahwa <strong>calon PNS wajib mengucapkan sumpah/janji PNS setelah diangkat PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan ikrar kesetiaan konstitusional aparatur kepada negara. Pengucapan sumpah mengikat tanggung jawab moral dan etika kedinasan pegawai dalam mengabdi kepada bangsa. Regulasi merumuskan bahwa calon PNS wajib mengangkat sumpah/janji segera setelah pengangkatan definitif. Dengan demikian, opsi B adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. Mengucapkan sumpah atau janji PNS menurut keyakinan agamanya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kewajiban administratif penyelenggara negara pada jabatan tertentu.</li><li><strong>C</strong>: Merupakan syarat penilaian karya tulis untuk kenaikan jabatan fungsional.</li><li><strong>D</strong>: Merupakan prosedur teknis penyaluran gaji dan bukan kewajiban ikrar.</li><li><strong>E</strong>: Merupakan urusan pemetaan penugasan yang menjadi wewenang biro kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>calon PNS wajib mengucapkan sumpah/janji PNS • setelah diangkat PNS</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang CPNS terbukti memalsukan surat keterangan riwayat pengalaman kerja saat melamar. Tindakan administratif yang dijatuhkan adalah...", "options": ["Penundaan pembayaran tunjangan kinerja selama satu triwulan dinas", "Pemberian teguran lisan di hadapan seluruh pegawai unit kantor satker", "Pemberhentian dengan pertimbangan pemberian keterangan tidak benar", "Pemindahan tugas kerja ke unit pelayanan dengan intensitas lebih tinggi", "Pengurangan masa cuti tahunan pegawai pada tahun anggaran berjalan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemberhentian CPNS Pemalsuan Keterangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Calon PNS dapat <strong>diberhentikan</strong> apabila terbukti <strong>memberikan keterangan atau bukti yang tidak benar pada waktu melamar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan sanksi tegas atas cacat integritas pada tahap rekrutmen pegawai. Pemalsuan dokumen membatalkan keabsahan proses seleksi sejak awal sehingga status kepesertaan tidak dapat dipertahankan. Regulasi menetapkan CPNS diberhentikan bila memberi bukti tidak benar saat melamar. Oleh karena itu, opsi C adalah keputusan yang benar.</p><p>Jawaban tepat: <strong>C. Pemberhentian dengan pertimbangan pemberian keterangan tidak benar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan hukuman disiplin ringan dan tidak sebanding dengan pemalsuan bukti.</li><li><strong>B</strong>: Merupakan sanksi pembinaan etika ringan untuk pelanggaran presensi.</li><li><strong>D</strong>: Merupakan mutasi internal dan bukan tindakan hukum atas ketidakjujuran seleksi.</li><li><strong>E</strong>: Merupakan sanksi administratif tata tertib cuti kedinasan tahunan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>calon PNS dapat diberhentikan dengan berbagai pertimbangan yaitu • memberikan keterangan atau bukti yang tidak benar pada waktu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C4 - Analisis", "question": "Tingkat pelanggaran disiplin yang menjadi dasar hukum pertimbangan pemberhentian seorang Calon PNS di masa percobaan adalah...", "options": ["Pelanggaran tata tertib presensi kerja kategori sangat ringan", "Pelanggaran etika berpakaian dinas kategori ringan berulang kali", "Kelalaian pengisian laporan kinerja harian staf kategori pembinaan", "Terbukti melakukan pelanggaran disiplin tingkat sedang atau berat", "Keterlambatan penyampaian laporan tugas berkala kategori teguran"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pertimbangan Pemberhentian Disiplin CPNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Calon PNS dapat diberhentikan apabila <strong>terbukti melakukan pelanggaran disiplin tingkat sedang atau berat</strong> selama menjalani masa percobaan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan ambang batas kedisiplinan yang menentukan kelanjutan status CPNS. Status masa percobaan menuntut kepatuhan norma yang tinggi, sehingga pelanggaran sedang maupun berat berakibat terminasi status kepegawaian. Aturan menyebutkan dasar pemberhentian adalah pelanggaran disiplin sedang atau berat. Dengan demikian, opsi D adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>D. Terbukti melakukan pelanggaran disiplin tingkat sedang atau berat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan ranah pembinaan teguran atasan langsung dan tidak memberhentikan.</li><li><strong>B</strong>: Merupakan pelanggaran disiplin ringan yang ditindak dengan teguran tertulis.</li><li><strong>C</strong>: Merupakan kekurangan administratif yang diselesaikan melalui bimbingan kerja.</li><li><strong>E</strong>: Merupakan kelalaian operasional tugas yang ditindak pembinaan internal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>calon PNS dapat diberhentikan dengan berbagai pertimbangan yaitu • terbukti melakukan pelanggaran disiplin tingkat sedang atau berat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pejabat yang memiliki wewenang resmi menetapkan pengangkatan Calon PNS menjadi PNS definitif adalah...", "options": ["Kepala Kantor Pelayanan Perbendaharaan Negara setempat", "Kepala Badan Pengawasan Keuangan dan Pembangunan", "Pimpinan Unit Pelaksana Teknis Eselon Empat Pengawas", "Koordinator Kelompok Jabatan Fungsional Madya Satker", "Pejabat Pembina Kepegawaian pada instansi pemerintah"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pejabat Berwenang Mengangkat CPNS Menjadi PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Calon PNS yang telah memenuhi persyaratan diangkat menjadi PNS oleh Pejabat Pembina Kepegawaian</strong> (PPK).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan pemegang kewenangan yuridis mutlak penetapan status kepegawaian aparatur. Pejabat Pembina Kepegawaian (Menteri/Kepala Lembaga/Gubernur/Bupati/Walikota) menerbitkan surat keputusan penetapan PNS definitif. Aturan menegaskan kewenangan ini berada di tangan Pejabat Pembina Kepegawaian. Oleh karena itu, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Pejabat Pembina Kepegawaian pada instansi pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pejabat perbendaharaan yang mengesahkan surat perintah pencairan dana.</li><li><strong>B</strong>: BPKP merupakan aparat pengawasan intern pemerintah dan bukan pejabat pengangkat PNS.</li><li><strong>C</strong>: Merupakan pejabat pengawas di tingkat unit operasional terbawah.</li><li><strong>D</strong>: Merupakan tenaga ahli fungsional yang menjalankan tugas keahlian profesi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Calon PNS yang diangkat menjadi PNS harus memenuhi persyaratan: • Pejabat Pembina Kepegawaian</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Menurut Undang-Undang ASN, pengisian Jabatan Manajerial dan Nonmanajerial diutamakan berasal dari...", "options": ["Pegawai Badan Usaha Milik Negara yang memiliki kinerja unggul", "Pegawai Negeri Sipil yang memenuhi kualifikasi dan kompetensi", "Tenaga konsultan swasta dengan keahlian sertifikasi internasional", "Lulusan perguruan tinggi luar negeri melalui jalur afirmasi khusus", "Staf kehormatan organisasi kemasyarakatan mitra kementerian"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Prioritas Pengisian Jabatan ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Ketentuan UU Nomor 20 Tahun 2023 menegaskan bahwa <strong>Jabatan Manajerial dan Nonmanajerial diutamakan diisi PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan asas prioritas karier aparatur sipil dalam birokrasi pemerintahan. Pengisian struktur organisasi utama diarahkan mengoptimalkan potensi PNS yang memiliki status pegawai tetap negara. Ketentuan normatif merumuskan jabatan manajerial dan nonmanajerial diutamakan diisi PNS. Dengan demikian, opsi B adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. Pegawai Negeri Sipil yang memenuhi kualifikasi dan kompetensi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pegawai korporasi negara yang memiliki regulasi ketenagakerjaan tersendiri.</li><li><strong>C</strong>: Merupakan penyedia jasa konsultansi eksternal berdasarkan kontrak kerja komersial.</li><li><strong>D</strong>: Merupakan pelamar umum yang wajib melalui seleksi penerimaan resmi terlebih dahulu.</li><li><strong>E</strong>: Merupakan anggota lembaga nonpemerintah yang berada di luar korps birokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Jabatan ASN diisi dari Pegawai ASN (PNS/PPPK) • Nonmanajerial diutamakan diisi PNS</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi manajemen ASN, Jabatan ASN tertentu dapat diisi oleh prajurit TNI dan anggota Polri pada tingkat...", "options": ["Seluruh kantor cabang dinas perwakilan daerah kabupaten", "Unit pelaksana teknis operasional pemerintah provinsi", "Instansi Pusat sesuai ketentuan peraturan perundangan", "Lembaga penegak hukum kewilayahan tingkat kecamatan", "Kantor perwakilan distrik teritorial perbatasan dinas"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pengisian Jabatan ASN Tertentu dari TNI Polri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Jabatan ASN tertentu <strong>dapat diisi dari: prajurit Tentara Nasional Indonesia dan anggota Kepolisian Negara Republik Indonesia</strong> pada <strong>Instansi Pusat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan lokus penugasan resmi prajurit TNI dan anggota Polri dalam struktur jabatan aparatur sipil negara. Pengisian jabatan sipil oleh unsur pertahanan dan keamanan dibatasi pada kementerian/lembaga pusat yang membutuhkan sinergi keahlian strategis nasional. Ketentuan perundang-undangan menegaskan bahwa pengisian jabatan ASN oleh TNI dan Polri dilaksanakan pada Instansi Pusat. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Instansi Pusat sesuai ketentuan peraturan perundangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kantor cabang daerah kabupaten bukan lokus pengisian jabatan ASN oleh prajurit militer.</li><li><strong>B</strong>: Unit pelaksana teknis daerah provinsi diisi oleh aparatur sipil daerah sesuai formasinya.</li><li><strong>D</strong>: Lembaga penegak hukum kecamatan merupakan pos operasional kepolisian di luar struktur ASN.</li><li><strong>E</strong>: Kantor distrik perbatasan merupakan wilayah administratif yang tidak menyerap penugasan ini.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Jabatan ASN tertentu dapat • prajurit Tentara Nasional Indonesia dan anggota Kepolisian Negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Ketentuan resiprokal dalam Pasal 20 UU Nomor 20 Tahun 2023 mengatur bahwa Pegawai ASN (PNS/PPPK) dapat...", "options": ["Memilih wilayah penugasan kerja sesuai domisili tempat tinggal", "Menduduki jabatan manajerial struktural tanpa seleksi terbuka", "Memegang status keprajuritan ganda selama mengemban tugas dinas", "Menduduki jabatan di lingkungan TNI dan Polri sesuai kompetensi", "Menerima tunjangan kehormatan ganda dari dua instansi kementerian"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Resiprokal Jabatan ASN di Lingkungan TNI Polri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pegawai ASN <strong>dapat menduduki jabatan di lingkungan Tentara Nasional Indonesia dan Kepolisian Negara Republik Indonesia</strong> sesuai kompetensi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan asas mobilitas talenta dua arah antara birokrasi aparatur sipil dan institusi pertahanan keamanan negara. Pegawai ASN yang memiliki kompetensi teknis profesional tertentu dapat ditugaskan menduduki jabatan di internal institusi TNI atau Polri sesuai kebutuhan. Ketentuan Pasal 20 UU Nomor 20 Tahun 2023 merumuskan hak resiprokal ASN untuk menduduki jabatan di lingkungan TNI dan Polri. Dengan demikian, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Menduduki jabatan di lingkungan TNI dan Polri sesuai kompetensi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penempatan tugas pegawai ditentukan oleh kebutuhan dinas instansi dan bukan pilihan bebas.</li><li><strong>B</strong>: Pengisian jabatan struktural tetap wajib mematuhi standar kualifikasi dan kompetensi terbuka.</li><li><strong>C</strong>: Aparatur sipil negara tidak dapat memegang status keprajuritan militer secara aktif.</li><li><strong>E</strong>: Prinsip pengelolaan anggaran negara melarang penerimaan penghasilan ganda untuk tugas yang sama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pegawai ASN (PNS/PPPK) dapat menduduki jabatan • Nasional Indonesia (TNI) dan Kepolisian Negara Republik Indonesia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pengembangan talenta dan pola karier Pegawai Negeri Sipil wajib diselenggarakan berdasarkan...", "options": ["Senioritas masa kerja kelender dan daftar urutan kepangkatan", "Rekomendasi subjektif pimpinan unit serta pertimbangan masa dinas", "Rotasi berkala otomatis tanpa penilaian capaian target kinerja", "Mekanisme penugasan internal berdasar ketersediaan kuota formasi", "Sistem Merit melalui manajemen talenta instansi pemerintah"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Fondasi Pengembangan Talenta dan Karier</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengembangan talenta dan karier dilakukan dengan mempertimbangkan kualifikasi, kompetensi, kinerja, dan kebutuhan instansi <strong>berdasarkan Sistem Merit melalui Manajemen Talenta</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan pilar utama tata kelola karier aparatur sipil negara yang transparan, adil, dan objektif. Manajemen talenta memetakan potensi dan kinerja pegawai untuk penempatan posisi yang tepat demi mendukung sasaran strategis instansi. Ketentuan perundang-undangan menetapkan pengembangan talenta dan pola karier PNS wajib diselenggarakan berdasar Sistem Merit melalui manajemen talenta. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Sistem Merit melalui manajemen talenta instansi pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Senioritas kalender semata telah digantikan oleh penilaian prestasi kinerja dan kualifikasi.</li><li><strong>B</strong>: Pertimbangan subjektif pimpinan bertentangan dengan prinsip transparansi sistem merit.</li><li><strong>C</strong>: Rotasi jabatan wajib mempertimbangkan hasil pemetaan talenta dan kesesuaian kompetensi.</li><li><strong>D</strong>: Penugasan formal tidak boleh mengabaikan standar kompetensi individu yang dipersyaratkan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengembangan Talenta dan Karier diselenggarakan berdasarkan • Merit melalui manajemen talenta</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Biro SDM kementerian menyusun daftar usulan promosi jabatan administrator. Faktor pembanding objektif yang sah dinilai adalah...", "options": ["Prestasi kerja, kepemimpinan, kerja sama, dan kreativitas pegawai", "Riwayat penugasan kepanitiaan ad-hoc dan jumlah sertifikat dinas", "Masa kerja golongan kepangkatan tanpa melihat catatan rekam jejak", "Tingkat partisipasi dalam forum komunikasi informal antar-satker", "Evaluasi administratif kelengkapan berkas presensi harian rutin"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Prinsip Pelaksanaan Promosi PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Promosi PNS dilakukan berdasarkan perbandingan objektif</strong> antara kompetensi, kualifikasi, persyaratan jabatan, <strong>penilaian atas prestasi kerja, kepemimpinan, kerjasama, kreativitas, dan pertimbangan tim penilai kinerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan parameter baku penilaian kinerja dan kualifikasi manajerial PNS. Penilaian promosi wajib bebas dari bias primordial dan murni mengukur rekam jejak kontribusi kerja nyata. Regulasi menyatakan promosi dinilai dari prestasi kerja, kepemimpinan, kerja sama, dan kreativitas. Dengan demikian, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>A. Prestasi kerja, kepemimpinan, kerja sama, dan kreativitas pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kepanitiaan ad-hoc dan sertifikat seminar bukan parameter utama penilaian kualifikasi promosi.</li><li><strong>C</strong>: Masa kerja kepangkatan semata tidak dapat mengabaikan penilaian prestasi dan rekam jejak.</li><li><strong>D</strong>: Forum informal antar-satker bukan instrumen penilaian resmi manajemen talenta ASN.</li><li><strong>E</strong>: Presensi harian merupakan syarat disiplin dasar dan bukan faktor penentu kelayakan promosi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Promosi PNS dilakukan berdasarkan perbandingan objektif • prestasi kerja, kepemimpinan, kerjasama, kreativitas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Asas perlakuan adil dalam promosi PNS menjamin bahwa promosi dilaksanakan tanpa membedakan...", "options": ["Nilai capaian kinerja tahunan dan predikat integritas tugas", "Gender, suku, agama, ras, maupun golongan sosial pegawai", "Tingkat kesesuaian kompetensi bidang dengan formasi jabatan", "Hasil uji sertifikasi kecakapan manajerial yang terstandardisasi", "Jenjang kepangkatan ruang minimal yang disyaratkan aturan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Asas Non-Diskriminasi Promosi PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Promosi PNS wajib dilaksanakan secara adil <strong>tanpa membedakan gender, suku, agama, ras, dan golongan</strong>, di mana setiap PNS yang memenuhi syarat memiliki hak setara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan perlindungan konstitusional terhadap hak asasi pegawai dalam meniti jenjang karier birokrasi. Sistem merit menuntut kesetaraan kesempatan bagi seluruh aparatur tanpa diskriminasi latar belakang identitas. Aturan menyatakan promosi dilarang membedakan gender, suku, agama, ras, dan golongan. Oleh karena itu, opsi B adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. Gender, suku, agama, ras, maupun golongan sosial pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan indikator capaian kerja wajib yang justru harus dipertimbangkan.</li><li><strong>C</strong>: Merupakan syarat mutlak kecakapan profesi yang wajib diuji secara teliti.</li><li><strong>D</strong>: Merupakan bukti formal penguasaan standar manajerial yang harus dipenuhi.</li><li><strong>E</strong>: Merupakan syarat legalitas kepegawaian yang wajib diperiksa sebelum promosi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>membedakan gender, suku, agama, ras, dan golongan • memenuhi syarat mempunyai hak yang sama untuk dipromosikan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pengisian jabatan pimpinan tinggi utama dan madya pada kementerian atau lembaga dilakukan secara...", "options": ["Tertutup melalui mekanisme penunjukan langsung oleh menteri", "Penetapan berkala menurut daftar antrean kepangkatan pegawai", "Terbuka dan kompetitif di kalangan PNS sesuai syarat jabatan", "Rotasi horizontal internal antar-pejabat eselon tingkat daerah", "Seleksi penugasan penyesuaian otomatis pasca pelatihan dasar"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Sifat Terbuka Pengisian JPT Utama dan Madya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengisian jabatan pimpinan tinggi utama dan madya pada kementerian, kesekretariatan lembaga, LPNK, dan Instansi Daerah dilakukan <strong>secara terbuka dan kompetitif di kalangan PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan mekanisme baku seleksi terbuka untuk menjaring talenta birokrasi terbaik pada level pimpinan puncak. Seleksi kompetitif memastikan pejabat yang terpilih memenuhi kualifikasi rekam jejak, integritas moral, serta kompetensi manajerial yang teruji. Ketentuan perundang-undangan menegaskan bahwa pengisian JPT utama dan madya dilakukan secara terbuka dan kompetitif di kalangan PNS. Dengan demikian, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Terbuka dan kompetitif di kalangan PNS sesuai syarat jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penunjukan langsung tertutup tanpa seleksi terbuka menyalahi ketentuan tata kelola JPT.</li><li><strong>B</strong>: Daftar urut kepangkatan tidak lagi menjadi dasar tunggal pengisian jabatan pimpinan tinggi.</li><li><strong>D</strong>: Rotasi horizontal internal hanya dapat dilakukan setelah pejabat menduduki JPT definitif.</li><li><strong>E</strong>: Pelatihan dasar merupakan syarat kelulusan CPNS dan bukan instrumen promosi pejabat tinggi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengisian jabatan pimpinan tinggi utama dan madya • dilakukan secara terbuka dan kompetitif di kalangan PNS</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C4 - Analisis", "question": "Dalam seleksi terbuka JPT Utama dan Madya, hal yang BUKAN merupakan kriteria penilaian normatif adalah...", "options": ["Kompetensi teknis dan manajerial calon", "Kualifikasi pendidikan dan kepangkatan calon", "Rekam jejak integritas dan moralitas calon", "Besaran kontribusi finansial dana kampanye partai", "Riwayat pendidikan dan pelatihan kepemimpinan calon"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Faktor Seleksi JPT Utama dan Madya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Seleksi JPT Utama dan Madya memperhatikan <strong>syarat kompetensi, kualifikasi, kepangkatan, pendidikan dan latihan, rekam jejak jabatan, dan integritas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan transaksi politik uang yang ilegal dan bertentangan total dengan asas netralitas aparatur. Seleksi JPT murni didasarkan pada kompetensi, kualifikasi, rekam jejak, dan integritas profesional pegawai. Aturan melarang segala bentuk intervensi dan sumbangan politik kepartaian. Oleh karena itu, opsi D adalah pilihan jawaban pengecualian yang benar.</p><p>Jawaban tepat: <strong>D. Besaran kontribusi finansial dana kampanye partai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kriteria utama pengujian kapabilitas memimpin organisasi kementerian.</li><li><strong>B</strong>: Merupakan prasyarat administratif legal yang wajib dipenuhi pelamar JPT.</li><li><strong>C</strong>: Merupakan tolak ukur etika dan rekam jejak moral yang wajib ditelusuri panitia.</li><li><strong>E</strong>: Merupakan bukti pembinaan kompetensi manajerial yang tercatat di riwayat dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengisian jabatan pimpinan tinggi utama dan madya • memperhatikan syarat kompetensi, kualifikasi, kepangkatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Sebelum Pejabat Pembina Kepegawaian menetapkan keputusan promosi seorang PNS, pertimbangan wajib diperoleh dari...", "options": ["Dewan pengurus pusat partai politik pemenang pemilu legislatif", "Konsultan manajemen sumber daya manusia dari pihak swasta", "Asosiasi serikat buruh ketenagakerjaan sektor swasta nasional", "Lembaga swadaya masyarakat pemerhati kebijakan anggaran publik", "Tim penilai kinerja PNS pada instansi pemerintah bersangkutan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Peran Tim Penilai Kinerja dalam Promosi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Promosi PNS dilakukan oleh <strong>Pejabat Pembina Kepegawaian setelah mendapat pertimbangan tim penilai kinerja PNS pada Instansi Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan lembaga penasihat internal instansi yang bertugas menilai kelayakan promosi secara profesional dan terukur. Rekomendasi tim memastikan setiap pengangkatan didukung bukti capaian kinerja riil dan analisis kesesuaian kompetensi. Regulasi menetapkan PPK bertindak atas pertimbangan tim penilai kinerja PNS. Dengan demikian, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Tim penilai kinerja PNS pada instansi pemerintah bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan institusi politik di luar ranah netralitas birokrasi pemerintahan.</li><li><strong>B</strong>: Merupakan entitas swasta eksternal yang tidak memiliki legalitas kewenangan penetapan.</li><li><strong>C</strong>: Merupakan serikat pekerja sektor industri umum yang terpisah dari korps ASN.</li><li><strong>D</strong>: Merupakan lembaga masyarakat pengawas eksternal tanpa mandat penilai ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dilakukan oleh Pejabat Pembina • tim penilai kinerja PNS pada Instansi Pemerintah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Ketentuan mengenai pengisian formasi jabatan tertentu dari unsur Pegawai Pemerintah dengan Perjanjian Kerja (PPPK) adalah...", "options": ["PPPK dapat mengisi Jabatan Manajerial dan Nonmanajerial tertentu", "PPPK dipersyaratkan menduduki seluruh jabatan pimpinan kementerian", "PPPK dialokasikan bertugas pada unit kerja pelayanan khusus daerah", "PPPK berhak diangkat langsung menjadi pejabat pimpinan tinggi utama", "PPPK ditempatkan pada posisi pendukung teknis operasional lapangan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Fleksibilitas Pengisian Jabatan dari PPPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Ketentuan undang-undang mengatur bahwa <strong>Jabatan Manajerial dan Jabatan Nonmanajerial tertentu dapat diisi dari PPPK</strong> sesuai kebutuhan organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan wujud fleksibilitas pengadaan tenaga ahli profesional dalam birokrasi. Pemerintah membuka peluang bagi PPPK menduduki posisi fungsional maupun manajerial tertentu guna mempercepat pencapaian target strategis. Aturan menyatakan jabatan manajerial dan nonmanajerial tertentu dapat diisi PPPK. Oleh karena itu, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. PPPK dapat mengisi Jabatan Manajerial dan Nonmanajerial tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Jabatan pimpinan kementerian diutamakan bagi PNS dan diisi melalui seleksi ketat.</li><li><strong>C</strong>: PPPK dapat bertugas di instansi pusat maupun daerah sesuai formasi kebutuhan yang ditetapkan.</li><li><strong>D</strong>: Pengangkatan JPT Utama mensyaratkan seleksi terbuka dan persetujuan Presiden.</li><li><strong>E</strong>: PPPK mengisi jabatan fungsional dan pimpinan tertentu, bukan terbatas pada tenaga pendukung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Jabatan Manajerial dan Jabatan • dapat diisi dari PPPK</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Secara normatif, lingkup keterbukaan pengisian jabatan pimpinan tinggi utama dan madya dilakukan pada skala...", "options": ["Internal terbatas pada satu unit eselon tiga satker", "Wilayah kelurahan tempat kantor kementerian berada", "Tingkat nasional di seluruh wilayah Republik Indonesia", "Kawasan perwakilan distrik kepulauan terluar negara", "Tingkat kabupaten lokasi penerimaan formasi dibuka"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Skala Pengisian JPT Utama dan Madya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Pengisian jabatan pimpinan tinggi utama dan madya sebagaimana dimaksud dilakukan pada tingkat nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan skala jangkauan seleksi pucuk kepemimpinan aparatur sipil negara. Level manajerial tertinggi menuntut penyaringan kader terbaik bangsa tanpa dibatasi sekat kedaerahan atau instansi asal. Regulasi menetapkan seleksi JPT utama dan madya diadakan pada tingkat nasional. Oleh karena itu, opsi C adalah ketetapan yang benar.</p><p>Jawaban tepat: <strong>C. Tingkat nasional di seluruh wilayah Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan lingkup pengisian jabatan pengawas lini unit terbawah.</li><li><strong>B</strong>: Merupakan lingkup kewilayahan pemukiman di luar jangkauan seleksi JPT.</li><li><strong>D</strong>: Merupakan zona afirmasi penempatan geografis khusus aparatur.</li><li><strong>E</strong>: Merupakan lingkup seleksi jabatan pelaksana daerah kabupaten/kota.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengisian jabatan pimpinan tinggi utama dan madya sebagaimana dimaksud • dilakukan pada tingkat nasional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Mekanisme pengisian jabatan pimpinan tinggi pratama dilaksanakan secara terbuka dan kompetitif pada lingkup...", "options": ["Lingkungan internal satu subbagian satuan kerja operasional", "Internal kelompok jabatan fungsional pelaksana kantor", "Lingkungan unit kerja penunjang administratif sekretariat daerah", "Tingkat nasional atau antar-kabupaten/kota dalam satu provinsi", "Internal kelompok jabatan fungsional pelaksana teknis wilayah"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Lingkup Seleksi JPT Pratama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Pengisian jabatan pimpinan tinggi pratama dilakukan secara terbuka dan kompetitif pada tingkat nasional atau antar-kabupaten/kota dalam 1 (satu) provinsi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan fleksibilitas jangkauan seleksi bagi eselon II/pratama yang diatur regulasi. Instansi dapat menjaring talenta dari seluruh tanah air atau memfokuskan mobilitas antardaerah dalam satu provinsi. Ketentuan hukum menegaskan seleksi pratama dapat berskala nasional atau antardaerah satu provinsi. Dengan demikian, opsi D adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Tingkat nasional atau antar-kabupaten/kota dalam satu provinsi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tingkat subbagian terlalu sempit untuk lingkup seleksi terbuka JPT Pratama.</li><li><strong>B</strong>: Merupakan lingkup promosi internal kelompok fungsional teknis tingkat pertama.</li><li><strong>C</strong>: Seleksi JPT Pratama terbuka luas mencakup skala nasional atau antardaerah dalam satu provinsi.</li><li><strong>E</strong>: Kelompok fungsional teknis merupakan jenjang profesi dan bukan batasan yurisdiksi JPT.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pengisian jabatan pimpinan tinggi pratama • kompetitif pada tingkat nasional atau antar-kabupaten/kota dalam 1 (satu)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Syarat yuridis mutlak agar jabatan pimpinan tinggi utama dan madya tertentu dapat diisi dari kalangan non-PNS adalah...", "options": ["Mendapat rekomendasi dari pimpinan serikat pekerja swasta", "Telah mempublikasikan buku ilmiah tentang sistem birokrasi", "Pernah menjabat sebagai direktur pada korporasi multinasional", "Memiliki izin tertulis dari pejabat pembina kepegawaian daerah", "Mendapat persetujuan Presiden dan ditetapkan dalam Keppres"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pengisian JPT Tertentu dari Kalangan Non-PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Jabatan pimpinan tinggi utama dan madya tertentu dapat berasal dari kalangan non-PNS dengan persetujuan Presiden</strong> yang pengisiannya dilakukan secara terbuka dan kompetitif serta ditetapkan dalam <strong>Keputusan Presiden</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan wewenang tertinggi kepala pemerintahan dalam menetapkan pejabat negara profesional. Pengangkatan tenaga ahli non-PNS pada pucuk pimpinan kementerian memerlukan persetujuan kepala negara guna menjamin integritas visi nasional. Regulasi mensyaratkan persetujuan Presiden dan penetapan melalui Keppres. Oleh karena itu, opsi E adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>E. Mendapat persetujuan Presiden dan ditetapkan dalam Keppres</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan organisasi buruh umum yang tidak memiliki kewenangan penetapan ASN.</li><li><strong>B</strong>: Merupakan karya akademik pribadi yang bukan syarat yuridis pengangkatan.</li><li><strong>C</strong>: Merupakan rekam jejak korporasi swasta yang tidak otomatis mengesahkan status.</li><li><strong>D</strong>: Merupakan pejabat daerah yang tidak berwenang mengesahkan JPT utama pusat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Jabatan pimpinan tinggi utama dan madya tertentu • kalangan non-PNS dengan persetujuan Presiden</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang perwira militer ingin mengikuti seleksi terbuka JPT Madya pada kementerian sipil umum. Syarat wajib yang harus dipenuhi adalah...", "options": ["Mengundurkan diri terlebih dahulu dari dinas aktif militer", "Mengajukan penugasan rangkap dengan tetap menerima gaji ganda", "Meminta surat rekomendasi penugasan dari gubernur setempat", "Menjalani cuti tahunan selama masa pendaftaran lelang jabatan", "Menyerahkan perlengkapan operasional kepada kejaksaan negeri"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Syarat TNI Polri Masuk JPT Umum</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Jabatan pimpinan tinggi dapat diisi oleh prajurit TNI dan anggota Polri <strong>setelah mengundurkan diri dari dinas aktif</strong> apabila dibutuhkan dan sesuai dengan kompetensi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan prinsip supremasi sipil dan pemisahan fungsi militer dengan tata kelola birokrasi sipil umum. Anggota TNI/Polri yang ingin berkompetisi dalam jabatan struktural sipil reguler wajib melepas status dinas aktif pertahanannya. Regulasi menegaskan keharusan mengundurkan diri dari dinas aktif terlebih dahulu. Dengan demikian, opsi A adalah keputusan yang tepat.</p><p>Jawaban tepat: <strong>A. Mengundurkan diri terlebih dahulu dari dinas aktif militer</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan larangan rangkap status dan larangan penggajian ganda uang negara.</li><li><strong>C</strong>: Merupakan pejabat daerah yang tidak membawahi komando personel militer.</li><li><strong>D</strong>: Merupakan hak cuti biasa yang tidak menghapus ikatan dinas aktif keprajuritan.</li><li><strong>E</strong>: Merupakan aparat kejaksaan yang berwenang dalam penuntutan perkara pidana.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>diisi oleh prajurit TNI dan anggota • setelah mengundurkan diri dari</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Panitia seleksi yang dibentuk oleh Pejabat Pembina Kepegawaian untuk pengisian JPT wajib beranggotakan...", "options": ["Seluruh pejabat eselon empat unit administrasi dan kepegawaian", "Kombinasi berimbang unsur internal dan eksternal instansi pemerintah", "Akademisi serta pakar independen bidang manajemen kepegawaian", "Pejabat struktural dari instansi pembina kepegawaian pusat", "Praktisi profesional bidang sumber daya manusia aparatur"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Komposisi Panitia Seleksi JPT</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengisian jabatan pimpinan tinggi dilakukan oleh PPK dengan terlebih dahulu membentuk <strong>panitia seleksi yang terdiri dari unsur internal maupun eksternal Instansi Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan kaidah objektivitas dan independensi penilaian seleksi jabatan birokrasi. Kehadiran pakar eksternal (akademisi/praktisi) mencegah potensi konflik kepentingan di lingkungan internal birokrasi. Aturan menyebutkan pansel terdiri dari unsur internal dan eksternal instansi. Oleh karena itu, opsi B adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>B. Kombinasi berimbang unsur internal dan eksternal instansi pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pansel JPT mensyaratkan kombinasi unsur internal dan eksternal, bukan seluruhnya eselon empat.</li><li><strong>C</strong>: Meskipun akademisi dapat dilibatkan, rumusan resmi mewajibkan keterpaduan unsur internal dan eksternal.</li><li><strong>D</strong>: Unsur eksternal tidak dibatasi hanya dari instansi pembina pusat semata.</li><li><strong>E</strong>: Praktisi profesional dapat menjadi bagian eksternal namun rumusan pokok adalah unsur internal-eksternal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>terlebih dahulu membentuk panitia seleksi Instansi • unsur internal maupun eksternal Instansi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan PermenPAN-RB Nomor 15 Tahun 2019, keanggotaan panitia seleksi instansi pemerintah terdiri atas unsur...", "options": ["Pejabat eselon satu kementerian koordinator saja", "Aparatur pengawas internal pemerintah daerah", "Internal maupun eksternal instansi bersangkutan", "Asosiasi profesi advokat dan konsultan hukum", "Staf khusus menteri serta tenaga ahli fraksi"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Koordinasi Pembentukan Pansel JPT</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pembentukan panitia seleksi instansi pemerintah untuk pengisian JPT terdiri dari <strong>unsur internal maupun eksternal Instansi Pemerintah yang bersangkutan</strong> sesuai PermenPAN-RB Nomor 15 Tahun 2019.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Regulasi pengisian JPT secara terbuka mengharuskan panitia seleksi memadukan perspektif internal instansi dan objektivitas ahli independen eksternal. Komposisi seimbang ini menjamin asesmen kompetensi kandidat berjalan secara profesional dan transparan.</p><p>Jawaban tepat: <strong>C. Internal maupun eksternal instansi bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pansel tidak boleh hanya diisi pejabat satu kementerian koordinator.</li><li><strong>B</strong>: Aparatur pengawas internal daerah bukan komposisi tunggal panitia.</li><li><strong>D</strong>: Asosiasi advokat bukan komponen wajib dalam seleksi terbuka JPT.</li><li><strong>E</strong>: Tenaga ahli fraksi partai politik dilarang menjadi anggota pansel.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>panitia seleksi Instansi • unsur internal maupun eksternal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Pengisian jabatan pimpinan tinggi utama dan madya tertentu dari kalangan non-PNS wajib ditetapkan melalui...", "options": ["Surat Keputusan Bersama Kepala Lembaga", "Peraturan Menteri Pendayagunaan Aparatur", "Instruksi Khusus Pejabat Pembina Kepegawaian", "Keputusan Presiden Republik Indonesia", "Piagam Kerja Sama Lembaga Non Struktural"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pengecualian Seleksi Terbuka bagi Instansi Merit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengisian JPT utama dan madya dari kalangan non-PNS dilaksanakan atas <strong>persetujuan Presiden</strong> dan secara resmi <strong>ditetapkan dalam Keputusan Presiden</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Pengangkatan profesional non-PNS pada posisi pimpinan tinggi utama dan madya merupakan hak prerogatif yang memerlukan persetujuan kepala negara. Penetapan akhir dituangkan secara sah dalam instrumen Keputusan Presiden setelah seleksi terbuka.</p><p>Jawaban tepat: <strong>D. Keputusan Presiden Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: SKB Kepala Lembaga tidak berwenang mengangkat pejabat pimpinan tinggi.</li><li><strong>B</strong>: Peraturan Menteri merupakan pedoman umum bukan instrumen pengangkatan.</li><li><strong>C</strong>: PPK instansi tidak dapat menetapkan pengangkatan non-PNS secara sepihak.</li><li><strong>E</strong>: Piagam kerja sama tidak memiliki kekuatan hukum mengangkat pejabat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kalangan non-PNS dengan persetujuan Presiden yang pengisiannya • ditetapkan dalam Keputusan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Prajurit TNI dan anggota Polri dapat mengisi jabatan pimpinan tinggi umum setelah memenuhi persyaratan mutlak berupa...", "options": ["Mendapatkan rekomendasi tertulis dari perwira tinggi", "Menyelesaikan masa penugasan operasi perbatasan", "Memperoleh tanda kehormatan tingkat bintang negara", "Menyerahkan seluruh tanda kepangkatan dinas militer", "Mengundurkan diri secara resmi dari dinas aktif"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kewajiban Pelaporan Berkala Sistem Merit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Prajurit TNI dan anggota Polri dapat mengisi jabatan pimpinan tinggi <strong>setelah mengundurkan diri dari dinas aktif</strong> apabila dibutuhkan dan sesuai kompetensi yang ditetapkan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Prinsip supremasi sipil dan netralitas birokrasi mengharuskan personel militer atau kepolisian beralih status menjadi purnawirawan/sipil sebelum menduduki JPT umum. Pengunduran diri dari dinas aktif menjadi syarat mutlak keikutsertaan seleksi.</p><p>Jawaban tepat: <strong>E. Mengundurkan diri secara resmi dari dinas aktif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rekomendasi tertulis atasan militer bukan syarat utama pengisian JPT sipil.</li><li><strong>B</strong>: Penugasan perbatasan tidak berkaitan dengan syarat administratif JPT.</li><li><strong>C</strong>: Tanda kehormatan militer bukan prasyarat menduduki jabatan birokrasi.</li><li><strong>D</strong>: Penyerahan tanda pangkat adalah konsekuensi simbolis bukan syarat formal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>prajurit TNI dan anggota • mengundurkan diri dari</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Regulasi menteri yang mengatur pedoman teknis pengisian jabatan pimpinan tinggi secara terbuka dan kompetitif adalah...", "options": ["Peraturan Menteri PAN-RB Nomor 15 Tahun 2019", "Peraturan Menteri Keuangan Nomor 10 Tahun 2015", "Peraturan Menteri Dalam Negeri Nomor 5 Tahun 2020", "Peraturan Badan Pengawas Pemilu Nomor 8 Tahun 2021", "Peraturan Jaksa Agung Muda Bidang Intelijen 2018"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Regulasi Teknis Pengisian JPT Terbuka</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Tata cara pengisian JPT terbuka dijelaskan pada <strong>Peraturan Menteri Pendayagunaan Aparatur Negara-Reformasi Birokrasi Nomor 15 Tahun 2019 tentang Pengisian Jabatan Pimpinan Tinggi Secara Terbuka dan Kompetitif di Lingkungan Instansi Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan dasar hukum teknis pelaksanaan lelang jabatan pimpinan tinggi aparatur sipil negara. PermenPAN-RB ini memuat standar pembentukan pansel, tahapan uji kompetensi, hingga penetapan calon pejabat. Regulasi yang tepat adalah PermenPAN-RB No 15 Tahun 2019. Oleh karena itu, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>A. Peraturan Menteri PAN-RB Nomor 15 Tahun 2019</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan peraturan bendahara umum negara mengenai tata kelola anggaran.</li><li><strong>C</strong>: Merupakan regulasi kementerian dalam negeri mengenai administrasi wilayah.</li><li><strong>D</strong>: Merupakan aturan tata laksana pengawasan kontestasi pemilu lembaga pemilu.</li><li><strong>E</strong>: Merupakan regulasi teknis internal korps adhyaksa dalam fungsi intelijen.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menteri Pendayagunaan Aparatur Negara-Reformasi Birokrasi Nomor 15 • Pengisian Jabatan Pimpinan Tinggi Secara Terbuka dan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Ketentuan penugasan prajurit TNI dan anggota Polri pada jabatan pimpinan tinggi di instansi pemerintah mengatur bahwa...", "options": ["Penugasan dilakukan pada seluruh jabatan struktural kementerian", "Dapat diisi oleh prajurit TNI dan Polri sesuai kompetensi dan aturan", "Seluruh pejabat sipil definitif wajib dialihkan ke jabatan pelaksana", "Penempatan personel militer menggantikan tata kelola birokrasi sipil", "Seleksi pimpinan tinggi dihapuskan bagi instansi pemerintah pusat"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penugasan TNI Polri di Instansi Tertentu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>JPT tertentu di lingkungan instansi pemerintah <strong>dapat diisi oleh prajurit Tentara Nasional Indonesia dan anggota Kepolisian Negara Republik Indonesia</strong> sesuai perundang-undangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan kebijakan sinergi kelembagaan bagi instansi yang memiliki tugas pokok terkait bidang pertahanan, keamanan, narkotika, atau intelijen. Penempatan personel tetap didasarkan pada kesesuaian kualifikasi kompetensi jabatan dan koridor regulasi yang berlaku. Ketentuan hukum menegaskan bahwa JPT tertentu dapat diisi prajurit TNI dan Polri sesuai kompetensi dan peraturan perundang-undangan. Dengan demikian, opsi B merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. Dapat diisi oleh prajurit TNI dan Polri sesuai kompetensi dan aturan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengisian jabatan oleh TNI dan Polri dibatasi pada instansi tertentu dan bukan seluruh kementerian.</li><li><strong>C</strong>: Pejabat sipil kementerian tetap memegang hak karier sesuai formasi meritokrasi instansi.</li><li><strong>D</strong>: Tata kelola birokrasi pemerintahan tetap tunduk pada hukum administrasi negara dan manajemen ASN.</li><li><strong>E</strong>: Seleksi terbuka tetap menjadi norma dasar pengisian jabatan pimpinan tinggi di birokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>jabatan pimpinan tinggi di lingkungan Instansi Pemerintah • dapat diisi oleh prajurit TNI dan anggota Polri</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam seleksi JPT Utama dan Madya di Instansi Pusat, jumlah nama calon pejabat terpilih yang diusulkan PPK kepada Presiden adalah...", "options": ["Satu nama calon tunggal terbaik", "Dua nama calon peraih nilai teratas", "Lima nama calon peserta nilai tertinggi", "Empat nama calon rekomendasi eselon satu", "Tiga nama calon hasil seleksi pansel"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Jumlah Calon JPT Utama Madya ke Presiden</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Panitia seleksi memilih <strong>3 (tiga) nama calon</strong> untuk setiap 1 lowongan, yang disampaikan kepada PPK untuk selanjutnya <strong>diusulkan 3 (tiga) nama calon kepada Presiden</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan kuota baku nama calon yang wajib diajukan ke kepala negara (*three-name list*). Hal ini memberikan diskresi legal bagi Presiden selaku pembina tertinggi ASN untuk memilih satu figur terbaik dari tiga kandidat terbaik. Aturan menegaskan jumlah yang diusulkan adalah 3 nama calon. Oleh karena itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Tiga nama calon hasil seleksi pansel</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan calon tunggal yang menghilangkan ruang hak prerogatif pemilihan Presiden.</li><li><strong>B</strong>: Merupakan usulan dua nama yang tidak memenuhi standar kuota baku regulasi.</li><li><strong>C</strong>: Merupakan kuota tahap awal uji wawancara dan bukan naskah usulan ke Presiden.</li><li><strong>D</strong>: Merupakan jumlah usulan berlebih yang tidak sesuai PermenPAN-RB No 15/2019.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>seleksi Instansi Pemerintah memilih 3 (tiga) nama calon • Kepegawaian mengusulkan 3 (tiga) nama calon sebagaimana dimaksud</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pihak yang memiliki kewenangan mutlak memilih satu nama dan menetapkan pejabat pimpinan tinggi utama dan madya di pusat adalah...", "options": ["Ketua Komisi Pemilihan Umum Republik Indonesia", "Kepala Badan Kepegawaian Daerah tingkat provinsi", "Presiden Republik Indonesia selaku pemegang wewenang", "Pimpinan organisasi masyarakat mitra kementerian", "Koordinator sekretariat panitia seleksi internal"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Wewenang Penetapan Pejabat JPT Utama Madya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Presiden memilih 1 (satu) nama dari 3 (tiga) nama calon yang disampaikan untuk ditetapkan sebagai pejabat pimpinan tinggi utama dan/atau madya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan pemegang kekuasaan tertinggi dalam penetapan posisi pimpinan birokrasi strategis negara. Kepala negara menelaah ketiga kandidat dan menetapkan satu pejabat terpilih melalui Keputusan Presiden. Aturan menyatakan Presiden yang memilih 1 dari 3 nama calon. Dengan demikian, opsi C adalah ketetapan yang tepat.</p><p>Jawaban tepat: <strong>C. Presiden Republik Indonesia selaku pemegang wewenang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penyelenggara pemilu kontestasi politik dan bukan pejabat kepegawaian.</li><li><strong>B</strong>: Merupakan pejabat teknis daerah yang tidak berwenang atas JPT utama pusat.</li><li><strong>D</strong>: Merupakan entitas swasta nonpemerintah di luar struktur tata negara.</li><li><strong>E</strong>: Merupakan pelaksana administrasi teknis pansel tanpa kewenangan memutus.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Presiden memilih 1 (satu) nama dari 3 (tiga) nama calon yang disampaikan • ditetapkan sebagai pejabat pimpinan tinggi utama dan/atau madya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Panitia seleksi JPT Pratama di kementerian telah menghasilkan tiga nama terbaik. Alur penyampaian naskah calon kepada PPK dilakukan melalui...", "options": ["Pengumuman terbuka di media sosial kementerian", "Rapat dengar pendapat bersama komisi parlemen", "Sidang terbuka majelis kehormatan disiplin pegawai", "Pejabat yang Berwenang pada instansi bersangkutan", "Laporan pengaduan masyarakat secara daring"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Mekanisme Pengisian JPT Pratama Pusat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pada pengisian JPT Pratama, panitia seleksi memilih 3 nama calon yang disampaikan <strong>kepada Pejabat Pembina Kepegawaian melalui Pejabat yang Berwenang (PyB)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jalur hierarki birokrasi resmi sebelum naskah masuk ke meja menteri/kepala lembaga selaku PPK. Pejabat yang Berwenang (PyB, seperti Sekretaris Jenderal) memberikan telaah pertimbangan manajerial atas ketiga calon tersebut. Regulasi menegaskan penyampaian dilakukan melalui Pejabat yang Berwenang. Oleh karena itu, opsi D adalah alur yang benar.</p><p>Jawaban tepat: <strong>D. Pejabat yang Berwenang pada instansi bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan sarana publikasi hasil dan bukan jalur legal penyampaian berkas.</li><li><strong>B</strong>: Merupakan forum pengawasan legislatif yang tidak mencampuri seleksi eselon II.</li><li><strong>C</strong>: Merupakan forum penegakan sanksi etik dan bukan prosedur seleksi jabatan.</li><li><strong>E</strong>: Merupakan kanal pengaduan publik yang terpisah dari tahapan pengusulan nama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pengisian jabatan pimpinan tinggi pratama dilakukan oleh Pejabat • melalui Pejabat yang Berwenang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pihak yang berwenang memilih satu dari tiga nama calon serta menetapkannya sebagai pejabat pimpinan tinggi pratama adalah...", "options": ["Auditor madya pada badan pemeriksa keuangan", "Direktur utama perusahaan penyedia tenaga kerja", "Hakim ketua pada pengadilan tata usaha negara", "Ketua dewan perwakilan rakyat tingkat daerah", "Pejabat Pembina Kepegawaian instansi pemerintah"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penetapan Pejabat JPT Pratama Pusat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Pejabat Pembina Kepegawaian lalu memilih 1 (satu) dari 3 (tiga) nama calon yang diusulkan dengan memperhatikan pertimbangan Pejabat yang Berwenang untuk ditetapkan sebagai pejabat pimpinan tinggi pratama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan pemegang otoritas penetapan JPT Pratama (eselon II). Berbeda dengan JPT Utama/Madya yang ditetapkan Presiden, JPT Pratama ditetapkan langsung oleh menteri/kepala lembaga selaku PPK instansi. Regulasi menyebutkan PPK yang memilih dan menetapkan pejabat JPT pratama. Dengan demikian, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Pejabat Pembina Kepegawaian instansi pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pejabat fungsional pemeriksa keuangan eksternal negara.</li><li><strong>B</strong>: Merupakan pimpinan korporasi swasta penyalur tenaga *outsourcing*.</li><li><strong>C</strong>: Merupakan aparatur peradilan yang mengadili perkara sengketa hukum.</li><li><strong>D</strong>: Merupakan pimpinan lembaga perwakilan rakyat tingkat daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pejabat Pembina Kepegawaian lalu memilih 1 (satu) dari 3 (tiga) nama calon • ditetapkan sebagai pejabat pimpinan tinggi pratama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Gubernur telah mengantongi tiga nama calon hasil seleksi JPT Madya provinsi (Sekda). Prosedur pengusulan kepada Presiden dilakukan melalui...", "options": ["Menteri Dalam Negeri Republik Indonesia", "Menteri Keuangan Republik Indonesia", "Kepala Kepolisian Daerah setempat", "Komandan Komando Resor Militer wilayah", "Ketua Mahkamah Konstitusi Indonesia"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Alur Pengusulan Sekda Provinsi / JPT Madya Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pada JPT madya tingkat provinsi, 3 nama calon diserahkan kepada PPK untuk selanjutnya <strong>diusulkan kepada Presiden melalui Menteri Dalam Negeri (Mendagri)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan pintu koordinasi hierarki pemerintahan daerah ke tingkat pusat. Menteri Dalam Negeri memverifikasi kepatuhan administratif dan kesesuaian regulasi sebelum meneruskan naskah usulan kepada kepala negara. Aturan menegaskan pengusulan ke Presiden wajib melalui Menteri Dalam Negeri. Oleh karena itu, opsi A adalah alur yang benar.</p><p>Jawaban tepat: <strong>A. Menteri Dalam Negeri Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan menteri pengelola keuangan negara dan fiskal makro.</li><li><strong>C</strong>: Merupakan pimpinan institusi kepolisian kewilayahan di daerah.</li><li><strong>D</strong>: Merupakan komandan satuan teritorial militer angkatan darat di daerah.</li><li><strong>E</strong>: Merupakan lembaga yudikatif penguji konstitusionalitas undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pengisian jabatan pimpinan tinggi madya di tingkat provinsi • usulkan kepada Presiden melalui</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pihak yang menetapkan keputusan final pengangkatan Sekretaris Daerah Provinsi (JPT Madya) adalah...", "options": ["Ketua dewan perwakilan rakyat daerah provinsi", "Presiden Republik Indonesia melalui Keputusan Presiden", "Rektor perguruan tinggi negeri terkemuka di daerah", "Ketua majelis permusyawaratan ulama kabupaten", "Kepala kantor perwakilan kementerian hukum dan ham"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penetapan Akhir JPT Madya Provinsi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Untuk JPT Madya tingkat provinsi, <strong>Presiden akan memilih 1 (satu) nama dari 3 (tiga) nama calon yang disampaikan untuk ditetapkan sebagai pejabat pimpinan tinggi madya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan pemegang hak legal penetapan sekretaris daerah provinsi sebagai eselon I daerah. Karena sekda provinsi memegang peranan strategis koordinasi nasional-daerah, penetapan hukumnya berada langsung di tangan Presiden. Regulasi menegaskan penetapan dilakukan oleh Presiden. Dengan demikian, opsi B adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. Presiden Republik Indonesia melalui Keputusan Presiden</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan lembaga legislatif daerah mitra eksekutif pemerintah provinsi.</li><li><strong>C</strong>: Merupakan pimpinan institusi pendidikan tinggi akademik.</li><li><strong>D</strong>: Merupakan tokoh keagamaan daerah yang tidak memiliki wewenang tata negara.</li><li><strong>E</strong>: Merupakan instansi vertikal kementerian yang tidak menetapkan sekda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>memilih 1 (satu) nama dari 3 (tiga) nama calon yang disampaikan • ditetapkan sebagai pejabat pimpinan tinggi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam seleksi terbuka JPT Pratama di lingkungan pemerintah daerah, pihak yang berwenang menetapkan dan melantik pejabat terpilih adalah...", "options": ["Pejabat Pembina Kepegawaian pemerintah daerah bersangkutan", "Sekretaris Daerah selaku pejabat yang berwenang instansi", "Ketua Komisi Pengawas Persaingan Usaha tingkat provinsi", "Pimpinan panitia seleksi terbuka instansi pemerintah daerah", "Inspektur daerah yang memimpin pengawasan internal wilayah"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penetapan JPT Pratama di Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pejabat Pembina Kepegawaian (PPK) daerah <strong>memilih 1 (satu) dari 3 (tiga) nama calon yang diusulkan oleh Panitia Seleksi</strong> untuk ditetapkan dan dilantik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan otoritas kepegawaian kepala daerah selaku Pejabat Pembina Kepegawaian (PPK) terhadap pejabat eselon II di wilayahnya. Setelah menerima hasil kerja panitia seleksi, kepala daerah memiliki wewenang menetapkan dan melantik satu kandidat terpilih. Ketentuan perundang-undangan menegaskan penetapan dan pelantikan JPT Pratama dilakukan oleh PPK daerah bersangkutan. Oleh karena itu, opsi A merupakan jawaban yang benar.</p><p>Jawaban tepat: <strong>A. Pejabat Pembina Kepegawaian pemerintah daerah bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sekretaris Daerah bertindak sebagai PyB yang mengoordinasikan usulan namun tidak melantik pejabat.</li><li><strong>C</strong>: Komisi persaingan usaha bertugas mengawasi praktik bisnis komersial di luar aparatur negara.</li><li><strong>D</strong>: Panitia seleksi bertugas menyaring tiga calon terbaik dan tidak memiliki wewenang melantik.</li><li><strong>E</strong>: Inspektur daerah menjalankan audit internal dan bukan pejabat pengangkat jabatan struktural.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pengisian jabatan pimpinan tinggi pratama dilakukan oleh Pejabat • memilih 1 (satu) dari 3 (tiga) nama calon</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi manajemen kepegawaian, definisi dari Pejabat yang Berwenang (PyB) adalah pejabat yang memiliki kewenangan...", "options": ["Menyelenggarakan penahanan fisik dan penyitaan aset aparatur", "Mengubah susunan struktur pasal dalam undang-undang dasar", "Mengesahkan pinjaman utang luar negeri atas nama negara", "Membubarkan kepengurusan partai politik peserta pemilu", "Menetapkan pengangkatan, pemindahan, dan pemberhentian pegawai ASN"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Definisi Pejabat yang Berwenang (PyB)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Pejabat yang Berwenang (PyB)</strong> didefinisikan sebagai <strong>pejabat yang memiliki kewenangan menetapkan pengangkatan, pemindahan, dan pemberhentian pegawai ASN</strong> sesuai pendelegasian wewenang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan batasan yuridis tugas dan fungsi PyB dalam manajemen ASN. PyB bertindak membantu PPK dalam tata kelola administrasi mutasi, promosi, dan disiplin pegawai. Regulasi mendefinisikan PyB sebagai pejabat pengatur pengangkatan, pemindahan, dan pemberhentian ASN. Dengan demikian, opsi E adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Menetapkan pengangkatan, pemindahan, dan pemberhentian pegawai ASN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tindakan pro-justitia penegak hukum peradilan pidana.</li><li><strong>B</strong>: Merupakan kewenangan tertinggi Majelis Permusyawaratan Rakyat.</li><li><strong>C</strong>: Merupakan kewenangan menteri keuangan bersama persetujuan parlemen.</li><li><strong>D</strong>: Merupakan wewenang eksklusif lembaga yudikatif Mahkamah Konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kewenangan menetapkan pengangkatan, pemindahan, dan pemberhentian • Pejabat yang Berwenang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Dalam menetapkan pejabat JPT Pratama dari tiga kandidat terbaik, Pejabat Pembina Kepegawaian wajib memperhatikan...", "options": ["Pertimbangan tertulis dari Pejabat yang Berwenang instansi", "Ramalan keberuntungan dari pakar spiritual daerah", "Hasil jajak pendapat terbuka akun media sosial publik", "Tingkat kedekatan hubungan keluarga calon dengan menteri", "Besaran janji gratifikasi yang ditawarkan oleh calon terpilih"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Fungsi Pertimbangan PyB dalam Seleksi Pratama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pejabat Pembina Kepegawaian memilih 1 dari 3 nama calon dengan <strong>memperhatikan pertimbangan Pejabat yang Berwenang (PyB)</strong> untuk ditetapkan sebagai pejabat pimpinan tinggi pratama.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan rambu tata kelola yang menjamin telaah manajerial teknis tidak terabaikan oleh keputusan politis menteri/kepala daerah. Masukan dari PyB memastikan figur yang dipilih memiliki kecocokan (*fit*) operasional dengan ritme kerja instansi. Regulasi menetapkan PPK wajib memperhatikan pertimbangan PyB. Oleh karena itu, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>A. Pertimbangan tertulis dari Pejabat yang Berwenang instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan hal takhayul yang bertentangan dengan rasionalitas tata kelola publik.</li><li><strong>C</strong>: Merupakan jajak pendapat informal yang tidak berdasar pada instrumen merit.</li><li><strong>D</strong>: Merupakan pertimbangan nepotisme yang dilarang keras dalam hukum birokrasi.</li><li><strong>E</strong>: Merupakan tindak pidana korupsi penyuapan yang diancam pidana penjara berat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>panitia seleksi memilih 3 (tiga) nama untuk setiap 1 • memperhatikan pertimbangan Pejabat yang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Langkah pertama yang wajib dilakukan oleh Pejabat Pembina Kepegawaian dalam rangka pengisian JPT Pratama adalah...", "options": ["Mengusulkan formasi mutasi langsung kepada menteri keuangan", "Terlebih dahulu membentuk panitia seleksi instansi pemerintah", "Menetapkan nama pejabat pengganti sementara selama masa lelang", "Melakukan penataan alokasi pagu belanja tunjangan kinerja satker", "Mengoordinasikan jadwal uji kompetensi dengan dewan legislatif"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tahapan Awal Pansel Pengisian JPT</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengisian JPT Pratama dilakukan oleh PPK <strong>dengan terlebih dahulu membentuk panitia seleksi</strong> instansi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan prasyarat formal tata kelola pengisian jabatan pimpinan tinggi melalui seleksi terbuka. Pembentukan panitia seleksi yang independen dan kompeten menjadi langkah awal untuk menjamin transparansi penyaringan kandidat. Ketentuan perundang-undangan menetapkan bahwa pengisian JPT dilakukan dengan terlebih dahulu membentuk panitia seleksi. Dengan demikian, opsi B merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. Terlebih dahulu membentuk panitia seleksi instansi pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Usulan mutasi JPT Pratama daerah bukan merupakan wewenang Menteri Keuangan.</li><li><strong>C</strong>: Penunjukan pelaksana tugas bersifat administratif penunjang dan bukan langkah formal seleksi.</li><li><strong>D</strong>: Alokasi tunjangan kinerja diatur dalam peraturan tersendiri dan bukan tahapan seleksi jabatan.</li><li><strong>E</strong>: Dewan legislatif tidak memiliki fungsi teknis dalam penyelenggaraan seleksi aparatur birokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pengisian jabatan pimpinan tinggi utama dan/atau madya • PermenPAN-RB Nomor 15 Tahun 2019</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Sebelum bupati atau walikota menetapkan pejabat Sekretaris Daerah Kabupaten/Kota terpilih, langkah wajib yang harus ditempuh adalah...", "options": ["Melakukan koordinasi terlebih dahulu dengan Gubernur setempat", "Meminta pengesahan naskah pelantikan kepada komisi yudisial", "Mengumumkan nama terpilih pada surat kabar internasional", "Menunggu persetujuan tertulis dari pimpinan serikat pengusaha", "Menyerahkan mandat kekuasaan daerah kepada komando distrik militer"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Koordinasi Penetapan Sekda Kab/Kota</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Khusus untuk pejabat pimpinan tinggi pratama yang memimpin sekretariat daerah kabupaten/kota, <strong>sebelum ditetapkan oleh bupati/walikota dikoordinasikan dengan gubernur</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan wujud kedudukan Gubernur sebagai wakil pemerintah pusat di wilayah daerah (*dekonsentrasi*). Koordinasi menjamin figur sekda kabupaten/kota memiliki kapabilitas sinergi dengan arah pembangunan provinsi dan nasional. Aturan menyatakan sebelum ditetapkan wajib dikoordinasikan dengan gubernur. Oleh karena itu, opsi A adalah langkah yang benar.</p><p>Jawaban tepat: <strong>A. Melakukan koordinasi terlebih dahulu dengan Gubernur setempat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan lembaga pengawas kehormatan peradilan dan hakim agung.</li><li><strong>C</strong>: Merupakan media luar negeri yang tidak diwajibkan dalam tata laksana ASN.</li><li><strong>D</strong>: Merupakan perkumpulan asosiasi komersial di luar ranah birokrasi publik.</li><li><strong>E</strong>: Merupakan tindakan peralihan kuasa yang bertentangan dengan hukum otonomi daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>memimpin sekretariat • dikoordinasikan dengan gubernur</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Demi menjamin stabilitas kepemimpinan organisasi, Pejabat Pembina Kepegawaian dilarang mengganti pejabat pimpinan tinggi selama...", "options": ["Enam bulan masa adaptasi penugasan kantor", "Dua tahun terhitung sejak waktu pelantikan", "Tiga tahun masa evaluasi program strategis", "Empat tahun masa bhakti jabatan pimpinan", "Tujuh tahun masa pengabdian aparatur sipil"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Larangan Mengganti JPT Selama 2 Tahun</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Pejabat Pembina Kepegawaian dilarang mengganti pejabat pimpinan tinggi selama 2 (dua) tahun terhitung sejak pelantikan pejabat pimpinan tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan perlindungan hukum bagi pejabat karier dari mutasi politis sewenang-wenang (seperti pasca pilkada). Masa tenang dua tahun diperlukan agar program kerja strategis yang dirancang dapat dieksekusi tuntas. Regulasi menegaskan larangan penggantian selama 2 tahun sejak pelantikan. Dengan demikian, opsi B adalah batas waktu yang tepat.</p><p>Jawaban tepat: <strong>B. Dua tahun terhitung sejak waktu pelantikan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan batas adaptasi informal dan bukan batas perlindungan hukum JPT.</li><li><strong>C</strong>: Merupakan masa evaluasi jangka menengah yang tidak menjadi ambang larangan.</li><li><strong>D</strong>: Merupakan siklus masa jabatan pimpinan daerah dan bukan ambang proteksi JPT.</li><li><strong>E</strong>: Merupakan masa kerja fungsional yang melampaui batas maksimal jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pejabat Pembina Kepegawaian dilarang mengganti pejabat pimpinan tinggi • selama 2 (dua) tahun terhitung sejak pelantikan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang menteri bermaksud merotasi seorang Direktur Jenderal (JPT Madya) yang baru menjabat satu tahun. Syarat legalitas penggantian ini adalah...", "options": ["Cukup menerbitkan nota dinas internal kementerian", "Mendapat persetujuan lisan dari seluruh staf eselon tiga", "Telah mendapat persetujuan resmi dari Presiden RI", "Meminta izin dispensasi kepada dinas ketenagakerjaan", "Menyelenggarakan unjuk rasa di lingkungan kantor kementerian"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pengecualian Penggantian JPT Utama Madya Sebelum 2 Tahun</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penggantian pejabat pimpinan tinggi utama dan madya <strong>sebelum 2 (dua) tahun dapat dilakukan setelah mendapat persetujuan Presiden</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan kontrol penyeimbang agar pergantian pejabat eselon I sebelum masa 2 tahun memiliki alasan kinerja yang sangat mendasar dan objektif. Izin kepala negara mencegah tindakan mutasi emosional atau politis sepihak oleh menteri. Regulasi mensyaratkan persetujuan Presiden. Oleh karena itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Telah mendapat persetujuan resmi dari Presiden RI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tindakan sepihak yang melanggar prosedur regulasi manajemen JPT.</li><li><strong>B</strong>: Merupakan jajak pendapat bawahan yang tidak memiliki landasan yuridis formal.</li><li><strong>D</strong>: Merupakan instansi pengawas buruh swasta yang tidak berwenang atas JPT madya.</li><li><strong>E</strong>: Merupakan aksi unjuk rasa yang dilarang bagi aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penggantian pejabat pimpinan tinggi utama dan madya sebelum 2 • setelah mendapat persetujuan Presiden</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi manajemen ASN, masa jabatan seorang pejabat pimpinan tinggi hanya dapat diduduki paling lama...", "options": ["Dua tahun tanpa dapat diperpanjang", "Tiga tahun dan dapat dievaluasi", "Empat tahun berdasarkan uji berkala", "Lima tahun dan dapat diperpanjang", "Tujuh tahun mengikuti masa kepala"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Batas Maksimal Masa Jabatan JPT</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>JPT hanya dapat diduduki <strong>paling lama 5 (lima) tahun</strong> dan <strong>dapat diperpanjang berdasarkan pencapaian kinerja, kesesuaian kompetensi, dan kebutuhan instansi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan batas waktu maksimal memegang posisi pimpinan tinggi dalam tata aturan kepegawaian ASN. Pembatasan durasi 5 tahun ditujukan untuk menjaga dinamika organisasi, regenerasi kepemimpinan, dan evaluasi berkala kinerja pimpinan. Ketentuan regulasi menetapkan JPT diduduki paling lama 5 tahun dan dapat diperpanjang berdasarkan evaluasi kinerja dan kompetensi. Dengan demikian, opsi D merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>D. Lima tahun dan dapat diperpanjang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dua tahun merupakan batas minimal evaluasi berkala dan bukan masa jabatan maksimal JPT.</li><li><strong>B</strong>: Tiga tahun bukan batas maksimal masa pemangkuan jabatan pimpinan tinggi dalam regulasi.</li><li><strong>C</strong>: Empat tahun bukan batas waktu limitatif jabatan struktural pimpinan tinggi aparatur.</li><li><strong>E</strong>: Tujuh tahun melampaui batas maksimal lima tahun yang diatur dalam ketentuan manajemen ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>diduduki paling lama 5 (lima) tahun • dapat diperpanjang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Setiap Instansi Pemerintah wajib menyusun kebutuhan jumlah dan jenis formasi PPPK berdasarkan instrumen...", "options": ["Perkiraan subjektif dari pimpinan loket pelayanan kantor", "Kapasitas ruangan gedung dan ketersediaan meja kursi kerja", "Jumlah kerabat aparatur yang belum memiliki pekerjaan tetap", "Daftar permohonan sumbangan dari yayasan kemasyarakatan", "Analisis jabatan dan analisis beban kerja instansi pemerintah"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Dasar Penyusunan Kebutuhan PPPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Setiap Instansi Pemerintah wajib menyusun kebutuhan jumlah dan jenis jabatan PPPK <strong>berdasarkan analisis jabatan (anjab) dan analisis beban kerja (ABK)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan metodologi saintifik dalam perencanaan formasi pegawai negara. Perhitungan berbasis anjab dan ABK memastikan pengadaan PPPK benar-benar sesuai dengan volume riil pekerjaan dan kebutuhan kompetensi teknis organisasi. Aturan menetapkan dasar penyusunan adalah anjab dan ABK. Oleh karena itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Analisis jabatan dan analisis beban kerja instansi pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan perkiraan tanpa dasar perhitungan beban volume tugas nyata.</li><li><strong>B</strong>: Merupakan pertimbangan sarana fisik kantor yang tidak mencerminkan beban tugas.</li><li><strong>C</strong>: Merupakan pertimbangan nepotisme yang dilarang dalam perencanaan formasi.</li><li><strong>D</strong>: Merupakan kegiatan sosial di luar ranah kebutuhan formasi teknis kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Instansi Pemerintah wajib menyusun kebutuhan jumlah dan jenis jabatan • jabatan dan analisis beban kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Regulasi hukum yang menetapkan rincian jenis-jenis jabatan yang dapat diisi oleh PPPK adalah...", "options": ["Peraturan Presiden Nomor 38 Tahun 2020", "Keputusan Komisi Yudisial Nomor 12 Tahun 2018", "Instruksi Jaksa Agung Muda Bidang Pembinaan 2017", "Surat Edaran Bersama Asosiasi Advokat Indonesia 2019", "Peraturan Bank Indonesia mengenai Sistem Kliring 2021"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Dasar Hukum Jenis Jabatan PPPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Jenis jabatan yang dapat diisi oleh PPPK diatur secara spesifik dengan <strong>Peraturan Presiden Nomor 38 Tahun 2020 tentang Jenis Jabatan yang Dapat Diisi oleh Pegawai Pemerintah dengan Perjanjian Kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan landasan hukum resmi yang merinci rumpun jabatan fungsional dan pimpinan tertentu yang terbuka bagi tenaga PPPK. Perpres ini menjamin kejelasan status formasi kepegawaian secara nasional. Regulasi yang tepat adalah Perpres Nomor 38 Tahun 2020. Dengan demikian, opsi A adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Peraturan Presiden Nomor 38 Tahun 2020</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan keputusan lembaga peradilan yang tidak mengatur formasi aparatur.</li><li><strong>C</strong>: Merupakan instruksi penegakan hukum internal institusi kejaksaan.</li><li><strong>D</strong>: Merupakan edaran organisasi profesi advokat hukum swasta.</li><li><strong>E</strong>: Merupakan regulasi perbankan mengenai lalu lintas pembayaran uang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peraturan Presiden Nomor 38 Tahun 2020 • Dapat Diisi oleh Pegawai Pemerintah dengan Perjanjian Kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Penyusunan rencana kebutuhan jumlah formasi PPPK oleh instansi pemerintah disusun untuk jangka waktu...", "options": ["Tiga bulan masa darurat penanganan bencana", "Minimal lima tahun yang diperinci per satu tahun", "Sepuluh tahun tanpa evaluasi target tahunan", "Enam bulan kalender masa percobaan pegawai", "Dua puluh tahun siklus jangka panjang instansi"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Jangka Waktu Perencanaan Kebutuhan PPPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Penyusunan kebutuhan jumlah PPPK dilakukan untuk jangka waktu minimal 5 (lima) tahun yang diperinci per 1 (satu) tahun</strong> berdasarkan prioritas kebutuhan dan ditetapkan Keputusan Menteri.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan kerangka waktu perencanaan kebutuhan SDM aparatur jangka menengah yang selaras dengan RPJMN. Rencana 5 tahunan dirinci secara tahunan agar belanja pegawai tetap terkendali dan adaptif terhadap prioritas instansi. Aturan menyatakan jangka waktu minimal 5 tahun diperinci per 1 tahun. Oleh karena itu, opsi B adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>B. Minimal lima tahun yang diperinci per satu tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penanganan tanggap darurat jangka pendek dan bukan rencana SDM.</li><li><strong>C</strong>: Merupakan rencana sepuluh tahunan tanpa rincian yang menyulitkan pengawasan.</li><li><strong>D</strong>: Merupakan durasi jangka pendek yang tidak mencerminkan arah pembangunan SDM.</li><li><strong>E</strong>: Merupakan visi jangka panjang makro di luar dokumen perencanaan kebutuhan riil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Penyusunan kebutuhan jumlah PPPK dilakukan untuk jangka waktu • (lima) tahun yang diperinci per 1 (satu) tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C4 - Analisis", "question": "Tahapan pengadaan calon PPPK yang BUKAN merupakan alur resmi sesuai regulasi manajemen PPPK adalah...", "options": ["Pembayaran mahar formasi ke rekening pribadi", "Pengumuman lowongan dan pelamaran calon", "Pelaksanaan seleksi dan pengumuman hasil", "Perencanaan kebutuhan formasi jabatan", "Pengangkatan resmi menjadi pegawai PPPK"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Urutan Tahapan Pengadaan PPPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengadaan calon PPPK dilakukan melalui tahapan: <strong>perencanaan, pengumuman lowongan, pelamaran, seleksi, pengumuman hasil seleksi, dan pengangkatan menjadi PPPK</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan tindakan pungutan liar (pungli) dan penipuan yang dilarang keras dalam hukum negara. Seluruh tahapan rekrutmen ASN/PPPK diselenggarakan secara akuntabel dan bebas biaya ilegal. Aturan menetapkan pengadaan murni melalui perencanaan, pengumuman, pelamaran, seleksi, pengumuman kelulusan, dan pengangkatan. Dengan demikian, opsi A adalah pilihan jawaban pengecualian yang benar.</p><p>Jawaban tepat: <strong>A. Pembayaran mahar formasi ke rekening pribadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan tahapan pembukaan akses informasi terbuka bagi seluruh WNI.</li><li><strong>C</strong>: Merupakan tahapan pengujian kompetensi objektif peserta seleksi.</li><li><strong>D</strong>: Merupakan tahapan awal perumusan kuota formasi berbasis analisis jabatan.</li><li><strong>E</strong>: Merupakan tahapan penetapan surat keputusan hubungan perjanjian kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengadaan calon PPPK dilakukan melalui tahapan • perencanaan, pengumuman lowongan, pelamaran, seleksi, pengumuman hasil</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Peraturan Pemerintah Nomor 49 Tahun 2018, masa perjanjian kerja bagi calon PPPK ditetapkan paling singkat...", "options": ["Tiga bulan masa percobaan tugas", "Enam bulan masa adaptasi fungsi", "Satu tahun dan dapat diperpanjang", "Tiga tahun masa evaluasi awal", "Sepuluh tahun masa kerja kontrak"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Masa Minimum Perjanjian Kerja PPPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengangkatan calon PPPK ditetapkan keputusan PPK dengan <strong>masa perjanjian kerja paling singkat 1 (satu) tahun</strong> dan dapat diperpanjang sesuai kebutuhan dan berdasarkan penilaian kinerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan durasi minimal legal ikatan kerja aparatur PPPK. Batas minimal satu tahun memberikan kepastian hukum bagi pegawai sekaligus waktu memadai bagi organisasi untuk mengevaluasi kinerja. Aturan menegaskan masa kerja paling singkat 1 (satu) tahun. Oleh karena itu, opsi C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Satu tahun dan dapat diperpanjang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan durasi uji coba pekerja swasta lepas dan bukan regulasi PPPK.</li><li><strong>B</strong>: Merupakan kurun waktu orientasi teknis kantor di luar ketentuan undang-undang.</li><li><strong>D</strong>: Merupakan asumsi durasi menengah yang melebihi batas minimum hukum.</li><li><strong>E</strong>: Merupakan akumulasi masa kerja perpanjangan jangka panjang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengangkatan calon PPPK ditetapkan dengan keputusan Pejabat Pembina • masa perjanjian kerja paling singkat 1 (satu) tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Penerimaan calon PPPK dilaksanakan melalui penilaian objektif yang didasarkan pada faktor...", "options": ["Senioritas pengabdian pada lembaga pemerintah daerah", "Tingkat kualifikasi akademik tertinggi yang diserahkan", "Rekomendasi kepemimpinan dari atasan satuan kerja", "Kompetensi, kualifikasi, dan kebutuhan instansi negara", "Hasil verifikasi administrasi berkas kelulusan awal"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Prinsip Penilaian Objektif PPPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penerimaan calon PPPK dilaksanakan berdasarkan <strong>objektivitas penilaian yang didasarkan pada kompetensi, kualifikasi, dan kebutuhan Instansi Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan perwujudan prinsip sistem merit dalam pengadaan aparatur pemerintah dengan perjanjian kerja. Seleksi objektif memadukan kecakapan teknis pelamar, standar pendidikan yang dipersyaratkan, serta formasi kebutuhan organisasi pemerintah. Ketentuan perundang-undangan menegaskan penerimaan calon PPPK didasarkan pada kompetensi, kualifikasi, dan kebutuhan instansi pemerintah. Oleh sebab itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Kompetensi, kualifikasi, dan kebutuhan instansi negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Senioritas pengabdian semata bukan penentu utama dalam seleksi kompetensi objektif PPPK.</li><li><strong>B</strong>: Kualifikasi akademik merupakan syarat administratif pelengkap dari penilaian kompetensi menyeluruh.</li><li><strong>C</strong>: Rekomendasi atasan tidak dapat menggantikan tahapan ujian seleksi kompetensi resmi pemerintah.</li><li><strong>E</strong>: Verifikasi berkas hanya tahapan penyaringan awal sebelum pelaksanaan seleksi kompetensi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>melalui penilaian secara objektif • kompetensi, kualifikasi, kebutuhan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi kepegawaian nasional, ketentuan peralihan status PPPK menjadi calon PNS adalah...", "options": ["Wajib mengikuti semua proses seleksi resmi calon PNS", "Otomatis diangkat menjadi PNS setelah masa kerja dinas", "Cukup melampirkan rekomendasi tertulis pimpinan satker", "Dapat dialihkan melalui mekanisme penyesuaian berkala", "Diberikan kuota khusus tanpa melalui tahapan seleksi"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Ketentuan Pengangkatan PPPK Menjadi PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PPPK <strong>tidak dapat diangkat secara otomatis menjadi calon PNS</strong>. Untuk diangkat menjadi calon PNS, PPPK harus <strong>mengikuti semua proses seleksi yang dilaksanakan bagi calon PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A menegaskan prinsip kesetaraan dan keadilan kompetitif dalam rekrutmen aparatur negara. Tidak ada jalur otomatis pengalihan status PPPK menjadi PNS, sehingga setiap pegawai PPPK tetap wajib menempuh seleksi formasi umum calon PNS. Ketentuan perundang-undangan menetapkan PPPK yang ingin menjadi PNS harus mengikuti semua proses seleksi calon PNS. Dengan demikian, opsi A merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Wajib mengikuti semua proses seleksi resmi calon PNS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Masa kerja dinas pada formasi PPPK tidak otomatis mengubah status pegawai menjadi PNS.</li><li><strong>C</strong>: Rekomendasi pimpinan satker tidak dapat menganulir kewajiban menempuh seleksi nasional.</li><li><strong>D</strong>: Mekanisme penyesuaian berkala hanya berlaku untuk jabatan fungsional dan bukan status PNS.</li><li><strong>E</strong>: Regulasi ASN melarang pemberian kuota istimewa tanpa melalui proses seleksi kompetitif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PPPK tidak dapat diangkat secara otomatis menjadi calon PNS • harus mengikuti semua proses seleksi yang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pemerintah wajib memberikan gaji yang adil dan layak kepada pegawai PPPK yang didasarkan pada...", "options": ["Masa kerja pengabdian pegawai pada instansi pemerintah", "Hasil capaian evaluasi perilaku kerja triwulanan dinas", "Beban kerja, tanggung jawab, dan risiko pekerjaan", "Tingkat kualifikasi ijazah dan golongan kepangkatan", "Standar kelayakan biaya hidup pada masing-masing daerah"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Dasar Penetapan Gaji PPPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pemerintah wajib membayar gaji yang adil dan layak kepada PPPK yang didasarkan pada <strong>beban kerja, tanggung jawab jabatan, dan risiko pekerjaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan tiga parameter baku penetapan remunerasi aparatur berbasis keadilan dan bobot tugas jabatan. Penghitungan besaran kompensasi mempertimbangkan volume tugas dinas, wewenang jabatan yang diemban, serta tingkat bahaya pekerjaan yang dihadapi. Ketentuan hukum menetapkan gaji PPPK didasarkan pada beban kerja, tanggung jawab jabatan, dan risiko pekerjaan. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Beban kerja, tanggung jawab, dan risiko pekerjaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masa kerja bukan dasar utama dalam perumusan struktur skala upah remunerasi modern.</li><li><strong>B</strong>: Evaluasi perilaku kerja merupakan dasar penilaian disiplin dan pemberian penghargaan prestasi.</li><li><strong>D</strong>: Kualifikasi ijazah menentukan persyaratan formasi dan bukan variabel tunggal penetapan gaji.</li><li><strong>E</strong>: Biaya hidup daerah diakomodasi melalui komponen tunjangan kemahalan dan bukan gaji pokok.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemerintah wajib membayar gaji yang adil dan layak kepada PPPK • beban kerja, tanggung jawab jabatan, dan resiko pekerjaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C4 - Analisis", "question": "Peristiwa hukum yang BUKAN merupakan alasan pemutusan hubungan perjanjian kerja PPPK dengan hormat adalah...", "options": ["Jangka waktu masa perjanjian kerja telah berakhir", "Pegawai bersangkutan meninggal dunia saat bertugas", "Pengunduran diri secara sah atas permintaan sendiri", "Perampingan organisasi akibat kebijakan pemerintah", "Dihukum penjara karena penyelewengan ideologi negara"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Sebab Pemutusan Kerja PPPK dengan Hormat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pemutusan perjanjian kerja PPPK dengan hormat terjadi karena: <strong>jangka waktu berakhir, meninggal dunia, atas permintaan sendiri, perampingan organisasi, atau tidak cakap jasmani/rohani</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan tindak kejahatan berat terhadap dasar negara yang berakibat pada pemutusan hubungan kerja TIDAK dengan hormat. Pemutusan dengan hormat hanya diberikan bagi sebab-sebab wajar dan non-kriminal seperti masa kontrak usai, wafat, atau perampingan. Dengan demikian, opsi E adalah jawaban pengecualian yang benar.</p><p>Jawaban tepat: <strong>E. Dihukum penjara karena penyelewengan ideologi negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan berakhirnya siklus masa perjanjian kerja formal secara wajar.</li><li><strong>B</strong>: Merupakan peristiwa takdir kematian yang ditutup dengan pemutusan hormat.</li><li><strong>C</strong>: Merupakan hak inisiatif pengunduran diri sukarela yang diajukan resmi.</li><li><strong>D</strong>: Merupakan kebijakan penataan kelembagaan pemerintah di luar kesalahan pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemutusan hubungan perjanjian kerja PPPK dilakukan dengan hormat karena • jangka waktu perjanjian kerja berakhir • meninggal dunia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang pegawai PPPK selama dua tahun berturut-turut terbukti tidak mencapai target kinerja perjanjian kerja. Keputusan administratif yang diambil adalah...", "options": ["Pemberian kenaikan bonus finansial tahunan dari kementerian", "Pemutusan perjanjian kerja dengan hormat tidak atas permintaan sendiri", "Pengangkatan langsung menjadi pejabat fungsional ahli utama", "Pemindahan tugas ke luar negeri dengan fasilitas dinas lengkap", "Pemberian cuti istimewa di luar tanggungan negara selama setahun"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemutusan PPPK Karena Target Kinerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pemutusan hubungan kerja PPPK dilakukan <strong>dengan hormat tidak atas permintaan sendiri</strong> apabila <strong>tidak memenuhi target kinerja yang telah disepakati sesuai dengan perjanjian kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan konsekuensi logis dari esensi kontrak kerja berbasis kinerja (*performance-based contract*). Aparatur PPPK direkrut untuk menuntaskan sasaran spesifik, sehingga kegagalan memenuhi target menjadi dasar terminasi kontrak secara hormat. Aturan menegaskan pemutusannya dilakukan dengan hormat tidak atas permintaan sendiri. Oleh karena itu, opsi B adalah keputusan yang tepat.</p><p>Jawaban tepat: <strong>B. Pemutusan perjanjian kerja dengan hormat tidak atas permintaan sendiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan imbalan prestasi kerja yang keliru diberikan kepada pegawai tidak perform.</li><li><strong>C</strong>: Merupakan promosi jabatan fungsional tertinggi yang menuntut kompetensi unggul.</li><li><strong>D</strong>: Merupakan tugas belajar atau dinas luar negeri yang membutuhkan kualifikasi prima.</li><li><strong>E</strong>: Merupakan fasilitas cuti tanpa gaji yang tidak menjawab masalah inkompetensi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemutusan hubungan perjanjian kerja PPPK dilakukan dengan hormat tidak atas • tidak memenuhi target kinerja yang telah disepakati</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang pegawai PPPK terdaftar resmi dan aktif menjabat sebagai pengurus partai politik. Jenis pemutusan hubungan kerja yang dijatuhkan adalah...", "options": ["Pemutusan hubungan perjanjian kerja dengan pemberian penghargaan", "Pemberian dispensasi khusus selama musim kampanye pemilihan umum", "Pemindahan penugasan sementara ke sekretariat dewan perwakilan", "Pemutusan hubungan perjanjian kerja dilakukan tidak dengan hormat", "Penundaan pembayaran gaji bulanan sampai masa kampanye selesai"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemutusan PPPK Keterlibatan Partai Politik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pemutusan hubungan perjanjian kerja PPPK dilakukan <strong>tidak dengan hormat</strong> karena terbukti <strong>menjadi anggota dan/atau pengurus partai politik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan sanksi terberat atas pelanggaran prinsip netralitas aparatur negara. Keterlibatan aktif dalam kepengurusan partai meruntuhkan independensi fungsi pelayanan publik ASN. Regulasi menegaskan keterlibatan parpol berakibat pemutusan perjanjian kerja tidak dengan hormat. Dengan demikian, opsi D adalah keputusan yang benar.</p><p>Jawaban tepat: <strong>D. Pemutusan hubungan perjanjian kerja dilakukan tidak dengan hormat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan terminasi kerja yang tidak pantas diberikan kepada pelanggar netralitas.</li><li><strong>B</strong>: Merupakan pembiaran pelanggaran hukum yang merusak netralitas birokrasi.</li><li><strong>C</strong>: Merupakan mutasi tugas yang tidak menyelesaikan pelanggaran status pegawai.</li><li><strong>E</strong>: Merupakan penundaan gaji ringan yang tidak sebanding dengan sanksi undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemutusan hubungan perjanjian kerja PPPK dilakukan tidak dengan hormat • menjadi anggota dan/atau pengurus partai politik</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pegawai PPPK yang terbukti melakukan tindak pidana kejahatan jabatan berdasarkan putusan pengadilan berkekuatan hukum tetap diberhentikan...", "options": ["Tidak dengan hormat sebagai aparatur perjanjian kerja", "Dengan hormat disertai pemberian uang pensiun bulanan", "Dengan hormat atas permintaan tertulis pejabat pembina", "Sementara selama masa proses peninjauan kembali perkara", "Secara bersyarat menunggu terbitnya surat grasi presiden"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemutusan PPPK Kejahatan Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pemutusan hubungan perjanjian kerja PPPK dilakukan <strong>tidak dengan hormat</strong> karena <strong>melakukan tindak pidana kejahatan jabatan atau tindak pidana kejahatan yang ada hubungannya dengan jabatan dan/atau pidana umum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan tindakan pembersihan birokrasi dari aparatur yang menyalahgunakan wewenang negara untuk kejahatan jabatan (seperti suap/korupsi). Vonis *inkracht* kejahatan jabatan secara otomatis menghapus kelayakan integritas aparatur sipil. Aturan menetapkan pemutusannya dilakukan tidak dengan hormat. Oleh karena itu, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Tidak dengan hormat sebagai aparatur perjanjian kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan hak pensiun yang tidak diberikan kepada pelaku kejahatan jabatan.</li><li><strong>C</strong>: Merupakan terminasi kerja terhormat yang tidak berlaku bagi pelaku pidana jabatan.</li><li><strong>D</strong>: Merupakan status pemberhentian sementara saat perkara masih dalam proses awal.</li><li><strong>E</strong>: Merupakan permohonan pengampunan yang tidak meniadakan sanksi kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemutusan hubungan perjanjian kerja PPPK dilakukan tidak dengan hormat • jabatan atau tindak pidana kejahatan yang ada hubungannya dengan jabatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pelanggaran disiplin tingkat berat yang dilakukan oleh pegawai PPPK berakibat pada pemutusan hubungan kerja berupa...", "options": ["Pemberian surat peringatan tertulis masa percobaan kerja", "Penundaan perpanjangan masa kontrak perjanjian kepegawaian", "Pemutusan hubungan kerja dengan hormat tidak atas permohonan", "Pemindahan tugas penempatan operasional pada unit terluar", "Penurunan kelas jabatan setingkat lebih rendah satu tahun"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemutusan PPPK Pelanggaran Disiplin Berat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pemutusan hubungan perjanjian kerja PPPK dilakukan <strong>dengan hormat tidak atas permintaan sendiri karena melakukan pelanggaran disiplin PPPK tingkat berat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan sanksi yuridis pemutusan hubungan kerja atas pelanggaran disiplin tingkat berat oleh aparatur perjanjian kerja. Pelanggaran berat menunjukkan ketidakmampuan pegawai menjaga integritas dan ketertiban kedinasan sehingga kontrak kerja diakhiri secara resmi oleh instansi. Ketentuan perundang-undangan menetapkan sanksi pelanggaran berat adalah pemutusan kerja dengan hormat tidak atas permintaan sendiri. Dengan demikian, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Pemutusan hubungan kerja dengan hormat tidak atas permohonan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Peringatan tertulis merupakan sanksi pembinaan administratif ringan dan bukan sanksi berat.</li><li><strong>B</strong>: Penundaan perpanjangan kontrak bukan bentuk hukuman disiplin pemutusan yang baku.</li><li><strong>D</strong>: Pemindahan tugas merupakan bentuk rotasi penugasan dan bukan putusan pelanggaran berat.</li><li><strong>E</strong>: Penurunan kelas jabatan merupakan jenis sanksi disiplin bagi PNS dan bukan pemutusan PPPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemutusan hubungan perjanjian kerja PPPK dilakukan dengan hormat tidak atas • melakukan pelanggaran disiplin PPPK tingkat berat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C4 - Analisis", "question": "Pegawai PPPK dijatuhi hukuman penjara 2 tahun karena tindak pidana kelalaian (tidak berencana) yang telah berkekuatan hukum tetap. Status pemutusannya adalah...", "options": ["Diberhentikan tidak dengan hormat dan dicabut seluruh hak perdata", "Diberikan izin menjalani masa hukuman sambil tetap menerima gaji pokok", "Dialihkan statusnya menjadi calon pegawai negeri sipil istimewa", "Diberikan cuti besar di luar tanggungan negara sampai masa tahanan usai", "Pemutusan hubungan perjanjian kerja dengan hormat tidak atas permintaan sendiri"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemutusan PPPK Pidana Tidak Berencana</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PPPK diputus hubungan kerjanya <strong>dengan hormat tidak atas permintaan sendiri</strong> apabila <strong>dihukum penjara paling singkat 2 tahun berdasarkan putusan berkekuatan hukum tetap karena tindak pidana tidak berencana</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan pembedaan perlakuan yuridis antara pidana kelalaian (tidak berencana) dengan kejahatan berencana/jabatan. Karena tidak terdapat niat jahat terencana (*mens rea* terencana), pemutusan hubungan kerjanya tetap berstatus 'dengan hormat tidak atas permintaan sendiri'. Aturan menyatakan pemutusannya dilakukan dengan hormat tidak atas permintaan sendiri. Oleh karena itu, opsi E adalah keputusan yang tepat.</p><p>Jawaban tepat: <strong>E. Pemutusan hubungan perjanjian kerja dengan hormat tidak atas permintaan sendiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan sanksi untuk tindak pidana berencana atau kejahatan jabatan.</li><li><strong>B</strong>: Merupakan fasilitas ilegal yang melanggar aturan pengelolaan keuangan negara.</li><li><strong>C</strong>: Merupakan alih status fiktif yang bertentangan dengan logika seleksi aparatur.</li><li><strong>D</strong>: Merupakan penyalahgunaan hak cuti kedinasan bagi terpidana peradilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dengan pidana penjara paling singkat 2 (dua) tahun • tindak pidana tersebut dilakukan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang PPPK dijatuhi vonis pidana penjara 3 tahun karena terbukti melakukan kejahatan penipuan berencana. Pemutusan perjanjian kerja dilakukan secara...", "options": ["Pemutusan perjanjian kerja dengan hormat atas permohonan", "Pemutusan hubungan perjanjian kerja tidak dengan hormat", "Pemutusan perjanjian kerja dengan hormat karena perampingan", "Pembebasan sementara dari tugas kedinasan selama banding", "Penundaan evaluasi perjanjian kerja sampai hukuman selesai"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemutusan PPPK Pidana Berencana</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pemutusan hubungan kerja dilakukan <strong>tidak dengan hormat</strong> karena <strong>dihukum penjara berdasarkan putusan pengadilan yang berkekuatan hukum tetap karena melakukan tindak pidana dengan pidana penjara paling singkat 2 (dua) tahun dan pidana dilakukan dengan berencana</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan bentuk pemutusan hubungan kerja terberat bagi aparatur yang terbukti melakukan tindak pidana dengan perencanaan matang dan vonis minimal 2 tahun. Kualifikasi kejahatan berencana membatalkan kelayakan integritas abdi negara sehingga diberhentikan secara tidak hormat. Ketentuan hukum menegaskan pemutusan hubungan perjanjian kerja dilakukan tidak dengan hormat. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Pemutusan hubungan perjanjian kerja tidak dengan hormat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemutusan dengan hormat atas permohonan sendiri diperuntukkan bagi pengunduran diri sukarela.</li><li><strong>C</strong>: Pemutusan karena perampingan organisasi merupakan alasan penataan kelembagaan dinas.</li><li><strong>D</strong>: Pembebasan sementara hanya berlangsung selama proses pemeriksaan sebelum vonis inkracht.</li><li><strong>E</strong>: Penundaan evaluasi tidak berlaku apabila vonis pengadilan telah berkekuatan hukum tetap.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>hubungan perjanjian kerja • tidak dengan hormat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pegawai PPPK yang mengalami gangguan kesehatan permanen sehingga tidak dapat lagi menjalankan tugas diputus hubungan kerjanya secara...", "options": ["Tidak dengan hormat karena kelalaian menjaga kesehatan fisik", "Pemberhentian sementara tanpa hak jaminan perlindungan sosial", "Penurunan jenjang jabatan kerja ke tingkat staf pelaksana terendah", "Dengan hormat karena tidak cakap jasmani dan/atau rohani", "Pemberhentian dengan sanksi pengembalian seluruh gaji yang diterima"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemutusan PPPK Ketidakcakapan Medis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pemutusan hubungan perjanjian kerja PPPK dilakukan <strong>dengan hormat</strong> karena <strong>tidak cakap jasmani dan/atau rohani sehingga tidak dapat menjalankan tugas dan kewajiban sesuai perjanjian kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan perlakuan humanis negara terhadap aparatur yang mengalami musibah kesehatan permanen. Karena ketidakmampuan kerja murni faktor medis di luar kesengajaan, terminasi dilakukan dengan terhormat dan pegawai berhak atas jaminan perlindungan. Aturan menyatakan pemutusannya dilakukan dengan hormat. Oleh karena itu, opsi D adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Dengan hormat karena tidak cakap jasmani dan/atau rohani</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan vonis tidak hormat yang tidak manusiawi dan menyalahi hukum.</li><li><strong>B</strong>: Merupakan tindakan penelantaran hak jaminan sosial aparatur negara.</li><li><strong>C</strong>: Merupakan mutasi jabatan yang tidak menjawab kondisi ketidakmampuan permanen.</li><li><strong>E</strong>: Merupakan penalti ganti rugi yang tidak berlaku bagi musibah kesehatan medis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tidak cakap jasmani dan/atau rohani sehingga tidak dapat menjalankan tugas • perjanjian kerja yang disepakati</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi manajemen PNS, batas usia pensiun bagi Pejabat Pimpinan Tinggi Utama dan Madya adalah...", "options": ["56 tahun", "60 tahun", "58 tahun", "62 tahun", "65 tahun"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Ruang Lingkup Bantuan Hukum PPPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Batas usia pensiun bagi: <strong>60 (enam puluh) tahun bagi pejabat pimpinan tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan batas usia pensiun (BUP) normatif bagi pejabat pimpinan tinggi dalam birokrasi pemerintahan. Pengaturan usia 60 tahun memberikan ruang pengabdian manajerial puncak dengan tetap menjaga sirkulasi regenerasi kepemimpinan aparatur. Ketentuan hukum kepegawaian menetapkan batas usia pensiun bagi pejabat pimpinan tinggi adalah 60 tahun. Dengan demikian, opsi B merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. 60 tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Batas usia 56 tahun merupakan batas usia pensiun pada ketentuan perundang-undangan lama.</li><li><strong>C</strong>: Batas usia 58 tahun berlaku bagi pejabat administrasi, pengawas, dan pelaksana.</li><li><strong>D</strong>: Batas usia 62 tahun bukan merupakan jenjang batas usia pensiun baku dalam manajemen ASN.</li><li><strong>E</strong>: Batas usia 65 tahun berlaku bagi pejabat fungsional keahlian jenjang Ahli Utama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Perlindungan berupa bantuan hukum dilakukan dengan • hukum dalam perkara yang dihadapi di pengadilan terkait pelaksanaan tugasnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Seorang PNS diangkat menjadi Ketua atau Anggota Mahkamah Konstitusi. Menurut Pasal 58 UU Nomor 20 Tahun 2023, status kepegawaiannya adalah...", "options": ["Diberhentikan permanen dan dicabut hak pensiun aparatur sipil", "Tetap menduduki jabatan struktural lamanya di kementerian asal", "Diberikan kenaikan pangkat otomatis dua tingkat setiap semester", "Diberhentikan sementara dari jabatan tanpa kehilangan status PNS", "Dialihkan menjadi pegawai kontrak daerah tempat tinggal keluarga"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Status PNS Diangkat Pimpinan Lembaga Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pegawai ASN dari PNS yang diangkat menjadi pejabat negara (seperti pimpinan MK, BPK, KY, KPK, Menteri, Dubes) <strong>diberhentikan sementara dari jabatannya, dan tidak kehilangan status sebagai PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan status hukum perlakuan aparatur karier yang mengemban amanah lembaga negara. Pejabat dinonaktifkan dari posisi struktural/fungsional kementeriannya agar fokus independen, namun nomor induk dan status PNS-nya tetap melekat. Regulasi menyatakan diberhentikan sementara dari jabatan dan tidak kehilangan status PNS. Oleh karena itu, opsi D adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Diberhentikan sementara dari jabatan tanpa kehilangan status PNS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pemberhentian sewenang-wenang yang melanggar hak perlindungan PNS.</li><li><strong>B</strong>: Merupakan rangkap jabatan struktural yang dilarang keras undang-undang.</li><li><strong>C</strong>: Merupakan promosi pangkat otomatis tanpa dasar hukum manajemen prestasi.</li><li><strong>E</strong>: Merupakan penurunan status kepegawaian yang tidak berdasar pada regulasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>ketua, wakil ketua, dan anggota Mahkamah Konstitusi • jabatannya, dan tidak kehilangan status sebagai PNS</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "PNS yang telah selesai menjalankan tugas sebagai pejabat negara diaktifkan kembali dalam jabatan PNS apabila...", "options": ["Tersedia lowongan formasi jabatan yang sesuai kualifikasi", "Mendapat rekomendasi tertulis dari pimpinan partai politik", "Menyelesaikan masa pembekalan tugas paling lama dua tahun", "Memiliki masa kerja aparatur paling singkat sepuluh tahun", "Menyatakan kesediaan ditempatkan pada instansi luar negeri"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pengaktifan Kembali PNS Pejabat Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS yang selesai menjalankan tugas sebagai Pejabat Negara <strong>dapat diaktifkan kembali dalam jabatan PNS apabila tersedia lowongan jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan syarat administratif pengaktifan kembali aparatur pasca mengemban tugas sebagai pejabat negara. Karena status kepegawaian PNS tetap dipertahankan selama masa bakti negara, yang bersangkutan berhak kembali bertugas aktif sepanjang terdapat formasi jabatan yang lowong. Ketentuan regulasi menegaskan PNS diaktifkan kembali apabila tersedia lowongan jabatan. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Tersedia lowongan formasi jabatan yang sesuai kualifikasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Rekomendasi partai politik bertentangan dengan asas netralitas aparatur sipil negara.</li><li><strong>C</strong>: Masa pembekalan dua tahun bukan prasyarat yuridis pengaktifan kembali status kepegawaian.</li><li><strong>D</strong>: Masa kerja sepuluh tahun bukan parameter formal pengaktifan mantan pejabat negara.</li><li><strong>E</strong>: Penempatan luar negeri merupakan mekanisme penugasan khusus dan bukan syarat pengaktifan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS tersebut tidak menjabat lagi sebagai pejabat negara • diaktifkan kembali</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Jika tidak tersedia lowongan jabatan bagi mantan pejabat negara, batas waktu maksimal sebelum diberhentikan dengan hormat adalah...", "options": ["Satu tahun kalender masa tunggu", "Dua tahun masa penyesuaian satker", "Paling lama lima tahun masa tunggu", "Tujuh tahun masa evaluasi kelembagaan", "Sepuluh tahun masa pembinaan dinas"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Batas Waktu Lowongan Jabatan Mantan Pejabat Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Berdasarkan Pasal 60 UU Nomor 20 Tahun 2023, dalam hal tidak tersedia lowongan jabatan, <strong>dalam waktu paling lama 5 (lima) tahun PNS yang bersangkutan diberhentikan dengan hormat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan masa tunggu legal (*grace period*) yang disediakan undang-undang untuk mencarikan posisi yang cocok bagi mantan pejabat negara. Jika hingga 5 tahun tidak ada formasi yang sesuai, hubungan dinas ditutup terhormat. Regulasi menetapkan kurun waktu paling lama 5 tahun. Oleh karena itu, opsi C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Paling lama lima tahun masa tunggu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan masa tunggu satu tahun pada ketentuan lama di UU No 5/2014.</li><li><strong>B</strong>: Merupakan durasi dua tahun yang tidak sesuai dengan UU No 20 Tahun 2023.</li><li><strong>D</strong>: Merupakan batas waktu berlebih yang melampaui ketentuan hukum positif.</li><li><strong>E</strong>: Merupakan kurun waktu sepuluh tahun yang menyebabkan ketidakpastian status.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dalam hal tidak tersedia lowongan jabatan • waktu paling lama 5 (lima) tahun PNS yang bersangkutan diberhentikan dengan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang PNS resmi ditetapkan oleh KPU sebagai calon kepala daerah dalam pemilihan umum. Kewajiban hukum yang wajib dipenuhi adalah...", "options": ["Mengajukan cuti tahunan selama masa kampanye pemilihan", "Mengajukan cuti di luar tanggungan negara hingga coblosan", "Menyatakan nonaktif sementara dari tugas dinas satker", "Meminta izin penugasan khusus kepada menteri kepegawaian", "Menyatakan pengunduran diri tertulis sebagai PNS sejak calon"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kewajiban Pengunduran Diri Calon Pilpres/Pilkada/Legislatif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS wajib <strong>mengundurkan diri secara tertulis sebagai PNS sejak ditetapkan sebagai calon</strong> Presiden, Wakil Presiden, DPR, DPD, DPRD, kepala daerah, atau wakil kepala daerah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan garis demarkasi mutlak antara dunia politik elektoral dengan korps birokrasi netral. Keterlibatan dalam kontestasi pemilihan menuntut pelepasan permanen status aparatur demi menjamin asas netralitas dan mencegah penyalahgunaan wewenang negara. Ketentuan undang-undang mewajibkan PNS mengundurkan diri tertulis sejak ditetapkan sebagai calon. Oleh sebab itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Menyatakan pengunduran diri tertulis sebagai PNS sejak calon</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengajuan cuti tahunan tidak diperbolehkan bagi aparatur yang mencalonkan diri dalam pemilu.</li><li><strong>B</strong>: Cuti di luar tanggungan negara bukan sarana legalitas untuk mengikuti kontestasi politik.</li><li><strong>C</strong>: Status nonaktif sementara tidak menggugurkan kewajiban pengunduran diri permanen.</li><li><strong>D</strong>: Izin penugasan khusus tidak dapat diberikan untuk kepentingan politik praktis perorangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS yang mencalonkan diri atau dicalonkan menjadi Presiden dan Wakil • wajib menyatakan pengunduran diri secara tertulis sebagai PNS sejak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Seorang pejabat karier diplomatik PNS diangkat Presiden menjadi Duta Besar Luar Biasa dan Berkuasa Penuh. Status hukum kedinasannya adalah...", "options": ["Diberhentikan permanen dengan sanksi pencabutan paspor diplomatik", "Diberhentikan sementara dari jabatannya tanpa kehilangan status PNS", "Otomatis kehilangan kewarganegaraan Indonesia karena bertugas di luar", "Diberikan hak kebal hukum perdata penuh atas seluruh aset keluarganya", "Dialihkan menjadi pegawai kontrak lokal kementerian luar negeri"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Status PNS Diangkat Duta Besar Luar Biasa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS yang diangkat menjadi <strong>Kepala perwakilan RI di luar negeri yang berkedudukan sebagai Duta Besar Luar Biasa dan Berkuasa Penuh</strong> menurut Pasal 58 UU 20/2023 <strong>diberhentikan sementara dari jabatannya, dan tidak kehilangan status sebagai PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan perlindungan status korps kepegawaian bagi pejabat negara di perwakilan luar negeri. Jabatan dubes adalah amanah pejabat negara, sehingga jabatan struktural/fungsional di kementerian dinonaktifkan sementara tanpa memutus status PNS-nya. Regulasi menyatakan statusnya diberhentikan sementara dari jabatan tanpa kehilangan status PNS. Oleh karena itu, opsi B adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. Diberhentikan sementara dari jabatannya tanpa kehilangan status PNS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan hukuman sanksi berat yang tidak berdasar bagi pengemban tugas negara.</li><li><strong>C</strong>: Merupakan kehilangan kewarganegaraan keliru yang mengabaikan tugas diplomatik.</li><li><strong>D</strong>: Merupakan kekebalan hukum privat yang tidak relevan dengan status kepegawaian.</li><li><strong>E</strong>: Merupakan penurunan derajat pegawai tetap menjadi tenaga honorer lokal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kepala perwakilan RI di luar negeri yang berkedudukan sebagai Duta Besar • jabatannya, dan tidak kehilangan status sebagai PNS</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C4 - Analisis", "question": "Lembaga negara yang pimpinannya diisi dari unsur PNS dengan status diberhentikan sementara tanpa kehilangan status PNS adalah...", "options": ["Direksi badan usaha milik swasta sektor telekomunikasi", "Komisi Pemilihan Umum Daerah tingkat distrik kepulauan", "Ketua, wakil ketua, dan anggota Komisi Yudisial serta KPK", "Dewan pengurus lembaga swadaya perlindungan konsumen", "Pimpinan asosiasi pengembang perumahan rakyat nasional"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Daftar Pejabat Negara Non-Kehilangan Status PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pasal 58 UU Nomor 20 Tahun 2023 merinci pimpinan pejabat negara yang mencakup: <strong>MK, BPK, Komisi Yudisial, KPK, Menteri/setingkat menteri, dan Duta Besar LBBP</strong> yang diberhentikan sementara dari jabatannya tanpa kehilangan status PNS.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan enumerasi yuridis lembaga negara independen yang termaktub dalam Pasal 58 UU ASN. Komisioner KY dan pimpinan KPK berkedudukan sebagai pejabat negara yang menuntut penonaktifan sementara dari jabatan sipil asalnya. Aturan menyebutkan pimpinan KY dan KPK termasuk dalam daftar resmi ini. Dengan demikian, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Ketua, wakil ketua, dan anggota Komisi Yudisial serta KPK</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pengurus korporasi bisnis komersial swasta nonpemerintah.</li><li><strong>B</strong>: Merupakan penyelenggara pemilu kewilayahan di luar daftar Pasal 58 UU ASN.</li><li><strong>D</strong>: Merupakan organisasi advokasi masyarakat nonpemerintah di luar struktur negara.</li><li><strong>E</strong>: Merupakan asosiasi perkumpulan pengusaha properti swasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pegawai ASN dari PNS yang diangkat menjadi • ketua, wakil ketua, dan anggota Komisi Yudisial • ketua, wakil ketua, dan anggota KPK</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 59 UU Nomor 20 Tahun 2023, titik waktu mutlak bagi PNS yang mencalonkan diri dalam pemilu untuk mundur tertulis adalah...", "options": ["Saat mengambil formulir pendaftaran di kantor partai politik", "Saat memasang baliho sosialisasi diri di jalan protokol", "Saat penghitungan suara resmi dinyatakan selesai oleh panitia", "Setelah resmi ditetapkan sebagai calon oleh lembaga penyelenggara", "Setelah resmi dilantik dan mengucapkan sumpah sebagai pejabat"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Titik Waktu Pengunduran Diri PNS Kontestasi Politik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS yang mencalonkan diri menjadi pejabat politik <strong>wajib menyatakan pengunduran diri secara tertulis sebagai PNS sejak ditetapkan sebagai calon</strong> oleh penyelenggara pemilihan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan ketetapan waktu hukum yang mengikat status kepegawaian peserta kontestasi. Surat pengunduran diri tidak menunggu hasil pemilu atau pelantikan, melainkan wajib diserahkan tepat pada momentum pengumuman penetapan daftar calon tetap (DCT). Regulasi menegaskan pengunduran diri berlaku sejak ditetapkan sebagai calon. Oleh karena itu, opsi D adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Setelah resmi ditetapkan sebagai calon oleh lembaga penyelenggara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tahap penjajakan awal yang belum memiliki kepastian penetapan hukum.</li><li><strong>B</strong>: Merupakan sosialisasi personal sebelum penetapan resmi oleh pihak KPU.</li><li><strong>C</strong>: Merupakan akhir proses kontestasi yang telah terlambat secara prosedur hukum.</li><li><strong>E</strong>: Merupakan tahap akhir pasca pemilu yang melanggar ketentuan status PNS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>wajib menyatakan pengunduran diri secara tertulis sebagai PNS • ditetapkan sebagai calon</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang PNS fungsional auditor BPKP terpilih melalui uji kepatutan parlemen menjadi Anggota BPK. Status kedinasannya diatur berupa...", "options": ["Diberhentikan tidak dengan hormat dan kehilangan seluruh hak aparatur", "Diberikan kewenangan merangkap jabatan sebagai kepala perwakilan daerah", "Dialihkan menjadi pegawai kontrak lepas pada sekretariat jenderal dpr", "Diharuskan mengajukan pensiun dini sukarela tanpa pertimbangan dinas", "Diberhentikan sementara dari jabatan fungsional tanpa hilang status PNS"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Status PNS Menjadi Anggota BPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS yang diangkat menjadi <strong>ketua, wakil ketua, dan anggota BPK</strong> diberhentikan sementara dari jabatannya dan <strong>tidak kehilangan status sebagai PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan aplikasi Pasal 58 UU 20/2023 bagi pimpinan lembaga pemeriksa keuangan eksternal negara. Auditor dinonaktifkan dari jabatan sebelumnya untuk mencegah konflik audit, namun status kepegawaian negerinya tetap terpelihara. Aturan menegaskan status diberhentikan sementara dari jabatan tanpa kehilangan status PNS. Dengan demikian, opsi E adalah keputusan yang benar.</p><p>Jawaban tepat: <strong>E. Diberhentikan sementara dari jabatan fungsional tanpa hilang status PNS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan sanksi pidana/disiplin yang tidak berlaku bagi pengangkatan pejabat negara.</li><li><strong>B</strong>: Merupakan rangkap jabatan yang mencederai independensi lembaga audit negara.</li><li><strong>C</strong>: Merupakan degradasi status yang tidak sesuai dengan ketentuan perundangan.</li><li><strong>D</strong>: Merupakan pemensiunan paksa yang menyalahi hak perlindungan karier PNS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>ketua, wakil ketua, dan anggota BPK • jabatannya, dan tidak kehilangan status sebagai PNS</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama pemberian jaminan bantuan hukum kepada aparatur sipil negara dalam penyelenggaraan tugas adalah...", "options": ["Memberikan pembebasan dari segala mekanisme pengawasan dinas", "Meniadakan pertanggungjawaban hukum bagi aparatur pemerintah", "Memberikan pendampingan hukum atas perkara pelaksanaan tugas", "Melindungi pegawai dari kewajiban mematuhi aturan peradilan", "Menjamin imunitas penuh atas pelanggaran kode etik kepegawaian"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Hakikat Jaminan Bantuan Hukum Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Bantuan hukum diberikan dalam bentuk <strong>pemberian bantuan hukum dalam perkara yang dihadapi di pengadilan terkait pelaksanaan tugasnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan esensi perlindungan hukum negara bagi aparatur yang menghadapi sengketa hukum dalam menjalankan kebijakan resmi dinas. Pendampingan ini memberikan ketenangan dan perlindungan bagi pegawai yang bertindak sesuai regulasi dari risiko kriminalisasi. Ketentuan hukum menegaskan bantuan hukum diberikan dalam perkara terkait pelaksanaan tugas kedinasan. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Memberikan pendampingan hukum atas perkara pelaksanaan tugas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aparatur sipil negara tetap wajib tunduk pada seluruh mekanisme pengawasan internal dan eksternal.</li><li><strong>B</strong>: Bantuan hukum bukan instrumen untuk menghapus akuntabilitas hukum dan pertanggungjawaban dinas.</li><li><strong>D</strong>: Setiap pegawai pemerintah wajib menghormati dan mematuhi seluruh putusan lembaga peradilan.</li><li><strong>E</strong>: Pelanggaran kode etik tetap diproses melalui majelis kehormatan tanpa imunitas khusus.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Perlindungan berupa bantuan hukum • perkara yang dihadapi di pengadilan terkait pelaksanaan tugasnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Penetapan mutasi PNS antar-kabupaten/kota dalam satu wilayah provinsi merupakan kewenangan...", "options": ["Walikota tempat domisili keluarga pemohon mutasi dinas", "Bupati tempat satuan kerja unit penugasan asal pegawai", "Kepala kantor wilayah kementerian keuangan provinsi", "Pimpinan inspektorat wilayah pemerintahan provinsi", "Gubernur setelah mendapat pertimbangan Kepala BKN"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kewenangan Mutasi Antar-Kabupaten/Kota Satu Provinsi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Mutasi PNS antar-kabupaten/kota dalam satu provinsi <strong>ditetapkan oleh gubernur setelah memperoleh pertimbangan Kepala BKN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan garis kewenangan koordinasi regional gubernur selaku wakil pemerintah pusat di wilayah terhadap mobilitas aparatur daerah. Keterlibatan pertimbangan teknis Kepala BKN memastikan perpindahan pegawai selaras dengan ketersediaan formasi dan basis data kepegawaian nasional. Ketentuan regulasi menetapkan mutasi antarkabupaten/kota dalam satu provinsi ditetapkan Gubernur setelah mendapat pertimbangan Kepala BKN. Dengan demikian, opsi E merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Gubernur setelah mendapat pertimbangan Kepala BKN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Walikota hanya berwenang menetapkan mutasi internal di dalam wilayah kotanya sendiri.</li><li><strong>B</strong>: Bupati instansi asal hanya memberikan persetujuan pelepasan dan bukan menetapkan mutasi antar-daerah.</li><li><strong>C</strong>: Kanwil Kemenkeu mengelola mutasi instansi vertikal kementerian dan bukan PNS pemerintah daerah.</li><li><strong>D</strong>: Inspektorat provinsi menjalankan fungsi pengawasan dan tidak berwenang menetapkan mutasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 28) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>antar-kabupaten/kota dalam satu provinsi ditetapkan oleh Gubernur • memperoleh pertimbangan kepala Badan Kepegawaian Negara (BKN)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Mutasi PNS antar-kabupaten/kota antar-provinsi dan antar-provinsi secara yuridis ditetapkan oleh...", "options": ["Ketua Mahkamah Agung Republik Indonesia", "Pimpinan badan usaha milik negara perbankan", "Ketua dewan perwakilan rakyat tingkat pusat", "Kepala dinas tenaga kerja tingkat provinsi", "Menteri PAN-RB setelah pertimbangan Kepala BKN"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kewenangan Mutasi Antar-Provinsi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Mutasi PNS <strong>antar-kabupaten/kota antar-provinsi dan antar-provinsi ditetapkan oleh Menteri PAN-RB setelah memperoleh pertimbangan kepala BKN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan wewenang skala makro lintas batas otonomi daerah yang dikelola langsung kementerian pendayagunaan aparatur. Pergerakan aparatur antarpulau/provinsi memerlukan persetujuan menteri guna menjaga keseimbangan formasi ASN nasional. Aturan menegaskan wewenang berada pada Menteri PAN-RB setelah pertimbangan BKN. Oleh karena itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Menteri PAN-RB setelah pertimbangan Kepala BKN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pimpinan tertinggi yudikatif peradilan umum negara.</li><li><strong>B</strong>: Merupakan pimpinan perseroan bank komersial milik pemerintah.</li><li><strong>C</strong>: Merupakan pimpinan legislatif parlemen tanpa fungsi operasional mutasi.</li><li><strong>D</strong>: Merupakan pejabat teknis dinas daerah dengan yurisdiksi terbatas satu daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 28) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>antar-kabupaten/kota antar-provinsi dan antar-provinsi • Menteri PAN-RB setelah memperoleh pertimbangan kepala BKN</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pengalihan penugasan mutasi seorang PNS dari instansi pemerintah daerah ke Instansi Pusat ditetapkan oleh...", "options": ["Gubernur pemerintah daerah wilayah asal pegawai", "Kepala Badan Kepegawaian Negara Republik Indonesia", "Menteri Dalam Negeri selaku pembina daerah wilayah", "Sekretaris Jenderal kementerian instansi penerima", "Ketua Komisi Pengawas Manajemen Aparatur Sipil"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kewenangan Mutasi Daerah ke Instansi Pusat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Mutasi PNS antar-instansi daerah dan instansi pusat <strong>ditetapkan oleh Kepala BKN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan peran integratif Badan Kepegawaian Negara sebagai otoritas pengelola administrasi kepegawaian nasional. Mutasi lintas yurisdiksi antara pemerintah daerah dengan kementerian lembaga pusat ditetapkan langsung oleh Kepala BKN untuk menjamin tertib administrasi nasional. Ketentuan perundang-undangan menetapkan mutasi antar-daerah dan instansi pusat ditetapkan oleh Kepala BKN. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Kepala Badan Kepegawaian Negara Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Gubernur berwenang pada mutasi antar-kabupaten/kota dalam satu provinsi dan bukan ke instansi pusat.</li><li><strong>C</strong>: Mendagri membina kebijakan umum pemda namun penetapan mutasi teknis PNS dilakukan Kepala BKN.</li><li><strong>D</strong>: Sekjen kementerian penerima memberikan persetujuan kebutuhan dan bukan menetapkan SK mutasi.</li><li><strong>E</strong>: Komisi pengawas bertugas mengawasi sistem merit dan tidak menerbitkan keputusan mutasi pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 28) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mutasi PNS provinsi/kabupaten/kota ke Instansi Pusat atau sebaliknya • ditetapkan oleh Kepala BKN</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Peraturan BKN Nomor 5 Tahun 2019, pelaksanaan mutasi PNS wajib berpegang teguh pada prinsip...", "options": ["Pengutamaan penempatan pegawai di kampung halaman pribadi", "Pemberian fasilitas rumah kedinasan gratis di pusat kota", "Pencegahan pemisahan keluarga tanpa memperhatikan formasi", "Larangan terjadinya benturan konflik kepentingan dalam penugasan", "Pemberian dispensasi jam kerja bagi pegawai di daerah perintis"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Prinsip Larangan Konflik Kepentingan Mutasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Mutasi PNS dilakukan dengan memperhatikan prinsip larangan konflik kepentingan</strong> sebagaimana diatur dalam Peraturan BKN Nomor 5 Tahun 2019.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan fondasi etika dan tata kelola bersih agar mutasi tidak menempatkan pegawai pada unit yang menimbulkan relasi kekeluargaan langsung atau kepentingan bisnis pribadi. Hal ini mencegah potensi korupsi, kolusi, dan nepotisme di tempat tugas baru. Aturan menegaskan prinsip larangan konflik kepentingan. Oleh karena itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Larangan terjadinya benturan konflik kepentingan dalam penugasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan preferensi subyektif pribadi yang tidak mengikat secara yuridis.</li><li><strong>B</strong>: Merupakan fasilitas materiil yang bergantung pada kemampuan aset negara.</li><li><strong>C</strong>: Merupakan pertimbangan sosial yang tidak boleh menabrak kebutuhan formasi.</li><li><strong>E</strong>: Merupakan pelanggaran disiplin pemenuhan jam kerja operasional dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 28) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Mutasi PNS dilakukan dengan memperhatikan prinsip • kepentingan (Peraturan BKN Nomor 5 Tahun 2019)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Ketentuan pembayaran tunjangan kemahalan bagi Pegawai ASN didasarkan pada tolak ukur...", "options": ["Tingkat kemahalan berdasarkan indeks harga di daerah masing-masing", "Jumlah tagihan kartu kredit pribadi yang dilaporkan ke dinas", "Frekuensi jam lembur yang dilakukan pegawai pada hari libur", "Banyaknya keluhan masyarakat yang masuk ke loket pelayanan", "Peringkat akreditasi almamater kelulusan pendidikan pegawai"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kriteria Pembayaran Tunjangan Kinerja dan Kemahalan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>ASN menerima <strong>tunjangan kemahalan yang dibayarkan sesuai dengan tingkat kemahalan berdasarkan indeks harga di daerah masing-masing</strong>, serta tunjangan kinerja sesuai pencapaian kinerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan dasar kompensasi geografis guna menjaga daya beli aparatur di wilayah dengan biaya hidup tinggi. Indeks harga konsumen dan tingkat kemahalan daerah menjadi rujukan objektif penetapan besaran tunjangan. Regulasi menyatakan tunjangan kemahalan dibayar berdasar indeks harga daerah. Dengan demikian, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Tingkat kemahalan berdasarkan indeks harga di daerah masing-masing</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan beban keuangan perdata individu yang tidak ditanggung anggaran negara.</li><li><strong>C</strong>: Merupakan dasar perhitungan uang lembur operasional kerja tambahan.</li><li><strong>D</strong>: Merupakan bahan evaluasi mutu pelayanan publik dan bukan tolak ukur tunjangan.</li><li><strong>E</strong>: Merupakan data kualifikasi akademik yang tidak mempengaruhi indeks wilayah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 28) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tunjangan kinerja (dibayarkan sesuai pencapaian kinerja) • kemahalan (dibayarkan sesuai dengan tingkat kemahalan berdasarkan indeks</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C4 - Analisis", "question": "Bentuk apresiasi kedinasan yang BUKAN merupakan hak penghargaan bagi PNS berprestasi kerja adalah...", "options": ["Pemberian tanda kehormatan kenegaraan resmi", "Pemberian kenaikan pangkat istimewa kedinasan", "Pemberian hak kepemilikan aset sarana negara", "Kesempatan prioritas pengembangan kompetensi", "Kesempatan menghadiri acara resmi kenegaraan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Ragam Bentuk Penghargaan Prestasi PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penghargaan dapat berupa: <strong>tanda kehormatan, kenaikan pangkat istimewa, kesempatan prioritas pengembangan kompetensi, dan/atau menghadiri acara resmi/kenegaraan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan tindakan yang bertentangan dengan hukum pengelolaan Barang Milik Negara (BMN). Seluruh sarana dan gedung kantor merupakan kekayaan milik negara yang tidak dapat dipindahtangankan menjadi hak milik privat pegawai atas nama penghargaan. Ketentuan perundang-undangan menetapkan bentuk penghargaan berupa tanda kehormatan, kenaikan pangkat, pengembangan kompetensi, dan menghadiri acara resmi. Dengan demikian, opsi C merupakan jawaban pengecualian yang tepat.</p><p>Jawaban tepat: <strong>C. Pemberian hak kepemilikan aset sarana negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemberian tanda kehormatan merupakan salah satu bentuk resmi penghargaan negara bagi PNS.</li><li><strong>B</strong>: Kenaikan pangkat istimewa merupakan hak apresiasi bagi aparatur berprestasi luar biasa.</li><li><strong>D</strong>: Prioritas pelatihan dan beasiswa merupakan bentuk penghargaan pengembangan kapasitas diri.</li><li><strong>E</strong>: Undangan menghadiri acara kenegaraan merupakan bentuk pengakuan kehormatan resmi bagi aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 28) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penghargaan berupa • tanda kehormatan • kenaikan pangkat istimewa • kesempatan prioritas untuk pengembangan kompetensi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Konsekuensi terhadap tanda kehormatan bagi PNS yang dijatuhi sanksi disiplin berat pemberhentian tidak dengan hormat adalah...", "options": ["Diberikan ganti rugi finansial setara nilai tanda jasa", "Dicabut hak memakai tanda kehormatan berdasar aturan", "Ditangguhkan pemakaiannya sampai terbit putusan baru", "Dialihkan pencatatannya ke dalam dokumen masa purna", "Dibatasi penggunaannya pada kegiatan internal keluarga"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pencabutan Hak Memakai Tanda Kehormatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS yang dijatuhi hukuman disiplin berat berupa pemberhentian tidak dengan hormat <strong>dicabut haknya untuk memakai tanda kehormatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan sanksi moral dan kehormatan yang mencabut seluruh atribut apresiasi negara dari aparatur yang melanggar sumpah jabatan. Pemecatan secara tidak hormat membatalkan kelayakan moral pemakaian bintang jasa atau satyalancana kenegaraan. Ketentuan perundang-undangan menegaskan bahwa PNS yang diberhentikan tidak dengan hormat dicabut haknya untuk memakai tanda kehormatan. Oleh sebab itu, opsi B merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. Dicabut hak memakai tanda kehormatan berdasar aturan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tanda kehormatan bersifat moral kenegaraan dan tidak memiliki nilai kompensasi finansial.</li><li><strong>C</strong>: Sanksi pemecatan tidak dengan hormat bersifat permanen dan bukan penangguhan sementara.</li><li><strong>D</strong>: PNS yang dipecat tidak hormat tidak memperoleh hak pensiun maupun pencatatan purnatugas resmi.</li><li><strong>E</strong>: Pencabutan tanda kehormatan berlaku mutlak di seluruh ruang publik maupun privat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 28) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sanksi administratif tingkat berat • tanda kehormatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Ketentuan mengenai ruang lingkup mutasi tugas dan/atau lokasi bagi Pegawai Negeri Sipil meliputi...", "options": ["Terbatas pada perpindahan antar-seksi dalam satu kantor operasional", "Diprioritaskan pada wilayah yang memiliki jarak terdekat dari domisili", "Dibatasi pada satuan kerja yang membidangi urusan administrasi umum", "Antar-instansi pusat, daerah, hingga kantor perwakilan RI luar negeri", "Memerlukan persetujuan dari seluruh rekan sejawat dalam unit kerja"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Ruang Lingkup Mobilitas Mutasi PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS dapat <strong>dimutasi tugas dan/atau lokasi dalam satu Instansi Pusat, antar-Instansi Pusat, Instansi Daerah, antar-Pusat dan Daerah, hingga ke Perwakilan NKRI di luar negeri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan perwujudan aparatur sipil sebagai perekat keutuhan Negara Kesatuan Republik Indonesia. Mobilitas penugasan terbuka fleksibel menjangkau seluruh instansi pemerintah baik di dalam negeri maupun pada kedutaan/konsulat luar negeri. Aturan menegaskan ruang lingkup mutasi hingga perwakilan RI luar negeri. Oleh karena itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Antar-instansi pusat, daerah, hingga kantor perwakilan RI luar negeri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ruang lingkup mutasi ASN sangat luas, mencakup antar-instansi pusat, daerah, hingga luar negeri.</li><li><strong>B</strong>: Penempatan mutasi didasarkan pada kebutuhan organisasi instansi, bukan pertimbangan jarak domisili.</li><li><strong>C</strong>: Mutasi berlaku untuk seluruh jenis jabatan dan unit kerja, tidak terbatas pada administrasi umum.</li><li><strong>E</strong>: Mutasi merupakan wewenang manajemen PPK dan tidak memerlukan persetujuan rekan kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 28) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Setiap PNS dapat dimutasi tugas dan/atau lokasi dalam 1 (satu) Instansi Pusat • ke Perwakilan Negara Kesatuan Republik</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan mutasi penugasan PNS dalam lingkungan internal satu Instansi Pusat atau Instansi Daerah merupakan wewenang...", "options": ["Pejabat Pembina Kepegawaian pada instansi bersangkutan", "Kepala kantor perwakilan kementerian keuangan daerah", "Koordinator perhimpunan serikat pekerja honorer kementerian", "Ketua komite tetap dewan penasihat tata kelola swasta", "Kepala Badan Kepegawaian dan Pengembangan SDM Daerah"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Wewenang Mutasi Internal Satu Instansi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Mutasi PNS <strong>dalam satu Instansi Pusat atau Instansi Daerah dilakukan oleh Pejabat Pembina Kepegawaian</strong> (PPK).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan kewenangan otonom menteri, pimpinan lembaga, atau kepala daerah dalam mengatur sebaran pegawai di unit kerjanya sendiri. Rotasi internal ditujukan mengoptimalkan penempatan kompetensi pegawai sesuai beban kerja. Regulasi menetapkan kewenangan ini berada di tangan Pejabat Pembina Kepegawaian. Dengan demikian, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Pejabat Pembina Kepegawaian pada instansi bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan instansi vertikal kementerian keuangan di wilayah provinsi/kota.</li><li><strong>C</strong>: Merupakan kelompok pekerja lepas di luar jalur komando pembina kepegawaian.</li><li><strong>D</strong>: Merupakan entitas nonpemerintah tanpa mandat yuridis tata kelola aparatur.</li><li><strong>E</strong>: Kepala BKP-SDM merupakan pelaksana teknis operasional kepegawaian dan bukan pemegang kewenangan PPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 28) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Mutasi PNS • dalam satu Instansi Pusat atau Instansi Daerah dilakukan oleh Pejabat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Nilai-nilai kualitatif yang menjadi prasyarat pertimbangan utama dalam pemberian penghargaan kedinasan bagi PNS adalah...", "options": ["Masa pengabdian tugas dan jumlah kehadiran apel dinas", "Penguasaan bahasa asing dan frekuensi pelatihan luar", "Ketaatan administratif dan keterlibatan panitia kerja", "Kelulusan jenjang pendidikan dan kepatuhan pelaporan", "Kesetiaan, pengabdian, kecakapan, jujur, dan prestasi"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Syarat Kualitatif Pemberian Penghargaan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Penghargaan diberikan berdasarkan pertimbangan: <strong>kesetiaan, pengabdian, kecakapan, kejujuran, disiplin, dan prestasi kerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan pilar karakter moralitas dan kinerja unggul aparatur negara dalam pemberian apresiasi kenegaraan. Penghargaan negara dianugerahkan kepada pegawai yang membuktikan integritas pengabdian, loyalitas kebangsaan, serta prestasi nyata dalam pelayanan publik. Ketentuan regulasi menetapkan pertimbangan penghargaan mencakup kesetiaan, pengabdian, kecakapan, kejujuran, disiplin, dan prestasi. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Kesetiaan, pengabdian, kecakapan, jujur, dan prestasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masa pengabdian dan kehadiran merupakan syarat umum disiplin dan bukan tolok ukur utama.</li><li><strong>B</strong>: Bahasa asing dan frekuensi diklat merupakan indikator kompetensi dan bukan kriteria pokok.</li><li><strong>C</strong>: Ketaatan administratif dan kepanitiaan merupakan tugas rutin sehari-hari abdi negara.</li><li><strong>D</strong>: Kelulusan pendidikan merupakan kualifikasi formal dan bukan pertimbangan kelayakan penghargaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 28) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS yang telah menunjukkan kesetiaan, pengabdian, kecakapan, kejujuran • kedisiplinan, dan prestasi kerja dalam melaksanakan tugasnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tujuan pokok dibentuknya wadah Korps Profesi Pegawai ASN Republik Indonesia adalah...", "options": ["Mengorganisir aksi mogok kerja massal saat tuntutan upah ditolak", "Menghimpun dana iuran politik guna mendukung calon kepala daerah", "Membentuk badan usaha komersial yang memonopoli proyek kementerian", "Menjaga kode etik profesi, standar pelayanan, dan pemersatu bangsa", "Menyediakan layanan pinjaman dana berbunga tinggi kepada anggota"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tujuan Wadah Korps Profesi ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Korps profesi Pegawai ASN Republik Indonesia bertujuan <strong>menjaga kode etik profesi dan standar pelayanan profesi ASN, dan mewujudkan jiwa korps ASN sebagai pemersatu bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan mandat luhur korps profesi ASN yang terbebas dari politik praktis dan kepentingan komersial. Wadah korps membina etika kerja, memastikan mutu layanan publik seragam di seluruh tanah air, dan merawat soliditas nasional. Aturan menegaskan tujuan korps adalah menjaga kode etik, standar pelayanan, dan pemersatu bangsa. Dengan demikian, opsi D adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>D. Menjaga kode etik profesi, standar pelayanan, dan pemersatu bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan aksi buruh industri yang dilarang keras bagi aparatur negara.</li><li><strong>B</strong>: Merupakan pelanggaran berat asas netralitas yang diancam hukuman pemecatan.</li><li><strong>C</strong>: Merupakan praktik monopoli terlarang yang melanggar hukum persaingan usaha.</li><li><strong>E</strong>: Merupakan aktivitas rentenir yang bertentangan dengan kesejahteraan korps.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 29) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pegawai ASN berhimpun dalam wadah korps profesi Pegawai ASN Republik • menjaga kode etik profesi dan standar pelayanan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Karakteristik penyelenggaraan Sistem Informasi ASN yang diwajibkan oleh undang-undang adalah...", "options": ["Diselenggarakan nasional dan terintegrasi antar-instansi", "Dikelola mandiri oleh masing-masing unit kerja pelaksana", "Bersifat tertutup tanpa keterhubungan jaringan kementerian", "Dikelola secara parsial mengikuti kebijakan pimpinan satker", "Dibatasi penerapannya pada pengelolaan data pejabat tinggi"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Karakteristik Sistem Informasi ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Sistem Informasi ASN <strong>diselenggarakan secara nasional dan terintegrasi antar-Instansi Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan prinsip keterpaduan basis data aparatur sipil negara secara nasional (*single source of truth*). Integrasi data antarinstitusi mempermudah interoperabilitas layanan kepegawaian, perencanaan penempatan talenta, serta monitoring kinerja aparatur secara terpadu. Ketentuan undang-undang menetapkan Sistem Informasi ASN diselenggarakan secara nasional dan terintegrasi antar-instansi. Dengan demikian, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Diselenggarakan nasional dan terintegrasi antar-instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pengelolaan mandiri yang terfragmentasi bertentangan dengan asas integrasi data nasional.</li><li><strong>C</strong>: Sistem informasi dirancang berbasis jaringan interoperabilitas dan bukan sistem tertutup.</li><li><strong>D</strong>: Pengelolaan parsial menghambat koordinasi dan validitas basis data kepegawaian nasional.</li><li><strong>E</strong>: Sistem informasi mencakup seluruh data pegawai ASN dari jenjang terendah hingga tertinggi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 29) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sistem Informasi ASN • secara nasional dan terintegrasi antar-Instansi Pemerintah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C4 - Analisis", "question": "Data yang BUKAN merupakan bagian dari sepuluh cakupan data resmi dalam Sistem Informasi ASN adalah...", "options": ["Data riwayat hidup dan pendidikan formal/nonformal", "Riwayat jabatan, kepangkatan, serta riwayat penggajian", "Daftar preferensi pilihan politik dan nama partai binaan", "Daftar penilaian prestasi kerja serta sertifikat kompetensi", "Riwayat tanda penghargaan, tanda jasa, dan tanda kehormatan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Muatan Data Sistem Informasi ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Sistem Informasi ASN memuat: <strong>riwayat hidup, pendidikan, jabatan/pangkat, tanda jasa/kehormatan, organisasi, gaji, diklat, prestasi kerja, SK, dan kompetensi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan data politik praktis yang dilarang berada dalam rekam jejak aparatur sipil yang netral. Sistem Informasi ASN murni merekam riwayat profesionalisme, kualifikasi, rekam jejak kedinasan, dan kompetensi pegawai. Dengan demikian, opsi C adalah pilihan jawaban pengecualian yang benar.</p><p>Jawaban tepat: <strong>C. Daftar preferensi pilihan politik dan nama partai binaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan data dasar identitas dan jenjang akademik aparatur sipil.</li><li><strong>B</strong>: Merupakan rekam jejak karier struktural/fungsional dan hak finansial dinas.</li><li><strong>D</strong>: Merupakan catatan kinerja tahunan dan bukti penguasaan keahlian teknis.</li><li><strong>E</strong>: Merupakan portofolio apresiasi negara atas kesetiaan dan prestasi dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 29) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sistem Informasi ASN memuat seluruh informasi dan data pegawai ASN, yang • riwayat jabatan dan kepangkatan;</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Upaya administratif yang dapat ditempuh dalam rangka penyelesaian sengketa pegawai ASN terdiri atas...", "options": ["Gugatan perdata ganti rugi dan mediasi di kepolisian wilayah", "Somasi terbuka di media massa dan dengar pendapat parlemen", "Permohonan uji materi undang-undang ke mahkamah konstitusi", "Tuntutan pembubaran organisasi kementerian di forum publik", "Keberatan administratif dan banding administratif"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Jenis Upaya Administratif Sengketa ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Sengketa pegawai ASN diselesaikan melalui <strong>upaya administratif, yang terdiri dari keberatan dan banding administratif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan dua pintu hukum penyelesaian sengketa internal sebelum perkara dapat dibawa ke Pengadilan Tata Usaha Negara (PTUN). Pegawai wajib menuntaskan jalur keberatan dan banding sebagai wujud kepatuhan pada hukum administrasi negara. Aturan menyatakan upaya administratif terdiri dari keberatan dan banding administratif. Oleh karena itu, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Keberatan administratif dan banding administratif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan jalur peradilan umum yang tidak menangani sengketa disiplin ASN.</li><li><strong>B</strong>: Merupakan aksi publikasi media yang melanggar etika tata kelola aparatur.</li><li><strong>C</strong>: Merupakan pengujian norma undang-undang dan bukan sengketa kasuistis pegawai.</li><li><strong>D</strong>: Merupakan tindakan ekstrem yang tidak diakui dalam sistem hukum administrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 29) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sengketa pegawai ASN diselesaikan melalui upaya administratif • keberatan dan banding administratif</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang PNS dijatuhi sanksi hukuman disiplin oleh Kepala Kantor Pelayanan. Pengajuan surat keberatan administratif ditujukan kepada...", "options": ["Ketua dewan perwakilan rakyat daerah tingkat kota", "Atasan pejabat yang berwenang menghukum bersangkutan", "Pimpinan kantor lembaga bantuan hukum swasta setempat", "Komandan komando distrik militer wilayah penugasan", "Kepala kepolisian resor wilayah hukum setempat"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tujuan Pengajuan Keberatan Administratif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Keberatan diajukan secara tertulis kepada atasan pejabat yang berwenang menghukum</strong> dengan memuat alasan keberatan dan tembusannya disampaikan kepada pejabat yang berwenang menghukum.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jalur pengujian hierarkis internal atas keputusan hukuman disiplin. Atasan langsung pejabat penghukum bertindak memeriksa ulang fakta, bukti, dan prosedur penjatuhan sanksi guna menjamin keadilan bagi pegawai. Aturan menyebutkan keberatan diajukan kepada atasan pejabat yang berwenang menghukum. Dengan demikian, opsi B adalah alur yang benar.</p><p>Jawaban tepat: <strong>B. Atasan pejabat yang berwenang menghukum bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan lembaga legislatif daerah yang tidak berwenang mengadili disiplin.</li><li><strong>C</strong>: Merupakan organisasi advokat swasta di luar hierarki penjatuhan sanksi sipil.</li><li><strong>D</strong>: Merupakan pimpinan teritorial tentara di luar sistem peradilan disiplin ASN.</li><li><strong>E</strong>: Merupakan penegak hukum kepolisian yang tidak memproses keberatan internal dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 29) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keberatan diajukan secara tertulis kepada • atasan pejabat yang berwenang menghukum</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Permohonan banding administratif terhadap sanksi hukuman disiplin pegawai ASN diajukan kepada...", "options": ["Dewan kehormatan partai politik pemenang pemilu", "Majelis perwakilan pedagang pasar tradisional", "Lembaga sensor film dan penyiaran nasional", "Badan pertimbangan ASN tingkat pusat", "Panitia ad-hoc pembentukan daerah pemekaran"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Lembaga Penerima Banding Administratif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Dalam mekanisme sengketa kepegawaian, <strong>banding administratif diajukan kepada badan pertimbangan ASN</strong> (dahulu BAPEK).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan kuasi-peradilan kepegawaian independen yang dibentuk untuk memeriksa banding sanksi berat (seperti pemberhentian). Badan Pertimbangan ASN menilai objektivitas putusan PPK sebelum pegawai membawa perkara ke tingkat pengadilan peradilan tata usaha negara. Aturan menegaskan banding diajukan ke badan pertimbangan ASN. Oleh karena itu, opsi D adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Badan pertimbangan ASN tingkat pusat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan partai politik partisan yang dilarang mencampuri urusan ASN.</li><li><strong>B</strong>: Merupakan paguyuban pedagang komersial di luar ranah aparatur publik.</li><li><strong>C</strong>: Merupakan komisi penyiaran di luar sistem sengketa manajemen pegawai.</li><li><strong>E</strong>: Merupakan kepanitiaan otonomi daerah yang tidak mengadili perkara disiplin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 29) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Adapun banding diajukan kepada • badan pertimbangan ASN</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C4 - Analisis", "question": "Peristiwa yang BUKAN merupakan dasar pemberhentian Pegawai Negeri Sipil dengan hormat adalah...", "options": ["Permohonan pengunduran diri atas permintaan sendiri", "Mencapai batas usia pensiun sesuai peraturan hukum", "Terbukti melakukan penyelewengan terhadap ideologi Pancasila", "Perampingan organisasi atau penataan kebijakan pemerintah", "Dinyatakan tidak cakap jasmani dan rohani oleh tim medis"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Penyebab Pemberhentian PNS dengan Hormat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS diberhentikan dengan hormat karena: <strong>permintaan sendiri, mencapai BUP, perampingan organisasi, tidak cakap jasmani/rohani, atau meninggal dunia/tewas/hilang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan bentuk pengkhianatan dasar negara yang berakibat pada pemberhentian TIDAK dengan hormat. Seluruh alasan pemberhentian dengan hormat berakar pada faktor alamiah, batas usia, permohonan sukarela, atau kebijakan restrukturisasi negara. Dengan demikian, opsi C adalah pilihan jawaban pengecualian yang benar.</p><p>Jawaban tepat: <strong>C. Terbukti melakukan penyelewengan terhadap ideologi Pancasila</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan hak inisiatif pegawai untuk mengakhiri masa tugas kedinasan.</li><li><strong>B</strong>: Merupakan akhir masa pengabdian normal yang dihargai dengan jaminan pensiun.</li><li><strong>D</strong>: Merupakan likuidasi instansi yang diselesaikan dengan pemberhentian terhormat.</li><li><strong>E</strong>: Merupakan kondisi medis permanen yang ditutup dengan pemberhentian terhormat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 29) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS diberhentikan dengan hormat karena • mencapai batas usia pensiun • perampingan organisasi atau kebijakan pemerintah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "PNS yang dipidana penjara paling singkat dua tahun atas perbuatan pidana TIDAK berencana yang telah berkekuatan hukum tetap...", "options": ["Otomatis diangkat menjadi pimpinan tinggi madya di kementerian", "Wajib dijatuhi hukuman mati oleh pejabat pembina kepegawaian", "Dapat diberhentikan dengan hormat atau tidak diberhentikan", "Diberikan kenaikan pangkat istimewa dua tingkat lebih tinggi", "Dilarang menerima kunjungan keluarga selama masa pemidanaan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Diskresi Hukuman Pidana Tidak Berencana Minimal 2 Tahun</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS <strong>dapat diberhentikan dengan hormat atau tidak diberhentikan</strong> karena dihukum penjara berdasarkan putusan pengadilan berkekuatan hukum tetap karena tindak pidana <strong>paling singkat 2 tahun dan dilakukan tidak berencana</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan diskresi hukum yang mempertimbangkan tidak adanya unsur kesengajaan (*dolus*) dalam perbuatan pidana (seperti kecelakaan lalu lintas). Pejabat Pembina Kepegawaian dapat menimbang apakah pegawai diberhentikan dengan hormat atau tetap dipertahankan. Regulasi menyatakan 'dapat diberhentikan dengan hormat atau tidak diberhentikan'. Oleh karena itu, opsi C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Dapat diberhentikan dengan hormat atau tidak diberhentikan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan promosi fiktif yang tidak masuk akal bagi terpidana kasus pidana.</li><li><strong>B</strong>: Merupakan vonis pidana pokok terberat yang hanya dijatuhkan hakim pengadilan.</li><li><strong>D</strong>: Merupakan penghargaan istimewa yang bertentangan dengan fakta hukuman pidana.</li><li><strong>E</strong>: Merupakan tata tertib lembaga pemasyarakatan di luar ranah status PNS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 29) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS dapat diberhentikan dengan hormat atau tidak diberhentikan • dihukum penjara berdasarkan putusan pengadilan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "PNS yang dinyatakan tewas atau hilang dalam menjalankan tugas operasi kedinasan negara diberhentikan secara...", "options": ["Pemberhentian sementara menunggu kepastian masa tugas", "Dengan hormat atas jasa dan pengabdian kepada negara", "Pemberhentian tidak atas permohonan sendiri berjangka", "Pemberhentian administratif dengan pemutusan hak tunjangan", "Pemberhentian jabatan definitif dengan status pegawai nonaktif"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemberhentian PNS Karena Gugur Tewas Hilang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS yang tewas atau dinyatakan hilang dalam menjalankan tugas <strong>diberhentikan dengan hormat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan penghormatan resmi negara atas pengorbanan jiwa raga abdi negara dalam menjalankan tugas kedinasan. Gugur atau dinyatakan tewas saat melaksanakan mandat kenegaraan diselesaikan melalui keputusan pemberhentian dengan hormat disertai hak-hak pensiun dan jaminan duka bagi ahli waris. Ketentuan perundang-undangan menegaskan pemberhentian tersebut dilakukan dengan hormat. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Dengan hormat atas jasa dan pengabdian kepada negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemberhentian sementara berlaku bagi PNS yang ditahan aparat hukum atau menjadi komisioner.</li><li><strong>C</strong>: Bukan penamaan yuridis bagi aparatur yang gugur dalam menjalankan tugas negara.</li><li><strong>D</strong>: Aparatur yang tewas berhak memperoleh jaminan perlindungan duka penuh dari negara.</li><li><strong>E</strong>: Status pegawai nonaktif tidak berlaku bagi pegawai yang telah dinyatakan tewas dalam tugas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 29) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS diberhentikan dengan hormat karena • meninggal dunia, tewas, atau hilang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Urgensi utama dibangunnya Sistem Informasi ASN yang terintegrasi secara nasional adalah menjamin...", "options": ["Keseragaman sistem pembukuan keuangan kas operasional", "Standarisasi pengadaan sarana logistik kantor kementerian", "Keterbukaan informasi data rahasia dokumen penyelidikan", "Efisiensi, efektivitas, dan akurasi keputusan manajemen", "Kemudahan pertukaran informasi kegiatan sosial aparatur"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Urgensi Sistem Informasi Manajemen ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Sistem Informasi ASN bertujuan untuk <strong>menjamin efisiensi, efektivitas, dan akurasi pengambilan keputusan dalam Manajemen ASN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan sasaran strategis dari transformasi digital kepegawaian nasional. Basis data yang terintegrasi dan akurat mencegah terjadinya kekeliruan penempatan pegawai, mempercepat pemrosesan mutasi dan pensiun, serta menjamin keandalan keputusan pimpinan. Ketentuan hukum menetapkan tujuan sistem informasi adalah menjamin efisiensi, efektivitas, dan akurasi pengambilan keputusan. Oleh sebab itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Efisiensi, efektivitas, dan akurasi keputusan manajemen</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembukuan kas operasional merupakan domain aplikasi perbendaharaan pada Kementerian Keuangan.</li><li><strong>B</strong>: Pengadaan logistik kantor dikelola melalui sistem pengadaan barang dan jasa pemerintah.</li><li><strong>C</strong>: Dokumen penyelidikan merupakan rahasia penegakan hukum dan bukan fungsi sistem informasi ASN.</li><li><strong>E</strong>: Kegiatan sosial kemasyarakatan bukan sasaran utama pembangunan pangkalan data kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 29) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>efisiensi, efektivitas, dan akurasi pengambilan keputusan dalam • diperlukan Sistem Informasi ASN</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "PNS dapat diberhentikan dengan hormat tidak atas permohonan sendiri apabila tidak melapor pasca tugas belajar dalam waktu paling lama...", "options": ["Tiga hari kerja sejak tanggal berakhirnya tugas belajar", "Tujuh hari kerja sejak tanggal berakhirnya tugas belajar", "Sepuluh hari kerja sesudah berakhirnya masa tugas belajar", "Tiga puluh hari kalender sejak selesai tugas belajar", "Lima belas hari kerja sejak berakhirnya masa tugas belajar"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Batas Waktu Lapor Pasca Tugas Belajar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS diberhentikan dengan hormat tidak atas permintaan sendiri apabila <strong>tidak melapor kepada PPK dalam jangka waktu paling lama 15 (lima belas) hari kerja setelah berakhirnya masa tugas belajar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan batas waktu kepatuhan administratif bagi aparatur yang telah menyelesaikan tugas belajar. Kegagalan melapor kembali kepada Pejabat Pembina Kepegawaian dalam batas 15 hari kerja mencerminkan kelalaian tugas berat yang berakibat pada pemutusan hubungan kerja terhormat tidak atas permintaan sendiri. Ketentuan perundang-undangan menetapkan batas waktu pelaporan paling lama 15 hari kerja. Dengan demikian, opsi E merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Lima belas hari kerja sejak berakhirnya masa tugas belajar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tenggat tiga hari kerja terlalu singkat untuk penyelesaian administrasi kepulangan studi.</li><li><strong>B</strong>: Tenggat tujuh hari kerja bukan batas limitatif yang diatur dalam ketentuan tugas belajar PNS.</li><li><strong>C</strong>: Tenggat sepuluh hari kerja bukan batas waktu yuridis pemberhentian pasca tugas belajar.</li><li><strong>D</strong>: Ketentuan kepegawaian menggunakan satuan hari kerja dan bukan tiga puluh hari kalender.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 30) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS juga dapat diberhentikan dengan hormat tidak atas permintaan sendiri • tidak melapor dalam jangka waktu paling lama 15 (lima belas) hari kerja sejak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat pengawas terbukti menggunakan ijazah palsu untuk kenaikan pangkat penyesuaian ijazah. Sanksi administratif yang dijatuhkan adalah...", "options": ["Pemberian teguran tertulis dan pemotongan uang makan dinas", "Pemberhentian dengan hormat tidak atas permintaan sendiri", "Penurunan jenjang kepangkatan satu tingkat selama enam bulan", "Pemindahan meja tugas ke bagian pelayanan loket perizinan", "Penundaan pembayaran tunjangan kinerja selama satu triwulan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Sanksi Penggunaan Ijazah Palsu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS diberhentikan <strong>dengan hormat tidak atas permintaan sendiri</strong> apabila <strong>terbukti menggunakan ijazah palsu dalam pembinaan kepegawaian</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan sanksi yuridis atas pelanggaran integritas akademik serius dalam pembinaan karier aparatur. Penggunaan dokumen pendidikan palsu merusak tatanan kualifikasi meritokrasi birokrasi, sehingga yang bersangkutan dihentikan kariernya secara tidak atas permintaan sendiri. Aturan menetapkan sanksinya pemberhentian dengan hormat tidak atas permintaan sendiri. Oleh karena itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Pemberhentian dengan hormat tidak atas permintaan sendiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan sanksi disiplin ringan yang tidak sebanding dengan pemalsuan ijazah.</li><li><strong>C</strong>: Merupakan penurunan pangkat sementara yang tidak menghapus cacat integritas.</li><li><strong>D</strong>: Merupakan mutasi internal tanpa dampak pembatalan status kepegawaian.</li><li><strong>E</strong>: Merupakan pemotongan tunjangan sementara yang terlalu ringan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 30) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS juga dapat diberhentikan dengan hormat tidak atas permintaan sendiri • terbukti menggunakan ijazah palsu dalam pembinaan kepegawaian</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "PNS yang terbukti melakukan penyelewengan terhadap ideologi Pancasila dan UUD 1945 dijatuhi sanksi kepegawaian berupa...", "options": ["Teguran tertulis disertai penugasan kembali ke daerah", "Pembebasan dari jabatan struktural menjadi fungsional", "Pemberhentian dengan hormat tidak atas permintaan sendiri", "Pemberhentian tidak dengan hormat sebagai aparatur negara", "Pengurangan tunjangan kinerja sebesar lima puluh persen"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemberhentian Tidak dengan Hormat Ideologi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS <strong>diberhentikan tidak dengan hormat</strong> apabila terbukti <strong>melakukan penyelewengan terhadap Pancasila dan Undang-Undang Dasar Negara Republik Indonesia Tahun 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Kesetiaan pada Pancasila dan UUD 1945 adalah kewajiban fundamental yang tidak dapat ditawar bagi seluruh aparatur sipil negara. Pelanggaran berupa penyelewengan terhadap ideologi negara berakibat sanksi terberat yaitu pemecatan tidak dengan hormat.</p><p>Jawaban tepat: <strong>D. Pemberhentian tidak dengan hormat sebagai aparatur negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Teguran tertulis hanya diperuntukkan bagi pelanggaran disiplin ringan.</li><li><strong>B</strong>: Pembebasan jabatan adalah sanksi hukuman disiplin kategori berat.</li><li><strong>C</strong>: Pemberhentian dengan hormat diberikan jika tidak ada unsur makar/pidana.</li><li><strong>E</strong>: Pemotongan tunjangan kinerja diterapkan pada disiplin tingkat sedang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 30) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>diberhentikan tidak dengan hormat • penyelewengan terhadap Pancasila dan UUD 1945</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Seorang PNS resmi ditahan oleh kejaksaan karena berstatus tersangka perkara dugaan korupsi. Keputusan administratif PPK terhadapnya adalah...", "options": ["Diberhentikan sementara dari statusnya sebagai pegawai negeri sipil", "Diberikan kenaikan pangkat kehormatan atas dedikasi kerja kantor", "Diberhentikan permanen tanpa menunggu putusan pengadilan berkekuatan tetap", "Diberikan bantuan dana taktis dinas untuk membayar uang penjaminan", "Diperintahkan tetap memimpin rapat satker dari dalam tahanan kejaksaan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemberhentian Sementara Tersangka Pidana</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Menurut Pasal 53 UU Nomor 20 Tahun 2023, PNS <strong>diberhentikan sementara</strong> apabila <strong>ditahan karena menjadi tersangka tindak pidana</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan status penonaktifan sementara demi menjunjung asas praduga tak bersalah (*presumption of innocence*) sekaligus menjaga integritas kedinasan. Selama masa penahanan, pegawai tidak dapat menjalankan tugas sehingga dinonaktifkan sementara menunggu putusan hakim yang berkekuatan hukum tetap (*inkracht*). Aturan menyatakan statusnya diberhentikan sementara. Oleh karena itu, opsi A adalah keputusan yang benar.</p><p>Jawaban tepat: <strong>A. Diberhentikan sementara dari statusnya sebagai pegawai negeri sipil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan promosi pangkat yang mustahil diberikan kepada tersangka tahanan.</li><li><strong>C</strong>: Merupakan pemecatan dini yang melanggar asas praduga tak bersalah sebelum inkracht.</li><li><strong>D</strong>: Merupakan penyalahgunaan dana anggaran negara untuk kepentingan pidana privat.</li><li><strong>E</strong>: Merupakan tindakan melanggar hukum tata tertib penahanan lembaga peradilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 30) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS diberhentikan sementara apabila • ditahan karena menjadi tersangka tindak pidana (Pasal 53 UU Nomor 20</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pihak yang memiliki kewenangan legal menerbitkan surat pengaktifan kembali PNS yang sebelumnya diberhentikan sementara adalah...", "options": ["Kepala kantor pos pelayanan pengiriman barang daerah", "Pimpinan organisasi lembaga swadaya pemerhati hukum", "Pejabat Pembina Kepegawaian pada instansi pemerintah", "Ketua komite penasihat perbankan milik swasta nasional", "Koordinator tenaga alih daya pada kantor sekretariat"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pejabat Pengaktif Kembali PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Pengaktifan kembali PNS yang diberhentikan sementara dilakukan oleh Pejabat Pembina Kepegawaian</strong> (PPK).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan pemegang kekuasaan kepegawaian tertinggi di instansi pemerintah yang memiliki otoritas memulihkan status kedinasan aparatur. Berdasarkan surat putusan pengadilan bebas atau berakhirnya masa penugasan negara, PPK menetapkan surat keputusan pengaktifan kembali. Aturan menyebutkan wewenang berada pada Pejabat Pembina Kepegawaian. Dengan demikian, opsi C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Pejabat Pembina Kepegawaian pada instansi pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan perusahaan logistik pos tanpa kewenangan administrasi kepegawaian.</li><li><strong>B</strong>: Merupakan entitas swasta nonpemerintah di luar struktur aparatur sipil.</li><li><strong>D</strong>: Merupakan komite perbankan swasta yang tidak mengelola status PNS.</li><li><strong>E</strong>: Merupakan petugas tenaga kerja kontrak di luar formasi aparatur sipil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 30) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengaktifan kembali PNS yang diberhentikan sementara • dilakukan oleh Pejabat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Hasil verifikasi faktual KPU mendapati seorang PNS aktif terdaftar sebagai bendahara dewan pimpinan cabang partai politik. Sanksinya adalah...", "options": ["Pemberian teguran lisan tertutup di ruangan biro kepegawaian", "Pemindahan wilayah kerja ke instansi vertikal di kabupaten lain", "Pemberhentian dengan hormat disertai hak uang tunggu pensiun", "Pemberian dispensasi khusus selama masa verifikasi peserta pemilu", "Pemberhentian tidak dengan hormat sebagai pegawai negeri sipil"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemberhentian Tidak dengan Hormat Parpol</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS <strong>diberhentikan tidak dengan hormat</strong> apabila terbukti <strong>menjadi anggota dan/atau pengurus partai politik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan ketentuan hukum tegas untuk menjaga netralitas aparatur sipil dari kontaminasi politik kepartaian. PNS dilarang merangkap sebagai anggota apalagi pengurus partai, dan pelanggaran atas asas ini berakibat pemutusan hubungan kerja tidak hormat. Aturan menegaskan sanksinya pemberhentian tidak dengan hormat. Oleh karena itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Pemberhentian tidak dengan hormat sebagai pegawai negeri sipil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan teguran ringan yang tidak sebanding dengan pelanggaran netralitas parpol.</li><li><strong>B</strong>: Merupakan mutasi yang tidak menghapus cacat netralitas keanggotaan partai.</li><li><strong>C</strong>: Merupakan terminasi kerja terhormat yang tidak sah diberikan bagi pengurus partai.</li><li><strong>D</strong>: Merupakan pembiaran ilegal yang merusak netralitas penyelenggara negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 30) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS diberhentikan tidak dengan hormat apabila • menjadi anggota dan/atau pengurus partai politik</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Seorang PNS yang telah disetujui untuk menjalani cuti di luar tanggungan negara (CLTN) secara hukum berstatus...", "options": ["Diberhentikan tetap dan dicabut hak kepangkatan dinas", "Diberhentikan dari jabatannya selama masa cuti berjalan", "Tetap memegang jabatan definitif tanpa pemotongan tunjangan", "Wajib menjalankan tugas kedinasan harian pada satuan kerja", "Diberikan penugasan khusus pada instansi pemerintah daerah"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Status PNS Menjalani CLTN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS yang menjalankan cuti di luar tanggungan negara <strong>diberhentikan dari jabatannya</strong> dan jabatannya dapat diisi oleh PNS lain.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan status kepegawaian resmi bagi pegawai yang memanfaatkan cuti di luar tanggungan negara. Pelepasan jabatan dilakukan agar posisi yang ditinggalkan dapat diisi oleh aparatur lain demi kelancaran operasional birokrasi, sementara status PNS tetap melekat tanpa penerimaan penghasilan negara. Ketentuan regulasi menetapkan PNS yang menjalani CLTN diberhentikan dari jabatannya. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Diberhentikan dari jabatannya selama masa cuti berjalan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PNS yang menjalani CLTN tidak diberhentikan tetap dari status kepegawaian korps aparatur.</li><li><strong>C</strong>: Selama masa CLTN, jabatan pegawai dilepaskan dan hak penghasilan dari kas negara dihentikan.</li><li><strong>D</strong>: Pegawai dibebaskan sepenuhnya dari kewajiban tugas dinas sehari-hari selama masa cuti.</li><li><strong>E</strong>: CLTN merupakan cuti kepentingan pribadi dan bukan mekanisme penugasan khusus instansi pemda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 30) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS diberhentikan sementara apabila • menjalani cuti di luar tanggungan negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C4 - Analisis", "question": "Kondisi yang BUKAN merupakan dasar hukum pemberian hak jaminan pensiun bagi PNS adalah...", "options": ["Pegawai meninggal dunia saat masih berstatus dinas aktif", "Mencapai batas usia pensiun resmi sesuai jenjang jabatan", "Atas permintaan sendiri dengan memenuhi usia dan masa kerja tertentu", "Diberhentikan tidak dengan hormat karena menjadi pengurus partai politik", "Perampingan organisasi dengan masa kerja dan usia pensiun tertentu"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kondisi Pemberian Jaminan Pensiun PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Berdasarkan UU Nomor 20 Tahun 2023, PNS diberikan jaminan pensiun apabila: <strong>meninggal dunia, atas permintaan sendiri dengan usia dan masa kerja tertentu, atau mencapai batas usia pensiun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan pemberhentian dengan sanksi tercela yang menghapuskan hak jaminan pensiun aparatur sipil negara. Hak jaminan pensiun adalah bentuk penghargaan atas pengabdian purna tugas yang bersih hingga akhir masa kerja. Oleh karena itu, opsi D adalah pilihan jawaban pengecualian yang benar.</p><p>Jawaban tepat: <strong>D. Diberhentikan tidak dengan hormat karena menjadi pengurus partai politik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan peristiwa kematian aktif yang hak pensiunnya diteruskan ke ahli waris.</li><li><strong>B</strong>: Merupakan kondisi normal purnatugas kedinasan penerima jaminan pensiun.</li><li><strong>C</strong>: Merupakan hak pensiun dini sukarela bagi pegawai yang memenuhi syarat.</li><li><strong>E</strong>: Merupakan kompensasi pensiun akibat kebijakan rasionalisasi kelembagaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 30) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS diberikan jaminan pensiun (UU Nomor 20 Tahun 2023) apabila • mencapai batas usia pensiun • meninggal dunia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "PNS yang dijatuhi pidana penjara KURANG dari dua tahun karena tindak pidana berencana berkekuatan hukum tetap diberhentikan secara...", "options": ["Dengan hormat tidak atas permintaan sendiri sebagai pegawai", "Dengan hormat atas permintaan pribadi dengan uang pesangon", "Diberikan kenaikan pangkat penyesuaian masa tahanan pidana", "Pemberhentian sementara sambil menunggu tawaran jabatan baru", "Tetap dipertahankan aktif berdinas tanpa catatan hukuman"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Sanksi Pidana Berencana Kurang dari 2 Tahun</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS diberhentikan <strong>dengan hormat tidak atas permintaan sendiri</strong> apabila <strong>dipidana dengan pidana penjara kurang dari 2 (dua) tahun berdasarkan putusan pengadilan berkekuatan hukum tetap karena tindak pidana dengan berencana</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan konstruksi yuridis Pasal 87 PP 11/2017 stdd PP 17/2020. Kendati hukuman penjara di bawah dua tahun, unsur kesengajaan/perencanaan tindak pidana menggugurkan kelayakan moral pegawai sehingga diberhentikan dengan hormat tidak atas permintaan sendiri. Regulasi menyatakan sanksinya diberhentikan dengan hormat tidak atas permintaan sendiri. Dengan demikian, opsi A adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Dengan hormat tidak atas permintaan sendiri sebagai pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan permohonan sukarela yang tidak relevan bagi status terpidana pidana.</li><li><strong>C</strong>: Merupakan kenaikan pangkat fiktif yang melanggar logika hukum kepegawaian.</li><li><strong>D</strong>: Merupakan status sementara yang keliru karena perkara telah inkracht.</li><li><strong>E</strong>: Merupakan pemutihan rekam jejak kriminal yang dilarang dalam sistem merit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 30) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS juga dapat diberhentikan dengan hormat tidak atas permintaan sendiri • dipidana dengan pidana penjara kurang dari 2 (dua) tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Seorang pejabat struktural PNS terpilih menjadi anggota komisioner lembaga nonstruktural (LNS). Status kepegawaiannya adalah...", "options": ["Diberikan kewenangan merangkap dua jabatan struktural sekaligus", "Diberhentikan permanen dengan pencabutan nomor induk kepegawaian", "Diberhentikan sementara dari jabatannya selama menjadi anggota LNS", "Dialihkan menjadi pegawai swasta tetap pada yayasan komersial", "Diharuskan mengundurkan diri permanen dari status aparatur negara"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Status PNS Diangkat Anggota LNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS <strong>diberhentikan sementara apabila diangkat menjadi komisioner atau anggota lembaga nonstruktural</strong> (LNS).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan aturan penugasan penunjang lembaga nonstruktural independen (seperti Komisi Informasi Publik atau Ombudsman). Jabatan karier sipil diistirahatkan sementara agar pegawai fokus bertugas di LNS tanpa merangkap komando. Aturan menyebutkan statusnya diberhentikan sementara. Oleh karena itu, opsi C adalah keputusan yang benar.</p><p>Jawaban tepat: <strong>C. Diberhentikan sementara dari jabatannya selama menjadi anggota LNS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan rangkap komando yang mencederai independensi lembaga nonstruktural.</li><li><strong>B</strong>: Merupakan penghapusan status sewenang-wenang yang melanggar hak pegawai.</li><li><strong>D</strong>: Merupakan pengalihan korporasi swasta di luar status pengabdian LNS negara.</li><li><strong>E</strong>: Merupakan kewajiban mundur permanen yang hanya berlaku bagi kontestan politik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 30) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS diberhentikan sementara apabila • diangkat menjadi komisioner atau anggota lembaga nonstruktural</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_tak_p02_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_tak_p02_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_tak_p02_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Tata Aturan Kepegawaian Paket 02";
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
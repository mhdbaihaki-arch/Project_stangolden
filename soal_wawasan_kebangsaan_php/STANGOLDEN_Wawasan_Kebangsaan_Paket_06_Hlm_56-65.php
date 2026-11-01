<?php
/**
 * STANGOLDEN.exam • Wawasan Kebangsaan Paket 06 (Halaman 56–65)
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
// $DB->get_record("stgwk_p06_rank") => tabel fisik "mdlhv_stgwk_p06_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgwk_p06_rank';
const STG_REVIEW_T = 'stgwk_p06_rev';
const STG_ANNO_T = 'stgwk_p06_anno';
const STG_KEY = [0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3];
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
            error_log('STANGOLDEN WK P06 HLM56-65 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN WK P06 HLM56-65: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Wawasan Kebangsaan Paket 06 (Hlm. 56–65) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 06 • Hlm. 56–65</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 06 • Hlm. 56–65</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Wawasan Kebangsaan Paket 06</h2>
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
const STG_LOCAL_KEY="stg_wk_p06_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Penyatuan kembali negara-negara bagian federal ke dalam pangkuan Republik Indonesia secara yuridis didasarkan pada ketentuan bahwa...", "options": ["Pembubaran dan penggabungan negara bagian diatur pasal 43 dan 49 RIS", "Peleburan wilayah serikat diputuskan sekehendak perwakilan militer sekutu", "Konstitusi federal melarang pembubaran wilayah tanpa izin Belanda", "Negara bagian diwajibkan mempertahankan batas teritorialnya secara permanen", "Pemerintah federal berhak membekukan dewan perwakilan daerah"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Yuridis Pembubaran Negara Bagian RIS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pembubaran dan penggabungan negara-negara bagian itu dimungkinkan dalam pasal 43 dan pasal 49 Konstitusi RIS</strong>, yang menjadi jalan konstitusional kembalinya bentuk negara kesatuan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Konstitusi RIS sendiri memuat klausul hukum yang memperbolehkan suatu negara bagian untuk bergabung dengan negara bagian lain atau membubarkan diri demi aspirasi rakyatnya, sehingga proses unifikasi berjalan sah secara hukum ketatanegaraan.</p><p>Jawaban tepat: <strong>A. Pembubaran dan penggabungan negara bagian diatur pasal 43 dan 49 RIS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena peleburan wilayah didasarkan pada kehendak rakyat dan pasal konstitusi, bukan komando sekutu.</li><li><strong>C</strong>: Kurang tepat karena Konstitusi RIS justru membuka ruang pembubaran dan penggabungan wilayah.</li><li><strong>D</strong>: Kurang tepat karena negara bagian memiliki hak konstitusional untuk menggabungkan wilayahnya.</li><li><strong>E</strong>: Kurang tepat karena pembubaran negara bagian berakar dari keputusan rakyat dan parlemen negara bagian terkait.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 56) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dimungkinkan dalam pasal 43 dan pasal 49 Konstitusi RlS</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pada bulan April 1950, dua entitas federal yang tercatat sebagai wilayah bagian terakhir sebelum tercapainya kesepakatan negara kesatuan adalah...", "options": ["Negara Pasundan bersama dengan Daerah Istimewa Kalimantan Barat", "Negara bagian Indonesia Timur dan Sumatera Timur", "Negara Madura berdampingan dengan wilayah Negara Jawa Timur", "Negara Sumatera Selatan bersama entitas Daerah Bangka Belitung", "Daerah Banjar bersekutu dengan federasi Dayak Besar di Kalimantan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Negara Bagian Terakhir Menjelang Pembubaran RIS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada bulan April 1950 sebagian besar wilayah telah bergabung ke RI Yogyakarta, menyisakan <strong>negara bagian Indonesia Timur dan Sumatera Timur</strong> yang belum bergabung.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Negara Indonesia Timur (NIT) dan Negara Sumatera Timur (NST) merupakan dua negara bagian bentukan Belanda yang bertahan paling akhir sebelum akhirnya pimpinannya bersepakat untuk melebur ke dalam NKRI.</p><p>Jawaban tepat: <strong>B. Negara bagian Indonesia Timur dan Sumatera Timur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Negara Pasundan telah lebih awal membubarkan diri pada awal tahun 1950.</li><li><strong>C</strong>: Kurang tepat karena Negara Madura dan Jawa Timur telah bergabung ke RI mendahului NIT dan NST.</li><li><strong>D</strong>: Kurang tepat karena Sumatera Selatan dan Bangka Belitung telah melebur ke RI sebelum April 1950.</li><li><strong>E</strong>: Kurang tepat karena satuan kenegaraan di Kalimantan telah menyatakan bergabung ke RI pada permulaan 1950.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 56) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>negara bagian Indonesia Timur dan Sumatera Timur</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Inti kesepakatan politik antara pimpinan Republik Indonesia di Yogyakarta dengan pimpinan negara federal RIS pada Mei 1950 adalah tekad untuk...", "options": ["Mempertahankan bentuk uni persemakmuran di bawah pengawasan kerajaan asing", "Membagi wilayah kepulauan nusantara menjadi empat federasi ekonomi mandiri", "Dalam waktu sesingkat-singkatnya bersama-sama mendirikan negara kesatuan", "Menyelenggarakan pemungutan suara berulang di setiap distrik persekutuan dagang", "Membentuk kabinet koalisi permanen yang dipimpin oleh komisioner tinggi sekutu"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesepakatan Pendirian Kembali Negara Kesatuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tercapai kesepakatan antara negara RI (Jogjakarta) dan negara RIS (mewakili NIT dan NST) untuk <strong>dalam waktu sesingkat-singkatnya bersama-sama mendirikan negara kesatuan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kedua pihak menyadari bahwa format federal memecah persatuan bangsa, sehingga disepakati komitmen bersama untuk membubarkan RIS dan memproklamasikan kembali NKRI.</p><p>Jawaban tepat: <strong>C. Dalam waktu sesingkat-singkatnya bersama-sama mendirikan negara kesatuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Uni Indonesia-Belanda justru hendak diakhiri demi kedaulatan merdeka penuh.</li><li><strong>B</strong>: Kurang tepat karena kesepakatan ditujukan untuk membentuk negara kesatuan tunggal, bukan empat federasi.</li><li><strong>D</strong>: Kurang tepat karena integrasi dicapai melalui konsensus musyawarah perwakilan parlemen yang sah.</li><li><strong>E</strong>: Kurang tepat karena pemerintahan baru dipimpin mandiri oleh para pemimpin bangsa Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 56) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sesingkat-singkatnya bersama-sama mendirikan negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Dokumen yuridis formal yang memuat kesepakatan resmi pembentukan kembali Negara Kesatuan Republik Indonesia antara RIS dan RI adalah...", "options": ["Nota kesepahaman gencatan senjata di atas kapal perang renville", "Perjanjian persekutuan ekonomi bilateral konferensi meja bundar den haag", "Dekrit darurat militer yang disahkan di markas komando pertempuran", "Piagam persetujuan tanggal 19 Mei 1950 antara pemerintah RIS dan negara RI", "Maklumat bersama pimpinan fraksi parlemen serikat di jakarta"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Piagam Persetujuan 19 Mei 1950</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Persetujuan untuk mendirikan negara kesatuan secara resmi dimuat dalam suatu <strong>piagam persetujuan tanggal 19 Mei 1950</strong> antara pemerintah RIS dan pemerintah RI Yogyakarta.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Piagam Persetujuan 19 Mei 1950 (dikenal sebagai Piagam Hatta-Sukawati) merupakan tonggak sejarah yuridis yang menjadi landasan penyusunan rancangan konstitusi baru (UUDS 1950) guna memulihkan bentuk NKRI.</p><p>Jawaban tepat: <strong>D. Piagam persetujuan tanggal 19 Mei 1950 antara pemerintah RIS dan negara RI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Perjanjian Renville ditandatangani pada Januari 1948 pada era revolusi fisik.</li><li><strong>B</strong>: Kurang tepat karena KMB di Den Haag menghasilkan pembentukan RIS, bukan pembubarannya.</li><li><strong>C</strong>: Kurang tepat karena Piagam 19 Mei 1950 adalah dokumen politik-hukum sipil, bukan dekrit militer.</li><li><strong>E</strong>: Kurang tepat karena naskah kesepakatan ditandatangani oleh pimpinan eksekutif tertinggi kedua entitas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 56) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>piagam persetujuan tanggal 19 Mei 1950</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Peristiwa bersejarah kembalinya bentuk negara kesatuan di Indonesia ditandai pada momentum bahwa...", "options": ["Pada 27 Desember 1949 seluruh kedaulatan diserahkan kepada persemakmuran", "Pada 10 November 1945 komisi internasional mengesahkan batas wilayah kepulauan", "Pada 5 Juli 1959 sistem kabinet parlementer dipulihkan kembali oleh parlemen", "Pada 18 Agustus 1945 sistem pemerintahan serikat resmi diberlakukan pertama kali", "Pada 17 Agustus 1950 negara federal RIS berubah menjadi NKRI"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Proklamasi Berdirinya Kembali NKRI 17 Agustus 1950</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pada tanggal 17 Agustus 1950 Negara Indonesia yang berbentuk negara federal RIS menjadi negara kesatuan</strong> bertepatan dengan peringatan lima tahun Proklamasi Kemerdekaan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Presiden Soekarno mengumumkan secara resmi pemulihan Negara Kesatuan Republik Indonesia pada 17 Agustus 1950, mengakhiri eksistensi RIS yang berumur kurang lebih 8 bulan.</p><p>Jawaban tepat: <strong>E. Pada 17 Agustus 1950 negara federal RIS berubah menjadi NKRI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena 27 Desember 1949 adalah awal berdirinya bentuk federal RIS.</li><li><strong>B</strong>: Kurang tepat karena 10 November 1945 adalah peristiwa Pertempuran Surabaya (Hari Pahlawan).</li><li><strong>C</strong>: Kurang tepat karena Dekrit 5 Juli 1959 mengakhiri sistem parlementer UUDS 1950 dan kembali ke UUD 1945.</li><li><strong>D</strong>: Kurang tepat karena 18 Agustus 1945 adalah penetapan UUD 1945 negara kesatuan, bukan serikat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 56) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tanggal 17 Agustus 1950 di seluruh Negara Kesatuan Republik</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Langkah teknis perundang-undangan yang ditempuh bersama pasca-penandatanganan Piagam 19 Mei 1950 adalah...", "options": ["Menyusun rancangan perubahan Konstitusi RIS menjadi UUDS 1950", "Menerbitkan mata uang bersama antara kerajaan belanda dengan bank indonesia", "Menetapkan naskah piagam jakarta menjadi hukum dasar tertinggi persemakmuran", "Mengadili para perwira militer yang terlibat dalam agresi senjata di daerah", "Menyusun batas demarkasi pertahanan baru di bawah koordinasi staf sekutu"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyusunan RUU Perubahan Konstitusi RIS Menjadi UUDS 1950</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setelah persetujuan dimuat dalam piagam 19 Mei 1950, proses selanjutnya adalah <strong>membuat rancangan perubahan Konstitusi RIS menjadi Undang-Undang Dasar Sementara (UUDS) 1950 Republik Indonesia oleh pihak RIS dan negara RI</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena secara yuridis peralihan dari negara federal ke negara kesatuan dilakukan melalui mekanisme amendemen terhadap Konstitusi RIS yang diubah menjadi teks UUDS 1950.</p><p>Jawaban tepat: <strong>A. Menyusun rancangan perubahan Konstitusi RIS menjadi UUDS 1950</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena unifikasi moneter dilakukan mandiri oleh Bank Indonesia pasca-nasionalisasi De Javasche Bank.</li><li><strong>C</strong>: Kurang tepat karena Piagam Jakarta adalah dokumen perumusan dasar negara tahun 1945.</li><li><strong>D</strong>: Kurang tepat karena fokus utama panitia bersama adalah penyusunan konstitusi unifikasi negara kesatuan.</li><li><strong>E</strong>: Kurang tepat karena garis demarkasi Van Mook dihapus seiring runtuhnya negara bagian bentukan kolonial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 56) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Konstitusi RIS menjadi Undang-Undang Dasar Sementara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Persetujuan formal terhadap naskah rancangan UUDS 1950 pada tanggal 15 Agustus 1950 dideklarasikan secara resmi...", "options": ["Di hadapan perwakilan dewan pertimbangan agung dan pimpinan kejaksaan", "Di depan rapat gabungan senat dan DPR RIS oleh Presiden RIS", "Dalam konferensi pers tertutup di kantor perwakilan komisi perdamaian", "Pada rapat kerja komisi militer pertahanan tingkat wilayah kepulauan", "Melalui siaran radio luar negeri yang dipancarkan dari benua eropa"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Persetujuan Rapat Gabungan Senat dan DPR RIS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Di depan rapat gabungan senat dan DPR RIS</strong>, pada tanggal 15 Agustus Presiden menyatakan bahwa rancangan perubahan telah disetujui oleh pihak RIS dan negara RI (Jogjakarta).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena rapat gabungan Senat (mewakili negara-negara bagian) dan DPR RIS (mewakili rakyat) merupakan forum perwakilan parlemen tertinggi di tingkat federal yang berwenang menyetujui perubahan konstitusi.</p><p>Jawaban tepat: <strong>B. Di depan rapat gabungan senat dan DPR RIS oleh Presiden RIS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena DPA bukan lembaga parlemen pada masa berlakunya Konstitusi RIS.</li><li><strong>C</strong>: Kurang tepat karena deklarasi dilakukan di sidang resmi parlemen nasional, bukan konferensi pers tertutup.</li><li><strong>D</strong>: Kurang tepat karena perubahan konstitusi merupakan kewenangan lembaga legislatif sipil.</li><li><strong>E</strong>: Kurang tepat karena pengumuman resmi dibacakan langsung di ibu kota negara Jakarta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 56) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Di depan rapat gabungan senat dan DPR RIS</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Naskah Undang-Undang Dasar Sementara 1950 yang telah disetujui bersama oleh kedua belah pihak secara administratif disahkan melalui prosedur...", "options": ["Diratifikasi oleh perwakilan ratu belanda di hadapan komisi arbitrase", "Diberikan stempel persetujuan oleh komandan jenderal pasukan sekutu", "Ditandatangani bersama Perdana Menteri dan Menteri Kehakiman RIS", "Disahkan secara mandiri oleh pimpinan badan pekerja komite nasional", "Ditetapkan melalui surat edaran menteri penerangan tingkat daerah"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengesahan Naskah Resmi UUDS 1950</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Naskah UUDS yang telah disetujui oleh pihak RIS dan negara RI (Jogjakarta) <strong>ditandatangani bersama Perdana Menteri dan Menteri Kehakiman RIS</strong> untuk kemudian diundangkan secara sah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena penandatanganan oleh Perdana Menteri (Mohammad Hatta) dan Menteri Kehakiman RIS menyempurnakan keabsahan yuridis naskah UUDS 1950 menjadi hukum positif yang mengikat seluruh tumpah darah Indonesia.</p><p>Jawaban tepat: <strong>C. Ditandatangani bersama Perdana Menteri dan Menteri Kehakiman RIS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena UUDS 1950 adalah produk hukum kedaulatan Indonesia tanpa ratifikasi pihak asing.</li><li><strong>B</strong>: Kurang tepat karena pasukan sekutu telah ditarik dari Indonesia sebelum tahun 1950.</li><li><strong>D</strong>: Kurang tepat karena pengesahan dilakukan oleh pejabat eksekutif tertinggi pemerintahan RIS dan RI.</li><li><strong>E</strong>: Kurang tepat karena konstitusi dasar tidak diundangkan melalui surat edaran menteri penerangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 56) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dan negara RI (Jogjakarta) ditandatangani bersama Perdana</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Kondisi geopolitik di tanah air menjelang terwujudnya kesepakatan Mei 1950 memperlihatkan fakta bahwa sebagian besar daerah...", "options": ["Telah menuntut pembentukan pemerintahan monarki di bawah kekuasaan kesultanan", "Menyusun mata uang daerah guna memisahkan diri dari kas perbendaharaan", "Menentang pembubaran pos-pos pangkalan militer pertahanan kerajaan asing", "Sebagian besar wilayah telah bergabung ke RI Negara Bagian", "Mewajibkan penggunaan bahasa asing dalam administrasi persidangan dewan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Status Entitas Sebelum Integrasi April 1950</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada bulan April 1950 mayoritas wilayah telah melebur, sehingga menyisakan beberapa bagian dari negara bagian Indonesia Timur dan Sumatera Timur yang <strong>belum bergabung dengan negara bagian Republik Indonesia (Jogjakarta)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena gelombang tuntutan rakyat (*people power*) di berbagai negara bagian menuntut kembali ke pangkuan RI Yogyakarta, membuktikan bahwa rakyat Indonesia menentang politik adu domba federal buatan penjajah.</p><p>Jawaban tepat: <strong>D. Sebagian besar wilayah telah bergabung ke RI Negara Bagian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena aspirasi rakyat menghendaki bentuk republik kesatuan, bukan monarki kesultanan.</li><li><strong>B</strong>: Kurang tepat karena daerah-daerah mendukung unifikasi kas dan mata uang rupiah nasional.</li><li><strong>C</strong>: Kurang tepat karena rakyat justru menuntut penarikan seluruh kekuatan militer kolonial KNIL.</li><li><strong>E</strong>: Kurang tepat karena bahasa Indonesia telah dijunjung tinggi sebagai bahasa persatuan resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 56) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Republik Indonesia (Jogjakarta) dan negara RlS yang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pemberian nama 'Undang-Undang Dasar Sementara' pada konstitusi yang disahkan tahun 1950 dilandasi oleh pertimbangan bahwa...", "options": ["Konstitusi tersebut berlaku sementara sepanjang masa darurat perang terbuka", "Aturan hukum dapat dibatalkan sewaktu-waktu oleh komisi PBB", "Naskah konstitusi disusun tanpa melibatkan tokoh pergerakan", "Pemerintah pusat merencanakan pembentukan kembali negara bagian pada masa depan", "UUDS 1950 dibentuk sembari menunggu hasil kerja Konstituante"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Terbentuknya UUDS 1950</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Terbentuknya Undang-Undang Dasar Sementara (UUDS)</strong> 1950 dirancang sebagai hukum dasar transisional sebelum terbentuknya undang-undang dasar tetap yang akan disusun oleh badan Konstituante hasil pemilu.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kata 'Sementara' menunjukkan bahwa UUDS 1950 bukan produk konstitusi final. Tugas menyusun konstitusi permanen diserahkan kepada lembaga Konstituante yang dipilih langsung oleh rakyat melalui Pemilu 1955.</p><p>Jawaban tepat: <strong>E. UUDS 1950 dibentuk sembari menunggu hasil kerja Konstituante</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena UUDS 1950 diberlakukan dalam situasi damai pasca-pengakuan kedaulatan.</li><li><strong>B</strong>: Kurang tepat karena kedaulatan penetapan konstitusi berada mutlak di tangan bangsa Indonesia.</li><li><strong>C</strong>: Kurang tepat karena UUDS disusun bersama oleh wakil-wakil sah dari RIS dan RI Yogyakarta.</li><li><strong>D</strong>: Kurang tepat karena komitmen bangsa telah final menuju negara kesatuan tanpa kembali ke bentuk serikat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 56) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Terbentuknya Undang-Undang Dasar Sementara (UUDS)</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Ciri pokok sistem pemerintahan parlementer yang dianut dalam UUDS 1950 ditegaskan melalui aturan bahwa...", "options": ["Presiden bertindak sebagai kepala eksekutif yang memegang kekuasaan mutlak", "Kekuasaan pemerintah di tangan kabinet yang dipimpin oleh Perdana Menteri", "Seluruh keputusan kementerian wajib disetujui terlebih dahulu oleh dewan militer", "Jabatan perdana menteri dihapuskan dan dirangkap oleh ketua mahkamah agung", "Para menteri bertanggung jawab secara mandiri kepada asosiasi pengusaha daerah"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Struktur Eksekutif Menurut UUDS 1950</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam UUDS 1950, Presiden berkedudukan sebagai kepala negara konstitusional, sedangkan <strong>kekuasaan pemerintah di tangan kabinet yang dipimpin oleh Perdana Menteri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena sistem parlementer memisahkan fungsi kepala negara (Presiden) dari fungsi kepala pemerintahan (Perdana Menteri). Kabinet dipimpin Perdana Menteri yang memegang mandat operasional pemerintahan sehari-hari.</p><p>Jawaban tepat: <strong>B. Kekuasaan pemerintah di tangan kabinet yang dipimpin oleh Perdana Menteri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Presiden dalam UUDS 1950 tidak memegang kekuasaan pemerintahan eksekutif langsung.</li><li><strong>C</strong>: Kurang tepat karena dewan militer tidak memegang wewenang persetujuan regulasi kabinet sipil.</li><li><strong>D</strong>: Kurang tepat karena jabatan perdana menteri eksis dan terpisah dari cabang kekuasaan kehakiman.</li><li><strong>E</strong>: Kurang tepat karena menteri bertanggung jawab kepada parlemen (DPR), bukan asosiasi pengusaha.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 57) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>oleh Perdana Menteri.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pertanggungjawaban politik eksekutif dalam sistem ketatanegaraan UUDS 1950 diatur dengan ketentuan bahwa...", "options": ["Kabinet bertanggung jawab penuh kepada pimpinan majelis permusyawaratan adat", "Pemerintah menyampaikan pertanggungjawaban tahunan kepada lembaga kejaksaan", "Menteri dipimpin Perdana Menteri bertanggung jawab ke parlemen", "Perdana menteri memegang hak mutlak membekukan keanggotaan komisi peradilan", "Menteri keuangan memegang hak veto terhadap setiap keputusan ketua parlemen"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akuntabilitas Kabinet Kepada Parlemen UUDS 1950</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai karakteristik sistem parlementer pada UUDS 1950: <strong>Para menteri yang dipimpin oleh Perdana Menteri bertanggung jawab kepada DPR/ Parlemen</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena parlemen memegang supremasi politik. Jika parlemen menjatuhkan mosi tidak percaya (*vote of no confidence*), Perdana Menteri beserta kabinetnya wajib mengembalikan mandat kepada Presiden.</p><p>Jawaban tepat: <strong>C. Menteri dipimpin Perdana Menteri bertanggung jawab ke parlemen</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena majelis adat bukan lembaga perwakilan politik tingkat nasional.</li><li><strong>B</strong>: Kurang tepat karena kejaksaan menjalankan fungsi penuntutan hukum, bukan pengawasan politik kabinet.</li><li><strong>D</strong>: Kurang tepat karena perdana menteri tidak berwenang membekukan komisi peradilan.</li><li><strong>E</strong>: Kurang tepat karena menteri keuangan tidak memiliki hak veto terhadap parlemen.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 57) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>DPR/ Parlemen.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Orientasi nilai politik dan sistem demokrasi yang diterapkan pada era berlakunya UUDS 1950 adalah...", "options": ["Demokrasi sentralistik yang melarang pendirian organisasi kemasyarakatan", "Paham totaliter yang membatasi hak pilih warga negara berdasarkan golongan kelas", "Sistem feodalistik dengan aristokrasi pemegang kekuasaan", "UUDS menganut Demokrasi Liberal yang mengutamakan kebebasan individu", "Demokrasi komunal yang menentang sistem perwakilan parlemen secara menyeluruh"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ideologi Demokrasi Liberal Pada Era UUDS 1950</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>UUDS menganut Demokrasi Liberal yang mengutamakan kebebasan individu</strong>, yang melahirkan kebebasan berpendapat dan sistem multipartai yang sangat cair.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena konstitusi UUDS 1950 sangat dipengaruhi oleh piagam HAM internasional (UDHR) yang menjunjung tinggi hak-hak sipil-politik dan kebebasan perorangan dalam berpolitik.</p><p>Jawaban tepat: <strong>D. UUDS menganut Demokrasi Liberal yang mengutamakan kebebasan individu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena era 1950-an justru ditandai menjamurnya berbagai partai politik dan ormas bebas.</li><li><strong>B</strong>: Kurang tepat karena pemilu 1955 diselenggarakan secara demokratis tanpa pembatasan kasta golongan.</li><li><strong>C</strong>: Kurang tepat karena sistem feodal bertentangan dengan prinsip kesetaraan warga dalam demokrasi liberal.</li><li><strong>E</strong>: Kurang tepat karena sistem perwakilan parlemen justru menjadi pusat aktivitas kenegaraan saat itu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 57) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>UUDS menganut Demokrasi Liberal yang mengutamakan kebebasan individu</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Bukti empiris bahwa sistem kabinet parlementer era UUDS 1950 tidak menjamin stabilitas pemerintahan adalah fakta bahwa...", "options": ["Presiden membekukan seluruh partai politik oposisi di dewan perwakilan", "Menteri keuangan memotong anggaran daerah secara serentak tanpa koordinasi", "Pemilihan umum legislatif gagal diselenggarakan di seluruh wilayah kepulauan", "Mahkamah agung menunda melantik pejabat tinggi eselon kementerian negara", "Telah terjadi pergantian kabinet sebanyak 7 (tujuh) kali karena dijatuhkan DPR"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Instabilitas Politik Kabinet Parlementer 1950-1959</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam kurun waktu 17 Agustus 1950 hingga 5 Juli 1959, <strong>telah terjadi pergantian kabinet sebanyak 7 (tujuh) kali karena dijatuhkan DPR</strong>, yang menunjukkan sistem parlementer tidak menjamin kestabilan pemerintah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena rata-rata usia kabinet rata-rata bertahan sekitar satu tahun (seperti Kabinet Natsir, Sukiman, Wilopo, Ali I, Burhanuddin Harahap, Ali II, dan Djuanda). Dinamika jatuh-bangun kabinet ini mengganggu kelangsungan program pembangunan nasional.</p><p>Jawaban tepat: <strong>E. Telah terjadi pergantian kabinet sebanyak 7 (tujuh) kali karena dijatuhkan DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pembubaran parpol baru terjadi pada masa Demokrasi Terpimpin pasca-1959.</li><li><strong>B</strong>: Kurang tepat karena problem utama adalah pertarungan politik fraksi-fraksi di parlemen dewan.</li><li><strong>C</strong>: Kurang tepat karena Pemilu 1955 terselenggara dengan sangat sukses, demokratis, dan aman.</li><li><strong>D</strong>: Kurang tepat karena peradilan tidak mencampuri urusan suksesi birokrasi kabinet politik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 57) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>terjadi pergantian kabinet sebanyak 7 (tujuh) kali karena dijatuhkan DPR</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pemberlakuan kembali UUD 1945 sebagai hukum dasar negara Republik Indonesia pada kurun waktu kedua bertumpu pada dasar hukum...", "options": ["Dasar berlakunya UUD 1945 kurun kedua adalah Dekrit 5 Juli 1959", "Resolusi majelis umum dewan keamanan perserikatan bangsa-bangsa di new york", "Ketetapan pleno istimewa komite nasional indonesia pusat di yogyakarta", "Surat keputusan kementerian kehakiman mengenai ratifikasi piagam perjanjian", "Hasil kesepakatan mufakat tertutup pimpinan partai pemenang pemilihan umum"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dekrit Presiden 5 Juli 1959 Kurun Waktu Kedua</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Dasar hukum berlakunya UUD 1945 dalam kurun waktu kedua adalah Dekrit Presiden tanggal 5 Juli 1959 yang memberlakukan kembali UUD 1945</strong> sebagai konstitusi negara Republik Indonesia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Dekrit 5 Juli 1959 yang dikeluarkan Presiden Soekarno membubarkan Konstituante, menetapkan berlakunya kembali UUD 1945 dan tidak berlakunya lagi UUDS 1950, serta pembentukan MPRS dan DPAS.</p><p>Jawaban tepat: <strong>A. Dasar berlakunya UUD 1945 kurun kedua adalah Dekrit 5 Juli 1959</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena penetapan konstitusi nasional merupakan tindakan kedaulatan internal bangsa Indonesia.</li><li><strong>C</strong>: Kurang tepat karena KNIP sudah tidak ada setelah pembentukan DPR hasil Pemilu 1955.</li><li><strong>D</strong>: Kurang tepat karena kembalinya konstitusi ditetapkan melalui Dekrit Presiden, bukan SK menteri.</li><li><strong>E</strong>: Kurang tepat karena kegagalan fraksi-fraksi di Konstituante yang memicu lahirnya Dekrit Presiden.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 57) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tanggal 5 Juli 1959, saat Dekrit Presiden dikeluarkan. UUDS menganut sistem Kabinet</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Tuntutan bersejarah yang disampaikan oleh rakyat dipelopori pemuda dan mahasiswa pada awal tahun 1966 yang dikenal sebagai TRITURA memuat isi...", "options": ["Pemberian amnesti narapidana, pembentukan federasi, dan kenaikan tarif cukai", "Bubarkan PKI, bersihkan kabinet, dan perbaiki ekonomi (Tritura)", "Nasionalisasi perbankan, unifikasi kepolisian militer, dan penutupan jalur laut", "Pemilihan langsung, penghapusan DPD, dan revisi undang-undang agraria", "Pembekuan dewan daerah, privatisasi BUMN, dan pinjaman luar negeri"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Tuntutan Rakyat (TRITURA)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tri Tuntutan Rakyat (TRITURA) yang dipelopori pemuda/mahasiswa berisi tiga tuntutan pokok: <strong>(1) Bubarkan PKI, (2) Bersihkan Kabinet dari unsur-unsur G-30-S/PKI, dan (3) Turunkan harga-harga/perbaikan ekonomi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena TRITURA lahir dari keprihatinan mendalam atas kekacauan politik dan krisis inflasi ekonomi parah pasca-pemberontakan G-30-S/PKI tahun 1965, menuntut penuntasan hukum terhadap dalang makar serta perbaikan taraf hidup rakyat.</p><p>Jawaban tepat: <strong>B. Bubarkan PKI, bersihkan kabinet, dan perbaiki ekonomi (Tritura)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena TRITURA menuntut pembubaran partai makar dan penurunan harga, bukan amnesti.</li><li><strong>C</strong>: Kurang tepat karena penutupan jalur laut dan unifikasi bukan substansi tuntutan mahasiswa 1966.</li><li><strong>D</strong>: Kurang tepat karena isu pilpres langsung dan DPD merupakan agenda gerakan reformasi 1998.</li><li><strong>E</strong>: Kurang tepat karena privatisasi BUMN bukan merupakan bagian dari tuntutan TRITURA.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 57) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Bersihkan Kabinet dari unsur-unsur G-30-S/PKI; dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Peristiwa tragis yang mengguncang stabilitas politik dan kedaulatan negara pada kurun waktu pertama pasca-Dekrit 1959 adalah...", "options": ["Pemberontakan perhimpunan serikat buruh pelabuhan di tanjung perak", "Serangan agresi militer armada perang kerajaan belanda di ibu kota", "Terjadi pemberontakan G-30-S/PKI yang mengancam keutuhan ideologi negara", "Pertikaian bersenjata perbatasan teritorial kepulauan dengan negara tetangga", "Krisis likuiditas moneter perbankan swasta berskala multinasional"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peristiwa G-30-S/PKI Tahun 1965</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada periode 5 Juli 1959 hingga sebelum 11 Maret 1966: <strong>Terjadi pemberontakan G-30-S/PKI</strong>. Sewaktu terjadi pemberontakan kondisi negara memprihatinkan khususnya di bidang ekonomi, politik, dan hukum, namun pemberontakan berhasil digagalkan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pemberontakan G-30-S/PKI tahun 1965 berusaha merongrong dasar negara Pancasila melalui penculikan dan pembunuhan para perwira tinggi militer AD, yang kemudian berhasil ditumpas oleh kekuatan patriotik bangsa.</p><p>Jawaban tepat: <strong>C. Terjadi pemberontakan G-30-S/PKI yang mengancam keutuhan ideologi negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pemogokan buruh bukan ancaman ideologis utama pada kurun tersebut.</li><li><strong>B</strong>: Kurang tepat karena agresi militer Belanda terjadi pada masa 1945–1949, bukan tahun 1965.</li><li><strong>D</strong>: Kurang tepat karena konfrontasi perbatasan bukan peristiwa pemberontakan internal di ibu kota.</li><li><strong>E</strong>: Kurang tepat karena krisis ekonomi era tersebut berupa hiperinflasi domestik, bukan kebangkrutan bank asing.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 57) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Terjadi pemberontakan G-30-S/PKI</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Langkah strategis yang diambil Presiden Soekarno guna memulihkan keamanan dan stabilitas nasional pada 11 Maret 1966 adalah...", "options": ["Menyerahkan mandat kedaulatan kepada majelis permusyawaratan darurat", "Menginstruksikan pembentukan persemakmuran pertahanan bersama negara sahabat", "Membubarkan seluruh kesatuan korps kepolisian dan kejaksaan agung", "Presiden menerbitkan Supersemar kepada Letjen Soeharto", "Mengumumkan pengunduran diri secara mendadak di hadapan pimpinan dewan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerbitan Surat Perintah Sebelas Maret (Supersemar)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada tanggal 11 Maret 1966 <strong>Presiden mengeluarkan Supersemar kepada Letjen Soeharto untuk mengambil langkah-langkah dan tindakan untuk mengamankan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Surat Perintah Sebelas Maret memberi wewenang penuh kepada Menteri/Panglima Angkatan Darat Letjen Soeharto untuk memulihkan ketertiban, ketenangan, serta menjamin keselamatan pribadi dan kewibawaan Presiden.</p><p>Jawaban tepat: <strong>D. Presiden menerbitkan Supersemar kepada Letjen Soeharto</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kedaulatan tetap dipegang oleh Presiden Soekarno pada saat penerbitan Supersemar.</li><li><strong>B</strong>: Kurang tepat karena Supersemar adalah instrumen pengamanan komando domestik internal.</li><li><strong>C</strong>: Kurang tepat karena kepolisian dan kejaksaan tetap beroperasi mengawal hukum dan keamanan.</li><li><strong>E</strong>: Kurang tepat karena Presiden Soekarno tidak mengundurkan diri pada 11 Maret 1966.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 57) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pada tanggal 11 Maret 1966 Presiden mengeluarkan Supersemar kepada</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Dalam periodisasi sejarah politik ketatanegaraan Indonesia, penerbitan Supersemar memiliki makna simbolis penting yaitu...", "options": ["Menandai berakhirnya bentuk negara kesatuan republik indonesia", "Menandai berhentinya operasional seluruh badan usaha milik negara", "Menjadi titik awal berlakunya sistem peradilan militer darurat tertutup", "Mengawali pembentukan kabinet parlementer liberal di lingkungan dewan", "Lahirnya Supersemar dianggap sebagai lahirnya Orde Baru di Indonesia"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Supersemar Sebagai Tonggak Lahirnya Orde Baru</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Lahirnya Supersemar dianggap sebagai lahirnya Orde Baru</strong>, yang menandai babak transisi kepemimpinan nasional dari masa Orde Lama menuju tatanan pemerintahan Orde Baru.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Supersemar menjadi dasar tindakan pembubaran PKI dan pembersihan aparatur negara, yang menjadi fondasi berdirinya rezim Orde Baru yang berkomitmen melaksanakan Pancasila dan UUD 1945 secara murni dan konsekuen.</p><p>Jawaban tepat: <strong>E. Lahirnya Supersemar dianggap sebagai lahirnya Orde Baru di Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena bentuk NKRI tetap berdiri kokoh dan senantiasa berlanjut.</li><li><strong>B</strong>: Kurang tepat karena BUMN tetap menjalankan fungsi produksi dan penyediaan kebutuhan masyarakat.</li><li><strong>C</strong>: Kurang tepat karena Supersemar ditujukan untuk pemulihan ketertiban umum dan keamanan nasional.</li><li><strong>D</strong>: Kurang tepat karena sistem parlementer telah diakhiri sejak Dekrit 1959 dan tidak dipulihkan kembali.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 57) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mengamankan negara. Lahirnya Supersemar dianggap sebagai lahirnya Orde</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan lintasan sejarah konstitusi, kurun waktu kedua berlakunya UUD 1945 secara garis besar diklasifikasikan ke dalam...", "options": ["Kurun waktu kedua berlakunya UUD 1945 dibagi empat periode", "Dua tahapan transisi darurat militer dan persemakmuran bersama sekutu", "Tiga dasawarsa berlakunya sistem monarki perwakilan daerah otonom", "Lima fase pembagian kedaulatan wilayah ke negara-negara serikat", "Enam kurun perombakan total naskah pembukaan undang-undang dasar"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Periodisasi Kurun Waktu Kedua UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kurun waktu kedua berlakunya UUD 1945 dari tanggal 5 Juli 1959 hingga sekarang dapat dibagi dalam empat periode</strong>, yaitu Orde Lama (1959–1966), Orde Baru (1966–1998), Reformasi (1998–1999), dan Pasca-Amandemen (1999–sekarang).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pembagian empat periode ini mencerminkan dinamika penerapan UUD 1945 dari masa Demokrasi Terpimpin, masa Orde Baru, masa awal Reformasi, hingga modernisasi konstitusi lewat empat tahap amandemen.</p><p>Jawaban tepat: <strong>A. Kurun waktu kedua berlakunya UUD 1945 dibagi empat periode</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena Indonesia bukan merupakan negara persemakmuran sekutu.</li><li><strong>C</strong>: Kurang tepat karena Indonesia adalah negara kesatuan berbentuk republik, bukan monarki.</li><li><strong>D</strong>: Kurang tepat karena bentuk federal berlaku pada era RIS 1949–1950, bukan kurun kedua.</li><li><strong>E</strong>: Kurang tepat karena naskah Pembukaan UUD 1945 senantiasa dijaga keasliannya dan bersifat permanen.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 57) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Juli 1959 hingga sekarang dapat dibagi dalam empat periode, yaitu:</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Ketetapan penting yang dihasilkan dalam Sidang Istimewa MPRS tahun 1967 melalui TAP MPRS No. XXXIII/MPRS/1967 adalah...", "options": ["Mengesahkan pembentukan kabinet koalisi bersama pimpinan partai politik", "Membatalkan pertanggungjawaban panitia perancang undang-undang darurat pemilu", "Menarik mandat MPRS dari Ir. Soekarno dan mengangkat pejabat Presiden", "Membubarkan secara permanen seluruh jajaran kepolisian dan kejaksaan agung", "Menetapkan pemilihan umum demokratis pertama dalam kurun waktu sepuluh hari"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sidang Istimewa MPRS Tahun 1967</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada tahun 1967 diadakan Sidang Istimewa MPRS yang <strong>menarik kembali mandat MPRS dari Presiden pada saat itu yaitu Ir. Soekarno, selanjutnya mengangkat Jenderal Soeharto sebagai pejabat Presiden (TAP MPRS No. XXXIII/MPRS/1967)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena penolakan terhadap pidato Nawaksara dan pelengkap Nawaksara mendorong MPRS mencabut kekuasaan eksekutif dari Presiden Soekarno dan menyerahkan mandat pejabat presiden kepada Jenderal Soeharto.</p><p>Jawaban tepat: <strong>C. Menarik mandat MPRS dari Ir. Soekarno dan mengangkat pejabat Presiden</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Sidang Istimewa 1967 berfokus pada suksesi kepemimpinan kepala negara, bukan kabinet partai.</li><li><strong>B</strong>: Kurang tepat karena MPRS mengadili pertanggungjawaban presiden, bukan panitia pemilu.</li><li><strong>D</strong>: Kurang tepat karena MPRS tidak membubarkan aparat penegak hukum.</li><li><strong>E</strong>: Kurang tepat karena pemilu baru diselenggarakan kemudian pada tahun 1971.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 58) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mandat MPRS dari Presiden pada saat itu yaitu Ir. Soekarno, selanjutnya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Perubahan status kepemimpinan nasional pada Sidang Umum MPRS tahun 1968 ditetapkan melalui keputusan untuk...", "options": ["Menetapkan masa jabatan kepala negara berlaku seumur hidup tanpa evaluasi", "Mendelegasikan fungsi anggaran belanja negara kepada pimpinan perbankan asing", "Membubarkan lembaga perwakilan rakyat dan membentuk dewan pertahanan darurat", "Mengangkat Jenderal Soeharto sebagai Presiden sampai hasil pemilu", "Menyerahkan mandat peradilan perdata kepada komite arbitrase internasional"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sidang Umum MPRS Tahun 1968</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada tahun 1968 diadakan Sidang Umum MPRS yang <strong>mengangkat Jenderal Soeharto sebagai Presiden tetap sampai terpilihnya Presiden hasil pemilu (TAP MPRS No. XLIV/MPRS/1968)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena TAP MPRS No. XLIV/MPRS/1968 mengukuhkan status Jenderal Soeharto dari pejabat presiden menjadi Presiden Republik Indonesia definitif hingga pemilu umum berikutnya terlaksana.</p><p>Jawaban tepat: <strong>D. Mengangkat Jenderal Soeharto sebagai Presiden sampai hasil pemilu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pengangkatan presiden seumur hidup adalah produk MPRS 1963 era Orde Lama yang dicabut.</li><li><strong>B</strong>: Kurang tepat karena penetapan APBN tetap berada di bawah wewenang pemerintah dan DPR-GR.</li><li><strong>C</strong>: Kurang tepat karena MPRS tidak membubarkan DPR melainkan mengukuhkan kelembagaan negara.</li><li><strong>E</strong>: Kurang tepat karena kedaulatan peradilan tetap berada pada badan peradilan nasional Mahkamah Agung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 58) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Soeharto sebagai Presiden tetap sampai terpilihnya Presiden hasil pemilu</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan pesta demokrasi pemilihan umum legislatif pertama pada masa pemerintahan Orde Baru diselenggarakan pada...", "options": ["Tahun 1966 bersamaan dengan terbitnya surat perintah sebelas maret", "Tahun 1955 pada masa transisi berlakunya undang-undang dasar sementara", "Tahun 1973 bertepatan dengan penetapan garis-garis besar haluan negara", "Tahun 1968 menjelang sidang umum majelis permusyawaratan darurat", "Pemilu pertama dalam masa Orde Baru diadakan pada tahun 1971"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemilihan Umum Pertama Era Orde Baru</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pemilu pertama dalam masa Orde Baru diadakan pada tahun 1971</strong>, selanjutnya pada tahun 1973 diadakan sidang umum MPR yang menetapkan GBHN dan memilih Presiden dan Wakil Presiden.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena setelah Pemilu 1955, Indonesia baru menyelenggarakan pemilihan umum kembali pada tanggal 5 Juli 1971 yang diikuti oleh 10 kontestan (9 parpol dan Sekber Golkar).</p><p>Jawaban tepat: <strong>E. Pemilu pertama dalam masa Orde Baru diadakan pada tahun 1971</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena tahun 1966 adalah momentum transisi politik terbitnya Supersemar.</li><li><strong>B</strong>: Kurang tepat karena Pemilu 1955 adalah pemilu pertama Indonesia pada masa Demokrasi Liberal.</li><li><strong>C</strong>: Kurang tepat karena tahun 1973 adalah Sidang Umum MPR hasil Pemilu 1971.</li><li><strong>D</strong>: Kurang tepat karena tahun 1968 adalah Sidang Umum MPRS pengangkatan presiden definitif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 58) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemilu pertama dalam masa Orde Baru diadakan pada tahun 1971</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Salah satu penyimpangan ketatanegaraan terhadap UUD 1945 yang terjadi pada masa Orde Lama tahun 1960 adalah...", "options": ["Hak Budget DPR tidak berjalan, dan pada tahun 1960 Presiden membubarkan DPR", "DPR membekukan operasional kementerian keuangan karena defisit anggaran belanja", "Mahkamah agung membatalkan naskah undang-undang dasar melalui putusan peradilan", "Pemerintah daerah membentuk badan arbitrase perbatasan teritorial kepulauan", "Menteri penerangan memecat pimpinan fraksi partai peserta pemilihan umum"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembubaran DPR Hasil Pemilu Oleh Presiden Tahun 1960</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyimpangan kurun waktu 1945–1966 antara lain: <strong>Hak Budget DPR tidak berjalan, dan pada tahun 1960 Presiden membubarkan DPR, karena DPR tidak menyetujui RAPBN yang diajukan pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Presiden Soekarno mengeluarkan Penetapan Presiden No. 3 Tahun 1960 yang membubarkan DPR hasil Pemilu 1955 dan menggantikannya dengan DPR-Gotong Royong (DPR-GR), yang bertentangan dengan prinsip UUD 1945.</p><p>Jawaban tepat: <strong>A. Hak Budget DPR tidak berjalan, dan pada tahun 1960 Presiden membubarkan DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena DPR tidak berwenang membekukan kementerian eksekutif.</li><li><strong>C</strong>: Kurang tepat karena MA tidak berwenang membatalkan teks UUD 1945.</li><li><strong>D</strong>: Kurang tepat karena pemda tidak memiliki wewenang urusan perbatasan internasional.</li><li><strong>E</strong>: Kurang tepat karena menteri tidak berwenang memecat pimpinan fraksi parlemen.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 58) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Hak Budget DPR tidak berjalan, dan pada tahun 1960 Presiden</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Penyimpangan terhadap ketentuan Pasal 7 UUD 1945 naskah asli pada masa Demokrasi Terpimpin ditandai oleh tindakan MPRS yang...", "options": ["Menetapkan masa jabatan kepala negara terbatas pada rentang waktu tiga tahun", "MPRS mengangkat Presiden seumur hidup melalui produk ketetapan majelis", "Mewajibkan presiden mengundurkan diri setiap kali rancangan regulasi tertunda", "Menghapuskan fungsi kepresidenan dan menggantikannya dengan dewan presidium", "Mendelegasikan pengangkatan kepala eksekutif kepada komite perwakilan daerah"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengangkatan Presiden Seumur Hidup</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada masa Orde Lama terjadi penyimpangan ketatanegaraan di mana <strong>MPRS mengangkat Presiden seumur hidup</strong> melalui Ketetapan MPRS No. III/MPRS/1963.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Pasal 7 UUD 1945 secara tegas mengatur masa jabatan presiden adalah lima tahun dan sesudahnya dapat dipilih kembali. Pengangkatan seumur hidup mencederai prinsip republik dan demokrasi berkala.</p><p>Jawaban tepat: <strong>B. MPRS mengangkat Presiden seumur hidup melalui produk ketetapan majelis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena masa jabatan yang ditetapkan MPRS 1963 adalah seumur hidup, bukan tiga tahun.</li><li><strong>C</strong>: Kurang tepat karena pengangkatan seumur hidup justru menghapuskan mekanisme pergantian berkala.</li><li><strong>D</strong>: Kurang tepat karena jabatan presiden tetap dipertahankan bahkan diperluas kewenangannya.</li><li><strong>E</strong>: Kurang tepat karena pengangkatan dilakukan langsung oleh MPRS bentukan Dekrit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 58) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>MPRS mengangkat Presiden seumur hidup</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Praktik pelemahan prinsip pemisahan kekuasaan (*trias politica*) pada masa Demokrasi Terpimpin terlihat dari kebijakan bahwa...", "options": ["Ketua dewan perwakilan rakyat memegang komando seluruh angkatan bersenjata", "Pimpinan badan pemeriksa keuangan berhak memveto seluruh regulasi presiden", "Ketua lembaga tinggi negara dijadikan menteri pembantu Presiden", "Hakim agung memimpin rapat koordinasi operasional kementerian perekonomian", "Para menteri kabinet diwajibkan merangkap jabatan sebagai kepala daerah otonom"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Subordinasi Pimpinan Lembaga Tinggi Menjadi Menteri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyimpangan kurun waktu Orde Lama nomor 4 adalah <strong>Ketua lembaga-lembaga tinggi negara dijadikan menteri-menteri negara</strong>, yang menempatkan pimpinan MPRS, DPR-GR, dan MA berstatus menteri pembantu presiden.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kedudukan ketua lembaga legislatif dan yudikatif yang dijadikan menteri bawahan presiden merusak independensi cabang kekuasaan lain dan menciptakan pemusatan kekuasaan mutlak di tangan Presiden.</p><p>Jawaban tepat: <strong>C. Ketua lembaga tinggi negara dijadikan menteri pembantu Presiden</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena komando tertinggi angkatan perang dipegang oleh Presiden, bukan ketua DPR.</li><li><strong>B</strong>: Kurang tepat karena BPK saat itu justru mengalami pelemahan fungsi pengawasan.</li><li><strong>D</strong>: Kurang tepat karena ketua MA dijadikan menteri yang tunduk pada presiden, bukan memimpin kementerian ekonomi.</li><li><strong>E</strong>: Kurang tepat karena menteri tidak merangkap sebagai kepala daerah tingkat provinsi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 58) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Ketua lembaga-lembaga tinggi negara dijadikan menteri-menteri negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Karakteristik penyelenggaraan ketatanegaraan pada masa Orde Baru (1966–1998) ditandai oleh fenomena hubungan kelembagaan di mana...", "options": ["Parlemen memegang dominasi penuh dalam menentukan arah seluruh kebijakan publik", "Pemerintah daerah memegang kedaulatan mutlak pengelolaan sumber keuangan negara", "Badan peradilan memiliki wewenang membatalkan ketetapan majelis permusyawaratan", "Kekuasaan lembaga kepresidenan sangat dominan dibanding lainnya", "Dewan pertimbangan agung menjalankan fungsi penuntutan perkara kejahatan ekonomi"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sentralisasi Kekuasaan Eksekutif Orde Baru</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam pelaksanaan UUD 1945 kurun waktu 1966–1998: Di satu pihak, <strong>kekuasaan lembaga tinggi presiden sangat berperan</strong>, di lain pihak lembaga-lembaga negara lainnya belum berfungsi secara optimal (*executive heavy*).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena meskipun lembaga negara telah dibentuk lengkap, kekuasaan eksekutif presiden sangat dominan mengendalikan proses politik dan hukum, sementara DPR dan lembaga pengawas cenderung bersikap menyetujui (*rubber stamp*).</p><p>Jawaban tepat: <strong>D. Kekuasaan lembaga kepresidenan sangat dominan dibanding lainnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena DPR pada era Orde Baru berada di bawah pengaruh kuat dominasi eksekutif presiden.</li><li><strong>B</strong>: Kurang tepat karena tata kelola keuangan negara era Orde Baru bersifat sangat sentralistik.</li><li><strong>C</strong>: Kurang tepat karena MA saat itu belum memiliki wewenang menguji undang-undang atau TAP MPR.</li><li><strong>E</strong>: Kurang tepat karena DPA berstatus dewan penasihat dan tidak menjalankan fungsi penuntutan hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 58) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kekuasaan lembaga tinggi presiden sangat berperan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pada masa awal kurun waktu kedua (1959–1966), pembentukan alat kelengkapan negara menghadapi kendala konstitusional berupa fakta bahwa...", "options": ["Seluruh anggota parlemen absen menghadiri persidangan di ibu kota negara", "Presiden dilarang mengangkat menteri sebelum persetujuan dari dewan adat", "Pemerintah menyerahkan pembentukan lembaga kepada perwakilan monarki asing", "Kementerian kehakiman membekukan operasional hierarki peradilan tata usaha", "Lembaga-lembaga negara belum dibentuk berdasarkan Undang-undang formal"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kendala Kelembagaan Pasca-Dekrit 1959</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyimpangan kurun waktu 1945 sampai sebelum 11 Maret 1966 nomor 1 adalah <strong>Lembaga-lembaga negara belum dibentuk berdasarkan Undang-undang, sebagaimana tercantum dalam UUD 1945</strong>, melainkan dibentuk lewat Penetapan Presiden.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena MPRS, DPR-GR, dan DPA dibentuk melalui Penetapan Presiden (Penpres) tersendiri tanpa persetujuan parlemen, bukan melalui undang-undang organik yang disetujui bersama oleh dewan perwakilan rakyat.</p><p>Jawaban tepat: <strong>E. Lembaga-lembaga negara belum dibentuk berdasarkan Undang-undang formal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena anggota parlemen DPR-GR ditunjuk langsung oleh Presiden dan tetap bersidang.</li><li><strong>B</strong>: Kurang tepat karena menteri diangkat langsung oleh Presiden sebagai hak prerogatif eksekutif.</li><li><strong>C</strong>: Kurang tepat karena pembentukan kelembagaan dilakukan oleh otoritas internal Presiden Soekarno.</li><li><strong>D</strong>: Kurang tepat karena peradilan tata usaha negara baru dibentuk pada era akhir Orde Baru (UU 5/1986).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 58) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Lembaga-lembaga negara belum dibentuk berdasarkan Undang-undang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Rentang waktu berlangsungnya era kepemimpinan nasional yang dikenal dengan masa Orde Baru berjalan pada kurun...", "options": ["Kurun waktu 11 Maret 1966 hingga 21 Mei 1998 (masa Orde Baru)", "Terhitung sejak 17 Agustus 1950 hingga dekrit presiden 5 Juli 1959", "Mulai sidang umum majelis tahun 1971 hingga pemilihan umum pertama reformasi", "Sejak pengesahan konstitusi ris hingga tercapainya piagam persetujuan unifikasi", "Terhitung sejak 21 Mei 1998 hingga rangkaian amandemen undang-undang dasar"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rentang Waktu Masa Pemerintahan Orde Baru</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kurun waktu 11 Maret 1966 hingga 21 Mei 1998 yang dikenal dengan masa Orde Baru</strong> berlangsung selama kurang lebih 32 tahun di bawah kepemimpinan Presiden Soeharto.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena masa Orde Baru dimulai sejak terbitnya Supersemar pada 11 Maret 1966 dan berakhir secara resmi saat Presiden Soeharto mengundurkan diri pada 21 Mei 1998 di hadapan Ketua Mahkamah Agung.</p><p>Jawaban tepat: <strong>A. Kurun waktu 11 Maret 1966 hingga 21 Mei 1998 (masa Orde Baru)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena rentang 1950–1959 adalah era berlakunya UUDS 1950 (Demokrasi Parlementer).</li><li><strong>C</strong>: Kurang tepat karena Orde Baru telah dimulai sejak 1966 sebelum Pemilu 1971.</li><li><strong>D</strong>: Kurang tepat karena era RIS berlangsung pada 1949–1950.</li><li><strong>E</strong>: Kurang tepat karena periode pasca-21 Mei 1998 merupakan era Pasca-Orde Baru / Reformasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 58) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kurun waktu 11 Maret 1966 hingga 21 Mei 1998 yang dikenal dengan masa Orde</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Mekanisme suksesi kepemimpinan eksekutif nasional yang berlaku pada era Orde Baru berdasarkan UUD 1945 sebelum amandemen adalah...", "options": ["Pasangan calon presiden dipilih langsung rakyat lewat pemilu", "MPR bersidang untuk menetapkan GBHN dan memilih Presiden dan Wakil Presiden", "Presiden ditunjuk bergiliran oleh pimpinan dewan daerah", "Kepala pemerintahan diangkat mahkamah berdasar uji kompetensi", "Suksesi kepemimpinan diserahkan kesepakatan dewan perbankan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran MPR Memilih Presiden Pada Masa Orde Baru</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada tahun 1973 dan sidang umum lima tahunan berikutnya diadakan sidang umum MPR yang bertugas <strong>menetapkan GBHN dan memilih Presiden dan Wakil Presiden</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena sebelum amandemen UUD 1945, MPR berkedudukan sebagai lembaga tertinggi negara pemegang kedaulatan rakyat yang memilih dan mengangkat Presiden sebagai mandataris MPR.</p><p>Jawaban tepat: <strong>B. MPR bersidang untuk menetapkan GBHN dan memilih Presiden dan Wakil Presiden</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pemilihan presiden langsung oleh rakyat baru dimulai pada Pemilu 2004 pasca-amandemen.</li><li><strong>C</strong>: Kurang tepat karena DPRD tidak memilih presiden melainkan anggota MPR di tingkat pusat.</li><li><strong>D</strong>: Kurang tepat karena Mahkamah Agung memegang cabang kekuasaan kehakiman dan tidak memilih presiden.</li><li><strong>E</strong>: Kurang tepat karena suksesi kepresidenan merupakan mekanisme ketatanegaraan konstitusi formal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 58) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>menetapkan GBHN dan memilih Presiden dan Wakil Presiden</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Sikap politik pemerintahan Orde Baru yang berupaya mensakralkan konstitusi dan menutup pintu perubahan dituangkan dalam...", "options": ["Ketetapan MPRS mengenai penunjukan presiden seumur hidup tanpa pergantian", "Maklumat kementerian kehakiman mengenai pembekuan fungsi amandemen dewan", "Undang-undang darurat penertiban organisasi partai politik tingkat nasional", "Pasal 104 TAP MPR I/1983 yang berketetapan mempertahankan UUD 1945", "Keputusan presiden tentang pelarangan diskusi akademis di lingkungan kampus"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketetapan Sakralisasi UUD 1945 Era Orde Baru</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Dikeluarkannya TAP MPR No. I/MPR/1983, dalam pasal 104 dinyatakan bahwa MPR berketetapan untuk mempertahankan dan tidak akan melakukan perubahan terhadap UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pasal 104 TAP MPR No. I/MPR/1983 mencerminkan kebijakan 'sakralisasi' konstitusi di era Orde Baru yang berusaha mengunci UUD 1945 dari upaya penyempurnaan atau perubahan.</p><p>Jawaban tepat: <strong>D. Pasal 104 TAP MPR I/1983 yang berketetapan mempertahankan UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penetapan presiden seumur hidup adalah produk MPRS 1963 pada era Orde Lama.</li><li><strong>B</strong>: Kurang tepat karena kementerian kehakiman tidak berwenang menerbitkan maklumat pembekuan konstitusi.</li><li><strong>C</strong>: Kurang tepat karena sakralisasi konstitusi dituangkan dalam produk hukum Ketetapan MPR formal.</li><li><strong>E</strong>: Kurang tepat karena sakralisasi UUD tertuang dalam aturan yuridis TAP MPR, bukan Keppres pelarangan diskusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 59) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dikeluarkannya TAP MPR No.I/MPR/1983, dalam pasal 104 dinyatakan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Alasan konstitusional dicabutnya Pasal 104 TAP MPR No. I/MPR/1983 oleh TAP MPR No. VII/MPR/1998 pada era reformasi adalah...", "options": ["Ketiadaan anggaran kas negara untuk menyelenggarakan sidang tahunan majelis", "Ketidaksepakatan pimpinan kementerian terhadap besaran tarif pajak nasional", "Instruksi dari dewan moneter internasional yang mewajibkan amandemen aturan", "Adanya permohonan tertulis dari majelis perwakilan kerajaan negara tetangga", "Ketentuan tersebut dicabut karena tidak sejalan dengan Pasal 37 UUD 1945"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pencabutan Ketetapan Larangan Perubahan UUD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketentuan pasal 104 TAP MPR No. I/MPR/1983 <strong>telah dicabut dengan TAP MPR No. VII/MPR/1998 tanggal 13 Nopember 1998 karena tidak sejalan dengan pasal 37 UUD 1945</strong> yang mengatur tata cara perubahan UUD.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Pasal 37 UUD 1945 justru memberikan hak dan mengatur prosedur formal bagi MPR untuk mengubah undang-undang dasar. Menutup pintu perubahan secara mutlak bertentangan dengan desain asli UUD 1945.</p><p>Jawaban tepat: <strong>E. Ketentuan tersebut dicabut karena tidak sejalan dengan Pasal 37 UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pencabutan didasarkan pada pertimbangan hukum konstitusional, bukan keterbatasan anggaran.</li><li><strong>B</strong>: Kurang tepat karena regulasi perpajakan tidak berkaitan dengan pasal perubahan konstitusi.</li><li><strong>C</strong>: Kurang tepat karena pencabutan TAP MPR merupakan agenda kedaulatan internal gerakan reformasi.</li><li><strong>D</strong>: Kurang tepat karena urusan konstitusi merupakan kedaulatan mutlak Republik Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 59) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>No. VII/MPR/I998 tanggal 13 Nopember 1998 karena tidak sejalan dengan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Instrumen hukum Orde Baru yang mewajibkan persetujuan rakyat terlebih dahulu sebelum MPR dapat mengubah pasal UUD 1945 adalah...", "options": ["TAP MPR No. IV/MPR/1983 tentang referendum yang mempersyaratkan jajak pendapat", "Undang-undang pembatasan partai politik dan penyederhanaan organisasi", "Surat keputusan kementerian penerangan mengenai sensor materi publikasi", "Piagam kesepakatan dewan pertahanan wilayah mengenai status bahaya darurat", "Ketetapan majelis permusyawaratan darurat tentang susunan perwakilan adat"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketetapan MPR Tentang Referendum</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Dikeluarkannya TAP MPR No. IV/MPR/1983 tentang referendum</strong>, di mana dinyatakan bahwa MPR berkehendak mempertahankan UUD 1945, dan apabila MPR hendak mengubah UUD 1945 harus melalui referendum.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Ketetapan Referendum (yang dijabarkan dalam UU No. 5/1985) menetapkan syarat kuorum yang hampir mustahil dicapai (90% pemilih terdaftar harus hadir dan 90% harus setuju), sehingga secara efektif membekukan amandemen konstitusi.</p><p>Jawaban tepat: <strong>A. TAP MPR No. IV/MPR/1983 tentang referendum yang mempersyaratkan jajak pendapat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena UU penyederhanaan partai mengatur fusi parpol (PPP, PDI, Golkar), bukan referendum.</li><li><strong>C</strong>: Kurang tepat karena SK menteri penerangan mengurusi SIUPP pers, bukan aturan referendum UUD.</li><li><strong>D</strong>: Kurang tepat karena kesepakatan dewan pertahanan bukan instrumen konstitusional perubahan pasal.</li><li><strong>E</strong>: Kurang tepat karena aturan referendum ditetapkan oleh MPR resmi melalui TAP MPR No. IV/1983.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 59) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>TAP MPR No. IV/MPR/1983 tentang referendum</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Dalam Sidang Istimewa MPR tahun 1998, ketentuan mengenai referendum untuk perubahan UUD 1945 secara resmi diakhiri melalui...", "options": ["Penerbitan dekrit darurat pembubaran dewan perwakilan rakyat tingkat pusat", "Pencabutan lewat TAP MPR VIII/1998 karena bertentangan Pasal 37 UUD", "Surat edaran mahkamah agung mengenai penangguhan sengketa pemilihan umum", "Perjanjian persekutuan dagang bilateral kawasan bersama negara sahabat", "Keputusan menteri dalam negeri mengenai tata cara pemungutan suara daerah"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pencabutan Ketetapan Referendum Era Reformasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>TAP MPR No. IV/MPR/1983 tentang referendum <strong>telah dicabut dengan TAP MPR No. VIII/MPR/1998 tanggal 13 Nopember 1998 karena tidak sejalan dengan pasal 37 UUD 1945</strong> yang mengatur tentang perubahan UUD 1945.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pencabutan TAP Referendum membuka jalan lebar bagi terselenggaranya rangkaian Amandemen UUD 1945 (1999–2002) sesuai mekanisme konstitusional murni yang digariskan Pasal 37 UUD 1945.</p><p>Jawaban tepat: <strong>B. Pencabutan lewat TAP MPR VIII/1998 karena bertentangan Pasal 37 UUD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Sidang Istimewa 1998 tidak membubarkan DPR melainkan menetapkan TAP reformasi.</li><li><strong>C</strong>: Kurang tepat karena MA tidak berwenang mencabut produk hukum Ketetapan MPR.</li><li><strong>D</strong>: Kurang tepat karena perjanjian dagang kawasan tidak berkaitan dengan hukum tata negara referendum.</li><li><strong>E</strong>: Kurang tepat karena pencabutan dilakukan oleh forum tertinggi MPR, bukan keputusan menteri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 59) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>MPR No. IV/MPR/1983 telah dicabut dengan TAP MPR No. VIII/MPR/1998</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Momentum bersejarah yang menandai berakhirnya masa pemerintahan Orde Baru di Indonesia terjadi pada...", "options": ["Tanggal 11 Maret 1966 saat surat perintah pengamanan negara diterbitkan", "Tanggal 19 Oktober 1999 saat pengesahan naskah amandemen pertama konstitusi", "Pada tanggal 21 Mei 1998 saat kepemimpinan Orde Baru resmi berakhir", "Tanggal 17 Agustus 1950 saat pembubaran negara serikat hasil konferensi", "Tanggal 5 Juli 1959 saat pembacaan dekrit pemberlakuan kembali konstitusi"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Berakhirnya Masa Pemerintahan Orde Baru</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pada tanggal 21 Mei 1998, Pemerintahan Orde Baru berakhir</strong> dengan pengunduran diri Presiden Soeharto setelah gelombang demonstrasi mahasiswa dan krisis moneter.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pada 21 Mei 1998 Presiden Soeharto menyatakan berhenti dari jabatannya di Istana Merdeka dan Wakil Presiden B.J. Habibie disumpah menjadi Presiden ketiga Republik Indonesia, mengawali era Reformasi.</p><p>Jawaban tepat: <strong>C. Pada tanggal 21 Mei 1998 saat kepemimpinan Orde Baru resmi berakhir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena 11 Maret 1966 adalah awal lahirnya era Orde Baru.</li><li><strong>B</strong>: Kurang tepat karena 19 Oktober 1999 adalah tanggal penetapan Amandemen Pertama UUD 1945.</li><li><strong>D</strong>: Kurang tepat karena 17 Agustus 1950 adalah tanggal berdirinya kembali NKRI pasca-RIS.</li><li><strong>E</strong>: Kurang tepat karena 5 Juli 1959 adalah tanggal Dekrit Presiden kembali ke UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 59) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemerintahan Orde Baru berakhir.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Rentang kurun waktu antara 21 Mei 1998 hingga 22 Oktober 1999 dalam lintasan ketatanegaraan Indonesia diklasifikasikan sebagai...", "options": ["Periode berlakunya undang-undang dasar sementara republik indonesia serikat", "Masa persemakmuran bersama perwakilan komisi perdamaian internasional", "Kurun waktu berlakunya sistem demokrasi terpimpin di bawah kendali presiden", "Masa Pasca Orde Baru yang menjadi jembatan awal era reformasi konstitusi", "Fase unifikasi pembentukan karesidenan wilayah kepulauan secara serentak"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Masa Pasca-Orde Baru (Transisi Reformasi)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kurun waktu 21 Mei 1998 hingga 22 Oktober 1999 yang dikenal dengan masa Pasca Orde Baru</strong> merupakan periode transisi pemerintahan Presiden B.J. Habibie yang meletakkan fondasi kebebasan pers, UU Politik baru, dan pelaksanaan Pemilu demokratis 1999.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena dalam kurun waktu 17 bulan ini terjadi transformasi hukum fundamental, pembebasan tahanan politik, pencabutan restriksi Orde Baru, serta penyelenggaraan Pemilu demokratis 7 Juni 1999.</p><p>Jawaban tepat: <strong>D. Masa Pasca Orde Baru yang menjadi jembatan awal era reformasi konstitusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Konstitusi RIS berlaku pada tahun 1949–1950, bukan 1998–1999.</li><li><strong>B</strong>: Kurang tepat karena Indonesia berdaulat penuh dan bukan negara persemakmuran.</li><li><strong>C</strong>: Kurang tepat karena Demokrasi Terpimpin berlangsung pada kurun 1959–1966.</li><li><strong>E</strong>: Kurang tepat karena sistem karesidenan tidak diberlakukan pada era pasca-Orde Baru.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 59) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pasca Orde Baru;</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Peristiwa bersejarah dimulainya amandemen konstitusi pasca-reformasi ditandai dengan...", "options": ["Penetapan piagam persetujuan pembubaran negara serikat pada sidang paripurna", "Pembentukan dewan perancang konstitusi darurat tingkat keresidenan wilayah", "Penerbitan dekrit pembatalan seluruh produk perundang-undangan era kemerdekaan", "Pengesahan traktat bilateral batas wilayah perairan dengan komisi sekutu", "Perubahan pertama UUD 1945 yang ditetapkan tanggal 19 Oktober 1999 oleh MPR"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penetapan Perubahan Pertama UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sidang Umum MPR hasil pemilu 1999 <strong>mengadakan perubahan pertama UUD 1945 yang ditetapkan tanggal 19 Oktober 1999</strong>, yang menjadi titik tolak reformasi konstitusi nasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Perubahan Pertama UUD 1945 disahkan pada Sidang Umum MPR 1999 (meliputi 9 pasal: Pasal 5, 7, 9, 13, 14, 15, 17, 20, dan 21) yang berfokus membatasi kekuasaan eksekutif dan mengembalikan fungsi legislasi ke parlemen.</p><p>Jawaban tepat: <strong>E. Perubahan pertama UUD 1945 yang ditetapkan tanggal 19 Oktober 1999 oleh MPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pembubaran negara serikat terjadi pada Agustus 1950.</li><li><strong>B</strong>: Kurang tepat karena amandemen dilakukan oleh MPR hasil Pemilu 1999, bukan dewan karesidenan.</li><li><strong>C</strong>: Kurang tepat karena reformasi tidak membatalkan seluruh UU melainkan mengamandemen pasal konstitusi.</li><li><strong>D</strong>: Kurang tepat karena traktat perairan bukan agenda sidang amandemen UUD MPR 1999.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 59) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Mengadakan perubahan pertama UUD 1945 yang ditetapkan tanggal 19</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Salah satu substansi mendasar dalam Perubahan Pertama UUD 1945 terhadap Pasal 7 adalah aturan bahwa...", "options": ["Masa jabatan Presiden dibatasi secara tegas paling banyak dua masa jabatan", "Presiden dapat memegang kekuasaan seumur hidup asalkan didukung mayoritas fraksi", "Masa bakti eksekutif dapat diperpanjang tanpa batas saat terjadi gejolak sosial", "Presiden wajib meletakkan jabatan setiap tiga tahun guna pemilihan kabinet", "Pemilihan presiden ditiadakan dan diganti sistem kepemimpinan presidium bergilir"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembatasan Masa Jabatan Eksekutif Amandemen Pertama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam amandemen pertama, perubahan yang sangat penting adalah <strong>dibatasinya masa jabatan Presiden paling banyak 2 masa jabatan</strong> (maksimal 10 tahun).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena sebelum amandemen Pasal 7 menimbulkan multitafsir ('dapat dipilih kembali' tanpa batas). Amandemen Pertama menambahkan frasa 'selama satu kali masa jabatan berikutnya', sehingga mencegah langgengnya kekuasaan otoriter berkepanjangan.</p><p>Jawaban tepat: <strong>A. Masa jabatan Presiden dibatasi secara tegas paling banyak dua masa jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena masa jabatan seumur hidup telah dilarang mutlak oleh konstitusi.</li><li><strong>C</strong>: Kurang tepat karena perpanjangan tanpa batas bertentangan dengan prinsip kepastian konstitusional Pasal 7.</li><li><strong>D</strong>: Kurang tepat karena masa jabatan reguler adalah 5 tahun per periode, bukan 3 tahun.</li><li><strong>E</strong>: Kurang tepat karena sistem presidensial tunggal tetap dipertahankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 59) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dibatasinya masa jabatan Presiden paling banyak 2 masa jabatan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pergeseran kekuasaan membentuk undang-undang dalam Perubahan Pertama UUD 1945 pada Pasal 20 ayat (1) menegaskan bahwa...", "options": ["Kekuasaan legislasi sepenuhnya diserahkan kepada dewan pertimbangan kepresidenan", "Pemegang kekuasaan pembentuk UU adalah DPR, bukan lagi Presiden", "Mahkamah agung memegang hak monopoli menyusun naskah rancangan peraturan", "Pemerintah daerah berhak mengesahkan undang-undang nasional secara mandiri", "Kementerian koordinator memiliki hak veto membatalkan persetujuan dewan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pergeseran Kekuasaan Legislasi ke DPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam amandemen pertama dinyatakan bahwa <strong>pemegang kekuasaan pembentuk UU adalah DPR, bukan lagi Presiden</strong>, memulihkan hakikat parlemen sebagai cabang legislatif.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena naskah asli Pasal 5 ayat (1) menempatkan Presiden sebagai pemegang kekuasaan membentuk UU dengan persetujuan DPR (*executive heavy*). Amandemen Pertama memindahkannya ke Pasal 20 ayat (1) sehingga DPR menjadi pemegang kekuasaan legislasi utama.</p><p>Jawaban tepat: <strong>B. Pemegang kekuasaan pembentuk UU adalah DPR, bukan lagi Presiden</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena dewan pertimbangan berkedudukan memberi pertimbangan, bukan membentuk UU.</li><li><strong>C</strong>: Kurang tepat karena Mahkamah Agung mengadili perkara peradilan, bukan membentuk undang-undang.</li><li><strong>D</strong>: Kurang tepat karena pembentukan undang-undang merupakan ranah DPR dan Presiden di tingkat pusat.</li><li><strong>E</strong>: Kurang tepat karena menteri tidak memiliki hak veto terhadap undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 59) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dinyatakan bahwa pemegang kekuasaan pembentuk UU adalah DPR,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Penegasan mengenai tiga fungsi pokok DPR (legislasi, anggaran, dan pengawasan) serta hak-hak dewan dirumuskan dalam...", "options": ["Ketetapan majelis permusyawaratan darurat tentang susunan kabinet transisi", "Maklumat bersama menteri koordinator mengenai penataan birokrasi daerah", "Perubahan kedua UUD 1945 yang ditetapkan pada tanggal 18 Agustus 2000", "Piagam kesepakatan dewan pertahanan nasional tentang keadaan bahaya perang", "Surat edaran mahkamah konstitusi mengenai tata cara penyelesaian sengketa"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penetapan Perubahan Kedua UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam Sidang Tahunan tahun 2000 diadakan <strong>perubahan kedua UUD 1945 yang ditetapkan pada tanggal 18 Agustus 2000</strong>, yang menegaskan fungsi DPR (legislasi, anggaran, pengawasan) dan hak dewan (interpelasi, angket, menyatakan pendapat).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Perubahan Kedua UUD 1945 menyempurnakan kelembagaan DPR (Pasal 20A), Pemerintah Daerah (Pasal 18, 18A, 18B), Wilayah Negara (Pasal 25A), Warga Negara (Pasal 26–27), Hak Asasi Manusia (Pasal 28A–28J), serta Pertahanan Keamanan (Pasal 30).</p><p>Jawaban tepat: <strong>C. Perubahan kedua UUD 1945 yang ditetapkan pada tanggal 18 Agustus 2000</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena penegasan fungsi DPR tertuang dalam naskah amandemen UUD, bukan ketetapan darurat.</li><li><strong>B</strong>: Kurang tepat karena maklumat menteri tidak berkedudukan sebagai hukum dasar konstitusi.</li><li><strong>D</strong>: Kurang tepat karena piagam dewan pertahanan bukan instrumen perubahan undang-undang dasar.</li><li><strong>E</strong>: Kurang tepat karena Mahkamah Konstitusi belum terbentuk pada tahun 2000 (baru dibentuk tahun 2003).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 59) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>yang ditetapkan pada tanggal 18 Agustus 2000, dan dalam amandemen ini</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Perubahan mendasar terkait doktrin kedaulatan rakyat dalam Perubahan Ketiga UUD 1945 tahun 2001 adalah...", "options": ["MPR diposisikan sebagai pemegang monopoli kekuasaan eksekutif dan peradilan", "Kekuasaan tertinggi negara dialihkan kepada pimpinan angkatan bersenjata", "Kedaulatan negara diserahkan kepada dewan pimpinan partai politik mayoritas", "Lembaga kepresidenan dibubarkan dan digantikan oleh kabinet presidium", "MPR tidak lagi memegang dan melaksanakan kedaulatan rakyat secara penuh"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pergeseran Kedudukan MPR Pasca-Perubahan Ketiga</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam amandemen ketiga UUD 1945 ditegaskan bahwa <strong>MPR tidak lagi memegang dan melaksanakan kedaulatan rakyat. Dengan demikian MPR bukan lagi sebagai lembaga tertinggi negara</strong>, melainkan lembaga negara yang sejajar dengan lembaga negara lainnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kedaulatan rakyat pasca-amandemen Pasal 1 ayat (2) berada di tangan rakyat dan dilaksanakan menurut Undang-Undang Dasar, memutus konsep 'supremasi MPR' menjadi 'supremasi konstitusi'.</p><p>Jawaban tepat: <strong>E. MPR tidak lagi memegang dan melaksanakan kedaulatan rakyat secara penuh</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena MPR tidak memonopoli kekuasaan eksekutif atau yudikatif.</li><li><strong>B</strong>: Kurang tepat karena kekuasaan negara dipegang oleh lembaga sipil konstitusional.</li><li><strong>C</strong>: Kurang tepat karena kedaulatan berada di tangan rakyat, bukan di tangan partai politik.</li><li><strong>D</strong>: Kurang tepat karena lembaga kepresidenan tetap kokoh sebagai pemegang kekuasaan pemerintahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 60) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>MPR tidak lagi memegang dan melaksanakan kedaulatan rakyat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Salah satu implikasi yuridis dari berakhirnya status MPR sebagai lembaga tertinggi negara dalam Perubahan Ketiga adalah...", "options": ["MPR tidak lagi menetapkan GBHN sebagai pedoman mandataris kepala negara", "Presiden wajib meminta izin kepada dewan perwakilan sebelum merombak kabinet", "Anggaran belanja kementerian ditetapkan secara mandiri oleh dewan moneter", "Pemerintah daerah berhak menyusun garis haluan pembangunan luar negeri", "Penyelenggaraan pemilihan umum diserahkan kepada panitia ad-hoc daerah"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penghapusan Kewenangan Menetapkan GBHN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perubahan mendasar dalam Perubahan Ketiga tahun 2001 antara lain menetapkan bahwa <strong>MPR tidak lagi menetapkan GBHN</strong> karena Presiden dipilih langsung oleh rakyat dan mempertanggungjawabkan visi misinya kepada rakyat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena arah pembangunan nasional kini dituangkan melalui undang-undang sistem perencanaan pembangunan nasional (SPPN/RPJPN) yang disusun bersama antara Presiden dan DPR, bukan lagi berupa GBHN produk MPR.</p><p>Jawaban tepat: <strong>A. MPR tidak lagi menetapkan GBHN sebagai pedoman mandataris kepala negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena perombakan kabinet merupakan hak prerogatif presiden.</li><li><strong>C</strong>: Kurang tepat karena anggaran belanja ditetapkan dalam APBN oleh DPR bersama Presiden.</li><li><strong>D</strong>: Kurang tepat karena urusan luar negeri merupakan urusan absolut pemerintah pusat.</li><li><strong>E</strong>: Kurang tepat karena pemilu diselenggarakan oleh KPU yang bersifat nasional dan mandiri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 60) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>MPR tidak lagi menetapkan GBHN</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Peran konstitusional MPR terkait pasangan kepala eksekutif hasil pemilu langsung menurut Perubahan Ketiga UUD 1945 adalah...", "options": ["Menentukan pasangan calon pemenang melalui pemungutan suara berjenjang dewan", "Melantik Presiden dan Wakil Presiden hasil pemilihan umum langsung", "Menguji kelayakan berkas administratif pencalonan di tingkat mahkamah peradilan", "Menetapkan susunan menteri koordinator pembantu pasangan terpilih secara mandiri", "Membatalkan mandat pasangan terpilih apabila tidak disetujui pimpinan fraksi"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi MPR Melantik Presiden Terpilih</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam amandemen ketiga ditetapkan bahwa MPR tidak lagi memilih Presiden dan Wapres, tetapi bertugas <strong>melantik Presiden dan Wakil Presiden hasil pemilihan umum secara langsung oleh rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena mandat keterpilihan pasangan Presiden/Wapres bersumber langsung dari kedaulatan rakyat lewat bilik suara, sedangkan MPR menjalankan fungsi konstitusional pengesahan formal pelantikan dan pengambilan sumpah jabatan.</p><p>Jawaban tepat: <strong>B. Melantik Presiden dan Wakil Presiden hasil pemilihan umum langsung</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena MPR tidak lagi menentukan pemenang pilpres lewat pemungutan suara tertutup dewan.</li><li><strong>C</strong>: Kurang tepat karena pengujian berkas syarat pasangan calon merupakan wewenang KPU.</li><li><strong>D</strong>: Kurang tepat karena susunan menteri merupakan hak prerogatif presiden terpilih.</li><li><strong>E</strong>: Kurang tepat karena MPR tidak berwenang membatalkan mandat pemilu secara sewenang-wenang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 60) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>melantik Presiden dan Wakil Presiden hasil pemilihan umum secara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Perubahan mendasar pada Pasal 6A ayat (1) UUD 1945 yang disahkan dalam Perubahan Ketiga mengatur prinsip bahwa...", "options": ["Kepala negara diangkat oleh dewan pertimbangan agung berdasarkan musyawarah", "Perdana menteri memegang mandat tertinggi eksekutif atas persetujuan senat", "Presiden dan Wakil Presiden dipilih langsung oleh rakyat dalam satu pasangan", "Pasangan pimpinan nasional dipilih bergantian oleh gabungan fraksi partai", "Kepala pemerintahan ditetapkan oleh komisi pemilihan umum secara mandiri"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Pemilihan Presiden Langsung Pasal 6A</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Poin perubahan mendasar nomor 4 dalam Perubahan Ketiga tahun 2001 adalah bahwa <strong>Presiden dan Wakil Presiden dipilih langsung oleh rakyat</strong> dalam satu pasangan melalui pemilihan umum.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pemilihan presiden secara langsung memperkuat legitimasi mandat kepemimpinan nasional dan memperkokoh stabilitas sistem presidensial dari ancaman manuver politik parlemen.</p><p>Jawaban tepat: <strong>C. Presiden dan Wakil Presiden dipilih langsung oleh rakyat dalam satu pasangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena DPA telah dihapuskan dan presiden dipilih oleh rakyat.</li><li><strong>B</strong>: Kurang tepat karena Indonesia tidak mengenal jabatan perdana menteri.</li><li><strong>D</strong>: Kurang tepat karena pasangan calon diusung oleh partai namun dipilih langsung oleh rakyat.</li><li><strong>E</strong>: Kurang tepat karena KPU adalah penyelenggara pemilu, bukan pemilih yang menetapkan pemenang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 60) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Presiden dan Wakil Presiden dipilih langsung oleh rakyat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Dalam hal terjadi usul pemberhentian Presiden dalam masa jabatannya, batasan konstitusional bagi kewenangan MPR adalah...", "options": ["MPR berwenang memberhentikan Presiden atas kehendak pimpinan komisi", "MPR wajib membubarkan kabinet kementerian terlebih dahulu sebelum sidang", "MPR dapat mengeksekusi pemakzulan tanpa memerlukan pemeriksaan peradilan", "MPR memberhentikan Presiden atas usul DPR berdasar putusan MK", "MPR menyerahkan putusan akhir pemakzulan kepada dewan pertimbangan daerah"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prosedur Ketat Pemakzulan Pasca-Amandemen Ketiga</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam amandemen ketiga diatur bahwa <strong>MPR berwenang memberhentikan Presiden dan atau Wakil Presiden atas usul DPR berdasarkan keputusan Mahkamah Konstitusi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena konstitusi memadukan jalur *impeachment* hukum dan politik. Tuduhan pelanggaran hukum berat wajib dibuktikan dan diputus oleh Mahkamah Konstitusi terlebih dahulu sebelum MPR dapat memproses pemberhentian.</p><p>Jawaban tepat: <strong>D. MPR memberhentikan Presiden atas usul DPR berdasar putusan MK</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena MPR tidak dapat memberhentikan presiden secara sewenang-wenang tanpa putusan MK.</li><li><strong>B</strong>: Kurang tepat karena MPR tidak berwenang membubarkan kabinet kementerian.</li><li><strong>C</strong>: Kurang tepat karena pemeriksaan peradilan di MK merupakan syarat konstitusional mutlak.</li><li><strong>E</strong>: Kurang tepat karena putusan akhir pemakzulan diambil di forum Sidang Paripurna MPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 60) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>atas usul DPR berdasarkan keputusan Mahkamah Konstitusi;</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Perubahan Ketiga UUD 1945 memasukkan ketentuan Pasal 7C yang menjamin independensi cabang legislatif, yaitu...", "options": ["DPR berwenang membekukan operasional seluruh kantor kementerian eksekutif", "Presiden dapat membubarkan DPR apabila rancangan anggaran ditolak dua kali", "Ketua DPR memegang hak prerogatif pembentukan angkatan perang pertahanan", "Anggota dewan dibebaskan dari kewajiban mematuhi putusan peradilan umum", "Ditegaskan bahwa Presiden tidak dapat membekukan/membubarkan DPR"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Pembubaran DPR Pasal 7C</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Poin penting amandemen ketiga nomor 7 menyatakan: <strong>Dengan tegas dinyatakan bahwa Presiden tidak dapat membekukan dan membubarkan DPR</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pencantuman Pasal 7C memberikan kepastian hukum bahwa Presiden dilarang mengulangi peristiwa sejarah kelam tahun 1960 di mana Presiden membubarkan DPR hasil pemilu.</p><p>Jawaban tepat: <strong>E. Ditegaskan bahwa Presiden tidak dapat membekukan/membubarkan DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena DPR tidak berwenang membekukan kementerian eksekutif.</li><li><strong>B</strong>: Kurang tepat karena jika RAPBN ditolak, pemerintah menjalankan APBN tahun lalu, bukan membubarkan DPR.</li><li><strong>C</strong>: Kurang tepat karena komando tertinggi pertahanan berada di tangan Presiden (Pasal 10).</li><li><strong>D</strong>: Kurang tepat karena anggota dewan tetap wajib tunduk pada hukum pidana yang berlaku.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 60) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dengan tegas dinyatakan bahwa Presiden tidak dapat membekukan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Lembaga-lembaga negara baru yang resmi dilahirkan melalui naskah Perubahan Ketiga UUD 1945 tahun 2001 adalah...", "options": ["Dewan Perwakilan Daerah, Mahkamah Konstitusi, dan Komisi Yudisial", "Komisi Yudisial, Dewan Perwakilan Daerah, dan Badan Pemeriksa Keuangan", "Dewan Pertimbangan Agung, Lembaga Ketahanan Nasional, dan Badan Sensor Film", "Otoritas Jasa Keuangan, Lembaga Penjamin Simpanan, dan Komisi Kejaksaan", "Badan Meteorologi Klimatologi, Ombudsman Republik Indonesia, dan KASN"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kelahiran Lembaga Baru Dalam Amandemen Ketiga</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu pembaruan struktur ketatanegaraan pada Perubahan Ketiga adalah <strong>adanya lembaga baru yaitu: DPD, Mahkamah Konstitusi, Komisi Yudisial</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena ketiga lembaga ini dibentuk untuk menyempurnakan demokrasi: DPD mewakili aspirasi teritorial daerah, MK mengawal kemurnian konstitusi, dan KY menegakkan integritas etika hakim kehakiman.</p><p>Jawaban tepat: <strong>A. Dewan Perwakilan Daerah, Mahkamah Konstitusi, dan Komisi Yudisial</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena BIN, KPK, dan BPS dibentuk melalui instrumen undang-undang, bukan tercantum dalam UUD.</li><li><strong>C</strong>: Kurang tepat karena DPA merupakan lembaga lama yang justru dihapuskan pasca-reformasi.</li><li><strong>D</strong>: Kurang tepat karena OJK dan LPS merupakan otoritas keuangan yang dibentuk berdasarkan undang-undang.</li><li><strong>E</strong>: Kurang tepat karena Ombudsman dan instansi pendukung lainnya bukan organ konstitusi UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 60) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Adanya lembaga baru yaitu: DPD, Mahkamah Konstitusi, Komisi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pembaruan materi muatan konstitusi yang diperkenalkan dalam Perubahan Ketiga UUD 1945 mencakup...", "options": ["Pemberian hak veto eksekutif dan pembentukan komando militer wilayah", "Adanya Bab baru tentang Pemilu serta penyempurnaan pengaturan Pasal 23", "Penghapusan otonomi daerah dan penyeragaman status desa seluruh nusantara", "Pemisahan kas daerah dari neraca pertanggungjawaban perbendaharaan negara", "Penetapan sistem mata uang ganda untuk perdagangan ekspor kepulauan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyempurnaan Bab Pemilu dan Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam amandemen ketiga ditambahkan <strong>Bab baru tentang Pemilu (Bab VIIB Pasal 22E) dan penyempurnaan pasal 23</strong> tentang keuangan negara, BPK, dan perpajakan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Bab VIIB meletakkan asas pemilu (luber jurdil), peserta pemilu, dan penyelenggara KPU, sementara penyempurnaan Pasal 23 mengatur asas keterbukaan dan akuntabilitas pengelolaan anggaran negara.</p><p>Jawaban tepat: <strong>B. Adanya Bab baru tentang Pemilu serta penyempurnaan pengaturan Pasal 23</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena konstitusi tidak memberikan hak veto kepada presiden.</li><li><strong>C</strong>: Kurang tepat karena otonomi daerah justru diperkuat dalam Pasal 18 UUD 1945.</li><li><strong>D</strong>: Kurang tepat karena kas daerah tetap terikat dalam kerangka kesatuan keuangan negara.</li><li><strong>E</strong>: Kurang tepat karena mata uang Indonesia bersifat tunggal yaitu Rupiah (Pasal 23B).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 60) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Adanya Bab baru tentang Pemilu; dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Perubahan mendasar dalam Perubahan Keempat UUD 1945 tahun 2002 terhadap struktur keanggotaan MPR adalah...", "options": ["Keanggotaan majelis terdiri atas delegasi utusan golongan dan ormas buruh", "Seluruh perwira tinggi militer aktif otomatis menduduki kursi perwakilan", "Susunan MPR terdiri dari anggota DPR dan anggota DPD hasil pemilihan umum", "Para rektor perguruan tinggi negeri ditunjuk menjadi anggota kehormatan", "Anggota majelis ditetapkan secara proporsional oleh pimpinan kementerian"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Susunan Bikameral MPR Pasca-Perubahan Keempat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam Perubahan Keempat tahun 2002 ditetapkan bahwa <strong>Susunan MPR terdiri dari anggota DPR dan DPD</strong> yang kesemuanya dipilih melalui pemilihan umum.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena perubahan ini menghapuskan unsur Utusan Golongan dan Utusan Daerah yang sebelumnya diangkat secara politis, sehingga seluruh anggota MPR 100% memperoleh mandat demokrasi dari rakyat.</p><p>Jawaban tepat: <strong>C. Susunan MPR terdiri dari anggota DPR dan anggota DPD hasil pemilihan umum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena utusan golongan tanpa pemilu telah resmi ditiadakan dari MPR.</li><li><strong>B</strong>: Kurang tepat karena TNI bersikap netral dan tidak mendapatkan alokasi kursi otomatis di parlemen.</li><li><strong>D</strong>: Kurang tepat karena rektor universitas tidak memiliki kuota kursi formal di MPR.</li><li><strong>E</strong>: Kurang tepat karena anggota MPR dipilih melalui pemilu legislatif, bukan penunjukan kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 60) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Susunan MPR tardiri dari anggota DPR dan DPD</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Penataan kelembagaan penasihat kepresidenan dalam Perubahan Keempat UUD 1945 diwujudkan melalui ketentuan bahwa...", "options": ["DPA diberikan wewenang yudisial membatalkan keputusan rapat kerja kabinet", "Presiden dilarang menerima saran dan pertimbangan dari pihak luar birokrasi", "Dewan penasihat kepresidenan dipimpin secara bergantian oleh ketua parlemen", "Lembaga DPA ditiadakan, diganti Dewan Pertimbangan bentukan Presiden", "Lembaga penasihat negara dibubarkan permanen tanpa wadah pengganti apapun"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penghapusan DPA dan Pembentukan Dewan Pertimbangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Materi Perubahan Keempat menetapkan: <strong>Tidak ada lagi lembaga tinggi negara yang namanya DPA, tapi Presiden diberi wewenang untuk membentuk Dewan Pertimbangan yang memberi nasihat/pertimbangan kepada Presiden yang diatur dengan UU</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Pasal 16 UUD 1945 diamandemen untuk menghapus DPA yang dinilai tidak efisien, dan menggantikannya dengan dewan pertimbangan yang berkedudukan di bawah eksekutif Presiden (Wantimpres).</p><p>Jawaban tepat: <strong>D. Lembaga DPA ditiadakan, diganti Dewan Pertimbangan bentukan Presiden</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena DPA telah dihapus dari struktur kelembagaan negara.</li><li><strong>B</strong>: Kurang tepat karena Presiden justru diberi wewenang membentuk dewan pertimbangan.</li><li><strong>C</strong>: Kurang tepat karena Dewan Pertimbangan berkedudukan membantu presiden, bukan dipimpin ketua DPR.</li><li><strong>E</strong>: Kurang tepat karena wadah pertimbangan tetap ada di bawah presiden (diatur dengan UU).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 60) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tidak ada lagi lembaga tinggi negara yang namanya DPA, tapi Presiden</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Kesepakatan dasar pertama fraksi-fraksi di MPR pada Sidang Tahunan 1999 terkait arah amandemen adalah...", "options": ["Sepakat untuk tidak mengubah Pembukaan UUD 1945 dalam kondisi apa pun", "Sepakat untuk menyusun kembali naskah teks proklamasi kemerdekaan", "Sepakat untuk mengubah dasar falsafah pancasila menjadi ideologi federal", "Sepakat untuk menghapuskan cita-cita kemerdekaan pada alinea kedua", "Sepakat untuk merombak tujuan perlindungan bangsa pada alinea keempat"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesepakatan Dasar Tidak Mengubah Pembukaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesepakatan dasar arah perubahan UUD 1945 nomor satu adalah <strong>sepakat untuk tidak mengubah Pembukaan UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Pembukaan UUD 1945 memuat dasar filosofis berdirinya negara (*staatsfundamentalnorm*), Pancasila, dan tujuan nasional. Mengubah Pembukaan sama artinya dengan membubarkan negara proklamasi 17 Agustus 1945.</p><p>Jawaban tepat: <strong>A. Sepakat untuk tidak mengubah Pembukaan UUD 1945 dalam kondisi apa pun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena teks proklamasi adalah dokumen historis abadi yang tidak diubah.</li><li><strong>C</strong>: Kurang tepat karena Pancasila sebagai dasar negara dijaga kemurniannya tanpa diubah.</li><li><strong>D</strong>: Kurang tepat karena seluruh alinea Pembukaan UUD 1945 disepakati untuk tidak disentuh.</li><li><strong>E</strong>: Kurang tepat karena tujuan nasional pada alinea keempat tetap menjadi panduan konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 61) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sepakat untuk tidak mengubah Pembukaan UUD 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Komitmen kebangsaan seluruh fraksi MPR dalam proses amandemen UUD 1945 ditegaskan melalui konsensus untuk...", "options": ["Mengubah bentuk negara menjadi konfederasi serikat kepulauan otonom", "Sepakat untuk mempertahankan bentuk Negara Kesatuan Republik Indonesia", "Membagi kedaulatan nasional kepada persemakmuran negara-negara bagian", "Memberikan hak penentuan nasib sendiri kepada setiap keresidenan wilayah", "Menyerahkan pengelolaan wilayah terluar kepada komisi pengawasan asing"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesepakatan Mempertahankan Bentuk NKRI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesepakatan dasar nomor dua menegaskan: <strong>sepakat untuk mempertahankan bentuk Negara Kesatuan Republik Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena bentuk negara kesatuan merupakan kesepakatan final pendiri bangsa yang dinilai paling tepat untuk menjaga integrasi ribuan pulau dan kemajemukan suku bangsa Indonesia, yang kemudian dikunci dalam Pasal 37 ayat (5) UUD 1945.</p><p>Jawaban tepat: <strong>B. Sepakat untuk mempertahankan bentuk Negara Kesatuan Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena bentuk konfederasi atau federasi ditolak dalam kesepakatan dasar.</li><li><strong>C</strong>: Kurang tepat karena kedaulatan negara kesatuan bersifat tunggal dan bulat.</li><li><strong>D</strong>: Kurang tepat karena hak pemisahan diri bertentangan dengan keutuhan NKRI.</li><li><strong>E</strong>: Kurang tepat karena wilayah terluar dijaga kedaulatannya secara utuh oleh negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 61) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sepakat untuk mempertahankan bentuk Negara Kesatuan Republik Indonesia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Terkait sistem pemerintahan negara, arah perubahan UUD 1945 yang disepakati oleh fraksi-fraksi di MPR adalah...", "options": ["Beralih menuju sistem kabinet parlementer yang dipimpin perdana menteri", "Menerapkan sistem monarki konstitusional dengan kepala negara turun-temurun", "Sepakat untuk mempertahankan sistem presidensiil dalam tata kelola pemerintahan", "Membentuk kabinet campuran yang bertanggung jawab bersama kepada komite adat", "Mewajibkan presiden membubarkan parlemen saat terjadi ketidaksepahaman"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesepakatan Mempertahankan Sistem Presidensial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesepakatan dasar nomor tiga adalah <strong>sepakat untuk mempertahankan sistem presidensiil</strong> sekaligus memurnikan pelaksanaannya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena sistem presidensial menjamin masa jabatan eksekutif yang pasti (*fixed term*) selama 5 tahun, mencegah instabilitas jatuh-bangun kabinet seperti pada era parlementer, serta memperjelas mekanisme saling mengawasi dan mengimbangi (*checks and balances*).</p><p>Jawaban tepat: <strong>C. Sepakat untuk mempertahankan sistem presidensiil dalam tata kelola pemerintahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena sistem parlementer dihindari demi menjamin stabilitas pemerintahan.</li><li><strong>B</strong>: Kurang tepat karena Indonesia adalah republik demokratis, bukan monarki.</li><li><strong>D</strong>: Kurang tepat karena menteri adalah pembantu presiden yang bertanggung jawab kepada presiden.</li><li><strong>E</strong>: Kurang tepat karena presiden dilarang membubarkan parlemen dalam sistem presidensial UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 61) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sepakat untuk mempertahankan sistem presidensiil</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Penyempurnaan sistematika hukum dalam amandemen UUD 1945 diwujudkan melalui kesepakatan untuk...", "options": ["Menjadikan penjelasan umum sebagai dokumen hukum tertinggi di atas pasal", "Mengalihkan aturan hak asasi manusia ke dalam naskah penjelasan tambahan", "Menghapuskan seluruh asas negara hukum yang tercantum dalam teks konstitusi", "Memindahkan hal-hal normatif dalam Penjelasan UUD ke pasal-pasal", "Menyusun kitab hukum penjelasan tersendiri di luar naskah resmi undang-undang"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemindahan Materi Normatif Penjelasan ke Batang Tubuh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesepakatan dasar nomor empat adalah <strong>sepakat untuk memindahkan hal-hal normatif yang ada dalam Penjelasan UUD 1945 ke dalam pasal-pasal UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena naskah asli Penjelasan UUD 1945 disusun oleh Prof. Soepomo tanpa pengesahan formal BPUPKI/PPKI. Dengan memindahkan norma penting (seperti prinsip negara hukum dan sistem konstitusional) ke batang tubuh pasal, kedudukan hukumnya menjadi berkekuatan mengikat penuh.</p><p>Jawaban tepat: <strong>D. Memindahkan hal-hal normatif dalam Penjelasan UUD ke pasal-pasal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Penjelasan UUD 1945 justru dihapuskan dan materi normatifnya dimasukkan ke pasal.</li><li><strong>B</strong>: Kurang tepat karena aturan HAM dimasukkan ke batang tubuh Bab XA Pasal 28A–28J.</li><li><strong>C</strong>: Kurang tepat karena asas negara hukum justru dimasukkan ke Pasal 1 ayat (3) batang tubuh.</li><li><strong>E</strong>: Kurang tepat karena naskah resmi UUD 1945 pasca-amandemen resmi terdiri atas Pembukaan dan Pasal-pasal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 61) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sepakat untuk memindahkan hal-hal normatif yang ada dalam Penjelasan UUD</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Metode penyusunan naskah perubahan yang disepakati oleh fraksi-fraksi di MPR dalam amandemen UUD 1945 adalah...", "options": ["Mengganti total seluruh naskah konstitusi dan membuang naskah asli proklamasi", "Menyerahkan kodifikasi perubahan pasal kepada dewan perancang internasional", "Menyusun piagam konstitusi darurat tanpa melampirkan teks UUD asli", "Menerbitkan lembaran hukum baru yang meniadakan kesinambungan historis bangsa", "Sepakat untuk menempuh cara adendum dalam melakukan amandemen terhadap UUD 1945"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Teknik Perubahan Konstitusi Melalui Cara Adendum</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesepakatan dasar nomor lima menetapkan: <strong>sepakat untuk menempuh cara adendum dalam melakukan amandemen terhadap UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena cara adendum mempertahankan naskah asli UUD 1945 tetap utuh, dan naskah perubahan (Perubahan I, II, III, dan IV) dilekatkan sebagai lampiran penyempurnaan, sehingga jejak sejarah autentik naskah proklamasi 1945 tetap terpelihara.</p><p>Jawaban tepat: <strong>E. Sepakat untuk menempuh cara adendum dalam melakukan amandemen terhadap UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena metode adendum tidak mengganti total melainkan menempelkan naskah perubahan.</li><li><strong>B</strong>: Kurang tepat karena amandemen dilakukan secara mandiri oleh MPR bangsa Indonesia.</li><li><strong>C</strong>: Kurang tepat karena naskah asli tetap dipertahankan dan disertakan berdampingan.</li><li><strong>D</strong>: Kurang tepat karena adendum justru menjamin kontinuitas kesinambungan historis konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 61) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sepakat untuk menempuh cara adendum dalam melakukan amandemen</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Lima kesepakatan dasar arah perubahan UUD 1945 secara yuridis dituangkan dan dilampirkan dalam...", "options": ["TAP MPR No. IX/MPR/1999 tentang Penugasan Badan Pekerja MPR", "Surat keputusan kementerian kehakiman tentang tim perumus regulasi", "Instruksi presiden mengenai pengamanan aset perbendaharaan negara pasca-pemilu", "Nota kesepahaman antarfraksi partai politik pemilik kursi mayoritas di parlemen", "Ketetapan majelis darurat militer mengenai pemulihan ketertiban umum nasional"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Payung Hukum Lima Kesepakatan Dasar Amandemen</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Lima kesepakatan dasar tersebut secara resmi dilampirkan dalam <strong>Ketetapan MPR No. IX/MPR/1999 tentang Penugasan Badan Pekerja Majelis Permusyawaratan Rakyat Republik Indonesia untuk Melanjutkan Perubahan UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena TAP MPR No. IX/1999 menjadi mandat konstitusional dan pedoman kerja mengikat bagi Panitia Ad Hoc I Badan Pekerja MPR (PAH I BP MPR) dalam merumuskan pasal-pasal amandemen secara bertahap.</p><p>Jawaban tepat: <strong>A. TAP MPR No. IX/MPR/1999 tentang Penugasan Badan Pekerja MPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena amandemen konstitusi adalah wewenang MPR, bukan kementerian kehakiman.</li><li><strong>C</strong>: Kurang tepat karena kesepakatan dasar merupakan produk ketetapan legislatif majelis, bukan inpres.</li><li><strong>D</strong>: Kurang tepat karena kesepakatan telah disahkan menjadi produk hukum Ketetapan MPR formal.</li><li><strong>E</strong>: Kurang tepat karena amandemen berjalan dalam iklim demokrasi sipil reformasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 61) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Lima kesepakatan tersebut dilampirkan dalam Ketetapan MPR No. IX/MPR/1999</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Lembaga pengkaji independen yang dibentuk untuk melakukan pengkajian komprehensif terhadap perubahan UUD 1945 didasarkan pada...", "options": ["Surat edaran mahkamah agung mengenai peninjauan kembali perkara tata usaha", "Ketetapan MPR No. I/MPR/2002 tentang Pembentukan Komisi Konstitusi", "Keputusan dewan pertahanan nasional tentang kesiapsiagaan teritorial wilayah", "Undang-undang pembentukan lembaga peradilan perselisihan hasil pemilihan umum", "Peraturan pemerintah pengganti undang-undang tentang restrukturisasi birokrasi"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembentukan Komisi Konstitusi Tahun 2002</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada Sidang Tahunan 2002 disepakati pembentukan Komisi Konstitusi berdasarkan <strong>Ketetapan MPR No. I/MPR/2002 tentang Pembentukan Komisi Konstitusi</strong> yang bertugas melakukan pengkajian secara komprehensif tentang perubahan UUD 1945.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Komisi Konstitusi (beranggotakan para pakar hukum, akademisi, dan tokoh masyarakat) dibentuk untuk menyelaraskan dan menguji secara ilmiah keseluruhan hasil amandemen UUD 1945 dari Perubahan I hingga Perubahan IV.</p><p>Jawaban tepat: <strong>B. Ketetapan MPR No. I/MPR/2002 tentang Pembentukan Komisi Konstitusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena MA tidak berwenang membentuk Komisi Konstitusi pengkaji UUD.</li><li><strong>C</strong>: Kurang tepat karena dewan pertahanan mengurusi pertahanan militer, bukan pengkajian konstitusi.</li><li><strong>D</strong>: Kurang tepat karena pembentukan peradilan sengketa pemilu adalah kompetensi Mahkamah Konstitusi.</li><li><strong>E</strong>: Kurang tepat karena Komisi Konstitusi dibentuk lewat TAP MPR, bukan lewat Perpu eksekutif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 61) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>perubahan UUD 1945 berdasarkan Ketetapan MPR No. I/MPR/2002 tentang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Tujuan dan arah pokok Perubahan Pertama UUD 1945 yang disahkan pada tanggal 19 Oktober 1999 difokuskan untuk...", "options": ["Membubarkan susunan kementerian dan menggantikannya dengan dewan gubernur", "Menetapkan mata uang tunggal kawasan perdagangan bebas lintas benua", "Membatasi wewenang Presiden dan memperkuat fungsi legislatif DPR", "Mendelegasikan fungsi pertahanan kepulauan kepada pimpinan kepolisian daerah", "Mewajibkan penyelenggaraan referendum sebelum penetapan anggaran belanja"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Substansi Perubahan Pertama UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perubahan Pertama UUD 1945 tahun 1999 yang <strong>arahnya adalah membatasi kekuasaan Presiden dan memperkuat kedudukan Dewan Perwakilan Rakyat (DPR) sebagai lembaga legislatif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pengalaman otoritarianisme Orde Baru bersumber dari kekuasaan eksekutif yang terlalu dominan (*executive heavy*), sehingga agenda awal amandemen adalah membatasi masa jabatan presiden dan menggeser wewenang legislasi utama ke DPR.</p><p>Jawaban tepat: <strong>C. Membatasi wewenang Presiden dan memperkuat fungsi legislatif DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena kementerian tetap dipertahankan di bawah Presiden (Pasal 17).</li><li><strong>B</strong>: Kurang tepat karena pengaturan mata uang dibahas pada Perubahan Keempat, bukan Perubahan Pertama.</li><li><strong>D</strong>: Kurang tepat karena pertahanan tetap berada di bawah TNI (Pasal 30).</li><li><strong>E</strong>: Kurang tepat karena amandemen justru menghapus rezim referendum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 61) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tahunan MPR Tahun 1999 yang arahnya adalah membatasi kekuasaan Presiden dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Materi muatan utama yang disahkan dalam Perubahan Kedua UUD 1945 pada tanggal 18 Agustus 2000 mencakup...", "options": ["Pemberian hak veto eksekutif dalam pembatalan produk perundang-undangan", "Penghapusan bahasa daerah dari kurikulum pendidikan satuan kerja formal", "Penetapan susunan pimpinan mahkamah agung oleh perwakilan serikat buruh", "Masalah wilayah negara dan pembagian pemerintahan daerah serta penegasan HAM", "Pemisahan kas daerah dari pembukuan pertanggungjawaban anggaran nasional"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Materi Pokok Perubahan Kedua UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perubahan Kedua UUD 1945 tahun 2000 <strong>meliputi masalah wilayah negara dan pembagian pemerintahan daerah, menyempurnakan fungsi DPR, serta ketentuan-ketentuan terperinci tentang HAM</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Perubahan Kedua mereformasi Pasal 18, 18A, 18B (otonomi daerah), Pasal 25A (NKRI negara kepulauan nusantara), Pasal 20A (fungsi & hak DPR), dan menambahkan 10 pasal HAM (Pasal 28A s/d 28J).</p><p>Jawaban tepat: <strong>D. Masalah wilayah negara dan pembagian pemerintahan daerah serta penegasan HAM</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena presiden tidak diberi hak veto undang-undang.</li><li><strong>B</strong>: Kurang tepat karena bahasa daerah justru dihormati dan dipelihara oleh negara (Pasal 32).</li><li><strong>C</strong>: Kurang tepat karena serikat buruh tidak memilih pimpinan Mahkamah Agung.</li><li><strong>E</strong>: Kurang tepat karena tata kelola keuangan tetap terintegrasi dalam kerangka NKRI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 61) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>masalah wilayah negara dan pembagian pemerintahan daerah, menyempurnakan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Karakteristik pelaksanaan proses amandemen konstitusi di Indonesia pada era reformasi ditandai oleh metode bahwa...", "options": ["Perubahan pasal disahkan secara serentak dalam satu kali persidangan kilat", "Perubahan naskah melalui musyawarah tertutup antar pimpinan fraksi", "Amandemen diserahkan kepada komite eksekutif kementerian tanpa melibatkan publik", "Pasal-pasal konstitusi diganti secara bergantian berdasarkan dekrit presiden", "Perubahan UUD 1945 bertahap pada Sidang Tahunan MPR 1999–2002"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Tahapan Amandemen UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Perubahan UUD 1945 kemudian dilakukan secara bertahap dan menjadi salah satu agenda Sidang Tahunan MPR dari tahun 1999 hingga perubahan keempat pada Sidang Tahunan MPR tahun 2002</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena proses amandemen empat tahap (1999, 2000, 2001, 2002) memungkinkan pembahasan yang mendalam, matang, partisipatif, serta meminimalkan potensi kegoncangan politik hukum yang drastis (*constitutional continuity*).</p><p>Jawaban tepat: <strong>E. Perubahan UUD 1945 bertahap pada Sidang Tahunan MPR 1999–2002</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena amandemen tidak dilakukan serentak secara kilat melainkan 4 tahap berturut-turut.</li><li><strong>B</strong>: Kurang tepat karena perdebatan pasal berlangsung di komisi dan sidang paripurna MPR yang terbuka.</li><li><strong>C</strong>: Kurang tepat karena amandemen melibatkan dengar pendapat publik dan perdebatan terbuka di MPR.</li><li><strong>D</strong>: Kurang tepat karena amandemen adalah wewenang MPR, bukan produk dekrit eksekutif presiden.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 61) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>agenda Sidang Tahunan MPR dari tahun 1999 hingga perubahan keempat pada</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Ruang lingkup materi pokok yang disahkan dalam Perubahan Ketiga UUD 1945 pada tanggal 9 November 2001 mencakup...", "options": ["Penetapan sistem uni monarki persekutuan dagang kepulauan", "Asas landasan bernegara, kelembagaan negara, dan ketentuan Pemilihan Umum", "Pembubaran peradilan umum dan pembentukan mahkamah militer tertutup", "Pemberian hak veto eksekutif kepada menteri koordinator perekonomian", "Pemisahan kas penerimaan pajak daerah dari neraca perbendaharaan negara"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Materi Pokok Perubahan Ketiga UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Perubahan Ketiga yang ditetapkan pada Sidang Tahunan MPR Tahun 2001 meliputi ketentuan tentang asas-asas landasan bernegara, kelembagaan negara dan hubungan antar lembaga negara, dan ketentuan-ketentuan tentang Pemilihan Umum yang ditetapkan pada tanggal 9 November 2001</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Perubahan Ketiga merupakan amandemen paling substantif yang meletakkan fondasi demokrasi modern: pemilihan presiden langsung, pembentukan MK, KY, DPD, serta bab pemilu.</p><p>Jawaban tepat: <strong>B. Asas landasan bernegara, kelembagaan negara, dan ketentuan Pemilihan Umum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Indonesia adalah negara kesatuan republik, bukan uni monarki.</li><li><strong>C</strong>: Kurang tepat karena peradilan umum tetap dipertahankan di bawah naungan Mahkamah Agung.</li><li><strong>D</strong>: Kurang tepat karena konstitusi tidak mengenal hak veto bagi pejabat menteri eksekutif.</li><li><strong>E</strong>: Kurang tepat karena neraca keuangan negara tetap terpadu dalam kerangka kesatuan fiskal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 62) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Perubahan Ketiga yang ditetapkan pada Sidang Tahunan MPR Tahun 2001</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Salah satu agenda reformasi struktur kelembagaan negara yang resmi ditetapkan dalam Perubahan Keempat UUD 1945 adalah...", "options": ["Pemberian wewenang yudisial kepada dewan perwakilan rakyat daerah", "Pembentukan kementerian penerangan khusus sensor publikasi media cetak", "Penghapusan Dewan Pertimbangan Agung (DPA) dari struktur lembaga tinggi", "Pengalihan fungsi kasasi peradilan kepada majelis permusyawaratan dewan", "Penetapan status keanggotaan dewan perwakilan rakyat seumur hidup"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Materi Pokok Perubahan Keempat UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Materi perubahan pada Perubahan Keempat meliputi ketentuan tentang kelembagaan negara dan hubungan antar lembaga negara, <strong>penghapusan Dewan Pertimbangan Agung (DPA)</strong>, pendidikan, kebudayaan, perekonomian, dan kesejahteraan sosial.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Perubahan Keempat menyempurnakan perampingan struktur negara dengan menghapus DPA dan memberikan wewenang kepada Presiden untuk membentuk dewan pertimbangan internal (Wantimpres).</p><p>Jawaban tepat: <strong>C. Penghapusan Dewan Pertimbangan Agung (DPA) dari struktur lembaga tinggi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena DPRD memegang fungsi legislatif daerah, bukan wewenang peradilan yudisial.</li><li><strong>B</strong>: Kurang tepat karena era reformasi menjamin kebebasan pers serta menghapus praktik sensor otoriter.</li><li><strong>D</strong>: Kurang tepat karena fungsi kasasi mutlak berada pada Mahkamah Agung, bukan MPR.</li><li><strong>E</strong>: Kurang tepat karena masa jabatan anggota DPR dibatasi periodik 5 tahun melalui pemilu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 62) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dan hubungan antar lembaga negara, penghapusan Dewan Pertimbangan Agung</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Rangkaian empat tahapan amandemen konstitusi Republik Indonesia secara resmi dituntaskan dan disahkan pada...", "options": ["Tanggal 19 Oktober 1999 pada penutupan sidang umum pertama reformasi", "Tanggal 9 November 2001 saat pembentukan mahkamah konstitusi disetujui", "Tanggal 18 Agustus 2000 bertepatan dengan pengesahan bab hak asasi", "Ditetapkan pada tanggal 10 Agustus 2002 pada Sidang Tahunan MPR", "Tanggal 17 Agustus 1950 saat pembubaran negara serikat hasil perundingan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggal Penetapan Perubahan Keempat UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perubahan Keempat UUD 1945 <strong>ditetapkan pada tanggal 10 Agustus 2002</strong> dalam Sidang Tahunan MPR RI, menandai selesainya pembaruan komprehensif konstitusi Indonesia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena tanggal 10 Agustus 2002 menjadi tonggak penyelesaian agenda reformasi konstitusi yang dimulai sejak 1999, menghasilkan naskah UUD 1945 yang modern dan demokratis.</p><p>Jawaban tepat: <strong>D. Ditetapkan pada tanggal 10 Agustus 2002 pada Sidang Tahunan MPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena 19 Oktober 1999 adalah tanggal penetapan Perubahan Pertama.</li><li><strong>B</strong>: Kurang tepat karena 9 November 2001 adalah tanggal penetapan Perubahan Ketiga.</li><li><strong>C</strong>: Kurang tepat karena 18 Agustus 2000 adalah tanggal penetapan Perubahan Kedua.</li><li><strong>E</strong>: Kurang tepat karena 17 Agustus 1950 adalah peringatan kembalinya NKRI pasca-RIS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 62) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>ditetapkan pada tanggal 10 Agustus 2002</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Perbandingan jumlah materi muatan antara naskah asli dengan naskah pasca-empat kali amandemen UUD 1945 tercermin pada data bahwa...", "options": ["Naskah asli berisi 100 butir ketentuan dan menyusut menjadi 50 butir aturan", "Materi muatan awal sebanyak 80 butir diringkas menjadi 60 butir klausul", "Naskah asli memuat 37 pasal dan dipadatkan menjadi 20 pasal regulasi", "Jumlah ketentuan konstitusi bertambah dari 45 butir menjadi tepat 300 butir", "Naskah asli berisi 71 butir ketentuan, setelah diubah menjadi 199"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perbandingan Kuantitatif Butir Ketentuan Konstitusi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jika naskah asli UUD 1945 berisi 71 butir ketentuan, maka setelah empat kali mengalami perubahan, materi muatan UUD 1945 mencakup 199 butir ketentuan</strong>, meningkat hampir tiga kali lipat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena penambahan ayat dan pasal baru (terutama mengenai rincian HAM, DPD, MK, KY, BPK, dan Pemilu) membuat UUD 1945 jauh lebih terperinci dan memberikan kepastian hukum yang kuat.</p><p>Jawaban tepat: <strong>E. Naskah asli berisi 71 butir ketentuan, setelah diubah menjadi 199</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena materi muatan konstitusi bertambah banyak, bukan menyusut.</li><li><strong>B</strong>: Kurang tepat karena data statistik modul menyebutkan perbandingan 71 menjadi 199 butir.</li><li><strong>C</strong>: Kurang tepat karena jumlah pasal bertambah melalui penomoran huruf (seperti 28A–28J).</li><li><strong>D</strong>: Kurang tepat karena angka kuantitatif resmi modul adalah 71 menjadi 199 butir ketentuan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 62) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>naskah asli UUD 1945 berisi 71 butir ketentuan, maka setelah empat kali mengalami</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Penetapan Perubahan Keempat UUD 1945 pada bagian penutup menyempurnakan instrumen transisi hukum yang terdiri atas...", "options": ["Aturan Peralihan Pasal I, II, III dan Aturan Tambahan Pasal I, II", "Empat pasal aturan peralihan dan lima pasal klausul darurat perang", "Satu pasal ketentuan penutup dan tiga pasal deklarasi pembentukan serikat", "Dua pasal tata cara referendum dan satu pasal pembatasan amandemen dewan", "Lima pasal ketentuan transisi peradilan dan dua pasal pembekuan kabinet"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyempurnaan Aturan Peralihan dan Aturan Tambahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perubahan Keempat UUD 1945 menata kembali ketentuan transisi menjadi: <strong>Aturan Peralihan Pasal I, II, dan III; serta Aturan Tambahan Pasal I dan II UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Aturan Peralihan mengatur keberlakuan peraturan perundang-undangan lama, pembentukan Mahkamah Konstitusi selambat-lambatnya 17 Agustus 2003, dan wewenang transisi MA; sementara Aturan Tambahan menugaskan MPR meninjau ketetapan MPRS/MPR serta menegaskan struktur naskah UUD 1945.</p><p>Jawaban tepat: <strong>A. Aturan Peralihan Pasal I, II, III dan Aturan Tambahan Pasal I, II</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena Aturan Peralihan pasca-amandemen terdiri dari 3 pasal, bukan 4 pasal.</li><li><strong>C</strong>: Kurang tepat karena format formal terdiri dari 3 pasal Peralihan dan 2 pasal Tambahan.</li><li><strong>D</strong>: Kurang tepat karena aturan referendum telah dicabut dan ditiadakan.</li><li><strong>E</strong>: Kurang tepat karena susunan baku konstitusi memuat 3 pasal Peralihan dan 2 pasal Tambahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 62) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Aturan Peralihan Pasal I, II, dan III; Aturan Tambahan Pasal I dan II</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Dalam Perubahan Keempat UUD 1945, penyempurnaan terhadap Bab XIV ditujukan untuk mempertegas...", "options": ["Pemberian izin monopoli perdagangan kepada konglomerasi perbankan swasta", "Ketentuan tentang perekonomian nasional dan jaminan kesejahteraan sosial rakyat", "Penarikan seluruh dana simpanan masyarakat untuk dialihkan ke kas pertahanan", "Penghapusan jaminan pemeliharaan fakir miskin dan anak terlantar oleh negara", "Penetapan upah buruh minimum secara langsung tanpa koordinasi dewan pengupahan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyempurnaan Pasal Ekonomi dan Kesejahteraan Sosial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Materi perubahan pada Perubahan Keempat mencakup <strong>ketentuan tentang perekonomian dan kesejahteraan sosial</strong> (Bab XIV Pasal 33 ayat 4 dan 5 serta Pasal 34 ayat 1, 2, 3, dan 4).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena amandemen menambahkan prinsip efisiensi berkeadilan, keberlanjutan, berwawasan lingkungan, dan kemandirian dalam Pasal 33, serta mewajibkan negara mengembangkan sistem jaminan sosial dan memelihara fakir miskin dalam Pasal 34.</p><p>Jawaban tepat: <strong>B. Ketentuan tentang perekonomian nasional dan jaminan kesejahteraan sosial rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Pasal 33 melarang monopoli swasta yang menguasai hajat hidup orang banyak.</li><li><strong>C</strong>: Kurang tepat karena negara menjamin perlindungan hak milik dan simpanan warga.</li><li><strong>D</strong>: Kurang tepat karena negara justru diwajibkan memelihara fakir miskin (Pasal 34 ayat 1).</li><li><strong>E</strong>: Kurang tepat karena regulasi ketenagakerjaan diatur secara adil dalam undang-undang ketenagakerjaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 62) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>perekonomian dan kesejahteraan sosial, dan aturan peralihan serta aturan tambahan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pembaruan substansi dalam Bab XIII UUD 1945 melalui Perubahan Keempat memuat jaminan fundamental mengenai...", "options": ["Pembatasan akses pendidikan tinggi khusus bagi kalangan birokrasi tertentu", "Penghapusan mata pelajaran sejarah perjuangan kemerdekaan dari kurikulum", "Pendidikan dan kebudayaan termasuk anggaran minimal 20% APBN", "Pelarangan pelestarian kesenian tari adat tradisional di lingkungan sekolah", "Penetapan bahasa asing sebagai bahasa pengantar wajib pada sekolah dasar"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Reformasi Pendidikan Nasional Pada Amandemen Keempat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perubahan Keempat UUD 1945 menyempurnakan <strong>ketentuan tentang pendidikan dan kebudayaan</strong> (Pasal 31 dan Pasal 32), termasuk mewajibkan alokasi anggaran pendidikan sekurang-kurangnya 20% dari APBN dan APBD.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Pasal 31 ayat (4) menjamin komitmen pendanaan negara untuk mencerdaskan kehidupan bangsa, sementara Pasal 32 menjamin kemajuan kebudayaan nasional dan penghormatan bahasa daerah.</p><p>Jawaban tepat: <strong>C. Pendidikan dan kebudayaan termasuk anggaran minimal 20% APBN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena setiap warga negara berhak mendapatkan pendidikan tanpa diskriminasi (Pasal 31 ayat 1).</li><li><strong>B</strong>: Kurang tepat karena pendidikan nasional menumbuhkan nilai patriotisme dan kepribadian bangsa.</li><li><strong>D</strong>: Kurang tepat karena negara memajukan kebudayaan nasional di tengah peradaban dunia.</li><li><strong>E</strong>: Kurang tepat karena Bahasa Indonesia adalah bahasa resmi negara yang diutamakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 62) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pendidikan • kebudayaan,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Keluasan jangkauan materi amandemen yang diselesaikan oleh MPR pada tahun 2002 dicirikan oleh realitas bahwa...", "options": ["Amandemen sekadar mengubah susunan satu pasal administratif pemilihan dewan", "Perubahan sekadar berfokus pada nama kementerian di bidang perekonomian negara", "MPR menunda seluruh usulan perubahan pasal yang disodorkan kalangan kampus", "Perubahan-perubahan tersebut meliputi hampir keseluruhan materi UUD 1945", "Naskah undang-undang dasar sama sekali tidak mengalami penambahan klausul"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keluasan Lingkup Amandemen UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Perubahan-perubahan tersebut meliputi hampir keseluruhan materi UUD 1945</strong>, mengubah struktur ketatanegaraan dari sistem sentralistik mandataris MPR menuju sistem demokrasi pemisahan kekuasaan yang seimbang (*checks and balances*).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena amandemen menyentuh hampir seluruh bab (kelembagaan, HAM, pemilu, pemda, ekonomi, peradilan, dan aturan peralihan), tanpa mengubah Pembukaan dan bentuk NKRI.</p><p>Jawaban tepat: <strong>D. Perubahan-perubahan tersebut meliputi hampir keseluruhan materi UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena amandemen mencakup 199 butir ketentuan, bukan sekadar satu pasal.</li><li><strong>B</strong>: Kurang tepat karena ruang lingkup amandemen bersifat menyeluruh di seluruh cabang kekuasaan.</li><li><strong>C</strong>: Kurang tepat karena MPR menyerap aspirasi luas masyarakat dan perguruan tinggi.</li><li><strong>E</strong>: Kurang tepat karena materi ketentuan bertambah dari 71 menjadi 199 butir.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 62) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Perubahan-perubahan tersebut meliputi hampir keseluruhan materi UUD 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Payung hukum konstitusional penyelenggaraan pesta demokrasi secara periodik lima tahunan disahkan melalui...", "options": ["Instruksi darurat panglima militer mengenai pengamanan kotak suara wilayah", "Surat edaran mahkamah agung tentang penanganan sengketa perselisihan dewan", "Maklumat kementerian penerangan mengenai jadwal siaran kampanye pemilu", "Piagam kesepakatan tertutup antar pimpinan partai politik pemilik suara", "Ketentuan Pemilihan Umum yang disahkan pada 9 November 2001"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penetapan Regulasi Pemilu Dalam Amandemen Ketiga</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perubahan Ketiga menetapkan <strong>ketentuan-ketentuan tentang Pemilihan Umum yang ditetapkan pada tanggal 9 November 2001</strong> yang dituangkan dalam Bab VIIB Pasal 22E UUD 1945.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Pasal 22E UUD 1945 memberikan jaminan bahwa pemilu wajib dilaksanakan secara LUBER dan JURDIL setiap lima tahun sekali serta diselenggarakan oleh komisi pemilihan umum yang bersifat nasional, tetap, dan mandiri.</p><p>Jawaban tepat: <strong>E. Ketentuan Pemilihan Umum yang disahkan pada 9 November 2001</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pemilu diselenggarakan oleh lembaga sipil KPU, bukan instruksi militer.</li><li><strong>B</strong>: Kurang tepat karena sengketa perselisihan pemilu diadili oleh Mahkamah Konstitusi.</li><li><strong>C</strong>: Kurang tepat karena landasan pemilu adalah pasal konstitusi UUD, bukan maklumat penerangan.</li><li><strong>D</strong>: Kurang tepat karena asas pemilu bertumpu pada hukum dasar tertulis yang terbuka bagi publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 62) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>yang ditetapkan pada tanggal 9 November 2001. Perubahan tersebut mengubah dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Meskipun amandemen mengubah 199 butir ketentuan batang tubuh, komitmen MPR yang tetap terjaga secara konsisten adalah bahwa...", "options": ["Pembukaan UUD 1945 tidak akan diubah dan tetap dipertahankan keutuhannya", "Struktur kabinet presidensial dapat diganti menjadi kabinet parlementer", "Batas wilayah kedaulatan kepulauan diserahkan kepada persekutuan asing", "Hak pilih pemilu warga negara dapat dibatasi berdasarkan latar belakang suku", "Kewenangan peradilan kasasi dialihkan kepada komisi etik asosiasi advokat"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsistensi Menjaga Kemurnian Pembukaan UUD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai dengan kesepakatan MPR yang menjadi lampiran Ketetapan MPR No. IX/MPR/1999, ditegaskan bahwa <strong>Pembukaan UUD 1945 tidak akan diubah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Pembukaan UUD 1945 memuat dasar filsafat berdirinya NKRI dan Pancasila. Kesepakatan untuk tidak menyentuh Pembukaan menjamin bahwa identitas jati diri bangsa dan tujuan luhur proklamasi tetap abadi.</p><p>Jawaban tepat: <strong>A. Pembukaan UUD 1945 tidak akan diubah dan tetap dipertahankan keutuhannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena kesepakatan dasar justru mempertahankan sistem presidensial.</li><li><strong>C</strong>: Kurang tepat karena kedaulatan wilayah NKRI dipertahankan secara utuh.</li><li><strong>D</strong>: Kurang tepat karena asas pemilu menjamin perlakuan setara bagi seluruh warga tanpa rasisme.</li><li><strong>E</strong>: Kurang tepat karena kasasi tetap menjadi wewenang konstitusional Mahkamah Agung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 62) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pembukaan UUD 1945 tidak akan diubah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pengaruh mendasar dari rangkaian amandemen UUD 1945 terhadap bentuk kenegaraan Indonesia adalah...", "options": ["Membuka peluang formal pembentukan negara bagian serikat di daerah kepulauan", "Menghapuskan batas otonomi teritorial demi sentralisasi kekuasaan kementerian", "Perubahan UUD 1945 mengukuhkan keberadaan Indonesia sebagai Negara Kesatuan", "Mendelegasikan fungsi pertahanan kepada milisi daerah otonom masing-masing", "Mengalihkan kedaulatan teritorial kepulauan kepada persekutuan monarki"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengukuhan Eksistensi Bentuk Negara Kesatuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Perubahan UUD 1945 mengukuhkan keberadaan Indonesia sebagai Negara Kesatuan dan menghilangkan keraguan terhadap pecahnya negara kesatuan Republik Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena amandemen memberikan kepastian hukum bahwa reformasi dan otonomi daerah bukan langkah menuju disintegrasi atau federalisme, melainkan upaya memperkuat integrasi nasional dalam bingkai NKRI.</p><p>Jawaban tepat: <strong>C. Perubahan UUD 1945 mengukuhkan keberadaan Indonesia sebagai Negara Kesatuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pasal-pasal amandemen secara tegas menepis bentuk negara bagian/serikat.</li><li><strong>B</strong>: Kurang tepat karena amandemen justru menjamin pelaksanaan otonomi daerah seluas-luasnya (Pasal 18).</li><li><strong>D</strong>: Kurang tepat karena pertahanan negara diselenggarakan tunggal melalui TNI.</li><li><strong>E</strong>: Kurang tepat karena kedaulatan berada di tangan rakyat dalam bentuk republik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 63) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Perubahan UUD 1945 mengukuhkan keberadaan Indonesia sebagai Negara Kesatuan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Keteguhan para pembentuk konstitusi era reformasi dalam menjaga konsensus nasional dibuktikan oleh fakta bahwa pasal-pasal UUD 1945...", "options": ["Memberikan hak bagi daerah penghasil migas untuk membentuk mata uang regional", "Menghapuskan ikatan persatuan bangsa demi kebebasan mutlak kelompok minoritas", "Mengizinkan pembentukan angkatan bersenjata mandiri di setiap provinsi terluar", "Tidak mengubah bentuk Negara Kesatuan Republik Indonesia", "Menyerahkan urusan diplomasi internasional kepada pejabat eksekutif daerah"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keteguhan Mempertahankan Bentuk NKRI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal-pasal dalam UUD 1945 telah memperkokoh prinsip Negara Kesatuan Republik Indonesia dan <strong>tidak sedikitpun mengubah Negara Kesatuan Republik Indonesia menjadi bentuk negara yang lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena komitmen menjaga bentuk negara kesatuan telah dikunci secara abadi (*entrenched*) dalam Pasal 37 ayat (5) UUD 1945 yang menyatakan bentuk NKRI tidak dapat dilakukan perubahan.</p><p>Jawaban tepat: <strong>D. Tidak mengubah bentuk Negara Kesatuan Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena mata uang adalah urusan mutlak pemerintah pusat (Pasal 23B).</li><li><strong>B</strong>: Kurang tepat karena prinsip persatuan bangsa dijunjung tinggi dalam Pancasila dan UUD 1945.</li><li><strong>C</strong>: Kurang tepat karena TNI adalah kekuatan pertahanan tunggal negara (Pasal 30).</li><li><strong>E</strong>: Kurang tepat karena urusan hubungan luar negeri merupakan wewenang Presiden dan pemerintah pusat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 63) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Republik Indonesia dan tidak sedikitpun mengubah Negara Kesatuan Republik</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Hubungan substansial antara desentralisasi dengan kedaulatan negara dalam naskah perubahan UUD 1945 dirumuskan dengan tujuan untuk...", "options": ["Mempermudah pemisahan wilayah terpencil dari kesatuan hukum nasional", "Menghapuskan tata kelola perizinan usaha mikro di tingkat pemerintahan desa", "Mengurangi alokasi anggaran pembangunan di luar wilayah ibu kota negara", "Membatasi mobilitas perpindahan penduduk antardaerah di seluruh kepulauan", "Mendorong pelaksanaan otonomi daerah untuk lebih memperkokoh Negara Kesatuan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Otonomi Daerah Sebagai Pilar Pengokoh NKRI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pasal-pasal dalam UUD 1945 mendorong pelaksanaan otonomi daerah untuk lebih memperkokoh Negara Kesatuan Republik Indonesia</strong> dan meningkatkan proses pembangunan di daerah dalam rangka mewujudkan kesejahteraan masyarakat di daerah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena otonomi daerah yang adil dan demokratis menghilangkan ketidakpuasan antardaerah, mempercepat pemerataan pembangunan, dan menumbuhkan rasa kepemilikan bersama terhadap NKRI.</p><p>Jawaban tepat: <strong>E. Mendorong pelaksanaan otonomi daerah untuk lebih memperkokoh Negara Kesatuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena otonomi daerah bertujuan memperkokoh persatuan, bukan pemisahan wilayah.</li><li><strong>B</strong>: Kurang tepat karena otonomi daerah mendukung kemudahan perizinan ekonomi lokal.</li><li><strong>C</strong>: Kurang tepat karena otonomi daerah justru memperluas penyaluran transfer anggaran ke daerah.</li><li><strong>D</strong>: Kurang tepat karena setiap warga negara bebas berpindah dan bertempat tinggal di wilayah NKRI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 63) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>memperkokoh Negara Kesatuan Republik Indonesia dan meningkatkan proses</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pasal 1 ayat (1) UUD 1945 naskah asli yang tetap dipertahankan utuh dalam amandemen memuat rumusan prinsip bahwa...", "options": ["Negara Indonesia ialah negara kesatuan yang berbentuk Republik", "Kekuasaan tertinggi negara berada pada majelis perwakilan utusan daerah", "Pemerintahan dipimpin oleh kabinet parlementer di bawah kendali parlemen", "Indonesia merupakan persemakmuran yang terdiri atas kesultanan otonom", "Birokrasi pemerintahan dijalankan berdasarkan hukum kebiasaan adat wilayah"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Bentuk Negara Pasal 1 Ayat 1</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 1 ayat (1) UUD 1945 yang merupakan naskah asli mengandung prinsip bahwa <strong>'Negara Indonesia ialah negara kesatuan yang berbentuk Republik'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pasal ini merupakan klausul fundamental yang menetapkan dua identitas utama negara: bentuk negara adalah Kesatuan (*unitary state*) dan bentuk pemerintahan adalah Republik (*res publica*).</p><p>Jawaban tepat: <strong>A. Negara Indonesia ialah negara kesatuan yang berbentuk Republik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena kekuasaan tertinggi berada di tangan rakyat menurut konstitusi (Pasal 1 ayat 2).</li><li><strong>C</strong>: Kurang tepat karena sistem pemerintahan Indonesia adalah presidensial, bukan parlementer.</li><li><strong>D</strong>: Kurang tepat karena Indonesia adalah republik kesatuan, bukan persemakmuran kesultanan.</li><li><strong>E</strong>: Kurang tepat karena birokrasi berlandaskan pada negara hukum modern (Pasal 1 ayat 3).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 63) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Negara Indonesia ialah negara kesatuan yang berbentuk Republik</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Perumusan Pasal 1 ayat (1) oleh para pendiri bangsa pada hakikatnya merupakan kelanjutan dari...", "options": ["Perjanjian persekutuan dagang antarpulau era kolonial abad ketujuh belas", "Sumpah Pemuda 1928 sebagai ikrar persatuan kebangsaan anak bangsa", "Nota perundingan gencatan senjata di hadapan utusan perdamaian internasional", "Keputusan rapat koordinasi pimpinan serikat buruh transportasi kereta api", "Maklumat komando pertahanan wilayah kepulauan bentukan militer asing"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akar Historis NKRI Pada Sumpah Pemuda 1928</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 1 ayat (1) UUD 1945 yang dirumuskan oleh PPKI merupakan <strong>tekad bangsa Indonesia yang menjadi sumpah anak bangsa pada 1928 yang dikenal dengan Sumpah Pemuda</strong>, yaitu satu nusa, satu bangsa, satu bahasa persatuan, satu tanah air yaitu Indonesia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Sumpah Pemuda 28 Oktober 1928 meletakkan kesadaran geopolitik bangsa Indonesia, yang kemudian diformalkan ke dalam hukum tata negara positif oleh PPKI pada 18 Agustus 1945.</p><p>Jawaban tepat: <strong>B. Sumpah Pemuda 1928 sebagai ikrar persatuan kebangsaan anak bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena Sumpah Pemuda menentang kolonialisme dagang VOC/Belanda.</li><li><strong>C</strong>: Kurang tepat karena integrasi NKRI lahir dari tekad persatuan pemuda, bukan perundingan asing.</li><li><strong>D</strong>: Kurang tepat karena Sumpah Pemuda mencakup seluruh pemuda nusantara dari berbagai organisasi kedaerahan.</li><li><strong>E</strong>: Kurang tepat karena semangat persatuan murni bersumber dari pergerakan nasional Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 63) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tekad bangsa Indonesia yang menjadi sumpah anak bangsa pada 1928 yang dikenal</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Sikap keteladanan yang dijadikan pedoman dasar oleh MPR periode 1999–2004 dalam mengawal arah amandemen konstitusi adalah...", "options": ["Keinginan mengadopsi secara mutlak sistem tata negara persemakmuran barat", "Upaya memperluas kewenangan eksekutif tanpa adanya mekanisme pengawasan parlemen", "Penghargaan cita-cita pendiri bangsa mewujudkan negara bersatu", "Kecenderungan membatasi kebebasan berserikat dan berkumpul bagi masyarakat sipil", "Tuntutan pembagian dana perimbangan secara sewenang di luar kerangka perundangan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pedoman Dasar MPR Era Amandemen</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Penghargaan terhadap cita-cita luhur para pendiri bangsa (The Founding Fathers) yang menginginkan Indonesia sebagai negara bangsa yang satu merupakan bagian dari pedoman dasar bagi MPR 1999-2004 dalam melakukan perubahan UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena MPR era reformasi tetap setia pada jiwa proklamasi dan visi kebangsaan para pendiri bangsa dalam mewujudkan negara merdeka, bersatu, berdaulat, adil, dan makmur.</p><p>Jawaban tepat: <strong>C. Penghargaan cita-cita pendiri bangsa mewujudkan negara bersatu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena amandemen berakar dari kepribadian bangsa dan kebutuhan demokrasi nasional.</li><li><strong>B</strong>: Kurang tepat karena amandemen justru membatasi kekuasaan eksekutif dan memperkuat checks and balances.</li><li><strong>D</strong>: Kurang tepat karena jaminan kebebasan berserikat justru dipertegas dalam Pasal 28E ayat (3).</li><li><strong>E</strong>: Kurang tepat karena dana perimbangan diatur secara transparan melalui undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 63) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>(The Founding Fathers) yang menginginkan Indonesia sebagai negara bangsa yang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Konsensus dasar nasional mengenai kedudukan Negara Kesatuan Republik Indonesia pasca-perubahan UUD 1945 menegaskan status bahwa NKRI adalah...", "options": ["Bentuk transisi sementara menuju pembentukan konfederasi monarki kepulauan", "Entitas kedaulatan yang bergantung pada persetujuan komisi internasional", "Format persekutuan wilayah yang dapat diubah melalui referendum daerah", "Bentuk final negara bagi bangsa Indonesia yang tidak dapat ditawar lagi", "Susunan pemerintahan yang dapat dibubarkan sewaktu-waktu oleh dekrit menteri"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Final Negara Bagi Bangsa Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wujud NKRI semakin kokoh setelah amandemen, yang dimulai dari kesepakatan MPR untuk <strong>tetap mempertahankan Negara Kesatuan Republik Indonesia sebagai bentuk final negara bagi bangsa Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena status NKRI sebagai bentuk final (*final form of the state*) merupakan kesepakatan abadi yang mengikat seluruh generasi dan menjadi harga mati bagi kelangsungan hidup bangsa.</p><p>Jawaban tepat: <strong>D. Bentuk final negara bagi bangsa Indonesia yang tidak dapat ditawar lagi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena NKRI adalah bentuk permanen dan final, bukan transisi.</li><li><strong>B</strong>: Kurang tepat karena kedaulatan NKRI mandiri dan merdeka dari campur tangan pihak asing.</li><li><strong>C</strong>: Kurang tepat karena bentuk NKRI tidak dapat diubah oleh mekanisme apa pun (Pasal 37 ayat 5).</li><li><strong>E</strong>: Kurang tepat karena dekrit menteri tidak berwenang membubarkan bentuk negara kesatuan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 63) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Negara Kesatuan Republik Indonesia sebagai bentuk final negara bagi bangsa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pertimbangan sosiologis utama yang melandasi pemilihan bentuk negara kesatuan bagi bangsa Indonesia adalah keyakinan bahwa bentuk ini...", "options": ["Memudahkan pemusatan seluruh sumber daya tambang di tangan elit ibu kota", "Memberikan kekuasaan mutlak kepada pimpinan angkatan perang bersenjata", "Dapat meniadakan perbedaan latar belakang etnisitas secara paksa", "Menghilangkan hak masyarakat daerah untuk menyampaikan aspirasi politik", "Paling tepat untuk mewadahi ide persatuan sebuah bangsa yang majemuk"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rasionalitas Pemilihan Bentuk Negara Kesatuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesepakatan mempertahankan bentuk negara kesatuan didasari pertimbangan bahwa negara kesatuan adalah bentuk yang ditetapkan sejak awal berdirinya negara dan <strong>dipandang paling tepat untuk mewadahi ide persatuan sebuah bangsa yang majemuk</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kemajemukan suku, agama, ras, dan adat istiadat memerlukan wadah pemersatu yang kokoh (Bhinneka Tunggal Ika) guna mencegah disintegrasi dan rivalitas antardaerah seperti yang terjadi pada masa negara federal RIS.</p><p>Jawaban tepat: <strong>E. Paling tepat untuk mewadahi ide persatuan sebuah bangsa yang majemuk</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurang tepat karena pemusatan sumber daya tambang bertentangan dengan prinsip keadilan sosial.</li><li><strong>B</strong>: Kurang tepat karena negara kesatuan berlandaskan pada supremasi hukum dan demokrasi perwakilan.</li><li><strong>C</strong>: Kurang tepat karena negara kesatuan menghargai dan melindungi kemajemukan etnis, bukan meniadakannya.</li><li><strong>D</strong>: Kurang tepat karena aspirasi daerah disalurkan secara konstitusional lewat DPD, DPR, dan DPRD.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 63) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>persatuan sebuah bangsa yang majemuk.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Agar pelaksanaan otonomi daerah selaras dengan hakikat tujuan pembangunan nasional dan tidak menyimpang dari NKRI, UUD 1945 mengamanatkan bahwa...", "options": ["Perlu pengaturan undang-undang komprehensif bagi otonomi daerah", "Pemerintah pusat wajib membekukan seluruh kewenangan pembentukan perda", "Seluruh pejabat daerah diangkat dari aparatur kementerian pusat", "Pemerintah daerah dilarang menyusun anggaran pendapatan belanja secara mandiri", "Tata kelola keuangan daerah diserahkan kepada perwakilan perbankan komersial"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi Pelaksanaan Otonomi Daerah Komprehensif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam pelaksanaan desentralisasi: <strong>diperlukan adanya pengaturan dalam peraturan perundang-undangan yang komprehensif untuk pelaksanaan otonomi daerah sehingga dapat dilaksanakan sesuai dengan hakikat tujuan pembangunan nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena payung hukum komprehensif (seperti UU Pemerintahan Daerah dan UU Hubungan Keuangan Pusat-Daerah) menjamin otonomi berjalan tertib, transparan, akuntabel, dan sinergis dengan program strategis nasional.</p><p>Jawaban tepat: <strong>A. Perlu pengaturan undang-undang komprehensif bagi otonomi daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kurang tepat karena pemda berhak menetapkan perda untuk melaksanakan otonomi (Pasal 18 ayat 6).</li><li><strong>C</strong>: Kurang tepat karena pemda membina ASN daerah sesuai manajemen kepegawaian nasional.</li><li><strong>D</strong>: Kurang tepat karena pemda bersama DPRD berwenang menetapkan APBD.</li><li><strong>E</strong>: Kurang tepat karena keuangan daerah dikelola melalui Kas Umum Daerah yang diaudit BPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 63) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>daerah. Oleh karena itu, diperlukan adanya pengaturan dalam peraturan perundangundangan yang komprehensif untuk pelaksanaan otonomi daerah sehingga dapat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Alasan utama kesepakatan MPR mempertahankan Negara Kesatuan Republik Indonesia sebagai bentuk final negara adalah karena dinilai paling tepat untuk...", "options": ["Memusatkan seluruh pengelolaan penerimaan pajak daerah", "Mewadahi ide persatuan sebuah bangsa yang majemuk", "Menyeragamkan struktur lembaga adat di seluruh kepulauan", "Menghapuskan garis batas administratif antarprovinsi", "Membatasi mobilitas penduduk antarpulau di nusantara"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Mempertahankan Bentuk Negara Kesatuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesepakatan mempertahankan bentuk <strong>Negara Kesatuan Republik Indonesia sebagai bentuk final</strong> didasari pertimbangan bahwa negara kesatuan dipandang paling tepat untuk <strong>mewadahi ide persatuan sebuah bangsa yang majemuk</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena bentuk negara kesatuan merupakan wadah yang paling kokoh dan representatif dalam mengintegrasikan keanekaragaman suku, agama, dan budaya di seluruh wilayah nusantara. Pilihan bentuk ini telah dikukuhkan sejak awal kemerdekaan guna mencegah potensi perpecahan bangsa. Kesepakatan konstitusional ini menjadi pilar utama keutuhan geopolitik nasional.</p><p>Jawaban tepat: <strong>B. Mewadahi ide persatuan sebuah bangsa yang majemuk</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengelolaan pajak daerah diatur dalam kerangka desentralisasi fiskal dan perimbangan keuangan.</li><li><strong>C</strong>: Menyeragamkan lembaga adat bertentangan dengan prinsip penghormatan kesatuan masyarakat hukum adat.</li><li><strong>D</strong>: Menghapuskan batas administratif tidak sesuai dengan pembagian wilayah pemerintahan daerah provinsi dan kabupaten/kota.</li><li><strong>E</strong>: Membatasi mobilitas penduduk bertentangan dengan hak asasi warga negara untuk berpindah tempat tinggal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 63) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>persatuan sebuah bangsa yang majemuk.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berapa jumlah pasal dalam konstitusi UUD 1945 yang secara langsung menyebutkan istilah Negara Kesatuan Republik Indonesia?", "options": ["Tiga pasal konstitusi", "Empat pasal konstitusi", "Enam pasal konstitusi", "Lima pasal konstitusi", "Tujuh pasal konstitusi"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal UUD 1945 yang Menyebutkan NKRI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UUD 1945 memuat rumusan yang secara langsung menyebutkan frasa <strong>Negara Kesatuan Republik Indonesia dalam lima pasal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena UUD 1945 secara eksplisit memuat penyebutan NKRI dalam lima pasal utama, yakni Pasal 1 ayat (1), Pasal 18 ayat (1), Pasal 18B ayat (2), Pasal 25A, dan Pasal 37 ayat (5). Ketentuan konstitusional ini menegaskan komitmen pendiri bangsa untuk menjaga keutuhan susunan negara kepulauan Indonesia. Penegasan bentuk kesatuan tercermin kuat dalam batang tubuh konstitusi negara.</p><p>Jawaban tepat: <strong>D. Lima pasal konstitusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tiga pasal konstitusi kurang tepat karena pasal penyebutan NKRI berjumlah lima pasal.</li><li><strong>B</strong>: Empat pasal konstitusi kurang tepat karena belum mencakup keseluruhan pasal penyebutan NKRI.</li><li><strong>C</strong>: Enam pasal konstitusi kurang tepat karena melampaui jumlah pasal penyebutan NKRI.</li><li><strong>E</strong>: Tujuh pasal konstitusi kurang tepat karena melebihi ketentuan resmi dalam UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 64) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>langsung menyebutkan tentang Negara Kesatuan Republik Indonesia dalam lima</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Klausul konstitusional mengenai bentuk Negara Kesatuan Republik Indonesia yang tidak dapat dilakukan perubahan diatur dalam...", "options": ["Pasal 1 ayat (1) UUD 1945", "Pasal 18 ayat (1) UUD 1945", "Pasal 25A UUD 1945", "Pasal 36A UUD 1945", "Pasal 37 ayat (5) UUD 1945"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketentuan Pasal 37 Ayat (5) UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pasal 37 ayat (5) UUD 1945</strong> menetapkan bahwa khusus mengenai <strong>bentuk Negara Kesatuan Republik Indonesia tidak dapat dilakukan perubahan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Pasal 37 ayat (5) UUD 1945 secara tegas mengunci bentuk NKRI sebagai kesepakatan final yang kebal terhadap amandemen konstitusi. Norma pembatasan ini menjamin keberlangsungan kedaulatan wilayah dan persatuan bangsa dari ancaman disintegrasi. Ketentuan ini menjadi landasan yuridis fundamental bagi eksistensi negara kesatuan.</p><p>Jawaban tepat: <strong>E. Pasal 37 ayat (5) UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pasal 1 ayat (1) mengatur bahwa Negara Indonesia ialah Negara Kesatuan yang berbentuk Republik.</li><li><strong>B</strong>: Pasal 18 ayat (1) mengatur pembagian wilayah daerah provinsi, kabupaten, dan kota.</li><li><strong>C</strong>: Pasal 25A mengatur wilayah negara kesatuan sebagai negara kepulauan yang berciri Nusantara.</li><li><strong>D</strong>: Pasal 36A mengatur lambang negara Garuda Pancasila dengan semboyan Bhinneka Tunggal Ika.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 64) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pasal 37 ayat (5) UUD 1945 serta rumusan pasal- pasal yang mengukuhkan Negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Prinsip kesatuan dalam wadah Negara Kesatuan Republik Indonesia secara substantif dipertegas dalam Pembukaan UUD 1945 pada alinea...", "options": ["Alinea keempat", "Alinea kedua", "Alinea ketiga", "Alinea pertama", "Alinea penutup"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Kesatuan dalam Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Prinsip kesatuan</strong> dalam NKRI dipertegas dalam <strong>alinea keempat Pembukaan UUD 1945</strong> terkait pembentukan pemerintahan pelindung segenap tumpah darah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena alinea keempat Pembukaan UUD 1945 memuat tujuan nasional untuk membentuk pemerintahan negara yang melindungi segenap bangsa Indonesia dan seluruh tumpah darah Indonesia. Penegasan ini meneguhkan amanat keutuhan wilayah dan persatuan warga negara di bawah naungan konstitusi. Rumusan tersebut menjadi fondasi utama integrasi nasional.</p><p>Jawaban tepat: <strong>A. Alinea keempat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Alinea kedua memuat cita-cita pergerakan kemerdekaan menuju negara merdeka, bersatu, berdaulat, adil, dan makmur.</li><li><strong>C</strong>: Alinea ketiga memuat motivasi spiritual atas berkat rahmat Allah Yang Maha Kuasa dan dorongan luhur kebangsaan.</li><li><strong>D</strong>: Alinea pertama memuat prinsip kemerdekaan sebagai hak segala bangsa serta penghapusan penjajahan.</li><li><strong>E</strong>: Alinea penutup kurang tepat karena struktur formal Pembukaan UUD 1945 berakhir pada alinea keempat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 64) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Prinsip kesatuan dalam Negara Kesatuan Republik Indonesia dipertegas dalam alinea</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Penyelenggaraan aparatur pemerintah yang menjamin keamanan wilayah perbatasan pulau terluar merupakan bentuk perwujudan dari mandat konstitusional untuk...", "options": ["Memajukan kesejahteraan umum warga kepulauan", "Melindungi segenap bangsa dan seluruh tumpah darah", "Mencerdaskan kehidupan seluruh generasi penerus", "Melaksanakan ketertiban dunia berdasarkan perdamaian", "Mewujudkan keadilan sosial bagi seluruh masyarakat"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pembentukan Pemerintahan Negara Kesatuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan pembentukan pemerintahan negara Indonesia dalam alinea keempat Pembukaan UUD 1945 adalah <strong>melindungi segenap bangsa Indonesia dan seluruh tumpah darah Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pengamanan wilayah perbatasan pulau terluar secara langsung menjalankan amanat perlindungan segenap bangsa dan seluruh tumpah darah Indonesia. Tindakan ini mencerminkan komitmen pertahanan kesatuan teritorial negara kesatuan. Aparatur negara berkewajiban mengawal kedaulatan yurisdiksi nasional.</p><p>Jawaban tepat: <strong>B. Melindungi segenap bangsa dan seluruh tumpah darah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Memajukan kesejahteraan umum berfokus pada penyediaan fasilitas perekonomian dan kemakmuran taraf hidup.</li><li><strong>C</strong>: Mencerdaskan kehidupan bangsa berkaitan dengan sektor pendidikan nasional dan pembinaan intelektual warga.</li><li><strong>D</strong>: Melaksanakan ketertiban dunia berorientasi pada partisipasi aktif dalam hubungan internasional dan diplomasi.</li><li><strong>E</strong>: Mewujudkan keadilan sosial merupakan muara pelaksanaan sila kelima Pancasila dalam keseimbangan hak warga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 64) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>negara Indonesia yang melindungi segenap bangsa Indonesia dan seluruh tumpah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Organisasi pergerakan yang menjadi tonggak awal sejarah perjuangan bangsa bersifat nasional pada tanggal 20 Mei 1908 adalah...", "options": ["Sarekat Dagang Islam", "Indische Partij", "Budi Utomo", "Perhimpunan Indonesia", "Partai Nasional Indonesia"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tonggak Awal Sejarah Perjuangan Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Lahirnya pergerakan <strong>Budi Utomo pada tanggal 20 Mei 1908</strong> merupakan <strong>tonggak awal sejarah perjuangan bangsa yang bersifat nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kelahiran organisasi Budi Utomo pada 20 Mei 1908 menandai pergeseran perjuangan kedaerahan menuju pergerakan modern berwawasan nasional. Peristiwa bersejarah ini meletakkan fondasi kebangkitan kesadaran persatuan di kalangan kaum terpelajar nusantara. Tanggal berdirinya organisasi ini diperingati sebagai Hari Kebangkitan Nasional.</p><p>Jawaban tepat: <strong>C. Budi Utomo</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sarekat Dagang Islam didirikan pada tahun 1905 di Surakarta oleh Samanhudi sebagai perhimpunan ekonomi pedagang pribumi.</li><li><strong>B</strong>: Indische Partij didirikan pada tahun 1912 di Bandung oleh Tiga Serangkai dengan haluan politik radikal.</li><li><strong>D</strong>: Perhimpunan Indonesia merupakan organisasi mahasiswa pergerakan di Belanda yang mempopulerkan identitas Indonesia pada 1925.</li><li><strong>E</strong>: Partai Nasional Indonesia didirikan pada tahun 1927 di Bandung oleh Ir. Soekarno untuk memperjuangkan kemerdekaan penuh.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 64) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>1908 yang merupakan tonggak awal sejarah perjuangan bangsa yang bersifat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C4 - Analisis", "question": "Gerakan kebangsaan yang tumbuh menyusul pergerakan Budi Utomo berkembang di berbagai bidang kehidupan nasional, KECUALI...", "options": ["Bidang politik dan ekonomi perdagangan", "Bidang pendidikan dan kesenian budaya", "Bidang pers cetak dan pergerakan kewanitaan", "Bidang militerisme dan ekspansionisme wilayah", "Bidang kewanitaan dan penerbitan pers"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dimensi Gerakan Kebangsaan Pasca Budi Utomo</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Gerakan kebangsaan pasca Budi Utomo meliputi bidang <strong>politik, ekonomi/perdagangan, pendidikan, kesenian, pers, dan kewanitaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat sebagai pengecualian karena pergerakan nasional Indonesia berwatak emansipatoris dan anti-kolonial, bukan bertujuan militerisme maupun ekspansionisme wilayah lain. Gerakan awal rakyat berfokus pada penguatan kapasitas intelektual, solidaritas ekonomi, dan penyadaran sosial politik. Dokumen historis mencatat perjuangan bertumpu pada ranah kultural dan kelembagaan sipil.</p><p>Jawaban tepat: <strong>D. Bidang militerisme dan ekspansionisme wilayah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bidang politik dan ekonomi perdagangan merupakan pilar pergerakan yang dirintis kelompok Sarekat Islam dan partai kebangsaan.</li><li><strong>B</strong>: Bidang pendidikan dan kesenian budaya merupakan sarana pencerdasan anak negeri melalui lembaga Taman Siswa dan sanggar budaya.</li><li><strong>C</strong>: Bidang pers cetak dan kewanitaan merupakan media penyebaran gagasan persatuan serta emansipasi perempuan seperti Putri Mardika.</li><li><strong>E</strong>: Bidang kewanitaan dan pers mencerminkan keterlibatan tokoh jurnalis serta aktivis wanita dalam membangun wawasan kebangsaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 64) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>bidang politik, ekonomi/ perdagangan, pendidikan, kesenian, pers, dan kewanitaan.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Peristiwa bersejarah pada 28 Oktober 1928 yang mempertegas tekad perjuangan persatuan kebangsaan Indonesia adalah...", "options": ["Kongres Perhimpunan Pelajar", "Deklarasi Piagam Jakarta", "Proklamasi Kemerdekaan", "Rapat Raksasa Ikada", "Sumpah Pemuda"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peristiwa Sumpah Pemuda 1928</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Sumpah Pemuda pada 28 Oktober 1928</strong> mempertegas tekad perjuangan dengan ikrar persatuan tanah air, bangsa, dan bahasa.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena ikrar Sumpah Pemuda tanggal 28 Oktober 1928 merupakan ikrar kebangsaan yang melebur sekat kedaerahan para pemuda Indonesia. Peristiwa ini menyatukan berbagai elemen kesukuan ke dalam komitmen tunggal berbangsa dan berbahasa Indonesia. Tonggak ini mengkristalkan kesadaran geopolitik nusantara menuju kemerdekaan bersama.</p><p>Jawaban tepat: <strong>E. Sumpah Pemuda</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kongres Perhimpunan Pelajar merupakan forum pertemuan kepemudaan yang mendahului lahirnya ikrar persatuan resmi.</li><li><strong>B</strong>: Deklarasi Piagam Jakarta dirumuskan oleh Panitia Sembilan pada 22 Juni 1945 sebagai rancangan pembukaan konstitusi.</li><li><strong>C</strong>: Proklamasi Kemerdekaan dikumandangkan pada 17 Agustus 1945 sebagai puncak pernyataan kemerdekaan bangsa Indonesia.</li><li><strong>D</strong>: Rapat Raksasa Ikada diselenggarakan pada 19 September 1945 di Jakarta untuk menunjukkan dukungan rakyat terhadap proklamasi kemerdekaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 64) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tekad perjuangan itu lebih tegas lagi dengan Sumpah Pemuda 28 Oktober 1928</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Unsur ketiga dalam ikrar persatuan Sumpah Pemuda 1928 yang mengikat komunikasi kebangsaan berbunyi...", "options": ["Menjunjung tinggi bahasa persatuan Bahasa Indonesia", "Menetapkan satu bendera nasional Merah Putih", "Mengakui satu ideologi negara Pancasila", "Mempertahankan satu bentuk negara kesatuan", "Menjaga satu kebudayaan luhur nusantara"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rumusan Ikrar Sumpah Pemuda</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ikrar Sumpah Pemuda 1928 menegaskan komitmen <strong>Satu Nusa, Satu Bangsa, dan menjunjung tinggi bahasa persatuan Bahasa Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena naskah ikrar Sumpah Pemuda 1928 butir ketiga berbunyi menjunjung tinggi bahasa persatuan, bahasa Indonesia. Kedudukan bahasa persatuan berfungsi sebagai sarana integrasi komunikasi lintas etnis di seluruh kepulauan nusantara. Ketetapan ini memposisikan bahasa Indonesia sebagai identitas kebangsaan yang inklusif.</p><p>Jawaban tepat: <strong>A. Menjunjung tinggi bahasa persatuan Bahasa Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Menetapkan satu bendera Merah Putih merupakan materi kesepakatan simbol kenegaraan yang dikukuhkan pada sidang PPKI 1945.</li><li><strong>C</strong>: Mengakui satu ideologi negara Pancasila dirumuskan pada masa persiapan kemerdekaan tahun 1945.</li><li><strong>D</strong>: Mempertahankan bentuk kesatuan merupakan komitmen ketatanegaraan yang ditetapkan dalam UUD 1945.</li><li><strong>E</strong>: Menjaga kebudayaan luhur merupakan muatan pasal pemajuan kebudayaan nasional dalam Pasal 32 UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 64) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Satu Bangsa, dan menjunjung tinggi bahasa persatuan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Puncak pencapaian sudut pandang kebangsaan Indonesia dalam wujud persatuan memproklamasikan kemerdekaan terjadi pada...", "options": ["Tanggal 20 Mei 1908", "Tanggal 17 Agustus 1945", "Tanggal 1 Juni 1945", "Tanggal 28 Oktober 1928", "Tanggal 18 Agustus 1945"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Proklamasi Kemerdekaan 17 Agustus 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sudut pandang kebangsaan mencapai <strong>tonggak sejarah kemerdekaan pada tanggal 17 Agustus 1945</strong> saat proklamasi dikumandangkan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pembacaan naskah Proklamasi Kemerdekaan pada 17 Agustus 1945 menandai puncak kulminasi perjuangan bangsa Indonesia. Peristiwa agung ini merealisasikan tekad persatuan kebangsaan menjadi entitas negara yang merdeka dan berdaulat. Momen bersejarah tersebut mengakhiri masa kolonialisme di nusantara.</p><p>Jawaban tepat: <strong>B. Tanggal 17 Agustus 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tanggal 20 Mei 1908 merupakan hari lahirnya organisasi Budi Utomo sebagai awal pergerakan nasional.</li><li><strong>C</strong>: Tanggal 1 Juni 1945 merupakan momentum pidato Sukarno mengenai prinsip dasar Pancasila di sidang BPUPK.</li><li><strong>D</strong>: Tanggal 28 Oktober 1928 merupakan ikrar bersejarah Sumpah Pemuda dalam menyatukan tekad pemuda.</li><li><strong>E</strong>: Tanggal 18 Agustus 1945 merupakan momentum penetapan UUD 1945 dan pemilihan Presiden serta Wakil Presiden.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 64) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tonggak sejarah, bersatu padu memproklamasikan kemerdekaan pada tanggal 17</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Gagasan, sikap, dan tekad konsepsi kebangsaan Indonesia bersumber dari perpaduan luhur antara...", "options": ["Hukum adat suku bangsa dan perjanjian internasional", "Sistem hukum kolonial dan doktrin militer barat", "Nilai budaya bangsa dan cita-cita moral rakyat", "Kepentingan ekonomi daerah dan persaingan dagang", "Peraturan birokrasi kerajaan dan petunjuk diplomatik"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sumber Nilai Konsepsi Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Konsepsi kebangsaan bersumber dari <strong>nilai-nilai budaya bangsa serta disemangati oleh cita-cita moral rakyat yang luhur</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena falsafah kebangsaan Indonesia digali langsung dari khazanah nilai budaya leluhur serta aspirasi moral rakyat. Perpaduan ini membentuk daya lentur batiniah bangsa dalam menghadapi aneka tantangan zaman. Sumber nilai kultural tersebut menjadi jiwa pemersatu identitas keindonesiaan.</p><p>Jawaban tepat: <strong>C. Nilai budaya bangsa dan cita-cita moral rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hukum adat dan perjanjian internasional merupakan instrumen legal yuridis, bukan sumber spiritual konsepsi kebangsaan.</li><li><strong>B</strong>: Sistem kolonial dan doktrin militer asing bertentangan dengan semangat kemerdekaan dan kepribadian bangsa.</li><li><strong>D</strong>: Kepentingan ekonomi dan persaingan dagang bersifat pragmatis sektoral dan bukan cita-cita luhur bangsa.</li><li><strong>E</strong>: Peraturan birokrasi kerajaan mencerminkan tata kelola feodal yang telah bertransformasi menjadi negara kesatuan modern.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 64) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tekad yang bersumber dari nilai-nilai budaya bangsa serta disemangati oleh cita-cita</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Kamus Besar Bahasa Indonesia, secara etimologis istilah 'wawasan' mengandung pengertian...", "options": ["Aturan tertulis dalam pergaulan masyarakat", "Ketetapan hukum bagi penyelenggara negara", "Kemampuan diplomasi dalam hubungan luar negeri", "Kekuatan militer untuk menjaga batas wilayah", "Hasil mewawas, tinjauan, atau cara pandang"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etimologi Istilah Wawasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Secara etimologis istilah <strong>wawasan</strong> berarti: (1) <strong>hasil mewawas, tinjauan, pandangan</strong> dan (2) <strong>konsepsi cara pandang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena etimologi kata wawasan merujuk pada aktivitas memandang, meninjau, serta merumuskan cara pandang terhadap realitas. Pengertian ini menjadi dasar pembentukan kerangka berpikir strategis bagi warga negara dalam melihat lingkungan kehidupannya. Konsep wawasan menekankan ketajaman sudut pandang kognitif.</p><p>Jawaban tepat: <strong>E. Hasil mewawas, tinjauan, atau cara pandang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aturan tertulis merujuk pada norma hukum positif atau tata tertib sosial.</li><li><strong>B</strong>: Ketetapan hukum bagi penyelenggara negara berkaitan dengan regulasi administrasi pemerintahan.</li><li><strong>C</strong>: Kemampuan diplomasi merujuk pada kompetensi negosiasi antar-bangsa.</li><li><strong>D</strong>: Kekuatan militer merujuk pada instrumen pertahanan negara fisik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 65) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>hasil mewawas, tinjauan, pandangan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Wawasan Kebangsaan sangat identik dengan Wawasan Nusantara yang memandang kepulauan nusantara sebagai kesatuan...", "options": ["Politik, sosial budaya, ekonomi, dan hankam", "Politik, birokrasi, moneter, dan agraria daerah", "Pendidikan, perniagaan, transportasi, dan maritim", "Kewilayahan, kehutanan, pertambangan, dan industri", "Peradilan, kepolisian, kepabeanan, dan perpajakan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Wawasan Kebangsaan dan Wawasan Nusantara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan Nusantara mewujudkan kepulauan nusantara sebagai <strong>kesatuan politik, sosial budaya, ekonomi, dan pertahanan keamanan (hankam)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena konsepsi Wawasan Nusantara memandang seluruh ruang geografis Indonesia terintegrasi secara utuh dalam aspek politik, sosial budaya, ekonomi, dan hankam. Pandangan holistik ini meniadakan pemisahan antar-pulau dan menyatukan tekad kebangsaan menuju tujuan nasional. Kesatuan multidimensi ini menjadi ciri khas geopolitik Indonesia.</p><p>Jawaban tepat: <strong>A. Politik, sosial budaya, ekonomi, dan hankam</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Birokrasi, moneter, dan agraria merupakan instrumen sektoral dalam manajemen teknis pemerintahan.</li><li><strong>C</strong>: Pendidikan, perniagaan, dan maritim merupakan subsistem fungsional yang berada di bawah kesatuan nasional.</li><li><strong>D</strong>: Kewilayahan, kehutanan, dan industri merupakan pengelompokan pemanfaatan sumber daya alam fisik.</li><li><strong>E</strong>: Peradilan, kepolisian, dan perpajakan merupakan aparat penegak hukum dan fiskal negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 65) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mencapai tujuan nasional yang mencakup perwujudan kepulauan nusantara sebagai</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Kelompok masyarakat yang bersamaan asal keturunan, adat, bahasa, dan sejarahnya serta berpemerintahan sendiri disebut...", "options": ["Masyarakat madani", "Bangsa", "Kelompok etnis", "Komunitas lokal", "Golongan sosial"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Bangsa Menurut KBBI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut KBBI, <strong>bangsa</strong> adalah <strong>kelompok masyarakat yang bersamaan asal keturunan, adat, bahasa, dan sejarahnya, serta berpemerintahan sendiri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena definisi leksikal kata bangsa merujuk pada persekutuan hidup manusia yang disatukan oleh ikatan sejarah, tradisi, bahasa, dan kedaulatan politik bersama. Unsur pemerintahan mandiri menegaskan status bangsa sebagai subjek dalam tatanan ketatanegaraan. Istilah ini menjadi dasar pembentukan konsep kebangsaan.</p><p>Jawaban tepat: <strong>B. Bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masyarakat madani merujuk pada masyarakat beradab yang menjunjung hukum, partisipasi, dan kebebasan sipil.</li><li><strong>C</strong>: Kelompok etnis menitikberatkan pada kesamaan geneologis dan tradisi lokal tanpa syarat berpemerintahan sendiri.</li><li><strong>D</strong>: Paguyuban warga merujuk pada perhimpunan sosial berbasis ikatan batiniah kekeluargaan yang bersifat lokal.</li><li><strong>E</strong>: Golongan sosial merujuk pada stratifikasi kelompok masyarakat berdasarkan status ekonomi atau profesi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 65) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Indonesia (2002) berarti kelompok masyarakat yang bersamaan asal keturunan, adat,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan tinjauan leksikal, salah satu makna dari kata 'kebangsaan' adalah...", "options": ["Kepemilikan hak atas tanah adat di suatu daerah", "Status kepangkatan pegawai di lingkungan pemerintahan", "Ciri-ciri yang menandai suatu golongan bangsa", "Kewenangan mutlak penguasa atas rakyat jelata", "Tata cara pelaksanaan pemungutan suara nasional"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Kata Kebangsaan Menurut KBBI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kata <strong>kebangsaan</strong> mengandung arti: (1) <strong>ciri-ciri yang menandai golongan bangsa</strong>, (2) perihal bangsa, dan (3) kesadaran diri sebagai warga negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena ciri penanda suatu golongan bangsa merupakan makna definitif pertama kata kebangsaan dalam kamus bahasa. Ciri-ciri pembeda ini mencerminkan karakteristik kepribadian serta identitas kolektif yang membedakannya dari persekutuan bangsa lain. Pemahaman ini mempertegas identitas keindonesiaan.</p><p>Jawaban tepat: <strong>C. Ciri-ciri yang menandai suatu golongan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kepemilikan hak atas tanah adat berkaitan dengan hukum agraria adat (hak ulayat).</li><li><strong>B</strong>: Status kepangkatan merupakan domain hierarki kepegawaian dalam manajemen aparatur sipil negara.</li><li><strong>D</strong>: Kewenangan mutlak penguasa mencerminkan sistem monarki absolut yang ditolak oleh demokrasi Pancasila.</li><li><strong>E</strong>: Tata cara pemungutan suara merupakan ranah teknis hukum kepemiluan umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 65) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mengandung arti (1) ciri-ciri yang menandai golongan bangsa, (2) perihal bangsa;</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Konsepsi cara pandang yang dilandasi oleh kesadaran diri warga negara terhadap diri dan lingkungannya dalam bernegara disebut...", "options": ["Wawasan Nusantara", "Wawasan Kedaerahan", "Ketahanan Nasional", "Wawasan Kebangsaan", "Kewaspadaan Nasional"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Umum Wawasan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Wawasan kebangsaan</strong> diartikan sebagai <strong>konsepsi cara pandang yang dilandasi kesadaran diri sebagai warga dari suatu negara akan diri dan lingkungannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena rumusan tersebut merupakan definisi konseptual umum dari wawasan kebangsaan dalam kehidupan berbangsa dan bernegara. Kesadaran diri warga negara mengarahkan cara bersikap dan berperilaku demi kebaikan bersama bangsa. Definisi ini menjadi pijakan dasar pembinaan jiwa nasionalisme aparatur.</p><p>Jawaban tepat: <strong>D. Wawasan Kebangsaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Wawasan Nusantara lebih menekankan geopolitik kepulauan Indonesia sebagai satu kesatuan utuh kepulauan nusantara.</li><li><strong>B</strong>: Wawasan kedaerahan merupakan cara pandang partikularistik yang memprioritaskan kepentingan kelompok lokal.</li><li><strong>C</strong>: Ketahanan Nasional menitikberatkan pada kondisi dinamis keuletan dan ketangguhan bangsa menghadapi ancaman.</li><li><strong>E</strong>: Kewaspadaan Nasional berkaitan dengan kesiapsiagaan menghadapi potensi gangguan keamanan dan stabilitas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 65) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>konsepsi cara pandang yang dilandasi akan kesadaran diri sebagai warga dari suatu</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Tokoh akademisi mantan Gubernur Lemhannas RI yang merumuskan definisi wawasan kebangsaan berorientasi persatuan wilayah adalah...", "options": ["Prof. B.J. Habibie", "Prof. Jimly Asshiddiqie", "Prof. Miriam Budiardjo", "Prof. Mochtar Kusumaatmadja", "Prof. Muladi"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Wawasan Kebangsaan Menurut Prof. Muladi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Prof. Muladi (mantan Gubernur Lemhannas RI)</strong>, wawasan kebangsaan adalah cara pandang bangsa mengutamakan kesatuan dan persatuan wilayah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Prof. Muladi memberikan sumbangan pemikiran teoretis yang otoritatif mengenai wawasan kebangsaan saat memimpin Lemhannas. Beliau merumuskan konsep integrasi nasional yang memadukan dimensi kultural dan struktural kehidupan bernegara. Pemikiran ini menjadi rujukan baku dalam kurikulum kebangsaan.</p><p>Jawaban tepat: <strong>E. Prof. Muladi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Prof. B.J. Habibie dikenal luas atas kepeloporan transformasi industri dirgantara dan teknologi nasional.</li><li><strong>B</strong>: Prof. Jimly Asshiddiqie merupakan ketua Mahkamah Konstitusi pertama dan pakar hukum tata negara.</li><li><strong>C</strong>: Prof. Miriam Budiardjo adalah pakar ilmu politik terkemuka dengan karya dasar-dasar ilmu politik.</li><li><strong>D</strong>: Prof. Mochtar Kusumaatmadja adalah perumus konsep negara kepulauan dalam hukum laut internasional (Deklarasi Djuanda).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 65) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Prof. Muladi, mantan Gubernur Lemhannas RI, meyampaikan bahwa wawasan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Menurut Prof. Muladi, integrasi nasional bangsa Indonesia dalam penyelenggaraan negara pada hakikatnya bersifat...", "options": ["Kultural dan bukan sekadar bernuansa struktural", "Kultural dan tidak bernuansa struktural", "Birokratis formal dan seremonial protokoler", "Struktural hierarkis tanpa dimensi kultural", "Yuridis normatif semata dalam perundangan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sifat Integrasi Nasional Menurut Prof. Muladi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesatuan atau <strong>integrasi nasional bersifat kultural dan bukan sekadar bernuansa struktural</strong>, melingkupi kesatuan ideologi hingga hankam.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena integrasi nasional tidak sekadar menyatukan lembaga formal pemerintahan (struktural), tetapi harus berakar pada ikatan hati, budaya, dan kesadaran batiniah masyarakat (kultural). Keterpaduan dua dimensi ini menjamin keutuhan negara dari ancaman kerapuhan institusional. Pendekatan kultural mengokohkan bangunan struktur kenegaraan.</p><p>Jawaban tepat: <strong>A. Kultural dan bukan sekadar bernuansa struktural</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kultural dan tidak bernuansa struktural keliru karena Prof. Muladi tetap mengakui pentingnya keteraturan struktur kelembagaan.</li><li><strong>C</strong>: Birokratis formal dan seremonial bersifat mekanis permukaan dan tidak menjamin kelanggengan persatuan sejati.</li><li><strong>D</strong>: Struktural hierarkis tanpa dimensi kultural merupakan pendekatan otoriter yang menafikan kemajemukan budaya rakyat.</li><li><strong>E</strong>: Yuridis normatif semata terlalu sempit karena integrasi membutuhkan penerimaan nilai-nilai hidup di masyarakat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 65) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>atau integrasi nasional bersifat kultural dan tidak hanya bernuansa struktural</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C4 - Analisis", "question": "Nuansa kesatuan nasional yang dirumuskan oleh Prof. Muladi mencakup lima aspek strategis kehidupan bernegara, KECUALI...", "options": ["Kesatuan ideologi", "Kesatuan kartel pasar komoditas", "Kesatuan sosial budaya", "Kesatuan ekonomi dan hankam", "Kesatuan politik"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cakupan Integrasi Nasional Menurut Prof. Muladi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Integrasi nasional menurut Prof. Muladi mencakup satu <strong>kesatuan ideologi, politik, sosial budaya, ekonomi, dan pertahanan keamanan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat sebagai pengecualian karena pembentukan kartel pasar merupakan praktik monopoli yang merusak keadilan ekonomi nasional dan bertentangan dengan asas demokrasi ekonomi. Lima aspek kesatuan yang dirumuskan mencakup bidang ideologi, politik, sosial budaya, ekonomi, dan hankam. Wawasan kebangsaan mengarahkan potensi ekonomi demi kesejahteraan bersama.</p><p>Jawaban tepat: <strong>B. Kesatuan kartel pasar komoditas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kesatuan ideologi merupakan konsensus bersama atas falsafah Pancasila sebagai pandangan hidup bangsa.</li><li><strong>C</strong>: Kesatuan sosial budaya merupakan perwujudan semboyan Bhinneka Tunggal Ika dalam keragaman adat tradisi.</li><li><strong>D</strong>: Kesatuan ekonomi dan hankam merupakan keterpaduan kemakmuran pasar nusantara dan sistem pertahanan semesta.</li><li><strong>E</strong>: Kesatuan politik merupakan konsensus kedaulatan dalam bingkai Negara Kesatuan Republik Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 65) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kesatuan ekonomi, dan kesatuan pertahanan dan keamanan.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Wawasan kebangsaan berfungsi menentukan cara bangsa mendayagunakan seluruh potensi dan kondisi geografi guna...", "options": ["Mendominasi kekuatan politik di kawasan regional", "Menghapus otonomi daerah demi sentralisasi penuh", "Mencapai cita-cita dan menjamin kepentingan nasional", "Menandingi kekuatan ekonomi negara adidaya", "Membatasi arus perdagangan barang dan jasa luar"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Wawasan Kebangsaan bagi Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kebangsaan menentukan cara bangsa <strong>mendayagunakan kondisi geografis, sejarah, sosio-budaya, ekonomi, politik, dan hankam dalam mencapai cita-cita dan menjamin kepentingan nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena fungsi utama wawasan kebangsaan adalah memandu pendayagunaan segenap modalitas bangsa untuk merealisasikan cita-cita kemerdekaan serta menjaga kepentingan nasional. Konsepsi ini memberikan arah dan pedoman strategis bagi perumusan kebijakan pembangunan nasional. Hal ini menegaskan orientasi kemaslahatan seluruh rakyat.</p><p>Jawaban tepat: <strong>C. Mencapai cita-cita dan menjamin kepentingan nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mendominasi politik regional bertentangan dengan politik luar negeri bebas aktif yang mengutamakan perdamaian.</li><li><strong>B</strong>: Menghapus otonomi daerah bertentangan dengan asas desentralisasi yang dijamin oleh Pasal 18 UUD 1945.</li><li><strong>D</strong>: Menandingi negara adidaya merupakan persaingan hegemonik yang bukan tujuan berdirinya republik Indonesia.</li><li><strong>E</strong>: Membatasi arus perdagangan bertentangan dengan prinsip kerja sama ekonomi global yang saling menguntungkan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 65) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dalam mencapai cita-cita dan menjamin kepentingan nasional. Wawasan kebangsaan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Wawasan kebangsaan menghendaki adanya pengetahuan yang memadai bagi setiap warga negara mengenai...", "options": ["Pola alokasi proyek konstruksi fisik di kementerian", "Kelemahan lawan politik dalam persaingan kekuasaan", "Strategi pembentukan opini massa melalui media sosial", "Tantangan masa kini dan mendatang serta potensi bangsa", "Rincian tata tertib protokoler perjamuan dinas resmi"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komitmen dan Tuntutan Wawasan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kebangsaan <strong>menghendaki pengetahuan yang memadai tentang tantangan masa kini dan masa mendatang serta berbagai potensi bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena wawasan kebangsaan menuntut warga negara dan aparatur memahami lanskap ancaman dan peluang masa depan secara komprehensif. Pengenalan potensi bangsa yang disertai pemahaman atas tantangan zaman menumbuhkan daya antisipasi strategis yang tangguh. Pengetahuan memadai ini menjamin keberlanjutan eksistensi bangsa.</p><p>Jawaban tepat: <strong>D. Tantangan masa kini dan mendatang serta potensi bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pola alokasi proyek merupakan aspek teknis operasional pengadaan barang dan jasa instansi.</li><li><strong>B</strong>: Kelemahan lawan politik mencerminkan intrik kekuasaan sempit yang tidak selaras dengan wawasan kebangsaan.</li><li><strong>C</strong>: Strategi pembentukan opini massa berkaitan dengan teknik hubungan masyarakat atau propaganda komunikasi.</li><li><strong>E</strong>: Rincian tata tertib protokoler berkaitan dengan tata laksana keprotokolan kenegaraan formal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 65) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>berbagai potensi bangsa.</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_wk_p06_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_wk_p06_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_wk_p06_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Wawasan Kebangsaan Paket 06";
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
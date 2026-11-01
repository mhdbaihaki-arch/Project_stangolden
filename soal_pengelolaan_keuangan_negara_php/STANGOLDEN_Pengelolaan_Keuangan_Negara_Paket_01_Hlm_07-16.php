<?php
/**
 * STANGOLDEN.exam • Pengelolaan Keuangan Negara Paket 01 (Halaman 07–16)
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
// $DB->get_record("stgpkn_p01_rank") => tabel fisik "mdlhv_stgpkn_p01_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgpkn_p01_rank';
const STG_REVIEW_T = 'stgpkn_p01_rev';
const STG_ANNO_T = 'stgpkn_p01_anno';
const STG_KEY = [0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4];
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
            error_log('STANGOLDEN PKN P01 HLM07-16 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN PKN P01 HLM07-16: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Pengelolaan Keuangan Negara Paket 01 (Hlm. 07–16) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 01 • Hlm. 07–16</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 01 • Hlm. 07–16</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Pengelolaan Keuangan Negara Paket 01</h2>
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
const STG_LOCAL_KEY="stg_pkn_p01_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pengelolaan keuangan negara pada hakikatnya didefinisikan sebagai pengelolaan yang dilakukan pemerintah terhadap...", "options": ["Pendapatan negara, belanja negara, dan pembiayaan", "Keuntungan badan usaha swasta berskala nasional", "Arus perputaran modal bank komersial daerah", "Tabungan sukarela seluruh warga masyarakat", "Aset kekayaan milik perorangan pegawai aparatur"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Pengelolaan Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pengelolaan Keuangan Negara</strong> adalah <strong>pengelolaan keuangan yang dilakukan oleh Pemerintah terhadap sumber-sumber keuangan berupa pendapatan negara, terhadap belanja negara dan sumber keuangan untuk membiayai kekurangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena secara harfiah pengelolaan keuangan negara mencakup tiga pilar utama APBN: pendapatan negara, belanja negara, dan sumber pembiayaan penutup defisit. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Pendapatan negara, belanja negara, dan pembiayaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keuntungan swasta bukan objek pengelolaan keuangan negara pemerintah.</li><li><strong>C</strong>: Modal bank komersial bukan kas negara melainkan aset perbankan.</li><li><strong>D</strong>: Tabungan sukarela warga merupakan aset privat milik masyarakat.</li><li><strong>E</strong>: Aset perorangan pegawai bukan bagian dari keuangan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pengelolaan keuangan yang dilakukan oleh • belanja negara dan sumber keuangan untuk</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan ketentuan perundang-undangan, pendapatan negara yang dikelola pemerintah bersumber dari...", "options": ["Laba bersih seluruh korporasi multinasional", "Penerimaan perpajakan dan bukan pajak", "Sumbangan wajib dari negara-negara mitra", "Pencetakan uang kertas secara berkala", "Penjualan seluruh aset strategis kementerian"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sumber Penerimaan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pendapatan negara bisa berasal dari berbagai sumber yakni dari pajak dan bukan pajak</strong> yang menurut peraturan perundangan menjadi wewenang Pemerintah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena dua komponen utama penerimaan negara dalam undang-undang keuangan negara adalah penerimaan perpajakan dan penerimaan negara bukan pajak (PNBP). Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Penerimaan perpajakan dan bukan pajak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemerintah hanya memungut pajak atas laba, bukan mengambil seluruh laba korporasi.</li><li><strong>C</strong>: Pendapatan negara tidak bergantung pada sumbangan wajib negara lain.</li><li><strong>D</strong>: Pencetakan uang adalah instrumen moneter Bank Indonesia, bukan pos pendapatan.</li><li><strong>E</strong>: Penjualan aset strategis bukan sumber pendapatan rutin negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pendapatan negara bisa berasal dari berbagai sumber • pajak dan bukan pajak</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan belanja negara oleh pemerintah pada hakikatnya diselenggarakan dalam rangka...", "options": ["Memperbesar cadangan devisa bank sentral", "Menambah kepemilikan modal di pasar modal", "Melaksanakan fungsi menyejahterakan masyarakat", "Meningkatkan keuntungan komersial instansi", "Menyaingi pertumbuhan sektor korporasi swasta"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Belanja Pemerintah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Belanja Pemerintah pada hakikatnya dilakukan dalam rangka melaksanakan fungsinya mensejahterakan masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena tujuan akhir dari seluruh alokasi belanja negara adalah pelayanan publik dan peningkatan kesejahteraan sosial ekonomi rakyat. Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Melaksanakan fungsi menyejahterakan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cadangan devisa dikelola oleh Bank Indonesia melalui kebijakan moneter.</li><li><strong>B</strong>: Belanja negara ditujukan untuk layanan publik, bukan spekulasi pasar saham.</li><li><strong>D</strong>: Instansi pemerintah bukan badan usaha pencari keuntungan komersial.</li><li><strong>E</strong>: Pemerintah berfungsi sebagai fasilitator ekonomi, bukan pesaing swasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Belanja Pemerintah pada hakikatnya dilakukan dalam rangka • mensejahterakan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Untuk menutup kekurangan kas atau membiayai belanja pembangunan, sumber keuangan pemerintah dapat berasal dari...", "options": ["Pungutan liar di luar peraturan dinas", "Penyitaan rekening simpanan warga negara", "Penerbitan obligasi tanpa persetujuan sah", "Pinjaman utang atau sumber sah lainnya", "Pemotongan berkala simpanan sukarela anggota serikat"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sumber Pembiayaan Defisit Pembangunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sumber-sumber keuangan untuk <strong>pembiayaan pembangunan dapat berasal dari utang atau sumber lainnya</strong> sesuai koridor perundang-undangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pos pembiayaan anggaran negara bersumber dari penarikan utang (SBN/pinjaman) serta pemanfaatan saldo kas lebih atau sumber pembiayaan sah lainnya. Dengan demikian, opsi D benar.</p><p>Jawaban tepat: <strong>D. Pinjaman utang atau sumber sah lainnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pungutan di luar undang-undang merupakan tindakan ilegal.</li><li><strong>B</strong>: Penyitaan simpanan warga melanggar hak asasi kepemilikan pribadi.</li><li><strong>C</strong>: Penerbitan surat utang wajib melalui persetujuan DPR dalam APBN.</li><li><strong>E</strong>: Pemotongan simpanan serikat bukan pembiayaan anggaran negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pembiayaan pembangunan dapat berasal dari utang atau sumber lainnya. • utang atau sumber lainnya.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam tata kelola keuangan negara, Kementerian Keuangan memegang posisi strategis sebagai...", "options": ["Chief Operational Officer (COO) teknis", "Lembaga pengawas eksternal independen", "Badan pemeriksa keabsahan kepatuhan hukum", "Otoritas tunggal peradilan tata usaha negara", "Chief Financial Officer (CFO) pemerintah"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Strategis Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian Keuangan mempunyai posisi strategis dalam pengelolaan keuangan negara, yakni sebagai <strong>Chief Financial Officer (CFO)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Menteri Keuangan bertindak sebagai CFO yang mengelola kebijakan fiskal, perbendaharaan, dan aset negara secara nasional. Oleh karena itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Chief Financial Officer (CFO) pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Chief Operational Officer (COO) dipegang oleh menteri teknis kementerian/lembaga.</li><li><strong>B</strong>: Pengawas eksternal independen adalah Badan Pemeriksa Keuangan (BPK).</li><li><strong>C</strong>: Pemeriksaan kepatuhan hukum laporan keuangan dilakukan oleh BPK.</li><li><strong>D</strong>: Peradilan tata usaha negara merupakan cabang yudikatif tersendiri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>wewenang dan fungsi Pemerintah dibatasi oleh peraturan perundangan • pembatasan-pembatasan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Wewenang pengelolaan keuangan oleh pemerintah dibatasi oleh peraturan perundang-undangan dalam bentuk...", "options": ["Persetujuan dan pengawasan pihak legislatif", "Intervensi mutlak lembaga moneter asing", "Penetapan harga komoditas oleh pasar gelap", "Pembatasan masa kerja pegawai perbendaharaan", "Pemberian hak veto kepada badan usaha swasta"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Pembatasan Kekuasaan Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wewenang dan fungsi pemerintah dibatasi oleh undang-undang, antara lain melalui <strong>persetujuan dan pengawasan yang dilakukan oleh pihak legislatif (DPR)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena sistem presidensial menempatkan DPR sebagai pemegang hak anggaran (budgeting) yang memberikan persetujuan dan pengawasan atas pelaksanaan APBN. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Persetujuan dan pengawasan pihak legislatif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Lembaga asing tidak memiliki hak intervensi kedaulatan anggaran negara.</li><li><strong>C</strong>: Pasar gelap tidak memiliki legitimasi hukum pembatasan kekuasaan.</li><li><strong>D</strong>: Masa kerja pegawai bukan bentuk pembatasan kekuasaan anggaran pemerintah.</li><li><strong>E</strong>: Sektor swasta tidak memiliki hak veto atas kebijakan keuangan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>persetujuan dan pengawasan yang dilakukan oleh pihak legislatif. • persetujuan dan pengawasan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pertanggungjawaban keuangan negara yang dikelola pemerintah wajib memenuhi dua aspek fundamental yaitu...", "options": ["Kerahasiaan mutlak dan fleksibilitas bebas", "Transparansi dan akuntabilitas publik", "Penyederhanaan format pembukuan secara mandiri", "Pemusatan wewenang pada staf pelaksana", "Komersialisasi aset demi pendapatan instansi"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Pertanggungjawaban Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pertanggungjawaban keuangan pemerintah diharapkan sesuai dengan koridor peraturan, selain memenuhi aspek <strong>transparansi dan akuntabilitas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena keterbukaan informasi (transparansi) dan tanggung jawab pertanggungjawaban hasil (akuntabilitas) adalah pilar tata kelola keuangan yang baik. Dengan demikian, opsi B benar.</p><p>Jawaban tepat: <strong>B. Transparansi dan akuntabilitas publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kerahasiaan mutlak bertentangan dengan prinsip keterbukaan informasi publik.</li><li><strong>C</strong>: Penyederhanaan tanpa standar melanggar konsistensi pembukuan kas.</li><li><strong>D</strong>: Wewenang diatur berjenjang dan bukan dipusatkan pada staf bawahan.</li><li><strong>E</strong>: Komersialisasi bukan aspek pertanggungjawaban keuangan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pertanggungjawaban keuangan yang dikelola oleh Pemerintah • transparansi dan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Saat terjadi defisit anggaran belanja untuk program penanganan wabah, langkah pembiayaan yang sah menurut undang-undang adalah...", "options": ["Memotong paksa simpanan dana nasabah di bank komersial", "Menjual gedung kementerian kepada pihak swasta asing", "Menggunakan sumber pembiayaan utang yang disetujui DPR", "Menghentikan seluruh layanan fasilitas kesehatan umum", "Menerbitkan instrumen kasir tanpa koordinasi otoritas perbankan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Pembiayaan Defisit APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekurangan dana belanja (defisit) ditutup melalui <strong>sumber-sumber keuangan pembiayaan yang sah seperti penarikan utang atas persetujuan DPR</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena penarikan utang melalui penerbitan SBN atau pinjaman merupakan instrumen pembiayaan resmi yang diatur dalam undang-undang APBN. Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Menggunakan sumber pembiayaan utang yang disetujui DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemotongan simpanan nasabah perbankan adalah tindakan melawan hukum.</li><li><strong>B</strong>: Aset strategis kementerian tidak boleh dipindahtangankan secara sewenang-wenang.</li><li><strong>D</strong>: Layanan kesehatan merupakan hak dasar rakyat yang wajib dilindungi belanja negara.</li><li><strong>E</strong>: Menerbitkan instrumen tanpa koordinasi melanggar batas wewenang kas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>belanja negara dan sumber keuangan untuk • kekurangan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Dalam penyusunan proyeksi penerimaan dan pengeluaran negara, instansi yang memegang komando strategis selaku bendahara umum adalah...", "options": ["Badan Pengawasan Keuangan dan Pembangunan pusat", "Otoritas Jasa Keuangan pengawas perbankan", "Kementerian Koordinator Bidang Perekonomian", "Kementerian Keuangan selaku bendahara umum negara", "Badan Perencanaan Pembangunan Nasional murni"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Peran Kemenkeu sebagai Pengelola Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian Keuangan memegang posisi strategis sebagai pengelola fiskal nasional dan <strong>Bendahara Umum Negara (BUN) / CFO</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena mandat undang-undang menempatkan Menkeu sebagai pengelola kas negara dan otoritas penyusun postur fiskal nasional. Maka dari itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Kementerian Keuangan selaku bendahara umum negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: BPKP bertindak sebagai aparat pengawasan intern, bukan bendahara umum.</li><li><strong>B</strong>: OJK mengawasi industri jasa keuangan dan perbankan, bukan kas negara.</li><li><strong>C</strong>: Kemenko Perekonomian mengoordinasikan kebijakan makro kementerian teknis.</li><li><strong>E</strong>: Bappenas merencanakan program prioritas pembangunan, bukan bendahara kas negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mencakup pokok bahasan tentang maksud dan tujuan pengelolaan • tujuan pengelolaan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Keterkaitan esensial antara wewenang pemerintah mengelola pendapatan dan belanja dengan pengawasan legislatif adalah...", "options": ["Memperlambat pencairan dana operasional seluruh satker daerah", "Mengalihkan tanggung jawab pengelolaan kas ke pihak DPR", "Menghapuskan kewajiban audit laporan keuangan oleh BPK", "Membatasi hak masyarakat memperoleh informasi anggaran", "Menjamin penggunaan anggaran sesuai mandat kesejahteraan rakyat"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Hubungan Pendapatan, Belanja, dan Pengawasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembatasan dan pengawasan legislatif bertujuan <strong>menjaga agar pertanggungjawaban keuangan tetap dalam koridor hukum demi kesejahteraan masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pengawasan DPR memastikan pendapatan yang dihimpun dari pajak benar-benar dibelanjakan untuk kepentingan dan kemakmuran rakyat secara transparan. Dengan demikian, opsi E benar.</p><p>Jawaban tepat: <strong>E. Menjamin penggunaan anggaran sesuai mandat kesejahteraan rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengawasan bukan bertujuan menghambat operasional melainkan menjaga akuntabilitas.</li><li><strong>B</strong>: Tanggung jawab eksekutif tidak beralih kepada legislatif.</li><li><strong>C</strong>: Pengawasan DPR tidak menggantikan fungsi audit independen BPK.</li><li><strong>D</strong>: Pengawasan justru memperkuat hak masyarakat atas transparansi APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kementerian Keuangan mempunyai posisi strategis dalam pengelolaan • Chief Financial Officer.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Undang-Undang Nomor 17 Tahun 2003, keuangan negara didefinisikan secara yuridis sebagai...", "options": ["Semua hak dan kewajiban negara yang dapat dinilai dengan uang", "Seluruh simpanan valuta asing milik bank swasta nasional", "Total aset properti milik warga negara di dalam negeri", "Kekayaan pribadi pejabat negara yang telah dilaporkan", "Keuntungan modal usaha mikro yang beredar di pasar"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Keuangan Negara UU 17/2003</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan UU 17/2003, Keuangan Negara adalah <strong>semua hak dan kewajiban negara yang dapat dinilai dengan uang, serta segala sesuatu baik berupa uang maupun barang yang dapat dijadikan milik negara berhubung pelaksanaan hak dan kewajiban tersebut</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena rumusan yuridis Pasal 1 angka 1 UU 17/2003 mendefinisikan keuangan negara dari dimensi hak dan kewajiban bernilai uang beserta aset yang menyertainya. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Semua hak dan kewajiban negara yang dapat dinilai dengan uang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Valuta asing bank swasta bukan milik negara melainkan milik entitas perbankan.</li><li><strong>C</strong>: Properti privat warga negara bukan objek keuangan negara.</li><li><strong>D</strong>: Harta pribadi pejabat negara adalah aset privat perorangan.</li><li><strong>E</strong>: Modal usaha mikro adalah kekayaan milik pengusaha privat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Undang-Undang Keuangan Negara (UU No 17 • Tahun 2003 tentang Keuangan Negara)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menurut penjelasan UU 17/2003, keuangan negara ditinjau dari pendekatan objek mencakup...", "options": ["Seluruh warga negara yang memiliki nomor pokok wajib pajak", "Hak dan kewajiban dinilai uang, kebijakan fiskal, moneter, dan BUMN", "Gedung kantor kementerian yang berada di ibu kota negara", "Susunan hierarki organisasi kementerian dan lembaga", "Jadwal sidang pembahasan rancangan undang-undang DPR"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pendekatan Objek Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Objek keuangan negara</strong> mencakup: hak dan kewajiban dinilai dengan uang, kebijakan/kegiatan bidang <strong>fiskal, moneter, dan pengelolaan kekayaan negara yang dipisahkan</strong>, serta uang dan barang milik negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pendekatan objek memetakan substansi materiil keuangan negara yang mencakup dimensi fiskal, moneter, dan kekayaan dipisahkan. Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Hak dan kewajiban dinilai uang, kebijakan fiskal, moneter, dan BUMN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Wajib pajak adalah subjek hukum pembayar pajak, bukan objek keuangan negara.</li><li><strong>C</strong>: Gedung kementerian hanya sebagian kecil dari barang milik negara.</li><li><strong>D</strong>: Struktur organisasi kementerian adalah subjek kelembagaan.</li><li><strong>E</strong>: Jadwal sidang adalah proses legislasi politik anggaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>semua hak dan kewajiban negara yang dapat dinilai dengan uang • dijadikan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Keuangan negara ditinjau dari pendekatan subjek didefinisikan sebagai seluruh objek keuangan negara yang...", "options": ["Diperjualbelikan secara bebas di bursa efek internasional", "Dikelola oleh lembaga swadaya masyarakat non-pemerintah", "Dimiliki dan/atau dikuasai oleh pemerintah dan badan publik", "Disimpan pada rekening pribadi pejabat perbendaharaan", "Dipinjamkan kepada badan usaha swasta tanpa jaminan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pendekatan Subjek Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Subjek keuangan negara adalah seluruh objek keuangan negara yang dimiliki dan/atau dikuasai oleh Pemerintah dan badan hukum publik lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pendekatan subjek menitikberatkan pada pihak pengendali atau pemilik objek keuangan, yaitu pemerintah pusat, pemda, dan badan hukum publik. Oleh karena itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Dimiliki dan/atau dikuasai oleh pemerintah dan badan publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perdagangan bursa efek bukan definisi pendekatan subjek keuangan negara.</li><li><strong>B</strong>: LSM non-pemerintah bukan subjek pemegang kekuasaan keuangan negara.</li><li><strong>D</strong>: Uang negara dilarang disimpan di rekening pribadi pegawai.</li><li><strong>E</strong>: Pinjaman tanpa jaminan bukan konsep subjek kepemilikan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>termasuk kebijakan dan kegiatan dalam bidang fiskal dan • pengelolaan kekayaan negara yang dipisahkan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan pendekatan proses, keuangan negara merupakan seluruh rangkaian kegiatan pengelolaan yang dimulai dari...", "options": ["Penerbitan surat tagihan pajak hingga penyitaan barang", "Pelantikan menteri kabinet hingga masa akhir jabatan", "Pencairan dana di bank persepsi hingga tutup kas harian", "Perumusan kebijakan hingga tahap pertanggungjawaban", "Pengesahan undang-undang hingga pengumuman media massa"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pendekatan Proses Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut prosesnya, keuangan negara adalah <strong>seluruh rangkaian kegiatan pengelolaan semua hak dan kewajiban negara dimulai dari perumusan kebijakan dan pengambilan keputusan sampai dengan pertanggungjawaban</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena siklus proses keuangan negara merentang utuh dari tahap inisiasi kebijakan, penganggaran, pelaksanaan, hingga audit pertanggungjawaban akhir. Dengan demikian, opsi D benar.</p><p>Jawaban tepat: <strong>D. Perumusan kebijakan hingga tahap pertanggungjawaban</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penagihan pajak hanya satu bagian teknis penerimaan, bukan seluruh siklus proses.</li><li><strong>B</strong>: Masa jabatan menteri adalah siklus politis kabinet.</li><li><strong>C</strong>: Tutup kas harian adalah prosedur teknis pembukuan bendahara.</li><li><strong>E</strong>: Pengumuman media adalah diseminasi informasi publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>seluruh objek keuangan negara yang dimiliki • dan/atau dikuasai oleh Pemerintah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Ditinjau dari pendekatan tujuan, seluruh kebijakan dan pengelolaan objek keuangan negara dimaksudkan dalam rangka...", "options": ["Peningkatan cadangan dividen korporasi", "Persaingan pengaruh geopolitik kawasan", "Komersialisasi pelayanan publik masyarakat", "Penumpukan kekayaan kas kasasi peradilan", "Penyelenggaraan pemerintahan negara"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pendekatan Tujuan Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan seluruh kebijakan, kegiatan, dan hubungan hukum yang berkaitan dengan objek keuangan negara adalah <strong>dalam rangka penyelenggaraan Pemerintahan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena eksistensi keuangan negara semata-mata menjadi instrumen pendukung terwujudnya fungsi dan tujuan bernegara pemerintah. Maka dari itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Penyelenggaraan pemerintahan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dividen korporasi bukan tujuan akhir penyelenggaraan keuangan negara.</li><li><strong>B</strong>: Persaingan kawasan bukan esensi yuridis tujuan keuangan UU 17/2003.</li><li><strong>C</strong>: Pelayanan publik negara berorientasi nirlaba, bukan komersial.</li><li><strong>D</strong>: Penumpukan kas peradilan menyimpang dari tujuan pelayanan bernegara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>seluruh rangkaian kegiatan • pengelolaan semua hak dan kewajiban negara yang dapat dinilai dengan uang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam penjelasan UU Nomor 17 Tahun 2003, pengertian keuangan negara dirumuskan melalui empat pendekatan yaitu...", "options": ["Objek, subjek, proses, dan tujuan", "Pendapatan, belanja, pembiayaan, dan defisit", "Fiskal, moneter, perbankan, dan pasar modal", "Pusat, daerah, desa, dan badan usaha", "Perencanaan, pelaksanaan, penatausahaan, dan audit"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Empat Pendekatan Pengertian Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengertian keuangan negara menurut UU 17/2003 diuraikan secara komprehensif melalui empat sudut pandang: <strong>1. Objek, 2. Subjek, 3. Proses, dan 4. Tujuan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penjelasan undang-undang secara baku membagi definisi ke dalam empat pendekatan teoretis yuridis tersebut. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Objek, subjek, proses, dan tujuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pendapatan, belanja, dan pembiayaan adalah komponen struktur APBN.</li><li><strong>C</strong>: Fiskal dan moneter adalah subbidang kebijakan ekonomi makro.</li><li><strong>D</strong>: Pusat dan daerah adalah tingkatan entitas pemerintahan.</li><li><strong>E</strong>: Perencanaan dan penatausahaan adalah tahapan manajemen operasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>perumusan kebijakan dan pengambilan keputusan sampai dengan • pertanggungjawaban.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Termasuk dalam ruang lingkup objek keuangan negara menurut UU 17/2003 selain uang dan barang milik negara adalah...", "options": ["Seluruh properti milik kedutaan besar negara sahabat", "Kebijakan fiskal, moneter, dan kekayaan dipisahkan", "Simpanan emas murni milik perorangan penduduk", "Surat utang yang diterbitkan oleh korporasi asing", "Tanah adat ulayat yang dikuasai masyarakat hukum"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ruang Lingkup Objek Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek keuangan negara mencakup hak/kewajiban bernilai uang, <strong>termasuk kebijakan dan kegiatan dalam bidang fiskal dan moneter, dan pengelolaan kekayaan negara yang dipisahkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena undang-undang memperluas objek keuangan negara mencakup ranah kebijakan fiskal, moneter, dan penyertaan modal negara pada BUMN. Oleh karena itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Kebijakan fiskal, moneter, dan kekayaan dipisahkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Properti kedutaan asing tunduk pada hukum kekebalan diplomatik internasional.</li><li><strong>C</strong>: Emas simpanan penduduk adalah hak milik privat warga.</li><li><strong>D</strong>: Obligasi swasta asing bukan objek keuangan milik negara Indonesia.</li><li><strong>E</strong>: Tanah ulayat adalah hak komunal masyarakat hukum adat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dalam rangka penyelenggaraan Pemerintahan negara. • hubungan hukum yang berkaitan dengan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Pemerintah daerah mengelola dana transfer dan aset gedung dinas otonom. Ditinjau dari pendekatan subjek, status dana dan aset tersebut adalah...", "options": ["Aset privat yang bebas dari audit BPK RI", "Dana lepas tanpa ikatan regulasi perundang-undangan", "Keuangan negara yang dikuasai pemerintah daerah", "Kekayaan pribadi kepala daerah yang menjabat", "Modal komersial milik perbankan daerah sepenuhnya"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Subjek Hukum Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekayaan yang dikuasai pemerintah daerah termasuk dalam <strong>subjek keuangan negara karena dikuasai oleh Pemerintah daerah sebagai badan hukum publik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pemerintah daerah merupakan entitas publik yang menguasai objek keuangan negara, sehingga dana dan asetnya tunduk pada rezim hukum keuangan negara. Dengan demikian, opsi C benar.</p><p>Jawaban tepat: <strong>C. Keuangan negara yang dikuasai pemerintah daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aset pemda wajib diaudit oleh BPK RI dan bukan aset privat.</li><li><strong>B</strong>: Dana transfer terikat ketat pada regulasi perbendaharaan negara.</li><li><strong>D</strong>: Bukan kekayaan pribadi melainkan barang milik daerah/negara.</li><li><strong>E</strong>: BUMD/Bank daerah dikelola terpisah dan bukan pemilik aset dinas pemda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>termasuk kebijakan dan kegiatan dalam bidang fiskal dan • moneter, dan pengelolaan kekayaan negara yang dipisahkan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Sebuah kementerian merumuskan rencana kerja anggaran, mencairkan DIPA, dan menyusun laporan keuangan. Rangkaian aktivitas ini mencerminkan...", "options": ["Pengawasan eksternal oleh aparat pemeriksa BPK", "Pelaksanaan tugas moneter oleh bank sentral", "Penyelenggaraan peradilan tata usaha perbankan", "Keuangan negara ditinjau dari pendekatan proses", "Kegiatan investasi saham jangka pendek kementerian"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Siklus Proses Keuangan Negara pada Satuan Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rangkaian perumusan rencana, pencairan anggaran, hingga penyusunan laporan pertanggungjawaban adalah perwujudan konkret dari <strong>keuangan negara menurut prosesnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena seluruh siklus kegiatan dari perencanaan hingga laporan pertanggungjawaban merupakan substansi definisi keuangan negara dari sudut pandang proses. Maka dari itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Keuangan negara ditinjau dari pendekatan proses</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyusunan laporan di satker adalah ranah eksekutif internal, bukan audit eksternal BPK.</li><li><strong>B</strong>: Aktivitas tersebut adalah kebijakan fiskal satker, bukan tugas moneter BI.</li><li><strong>C</strong>: Bukan proses peradilan yudisial.</li><li><strong>E</strong>: Satker kementerian dilarang berspekulasi investasi saham jangka pendek.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sesuatu baik berupa uang maupun berupa barang yang dapat dijadikan milik • pelaksanaan hak dan kewajiban tersebut.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Kementerian PUPR membiayai pembangunan jembatan antarpulau dengan APBN. Ditinjau dari pendekatan tujuan, pengeluaran kas ini diarahkan untuk...", "options": ["Mencari keuntungan komersial bagi pejabat instansi", "Menambah utang luar negeri tanpa perhitungan aset", "Menghabiskan alokasi pagu anggaran sebelum tutup tahun", "Menaikkan nilai pasar saham kontraktor swasta rekanan", "Mendukung penyelenggaraan tugas pemerintahan negara"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Pengadaan Sarana Publik Berorientasi Tujuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengelolaan objek keuangan negara ditujukan <strong>dalam rangka penyelenggaraan Pemerintahan negara</strong>, termasuk penyediaan infrastruktur publik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pembangunan jembatan adalah wujud nyata pelaksanaan fungsi pelayanan pemerintah untuk konektivitas masyarakat dan perekonomian nasional. Oleh sebab itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Mendukung penyelenggaraan tugas pemerintahan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemerintah membangun sarana publik nirlaba, bukan mencari laba pejabat.</li><li><strong>B</strong>: Pengeluaran negara didasari kelayakan manfaat, bukan menimbun utang buta.</li><li><strong>C</strong>: Menghabiskan pagu tanpa orientasi output adalah pelanggaran efisiensi.</li><li><strong>D</strong>: Tujuan belanja negara bukan memicu keuntungan saham rekanan swasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tujuan seluruh kebijakan, kegiatan, dan hubungan hukum • Pemerintahan negara.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Perbedaan konseptual mendasar antara pendekatan objek dan pendekatan subjek dalam keuangan negara terletak pada...", "options": ["Objek fokus pada substansi materiil, subjek fokus pada entitas penguasa", "Objek menilai jam kerja, subjek menilai besaran pangkat", "Objek berlaku di daerah, subjek berlaku terbatas pada pusat", "Objek bersifat sementara, subjek bersifat abadi selamanya", "Objek diaudit legislatif, subjek diaudit yudikatif murni"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Perbedaan Pendekatan Objek dan Subjek</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pendekatan <strong>objek menitikberatkan pada substansi materiil</strong> (hak/kewajiban dinilai uang, fiskal, moneter), sedangkan <strong>subjek menitikberatkan pada entitas hukum pemegang kekuasaan</strong> (pemerintah dan badan publik).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pembedaan objek dan subjek secara doktrinal memisahkan antara apa yang dikelola (materiil hak/kewajiban) dan siapa pengelola atau pemilik sahnya (pemerintah/badan publik). Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Objek fokus pada substansi materiil, subjek fokus pada entitas penguasa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Jam kerja dan pangkat adalah administrasi SDM aparatur.</li><li><strong>C</strong>: Pendekatan subjek dan objek berlaku di seluruh entitas negara.</li><li><strong>D</strong>: Pembedaan bukan berdasarkan durasi waktu sementara atau abadi.</li><li><strong>E</strong>: Pengauditan keuangan negara dilakukan oleh BPK RI secara mandiri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>semua hak dan kewajiban negara yang dapat • prosesnya, keuangan negara merupakan seluruh rangkaian kegiatan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Bidang pengelolaan keuangan negara berdasarkan perluasan objek dikelompokkan ke dalam tiga subbidang yaitu...", "options": ["Pengelolaan pajak, bea cukai, dan retribusi daerah", "Pengelolaan fiskal, moneter, dan kekayaan dipisahkan", "Pengelolaan kas, piutang, dan penghapusan utang", "Pengelolaan ekspor, impor, dan devisa perbankan", "Pengelolaan modal, saham, dan obligasi korporasi"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Subbidang Pengelolaan Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bidang pengelolaan keuangan negara dikelompokkan dalam: <strong>1. subbidang pengelolaan fiskal, 2. subbidang pengelolaan moneter, dan 3. subbidang pengelolaan kekayaan negara yang dipisahkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena klasifikasi resmi perundang-undangan membagi keuangan negara secara makro ke dalam tiga pilar operasional tersebut. Oleh sebab itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Pengelolaan fiskal, moneter, dan kekayaan dipisahkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak dan cukai merupakan komponen penerimaan dalam subbidang fiskal.</li><li><strong>C</strong>: Kas dan piutang adalah pos neraca perbendaharaan.</li><li><strong>D</strong>: Ekspor dan impor merupakan aktivitas perdagangan lintas negara.</li><li><strong>E</strong>: Saham korporasi adalah instrumen pasar modal komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dikelompokkan dalam: subbidang pengelolaan • fiskal, subbidang pengelolaan moneter, dan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Subbidang pengelolaan fiskal didefinisikan sebagai segala kegiatan pemerintah yang mencakup...", "options": ["Pengawasan suku bunga perbankan komersial swasta", "Pencetakan fisik lembaran uang rupiah di perum peruri", "Penerimaan dan pengeluaran uang yang dilakukan pemerintah", "Penetapan cadangan kas minimum bank perkreditan rakyat", "Penyelidikan tindak pidana pencucian uang nasabah"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Subbidang Pengelolaan Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Subbidang Pengelolaan Fiskal mengandung pengertian segala kegiatan yang mencakup penerimaan dan pengeluaran uang yang dilakukan oleh Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena hakikat kebijakan fiskal adalah pengelolaan arus uang masuk (pendapatan) dan uang keluar (belanja) negara melalui instrumen APBN. Maka dari itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Penerimaan dan pengeluaran uang yang dilakukan pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengawasan suku bunga bank adalah ranah kebijakan moneter dan OJK.</li><li><strong>B</strong>: Pencetakan fisik uang adalah tugas teknis operasional Bank Indonesia.</li><li><strong>D</strong>: Cadangan wajib minimum adalah instrumen likuiditas kebijakan moneter.</li><li><strong>E</strong>: Penyelidikan pencucian uang merupakan kewenangan PPATK dan penegak hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>segala kegiatan yang • mencakup penerimaan dan pengeluaran uang yang dilakukan oleh Pemerintah.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kebijakan fiskal yang tercermin dalam APBN diarahkan untuk mencapai tiga tujuan utama yaitu...", "options": ["Eksploitasi sumber daya, sentralisasi kas, dan monopoli", "Pengurangan cadangan devisa, inflasi tinggi, dan impor", "Peniadaan pajak, pembiayaan utang bebas, dan defisit", "Alokasi sumber dana, distribusi, dan stabilisasi ekonomi", "Pemberian subsidi swasta, privatisasi total, dan komisi"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Tujuan Kebijakan Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Tujuan kebijakan fiskal mencakup alokasi sumber dana keuangan, distribusinya dan stabilisasi ekonomi</strong>, yakni menjaga pertumbuhan, membuka lapangan kerja, dan stabilitas harga.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena triad fungsi alokasi, distribusi, dan stabilisasi adalah tujuan normatif kebijakan anggaran belanja pemerintah. Dengan demikian, opsi D benar.</p><p>Jawaban tepat: <strong>D. Alokasi sumber dana, distribusi, dan stabilisasi ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Monopoli dan eksploitasi bertentangan dengan prinsip demokrasi ekonomi.</li><li><strong>B</strong>: Mengurangi devisa dan memicu inflasi adalah dampak negatif kegagalan kebijakan.</li><li><strong>C</strong>: Peniadaan pajak menghancurkan kemampuan fiskal negara membiayai belanja.</li><li><strong>E</strong>: Privatisasi total bukan tujuan dasar kebijakan fiskal APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tujuan kebijakan fiskal mencakup alokasi sumber dana keuangan, • stabilisasi ekonomi, yakni mempertahankan laju pertumbuhan ekonomi,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Subbidang pengelolaan moneter didefinisikan sebagai kebijakan di bidang keuangan yang berkenaan dengan...", "options": ["Pengadaan seragam dinas dan kendaraan operasional kementerian", "Penerbitan surat izin mendirikan bangunan di daerah", "Penataan jenjang kepangkatan aparatur sipil negara", "Penarikan tarif pajak bumi dan bangunan sektor perdesaan", "Jumlah uang beredar, cadangan wajib, diskonto, dan pasar terbuka"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Subbidang Pengelolaan Moneter</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Subbidang Pengelolaan Moneter adalah kebijakan yang berkenaan dengan jumlah uang yang beredar, cadangan wajib bank, tingkat diskonto, pengendalian kredit, dan pasar terbuka, termasuk kurs valuta asing</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena instrumen-instrumen kuantitatif likuiditas perbankan dan nilai tukar uang merupakan esensi dari pengelolaan moneter. Oleh sebab itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Jumlah uang beredar, cadangan wajib, diskonto, dan pasar terbuka</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengadaan barang dan kendaraan adalah urusan belanja barang/modal satker.</li><li><strong>B</strong>: Izin mendirikan bangunan adalah fungsi perizinan pemerintah daerah.</li><li><strong>C</strong>: Pola kepangkatan adalah domain manajemen kepegawaian BKN.</li><li><strong>D</strong>: Tarif PBB adalah ranah kebijakan perpajakan fiskal daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Subbidang Pengelolaan Moneter adalah kebijakan yang dilakukan oleh Pemerintah • kurs valuta asing.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan kebijakan moneter di Indonesia dalam praktiknya secara independen dijalankan oleh...", "options": ["Bank Indonesia selaku bank sentral", "Kementerian Keuangan Republik Indonesia", "Otoritas Jasa Keuangan sektor perbankan", "Lembaga Penjamin Simpanan nasabah", "Himpunan Bank Milik Negara (Himbara)"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelaksana Kebijakan Moneter Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kebijakan moneter ini dalam praktiknya dilakukan oleh Bank Indonesia</strong> selaku bank sentral yang bebas dari campur tangan pemerintah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena undang-undang menetapkan Bank Indonesia sebagai otoritas moneter independen yang memegang wewenang tunggal pengendalian uang beredar. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Bank Indonesia selaku bank sentral</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kementerian Keuangan adalah otoritas kebijakan fiskal, bukan moneter.</li><li><strong>C</strong>: OJK bertugas mengawasi industri jasa keuangan, bukan menetapkan kebijakan moneter.</li><li><strong>D</strong>: LPS bertugas menjamin simpanan nasabah perbankan yang likuidasi.</li><li><strong>E</strong>: Himbara adalah asosiasi bank komersial BUMN, bukan bank sentral.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kebijakan moneter ini dalam praktiknya dilakukan oleh Bank Indonesia. • Bank Indonesia.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Salah satu tujuan utama dilaksanakannya kebijakan moneter oleh bank sentral adalah...", "options": ["Menaikkan tarif pajak penghasilan badan usaha", "Mempertahankan kestabilan nilai mata uang rupiah", "Membayar gaji pokok aparatur sipil negara di daerah", "Membangun proyek infrastruktur jalan tol lintas pulau", "Mengalokasikan dana hibah bantuan sosial bencana"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Kebijakan Moneter Kestabilan Nilai Rupiah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan kebijakan moneter antara lain: <strong>mengarahkan penggunaan uang dan kredit sedemikian rupa sehingga nilai rupiah dapat dipertahankan kestabilannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena mandat pokok bank sentral adalah menjaga stabilitas moneter yang tecermin dari kestabilan inflasi dan stabilitas nilai tukar rupiah. Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Mempertahankan kestabilan nilai mata uang rupiah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penetapan tarif pajak adalah instrumen kebijakan fiskal dalam undang-undang.</li><li><strong>C</strong>: Pembayaran gaji ASN merupakan belanja pegawai dalam APBN/APBD.</li><li><strong>D</strong>: Pembangunan jalan tol dibiayai melalui belanja modal APBN atau investasi.</li><li><strong>E</strong>: Dana hibah bansos disalurkan melalui pos belanja APBN pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menyesuaikan jumlah uang yang beredar di masyarakat; • Mengarahkan penggunaan uang dan kredit sedemikian rupa sehingga nilai rupiah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penyediaan kredit dengan suku bunga rendah dalam kebijakan moneter diarahkan untuk...", "options": ["Memicu konsumsi barang-barang impor mewah", "Menekan pertumbuhan usaha mikro di perdesaan", "Mendorong produsen meningkatkan kegiatan produksi", "Memperbanyak penarikan uang tunai di masyarakat", "Menurunkan kapasitas daya saing industri dalam negeri"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Moneter Suku Bunga Rendah bagi Produsen</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan moneter bertujuan <strong>menyediakan kredit dengan suku bunga rendah untuk mendorong produsen meningkatkan kegiatan produksi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena suku bunga kredit yang terjangkau meringankan biaya modal bagi sektor riil sehingga output produksi nasional meningkat. Dengan demikian, opsi C benar.</p><p>Jawaban tepat: <strong>C. Mendorong produsen meningkatkan kegiatan produksi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kredit produksi bukan ditujukan untuk memicu konsumsi barang impor mewah.</li><li><strong>B</strong>: Kebijakan moneter mendukung ekspansi usaha mikro, bukan menekannya.</li><li><strong>D</strong>: Tujuannya adalah pembiayaan investasi riil, bukan memicu peredaran uang liar.</li><li><strong>E</strong>: Tujuannya memperkuat daya saing industri domestik, bukan menurunkannya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menyediakan kredit dengan suku bunga rendah untuk mendorong produsen • meningkatkan kegiatan produksi;</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Pemerintah bersama DPR menetapkan alokasi anggaran perlindungan sosial dan perpajakan tahunan. Instrumen resmi kebijakan ini tercermin dalam...", "options": ["Peraturan dewan gubernur bank sentral tentang suku bunga", "Surat edaran otoritas bursa efek tentang transaksi saham", "Laporan neraca pembayaran luar negeri perbankan", "Dokumen APBN yang disahkan menjadi undang-undang", "Perjanjian pinjaman komersial antar-perusahaan swasta"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Kebijakan Fiskal Melalui APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kebijakan fiskal tercermin dari APBN yang ditetapkan</strong> oleh pemerintah bersama lembaga legislatif setiap tahunnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena APBN merupakan wujud operasionalisasi kebijakan fiskal pemerintah yang memuat rencana pendapatan, pagu belanja, dan pembiayaan negara. Oleh sebab itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Dokumen APBN yang disahkan menjadi undang-undang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Peraturan dewan gubernur BI adalah instrumen kebijakan moneter, bukan fiskal.</li><li><strong>B</strong>: Edaran bursa efek mengatur pasar modal komersial.</li><li><strong>C</strong>: Neraca pembayaran adalah data statistik transaksi ekonomi internasional.</li><li><strong>E</strong>: Pinjaman antar-perusahaan swasta adalah ranah hukum perdata bisnis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menyediakan tingkat lapangan kerja tertentu; • lapangan kerja</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Bank Indonesia menjual Sertifikat Bank Indonesia (SBI) untuk menyerap kelebihan likuiditas uang. Tindakan ini merupakan implementasi dari...", "options": ["Kebijakan fiskal penerimaan pajak penghasilan", "Pengelolaan kekayaan negara yang dipisahkan", "Pencairan dana belanja bantuan sosial kemensos", "Penarikan dividen badan usaha milik negara", "Kebijakan moneter operasi pasar terbuka"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Operasi Pasar Terbuka Bank Sentral</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Operasi penjualan surat berharga untuk mengendalikan jumlah uang beredar merupakan bagian dari <strong>subbidang pengelolaan moneter melalui kebijakan pasar terbuka</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena penjualan SBI adalah instrumen klasik operasi pasar terbuka bank sentral guna menjaga stabilitas harga dan mengendalikan inflasi. Maka dari itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Kebijakan moneter operasi pasar terbuka</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak penghasilan adalah instrumen penerimaan fiskal pemerintah, bukan moneter.</li><li><strong>B</strong>: Kekayaan dipisahkan berkaitan dengan pembinaan BUMN/BUMD.</li><li><strong>C</strong>: Belanja bansos adalah pelaksanaan belanja fiskal kementerian sosial.</li><li><strong>D</strong>: Dividen BUMN adalah pos penerimaan bukan pajak (PNBP) APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>cadangan wajib bank, tingkat diskonto, kebijakan • pengendalian kredit dan kebijakan pasar terbuka</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Perbedaan mendasar antara subbidang pengelolaan fiskal dan subbidang pengelolaan moneter terletak pada...", "options": ["Fiskal mengelola APBN pemerintah, moneter mengendalikan uang beredar BI", "Fiskal dijalankan bank daerah, moneter dijalankan kementerian sosial", "Fiskal berfokus pada cetak uang, moneter berfokus pada belanja subsidi", "Fiskal bersifat nirlaba murni, moneter mencari laba komersial terbesar", "Fiskal diawasi kepolisian, moneter diawasi kejaksaan agung"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Perbedaan Subbidang Fiskal dan Moneter</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Subbidang fiskal mencakup penerimaan dan pengeluaran APBN oleh pemerintah, sedangkan subbidang moneter mengatur peredaran uang, suku bunga, dan kredit oleh Bank Indonesia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena membedakan secara tegas otoritas pelaksana dan instrumennya: Kementerian Keuangan dengan APBN (fiskal) vs Bank Indonesia dengan instrumen moneter (likuiditas). Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Fiskal mengelola APBN pemerintah, moneter mengendalikan uang beredar BI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bank daerah dan kemensos bukan pemegang otoritas kebijakan fiskal/moneter makro.</li><li><strong>C</strong>: Fokus tertukar terbalik; moneter yang mengelola uang dan fiskal yang mengelola belanja.</li><li><strong>D</strong>: Bank Indonesia bukan badan komersial pencari laba dividen.</li><li><strong>E</strong>: Kedua bidang tunduk pada sistem pengawasan perundangan tersendiri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pengelolaan kekayaan negara yang • dipisahkan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Pelaksanaan kebijakan moneter dituntut berjalan secara hati-hati (prudent) dengan batasan utama yaitu...", "options": ["Wajib menghasilkan setoran dividen kas terbesar bagi kas negara", "Dilaksanakan tanpa memberatkan beban keuangan negara dan masyarakat", "Mengharuskan suku bunga pinjaman dinaikkan setinggi mungkin", "Membekukan seluruh transaksi perbankan di akhir tahun anggaran", "Mewajibkan penukaran seluruh valuta asing menjadi rupiah paksa"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Batasan Pelaksanaan Kebijakan Moneter</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan kelima kebijakan moneter adalah <strong>mengusahakan agar kebijakan moneter dapat dilaksanakan tanpa memberatkan beban keuangan negara dan masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pengetatan moneter tidak boleh menimbulkan gejolak beban bunga utang pemerintah yang berlebihan atau mematikan daya beli masyarakat. Dengan demikian, opsi B benar.</p><p>Jawaban tepat: <strong>B. Dilaksanakan tanpa memberatkan beban keuangan negara dan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bank sentral bukan entitas pencari setoran dividen komersial.</li><li><strong>C</strong>: Bunga yang terlalu tinggi membebani dunia usaha dan memicu kredit macet.</li><li><strong>D</strong>: Membekukan perbankan melumpuhkan sistem pembayaran perekonomian.</li><li><strong>E</strong>: Penukaran paksa devisa bertentangan dengan rezim devisa bebas yang bertanggung jawab.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tujuan • kebijaksanaan moneter secara umum adalah:</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Subbidang pengelolaan kekayaan negara yang dipisahkan mencakup aset negara yang pengelolaannya diserahkan kepada...", "options": ["Yayasan sosial nirlaba milik perorangan menteri", "Perusahaan multinasional tanpa saham pemerintah", "Badan Usaha Milik Negara atau Daerah (BUMN/BUMD)", "Koperasi karyawan pada masing-masing kantor satker", "Lembaga konsultan bisnis penasihat investasi asing"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Subbidang Kekayaan Negara Dipisahkan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Subbidang Pengelolaan Kekayaan negara yang dipisahkan adalah komponen keuangan negara yang pengelolaannya diserahkan kepada perusahaan yang seluruh atau sebagian modal/sahamnya dimiliki oleh negara, atau sering disebut BUMN/BUMD</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pemisahan kekayaan negara secara hukum diwadahi dalam badan usaha korporasi negara, baik di tingkat pusat (BUMN) maupun daerah (BUMD). Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Badan Usaha Milik Negara atau Daerah (BUMN/BUMD)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Yayasan sosial perorangan bukan entitas pengelola kekayaan negara dipisahkan.</li><li><strong>B</strong>: Perusahaan tanpa saham pemerintah adalah murni korporasi privat.</li><li><strong>D</strong>: Koperasi pegawai berstatus perdata dan bukan BUMN/BUMD.</li><li><strong>E</strong>: Konsultan investasi hanya memberikan jasa advisory komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>komponen • keuangan negara yang pengelolaannya diserahkan kepada perusahaan yang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Hubungan keuangan antara kekayaan negara yang dipisahkan (BUMN) dengan APBN bersifat tidak langsung melalui pos...", "options": ["Pembayaran gaji bulanan seluruh pegawai dan direksi BUMN", "Penarikan langsung kas operasional rekening giro BUMN", "Penetapan harga barang eceran toko BUMN oleh bendahara", "Penyertaan modal negara dan setoran bagian laba/dividen", "Pemberian pinjaman operasional di luar batas peruntukan BUMN"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Kekayaan Negara Dipisahkan dengan APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekayaan dipisahkan memiliki hubungan tidak langsung dengan APBN, <strong>misalnya dalam hal pemerintah menyertakan tambahan modal (PMN) atau dalam hal adanya setoran bagian laba BUMN untuk Pemerintah sebagai pos pembiayaan/pendapatan APBN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena keuangan BUMN dikelola secara mandiri (corporate governance); keterkaitannya dengan APBN hanya melalui pos Penyertaan Modal Negara (PMN) dan dividen bagian laba. Maka dari itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Penyertaan modal negara dan setoran bagian laba/dividen</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Gaji pegawai BUMN dibayar dari kas operasional perusahaan, bukan dari APBN.</li><li><strong>B</strong>: Pemerintah tidak boleh menarik kas harian rekening giro BUMN secara langsung.</li><li><strong>C</strong>: Harga produk BUMN ditentukan oleh korporasi dan mekanisme pasar.</li><li><strong>E</strong>: Pemberian dana negara wajib dianggarkan sah dalam APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dengan APBN bukan hubungan langsung, tetapi tidak langsung, • pos-pos pembiayaan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menurut pakar keuangan publik Richard Musgrave, keuangan negara tidak sekadar uang masuk dan keluar melainkan berhubungan dengan...", "options": ["Pencetakan surat berharga komersial di bursa saham", "Perolehan keuntungan moneter maksimum bagi kas negara", "Pengurangan peran pemerintah dalam seluruh sektor publik", "Penghapusan seluruh sistem perpajakan di masyarakat", "Fungsi alokasi sumber ekonomi, distribusi, dan stabilisasi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Keuangan Negara Menurut Richard Musgrave</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut Musgrave, keuangan negara berhubungan dengan <strong>fungsi alokasi sumber-sumber ekonomi, fungsi distribusi, dan fungsi stabilisasi, termasuk pertumbuhan ekonomi dan dampaknya terhadap masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena teori keuangan publik Musgrave menegaskan peran instrumental anggaran negara dalam mengalokasikan sumber daya, mendistribusikan pendapatan, dan menjaga stabilitas ekonomi. Oleh karena itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Fungsi alokasi sumber ekonomi, distribusi, dan stabilisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Musgrave tidak mendefinisikan keuangan publik sebagai spekulasi surat berharga.</li><li><strong>B</strong>: Tujuan keuangan negara adalah kesejahteraan sosial, bukan laba komersial kas negara.</li><li><strong>C</strong>: Musgrave justru menekankan perlunya intervensi aktif pemerintah melalui anggaran.</li><li><strong>D</strong>: Pajak merupakan instrumen inti pembiayaan fungsi negara menurut Musgrave.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menurut Musgrave, keuangan negara tidak hanya berhubungan dengan uang • alokasi sumber-sumber</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pungutan pajak oleh pemerintah di satu sisi meningkatkan penerimaan negara, namun di sisi lain berdampak pada...", "options": ["Berkurangnya daya beli dan penurunan permintaan masyarakat", "Lonjakan inflasi tak terkendali di pasar komoditas", "Peningkatan drastis jumlah tabungan perbankan swasta", "Penurunan suku bunga pinjaman di bank sentral secara instan", "Hapusnya kewajiban pelaporan surat pemberitahuan tahunan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Pungutan Pajak terhadap Daya Beli</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pungutan pajak akan meningkatkan penerimaan negara, <strong>namun di lain pihak akan mengurangi daya beli masyarakat sehingga mengurangi permintaan masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pajak menyerap pendapatan siap pakai (disposable income) warga, sehingga mengurangi kemampuan belanja dan konsumsi riil masyarakat. Dengan demikian, opsi A benar.</p><p>Jawaban tepat: <strong>A. Berkurangnya daya beli dan penurunan permintaan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penarikan pajak justru bersifat meredam inflasi (kontraktif), bukan memicu inflasi.</li><li><strong>C</strong>: Pajak mengurangi disposable income sehingga tabungan cenderung tidak melonjak.</li><li><strong>D</strong>: Suku bunga bank sentral ditetapkan oleh dewan gubernur BI, bukan efek instan pajak.</li><li><strong>E</strong>: Pelaporan SPT tetap menjadi kewajiban hukum perpajakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keuangan negara, melalui penerimaan/pendapatan • bekerjanya</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Belanja pemerintah yang dialokasikan untuk membeli barang dan jasa dari masyarakat memberikan dampak ekonomi berupa...", "options": ["Macetnya arus kas perdagangan produsen dalam negeri", "Mendorong perputaran ekonomi dan menambah daya beli", "Penurunan kapasitas penyerapan tenaga kerja lokal", "Peningkatan suku bunga acuan bank sentral secara tajam", "Penghentian seluruh kegiatan produksi sektor swasta"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Belanja Pemerintah terhadap Perekonomian</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Belanja Pemerintah yang digunakan untuk membeli barang dan jasa akan mendorong ekonomi masyarakat dan kemudian akan menambah daya beli masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena injeksi belanja pemerintah menyuntikkan likuiditas ke pasar, menggerakkan produksi pengusaha lokal, dan menciptakan pendapatan bagi masyarakat. Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Mendorong perputaran ekonomi dan menambah daya beli</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja negara justru memperlancar likuiditas arus kas produsen.</li><li><strong>C</strong>: Belanja pemerintah menciptakan efek pengganda (multiplier effect) penyerapan tenaga kerja.</li><li><strong>D</strong>: Suku bunga acuan adalah instrumen kebijakan moneter terpisah.</li><li><strong>E</strong>: Belanja pemerintah menyuburkan bisnis swasta sebagai penyedia barang/jasa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>belanja Pemerintah, yang digunakan • untuk membeli barang dan jasa dari masyarakat,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kondisi di mana total penerimaan negara lebih besar daripada total pengeluaran belanja negara disebut...", "options": ["Anggaran statis berimbang", "Anggaran statis berimbang", "Surplus anggaran APBN", "Gagal bayar utang negara", "Krisis likuiditas perbankan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kondisi Surplus Anggaran Pendapatan dan Belanja Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Apabila penerimaan negara melebihi pengeluaran negara, maka <strong>Anggaran Pendapatan dan Belanja Negara (APBN) akan mengalami surplus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kelebihan penerimaan dibandingkan realisasi belanja secara baku didefinisikan sebagai surplus fiskal dalam terminologi APBN. Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Surplus anggaran APBN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Anggaran statis bukan istilah untuk kondisi penerimaan melebihi belanja.</li><li><strong>B</strong>: Anggaran statis bukan istilah untuk kondisi penerimaan melebihi belanja.</li><li><strong>D</strong>: Gagal bayar adalah ketidakmampuan melunasi kewajiban utang.</li><li><strong>E</strong>: Krisis likuiditas perbankan adalah masalah di sektor moneter keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penerimaan negara melebihi pengeluaran • Anggaran Pendapatan dan Belanja Negara (APBN) akan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Pemerintah mengalokasikan dana segar Rp5 triliun untuk memperkuat struktur permodalan PT PLN dalam proyek kelistrikan desa. Pos anggaran yang digunakan adalah...", "options": ["Belanja pegawai untuk gaji direksi dan komisaris PLN", "Belanja barang operasional kantor kementerian ESDM", "Penerimaan bukan pajak dari penjualan aset daerah", "Pembiayaan investasi melalui Penyertaan Modal Negara", "Bantuan sosial langsung tunai kepada direksi BUMN"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Penyertaan Modal Negara (PMN)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tambahan modal bagi BUMN dicatat dalam <strong>pos pembiayaan APBN melalui skema Penyertaan Modal Negara (PMN)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena penambahan modal pada kekayaan negara yang dipisahkan merupakan pengeluaran pembiayaan investasi negara, bukan belanja operasional kementerian. Oleh karena itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Pembiayaan investasi melalui Penyertaan Modal Negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Gaji direksi BUMN dibiayai dari pendapatan usaha perusahaan sendiri.</li><li><strong>B</strong>: Belanja barang KESDM adalah operasional kantor kementerian teknis.</li><li><strong>C</strong>: PMN adalah pos pengeluaran pembiayaan, bukan pos penerimaan.</li><li><strong>E</strong>: Bansos tunai ditujukan bagi keluarga miskin, bukan korporasi BUMN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemerintah menyertakan tambahan modal dalam BUMN • merupakan pos-pos pembiayaan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "PT Telkom membukukan laba bersih dan menyetorkan bagian laba dividen kepada pemerintah sebesar Rp3 triliun. Dalam APBN setoran ini dicatat pada pos...", "options": ["Penerimaan cukai tembakau dan minuman beralkohol", "Hibah luar negeri tanpa ikatan proyek pembangunan", "Pembiayaan penarikan utang surat berharga syariah", "Belanja subsidi harga energi bahan bakar minyak", "Penerimaan Negara Bukan Pajak (PNBP) bagian laba BUMN"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Penerimaan Dividen BUMN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setoran bagian laba dari BUMN merupakan salah satu komponen penerimaan negara dalam pos <strong>Pendapatan Bagian Laba BUMN (kelompok PNBP)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena dividen atas kepemilikan saham negara pada kekayaan yang dipisahkan disetorkan langsung ke kas negara sebagai bagian dari PNBP. Dengan demikian, opsi E benar.</p><p>Jawaban tepat: <strong>E. Penerimaan Negara Bukan Pajak (PNBP) bagian laba BUMN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cukai adalah pungutan atas barang kena cukai, bukan dividen laba BUMN.</li><li><strong>B</strong>: Hibah berasal dari pihak luar negeri atau donor sukarela.</li><li><strong>C</strong>: SBSN adalah instrumen pembiayaan utang syariah, bukan dividen laba.</li><li><strong>D</strong>: Subsidi BBM adalah pos belanja pengeluaran APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>setoran bagian laba BUMN untuk Pemerintah merupakan pos-pos pembiayaan • APBN.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Meskipun surplus APBN menjamin kecukupan dana belanja pemerintah, risiko kontraktif yang dapat timbul di masyarakat adalah...", "options": ["Turunnya daya beli akibat beban pungutan pajak yang tinggi", "Terjadinya lonjakan inflasi harga barang kebutuhan pokok", "Meningkatnya beban pembayaran bunga utang luar negeri", "Gagal bayar kewajiban gaji aparatur sipil negara", "Ketergantungan ekstrem pada pinjaman dana perbankan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Efek Samping Surplus Anggaran Tinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Surplus yang terlalu tinggi dapat berdampak negatif karena <strong>mengurangi daya beli masyarakat akibat beban pajak yang tinggi dan menciptakan ketidakseimbangan permintaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penarikan pajak yang berlebihan demi surplus menyedot uang dari sektor riil, sehingga konsumsi masyarakat menurun dan pertumbuhan ekonomi berisiko melambat. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Turunnya daya beli akibat beban pungutan pajak yang tinggi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Surplus justru cenderung menekan harga (deflatoir), bukan memicu inflasi tinggi.</li><li><strong>C</strong>: Surplus anggaran justru memperkecil kebutuhan penarikan utang baru.</li><li><strong>D</strong>: Surplus menjamin ketersediaan dana kas untuk seluruh belanja rutin termasuk gaji.</li><li><strong>E</strong>: Pemerintah surplus tidak bergantung pada pinjaman bank komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Surplus berarti penerimaan negara cukup untuk mendanai • daya beli masyarakat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Keuangan negara melalui kebijakan penerimaan dan belanja mampu mempengaruhi pertumbuhan ekonomi dengan cara...", "options": ["Menghapuskan hukum penawaran dan permintaan di pasar", "Mempengaruhi mekanisme harga, daya beli, dan permintaan", "Menetapkan kuota belanja harian per kapita secara paksa", "Mengambil alih seluruh kepemilikan pabrik manufaktur", "Mewajibkan warga menukar seluruh uangnya dengan kupon"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Pengaruh Keuangan Negara terhadap Mekanisme Harga</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keuangan negara mempengaruhi pertumbuhan ekonomi karena <strong>penerimaan dan belanja negara dapat mempengaruhi bekerjanya mekanisme harga dan daya beli masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena tarikan pajak dan dorongan belanja negara mengubah kurva permintaan dan penawaran agregat, yang secara langsung menentukan tingkat harga dan volume output nasional. Oleh sebab itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Mempengaruhi mekanisme harga, daya beli, dan permintaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kebijakan fiskal beroperasi di dalam mekanisme pasar, bukan menghapuskan hukum pasar.</li><li><strong>C</strong>: Negara tidak membatasi kuota belanja harian individu dalam sistem pasar terbuka.</li><li><strong>D</strong>: Pemerintah tidak menasionalisasi seluruh pabrik swasta.</li><li><strong>E</strong>: Sistem kupon tertutup bukan ciri pengelolaan keuangan modern.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dengan demikian tujuan • Pengelolaan Keuangan Negara adalah:</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Perbedaan pokok tata kelola antara aset Barang Milik Negara (BMN) kementerian dan kekayaan negara yang dipisahkan (BUMN) adalah...", "options": ["BMN bebas diaudit BPK, BUMN wajib diaudit inspektorat kementerian", "BMN wajib menghasilkan laba tunai, BUMN dilarang meraup laba", "BMN dikelola rezim perbendaharaan, BUMN dikelola rezim korporasi", "BMN dimiliki oleh pejabat satker, BUMN dimiliki oleh investor asing", "BMN tunduk pada hukum pasar modal, BUMN tunduk pada kas daerah"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Perbedaan Pengelolaan Aset BUMN dan BMN Kantor</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekayaan dipisahkan (BUMN) <strong>dikelola secara berbeda (rezim korporasi bisnis) terpisah dari mekanisme belanja operasional langsung APBN/BMN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena BMN satker terikat langsung pada tata usaha perbendaharaan dan APBN, sedangkan BUMN dikelola secara profesional mandiri berdasarkan undang-undang perseroan terbatas. Oleh karena itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. BMN dikelola rezim perbendaharaan, BUMN dikelola rezim korporasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kedua entitas tetap berada dalam yurisdiksi pemeriksaan audit BPK RI.</li><li><strong>B</strong>: BMN berorientasi pelayanan publik, sedangkan BUMN dituntut menghasilkan laba komersial.</li><li><strong>D</strong>: BMN adalah milik negara, bukan kepemilikan pribadi pejabat satker.</li><li><strong>E</strong>: BUMN perseroan terbuka yang tunduk pada pasar modal, bukan kas daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>keuangan negara yang pengelolaannya diserahkan kepada perusahaan yang • Badan Usaha Milik Negara (BUMN)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kondisi saat pengeluaran negara lebih besar dari penerimaannya (APBN defisit) memberikan dampak ekonomi berupa...", "options": ["Menurunkan jumlah peredaran uang di sektor riil", "Mematikan aktivitas perdagangan barang dan jasa", "Mengurangi alokasi anggaran infrastruktur publik", "Menambah daya beli masyarakat melalui injeksi belanja", "Menaikkan penerimaan kas negara secara otomatis"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Defisit APBN dan Daya Beli</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Apabila pengeluaran lebih besar dari penerimaan (APBN defisit), <strong>defisit akan menambah daya beli masyarakat lebih besar</strong> melalui stimulus belanja pemerintah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena defisit menandakan pemerintah membelanjakan lebih banyak uang daripada yang ditarik dari rakyat, sehingga likuiditas dan daya beli masyarakat bertambah. Maka dari itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Menambah daya beli masyarakat melalui injeksi belanja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Defisit menyuntikkan likuiditas baru, bukan menurunkan peredaran uang.</li><li><strong>B</strong>: Aktivitas perdagangan justru terdorong oleh permintaan pemerintah.</li><li><strong>C</strong>: Defisit sering kali dipicu oleh ekspansi pembangunan infrastruktur.</li><li><strong>E</strong>: Defisit adalah selisih kekurangan, bukan kenaikan penerimaan kas otomatis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>apabila pengeluaran lebih besar dari penerimaannya, yang berarti • APBN defisit, defisit akan menambah daya beli masyarakat lebih besar.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kebijakan fiskal counter-cyclical didefinisikan sebagai kebijakan anggaran pemerintah yang...", "options": ["Menyerahkan penyesuaian harga pada mekanisme pasar bebas murni", "Menyerahkan penyesuaian harga pada mekanisme pasar bebas murni", "Memangkas anggaran saat krisis ekonomi sedang melanda", "Menaikkan tarif pajak secara drastis saat terjadi resesi", "Bergerak berlawanan arah dengan siklus fluktuasi ekonomi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Kebijakan Fiskal Counter-Cyclical</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan fiskal <strong>counter-cyclical adalah kebijakan yang bergerak berlawanan arah dengan siklus ekonomi: meningkatkan belanja/menurunkan pajak saat resesi, dan sebaliknya saat inflasi tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena prinsip counter-cyclical dirancang untuk meredam ayunan gelombang siklus bisnis agar perekonomian tetap stabil dan terhindar dari krisis ekstrem. Dengan demikian, opsi E benar.</p><p>Jawaban tepat: <strong>E. Bergerak berlawanan arah dengan siklus fluktuasi ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pasar bebas murni tanpa intervensi rentan memicu kegagalan pasar.</li><li><strong>B</strong>: Pasar bebas murni tanpa intervensi rentan memicu kegagalan pasar.</li><li><strong>C</strong>: Memangkas anggaran saat krisis memperparah kontraksi resesi.</li><li><strong>D</strong>: Menaikkan pajak saat resesi memukul daya beli yang sudah lemah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kebijakan fiskal yang • bersifat counter-cyclical menjadi penting, yaitu kebijakan yang bergerak</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kebijakan fiskal yang sejalan dengan arah siklus ekonomi (meningkatkan belanja saat tumbuh dan memangkas saat lesu) disebut...", "options": ["Kebijakan fiskal pro-cyclical", "Kebijakan fiskal counter-cyclical", "Kebijakan moneter uang ketat", "Kebijakan proteksionisme tarif", "Kebijakan autarki perdagangan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Kebijakan Fiskal Pro-Cyclical</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kebijakan yang bersifat pro-cyclical adalah kebijakan yang sejalan dengan arah siklus ekonomi</strong>, yang berisiko memperkuat gejolak ekonomi dan memperburuk kondisi krisis.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena sifatnya yang searah (pro) dengan pergerakan siklus bisnis pasar, sehingga menambah akselerasi saat booming dan memperdalam jurang saat resesi. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Kebijakan fiskal pro-cyclical</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Counter-cyclical bergerak berlawanan arah untuk menstabilkan fluktuasi.</li><li><strong>C</strong>: Kebijakan uang ketat adalah instrumen moneter kenaikan suku bunga.</li><li><strong>D</strong>: Proteksionisme adalah tarif pembatasan barang impor luar negeri.</li><li><strong>E</strong>: Autarki adalah isolasi sistem ekonomi mandiri tanpa perdagangan luar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kebijakan yang bersifat pro-cyclical adalah kebijakan yang sejalan • dengan arah siklus ekonomi,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam strategi counter-cyclical, langkah yang wajib diambil pemerintah saat perekonomian mengalami resesi pelemahan adalah...", "options": ["Menutup peredaran uang kartal di seluruh wilayah negara", "Meningkatkan belanja negara atau menurunkan beban pajak", "Menaikkan tarif pajak dan memangkas bantuan sosial", "Menghentikan seluruh pencairan gaji pegawai pemerintah", "Mewajibkan bank swasta menyerahkan seluruh modalnya"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Instrumen Counter-Cyclical pada Masa Resesi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Saat ekonomi melemah (resesi), kebijakan counter-cyclical dijalankan dengan <strong>pemerintah meningkatkan belanja atau menurunkan pajak</strong> guna memulihkan permintaan agregat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena penurunan pajak dan peningkatan belanja menyuntikkan stimulus finansial ke pasar, mencegah gelombang PHK, dan membangkitkan gairah konsumsi rakyat. Oleh karena itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Meningkatkan belanja negara atau menurunkan beban pajak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menutup peredaran uang meruntuhkan sistem ekonomi transaksi.</li><li><strong>C</strong>: Menaikkan pajak saat resesi merupakan tindakan pro-cyclical yang memperburuk krisis.</li><li><strong>D</strong>: Pemberhentian gaji pegawai menghancurkan daya beli aparatur.</li><li><strong>E</strong>: Penyitaan modal bank swasta adalah tindakan inkonstitusional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>berlawanan arah dengan siklus ekonomi: pemerintah meningkatkan belanja • atau menurunkan pajak saat ekonomi melemah (resesi),</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Sebaliknya, pada saat ekonomi mengalami overheating atau inflasi tinggi, kebijakan counter-cyclical yang tepat adalah...", "options": ["Memperbesar defisit APBN hingga batas maksimal", "Membagikan bantuan langsung tunai berlipat ganda", "Mengurangi belanja negara atau menaikkan pajak", "Menurunkan seluruh suku bunga pinjaman perbankan", "Mencetak uang kertas baru dalam jumlah besar"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Instrumen Counter-Cyclical pada Masa Inflasi Tinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Saat ekonomi memanas (inflasi tinggi), pemerintah menjalankan tindakan pengetatan fiskal yaitu <strong>mengurangi belanja atau menaikkan pajak</strong> untuk mendinginkan permintaan berlebih.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena penarikan likuiditas lewat kenaikan pajak dan penundaan belanja negara akan menahan laju lonjakan harga barang umum (inflasi). Dengan demikian, opsi C benar.</p><p>Jawaban tepat: <strong>C. Mengurangi belanja negara atau menaikkan pajak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Memperbesar defisit justru memanaskan inflasi lebih parah.</li><li><strong>B</strong>: Menggandakan bansos saat overheating menambah kelebihan likuiditas.</li><li><strong>D</strong>: Menurunkan suku bunga memicu lonjakan kredit dan inflasi.</li><li><strong>E</strong>: Mencetak uang memicu hiperinflasi mata uang domestik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mengurangi belanja atau menaikkan pajak saat ekonomi memanas (inflasi • meredam fluktuasi ekonomi dan menjaga</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Teori ekonomi Keynesian yang dikemukakan oleh John Maynard Keynes menekankan pentingnya...", "options": ["Penyerahan seluruh urusan ekonomi pada mekanisme pasar bebas", "Penghapusan APBN dan perbankan sentral di setiap negara", "Kewajiban anggaran berimbang mutlak tanpa toleransi utang", "Peran aktif pemerintah melalui intervensi kebijakan fiskal", "Pemberlakuan mata uang komoditas tunggal tanpa uang kertas"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Teori Peran Aktif Pemerintah John Maynard Keynes</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pendapat Keynes menyatakan bahwa pemerintah memiliki peran aktif dalam mengelola perekonomian, terutama melalui kebijakan fiskal</strong> untuk mengatasi pengangguran dan inflasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena revolusi pemikiran Keynes mematahkan doktrin pasar bebas klasik dengan membuktikan pentingnya stimulus anggaran pemerintah dalam mengatasi depresi ekonomi. Maka dari itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Peran aktif pemerintah melalui intervensi kebijakan fiskal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pasar bebas murni tanpa intervensi adalah pandangan mazhab klasik Adam Smith.</li><li><strong>B</strong>: Keynes justru memperkuat peranan APBN dalam pengelolaan makroekonomi.</li><li><strong>C</strong>: Keynes membolehkan defisit anggaran untuk membiayai belanja stimulus.</li><li><strong>E</strong>: Mata uang komoditas emas bukan substansi pokok teori belanja fiskal Keynes.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>APBN dapat juga digunakan sebagai alat untuk mengatasi inflasi dan deflasi, • defisit APBN atau surplus APBN.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menurut Keynes, instrumen utama yang paling efektif untuk mendorong permintaan agregat saat ekonomi lesu adalah...", "options": ["Penundaan pembayaran gaji seluruh tenaga pendidik", "Penutupan akses ekspor barang komoditas keluar negeri", "Penaikan bea masuk bahan baku industri manufaktur", "Pembatasan jam operasional pasar tradisional daerah", "Belanja pemerintah secara terencana dan terarah"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pentingnya Belanja Pemerintah Menurut Keynes</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keynes menekankan <strong>pentingnya belanja pemerintah untuk mendorong permintaan agregat saat ekonomi lesu</strong> guna memulihkan lapangan kerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena ketika sektor swasta dan konsumsi rumah tangga melemah saat krisis, belanjalah pemerintah yang bertindak sebagai mesin penggerak utama permintaan ekonomi. Oleh sebab itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Belanja pemerintah secara terencana dan terarah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menunda gaji justru melumpuhkan konsumsi rumah tangga pegawai.</li><li><strong>B</strong>: Menutup ekspor mematikan devisa dan pendapatan produsen dalam negeri.</li><li><strong>C</strong>: Menaikkan bea bahan baku membebani industri manufaktur lokal.</li><li><strong>D</strong>: Membatasi pasar mengganggu rantai pasok distribusi logistik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>misalnya meningkatkan belanja saat ekonomi • sedang tumbuh dan menguranginya saat ekonomi melemah.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Saat terjadi krisis ekonomi pandemi, pemerintah memberikan insentif pajak PPh pasal 21 ditanggung pemerintah dan memperbesar bansos. Tindakan ini mencerminkan...", "options": ["Kebijakan fiskal counter-cyclical penyelamatan ekonomi", "Kebijakan pro-cyclical pengetatan anggaran negara", "Kebijakan moneter kenaikan cadangan wajib perbankan", "Penyerahan kendali fiskal kepada lembaga swasta", "Kebijakan belanja berlebih di luar batas kerangka fiskal"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Kebijakan Fiskal Pemulihan Krisis Pandemi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemberian stimulus bansos dan keringanan pajak saat masa krisis ekonomi adalah perwujudan konkret dari <strong>kebijakan fiskal counter-cyclical</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pemerintah melonggarkan pajak dan meningkatkan belanja pengaman sosial untuk melawan pelemahan ekonomi, persis sesuai formula counter-cyclical. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Kebijakan fiskal counter-cyclical penyelamatan ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pro-cyclical justru akan menaikkan pajak saat krisis dan memangkas bansos.</li><li><strong>C</strong>: Insentif pajak dan bansos adalah instrumen fiskal APBN, bukan kebijakan moneter BI.</li><li><strong>D</strong>: Kendali fiskal tetap dipegang oleh Kementerian Keuangan.</li><li><strong>E</strong>: Program pemulihan krisis didasarkan pada payung perundangan undang-undang resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>meredam fluktuasi ekonomi dan menjaga • stabilitas.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Sebuah negara mengalami penurunan pendapatan saat resesi dan langsung memotong drastis anggaran jaring pengaman sosialnya. Risiko yang dihadapi adalah...", "options": ["Pertumbuhan ekonomi langsung melonjak tinggi seketika", "Kondisi krisis makin parah akibat kebijakan pro-cyclical", "Nilai tukar mata uang menguat tanpa bantuan bank sentral", "Daya beli masyarakat miskin terlindungi sempurna", "Terbukanya jutaan lapangan kerja baru di sektor swasta"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Bahaya Kebijakan Fiskal Pro-Cyclical</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan pro-cyclical yang memotong belanja saat ekonomi lesu <strong>dapat memperkuat gejolak ekonomi dan berisiko memperburuk kondisi saat terjadi krisis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pemotongan bansos di tengah krisis memperdalam kejatuhan daya beli rakyat, memicu kontraksi konsumsi yang lebih parah, dan memperlama resesi. Dengan demikian, opsi B benar.</p><p>Jawaban tepat: <strong>B. Kondisi krisis makin parah akibat kebijakan pro-cyclical</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemotongan belanja saat resesi menekan pertumbuhan, bukan melonjakkannya.</li><li><strong>C</strong>: Krisis yang memburuk justru melemahkan stabilitas nilai tukar.</li><li><strong>D</strong>: Daya beli kaum miskin hancur akibat pemangkasan bantuan jaring sosial.</li><li><strong>E</strong>: Resesi yang mendalam meningkatkan angka pengangguran terbuka.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>memperkuat gejolak ekonomi dan berisiko memperburuk kondisi saat terjadi • krisis.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Perbedaan filosofis mendasar antara kebijakan counter-cyclical dan kebijakan pro-cyclical terletak pada...", "options": ["Instansi pelaksana: kementerian keuangan vs badan intelijen", "Dasar hukum: undang-undang resmi vs adat istiadat kedaerahan", "Arah respon anggaran: berlawanan arah vs sejalan siklus ekonomi", "Target mata uang: transaksi rupiah vs transaksi komoditas barter", "Objek audit: pemeriksaan BPK RI vs audit lembaga swadaya asing"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Pembeda Counter-Cyclical dan Pro-Cyclical</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Counter-cyclical <strong>bergerak berlawanan arah dengan siklus</strong> guna meredam fluktuasi, sedangkan pro-cyclical <strong>bergerak sejalan dengan siklus</strong> ekonomi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena perbedaan distingtif keduanya adalah orientasi respon arah fiskal terhadap gelombang pertumbuhan ekonomi: menahan gejolak (counter) atau justru mengikuti gelombang (pro). Maka dari itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Arah respon anggaran: berlawanan arah vs sejalan siklus ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kedua kebijakan dirumuskan oleh otoritas fiskal yang sama (Kemenkeu).</li><li><strong>B</strong>: Keduanya merupakan konsep teoretis kebijakan anggaran kenegaraan resmi.</li><li><strong>D</strong>: Keduanya bertransaksi menggunakan mata uang resmi negara.</li><li><strong>E</strong>: Pemeriksaan atas anggaran negara tetap dilakukan secara mandiri oleh BPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>bersifat counter-cyclical menjadi penting, yaitu kebijakan yang bergerak • berlawanan arah dengan siklus ekonomi:</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Perluasan tugas dan fungsi negara modern di luar konsep 'negara penjaga malam' (night-watchman state) ditandai oleh kewajiban...", "options": ["Menyerahkan urusan pertahanan kepada tentara bayaran privat", "Membatasi penyediaan barang publik bagi kelompok tertentu", "Membatasi penyediaan barang publik bagi kelompok tertentu", "Menjaga stabilitas perekonomian melalui kebijakan fiskal responsif", "Mewajibkan eksekutif tunduk pada instruksi pasar saham komersial"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Perluasan Fungsi Negara Modern</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas negara modern tidak sekadar pertahanan, peradilan, dan barang publik, <strong>namun juga menjaga kestabilan perekonomian melalui kebijakan fiskal yang responsif terhadap dinamika siklus ekonomi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena paradigma modern menuntut negara hadir secara aktif menstabilkan makroekonomi, mengatasi inflasi, serta menjamin lapangan kerja melalui intervensi fiskal APBN. Oleh sebab itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Menjaga stabilitas perekonomian melalui kebijakan fiskal responsif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pertahanan nasional tetap menjadi monopoli sah kedaulatan militer negara.</li><li><strong>B</strong>: Barang publik dinikmati oleh seluruh rakyat tanpa batasan golongan.</li><li><strong>C</strong>: Barang publik dinikmati oleh seluruh rakyat tanpa batasan golongan.</li><li><strong>E</strong>: Negara tidak boleh disubordinasi oleh kepentingan pasar saham komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kestabilan perekonomian melalui kebijakan fiskal yang responsif terhadap • dinamika siklus ekonomi.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam buku The Theory of Public Finance, Richard Musgrave menyatakan bahwa tugas dan fungsi negara meliputi...", "options": ["Monopoli perdagangan, privatisasi perbankan, dan deregulasi moneter", "Penyeragaman tarif pajak, penghapusan subsidi, dan ekspansi wilayah", "Sentralisasi industri, pembatasan devisa, dan pengendalian pasar", "Pengawasan peradilan, pencetakan uang kartal, dan penetapan bea impor", "Realokasi sumber daya, redistribusi pendapatan, dan stabilisasi ekonomi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Fungsi Negara Menurut Richard Musgrave</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut <strong>Richard Musgrave</strong> dalam buku <strong>The Theory of Public Finance</strong>, tugas dan fungsi negara meliputi: <strong>realokasi sumber-sumber daya ekonomi, redistribusi pendapatan, dan stabilisasi ekonomi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Musgrave mengklasifikasikan tiga cabang fungsi sektor publik fiskal modern guna mengatasi kegagalan pasar. Oleh sebab itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Realokasi sumber daya, redistribusi pendapatan, dan stabilisasi ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Monopoli dagang dan privatisasi bukan trisula fungsi negara versi Musgrave.</li><li><strong>B</strong>: Penyeragaman tarif dan ekspansi wilayah bukan teori keuangan publik.</li><li><strong>C</strong>: Sentralisasi industri merupakan prinsip ekonomi komando tertutup.</li><li><strong>D</strong>: Pencetakan uang merupakan kewenangan bank sentral bidang moneter.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>The Theory of Public Finance • realokasi sumber-sumber daya</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan fungsi realokasi sumber daya ekonomi oleh negara ditujukan untuk mengoptimalkan sumber daya melalui...", "options": ["Penyediaan barang publik dan pengaturan eksternalitas", "Pemberian pinjaman komersial kepada korporasi besar", "Penetapan tingkat bunga simpanan perbankan swasta", "Pengambilalihan aset produksi milik masyarakat luas", "Penetapan kuota produksi barang industri manufaktur daerah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Realokasi Sumber Daya Ekonomi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Realokasi sumber daya ekonomi</strong> dilakukan dengan memanfaatkan sumber ekonomi terbatas secara optimal, termasuk <strong>penyediaan barang publik dan pengaturan eksternalitas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pasar bebas kerap gagal menyediakan barang publik murni dan mengendalikan dampak eksternalitas, sehingga intervensi alokasi negara mutlak dibutuhkan. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Penyediaan barang publik dan pengaturan eksternalitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pinjaman komersial korporasi merupakan ranah bisnis bank komersial.</li><li><strong>C</strong>: Suku bunga simpanan perbankan ditentukan oleh mekanisme pasar dan BI.</li><li><strong>D</strong>: Pengambilalihan aset warga melanggar kepemilikan hak privat yang sah.</li><li><strong>E</strong>: Pembatasan volume panen justru merugikan ketahanan pangan nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Realokasi sumber daya ekonomi dilakukan dengan memanfaatkan • penyediaan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Fungsi redistribusi pendapatan yang diselenggarakan negara melalui kebijakan fiskal bertujuan untuk menciptakan keadilan melalui...", "options": ["Penerbitan saham perdana perusahaan daerah", "Sistem perpajakan dan transfer pembayaran", "Pemberian dana talangan investasi pemodal besar", "Pengendalian jumlah devisa di bursa berjangka", "Penghapusan pungutan retribusi daerah serentak"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Fungsi Redistribusi Pendapatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Redistribusi pendapatan</strong> bertujuan untuk <strong>menciptakan keadilan sosial melalui sistem perpajakan dan transfer pembayaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena redistribusi bekerja dengan memungut penerimaan dari kelompok berpendapatan tinggi lalu menyalurkannya kembali kepada kelompok rentan via belanja transfer. Oleh karena itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Sistem perpajakan dan transfer pembayaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penerbitan saham perdana merupakan kegiatan penghimpunan modal bursa efek.</li><li><strong>C</strong>: Dana talangan pemodal besar bertentangan dengan prinsip keadilan sosial.</li><li><strong>D</strong>: Pengendalian devisa valuta asing berada di bawah domain kebijakan BI.</li><li><strong>E</strong>: Penghapusan retribusi serentak mengurangi pendapatan asli daerah resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Redistribusi pendapatan bertujuan untuk menciptakan keadilan sosial • sistem perpajakan dan transfer pembayaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Fungsi stabilisasi ekonomi dilaksanakan oleh pemerintah guna menjaga kestabilan makroekonomi, terutama dalam rangka...", "options": ["Menentukan tarif premi asuransi kendaraan umum", "Mengatur komisi transaksi perdagangan bursa saham", "Mengatasi inflasi, deflasi, dan pengangguran", "Menetapkan biaya sewa pergudangan logistik swasta", "Menyeragamkan harga jual properti perumahan kota"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Fungsi Stabilisasi Ekonomi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Stabilisasi ekonomi</strong> dilakukan untuk <strong>menjaga kestabilan makroekonomi, seperti mengatasi inflasi, deflasi, dan pengangguran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena sasaran inti stabilisasi fiskal adalah memelihara keseimbangan fundamental ekonomi makro dari ancaman lonjakan harga serta resesi ketenagakerjaan. Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Mengatasi inflasi, deflasi, dan pengangguran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tarif premi asuransi kendaraan merupakan produk industri keuangan komersial.</li><li><strong>B</strong>: Komisi transaksi efek ditetapkan oleh otoritas regulator pasar modal.</li><li><strong>D</strong>: Sewa gudang swasta ditentukan oleh mekanisme kesepakatan perdata niaga.</li><li><strong>E</strong>: Harga properti ditentukan oleh mekanisme penawaran dan permintaan pasar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Stabilisasi ekonomi dilakukan untuk menjaga kestabilan makroekonomi • mengatasi inflasi, deflasi, dan pengangguran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pemerintah mendorong redistribusi pendapatan melalui APBN dengan tujuan utama agar tidak terjadi...", "options": ["Penurunan indeks harga saham gabungan di bursa efek", "Persaingan usaha antarprodusen barang elektronik lokal", "Perubahan pola konsumsi barang impor kelas menengah", "Kesenjangan mencolok antara kelompok kaya dan miskin", "Penurunan suku bunga kredit perumahan rakyat nasional"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pencegahan Kesenjangan melalui APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Melalui kebijakan fiskal dalam APBN, Pemerintah <strong>mendorong terjadinya redistribusi pendapatan agar tidak terjadi kesenjangan antara golongan masyarakat kaya dan miskin secara mencolok</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena redistribusi fiskal berfungsi mempersempit jurang disparitas kesejahteraan antarlapisan pendapatan warga demi stabilitas sosial. Maka dari itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Kesenjangan mencolok antara kelompok kaya dan miskin</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Indeks harga saham berfluktuasi dipengaruhi oleh sentimen pasar modal.</li><li><strong>B</strong>: Persaingan usaha produsen diawasi oleh komisi pengawas persaingan niaga.</li><li><strong>C</strong>: Pola konsumsi barang impor dipengaruhi oleh daya beli dan preferensi pasar.</li><li><strong>E</strong>: Suku bunga kredit perumahan dipengaruhi kebijakan moneter perbankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Mendorong Re-distribusi Pendapatan • Melalui kebijakan fiskal dalam APBN</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penerapan prinsip 'ability to pay' dalam kebijakan perpajakan pemerintah diwujudkan melalui pemungutan pajak yang...", "options": ["Diberikan persentase tetap sama bagi seluruh lapisan warga", "Dihapuskan secara bertahap bagi pemilik korporasi konglomerat", "Ditentukan berdasarkan kesepakatan negosiasi perorangan", "Diberlakukan secara acak sesuai kebutuhan belanja kementerian", "Lebih besar kepada kelompok masyarakat yang lebih mampu"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Prinsip Ability to Pay</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Untuk menciptakan keadilan, pemerintah <strong>mengenakan pajak yang lebih banyak kepada kelompok masyarakat yang lebih mampu (ability to pay principle)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena prinsip kemampuan membayar mendasari tarif progresif, di mana wajib pajak berkapasitas ekonomi tinggi memikul kontribusi pajak lebih proporsional. Oleh karena itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Lebih besar kepada kelompok masyarakat yang lebih mampu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tarif tetap tanpa melihat daya pikul bertentangan dengan keadilan vertikal.</li><li><strong>B</strong>: Menghapuskan pajak bagi korporasi besar merusak basis penerimaan negara.</li><li><strong>C</strong>: Pajak bersifat memaksa berdasarkan undang-undang, bukan negosiasi privat.</li><li><strong>D</strong>: Pemungutan acak bertentangan dengan asas kepastian hukum perpajakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>ability to pay principle • pajak yang lebih banyak</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Kebijakan pengeluaran pemerintah yang berpihak kepada masyarakat berpenghasilan rendah (pro-poor) diterapkan dalam bentuk...", "options": ["Alokasi bantuan sosial dan subsidi dasar tepat sasaran", "Pembangunan sarana rekreasi mewah di pusat kawasan bisnis", "Pemberian insentif pembebasan pajak barang mewah impor", "Pengadaan armada kendaraan dinas protokoler kementerian", "Penyediaan fasilitas penerbangan eksekutif pejabat pusat"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alokasi Belanja Pro-Poor</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah mengalokasikan penerimaan dalam bentuk <strong>pengeluaran/belanja negara yang berpihak kepada masyarakat yang kurang mampu (pro poor)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena wujud nyata belanja pro-poor adalah mengarahkan anggaran APBN untuk pemenuhan jaring pengaman sosial, perlindungan dasar, dan subsidi warga rentan. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Alokasi bantuan sosial dan subsidi dasar tepat sasaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sarana rekreasi mewah tidak menyentuh kebutuhan masyarakat berpenghasilan rendah.</li><li><strong>C</strong>: Pembebasan pajak barang mewah impor menguntungkan kalangan mampu.</li><li><strong>D</strong>: Armada dinas protokoler merupakan belanja barang aparatur pemerintah.</li><li><strong>E</strong>: Fasilitas penerbangan eksekutif pejabat merupakan belanja dinas kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>belanja negara yang berpihak • pro poor</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Apabila sumber daya yang ada di masyarakat tidak terdistribusikan secara optimal, dampak yang timbul adalah...", "options": ["Peningkatan cadangan devisa pemerintah secara berlebih", "Kesenjangan sosial dan ketidakstabilan pasar ekonomi", "Pertumbuhan pesat seluruh industri pengolahan primer", "Kemandirian penuh pembiayaan modal koperasi daerah", "Penurunan beban utang pokok pemerintah luar negeri"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Disparitas Distribusi Sumber Daya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Apabila sumber daya yang ada di masyarakat <strong>tidak terdistribusikan secara optimal, maka akan menimbulkan ketidakseimbangan dalam perekonomian negara, seperti kesenjangan sosial dan ketidakstabilan pasar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kegagalan pemerataan distribusi sumber daya memicu friksi sosial, konsentrasi kekayaan sepihak, serta distorsi efisiensi pasar makro. Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Kesenjangan sosial dan ketidakstabilan pasar ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ketimpangan distribusi sumber daya tidak berkaitan dengan lonjakan devisa.</li><li><strong>C</strong>: Distorsi pasar justru menghambat pertumbuhan industri pengolahan.</li><li><strong>D</strong>: Koperasi daerah kerap kesulitan modal apabila distribusi sumber daya timpang.</li><li><strong>E</strong>: Ketidakstabilan pasar dapat meningkatkan beban risiko fiskal negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tidak terdistribusikan secara • ketidakseimbangan dalam perekonomian</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Undang-Undang Nomor 17 Tahun 2003, pemegang kekuasaan umum pengelolaan keuangan negara adalah...", "options": ["Menteri Keuangan selaku bendahara umum negara", "Ketua Badan Pemeriksa Keuangan Republik Indonesia", "Presiden sebagai bagian dari kekuasaan pemerintahan", "Pimpinan Dewan Perwakilan Rakyat Republik Indonesia", "Gubernur Bank Indonesia selaku pemegang otoritas moneter"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemegang Kekuasaan Umum Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan UU Nomor 17 Tahun 2003 tentang Keuangan Negara, <strong>Presiden adalah pemegang kekuasaan umum pengelolaan keuangan negara sebagai bagian dari kekuasaan pemerintahan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena konstitusi menempatkan kekuasaan pemerintahan negara di tangan Presiden, yang meliputi kekuasaan umum pengelolaan fiskal kenegaraan. Oleh karena itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Presiden sebagai bagian dari kekuasaan pemerintahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menteri Keuangan menjalankan mandat kuasa yang dilimpahkan oleh Presiden.</li><li><strong>B</strong>: Ketua BPK merupakan pimpinan lembaga audit eksternal independen negara.</li><li><strong>D</strong>: DPR menjalankan fungsi legislasi, anggaran, dan pengawasan parlemen.</li><li><strong>E</strong>: Gubernur BI memegang otoritas independen di bidang moneter negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Presiden • pemegang kekuasaan umum pengelolaan keuangan negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pelimpahan kekuasaan Presiden kepada Menteri Keuangan dijalankan dalam kapasitas mandat selaku...", "options": ["Pengguna anggaran bagi seluruh kementerian negara teknis", "Penanggung jawab tunggal penetapan kebijakan suku bunga", "Pemeriksa independen atas laporan keuangan pemerintah", "Pengelola fiskal dan wakil pemerintah dalam kekayaan dipisahkan", "Kepala pelaksana operasional pembangunan infrastruktur jalan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mandat Kuasa Presiden kepada Menteri Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Fungsi pemegang kekuasaan umum dikuasakan kepada Menteri Keuangan <strong>selaku Pengelola Fiskal dan Wakil Pemerintah dalam kepemilikan kekayaan Negara yang dipisahkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Menteri Keuangan bertindak sebagai pengelola kebijakan fiskal makro sekaligus pemegang kuasa kepemilikan saham negara pada BUMN. Oleh sebab itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Pengelola fiskal dan wakil pemerintah dalam kekayaan dipisahkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengguna anggaran kementerian teknis berada pada menteri masing-masing.</li><li><strong>B</strong>: Kebijakan suku bunga merupakan wewenang Bank Indonesia selaku bank sentral.</li><li><strong>C</strong>: Pemeriksaan laporan keuangan negara adalah mandat konstitusional BPK.</li><li><strong>E</strong>: Pelaksana proyek fisik jalan merupakan tugas teknis kementerian terkait.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengelola Fiskal dan Wakil Pemerintah dalam kepemilikan kekayaan • dikuasakan kepada Menteri Keuangan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kekuasaan pengelolaan keuangan negara yang dilimpahkan Presiden kepada menteri atau pimpinan lembaga berkedudukan selaku...", "options": ["Pengelola Fiskal pembina perbendaharaan nasional", "Wakil Pemerintah dalam kepemilikan kekayaan daerah", "Pemeriksa internal kelaikan laporan audit BPK", "Pengatur likuiditas lalu lintas pembayaran perbankan", "Pengguna Anggaran atau Pengguna Barang kementerian"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mandat Kuasa kepada Pimpinan Lembaga</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekuasaan pengelolaan keuangan negara <strong>selaku Pengguna Anggaran/Pengguna Barang kementerian negara/lembaga negara dikuasakan kepada masing-masing menteri/pimpinan lembaga</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pimpinan kementerian/lembaga bertanggung jawab mengelola anggaran dan barang milik negara pada unit organisasi yang dipimpinnya. Maka dari itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Pengguna Anggaran atau Pengguna Barang kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengelola fiskal nasional merupakan mandat khusus Menteri Keuangan.</li><li><strong>B</strong>: Wakil pemerintah kekayaan daerah adalah mandat kepala daerah.</li><li><strong>C</strong>: Pemeriksaan kelaikan audit merupakan fungsi BPK, bukan menteri teknis.</li><li><strong>D</strong>: Pengatur likuiditas perbankan adalah otoritas Bank Sentral.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengguna Anggaran/Pengguna Barang • menteri/pimpinan lembaga</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kekuasaan pengelolaan keuangan negara diserahkan oleh Presiden kepada gubernur, bupati, atau walikota dalam kapasitas selaku...", "options": ["Kepala pemerintahan daerah untuk mengelola keuangan daerah", "Bendahara umum negara pengelola kas perbendaharaan pusat", "Pengguna anggaran kementerian teknis urusan dalam negeri", "Pemeriksa independen kepatuhan belanja perbankan daerah", "Kuasa pengelola moneter penstabil nilai tukar rupiah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyerahan Pengelolaan Keuangan Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekuasaan pengelolaan keuangan negara dijalankan dalam bentuk <strong>penyerahan kepada gubernur/bupati/walikota selaku kepala Pemerintahan daerah untuk mengelola keuangan daerah dan mewakili Pemerintah daerah dalam kepemilikan kekayaan daerah yang dipisahkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena dalam kerangka desentralisasi otonomi daerah, kepala daerah memegang kekuasaan pengelolaan APBD dan BUMD. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Kepala pemerintahan daerah untuk mengelola keuangan daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bendahara Umum Negara dijabat oleh Menteri Keuangan di tingkat pusat.</li><li><strong>C</strong>: Pengguna anggaran kementerian teknis dijabat oleh menteri teknis.</li><li><strong>D</strong>: Pemeriksa kepatuhan belanja adalah fungsi aparat pengawas dan BPK.</li><li><strong>E</strong>: Pengendali nilai tukar rupiah adalah wewenang Bank Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Penyerahan kepada gubernur/bupati/walikota selaku kepala Pemerintahan • mengelola keuangan daerah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Undang-Undang Nomor 17 Tahun 2003 menegaskan bahwa kekuasaan pemerintah di bidang fiskal tidak mencakup...", "options": ["Pengelolaan penerimaan perpajakan dan bea cukai pusat", "Kewenangan di bidang moneter demi stabilitas nilai rupiah", "Pengalokasian belanja operasional kementerian teknis", "Pengelolaan pembiayaan utang dan penerbitan obligasi negara", "Penyusunan nota keuangan dan rancangan undang-undang APBN"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemisahan Bidang Fiskal dan Kewenangan Moneter</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekuasaan pengelolaan keuangan negara menegaskan bahwa <strong>kekuasaan di bidang fiskal tidak termasuk kewenangan di bidang moneter</strong> guna mencapai stabilitas nilai rupiah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena hukum keuangan negara memisahkan secara tegas kewenangan fiskal pemerintah dengan otoritas moneter yang dipegang bank sentral independen. Oleh sebab itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Kewenangan di bidang moneter demi stabilitas nilai rupiah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penerimaan perpajakan dan kepabeanan merupakan wewenang sah fiskal Menkeu.</li><li><strong>C</strong>: Belanja kementerian teknis merupakan bagian dari pengelolaan fiskal.</li><li><strong>D</strong>: Pengelolaan pembiayaan utang adalah domain kewenangan fiskal pemerintah.</li><li><strong>E</strong>: Penyusunan nota keuangan merupakan mandat pemerintah di bidang fiskal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kekuasaan di bidang fiskal tidak termasuk kewenangan di bidang moneter • stabilitas nilai rupiah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penetapan dan pelaksanaan kebijakan moneter serta penjagaan kelancaran sistem pembayaran dilaksanakan oleh...", "options": ["Kementerian Koordinator Bidang Perekonomian", "Lembaga Penjamin Simpanan Perbankan Nasional", "Bank Sentral, yakni Bank Indonesia", "Badan Pengawasan Keuangan dan Pembangunan", "Otoritas Jasa Keuangan Bidang Perasuransian"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Otoritas Pelaksana Kebijakan Moneter</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penetapan dan pelaksanaan kebijakan moneter serta mengatur dan menjaga kelancaran sistem pembayaran <strong>dilakukan oleh Bank Sentral, yakni Bank Indonesia yang tunduk pada peraturan perundangan di bidang moneter</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Bank Indonesia adalah bank sentral independen yang diberi mandat konstitusional menjaga kestabilan nilai rupiah dan sistem pembayaran. Maka dari itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Bank Sentral, yakni Bank Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kemenko Perekonomian menjalankan fungsi koordinasi sinkronisasi kementerian.</li><li><strong>B</strong>: LPS bertugas menjamin simpanan nasabah dan resolusi bank gagal.</li><li><strong>D</strong>: BPKP bertugas melakukan audit dan pengawasan internal pemerintah.</li><li><strong>E</strong>: OJK bertugas mengawasi sektor jasa keuangan terintegrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Bank Sentral, yakni Bank Indonesia • menjaga kelancaran sistem pembayaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menteri Keuangan pada hakikatnya bertindak sebagai Chief Financial Officer (CFO) pemerintah yang berwenang atas...", "options": ["Pelaksanaan teknis pembangunan proyek pelabuhan laut", "Penetapan putusan peradilan tata usaha niaga negara", "Pengawasan kedisiplinan pegawai aparatur dinas daerah", "Pengelolaan aset dan kewajiban negara secara nasional", "Pengesahan perizinan operasional lembaga penyiaran publik"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Menteri Keuangan sebagai Chief Financial Officer</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menteri Keuangan sebagai pembantu Presiden dalam bidang keuangan pada hakikatnya adalah <strong>Chief Financial Officer (CFO) yang berwenang dan bertanggung jawab atas pengelolaan aset dan kewajiban negara secara nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kapasitas CFO menuntut Menteri Keuangan menjaga neraca keuangan negara, kas umum, serta manajemen risiko fiskal negara secara utuh. Oleh karena itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Pengelolaan aset dan kewajiban negara secara nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Proyek pelabuhan merupakan wewenang teknis operasional Kementerian Perhubungan.</li><li><strong>B</strong>: Putusan peradilan tata usaha niaga adalah domain kekuasaan yudikatif.</li><li><strong>C</strong>: Kedisiplinan dinas daerah merupakan tugas pembinaan kepegawaian daerah.</li><li><strong>E</strong>: Perizinan penyiaran adalah wewenang kementerian bidang komunikasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Chief Financial Officer (CFO) • pengelolaan aset dan kewajiban negara secara nasional</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Para menteri teknis dan pimpinan lembaga pada hakikatnya berkedudukan sebagai Chief Operational Officer (COO) yang bertanggung jawab atas...", "options": ["Pengelolaan utang piutang negara secara menyeluruh", "Penerbitan surat utang negara di pasar modal global", "Pengendalian saldo rekening kas umum negara pusat", "Pengaturan portofolio cadangan devisa internasional", "Penyelenggaraan pemerintahan sesuai bidang tugas masing-masing"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pimpinan Lembaga sebagai Chief Operational Officer</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Para menteri dan pimpinan lembaga negara pada hakikatnya adalah <strong>Chief Operational Officer (COO) yang berwenang dan bertanggung jawab atas penyelenggaraan pemerintahan sesuai bidang tugas dan fungsi masing-masing</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena menteri teknis menjalankan eksekusi kegiatan operasional program kerja sektoral untuk mencapai keluaran pelayanan kementerian. Oleh sebab itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Penyelenggaraan pemerintahan sesuai bidang tugas masing-masing</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengelolaan utang piutang negara agregat adalah wewenang Menkeu (CFO).</li><li><strong>B</strong>: Penerbitan surat utang negara merupakan kewenangan eksklusif Menkeu.</li><li><strong>C</strong>: Pengendalian kas umum negara pusat adalah mandat Bendahara Umum Negara.</li><li><strong>D</strong>: Pengaturan cadangan devisa internasional merupakan wewenang Bank Sentral.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Chief Operational Officer • penyelenggaraan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pembagian kewenangan yang jelas antara Menteri Keuangan dan menteri teknis dirancang untuk memberikan jaminan...", "options": ["Terlaksananya mekanisme saling uji dan kejelasan akuntabilitas", "Pemberian hak veto mutlak kementerian keuangan atas regulasi", "Pencairan dana anggaran dinas tanpa verifikasi bukti belanja", "Penghapusan kewajiban pelaporan berkala realisasi belanja", "Penyatuan seluruh rekening bendahara ke rekening pribadi pimpinan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mekanisme Saling Uji (Check and Balance)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembagian kewenangan yang jelas bertujuan memberikan <strong>jaminan terlaksananya mekanisme saling uji (check and balance) dan jaminan atas kejelasan akuntabilitas Menteri Keuangan sebagai Bendahara Umum Negara dan Menteri Teknis sebagai Pengguna Anggaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pemisahan tegas antara pemegang kas (BUN) dan pemegang otorisasi belanja (PA) mencegah penyalahgunaan kekuasaan keuangan secara sepihak. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Terlaksananya mekanisme saling uji dan kejelasan akuntabilitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kementerian Keuangan tidak memiliki hak veto atas regulasi kementerian teknis.</li><li><strong>C</strong>: Pencairan dana tanpa verifikasi berkas melanggar disiplin perbendaharaan.</li><li><strong>D</strong>: Pelaporan realisasi belanja merupakan kewajiban akuntabilitas yang mutlak.</li><li><strong>E</strong>: Penyatuan rekening dinas ke rekening pribadi dilarang keras oleh hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mekanisme saling uji (check and balance) • kejelasan akuntabilitas</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam arsitektur perbendaharaan negara, kejelasan akuntabilitas ditegakkan melalui penetapan kedudukan...", "options": ["Menteri teknis sebagai BUN dan Menteri Keuangan sebagai Pengguna Barang", "Menteri Keuangan sebagai BUN dan menteri teknis sebagai Pengguna Anggaran", "Gubernur BI sebagai BUN dan Presiden sebagai penanggung jawab kasir", "Ketua BPK sebagai BUN dan kementerian teknis sebagai pengawas internal", "Kepala Bappenas sebagai BUN dan Menkeu sebagai perencana sektoral"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Fungsional BUN dan Pengguna Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tata kelola keuangan negara memberikan kejelasan akuntabilitas <strong>Menteri Keuangan sebagai Bendahara Umum Negara (BUN) dan Menteri Teknis sebagai Pengguna Anggaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Menteri Keuangan bertindak sebagai pengelola kas negara (komptabel) dan menteri teknis bertindak sebagai pemegang hak otorisasi kegiatan (ordonatur). Oleh karena itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Menteri Keuangan sebagai BUN dan menteri teknis sebagai Pengguna Anggaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menteri teknis bertindak sebagai pengguna anggaran, bukan bendahara umum.</li><li><strong>C</strong>: Gubernur BI memimpin bank sentral, bukan bendahara umum negara.</li><li><strong>D</strong>: Ketua BPK bertindak sebagai auditor eksternal, bukan bendahara umum.</li><li><strong>E</strong>: Kepala Bappenas menyusun perencanaan, bukan bendahara umum negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Bendahara Umum Negara • Menteri Teknis sebagai Pengguna Anggaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pembagian kewenangan pengelolaan keuangan memberikan fleksibilitas bagi menteri teknis dengan tujuan utama...", "options": ["Menambah alokasi pagu anggaran belanja tanpa persetujuan DPR", "Mengalihkan kelebihan kas negara ke instrumen investasi berisiko", "Optimalisasi kinerja kementerian untuk menghasilkan output yang ditetapkan", "Menetapkan tarif pungutan perpajakan sektoral secara mandiri", "Menunda penyampaian laporan pertanggungjawaban realisasi kegiatan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fleksibilitas Pengelolaan Anggaran Kementerian Teknis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembagian kewenangan memberikan <strong>fleksibilitas bagi menteri teknis, sebagai pengguna anggaran, untuk mengatur penggunaan anggaran kementeriannya secara efisien dan efektif dalam rangka optimalisasi kinerja kementeriannya untuk menghasilkan output yang telah ditetapkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pendekatan penganggaran berbasis kinerja memberikan keleluasaan operasional menteri teknis agar fokus mencapai keluaran program secara optimal. Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Optimalisasi kinerja kementerian untuk menghasilkan output yang ditetapkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penambahan pagu anggaran belanja tetap wajib memperoleh persetujuan DPR.</li><li><strong>B</strong>: Pengalihan kas negara ke investasi spekulatif dilarang oleh regulasi.</li><li><strong>D</strong>: Tarif pajak ditetapkan melalui undang-undang bersama DPR, bukan kementerian teknis.</li><li><strong>E</strong>: Penundaan pelaporan pertanggungjawaban melanggar asas akuntabilitas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>fleksibilitas bagi menteri teknis, sebagai pengguna • optimalisasi kinerja kementeriannya untuk menghasilkan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menteri teknis diberikan wewenang mengatur penggunaan anggaran kementeriannya karena pihak kementerian teknis...", "options": ["Memiliki wewenang mencetak uang kartal untuk operasional proyek", "Berhak mengubah struktur kode akun anggaran belanja secara mandiri", "Berhak mengubah struktur kode akun anggaran belanja secara mandiri", "Paling memahami operasional kebijakan sektor yang menjadi bidangnya", "Bertanggung jawab atas pengendalian tingkat inflasi nasional tahunan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Pemberian Wewenang Pengguna Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menteri teknis berwenang mengatur penggunaan anggaran <strong>karena kementerian teknis yang paling memahami operasional kebijakan sektor-sektor yang menjadi bidangnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kementerian teknis memiliki kompetensi substansi operasional paling mendalam atas program-program kerja sektoral di lapangan. Maka dari itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Paling memahami operasional kebijakan sektor yang menjadi bidangnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pencetakan uang merupakan kewenangan eksklusif Bank Sentral.</li><li><strong>B</strong>: Bagan akun standar anggaran ditetapkan secara terpusat oleh Menkeu.</li><li><strong>C</strong>: Bagan akun standar anggaran ditetapkan secara terpusat oleh Menkeu.</li><li><strong>E</strong>: Pengendalian inflasi merupakan koordinasi makro fiskal dan moneter.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>paling memahami • operasional kebijakan sektor-sektor yang menjadi bidangnya</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Bentuk pelaksanaan kekuasaan umum pengelolaan keuangan negara mencakup penyerahan mandat berikut, KECUALI...", "options": ["Penguasaan pengelolaan fiskal kepada Menteri Keuangan selaku CFO", "Pelimpahan status pengguna anggaran kepada pimpinan lembaga", "Penyerahan pengelolaan keuangan daerah kepada gubernur atau bupati", "Kewenangan mewakili pemerintah dalam kekayaan yang dipisahkan", "Pelimpahan kewenangan moneter kepada menteri koordinator teknis"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembedaan Mandat Kekuasaan Keuangan (Kecuali)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekuasaan pengelolaan keuangan negara <strong>tidak mencakup kewenangan di bidang moneter</strong>, karena kebijakan moneter merupakan otoritas Bank Indonesia secara mandiri.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat sebagai pengecualian karena kewenangan moneter tidak pernah dilimpahkan kepada menteri kabinet manapun, melainkan mandiri pada Bank Sentral. Oleh karena itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Pelimpahan kewenangan moneter kepada menteri koordinator teknis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengelolaan fiskal kepada Menteri Keuangan adalah mandat resmi perundangan.</li><li><strong>B</strong>: Pengguna anggaran pada pimpinan lembaga merupakan bentuk mandat resmi.</li><li><strong>C</strong>: Pengelolaan keuangan daerah kepada kepala daerah adalah amanat desentralisasi.</li><li><strong>D</strong>: Mewakili kekayaan dipisahkan adalah wewenang sah Menteri Keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemerintah dalam kepemilikan kekayaan negara yang dipisahkan; • kepala Pemerintahan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Penerapan mekanisme saling uji (check and balance) di lingkungan satker tercermin ketika pejabat pengguna anggaran...", "options": ["Menerbitkan SPM yang diuji keabsahannya oleh KPPN selaku kuasa BUN", "Mencairkan dana belanja langsung melalui rekening pribadi bendahara", "Meminta persetujuan lisan kepada direksi bank sentral setempat", "Menetapkan batasan alokasi pagu belanja secara mandiri di satker", "Mengalihkan anggaran pembangunan untuk kepentingan investasi saham"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Saling Uji dalam Pencairan Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mekanisme saling uji terwujud melalui pemisahan kewenangan jelas antara <strong>satker kementerian teknis selaku Pengguna Anggaran yang menerbitkan SPM dan KPPN selaku Kuasa BUN yang menerbitkan SP2D</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena hubungan satker (PA/PPSPM) dengan KPPN (Kuasa BUN) mencerminkan saling uji akuntabilitas pengujian tagihan belanja negara sebelum uang kas dicairkan. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Menerbitkan SPM yang diuji keabsahannya oleh KPPN selaku kuasa BUN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pencairan ke rekening pribadi melanggar tata tertib perbendaharaan.</li><li><strong>C</strong>: Pencairan anggaran satker tidak memerlukan persetujuan lisan Bank Indonesia.</li><li><strong>D</strong>: Penetapan pagu belanja tanpa dasar hukum bertentangan dengan asas legalitas.</li><li><strong>E</strong>: Pengalihan anggaran ke saham swasta melanggar peruntukan DIPA dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kewenangan yang jelas, dalam pengelolaan keuangan negara • memberikan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam menjalankan kebijakan moneter dan sistem pembayaran, Bank Indonesia memiliki kedudukan yang...", "options": ["Bertanggung jawab langsung di bawah arahan menteri perdagangan", "Tunduk pada peraturan perundang-undangan di bidang moneter", "Merupakan anak badan usaha milik perbankan swasta nasional", "Bebas dari kewajiban memelihara stabilitas nilai tukar rupiah", "Fokus melayani transaksi penerimaan retribusi dinas pemda"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kepatuhan Hukum Bank Sentral</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bank Indonesia bertindak sebagai bank sentral yang <strong>tunduk pada peraturan perundangan di bidang moneter</strong> guna memelihara stabilitas nilai rupiah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena independensi kelembagaan Bank Indonesia tetap berlandaskan pada kepatuhan terhadap koridor regulasi undang-undang moneter negara. Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Tunduk pada peraturan perundang-undangan di bidang moneter</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bank Indonesia bersifat independen, bukan di bawah kementerian perdagangan.</li><li><strong>C</strong>: Bank Indonesia adalah lembaga negara berkedudukan bank sentral.</li><li><strong>D</strong>: Menjaga stabilitas nilai rupiah merupakan tugas inti konstitusional BI.</li><li><strong>E</strong>: BI melayani sistem pembayaran nasional terintegrasi secara makro.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tunduk pada peraturan • perundangan di bidang moneter</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kekuasaan pengelolaan keuangan daerah tingkat operasional dijalankan oleh kepala satuan kerja pengelola keuangan daerah selaku...", "options": ["Auditor independen belanja daerah", "Pemeriksa kepatuhan perpajakan pusat", "Pejabat pengelola APBD", "Kuasa pemegang kas perbankan swasta", "Pengawas sengketa tata kelola perdata"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pejabat Pengelola Keuangan Daerah (PPKD)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengelolaan keuangan daerah dilaksanakan oleh <strong>kepala satuan kerja pengelola keuangan daerah selaku pejabat pengelola APBD</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Kepala SKPKD berkedudukan sebagai Pejabat Pengelola Keuangan Daerah (PPKD) yang mengemban fungsi bendahara umum daerah. Oleh karena itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Pejabat pengelola APBD</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Auditor belanja daerah merupakan wewenang aparat inspektorat dan BPK.</li><li><strong>B</strong>: Pemeriksa pajak pusat adalah otoritas Direktorat Jenderal Pajak.</li><li><strong>D</strong>: Pemegang kas bank swasta adalah manajemen perbankan komersial.</li><li><strong>E</strong>: Pengawas sengketa perdata merupakan kewenangan institusi peradilan umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kepala satuan kerja • pengelola keuangan daerah selaku pejabat pengelola APBD</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pejabat yang mengemban mandat selaku pengguna anggaran atau barang di lingkungan dinas pemerintah daerah adalah...", "options": ["Pimpinan badan usaha swasta pelaksana proyek", "Ketua dewan perwakilan rakyat daerah", "Kepala kantor perwakilan bank sentral daerah", "Kepala satuan kerja perangkat daerah", "Pimpinan unit pelaksana teknis dinas daerah"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengguna Anggaran dan Barang Tingkat Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekuasaan pengelolaan keuangan daerah dilaksanakan oleh <strong>kepala satuan kerja perangkat daerah selaku pejabat pengguna anggaran/barang daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Kepala SKPD (dinas/badan daerah) bertanggung jawab memimpin operasional satuan kerja dan mengeksekusi DPA dinasnya. Oleh sebab itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Kepala satuan kerja perangkat daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Badan usaha swasta pelaksana proyek berkedudukan sebagai penyedia barang/jasa.</li><li><strong>B</strong>: DPRD menjalankan fungsi pengawasan dan persetujuan peraturan daerah.</li><li><strong>C</strong>: Kantor bank sentral daerah tidak mengelola anggaran belanja dinas pemda.</li><li><strong>E</strong>: Paguyuban pegawai merupakan wadah keanggotaan profesi non-struktural.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kepala satuan kerja perangkat daerah • pengguna anggaran/barang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penerapan Asas Tahunan dalam pengelolaan keuangan negara membatasi berlakunya DIPA pada masa waktu...", "options": ["Dua belas bulan terhitung sejak pelantikan pejabat eselon", "Enam bulan masa sidang anggaran perwakilan legislatif", "Tiga triwulan operasional dinas sesuai tahun kalender hijriah", "Lima tahun penuh sesuai periode rencana pembangunan jangka menengah", "Satu tahun anggaran mulai 1 Januari sampai 31 Desember"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Tahunan Pengelolaan Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Tahunan</strong> membatasi berlakunya anggaran untuk satu tahun anggaran, di mana <strong>DIPA berlaku mulai 1 Januari s.d. 31 Desember</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena tahun anggaran negara Indonesia dimulai pada tanggal 1 Januari dan berakhir pada tanggal 31 Desember tahun berkenaan. Maka dari itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Satu tahun anggaran mulai 1 Januari sampai 31 Desember</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masa berlaku anggaran tidak dihitung dari tanggal pelantikan pejabat.</li><li><strong>B</strong>: Masa berlaku anggaran berlaku satu tahun penuh, bukan enam bulan sidang.</li><li><strong>C</strong>: Tahun kalender anggaran didasarkan pada kalender masehi resmi kenegaraan.</li><li><strong>D</strong>: Periode lima tahun merupakan dokumen perencanaan RPJMN, bukan DIPA tahunan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tahunan yang membatasi berlakunya anggaran untuk satu tahun anggaran • DIPA berlaku mulai 1 Januari s.d. 31 Desember</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Berdasarkan Asas Tahunan, seluruh tagihan pembayaran yang timbul pada tahun berkenaan hanya boleh dibebankan pada...", "options": ["Dokumen DIPA resmi tahun anggaran yang bersangkutan", "Estimasi pagu belanja dinas lima tahun berikutnya", "Rekening penampungan taktis pimpinan unit kerja", "Pos penerimaan dividen perusahaan milik pemerintah daerah", "Simpanan sukarela pegawai aparatur satuan kerja"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembebanan Tagihan Menurut Asas Tahunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan asas tahunan, <strong>seluruh tagihan pada tahun yang bersangkutan hanya bisa dibebankan pada DIPA tahun yang bersangkutan tersebut</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena prinsip periodisitas anggaran melarang pembebanan belanja di luar dokumen anggaran tahun buku berjalan tanpa mekanisme resmi. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Dokumen DIPA resmi tahun anggaran yang bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Tagihan tahun berjalan tidak boleh dibebankan sembarangan pada pagu lima tahunan.</li><li><strong>C</strong>: Dana penampungan taktis dilarang dalam pengelolaan keuangan negara.</li><li><strong>D</strong>: Dividen daerah merupakan pos penerimaan kekayaan daerah dipisahkan.</li><li><strong>E</strong>: Simpanan perorangan pegawai bukan sumber pembiayaan belanja dinas negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Seluruh • tagihan pada tahun yang bersangkutan hanya bisa dibebankan pada DIPA</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Asas Universalitas mewajibkan seluruh pengeluaran dan penerimaan negara dimasukkan dalam APBN, yang menegaskan bahwa...", "options": ["Pendapatan satker boleh dialihkan ke kas operasional mitra kerja", "Dana non-budgeter tidak diperkenankan keberadaannya", "Belanja operasional dinas boleh didanai dari kas titipan pihak ketiga", "Unit kerja berhak membentuk pos anggaran rahasia tanpa pencatatan", "Penerimaan negara bukan pajak boleh langsung digunakan tanpa otorisasi"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Universalitas dan Larangan Dana Non-Budgeter</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Universalitas</strong> menghendaki agar setiap pengeluaran dan penerimaan negara/daerah harus berdasarkan dan dimasukkan dalam APBN/APBD. <strong>Dana non-budgeter tidak diperkenankan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena asas ini menolak segala bentuk penerimaan atau pengeluaran di luar pencatatan resmi APBN guna menjamin integritas kas negara. Oleh sebab itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Dana non-budgeter tidak diperkenankan keberadaannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengalihan pendapatan ke rekening paguyuban merupakan pelanggaran hukum kas.</li><li><strong>C</strong>: Pendanaan belanja dari titipan kas gelap dilarang keras dalam regulasi.</li><li><strong>D</strong>: Pembentukan pos rahasia tanpa pembukuan melanggar transparansi fiskal.</li><li><strong>E</strong>: Penggunaan langsung PNBP tanpa mekanisme DIPA melanggar undang-undang perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Universalitas yang menghendaki agar setiap pengeluaran negara/daerah • Dana non-budgeter tidak diperkenankan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penerapan Asas Kesatuan (unified budget) dalam penyusunan APBN ditandai oleh penyajian seluruh anggaran dalam satu dokumen tanpa...", "options": ["Pencatatan rincian belanja modal aparatur kementerian", "Pengesahan resmi dari badan perwakilan perundangan rakyat", "Pemisahan antara anggaran rutin dan anggaran pembangunan", "Keterlibatan pimpinan lembaga dalam penyusunan usulan program", "Pemeriksaan saldo kas oleh badan pemeriksa keuangan resmi"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Kesatuan (Unified Budget)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Kesatuan</strong> yaitu semua pendapatan dan belanja negara/daerah disajikan dalam satu dokumen anggaran (<strong>unified budget</strong>) dan <strong>tidak lagi dikelompokkan dalam anggaran rutin dan anggaran pembangunan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena reformasi keuangan negara menghapus dikotomi anggaran rutin dan anggaran pembangunan menjadi satu dokumen penganggaran terpadu. Maka dari itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Pemisahan antara anggaran rutin dan anggaran pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja modal aparatur tetap dicatat secara rinci dalam dokumen anggaran.</li><li><strong>B</strong>: Pengesahan APBN tetap mutlak melalui persetujuan bersama DPR RI.</li><li><strong>D</strong>: Pimpinan lembaga tetap terlibat penuh menyusun usulan belanja instansinya.</li><li><strong>E</strong>: Pemeriksaan saldo kas oleh BPK tetap berjalan secara rutin dan berkala.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kesatuan yaitu semua pendapatan dan belanja negara/daerah disajikan dalam • satu dokumen anggaran (unified budget)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Asas Spesialitas dalam pengelolaan keuangan negara mewajibkan bahwa kredit anggaran yang tersedia dalam DIPA harus...", "options": ["Disajikan secara gelondongan tanpa batasan pos belanja", "Disediakan dalam bentuk rekening cadangan tanpa peruntukan", "Diberikan kebebasan dialihkan antarkementerian tanpa prosedur", "Disajikan secara terperinci dan jelas peruntukan belanjanya", "Ditetapkan secara tertutup bagi pengawasan aparat pemeriksa"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Spesialitas Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Spesialitas</strong> yaitu <strong>kredit anggaran yang tersedia harus dirinci dan jelas peruntukannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena asas ini mengharuskan otorisasi anggaran dialokasikan secara spesifik menurut jenis belanja, fungsi, serta program kerja yang sah. Oleh karena itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Disajikan secara terperinci dan jelas peruntukan belanjanya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Anggaran gelondongan (lump sum) tanpa batas dilarang dalam sistem DIPA.</li><li><strong>B</strong>: Rekening cadangan tanpa peruntukan melanggar asas transparansi belanja.</li><li><strong>C</strong>: Pengalihan antarpos anggaran wajib melalui mekanisme revisi DIPA resmi.</li><li><strong>E</strong>: Anggaran negara wajib dapat diakses dan diawasi oleh auditor resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Spesialitas yaitu kredit anggaran yang tersedia harus dirinci dan jelas • peruntukannya</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Asas Akuntabilitas yang berorientasi pada hasil mewajibkan pertanggungjawaban kegiatan keuangan negara kepada rakyat karena...", "options": ["Seluruh kekayaan negara berasal dari pinjaman luar negeri", "Laporan keuangan difokuskan untuk konsumsi telaah dinas internal", "Laporan keuangan difokuskan untuk konsumsi telaah dinas internal", "Masyarakat menanggung seluruh beban utang komersial perbankan", "Setiap sen uang negara pada dasarnya adalah uang rakyat"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Akuntabilitas Berorientasi Hasil</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Akuntabilitas yang berorientasi pada hasil</strong> menentukan bahwa setiap kegiatan dan hasil akhir pengelolaan keuangan negara harus dapat dipertanggungjawabkan kepada rakyat, <strong>karena pada dasarnya setiap sen uang negara adalah uang rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena legitimasi pengelolaan keuangan publik bertumpu pada mandat amanah rakyat yang membiayai negara melalui pembayaran pajak. Oleh sebab itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Setiap sen uang negara pada dasarnya adalah uang rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keuangan negara didanai terutama oleh pendapatan pajak domestik.</li><li><strong>B</strong>: Laporan keuangan negara dipublikasikan terbuka sebagai wujud akuntabilitas.</li><li><strong>C</strong>: Laporan keuangan negara dipublikasikan terbuka sebagai wujud akuntabilitas.</li><li><strong>D</strong>: Beban utang perbankan privat merupakan tanggung jawab entitas usaha terkait.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Akuntabilitas yang berorientasi pada hasil • setiap sen uang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan prinsip akuntabilitas keuangan negara, hasil akhir pengelolaan keuangan harus dipertanggungjawabkan kepada masyarakat selaku...", "options": ["Pemegang kedaulatan tertinggi negara", "Kreditur tunggal pembiayaan surat utang publik", "Konsumen akhir dari seluruh jasa badan usaha swasta", "Mitra pengadaan barang pada lelang perbendaharaan", "Penjamin pinjaman komersial perbankan internasional"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rakyat sebagai Pemegang Kedaulatan Tertinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengelolaan keuangan negara harus dapat dipertanggungjawabkan kepada <strong>masyarakat atau rakyat sebagai pemegang kedaulatan tertinggi negara</strong> sesuai peraturan perundang-undangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena dalam sistem demokrasi konstitusional, kedaulatan berada di tangan rakyat sehingga pemerintah wajib mempertanggungjawabkan setiap rupiah penggunaan dana publik. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Pemegang kedaulatan tertinggi negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Surat utang publik dibeli oleh berbagai investor domestik dan global.</li><li><strong>C</strong>: Masyarakat adalah warga negara berdaulat, bukan semata konsumen swasta.</li><li><strong>D</strong>: Mitra pengadaan merupakan rekanan kerja kontrak pihak ketiga.</li><li><strong>E</strong>: Penjamin pinjaman komersial perbankan bukan kedudukan konstitusional warga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>masyarakat atau rakyat sebagai • pemegang kedaulatan tertinggi negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penerapan Asas Profesionalitas dalam tata kelola keuangan negara menuntut pengelola perbendaharaan mengutamakan...", "options": ["Kedekatan hubungan kekerabatan dengan pimpinan kementerian", "Keahlian dan kompetensi yang berlandaskan kode etik", "Kecepatan pencairan dana tanpa memeriksa bukti tagihan", "Kepatuhan pada instruksi lisan tanpa dokumen pengesahan", "Keringanan sanksi atas temuan ketidakpatuhan administrasi"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Profesionalitas dalam Pengelolaan Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Profesionalitas</strong> berarti <strong>mengutamakan keahlian dan kompetensi yang berlandaskan kode etik dan ketentuan perundang-undangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pengelolaan keuangan negara menuntut integritas moral, standar keilmuan perbendaharaan, serta kepatuhan etika kedinasan yang tinggi. Oleh karena itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Keahlian dan kompetensi yang berlandaskan kode etik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hubungan kekerabatan bertentangan dengan prinsip meritokrasi dan profesionalisme.</li><li><strong>C</strong>: Pencairan dana tanpa verifikasi bukti melanggar prosedur verifikasi tagihan.</li><li><strong>D</strong>: Pengelolaan anggaran wajib berdasarkan dokumen tertulis yang berkekuatan hukum.</li><li><strong>E</strong>: Pemberian keringanan sepihak atas temuan melanggar tata tertib audit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Profesionalitas, yang berarti mengutamakan keahlian dan kompetensi yang • berlandaskan kode etik</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Asas Proporsionalitas dalam pengelolaan keuangan negara diartikan sebagai asas yang mengutamakan keseimbangan antara...", "options": ["Jumlah belanja modal dan belanja subsidi bahan bakar", "Pendapatan dari sektor migas dan penerimaan pajak badan", "Hak dan kewajiban penyelenggara negara", "Kapasitas ruangan kerja dan jumlah pegawai aparatur kantor", "Volume penerbitan obligasi dan pinjaman proyek bilateral"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Proporsionalitas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Proporsionalitas</strong> yakni asas yang <strong>mengutamakan keseimbangan antara hak dan kewajiban penyelenggara negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena keadilan tata kelola menuntut aparatur memperoleh haknya secara wajar dan proporsional sebanding dengan kewajiban tugas kenegaraan yang dipikulnya. Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Hak dan kewajiban penyelenggara negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keseimbangan belanja modal dan subsidi adalah kebijakan postur fiskal.</li><li><strong>B</strong>: Penerimaan migas dan pajak badan merupakan komposisi pendapatan negara.</li><li><strong>D</strong>: Kapasitas ruangan kantor adalah urusan manajemen fasilitas dinas internal.</li><li><strong>E</strong>: Porsi obligasi dan pinjaman luar negeri adalah strategi pembiayaan utang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Proporsionalitas, yakni asas yang mengutamakan keseimbangan antara hak • dan kewajiban penyelenggara negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Asas-asas umum pengelolaan keuangan negara dirumuskan untuk memberikan daya dukung optimal pemerintahan, mencakup asas berikut, KECUALI...", "options": ["Asas Tahunan yang membatasi masa berlakunya anggaran", "Asas Kesatuan yang menyajikan anggaran dalam satu dokumen", "Asas Spesialitas yang merinci peruntukan kredit anggaran", "Asas Komersialisasi Aset demi memaksimalkan laba kasir", "Asas Akuntabilitas yang berorientasi pada pencapaian hasil"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Asas Umum Keuangan Negara (Kecuali)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Asas umum pengelolaan keuangan negara meliputi <strong>Asas Tahunan, Asas Universalitas, Asas Kesatuan, Asas Spesialitas, Asas Akuntabilitas, Asas Profesionalitas, dan Asas Proporsionalitas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat sebagai pengecualian karena tata kelola keuangan negara berorientasi pada pelayanan publik, bukan mengejar keuntungan komersial layaknya korporasi bisnis. Maka dari itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Asas Komersialisasi Aset demi memaksimalkan laba kasir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Asas Tahunan merupakan pilar resmi asas umum perundangan keuangan negara.</li><li><strong>B</strong>: Asas Kesatuan adalah asas resmi dokumen anggaran tunggal terpadu.</li><li><strong>C</strong>: Asas Spesialitas merupakan asas rincian pos kredit anggaran resmi.</li><li><strong>E</strong>: Asas Akuntabilitas berorientasi hasil adalah asas pokok tata kelola APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>daya dukung penyelenggaraan pemerintahan negara yang optimal • keuangan negara dikelola berdasarkan asas umum sebagai berikut:</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Asas Keterbukaan membuka diri terhadap hak masyarakat untuk memperoleh informasi tentang keuangan negara yang bersifat...", "options": ["Rahasia jabatan yang diperuntukkan bagi pejabat perbendaharaan", "Terbatas bagi perwakilan anggota asosiasi atau lembaga riset perbankan", "Khusus dan disesuaikan menurut diskresi pimpinan pengelola kas daerah", "Mengikat secara internal pada forum koordinasi antar-instansi teknis", "Benar, jujur, akurat, dan tidak diskriminatif bagi seluruh warga"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Keterbukaan Informasi Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Keterbukaan</strong> yaitu asas yang membuka diri terhadap hak masyarakat untuk memperoleh <strong>informasi yang benar, jujur, dan tidak diskriminatif</strong> tentang pengelolaan keuangan negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena transparansi menuntut keterbukaan informasi publik yang valid, akurat, dan dapat diakses oleh seluruh lapisan masyarakat secara adil. Oleh karena itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Benar, jujur, akurat, dan tidak diskriminatif bagi seluruh warga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Informasi keuangan negara bersifat terbuka bagi publik luas dan bukan rahasia jabatan.</li><li><strong>B</strong>: Akses informasi fiskal tidak dibatasi bagi kelompok atau asosiasi tertentu.</li><li><strong>C</strong>: Informasi keuangan negara bersifat umum dan bukan konsumsi khusus pimpinan.</li><li><strong>D</strong>: Keterbukaan informasi menjangkau masyarakat luas, bukan forum internal instansi semata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>informasi yang benar, jujur, dan • tidak</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam penyelenggaraan Asas Keterbukaan, penyampaian informasi keuangan negara tetap wajib memperhatikan perlindungan atas...", "options": ["Hak asasi pribadi, golongan, dan rahasia negara", "Kepentingan laba usaha para rekanan penyedia swasta", "Target perolehan dividen bank sentral internasional", "Kerahasiaan identitas instansi yang terkena audit BPK", "Rencana promosi jabatan aparatur di lingkungan kementerian"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batasan Keterbukaan Informasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keterbukaan informasi keuangan dilaksanakan dengan tetap memperhatikan <strong>perlindungan atas hak-hak asasi pribadi, golongan, dan rahasia negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena keterbukaan publik memiliki batasan hukum mutlak terhadap data privat perseorangan dan aspek pertahanan keamanan kedaulatan negara. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Hak asasi pribadi, golongan, dan rahasia negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kepentingan laba mitra swasta bukan alasan pembatasan informasi publik.</li><li><strong>C</strong>: Dividen bank sentral bukan objek perlindungan rahasia asasi keterbukaan.</li><li><strong>D</strong>: Identitas instansi auditee tertuang dalam laporan resmi hasil audit BPK.</li><li><strong>E</strong>: Promosi aparatur merupakan administrasi kepegawaian berbasis merit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>perlindungan atas hak-hak asasi • rahasia</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Asas pemeriksaan keuangan oleh badan pemeriksa yang bebas dan mandiri dalam praktiknya dilaksanakan oleh...", "options": ["Badan Pengawasan Keuangan dan Pembangunan", "Badan Pemeriksa Keuangan (BPK RI)", "Komisi Pengawas Persaingan Usaha Niaga", "Inspektorat Jenderal Kementerian Keuangan", "Otoritas Pengawas Lembaga Pembiayaan Publik"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Lembaga Pemeriksa Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemeriksaan keuangan oleh badan pemeriksa yang bebas dan mandiri dalam praktiknya <strong>dilakukan oleh Badan Pemeriksa Keuangan (BPK RI)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena BPK RI merupakan satu-satunya lembaga audit eksternal independen yang diamanatkan langsung oleh UUD 1945 untuk memeriksa keuangan negara. Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Badan Pemeriksa Keuangan (BPK RI)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: BPKP merupakan aparat pengawasan intern pemerintah (APIP) ranah eksekutif.</li><li><strong>C</strong>: KPPU bertugas mengawasi persaingan niaga usaha komersial.</li><li><strong>D</strong>: Itjen Kemenkeu bertindak sebagai auditor internal tingkat kementerian.</li><li><strong>E</strong>: Pengawas lembaga pembiayaan merupakan fungsi pengawasan OJK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemeriksaan keuangan oleh badan pemeriksa yang bebas dan mandiri • Badan Pemeriksa Keuangan (BPK RI)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Karakter 'bebas dan mandiri' yang dimiliki Badan Pemeriksa Keuangan (BPK RI) mengandung arti bahwa lembaga ini...", "options": ["Bebas menyimpang dari tata cara pemeriksaan keuangan negara", "Mandiri menetapkan tarif pajak bagi badan usaha komersial", "Bebas menyimpang dari tata cara pemeriksaan keuangan negara", "Bebas dari kewajiban menyampaikan hasil audit kepada DPR", "Mandiri mencairkan kas negara untuk keperluan operasional dinas"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter Bebas dan Mandiri BPK RI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Karakteristik <strong>bebas dan mandiri</strong> menjamin Badan Pemeriksa Keuangan dapat melakukan pemeriksaan secara objektif <strong>tanpa intervensi dari cabang kekuasaan eksekutif maupun pihak lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena independensi konstitusional BPK menjamin temuan dan opini hasil pemeriksaan keuangan negara steril dari tekanan politik kekuasaan. Oleh karena itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Bebas menyimpang dari tata cara pemeriksaan keuangan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: BPK terikat secara ketat pada Standar Pemeriksaan Keuangan Negara.</li><li><strong>B</strong>: Penetapan tarif pajak adalah wewenang DPR bersama Presiden.</li><li><strong>D</strong>: BPK diwajibkan konstitusi menyerahkan hasil pemeriksaan kepada DPR/DPD/DPRD.</li><li><strong>E</strong>: Pencairan kas negara adalah wewenang Menkeu selaku Bendahara Umum Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>bebas dan mandiri • Badan Pemeriksa Keuangan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Satuan kerja pemerintah mengimplementasikan Asas Keterbukaan dalam pengelolaan anggarannya dengan cara...", "options": ["Menunda klarifikasi tertulis permohonan penjelasan dari perwakilan warga", "Menyediakan akses informasi keuangan bagi pejabat struktural saja", "Menyediakan akses informasi keuangan bagi pejabat struktural saja", "Menyediakan publikasi berkala tentang kinerja realisasi anggaran", "Menghapus papan pengumuman pengadaan barang dinas di kantor"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Keterbukaan Informasi pada Unit Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan asas keterbukaan diwujudkan dengan menghormati <strong>hak masyarakat untuk memperoleh informasi tentang pengelolaan keuangan negara</strong> melalui saluran komunikasi publik resmi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena mempublikasikan laporan realisasi anggaran satker secara transparan memenuhi hak publik atas akuntabilitas penggunaan uang negara. Oleh sebab itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Menyediakan publikasi berkala tentang kinerja realisasi anggaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menunda penjelasan warga bertentangan dengan asas keterbukaan publik.</li><li><strong>B</strong>: Membatasi informasi bagi pimpinan struktural melanggar transparansi dinas.</li><li><strong>C</strong>: Membatasi informasi bagi pimpinan struktural melanggar transparansi dinas.</li><li><strong>E</strong>: Papan lelang pengadaan wajib dipublikasikan secara terbuka melalui LPSE.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>hak • masyarakat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Ketentuan yang mewajibkan perlindungan hak asasi pribadi dan golongan dalam Asas Keterbukaan bertujuan untuk...", "options": ["Memberi perlindungan hukum bagi pelaku pelanggaran anggaran dinas", "Membatasi proses pemeriksaan aparat penegak hukum atas pos belanja", "Membatasi proses pemeriksaan aparat penegak hukum atas pos belanja", "Membebaskan pejabat publik dari kewajiban pelaporan kekayaan", "Mencegah penyalahgunaan data rahasia individu dari eksploitasi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keseimbangan Transparansi dan Hak Pribadi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyediaan informasi keuangan publik dibatasi oleh <strong>perlindungan atas hak asasi pribadi, golongan, dan rahasia negara</strong> guna melindungi privasi serta keamanan kedaulatan nasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena transparansi anggaran tidak boleh melanggar hak privasi personal warga atau membocorkan data rahasia yang dapat dieksploitasi pihak tidak berwenang. Maka dari itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Mencegah penyalahgunaan data rahasia individu dari eksploitasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pelanggaran anggaran dinas tidak berhak mendapat proteksi kerahasiaan.</li><li><strong>B</strong>: Aparat penegak hukum memiliki kewenangan hukum memeriksa dokumen belanja.</li><li><strong>C</strong>: Aparat penegak hukum memiliki kewenangan hukum memeriksa dokumen belanja.</li><li><strong>D</strong>: Pejabat publik tetap diwajibkan melaporkan harta kekayaan (LHKPN).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pribadi, • golongan,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pemeriksaan keuangan negara secara eksternal diserahkan kepada BPK RI untuk menjamin bahwa proses audit...", "options": ["Berjalan secara independen tanpa benturan kepentingan eksekutif", "Dikelola langsung di bawah pengawasan kementerian teknis", "Mengutamakan aspek pengampunan denda bagi keterlambatan proyek", "Dapat dibatalkan sewaktu-waktu oleh instruksi pejabat perbendaharaan", "Menyerahkan opini pemeriksaan kepada badan usaha swasta rekanan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Landasan Kelembagaan Pemeriksa Eksternal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Audit eksternal dilaksanakan oleh <strong>badan pemeriksa yang bebas dan mandiri, yakni BPK RI</strong>, demi menjamin objektivitas tanpa konflik kepentingan dengan pengelola anggaran.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penyerahan audit kepada BPK menjamin tidak adanya campur tangan pemerintah selaku pihak terperiksa (auditee). Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Berjalan secara independen tanpa benturan kepentingan eksekutif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Audit eksternal tidak boleh berada di bawah kendali kementerian teknis.</li><li><strong>C</strong>: Audit bertujuan menilai kewajaran dan kepatuhan, bukan mengampuni denda.</li><li><strong>D</strong>: Pejabat perbendaharaan tidak berwenang membatalkan pemeriksaan BPK.</li><li><strong>E</strong>: Opini pemeriksaan diserahkan resmi kepada DPR, DPD, dan DPRD.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dalam praktiknya dilakukan oleh Badan Pemeriksa Keuangan (BPK RI) • badan pemeriksa yang bebas dan mandiri</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pengecualian asas keterbukaan diberlakukan terhadap informasi tertentu yang diklasifikasikan sebagai...", "options": ["Rincian harga pembelian perabot mebel pada kantor dinas", "Rahasia negara demi menjaga keamanan dan kedaulatan bangsa", "Alokasi biaya perjalanan dinas para staf perbendaharaan", "Daftar penerima beasiswa pendidikan bagi putra pegawai", "Jadwal lokakarya pelatihan kepemimpinan aparatur daerah"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengecualian Rahasia Negara dalam Tata Kelola</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Asas Keterbukaan membatasi penyebaran informasi yang menyangkut <strong>rahasia negara</strong> demi keselamatan pertahanan, keamanan, dan kedaulatan negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena informasi yang berstatus rahasia negara dilindungi undang-undang dari keterbukaan bebas demi stabilitas pertahanan nasional. Oleh sebab itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Rahasia negara demi menjaga keamanan dan kedaulatan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Harga mebel kantor merupakan informasi belanja barang terbuka umum.</li><li><strong>C</strong>: Biaya perjalanan dinas tercatat dalam dokumen pertanggungjawaban terbuka.</li><li><strong>D</strong>: Daftar penerima beasiswa merupakan program kedinasan transparan.</li><li><strong>E</strong>: Jadwal lokakarya aparatur merupakan agenda kegiatan dinas reguler.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>rahasia • negara.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pemeriksaan keuangan yang dijalankan oleh badan pemeriksa independen berfokus pada dua pilar utama, yaitu...", "options": ["Perolehan keuntungan dan margin laba bersih bursa efek", "Tingkat persaingan tarif ekspedisi pengiriman swasta", "Pengelolaan dan pertanggungjawaban keuangan negara", "Desain arsitektur gedung perkantoran badan usaha komersial", "Jumlah kepemilikan polis asuransi jiwa masyarakat sipil"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Audit BPK atas Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemeriksaan keuangan oleh badan pemeriksa bebas dan mandiri diarahkan untuk memeriksa <strong>pengelolaan dan tanggung jawab tentang keuangan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena mandat audit BPK meliputi evaluasi tata kelola pelaksanaan anggaran serta laporan pertanggungjawaban atas kas negara. Maka dari itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Pengelolaan dan pertanggungjawaban keuangan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Margin laba bursa efek merupakan ranah pasar modal dan otoritas bursa.</li><li><strong>B</strong>: Tarif ekspedisi swasta adalah urusan persaingan usaha logistik komersial.</li><li><strong>D</strong>: Desain gedung komersial bukan objek pemeriksaan keuangan negara.</li><li><strong>E</strong>: Polis asuransi jiwa masyarakat merupakan aset keuangan perorangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemeriksaan keuangan oleh badan pemeriksa • pengelolaan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Keterpaduan antara Asas Keterbukaan dan asas pemeriksaan independen ditujukan untuk mewujudkan sistem keuangan negara yang...", "options": ["Dikelola secara tertutup guna mencegah masukan koreksi publik", "Dikelola secara tertutup guna mencegah masukan koreksi publik", "Bebas mengalihkan dana APBN untuk modal usaha perseorangan", "Transparan, akuntabel, dan bebas dari praktik penyimpangan", "Tidak terikat pada ketentuan standar perundang-undangan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Asas Keterbukaan dan Audit Eksternal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan seluruh asas pengelolaan keuangan negara secara terpadu bertujuan menjamin <strong>pengelolaan keuangan negara yang transparan, akuntabel, dan bertanggung jawab</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena keterbukaan publik yang diimbangi audit profesional independen merupakan formula pencegahan penyimpangan anggaran paling efektif. Oleh karena itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Transparan, akuntabel, dan bebas dari praktik penyimpangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengelolaan tertutup bertentangan langsung dengan asas keterbukaan.</li><li><strong>B</strong>: Pengelolaan tertutup bertentangan langsung dengan asas keterbukaan.</li><li><strong>C</strong>: Pengalihan dana APBN untuk usaha perorangan merupakan tindak pidana korupsi.</li><li><strong>E</strong>: Keuangan negara wajib tunduk pada kepatuhan peraturan perundang-undangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>keuangan • negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dua asas umum pelengkap yang ditegaskan pada bagian akhir penataan keuangan negara di Undang-Undang Keuangan Negara adalah...", "options": ["Asas Monopoli Perdagangan dan asas proteksionisme komoditas", "Asas Fleksibilitas Kasir dan asas pergeseran pagu bebas prosedur", "Asas Sentralisasi Perbankan dan asas pemungutan iuran wajib", "Asas Pembebasan Pajak dan asas pinjaman komersial tanpa batas", "Asas Keterbukaan dan asas pemeriksaan oleh badan bebas dan mandiri"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sintesis Asas Keterbukaan dan Pemeriksaan Mandiri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dua asas penutup dalam tata kelola keuangan negara adalah <strong>Asas Keterbukaan (angka 8) dan Pemeriksaan keuangan oleh badan pemeriksa yang bebas dan mandiri (angka 9)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kedua asas ini merupakan pilar pengawasan eksternal dan keterbukaan informasi yang melengkapi asas-asas operasional perbendaharaan sebelumnya. Oleh sebab itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Asas Keterbukaan dan asas pemeriksaan oleh badan bebas dan mandiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Monopoli perdagangan bukan asas umum tata kelola keuangan negara.</li><li><strong>B</strong>: Pergeseran pagu tanpa prosedur melanggar asas spesialitas anggaran.</li><li><strong>C</strong>: Sentralisasi perbankan bukan bagian asas pengelolaan fiskal negara.</li><li><strong>D</strong>: Pinjaman komersial tanpa batas bertentangan dengan batas rasio defisit APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemeriksaan keuangan oleh badan pemeriksa yang bebas dan mandiri • Keterbukaan,</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_pkn_p01_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_pkn_p01_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_pkn_p01_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Pengelolaan Keuangan Negara Paket 01";
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
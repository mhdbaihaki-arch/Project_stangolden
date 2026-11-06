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
      Latihan Pengelolaan Keuangan Negara
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
<h1>Latihan Pengelolaan Keuangan Negara <span class="series-tag">Paket 01 • Hlm. 07–16</span></h1>
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
const questions=[{"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pengelolaan keuangan negara pada hakikatnya didefinisikan sebagai pengelolaan yang dilakukan pemerintah terhadap...", "options": ["Pendapatan negara, belanja negara, dan pembiayaan defisit", "Kebijakan moneter, cadangan devisa, dan perbankan nasional", "Penanaman modal daerah, obligasi daerah, dan bagi hasil", "Neraca pembayaran, kurs valuta asing, dan peredaran uang", "Penjaminan simpanan perbankan dan resolusi likuiditas"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Pengelolaan Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pengelolaan Keuangan Negara</strong> adalah pengelolaan keuangan yang dilakukan oleh Pemerintah terhadap sumber-sumber keuangan berupa <strong>pendapatan negara, belanja negara, dan sumber keuangan untuk membiayai kekurangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Secara yuridis dan konseptual, pengelolaan keuangan negara mencakup tiga pilar utama anggaran pemerintah, yaitu pengelolaan pendapatan negara, pelaksanaan belanja negara, dan pengelolaan sumber pembiayaan untuk menutup defisit anggaran. Ketiga komponen tersebut merupakan instrumen pokok dalam penyelenggaraan keuangan negara. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Pendapatan negara, belanja negara, dan pembiayaan defisit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan lingkup kebijakan moneter yang menjadi kewenangan Bank Indonesia.</li><li><strong>C</strong>: Merupakan instrumen pengelolaan keuangan daerah dalam kerangka desentralisasi fiskal.</li><li><strong>D</strong>: Merupakan ranah neraca makroekonomi eksternal dan sistem pembayaran perbankan.</li><li><strong>E</strong>: Merupakan mandat kelembagaan penjaminan simpanan perbankan nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengelolaan Keuangan Negara adalah • pendapatan negara, terhadap belanja negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan ketentuan perundang-undangan, pendapatan negara yang dikelola pemerintah bersumber dari...", "options": ["Penerimaan dividen BUMN dan laba operasi bank sentral", "Penerimaan perpajakan dan penerimaan negara bukan pajak", "Pinjaman program bilateral dan penerbitan obligasi ritel", "Penarikan hibah luar negeri dan dana bagi hasil daerah", "Pemanfaatan saldo anggaran lebih dan dana abadi nasional"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sumber Penerimaan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pendapatan negara</strong> bersumber dari berbagai instrumen resmi pemerintah, yakni <strong>dari pajak dan bukan pajak</strong> yang sah menurut peraturan perundang-undangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Berdasarkan arsitektur penerimaan negara, dua komponen utama penerimaan negara adalah penerimaan perpajakan dan penerimaan negara bukan pajak (PNBP). Kedua sumber penerimaan ini memiliki dasar hukum kuat dan menjadi wewenang penuh pemerintah untuk membiayai belanja negara. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Penerimaan perpajakan dan penerimaan negara bukan pajak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan komponen spesifik bagian kekayaan negara dipisahkan dan bukan kategori induk.</li><li><strong>C</strong>: Merupakan instrumen pembiayaan utang untuk menutup defisit anggaran belanja.</li><li><strong>D</strong>: Merupakan gabungan pos penerimaan hibah dan mekanisme transfer fiskal daerah.</li><li><strong>E</strong>: Merupakan instrumen pengelolaan sisa kas dan investasi dana cadangan pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pendapatan negara bisa berasal • dari pajak dan bukan pajak</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan belanja negara oleh pemerintah pada hakikatnya diselenggarakan dalam rangka...", "options": ["Menstabilkan nilai tukar rupiah di pasar valuta asing", "Mengendalikan rasio kecukupan likuiditas perbankan", "Melaksanakan fungsi menyejahterakan masyarakat", "Memperbesar cadangan devisa internasional pemerintah", "Mengarahkan portofolio investasi pasar modal nasional"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Belanja Pemerintah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Belanja Pemerintah</strong> pada hakikatnya dilakukan dalam rangka <strong>melaksanakan fungsinya mensejahterakan masyarakat</strong> melalui penyediaan barang publik dan pelayanan sosial.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Tujuan hakiki dari seluruh alokasi belanja negara adalah melayani kebutuhan publik dan mewujudkan kemakmuran rakyat. Anggaran belanja dialokasikan untuk pembangunan infrastruktur, pendidikan, kesehatan, dan perlindungan sosial guna menjamin peningkatan taraf hidup masyarakat. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Melaksanakan fungsi menyejahterakan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan sasaran pokok pelaksanaan kebijakan moneter oleh bank sentral.</li><li><strong>B</strong>: Merupakan wewenang pengaturan dan pengawasan industri perbankan nasional.</li><li><strong>D</strong>: Merupakan fungsi pengelolaan cadangan aset luar negeri oleh otoritas moneter.</li><li><strong>E</strong>: Merupakan domain kebijakan pengaturan industri keuangan dan pasar modal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Belanja Pemerintah pada hakikatnya • mensejahterakan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Untuk menutup kekurangan kas atau membiayai belanja pembangunan, sumber keuangan pemerintah dapat berasal dari...", "options": ["Pengalihan cadangan kas operasional bank sentral", "Penarikan dana cadangan penjaminan simpanan publik", "Pemanfaatan kuota penerimaan bea masuk komoditas", "Pinjaman utang atau sumber pembiayaan yang sah", "Penjualan surat berharga komersial milik perbankan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sumber Pembiayaan Defisit Pembangunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sumber-sumber keuangan untuk <strong>pembiayaan pembangunan dapat berasal dari utang atau sumber lainnya</strong> sesuai dengan koridor peraturan perundang-undangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Ketika pendapatan negara tidak mencukupi untuk mendanai seluruh kebutuhan belanja pembangunan, undang-undang memperbolehkan pemerintah melakukan pembiayaan anggaran. Instrumen pembiayaan yang sah mencakup penarikan pinjaman, penerbitan surat berharga negara, maupun pemanfaatan saldo anggaran lebih. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Pinjaman utang atau sumber pembiayaan yang sah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cadangan kas bank sentral diperuntukkan bagi stabilitas moneter dan bukan belanja kas APBN.</li><li><strong>B</strong>: Dana penjaminan simpanan dikelola khusus oleh LPS untuk resolusi perbankan bermasalah.</li><li><strong>C</strong>: Penerimaan bea masuk adalah pos pendapatan negara rutin dan bukan pos pembiayaan.</li><li><strong>E</strong>: Surat berharga komersial perbankan merupakan aset privat milik perbankan umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pembiayaan pembangunan dapat berasal • utang atau sumber lainnya</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam tata kelola keuangan negara, Kementerian Keuangan memegang posisi strategis sebagai...", "options": ["Chief Operational Officer di bidang teknis sektoral", "Lembaga pengawas internal independen pemerintah", "Badan pengawas stabilitas perbankan dan moneter", "Otoritas tunggal perencana pembangunan jangka panjang", "Chief Financial Officer bagi pemerintah negara"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Strategis Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian Keuangan mempunyai posisi strategis dalam pengelolaan keuangan negara, yakni sebagai <strong>Chief Financial Officer (CFO)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Menteri Keuangan bertindak sebagai pengelola fiskal nasional dan Bendahara Umum Negara yang mengemban mandat CFO. Posisi strategis ini berwenang mengelola portofolio aset dan kewajiban negara secara komprehensif guna menjaga kesinambungan fiskal nasional. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Chief Financial Officer bagi pemerintah negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Chief Operational Officer (COO) diemban oleh menteri teknis kementerian dan lembaga.</li><li><strong>B</strong>: Pengawasan intern independen dilakukan oleh Badan Pengawasan Keuangan dan Pembangunan.</li><li><strong>C</strong>: Pengawasan perbankan dan stabilitas moneter dijalankan oleh Bank Indonesia dan OJK.</li><li><strong>D</strong>: Perencanaan pembangunan nasional merupakan mandat pokok Kementerian PPN/Bappenas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>posisi strategis dalam pengelolaan keuangan • Chief Financial Officer</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Wewenang pengelolaan keuangan oleh pemerintah dibatasi oleh peraturan perundang-undangan dalam bentuk...", "options": ["Persetujuan dan pengawasan pihak legislatif", "Penetapan pagu indikatif bersama bank sentral", "Evaluasi kinerja makro oleh lembaga koordinasi", "Pembahasan rencana kerja bersama kementerian", "Pemeriksaan administratif internal inspektorat"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Pembatasan Kekuasaan Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wewenang dan fungsi pemerintah dibatasi oleh peraturan perundang-undangan, antara lain melalui <strong>persetujuan dan pengawasan yang dilakukan oleh pihak legislatif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Prinsip pemisahan kekuasaan menuntut adanya mekanisme saling uji agar wewenang eksekutif dalam mengelola anggaran tidak bersifat mutlak. Pembatasan kekuasaan diwujudkan melalui hak legislatif dalam memberikan persetujuan undang-undang APBN serta melakukan pengawasan berkala atas pelaksanaannya. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Persetujuan dan pengawasan pihak legislatif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pagu indikatif disusun pemerintah bersama Bappenas dan bukan wewenang bank sentral.</li><li><strong>C</strong>: Evaluasi makro merupakan koordinasi internal pemerintah dan bukan pembatasan yuridis.</li><li><strong>D</strong>: Pembahasan rencana kerja adalah tahapan internal penyusunan usulan anggaran eksekutif.</li><li><strong>E</strong>: Pemeriksaan inspektorat adalah fungsi pengawasan intern aparatur dalam ranah eksekutif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>wewenang dan fungsi Pemerintah dibatasi • persetujuan dan pengawasan yang dilakukan oleh pihak legislatif</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pertanggungjawaban keuangan negara yang dikelola pemerintah wajib memenuhi dua aspek fundamental yaitu...", "options": ["Efisiensi internal dan kesinambungan fiskal", "Transparansi dan akuntabilitas publik", "Kepatuhan operasional dan tertib anggaran", "Kepastian hukum dan konsistensi perencanaan", "Keterpaduan sistem dan profesionalitas aparatur"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Pertanggungjawaban Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pertanggungjawaban keuangan yang dikelola oleh Pemerintah diharapkan sesuai dengan koridor peraturan, selain memenuhi aspek <strong>transparansi dan akuntabilitas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Tata kelola keuangan negara yang baik menuntut keterbukaan informasi (transparansi) agar publik dapat memantau penggunaan anggaran, serta pertanggungjawaban yang jelas (akuntabilitas) atas setiap rupiah belanja yang dialokasikan. Kedua aspek ini merupakan pilar utama legitimasi pengelolaan anggaran publik. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Transparansi dan akuntabilitas publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan sasaran kebijakan alokatif fiskal dan bukan pilar pertanggungjawaban yuridis.</li><li><strong>C</strong>: Merupakan indikator teknis kepatuhan perbendaharaan dalam pelaksanaan anggaran belanja.</li><li><strong>D</strong>: Merupakan asas kepatuhan hukum umum dalam penyelenggaraan tata kelola birokrasi.</li><li><strong>E</strong>: Merupakan standar kualitas aparatur pengelola perbendaharaan dan infrastruktur sistem.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pertanggungjawaban keuangan yang dikelola oleh Pemerintah • transparansi dan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Saat terjadi defisit anggaran belanja untuk program prioritas pemerintah, langkah pembiayaan yang sah menurut perundang-undangan adalah...", "options": ["Menarik pinjaman program langsung tanpa pagu APBN", "Mengalihkan cadangan likuiditas penjaminan simpanan", "Menggunakan sumber pembiayaan utang yang disetujui DPR", "Menerbitkan surat perbendaharaan jangka pendek BI", "Menggeser alokasi penerimaan royalti migas daerah"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Pembiayaan Defisit APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekurangan kas atau defisit anggaran ditutup melalui <strong>sumber-sumber keuangan untuk pembiayaan pembangunan seperti penarikan utang atas persetujuan DPR</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Dalam situasi di mana realisasi belanja melebihi realisasi pendapatan, pemerintah diizinkan menutup defisit anggaran melalui pos pembiayaan yang sah. Penarikan utang baik berupa pinjaman maupun penerbitan surat berharga negara wajib tercantum dalam undang-undang APBN dan memperoleh persetujuan DPR. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Menggunakan sumber pembiayaan utang yang disetujui DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Setiap penarikan pembiayaan utang wajib memiliki dasar plafon resmi dalam APBN.</li><li><strong>B</strong>: Cadangan likuiditas penjaminan simpanan terikat secara khusus untuk mandat LPS.</li><li><strong>D</strong>: Surat perbendaharaan negara diterbitkan pemerintah, bukan instrumen langsung BI.</li><li><strong>E</strong>: Royalti migas daerah merupakan hak dana bagi hasil daerah yang tidak dapat digeser sepihak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>membiayai kekurangan • pembiayaan pembangunan dapat berasal dari utang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Dalam penyusunan proyeksi penerimaan dan pengeluaran negara, instansi yang memegang komando strategis selaku bendahara umum adalah...", "options": ["Badan Pengawasan Keuangan dan Pembangunan pusat", "Otoritas Jasa Keuangan selaku pengawas perbankan", "Kementerian Koordinator Bidang Perekonomian negara", "Kementerian Keuangan selaku bendahara umum negara", "Badan Perencanaan Pembangunan Nasional terpadu"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Peran Kemenkeu sebagai Pengelola Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian Keuangan mempunyai posisi strategis dalam pengelolaan keuangan negara selaku <strong>Chief Financial Officer (CFO) dan Bendahara Umum Negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Undang-undang menugaskan Menteri Keuangan sebagai pengelola fiskal nasional dan Bendahara Umum Negara (BUN). Kementerian Keuangan berwenang merumuskan proyeksi pendapatan, menetapkan kerangka belanja, serta mengatur aliran likuiditas rekening kas umum negara. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Kementerian Keuangan selaku bendahara umum negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: BPKP menjalankan fungsi audit dan konsultasi intern aparatur pengawasan pemerintah.</li><li><strong>B</strong>: OJK bertugas mengatur dan mengawasi industri jasa keuangan dan perbankan komersial.</li><li><strong>C</strong>: Kemenko Perekonomian mengoordinasikan sinkronisasi kebijakan ekonomi lintas kementerian.</li><li><strong>E</strong>: Bappenas merumuskan rencana pembangunan jangka menengah dan prioritas proyek nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kementerian Keuangan mempunyai posisi strategis • Chief Financial Officer</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Keterkaitan esensial antara wewenang pemerintah mengelola pendapatan dan belanja dengan pengawasan legislatif adalah...", "options": ["Memisahkan fungsi perencanaan dengan penetapan pagu indikatif", "Menyatukan fungsi eksekutif dan legislatif dalam pengelolaan kas", "Mengalihkan wewenang pembukuan perbendaharaan ke komisi anggaran", "Membatasi fleksibilitas eksekusi program di seluruh kementerian", "Menjamin penggunaan anggaran sesuai mandat kesejahteraan rakyat"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Hubungan Pendapatan, Belanja, dan Pengawasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengawasan legislatif dan pembatasan perundang-undangan bertujuan <strong>menjaga agar pengelolaan anggaran negara benar-benar diarahkan untuk mensejahterakan masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Hubungan fungsional antara kewenangan eksekutif mengelola anggaran dan pengawasan parlemen dibangun untuk menegakkan kedaulatan rakyat. Pengawasan DPR memastikan bahwa penerimaan yang dipungut dari masyarakat dialokasikan secara transparan, akuntabel, dan tepat sasaran demi kemakmuran umum. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Menjamin penggunaan anggaran sesuai mandat kesejahteraan rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemisahan tahapan perencanaan adalah bagian dari siklus penyusunan anggaran teknis.</li><li><strong>B</strong>: Fungsi eksekutif dan legislatif wajib dipisahkan secara tegas dalam doktrin tata negara.</li><li><strong>C</strong>: Wewenang pembukuan perbendaharaan tetap berada pada ranah eksekutif selaku pengelola kas.</li><li><strong>D</strong>: Fleksibilitas teknis tetap diberikan kepada kementerian teknis untuk mencapai target output.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 07) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mensejahterakan • persetujuan dan pengawasan yang dilakukan oleh pihak legislatif</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Undang-Undang Nomor 17 Tahun 2003, keuangan negara didefinisikan secara yuridis sebagai...", "options": ["Semua hak dan kewajiban negara yang dapat dinilai dengan uang", "Seluruh penerimaan perpajakan yang dihimpun bendahara negara", "Total aset properti dan kekayaan milik seluruh badan publik", "Keseluruhan instrumen pasar modal dan modal investasi publik", "Arus peredaran uang kartal dan giral dalam sistem perbankan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Keuangan Negara UU 17/2003</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut Undang-Undang Nomor 17 Tahun 2003, <strong>Keuangan Negara</strong> adalah <strong>semua hak dan kewajiban negara yang dapat dinilai dengan uang</strong>, serta segala sesuatu baik berupa uang maupun barang yang dapat dijadikan milik negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Definisi resmi Pasal 1 angka 1 UU 17/2003 menetapkan hakikat keuangan negara dari dimensi hak dan kewajiban yang berwujud atau dapat dinilai dengan satuan uang. Rumusan ini mencakup seluruh kekayaan yang diperoleh berhubung dengan pelaksanaan hak dan kewajiban tersebut. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Semua hak dan kewajiban negara yang dapat dinilai dengan uang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penerimaan perpajakan adalah salah satu hak penerimaan dan bukan definisi menyeluruh.</li><li><strong>C</strong>: Aset properti merupakan wujud barang milik negara yang menyertai hak dan kewajiban.</li><li><strong>D</strong>: Instrumen pasar modal adalah sarana investasi pasar keuangan dan bukan cakupan penuh.</li><li><strong>E</strong>: Perputaran uang kartal dan giral merupakan indikator moneter dalam sistem perbankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>semua hak dan kewajiban negara yang dapat dinilai dengan uang • dapat dijadikan milik negara berhubung dengan pelaksanaan hak dan kewajiban</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan pendekatan objek, ruang lingkup keuangan negara meliputi hak dan kewajiban bernilai uang termasuk...", "options": ["Seluruh wajib pajak perorangan dan badan hukum usaha", "Bidang fiskal, moneter, dan kekayaan negara dipisahkan", "Rencana kerja operasional aparatur kementerian negara", "Struktur hierarki kelembagaan kementerian dan lembaga", "Risalah sidang paripurna pembahasan anggaran legislatif"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pendekatan Objek Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Objek dari keuangan negara</strong> mencakup semua hak dan kewajiban negara yang dapat dinilai dengan uang, termasuk <strong>kebijakan dan kegiatan dalam bidang fiskal dan moneter, dan pengelolaan kekayaan negara yang dipisahkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Pendekatan objek menitikberatkan pada substansi materiil keuangan negara yang mencakup kebijakan dan aktivitas dalam tiga subbidang utama: subbidang fiskal, subbidang moneter, serta subbidang pengelolaan kekayaan negara yang dipisahkan. Ketiganya merupakan perluasan cakupan objek keuangan negara. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Bidang fiskal, moneter, dan kekayaan negara dipisahkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Wajib pajak adalah subjek hukum yang dikenai kewajiban perpajakan.</li><li><strong>C</strong>: Rencana kerja operasional adalah dokumen perencanaan kegiatan kementerian.</li><li><strong>D</strong>: Struktur kelembagaan merupakan aspek organisasi dan tata laksana birokrasi pemerintahan.</li><li><strong>E</strong>: Risalah sidang adalah catatan dokumentasi proses deliberasi politik anggaran legislatif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Objek dari keuangan negara • kebijakan dan kegiatan dalam bidang fiskal dan moneter, dan pengelolaan kekayaan negara yang dipisahkan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Keuangan negara ditinjau dari pendekatan subjek didefinisikan sebagai seluruh objek keuangan negara yang...", "options": ["Dikelola bersama lembaga nonpemerintah di daerah", "Didaftarkan sebagai portofolio pasar modal nasional", "Dimiliki dan/atau dikuasai oleh pemerintah dan badan publik", "Dikelola mandiri oleh organisasi profesi perbendaharaan", "Dialihkan pengelolaannya kepada pihak ketiga komersial"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pendekatan Subjek Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Subjek keuangan negara</strong> adalah seluruh objek keuangan negara yang <strong>dimiliki dan/atau dikuasai oleh Pemerintah dan badan hukum publik lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Ditinjau dari pendekatan subjek, keuangan negara melekat pada entitas penguasa atau pemiliknya, yaitu pemerintah pusat, pemerintah daerah, serta badan hukum publik lainnya yang mengemban mandat pengelolaan uang dan barang publik. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Dimiliki dan/atau dikuasai oleh pemerintah dan badan publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Lembaga nonpemerintah bukan merupakan subjek badan hukum publik pengelola kas negara.</li><li><strong>B</strong>: Portofolio pasar modal adalah instrumen investasi dan bukan kriteria subjek hukum negara.</li><li><strong>D</strong>: Organisasi profesi merupakan entitas perkumpulan dan bukan pengelola sah keuangan negara.</li><li><strong>E</strong>: Pengalihan pada pihak ketiga komersial tidak mengubah hakikat kepemilikan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Subjek keuangan negara • dimiliki dan/atau dikuasai oleh Pemerintah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan pendekatan proses, keuangan negara merupakan seluruh rangkaian kegiatan pengelolaan yang dimulai dari...", "options": ["Penetapan rencana strategis hingga pengumuman tender lelang", "Pelantikan pengelola keuangan hingga masa audit inspektorat", "Penerbitan surat tagihan perbendaharaan hingga tutup buku", "Perumusan kebijakan hingga tahap pertanggungjawaban", "Pengesahan alokasi pagu hingga penyaluran dana transfer"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pendekatan Proses Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut prosesnya, keuangan negara merupakan <strong>seluruh rangkaian kegiatan pengelolaan</strong> hak dan kewajiban bernilai uang yang dimulai <strong>dari perumusan kebijakan dan pengambilan keputusan sampai dengan pertanggungjawaban</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Pendekatan proses memandang keuangan negara sebagai satu siklus penganggaran utuh yang berkesinambungan. Siklus ini diawali dari tahapan formulasi kebijakan dan pengambilan keputusan politik anggaran, berlanjut ke pelaksanaan dan penatausahaan, hingga berakhir pada pertanggungjawaban di hadapan legislatif. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Perumusan kebijakan hingga tahap pertanggungjawaban</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengumuman lelang merupakan tahapan teknis pengadaan barang dan bukan siklus akhir.</li><li><strong>B</strong>: Pelantikan pejabat adalah peristiwa kepegawaian dan bukan rangkaian siklus anggaran.</li><li><strong>C</strong>: Tutup buku adalah prosedur akuntansi periodik dan bukan batas akhir pertanggungjawaban.</li><li><strong>E</strong>: Penyaluran dana transfer adalah salah satu mekanisme eksekusi belanja pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menurut prosesnya • dimulai dari perumusan kebijakan dan pengambilan keputusan sampai dengan pertanggungjawaban</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Ditinjau dari pendekatan tujuan, seluruh kebijakan dan pengelolaan objek keuangan negara dimaksudkan dalam rangka...", "options": ["Perluasan portofolio investasi pasar modal daerah", "Pembentukan cadangan likuiditas sektor perbankan", "Pengendalian arus perdagangan barang komoditas ekspor", "Akumulasi surplus kas pada instansi teknis kementerian", "Penyelenggaraan pemerintahan negara"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pendekatan Tujuan Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Tujuan</strong> seluruh kebijakan, kegiatan, dan hubungan hukum yang berkaitan dengan pemilikan dan/atau penguasaan objek keuangan negara adalah <strong>dalam rangka penyelenggaraan Pemerintahan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Pendekatan tujuan menegaskan bahwa pengelolaan keuangan negara bukan semata-mata aktivitas administratif mencari penerimaan, melainkan sarana instrumental untuk membiayai roda pemerintahan negara dalam rangka melindungi dan menyejahterakan seluruh rakyat. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Penyelenggaraan pemerintahan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Investasi pasar modal adalah instrumen penempatan dana dan bukan tujuan hakiki negara.</li><li><strong>B</strong>: Cadangan likuiditas perbankan merupakan tujuan kebijakan moneter pengawasan bank.</li><li><strong>C</strong>: Pengendalian arus ekspor adalah sasaran kebijakan perdagangan luar negeri pemerintah.</li><li><strong>D</strong>: Pemerintah tidak bertujuan menumpuk kas komersial pada instansi teknis birokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tujuan seluruh kebijakan • dalam rangka penyelenggaraan Pemerintahan negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam penjelasan Undang-Undang Nomor 17 Tahun 2003, pengertian keuangan negara dirumuskan melalui empat pendekatan yaitu...", "options": ["Objek, subjek, proses, dan tujuan", "Pendapatan, belanja, pembiayaan, dan defisit", "Fiskal, moneter, perbankan, dan pasar modal", "Pusat, daerah, desa, dan badan usaha", "Perencanaan, pelaksanaan, penatausahaan, dan audit"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Empat Pendekatan Pengertian Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengertian keuangan negara menurut penjelasan peraturan perundang-undangan dirumuskan melalui empat pendekatan: <strong>pendekatan objek, subjek, proses, dan tujuan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Penjelasan umum Undang-Undang Nomor 17 Tahun 2003 menguraikan konsep keuangan negara secara komprehensif melalui empat sudut pandang yuridis: objek (substansi), subjek (penguasa), proses (siklus tata kelola), dan tujuan (penyelenggaraan pemerintahan). Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Objek, subjek, proses, dan tujuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan struktur postur APBN dalam penyusunan anggaran pendapatan dan belanja.</li><li><strong>C</strong>: Merupakan pembagian sektor ekonomi makro dan sistem keuangan nasional.</li><li><strong>D</strong>: Merupakan tingkatan entitas pemerintahan dalam sistem otonomi dan korporasi publik.</li><li><strong>E</strong>: Merupakan tahapan siklus administratif perbendaharaan dan pengawasan anggaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Objek dari keuangan negara • Subjek keuangan negara • Menurut prosesnya</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Termasuk dalam ruang lingkup objek keuangan negara selain uang dan barang milik negara adalah...", "options": ["Seluruh simpanan valuta asing pada perbankan umum", "Kebijakan fiskal, moneter, dan kekayaan dipisahkan", "Portofolio obligasi korporasi yang tercatat di bursa", "Seluruh cadangan modal milik lembaga perasuransian", "Dana abadi nirlaba yang dikelola yayasan masyarakat"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ruang Lingkup Objek Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ruang lingkup <strong>objek keuangan negara</strong> mencakup hak dan kewajiban bernilai uang, uang dan barang milik negara, serta <strong>kebijakan dan kegiatan dalam bidang fiskal, moneter, dan pengelolaan kekayaan negara yang dipisahkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Pendekatan objek memperluas cakupan keuangan negara tidak hanya sebatas fisik kas dan aset berwujud, melainkan meliputi seluruh kebijakan dan aktivitas pemerintah dalam ranah fiskal, moneter, serta pengelolaan kekayaan negara pada BUMN/BUMD. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Kebijakan fiskal, moneter, dan kekayaan dipisahkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Simpanan perbankan komersial merupakan aset nasabah privat dan bukan objek kas negara.</li><li><strong>C</strong>: Obligasi korporasi swasta merupakan instrumen utang komersial sektor privat.</li><li><strong>D</strong>: Cadangan modal asuransi komersial merupakan kekayaan entitas bisnis swasta.</li><li><strong>E</strong>: Dana yayasan masyarakat merupakan kekayaan badan hukum privat nirpemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Objek dari keuangan negara • bidang fiskal dan moneter, dan pengelolaan kekayaan negara yang dipisahkan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Pemerintah daerah mengelola dana transfer dan aset gedung dinas otonom. Ditinjau dari pendekatan subjek, status dana dan aset tersebut adalah...", "options": ["Dana perimbangan khusus milik entitas nonpemerintah", "Modal penyertaan daerah yang dikelola dinas teknis", "Keuangan negara yang dikuasai pemerintah daerah", "Dana titipan perbankan yang bersifat non-budgeter", "Portofolio investasi daerah pada pasar modal umum"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Subjek Hukum Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ditinjau dari pendekatan subjek, seluruh objek keuangan negara yang <strong>dimiliki dan/atau dikuasai oleh Pemerintah (termasuk pemerintah daerah)</strong> berkedudukan sebagai <strong>keuangan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Pemerintah daerah merupakan subjek hukum publik yang menjalankan fungsi pemerintahan otonom. Dana transfer APBN dan aset gedung yang dikelola oleh pemerintah daerah secara yuridis berkedudukan sebagai keuangan negara yang penguasaannya didelegasikan kepada kepala daerah. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Keuangan negara yang dikuasai pemerintah daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemerintah daerah adalah entitas badan publik resmi dan bukan badan nonpemerintah.</li><li><strong>B</strong>: Gedung dinas adalah aset operasional barang milik daerah dan bukan modal penyertaan.</li><li><strong>D</strong>: Dana transfer tercatat resmi dalam APBD sehingga bukan merupakan dana non-budgeter.</li><li><strong>E</strong>: Aset operasional dinas bukan merupakan portofolio surat berharga di bursa efek.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Subjek keuangan negara adalah seluruh objek • dimiliki dan/atau dikuasai oleh Pemerintah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Sebuah satuan kerja merumuskan rencana kerja anggaran, mencairkan DIPA, dan menyusun laporan pertanggungjawaban. Rangkaian aktivitas ini mencerminkan...", "options": ["Pengawasan eksternal oleh aparat pemeriksa independen", "Pelaksanaan tugas moneter oleh bank sentral nasional", "Penyelenggaraan peradilan tata usaha perbendaharaan", "Keuangan negara ditinjau dari pendekatan proses", "Kegiatan optimalisasi modal investasi jangka panjang"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Siklus Proses Keuangan Negara pada Satuan Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut prosesnya, keuangan negara adalah <strong>rangkaian kegiatan pengelolaan dari perumusan kebijakan, pelaksanaan anggaran, hingga tahap pertanggungjawaban</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Tahapan penyusunan rencana kerja (perumusan), pencairan DIPA (pelaksanaan), dan penyusunan laporan keuangan (pertanggungjawaban) merupakan manifestasi langsung dari siklus tata kelola keuangan negara ditinjau dari pendekatan proses. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Keuangan negara ditinjau dari pendekatan proses</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengawasan eksternal dijalankan oleh BPK setelah laporan keuangan selesai disusun.</li><li><strong>B</strong>: Aktivitas satker adalah pelaksanaan belanja fiskal dan bukan operasi moneter BI.</li><li><strong>C</strong>: Rangkaian anggaran merupakan proses tata usaha pemerintahan dan bukan peradilan sengketa.</li><li><strong>E</strong>: Pencairan DIPA adalah realisasi belanja operasional dan bukan investasi pasar modal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menurut prosesnya, keuangan negara merupakan seluruh rangkaian • pengambilan keputusan sampai dengan pertanggungjawaban</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Kementerian Pekerjaan Umum membiayai pembangunan jembatan antarpulau dengan APBN. Ditinjau dari pendekatan tujuan, pengeluaran kas ini diarahkan untuk...", "options": ["Memperbesar dividen operasional kementerian terkait", "Mengakumulasi aset komersial milik dinas teknis", "Memperluas instrumen investasi obligasi daerah", "Meningkatkan cadangan kas valuta pada perbankan", "Mendukung penyelenggaraan tugas pemerintahan negara"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Pengadaan Sarana Publik Berorientasi Tujuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan seluruh kebijakan dan penguasaan keuangan negara adalah <strong>dalam rangka penyelenggaraan Pemerintahan negara</strong> guna melayani kepentingan umum.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Pembangunan jembatan antarpulau merupakan pengadaan infrastruktur pelayanan publik yang bertujuan memperlancar konektivitas transportasi dan logistik rakyat. Belanja kas negara ini mencerminkan hakikat pendekatan tujuan dalam mendukung penyelenggaraan fungsi pemerintahan. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Mendukung penyelenggaraan tugas pemerintahan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kementerian negara bukan entitas bisnis komersial pencari pembagian dividen tunai.</li><li><strong>B</strong>: Jembatan publik dibangun untuk utilitas umum dan bukan aset komersial pencari laba.</li><li><strong>C</strong>: Pengeluaran fisik infrastruktur bukan merupakan transaksi pembelian obligasi daerah.</li><li><strong>D</strong>: Belanja modal tidak ditujukan untuk menimbun cadangan valas pada sistem perbankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 09) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tujuan seluruh kebijakan, kegiatan, dan hubungan hukum • penyelenggaraan Pemerintahan negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Perbedaan konseptual mendasar antara pendekatan objek dan pendekatan subjek dalam keuangan negara terletak pada...", "options": ["Objek fokus pada substansi materiil, subjek fokus pada entitas penguasa", "Objek fokus pada siklus penganggaran, subjek fokus pada target pembangunan", "Objek fokus pada batas kewenangan, subjek fokus pada pertanggungjawaban", "Objek fokus pada instrumen moneter, subjek fokus pada stabilitas makro", "Objek fokus pada aspek legalitas formil, subjek fokus pada alokasi kas"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Perbedaan Pendekatan Objek dan Subjek</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pendekatan objek</strong> memandang keuangan negara dari <strong>substansi materiil hak dan kewajiban bernilai uang</strong>, sedangkan <strong>pendekatan subjek</strong> memandang dari <strong>entitas pemilik atau penguasa objek tersebut</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Pendekatan objek mendefinisikan keuangan negara berdasarkan apa yang dikelola (hak, kewajiban, uang, barang, serta kebijakan fiskal/moneter). Sebaliknya, pendekatan subjek mendefinisikannya berdasarkan siapa yang menguasai atau memiliki objek tersebut, yaitu pemerintah dan badan hukum publik. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Objek fokus pada substansi materiil, subjek fokus pada entitas penguasa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Siklus penganggaran dan target pembangunan merupakan ranah pendekatan proses dan tujuan.</li><li><strong>C</strong>: Batas wewenang dan pertanggungjawaban terkait aspek organisasi dan tata laksana hukum.</li><li><strong>D</strong>: Instrumen moneter dan stabilitas makro adalah subbidang kebijakan moneter perbankan.</li><li><strong>E</strong>: Legalitas formil dan alokasi kas merupakan instrumen kepatuhan perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pendekatan objek • hak dan kewajiban negara yang dapat dinilai dengan uang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Bidang pengelolaan keuangan negara berdasarkan perluasan objek dikelompokkan ke dalam tiga subbidang yaitu...", "options": ["Pengelolaan pajak, bea cukai, dan retribusi daerah", "Pengelolaan fiskal, moneter, dan kekayaan dipisahkan", "Pengelolaan kas, piutang, dan pembiayaan defisit", "Pengelolaan ekspor, impor, dan perizinan niaga", "Pengelolaan modal, saham, dan dividen korporasi"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Subbidang Pengelolaan Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bidang pengelolaan keuangan negara dapat dikelompokkan dalam: <strong>subbidang pengelolaan fiskal, subbidang pengelolaan moneter, dan subbidang pengelolaan kekayaan negara yang dipisahkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Perluasan pendekatan objek membagi pengelolaan keuangan negara ke dalam tiga domain utama: fiskal (APBN pemerintah), moneter (jumlah uang beredar oleh Bank Indonesia), dan kekayaan negara yang dipisahkan (BUMN/BUMD). Klasifikasi tripartit ini merupakan pembagian baku menurut undang-undang. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Pengelolaan fiskal, moneter, dan kekayaan dipisahkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak, bea cukai, dan retribusi merupakan komponen pendapatan dalam subbidang fiskal.</li><li><strong>C</strong>: Kas, piutang, dan pembiayaan merupakan fungsi perbendaharaan teknis dalam fiskal.</li><li><strong>D</strong>: Ekspor, impor, dan perizinan niaga adalah instrumen kebijakan perdagangan kementerian.</li><li><strong>E</strong>: Modal, saham, dan dividen merupakan instrumen pasar modal dan korporasi privat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>subbidang pengelolaan fiskal, subbidang pengelolaan moneter • subbidang pengelolaan kekayaan negara yang dipisahkan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Subbidang pengelolaan fiskal didefinisikan sebagai segala kegiatan pemerintah yang mencakup...", "options": ["Pengaturan tingkat suku bunga perbankan nasional", "Pengendalian volume uang kartal yang beredar di pasar", "Penerimaan dan pengeluaran uang yang dilakukan pemerintah", "Penetapan cadangan kas minimum bank komersial umum", "Pengawasan sistem kliring dan lalu lintas giro perbankan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Subbidang Pengelolaan Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Subbidang Pengelolaan Fiskal</strong> mengandung pengertian <strong>segala kegiatan yang mencakup penerimaan dan pengeluaran uang yang dilakukan oleh Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Subbidang fiskal berkaitan langsung dengan kebijakan anggaran pendapatan dan belanja negara. Segala aktivitas pemungutan penerimaan perpajakan/PNBP dan eksekusi pengeluaran belanja untuk layanan publik merupakan inti pokok dari pengelolaan fiskal pemerintah. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Penerimaan dan pengeluaran uang yang dilakukan pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengaturan suku bunga adalah instrumen kebijakan moneter diskonto Bank Indonesia.</li><li><strong>B</strong>: Pengendalian volume uang beredar merupakan tugas utama otoritas moneter nasional.</li><li><strong>D</strong>: Penetapan giro wajib minimum perbankan adalah instrumen moneter bank sentral.</li><li><strong>E</strong>: Pengawasan sistem kliring merupakan mandat BI dalam menjaga kelancaran sistem pembayaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Subbidang Pengelolaan Fiskal mengandung pengertian • penerimaan dan pengeluaran uang yang dilakukan oleh Pemerintah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kebijakan fiskal yang tercermin dalam APBN diarahkan untuk mencapai tiga tujuan utama yaitu...", "options": ["Efisiensi pasar, deregulasi modal, dan privatisasi", "Pengendalian kurs, pengawasan bank, dan kliring kas", "Penyerapan utang, penurunan suku bunga, dan devisa", "Alokasi sumber dana, distribusi, dan stabilisasi ekonomi", "Penjaminan simpanan, pengawasan modal, dan bursa"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Tujuan Kebijakan Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Tujuan kebijakan fiskal</strong> mencakup <strong>alokasi sumber dana keuangan, distribusinya dan stabilisasi ekonomi</strong> guna mempertahankan pertumbuhan dan kestabilan harga.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Trilogi tujuan kebijakan fiskal menuntut APBN berfungsi sebagai instrumen pengalokasian sumber daya untuk barang publik, pendistribusian pendapatan demi keadilan sosial, serta stabilisasi makroekonomi guna menekan pengangguran dan inflasi. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Alokasi sumber dana, distribusi, dan stabilisasi ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Deregulasi dan privatisasi adalah kebijakan restrukturisasi ekonomi pasar bebas.</li><li><strong>B</strong>: Pengendalian kurs dan kliring kas merupakan wewenang kebijakan moneter bank sentral.</li><li><strong>C</strong>: Penurunan suku bunga adalah transmisi kebijakan moneter perbankan komersial.</li><li><strong>E</strong>: Penjaminan simpanan dan pengawasan bursa adalah mandat otoritas industri keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tujuan kebijakan fiskal mencakup alokasi sumber dana • distribusinya dan stabilisasi ekonomi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Subbidang pengelolaan moneter didefinisikan sebagai kebijakan di bidang keuangan yang berkenaan dengan...", "options": ["Perumusan alokasi belanja kementerian dan lembaga", "Penetapan pagu dana bagi hasil serta dana perimbangan", "Pengelolaan barang milik negara dan piutang kementerian", "Pemungutan tarif bea masuk dan cukai komoditas impor", "Jumlah uang beredar, cadangan wajib, diskonto, dan pasar terbuka"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Subbidang Pengelolaan Moneter</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Subbidang Pengelolaan Moneter</strong> adalah kebijakan di bidang keuangan yang berkenaan dengan <strong>jumlah uang beredar, cadangan wajib minimum, fasilitas diskonto, dan operasi pasar terbuka</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Pengelolaan moneter berfokus pada pengendalian besaran moneter dan likuiditas perekonomian. Instrumen utamanya mencakup pengaturan jumlah uang beredar, penetapan giro wajib minimum perbankan, tingkat diskonto, serta jual beli surat berharga dalam operasi pasar terbuka. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Jumlah uang beredar, cadangan wajib, diskonto, dan pasar terbuka</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perumusan belanja kementerian merupakan tugas penyusunan APBN subbidang fiskal.</li><li><strong>B</strong>: Penetapan dana perimbangan adalah kebijakan transfer fiskal ke pemerintah daerah.</li><li><strong>C</strong>: Pengelolaan barang milik negara merupakan domain perbendaharaan dan aset kementerian.</li><li><strong>D</strong>: Pemungutan bea cukai merupakan fungsi penerimaan perpajakan dalam subbidang fiskal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Subbidang Pengelolaan Moneter adalah kebijakan • jumlah uang yang beredar dalam masyarakat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan kebijakan moneter di Indonesia dalam praktiknya secara independen dijalankan oleh...", "options": ["Bank Indonesia selaku bank sentral", "Kementerian Keuangan Republik Indonesia", "Otoritas Jasa Keuangan sektor perbankan", "Lembaga Penjamin Simpanan nasabah", "Himpunan Bank Milik Negara (Himbara)"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelaksana Kebijakan Moneter Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan moneter dalam praktiknya <strong>dilakukan oleh Bank Sentral, yakni Bank Indonesia</strong> yang memiliki independensi kelembagaan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Berdasarkan konstitusi dan undang-undang keuangan negara, kewenangan di bidang moneter dipisahkan secara tegas dari kekuasaan fiskal pemerintah. Bank Indonesia bertindak sebagai bank sentral independen yang berwenang menetapkan dan melaksanakan kebijakan moneter nasional. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Bank Indonesia selaku bank sentral</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kementerian Keuangan merupakan otoritas pengelola kebijakan fiskal pemerintah.</li><li><strong>C</strong>: OJK mengemban mandat pengaturan dan pengawasan kelembagaan industri jasa keuangan.</li><li><strong>D</strong>: LPS bertugas menjamin simpanan nasabah dan melaksanakan resolusi bank gagal.</li><li><strong>E</strong>: Himbara adalah kelompok perbankan komersial pelaksana fungsi intermediasi bisnis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kebijakan moneter ini dalam praktiknya • dilakukan oleh Bank Indonesia</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Salah satu tujuan utama dilaksanakannya kebijakan moneter oleh bank sentral adalah...", "options": ["Menetapkan tarif penerimaan perpajakan nasional", "Mempertahankan kestabilan nilai mata uang rupiah", "Menentukan rincian alokasi belanja kementerian", "Mengelola pos pembiayaan utang luar negeri", "Menetapkan prioritas pembangunan infrastruktur"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Kebijakan Moneter Kestabilan Nilai Rupiah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Tujuan kebijakan moneter</strong> adalah <strong>mempertahankan kestabilan nilai mata uang</strong>, baik terhadap barang dan jasa (inflasi) maupun terhadap mata uang asing.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Mandat utama Bank Indonesia dalam menjalankan kebijakan moneter adalah menjaga dan memelihara kestabilan nilai rupiah. Kestabilan ini tecermin dari laju inflasi yang rendah dan terkendali serta stabilitas nilai tukar terhadap mata uang negara mitra dagang. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Mempertahankan kestabilan nilai mata uang rupiah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penetapan tarif pajak merupakan wewenang pemerintah bersama DPR dalam ranah fiskal.</li><li><strong>C</strong>: Alokasi belanja kementerian ditentukan dalam siklus penyusunan APBN pemerintah.</li><li><strong>D</strong>: Pengelolaan utang negara adalah fungsi perbendaharaan pada Kementerian Keuangan.</li><li><strong>E</strong>: Prioritas pembangunan nasional dirumuskan oleh Bappenas dan kementerian teknis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tujuan kebijaksanaan moneter • nilai rupiah dapat dipertahankan kestabilannya</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penyediaan kredit dengan suku bunga rendah dalam kebijakan moneter diarahkan untuk...", "options": ["Menstabilkan tingkat cadangan devisa luar negeri", "Membatasi volume transaksi pada pasar modal", "Mendorong produsen meningkatkan kegiatan produksi", "Menahan laju peredaran uang kartal masyarakat", "Menyeimbangkan posisi neraca transaksi berjalan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Moneter Suku Bunga Rendah bagi Produsen</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan moneter menyediakan kredit dengan suku bunga rendah yang ditujukan untuk <strong>mendorong produsen meningkatkan kegiatan produksinya</strong> guna membuka lapangan kerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Tingkat suku bunga pinjaman yang terjangkau menurunkan biaya modal bagi sektor usaha riil. Kemudahan akses pembiayaan ini mendorong produsen melakukan ekspansi usaha, menambah kapasitas output produksi, dan menyerap lebih banyak tenaga kerja lokal. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Mendorong produsen meningkatkan kegiatan produksi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cadangan devisa dikelola melalui operasi valas dan transaksi devisa hasil ekspor.</li><li><strong>B</strong>: Pasar modal berkembang melalui likuiditas yang cukup dan bukan dengan pembatasan volume.</li><li><strong>D</strong>: Suku bunga rendah cenderung menambah likuiditas dan bukan menahan uang beredar.</li><li><strong>E</strong>: Neraca transaksi berjalan dipengaruhi oleh kinerja ekspor-impor komoditas riil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menyediakan kredit dengan suku bunga rendah • meningkatkan kegiatan produksi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Pemerintah bersama DPR menetapkan alokasi anggaran perlindungan sosial dan perpajakan tahunan. Instrumen resmi kebijakan ini tercermin dalam...", "options": ["Peraturan dewan gubernur bank sentral tentang suku bunga", "Laporan neraca pembayaran internasional perbankan", "Kerangka stabilitas sistem keuangan komite koordinasi", "Dokumen APBN yang disahkan menjadi undang-undang", "Pedoman penjaminan likuiditas perbankan nasional"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Kebijakan Fiskal Melalui APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kebijakan fiskal tercermin dari APBN yang ditetapkan</strong> bersama oleh pemerintah dan DPR dalam bentuk undang-undang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Seluruh kebijakan fiskal pemerintah, baik yang menyangkut target pendapatan perpajakan maupun alokasi pengeluaran perlindungan sosial, diwujudkan secara legal formal dalam dokumen Anggaran Pendapatan dan Belanja Negara (APBN) yang disahkan menjadi undang-undang. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Dokumen APBN yang disahkan menjadi undang-undang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Peraturan dewan gubernur BI merupakan instrumen regulasi kebijakan moneter bank sentral.</li><li><strong>B</strong>: Neraca pembayaran adalah laporan statistik transaksi ekonomi luar negeri perbankan.</li><li><strong>C</strong>: Kerangka stabilitas sistem keuangan adalah forum sinergi makroprudensial KSSK.</li><li><strong>E</strong>: Pedoman likuiditas perbankan merupakan ketentuan operasional bagi industri perbankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kebijakan fiskal tercermin dari APBN yang ditetapkan • alokasi sumber dana keuangan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Bank Indonesia menjual Sertifikat Bank Indonesia (SBI) untuk menyerap kelebihan likuiditas uang. Tindakan ini merupakan implementasi dari...", "options": ["Kebijakan fiskal penerimaan perpajakan negara", "Pengelolaan kekayaan negara yang dipisahkan", "Penganggaran belanja transfer bantuan sosial", "Penatausahaan dividen bagian laba badan usaha", "Kebijakan moneter operasi pasar terbuka"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Operasi Pasar Terbuka Bank Sentral</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tindakan bank sentral memperjualbelikan surat berharga guna mengendalikan jumlah uang beredar merupakan instrumen <strong>kebijakan moneter melalui operasi pasar terbuka</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Penjualan Sertifikat Bank Indonesia (SBI) oleh bank sentral bertujuan menarik likuiditas kas dari perbankan ke bank sentral guna meredam tekanan inflasi. Operasi ini merupakan instrumen pokok kebijakan moneter pasar terbuka (open market operations). Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Kebijakan moneter operasi pasar terbuka</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penerimaan pajak merupakan pungutan wajib negara yang dikelola instansi fiskal.</li><li><strong>B</strong>: Kekayaan dipisahkan berkaitan dengan permodalan dan laba dividen BUMN/BUMD.</li><li><strong>C</strong>: Bantuan sosial merupakan instrumen belanja redistribusi dalam APBN pemerintah.</li><li><strong>D</strong>: Dividen badan usaha adalah pos penerimaan bukan pajak atas kepemilikan saham negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kebijakan pasar terbuka • jumlah uang yang beredar</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Perbedaan mendasar antara subbidang pengelolaan fiskal dan subbidang pengelolaan moneter terletak pada...", "options": ["Fiskal mengelola APBN pemerintah, moneter mengendalikan uang beredar BI", "Fiskal fokus pada perbankan, moneter fokus pada penyediaan fasilitas publik", "Fiskal mengendalikan suku bunga, moneter menyusun target penerimaan pajak", "Fiskal mengatur sistem kliring kas, moneter mengelola belanja kementerian", "Fiskal mengawasi pasar modal, moneter mengarahkan distribusi transfer daerah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Perbedaan Subbidang Fiskal dan Moneter</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Subbidang fiskal</strong> mengelola anggaran pendapatan dan belanja (APBN) oleh pemerintah, sedangkan <strong>subbidang moneter</strong> mengendalikan besaran moneter dan uang beredar oleh Bank Indonesia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Pengelolaan fiskal berfokus pada instrumen anggaran pemerintah (pajak dan belanja) untuk pertumbuhan dan pemerataan. Di sisi lain, pengelolaan moneter berfokus pada pengendalian besaran uang, suku bunga, dan cadangan perbankan oleh bank sentral demi stabilitas nilai mata uang. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Fiskal mengelola APBN pemerintah, moneter mengendalikan uang beredar BI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Terbalik, penyediaan fasilitas publik adalah mandat fiskal sedangkan perbankan moneter.</li><li><strong>C</strong>: Terbalik, pengendalian suku bunga adalah instrumen moneter dan pajak adalah fiskal.</li><li><strong>D</strong>: Terbalik, sistem kliring diatur oleh moneter sedangkan belanja dikelola oleh fiskal.</li><li><strong>E</strong>: Transfer daerah adalah instrumen fiskal sedangkan pengawasan pasar modal oleh OJK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>subbidang pengelolaan fiskal • subbidang pengelolaan moneter</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Pelaksanaan kebijakan moneter dituntut berjalan secara hati-hati (prudent) dengan batasan utama yaitu...", "options": ["Menjaga surplus neraca modal tanpa memperhatikan inflasi", "Dilaksanakan tanpa memberatkan beban keuangan negara dan masyarakat", "Mengarahkan alokasi kredit khusus pada korporasi BUMN", "Membatasi cadangan primer perbankan hingga batas minimal", "Menghapus batas maksimum penyaluran kredit perbankan"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Batasan Pelaksanaan Kebijakan Moneter</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pelaksanaan kebijakan moneter harus dilakukan sedemikian rupa <strong>tanpa memberatkan beban keuangan negara maupun masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Kebijakan moneter tidak boleh menimbulkan distorsi biaya yang berlebihan bagi perekonomian nasional. Kebijakan pengendalian suku bunga dan likuiditas harus menjaga keseimbangan agar tidak membebani biaya utang kas negara maupun menekan daya beli masyarakat. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Dilaksanakan tanpa memberatkan beban keuangan negara dan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengendalian inflasi adalah sasaran pokok dan tidak boleh diabaikan demi neraca modal.</li><li><strong>C</strong>: Kredit perbankan harus dialokasikan secara inklusif kepada seluruh sektor produktif.</li><li><strong>D</strong>: Cadangan primer perbankan diatur secara proporsional demi keamanan likuiditas.</li><li><strong>E</strong>: Prinsip kehati-hatian perbankan tetap mewajibkan adanya batas maksimum penyaluran kredit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 10) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kebijakan moneter dapat dilaksanakan • tanpa memberatkan beban keuangan negara dan masyarakat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Subbidang pengelolaan kekayaan negara yang dipisahkan mencakup aset negara yang pengelolaannya diserahkan kepada...", "options": ["Badan Layanan Umum pengelola dana operasional dinas", "Lembaga nonstruktural pengawas persaingan usaha", "Badan Usaha Milik Negara atau Daerah (BUMN/BUMD)", "Satuan kerja pelaksana fungsi perbendaharaan umum", "Otoritas independen pengawas jasa perbankan negara"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Subbidang Kekayaan Negara Dipisahkan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Subbidang Pengelolaan Kekayaan negara yang dipisahkan</strong> adalah komponen keuangan negara yang pengelolaannya diserahkan kepada <strong>perusahaan yang seluruh atau sebagian modal atau sahamnya dimiliki oleh negara (BUMN/BUMD)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Kekayaan negara yang dipisahkan adalah kekayaan negara yang dilepaskan dari tata kelola perbendaharaan umum APBN untuk dijadikan modal atau saham pada korporasi milik negara, yaitu BUMN atau BUMD, yang dikelola secara profesional menurut prinsip korporasi yang sehat. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Badan Usaha Milik Negara atau Daerah (BUMN/BUMD)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Badan Layanan Umum (BLU) tetap merupakan satker kementerian dan bukan kekayaan dipisahkan.</li><li><strong>B</strong>: Lembaga nonstruktural adalah instansi pendukung birokrasi dan bukan badan usaha milik negara.</li><li><strong>D</strong>: Satuan kerja perbendaharaan mengelola kas negara secara langsung dalam rezim APBN.</li><li><strong>E</strong>: Otoritas pengawas jasa keuangan adalah lembaga regulasi dan bukan entitas korporasi BUMN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Subbidang Pengelolaan Kekayaan negara yang dipisahkan • Badan Usaha Milik Negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Hubungan keuangan antara kekayaan negara yang dipisahkan (BUMN) dengan APBN bersifat tidak langsung melalui pos...", "options": ["Alokasi belanja barang operasional harian kementerian", "Penatausahaan kas penampungan pada bank sentral", "Penetapan standar tarif pelayanan publik dinas teknis", "Penyertaan modal negara dan setoran bagian laba dividen", "Pengesahan dokumen anggaran proyek strategis daerah"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Kekayaan Negara Dipisahkan dengan APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hubungan antara kekayaan negara yang dipisahkan dengan APBN berlangsung melalui <strong>penyertaan modal negara (PMN)</strong> dari kas negara dan setoran <strong>bagian laba (dividen)</strong> ke kas negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>BUMN/BUMD mengelola kekayaannya secara otonom terpisah dari kas harian pemerintah. Hubungan keuangannya dengan APBN hanya terjadi pada dua pintu: pengeluaran pembiayaan pemerintah berupa Penyertaan Modal Negara (PMN) dan penerimaan bukan pajak (PNBP) berupa setoran dividen laba. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Penyertaan modal negara dan setoran bagian laba dividen</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja operasional kementerian didanai langsung lewat DIPA satker dan bukan BUMN.</li><li><strong>B</strong>: Kas penampungan bank sentral adalah rekening kas umum negara milik perbendaharaan.</li><li><strong>C</strong>: Standar tarif layanan teknis diatur lewat regulasi kementerian terkait.</li><li><strong>E</strong>: Dokumen anggaran proyek daerah merupakan instrumen pelaksanaan belanja APBD.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>menyertakan tambahan modal dalam BUMN • setoran bagian laba BUMN</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menurut pakar keuangan publik Richard Musgrave, keuangan negara tidak sekadar uang masuk dan keluar melainkan berhubungan dengan...", "options": ["Penataan peredaran instrumen modal di bursa efek", "Pengawasan likuiditas portofolio perbankan nasional", "Pembentukan cadangan valuta asing bank sentral", "Penetapan suku bunga acuan perbankan komersial", "Fungsi alokasi sumber ekonomi, distribusi, dan stabilisasi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Keuangan Negara Menurut Richard Musgrave</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut Richard Musgrave, keuangan negara mencakup tugas dan fungsi fundamental yakni: <strong>realokasi sumber daya ekonomi, redistribusi pendapatan, dan stabilisasi ekonomi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Musgrave memandang keuangan negara sebagai instrumen kebijakan publik yang strategis. Keuangan negara tidak boleh dipandang sempit hanya sebagai pencatatan pembukuan kas masuk dan keluar, melainkan menjalankan tiga fungsi pokok: mengalokasikan sumber daya, mendistribusikan pendapatan secara adil, dan menstabilkan makroekonomi. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Fungsi alokasi sumber ekonomi, distribusi, dan stabilisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Instrumen bursa efek merupakan mekanisme perdagangan surat berharga pasar modal.</li><li><strong>B</strong>: Likuiditas perbankan merupakan objek pengawasan sektor keuangan oleh bank sentral dan OJK.</li><li><strong>C</strong>: Cadangan devisa luar negeri adalah instrumen pengamanan neraca pembayaran moneter.</li><li><strong>D</strong>: Suku bunga acuan merupakan instrumen transmisi kebijakan moneter bank sentral.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menurut Musgrave, keuangan negara tidak hanya berhubungan • fungsi alokasi sumber-sumber ekonomi, fungsi distribusi, dan fungsi stabilisasi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pungutan pajak oleh pemerintah di satu sisi meningkatkan penerimaan negara, namun di sisi lain berdampak pada...", "options": ["Berkurangnya daya beli dan penurunan permintaan masyarakat", "Meningkatnya kapasitas likuiditas pada sektor perbankan", "Menurunnya cadangan devisa yang dikelola bank sentral", "Bertambahnya beban pembiayaan utang pada pasar modal", "Menguatnya tingkat nilai tukar mata uang di pasar valuta"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Pungutan Pajak terhadap Daya Beli</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerimaan negara dari pajak di satu sisi mengisi kas negara, namun di sisi lain <strong>akan mengurangi daya beli masyarakat sehingga permintaan atas barang dan jasa menurun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Pungutan pajak mengurangi disposable income atau pendapatan siap konsumsi yang dimiliki oleh masyarakat dan dunia usaha. Pengurangan pendapatan siap pakai ini secara otomatis menyebabkan penurunan daya beli dan menekan tingkat permintaan agregat di pasar. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Berkurangnya daya beli dan penurunan permintaan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pungutan pajak menarik likuiditas dari kas masyarakat ke kas negara dan tidak menambah likuiditas bank.</li><li><strong>C</strong>: Penerimaan pajak domestik dalam rupiah tidak langsung mengurangi cadangan devisa luar negeri.</li><li><strong>D</strong>: Penerimaan pajak yang kuat justru mengurangi kebutuhan pemerintah menarik pembiayaan utang.</li><li><strong>E</strong>: Pajak domestik tidak secara instan memperkuat nilai tukar valuta asing di pasar uang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pungutan pajak kepada masyarakat • mengurangi daya beli masyarakat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Belanja pemerintah yang dialokasikan untuk membeli barang dan jasa dari masyarakat memberikan dampak ekonomi berupa...", "options": ["Terjadinya pengetatan likuiditas di pasar perbankan", "Mendorong perputaran ekonomi dan menambah daya beli", "Penurunan cadangan modal pada sektor badan usaha", "Pergeseran struktur portofolio di pasar obligasi", "Penahanan volume peredaran barang kebutuhan pokok"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Belanja Pemerintah terhadap Perekonomian</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengeluaran atau <strong>belanja pemerintah akan memutar roda perekonomian dan menambah daya beli masyarakat</strong> melalui pembelian barang dan jasa.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Injeksi dana belanja negara ke dalam perekonomian menggerakkan permintaan terhadap produk domestik, menciptakan pendapatan bagi para pelaku usaha penyedia barang/jasa, dan membuka lapangan kerja. Hal ini mendorong percepatan perputaran roda perekonomian nasional. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Mendorong perputaran ekonomi dan menambah daya beli</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyaluran belanja justru menyuntikkan likuiditas dana segar ke dalam sistem perbankan.</li><li><strong>C</strong>: Belanja pemerintah menciptakan pendapatan yang memperkuat modal kerja dunia usaha.</li><li><strong>D</strong>: Pengeluaran belanja riil berdampak pada perputaran barang dan bukan sekadar obligasi pasar modal.</li><li><strong>E</strong>: Belanja publik memperlancar arus distribusi dan peredaran kebutuhan pokok masyarakat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>belanja Pemerintah, yang digunakan untuk membeli barang dan jasa • menambah daya beli masyarakat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kondisi di mana total penerimaan negara lebih besar daripada total pengeluaran belanja negara disebut...", "options": ["Anggaran berimbang dinamis", "Defisit anggaran fiskal", "Surplus anggaran APBN", "Keseimbangan primer nol", "Pembiayaan neto nihil"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kondisi Surplus Anggaran Pendapatan dan Belanja Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kondisi keuangan negara di mana <strong>penerimaan lebih besar dari pengeluaran</strong> disebut sebagai <strong>APBN surplus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Secara teknis anggaran, selisih positif antara realisasi penerimaan negara (pajak dan PNBP) terhadap total belanja negara menghasilkan posisi surplus anggaran. Surplus ini dapat digunakan untuk membentuk dana cadangan, melunasi kewajiban utang, atau mendanai investasi pemerintah. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Surplus anggaran APBN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Anggaran berimbang adalah posisi di mana total penerimaan tepat menyamai total pengeluaran.</li><li><strong>B</strong>: Defisit anggaran terjadi jika pengeluaran belanja pemerintah melebihi total penerimaan.</li><li><strong>D</strong>: Keseimbangan primer nol adalah kondisi penerimaan dikurangi belanja di luar bunga bernilai nol.</li><li><strong>E</strong>: Pembiayaan neto nihil merujuk pada ketiadaan penarikan pembiayaan bersih baru.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penerimaan negara melebihi pengeluaran negara • Anggaran Pendapatan dan Belanja Negara (APBN) akan mengalami surplus</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Pemerintah mengalokasikan dana segar Rp5 triliun untuk memperkuat struktur permodalan PT PLN dalam proyek kelistrikan desa. Pos anggaran yang digunakan adalah...", "options": ["Belanja pegawai untuk pemenuhan tunjangan kinerja dinas", "Belanja barang operasional kantor kementerian terkait", "Penerimaan bukan pajak dari pemanfaatan aset satker", "Pembiayaan investasi melalui Penyertaan Modal Negara", "Bantuan pemerintah transfer daerah khusus penugasan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Penyertaan Modal Negara (PMN)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penambahan permodalan pada BUMN bersumber dari APBN pos pembiayaan melalui instrumen <strong>Penyertaan Modal Negara (PMN)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Injeksi modal pemerintah kepada korporasi milik negara (BUMN) dicatat dalam struktur APBN sebagai pos pengeluaran pembiayaan investasi, yakni Penyertaan Modal Negara (PMN). Alokasi ini memperbesar ekuitas modal negara pada korporasi terkait untuk penugasan pembangunan publik. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Pembiayaan investasi melalui Penyertaan Modal Negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja pegawai digunakan khusus untuk gaji dan tunjangan aparatur sipil negara.</li><li><strong>B</strong>: Belanja barang operasional digunakan untuk pengadaan perlengkapan kantor dinas kementerian.</li><li><strong>C</strong>: Pemanfaatan aset merupakan pos penerimaan kas dan bukan pengeluaran investasi modal.</li><li><strong>E</strong>: Transfer daerah dialokasikan kepada pemerintah daerah otonom dan bukan korporasi BUMN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>menyertakan tambahan modal dalam BUMN • pos-pos pembiayaan APBN</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "PT Telkom membukukan laba bersih dan menyetorkan bagian dividen kepada pemerintah sebesar Rp3 triliun. Dalam APBN setoran ini dicatat pada pos...", "options": ["Penerimaan kepabeanan dan cukai komoditas strategis", "Penerimaan hibah program pembangunan infrastruktur", "Pembiayaan penarikan utang surat berharga syariah", "Belanja subsidi kompensasi energi bahan bakar minyak", "Penerimaan Negara Bukan Pajak bagian laba BUMN"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Penerimaan Dividen BUMN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setoran bagian laba atau dividen dari BUMN yang menjadi hak pemerintah dimasukkan ke dalam APBN sebagai <strong>Penerimaan Negara Bukan Pajak (PNBP)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Berdasarkan struktur klasifikasi pendapatan negara, bagian keuntungan atau dividen yang diperoleh dari penyertaan modal pemerintah pada BUMN dikelompokkan sebagai Penerimaan Negara Bukan Pajak (PNBP) pos pendapatan kekayaan negara yang dipisahkan. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Penerimaan Negara Bukan Pajak bagian laba BUMN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kepabeanan dan cukai merupakan pos penerimaan perpajakan atas arus barang impor dan cukai.</li><li><strong>B</strong>: Hibah merupakan penerimaan sukarela dari pihak luar yang tidak perlu dibayar kembali.</li><li><strong>C</strong>: Surat berharga syariah merupakan instrumen pembiayaan utang penutup defisit APBN.</li><li><strong>D</strong>: Subsidi energi merupakan pos pengeluaran belanja negara untuk meringankan beban masyarakat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>setoran bagian laba BUMN untuk Pemerintah • pos-pos pembiayaan APBN</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Meskipun surplus APBN menjamin kecukupan kas pemerintah, risiko kontraktif yang dapat timbul di masyarakat adalah...", "options": ["Turunnya daya beli akibat beban pungutan pajak yang tinggi", "Terjadinya lonjakan inflasi pada harga barang kebutuhan pokok", "Meningkatnya defisit transaksi berjalan di sektor perdagangan", "Melemahnya stabilitas nilai tukar mata uang di pasar valuta", "Bertambahnya beban pembayaran bunga utang luar negeri"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Efek Samping Surplus Anggaran Tinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Surplus APBN yang berlebihan</strong> dapat menimbulkan dampak kontraktif yakni <strong>mengurangi daya beli masyarakat secara drastis</strong> akibat pungutan pajak yang terlalu tinggi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Surplus anggaran terjadi ketika penerimaan (pajak) melampaui pengeluaran belanja pemerintah. Apabila surplus dipicu oleh beban pungutan pajak yang terlalu berat sementara belanja publik ditahan, uang yang beredar di sektor riil berkurang, sehingga menekan daya beli masyarakat dan memperlambat pertumbuhan ekonomi. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Turunnya daya beli akibat beban pungutan pajak yang tinggi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Surplus anggaran bersifat kontraktif sehingga justru menahan laju inflasi dan bukan melonjakkannya.</li><li><strong>C</strong>: Defisit transaksi berjalan dipengaruhi oleh neraca ekspor-impor barang secara internasional.</li><li><strong>D</strong>: Pengetatan fiskal melalui surplus anggaran cenderung menstabilkan nilai tukar mata uang.</li><li><strong>E</strong>: Surplus kas memberikan ruang fiskal untuk melunasi utang sehingga beban bunga berkurang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Surplus berarti penerimaan negara cukup • mengurangi daya beli masyarakat (karena beban pajak yang tinggi)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Keuangan negara melalui kebijakan penerimaan dan belanja mampu mempengaruhi pertumbuhan ekonomi dengan cara...", "options": ["Menetapkan batas maksimum transaksi modal di bursa efek", "Mempengaruhi mekanisme harga, daya beli, dan permintaan", "Mengarahkan portofolio kredit perbankan secara terpusat", "Mengendalikan cadangan primer seluruh bank komersial", "Menjamin likuiditas pasar modal melalui intervensi kurs"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Pengaruh Keuangan Negara terhadap Mekanisme Harga</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan penerimaan dan belanja negara berpengaruh terhadap <strong>mekanisme harga, tingkat daya beli, dan volume permintaan masyarakat</strong> atas barang dan jasa.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Instrumen fiskal bekerja mempengaruhi perekonomian melalui intervensi daya beli. Pajak menahan permintaan agregat, sementara belanja pemerintah menginjeksi daya beli baru ke pasar, yang secara bersama-sama menentukan titik keseimbangan harga dan volume produksi riil. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Mempengaruhi mekanisme harga, daya beli, dan permintaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Batas transaksi pasar modal merupakan regulasi teknis bursa efek dan otoritas keuangan.</li><li><strong>C</strong>: Penyaluran kredit perbankan komersial diatur oleh manajemen perbankan dan OJK.</li><li><strong>D</strong>: Pengendalian cadangan primer perbankan adalah instrumen kebijakan moneter Bank Indonesia.</li><li><strong>E</strong>: Intervensi kurs pasar valuta asing merupakan wewenang operasi moneter bank sentral.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dapat mempengaruhi bekerjanya mekanisme harga • menambah daya beli masyarakat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Perbedaan pokok tata kelola antara aset Barang Milik Negara (BMN) kementerian dan kekayaan negara yang dipisahkan (BUMN) adalah...", "options": ["BMN diaudit aparat eksternal, BUMN diaudit badan peradilan", "BMN bertujuan mencari laba, BUMN dilarang menghasilkan laba", "BMN dikelola rezim perbendaharaan, BUMN dikelola rezim korporasi", "BMN diatur perdata murni, BUMN diatur hukum pidana khusus", "BMN tunduk pada pasar modal, BUMN tunduk pada kas daerah"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Perbedaan Pengelolaan Aset BMN dan BUMN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Aset <strong>BMN dikelola dengan rezim hukum perbendaharaan negara</strong>, sedangkan <strong>kekayaan negara dipisahkan (BUMN) dikelola dengan rezim hukum korporasi perseroan terbatas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Barang Milik Negara (BMN) tunduk pada tata kelola perbendaharaan publik APBN untuk mendukung operasional layanan birokrasi tanpa berorientasi laba komersial. Sebaliknya, aset kekayaan dipisahkan dilepaskan ke dalam entitas BUMN untuk dikelola secara independen berdasarkan hukum korporasi bisnis yang berorientasi profit dan nilai tambah. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. BMN dikelola rezim perbendaharaan, BUMN dikelola rezim korporasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Baik BMN maupun BUMN sama-sama menjadi objek audit pemeriksaan eksternal oleh BPK RI.</li><li><strong>B</strong>: Terbalik, BMN tidak berorientasi laba komersial sedangkan BUMN bertujuan meraih keuntungan.</li><li><strong>D</strong>: BMN diatur hukum administrasi publik negara dan BUMN berlandaskan hukum perseroan.</li><li><strong>E</strong>: BMN tunduk pada ketentuan kas negara APBN dan bukan instrumen bursa pasar modal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 11) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Subbidang Pengelolaan Kekayaan negara yang dipisahkan • modal atau sahamnya dimiliki oleh negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kondisi saat pengeluaran negara lebih besar dari penerimaannya (APBN defisit) memberikan dampak ekonomi berupa...", "options": ["Penurunan cadangan modal pada lembaga pembiayaan daerah", "Pengetatan likuiditas perbankan dan kenaikan suku bunga", "Penurunan volume perdagangan antarwilayah di dalam negeri", "Menambah daya beli masyarakat melalui injeksi belanja", "Penurunan kapasitas produksi pada industri manufaktur"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Defisit APBN dan Daya Beli</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kondisi saat pengeluaran lebih besar dari penerimaannya (APBN defisit) berdampak positif bagi stimulasi ekonomi karena <strong>defisit akan menambah daya beli masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Kebijakan defisit anggaran berarti pemerintah mengalirkan belanja ke masyarakat dalam jumlah yang lebih besar daripada dana yang ditarik melalui pajak. Selisih ekspansi belanja ini memberikan suntikan likuiditas segar ke pasar sehingga menambah daya beli masyarakat secara luas. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Menambah daya beli masyarakat melalui injeksi belanja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Defisit anggaran tidak secara langsung menurunkan modal lembaga pembiayaan daerah.</li><li><strong>B</strong>: Injeksi belanja pemerintah justru menambah likuiditas dana dalam perekonomian nasional.</li><li><strong>C</strong>: Peningkatan daya beli dari belanja APBN mendorong peningkatan perdagangan antardaerah.</li><li><strong>E</strong>: Tambahan permintaan akibat defisit anggaran mendorong perluasan produksi industri manufaktur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>apabila pengeluaran lebih besar dari penerimaannya • defisit akan menambah daya beli masyarakat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kebijakan fiskal counter-cyclical didefinisikan sebagai kebijakan anggaran pemerintah yang...", "options": ["Mengikuti secara langsung pergerakan pertumbuhan ekonomi pasar", "Mempertahankan posisi anggaran berimbang secara mutlak", "Menyerahkan penyesuaian belanja pada mekanisme perbankan", "Memperbesar penarikan pajak saat terjadi pelemahan ekonomi", "Bergerak berlawanan arah dengan siklus fluktuasi ekonomi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Kebijakan Fiskal Counter-Cyclical</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kebijakan fiskal counter-cyclical</strong> adalah kebijakan anggaran yang <strong>bergerak berlawanan arah dengan siklus ekonomi</strong> guna meredam gejolak resesi maupun inflasi berlebih.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Strategi counter-cyclical dirancang untuk menstabilkan perekonomian dengan merespons siklus secara terbalik: ketika perekonomian melemah, pemerintah melakukan ekspansi fiskal (menambah belanja/menurunkan pajak); sebaliknya ketika ekonomi terlalu panas (overheating), pemerintah melakukan pengetatan anggaran. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Bergerak berlawanan arah dengan siklus fluktuasi ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengikuti arah siklus pasar merupakan ciri dari kebijakan pro-cyclical.</li><li><strong>B</strong>: Anggaran berimbang mutlak tidak mencerminkan respon dinamis kebijakan counter-cyclical.</li><li><strong>C</strong>: Penyesuaian belanja adalah keputusan fiskal pemerintah dan bukan mekanisme perbankan.</li><li><strong>D</strong>: Menaikkan pajak saat ekonomi melemah adalah tindakan kontraktif yang memperparah resesi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kebijakan fiskal yang bersifat counter-cyclical • kebijakan yang bergerak berlawanan arah dengan siklus ekonomi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kebijakan fiskal yang sejalan dengan arah siklus ekonomi (meningkatkan belanja saat tumbuh dan memangkas saat lesu) disebut...", "options": ["Kebijakan fiskal pro-cyclical", "Kebijakan fiskal counter-cyclical", "Kebijakan moneter kontraktif", "Kebijakan anggaran berimbang", "Kebijakan stabilisasi dinamis"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Kebijakan Fiskal Pro-Cyclical</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan fiskal yang <strong>bergerak searah dengan siklus ekonomi</strong> (ekspansif saat booming dan kontraktif saat resesi) disebut sebagai <strong>kebijakan pro-cyclical</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Kebijakan pro-cyclical memperkuat gelombang siklus ekonomi karena pemerintah menambah belanja saat ekonomi sedang bertumbuh kencang dan justru memangkas belanja saat ekonomi sedang mengalami kelesuan. Kebijakan ini dinilai berisiko memperparah ketidakstabilan ekonomi. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Kebijakan fiskal pro-cyclical</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kebijakan counter-cyclical bergerak berlawanan arah guna meredam siklus fluktuasi ekonomi.</li><li><strong>C</strong>: Moneter kontraktif adalah kebijakan bank sentral mengurangi jumlah uang yang beredar.</li><li><strong>D</strong>: Anggaran berimbang menjaga kesamaan antara total penerimaan dan total belanja negara.</li><li><strong>E</strong>: Stabilisasi dinamis mengacu pada penyesuaian makro terencana untuk stabilitas jangka panjang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kebijakan yang bersifat pro-cyclical • sejalan dengan arah siklus ekonomi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam strategi counter-cyclical, langkah yang tepat diambil pemerintah saat perekonomian mengalami resesi pelemahan adalah...", "options": ["Mengetatkan penyaluran kredit dan menaikkan suku bunga", "Meningkatkan belanja negara atau menurunkan beban pajak", "Menaikkan tarif pajak dan memperkecil defisit anggaran", "Menahan pengeluaran belanja modal hingga ekonomi pulih", "Mengurangi transfer dana perimbangan ke daerah otonom"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Instrumen Counter-Cyclical pada Masa Resesi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada saat terjadi resesi pelemahan ekonomi, kebijakan counter-cyclical dilakukan dengan <strong>meningkatkan belanja negara atau mengurangi tarif perpajakan</strong> guna memulihkan daya beli.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Ketika aktivitas ekonomi melemah dan pengangguran meningkat, pemerintah mengambil peran sebagai penggerak permintaan melalui stimulus fiskal. Peningkatan belanja negara dan pemberian insentif penurunan pajak akan mengembalikan daya beli masyarakat dan membangkitkan roda perekonomian. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Meningkatkan belanja negara atau menurunkan beban pajak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengetatkan kredit dan menaikkan bunga adalah kebijakan moneter kontraktif penahan inflasi.</li><li><strong>C</strong>: Menaikkan pajak saat resesi justru memperdalam kejatuhan daya beli masyarakat.</li><li><strong>D</strong>: Menahan belanja modal akan memperlambat pembangunan dan memperburuk kelesuan sektor riil.</li><li><strong>E</strong>: Memotong dana transfer daerah akan melumpuhkan likuiditas pelayanan publik di daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>counter-cyclical • pemerintah meningkatkan belanja atau menurunkan pajak saat ekonomi melemah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Sebaliknya, pada saat ekonomi mengalami overheating atau inflasi tinggi, kebijakan counter-cyclical yang tepat adalah...", "options": ["Memperbesar defisit anggaran belanja untuk stimulus", "Melipatgandakan pagu transfer dana bantuan sosial", "Mengurangi belanja negara atau menaikkan pajak", "Memperluas insentif pembebasan pajak penghasilan", "Mengakselerasi penyaluran belanja proyek fisik baru"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Instrumen Counter-Cyclical pada Kondisi Overheating</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada saat ekonomi mengalami overheating atau inflasi yang sangat tinggi, kebijakan counter-cyclical menuntut pemerintah <strong>mengurangi pengeluaran belanja atau menaikkan penerimaan pajak</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Inflasi tinggi terjadi ketika permintaan agregat melampaui kapasitas penawaran barang dan jasa. Untuk meredam lonjakan harga, pemerintah menerapkan pengetatan fiskal dengan cara menahan laju belanja negara dan menaikkan tarif pajak agar kelebihan permintaan dapat diredam. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Mengurangi belanja negara atau menaikkan pajak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Memperbesar defisit belanja akan menyuntikkan likuiditas baru dan memperparah inflasi.</li><li><strong>B</strong>: Melipatgandakan bansos saat ekonomi terlalu panas akan mendorong lonjakan harga komoditas.</li><li><strong>D</strong>: Membebaskan pajak akan memicu lonjakan konsumsi yang mempercepat laju inflasi pasar.</li><li><strong>E</strong>: Mengakselerasi proyek fisik baru akan memicu perebutan bahan baku dan menaikkan harga umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>ekonomi memanas (inflasi tinggi) • mengurangi belanja atau menaikkan pajak</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Teori ekonomi yang dikemukakan oleh John Maynard Keynes menekankan pentingnya...", "options": ["Penyerahan alokasi sumber daya pada pasar bebas murni", "Pembatasan peran anggaran pemerintah seminimal mungkin", "Penerapan prinsip anggaran berimbang mutlak tanpa defisit", "Peran aktif pemerintah melalui intervensi kebijakan fiskal", "Pengendalian ekonomi terpusat lewat instrumen moneter"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Intervensi Pemerintah Menurut Keynes</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pandangan Keynes menekankan bahwa mekanisme pasar tidak selalu dapat menyelesaikan kelesuan ekonomi secara mandiri, sehingga menuntut <strong>peran aktif pemerintah melalui intervensi kebijakan fiskal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>John Maynard Keynes membantah doktrin ekonomi klasik yang meyakini pasar selalu mencapai titik keseimbangan optimal dengan sendirinya. Keynes menegaskan bahwa saat resesi melanda, intervensi aktif pemerintah melalui belanja publik sangat diperlukan untuk memulihkan permintaan pasar. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Peran aktif pemerintah melalui intervensi kebijakan fiskal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pasar bebas murni tanpa intervensi adalah doktrin ekonomi klasik laissez-faire.</li><li><strong>B</strong>: Pembatasan peran anggaran seminimal mungkin adalah konsep negara penjaga malam.</li><li><strong>C</strong>: Anggaran berimbang mutlak ditolak Keynes karena membatasi stimulus belanja saat krisis.</li><li><strong>E</strong>: Pengendalian moneter semata adalah pandangan kaum monetaris dan bukan Keynesian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pendapat Keynes • teori ekonomi Keynesian, intervensi pemerintah diperlukan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menurut Keynes, instrumen fiskal utama yang paling efektif untuk mendorong permintaan agregat saat ekonomi lesu adalah...", "options": ["Pengetatan rasio likuiditas perbankan komersial", "Pengurangan subsidi tarif pelayanan utilitas publik", "Kenaikan tarif bea keluar komoditas barang ekspor", "Penundaan proyek infrastruktur strategis nasional", "Belanja pemerintah secara terencana dan terarah"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Belanja Pemerintah sebagai Pendorong Permintaan Agregat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut Keynes, instrumen utama untuk menggerakkan perekonomian yang sedang lesu adalah <strong>belanja pemerintah secara terencana dan terarah</strong> guna menciptakan efek pengganda.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Keynes menempatkan belanja pemerintah (government expenditure) sebagai komponen permintaan agregat yang paling dapat dikendalikan langsung oleh otoritas publik. Ketika konsumsi masyarakat dan investasi swasta anjlok, belanja pemerintahlah yang mampu menggerakkan kembali aktivitas ekonomi. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Belanja pemerintah secara terencana dan terarah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengetatan likuiditas merupakan instrumen moneter kontraktif penekan permintaan.</li><li><strong>B</strong>: Pengurangan subsidi akan menaikkan beban biaya hidup masyarakat saat resesi melanda.</li><li><strong>C</strong>: Kenaikan bea keluar menghambat kinerja ekspor komoditas nasional ke pasar global.</li><li><strong>D</strong>: Menunda proyek infrastruktur justru menghilangkan lapangan kerja dan permintaan bahan baku.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keynes menekankan pentingnya belanja pemerintah • mendorong permintaan agregat saat ekonomi lesu</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Saat terjadi krisis ekonomi, pemerintah memberikan insentif pemotongan pajak dan memperbesar anggaran bantuan sosial. Tindakan ini mencerminkan...", "options": ["Kebijakan fiskal counter-cyclical penyelamatan ekonomi", "Kebijakan pro-cyclical pengetatan anggaran belanja", "Kebijakan moneter pengetatan likuiditas perbankan", "Kebijakan stabilisasi berbasis anggaran berimbang", "Kebijakan deregulasi perdagangan luar negeri bebas"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Kebijakan Fiskal Pemulihan Krisis Pandemi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Langkah pemerintah memperbesar belanja dan memberikan insentif pajak saat perekonomian melemah merupakan wujud nyata <strong>kebijakan fiskal yang bersifat counter-cyclical</strong> guna menjaga daya beli masyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Dalam situasi pelemahan ekonomi atau resesi, intervensi pemerintah melalui penurunan beban pajak dan peningkatan transfer bantuan sosial bertujuan memulihkan daya beli masyarakat yang terpuruk. Respons anggaran yang bergerak melawan arah kelesuan ekonomi ini merupakan penerapan instrumen fiskal counter-cyclical. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Kebijakan fiskal counter-cyclical penyelamatan ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kebijakan pro-cyclical justru memangkas belanja dan menaikkan pajak saat krisis.</li><li><strong>C</strong>: Pengetatan likuiditas perbankan adalah instrumen moneter kontraktif bank sentral.</li><li><strong>D</strong>: Anggaran berimbang membatasi stimulus belanja karena terikat pada penerimaan kas.</li><li><strong>E</strong>: Deregulasi perdagangan adalah instrumen fasilitasi ekspor-impor sektor riil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kebijakan fiskal yang bersifat counter-cyclical • pemerintah meningkatkan belanja atau menurunkan pajak saat ekonomi melemah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Sebuah negara mengalami penurunan pendapatan saat resesi dan langsung memotong drastis anggaran belanja sosialnya. Risiko yang dihadapi adalah...", "options": ["Laju inflasi langsung melonjak melampaui target bank sentral", "Kondisi krisis makin parah akibat kebijakan pro-cyclical", "Nilai tukar mata uang menguat tanpa bantuan intervensi pasar", "Penerimaan perpajakan meningkat pesat dalam jangka pendek", "Surplus neraca perdagangan luar negeri meningkat drastis"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Bahaya Kebijakan Fiskal Pro-Cyclical</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan yang bersifat pro-cyclical dengan memotong belanja saat ekonomi melemah <strong>dapat memperkuat gejolak ekonomi dan berisiko memperburuk kondisi saat terjadi krisis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Memangkas belanja jaring pengaman sosial di tengah resesi menghilangkan bantalan konsumsi bagi masyarakat miskin. Penurunan belanja pemerintah yang searah dengan kontraksi pasar (pro-cyclical) ini akan memicu penurunan permintaan agregat lebih dalam dan memperparah keparahan krisis ekonomi. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Kondisi krisis makin parah akibat kebijakan pro-cyclical</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemotongan belanja menekan permintaan sehingga laju inflasi cenderung turun drastis.</li><li><strong>C</strong>: Resesi ekonomi yang memburuk umumnya melemahkan kepercayaan atas nilai tukar domestik.</li><li><strong>D</strong>: Krisis yang bertambah parah justru menurunkan basis pajak dan pendapatan negara.</li><li><strong>E</strong>: Kelesuan sektor produksi dalam negeri cenderung menekan volume perdagangan ekspor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kebijakan yang bersifat pro-cyclical • berisiko memperburuk kondisi saat terjadi krisis</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Perbedaan filosofis mendasar antara kebijakan counter-cyclical dan kebijakan pro-cyclical terletak pada...", "options": ["Fokus sasaran: sektor korporasi vs usaha mikro masyarakat", "Waktu eksekusi: awal tahun anggaran vs akhir tahun anggaran", "Arah respon anggaran: berlawanan arah vs sejalan siklus ekonomi", "Basis instrumen: penarikan utang vs penerbitan surat berharga", "Sifat pelaporan: akuntansi kas vs akuntansi akrual pemerintahan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Pembeda Counter-Cyclical dan Pro-Cyclical</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perbedaan pokok keduanya terletak pada arah gerak respons fiskal: <strong>counter-cyclical bergerak berlawanan arah dengan siklus</strong>, sedangkan <strong>pro-cyclical bergerak sejalan dengan arah siklus ekonomi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Secara teoritis, pembeda utama kedua kebijakan fiskal ini adalah respons postur anggaran terhadap fluktuasi ekonomi. Counter-cyclical mengintervensi dengan arah berlawanan untuk meredam gejolak fluktuasi, sedangkan pro-cyclical bergerak searah dengan gelombang siklus yang berisiko memperbesar ketidakstabilan ekonomi. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Arah respon anggaran: berlawanan arah vs sejalan siklus ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Fokus sasaran sektor usaha bukan merupakan kriteria pembeda arah kebijakan siklikal.</li><li><strong>B</strong>: Waktu pelaksanaan anggaran ditentukan oleh siklus APBN dan bukan pembeda doktrin.</li><li><strong>D</strong>: Instrumen pembiayaan utang dapat digunakan dalam kedua pendekatan kebijakan.</li><li><strong>E</strong>: Sistem akuntansi kas atau akrual merupakan standar pencatatan teknis pembukuan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kebijakan yang bergerak berlawanan arah dengan siklus ekonomi • sejalan dengan arah siklus ekonomi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Perluasan tugas dan fungsi negara modern di luar konsep penjaga malam (night-watchman state) ditandai oleh kewajiban...", "options": ["Membatasi alokasi anggaran belanja untuk fungsi pertahanan", "Menyerahkan penyediaan seluruh barang publik kepada pasar", "Mempertahankan postur anggaran berimbang tanpa penyesuaian", "Menjaga stabilitas perekonomian melalui kebijakan fiskal responsif", "Meniadakan instrumen redistribusi pendapatan masyarakat"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Perluasan Fungsi Negara Modern</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Negara modern tidak sekadar menyelenggarakan pertahanan, peradilan, dan barang publik, namun bertugas <strong>menjaga kestabilan perekonomian melalui kebijakan fiskal yang responsif terhadap dinamika siklus ekonomi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Konsep negara penjaga malam klasik membatasi fungsi negara hanya pada pertahanan, hukum, dan keamanan semata. Dalam perekonomian modern, tanggung jawab negara meluas mencakup stabilisasi makroekonomi melalui kebijakan fiskal yang aktif guna melindungi kesejahteraan rakyat dari ancaman inflasi dan pengangguran. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Menjaga stabilitas perekonomian melalui kebijakan fiskal responsif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Membatasi anggaran hanya untuk pertahanan adalah ciri doktrin negara penjaga malam.</li><li><strong>B</strong>: Penyediaan barang publik tetap menjadi tanggung jawab negara karena adanya kegagalan pasar.</li><li><strong>C</strong>: Anggaran berimbang pasif tidak mampu merespons gejolak ekonomi krisis secara dinamis.</li><li><strong>E</strong>: Redistribusi pendapatan merupakan fungsi pokok negara modern demi keadilan sosial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 12) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tugas dan fungsi negara menjadi lebih penting • menjaga kestabilan perekonomian melalui kebijakan fiskal yang responsif</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam buku The Theory of Public Finance, Richard Musgrave menyatakan bahwa tugas dan fungsi negara meliputi...", "options": ["Monopoli perdagangan, proteksionisme, dan pengawasan devisa", "Penyeragaman tarif bea, deregulasi perbankan, dan privatisasi", "Sentralisasi modal, pengendalian upah, dan pembatasan impor", "Pengawasan bursa saham, penjaminan kredit, dan cetak uang", "Realokasi sumber daya, redistribusi pendapatan, dan stabilisasi ekonomi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Fungsi Negara Menurut Richard Musgrave</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam buku The Theory of Public Finance, Richard Musgrave merumuskan tiga fungsi utama keuangan publik negara, yakni: <strong>realokasi sumber daya ekonomi, redistribusi pendapatan, dan stabilisasi ekonomi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Teori keuangan publik modern Richard Musgrave menetapkan tiga cabang fungsi fundamental pemerintah: cabang alokasi untuk menyediakan barang publik, cabang distribusi untuk mewujudkan keadilan pendapatan, dan cabang stabilisasi untuk memelihara kesempatan kerja serta stabilitas harga. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Realokasi sumber daya, redistribusi pendapatan, dan stabilisasi ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Monopoli dan proteksionisme merupakan kebijakan perdagangan merkantilisme klasik.</li><li><strong>B</strong>: Deregulasi perbankan dan privatisasi adalah strategi reformasi pasar finansial.</li><li><strong>C</strong>: Pengendalian upah dan modal terpusat merupakan ciri sistem ekonomi komando.</li><li><strong>D</strong>: Pengawasan bursa dan cetak uang merupakan instrumen sektor finansial dan moneter.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>The Theory of Public Finance, Musgrave menyatakan bahwa tugas dan fungsi negara meliputi • realokasi sumber-sumber daya ekonomi, redistribusi pendapatan, dan stabilisasi ekonomi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan fungsi realokasi sumber daya ekonomi oleh negara ditujukan untuk mengoptimalkan sumber daya melalui...", "options": ["Penyediaan barang publik dan pengaturan eksternalitas", "Penyaluran kredit berbunga rendah kepada korporasi besar", "Penetapan suku bunga acuan simpanan perbankan nasional", "Pengendalian lalu lintas devisa modal pasar keuangan", "Pembatasan kuota produksi industri manufaktur swasta"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Realokasi Sumber Daya Ekonomi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Realokasi sumber daya ekonomi</strong> dilakukan dengan memanfaatkan sumber daya terbatas secara optimal, termasuk <strong>penyediaan barang publik dan pengaturan eksternalitas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Mekanisme pasar sering kali gagal menyediakan barang publik (public goods) seperti jalan raya, pertahanan, dan sanitasi, serta tidak mampu mengatasi dampak eksternalitas negatif secara mandiri. Fungsi alokasi negara hadir untuk mengarahkan sumber daya nasional guna memenuhi kebutuhan barang publik tersebut. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Penyediaan barang publik dan pengaturan eksternalitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penyaluran kredit korporasi merupakan aktivitas bisnis intermediasi perbankan komersial.</li><li><strong>C</strong>: Penetapan suku bunga simpanan adalah instrumen kebijakan moneter bank sentral.</li><li><strong>D</strong>: Pengendalian devisa modal terkait dengan neraca pembayaran luar negeri moneter.</li><li><strong>E</strong>: Pembatasan kuota produksi swasta merupakan intervensi pasar dalam regulasi industri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Realokasi sumber daya ekonomi dilakukan • penyediaan barang publik dan pengaturan eksternalitas</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Fungsi redistribusi pendapatan yang diselenggarakan negara melalui kebijakan fiskal bertujuan untuk menciptakan keadilan melalui...", "options": ["Penjualan surat utang negara di bursa efek internasional", "Sistem perpajakan dan pengeluaran transfer pembayaran", "Pemberian insentif pembebasan bea bagi investor besar", "Pengendalian saldo cadangan valuta pada bank sentral", "Pembentukan badan usaha baru milik pemerintah daerah"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Fungsi Redistribusi Pendapatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Redistribusi pendapatan</strong> bertujuan untuk menciptakan keadilan sosial yang dilaksanakan <strong>melalui sistem perpajakan dan transfer pembayaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Fungsi redistribusi bekerja dengan memungut pajak dari kelompok masyarakat yang berpendapatan lebih tinggi, kemudian menyalurkannya kembali kepada kelompok masyarakat berpendapatan rendah dalam bentuk transfer pembayaran, subsidi, dan bantuan sosial demi mengurangi kesenjangan. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Sistem perpajakan dan pengeluaran transfer pembayaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penjualan obligasi negara adalah instrumen pembiayaan utang penutup defisit APBN.</li><li><strong>C</strong>: Pembebasan bea bagi pemodal besar merupakan insentif investasi modal industri.</li><li><strong>D</strong>: Cadangan valuta asing dikelola oleh otoritas moneter untuk stabilitas nilai tukar.</li><li><strong>E</strong>: Pembentukan BUMD merupakan pengelolaan kekayaan daerah dalam ranah investasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Redistribusi pendapatan bertujuan untuk menciptakan keadilan sosial • sistem perpajakan dan transfer pembayaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Fungsi stabilisasi ekonomi dilaksanakan oleh pemerintah guna menjaga kestabilan makroekonomi, terutama dalam rangka...", "options": ["Mengatur margin laba perdagangan komoditas ekspor", "Menentukan komisi transaksi pada pasar modal nasional", "Mengatasi inflasi, deflasi, dan tingkat pengangguran", "Menetapkan tarif sewa sarana pergudangan logistik", "Menyeragamkan standar harga properti perumahan umum"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Fungsi Stabilisasi Ekonomi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Stabilisasi ekonomi</strong> dilakukan pemerintah untuk menjaga kestabilan makroekonomi, seperti <strong>mengatasi inflasi, deflasi, dan pengangguran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Tujuan pokok dari fungsi stabilisasi adalah memelihara fundamental perekonomian agar berada pada tingkat kesempatan kerja penuh (full employment) dan tingkat harga yang stabil. Intervensi fiskal digunakan untuk mencegah terjadinya lonjakan inflasi tinggi maupun deflasi berkepanjangan yang memicu resesi. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Mengatasi inflasi, deflasi, dan tingkat pengangguran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Margin laba perdagangan komoditas ditentukan oleh mekanisme persaingan pasar bebas.</li><li><strong>B</strong>: Komisi transaksi bursa efek diatur oleh bursa efek dan otoritas pengawas pasar modal.</li><li><strong>D</strong>: Tarif sewa pergudangan logistik merupakan kesepakatan komersial antarpelaku usaha.</li><li><strong>E</strong>: Harga properti perumahan dibentuk oleh interaksi penawaran dan permintaan pasar properti.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Stabilisasi ekonomi dilakukan untuk menjaga kestabilan makroekonomi • mengatasi inflasi, deflasi, dan pengangguran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pemerintah mendorong redistribusi pendapatan melalui APBN dengan tujuan utama agar tidak terjadi...", "options": ["Fluktuasi indeks harga saham gabungan di bursa efek", "Persaingan usaha antarpelaku ekonomi di pasar terbuka", "Perubahan tren konsumsi barang sekunder masyarakat", "Kesenjangan mencolok antara kelompok kaya dan miskin", "Penurunan penyaluran pembiayaan perumahan nasional"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pencegahan Kesenjangan melalui APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah mendorong terjadinya redistribusi pendapatan <strong>agar tidak terjadi kesenjangan antara golongan masyarakat kaya dan golongan masyarakat miskin secara mencolok</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Ketimpangan distribusi pendapatan yang ekstrem dapat memicu kecemburuan sosial, kemiskinan struktural, dan instabilitas nasional. Melalui instrumen APBN, pemerintah melakukan pemerataan pendapatan agar jurang pemisah antara kelompok kaya dan kelompok miskin dapat dipersempit secara berkeadilan. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Kesenjangan mencolok antara kelompok kaya dan miskin</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Fluktuasi harga saham mencerminkan dinamika sentimen investasi pasar modal harian.</li><li><strong>B</strong>: Persaingan usaha yang sehat justru dilindungi dan didorong dalam sistem ekonomi nasional.</li><li><strong>C</strong>: Perubahan tren konsumsi merupakan preferensi pasar individu yang bersifat wajar.</li><li><strong>E</strong>: Penyaluran kredit perumahan merupakan indikator intermediasi industri perbankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>redistribusi pendapatan • agar tidak terjadi kesenjangan antara golongan masyarakat kaya dan golongan masyarakat miskin</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Penerapan prinsip ability to pay dalam kebijakan perpajakan pemerintah diwujudkan melalui pemungutan pajak yang...", "options": ["Ditetapkan dengan persentase flat sama bagi seluruh warga", "Menggunakan besaran nominal seragam tanpa membedakan laba", "Didasarkan pada frekuensi transaksi belanja barang impor", "Diberlakukan khusus kepada sektor industri komoditas primer", "Lebih besar kepada kelompok masyarakat yang lebih mampu"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Prinsip Ability to Pay</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan prinsip <strong>ability to pay principle</strong> menuntut pemerintah <strong>mengenakan pajak yang lebih banyak kepada kelompok masyarakat yang lebih mampu</strong> demi keadilan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Prinsip kemampuan membayar (ability to pay) menegaskan bahwa beban pungutan pajak harus proporsional dengan kapasitas ekonomi wajib pajak. Kelompok yang memiliki kekayaan dan penghasilan lebih tinggi dikenakan tarif pajak progresif lebih besar guna membiayai penyediaan layanan bagi masyarakat luas. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Lebih besar kepada kelompok masyarakat yang lebih mampu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tarif flat tunggal tidak mencerminkan prinsip keadilan daya pikul ekonomi progresif.</li><li><strong>B</strong>: Besaran seragam membebani masyarakat berpenghasilan rendah secara tidak adil.</li><li><strong>C</strong>: Frekuensi transaksi impor tidak menggambarkan total kapasitas kemampuan finansial.</li><li><strong>D</strong>: Pajak dipungut atas seluruh objek penghasilan dan bukan terbatas komoditas primer.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mengenakan pajak yang lebih banyak kepada kelompok masyarakat yang lebih mampu • ability to pay principle</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Kebijakan pengeluaran pemerintah yang berpihak kepada masyarakat berpenghasilan rendah (pro-poor) diterapkan dalam bentuk...", "options": ["Alokasi bantuan sosial dan subsidi dasar tepat sasaran", "Pembangunan infrastruktur konektivitas kawasan logistik", "Penyaluran insentif belanja modal industri manufaktur", "Pengadaan peralatan pertahanan dan keamanan nasional", "Peningkatan kapasitas riset serta inovasi teknologi"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alokasi Belanja Pro-Poor</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengeluaran belanja negara yang menganut prinsip <strong>pro-poor</strong> diwujudkan dalam program belanja yang <strong>berpihak kepada masyarakat yang kurang mampu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Kebijakan fiskal pro-poor mengarahkan porsi anggaran belanja negara untuk melindungi kelompok rentan melalui bantuan sosial tunai, jaminan kesehatan masyarakat, subsidi kebutuhan pokok, serta beasiswa pendidikan agar mereka dapat meningkatkan kualitas hidupnya. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Alokasi bantuan sosial dan subsidi dasar tepat sasaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Konektivitas kawasan logistik adalah belanja modal infrastruktur pertumbuhan ekonomi umum.</li><li><strong>C</strong>: Insentif belanja modal manufaktur merupakan stimulus bagi pengembangan dunia industri.</li><li><strong>D</strong>: Pengadaan peralatan pertahanan merupakan belanja fungsi pertahanan kedaulatan negara.</li><li><strong>E</strong>: Kapasitas riset dan inovasi merupakan belanja pengembangan ilmu pengetahuan nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pengeluaran/belanja negara yang berpihak kepada masyarakat yang kurang mampu • pro poor</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Apabila sumber daya yang ada di masyarakat tidak terdistribusikan secara adil dan merata, dampak yang timbul adalah...", "options": ["Terjadinya peningkatan cadangan devisa pemerintah secara berlebih", "Kesenjangan sosial yang memicu ketidakstabilan sosial ekonomi", "Pertumbuhan pesat pada sektor industri pengolahan primer", "Percepatan kemandirian pembiayaan fiskal daerah tertinggal", "Penurunan beban utang pokok pemerintah jangka panjang"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Disparitas Distribusi Sumber Daya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Apabila sumber daya di masyarakat tidak terdistribusikan secara optimal, akan menimbulkan ketidakseimbangan perekonomian seperti <strong>kesenjangan sosial dan ketidakstabilan pasar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Distribusi sumber daya yang timpang menyebabkan terkonsentrasinya kekayaan pada segelintir kelompok elit, sementara mayoritas rakyat kehilangan akses ekonomi. Kondisi ini memicu ketegangan sosial politik, menurunkan permintaan domestik, dan merusak stabilitas pasar secara luas. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Kesenjangan sosial yang memicu ketidakstabilan sosial ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ketimpangan distribusi domestik tidak secara otomatis menambah cadangan devisa negara.</li><li><strong>C</strong>: Ketidakstabilan pasar justru menghambat ekspansi industri manufaktur pengolahan.</li><li><strong>D</strong>: Daerah tertinggal akan semakin terpuruk dan makin bergantung pada bantuan transfer pusat.</li><li><strong>E</strong>: Disparitas ekonomi yang tinggi sering kali menuntut pemerintah menarik utang lebih besar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sumber daya yang ada di masyarakat tidak terdistribusikan secara optimal • kesenjangan sosial dan ketidakstabilan pasar</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Undang-Undang Nomor 17 Tahun 2003, pemegang kekuasaan umum pengelolaan keuangan negara adalah...", "options": ["Menteri Keuangan selaku bendahara umum negara", "Ketua Badan Pemeriksa Keuangan Republik Indonesia", "Presiden sebagai bagian dari kekuasaan pemerintahan", "Pimpinan Dewan Perwakilan Rakyat Republik Indonesia", "Gubernur Bank Indonesia selaku pemegang otoritas moneter"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemegang Kekuasaan Umum Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan Pasal 6 UU Nomor 17 Tahun 2003, <strong>Presiden adalah pemegang kekuasaan umum pengelolaan keuangan negara sebagai bagian dari kekuasaan pemerintahan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Dalam sistem pemerintahan presidensial, Presiden memegang kekuasaan eksekutif tertinggi, yang di dalamnya mencakup kekuasaan umum atas pengelolaan keuangan negara. Pejabat lain seperti menteri dan kepala daerah menjalankan wewenang pengelolaan atas dasar pendelegasian atau penyerahan kekuasaan dari Presiden. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Presiden sebagai bagian dari kekuasaan pemerintahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menteri Keuangan mengemban kekuasaan yang dikuasakan oleh Presiden selaku pengelola fiskal.</li><li><strong>B</strong>: BPK RI adalah lembaga eksternal pemeriksa keuangan yang bebas dan mandiri dari eksekutif.</li><li><strong>D</strong>: DPR merupakan cabang legislatif pemegang hak persetujuan dan pengawasan anggaran.</li><li><strong>E</strong>: Gubernur BI memimpin bank sentral independen di luar cabang kekuasaan pemerintahan umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Presiden adalah pemegang kekuasaan umum pengelolaan keuangan negara • sebagai bagian dari kekuasaan pemerintahan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pelimpahan kekuasaan Presiden kepada Menteri Keuangan dijalankan dalam kapasitas mandat selaku...", "options": ["Pengguna anggaran bagi seluruh kementerian negara teknis", "Penanggung jawab tunggal penetapan kebijakan suku bunga", "Pemeriksa independen atas laporan keuangan pemerintah", "Pengelola fiskal dan wakil pemerintah dalam kekayaan dipisahkan", "Kepala pelaksana teknis pembangunan infrastruktur nasional"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mandat Kuasa Presiden kepada Menteri Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekuasaan Presiden di bidang keuangan dikuasakan kepada Menteri Keuangan <strong>selaku Pengelola Fiskal dan Wakil Pemerintah dalam kepemilikan kekayaan Negara yang dipisahkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Menteri Keuangan menerima kuasa mandat ganda dari Presiden: pertama, sebagai pengelola fiskal nasional yang mengatur kebijakan perbendaharaan dan APBN; kedua, sebagai wakil pemerintah yang memegang hak kepemilikan modal negara pada korporasi BUMN. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Pengelola fiskal dan wakil pemerintah dalam kekayaan dipisahkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengguna anggaran kementerian teknis diemban oleh masing-masing menteri teknis terkait.</li><li><strong>B</strong>: Kebijakan suku bunga merupakan wewenang independen dewan gubernur bank sentral.</li><li><strong>C</strong>: Pemeriksaan independen laporan keuangan adalah wewenang Badan Pemeriksa Keuangan.</li><li><strong>E</strong>: Pelaksana fisik proyek infrastruktur dijalankan oleh kementerian teknis sektoral.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>selaku Pengelola Fiskal dan Wakil Pemerintah dalam kepemilikan kekayaan Negara yang dipisahkan • dikuasakan kepada Menteri Keuangan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kekuasaan pengelolaan keuangan negara yang dilimpahkan Presiden kepada menteri atau pimpinan lembaga berkedudukan selaku...", "options": ["Pengelola fiskal pembina perbendaharaan nasional", "Wakil pemerintah dalam kepemilikan modal daerah", "Pemeriksa internal kelayakan audit eksternal BPK", "Pengatur likuiditas lalu lintas pembayaran perbankan", "Pengguna Anggaran atau Pengguna Barang kementerian"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mandat Kuasa kepada Pimpinan Lembaga</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekuasaan pengelolaan keuangan negara dikuasakan kepada masing-masing menteri/pimpinan lembaga <strong>selaku Pengguna Anggaran/Pengguna Barang kementerian negara/lembaga</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Para menteri teknis dan kepala lembaga negara menerima mandat kuasa dari Presiden sebagai Pengguna Anggaran (PA) dan Pengguna Barang (PB). Mandat ini memberi wewenang untuk merencanakan, melaksanakan, dan mempertanggungjawabkan belanja kementerian sesuai bidang tugasnya masing-masing. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Pengguna Anggaran atau Pengguna Barang kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengelola fiskal nasional secara eksklusif dikuasakan kepada Menteri Keuangan.</li><li><strong>B</strong>: Kepemilikan modal daerah diwakili oleh kepala pemerintahan daerah (gubernur/bupati).</li><li><strong>C</strong>: Pemeriksaan kelayakan laporan audit eksternal bukan wewenang pimpinan kementerian teknis.</li><li><strong>D</strong>: Pengaturan likuiditas sistem pembayaran merupakan wewenang moneter Bank Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 13) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>selaku Pengguna Anggaran/Pengguna Barang • dikuasakan kepada masing-masing menteri/pimpinan lembaga</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kekuasaan pengelolaan keuangan negara diserahkan oleh Presiden kepada gubernur, bupati, atau walikota dalam kapasitas selaku...", "options": ["Kepala pemerintahan daerah untuk mengelola keuangan daerah", "Bendahara umum negara pengelola kas perbendaharaan pusat", "Pengguna anggaran kementerian teknis urusan dalam negeri", "Pemeriksa independen kepatuhan belanja perbankan daerah", "Kuasa pengelola moneter penstabil nilai tukar rupiah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyerahan Pengelolaan Keuangan Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekuasaan Presiden diserahkan kepada gubernur/bupati/walikota <strong>selaku kepala Pemerintahan daerah untuk mengelola keuangan daerah dan mewakili kepemilikan kekayaan daerah yang dipisahkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Dalam kerangka desentralisasi fiskal, Presiden menyerahkan kekuasaan pengelolaan keuangan daerah kepada kepala daerah otonom. Gubernur, bupati, atau walikota bertindak sebagai pemegang kekuasaan pengelolaan keuangan daerah yang bertanggung jawab atas APBD dan BUMD di wilayahnya. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Kepala pemerintahan daerah untuk mengelola keuangan daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bendahara Umum Negara (BUN) dijabat oleh Menteri Keuangan Republik Indonesia.</li><li><strong>C</strong>: Kepala daerah bukan pengguna anggaran kementerian teknis urusan dalam negeri.</li><li><strong>D</strong>: Pemeriksa independen laporan keuangan daerah dilaksanakan oleh BPK RI Perwakilan.</li><li><strong>E</strong>: Kewenangan moneter tidak diserahkan ke daerah melainkan berada pada Bank Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Penyerahan kepada gubernur/bupati/walikota • untuk mengelola keuangan daerah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Undang-Undang Nomor 17 Tahun 2003 menegaskan bahwa kekuasaan pemerintah di bidang fiskal tidak mencakup...", "options": ["Pengelolaan penerimaan perpajakan dan bea cukai pusat", "Kewenangan di bidang moneter demi stabilitas nilai rupiah", "Pengalokasian belanja operasional kementerian teknis", "Pengelolaan pembiayaan utang dan penerbitan obligasi negara", "Penyusunan nota keuangan dan rancangan undang-undang APBN"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemisahan Bidang Fiskal dan Kewenangan Moneter</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Undang-Undang Keuangan Negara menegaskan bahwa <strong>kekuasaan di bidang fiskal tidak termasuk kewenangan di bidang moneter</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Pemisahan antara kebijakan fiskal dan kebijakan moneter ditegakkan guna mencegah konflik kepentingan dalam pencetakan uang dan pembiayaan anggaran. Pemerintah memegang kekuasaan fiskal (APBN), sedangkan kewenangan moneter diserahkan sepenuhnya kepada bank sentral yang independen. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Kewenangan di bidang moneter demi stabilitas nilai rupiah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penerimaan pajak dan bea cukai adalah komponen utama kekuasaan fiskal pemerintah.</li><li><strong>C</strong>: Alokasi belanja kementerian teknis merupakan wewenang pokok eksekutif di bidang fiskal.</li><li><strong>D</strong>: Pengelolaan utang dan obligasi negara adalah instrumen pembiayaan fiskal Kementerian Keuangan.</li><li><strong>E</strong>: Penyusunan nota keuangan APBN merupakan mandat konstitusional pemerintah eksekutif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kekuasaan di bidang fiskal tidak termasuk • kewenangan di bidang moneter</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penetapan dan pelaksanaan kebijakan moneter serta penjagaan kelancaran sistem pembayaran dilaksanakan oleh...", "options": ["Kementerian Koordinator Bidang Perekonomian", "Lembaga Penjamin Simpanan Perbankan Nasional", "Bank Sentral, yakni Bank Indonesia", "Badan Pengawasan Keuangan dan Pembangunan", "Otoritas Jasa Keuangan Bidang Perasuransian"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Otoritas Pelaksana Kebijakan Moneter</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Untuk mencapai stabilitas nilai rupiah, penetapan dan pelaksanaan kebijakan moneter serta pengaturan kelancaran sistem pembayaran <strong>dilakukan oleh Bank Sentral, yakni Bank Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Mandat konstitusional membebankan pengelolaan stabilitas nilai rupiah kepada bank sentral yang bebas dari campur tangan pemerintah. Bank Indonesia bertindak sebagai otoritas moneter tunggal yang berwenang mengatur suku bunga, cadangan wajib, dan kelancaran sistem lalu lintas pembayaran nasional. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Bank Sentral, yakni Bank Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kemenko Perekonomian mengoordinasikan kebijakan antarkementerian dalam ranah fiskal dan riil.</li><li><strong>B</strong>: LPS bertugas menjamin dana simpanan nasabah dan menangani resolusi bank gagal.</li><li><strong>D</strong>: BPKP menjalankan fungsi audit pengawasan keuangan intern pemerintah.</li><li><strong>E</strong>: OJK mengawasi tata kelola kelembagaan dan produk industri jasa keuangan nonbank.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pelaksanaan kebijakan moneter serta mengatur dan menjaga kelancaran sistem pembayaran • Bank Sentral, yakni Bank Indonesia</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menteri Keuangan pada hakikatnya bertindak sebagai Chief Financial Officer (CFO) pemerintah yang berwenang atas...", "options": ["Pelaksanaan teknis pembangunan proyek pelabuhan laut", "Penetapan regulasi peradilan tata usaha niaga negara", "Pengawasan operasional kedinasan aparatur sipil daerah", "Pengelolaan aset dan kewajiban negara secara nasional", "Pengesahan operasional badan penyiaran publik daerah"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Menteri Keuangan sebagai Chief Financial Officer</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menteri Keuangan pada hakikatnya adalah <strong>Chief Financial Officer (CFO)</strong> yang berwenang dan bertanggung jawab atas <strong>pengelolaan aset dan kewajiban negara secara nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Sebagai CFO pemerintah, Menteri Keuangan mengendalikan neraca keuangan negara secara makro. Tanggung jawab ini mencakup optimalisasi penerimaan kas, pembiayaan utang, pengelolaan kas umum negara, penatausahaan aset barang milik negara, serta penjagaan kesinambungan fiskal jangka panjang. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Pengelolaan aset dan kewajiban negara secara nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pelaksanaan teknis pelabuhan laut adalah wewenang kementerian yang membidangi perhubungan.</li><li><strong>B</strong>: Regulasi peradilan merupakan wewenang cabang yudikatif pada Mahkamah Agung.</li><li><strong>C</strong>: Pengawasan operasional aparatur daerah merupakan wewenang pemerintah daerah setempat.</li><li><strong>E</strong>: Perizinan penyiaran adalah wewenang kementerian bidang komunikasi dan komisi penyiaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Chief Financial Officer (CFO) • pengelolaan aset dan kewajiban negara secara nasional</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Para menteri teknis dan pimpinan lembaga pada hakikatnya berkedudukan sebagai Chief Operational Officer (COO) yang bertanggung jawab atas...", "options": ["Pengelolaan utang piutang negara secara menyeluruh", "Penerbitan surat utang negara di pasar modal global", "Pengendalian saldo rekening kas umum negara pusat", "Pengaturan portofolio cadangan devisa internasional", "Penyelenggaraan pemerintahan sesuai bidang tugas masing-masing"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pimpinan Lembaga sebagai Chief Operational Officer</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Para menteri dan pimpinan lembaga negara pada hakikatnya adalah <strong>Chief Operational Officer (COO)</strong> yang bertanggung jawab atas <strong>penyelenggaraan pemerintahan sesuai bidang tugas dan fungsi masing-masing</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Kedudukan menteri teknis sebagai COO menempatkan mereka sebagai manajer operasional program sektoral. Mereka bertanggung jawab mengeksekusi alokasi anggaran belanja menjadi keluaran (output) nyata dan hasil (outcome) pelayanan publik yang bermanfaat bagi masyarakat di bidang masing-masing. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Penyelenggaraan pemerintahan sesuai bidang tugas masing-masing</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengelolaan utang piutang nasional adalah mandat CFO pada Kementerian Keuangan.</li><li><strong>B</strong>: Penerbitan surat utang negara adalah wewenang Menteri Keuangan selaku pengelola kas.</li><li><strong>C</strong>: Rekening kas umum negara dikelola oleh Menteri Keuangan selaku Bendahara Umum Negara.</li><li><strong>D</strong>: Cadangan devisa dikelola oleh Bank Indonesia dalam rangka kebijakan moneter.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Chief Operational Officer (COO) • penyelenggaraan pemerintahan sesuai bidang tugas dan fungsi masing-masing</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pembagian kewenangan yang jelas antara Menteri Keuangan dan menteri teknis dirancang untuk memberikan jaminan...", "options": ["Terlaksananya mekanisme saling uji dan kejelasan akuntabilitas", "Sentralisasi seluruh kewenangan teknis pada bendahara umum", "Penyeragaman rencana kebutuhan belanja antar-kementerian", "Fleksibilitas pergeseran alokasi pagu tanpa batasan output", "Pembentukan unit kas operasional mandiri di setiap satker"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mekanisme Saling Uji (Check and Balance)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembagian wewenang yang jelas antara Menkeu dan menteri teknis memberikan jaminan <strong>terlaksananya mekanisme saling uji (check and balance)</strong> serta <strong>kejelasan akuntabilitas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Pemisahan peran antara pengelola fiskal/BUN (Menkeu) dan pengguna anggaran (menteri teknis) menjamin bahwa pihak yang meminta pencairan dana berbeda dengan pihak yang menguji keabsahan perintah pembayaran. Mekanisme saling uji ini mencegah penyalahgunaan kekuasaan anggaran. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Terlaksananya mekanisme saling uji dan kejelasan akuntabilitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Undang-undang justru mendesentralisasikan kewenangan teknis kepada menteri sektoral.</li><li><strong>C</strong>: Rencana kebutuhan belanja disusun secara spesifik sesuai karakteristik kementerian masing-masing.</li><li><strong>D</strong>: Pergeseran pagu belanja tetap wajib mematuhi koridor target output yang telah disahkan.</li><li><strong>E</strong>: Satker tidak diperkenankan membentuk rekening kas mandiri di luar sistem kas umum negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mekanisme saling uji (check and balance) • kejelasan akuntabilitas Menteri Keuangan sebagai Bendahara Umum Negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam arsitektur perbendaharaan negara, kejelasan akuntabilitas ditegakkan melalui penetapan kedudukan...", "options": ["Menteri teknis sebagai BUN dan Menteri Keuangan sebagai Pengguna Barang", "Menteri Keuangan sebagai BUN dan menteri teknis sebagai Pengguna Anggaran", "Gubernur BI sebagai BUN dan Presiden sebagai penanggung jawab kasir", "Ketua BPK sebagai BUN dan kementerian teknis sebagai pengawas internal", "Kepala Bappenas sebagai BUN dan Menkeu sebagai perencana sektoral"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Fungsional BUN dan Pengguna Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kejelasan akuntabilitas ditegakkan melalui posisi <strong>Menteri Keuangan sebagai Bendahara Umum Negara (BUN)</strong> dan <strong>Menteri Teknis sebagai Pengguna Anggaran (PA)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Struktur hukum perbendaharaan membagi tugas perbendaharaan secara profesional: Menteri Keuangan bertanggung jawab atas tersedianya kas dan keabsahan pencairan selaku BUN, sedangkan menteri teknis bertanggung jawab atas manfaat operasional belanja selaku Pengguna Anggaran. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Menteri Keuangan sebagai BUN dan menteri teknis sebagai Pengguna Anggaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Terbalik, Menteri Keuangan bertindak sebagai BUN dan menteri teknis adalah Pengguna Barang/Anggaran.</li><li><strong>C</strong>: Gubernur BI adalah otoritas moneter independen dan bukan Bendahara Umum Negara.</li><li><strong>D</strong>: Ketua BPK memimpin lembaga audit eksternal dan dilarang merangkap fungsi bendahara kas.</li><li><strong>E</strong>: Kepala Bappenas berkedudukan sebagai perencana makro dan bukan pemegang kas umum negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menteri Keuangan sebagai Bendahara Umum Negara • Menteri Teknis sebagai Pengguna Anggaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pembagian kewenangan pengelolaan keuangan memberikan fleksibilitas bagi menteri teknis dengan tujuan utama...", "options": ["Menyesuaikan standar akuntansi pemerintahan secara berkala", "Melaksanakan fungsi bendahara umum negara di tingkat satker", "Optimalisasi kinerja kementerian untuk menghasilkan output yang ditetapkan", "Menetapkan kebijakan moneter sektoral sesuai dinamika pasar", "Mengalihkan aset barang milik negara kepada pihak perbankan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fleksibilitas Pengelolaan Anggaran Kementerian Teknis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemberian fleksibilitas kepada menteri teknis bertujuan untuk <strong>optimalisasi kinerja kementeriannya untuk menghasilkan output yang telah ditetapkan</strong> secara efisien dan efektif.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Menteri teknis diberikan keleluasaan mengelola anggarannya agar eksekusi program tidak terhambat oleh birokrasi perizinan kas yang kaku. Fleksibilitas ini berfokus pada pendekatan anggaran berbasis kinerja, di mana manajer operasional diberi kebebasan teknis demi tercapainya target keluaran pelayanan publik. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Optimalisasi kinerja kementerian untuk menghasilkan output yang ditetapkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Standar akuntansi pemerintahan ditetapkan oleh komite standar independen secara nasional.</li><li><strong>B</strong>: Fungsi bendahara umum negara tetap menjadi kewenangan eksklusif Menteri Keuangan.</li><li><strong>D</strong>: Kebijakan moneter berada di luar lingkup kewenangan kementerian teknis pemerintahan.</li><li><strong>E</strong>: Pemindahtanganan barang milik negara wajib melalui prosedur perizinan perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>fleksibilitas bagi menteri teknis, sebagai pengguna anggaran, untuk mengatur penggunaan anggaran • optimalisasi kinerja kementeriannya untuk menghasilkan output yang telah ditetapkan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Menteri teknis diberikan wewenang mengatur penggunaan anggaran kementeriannya karena pihak kementerian teknis...", "options": ["Bertanggung jawab langsung atas penarikan pinjaman luar negeri", "Memiliki wewenang mengesahkan undang-undang anggaran tahunan", "Berwenang menetapkan proyeksi makroekonomi dan penerimaan pajak", "Paling memahami operasional kebijakan sektor yang menjadi bidangnya", "Memegang kendali tunggal atas pengelolaan cadangan kas negara"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alasan Pemberian Wewenang Pengguna Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wewenang operasional diberikan kepada kementerian teknis <strong>karena kementerian teknis yang paling memahami operasional kebijakan sektor-sektor yang menjadi bidangnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Kementerian teknis memiliki kompetensi substansial mengenai kondisi lapangan, kendala teknis, dan kebutuhan riil masyarakat pada sektornya masing-masing. Oleh karena itu, penyerahan wewenang penggunaan anggaran kepada menteri teknis menjamin alokasi belanja dieksekusi secara tepat guna dan kontekstual. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Paling memahami operasional kebijakan sektor yang menjadi bidangnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penarikan pinjaman luar negeri merupakan kewenangan pembiayaan Kementerian Keuangan.</li><li><strong>B</strong>: Pengesahan undang-undang anggaran tahunan adalah wewenang bersama DPR dan Presiden.</li><li><strong>C</strong>: Proyeksi makro dan pajak disusun oleh Kementerian Keuangan bersama Bappenas.</li><li><strong>E</strong>: Pengelolaan kas umum negara dikendalikan oleh Bendahara Umum Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kementerian teknis yang paling memahami • operasional kebijakan sektor-sektor yang menjadi bidangnya</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Bentuk pelaksanaan kekuasaan umum pengelolaan keuangan negara mencakup penyerahan mandat berikut, KECUALI...", "options": ["Penguasaan pengelolaan fiskal kepada Menteri Keuangan selaku CFO", "Pelimpahan status pengguna anggaran kepada pimpinan lembaga", "Penyerahan pengelolaan keuangan daerah kepada gubernur atau bupati", "Kewenangan mewakili pemerintah dalam kekayaan yang dipisahkan", "Pelimpahan kewenangan moneter kepada menteri koordinator teknis"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembedaan Mandat Kekuasaan Keuangan (Kecuali)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekuasaan di bidang fiskal <strong>tidak termasuk kewenangan di bidang moneter</strong>, sehingga kewenangan moneter tidak dilimpahkan kepada menteri mana pun melainkan diserahkan kepada Bank Indonesia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Undang-Undang Nomor 17 Tahun 2003 secara tegas memisahkan kekuasaan moneter dari kekuasaan eksekutif pemerintah. Kewenangan moneter tidak boleh dilimpahkan kepada menteri koordinator maupun menteri teknis lainnya demi menjaga independensi stabilitas nilai rupiah oleh bank sentral. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Pelimpahan kewenangan moneter kepada menteri koordinator teknis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan mandat resmi yang dikuasakan Presiden kepada Menteri Keuangan.</li><li><strong>B</strong>: Merupakan mandat kuasa Presiden kepada para menteri dan pimpinan lembaga.</li><li><strong>C</strong>: Merupakan mandat desentralisasi yang diserahkan Presiden kepada kepala daerah.</li><li><strong>D</strong>: Merupakan wewenang yang dikuasakan kepada Menkeu dan kepala daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kekuasaan di bidang fiskal tidak termasuk kewenangan di bidang moneter • Dikuasakan kepada Menteri Keuangan • Diserahkan kepada gubernur/bupati/walikota</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Penerapan mekanisme saling uji (check and balance) di lingkungan satker tercermin ketika pejabat pengguna anggaran...", "options": ["Menerbitkan SPM yang diuji keabsahannya oleh KPPN selaku kuasa BUN", "Mengajukan nota debet langsung kepada bank sentral tanpa verifikasi", "Mengesahkan laporan pertanggungjawaban tanpa telaah pembukuan satker", "Memindahkan alokasi belanja modal menjadi belanja barang secara bebas", "Menyalurkan pembayaran pihak ketiga melalui rekening perantara daerah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Saling Uji dalam Pencairan Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerapan prinsip <strong>saling uji (check and balance)</strong> tercermin dari pemisahan fungsi: satker menerbitkan Surat Perintah Membayar (SPM) yang kemudian <strong>diuji keabsahannya oleh KPPN selaku Kuasa BUN sebelum kas dicairkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Mekanisme check and balance perbendaharaan menjamin pengawasan berjenjang. Pejabat Pengguna Anggaran menguji kebenaran material tagihan pihak ketiga, sementara KPPN (selaku Kuasa BUN) menguji keabsahan formal dokumen dan ketersediaan pagu dana sebelum menerbitkan SP2D ke bank persepsi. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Menerbitkan SPM yang diuji keabsahannya oleh KPPN selaku kuasa BUN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Satker dilarang menerbitkan nota debet langsung kepada bank sentral.</li><li><strong>C</strong>: Mengesahkan laporan tanpa telaah melanggar tata tertib pertanggungjawaban akuntansi.</li><li><strong>D</strong>: Pergeseran pagu belanja antarmodal dan barang memerlukan revisi DIPA resmi.</li><li><strong>E</strong>: Pembayaran pihak ketiga langsung ditransfer ke rekening penerima tanpa rekening perantara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mekanisme saling uji (check and balance) • Menteri Keuangan sebagai Bendahara Umum Negara dan Menteri Teknis sebagai Pengguna Anggaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam menjalankan kebijakan moneter dan sistem pembayaran, Bank Indonesia memiliki kedudukan yang...", "options": ["Merupakan unit pelaksana teknis kementerian keuangan", "Tunduk pada peraturan perundang-undangan di bidang moneter", "Bertanggung jawab langsung kepada badan pengawas perbankan", "Menjalankan fungsi sebagai bendahara umum kas negara", "Mengelola pendapatan perpajakan dan kepabeanan pusat"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kepatuhan Hukum Bank Sentral</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bank Sentral, yakni Bank Indonesia, melaksanakan kebijakan moneter dan sistem pembayaran dengan <strong>tunduk pada peraturan perundangan di bidang moneter</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Meskipun memiliki status kelembagaan yang independen dari pengaruh kabinet eksekutif, pelaksanaan tugas Bank Indonesia tetap wajib berlandaskan hukum dan tunduk pada ketentuan peraturan perundang-undangan di bidang perbankan sentral dan moneter yang berlaku di Indonesia. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Tunduk pada peraturan perundang-undangan di bidang moneter</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bank Indonesia bukan unit pelaksana teknis di bawah Kementerian Keuangan.</li><li><strong>C</strong>: Bank Indonesia adalah otoritas independen dan bukan bawahan badan pengawas lain.</li><li><strong>D</strong>: Bendahara Umum Negara adalah fungsi yang melekat pada Menteri Keuangan.</li><li><strong>E</strong>: Penerimaan perpajakan dan bea cukai dikelola oleh kementerian keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 14) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Bank Sentral, yakni Bank Indonesia • tunduk pada peraturan perundangan di bidang moneter</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kekuasaan pengelolaan keuangan daerah tingkat operasional dijalankan oleh kepala satuan kerja pengelola keuangan daerah selaku...", "options": ["Auditor internal belanja daerah", "Pemeriksa administrasi kas pemda", "Pejabat pengelola APBD daerah", "Kuasa pemegang kas perbankan", "Pengawas kepatuhan anggaran dinas"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pejabat Pengelola Keuangan Daerah (PPKD)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kekuasaan pengelolaan keuangan daerah dilaksanakan oleh <strong>kepala satuan kerja pengelola keuangan daerah (SKPKD) selaku pejabat pengelola APBD (PPKD)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Dalam struktur penganggaran daerah, kepala satuan kerja pengelola keuangan daerah (seperti Kepala BPKAD) mengemban peran sebagai Pejabat Pengelola Keuangan Daerah (PPKD) yang mengelola kas umum daerah dan menyusun postur APBD daerah secara keseluruhan. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Pejabat pengelola APBD daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Auditor intern laporan belanja daerah dijalankan oleh Inspektorat Daerah.</li><li><strong>B</strong>: Pemeriksaan perpajakan pusat merupakan wewenang direktorat jenderal pada kementerian.</li><li><strong>D</strong>: Kuasa kas perbankan daerah merupakan fungsi manajemen operasional bank daerah.</li><li><strong>E</strong>: Pengawasan sengketa perdata diselesaikan melalui mekanisme lembaga peradilan hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kepala satuan kerja pengelola keuangan daerah • pejabat pengelola APBD</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pejabat yang mengemban mandat selaku pengguna anggaran atau barang di lingkungan dinas pemerintah daerah adalah...", "options": ["Inspektur pembina pengawasan internal daerah", "Ketua komisi anggaran perwakilan rakyat daerah", "Kepala biro administrasi perekonomian daerah", "Kepala satuan kerja perangkat daerah", "Pimpinan sekretariat lembaga koordinasi daerah"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengguna Anggaran dan Barang Tingkat Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengelolaan keuangan daerah dilaksanakan oleh <strong>kepala satuan kerja perangkat daerah (SKPD) selaku pejabat pengguna anggaran/barang daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Para kepala dinas atau kepala badan di lingkungan pemerintah daerah (kepala SKPD) berkedudukan sebagai Pengguna Anggaran (PA) dan Pengguna Barang (PB) di tingkat daerah. Mereka berwenang mengeksekusi DPA dinas dan mempertanggungjawabkan output layanan teknis kedinasan. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Kepala satuan kerja perangkat daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Inspektur daerah mengemban fungsi pengawasan dan audit intern aparatur daerah.</li><li><strong>B</strong>: Ketua komisi anggaran DPRD menjalankan fungsi pengawasan dan persetujuan legislatif.</li><li><strong>C</strong>: Biro administrasi perekonomian adalah unit staf pendukung koordinasi kebijakan daerah.</li><li><strong>E</strong>: Sekretariat lembaga koordinasi bertugas memfasilitasi administrasi rapat koordinasi dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kepala satuan kerja perangkat daerah • pejabat pengguna anggaran/barang daerah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penerapan Asas Tahunan dalam pengelolaan keuangan negara membatasi berlakunya DIPA pada masa waktu...", "options": ["Dua belas bulan terhitung sejak pelantikan pejabat perbendaharaan", "Enam bulan masa sidang anggaran lembaga perwakilan legislatif", "Tiga triwulan operasional dinas sesuai tahun anggaran berjalan", "Lima tahun penuh sesuai periode rencana pembangunan jangka menengah", "Satu tahun anggaran mulai 1 Januari sampai 31 Desember"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Tahunan Pengelolaan Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Tahunan</strong> membatasi berlakunya anggaran untuk satu tahun anggaran, di mana <strong>DIPA berlaku mulai 1 Januari s.d. 31 Desember</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Asas tahunan memberikan batasan waktu legal bagi otorisasi anggaran negara. Seluruh penerimaan dan pengeluaran pemerintah hanya dapat dilakukan dalam batas periode satu tahun kalender fiskal, yaitu terhitung sejak 1 Januari hingga berakhir pada 31 Desember tahun berkenaan. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Satu tahun anggaran mulai 1 Januari sampai 31 Desember</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masa berlaku anggaran terikat pada tahun kalender fiskal dan bukan masa jabatan pejabat.</li><li><strong>B</strong>: Masa sidang legislatif merupakan agenda pembahasan politik anggaran di parlemen.</li><li><strong>C</strong>: Tahun anggaran berjalan mencakup empat triwulan penuh selama dua belas bulan.</li><li><strong>D</strong>: Periode lima tahun merupakan rentang waktu perencanaan strategis RPJMN pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tahunan yang membatasi berlakunya anggaran untuk satu tahun anggaran • 1 Januari s.d. 31 Desember</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Asas Tahunan, seluruh tagihan pembayaran yang timbul pada tahun berkenaan hanya boleh dibebankan pada...", "options": ["Dokumen DIPA resmi tahun anggaran yang bersangkutan", "Rencana kerja strategis jangka menengah kementerian", "Cadangan kas operasional bendahara umum negara", "Proyeksi penerimaan negara bukan pajak tahun depan", "Alokasi pagu belanja tambahan tahun anggaran berjalan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembebanan Tagihan Menurut Asas Tahunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan asas tahunan, <strong>seluruh tagihan pada tahun yang bersangkutan hanya bisa dibebankan pada DIPA tahun yang bersangkutan tersebut</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Disiplin anggaran menuntut kepatuhan atas periode pembebanan kas. Tagihan atas barang dan jasa yang telah diselesaikan pada tahun anggaran berjalan hanya sah dibayarkan dengan membebankannya pada pagu kredit anggaran DIPA tahun yang bersangkutan. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Dokumen DIPA resmi tahun anggaran yang bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Rencana jangka menengah adalah panduan perencanaan arah kebijakan lima tahunan.</li><li><strong>C</strong>: Cadangan kas BUN tidak dapat digunakan tanpa dasar pembebanan akun DIPA satker.</li><li><strong>D</strong>: Proyeksi penerimaan tahun depan belum memiliki kekuatan hukum untuk membiayai tagihan.</li><li><strong>E</strong>: Belanja tambahan hanya dapat digunakan jika telah resmi disahkan dalam revisi DIPA.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Seluruh tagihan pada tahun yang bersangkutan • DIPA tahun yang bersangkutan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Asas Universalitas mewajibkan seluruh pengeluaran dan penerimaan negara dimasukkan dalam APBN, yang menegaskan bahwa...", "options": ["Seluruh belanja wajib dialokasikan melalui mekanisme transfer daerah", "Dana non-budgeter tidak diperkenankan keberadaannya", "Pengeluaran negara dapat dicairkan mendahului pengesahan undang-undang", "Penerimaan perpajakan dikelola terpisah dari pos belanja kementerian", "Pembiayaan utang dicatat pada pembukuan tersendiri di luar postur kas"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Universalitas dan Larangan Dana Non-Budgeter</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Universalitas</strong> menghendaki agar setiap pengeluaran dan penerimaan dimasukkan dalam APBN/APBD, serta menegaskan bahwa <strong>dana non-budgeter tidak diperkenankan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Asas universalitas melarang praktik pengelolaan dana di luar mekanisme anggaran resmi (off-budget). Seluruh uang masuk dan uang keluar yang berkaitan dengan pelaksanaan tugas kedinasan pemerintah wajib dicatat utuh dalam dokumen APBN guna menjamin akuntabilitas publik. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Dana non-budgeter tidak diperkenankan keberadaannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mekanisme belanja negara mencakup belanja kementerian pusat dan transfer ke daerah.</li><li><strong>C</strong>: Pencairan belanja negara dilarang mendahului pengesahan undang-undang APBN.</li><li><strong>D</strong>: Penerimaan pajak dan belanja disatukan dalam postur kas umum negara APBN.</li><li><strong>E</strong>: Pembiayaan utang wajib dicatat secara utuh dan transparan dalam struktur APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Universalitas yang menghendaki agar setiap pengeluaran • Dana non-budgeter tidak diperkenankan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penerapan Asas Kesatuan (unified budget) dalam penyusunan APBN ditandai oleh penyajian seluruh anggaran dalam satu dokumen tanpa...", "options": ["Pemisahan rincian belanja modal dan belanja pegawai", "Pengesahan formal dari badan perwakilan legislatif", "Pemisahan antara anggaran rutin dan anggaran pembangunan", "Penetapan alokasi bagi hasil dan transfer ke daerah", "Penyesuaian standar akuntansi berbasis kas ke akrual"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Kesatuan (Unified Budget)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Kesatuan</strong> menghendaki semua pendapatan dan belanja disajikan dalam <strong>satu dokumen anggaran (unified budget) dan tidak lagi dikelompokkan dalam anggaran rutin dan anggaran pembangunan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Reformasi keuangan negara menghapus sistem dual budgeting yang memisahkan anggaran rutin (DIK) dan anggaran pembangunan (DIP). Melalui asas kesatuan, seluruh jenis belanja disatukan dalam satu dokumen DIPA berbasis klasifikasi ekonomi, organisasi, dan fungsi. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Pemisahan antara anggaran rutin dan anggaran pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja modal dan belanja pegawai tetap dirinci jelas menurut kode akun ekonomi.</li><li><strong>B</strong>: Pengesahan formal parlemen adalah syarat mutlak konstitusional dalam APBN.</li><li><strong>D</strong>: Alokasi transfer ke daerah tetap disajikan terpadu dalam struktur belanja negara.</li><li><strong>E</strong>: Penerapan akrual adalah basis akuntansi pelaporan dan bukan makna asas kesatuan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kesatuan yaitu semua pendapatan dan belanja • unified budget • anggaran rutin dan anggaran pembangunan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Asas Spesialitas dalam pengelolaan keuangan negara mewajibkan bahwa kredit anggaran yang tersedia dalam DIPA harus...", "options": ["Disajikan secara ringkas dalam pos pembiayaan makro", "Dialokasikan dalam bentuk dana cadangan darurat umum", "Dikelompokkan berdasarkan sumber pendapatan yang diterima", "Disajikan secara terperinci dan jelas peruntukan belanjanya", "Digabungkan dalam satu pos belanja agregat nasional"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Spesialitas Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Spesialitas</strong> mewajibkan bahwa <strong>kredit anggaran yang tersedia harus dirinci dan jelas peruntukannya</strong> bagi setiap program kegiatan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Asas spesialitas mencegah pemberian anggaran yang bersifat gelondongan tanpa kejelasan arah penggunaan. Otorisasi belanja dalam DIPA wajib memuat rincian terperinci mengenai jenis belanja, mata anggaran akun, volume target, serta keluaran yang harus dihasilkan oleh satuan kerja. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Disajikan secara terperinci dan jelas peruntukan belanjanya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyajian ringkas bertentangan dengan prinsip transparansi rincian spesialitas.</li><li><strong>B</strong>: Dana darurat umum memiliki pos khusus dan bukan bentuk baku kredit anggaran DIPA.</li><li><strong>C</strong>: Pengelompokan anggaran didasarkan pada klasifikasi fungsi dan bukan asal sumber kas.</li><li><strong>E</strong>: Penggabungan secara agregat menghambat pengendalian dan pengawasan rincian belanja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Spesialitas yaitu kredit anggaran • harus dirinci dan jelas peruntukannya</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Asas Akuntabilitas yang berorientasi pada hasil mewajibkan pertanggungjawaban kegiatan keuangan negara kepada rakyat karena...", "options": ["Seluruh program pembangunan didanai oleh pendapatan hibah", "Kebijakan fiskal diputuskan secara mandiri oleh bendahara negara", "Penerimaan negara dihimpun dari surplus modal badan usaha publik", "Pertanggungjawaban anggaran ditujukan terbatas pada aparat pengawas", "Setiap sen uang negara pada dasarnya adalah uang rakyat"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Akuntabilitas Berorientasi Hasil</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Akuntabilitas berorientasi hasil</strong> menuntut pertanggungjawaban kepada masyarakat selaku pemegang kedaulatan tertinggi, <strong>karena pada dasarnya setiap sen uang negara adalah uang rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Kedaulatan keuangan negara berada di tangan rakyat. Setiap rupiah yang dikelola oleh birokrasi bersumber dari pajak dan pengorbanan masyarakat, sehingga pengelola perbendaharaan wajib membuktikan bahwa anggaran tersebut menghasilkan manfaat nyata bagi kesejahteraan rakyat. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Setiap sen uang negara pada dasarnya adalah uang rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pendapatan hibah hanya merupakan bagian kecil dari keseluruhan penerimaan APBN.</li><li><strong>B</strong>: Kebijakan fiskal disahkan bersama DPR dan bukan diputuskan sepihak oleh bendahara.</li><li><strong>C</strong>: Penerimaan APBN mayoritas bersumber dari penerimaan perpajakan masyarakat.</li><li><strong>D</strong>: Pertanggungjawaban ditujukan kepada rakyat dan parlemen, bukan hanya aparat intern.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Akuntabilitas yang berorientasi pada hasil • setiap sen uang negara adalah uang rakyat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan prinsip akuntabilitas keuangan negara, hasil akhir pengelolaan keuangan harus dipertanggungjawabkan kepada masyarakat selaku...", "options": ["Pemegang kedaulatan tertinggi negara", "Kreditur tunggal pembiayaan surat utang publik", "Pengelola operasional aset barang milik negara", "Mitra pelaksana pengadaan pada perbendaharaan", "Penjamin cadangan likuiditas kas perbankan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rakyat sebagai Pemegang Kedaulatan Tertinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hasil akhir kegiatan pengelolaan keuangan negara harus dipertanggungjawabkan kepada <strong>masyarakat atau rakyat sebagai pemegang kedaulatan tertinggi negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Konstitusi menegaskan bahwa kedaulatan berada di tangan rakyat dan dijalankan menurut undang-undang. Dalam pengelolaan anggaran publik, akuntabilitas tertinggi pemerintah adalah kepada masyarakat yang telah mempercayakan mandat dan sumber daya keuangannya kepada negara. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Pemegang kedaulatan tertinggi negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kreditur surat utang adalah investor pasar modal dan bukan posisi yuridis seluruh rakyat.</li><li><strong>C</strong>: Pengelola operasional aset barang milik negara adalah kementerian dan satker pengguna.</li><li><strong>D</strong>: Mitra lelang pengadaan adalah rekanan penyedia berbadan usaha komersial.</li><li><strong>E</strong>: Penjamin likuiditas perbankan adalah fungsi kelembagaan bank sentral dan LPS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dipertanggungjawabkan kepada masyarakat atau rakyat • pemegang kedaulatan tertinggi negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penerapan Asas Profesionalitas dalam tata kelola keuangan negara menuntut pengelola perbendaharaan mengutamakan...", "options": ["Senioritas masa kerja aparatur dalam penugasan perbendaharaan", "Keahlian dan kompetensi yang berlandaskan kode etik", "Kecepatan penyerapan anggaran di atas kepatuhan regulasi", "Fleksibilitas diskresi pejabat dalam mengatasi kendala kas", "Efisiensi belanja operasional melalui pemotongan standar biaya"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Profesionalitas dalam Pengelolaan Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Profesionalitas</strong> dalam pengelolaan keuangan negara berarti <strong>mengutamakan keahlian dan kompetensi yang berlandaskan kode etik dan ketentuan perundang-undangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Pengelolaan perbendaharaan negara memerlukan standar integritas dan kualifikasi teknis yang andal. Aparatur pengelola keuangan dituntut memiliki kecakapan profesional, bersertifikasi, serta menjunjung tinggi kode etik kedinasan guna mencegah terjadinya kekeliruan administrasi dan kerugian negara. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Keahlian dan kompetensi yang berlandaskan kode etik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penugasan perbendaharaan mengutamakan kompetensi teknis di atas sekadar senioritas.</li><li><strong>C</strong>: Penyerapan anggaran harus tetap patuh pada koridor regulasi dan standar pertanggungjawaban.</li><li><strong>D</strong>: Diskresi aparatur dibatasi secara ketat oleh hukum perbendaharaan negara.</li><li><strong>E</strong>: Standar biaya masukan ditetapkan resmi oleh kementerian dan tidak boleh dipotong sepihak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Profesionalitas, yang berarti • mengutamakan keahlian dan kompetensi yang berlandaskan kode etik</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Asas Proporsionalitas dalam pengelolaan keuangan negara diartikan sebagai asas yang mengutamakan keseimbangan antara...", "options": ["Alokasi belanja pegawai dan belanja barang kementerian", "Penerimaan perpajakan dan pendapatan bukan pajak", "Hak dan kewajiban penyelenggara negara", "Beban kewenangan pusat dan urusan otonomi daerah", "Posisi pembiayaan utang dan saldo kas cadangan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Proporsionalitas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Proporsionalitas</strong> adalah asas yang <strong>mengutamakan keseimbangan antara hak dan kewajiban penyelenggara negara</strong> dalam pengelolaan keuangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Prinsip proporsionalitas menuntut adanya kesetaraan yang wajar antara hak yang diterima oleh penyelenggara negara (kewenangan mengelola pagu belanja, fasilitas, dan honorarium) dengan kewajiban pertanggungjawaban serta beban tugas yang harus dituntaskan di hadapan hukum. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Hak dan kewajiban penyelenggara negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keseimbangan jenis belanja merupakan sasaran efisiensi postur DIPA kementerian.</li><li><strong>B</strong>: Komposisi pajak dan PNBP merupakan struktur kerangka pendapatan negara.</li><li><strong>D</strong>: Beban urusan pusat dan daerah diatur dalam kerangka hubungan keuangan pusat-daerah.</li><li><strong>E</strong>: Posisi utang dan saldo kas merupakan strategi manajemen likuiditas perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Proporsionalitas, yakni asas yang mengutamakan • keseimbangan antara hak dan kewajiban penyelenggara negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Asas-asas umum pengelolaan keuangan negara dirumuskan untuk memberikan daya dukung optimal pemerintahan, mencakup asas berikut, KECUALI...", "options": ["Asas Tahunan yang membatasi masa berlakunya anggaran", "Asas Kesatuan yang menyajikan anggaran dalam satu dokumen", "Asas Spesialitas yang merinci peruntukan kredit anggaran", "Asas Sentralisasi Kas yang memusatkan rekening dinas daerah", "Asas Akuntabilitas yang berorientasi pada pencapaian hasil"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Asas Umum Keuangan Negara (Kecuali)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Asas-asas umum pengelolaan keuangan negara meliputi: <strong>Tahunan, Universalitas, Kesatuan, Spesialitas, Akuntabilitas berorientasi hasil, Profesionalitas, Proporsionalitas, Keterbukaan, dan Pemeriksaan Bebas Mandiri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Undang-Undang Nomor 17 Tahun 2003 tidak mengenal 'Asas Sentralisasi Kas yang memusatkan rekening dinas daerah' sebagai bagian dari asas-asas umum keuangan negara. Pengelolaan kas daerah justru didesentralisasikan kepada kepala daerah otonom melalui Kas Umum Daerah masing-masing. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Asas Sentralisasi Kas yang memusatkan rekening dinas daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan asas umum nomor 1 yang membatasi anggaran satu tahun kalender fiskal.</li><li><strong>B</strong>: Merupakan asas umum nomor 3 yang menyatukan seluruh anggaran dalam dokumen unified budget.</li><li><strong>C</strong>: Merupakan asas umum nomor 4 yang mewajibkan rincian peruntukan kredit anggaran.</li><li><strong>E</strong>: Merupakan asas umum nomor 5 yang menuntut pertanggungjawaban berorientasi hasil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 15) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Asas Umum Pengelolaan Keuangan Negara • Universalitas • Spesialitas</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Asas Keterbukaan membuka diri terhadap hak masyarakat untuk memperoleh informasi tentang keuangan negara yang bersifat...", "options": ["Sederhana, praktis, dan dapat diubah sewaktu-waktu sesuai kebutuhan", "Bersifat teknis operasional dan diperuntukkan bagi perbankan", "Ringkas dan difokuskan pada proyeksi rencana kerja mendatang", "Fleksibel dan menyesuaikan dengan pertimbangan mitra kerja", "Benar, jujur, akurat, dan tidak diskriminatif bagi seluruh warga"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Keterbukaan Informasi Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Asas Keterbukaan</strong> adalah asas yang membuka diri terhadap hak masyarakat untuk memperoleh informasi yang <strong>benar, jujur, dan tidak diskriminatif</strong> tentang pengelolaan keuangan negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Asas keterbukaan menjamin hak warga negara atas akses informasi publik. Informasi keuangan yang disajikan oleh instansi pemerintah harus objektif, dapat dipertanggungjawabkan kebenarannya, tidak direkayasa, serta dapat diakses oleh seluruh lapisan masyarakat tanpa diskriminasi. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Benar, jujur, akurat, dan tidak diskriminatif bagi seluruh warga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Informasi keuangan harus berlandaskan standar pembukuan baku dan tidak dapat diubah sepihak.</li><li><strong>B</strong>: Informasi keuangan publik diperuntukkan bagi seluruh masyarakat dan bukan perbankan semata.</li><li><strong>C</strong>: Informasi wajib mencakup realisasi belanja historis dan bukan hanya proyeksi masa depan.</li><li><strong>D</strong>: Penyajian informasi terikat pada asas transparansi hukum dan bukan pertimbangan mitra kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keterbukaan, yaitu asas yang membuka diri terhadap hak masyarakat • informasi yang benar, jujur, dan tidak diskriminatif</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam penyelenggaraan Asas Keterbukaan, penyampaian informasi keuangan negara tetap wajib memperhatikan perlindungan atas...", "options": ["Hak asasi pribadi, golongan, dan rahasia negara", "Rencana pengadaan barang dan jasa instansi pemerintah", "Laporan pertanggungjawaban realisasi anggaran tahunan", "Data penerima program bantuan sosial dan subsidi dasar", "Realisasi penerimaan perpajakan dan bukan pajak daerah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batasan Keterbukaan Informasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyediaan informasi publik dalam asas keterbukaan tetap dibatasi oleh <strong>perlindungan atas hak-hak asasi pribadi, golongan, dan rahasia negara</strong> demi keamanan kedaulatan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Transparansi anggaran tidak bersifat mutlak tanpa batas. Undang-undang memberikan pengecualian terhadap informasi yang menyangkut kerahasiaan pertahanan keamanan negara, rahasia jabatan, serta data privat individu guna mencegah terjadinya ancaman kedaulatan dan pelanggaran privasi. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Hak asasi pribadi, golongan, dan rahasia negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Rencana pengadaan barang publik wajib diumumkan secara transparan pada sistem pengadaan.</li><li><strong>C</strong>: Laporan realisasi anggaran merupakan informasi terbuka yang wajib dipublikasikan berkala.</li><li><strong>D</strong>: Daftar penerima bansos merupakan dokumen publik guna mencegah penyaluran fiktif.</li><li><strong>E</strong>: Realisasi penerimaan pajak daerah adalah data statistik fiskal yang terbuka bagi masyarakat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tetap memperhatikan perlindungan atas hak-hak asasi pribadi • golongan, dan rahasia negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Asas pemeriksaan keuangan oleh badan pemeriksa yang bebas dan mandiri dalam praktiknya dilaksanakan oleh...", "options": ["Badan Pengawasan Keuangan dan Pembangunan", "Badan Pemeriksa Keuangan (BPK RI)", "Komisi Pengawas Persaingan Usaha Niaga", "Inspektorat Jenderal Kementerian Keuangan", "Otoritas Pengawas Lembaga Pembiayaan Publik"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Lembaga Pemeriksa Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemeriksaan keuangan oleh badan pemeriksa yang bebas dan mandiri dalam praktiknya <strong>dilakukan oleh Badan Pemeriksa Keuangan (BPK RI)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Konstitusi UUD 1945 Pasal 23E mengamanatkan pembentukan satu badan pemeriksa keuangan yang bebas dan mandiri, yakni BPK RI. BPK bertindak sebagai satu-satunya lembaga audit eksternal negara yang berwenang memeriksa pengelolaan dan tanggung jawab keuangan negara secara objektif. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Badan Pemeriksa Keuangan (BPK RI)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: BPKP adalah aparat pengawasan intern pemerintah yang bertanggung jawab kepada Presiden.</li><li><strong>C</strong>: KPPU adalah lembaga pengawas persaingan usaha dagang dan bukan audit keuangan negara.</li><li><strong>D</strong>: Inspektorat Jenderal Kemenkeu adalah aparat pengawas intern kementerian.</li><li><strong>E</strong>: Otoritas pembiayaan adalah regulator industri jasa dan bukan pemeriksa anggaran negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemeriksaan keuangan oleh badan pemeriksa yang bebas dan mandiri • Badan Pemeriksa Keuangan (BPK RI)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Karakter bebas dan mandiri yang dimiliki Badan Pemeriksa Keuangan (BPK RI) mengandung arti bahwa lembaga ini...", "options": ["Bebas menentukan sendiri standar pembukuan akuntansi perbankan", "Mandiri menetapkan tarif pungutan pendapatan negara bukan pajak", "Bebas dari pengaruh dan campur tangan pemerintah dalam pemeriksaannya", "Mandiri mencairkan dana perbendaharaan tanpa persetujuan undang-undang", "Bebas menunda penyerahan laporan hasil pemeriksaan kepada parlemen"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter Bebas dan Mandiri BPK RI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Karakter <strong>bebas dan mandiri</strong> menjamin Badan Pemeriksa Keuangan dapat menjalankan fungsi audit secara objektif <strong>tanpa intervensi dari cabang kekuasaan pemerintah mana pun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Independensi BPK RI dirancang agar auditor negara steril dari tekanan politik kekuasaan eksekutif. Status bebas dan mandiri menjamin bahwa perencanaan audit, penentuan metodologi pemeriksaan, hingga penyusunan opini laporan keuangan bebas dari pengaruh dan intervensi pihak yang diperiksa. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Bebas dari pengaruh dan campur tangan pemerintah dalam pemeriksaannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Standar pembukuan akuntansi keuangan disusun oleh asosiasi profesi dan komite independen.</li><li><strong>B</strong>: Penetapan tarif PNBP merupakan wewenang undang-undang dan peraturan pemerintah.</li><li><strong>D</strong>: Pencairan kas negara adalah wewenang Menteri Keuangan selaku Bendahara Umum Negara.</li><li><strong>E</strong>: BPK diwajibkan konstitusi menyerahkan Laporan Hasil Pemeriksaan kepada DPR, DPD, dan DPRD.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemeriksaan keuangan oleh badan pemeriksa yang bebas dan mandiri • BPK RI</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Satuan kerja pemerintah mengimplementasikan Asas Keterbukaan dalam pengelolaan anggarannya dengan cara...", "options": ["Menyusun laporan evaluasi kinerja untuk telaah internal pimpinan", "Melakukan koordinasi berkala dengan aparat pengawasan intern", "Memperbarui sistem pencatatan inventaris barang milik negara", "Menyediakan publikasi berkala tentang kinerja realisasi anggaran", "Mengoptimalkan pemanfaatan aplikasi pembukuan perbendaharaan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Keterbukaan Informasi pada Unit Kerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Implementasi <strong>Asas Keterbukaan</strong> pada satuan kerja diwujudkan melalui <strong>penyediaan publikasi informasi keuangan yang jujur, akurat, dan dapat diakses publik secara berkala</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Asas keterbukaan menuntut transparansi aktif dari penyelenggara negara kepada masyarakat. Satuan kerja mengimplementasikan asas ini dengan mempublikasikan realisasi penyerapan anggaran, capaian output kegiatan, serta laporan kinerja tahunan melalui portal informasi resmi yang dapat diakses publik. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Menyediakan publikasi berkala tentang kinerja realisasi anggaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Laporan telaah internal pimpinan bersifat tertutup untuk kebutuhan manajemen satker.</li><li><strong>B</strong>: Koordinasi dengan pengawas intern merupakan fungsi kepatuhan pengawasan aparatur.</li><li><strong>C</strong>: Inventarisasi barang milik negara merupakan penatausahaan teknis aset perbendaharaan.</li><li><strong>E</strong>: Pemanfaatan aplikasi pembukuan adalah efisiensi operasional sistem akuntansi internal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keterbukaan • informasi yang benar, jujur, dan tidak diskriminatif</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Ketentuan yang mewajibkan perlindungan hak asasi pribadi dan golongan dalam Asas Keterbukaan bertujuan untuk...", "options": ["Membatasi akses masyarakat terhadap informasi belanja publik", "Menjamin kerahasiaan identitas pejabat pengelola kas negara", "Menjaga stabilitas neraca modal dalam perbankan nasional", "Mengurangi beban administrasi pelaporan pertanggungjawaban satker", "Mencegah penyalahgunaan data rahasia individu dari eksploitasi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keseimbangan Transparansi dan Hak Pribadi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perlindungan atas hak asasi pribadi dan golongan dalam transparansi fiskal bertujuan <strong>menjaga privasi individu dari penyalahgunaan data pribadi tanpa mengabaikan keterbukaan informasi publik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Meskipun keuangan negara menganut prinsip transparansi, perlindungan hak asasi pribadi tetap ditegakkan agar data sensitif perorangan (seperti rekam medis pasien penerima bantuan atau identitas terlindung) tidak tersebar luas dan disalahgunakan secara tidak bertanggung jawab. Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Mencegah penyalahgunaan data rahasia individu dari eksploitasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perlindungan privasi bukan bertujuan menghalangi hak masyarakat atas informasi anggaran.</li><li><strong>B</strong>: Identitas pejabat pengelola anggaran merupakan informasi jabatan publik yang terbuka.</li><li><strong>C</strong>: Stabilitas neraca modal perbankan adalah objek kebijakan makroprudensial bank sentral.</li><li><strong>D</strong>: Ketentuan privasi tidak mengurangi kewajiban administrasi pelaporan akuntansi satker.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>perlindungan atas hak-hak asasi pribadi, golongan, dan rahasia negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pemeriksaan keuangan negara secara eksternal diserahkan kepada BPK RI untuk menjamin bahwa proses audit...", "options": ["Berjalan secara independen tanpa benturan kepentingan eksekutif", "Mengikuti arahan langsung dari pimpinan kementerian teknis", "Disesuaikan dengan target pencapaian penerimaan kas negara", "Mengedepankan pertimbangan fleksibilitas operasional satuan kerja", "Memprioritaskan pengawasan terhadap instansi pemerintah daerah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Landasan Kelembagaan Pemeriksa Eksternal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemeriksaan oleh BPK RI menjamin proses audit <strong>berjalan independen, objektif, dan terbebas dari benturan kepentingan dengan cabang eksekutif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Jika pemeriksaan laporan keuangan diserahkan kepada instansi yang berada di bawah eksekutif, potensi konflik kepentingan sangat tinggi. Penyerahan fungsi audit eksternal kepada lembaga konstitusional mandiri (BPK RI) memastikan pengujian kepatuhan dan kewajaran anggaran dilakukan secara objektif tanpa tekanan kekuasaan. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Berjalan secara independen tanpa benturan kepentingan eksekutif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: BPK dilarang mengikuti instruksi dari pimpinan kementerian yang menjadi objek auditnya.</li><li><strong>C</strong>: Audit eksternal berfokus pada kepatuhan hukum dan kewajaran, bukan target setoran kas.</li><li><strong>D</strong>: Auditor independen terikat pada standar pemeriksaan ketat dan bukan fleksibilitas satker.</li><li><strong>E</strong>: Pemeriksaan BPK mencakup seluruh entitas pemerintah pusat, kementerian, dan pemda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>badan pemeriksa yang bebas dan mandiri • BPK RI</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pengecualian asas keterbukaan diberlakukan terhadap informasi tertentu yang diklasifikasikan sebagai...", "options": ["Rencana belanja pengadaan sarana perkantoran pemerintah", "Rahasia negara demi menjaga keamanan dan kedaulatan bangsa", "Laporan realisasi penyerapan anggaran satuan kerja dinas", "Rincian tarif pelayanan administrasi penerimaan bukan pajak", "Dokumen petunjuk teknis pelaksanaan kegiatan operasional"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengecualian Rahasia Negara dalam Tata Kelola</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Asas Keterbukaan mengecualikan penyebaran informasi yang diklasifikasikan sebagai <strong>rahasia negara demi menjaga keselamatan pertahanan, keamanan, dan kedaulatan bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Informasi keuangan yang berkaitan langsung dengan strategi pertahanan militer, intelijen negara, dan keamanan nasional berkedudukan sebagai rahasia negara. Informasi ini dikecualikan secara sah dari keterbukaan informasi publik guna mencegah ancaman nyata terhadap keutuhan kedaulatan negara. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Rahasia negara demi menjaga keamanan dan kedaulatan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengadaan perkantoran merupakan belanja umum yang wajib transparan kepada masyarakat.</li><li><strong>C</strong>: Realisasi anggaran satker adalah informasi kinerja publik yang wajib diumumkan berkala.</li><li><strong>D</strong>: Tarif PNBP wajib diatur dalam peraturan resmi yang disosialisasikan kepada warga.</li><li><strong>E</strong>: Petunjuk teknis operasional adalah panduan tata kelola terbuka bagi aparatur sipil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>rahasia negara • Keterbukaan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pemeriksaan keuangan yang dijalankan oleh badan pemeriksa independen berfokus pada dua pilar utama, yaitu...", "options": ["Tingkat efektivitas peredaran uang kartal di masyarakat", "Kinerja portofolio penjaminan simpanan perbankan swasta", "Pengelolaan dan pertanggungjawaban keuangan negara", "Pertumbuhan nilai aset perusahaan penanaman modal asing", "Volume transaksi perdagangan komoditas ekspor daerah"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Audit BPK atas Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemeriksaan oleh badan pemeriksa yang bebas dan mandiri diarahkan untuk memeriksa <strong>pengelolaan dan tanggung jawab tentang keuangan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Mandat audit BPK RI berfokus pada dua aspek fundamental: bagaimana keuangan negara dikelola (perencanaan, penatausahaan kas, pengadaan aset) dan bagaimana keuangan tersebut dipertanggungjawabkan dalam laporan pertanggungjawaban APBN/APBD sesuai standar akuntansi pemerintahan. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Pengelolaan dan pertanggungjawaban keuangan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Peredaran uang kartal merupakan indikator sistem pembayaran moneter Bank Indonesia.</li><li><strong>B</strong>: Penjaminan simpanan perbankan swasta adalah fungsi kelembagaan LPS.</li><li><strong>D</strong>: Pertumbuhan aset modal asing merupakan ranah investasi korporasi privat.</li><li><strong>E</strong>: Volume perdagangan ekspor daerah merupakan domain statistik perdagangan kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemeriksaan keuangan oleh badan pemeriksa • BPK RI</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C4 - Analisis", "question": "Keterpaduan antara Asas Keterbukaan dan asas pemeriksaan independen ditujukan untuk mewujudkan sistem keuangan negara yang...", "options": ["Efisien, tersentralisasi, dan berfokus pada target pertumbuhan", "Fleksibel, responsif, dan mengutamakan kecepatan penyerapan", "Terkoordinasi, berjenjang, dan berbasis diskresi kepemimpinan", "Transparan, akuntabel, dan bebas dari praktik penyimpangan", "Terencana, terpadu, dan berorientasi pada ekspansi belanja"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Asas Keterbukaan dan Audit Eksternal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keterpaduan asas keterbukaan dan pemeriksaan bebas mandiri ditujukan untuk mewujudkan pengelolaan keuangan negara yang <strong>transparan, akuntabel, dan bebas dari penyimpangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Sinergi antara keterbukaan informasi kepada publik dan pengawasan audit independen oleh BPK RI menciptakan sistem saling kontrol yang kuat. Transparansi memungkinkan kontrol sosial oleh rakyat, sedangkan audit profesional mengungkap ketidaksesuaian prosedur, sehingga menjamin integritas anggaran bebas dari korupsi. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Transparan, akuntabel, dan bebas dari praktik penyimpangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sentralisasi kekuasaan bertentangan dengan prinsip desentralisasi fiskal dan saling uji.</li><li><strong>B</strong>: Kecepatan penyerapan tanpa kontrol kepatuhan berisiko memicu pemborosan anggaran.</li><li><strong>C</strong>: Tata kelola keuangan publik berlandaskan kepatuhan hukum dan bukan diskresi pimpinan.</li><li><strong>E</strong>: Ekspansi belanja tanpa pengawasan audit akan membahayakan kesinambungan fiskal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keterbukaan • Pemeriksaan keuangan oleh badan pemeriksa yang bebas dan mandiri</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dua asas umum pelengkap yang ditegaskan pada bagian akhir penataan keuangan negara di Undang-Undang Keuangan Negara adalah...", "options": ["Asas Tahunan dan asas universalitas perbendaharaan", "Asas Kesatuan dan asas spesialitas pembebanan anggaran", "Asas Akuntabilitas dan asas profesionalitas birokrasi", "Asas Proporsionalitas dan asas kemandirian fiskal", "Asas Keterbukaan dan asas pemeriksaan bebas mandiri"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sintesis Asas Keterbukaan dan Pemeriksaan Mandiri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dua asas umum penutup dalam tata kelola keuangan negara adalah <strong>Asas Keterbukaan (angka 8) dan Pemeriksaan keuangan oleh badan pemeriksa yang bebas dan mandiri (angka 9)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Dalam sistematika penjelasan asas-asas umum pengelolaan keuangan negara pada Undang-Undang Nomor 17 Tahun 2003, dua pilar penutup yang menyempurnakan tata kelola keuangan modern adalah Asas Keterbukaan (angka 8) dan Asas Pemeriksaan Keuangan oleh Badan Pemeriksa yang Bebas dan Mandiri (angka 9). Oleh karena itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Asas Keterbukaan dan asas pemeriksaan bebas mandiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Asas tahunan dan universalitas merupakan asas umum pembuka pada angka 1 dan 2.</li><li><strong>B</strong>: Asas kesatuan dan spesialitas merupakan asas perbendaharaan pada angka 3 dan 4.</li><li><strong>C</strong>: Asas akuntabilitas dan profesionalitas merupakan asas tata kelola pada angka 5 dan 6.</li><li><strong>D</strong>: Asas proporsionalitas berada pada angka 7 dan kemandirian bukan asas dalam daftar 9 asas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keterbukaan • Pemeriksaan keuangan oleh badan pemeriksa yang bebas dan mandiri</strong></div></div>"}];
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
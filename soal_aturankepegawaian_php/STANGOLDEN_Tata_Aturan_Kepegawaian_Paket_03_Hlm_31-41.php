<?php
/**
 * STANGOLDEN.exam • Tata Aturan Kepegawaian Paket 03 (Halaman 31–41)
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
// $DB->get_record("stgtak_p03_rank") => tabel fisik "mdlhv_stgtak_p03_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgtak_p03_rank';
const STG_REVIEW_T = 'stgtak_p03_rev';
const STG_ANNO_T = 'stgtak_p03_anno';
const STG_KEY = [0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 0, 1, 2, 3, 0, 1, 0, 1, 4, 4, 1, 2, 3, 4, 0, 1, 2, 3, 0, 2, 4, 4, 3, 0, 2, 4, 1, 3, 1, 3, 0, 2, 4, 1, 3, 0, 2, 4, 2, 4, 1, 3, 0, 2, 4, 0, 1, 0, 3, 0, 2, 4, 1, 3, 0, 2, 4, 1, 4, 1, 3, 3, 2, 4, 1, 3, 0, 2];
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
            error_log('STANGOLDEN TAK P03 HLM31-41 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN TAK P03 HLM31-41: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Tata Aturan Kepegawaian Paket 03 (Hlm. 31–41) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 03 • Hlm. 31–41</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 03 • Hlm. 31–41</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Tata Aturan Kepegawaian Paket 03</h2>
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
const STG_LOCAL_KEY="stg_tak_p03_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Batas Usia Pensiun (BUP) bagi pejabat administrasi, pejabat fungsional ahli pertama, dan ahli muda adalah...", "options": ["58 tahun kalender masa pengabdian", "60 tahun masa bakti aparatur negara", "62 tahun masa kedinasan pemerintah", "65 tahun masa jabatan fungsional", "70 tahun masa kerja kepegawaian"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Batas Usia Pensiun Pejabat Administrasi dan JF Ahli Pertama/Muda</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Berdasarkan PP 11/2017 stdd PP 17/2020, Batas Usia Pensiun PNS adalah <strong>58 tahun bagi pejabat administrasi, pejabat fungsional ahli muda, ahli pertama, dan fungsional keterampilan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan batas usia pensiun baku bagi aparatur jenjang administrasi dan keahlian tingkat dasar hingga menengah. Ketentuan ini memberikan kepastian regenerasi kepemimpinan dan penataan formasi berkala. Regulasi menetapkan BUP kelompok jabatan ini tepat 58 tahun. Dengan demikian, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>A. 58 tahun kalender masa pengabdian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan batas usia pensiun bagi pejabat pimpinan tinggi dan fungsional ahli madya.</li><li><strong>C</strong>: Merupakan batas usia pensiun profesi tertentu di luar ketentuan umum manajemen PNS.</li><li><strong>D</strong>: Merupakan batas usia pensiun khusus bagi pejabat fungsional ahli utama.</li><li><strong>E</strong>: Merupakan batas pensiun hakim agung atau guru besar akademisi senior.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 31) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>58 (lima puluh delapan) tahun bagi pejabat administrasi • ahli muda, pejabat fungsional ahli pertama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Batas Usia Pensiun bagi Pegawai Negeri Sipil yang menduduki Jabatan Pimpinan Tinggi dan JF Ahli Madya adalah...", "options": ["56 tahun kalender dinas", "60 tahun masa kedinasan", "62 tahun masa penugasan", "65 tahun masa pengabdian", "58 tahun masa pengabdian"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Batas Usia Pensiun JPT dan JF Madya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Batas Usia Pensiun bagi PNS adalah <strong>60 (enam puluh) tahun bagi pejabat pimpinan tinggi dan pejabat fungsional madya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan ketentuan batas usia bagi pucuk pimpinan birokrasi dan tenaga ahli tingkat madya. Kematangan kepemimpinan strategis dan keahlian mendalam dibutuhkan hingga batas usia enam puluh tahun. Regulasi menegaskan BUP bagi JPT dan JF Madya adalah 60 tahun. Oleh karena itu, opsi B adalah ketetapan yang tepat.</p><p>Jawaban tepat: <strong>B. 60 tahun masa kedinasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan usia masa pensiun dini sukarela yang memenuhi syarat tertentu.</li><li><strong>C</strong>: Merupakan batas usia fiktif yang tidak tercantum dalam PP 11/2017 stdd PP 17/2020.</li><li><strong>D</strong>: Merupakan batas pensiun bagi pejabat fungsional jenjang ahli utama.</li><li><strong>E</strong>: Merupakan batas pensiun bagi pejabat administrasi dan JF keterampilan/pertama/muda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 31) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>60 (enam puluh) tahun bagi pejabat pimpinan tinggi • pejabat fungsional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Batas Usia Pensiun yang ditetapkan oleh peraturan perundang-undangan bagi PNS pemangku Jabatan Fungsional Ahli Utama adalah...", "options": ["58 tahun masa kedinasan", "60 tahun masa pengabdian", "65 tahun masa pengabdian", "67 tahun masa kepakaran", "70 tahun masa pengabdian"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Batas Usia Pensiun JF Ahli Utama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Sesuai PP 11/2017 stdd PP 17/2020, Batas Usia Pensiun adalah <strong>65 (enam puluh lima) tahun bagi PNS yang memangku pejabat fungsional ahli utama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan apresiasi dan pemanfaatan kepakaran tertinggi fungsional aparatur negara. Jenjang Ahli Utama memiliki kontribusi substansi strategis bagi kebijakan nasional sehingga masa baktinya diperpanjang hingga enam puluh lima tahun. Aturan menetapkan BUP JF Ahli Utama adalah 65 tahun. Dengan demikian, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. 65 tahun masa pengabdian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan batas usia pensiun bagi pejabat administrasi dan JF pertama/muda.</li><li><strong>B</strong>: Merupakan batas usia pensiun bagi JPT dan pejabat fungsional madya.</li><li><strong>D</strong>: Merupakan batas usia fiktif di luar peraturan perundangan manajemen PNS.</li><li><strong>E</strong>: Merupakan batas usia bagi profesor riset atau hakim peradilan tinggi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 31) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>65 (enam puluh lima) tahun bagi • PP 11 Tahun 2017 sebagaimana telah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Jaminan pensiun dan jaminan hari tua diberikan kepada PNS dengan fungsi utama sebagai...", "options": ["Dana cadangan darurat penanggulangan inflasi daerah", "Uang kompensasi atas pelepasan hak kepegawaian dinas", "Bantuan modal usaha bagi kegiatan kewirausahaan baru", "Perlindungan kesinambungan penghasilan dan penghargaan jasa", "Alokasi tabungan investasi portofolio pasar modal negara"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Fungsi Jaminan Pensiun dan JHT</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Jaminan pensiun dan jaminan hari tua PNS diberikan sebagai <strong>perlindungan kesinambungan penghasilan hari tua, sebagai hak dan sebagai penghargaan atas pengabdian PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan filosofi perlindungan sosial negara bagi purnakaryawan aparatur sipil. Setelah puluhan tahun mengabdi pada negara, kestabilan finansial aparatur di masa pensiun dijamin sebagai wujud hak hukum dan apresiasi pengabdian bangsa. Ketentuan perundang-undangan menetapkan fungsi jaminan pensiun sebagai perlindungan kesinambungan penghasilan dan penghargaan atas jasa pegawai. Oleh karena itu, opsi D merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>D. Perlindungan kesinambungan penghasilan dan penghargaan jasa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dana cadangan inflasi daerah dikelola melalui instrumen fiskal APBD dan bukan hak pensiun.</li><li><strong>B</strong>: Jaminan pensiun merupakan hak pengabdian dan bukan kompensasi pelepasan status pegawai.</li><li><strong>C</strong>: Modal wirausaha difasilitasi melalui perbankan komersial dan bukan tujuan utama program pensiun.</li><li><strong>E</strong>: Pengelolaan dana pensiun berfokus pada jaminan proteksi hari tua dan bukan spekulasi modal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 31) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Jaminan pensiun PNS dan jaminan hari tua PNS diberikan sebagai • kesinambungan penghasilan hari tua, sebagai hak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ketentuan batas usia pensiun bagi PNS yang memangku jabatan fungsional tertentu yang diatur khusus dalam undang-undang adalah...", "options": ["Otomatis disamakan menjadi 55 tahun untuk seluruh rumpun jabatan", "Wajib mengikuti keputusan menteri keuangan selaku bendahara negara", "Mengikuti kesepakatan informal antar-pejabat fungsional di satker", "Ditetapkan berdasarkan kebijakan penugasan perwakilan luar negeri", "Berlaku sesuai batas usia pensiun yang ditetapkan undang-undang terkait"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Ketentuan BUP Khusus Berdasarkan Undang-Undang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Batas Usia Pensiun bagi PNS yang menduduki Jabatan Fungsional yang ditentukan dalam undang-undang, berlaku ketentuan sesuai dengan Batas Usia Pensiun yang ditetapkan dalam undang-undang yang bersangkutan</strong> (*lex specialis*).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan penerapan asas *lex specialis derogat legi generali* dalam hukum kepegawaian. Jika undang-undang profesi khusus (seperti UU Dosen/Guru Besar atau Jaksa) menetapkan batas usia tersendiri, maka ketentuan khusus undang-undang itulah yang berlaku mengikat. Regulasi menyatakan berlaku sesuai BUP undang-undang yang bersangkutan. Dengan demikian, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Berlaku sesuai batas usia pensiun yang ditetapkan undang-undang terkait</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penyeragaman keliru yang menabrak ketentuan undang-undang profesi.</li><li><strong>B</strong>: Merupakan menteri sektoral fiskal yang tidak menetapkan BUP profesi lain.</li><li><strong>C</strong>: Merupakan musyawarah tanpa dasar legalitas yang batal demi hukum.</li><li><strong>D</strong>: Penugasan perwakilan luar negeri diatur melalui regulasi penempatan instansi dan bukan keputusan sepihak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 31) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Batas Usia Pensiun bagi PNS yang • berlaku ketentuan sesuai dengan Batas Usia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Sebuah instansi menggabungkan dua unit riset sehingga terjadi perampingan organisasi. Pegawai terdampak yang memenuhi syarat berhak atas...", "options": ["Hak pensiun dini dengan hormat sesuai kebijakan pemerintah", "Pemindahan tugas berkala pada jabatan pelaksana administrasi", "Penetapan status penugasan penyesuaian kompetensi teknis", "Pemberian dispensasi pembebasan tugas dinas sementara waktu", "Pengalihan status kepegawaian menjadi tenaga ahli kontrak"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemberhentian Pensiun Dini Rasionalisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS diberhentikan dengan hormat atau <strong>diberikan pensiun dini dengan hormat apabila memenuhi syarat usia dan masa kerja akibat perampingan organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan bentuk jaminan hak aparatur yang terdampak penataan struktur kelembagaan negara. Pegawai yang posisinya terhapus akibat reorganisasi dan memenuhi batas minimal usia serta masa kerja berhak memperoleh pensiun dini secara terhormat. Ketentuan perundang-undangan menjamin pemberian hak pensiun dini dengan hormat akibat perampingan organisasi. Oleh karena itu, opsi A merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Hak pensiun dini dengan hormat sesuai kebijakan pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pemindahan ke jabatan pelaksana hanya alternatif penempatan dan bukan hak purna jabatan.</li><li><strong>C</strong>: Penyesuaian kompetensi merupakan langkah pembinaan dan bukan solusi terminasi organisasi.</li><li><strong>D</strong>: Pembebasan tugas sementara tidak memberikan kepastian status hukum kepegawaian jangka panjang.</li><li><strong>E</strong>: PNS tidak dapat dialihkan menjadi tenaga kontrak swasta akibat perampingan organisasi pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 31) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>perampingan organisasi atau kebijakan pemerintah • pensiun dini</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pengelolaan jaminan pensiun dan jaminan hari tua bagi aparatur sipil negara terintegrasi ke dalam kerangka...", "options": ["Asuransi komersial privat yang terdaftar pada otoritas jasa", "Program jaminan sosial nasional yang diatur undang-undang", "Lembaga pembiayaan perumahan dan tabungan hari tua pegawai", "Badan pengelola dana pensiun sukarela kemitraan perbankan", "Unit pengelola dana amanah kesejahteraan pegawai instansi"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Integrasi Jaminan Pensiun ke SJSN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Jaminan pensiun dan jaminan hari tua <strong>diberikan dalam program jaminan sosial nasional</strong> sesuai ketentuan peraturan perundang-undangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan kebijakan integrasi sistem proteksi sosial aparatur secara nasional. Program pensiun ASN dipayungi kerangka Sistem Jaminan Sosial Nasional (SJSN) guna memastikan tata kelola dana amanah yang akuntabel, transparan, dan berkelanjutan. Ketentuan perundang-undangan menegaskan bahwa jaminan pensiun diberikan dalam program jaminan sosial nasional. Dengan demikian, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Program jaminan sosial nasional yang diatur undang-undang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Asuransi komersial privat berorientasi laba dan berada di luar jaminan sosial resmi negara.</li><li><strong>C</strong>: Pembiayaan perumahan pegawai merupakan fasilitas penunjang dan bukan payung jaminan pensiun.</li><li><strong>D</strong>: Dana pensiun sukarela perbankan merupakan program tambahan nonwajib di luar jaminan sosial nasional.</li><li><strong>E</strong>: Unit dana amanah internal tidak memiliki mandat hukum mengelola jaminan pensiun nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 31) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Jaminan pensiun dan jaminan hari tua • program jaminan sosial nasional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi manajemen PNS, Batas Usia Pensiun bagi seorang pejabat fungsional keterampilan adalah...", "options": ["55 tahun masa tugas", "56 tahun masa tugas", "58 tahun masa tugas", "60 tahun masa tugas", "65 tahun masa tugas"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • BUP Pejabat Fungsional Keterampilan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Batas Usia Pensiun bagi <strong>pejabat fungsional keterampilan</strong> adalah <strong>58 (lima puluh delapan) tahun</strong> sebagaimana diatur dalam PP 11/2017 stdd PP 17/2020.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan ambang batas usia pensiun bagi rumpun fungsional kategori keterampilan (pemula, terampil, mahir, penyelia). Batas usia 58 tahun disetarakan dengan pejabat administrasi dan fungsional ahli pertama/muda. Regulasi menetapkan batas usianya adalah 58 tahun. Oleh karena itu, opsi C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. 58 tahun masa tugas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan batas usia pensiun lama pada masa regulasi sebelum reformasi.</li><li><strong>B</strong>: Merupakan usia pensiun yang tidak dikenal dalam PP 11/2017 stdd PP 17/2020.</li><li><strong>D</strong>: Merupakan batas usia pensiun bagi pejabat fungsional ahli madya dan JPT.</li><li><strong>E</strong>: Merupakan batas usia pensiun bagi pemangku fungsional ahli utama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 31) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pejabat fungsional • 58 (lima puluh delapan) tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "PNS yang dinyatakan tidak cakap jasmani dan/atau rohani oleh tim penguji kesehatan resmi diberhentikan secara...", "options": ["Pemberhentian sementara menunggu pemulihan kesehatan dinas", "Pemberhentian dengan penyesuaian penurunan kelas jabatan", "Pembebasan tugas operasional dengan tunjangan pemeliharaan", "Hormat dengan hak pensiun sesuai ketentuan perundangan", "Pemberhentian administratif berjangka tanpa penetapan SK"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pensiun Akibat Ketidakcakapan Medis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS diberhentikan dengan hormat apabila <strong>tidak cakap jasmani dan/atau rohani sehingga tidak dapat menjalankan tugas dan kewajiban</strong>, dan diberikan hak pensiun.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan perlakuan hukum yang adil dan manusiawi bagi aparatur yang mengalami kendala kesehatan permanen. Berdasarkan vonis resmi tim penguji kesehatan pemerintah, pegawai diberhentikan secara terhormat serta tetap diberikan jaminan hak pensiun. Ketentuan hukum kepegawaian menetapkan pemberhentian dilakukan dengan hormat disertai hak pensiun sesuai regulasi. Dengan demikian, opsi D merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>D. Hormat dengan hak pensiun sesuai ketentuan perundangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemberhentian sementara hanya berlaku bila tim dokter menyatakan sakit bersifat sementara.</li><li><strong>B</strong>: Penurunan kelas jabatan merupakan sanksi disiplin dan bukan perlakuan bagi disabilitas medis.</li><li><strong>C</strong>: Pembebasan tugas bukan penetapan permanen bagi kondisi ketidakcakapan permanen.</li><li><strong>E</strong>: Seluruh pemberhentian ASN wajib dituangkan secara formal melalui Surat Keputusan resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 31) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tidak cakap jasmani dan/atau rohani sehingga tidak dapat menjalankan tugas • dan kewajiban</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dasar regulasi pemerintah yang mengatur rincian ketentuan Batas Usia Pensiun PNS menurut jenjang jabatan adalah...", "options": ["Undang-Undang Nomor 1 Tahun 2004 tentang Perbendaharaan Negara", "Peraturan Presiden Nomor 16 Tahun 2018 tentang Pengadaan Barang", "Keputusan Komisi Yudisial Nomor 5 Tahun 2015 tentang Kode Etik", "Instruksi Jaksa Agung Republik Indonesia Nomor 2 Tahun 2019", "PP 11 Tahun 2017 stdd PP 17 Tahun 2020 Manajemen PNS"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Dasar Regulasi Batas Usia Pensiun PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Rincian ketentuan Batas Usia Pensiun PNS diatur dalam <strong>PP 11 Tahun 2017 sebagaimana telah diubah dengan PP 17 Tahun 2020 tentang Manajemen Pegawai Negeri Sipil</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan peraturan pemerintah induk tata kelola kepegawaian negeri sipil nasional. PP ini memuat norma baku BUP mulai dari 58 tahun (administrasi/ahli muda/pertama/keterampilan), 60 tahun (JPT/madya), hingga 65 tahun (ahli utama). Regulasi yang tepat adalah PP 11/2017 stdd PP 17/2020. Oleh karena itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. PP 11 Tahun 2017 stdd PP 17 Tahun 2020 Manajemen PNS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan undang-undang pengelolaan perbendaharaan dan kas umum negara.</li><li><strong>B</strong>: Merupakan perpres tata laksana pengadaan barang dan jasa pemerintah.</li><li><strong>C</strong>: Merupakan kode etik perilaku profesi hakim lembaga kehakiman.</li><li><strong>D</strong>: Merupakan regulasi teknis internal aparatur penuntut umum kejaksaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 31) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PNS diberhentikan dengan hormat karena mencapai batas usia pensiun • PP 11 Tahun 2017 sebagaimana telah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam sejarah transformasi kelembagaan, Kementerian Keuangan telah mengimplementasikan Sistem Merit secara konsisten sejak...", "options": ["Pengesahan undang-undang perbendaharaan negara tahun 1999", "Pelaksanaan program reformasi birokrasi kementerian tahun 2007", "Penerapan kebijakan desentralisasi fiskal daerah tahun 2014", "Penerbitan paket undang-undang keuangan negara tahun 2003", "Pemberlakuan undang-undang penanaman modal terpadu tahun 2008"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Linimasa Penerapan Sistem Merit di Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kementerian Keuangan telah menerapkan Sistem Merit dalam manajemen ASN <strong>sejak tahun 2007 sejalan dengan Reformasi Birokrasi Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan tonggak sejarah penting pembaharuan manajemen sumber daya manusia di Kementerian Keuangan. Sejak tahun 2007, Kemenkeu memelopori tata kelola ASN berbasis meritokrasi, pemeringkatan jabatan terukur, transparansi seleksi, dan remunerasi berbasis kinerja. Linimasa reformasi birokrasi mencatat tahun 2007 sebagai awal implementasi terpadu sistem merit. Oleh karena itu, opsi B merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. Pelaksanaan program reformasi birokrasi kementerian tahun 2007</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tahun 1999 merupakan era awal reformasi hukum perbankan dan bukan reformasi birokrasi Kemenkeu.</li><li><strong>C</strong>: Tahun 2014 merupakan momentum lahirnya UU Nomor 5 Tahun 2014 tentang ASN.</li><li><strong>D</strong>: Tahun 2003 menandai penerbitan UU Keuangan Negara dan bukan penerapan sistem merit kepegawaian.</li><li><strong>E</strong>: Tahun 2008 merupakan penataan kelembagaan umum penanaman modal dan bukan tata kelola ASN Kemenkeu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 33) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kementerian Keuangan sejak reformasi birokrasi tahun 2007 • telah menerapkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C4 - Analisis", "question": "Penerapan Sistem Merit di Kementerian Keuangan sejak 2007 diwujudkan melalui kebijakan nyata, KECUALI...", "options": ["Rekrutmen aparatur yang transparan dan objektif", "Pengukuran standar kompetensi teknis dan manajerial", "Pemberian promosi berdasarkan garis kekerabatan pejabat", "Penerapan manajemen kinerja dan pemeringkatan jabatan", "Remunerasi berbasis kelas jabatan dan capaian kinerja"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Bentuk Nyata Sistem Merit di Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Sistem merit di Kementerian Keuangan diterapkan melalui: <strong>rekrutmen objektif/transparan, pengukuran kompetensi, manajemen kinerja, peringkat jabatan, talent management, dan remunerasi berbasis jabatan/kinerja</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan praktik nepotisme yang bertentangan secara diametral dengan filosofi Sistem Merit. Seluruh promosi dan mutasi di Kemenkeu didasarkan pada kompetensi, kualifikasi, dan prestasi kinerja terukur. Oleh karena itu, opsi C adalah pilihan jawaban pengecualian yang benar.</p><p>Jawaban tepat: <strong>C. Pemberian promosi berdasarkan garis kekerabatan pejabat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pilar pintu masuk pengadaan pegawai berbasis meritokrasi murni.</li><li><strong>B</strong>: Merupakan instrumen pengukuran kapasitas kerja pegawai secara terstandardisasi.</li><li><strong>D</strong>: Merupakan sistem pengelolaan output kerja (*performance management*) dan *grading*.</li><li><strong>E</strong>: Merupakan skema kompensasi adil (*equal pay for equal work*) di birokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 33) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>rekrutmen yang objektif dan transparan, pengukuran • penerapan manajemen kinerja, peringkat jabatan, talent management</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Regulasi terbaru Kementerian Keuangan yang menjadi dasar hukum pelaksanaan Manajemen Talenta adalah...", "options": ["Peraturan Menteri Keuangan Nomor 10 Tahun 2015", "Peraturan Direktur Jenderal Anggaran Nomor 5 Tahun 2020", "Surat Edaran Sekretaris Jenderal Nomor 12 Tahun 2018", "Peraturan Menteri Keuangan Nomor 38 Tahun 2025", "Keputusan Kepala Badan Pendidikan Keuangan 2019"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Dasar Regulasi Manajemen Talenta Kemenkeu Terbaru</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kementerian Keuangan telah menerbitkan <strong>Peraturan Menteri Keuangan Republik Indonesia Nomor 38 Tahun 2025 tentang Manajemen Talenta di Lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan regulasi termutakhir yang menyempurnakan dan mempercepat akselerasi sistem merit di Kemenkeu sesuai amanat UU No 20/2023. Peraturan ini mencabut regulasi manajemen talenta terdahulu. Regulasi yang tepat adalah PMK Nomor 38 Tahun 2025. Dengan demikian, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Peraturan Menteri Keuangan Nomor 38 Tahun 2025</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan aturan lama mengenai standar biaya yang tidak mengatur talenta.</li><li><strong>B</strong>: Merupakan regulasi perbendaharaan operasional anggaran eselon satu.</li><li><strong>C</strong>: Merupakan surat edaran internal di luar format peraturan menteri keuangan.</li><li><strong>E</strong>: Merupakan keputusan kepala badan diklat teknis pembelajaran aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 33) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peraturan Menteri Keuangan Republik Indonesia Nomor • Manajemen Talenta di lingkungan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penerbitan Peraturan Menteri Keuangan Nomor 38 Tahun 2025 mencabut dua regulasi manajemen talenta sebelumnya, yaitu...", "options": ["PMK Nomor 10/PMK.01/2012 dan PMK Nomor 20/PMK.01/2014", "PMK Nomor 45/PMK.01/2015 dan PMK Nomor 50/PMK.01/2018", "PMK Nomor 75/PMK.01/2013 dan PMK Nomor 85/PMK.01/2015", "PMK Nomor 90/PMK.01/2010 dan PMK Nomor 95/PMK.01/2012", "PMK Nomor 60/PMK.01/2016 dan PMK Nomor 161/PMK.01/2017"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Ketentuan Terdahulu yang Dicabut PMK 38/2025</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PMK Nomor 38 Tahun 2025 <strong>mencabut 2 ketentuan sebelumnya yaitu PMK Nomor 60/PMK.01/2016 tentang Manajemen Talenta Kementerian Keuangan stdd PMK Nomor 161/PMK.01/2017</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan dasar transisi hukum regulasi manajemen talenta Kemenkeu. Ketentuan lama PMK 60/2016 dan perubahannya PMK 161/2017 digantikan seutuhnya oleh PMK 38/2025 demi keselarasan dengan UU ASN Nomor 20 Tahun 2023. Aturan menyatakan yang dicabut adalah PMK 60/2016 stdd PMK 161/2017. Oleh karena itu, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. PMK Nomor 60/PMK.01/2016 dan PMK Nomor 161/PMK.01/2017</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan nomor regulasi fiktif yang tidak mengatur manajemen talenta.</li><li><strong>B</strong>: Merupakan nomor PMK yang mengatur pengelolaan anggaran kas negara.</li><li><strong>C</strong>: Merupakan regulasi tata naskah dinas di luar ruang lingkup suksesi talenta.</li><li><strong>D</strong>: Merupakan peraturan administrasi kantor era awal reformasi kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 33) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mencabut 2 (dua) ketentuan sebelumnya • Peraturan Menteri Keuangan Republik Indonesia Nomor 60/PMK.01/2016 • Peraturan Menteri Keuangan Republik Indonesia Nomor</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Manajemen Talenta di Kementerian Keuangan diselenggarakan berdasarkan asas keterbukaan dan keadilan guna...", "options": ["Menjamin objektivitas pembinaan karier aparatur kementerian", "Membatasi hak mutasi penugasan pegawai pada instansi daerah", "Menetapkan penempatan jabatan struktural berdasarkan domisili", "Mengurangi alokasi belanja pendidikan dan pelatihan dinas", "Menyeragamkan pola kenaikan pangkat tanpa evaluasi kompetensi"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Karakteristik Rencana Suksesi di Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Manajemen Talenta Kemenkeu diselenggarakan secara objektif, terencana, terbuka, tepat waktu, akuntabel, bebas intervensi politik, dan <strong>bersih dari praktik korupsi, kolusi, dan nepotisme</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan tujuan mendasar penerapan manajemen talenta dalam menjamin objektivitas pembinaan karier pegawai. Penilaian yang transparan dan terukur memastikan setiap aparatur memiliki peluang yang setara untuk berkembang sesuai kapasitas dan kompetensinya. Prinsip sistem merit menegaskan pentingnya objektivitas dalam pengembangan karier pegawai. Oleh sebab itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Menjamin objektivitas pembinaan karier aparatur kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Manajemen talenta justru mendorong mobilitas talenta yang dinamis dan bukan membatasi mutasi.</li><li><strong>C</strong>: Penempatan jabatan didasarkan pada kompetensi dan kinerja pegawai, bukan faktor kedekatan domisili.</li><li><strong>D</strong>: Pengembangan kompetensi merupakan bagian integral dari pembinaan talenta dan bukan untuk dikurangi.</li><li><strong>E</strong>: Pola karier menuntut diferensiasi berbasis capaian kinerja nyata dan bukan penyeragaman otomatis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 33) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>succession planning yang objektif, terencana, terbuka • tepat waktu, dan akuntabel</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kriteria utama PNS Kementerian Keuangan yang dipersiapkan melalui manajemen talenta untuk mengisi jabatan struktural berdampak signifikan adalah...", "options": ["Memiliki masa kerja terlama di unit pusat kementerian", "Memiliki kualifikasi, kompetensi, dan kinerja yang optimal", "Memiliki kedekatan personal dengan pejabat pembina utama", "Pernah menjabat sebagai ketua panitia peringatan hari besar", "Memiliki sertifikat penghargaan kejuaraan olahraga dinas"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kriteria Pegawai Pengisi Jabatan Strategis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Manajemen talenta mencari PNS Kementerian Keuangan terbaik yang <strong>memiliki kualifikasi, kompetensi, dan kinerja optimal untuk mengisi jabatan struktural yang berdampak secara signifikan</strong> terhadap visi kementerian.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan segitiga emas kriteria meritokrasi modern (kualifikasi, kompetensi, kinerja). Pejabat pada posisi berdampak signifikan (*impactful position*) wajib membuktikan keunggulan rekam jejak capaian kerja nyata dan kecakapan kepemimpinan. Regulasi mensyaratkan kualifikasi, kompetensi, dan kinerja optimal. Oleh karena itu, opsi B adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Memiliki kualifikasi, kompetensi, dan kinerja yang optimal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan ukuran senioritas pasif yang tidak menjamin mutu kepemimpinan.</li><li><strong>C</strong>: Merupakan relasi subjektif yang dilarang dalam sistem manajemen talenta.</li><li><strong>D</strong>: Merupakan kepanitiaan seremonial yang bukan indikator capaian kinerja strategis.</li><li><strong>E</strong>: Merupakan kegiatan ekstrakurikuler dinas di luar kompetensi jabatan publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 33) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pegawai Negeri Sipil Kementerian Keuangan terbaik • kualifikasi, kompetensi, dan kinerja optimal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama penyiapan talenta terbaik untuk menduduki jabatan struktural strategis di Kementerian Keuangan adalah...", "options": ["Menghabiskan alokasi anggaran perjalanan dinas tahunan", "Mengurangi beban jam kerja staf pelaksana operasional", "Menunjang pencapaian visi, misi, dan strategi kementerian", "Memperbanyak jumlah unit kerja baru di daerah pelosok", "Menambah porsi belanja pengadaan kendaraan dinas pimpinan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tujuan Penyiapan Calon Pemimpin Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Talenta terbaik disiapkan guna mengisi posisi strategis yang <strong>berdampak secara signifikan terhadap pencapaian visi, misi, dan strategi Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan orientasi akhir dari manajemen talenta yaitu efektivitas organisasi dalam mengelola keuangan negara. Posisi kunci yang dipegang oleh figur kompeten memastikan visi pengawalan fiskal negara dapat tercapai prima. Regulasi menegaskan orientasinya pada pencapaian visi, misi, dan strategi kementerian. Dengan demikian, opsi C adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>C. Menunjang pencapaian visi, misi, dan strategi kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penyerapan anggaran formalitas yang menyalahi efisiensi belanja.</li><li><strong>B</strong>: Merupakan pembagian tugas teknis rutin di luar sasaran strategis kementerian.</li><li><strong>D</strong>: Merupakan pemekaran struktur tanpa pertimbangan efektivitas tata kelola.</li><li><strong>E</strong>: Merupakan pengeluaran barang dinas konsumtif yang tidak berkaitan dengan visi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 33) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>yang berdampak secara signifikan • terhadap pencapaian visi, misi, dan strategi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pelaksanaan asesmen kompetensi dalam Manajemen Talenta Kementerian Keuangan bertujuan utama untuk...", "options": ["Menentukan besaran tarif potongan iuran kesehatan pegawai", "Menyeleksi calon penerima fasilitas perumahan dinas satker", "Menetapkan kriteria penugasan keanggotaan tim panitia lelang", "Memetakan profil kecakapan dan potensi kepemimpinan pegawai", "Menilai tingkat kepatuhan jam kerja operasional harian kantor"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Dasar Pemberian Remunerasi Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Asesmen kompetensi dilaksanakan untuk <strong>mengetahui profil kompetensi dan potensi Pegawai</strong> sebagai dasar penempatan dalam *talent pool*.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan fungsi strategis uji asesmen kompetensi manajerial dan sosial kultural dalam manajemen talenta. Pemetaan profil keahlian dan potensi kepemimpinan menjadi dasar penempatan pegawai dalam kotak talenta (*nine box matrix*) secara tepat sasaran. Regulasi menetapkan asesmen bertujuan memetakan kompetensi dan potensi pegawai aparatur. Dengan demikian, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Memetakan profil kecakapan dan potensi kepemimpinan pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Iuran kesehatan diatur berdasarkan persentase gaji pokok dan bukan melalui uji asesmen.</li><li><strong>B</strong>: Fasilitas rumah dinas dialokasikan berdasar ketersediaan aset dan bukan hasil asesmen talenta.</li><li><strong>C</strong>: Sertifikasi pengadaan barang dan jasa memiliki instrumen tersendiri di luar asesmen manajerial.</li><li><strong>E</strong>: Kepatuhan jam kerja diukur melalui presensi digital dan bukan instrumen asesmen potensi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 33) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemberian remunerasi berbasis • peringkat jabatan dan kinerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Payung hukum undang-undang utama yang mendasari transformasi manajemen ASN dan talenta di Kementerian Keuangan saat ini adalah...", "options": ["Undang-Undang Nomor 8 Tahun 1974 tentang Pokok Kepegawaian", "Undang-Undang Nomor 43 Tahun 1999 tentang Perubahan Kepegawaian", "Undang-Undang Nomor 17 Tahun 2003 tentang Keuangan Negara", "Undang-Undang Nomor 1 Tahun 2004 tentang Perbendaharaan Negara", "Undang-Undang Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Payung Hukum Utama Pengelolaan ASN Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Implementasi sistem merit dan manajemen talenta Kementerian Keuangan diselenggarakan <strong>sesuai Undang-Undang Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan regulasi payung tertinggi aparatur negara terkini yang menggantikan UU No 5/2014. UU 20/2023 memperkenalkan paradigma baru mobilitas talenta, fleksibilitas pengisian jabatan, dan penguatan digitalisasi ASN. Undang-undang yang tepat adalah UU Nomor 20 Tahun 2023. Dengan demikian, opsi E adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>E. Undang-Undang Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan undang-undang kepegawaian era Orde Baru yang telah dicabut lama.</li><li><strong>B</strong>: Merupakan regulasi era transisi reformasi yang telah digantikan regulasi baru.</li><li><strong>C</strong>: Merupakan payung hukum pengelolaan fiskal dan anggaran keuangan negara.</li><li><strong>D</strong>: Merupakan undang-undang tata laksana kebendaharaan umum negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 33) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Undang-Undang Nomor 20 Tahun 2023 • tentang Aparatur Sipil</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Instrumen pemetaan talenta yang digunakan untuk mengelompokkan pegawai berdasarkan kinerja dan potensi adalah...", "options": ["Matriks sembilan kotak manajemen talenta aparatur", "Daftar urut kepangkatan pegawai struktural kementerian", "Bagan hierarki komando operasional satuan kerja dinas", "Formulir sasaran kinerja pegawai tahunan terpadu", "Tabel analisis beban kerja dan evaluasi formasi unit"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Cakupan Penempatan Manajemen Talenta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pemetaan talenta dilakukan menggunakan <strong>matriks 9 (sembilan) kotak (*nine-box matrix*)</strong> yang memadukan sumbu kinerja dan sumbu potensi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan instrumen diagnostik standar dalam manajemen talenta aparatur sipil negara. Matriks sembilan kotak memadukan evaluasi capaian kinerja pada satu sumbu dan potensi pengembangan pada sumbu lainnya guna menentukan strategi pembinaan pegawai. Ketentuan manajemen talenta menetapkan pemetaan dilakukan melalui matriks sembilan kotak. Oleh karena itu, opsi A merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Matriks sembilan kotak manajemen talenta aparatur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Daftar urut kepangkatan hanya memuat urutan administratif kepangkatan dan bukan pemetaan potensi.</li><li><strong>C</strong>: Bagan hierarki komando menggambarkan jalur struktural wewenang dan bukan instrumen talenta.</li><li><strong>D</strong>: Sasaran kinerja pegawai hanya mengukur dimensi capaian kerja tanpa mengukur sumbu potensi.</li><li><strong>E</strong>: Analisis beban kerja digunakan untuk menghitung kebutuhan jumlah pegawai dan bukan kotak talenta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 33) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>posisi lain yang dianggap strategis • oleh Kementerian</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Sistem pengelolaan SDM untuk mencari, mengelola, mengembangkan, mempertahankan, dan mengevaluasi PNS Kemenkeu terbaik disebut...", "options": ["Sistem administrasi mutasi kedaerahan terpadu", "Manajemen pembinaan sanksi pelanggaran presensi", "Manajemen Talenta di lingkungan Kementerian Keuangan", "Tata kelola pengadaan barang logistik operasional", "Sistem informasi pengelolaan kas perbendaharaan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Definisi Manajemen Talenta Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Manajemen Talenta di lingkungan Kementerian Keuangan Republik Indonesia merupakan serangkaian sistem pengelolaan SDM untuk mencari, mengelola, mengembangkan, mempertahankan, dan mengevaluasi PNS Kemenkeu terbaik yang dipersiapkan sebagai calon pemimpin masa depan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan rumusan definisi resmi manajemen talenta Kemenkeu. Konsep ini mencakup siklus hulu ke hilir (*end-to-end talent lifecycle*), mulai dari identifikasi kandidat unggul hingga retensi kepemimpinan. Regulasi merumuskannya sebagai Manajemen Talenta Kemenkeu. Dengan demikian, opsi C adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>C. Manajemen Talenta di lingkungan Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan prosedur teknis perpindahan tugas geografis aparatur dinas.</li><li><strong>B</strong>: Merupakan penegakan tata tertib presensi kerja bawahan di unit kantor.</li><li><strong>D</strong>: Merupakan manajemen pengadaan fasilitas sarana dan prasarana kantor.</li><li><strong>E</strong>: Merupakan sistem aplikasi pembukuan kas pada direktorat perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 34) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mengelola, mengembangkan, mempertahankan, dan mengevaluasi • terbaik yang dipersiapkan sebagai calon pemimpin</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pegawai yang berada pada kotak talenta tertinggi (kotak 9) dikategorikan sebagai pegawai dengan kualifikasi...", "options": ["Potensi tinggi namun kinerja operasional masih berkembang", "Kinerja memuaskan dengan potensi manajerial tingkat dasar", "Potensi memadai dan kinerja sesuai standar target dinas", "Kinerja luar biasa dan potensi kepemimpinan sangat tinggi", "Kinerja unggul namun belum mengikuti asesmen kompetensi"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Fokus Sasaran Jabatan Target Talent</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kotak 9 (kelompok talenta unggul) merupakan kategori bagi pegawai dengan <strong>kinerja di atas ekspektasi dan potensi tinggi</strong>, yang siap dipromosikan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan karakteristik profil pegawai pada puncak matriks manajemen talenta (*top talent*). Pegawai pada kotak ini telah membuktikan capaian kinerja luar biasa melampaui target serta memiliki kapasitas kepemimpinan yang matang untuk suksesi jabatan. Ketentuan manajemen talenta menetapkan kotak sembilan berisi pegawai berkinerja unggul dan berpotensi tinggi. Dengan demikian, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Kinerja luar biasa dan potensi kepemimpinan sangat tinggi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Potensi tinggi dengan kinerja berkembang menempati kuadran kotak yang berbeda.</li><li><strong>B</strong>: Kinerja memuaskan dengan potensi dasar menempati area tengah matriks talenta.</li><li><strong>C</strong>: Potensi memadai dengan kinerja standar menempati kotak kategori pegawai solid.</li><li><strong>E</strong>: Seluruh pegawai dalam matriks talenta wajib telah menyelesaikan tahapan asesmen kompetensi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 34) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>menyiapkan talent untuk mengisi posisi yang setingkat lebih tinggi • lain yang dianggap strategis</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Rencana suksesi dalam manajemen talenta dipersiapkan oleh instansi pemerintah secara terencana untuk...", "options": ["Menghapus keberadaan jenjang jabatan struktural menengah", "Menentukan alokasi pagu belanja operasional perjalanan dinas", "Mengurangi beban tugas administratif pada satuan kerja daerah", "Membatasi mobilitas penugasan karier lintas kementerian", "Mengisi lowongan jabatan target melalui kelompok talenta"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Definisi Yuridis Sistem Merit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Rencana suksesi disusun untuk <strong>mempersiapkan suksesor yang siap menduduki jabatan target (*critical position*)</strong> berdasarkan pemetaan kelompok talenta.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan fungsi strategis rencana suksesi dalam menjamin keberlanjutan kepemimpinan organisasi (*leadership continuity*). Pemetaan kader siap promosi dari kelompok talenta unggul memastikan posisi strategis lowong dapat segera terisi oleh figur yang kompeten dan berintegritas. Ketentuan perundang-undangan menetapkan rencana suksesi ditujukan untuk mengisi jabatan target melalui kelompok talenta. Oleh sebab itu, opsi E merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Mengisi lowongan jabatan target melalui kelompok talenta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rencana suksesi tidak bertujuan menghapus hierarki jabatan struktural dalam birokrasi.</li><li><strong>B</strong>: Alokasi anggaran belanja perjalanan dinas diatur dalam ketentuan standar biaya masukan.</li><li><strong>C</strong>: Beban tugas administratif diselesaikan melalui efisiensi proses bisnis dan digitalisasi kerja.</li><li><strong>D</strong>: Rencana suksesi justru membuka mobilitas talenta aparatur secara dinamis dan transparan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 34) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kebijakan dalam Manajemen Aparatur Sipil Negara • kualifikasi, kompetensi, dan kinerja secara adil dan wajar</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C4 - Analisis", "question": "Faktor yang secara tegas DILARANG menjadi pembeda dalam penerapan Sistem Merit manajemen ASN adalah...", "options": ["Latar belakang politik, ras, agama, status nikah, atau disabilitas", "Tingkat kesesuaian kualifikasi pendidikan dengan syarat jabatan", "Hasil skor penilaian kompetensi manajerial dalam asesmen resmi", "Capaian predikat penilaian kinerja tahunan yang terdokumentasi", "Pemenuhan standar sertifikasi keahlian teknis yang dipersyaratkan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Asas Non-Diskriminasi Sistem Merit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Sistem Merit diterapkan secara adil dan wajar <strong>tanpa membedakan latar belakang politik, ras, warna kulit, agama, asal usul, jenis kelamin, status pernikahan, umur atau kondisi kecacatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan atribut identitas sosial dan kondisi fisik yang dilindungi dari segala bentuk diskriminasi birokrasi. Penilaian pegawai murni bertumpu pada kompetensi dan kinerja, bukan identitas personal atau preferensi politik. Oleh karena itu, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Latar belakang politik, ras, agama, status nikah, atau disabilitas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan parameter objektif kualifikasi yang sah dan wajib diuji.</li><li><strong>C</strong>: Merupakan tolak ukur kompetensi yang wajib menjadi dasar pemeringkatan.</li><li><strong>D</strong>: Merupakan bukti kontribusi kinerja nyata yang wajib menjadi dasar promosi.</li><li><strong>E</strong>: Merupakan bukti legalitas penguasaan keahlian teknis formasi jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 34) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tanpa membedakan latar belakang politik, ras, warna kulit, agama • status pernikahan, umur atau kondisi kecacatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penerapan sistem merit melalui manajemen talenta ditujukan untuk memastikan jabatan target diduduki oleh...", "options": ["Pegawai berdasar masa kerja penugasan terlama di instansi", "Pegawai yang tepat pada waktu yang tepat secara profesional", "Aparatur yang memiliki jaringan kemitraan bisnis luas", "Pegawai senior yang telah menyelesaikan seluruh diklat teknis", "Staf yang ditunjuk langsung melalui rekomendasi asosiasi"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Prinsip The Right Man on The Right Place and Time</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Manajemen talenta bertujuan menempatkan <strong>pegawai yang tepat pada jabatan yang tepat (*right person on the right place*) pada waktu yang tepat</strong> sejalan tata kelola yang baik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan prinsip penempatan pegawai presisi dalam tata kelola sumber daya manusia modern. Penempatan tepat sasaran waktu mencegah terjadinya kekosongan kepemimpinan serta menjamin kesiapan kompetensi pejabat baru dalam memimpin unit kerja. Ketentuan manajemen talenta menegaskan tujuan penempatan pegawai yang tepat pada waktu yang tepat. Dengan demikian, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Pegawai yang tepat pada waktu yang tepat secara profesional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masa kerja semata tidak menjamin kesesuaian kompetensi aparatur terhadap jabatan target.</li><li><strong>C</strong>: Kemitraan bisnis privat bukan kriteria penempatan jabatan dalam birokrasi pemerintahan.</li><li><strong>D</strong>: Kelulusan diklat merupakan komponen penunjang kualifikasi dan bukan satu-satunya penentu penempatan.</li><li><strong>E</strong>: Penunjukan langsung tanpa proses asesmen bertentangan dengan prinsip meritokrasi terbuka.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 34) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>memastikan bahwa jabatan target setingkat lebih • pada waktu yang tepat sejalan dengan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Undang-Undang Nomor 20 Tahun 2023, ruang lingkup Manajemen ASN minimal terdiri atas...", "options": ["Tiga tahapan terbatas pada pengadaan, penggajian, dan pensiun", "Lima tahapan seleksi, penempatan, rotasi, sanksi, dan pemecatan", "Delapan siklus pengelolaan SDM terpadu dari perencanaan hingga pensiun", "Sepuluh tahapan verifikasi dokumen dan pengawasan aset dinas", "Dua belas tahapan pendidikan vokasi militer keprajuritan dinas"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Delapan Siklus Manajemen ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Manajemen ASN minimal terdiri atas <strong>8 siklus</strong>: <strong>1. perencanaan kebutuhan, 2. pengadaan, 3. penguatan budaya kerja & citra institusi, 4. pengelolaan kinerja, 5. pengembangan talenta & karier, 6. pengembangan kompetensi, 7. pemberian penghargaan & pengakuan, dan 8. pemberhentian</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan arsitektur siklus hidup aparatur sipil negara yang utuh sesuai UU 20/2023. Kedelapan elemen memastikan pengelolaan SDM aparatur berjalan integratif dari saat masuk hingga purnatugas. Aturan merumuskan manajemen ASN minimal terdiri atas 8 siklus tersebut. Oleh karena itu, opsi C adalah ketetapan yang tepat.</p><p>Jawaban tepat: <strong>C. Delapan siklus pengelolaan SDM terpadu dari perencanaan hingga pensiun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan penyederhanaan usang yang mengabaikan siklus budaya dan talenta.</li><li><strong>B</strong>: Merupakan tahapan parsial yang tidak memuat perencanaan dan kompetensi.</li><li><strong>D</strong>: Merupakan fungsi verifikasi administratif di luar konsep siklus ASN.</li><li><strong>E</strong>: Merupakan kurikulum militer yang tidak berlaku bagi korps sipil ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 34) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Manajemen ASN minimal terdiri atas • perencanaan kebutuhan • pengadaan • penguatan budaya kerja dan citra institusi • pengelolaan kinerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Salah satu komponen baru yang masuk ke dalam delapan siklus Manajemen ASN menurut UU Nomor 20 Tahun 2023 adalah...", "options": ["Penyediaan fasilitas perumahan dinas permanen bagi aparatur", "Pemberian dispensasi jam kerja operasional kantor pelayanan", "Pembentukan unit advokasi bantuan politik kepegawaian dinas", "Penguatan budaya kerja dan citra institusi aparatur negara", "Penataan sistem pengadaan perlengkapan kantor secara mandiri"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Siklus Penguatan Budaya Kerja dan Citra Institusi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Siklus Manajemen ASN menurut UU 20/2023 mencakup komponen baru yaitu: <strong>Penguatan budaya kerja dan citra institusi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan pembaruan fundamental dalam regulasi aparatur sipil negara modern. Budaya kerja berorientasi pelayanan dan integritas serta reputasi positif institusi diposisikan sebagai pilar strategis penentu keberhasilan transformasi birokrasi. Ketentuan perundang-undangan mencantumkan penguatan budaya kerja dan citra institusi sebagai bagian siklus resmi manajemen ASN. Dengan demikian, opsi D merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>D. Penguatan budaya kerja dan citra institusi aparatur negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyediaan rumah dinas merupakan aspek pengelolaan aset dan bukan siklus manajemen ASN.</li><li><strong>B</strong>: Dispensasi jam kerja bukan komponen siklus pembinaan sumber daya manusia aparatur.</li><li><strong>C</strong>: Pembentukan unit bantuan politik melanggar asas netralitas aparatur sipil negara.</li><li><strong>E</strong>: Pengadaan perlengkapan kantor termasuk siklus pengelolaan barang milik negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 34) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Manajemen ASN minimal terdiri atas • penguatan budaya kerja dan citra institusi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dua siklus Manajemen ASN yang berfokus langsung pada peningkatan kapasitas dan penataan jalur penugasan aparatur adalah...", "options": ["Pengadaan logistik dinas dan penataan tata ruang gedung", "Pengelolaan data kepegawaian dan penyusunan formasi unit", "Penetapan standar kinerja dan evaluasi kepatuhan presensi", "Pemberian penghargaan disiplin dan pengelolaan arsip satker", "Pengembangan talenta karier serta pengembangan kompetensi"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pengembangan Talenta dan Kompetensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Dua siklus yang berfokus pada kapabilitas aparatur adalah <strong>pengembangan talenta dan karier</strong> serta <strong>pengembangan kompetensi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan pilar investasi modal manusia dalam birokrasi aparatur sipil negara. Siklus pengembangan kompetensi membekali aparatur dengan pengetahuan dan keterampilan mutakhir, sedangkan pengembangan talenta menata penempatan karier pegawai secara optimal. Ketentuan perundang-undangan menetapkan pengembangan talenta/karier dan kompetensi sebagai siklus pembinaan aparatur. Oleh sebab itu, opsi E merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Pengembangan talenta karier serta pengembangan kompetensi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Logistik dan tata ruang gedung merupakan aspek penunjang sarana prasarana perkantoran.</li><li><strong>B</strong>: Basis data kepegawaian dan formasi merupakan komponen perencanaan kebutuhan pegawai.</li><li><strong>C</strong>: Standar kinerja dan presensi merupakan komponen siklus pengelolaan kinerja aparatur.</li><li><strong>D</strong>: Penghargaan dan arsip merupakan bagian komponen kesejahteraan dan tata usaha kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 34) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Manajemen ASN minimal terdiri atas • pengembangan talenta dan karier • pengembangan kompetensi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Bagi pegawai yang masuk ke dalam kelompok talenta unggul (talent pool), kementerian memberikan kesempatan akselerasi melalui...", "options": ["Pengembangan karier dan peningkatan kompetensi terencana", "Pemberian hak menentukan penempatan tugas secara mandiri", "Dispensasi pembebasan kewajiban evaluasi capaian kinerja", "Fasilitasi promosi langsung tanpa verifikasi syarat jabatan", "Pemberian tunjangan khusus di luar batas pagu anggaran sah"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemberian Kesempatan Melalui Program Pengembangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pegawai dalam *talent pool* diprioritaskan untuk <strong>pengembangan karier dan peningkatan kompetensi secara terencana</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan bentuk pembinaan institusi yang terstruktur bagi para kader aparatur berkinerja unggul. Pegawai dalam *talent pool* dipersiapkan melalui penugasan strategis lintas unit, pelatihan kepemimpinan lanjutan, serta akselerasi karier yang transparan dan akuntabel. Ketentuan manajemen talenta menetapkan prioritas bagi talenta unggul adalah pengembangan karier dan kompetensi terencana. Dengan demikian, opsi A merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Pengembangan karier dan peningkatan kompetensi terencana</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penempatan tugas pegawai tetap didasarkan pada kebutuhan organisasi dan formasi yang lowong.</li><li><strong>C</strong>: Setiap pegawai ASN tanpa terkecuali wajib dievaluasi kinerjanya secara berkala.</li><li><strong>D</strong>: Promosi jabatan tetap wajib memenuhi persyaratan kualifikasi, rekam jejak, dan integritas.</li><li><strong>E</strong>: Pemberian hak keuangan aparatur wajib berpedoman ketat pada pagu peraturan perundang-undangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 34) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Manajemen Talenta juga mengembangkan Pegawai dengan memberikan • kesempatan pengembangan karier dan peningkatan kompetensi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dua tahapan penutup yang melengkapi delapan siklus Manajemen ASN menurut ketentuan undang-undang adalah...", "options": ["Pemberian dana pinjaman koperasi dan pengadaan kendaraan dinas", "Pemberian penghargaan dan pengakuan serta pemberhentian pegawai", "Pemberian dispensasi presensi kerja dan penghapusan sistem grading", "Pembubaran organisasi kementerian dan likuidasi rekening kas", "Penyerahan wewenang dinas kepada perusahaan konsultan swasta"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Dua Siklus Terakhir Manajemen ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Dua siklus terakhir dalam 8 siklus manajemen ASN adalah <strong>butir 7: pemberian penghargaan dan pengakuan</strong>, serta <strong>butir 8: pemberhentian</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan siklus apresiasi dan pelepasan resmi pengabdian aparatur. Negara memberikan apresiasi atas dedikasi dan menutup masa bakti dengan pemberhentian yang terhormat serta hak jaminan hari tua. Aturan merumuskan siklus 7 dan 8 adalah penghargaan/pengakuan dan pemberhentian. Oleh karena itu, opsi B adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. Pemberian penghargaan dan pengakuan serta pemberhentian pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan urusan pembiayaan koperasi di luar siklus pokok hukum ASN.</li><li><strong>C</strong>: Merupakan tindakan perusakan tata kelola aparatur yang menyalahi aturan.</li><li><strong>D</strong>: Merupakan tindakan likuidasi kementerian yang tidak ada kaitannya.</li><li><strong>E</strong>: Merupakan privatisasi wewenang negara yang dilarang undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 34) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Manajemen ASN minimal terdiri atas • pemberian penghargaan dan pengakuan • pemberhentian</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kewajiban instansi pemerintah dalam menyusun rencana kebutuhan Pegawai ASN didasarkan pada landasan hukum...", "options": ["Pasal 10 Undang-Undang Nomor 17 Tahun 2003", "Pasal 15 Undang-Undang Nomor 1 Tahun 2004", "Pasal 32 Undang-Undang Nomor 20 Tahun 2023", "Pasal 25 Peraturan Pemerintah Nomor 11 Tahun 2017", "Pasal 5 Peraturan Presiden Nomor 16 Tahun 2018"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Dasar Kewajiban Penyusunan Rencana Kebutuhan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Instansi Pemerintah menyusun rencana kebutuhan Pegawai ASN sesuai dengan kebijakan perencanaan kebutuhan Pegawai</strong> sebagaimana ditegaskan dalam <strong>Pasal 32 UU 20 Tahun 2023</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan pasal rujukan yuridis utama dalam undang-undang ASN terbaru yang mewajibkan instansi pemerintah memetakan kebutuhan pegawai secara berkesinambungan. Regulasi yang tepat adalah Pasal 32 UU Nomor 20 Tahun 2023. Dengan demikian, opsi C adalah ketetapan yang benar.</p><p>Jawaban tepat: <strong>C. Pasal 32 Undang-Undang Nomor 20 Tahun 2023</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pasal dalam undang-undang keuangan negara mengenai kekuasaan fiskal.</li><li><strong>B</strong>: Merupakan pasal undang-undang perbendaharaan negara mengenai pejabat perbendaharaan.</li><li><strong>D</strong>: Merupakan pasal peraturan pemerintah tentang manajemen PNS di luar rujukan undang-undang.</li><li><strong>E</strong>: Merupakan pasal perpres pengadaan barang dan jasa mengenai jenis pengadaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 35) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Instansi Pemerintah menyusun rencana kebutuhan Pegawai ASN • Pasal 32 UU 20 Tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pedoman resmi penghitungan dan pengusulan formasi kebutuhan Jabatan Fungsional di Kementerian Keuangan diatur melalui...", "options": ["Keputusan Direktur Jenderal Pajak Nomor 12 Tahun 2018", "Surat Edaran Kepala Badan Kebijakan Fiskal Nomor 3 Tahun 2019", "Peraturan Direktur Jenderal Bea Cukai Nomor 8 Tahun 2021", "Peraturan Menteri Keuangan Nomor 37/PMK.01/2020", "Instruksi Menteri Keuangan mengenai Tata Naskah 2017"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pedoman Kebutuhan Jabatan Fungsional Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Perhitungan kebutuhan Jabatan Fungsional Kementerian Keuangan diatur dengan <strong>Peraturan Menteri Keuangan Nomor 37/PMK.01/2020 tentang Pedoman Penghitungan dan Pengusulan Kebutuhan Jabatan Fungsional pada Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan regulasi operasional menteri yang menetapkan rumus analisis beban kerja dan formasi kebutuhan fungsional di seluruh unit eselon satu Kemenkeu. Regulasi yang tepat adalah PMK Nomor 37/PMK.01/2020. Oleh karena itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Peraturan Menteri Keuangan Nomor 37/PMK.01/2020</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan keputusan teknis perpajakan eselon satu di luar pedoman JF kementerian.</li><li><strong>B</strong>: Merupakan surat edaran internal perumusan kebijakan fiskal makro.</li><li><strong>C</strong>: Merupakan regulasi kepabeanan dan cukai untuk pengawasan kepelabuhanan.</li><li><strong>E</strong>: Merupakan instruksi administrasi tata surat-menyurat kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 35) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peraturan Menteri Keuangan Nomor 37/PMK.01/2020 • Pedoman Penghitungan dan Pengusulan Kebutuhan Jabatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pola pelatihan dasar bagi Calon Pegawai Negeri Sipil di Kementerian Keuangan saat ini menerapkan kurikulum...", "options": ["Peraturan Lembaga Administrasi Negara Nomor 1 Tahun 2021", "Keputusan Menteri Pertahanan Republik Indonesia Nomor 10 Tahun 2015", "Peraturan Menteri Pendidikan dan Kebudayaan Nomor 12 Tahun 2018", "Instruksi Kepala Kepolisian Negara Republik Indonesia 2019", "Peraturan Badan Nasional Penanggulangan Bencana 2020"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Regulasi Pelatihan Dasar CPNS Terbaru</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Dalam pengadaan PNS, Kementerian Keuangan menerapkan pola baru latsar ASN berdasarkan <strong>Peraturan Lembaga Administrasi Negara (LAN) RI Nomor 1 Tahun 2021 tentang Pelatihan Dasar Calon Pegawai Negeri Sipil</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan dasar hukum kurikulum Latsar CPNS nasional yang diselenggarakan secara terpadu (*blended learning* dan internalisasi nilai BerAKHLAK). LAN berkedudukan sebagai instansi pembina diklat aparatur sipil nasional. Regulasi yang tepat adalah Peraturan LAN Nomor 1 Tahun 2021. Dengan demikian, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Peraturan Lembaga Administrasi Negara Nomor 1 Tahun 2021</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan regulasi militer pertahanan negara di luar instansi pembina Latsar sipil.</li><li><strong>C</strong>: Merupakan regulasi sistem persekolahan umum kementerian pendidikan.</li><li><strong>D</strong>: Merupakan instruksi kepolisian mengenai pendidikan pembentukan bintara/perwira.</li><li><strong>E</strong>: Merupakan aturan kebencanaan di luar ruang lingkup kediklatan CPNS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 35) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Negara Republik Indonesia Nomor 1 Tahun • Calon Pegawai Negeri Sipil</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pengelompokan rumpun jabatan di lingkungan Kementerian Keuangan ke dalam Job Family ditetapkan melalui...", "options": ["Surat Edaran Direktur Jenderal Anggaran Nomor 1 Tahun 2011", "Keputusan Menteri Keuangan Nomor 461/KMK.01/2013", "Peraturan Menteri PAN-RB Nomor 20 Tahun 2015", "Keputusan Kepala BKN mengenai Kenaikan Pangkat 2016", "Instruksi Presiden mengenai Efisiensi Anggaran 2014"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Job Family Kementerian Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Di Kementerian Keuangan, jabatan telah dikelompokkan dalam <strong>Job Family Kementerian Keuangan berdasarkan Keputusan Menteri Keuangan (KMK) Nomor 461/KMK.01/2013</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan regulasi dasar pengelompokan rumpun pekerjaan di Kemenkeu yang membagi klaster tugas ke dalam famili jabatan sejenis guna memudahkan penataan pola karier dan mutasi. Regulasi yang tepat adalah KMK Nomor 461/KMK.01/2013. Oleh karena itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Keputusan Menteri Keuangan Nomor 461/KMK.01/2013</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan edaran teknis anggaran belanja unit eselon satu Kemenkeu.</li><li><strong>C</strong>: Merupakan regulasi kementerian pendayagunaan aparatur tingkat makro.</li><li><strong>D</strong>: Merupakan keputusan kepala BKN tentang kenaikan pangkat pegawai negeri.</li><li><strong>E</strong>: Merupakan instruksi penghematan belanja operasional kantor kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 35) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Beberapa jabatan sudah dikelompokkan dalam Job • KMK Nomor 461/KMK.01/2013</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ruang lingkup Jabatan Karier dalam pola mutasi di lingkungan Kementerian Keuangan mencakup...", "options": ["Seluruh jabatan pimpinan tinggi madya dan staf penasihat", "Tenaga staf khusus menteri dan pimpinan lembaga nonstruktural", "Jabatan struktural eselon II, III, IV, V, dan fungsional", "Seluruh pegawai berstatus tenaga alih daya dan staf kontrak", "Pegawai penugasan khusus pada badan usaha milik negara"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Definisi Jabatan Karier di Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Jabatan Karier di Kemenkeu meliputi <strong>jabatan struktural eselon II, III, IV, dan V, serta jabatan fungsional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan batasan yuridis cakupan jabatan karier aparatur sipil negara di lingkungan Kementerian Keuangan. Jabatan karier menaungi pegawai berstatus ASN tetap yang meniti tangga kepangkatan struktural dan keahlian fungsional dalam struktur kementerian. Ketentuan regulasi menetapkan ruang lingkup jabatan karier mencakup eselon II sampai V dan jabatan fungsional. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Jabatan struktural eselon II, III, IV, V, dan fungsional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Staf penasihat ahli merupakan jabatan nonkarier tertentu di luar tangga jabatan struktural.</li><li><strong>B</strong>: Staf khusus menteri merupakan posisi politis penugasan khusus yang bukan jabatan karier ASN.</li><li><strong>D</strong>: Tenaga alih daya dan staf kontrak terikat perjanjian kerja pihak ketiga dan bukan jabatan karier.</li><li><strong>E</strong>: Penugasan BUMN merupakan penugasan luar instansi dan bukan klasifikasi internal jabatan karier.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 35) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Jabatan karier • jabatan struktural eselon II, eselon III,</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam tata aturan kepegawaian Kementerian Keuangan, batasan pengertian dari Pola Mutasi adalah...", "options": ["Penyesuaian formasi penugasan demi pemerataan beban kerja", "Mekanisme perpindahan tugas atas dasar permohonan mandiri", "Proses seleksi penerimaan calon aparatur sipil negara baru", "Sistem pemindahan pegawai yang terencana dan berjenjang", "Pola penataan karier berdasar evaluasi tahunan kementerian"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Definisi Pola Mutasi Terencana</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pola Mutasi adalah <strong>pola pemindahan Pegawai dari suatu jabatan ke jabatan lain yang setara, baik di dalam satu unit kerja maupun antarunit kerja, yang terencana dan berjenjang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan definisi baku pola mutasi kepegawaian dalam regulasi internal kementerian. Kebijakan mutasi dirancang secara sistematis untuk memperluas wawasan tugas aparatur, mencegah kejenuhan kerja, serta memperkuat koordinasi kelembagaan secara berjenjang. Ketentuan tata aturan kepegawaian mendefinisikan pola mutasi sebagai sistem pemindahan pegawai yang terencana dan berjenjang. Oleh sebab itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Sistem pemindahan pegawai yang terencana dan berjenjang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemerataan beban kerja merupakan dampak operasional dan bukan rumusan definisi pola mutasi.</li><li><strong>B</strong>: Mutasi pegawai merupakan kewenangan dinas instansi dan bukan mekanisme permohonan bebas.</li><li><strong>C</strong>: Seleksi penerimaan baru merupakan tahapan pengadaan CPNS dan bukan perpindahan mutasi.</li><li><strong>E</strong>: Penataan berdasar evaluasi tahunan merupakan manajemen kinerja dan bukan pengertian mutasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 35) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pola mutasi adalah • sistem pemindahan PNS dalam jabatan karier</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pedoman Pelaksanaan Mutasi dan Promosi Internal serta Antar-Unit bagi PNS di lingkungan Kementerian Keuangan diatur melalui...", "options": ["Keputusan Menteri Keuangan Nomor 7/KMK.01/2023", "Peraturan Direktur Jenderal Perbendaharaan Nomor 4 Tahun 2021", "Surat Edaran Kepala Badan Kebijakan Fiskal Nomor 9 Tahun 2020", "Peraturan Menteri Koordinator Perekonomian Nomor 2 Tahun 2022", "Keputusan Bersama Pimpinan Badan Pemeriksa Keuangan 2019"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pedoman Mutasi dan Promosi Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pedoman mutasi dan promosi internal serta antar-unit diatur melalui <strong>KMK Nomor 7/KMK.01/2023 tentang Pedoman Pelaksanaan Mutasi dan Promosi Internal dan antar-unit bagi PNS di lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan dasar operasional biro SDM Kemenkeu dalam menyusun pertimbangan mutasi dan promosi jabatan aparatur. KMK ini memuat 17 prinsip pertimbangan mutasi internal dan lintas eselon satu. Regulasi yang tepat adalah KMK Nomor 7/KMK.01/2023. Dengan demikian, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Keputusan Menteri Keuangan Nomor 7/KMK.01/2023</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan aturan perbendaharaan operasional lingkup eselon satu DJPb.</li><li><strong>C</strong>: Merupakan surat edaran internal perumusan kerangka makro fiskal.</li><li><strong>D</strong>: Merupakan kementerian koordinator di luar regulasi internal kementerian keuangan.</li><li><strong>E</strong>: Merupakan lembaga auditor eksternal negara di luar struktur eksekutif Kemenkeu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 35) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>KMK 7/KMK.01/2023 • dan Promosi Internal dan antar-unit bagi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C4 - Analisis", "question": "Berdasarkan Pasal 19 PP Nomor 11 Tahun 2017, alur tahapan pengadaan PNS yang BUKAN merupakan prosedur resmi adalah...", "options": ["Perencanaan formasi dan pengumuman lowongan", "Penerimaan uang jaminan seleksi ke panitia lokal", "Pelamaran berkas dan pelaksanaan seleksi tes", "Pengumuman hasil seleksi dan masa percobaan dinas", "Pengangkatan resmi menjadi Pegawai Negeri Sipil"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tahapan Lengkap Pengadaan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengadaan PNS melalui: <strong>perencanaan, pengumuman lowongan, pelamaran, seleksi, pengumuman hasil seleksi, masa percobaan, dan pengangkatan menjadi PNS</strong> (Pasal 19 PP 11/2017).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan praktik pungutan liar dan tindak pidana korupsi yang bertentangan mutlak dengan prinsip seleksi bersih bebas biaya. Seluruh proses pengadaan CPNS dibiayai anggaran negara secara transparan. Oleh karena itu, opsi B adalah pilihan jawaban pengecualian yang benar.</p><p>Jawaban tepat: <strong>B. Penerimaan uang jaminan seleksi ke panitia lokal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tahapan pembuka analisis formasi dan publikasi akses warga.</li><li><strong>C</strong>: Merupakan tahapan pendaftaran administrasi dan ujian kompetensi terukur.</li><li><strong>D</strong>: Merupakan pengumuman kelulusan berbasis skor CAT dan masa percobaan 1 tahun.</li><li><strong>E</strong>: Merupakan tahap akhir penerbitan SK pengangkatan PNS definitif oleh PPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 35) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengadaan PNS dilakukan melalui tahapan • lowongan, pelamaran, seleksi, pengumuman hasil seleksi,</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tujuan mendasar dari penyelenggaraan kegiatan pengadaan ASN adalah...", "options": ["Mengisi formasi kebutuhan pegawai pada instansi pemerintah", "Menyerap anggaran belanja pegawai operasional pada akhir tahun", "Memfasilitasi penempatan kerja bagi lulusan sekolah kedinasan", "Menambah jumlah staf administrasi penunjang satuan kerja", "Menggantikan seluruh posisi pegawai yang memasuki masa pensiun"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tujuan Pengadaan ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengadaan ASN bertujuan untuk <strong>mengisi kebutuhan formasi Pegawai ASN pada Instansi Pemerintah</strong> sesuai analisis jabatan dan beban kerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan esensi utama pengadaan aparatur sipil negara dalam birokrasi pemerintahan. Rekrutmen diselenggarakan murni untuk memenuhi lowongan formasi jabatan yang dibutuhkan unit organisasi demi keberlangsungan pelayanan publik dan tugas pemerintahan. Ketentuan perundang-undangan menetapkan pengadaan ASN bertujuan mengisi kebutuhan pegawai pada instansi pemerintah. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Mengisi formasi kebutuhan pegawai pada instansi pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pengadaan aparatur berorientasi pada pemenuhan SDM dan bukan sarana penyerapan anggaran kas.</li><li><strong>C</strong>: Lulusan kedinasan hanya salah satu sumber pelamar dan bukan tujuan umum pengadaan ASN.</li><li><strong>D</strong>: Penambahan pegawai harus berbasis analisis jabatan dan beban kerja, bukan sekadar memperbanyak staf.</li><li><strong>E</strong>: Pengadaan tidak otomatis mengganti seluruh pensiunan melainkan mengikuti formasi yang disetujui.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 35) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengadaan ASN merupakan kegiatan untuk • mengisi kebutuhan dalam suatu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam tata kelola kepegawaian Kementerian Keuangan, definisi ringkas dari Mutasi adalah...", "options": ["Pemberhentian sementara bagi pegawai yang menghadapi masalah pidana", "Pemindahan Pegawai Negeri Sipil dalam lingkup jabatan karier", "Pemberian kenaikan pangkat pengabdian saat pegawai wafat tugas", "Pemberian cuti istimewa di luar tanggungan negara selama satu tahun", "Penurunan kelas jabatan karena tidak mencapai target kinerja"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Definisi Mutasi Kedinasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Dalam Pola Mutasi Kemenkeu dirumuskan secara tegas bahwa <strong>Mutasi adalah pemindahan PNS dalam jabatan karier</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan pengertian substantif mutasi kedinasan. Mutasi menggerakkan pegawai antartugas, antarjabatan, atau antarlokasi dalam koridor jabatan struktural maupun fungsional secara dinamis. Aturan merumuskan mutasi sebagai pemindahan PNS dalam jabatan karier. Oleh karena itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Pemindahan Pegawai Negeri Sipil dalam lingkup jabatan karier</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan status pemberhentian sementara tersangka dan bukan mutasi karier.</li><li><strong>C</strong>: Merupakan jenis kenaikan pangkat anumerta atau pengabdian saat pensiun.</li><li><strong>D</strong>: Merupakan fasilitas izin bebas tugas cuti CLTN tanpa penggajian negara.</li><li><strong>E</strong>: Merupakan penyesuaian kelas grading akibat penurunan kinerja atau demosi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 35) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Mutasi adalah • pemindahan PNS dalam jabatan karier</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan KMK Nomor 7/KMK.01/2023, pola penyegaran mutasi diterapkan bagi PNS Kemenkeu yang telah menduduki jabatan selama...", "options": ["Di atas satu tahun masa penugasan satker", "Di atas dua tahun masa dinas operasional", "Di atas tiga tahun masa evaluasi berkala", "Di atas empat tahun masa dinas aktif kantor", "Di atas lima tahun masa jabatan berjalan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Masa Jabatan Pola Penyegaran Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>KMK 7/KMK.01/2023 menetapkan pertimbangan mutasi mencakup <strong>pola penyegaran bagi PNS Kemenkeu yang telah memiliki masa jabatan di atas 5 (lima) tahun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan batas waktu optimal untuk mencegah kejenuhan kerja (*burnout*) dan potensi zona nyaman yang mengikis objektivitas tugas. Setelah lima tahun, pegawai dipindahkan ke pos baru untuk memperkaya pengalaman organisasi. Aturan menetapkan ambangnya adalah di atas 5 tahun. Oleh karena itu, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Di atas lima tahun masa jabatan berjalan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan durasi minimal satu tahun yang terlalu dini untuk penyegaran.</li><li><strong>B</strong>: Merupakan masa dinas normal jangka pendek dalam suatu unit kerja.</li><li><strong>C</strong>: Merupakan batas waktu evaluasi jabatan tertentu di wilayah sulit.</li><li><strong>D</strong>: Merupakan siklus empat tahunan kenaikan pangkat reguler pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pola penyegaran bagi PNS Kemenkeu • di atas 5 (lima) tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Masa kerja penugasan bagi pegawai pelaksana PNS Kemenkeu di unit kerja wilayah sulit perhubungan dan berisiko tinggi/konflik dibatasi paling lama...", "options": ["Paling lama satu tahun masa tugas", "Paling lama lima tahun masa penugasan", "Paling lama tujuh tahun masa penugasan", "Paling lama sepuluh tahun masa tugas", "Paling lama tiga tahun masa penugasan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Masa Tugas Pelaksana di Wilayah Sulit/Konflik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Masa kerja PNS Kemenkeu di wilayah sulit perhubungan dan berisiko tinggi/konflik adalah <strong>bagi pegawai pelaksana/setingkat paling lama 3 (tiga) tahun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan batas proteksi fisik dan psikologis bagi staf pelaksana yang bertugas di medan sulit atau rawan konflik perbatasan. Pembatasan maksimal 3 tahun menjamin kepastian rotasi kembali ke wilayah normal. Regulasi menetapkan durasinya paling lama 3 tahun. Dengan demikian, opsi E adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>E. Paling lama tiga tahun masa penugasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan durasi satu tahun yang belum menuntaskan siklus penugasan daerah.</li><li><strong>B</strong>: Merupakan batas masa jabatan pola penyegaran normal dan bukan daerah konflik.</li><li><strong>C</strong>: Merupakan durasi berlebih yang membahayakan ketahanan fisik pegawai pelaksana.</li><li><strong>D</strong>: Merupakan kurun waktu sepuluh tahun yang melanggar prinsip rotasi perlindungan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>wilayah sulit • pelaksana/setingkat paling lama 3 (tiga) tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Bagi pejabat administrator dan pengawas yang bertugas di unit kerja wilayah sulit perhubungan dan berisiko tinggi/konflik, masa tugasnya dibatasi paling lama...", "options": ["Enam bulan kalender kepemimpinan", "Paling lama dua tahun masa penugasan", "Paling lama empat tahun masa tugas", "Paling lama lima tahun masa tugas", "Paling lama enam tahun masa tugas"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Masa Tugas Administrator/Pengawas Wilayah Sulit/Konflik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Bagi <strong>pejabat administrator/pengawas/setingkat</strong>, masa kerja penugasan di wilayah sulit perhubungan dan berisiko tinggi/konflik dibatasi <strong>paling lama 2 (dua) tahun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan kebijakan khusus bagi pejabat manajerial di medan berisiko tinggi. Karena beban tanggung jawab mengendalikan kantor dan memimpin personel di wilayah sulit sangat berat, masa penugasan dibatasi lebih singkat (2 tahun) dibanding pelaksana (3 tahun). Aturan menyebutkan paling lama 2 tahun. Oleh karena itu, opsi B adalah ketetapan yang tepat.</p><p>Jawaban tepat: <strong>B. Paling lama dua tahun masa penugasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan waktu terlalu pendek yang tidak efektif untuk stabilitas kantor.</li><li><strong>C</strong>: Merupakan masa empat tahun yang melampaui batas maksimal pejabat di medan sulit.</li><li><strong>D</strong>: Merupakan siklus lima tahunan wilayah normal dan tidak berlaku di medan konflik.</li><li><strong>E</strong>: Merupakan durasi enam tahun yang melanggar batas regulasi KMK 7/2023.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>wilayah sulit • administrator/pengawas/setingkat paling lama 2 (dua) tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ketentuan batas masa penempatan atau penugasan PNS Kementerian Keuangan pada unit organisasi non-eselon adalah...", "options": ["Paling lama satu tahun tanpa kemungkinan perpanjangan", "Permanen seumur hidup tanpa proses evaluasi berkala", "Paling lama 3 tahun dan dapat diperpanjang tiap 2 tahun", "Paling lama lima tahun penuh mengikuti periode menteri", "Dua tahun kalender dan wajib dilakukan rotasi satker"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Masa Penugasan pada Unit Organisasi Non-Eselon</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Masa penugasan PNS Kemenkeu pada unit non-eselon adalah <strong>paling lama 3 (tiga) tahun dan dapat diperpanjang setiap 2 (dua) tahun</strong> berdasarkan evaluasi kinerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jangka waktu penugasan aparatur pada struktur organisasi non-eselon yang ditetapkan regulasi internal kementerian. Batasan durasi 3 tahun dengan opsi perpanjangan per 2 tahun memberikan kepastian operasional unit kerja khusus sekaligus menjaga rotasi dinamis aparatur. Ketentuan regulasi menetapkan masa penugasan non-eselon paling lama 3 tahun dan dapat diperpanjang tiap 2 tahun. Dengan demikian, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Paling lama 3 tahun dan dapat diperpanjang tiap 2 tahun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Satu tahun merupakan evaluasi kinerja berkala dan bukan batas maksimal penugasan unit non-eselon.</li><li><strong>B</strong>: Penugasan non-eselon tidak bersifat permanen guna mencegah stagnasi karier aparatur.</li><li><strong>D</strong>: Periode menteri tidak menentukan masa tugas operasional penugasan pegawai pada unit non-eselon.</li><li><strong>E</strong>: Rotasi wajib dua tahun tidak sejalan dengan jangka waktu maksimal 3 tahun yang diatur regulasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kemenkeu pada unit organisasi non-eselon • paling lama 3 (tiga) tahun dan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Demi stabilitas persiapan pensiun, mutasi PNS Kemenkeu mempertimbangkan batas usia pegawai menjelang BUP yaitu paling sedikit...", "options": ["Enam bulan kalender sebelum masa pensiun", "Satu tahun dinas sebelum memasuki usia pensiun", "Dua tahun berjalan sebelum tanggal pensiun", "Tiga tahun sebelum memasuki batas usia pensiun", "Lima tahun penuh sebelum masa pensiun tiba"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Batas Usia Menjelang Pensiun Pertimbangan Mutasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pertimbangan mutasi memperhatikan <strong>batas usia paling sedikit 3 (tiga) tahun sebelum PNS Kemenkeu memasuki batas usia pensiun</strong>, kecuali terdapat kebutuhan organisasi atau ditentukan lain.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan perlakuan humanis dan efisiensi anggaran negara. Pegawai yang tinggal 3 tahun menjelang pensiun sedapat mungkin tidak dipindahtugaskan ke daerah jauh agar dapat mempersiapkan transisi masa purnatugas bersama keluarga di domisili tetapnya. Aturan menyebutkan batas usia minimal 3 tahun sebelum BUP. Oleh karena itu, opsi D adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>D. Tiga tahun sebelum memasuki batas usia pensiun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan masa menjelang pensiun yang terlalu mepet bagi perencanaan mutasi.</li><li><strong>B</strong>: Merupakan masa pembekalan pensiun dan bukan ambang proteksi mutasi 3 tahun.</li><li><strong>C</strong>: Merupakan batas dua tahun yang tidak sesuai dengan KMK 7/2023.</li><li><strong>E</strong>: Merupakan batas lima tahun yang terlalu panjang dan membatasi mobilitas normal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>batas usia paling sedikit 3 (tiga) tahun sebelum • memasuki batas usia pensiun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam KMK 7/KMK.01/2023, elemen rekam jejak perilaku yang secara eksplisit dinilai dalam mutasi dan promosi pegawai adalah...", "options": ["Jumlah pengikut akun media sosial dan frekuensi siaran langsung", "Kepemilikan aset perangkat gawai pintar merek luar negeri", "Kemampuan meretas situs internet milik kementerian negara lain", "Status keanggotaan dalam forum transaksi mata uang digital", "Rekam jejak digital (digital footprint) dan integritas pegawai"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pertimbangan Rekam Jejak Digital (Digital Footprint)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Mutasi dan promosi Kemenkeu mempertimbangkan <strong>rekam jejak mengenai perilaku dan/atau disiplin PNS Kemenkeu, termasuk rekam jejak digital (digital footprint) dan integritas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan instrumen penyaringan modern aparatur di era digital. Aktivitas pegawai di ruang siber (seperti ujaran kebencian, radikalisme, atau pelanggaran kode etik di media sosial) menjadi bahan pertimbangan integritas sebelum dipromosikan atau dimutasi. Aturan menegaskan penilaian mencakup digital footprint dan integritas. Dengan demikian, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Rekam jejak digital (digital footprint) dan integritas pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan popularitas media sosial pribadi tanpa relevansi kinerja dinas.</li><li><strong>B</strong>: Merupakan kepemilikan gawai pribadi yang bukan standar rekam jejak etik.</li><li><strong>C</strong>: Merupakan tindakan peretasan ilegal yang melanggar hukum siber pidana.</li><li><strong>D</strong>: Merupakan aktivitas finansial privat di luar indikator rekam jejak disiplin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>rekam jejak mengenai perilaku dan/atau disiplin PNS Kemenkeu • rekam jejak digital (digital footprint) dan integritas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Biro SDM Kemenkeu menjadwalkan gelombang mutasi pegawai luar daerah pada bulan Juni-Juli dan Desember. Pertimbangan humanis yang mendasarinya adalah...", "options": ["Menyesuaikan dengan masa pergantian tahun ajaran sekolah anak", "Pertimbangan jarak tempuh geografis antar-satuan kerja wilayah", "Menghabiskan alokasi anggaran tiket pesawat sebelum audit BPK", "Menunggu selesainya perayaan pesta rakyat di ibu kota provinsi", "Memberikan kesempatan pegawai berlibur panjang bersama keluarga"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pertimbangan Tahun Ajaran Sekolah Anak dalam Mutasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Mutasi Kemenkeu mempertimbangkan aspek kesejahteraan keluarga yaitu <strong>masa pergantian tahun ajaran/periode pendidikan per semester bagi keluarga inti dari PNS Kemenkeu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan kebijakan pro-keluarga (*family-friendly policy*) dalam manajemen ASN Kemenkeu. Penyelenggaraan mutasi diselaraskan dengan kalender kenaikan kelas dan kelulusan anak agar proses perpindahan sekolah tidak terganggu di tengah semester. Regulasi menegaskan pertimbangan pergantian tahun ajaran pendidikan keluarga. Oleh karena itu, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Menyesuaikan dengan masa pergantian tahun ajaran sekolah anak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Faktor geografis diperhitungkan dalam analisis kebutuhan namun bukan penghambat rotasi berkala.</li><li><strong>C</strong>: Merupakan motif serapan anggaran yang keliru dalam perencanaan SDM.</li><li><strong>D</strong>: Merupakan agenda pesta kultural yang tidak berkaitan dengan regulasi dinas.</li><li><strong>E</strong>: Merupakan liburan pribadi yang tidak menjadi landasan penetapan mutasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>masa pergantian tahun ajaran/periode pendidikan per semester • keluarga inti dari PNS Kemenkeu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Dua orang PNS Kemenkeu yang berstatus suami-istri mengajukan penempatan kerja di wilayah kota yang sama. Dasar pertimbangan yang sah adalah...", "options": ["Preferensi lokasi domisili pribadi keluarga pegawai", "Pasangan suami istri di Kemenkeu dan aspek kemanusiaan", "Rekomendasi tertulis dari perhimpunan pegawai kementerian", "Hak penentuan tempat dinas berdasar masa kerja penugasan", "Tingkat senioritas kepangkatan tertinggi dalam keluarga"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pertimbangan Pasangan Suami Istri dan Aspek Kemanusiaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pertimbangan mutasi dapat memperhatikan <strong>pasangan suami/istri yang bekerja di lingkungan Kementerian Keuangan dan aspek kemanusiaan</strong>, dengan tetap memperhatikan kebutuhan organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan kebijakan humanis dalam manajemen pola mutasi Kementerian Keuangan. Pimpinan memberikan ruang pertimbangan bagi keutuhan keluarga aparatur sesama pegawai Kemenkeu dengan tetap memprioritaskan kepentingan efektivitas organisasi. Ketentuan KMK Pola Mutasi menetapkan pertimbangan pasangan suami/istri di Kemenkeu dan aspek kemanusiaan sebagai dasar yang sah. Oleh sebab itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Pasangan suami istri di Kemenkeu dan aspek kemanusiaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Preferensi domisili pribadi bukan dasar pertimbangan hukum resmi dalam penataan formasi.</li><li><strong>C</strong>: Perhimpunan pegawai tidak memiliki kewenangan formal memberikan rekomendasi mutasi aparatur.</li><li><strong>D</strong>: Masa kerja penugasan tidak memberikan hak menentukan sendiri lokasi penempatan kerja.</li><li><strong>E</strong>: Senioritas kepangkatan bukan parameter dalam penyatuan lokasi kerja pasangan aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pasangan suami/istri yang bekerja di lingkungan Kementerian Keuangan • dan di luar Kementerian Keuangan dan/atau aspek kemanusiaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam pelaksanaan mutasi penugasan PNS Kemenkeu ke satuan kerja baru, pembiayaan perpindahan dinas diatur dengan ketentuan...", "options": ["Seluruh biaya transportasi ditanggung mandiri oleh pegawai", "Biaya perpindahan ditanggung bersama mitra kerja di daerah", "Penyediaan anggaran biaya perpindahan bagi PNS bersangkutan", "Pembiayaan diakumulasikan dalam tunjangan kinerja bulanan", "Pegawai menanggung separuh biaya dan separuh dari instansi"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kewajiban Penyediaan Biaya Perpindahan Mutasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS yang dimutasi <strong>diberikan biaya perpindahan</strong> yang dibebankan pada anggaran instansi sesuai peraturan perundang-undangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan hak perlindungan finansial bagi aparatur yang berpindah tugas demi kepentingan dinas negara. Karena mutasi dilaksanakan atas perintah kedinasan, negara berkewajiban menanggung biaya perjalanan, akomodasi, serta pengangkutan barang pindahan pegawai beserta keluarganya. Ketentuan perundang-undangan menetapkan penyediaan anggaran biaya perpindahan bagi PNS yang dimutasi. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Penyediaan anggaran biaya perpindahan bagi PNS bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembiayaan mandiri membebani aparatur dan melanggar hak perlindungan perjalanan dinas pindah.</li><li><strong>B</strong>: Mitra kerja pihak swasta dilarang membiayai perjalanan dinas pegawai demi mencegah gratifikasi.</li><li><strong>D</strong>: Biaya perpindahan dialokasikan terpisah dalam pos belanja perjalanan dinas pindah, bukan tunjangan.</li><li><strong>E</strong>: Skema bagi hasil biaya perjalanan pindah dinas tidak dikenal dalam tata kelola keuangan APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penyediaan anggaran untuk biaya perpindahan PNS Kemenkeu • termasuk keluarga inti dari PNS Kemenkeu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kebijakan mutasi dan promosi di Kementerian Keuangan secara terencana memprioritaskan penempatan strategis bagi...", "options": ["Pegawai dengan masa kerja pengabdian kantor paling lama", "Aparatur yang aktif dalam organisasi kepanitiaan informal", "Staf yang telah menyelesaikan pendidikan formal pascasarjana", "Talenta terbaik yang masuk ke dalam kelompok talent pool", "Pegawai dengan tingkat kepatuhan administratif tertinggi"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemberdayaan Lulusan Pascasarjana Luar Negeri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Mutasi dan promosi diprioritaskan bagi <strong>pegawai yang masuk dalam kelompok rencana suksesi (*talent pool*)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan perwujudan meritokrasi dalam penempatan jabatan strategis kementerian. Pegawai yang telah teruji kompetensi dan kinerjanya serta menempati kuadran talenta terbaik diprioritaskan mengisi posisi kepemimpinan untuk menjamin keberhasilan kinerja instansi. Ketentuan pola karier memprioritaskan penempatan bagi talenta terbaik dalam *talent pool*. Dengan demikian, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Talenta terbaik yang masuk ke dalam kelompok talent pool</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masa kerja lama tanpa diimbangi kinerja unggul bukan prioritas utama dalam sistem merit.</li><li><strong>B</strong>: Keaktifan organisasi informal bukan parameter formal penilaian manajemen talenta kementerian.</li><li><strong>C</strong>: Gelar pascasarjana merupakan kualifikasi pendidikan dan bukan jaminan prioritas promosi.</li><li><strong>E</strong>: Kepatuhan administratif merupakan kewajiban dasar seluruh pegawai dan bukan tolok ukur promosi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 36) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pascasarjana luar negeri • PNS Kemenkeu yang berprestasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan PMK Nomor 224/PMK.01/2020, pola karier horizontal didefinisikan sebagai perpindahan jabatan yang...", "options": ["Menuju posisi jabatan lain yang setara dalam atau antarkelompok", "Menuju posisi jabatan yang lebih tinggi dalam satu kelompok jabatan", "Menuju posisi jabatan yang lebih tinggi antarkelompok JA, JF, atau JPT", "Menuju posisi jabatan struktural eselon satu di kementerian koordinator", "Menuju posisi staf pelaksana dengan penurunan kelas jabatan aktif"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Definisi Pola Karier Horizontal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Pola karier horizontal</strong> adalah <strong>perpindahan dari satu posisi Jabatan ke posisi Jabatan lain yang setara, baik di dalam satu kelompok maupun antar-kelompok JA, JF, atau JPT</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan rumusan resmi perpindahan mendatar (*lateral move*). Pola horizontal memperluas wawasan kerja dan pengalaman teknis aparatur pada level tanggung jawab yang setingkat tanpa perubahan kelas grading. Regulasi menegaskan polanya menuju posisi jabatan lain yang setara. Dengan demikian, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>A. Menuju posisi jabatan lain yang setara dalam atau antarkelompok</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan definisi pola karier vertikal dalam satu kelompok jabatan.</li><li><strong>C</strong>: Merupakan definisi pola karier diagonal antarkelompok jabatan berbeda.</li><li><strong>D</strong>: Merupakan promosi jabatan pimpinan tinggi madya dan bukan pola horizontal.</li><li><strong>E</strong>: Merupakan demosi penurunan kelas jabatan akibat pelanggaran disiplin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>horizontal, yaitu perpindahan dari satu posisi • baik di dalam satu kelompok maupun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perpindahan dari satu posisi jabatan ke posisi jabatan lain yang lebih tinggi di dalam satu kelompok JA, JF, atau JPT disebut pola karier...", "options": ["Diagonal antarkelompok fungsional", "Semi horizontal unit non-eselon", "Vertikal dalam satu rumpun kelompok", "Rotasi berkala penyegaran wilayah", "Sirkuler lintas kementerian negara"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Definisi Pola Karier Vertikal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Pola karier vertikal</strong> adalah <strong>perpindahan dari satu posisi Jabatan ke posisi Jabatan yang lain yang lebih tinggi, di dalam satu kelompok JA, JF, atau JPT</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan promosi jenjang berjenjang dalam jalur yang sama (misal dari JF Ahli Muda ke JF Ahli Madya, atau Pengawas ke Administrator). Kenaikan ini menguji peningkatan kematangan pada rumpun profesi serupa. Regulasi merumuskannya sebagai pola karier vertikal. Oleh karena itu, opsi C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Vertikal dalam satu rumpun kelompok</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan perpindahan ke jabatan lebih tinggi namun menyeberang antarkelompok.</li><li><strong>B</strong>: Merupakan perpindahan setingkat yang melibatkan unit organisasi non-eselon.</li><li><strong>D</strong>: Merupakan pemindahan horizontal penyegaran tugas tanpa kenaikan jenjang.</li><li><strong>E</strong>: Merupakan istilah fiktif di luar taksonomi pola karier PMK 224/2020.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>vertikal, yaitu perpindahan dari satu posisi • di dalam satu kelompok JA, JF, atau JPT</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perpindahan dari satu posisi jabatan ke posisi jabatan lain yang LEBIH TINGGI ANTAR-KELOMPOK JA, JF, atau JPT merupakan bentuk pola karier...", "options": ["Horizontal internal fungsional", "Semi horizontal antarsatker", "Vertikal berjenjang keahlian", "Statis administratif pelaksana", "Diagonal antarkelompok jabatan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Definisi Pola Karier Diagonal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>Pola karier diagonal</strong> adalah <strong>perpindahan dari satu posisi Jabatan ke posisi Jabatan lain yang lebih tinggi antar-kelompok JA, JF, atau JPT</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan mobilitas karier silang promosi (misalnya seorang pejabat fungsional ahli madya dipromosikan menduduki jabatan pimpinan tinggi pratama/eselon II). Perpindahan ini menaikkan jenjang sekaligus melintasi rumpun kelompok. Aturan menetapkan bentuknya adalah pola diagonal. Dengan demikian, opsi E adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>E. Diagonal antarkelompok jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan perpindahan mendatar setara tanpa kenaikan jenjang jabatan.</li><li><strong>B</strong>: Merupakan perpindahan setara dengan unit non-eselon khusus Kemenkeu.</li><li><strong>C</strong>: Merupakan promosi dalam satu kelompok internal yang sama.</li><li><strong>D</strong>: Merupakan istilah pasif di luar konsep pola karier kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>diagonal, yaitu perpindahan dari satu posisi • antar-kelompok JA, JF, atau JPT</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat administrator Kemenkeu dipindahtugaskan menduduki jabatan manajer pada unit organisasi non-eselon yang dianggap setingkat. Pola karier ini berbentuk...", "options": ["Pola karier vertikal mutlak dalam kelompok fungsional", "Pola mutasi demosi penurunan tanggung jawab manajerial", "Pola karier diagonal terbuka tingkat kementerian koordinator", "Pola karier transisi pensiun di luar tanggungan negara", "Pola karier semi horizontal di lingkungan Kementerian Keuangan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pola Karier Semi Horizontal Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pola karier <strong>semi horizontal</strong> meliputi perpindahan <strong>dari JA, JF, atau JPT ke posisi Jabatan pada Unit non Eselon yang dianggap setingkat</strong>, atau sebaliknya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan pola khas Kemenkeu untuk menunjang fleksibilitas pengisian unit-unit non-eselon (seperti BLU atau Badan Pengelola Dana). Karena level tanggung jawabnya setara dengan jabatan struktural/fungsional asalnya, perpindahan ini dikategorikan semi horizontal. Aturan merumuskannya sebagai pola karier semi horizontal. Oleh karena itu, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Pola karier semi horizontal di lingkungan Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan promosi vertikal jabatan yang menghasilkan kenaikan tingkatan.</li><li><strong>B</strong>: Merupakan hukuman penurunan jabatan yang keliru diterapkan pada penugasan BLU.</li><li><strong>C</strong>: Merupakan promosi silang antarkelompok jabatan ke level lebih tinggi.</li><li><strong>D</strong>: Merupakan status menjelang purnatugas CLTN tanpa pelaksanaan tugas aktif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Semi horizontal, yaitu perpindahan dari dan ke Jabatan lain • dari JA, JF, atau JPT ke</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat pengawas (eselon IV) Kemenkeu dipromosikan menjadi Direktur Eksekutif unit non-eselon yang dianggap setara eselon III. Bentuk pola karier ini adalah...", "options": ["Pola karier horizontal setingkat di satker daerah", "Pola karier linier tertutup kelompok jabatan administrasi", "Pola karier rotasi musiman perwakilan kepulauan terluar", "Pola karier semi vertikal di lingkungan Kementerian Keuangan", "Pola karier kehormatan tanpa pembebanan tanggung jawab dinas"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pola Karier Semi Vertikal Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pola karier <strong>semi vertikal</strong> meliputi perpindahan <strong>dari JA, JF, atau JPT ke posisi Jabatan pada Unit non Eselon yang dianggap lebih tinggi</strong>, atau sebaliknya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan bentuk promosi yang melibatkan struktur unit non-eselon. Kenaikan wewenang dari level pengawas ke posisi pimpinan unit non-eselon yang berbobot lebih tinggi memenuhi kriteria semi vertikal. Regulasi menetapkan polanya adalah semi vertikal. Dengan demikian, opsi D adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>D. Pola karier semi vertikal di lingkungan Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan perpindahan lateral mendatar tanpa ada kenaikan level jabatan.</li><li><strong>B</strong>: Merupakan pola tertutup konvensional di luar fleksibilitas unit non-eselon.</li><li><strong>C</strong>: Merupakan rotasi geografis biasa tanpa perubahan bobot tingkatan tugas.</li><li><strong>E</strong>: Merupakan gelar kehormatan fiktif yang tidak dikenal dalam PMK 224/2020.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Semi vertikal, yaitu perpindahan dari dan ke Jabatan lain • dari JA, JF, atau JPT ke</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pihak yang memberikan telaah dan pertimbangan resmi atas kesetaraan jabatan dalam pola karier semi horizontal dan semi vertikal adalah...", "options": ["Sekretariat Jenderal c.q. Biro Sumber Daya Manusia Kemenkeu", "Dewan Perwakilan Rakyat Komisi Keuangan dan Perbankan", "Badan Pengawasan Keuangan dan Pembangunan Perwakilan Daerah", "Asosiasi Pengusaha Mineral dan Batubara Indonesia", "Komite Nasional Keselamatan Transportasi Kementerian Perhubungan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Peran Biro SDM dalam Pola Karier Khusus</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Perpindahan dalam pola karier khusus Kemenkeu dilaksanakan sesuai <strong>pertimbangan Sekretariat Jenderal c.q. Biro Sumber Daya Manusia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan unit pengelola kepegawaian sentral di Kemenkeu yang berwenang menimbang kesetaraan kompetensi, analisis beban tugas, dan grading posisi non-eselon dengan jabatan struktural/fungsional. Aturan menetapkan pertimbangan berasal dari Setjen c.q. Biro SDM. Oleh karena itu, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Sekretariat Jenderal c.q. Biro Sumber Daya Manusia Kemenkeu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan komisi legislatif parlemen tanpa fungsi operasional SDM kementerian.</li><li><strong>C</strong>: Merupakan instansi audit internal pemerintah di luar hierarki manajemen Kemenkeu.</li><li><strong>D</strong>: Merupakan asosiasi industri swasta di luar urusan jabatan publik.</li><li><strong>E</strong>: Merupakan lembaga investigasi kecelakaan transportasi kementerian perhubungan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>perpindahan ke dalam Jabatan yang setingkat/ • pertimbangan Sekretariat Jenderal c.q. Biro Sumber</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tiga bentuk pokok pola karier aparatur sebagaimana diatur secara umum dalam PMK Nomor 224/PMK.01/2020 adalah...", "options": ["Sentralisasi, desentralisasi, dan dekonsentrasi kepegawaian", "Struktural, manajerial, dan operasional kewilayahan", "Horizontal, vertikal, dan diagonal antarkelompok jabatan", "Reguler, kehormatan, dan pengabdian purna kedinasan", "Internal, eksternal, dan internasional kenegaraan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tiga Bentuk Pokok Pola Karier PMK 224/2020</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Bentuk pola karier pokok pada PMK 224/2020 meliputi: <strong>1) horizontal (setara), 2) vertikal (lebih tinggi satu kelompok), dan 3) diagonal (lebih tinggi antarkelompok)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan klasifikasi tiga dimensi pergerakan karier baku ASN. Ketiga arah mobilitas ini mewadahi rotasi penyegaran, promosi jenjang, serta lompatan kompetensi lintas rumpun jabatan. Aturan menegaskan tiga bentuknya adalah horizontal, vertikal, dan diagonal. Dengan demikian, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Horizontal, vertikal, dan diagonal antarkelompok jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan asas hubungan kekuasaan pemerintah pusat dan daerah.</li><li><strong>B</strong>: Merupakan pembagian jenis fungsi tugas dan bukan arah pola karier.</li><li><strong>D</strong>: Merupakan rumpun jenis kenaikan pangkat pegawai negeri sipil.</li><li><strong>E</strong>: Merupakan jangkauan lokasi penugasan dan bukan geometri pola karier.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Bentuk pola karier sebagaimana diatur pada PMK 224/2020 meliputi • horizontal • vertikal • diagonal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Seorang PNS Kemenkeu telah menyelesaikan penugasan pada unit non-eselon. Penempatan kembali ke struktur reguler yang setingkat merupakan wujud...", "options": ["Pemberian sanksi pembebasan tugas dinas kementerian", "Penurunan jabatan akibat reorganisasi kelembagaan unit", "Promosi akselerasi jabatan struktural tingkat tinggi", "Penataan administratif mutasi berkala antardaerah dinas", "Jaminan perlindungan status karier kepegawaian aparatur"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kembalinya Pejabat Non-Eselon ke Jabatan Setingkat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pegawai yang selesai bertugas di unit non-eselon <strong>ditempatkan kembali pada jabatan yang setingkat dengan jabatan sebelum penugasan</strong> sebagai jaminan karier.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan kepastian hukum dan perlindungan karier bagi pegawai yang bersedia ditugaskan pada unit khusus. Setelah mandat penugasan selesai, instansi menjamin pengembalian pegawai ke struktur jabatan reguler yang setara dengan posisi sebelumnya tanpa kehilangan hak kepegawaiannya. Ketentuan regulasi menjamin perlindungan status karier melalui penempatan kembali pada jabatan setingkat. Oleh sebab itu, opsi E merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Jaminan perlindungan status karier kepegawaian aparatur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penempatan kembali bukan sanksi melainkan hak perlindungan karier pegawai purnatugas unit.</li><li><strong>B</strong>: Penempatan pada jabatan setingkat bukan bentuk penurunan jabatan struktural pegawai.</li><li><strong>C</strong>: Penempatan kembali pada jenjang setara bukan merupakan mekanisme promosi akselerasi.</li><li><strong>D</strong>: Penataan ini berfokus pada reintegrasi jabatan aparatur pasca penugasan khusus unit non-eselon.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dari Jabatan pada non Eselon ke • dengan Jabatan terakhir PNS Kemenkeu sebelum</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat pengawas terpilih menduduki Jabatan Fungsional Ahli Madya (setara Eselon III). Perpindahan ini menerapkan pola...", "options": ["Pola karier horizontal pada rumpun jabatan kerja yang sama", "Pola karier diagonal antarkelompok jabatan ke jenjang tinggi", "Pola karier vertikal murni dalam jalur struktural pengawasan", "Pola karier penyesuaian otomatis tanpa perubahan tanggung jawab", "Pola karier penugasan khusus sementara di luar instansi induk"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Contoh Penerapan Pola Karier Diagonal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Perpindahan dari Jabatan Administrasi ke Jabatan Fungsional dengan jenjang lebih tinggi merupakan <strong>pola karier diagonal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan pola mobilitas karier diagonal dalam birokrasi pemerintahan modern. Pola diagonal terjadi ketika aparatur berpindah melintasi batas kelompok jabatan (dari jabatan administrasi pengawas ke jabatan fungsional) sekaligus mengalami peningkatan kesetaraan jenjang karier yang lebih tinggi. Ketentuan hukum kepegawaian mengategorikan perpindahan antarkelompok jabatan ke jenjang lebih tinggi sebagai pola karier diagonal. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Pola karier diagonal antarkelompok jabatan ke jenjang tinggi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pola horizontal merupakan perpindahan jabatan setara dalam satu kelompok jabatan yang sama.</li><li><strong>C</strong>: Pola vertikal murni berlangsung dalam satu kelompok jabatan struktural yang sama ke jenjang atas.</li><li><strong>D</strong>: Perpindahan ini membawa konsekuensi perubahan peran substantif dan bukan penyesuaian otomatis.</li><li><strong>E</strong>: Perpindahan ini berlangsung definitif di dalam birokrasi dan bukan penugasan khusus sementara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>perpindahan dari satu posisi Jabatan ke • antar-kelompok JA, JF, atau JPT</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perpindahan penugasan PNS Kemenkeu dari satu unit non-eselon ke posisi pada unit non-eselon lainnya yang dianggap setingkat tergolong ke dalam...", "options": ["Pola mutasi vertikal promosi penjenjangan karier", "Pola penugasan lintas instansi kementerian lembaga", "Pola pengembangan kompetensi berbasis penugasan khusus", "Pola karier semi horizontal di lingkungan Kemenkeu", "Pola pergeseran diagonal antar kelompok keahlian"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Mutasi Antar-Unit Non-Eselon Setingkat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Perpindahan tugas dari jabatan pada unit non-eselon ke posisi jabatan pada unit non-eselon lainnya yang dianggap setingkat dikategorikan sebagai <strong>pola karier semi horizontal</strong> di lingkungan Kemenkeu.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Pola karier internal Kemenkeu mengakomodasi penugasan fleksibel antar-unit pelaksana teknis non-eselon. Pergeseran antar-posisi yang setingkat pada rumpun unit non-eselon tersebut dirumuskan sebagai pola karier semi horizontal.</p><p>Jawaban tepat: <strong>D. Pola karier semi horizontal di lingkungan Kemenkeu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pola vertikal mensyaratkan perpindahan ke jenjang jabatan lebih tinggi.</li><li><strong>B</strong>: Penugasan lintas instansi merujuk mutasi ke luar Kementerian Keuangan.</li><li><strong>C</strong>: Pengembangan kompetensi berfokus pada pelatihan dan magang kedinasan.</li><li><strong>E</strong>: Pola diagonal melibatkan pergeseran antar-kelompok jabatan berbeda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 37) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Semi horizontal, yaitu perpindahan dari dan ke Jabatan lain, yang • ke posisi Jabatan pada Unit non Eselon yang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi manajemen kepegawaian negara, empat jenis kenaikan pangkat bagi PNS meliputi...", "options": ["Kenaikan pangkat berkala, prestasi, jabatan, dan khusus", "Kenaikan pangkat reguler, pilihan, pengabdian, dan anumerta", "Kenaikan pangkat otomatis, penyesuaian, dinas, dan jasa", "Kenaikan pangkat fungsional, struktural, madya, dan utama", "Kenaikan pangkat administratif, kehormatan, masa, dan tugas"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Empat Jenis Kenaikan Pangkat PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kenaikan pangkat PNS terdiri atas: <strong>a. kenaikan pangkat reguler; b. kenaikan pangkat pilihan; c. kenaikan pangkat anumerta; dan d. kenaikan pangkat pengabdian</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan klasifikasi resmi empat rumpun kenaikan pangkat aparatur sipil negara dalam sistem kepegawaian nasional. Keempat jenis ini mengakomodasi kenaikan pangkat periodik rutin, apresiasi jabatan/prestasi, penghargaan purnatugas pengabdian, serta penghormatan bagi yang gugur dalam tugas. Ketentuan perundang-undangan menetapkan jenis kenaikan pangkat PNS terdiri atas reguler, pilihan, pengabdian, dan anumerta. Dengan demikian, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Kenaikan pangkat reguler, pilihan, pengabdian, dan anumerta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kenaikan pangkat berkala dan prestasi bukan nomenklatur yuridis baku dalam regulasi kepegawaian.</li><li><strong>C</strong>: Otomatis dan penyesuaian merupakan mekanisme teknis pengusulan dan bukan jenis resmi pangkat.</li><li><strong>D</strong>: Fungsional dan struktural merupakan jalur dalam kenaikan pangkat pilihan, bukan jenis pokok.</li><li><strong>E</strong>: Administratif dan kehormatan bukan pembagian empat kategori kenaikan pangkat dalam undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kenaikan pangkat merupakan penghargaan PNS • kenaikan pangkat reguler, kenaikan pangkat istimewa,</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Syarat masa dinas minimum dalam pangkat terakhir yang wajib dipenuhi seorang PNS untuk memperoleh Kenaikan Pangkat Reguler adalah...", "options": ["Sekurang-kurangnya telah berdinas satu tahun", "Sekurang-kurangnya telah bertugas dua tahun", "Sekurang-kurangnya telah berdinas tiga tahun", "Sekurang-kurangnya telah empat tahun berpangkat", "Sekurang-kurangnya telah bertugas lima tahun"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Syarat Masa Pangkat Kenaikan Reguler</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kenaikan pangkat reguler diberikan kepada PNS yang: <strong>sekurang-kurangnya telah 4 (empat) tahun dalam pangkat terakhir</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan masa tenggang kepangkatan minimum bagi pemberian kenaikan pangkat reguler pegawai negeri sipil. Periode 4 tahun dipandang sebagai siklus pengabdian wajar bagi aparatur untuk mengumpulkan pengalaman dan membuktikan kesetiaan tugas sebelum dinaikkan ke jenjang kepangkatan berikutnya. Ketentuan regulasi menetapkan syarat sekurang-kurangnya telah 4 tahun dalam pangkat terakhir. Oleh sebab itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Sekurang-kurangnya telah empat tahun berpangkat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Satu tahun belum memenuhi ambang batas waktu minimal untuk usulan kenaikan pangkat reguler.</li><li><strong>B</strong>: Dua tahun merupakan syarat untuk kenaikan pangkat pilihan jabatan tertentu dan bukan reguler.</li><li><strong>C</strong>: Tiga tahun belum mencukupi batas waktu normatif 4 tahun yang ditentukan peraturan kepegawaian.</li><li><strong>E</strong>: Lima tahun melampaui batas waktu normatif 4 tahun yang disyaratkan dalam kenaikan pangkat reguler.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kenaikan Pangkat Reguler diberikan kepada PNS yang • sekurang-kurangnya telah empat tahun dalam pangkat terakhir</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Persyaratan nilai prestasi kerja yang wajib dipenuhi oleh PNS untuk usulan kenaikan pangkat reguler adalah...", "options": ["Setiap unsur bernilai baik dalam dua tahun terakhir", "Memperoleh predikat sangat baik pada semester berjalan", "Nilai rata-rata prestasi kerja minimal berpredikat cukup", "Memiliki rekomendasi bebas temuan dari inspektorat jenderal", "Telah mempublikasikan karya tulis ilmiah terindeks nasional"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Syarat Penilaian Kinerja Kenaikan Reguler</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Syarat Kenaikan Pangkat Reguler antara lain menetapkan bahwa <strong>setiap unsur penilaian prestasi kerja bernilai baik dalam 2 (dua) tahun terakhir</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Berdasarkan regulasi kenaikan pangkat reguler PNS, rekam jejak capaian kinerja selama 2 tahun berturut-turut wajib berada pada kategori minimal 'baik' pada setiap unsurnya. Kriteria ini membuktikan konsistensi dedikasi pegawai yang bersangkutan.</p><p>Jawaban tepat: <strong>A. Setiap unsur bernilai baik dalam dua tahun terakhir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Predikat sangat baik semester berjalan tidak menghapus syarat dua tahun.</li><li><strong>C</strong>: Nilai berpredikat cukup tidak memenuhi syarat kenaikan pangkat reguler.</li><li><strong>D</strong>: Rekomendasi inspektorat adalah dokumen pengawasan bukan syarat berkas reguler.</li><li><strong>E</strong>: Karya tulis ilmiah adalah syarat kenaikan jenjang jabatan fungsional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kenaikan Pangkat Reguler diberikan kepada PNS • dua tahun terakhir bernilai baik</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Kenaikan Pangkat Pilihan dapat dianugerahkan kepada PNS yang menunjukkan prestasi kerja luar biasa baiknya, dengan bukti yuridis berupa...", "options": ["Rekomendasi tertulis dari perhimpunan profesi aparatur", "Penetapan hasil evaluasi berkala pimpinan unit kerja", "Surat keputusan yang disahkan Pejabat Pembina Kepegawaian", "Sertifikat keikutsertaan program pendidikan dinas luar", "Piagam penghargaan prestasi kerja tingkat satuan kerja"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kenaikan Pangkat Pilihan Prestasi Luar Biasa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Prestasi kerja luar biasa baiknya dibuktikan dengan <strong>surat keputusan yang ditandatangani oleh Pejabat Pembina Kepegawaian (PPK)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan instrumen legalitas formal pengakuan prestasi istimewa aparatur negara. Pengesahan keputusan oleh PPK membuktikan bahwa karya luar biasa pegawai telah diverifikasi secara objektif dan diakui secara resmi oleh pimpinan tertinggi instansi. Ketentuan perundang-undangan menegaskan bukti prestasi kerja luar biasa dibuktikan dengan surat keputusan dari Pejabat Pembina Kepegawaian. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Surat keputusan yang disahkan Pejabat Pembina Kepegawaian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rekomendasi perhimpunan profesi bukan dasar penetapan yuridis kenaikan pangkat pilihan aparatur.</li><li><strong>B</strong>: Evaluasi pimpinan unit kerja merupakan bahan usulan awal dan belum memiliki kekuatan SK PPK.</li><li><strong>D</strong>: Sertifikat diklat merupakan bukti pengembangan kompetensi dan bukan penetapan prestasi luar biasa.</li><li><strong>E</strong>: Piagam penghargaan satker merupakan apresiasi internal yang belum berkekuatan penetapan PPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kenaikan Pangkat Pilihan diberikan kepada PNS yang • menunjukkan prestasi kerja luar biasa baiknya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Seorang PNS peneliti berhasil menciptakan inovasi teknologi yang terbukti menghemat belanja negara miliaran rupiah. Pegawai bersangkutan berhak atas...", "options": ["Pemberian dana bagi hasil efisiensi anggaran belanja", "Kenaikan pangkat anumerta atas pengabdian kepada negara", "Pemberhentian dinas dengan hak pensiun dini maksimal", "Pembebasan kewajiban pelaporan kinerja instansi tahunan", "Kenaikan pangkat pilihan atas prestasi kerja luar biasa"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kenaikan Pangkat Penemuan Baru bagi Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS yang menemukan penemuan baru yang bermanfaat bagi negara berhak atas <strong>kenaikan pangkat pilihan karena menunjukkan prestasi kerja luar biasa baiknya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan bentuk penghargaan kepangkatan istimewa bagi aparatur yang menghasilkan karya inovasi strategis bagi negara. Temuan yang memberikan manfaat nyata berupa efisiensi belanja negara memenuhi kriteria prestasi kerja luar biasa sehingga pegawai layak dianugerahi kenaikan pangkat pilihan mendahului periode reguler. Ketentuan hukum menetapkan hak atas kenaikan pangkat pilihan prestasi kerja luar biasa. Dengan demikian, opsi E merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Kenaikan pangkat pilihan atas prestasi kerja luar biasa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hukum perbendaharaan negara melarang bagi hasil penghematan anggaran kas kepada pegawai pribadi.</li><li><strong>B</strong>: Kenaikan pangkat anumerta hanya dianugerahkan bagi aparatur yang gugur atau tewas dalam tugas.</li><li><strong>C</strong>: Pensiun dini diberikan atas permohonan atau reorganisasi dan bukan bentuk apresiasi inovasi.</li><li><strong>D</strong>: Pelaporan kinerja merupakan kewajiban abadi setiap aparatur tanpa pengecualian prestasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kenaikan Pangkat Pilihan diberikan kepada PNS yang • menemukan penemuan baru yang bermanfaat bagi negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Bagi PNS yang memiliki masa kerja 30 tahun atau lebih menjelang pensiun BUP, syarat waktu dalam pangkat terakhir untuk Kenaikan Pangkat Pengabdian adalah...", "options": ["Telah empat tahun penuh dalam pangkat terakhir", "Telah sekurang-kurangnya satu bulan dalam pangkat terakhir", "Telah dua tahun kalender dalam pangkat terakhir", "Telah enam bulan dinas aktif dalam pangkat terakhir", "Telah lima tahun berturut-turut dalam pangkat terakhir"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Syarat Kenaikan Pangkat Pengabdian Masa Kerja 30 Tahun</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kenaikan Pangkat Pengabdian diberikan apabila memiliki <strong>masa kerja 30 tahun atau lebih dan telah 1 (satu) bulan dalam pangkat terakhir</strong> serta tidak pernah dijatuhi hukuman disiplin sedang/berat dalam 1 tahun terakhir.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan kemudahan istimewa bagi abdi negara dengan masa kerja sangat panjang (30 tahun ke atas). Untuk menghargai loyalitas tanpa cela tersebut, syarat masa pangkat terakhir diringankan menjadi minimal 1 bulan saja sebelum pensiun/meninggal. Aturan menetapkan durasinya 1 bulan dalam pangkat terakhir. Oleh karena itu, opsi B adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>B. Telah sekurang-kurangnya satu bulan dalam pangkat terakhir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan syarat umum empat tahun pada kenaikan pangkat reguler biasa.</li><li><strong>C</strong>: Merupakan syarat masa kerja 10 tahun (telah 2 tahun dalam pangkat terakhir).</li><li><strong>D</strong>: Merupakan periode semesteran dan bukan ambang masa kerja 30 tahun.</li><li><strong>E</strong>: Merupakan syarat yang terlalu memberatkan bagi pegawai purna bakti 30 tahun.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kenaikan Pangkat Pengabdian • memiliki masa kerja 30 tahun atau lebih</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Bagi PNS yang akan mencapai batas usia pensiun dengan masa kerja 20 tahun atau lebih, syarat dalam pangkat terakhir untuk Kenaikan Pangkat Pengabdian adalah...", "options": ["Telah satu bulan kalender dalam pangkat terakhir", "Telah tiga bulan dinas dalam pangkat terakhir", "Telah enam bulan berjalan dalam pangkat terakhir", "Telah satu tahun dalam pangkat terakhir", "Telah tiga tahun penuh dalam pangkat terakhir"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Syarat Kenaikan Pangkat Pengabdian Masa Kerja 20 Tahun</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kenaikan Pangkat Pengabdian diberikan kepada PNS yang pensiun mencapai BUP <strong>apabila masa kerja 20 tahun atau lebih dan pegawai yang bersangkutan telah 1 (satu) tahun dalam pangkat terakhir</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan formula proporsionalitas masa kerja dengan syarat pangkat terakhir (20 tahun kerja = 1 tahun pangkat terakhir). Ketentuan ini memastikan kenaikan kehormatan pengabdian dinikmati aparatur yang memenuhi kedisiplinan dan masa bakti prima. Regulasi menetapkan syaratnya telah 1 tahun dalam pangkat terakhir. Dengan demikian, opsi D adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Telah satu tahun dalam pangkat terakhir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan syarat kenaikan pengabdian bagi masa kerja 30 tahun ke atas.</li><li><strong>B</strong>: Merupakan kurun waktu triwulanan yang tidak dikenal dalam formula pengabdian.</li><li><strong>C</strong>: Merupakan durasi semester yang tidak sesuai dengan ketentuan PP manajemen PNS.</li><li><strong>E</strong>: Merupakan masa tunggu tiga tahun yang melampaui ketentuan satu tahun.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kenaikan Pangkat Pengabdian • apabila masa kerja 20 tahun atau lebih</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Syarat masa pangkat terakhir bagi PNS yang memasuki masa pensiun BUP dengan masa kerja minimal 10 tahun adalah...", "options": ["Telah dua tahun dalam pangkat terakhir", "Telah empat tahun dalam pangkat terakhir", "Telah lima tahun dalam pangkat terakhir", "Telah enam bulan dalam pangkat terakhir", "Telah satu bulan dalam pangkat terakhir"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Syarat Kenaikan Pangkat Pengabdian Masa Kerja 10 Tahun</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kenaikan Pangkat Pengabdian diberikan <strong>apabila masa kerja 10 tahun atau lebih telah 2 (dua) tahun dalam pangkat terakhir</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan tangga syarat masa kerja terbawah (10 tahun kerja = 2 tahun pangkat terakhir) untuk memperoleh satu tingkat kenaikan pangkat pengabdian sebelum pensiun. Aturan mensyaratkan durasinya telah 2 tahun dalam pangkat terakhir. Oleh karena itu, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>A. Telah dua tahun dalam pangkat terakhir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan syarat kenaikan pangkat reguler empat tahunan non-jabatan.</li><li><strong>C</strong>: Merupakan masa tunggu lima tahun yang tidak sesuai dengan regulasi pensiun.</li><li><strong>D</strong>: Merupakan durasi semesteran di luar formula resmi kenaikan pengabdian.</li><li><strong>E</strong>: Merupakan syarat khusus bagi pegawai dengan masa kerja 30 tahun ke atas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kenaikan Pangkat Pengabdian • apabila masa kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C4 - Analisis", "question": "Salah satu syarat batas waktu bagi PNS untuk diusulkan memperoleh Kenaikan Pangkat Pengabdian menjelang pensiun adalah...", "options": ["Paling lama satu bulan sebelum pemberhentian tugas dinas", "Paling lama dua bulan sebelum berlakunya surat keputusan", "Paling lama satu bulan sebelum tanggal mencapai batas pensiun", "Paling lambat enam bulan sebelum berakhirnya masa jabatan", "Paling lambat tiga bulan sebelum berakhirnya tugas kedinasan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Syarat Disiplin Kenaikan Pangkat Pengabdian</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kenaikan pangkat pengabdian diusulkan <strong>paling lama 1 (satu) bulan sebelum PNS yang bersangkutan mencapai batas usia pensiun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan batas waktu administrasi pengusulan tanda jasa kenaikan pangkat pengabdian kepegawaian. Tenggat waktu satu bulan sebelum mencapai BUP menjamin proses penetapan keputusan kenaikan pangkat dan penyesuaian hak pensiun berjalan tuntas sebelum masa dinas aktif berakhir. Ketentuan perundang-undangan menetapkan batas waktu pengusulan paling lama 1 bulan sebelum mencapai batas usia pensiun. Oleh sebab itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Paling lama satu bulan sebelum tanggal mencapai batas pensiun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan rumusan baku yang diatur dalam tata tertib pengusulan pangkat pengabdian BKN.</li><li><strong>B</strong>: Tenggat dua bulan bukan batas waktu limitatif yang ditentukan dalam regulasi kepegawaian.</li><li><strong>D</strong>: Enam bulan merupakan persiapan berkas usul pensiun dan bukan batas limit pengusulan pangkat.</li><li><strong>E</strong>: Tiga bulan bukan batas akhir penerimaan berkas usulan kenaikan pangkat pengabdian PNS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tidak pernah dijatuhi hukuman disiplin sedang atau berat • PPKP bernilai baik</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam Kenaikan Pangkat Reguler, batasan kepangkatan PNS yang bersangkutan diatur dengan ketentuan ketat yaitu...", "options": ["Wajib setara dengan pangkat pejabat pembina kepegawaian", "Bebas melampaui pangkat pimpinan unit eselon dua di atasnya", "Harus lebih tinggi satu tingkat dari pangkat menteri keuangan", "Boleh melebihi pangkat kepala kantor wilayah tempat bertugas", "Tidak boleh melampaui pangkat yang dimiliki atasan langsungnya"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Batas Pangkat Atasan pada Kenaikan Reguler</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kenaikan Pangkat Reguler diberikan dengan ketentuan bahwa pangkat baru PNS yang bersangkutan <strong>tidak melampaui pangkat atasan langsungnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan asas hierarki kepemimpinan dalam birokrasi pemerintahan (*command line*). Kenaikan reguler staf pelaksana tidak boleh melampaui golongan ruang pejabat struktural yang memimpinnya secara langsung guna menjaga wibawa komando dinas. Regulasi menegaskan pangkatnya tidak melampaui pangkat atasan langsung. Oleh karena itu, opsi E adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Tidak boleh melampaui pangkat yang dimiliki atasan langsungnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pangkat menteri/kepala lembaga yang merupakan puncak tertinggi.</li><li><strong>B</strong>: Merupakan pelampauan pangkat eselon dua yang melanggar hierarki birokrasi.</li><li><strong>C</strong>: Merupakan asumsi mustahil yang bertentangan dengan struktur tata negara.</li><li><strong>D</strong>: Merupakan pelanggaran tingkatan pangkat terhadap pimpinan unit kerja wilayah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 38) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kenaikan Pangkat Reguler diberikan kepada PNS yang • tidak melampaui pangkat atasan langsungnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Seorang pejabat pengawas yang berkinerja amat baik dipromosikan menduduki Jabatan Administrator. Mekanisme promosi ini menerapkan...", "options": ["Pola karier horizontal dalam kelompok jabatan administrasi", "Pola karier diagonal melintasi kelompok jabatan fungsional", "Pola karier vertikal murni dalam struktur kepemimpinan", "Pola penugasan penyesuaian formasi jabatan kepegawaian", "Pola rotasi berkala berbasis pertimbangan kebutuhan dinas"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kriteria Kenaikan Pangkat Anumerta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Promosi dari Jabatan Pengawas ke Jabatan Administrator merupakan <strong>pola karier vertikal</strong> dalam kelompok Jabatan Manajerial.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan pola pengembangan karier bertingkat lurus ke atas dalam kelompok jabatan manajerial. Pergerakan dari jabatan pengawas ke jenjang administrator mencerminkan kenaikan tanggung jawab kepemimpinan dan hierarki wewenang dalam rumpun jabatan manajerial yang sama. Ketentuan perundang-undangan mengategorikan promosi berjenjang ini sebagai pola karier vertikal. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Pola karier vertikal murni dalam struktur kepemimpinan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pola horizontal merupakan mutasi antarnama jabatan yang setara pada jenjang yang sama.</li><li><strong>B</strong>: Pola diagonal melibatkan pergerakan antar-rumpun kelompok jabatan yang berbeda kualifikasi.</li><li><strong>D</strong>: Penyesuaian formasi merupakan proses administrasi penempatan dan bukan mekanisme promosi vertikal.</li><li><strong>E</strong>: Rotasi berkala berorientasi pada penyegaran tugas dan bukan promosi kenaikan jenjang jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kenaikan Pangkat Anumerta diberikan kepada PNS yang • dalam menjalankan tugas kewajibannya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi kepegawaian, berlakunya surat keputusan Kenaikan Pangkat Anumerta ditetapkan mulai...", "options": ["Tanggal awal tahun anggaran berikutnya setelah laporan disetujui", "Tanggal penerbitan surat penguburan oleh dinas pemakaman umum", "Tanggal satu bulan Oktober tahun pengusulan berkas kepegawaian", "Tanggal penandatanganan akta waris oleh seluruh anggota keluarga", "Tanggal Pegawai Negeri Sipil yang bersangkutan dinyatakan tewas"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Masa Berlaku Kenaikan Pangkat Anumerta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Secara yuridis, <strong>kenaikan pangkat anumerta ditetapkan berlaku mulai tanggal Pegawai Negeri Sipil yang bersangkutan tewas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan kepastian titik tolak waktu hukum perlindungan hak ahli waris. SK anumerta berlaku surut terhitung tepat sejak hari terjadinya peristiwa gugurnya pegawai saat bertugas, bukan pada tanggal penetapan SK di masa depan. Aturan menegaskan berlaku mulai tanggal PNS bersangkutan tewas. Oleh karena itu, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Tanggal Pegawai Negeri Sipil yang bersangkutan dinyatakan tewas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan siklus tahun anggaran baru yang menunda hak keuangan ahli waris.</li><li><strong>B</strong>: Merupakan urusan administrasi pemakaman di luar surat keputusan kenegaraan.</li><li><strong>C</strong>: Merupakan tanggal periodik kenaikan pangkat biasa dan bukan anumerta.</li><li><strong>D</strong>: Merupakan penetapan hukum perdata keluarga di pengadilan agama/negeri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kenaikan pangkat anumerta ditetapkan berlaku • Negeri Sipil yang bersangkutan tewas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pengertian dari Daftar Urut Kepangkatan (DUK) Pegawai Negeri Sipil adalah suatu daftar yang memuat nama-nama PNS yang...", "options": ["Mendapatkan fasilitas kredit kepemilikan rumah bersubsidi dinas", "Disusun secara hierarkis menurut tingkatan kepangkatan pegawai", "Daftar hadir presensi sidik jari pada loket pelayanan masyarakat", "Daftar pemegang rekening tabungan pensiun pada bank pemerintah", "Daftar nama pelamar yang dinyatakan gagal dalam seleksi berkas"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Definisi Daftar Urut Kepangkatan (DUK)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p><strong>DUK PNS adalah suatu daftar yang memuat nama-nama PNS dari suatu satuan organisasi negara yang disusun menurut tingkatan kepangkatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan fungsi DUK sebagai instrumen transparansi susunan kedudukan senioritas kepangkatan aparatur di suatu satker. DUK memastikan pertimbangan karier pegawai memiliki rujukan hierarkis yang objektif dan tertib administrasi. Aturan mendefinisikan DUK sebagai daftar nama yang disusun menurut tingkatan kepangkatan. Dengan demikian, opsi B adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>B. Disusun secara hierarkis menurut tingkatan kepangkatan pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan program pembiayaan perumahan sosial di luar daftar kepangkatan.</li><li><strong>C</strong>: Merupakan rekapitulasi presensi harian staf dan bukan tingkatan pangkat.</li><li><strong>D</strong>: Merupakan data nasabah perbankan mitra pengelola gaji dinas.</li><li><strong>E</strong>: Merupakan daftar eliminasi seleksi penerimaan CPNS baru.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>DUK PNS adalah suatu daftar yang • yang disusun menurut tingkatan kepangkatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Enam tolak ukur yang digunakan untuk menetapkan nomor urut dalam DUK PNS secara berturut-turut adalah...", "options": ["Usia, pendidikan, masa kerja, jabatan, latihan, dan golongan pangkat", "Jabatan, usia, masa kerja, pangkat, pendidikan, dan jumlah keluarga", "Pendidikan, pangkat, jabatan, latihan jabatan, usia, dan masa kerja", "Pangkat, jabatan, masa kerja, latihan jabatan, pendidikan, dan usia", "Masa kerja, pangkat, jabatan, pendidikan, latihan jabatan, dan usia"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Enam Ukuran Penentu Nomor Urut DUK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Ukuran untuk menetapkan nomor urut dalam DUK secara berturut-turut adalah: <strong>1. pangkat, 2. jabatan, 3. masa kerja, 4. latihan jabatan, 5. pendidikan, dan 6. usia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan urutan prioritas matematis baku dalam penetapan nomor urut DUK. Pangkat menjadi pembeda pertama; jika sama dilihat jabatannya; jika sama dilihat masa kerjanya; disusul diklat jabatan, tingkat pendidikan, dan terakhir faktor usia. Aturan menetapkan urutan berturut-turut pangkat, jabatan, masa kerja, latihan, pendidikan, dan usia. Oleh karena itu, opsi D adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Pangkat, jabatan, masa kerja, latihan jabatan, pendidikan, dan usia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan susunan terbalik yang mengutamakan faktor usia di atas pangkat.</li><li><strong>B</strong>: Merupakan urutan acak yang memasukkan unsur jumlah keluarga non-aturan.</li><li><strong>C</strong>: Merupakan urutan keliru yang menempatkan pendidikan di atas pangkat dinas.</li><li><strong>E</strong>: Merupakan susunan keliru yang mendahulukan masa kerja di atas pangkat golongan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Ukuran yang digunakan untuk menetapkan nomor • pangkat, jabatan, masa kerja, latihan jabatan,</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Prinsip ketunggalan nomor urut dalam penyusunan Daftar Urut Kepangkatan (DUK) PNS menegaskan bahwa...", "options": ["Dalam DUK tidak boleh ada dua nama PNS yang sama nomor urutnya", "Seluruh pejabat fungsional wajib menempati nomor urut paling awal", "Nomor urut DUK boleh dibagi bersama bagi pegawai kembar keluarga", "Nomor urut ditentukan melalui pengundian nomor acak di awal tahun", "Pegawai wanita dan pria dipisahkan ke dalam dua buku naskah berbeda"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Asas Tunggal Nomor Urut DUK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Ketentuan penataan DUK menegaskan bahwa <strong>dalam DUK tidak boleh ada dua nama PNS yang sama nomor urutnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan asas kepastian hierarki di mana setiap pegawai menempati posisi tunggal dalam daftar susunan unit organisasi. Jika indikator pangkat sama, ukuran berikutnya (jabatan, masa kerja, latihan, pendidikan, usia) terus diuji hingga ditemukan pembeda nomor urut yang unik. Aturan menyatakan tidak boleh ada dua nama bernomor urut sama. Dengan demikian, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>A. Dalam DUK tidak boleh ada dua nama PNS yang sama nomor urutnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan pengistimewaan fungsional yang bertentangan dengan rumus DUK.</li><li><strong>C</strong>: Merupakan nomor ganda yang dilarang keras dalam regulasi DUK kepegawaian.</li><li><strong>D</strong>: Merupakan penentuan undian acak yang menabrak asas meritokrasi objektif.</li><li><strong>E</strong>: Merupakan pemisahan gender diskriminatif yang dilarang undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dalam DUK tidak boleh ada dua nama PNS • yang sama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pola mutasi di lingkungan Kementerian Keuangan dilaksanakan dengan memegang teguh prinsip dasar yaitu...", "options": ["Kepentingan pribadi pegawai dengan dispensasi jam dinas", "Keseragaman penempatan berdasar kedekatan geografis asal", "Transparan, objektif, kompetitif, dan berkeadilan kerja", "Kewenangan mutlak pimpinan satker tanpa instrumen asesmen", "Rotasi massal tahunan tanpa melihat kebutuhan organisasi"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Prinsip Manajemen Karier Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Prinsip mutasi di Kemenkeu meliputi: <strong>transparan, objektif, kompetitif, berkeadilan, dan berorientasi pada pencapaian kinerja organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan nilai-nilai dasar pelaksanaan mutasi aparatur sipil negara di lingkungan kementerian. Prinsip transparansi, objektivitas, kompetensi, dan keadilan memastikan bahwa setiap perpindahan tugas aparatur bermuara pada peningkatan kinerja kelembagaan dan pembinaan talenta yang berimbang. Ketentuan KMK Pola Mutasi menetapkan prinsip mutasi adalah transparan, objektif, kompetitif, dan berkeadilan. Dengan demikian, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Transparan, objektif, kompetitif, dan berkeadilan kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kepentingan dinas dan organisasi mengungguli kepentingan preferensi pribadi aparatur.</li><li><strong>B</strong>: Kedekatan geografis bukan dasar utama pemerataan penugasan aparatur sipil nasional.</li><li><strong>D</strong>: Kewenangan pimpinan wajib berpedoman pada koridor meritokrasi dan instrumen asesmen.</li><li><strong>E</strong>: Rotasi massal tanpa analisis kebutuhan bertentangan dengan prinsip penataan organisasi efisien.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Manajemen Karier di lingkungan Kementerian Keuangan • objektif, terencana, dan akuntabel</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Seorang petugas Bea Cukai gugur saat diserang oleh sindikat penyelundup bersenjata dalam patroli laut resmi. Hak kepangkatan yang diberikan negara adalah...", "options": ["Kenaikan pangkat pilihan jalur penemuan metode baru", "Penurunan pangkat satu tingkat karena gagal melumpuhkan pelaku", "Pemberhentian dinas tanpa pemberian hak jaminan duka cita", "Pemberian dispensasi kenaikan pangkat reguler empat tahunan", "Pemberian Kenaikan Pangkat Anumerta berlaku sejak tanggal gugur"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kenaikan Pangkat Anumerta Akibat Tindakan Anasir Jahat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kenaikan Pangkat Anumerta diberikan kepada PNS yang <strong>meninggal dunia karena perbuatan anasir yang tidak bertanggung jawab ataupun sebagai akibat tindakan terhadap anasir itu</strong> saat menjalankan tugas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan penghargaan tertinggi bagi prajurit sipil yang menjadi korban kejahatan saat membela kepentingan kedaulatan penerimaan negara. Peristiwa gugur akibat serangan penyelundup (*anasir*) diakui negara melalui penganugerahan pangkat anumerta terhitung sejak tanggal wafatnya. Aturan menetapkan haknya adalah Kenaikan Pangkat Anumerta. Dengan demikian, opsi E adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>E. Pemberian Kenaikan Pangkat Anumerta berlaku sejak tanggal gugur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kenaikan pangkat inovasi ilmiah yang tidak relevan bagi korban gugur.</li><li><strong>B</strong>: Merupakan tindakan tidak adil yang menghina pengorbanan jiwa aparatur negara.</li><li><strong>C</strong>: Merupakan penelantaran hak asasi yang melanggar ketentuan jaminan tewas.</li><li><strong>D</strong>: Merupakan kenaikan reguler berkala biasa tanpa makna penghargaan kehormatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>meninggal dunia karena perbuatan anasir yang tidak bertanggungjawab • Kenaikan Pangkat Anumerta diberikan kepada PNS</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Objektivitas dalam mempertimbangkan kenaikan pangkat dan pengangkatan jabatan aparatur antara lain diwujudkan dengan memperhatikan...", "options": ["Hasil telaah nomor urut dalam Daftar Urut Kepangkatan (DUK) pegawai", "Tingkat kesamaan suku dan daerah asal pegawai dengan pimpinan", "Besaran pinjaman dana yang diajukan pegawai kepada koperasi", "Kepemilikan sertifikat keikutsertaan organisasi sayap partai", "Jumlah aset kendaraan mewah yang diparkir di halaman kantor"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Peran DUK dalam Pertimbangan Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Objektivitas dalam mempertimbangkan kenaikan pangkat dan jabatan antara lain adalah dengan <strong>memperhatikan nomor urut dalam Daftar Urut Kepangkatan (DUK) dari PNS yang akan dipertimbangkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan salah satu tolok ukur kesetaraan urut kedudukan aparatur sipil. Dengan menelaah DUK, promosi dan kenaikan pangkat dilakukan secara tertib tanpa melangkahi rekan sejawat yang memiliki kualifikasi, masa kerja, dan kepangkatan lebih matang tanpa alasan kinerja yang sah. Regulasi menyatakan pertimbangan memperhatikan nomor urut DUK. Oleh karena itu, opsi A adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Hasil telaah nomor urut dalam Daftar Urut Kepangkatan (DUK) pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan nepotisme primordial kedaerahan yang dilarang undang-undang.</li><li><strong>C</strong>: Merupakan transaksi finansial koperasi privat di luar tolak ukur jabatan.</li><li><strong>D</strong>: Merupakan pelanggaran berat netralitas politik aparatur sipil negara.</li><li><strong>E</strong>: Merupakan gaya hidup pamer aset yang bertentangan dengan kode etik aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pengangkatan dalam jabatan • nomor urut dalam Daftar Urut Kepangkatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Asas pemerataan dan keadilan dalam pola mutasi kepegawaian Kementerian Keuangan diwujudkan melalui penataan...", "options": ["Penempatan kerja berdasar pertimbangan kesukuan daerah", "Keseimbangan penugasan antara kantor pusat dan kantor daerah", "Pemberian dispensasi tugas bagi pegawai di unit tertentu", "Alokasi fasilitas dinas mewah bagi seluruh staf kantor", "Penghapusan evaluasi capaian target kinerja tahunan dinas"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Anumerta Akibat Cacat/Luka Dinas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pola mutasi memperhatikan <strong>keseimbangan penugasan antara unit kantor pusat dan kantor vertikal daerah</strong> untuk pemerataan pengalaman dan keadilan karier.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan perwujudan asas keadilan distributif dalam pembinaan karier aparatur sipil kementerian. Rotasi berimbang antara kantor pusat (perumus kebijakan) dan kantor vertikal daerah (eksekutor pelayanan/pengawasan) membentuk profil kompetensi pegawai yang tangguh, komprehensif, dan berpengalaman luas. Ketentuan pola mutasi menetapkan keseimbangan penugasan antara kantor pusat dan daerah sebagai pilar keadilan. Oleh sebab itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Keseimbangan penugasan antara kantor pusat dan kantor daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pertimbangan kedaerahan kesukuan dilarang keras dalam manajemen kepegawaian berbasis merit.</li><li><strong>C</strong>: Dispensasi tugas merugikan pelayanan publik dan melanggar prinsip disiplin aparatur sipil.</li><li><strong>D</strong>: Fasilitas dinas dialokasikan berdasar standar kebutuhan kedinasan dan bukan azas pemerataan mutasi.</li><li><strong>E</strong>: Evaluasi kinerja merupakan roh akuntabilitas organisasi yang wajib diselenggarakan secara kontinu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>meninggal dunia yang langsung diakibatkan oleh • didapat dalam menjalankan tugas kewajibannya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ruang lingkup pembinaan karier aparatur yang diselenggarakan berdasarkan Sistem Merit meliputi...", "options": ["Pengembangan kompetensi, pola karier, mutasi, dan promosi", "Pengelolaan aset logistik perkantoran dan belanja modal", "Pengawasan penegakan hukum perdata dan pidana korporasi", "Penyelenggaraan bantuan keuangan sosial dan hibah daerah", "Penetapan tarif pajak perdagangan internasional terpadu"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Ruang Lingkup Kegiatan Manajemen Karier Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pembinaan karier berdasarkan Sistem Merit meliputi: <strong>pengembangan kompetensi, pengembangan karier, pola karier, mutasi, dan promosi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan pilar substantif manajemen karier aparatur sipil negara berbasis meritokrasi. Seluruh rangkaian perjalanan karier aparatur dipandu secara terstruktur melalui pemetaan kompetensi, pola pergerakan karier yang jelas, serta mekanisme mutasi dan promosi yang objektif. Ketentuan perundang-undangan menegaskan pembinaan karier sistem merit mencakup pengembangan kompetensi, pola karier, mutasi, dan promosi. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Pengembangan kompetensi, pola karier, mutasi, dan promosi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Logistik dan belanja modal merupakan ruang lingkup pengelolaan barang milik negara.</li><li><strong>C</strong>: Penegakan hukum perdata dan pidana merupakan fungsi yudisial lembaga peradilan negara.</li><li><strong>D</strong>: Bantuan keuangan sosial merupakan program penanggulangan kemiskinan dan bukan manajemen ASN.</li><li><strong>E</strong>: Tarif pajak internasional merupakan domain kebijakan fiskal makro Kementerian Keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 39) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Manajemen Karier • Sistem Merit</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Salah satu tujuan utama manajemen karier di Kementerian Keuangan dalam rangka mencetak pemimpin masa depan adalah...", "options": ["Mendorong peningkatan kompetensi manajerial pegawai swasta", "Memusatkan seluruh wewenang pengelolaan dinas pada pimpinan", "Membatasi regenerasi kepemimpinan pada unit kerja tertentu", "Mempersiapkan kader suksesi kepemimpinan yang berintegritas", "Menyeragamkan pola penugasan tanpa melihat keahlian teknis"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Dukungan Manajemen Karier terhadap Leaders Factory</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Tujuan manajemen karier antara lain <strong>mempersiapkan calon pemimpin masa depan (*future leaders*) yang memiliki integritas dan kompetensi tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan orientasi jangka panjang dari pelembagaan manajemen karier aparatur kementerian. Dengan membina para kader bertalenta secara terencana, organisasi memiliki pasokan pemimpin masa depan yang tidak hanya cakap secara manajerial tetapi juga teguh berintegritas. Ketentuan manajemen karier menetapkan tujuannya mempersiapkan kader suksesi kepemimpinan yang berintegritas. Dengan demikian, opsi D merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>D. Mempersiapkan kader suksesi kepemimpinan yang berintegritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Manajemen karier Kemenkeu diperuntukkan bagi aparatur sipil negara kementerian, bukan pegawai swasta.</li><li><strong>B</strong>: Sentralisasi berlebihan menghambat pendelegasian wewenang dan pembinaan inisiatif kepemimpinan.</li><li><strong>C</strong>: Regenerasi kepemimpinan wajib dibuka secara merata di seluruh unit kerja kementerian.</li><li><strong>E</strong>: Pola penugasan menuntut kesesuaian keahlian teknis spesifik dan bukan penyeragaman kaku.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>menyeimbangkan antara kebutuhan organisasi dan pengembangan • mendukung pelaksanaan program Leaders Factory</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Peraturan Menteri Keuangan yang secara khusus mengatur tentang Manajemen Karier di Lingkungan Kementerian Keuangan adalah...", "options": ["Peraturan Menteri Keuangan Nomor 224/PMK.01/2020", "Peraturan Menteri Keuangan Nomor 10/PMK.01/2015", "Peraturan Menteri Keuangan Nomor 50/PMK.01/2018", "Peraturan Menteri Keuangan Nomor 90/PMK.01/2012", "Peraturan Menteri Keuangan Nomor 15/PMK.01/2017"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Dasar Regulasi Manajemen Karier Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Ketentuan mengenai manajemen karier di Kemenkeu diatur dalam <strong>Peraturan Menteri Keuangan Nomor 224/PMK.01/2020 tentang Manajemen Karier di Lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan regulasi operasional komprehensif yang mengatur pola karier horizontal, vertikal, diagonal, semi horizontal/vertikal, serta penugasan pegawai Kemenkeu. Regulasi yang tepat adalah PMK Nomor 224/PMK.01/2020. Oleh karena itu, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>A. Peraturan Menteri Keuangan Nomor 224/PMK.01/2020</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan PMK tentang standar biaya masukan anggaran negara.</li><li><strong>C</strong>: Merupakan PMK pengelolaan perbendaharaan belanja dinas.</li><li><strong>D</strong>: Merupakan aturan lama organisasi tata kerja kementerian keuangan.</li><li><strong>E</strong>: Merupakan aturan teknis tata naskah surat dinas kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peraturan Menteri Keuangan Nomor • Manajemen Karier di Lingkungan Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dasar hukum regulasi yang mengatur tentang penyelenggaraan program Leaders Factory di lingkungan Kementerian Keuangan adalah...", "options": ["PMK Nomor 120/PMK.01/2015", "PMK Nomor 150/PMK.01/2016", "PMK Nomor 191/PMK.01/2018", "PMK Nomor 210/PMK.01/2019", "PMK Nomor 250/PMK.01/2021"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Regulasi Program Leaders Factory Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Program pencetakan calon pemimpin diatur melalui <strong>Peraturan Menteri Keuangan Nomor 191/PMK.01/2018 tentang Leaders Factory di Lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan payung hukum inisiatif Kemenkeu dalam menyaring, menggembleng, dan menugaskan kader-kader aparatur terbaik untuk mengisi peran kepemimpinan nasional. Regulasi yang tepat adalah PMK Nomor 191/PMK.01/2018. Dengan demikian, opsi C adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>C. PMK Nomor 191/PMK.01/2018</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan nomor regulasi yang tidak mengatur program pencetakan pemimpin.</li><li><strong>B</strong>: Merupakan aturan penataan organisasi internal kantor eselon satu.</li><li><strong>D</strong>: Merupakan regulasi perbendaharaan kas negara pada akhir tahun anggaran.</li><li><strong>E</strong>: Merupakan aturan pengelolaan barang milik negara di lingkungan satker.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peraturan Menteri Keuangan Nomor 191/PMK.01/2018 • Factory di Lingkungan Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Salah satu dari enam tujuan perumusan Manajemen Karier di Kementerian Keuangan bagi kenyamanan aparatur adalah...", "options": ["Pemberian dispensasi pemenuhan target kinerja pada triwulan akhir", "Memberikan hak pensiun otomatis bagi pegawai di bawah usia 30 tahun", "Memberikan izin penolakan penugasan dinas di wilayah kepulauan", "Memberikan hak bagi staf untuk menentukan sendiri nominal gaji", "Memberikan kejelasan dan kepastian jenjang karier bagi setiap PNS"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tujuan Kepastian Karier bagi Pegawai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Tujuan manajemen karier butir b adalah <strong>memberikan kejelasan dan kepastian karier bagi Pegawai Negeri Sipil</strong> di lingkungan Kementerian Keuangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan pemenuhan hak transparansi pola jenjang karier aparatur sipil. Dengan adanya standar kompetensi, peta suksesi, dan pola karier yang jelas, setiap pegawai mengetahui secara pasti prasyarat apa yang harus dipenuhi untuk mencapai level jabatan tertentu. Regulasi menegaskan tujuannya memberikan kejelasan dan kepastian karier. Oleh karena itu, opsi E adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>E. Memberikan kejelasan dan kepastian jenjang karier bagi setiap PNS</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Evaluasi kinerja pegawai berjalan berkesinambungan tanpa pemberian dispensasi target.</li><li><strong>B</strong>: Merupakan pemensiunan usia muda yang melanggar ketentuan hukum pensiun.</li><li><strong>C</strong>: Merupakan penolakan tugas yang melanggar ikrar penempatan NKRI.</li><li><strong>D</strong>: Merupakan penentuan gaji sepihak yang melanggar sistem remunerasi negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>memberikan kejelasan dan kepastian karier</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tata kelola SDM pada unit organisasi non-eselon di lingkungan Kementerian Keuangan secara spesifik diatur dengan...", "options": ["Peraturan Menteri Keuangan Nomor 50/PMK.01/2017", "Peraturan Menteri Keuangan Nomor 146/PMK.01/2020", "Keputusan Direktur Jenderal Pajak Nomor 8 Tahun 2019", "Instruksi Sekretaris Jenderal mengenai Presensi 2018", "Surat Edaran Kepala Biro Organisasi dan Tata Laksana 2021"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Dasar Regulasi SDM Unit Non-Eselon Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Ketentuan dasar hukum manajemen karier berhubungan dengan <strong>Peraturan Menteri Keuangan Nomor 146/PMK.01/2020 tentang Manajemen Sumber Daya Manusia pada Unit Organisasi Non Eselon</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan regulasi komprehensif penataan pegawai yang ditugaskan pada lembaga-lembaga non-eselon di bawah Kemenkeu. PMK ini mengatur hak, kewajiban, tata penugasan, dan evaluasi berkala pegawai non-eselon. Regulasi yang tepat adalah PMK Nomor 146/PMK.01/2020. Dengan demikian, opsi B adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. Peraturan Menteri Keuangan Nomor 146/PMK.01/2020</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan nomor regulasi yang tidak berkaitan dengan unit non-eselon.</li><li><strong>C</strong>: Merupakan aturan internal teknis penerimaan pajak direktorat jenderal.</li><li><strong>D</strong>: Merupakan tata tertib kehadiran kerja harian sekretariat jenderal.</li><li><strong>E</strong>: Merupakan surat edaran penataan tata naskah struktur kelembagaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peraturan Menteri Keuangan Nomor • Manajemen Sumber Daya Manusia pada Unit Organisasi Non Eselon</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penyelenggaraan Manajemen Karier di Kementerian Keuangan secara spesifik diarahkan untuk...", "options": ["Menyerahkan proses penilaian kompetensi kepada pihak mitra", "Membatasi pengumuman lowongan formasi pada pejabat pusat", "Menetapkan masa kerja sebagai satu-satunya penentu karier", "Mewujudkan kepastian arah pengembangan karier setiap pegawai", "Menghapuskan mekanisme seleksi terbuka pada jenjang madya"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Objektivitas dan Transparansi Perencanaan Karier</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Manajemen karier bertujuan <strong>memberikan kepastian arah karier bagi Pegawai</strong> berdasarkan kualifikasi, kompetensi, dan kinerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan manfaat utama penerapan pola karier yang terstruktur dan transparan bagi aparatur. Adanya peta jalan karier yang jelas memberikan motivasi bagi setiap pegawai untuk berkinerja terbaik karena mengetahui jalur kenaikan jenjang yang dapat diraih secara adil. Ketentuan regulasi menetapkan tujuan manajemen karier adalah mewujudkan kepastian arah karier pegawai. Oleh sebab itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Mewujudkan kepastian arah pengembangan karier setiap pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Asesmen kompetensi diselenggarakan oleh unit resmi kementerian dan asesor bersertifikat.</li><li><strong>B</strong>: Transparansi formasi lowongan wajib diumumkan secara terbuka bagi seluruh pegawai yang memenuhi syarat.</li><li><strong>C</strong>: Sistem merit menolak masa kerja sebagai dasar tunggal penetapan karier pegawai.</li><li><strong>E</strong>: Seleksi terbuka merupakan amanat undang-undang yang wajib dipatuhi pada jenjang manajerial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>meningkatkan objektivitas dan transparansi • perencanaan karier</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dasar hukum pemerintah pusat yang mengatur standar baku penilaian kinerja PNS yang diacu dalam manajemen karier Kemenkeu adalah...", "options": ["Peraturan Pemerintah Nomor 30 Tahun 2019", "Peraturan Pemerintah Nomor 10 Tahun 2015", "Peraturan Pemerintah Nomor 45 Tahun 2013", "Peraturan Presiden Nomor 16 Tahun 2018", "Keputusan Presiden Nomor 20 Tahun 2017"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Dasar Hukum Penilaian Kinerja PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Dasar hukum manajemen karier berhubungan dengan regulasi kinerja yaitu <strong>Peraturan Pemerintah Nomor 30 Tahun 2019 tentang Penilaian Kinerja Pegawai Negeri Sipil</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan regulasi nasional yang mengatur siklus perencanaan kinerja, pelaksanaan, pemantauan, pembinaan, penilaian melalui SKP dan perilaku kerja, hingga tindak lanjut hasil evaluasi kinerja PNS. Regulasi yang tepat adalah PP Nomor 30 Tahun 2019. Dengan demikian, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Peraturan Pemerintah Nomor 30 Tahun 2019</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan nomor PP yang tidak mengatur sistem penilaian kinerja PNS.</li><li><strong>C</strong>: Merupakan peraturan pemerintah tata cara pelaksanaan APBN belanja negara.</li><li><strong>D</strong>: Merupakan peraturan presiden mengenai pengadaan barang dan jasa.</li><li><strong>E</strong>: Merupakan keputusan presiden penugasan di luar manajemen penilaian kinerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peraturan Pemerintah Nomor 30 Tahun 2019 • Pegawai Negeri Sipil</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Pengembangan Sumber Daya Manusia aparatur di lingkungan Kementerian Keuangan secara terintegrasi berpedoman pada...", "options": ["PMK Nomor 10/PMK.01/2014", "PMK Nomor 100/PMK.01/2016", "PMK Nomor 216/PMK.01/2018", "PMK Nomor 240/PMK.01/2021", "PMK Nomor 300/PMK.01/2023"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Dasar Regulasi Manajemen Pengembangan SDM Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengembangan SDM Kemenkeu diatur secara terpadu melalui <strong>Peraturan Menteri Keuangan Nomor 216/PMK.01/2018 tentang Manajemen Pengembangan Sumber Daya Manusia di Lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan regulasi induk yang memayungi penyelenggaraan beasiswa, pelatihan klasikal/non-klasikal, sertifikasi kompetensi, dan pembelajaran terintegrasi bagi pegawai Kemenkeu. Regulasi yang tepat adalah PMK Nomor 216/PMK.01/2018. Oleh karena itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. PMK Nomor 216/PMK.01/2018</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan aturan lama yang tidak mengatur manajemen pengembangan SDM.</li><li><strong>B</strong>: Merupakan regulasi perbendaharaan operasional belanja kementerian.</li><li><strong>D</strong>: Merupakan nomor peraturan yang mengatur tarif perizinan penerimaan negara.</li><li><strong>E</strong>: Merupakan nomor PMK yang tidak mengatur kerangka pengembangan pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peraturan Menteri Keuangan Nomor • Manajemen Pengembangan Sumber Daya Manusia di</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Perumusan Manajemen Karier di Kementerian Keuangan memiliki tujuan penting dalam mendorong peningkatan...", "options": ["Beban tugas kerja harian pegawai tanpa penyesuaian sarana", "Persaingan informal antar-satuan kerja dalam alokasi aset", "Pengeluaran belanja perjalanan dinas operasional kementerian", "Jumlah staf administrasi pada kantor perwakilan wilayah", "Kinerja dan profesionalitas aparatur dalam pelayanan publik"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Peningkatan Motivasi dan Kinerja PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Manajemen karier bertujuan <strong>mendorong peningkatan kinerja dan profesionalitas Pegawai</strong> secara berkesinambungan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan sasaran puncak dari tata kelola sumber daya manusia aparatur berbasis meritokrasi. Manajemen karier yang sehat mendorong profesionalitas pegawai dalam bekerja, yang pada akhirnya bermuara pada peningkatan kualitas pelayanan publik dan pengelolaan keuangan negara. Ketentuan regulasi menetapkan manajemen karier bertujuan mendorong peningkatan kinerja dan profesionalitas pegawai. Oleh karena itu, opsi E merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Kinerja dan profesionalitas aparatur dalam pelayanan publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Manajemen karier bertujuan mengoptimalkan efisiensi kerja dan bukan menambah beban tanpa sarana.</li><li><strong>B</strong>: Pola karier menumbuhkan budaya kolaborasi sinergis dan bukan persaingan perebutan aset.</li><li><strong>C</strong>: Efisiensi anggaran perjalanan dinas merupakan prinsip perbendaharaan dan bukan sasaran karier.</li><li><strong>D</strong>: Penataan jumlah pegawai berpedoman pada analisis beban kerja dan bukan target manajemen karier.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>meningkatkan motivasi, kompetensi • Kinerja PNS</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Penyelenggaraan Manajemen Karier berbasis meritokrasi di Kemenkeu bertujuan butir e untuk mendorong peningkatan...", "options": ["Keterikatan emosional pegawai dengan partai politik tertentu", "Profesionalitas dan integritas Pegawai Negeri Sipil", "Ketergantungan operasional kantor pada tenaga konsultan swasta", "Frekuensi unjuk rasa menuntut kenaikan uang makan kedinasan", "Kemampuan teknis aparatur dalam mengoperasikan sistem presensi"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Peningkatan Profesionalitas Aparatur Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Tujuan manajemen karier butir e secara tegas merumuskan untuk <strong>mendorong peningkatan profesionalitas PNS</strong> di lingkungan Kementerian Keuangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan sasaran pembangunan aparatur berkelas dunia (*world-class bureaucracy*). Pegawai yang profesional menuntaskan tugas secara akurat, etis, dan berorientasi hasil demi mengawal keuangan negara secara kredibel. Aturan menyebutkan tujuannya mendorong peningkatan profesionalitas PNS. Oleh karena itu, opsi B adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. Profesionalitas dan integritas Pegawai Negeri Sipil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pelanggaran berat netralitas yang diancam hukuman disiplin berat.</li><li><strong>C</strong>: Merupakan pelemahan kapasitas aparatur internal kementerian negara.</li><li><strong>D</strong>: Merupakan tindakan anarkis yang dilarang bagi korps pegawai negeri sipil.</li><li><strong>E</strong>: Keahlian teknis presensi merupakan keterampilan dasar operasional dan bukan dimensi kinerja utama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 40) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mendorong peningkatan profesionalitas • profesionalitas PNS</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi Manajemen Karier Kemenkeu, pengembangan talenta dan karier pegawai wajib mempertimbangkan empat aspek pokok yaitu...", "options": ["Masa dinas, golongan kepangkatan, usia, dan riwayat mutasi", "Pendidikan formal, sertifikat pelatihan, kehadiran, dan eselon", "Rekomendasi atasan, kepatuhan disiplin, integritas, dan gelar", "Pengalaman penugasan, keaktifan organisasi, jabatan, dan asal", "Kualifikasi, kompetensi, kinerja, dan kebutuhan instansi"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Empat Pertimbangan Pengembangan Talenta Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengembangan talenta dan karier dilakukan dengan mempertimbangkan <strong>kualifikasi, kompetensi, kinerja, dan kebutuhan organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan empat pilar pertimbangan mutlak dalam sistem meritokrasi aparatur sipil negara modern. Pemaduan antara latar belakang pendidikan (kualifikasi), kecakapan kerja (kompetensi), rekam jejak prestasi (kinerja), serta formasi organisasi (kebutuhan instansi) menjamin penempatan yang akuntabel. Ketentuan hukum menegaskan pertimbangan talenta mencakup kualifikasi, kompetensi, kinerja, dan kebutuhan organisasi. Dengan demikian, opsi E merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Kualifikasi, kompetensi, kinerja, dan kebutuhan instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masa dinas dan usia semata merupakan parameter lama yang tidak lagi menjadi penentu pokok.</li><li><strong>B</strong>: Sertifikat pelatihan dan eselon merupakan bagian dari instrumen pendukung kualifikasi.</li><li><strong>C</strong>: Rekomendasi atasan bersifat subjektif bila tidak didasarkan pada data capaian kinerja terukur.</li><li><strong>D</strong>: Keaktifan organisasi di luar dinas bukan parameter pokok dalam pertimbangan talenta ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengembangan talenta dan karier dilakukan dengan mempertimbangkan • kualifikasi, kompetensi, kinerja, dan kebutuhan Instansi Pemerintah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Tiga mekanisme operasional yang digunakan dalam pengembangan karier bagi PNS Kementerian Keuangan adalah...", "options": ["Penugasan berkala, evaluasi tahunan, dan pembekalan teknis", "Mutasi, promosi, dan/atau penugasan khusus instansi luar", "Rotasi dinas, perpanjangan masa kontrak, dan uji kelayakan", "Pemberian tugas belajar, cuti besar, dan masa pembinaan", "Asesmen kompetensi, pembinaan disiplin, dan uji keahlian"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Tiga Jalur Pengembangan Karier Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengembangan karier PNS dilakukan melalui <strong>mutasi, promosi, dan/atau penugasan khusus di luar Instansi Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan tiga saluran mobilitas karier resmi bagi aparatur sipil negara di lingkungan kementerian. Pengembangan karier ditempuh melalui pergerakan horizontal (mutasi), pergerakan vertikal (promosi), maupun perluasan wawasan pengalaman melalui perbantuan penugasan di luar kementerian. Ketentuan perundang-undangan menetapkan mekanisme pengembangan karier melalui mutasi, promosi, dan penugasan khusus luar instansi. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Mutasi, promosi, dan/atau penugasan khusus instansi luar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembekalan teknis merupakan instrumen diklat dan bukan mekanisme pergerakan karier pegawai.</li><li><strong>C</strong>: Perpanjangan kontrak merupakan rezim kepegawaian PPPK dan bukan pola karier PNS.</li><li><strong>D</strong>: Cuti besar merupakan hak istirahat pegawai dan bukan mekanisme mobilitas karier dinas.</li><li><strong>E</strong>: Asesmen dan pembinaan disiplin merupakan sarana evaluasi dan bukan saluran mobilitas karier.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengembangan karier bagi PNS Kemenkeu dilakukan melalui • mutasi, promosi, dan/atau penugasan di luar Kementerian Keuangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat administrator Kemenkeu ditugaskan berdinas pada kementerian koordinator. Status jabatannya diatur dengan ketentuan...", "options": ["Tetap memegang jabatan definitif dan menerima gaji ganda", "Diberhentikan tetap dari status korps pegawai negeri sipil", "Wajib mengajukan surat pensiun dini sukarela dari instansi", "Diberhentikan dari jabatan lamanya dan diangkat di instansi baru", "Dinonaktifkan sementara dari keanggotaan aparatur kementerian"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Status Jabatan PNS Kemenkeu Ditugaskan di Luar Instansi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS yang ditugaskan di luar instansi induk <strong>diberhentikan dari jabatannya pada instansi induk dan diangkat dalam jabatan pada instansi penerima</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan status administrasi jabatan bagi aparatur yang melaksanakan penugasan di luar instansi induk. Pelepasan jabatan pada instansi asal dilakukan agar posisi tersebut dapat diisi pejabat baru, sementara pegawai bersangkutan dilantik menduduki jabatan definitif pada kementerian penerima tanpa memutus status kepegawaian PNS-nya. Ketentuan regulasi menetapkan pegawai diberhentikan dari jabatan lama dan diangkat pada instansi baru. Oleh sebab itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Diberhentikan dari jabatan lamanya dan diangkat di instansi baru</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rangkap jabatan dan penerimaan gaji ganda dilarang keras dalam hukum keuangan negara.</li><li><strong>B</strong>: Penugasan di kementerian lain tidak memutus ikatan status kewarganegaraan PNS pegawai.</li><li><strong>C</strong>: Penugasan antar-instansi merupakan pengabdian aktif dan bukan alasan pengajuan pensiun dini.</li><li><strong>E</strong>: Pegawai tetap berstatus ASN aktif pada instansi penerima dan bukan pegawai nonaktif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>ditugaskan di luar Kemenkeu • dan diangkat pada Jabatan di instansi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C4 - Analisis", "question": "Jalur pengembangan karier aparatur dalam Jabatan Fungsional (JF) di Kemenkeu yang BUKAN merupakan mekanisme resmi adalah...", "options": ["Pengangkatan pertama formasi jabatan fungsional", "Perpindahan tugas kepegawaian dari jabatan lain", "Penyesuaian kualifikasi keahlian atau inpassing", "Penetapan otomatis tanpa verifikasi angka kredit", "Promosi kenaikan jenjang jabatan fungsional ahli"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Jalur Pengembangan Karier dalam Jabatan Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengangkatan JF dilakukan melalui: <strong>pengangkatan pertama, perpindahan dari jabatan lain, penyesuaian (inpassing), dan promosi</strong>. Tidak ada penetapan otomatis tanpa syarat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan mekanisme nonprosedural yang bertentangan dengan prinsip meritokrasi jabatan fungsional. Pengangkatan dan kenaikan jenjang fungsional wajib melalui pengujian kompetensi serta pemenuhan angka kredit yang terverifikasi secara ketat. Ketentuan perundang-undangan menetapkan empat jalur resmi JF yaitu pengangkatan pertama, perpindahan, inpassing, dan promosi. Dengan demikian, opsi D merupakan jawaban pengecualian yang tepat.</p><p>Jawaban tepat: <strong>D. Penetapan otomatis tanpa verifikasi angka kredit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengangkatan pertama merupakan jalur masuk resmi bagi CPNS yang melamar pada formasi JF.</li><li><strong>B</strong>: Perpindahan dari jabatan lain merupakan jalur resmi peralihan struktural/pelaksana ke JF.</li><li><strong>C</strong>: Inpassing merupakan jalur resmi penyesuaian bagi kebutuhan penataan kelembagaan birokrasi.</li><li><strong>E</strong>: Promosi merupakan jalur kenaikan jenjang jabatan fungsional bagi yang berprestasi unggul.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Bagi PNS Kemenkeu dapat diberikan pengembangan • Pengangkatan pertama • Perpindahan dari Jabatan lain • Penyesuaian/inpassing</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Syarat mutlak yang wajib dipenuhi oleh pejabat fungsional untuk dapat naik jenjang jabatan satu tingkat lebih tinggi adalah...", "options": ["Mendapatkan rekomendasi kepemimpinan dari kepala daerah", "Telah mencapai usia minimal lima puluh lima tahun kalender", "Telah memenuhi perolehan angka kredit yang dipersyaratkan", "Memiliki masa kerja pengabdian minimal sepuluh tahun dinas", "Menyelesaikan penugasan operasional pada kantor terluar"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Kenaikan Jenjang Jabatan Fungsional Berbasis Angka Kredit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Kenaikan jenjang JF mensyaratkan <strong>pemenuhan angka kredit kumulatif minimal</strong>, lulus uji kompetensi, dan tersedianya lowongan formasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan tolok ukur utama produktivitas dan kepakaran teknis pejabat fungsional dalam birokrasi. Kenaikan jenjang jabatan fungsional menuntut pembuktian kinerja melalui akumulasi angka kredit butir kegiatan profesi serta kelulusan uji kompetensi. Ketentuan hukum kepegawaian menetapkan syarat kenaikan jenjang JF adalah pemenuhan angka kredit yang dipersyaratkan. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Telah memenuhi perolehan angka kredit yang dipersyaratkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rekomendasi kepala daerah bukan syarat substantif bagi pejabat fungsional kementerian.</li><li><strong>B</strong>: Kenaikan jenjang JF ditentukan oleh capaian kinerja profesi dan bukan faktor ambang usia.</li><li><strong>D</strong>: Masa kerja sepuluh tahun bukan syarat mutlak apabila angka kredit telah terpenuhi lebih cepat.</li><li><strong>E</strong>: Penugasan kantor terluar bukan syarat limitatif kenaikan jenjang jabatan fungsional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kenaikan jenjang JF satu tingkat lebih tinggi • setelah memenuhi angka</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Ketentuan mengenai mutasi dan promosi bagi PNS Kementerian Keuangan yang bertugas pada Unit Non-Eselon mengatur bahwa...", "options": ["Pegawai non-eselon dilarang memperoleh promosi jabatan", "Seluruh hak karier kepegawaian dinonaktifkan sementara", "Diprioritaskan menduduki jabatan fungsional pelaksana", "Dapat dimutasi terbatas pada sesama unit penugasan khusus", "Tetap memiliki hak promosi dan mutasi sesuai kompetensi"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Pemberlakuan Mutasi Promosi pada Unit Non-Eselon</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>PNS pada unit non-eselon <strong>tetap memiliki hak yang sama dalam pengembangan karier, mutasi, dan promosi</strong> berdasarkan kualifikasi, kompetensi, dan kinerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jaminan kesetaraan hak karier bagi seluruh pegawai aparatur sipil negara di lingkungan kementerian. Penugasan pada unit non-eselon (seperti badan pengelola dana atau komite khusus) tidak mengurangi hak aparatur untuk berkompetisi meraih promosi dan mutasi jabatan reguler. Ketentuan regulasi menetapkan bahwa pegawai pada unit non-eselon tetap memiliki hak promosi dan mutasi sesuai kompetensi. Oleh sebab itu, opsi E merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Tetap memiliki hak promosi dan mutasi sesuai kompetensi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penugasan khusus pada unit non-eselon tidak menghapuskan hak promosi karier pegawai.</li><li><strong>B</strong>: Status kepegawaian dan pembinaan talenta tetap melekat aktif selama masa penugasan unit.</li><li><strong>C</strong>: Penempatan diarahkan sesuai kualifikasi dan kompetensi nyata, bukan pembatasan pelaksana.</li><li><strong>D</strong>: Pegawai dapat kembali bermutasi ke struktur unit eselon reguler kementerian induk.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Mutasi dan promosi juga berlaku • penempatan atau penugasan pada Unit nonEselon</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat fungsional yang sebelumnya ditugaskan menjadi pejabat negara telah selesai bertugas. Pemulihan jabatan fungsionalnya ditempuh melalui...", "options": ["Pengangkatan pertama melalui jalur penerimaan formasi baru", "Mekanisme pengangkatan kembali ke dalam Jabatan Fungsional", "Penetapan status penugasan penyesuaian berkala kepegawaian", "Pemberian dispensasi kelulusan tanpa uji portofolio kinerja", "Pengalihan otomatis menjadi pejabat administrasi pengawas"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Mekanisme Pengangkatan Kembali ke dalam JF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pejabat Fungsional yang berhenti karena diangkat menjadi Pejabat Negara <strong>dapat diangkat kembali dalam Jabatan Fungsional yang didudukinya</strong> setelah selesai menjalankan tugas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jalur resmi pemulihan profesi aparatur sipil negara pasca pengabdian sebagai pejabat negara. Karena pegawai dibebaskan sementara dari tugas profesi selama menjabat di lembaga negara, yang bersangkutan berhak diangkat kembali pada jenjang jabatan fungsional terakhir yang didudukinya. Ketentuan regulasi menetapkan pemulihan ditempuh melalui mekanisme pengangkatan kembali dalam Jabatan Fungsional. Dengan demikian, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Mekanisme pengangkatan kembali ke dalam Jabatan Fungsional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengangkatan pertama hanya berlaku bagi calon aparatur yang baru pertama kali memasuki JF.</li><li><strong>C</strong>: Penyesuaian berkala diperuntukkan bagi inpassing massal dan bukan pemulihan purnatugas.</li><li><strong>D</strong>: Pengangkatan kembali tetap memperhatikan formasi yang lowong dan syarat kepegawaian sah.</li><li><strong>E</strong>: Pejabat fungsional berhak kembali ke rumpun profesinya dan tidak dialihkan ke pengawas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Bagi PNS Kemenkeu dapat diberikan pengembangan • Pengangkatan kembali</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Dalam Gambar 5.1 Manajemen Karier di Kementerian Keuangan, pilar awal pembinaan penugasan aparatur secara terpadu diwadahi dalam...", "options": ["Pelelangan aset inventaris kantor pada unit operasional daerah", "Pemberian sanksi administratif penurunan kelas grading dinas", "Pengawasan rekening tabungan perbankan pribadi milik keluarga", "Pengembangan Karier berbasis kualifikasi, kompetensi, dan kinerja", "Penarikan iuran dana solidaritas pegawai di lingkungan sekretariat"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Komponen Gambar 5.1 Manajemen Karier Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Berdasarkan Gambar 5.1 dan uraian huruf a, pilar utama pengelolaan pegawai adalah <strong>Pengembangan Karier yang mempertimbangkan kualifikasi, kompetensi, kinerja, dan kebutuhan instansi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan fondasi diagram alur manajemen karier Kemenkeu. Seluruh skema mutasi, promosi, penugasan luar instansi, dan fungsional bermuara pada pengembangan kapasitas karier yang terukur dan meritokratis. Regulasi mencantumkannya dalam huruf a Pengembangan Karier. Oleh karena itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Pengembangan Karier berbasis kualifikasi, kompetensi, dan kinerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tata kelola BMN perbendaharaan di luar diagram karier pegawai.</li><li><strong>B</strong>: Merupakan ranah hukuman disiplin dan bukan pilar pengembangan karier.</li><li><strong>C</strong>: Merupakan data privat perbankan yang dilindungi undang-undang keuangan.</li><li><strong>E</strong>: Merupakan kegiatan sosial informal di luar struktur resmi manajemen karier.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Gambar 5.1 Manajemen Karier di Kementerian Keuangan • Pengembangan Karier</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C2 - Pemahaman", "question": "Jalur pengangkatan ke dalam Jabatan Fungsional dalam rangka penataan organisasi atau pembentukan jabatan fungsional baru disebut jalur...", "options": ["Penyesuaian atau inpassing jabatan fungsional", "Perpindahan tugas kepegawaian antar-instansi", "Pengangkatan pertama pelamar formasi kementerian", "Promosi kenaikan jenjang jabatan profesi unggul", "Penyetaraan penugasan berkala aparatur sipil"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Jalur Penyesuaian Inpassing JF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengangkatan melalui <strong>penyesuaian (inpassing)</strong> dilaksanakan untuk kepentingan penataan organisasi atau penetapan JF baru.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan mekanisme transisi kelembagaan resmi untuk mengakomodasi penataan birokrasi pemerintahan. Jalur inpassing membuka kesempatan bagi PNS yang telah berpengalaman di bidang tugas terkait untuk dialihkan menduduki jabatan fungsional baru sesuai kualifikasinya. Ketentuan perundang-undangan menetapkan pengangkatan dalam rangka penataan organisasi dilakukan melalui penyesuaian atau inpassing. Oleh karena itu, opsi A merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>A. Penyesuaian atau inpassing jabatan fungsional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Perpindahan tugas antar-instansi merupakan jalur mobilitas reguler perorangan di luar inpassing.</li><li><strong>C</strong>: Pengangkatan pertama diperuntukkan bagi formasi awal CPNS yang melamar langsung ke JF.</li><li><strong>D</strong>: Promosi merupakan kenaikan jenjang fungsional berdasarkan prestasi kinerja angka kredit.</li><li><strong>E</strong>: Penyetaraan penugasan berkala bukan penamaan resmi mekanisme pengangkatan jabatan fungsional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Bagi PNS Kemenkeu dapat diberikan pengembangan • Penyesuaian/inpassing</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 03", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat pengawas yang memiliki keahlian analisis data keuangan ingin beralih profesi menjadi fungsional Pranata Keuangan. Jalur resmi yang ditempuh adalah...", "options": ["Mengundurkan diri terlebih dahulu sebagai pegawai negeri sipil", "Membeli sertifikat keahlian dari lembaga pelatihan tidak terakreditasi", "Jalur perpindahan dari jabatan lain ke dalam jabatan fungsional", "Menunggu diterbitkannya surat keputusan pemensiunan dini dinas", "Meminta penetapan langsung kepada komisi pemilihan umum daerah"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">🎯 Intisari Materi • Jalur Perpindahan dari Jabatan Lain ke JF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 📌 Konsep Inti Textbook</div><p>Pengembangan karier dalam JF dapat ditempuh melalui jalur <strong>Perpindahan dari Jabatan lain</strong> dengan mengikuti uji kompetensi dan memenuhi syarat yang berlaku.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 💡 Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan pintu mutasi diagonal atau horizontal bagi pegawai struktural/pelaksana yang ingin mendedikasikan keahliannya ke rumpun fungsional. Mekanisme ini memfasilitasi konversi karier secara resmi sesuai minat dan kompetensi aparatur. Aturan menetapkan jalurnya adalah Perpindahan dari Jabatan lain. Oleh karena itu, opsi C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Jalur perpindahan dari jabatan lain ke dalam jabatan fungsional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 🔍 Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pengunduran diri keliru yang mematikan status kepesertaan PNS.</li><li><strong>B</strong>: Merupakan tindak pidana pemalsuan sertifikat yang melanggar hukum kedinasan.</li><li><strong>D</strong>: Merupakan pemensiunan dini yang bertentangan dengan keinginan berkarier aktif.</li><li><strong>E</strong>: Merupakan lembaga pemilu yang tidak memiliki wewenang kepegawaian kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 41) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Bagi PNS Kemenkeu dapat diberikan pengembangan • Perpindahan dari Jabatan lain</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_tak_p03_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_tak_p03_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_tak_p03_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Tata Aturan Kepegawaian Paket 03";
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
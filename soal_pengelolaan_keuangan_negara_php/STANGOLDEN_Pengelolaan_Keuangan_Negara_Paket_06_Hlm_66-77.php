<?php
/**
 * STANGOLDEN.exam • Pengelolaan Keuangan Negara Paket 06 (Halaman 66–77)
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
// $DB->get_record("stgpkn_p06_rank") => tabel fisik "mdlhv_stgpkn_p06_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgpkn_p06_rank';
const STG_REVIEW_T = 'stgpkn_p06_rev';
const STG_ANNO_T = 'stgpkn_p06_anno';
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
            error_log('STANGOLDEN PKN P06 HLM66-77 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN PKN P06 HLM66-77: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Pengelolaan Keuangan Negara Paket 06 (Hlm. 66–77) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 06 • Hlm. 66–77</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 06 • Hlm. 66–77</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Pengelolaan Keuangan Negara Paket 06</h2>
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
const STG_LOCAL_KEY="stg_pkn_p06_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Dilihat dari struktur ketatanegaraan, sistem pengawasan di Indonesia terbagi atas dua sistem yaitu...", "options": ["Sistem Pengawasan Negara Kesatuan RI dan Pemerintah Pusat", "Sistem Pengawasan Sektor Perbankan dan Asuransi Komersial", "Sistem Pengawasan Lembaga Swadaya Masyarakat dan Pers", "Sistem Pengawasan Organisasi Politik dan Serikat Buruh", "Sistem Pengawasan Dewan Adat Desa dan Tokoh Pemuda"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Sistem Pengawasan Utama Pemerintahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sistem pengawasan terbagi atas dua sistem utama yaitu <strong>Sistem Pengawasan Negara Kesatuan RI dan Sistem Pengawasan Pemerintah Pusat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pembagian ini mencerminkan struktur ketatanegaraan, di mana pengawasan NKRI merepresentasikan cabang kekuasaan eksternal negara sedangkan pengawasan pemerintah pusat merepresentasikan internal eksekutif.</p><p>Jawaban tepat: <strong>A. Sistem Pengawasan Negara Kesatuan RI dan Pemerintah Pusat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Perbankan dan asuransi diawasi oleh Otoritas Jasa Keuangan.</li><li><strong>C</strong>: LSM dan pers adalah instrumen pengawasan partisipatif masyarakat.</li><li><strong>D</strong>: Partai politik dan serikat buruh adalah entitas supra/infrastruktur politik.</li><li><strong>E</strong>: Dewan adat dan pemuda adalah elemen sosiologis kemasyarakatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 66) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sistem Pengawasan Negara Kesatuan RI dan • Sistem Pengawasan Pemerintah Pusat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Sistem Pengawasan Negara Kesatuan RI dalam tata kelola kenegaraan lazim disebut sebagai...", "options": ["Sistem Pengawasan Melekat Kedinasan", "Sistem Pengawasan Eksternal Pemerintah", "Sistem Pengendalian Mutu Manajemen", "Sistem Kepatuhan Hukum Internal Satker", "Sistem Audit Operasional Bawahan Kantor"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sebutan Sistem Pengawasan Negara Kesatuan RI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sistem Pengawasan Negara Kesatuan RI dapat disebut sebagai <strong>Sistem Pengawasan Eksternal Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena lembaga pemeriksa eksternal seperti DPR, DPD, dan BPK berada di luar struktur organisasi eksekutif kabinet pemerintah pusat, bertindak atas nama kedaulatan negara.</p><p>Jawaban tepat: <strong>B. Sistem Pengawasan Eksternal Pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengawasan melekat dilakukan oleh atasan langsung pejabat bersangkutan.</li><li><strong>C</strong>: Pengendalian mutu adalah instrumen teknis manajemen operasional.</li><li><strong>D</strong>: Kepatuhan hukum internal dijalankan oleh unit kepatuhan internal.</li><li><strong>E</strong>: Audit operasional bawahan adalah tugas fungsional inspektorat jenderal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 66) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sistem Pengawasan Eksternal • Pemerintah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Sistem pengawasan yang berada di bawah kendali presiden selaku kepala eksekutif disebut...", "options": ["Sistem Pengawasan Peradilan Mahkamah", "Sistem Pengawasan Moneter Independen", "Sistem Pengawasan Internal Pemerintah", "Sistem Pengawasan Komisi Yudisial Negara", "Sistem Pengawasan Oposisi Parlemen Dewan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sebutan Sistem Pengawasan Pemerintah Pusat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sistem Pengawasan Pemerintah Pusat disebut sebagai <strong>Sistem Pengawasan Internal Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena aparat pengawasan internal pemerintah (APIP) seperti BPKP dan Itjen kementerian/lembaga berada di dalam ranah kekuasaan eksekutif untuk mengawal kebijakan presiden.</p><p>Jawaban tepat: <strong>C. Sistem Pengawasan Internal Pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengawasan peradilan berada di bawah Mahkamah Agung.</li><li><strong>B</strong>: Pengawasan moneter adalah wewenang independen bank sentral (BI).</li><li><strong>D</strong>: Komisi Yudisial mengawasi martabat dan perilaku para hakim peradilan.</li><li><strong>E</strong>: Oposisi parlemen menjalankan fungsi politik pengawasan legislatif DPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 66) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Internal Pemerintah.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Sistem pengawasan atas pelaksanaan APBN secara komprehensif dikelompokkan menjadi...", "options": ["Sistem penyusunan pagu anggaran dan sistem realisasi belanja", "Sistem verifikasi anggaran kas dan sistem pembukuan akrual", "Sistem audit pajak pertambahan nilai dan bea cukai impor", "Sistem pengawasan eksternal dan sistem pengawasan internal", "Sistem pinjaman dalam negeri dan sistem hibah luar negeri"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengelompokan Pengawasan Pelaksanaan APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Sistem Pengawasan APBN dapat dikelompokkan menjadi sistem pengawasan eksternal dan sistem pengawasan internal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kedua kelompok pengawasan ini saling melengkapi untuk menjamin uang rakyat dalam APBN dikelola secara akuntabel, transparan, dan taat hukum.</p><p>Jawaban tepat: <strong>D. Sistem pengawasan eksternal dan sistem pengawasan internal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pagu dan realisasi belanja adalah siklus perencanaan anggaran.</li><li><strong>B</strong>: Verifikasi kas dan pembukuan akrual adalah mekanisme akuntansi.</li><li><strong>C</strong>: Audit PPN dan cukai merupakan pemeriksaan penerimaan perpajakan.</li><li><strong>E</strong>: Pinjaman dan hibah adalah instrumen sumber pembiayaan defisit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 66) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>eksternal dan sistem pengawasan internal.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan amanat UUD 1945, kekuasaan pemerintahan negara dipegang oleh presiden menurut...", "options": ["Ketetapan komite ekonomi kamar dagang niaga", "Keputusan bersama pimpinan partai politik", "Hasil musyawarah majelis perwakilan daerah", "Petunjuk teknis operasional dewan moneter", "Undang-Undang Dasar Republik Indonesia"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kekuasaan Pemerintahan Presiden Menurut UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebagaimana diamanatkan dalam UUD 1945 bahwa <strong>presiden memegang kekuasaan pemerintahan menurut Undang-Undang Dasar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Indonesia menganut sistem presidensial berlandaskan konstitusi hukum, di mana presiden menjalankan mandat kekuasaan eksekutif berdasarkan koridor UUD 1945.</p><p>Jawaban tepat: <strong>E. Undang-Undang Dasar Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ketetapan kamar dagang adalah produk asosiasi pelaku niaga swasta.</li><li><strong>B</strong>: Pemerintahan negara dijalankan atas dasar hukum dasar, bukan koalisi partai.</li><li><strong>C</strong>: Musyawarah perwakilan daerah tidak menetapkan kekuasaan presiden.</li><li><strong>D</strong>: Petunjuk dewan moneter mengatur stabilitas moneter perbankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 66) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kekuasaan pemerintahan menurut Undang-Undang Dasar.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Dalam rangka melaksanakan tugas kepemerintahan, pemerintah memerlukan dana pembiayaan dalam bentuk...", "options": ["Anggaran Pendapatan dan Belanja Negara (APBN)", "Pinjaman modal kerja jangka pendek komersial", "Dana simpanan darurat pejabat administrator", "Penerbitan saham perdana perusahaan persero", "Bantuan iuran sukarela pegawai negeri sipil"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kebutuhan Pembiayaan Tugas Kepemerintahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam melaksanakan tugas kepemerintahannya, presiden memerlukan dana <strong>untuk pembiayaannya dalam bentuk APBN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena APBN merupakan instrumen keuangan negara utama yang memuat rencana pendapatan, belanja, dan pembiayaan untuk membiayai seluruh urusan pemerintahan.</p><p>Jawaban tepat: <strong>A. Anggaran Pendapatan dan Belanja Negara (APBN)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pinjaman komersial jangka pendek bukan bentuk rencana pembiayaan negara.</li><li><strong>C</strong>: Simpanan darurat pejabat pribadi dilarang membiayai dinas negara.</li><li><strong>D</strong>: Penerbitan saham perdana adalah aksi korporasi pasar modal BUMN.</li><li><strong>E</strong>: Iuran sukarela pegawai bukan sumber keuangan resmi penyelenggaraan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 66) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>untuk pembiayaannya dalam bentuk APBN.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Penetapan struktur pengawasan pada hakikatnya dirumuskan dengan berpijak pada...", "options": ["Model manajemen korporasi multinasional global", "Struktur Sistem Pemerintahan Negara Indonesia", "Struktur permodalan pasar valuta asing dunia", "Sistem komersialisasi perbankan investasi swasta", "Model peradilan arbitrase perdagangan regional"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Struktur Pengawasan Pemerintahan Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dilihat dari <strong>struktur pengawasan yang berlaku dalam Sistem Pemerintahan Negara Indonesia maka sistem</strong> pengawasan dibagi atas sistem eksternal dan internal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena desain mekanisme pengawasan publik harus selaras dengan pembagian kekuasaan dan tata kelola kenegaraan yang diatur dalam konstitusi Indonesia.</p><p>Jawaban tepat: <strong>B. Struktur Sistem Pemerintahan Negara Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Manajemen korporasi swasta berorientasi pada pencarian laba usaha komersial.</li><li><strong>C</strong>: Pasar valuta asing adalah pasar perdagangan instrumen mata uang.</li><li><strong>D</strong>: Perbankan investasi mengelola transaksi pasar modal korporasi.</li><li><strong>E</strong>: Arbitrase perdagangan menyelesaikan sengketa perdata bisnis internasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 66) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sistem Pemerintahan Negara Indonesia maka sistem</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Jika instansi pemerintah mengaudit anggaran secara internal sebelum diaudit BPK, penerapan tersebut merupakan...", "options": ["Pencegahan dini keterlibatan pimpinan lembaga perwakilan dewan", "Tumpang tindih pemeriksaan yang merugikan keuangan kantor", "Sinergi sistem pengawasan internal dan pengawasan eksternal", "Pengurangan wewenang konstitusional pemeriksa eksternal negara", "Pembatasan hak aparat penegak hukum dalam mengusut kasus"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Komplementer Pengawasan APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengawasan APBN memadukan <strong>sistem pengawasan eksternal dan sistem pengawasan internal</strong> agar terwujud tata kelola keuangan yang transparan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pengawasan internal (APIP) berfungsi sebagai lapis pertahanan pertama manajerial, sedangkan pemeriksaan BPK (eksternal) memberikan penilaian independen atas kewajaran laporan keuangan.</p><p>Jawaban tepat: <strong>C. Sinergi sistem pengawasan internal dan pengawasan eksternal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Audit internal tidak ditujukan untuk mencegah keterlibatan dewan.</li><li><strong>B</strong>: Audit bertahap internal dan eksternal bukan tumpang tindih merugikan.</li><li><strong>D</strong>: Pemeriksaan internal tidak mengurangi wewenang konstitusional BPK.</li><li><strong>E</strong>: Audit reguler tidak membatasi kewenangan penyelidikan aparat penegak hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 66) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>eksternal dan sistem pengawasan internal.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pada hakikatnya, kedudukan hukum APBN merupakan wujud pemberian...", "options": ["Surat kuasa khusus penarikan dana cadangan kas", "Instruksi langsung dari lembaga perbankan dunia", "Perjanjian perdata antara menteri dan pengusaha", "Mandat dari DPR RI kepada pihak pemerintah", "Ketetapan mandiri presiden tanpa persetujuan dewan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat APBN Sebagai Mandat DPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pada hakekatnya APBN tersebut merupakan mandat yang diberikan oleh DPR RI kepada pemerintah</strong> untuk melakukan penerimaan dan pengeluaran.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena DPR memegang hak anggaran atas nama rakyat untuk memberi mandat dan otorisasi batas pengeluaran kepada pemerintah melalui pengesahan undang-undang APBN.</p><p>Jawaban tepat: <strong>D. Mandat dari DPR RI kepada pihak pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Surat kuasa khusus adalah instrumen penatausahaan kas perbendaharaan.</li><li><strong>B</strong>: Bank dunia adalah mitra multilateral, bukan pemberi mandat APBN.</li><li><strong>C</strong>: APBN adalah produk hukum publik ketatanegaraan, bukan kontrak perdata.</li><li><strong>E</strong>: Presiden dilarang menetapkan APBN secara mandiri tanpa persetujuan DPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 67) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pada hakekatnya APBN tersebut merupakan mandat yang diberikan oleh DPR RI • kepada pemerintah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Mandat APBN yang diserahkan oleh DPR memberikan wewenang kepada pemerintah untuk...", "options": ["Menyalurkan kredit konsumtif langsung kepada masyarakat umum", "Mencetak uang secara bebas tanpa batas cadangan devisa kas", "Menyita seluruh laba bersih badan usaha milik swasta nasional", "Menetapkan tarif pajak daerah tanpa persetujuan kepala wilayah", "Melakukan penerimaan pendapatan dan membiayai pengeluaran negara"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Substansi Mandat APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mandat APBN adalah untuk <strong>melakukan penerimaan pendapatan negara dan menggunakan penerimaan tersebut untuk membiayai pengeluaran</strong> guna mencapai tujuan tertentu.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena fungsi otorisasi APBN menjadi dasar hukum bagi pemerintah untuk memungut pendapatan negara dan membelanjakannya sesuai batas pagu yang disetujui.</p><p>Jawaban tepat: <strong>E. Melakukan penerimaan pendapatan dan membiayai pengeluaran negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyaluran kredit konsumtif masyarakat adalah bisnis bank umum komersial.</li><li><strong>B</strong>: Pencetakan uang diatur oleh bank sentral dengan prinsip stabilitas moneter.</li><li><strong>C</strong>: Pemerintah hanya memungut pajak legal, bukan menyita laba swasta.</li><li><strong>D</strong>: Tarif pajak daerah diatur dalam perda bersama DPRD wilayah setempat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 67) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>melakukan penerimaan pendapatan negara dan • menggunakan penerimaan tersebut untuk membiayai pengeluaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "APBN ditetapkan tiap tahun dengan instrumen hukum yang memerlukan...", "options": ["Undang-Undang atas persetujuan bersama DPR RI dengan presiden", "Peraturan Pemerintah Pengganti Undang-Undang secara rutin tahunan", "Surat Edaran Bersama pimpinan komisi dewan dan menteri kabinet", "Keputusan Presiden tanpa kewajiban pembahasan bersama dewan", "Ketetapan musyawarah kerja menteri koordinator perekonomian"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Hukum Penetapan APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>APBN <strong>ditetapkan tiap-tiap tahun dengan Undang-Undang dan setiap Undang-Undang menghendaki persetujuan bersama DPR RI dengan presiden</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena ketentuan pasal 23 UUD 1945 mengamanatkan bahwa APBN sebagai wujud pengelolaan keuangan negara ditetapkan tiap tahun dengan undang-undang atas persetujuan bersama legislatif dan eksekutif.</p><p>Jawaban tepat: <strong>A. Undang-Undang atas persetujuan bersama DPR RI dengan presiden</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Perppu hanya diterbitkan pada situasi kegentingan memaksa yang darurat.</li><li><strong>C</strong>: Surat edaran bersama adalah pedoman administratif kedinasan.</li><li><strong>D</strong>: Keppres berada di bawah hierarki undang-undang dan tidak memuat APBN.</li><li><strong>E</strong>: Ketetapan menteri koordinator mengatur koordinasi teknis kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 67) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>ditetapkan tiap-tiap tahun dengan Undang-Undang dan setiap Undang-Undang • menghendaki persetujuan bersama DPR RI dengan presiden.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Sebagai konsekuensi atas mandat anggaran yang telah disetujui dewan, pemerintah berkewajiban...", "options": ["Menyerahkan seluruh aset fisik kementerian kepada dewan perwakilan", "Memberikan pertanggungjawaban atas pelaksanaan APBN kepada DPR", "Membubarkan badan peradilan yang memeriksa sengketa perbendaharaan", "Menghapus seluruh catatan utang negara dari neraca keuangan kas", "Meminta izin harian kepada komisi dewan untuk setiap mutasi kas"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Pertanggungjawaban Pelaksanaan APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai dengan ketentuan perundangan, <strong>pemerintah berkewajiban memberikan pertanggungjawaban atas pelaksanaan APBN yang telah disetujui oleh DPR</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pertanggungjawaban keuangan merupakan perwujudan prinsip akuntabilitas publik atas penggunaan dana mandat rakyat yang dikelola pemerintah.</p><p>Jawaban tepat: <strong>B. Memberikan pertanggungjawaban atas pelaksanaan APBN kepada DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aset kementerian tetap berstatus Barang Milik Negara (BMN) eksekutif.</li><li><strong>C</strong>: Peradilan sengketa perbendaharaan dilindungi oleh peraturan hukum resmi.</li><li><strong>D</strong>: Catatan utang wajib dilaporkan secara jujur dalam neraca dan CaLK.</li><li><strong>E</strong>: Izin dewan diberikan secara gelondongan per program, bukan harian kasir.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 67) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemerintah berkewajiban memberikan • pertanggungjawaban atas pelaksanaan APBN yang telah disetujui oleh DPR</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Kewajiban presiden menyampaikan RUU pertanggungjawaban pelaksanaan APBN ditegaskan dalam...", "options": ["Pasal 5 Peraturan Bank Indonesia tentang Lalu Lintas Devisa", "Pasal 10 ayat 4 Keputusan Menteri Keuangan Nomor 20 Tahun 2001", "Pasal 30 ayat 1 dan 2 Undang-Undang Nomor 17 Tahun 2003", "Pasal 18 Instruksi Presiden tentang Penghematan Belanja Dinas", "Pasal 8 Surat Edaran Direktur Jenderal Pajak tentang SPT Masa"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum Pertanggungjawaban UU 17/2003</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pertanggungjawaban pelaksanaan APBN diatur dalam <strong>pasal 30 ayat 1 dan 2 Undang-Undang Nomor 17 Tahun 2003</strong> tentang Keuangan Negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pasal 30 UU 17/2003 mewajibkan presiden menyampaikan rancangan undang-undang tentang pertanggungjawaban pelaksanaan APBN berupa laporan keuangan yang telah diperiksa BPK.</p><p>Jawaban tepat: <strong>C. Pasal 30 ayat 1 dan 2 Undang-Undang Nomor 17 Tahun 2003</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Peraturan BI mengatur kebijakan moneter dan stabilitas nilai rupiah.</li><li><strong>B</strong>: Keputusan menteri keuangan mengatur teknis operasional departemen.</li><li><strong>D</strong>: Inpres penghematan adalah pedoman internal pembatasan anggaran dinas.</li><li><strong>E</strong>: Surat edaran dirjen pajak mengatur pelaporan administrasi perpajakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 67) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pasal 30 ayat 1 dan 2 Undang-Undang Nomor 17 Tahun 2003</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Untuk memeriksa pengelolaan keuangan negara, UUD 1945 mengamanatkan pembentukan Badan...", "options": ["Audit Investasi Asing yang berada di bawah perwakilan donor", "Pengawas Perbankan di bawah naungan asosiasi kredit swasta", "Penyelidik Khusus yang tunduk pada arahan partai penguasa", "Pemeriksa Keuangan yang berkedudukan bebas dan mandiri", "Penilai Pajak Daerah yang dikelola oleh konsorsium niaga"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan BPK Menurut Pasal 23E UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 23E ayat 1 UUD 1945 menyatakan untuk memeriksa pengelolaan dan tanggung jawab keuangan negara diadakan satu <strong>Badan Pemeriksa Keuangan yang bebas dan mandiri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kemandirian (independensi) mutlak diperlukan agar auditor negara dapat memeriksa dan memberikan penilaian tanpa intervensi pihak mana pun.</p><p>Jawaban tepat: <strong>D. Pemeriksa Keuangan yang berkedudukan bebas dan mandiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemeriksa keuangan negara mengabdi pada kedaulatan bangsa, bukan donor.</li><li><strong>B</strong>: Pengawasan perbankan swasta bukan lembaga audit konstitusional negara.</li><li><strong>C</strong>: Badan audit negara dilarang dipengaruhi oleh kepentingan partai politik.</li><li><strong>E</strong>: Penilai pajak daerah bukan lembaga audit eksternal tertinggi negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 67) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemeriksa Keuangan yang bebas dan mandiri.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan ketentuan Pasal 20A UUD 1945, DPR memiliki tiga fungsi pokok yaitu...", "options": ["Fungsi komersial, fungsi penanaman modal, dan fungsi laba", "Fungsi peradilan, fungsi eksekusi, dan fungsi mediasi", "Fungsi operasional, fungsi kebendaharaan, dan fungsi kas", "Fungsi verifikasi, fungsi pembukuan, dan fungsi pelaporan", "Fungsi legislasi, fungsi anggaran, dan fungsi pengawasan"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Fungsi Pokok DPR Menurut Konstitusi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam pasal 20A UUD 1945 disebutkan bahwa <strong>DPR memiliki fungsi legislasi, fungsi anggaran dan fungsi pengawasan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena ketiga fungsi ini menjadi pilar pelaksanaan mandat kedaulatan rakyat oleh DPR dalam menyusun UU, mengesahkan APBN, dan mengawasi jalannya pemerintahan.</p><p>Jawaban tepat: <strong>E. Fungsi legislasi, fungsi anggaran, dan fungsi pengawasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Fungsi komersial dan penanaman modal adalah fungsi entitas bisnis korporasi.</li><li><strong>B</strong>: Peradilan, eksekusi, dan mediasi adalah fungsi cabang yudikatif pengadilan.</li><li><strong>C</strong>: Operasional dan kebendaharaan merupakan fungsi pengelola kas kementerian.</li><li><strong>D</strong>: Verifikasi dan pembukuan adalah tugas teknis satuan kerja akuntansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 67) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>fungsi legislasi, fungsi anggaran dan fungsi pengawasan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pengawasan operasional terhadap pelaksanaan APBN oleh kementerian mitra kerja dilakukan dewan...", "options": ["Lewat komisi-komisi yang ada di lingkungan DPR RI", "Melalui serikat pegawai administrasi sekretariat jenderal", "Melalui perwakilan kantor hukum perdata advokat swasta", "Lewat panitia lelang pengadaan barang dan jasa kantor", "Melalui lembaga swadaya kemasyarakatan tingkat daerah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelaksanaan Tugas Pengawasan Oleh Komisi DPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Secara operasional, pengawasan DPR dilakukan oleh alat-alat kelengkapan dewan antara lain <strong>lewat komisi-komisi yang ada di DPR</strong> sesuai lingkup tugasnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena komisi-komisi DPR merupakan alat kelengkapan yang membidangi sektor kementerian tertentu (misalnya Komisi XI membidangi keuangan dan perbankan).</p><p>Jawaban tepat: <strong>A. Lewat komisi-komisi yang ada di lingkungan DPR RI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sekretariat jenderal bertugas mendukung administrasi kelembagaan dewan.</li><li><strong>C</strong>: Kantor hukum advokat bertindak mewakili kepentingan perdata klien.</li><li><strong>D</strong>: Panitia lelang adalah pejabat fungsional pengadaan barang pemerintah.</li><li><strong>E</strong>: LSM adalah wadah swadaya masyarakat di luar alat kelengkapan DPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 67) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>lewat komisi-komisi yang ada di DPR</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Bahan pertimbangan pengawasan APBN yang terkait dengan daerah diperoleh DPR dari Dewan...", "options": ["Penasihat Keuangan Asing pengelola investasi perbankan", "Perwakilan Daerah (DPD) yang mengawasi pelaksanaan APBN", "Pertimbangan Perdagangan Bebas wilayah perbatasan laut", "Pemeriksa Kepatuhan Pajak daerah tingkat kabupaten", "Pengawas Koperasi Mikro dan usaha perdesaan terpencil"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengawasan APBN Oleh Dewan Perwakilan Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>DPR memperoleh bahan pertimbangan untuk ditindaklanjuti dari <strong>Dewan Perwakilan Daerah (DPD) yang melakukan pengawasan atas pelaksanaan APBN</strong> di daerah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena DPD memiliki kewenangan konstitusional mengawasi pelaksanaan undang-undang mengenai APBN, pajak, pendidikan, dan agama yang berkaitan dengan kepentingan daerah.</p><p>Jawaban tepat: <strong>B. Perwakilan Daerah (DPD) yang mengawasi pelaksanaan APBN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penasihat asing tidak memiliki status konstitusional pengawasan APBN.</li><li><strong>C</strong>: Pertimbangan perdagangan bebas adalah forum koordinasi ekspor-impor.</li><li><strong>D</strong>: Pemeriksa pajak kabupaten berada di bawah dinas pendapatan pemda.</li><li><strong>E</strong>: Pengawas koperasi bertugas membina entitas koperasi simpan pinjam.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 67) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Perwakilan Daerah (DPD) yang melakukan pengawasan atas pelaksanaan APBN</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Fungsi BPK dalam melaksanakan pemeriksaan pertanggungjawaban keuangan negara dinamakan...", "options": ["Fungsi penyusunan standar tarif retribusi pelayanan", "Fungsi penuntutan perkara kejahatan aparatur sipil", "Fungsi audit dan operasional atas pelaksanaan APBN", "Fungsi pencairan surat perintah membayar bendahara", "Fungsi pembentukan organisasi kantor badan peradilan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Audit dan Operasional BPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Fungsi audit dan operasional yaitu melaksanakan pemeriksaan atas tanggung jawab Keuangan Negara dan pelaksanaan APBN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena fungsi ini mencakup kegiatan operasional pemeriksaan lapangan secara langsung terhadap pengelolaan dan pertanggungjawaban anggaran di seluruh entitas negara.</p><p>Jawaban tepat: <strong>C. Fungsi audit dan operasional atas pelaksanaan APBN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyusunan tarif pelayanan publik ditetapkan oleh menteri/kepala daerah.</li><li><strong>B</strong>: Penuntutan pidana adalah wewenang kejaksaan dan penegak hukum.</li><li><strong>D</strong>: Pencairan SPM merupakan fungsi perbendaharaan kantor KPPN.</li><li><strong>E</strong>: Pembentukan badan peradilan diatur oleh Mahkamah Agung dan undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 68) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Fungsi audit dan operasional yaitu melaksanakan pemeriksaan atas tanggung • jawab Keuangan Negara dan pelaksanaan APBN.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Wewenang BPK dalam mengadili perkara ganti kerugian negara melalui peradilan komptabel merupakan...", "options": ["Fungsi mediasi sengketa waris perdata keluarga bendahara", "Fungsi legislatif penetapan rancangan undang-undang anggaran", "Fungsi eksekutif penagihan pajak paksa dengan surat sita", "Fungsi yudikatif dalam hal tuntutan perbendaharaan negara", "Fungsi administrasi kepegawaian mutasi pejabat fungsional"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Yudikatif BPK (Peradilan Komptabel)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Fungsi yudikatif yaitu melakukan peradilan komptabel dalam hal tuntutan perbendaharaan</strong> terhadap bendahara yang lalai merugikan negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena BPK memiliki wewenang yudisial memproses dan menetapkan Tuntutan Ganti Rugi (TGR) kepada bendahara pengelola keuangan yang menimbulkan kerugian kas negara.</p><p>Jawaban tepat: <strong>D. Fungsi yudikatif dalam hal tuntutan perbendaharaan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sengketa waris perdata diselesaikan di Pengadilan Agama atau Negeri.</li><li><strong>B</strong>: Penetapan RUU anggaran adalah fungsi bersama DPR dan presiden.</li><li><strong>C</strong>: Penagihan pajak aktif dilakukan oleh jurusita pajak kementerian.</li><li><strong>E</strong>: Mutasi kepegawaian adalah wewenang Badan Kepegawaian Negara dan PPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 68) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Fungsi yudikatif yaitu melakukan peradilan komptabel dalam hal tuntutan • perbendaharaan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pemberian saran atau pertimbangan oleh BPK kepada pemerintah demi perbaikan keuangan negara merupakan...", "options": ["Fungsi intervensi langsung penentuan tender pengadaan jasa", "Fungsi komando mutlak yang wajib dipatuhi oleh presiden", "Fungsi pengenaan sanksi denda administratif secara tunai", "Fungsi pelimpahan wewenang audit kepada kantor akuntan", "Fungsi rekomendasi bagi perbaikan tata kelola keuangan"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Rekomendasi BPK Kepada Pemerintah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Fungsi rekomendasi yaitu memberi saran dan atau pertimbangan kepada Pemerintah</strong> bilamana dipandang perlu untuk kepentingan negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena rekomendasi audit BPK bertujuan mendorong perbaikan sistem pengendalian intern dan kepatuhan perundang-undangan pada entitas pemerintah yang diperiksa.</p><p>Jawaban tepat: <strong>E. Fungsi rekomendasi bagi perbaikan tata kelola keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Auditor negara dilarang melakukan intervensi proses tender pengadaan.</li><li><strong>B</strong>: Saran BPK bersifat rekomendasi perbaikan, bukan garis komando eksekutif.</li><li><strong>C</strong>: Sanksi administratif diterapkan oleh instansi pembina pegawai.</li><li><strong>D</strong>: Pelimpahan wewenang audit bukan makna dari fungsi rekomendasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 68) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Fungsi rekomendasi yaitu memberi saran dan atau pertimbangan kepada • Pemerintah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 4 UU Nomor 15 Tahun 2004, pemeriksaan BPK terdiri atas...", "options": ["Tiga tipe utama pemeriksaan keuangan, kinerja, dan PDTT", "Dua macam pemeriksaan yaitu pemeriksaan kas dan inventaris", "Empat tingkatan pengujian kelayakan kredit usaha daerah", "Lima tahapan penilaian kepatuhan administrasi surat dinas", "Enam model investigasi rahasia transaksi pasar modal"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Tipe Utama Pemeriksaan BPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 4 UU 15/2004, <strong>pemeriksaan yang dilakukan BPK terdiri atas 3 tipe utama</strong>: pemeriksaan keuangan, pemeriksaan kinerja, dan pemeriksaan dengan tujuan tertentu.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena undang-undang audit keuangan negara membakukan tiga kategori pemeriksaan ini untuk memenuhi kebutuhan akuntabilitas yang menyeluruh.</p><p>Jawaban tepat: <strong>A. Tiga tipe utama pemeriksaan keuangan, kinerja, dan PDTT</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pemeriksaan kas dan inventaris hanya bagian dari teknik pemeriksaan.</li><li><strong>C</strong>: Uji kredit usaha adalah tugas analis perbankan komersial.</li><li><strong>D</strong>: Penilaian surat dinas adalah ranah tata persuratan sekretariat.</li><li><strong>E</strong>: Investasi pasar modal diawasi oleh pengawas bursa dan OJK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 68) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemeriksaan yang dilakukan BPK terdiri atas 3 tipe utama</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama BPK melaksanakan Pemeriksaan Keuangan atas laporan pertanggungjawaban adalah...", "options": ["Menghitung tingkat keuntungan komersial yang diperoleh satker", "Memberikan opini tentang tingkat kewajaran informasi laporan", "Menetapkan besaran gaji pokok bulanan aparatur sipil negara", "Menjatuhkan putusan hukuman kurungan bagi pejabat pelaksana", "Mengambil alih seluruh pengelolaan kas pada bank operasional"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pemeriksaan Keuangan BPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemeriksaan keuangan dilakukan oleh BPK dalam rangka <strong>memberikan opini tentang tingkat kewajaran informasi yang disajikan dalam laporan keuangan pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena opini audit (WTP, WDP, TW, TMP) merupakan pernyataan profesional pemeriksa mengenai kesesuaian penyajian laporan keuangan dengan Standar Akuntansi Pemerintahan.</p><p>Jawaban tepat: <strong>B. Memberikan opini tentang tingkat kewajaran informasi laporan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemerintah bukan entitas komersial pencari laba pemodal.</li><li><strong>C</strong>: Gaji pokok ASN diatur oleh peraturan pemerintah tentang gaji aparatur.</li><li><strong>D</strong>: Hukuman kurungan dijatuhkan oleh hakim pengadilan tindak pidana.</li><li><strong>E</strong>: Pengelolaan kas kasir adalah wewenang bendahara umum negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 68) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>memberikan opini tentang tingkat kewajaran informasi yang disajikan • dalam laporan keuangan pemerintah.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pemeriksaan kinerja BPK memfokuskan pengujian program kerja pemerintah pada aspek...", "options": ["Aspek loyalitas politik pegawai terhadap pimpinan kementerian", "Aspek kepatutan busana dinas dan kerapian dokumen kearsipan", "Aspek ekonomi, efisiensi, dan efektivitas pengelolaan anggaran", "Aspek keuntungan devisa dari kegiatan perdagangan komersial", "Aspek kekayaan pribadi pimpinan lembaga non-departemen dinas"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Aspek Pemeriksaan Kinerja (3E)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pemeriksaan kinerja, merupakan pemeriksaan atas aspek ekonomi dan efisiensi, serta pemeriksaan atas aspek efektivitas</strong> (3E) kegiatan pemerintah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena prinsip *value for money* (ekonomis, efisien, dan efektif) memastikan penggunaan sumber daya negara menghasilkan dampak nyata bagi masyarakat.</p><p>Jawaban tepat: <strong>C. Aspek ekonomi, efisiensi, dan efektivitas pengelolaan anggaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: ASN diwajibkan netral dan tidak diuji dari loyalitas politik.</li><li><strong>B</strong>: Kerapian fisik adalah urusan kerumahtanggaan kantor setempat.</li><li><strong>D</strong>: Pemeriksaan kinerja tidak menitikberatkan laba dagang valas komersial.</li><li><strong>E</strong>: Kekayaan pribadi dilaporkan terpisah melalui instrumen LHKPN KPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 68) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pemeriksaan kinerja, merupakan pemeriksaan atas aspek ekonomi dan • efisiensi, serta pemeriksaan atas aspek efektivitas</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Bagi lembaga perwakilan (DPR/DPRD), tujuan utama pemeriksaan kinerja BPK adalah...", "options": ["Menyetujui kenaikan anggaran operasional fraksi-fraksi partai", "Menyusun draft rancangan peraturan daerah tentang retribusi", "Menunjuk langsung kantor akuntan publik rekanan lelang", "Mengidentifikasikan hal-hal yang perlu menjadi perhatian dewan", "Menghentikan seluruh pencairan belanja transfer bagi daerah"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pemeriksaan Kinerja Bagi Lembaga Perwakilan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan pemeriksaan kinerja bagi lembaga perwakilan adalah untuk <strong>mengidentifikasikan hal-hal yang perlu menjadi perhatian lembaga perwakilan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena temuan dan simpulan audit kinerja memberikan wawasan faktual bagi anggota dewan dalam mengawasi efektivitas kebijakan pemerintah.</p><p>Jawaban tepat: <strong>D. Mengidentifikasikan hal-hal yang perlu menjadi perhatian dewan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Audit kinerja tidak ditujukan untuk menaikkan anggaran fraksi partai.</li><li><strong>B</strong>: Perda retribusi dirancang oleh pemerintah daerah bersama DPRD.</li><li><strong>C</strong>: Penunjukan KAP rekanan bukan hak dari anggota dewan.</li><li><strong>E</strong>: Penghentian transfer anggaran diatur oleh regulasi menteri keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 68) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mengidentifikasikan hal-hal yang perlu menjadi perhatian lembaga perwakilan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pemeriksaan dengan tujuan tertentu yang diselenggarakan oleh BPK mencakup...", "options": ["Pemeriksaan dokumen pendaftaran peserta pemilu wilayah", "Pemeriksaan kelayakan izin mendirikan bangunan di daerah", "Penilaian tingkat kepuasan pelanggan restoran komersial", "Pemeriksaan kesehatan jasmani calon pegawai negeri sipil", "Pemeriksaan hal lain berkaitan keuangan dan investigatif"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ruang Lingkup Pemeriksaan Dengan Tujuan Tertentu (PDTT)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Termasuk dalam pemeriksaan tujuan tertentu adalah <strong>pemeriksaan atas hal-hal lain yang berkaitan dengan keuangan dan pemeriksaan investigatif</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena PDTT dirancang khusus di luar audit keuangan dan kinerja, seperti audit kepatuhan pengadaan barang atau pemeriksaan investigatif atas indikasi kecurangan.</p><p>Jawaban tepat: <strong>E. Pemeriksaan hal lain berkaitan keuangan dan investigatif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dokumen pemilu diperiksa oleh Komisi Pemilihan Umum (KPU).</li><li><strong>B</strong>: Izin bangunan adalah wewenang dinas tata ruang pemda setempat.</li><li><strong>C</strong>: Kepuasan restoran adalah survei pasar swasta pelaku usaha kuliner.</li><li><strong>D</strong>: Kesehatan fisik CPNS diperiksa oleh tim dokter penguji instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 68) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>atas hal-hal lain yang berkaitan dengan keuangan dan pemeriksaan investigatif.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C4 - Analisis", "question": "Pernyataan yang tepat mengenai pembedaan ketiga tipe pemeriksaan BPK adalah...", "options": ["Pemeriksaan keuangan menghasilkan opini sedangkan kinerja menguji 3E", "Pemeriksaan keuangan mencari pidana sedangkan kinerja memberi opini", "Pemeriksaan kinerja mengaudit neraca sedangkan keuangan meneliti kasus", "Pemeriksaan PDTT memberikan opini sedangkan keuangan membuat saran", "Ketiga tipe pemeriksaan menghasilkan opini yang sama secara seragam"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembedaan Karakter 3 Jenis Pemeriksaan BPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan UU 15/2004, <strong>pemeriksaan yang dilakukan BPK terdiri atas 3 tipe utama</strong>: keuangan (menghasilkan opini), kinerja (menghasilkan temuan 3E dan rekomendasi), serta PDTT (menghasilkan kesimpulan).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pemeriksaan keuangan fokus pada kewajaran penyajian laporan (opini), sedangkan pemeriksaan kinerja menilai aspek ekonomi, efisiensi, dan efektivitas pelaksanaan program.</p><p>Jawaban tepat: <strong>A. Pemeriksaan keuangan menghasilkan opini sedangkan kinerja menguji 3E</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pemeriksaan keuangan menghasilkan opini wajar, bukan vonis tindak pidana.</li><li><strong>C</strong>: Audit neraca masuk ranah audit keuangan, bukan pemeriksaan kinerja.</li><li><strong>D</strong>: Pemeriksaan dengan tujuan tertentu (PDTT) menghasilkan kesimpulan, bukan opini.</li><li><strong>E</strong>: Ketiga tipe pemeriksaan memiliki fokus dan produk output laporan yang berbeda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 68) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemeriksaan yang dilakukan BPK terdiri atas 3 tipe utama</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pengujian kepatuhan pengeluaran uang negara pada hakikatnya mencakup pemeriksaan...", "options": ["Pemeriksaan kas operasional sekretariat dewan pembina yayasan", "Pemeriksaan atas penerimaan dan anggaran belanja negara", "Penilaian perolehan omzet penjualan toko ritel swasta", "Pengujian tarif sewa kendaraan angkutan umum perkotaan", "Pemeriksaan iuran perumahan warga pada tingkat rukun warga"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ruang Lingkup Pemeriksaan Penerimaan dan Belanja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemeriksaan BPK meliputi <strong>pemeriksaan atas penerimaan anggaran dan pemeriksaan atas anggaran belanja negara</strong> sesuai UU Perbendaharaan Negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kedua sisi anggaran (penerimaan kas negara dan pengeluaran belanja dinas) wajib diuji kepatuhannya terhadap batas pagu APBN dan aturan perundang-undangan.</p><p>Jawaban tepat: <strong>B. Pemeriksaan atas penerimaan dan anggaran belanja negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Iuran paguyuban kantor adalah dana internal sosial non-APBN.</li><li><strong>C</strong>: Omzet ritel swasta adalah pendapatan transaksi bisnis komersial.</li><li><strong>D</strong>: Tarif angkutan kota ditetapkan oleh dinas perhubungan pemda.</li><li><strong>E</strong>: Iuran rukun warga adalah kas lingkungan sosial ketetanggaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 69) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemeriksaan atas penerimaan • anggaran dan pemeriksaan atas anggaran belanja negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Menurut tujuannya, salah satu fokus pemeriksaan yang diselenggarakan BPK adalah...", "options": ["Pemeriksaan atas kepemilikan aset properti para direksi bank", "Pemeriksaan atas keharmonisan hubungan kerja antar-pegawai", "Pemeriksaan atas penguasaan dan pengurusan keuangan negara", "Penilaian atas strategi promosi produk olahan makanan instan", "Pemeriksaan tata cara pemungutan retribusi parkir kendaraan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemeriksaan Penguasaan dan Pengurusan Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut tujuannya pemeriksaan BPK terdiri atas: <strong>pemeriksaan atas penguasaan dan pengurusan keuangan</strong>, ketaatan aturan, kehematan/efisiensi, dan efektivitas tujuan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena penguasaan dan pengurusan kebendaharaan (komptabel) memastikan pejabat pengelola keuangan bertanggung jawab penuh atas uang yang dipercayakan kepadanya.</p><p>Jawaban tepat: <strong>C. Pemeriksaan atas penguasaan dan pengurusan keuangan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aset pribadi direksi bank swasta bukan domain audit anggaran APBN.</li><li><strong>B</strong>: Keharmonisan kerja adalah aspek sosiologis manajemen SDM kantor.</li><li><strong>D</strong>: Strategi promosi makanan adalah taktik bisnis pemasaran korporasi.</li><li><strong>E</strong>: Retribusi parkir daerah diperiksa oleh inspektorat kabupaten/kota.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 69) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemeriksaan atas penguasaan dan pengurusan keuangan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pemeriksaan BPK yang bertujuan menilai ketiadaan pemborosan dalam alokasi anggaran adalah...", "options": ["Pemeriksaan tingkat kunjungan wisatawan di tempat rekreasi", "Pemeriksaan atas batas kewajaran laba perusahaan swasta", "Pemeriksaan atas target volume penjualan komoditas korporasi", "Pemeriksaan atas kehematan dan efisiensi keuangan negara", "Pemeriksaan mutu bahan bangunan proyek perumahan warga"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemeriksaan Kehematan dan Efisiensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemeriksaan BPK mencakup <strong>pemeriksaan atas kehematan dan efisiensi dalam penggunaan keuangan negara</strong> agar setiap rupiah menghasilkan output optimal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena uji kehematan (ekonomi) dan efisiensi memastikan pengadaan input dilakukan dengan harga bersaing tanpa ada inefisiensi biaya operasional.</p><p>Jawaban tepat: <strong>D. Pemeriksaan atas kehematan dan efisiensi keuangan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kunjungan wisata adalah indikator sektor pariwisata daerah.</li><li><strong>B</strong>: Laba korporasi swasta ditentukan oleh persaingan pasar modal.</li><li><strong>C</strong>: Volume panen pangan dihitung oleh kementerian pertanian/BPS.</li><li><strong>E</strong>: Mutu bahan perumahan warga diawasi oleh pengawas dinas PU.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 69) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemeriksaan atas kehematan dan efisiensi dalam penggunaan keuangan • negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Dalam melaksanakan audit, BPK diamanatkan oleh UU 15/2004 untuk memanfaatkan...", "options": ["Survei elektabilitas kepuasan publik terhadap menteri terkait", "Laporan evaluasi kinerja organisasi masyarakat setempat", "Opini subjektif pemberitaan media daring tanpa verifikasi", "Hasil riset akademis mahasiswa magang di unit kantor dinas", "Hasil pekerjaan Aparat Pengawasan Intern Pemerintah (APIP)"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemanfaatan Hasil Pengawasan APIP Oleh BPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam melakukan pemeriksaannya, BPK <strong>memperhatikan dan memanfaatkan hasil-hasil pekerjaan Aparat Pengawasan Intern Pemerintah (APIP)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena sinergi antara auditor eksternal dan APIP meningkatkan efisiensi pemeriksaan dan memperluas cakupan deteksi atas area-area berisiko tinggi.</p><p>Jawaban tepat: <strong>E. Hasil pekerjaan Aparat Pengawasan Intern Pemerintah (APIP)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Survei elektabilitas politis di luar ruang lingkup pemeriksaan audit.</li><li><strong>B</strong>: Laporan ormas adalah aspirasi masyarakat, bukan standar audit resmi.</li><li><strong>C</strong>: Berita media daring memerlukan konfirmasi bukti pendukung objektif.</li><li><strong>D</strong>: Riset mahasiswa magang bukan dasar laporan pemeriksaan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 69) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>memperhatikan dan memanfaatkan hasil-hasil pekerjaan Aparat Pengawasan • Intern Pemerintah (APIP).</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Guna mendukung tugas BPK, kewajiban yang harus dipenuhi oleh pimpinan APIP adalah...", "options": ["Menyampaikan laporan hasil pemeriksaannya kepada BPK", "Menyerahkan dokumen asli bukti pembayaran kas belanja", "Menghentikan seluruh kegiatan audit intern selama masa reses", "Meminta persetujuan BPK untuk setiap surat tugas inspektorat", "Menyita brankas uang tunai kasir satuan kerja kementerian"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban APIP Menyampaikan LHP ke BPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Untuk keperluan pemanfaatan hasil pengawasan, <strong>APIP wajib menyampaikan laporan hasil pemeriksaannya kepada Badan</strong> (BPK) sepanjang tidak bertentangan dengan UU.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penyerahan LHP APIP memberikan gambaran awal mengenai kondisi pengendalian intern instansi sebelum BPK turun melakukan pemeriksaan substantif.</p><p>Jawaban tepat: <strong>A. Menyampaikan laporan hasil pemeriksaannya kepada BPK</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Dokumen asli bukti kas disimpan oleh bendahara pengeluaran satker.</li><li><strong>C</strong>: Audit intern tetap berjalan kontinu tanpa tergantung masa reses DPR.</li><li><strong>D</strong>: Surat tugas APIP diterbitkan mandiri oleh pimpinan APIP instansi.</li><li><strong>E</strong>: Penyitaan brankas hanya dapat dilakukan oleh aparat penegak hukum berwenang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 69) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>APIP wajib menyampaikan • laporan hasil pemeriksaannya kepada Badan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Saat pemeriksaan berlangsung, pemeriksa BPK diberi kewenangan khusus untuk melakukan...", "options": ["Penahanan fisik para pejabat pengelola keuangan di dalam kantor", "Penyegelan uang, barang, atau dokumen keuangan yang diperiksa", "Pelelangan terbuka terhadap aset mobil dinas pimpinan kementerian", "Pemutusan aliran listrik dan fasilitas komunikasi satuan kerja", "Pembekuan langsung rekening gaji seluruh pegawai negeri kantor"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewenangan Penyegelan Oleh Auditor BPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>BPK diberi kewenangan <strong>melakukan penyegelan untuk mengamankan uang, barang, dan/atau dokumen pengelolaan keuangan negara</strong> pada saat pemeriksaan berlangsung.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena tindakan penyegelan merupakan langkah pengamanan bukti agar bukti fisik uang, barang, atau berkas pertanggungjawaban tidak diubah atau dipindahtangankan.</p><p>Jawaban tepat: <strong>B. Penyegelan uang, barang, atau dokumen keuangan yang diperiksa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penahanan fisik orang adalah kewenangan aparat hukum peradilan pidana.</li><li><strong>C</strong>: Pelelangan aset BMN dilakukan oleh instansi pengelola lelang resmi.</li><li><strong>D</strong>: Pemutusan utilitas kantor mengganggu pelayanan umum masyarakat.</li><li><strong>E</strong>: Gaji pegawai dilindungi oleh undang-undang aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 69) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>melakukan penyegelan untuk mengamankan • uang, barang, dan/atau dokumen pengelolaan keuangan negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi audit, Laporan Hasil Pemeriksaan (LHP) Keuangan akan menghasilkan...", "options": ["Surat perintah pemblokiran anggaran dinas kementerian", "Ketetapan vonis ganti rugi perdata kepada pihak ketiga", "Opini tentang kewajaran penyajian laporan keuangan", "Rekomendasi penutupan sementara gedung kantor satuan kerja", "Daftar pemecatan pegawai pelaksana urusan pembukuan dinas"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Output LHP Pemeriksaan Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>LHP Pemeriksaan Keuangan akan menghasilkan opini</strong> atas kewajaran penyajian informasi laporan keuangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena opini audit merupakan simpulan akhir yang menyatakan apakah laporan keuangan telah disajikan secara wajar dalam semua hal yang material sesuai SAP.</p><p>Jawaban tepat: <strong>C. Opini tentang kewajaran penyajian laporan keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemblokiran pagu belanja dilakukan oleh direktorat jenderal anggaran.</li><li><strong>B</strong>: Vonis perdata ditetapkan melalui persidangan majelis pengadilan.</li><li><strong>D</strong>: Penutupan gedung bukan merupakan output dari audit laporan keuangan.</li><li><strong>E</strong>: Sanksi disiplin pemecatan pegawai ditetapkan oleh PPK instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 69) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>LHP Pemeriksaan Keuangan akan menghasilkan • opini.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berbeda dengan audit keuangan, LHP Pemeriksaan Kinerja BPK akan menghasilkan...", "options": ["Peringkat akreditasi lembaga pendidikan aparatur sipil", "Pernyataan opini wajar tanpa pengecualian secara mutlak", "Ketetapan sita eksekusi tanah dan bangunan milik satker", "Temuan, kesimpulan, dan rekomendasi perbaikan kinerja", "Izin operasional penarikan dana pinjaman luar negeri"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Output LHP Pemeriksaan Kinerja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>LHP Pemeriksaan Kinerja akan menghasilkan temuan, kesimpulan, dan rekomendasi</strong> perbaikan bagi entitas pemerintah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena audit kinerja tidak memberikan opini akuntansi, melainkan menyajikan temuan kelemahan efisiensi/efektivitas beserta rekomendasi perbaikan manajemen.</p><p>Jawaban tepat: <strong>D. Temuan, kesimpulan, dan rekomendasi perbaikan kinerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Akreditasi lembaga pendidikan dinas dikeluarkan oleh BAN-PT/LAN.</li><li><strong>B</strong>: Opini WTP adalah produk khusus dari pemeriksaan keuangan.</li><li><strong>C</strong>: Sita eksekusi merupakan tindakan penetapan jurusita pengadilan.</li><li><strong>E</strong>: Izin pinjaman luar negeri dikoordinasikan oleh Bappenas dan Kemenkeu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 69) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>LHP Pemeriksaan Kinerja akan menghasilkan temuan, kesimpulan, dan • rekomendasi.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Setelah menerima LHP dari BPK, langkah wajib yang harus diambil oleh Menteri/Pimpinan Lembaga adalah...", "options": ["Membatalkan seluruh kegiatan belanja kementerian pada tahun berjalan", "Mengajukan banding sengketa ke pengadilan tata usaha negara", "Menyimpan berkas audit di ruang arsip tertutup tanpa tindak lanjut", "Meminta persetujuan BPK untuk menghapus seluruh catatan temuan", "Melakukan koreksi dan penyesuaian yang diperlukan pada laporan"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tindak Lanjut Temuan LHP Oleh Menteri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>LHP BPK disampaikan kepada Menteri untuk <strong>digunakan untuk melakukan koreksi dan penyesuaian yang diperlukan</strong> sebelum laporan keuangan diserahkan ke DPR.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena hasil audit BPK menjadi dasar penyempurnaan laporan keuangan agar laporan keuangan yang diaudit (audited) benar-benar mencerminkan kondisi riil.</p><p>Jawaban tepat: <strong>E. Melakukan koreksi dan penyesuaian yang diperlukan pada laporan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Koreksi audit tidak membatalkan kegiatan operasional yang sah.</li><li><strong>B</strong>: LHP BPK ditindaklanjuti secara administratif, bukan digugat ke PTUN.</li><li><strong>C</strong>: Membiarkan temuan tanpa tindak lanjut melanggar kewajiban hukum pejabat.</li><li><strong>D</strong>: Temuan audit tidak dapat dihapus atas permintaan pimpinan entitas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 69) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>digunakan untuk melakukan koreksi dan penyesuaian yang diperlukan,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Laporan keuangan pemerintah yang disampaikan Presiden kepada DPR RI berstatus sebagai...", "options": ["Laporan keuangan yang telah diperiksa (audited financial statement)", "Rancangan estimasi kas sementara yang belum diverifikasi kantor", "Draft pembukuan rahasia yang disimpan oleh direktorat jenderal", "Kompilasi nota pencairan dana operasional triwulan pertama", "Laporan perkiraan kas cadangan yang disusun konsultan swasta"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyajian Audited Financial Statement</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Koreksi audit dimuat <strong>sehingga laporan keuangan yang telah diperiksa (audited financial statement) memuat koreksi dimaksud sebelum disampaikan kepada DPR</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena sesuai undang-undang, pertanggungjawaban pelaksanaan APBN yang diserahkan kepada parlemen wajib berupa laporan yang telah diaudit oleh BPK.</p><p>Jawaban tepat: <strong>A. Laporan keuangan yang telah diperiksa (audited financial statement)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Laporan yang diserahkan ke DPR harus berstatus final audited, bukan estimasi.</li><li><strong>C</strong>: Keuangan negara menganut asas transparansi dan keterbukaan publik.</li><li><strong>D</strong>: Nota pencairan triwulan adalah berkas transaksi pembukuan satker.</li><li><strong>E</strong>: Laporan keuangan negara disusun resmi oleh kementerian keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 70) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sehingga laporan keuangan yang telah diperiksa (audited financial statement) • memuat koreksi dimaksud sebelum disampaikan kepada DPR.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Seluruh hasil audit kementerian/lembaga dalam semester tertentu dihimpun oleh BPK dalam...", "options": ["Buku Kas Pembantu Pengeluaran Kasir Satuan Kerja", "Buku Hasil Pemeriksaan Semester Badan (HAPSEM)", "Buku Register Penomoran Surat Masuk Kantor Dinas", "Buku Panduan Penatausahaan Pajak Bumi dan Bangunan", "Buku Catatan Kepegawaian Aparatur Sipil Wilayah"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyusunan Himpunan HAPSEM BPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setelah pemutakhiran data, laporan semester dihimpun dalam <strong>Buku Hasil Pemeriksaan Semester Badan (HAPSEM)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena HAPSEM (kini lazim disebut IHPS - Ikhtisar Hasil Pemeriksaan Semester) merupakan dokumen komprehensif BPK yang memuat ringkasan seluruh temuan audit.</p><p>Jawaban tepat: <strong>B. Buku Hasil Pemeriksaan Semester Badan (HAPSEM)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Buku kas pembantu adalah buku catatan harian milik bendahara satker.</li><li><strong>C</strong>: Buku register surat masuk adalah tata naskah tata usaha kantor.</li><li><strong>D</strong>: Buku panduan PBB adalah petunjuk operasional penetapan pajak pemda.</li><li><strong>E</strong>: Catatan kepegawaian adalah arsip rekam jejak riwayat ASN di BKN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 70) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Buku Hasil • Pemeriksaan Semester Badan (HAPSEM).</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Penyerahan Buku Hasil Pemeriksaan Semester (HAPSEM) oleh pimpinan BPK dilakukan dalam...", "options": ["Rapat dengar pendapat terbatas bersama sekretariat jenderal", "Pertemuan tertutup antarketua umum partai koalisi dewan", "Rapat Paripurna DPR RI sebagai forum tertinggi dewan", "Forum diskusi kelompok kerja asosiasi industri manufaktur", "Musyawarah internal panitia anggaran di luar gedung dewan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyerahan Hasil Pemeriksaan Dalam Rapat Paripurna</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hasil Pemeriksaan Semester diserahkan kepada dewan dan <strong>penyampaiannya kepada DPR RI dilakukan dalam Rapat Paripurna DPR RI</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Rapat Paripurna merupakan forum permusyawaratan tertinggi DPR yang bersifat resmi dan terbuka untuk menyampaikan laporan audit kepada wakil rakyat.</p><p>Jawaban tepat: <strong>C. Rapat Paripurna DPR RI sebagai forum tertinggi dewan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rapat dengar pendapat terbatas membahas topik teknis komisi.</li><li><strong>B</strong>: Penyerahan dokumen konstitusional negara dilarang dalam forum partai.</li><li><strong>D</strong>: Diskusi kelompok manufaktur adalah forum asosiasi dunia usaha.</li><li><strong>E</strong>: Penyampaian resmi wajib di ruang sidang paripurna gedung dewan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 70) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penyampaiannya kepada DPR RI dilakukan dalam Rapat Paripurna DPR RI.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Landasan filosofis pengawasan masyarakat berakar pada Pasal 1 ayat 2 UUD 1945 bahwa...", "options": ["Warga negara wajib mematuhi seluruh ketetapan dinas tanpa kritik", "Kekuasaan tertinggi negara berada di tangan majelis pimpinan partai", "Pemerintah berkuasa mutlak tanpa kewajiban pengawasan masyarakat", "Kedaulatan berada di tangan rakyat berdasar Undang-Undang Dasar", "Pengelolaan keuangan negara dirahasiakan sepenuhnya dari publik"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Landasan Konstitusional Pengawasan Oleh Masyarakat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 1 ayat 2 UUD 1945 menegaskan bahwa <strong>kedaulatan berada di tangan rakyat dan dilaksanakan menurut Undang-Undang Dasar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena rakyat adalah pemilik sejati kedaulatan negara, sehingga masyarakat berhak mengawasi jalannya pemerintahan dan penggunaan dana publik.</p><p>Jawaban tepat: <strong>D. Kedaulatan berada di tangan rakyat berdasar Undang-Undang Dasar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masyarakat berhak memberikan saran, kritik, dan pendapat konstruktif.</li><li><strong>B</strong>: Kedaulatan berada di tangan rakyat, bukan di tangan elit partai.</li><li><strong>C</strong>: Pemerintahan demokratis wajib tunduk pada kontrol pengawasan publik.</li><li><strong>E</strong>: Keuangan negara berasaskan transparansi, bukan kerahasiaan tertutup.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 70) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kedaulatan berada di tangan • rakyat dan dilaksanakan menurut Undang-Undang Dasar.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Sesuai Pasal 6 Tap MPR RI No. XV/1998, penyelenggaraan otonomi daerah diperkuat dengan...", "options": ["Pembatasan hak penyampaian aspirasi bagi warga di perdesaan", "Pemberian hak veto mutlak kepada gubernur kepala daerah", "Penarikan seluruh wewenang keuangan ke tangan pemerintah pusat", "Peniadaan pengawasan eksternal terhadap pemanfaatan dana kas", "Pengawasan oleh dewan perwakilan rakyat daerah dan masyarakat"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengawasan Otonomi Daerah Oleh Masyarakat (Tap MPR XV/1998)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pasal 6 Tap MPR XV/1998 menetapkan bahwa <strong>Penyelenggaraan otonomi daerah diperkuat dengan pengawasan oleh DPRD dan masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena desentralisasi kekuasaan menuntut partisipasi pengawasan aktif dari warga lokal dan DPRD agar dana otonomi tidak disalahgunakan.</p><p>Jawaban tepat: <strong>E. Pengawasan oleh dewan perwakilan rakyat daerah dan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aspirasi masyarakat perdesaan dilindungi oleh perundang-undangan.</li><li><strong>B</strong>: Gubernur tidak memiliki hak veto mutlak atas pengawasan daerah.</li><li><strong>C</strong>: Otonomi daerah memberikan desentralisasi wewenang ke pemda.</li><li><strong>D</strong>: Pengawasan eksternal tetap wajib dilakukan secara ketat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 70) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Penyelenggaraan otonomi daerah diperkuat dengan pengawasan oleh • DPRD dan masyarakat.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Peran serta masyarakat dalam memberikan informasi penyelenggaraan negara diatur dalam...", "options": ["UU No. 28/1999 tentang Penyelenggara Bersih dan Bebas KKN", "UU Perpajakan Daerah dan Retribusi Jasa Umum Pelayanan", "Peraturan Pemerintah tentang Penertiban Pedagang Kaki Lima", "Keputusan Direktur Perbankan tentang Batas Penarikan Kasir", "Surat Ketetapan Pajak Kurang Bayar Kantor Pelayanan Pajak"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Serta Masyarakat Bebas KKN (UU 28/1999)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>UU No. 28/1999 tentang Penyelenggaraan Negara yang Bersih dan Bebas KKN</strong> Pasal 8 dan 9 menjamin peran serta masyarakat mencari informasi dan memberi saran.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena undang-undang ini menjadi tonggak reformasi yang melegitimasi hak warga negara dalam mengawasi integritas para pejabat publik.</p><p>Jawaban tepat: <strong>A. UU No. 28/1999 tentang Penyelenggara Bersih dan Bebas KKN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: UU PDRD mengatur wewenang pemda memungut pajak dan retribusi.</li><li><strong>C</strong>: Penertiban PKL adalah urusan ketertiban umum peraturan daerah.</li><li><strong>D</strong>: Batas kasir perbankan diatur oleh ketentuan operasional bank.</li><li><strong>E</strong>: SKPKB adalah penetapan kekurangan utang pajak wajib pajak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 70) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>UU No. 28/1999 tentang Penyelenggaraan Negara yang Bersih dan Bebas KKN</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Dalam tata kelola dana desa berdasarkan UU Nomor 6 Tahun 2014, ditegaskan bahwa...", "options": ["Kepala desa berwenang mengelola dana desa tanpa bukti kas belanja", "Masyarakat Desa berhak memantau pelaksanaan Pembangunan Desa", "Pemerintah kabupaten dilarang mengaudit penggunaan dana perdesaan", "Warga desa dilarang menghadiri musyawarah rencana pembangunan", "Pembangunan fisik desa diserahkan kepada pemborong swasta murni"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemantauan Pembangunan Desa (UU 6/2014)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>UU No. 6/2014 tentang Desa Pasal 82 ayat 2 menyatakan <strong>Masyarakat Desa berhak melakukan pemantauan terhadap pelaksanaan Pembangunan Desa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena transparansi pengelolaan keuangan desa diwujudkan melalui partisipasi langsung warga desa dalam mengawasi proyek dan anggaran desa.</p><p>Jawaban tepat: <strong>B. Masyarakat Desa berhak memantau pelaksanaan Pembangunan Desa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengelolaan dana desa wajib disertai bukti transaksi yang akuntabel.</li><li><strong>C</strong>: Inspektorat kabupaten berwenang penuh memeriksa keuangan desa.</li><li><strong>D</strong>: Musrenbangdes adalah forum terbuka bagi seluruh perwakilan warga.</li><li><strong>E</strong>: Pembangunan desa diutamakan secara swakelola padat karya tunai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 70) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Masyarakat Desa berhak melakukan pemantauan terhadap pelaksanaan • Pembangunan Desa.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Tata cara masyarakat dalam mencari informasi dan menyampaikan saran pendapat diatur dalam...", "options": ["Instruksi Bersama Direktur Perbendaharaan dan Cukai Impor", "PP tentang Pemberian Tunjangan Kinerja Pegawai Daerah", "PP No. 68/1999 tentang Tata Cara Peran Serta Masyarakat", "Keputusan Panitia Lelang tentang Standar Dokumen Penawaran", "Surat Edaran Bersama Kepala Satuan Polisi Pamong Praja"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tata Cara Peran Serta Masyarakat (PP 68/1999)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>PP No. 68/1999 tentang Tata Cara Pelaksanaan Peran Serta Masyarakat dalam Penyelenggaraan Negara</strong> mengatur prosedur pencarian informasi dan penyampaian saran yang bertanggung jawab.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena PP ini menjadi pedoman operasional bagi masyarakat agar partisipasi pengawasan dijalankan sesuai hukum dan etika norma umum.</p><p>Jawaban tepat: <strong>C. PP No. 68/1999 tentang Tata Cara Peran Serta Masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Instruksi perbendaharaan mengatur penatausahaan kas negara.</li><li><strong>B</strong>: PP tunjangan kinerja mengatur hak keuangan pegawai dinas.</li><li><strong>D</strong>: Standar dokumen penawaran lelang mengatur administrasi tender.</li><li><strong>E</strong>: SE Satpol PP mengatur penegakan peraturan ketertiban umum pemda.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 70) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PP No. 68/1999 tentang Tata Cara Pelaksanaan Peran Serta Masyarakat • dalam Penyelenggaraan Negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Salah satu bentuk konkret peran serta masyarakat dalam penyelenggaraan negara adalah...", "options": ["Hak membekukan rekening kasir satuan kerja kementerian", "Hak menyita arsip rahasia negara secara mandiri tanpa izin", "Hak menginterogasi pejabat administrasi di luar pengadilan", "Hak mencari, memperoleh, dan memberikan informasi publik", "Hak membatalkan penerapan seluruh peraturan perundang-undangan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Memperoleh Informasi Penyelenggara Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Peran serta masyarakat mencakup <strong>Hak mencari, memperoleh dan memberikan informasi mengenai penyelenggara negara</strong> secara bertanggung jawab.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena keterbukaan informasi publik memungkinkan masyarakat memantau kinerja pemerintah dan mendeteksi penyimpangan sejak dini.</p><p>Jawaban tepat: <strong>D. Hak mencari, memperoleh, dan memberikan informasi publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembekuan rekening kasir adalah wewenang bendahara umum negara.</li><li><strong>B</strong>: Penyitaan dokumen hanya boleh dilakukan oleh penyidik penegak hukum.</li><li><strong>C</strong>: Interogasi di luar prosedur hukum melanggar hak asasi perorangan.</li><li><strong>E</strong>: Warga negara wajib mematuhi peraturan hukum yang berlaku sah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 71) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Hak mencari, memperoleh dan memberikan informasi mengenai • penyelenggara negara,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Dalam berinteraksi dengan instansi pemerintah, hak mendasar yang dimiliki masyarakat adalah...", "options": ["Hak menentukan lokasi penempatan pegawai pada satuan kerja", "Hak menuntut prioritas khusus berdasarkan status sosial ekonomi", "Hak meminta pembebasan dari seluruh pungutan bea pajak resmi", "Hak menggunakan fasilitas dinas kantor untuk urusan pribadi", "Hak memperoleh pelayanan yang sama dan adil tanpa diskriminasi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Pelayanan Yang Sama dan Adil</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Masyarakat memiliki <strong>Hak untuk memperoleh pelayanan yang sama dan adil dari penyelenggara negara</strong> tanpa diskriminasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena prinsip kesetaraan di depan hukum (*equality before the law*) menjamin setiap warga berhak atas perlakuan adil dalam pelayanan birokrasi.</p><p>Jawaban tepat: <strong>E. Hak memperoleh pelayanan yang sama dan adil tanpa diskriminasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penempatan pegawai adalah wewenang pembina kepegawaian dinas.</li><li><strong>B</strong>: Birokrasi dilarang memberi keistimewaan atas dasar status sosial.</li><li><strong>C</strong>: Pajak merupakan kewajiban konstitusional setiap warga negara.</li><li><strong>D</strong>: Fasilitas dinas kantor khusus diperuntukkan bagi tugas operasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 71) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Hak untuk memperoleh pelayanan yang sama dan adil dari penyelenggara • negara,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Bentuk partisipasi publik dalam merespons kebijakan aparatur pemerintah diwujudkan melalui...", "options": ["Hak menyampaikan saran dan pendapat secara bertanggung jawab", "Hak melakukan perusakan sarana kantor pelayanan umum dinas", "Hak menyebarkan kabar bohong mengenai pejabat instansi publik", "Hak menghentikan operasional pelayanan kesehatan masyarakat", "Hak memboikot pembayaran tagihan listrik di seluruh daerah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Menyampaikan Saran dan Pendapat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bentuk peran serta adalah <strong>Hak menyampaikan saran dan pendapat secara bertanggung jawab terhadap kebijakan penyelenggara negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penyampaian saran dan kritik secara bertanggung jawab mendorong perbaikan kualitas kebijakan publik tanpa melanggar ketertiban hukum.</p><p>Jawaban tepat: <strong>A. Hak menyampaikan saran dan pendapat secara bertanggung jawab</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Perusakan sarana dinas merupakan tindak pidana perusakan aset negara.</li><li><strong>C</strong>: Penyebaran kabar bohong melanggar undang-undang informasi transaksi.</li><li><strong>D</strong>: Pelayanan kesehatan adalah layanan esensial yang tidak boleh terhenti.</li><li><strong>E</strong>: Boikot utilitas melanggar ketertiban umum dan aturan hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 71) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Hak menyampaikan saran dan pendapat secara bertanggung jawab terhadap • kebijakan penyelenggara negara,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Agar masyarakat berani melaporkan indikasi penyimpangan, negara menjamin pemberian...", "options": ["Imbalan uang tunai langsung dari anggaran belanja pegawai satker", "Hak perlindungan hukum dalam melaksanakan peran partisipasinya", "Kenaikan pangkat tituler kehormatan pada dinas kepangkatan ASN", "Kekebalan mutlak dari seluruh penegakan hukum perkara pidana", "Hak kepemilikan tanah milik kementerian negara secara pribadi"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Perlindungan Hukum Bagi Pelapor</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Warga negara memiliki <strong>Hak memperoleh perlindungan hukum dalam melaksanakan hak-haknya tersebut di atas</strong> (*whistleblower protection*).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena jaminan perlindungan hukum (misalnya melalui LPSK) melindungi saksi dan pelapor dari ancaman fisik, teror, maupun tuntutan balasan.</p><p>Jawaban tepat: <strong>B. Hak perlindungan hukum dalam melaksanakan peran partisipasinya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja pegawai hanya dibayarkan untuk hak gaji aparatur dinas.</li><li><strong>C</strong>: Pangkat kehormatan diberikan berdasar regulasi kepangkatan militer.</li><li><strong>D</strong>: Perlindungan hukum bukan berarti kekebalan dari tindak kejahatan pidana.</li><li><strong>E</strong>: BMN tanah milik kementerian dilarang dialihkan menjadi hak perorangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 71) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Hak memperoleh perlindungan hukum dalam melaksanakan hak-haknya • tersebut di atas.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Struktur Aparat Pengawas Intern Pemerintah (APIP) saat ini pada pokoknya terdiri atas...", "options": ["Aparat kejaksaan agung, kepolisian, dan pengadilan negeri", "Dewan Perwakilan Rakyat, Dewan Perwakilan Daerah, dan BPK", "BPKP, Itjen Kementerian/LPND, dan SPI pada setiap BUMN", "Kantor Akuntan Publik swasta dan konsultan keuangan ritel", "Badan Narkotika Nasional dan Badan Nasional Penanggulangan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Struktur Kelembagaan APIP Saat Ini</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Struktur pengawasan Aparat Pengawas Instansi Pemerintah (APIP) pada saat ini terdiri atas Badan Pengawasan Keuangan dan Pembangunan (BPKP), Inspektur Jenderal Kementerian/Unit Pengawasan LPND, Satuan Pengawas Intern pada setiap BUMN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena ketiga institusi pengawas internal inilah yang menjalankan fungsi audit manajerial di lingkungan eksekutif dan badan usaha milik negara.</p><p>Jawaban tepat: <strong>C. BPKP, Itjen Kementerian/LPND, dan SPI pada setiap BUMN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kejaksaan, kepolisian, dan pengadilan adalah aparat peradilan pidana.</li><li><strong>B</strong>: DPR, DPD, dan BPK adalah institusi pengawasan eksternal negara.</li><li><strong>D</strong>: KAP dan konsultan adalah penyedia jasa audit komersial swasta.</li><li><strong>E</strong>: BNN dan BNPB adalah lembaga pemerintah urusan non-pengawasan anggaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 71) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Struktur pengawasan Aparat Pengawas Instansi Pemerintah (APIP) pada saat ini • terdiri atas Badan Pengawasan Keuangan dan Pembangunan (BPKP), Inspektur</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Tujuan pengawasan APIP adalah mendukung kelancaran kegiatan pemerintahan dengan...", "options": ["Penetapan besaran tarif royalti hasil tambang sumber daya", "Pemberian opini wajar atas laporan pertanggungjawaban APBN", "Penuntutan peradilan komptabel pemotongan gaji para pegawai", "Pemeriksaan operasional yang menyampaikan rekomendasi perbaikan", "Penyusunan nota kesepakatan pinjaman dana perbankan daerah"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pengawasan dan Ruang Lingkup APIP</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan pengawasan APIP adalah mendukung kelancaran pelaksanaan tugas dengan ruang lingkup <strong>pemeriksaan operasional atau pemeriksaan komprehensif yang menyampaikan rekomendasi perbaikan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena APIP bertindak sebagai konsultan internal (*assurance and consulting*) bagi manajemen guna memperbaiki tata kelola dan meminimalkan risiko.</p><p>Jawaban tepat: <strong>D. Pemeriksaan operasional yang menyampaikan rekomendasi perbaikan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tarif royalti tambang ditetapkan dalam peraturan menteri ESDM.</li><li><strong>B</strong>: Pemberian opini atas laporan keuangan negara adalah wewenang BPK.</li><li><strong>C</strong>: Peradilan komptabel merupakan wewenang yudikatif lembaga BPK.</li><li><strong>E</strong>: Pinjaman pemda diatur oleh kepala daerah bersama DPRD setempat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 71) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tujuan pengawasan APIP • menyampaikan rekomendasi perbaikan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Secara historis, Badan Pengawasan Keuangan dan Pembangunan (BPKP) dibentuk berdasarkan...", "options": ["Surat Keputusan Bersama Menteri Keuangan dan Dalam Negeri", "Undang-Undang Perbendaharaan Hindia Belanda (ICW) Tahun 1925", "Peraturan Pemerintah Pengganti Undang-Undang Tahun 1960", "Ketetapan Dewan Moneter dan Perbankan Nasional Tahun 1990", "Keputusan Presiden RI No. 31 Tahun 1983 tanggal 3 Juni 1983"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum Pembentukan BPKP 1983</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>BPKP dibentuk berdasarkan Keputusan Presiden RI No.31 tahun 1983 tanggal 3 Juni 1983</strong>, yang kemudian diperbarui dengan Perpres 192/2014 jo Perpres 20/2023.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Keppres 31/1983 mentransformasikan aparat pengawasan internal menjadi BPKP yang bertugas mengawasi keuangan dan pembangunan nasional.</p><p>Jawaban tepat: <strong>E. Keputusan Presiden RI No. 31 Tahun 1983 tanggal 3 Juni 1983</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: SKB menteri bukan landasan pembentukan lembaga pemerintah non-kementerian.</li><li><strong>B</strong>: ICW 1925 adalah regulasi perbendaharaan peninggalan kolonial Belanda.</li><li><strong>C</strong>: Perppu 1960 bukan dasar hukum pembentukan kelembagaan BPKP.</li><li><strong>D</strong>: Ketetapan dewan moneter 1990 mengatur likuiditas sistem perbankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 71) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>BPKP dibentuk berdasarkan Keputusan Presiden RI No.31 tahun 1983 • tanggal 3 Juni 1983.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Perpres Nomor 192 Tahun 2014 jo Perpres Nomor 20 Tahun 2023, BPKP bertugas menyelenggarakan...", "options": ["Pengawasan keuangan negara/daerah dan pembangunan nasional", "Penetapan vonis perkara tindak pidana korporasi swasta", "Pencetakan uang kertas dan uang logam valuta rupiah", "Pelelangan aset sitaan piutang pajak kantor wilayah", "Penyelidikan intelijen pertahanan perbatasan kepulauan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum dan Tugas Pokok BPKP</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>BPKP mempunyai tugas menyelenggarakan urusan pemerintahan di bidang pengawasan keuangan negara/daerah dan pembangunan nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena BPKP berkedudukan sebagai auditor internal presiden yang mengawal akuntabilitas keuangan dan efektivitas proyek strategis pembangunan di tingkat pusat maupun daerah.</p><p>Jawaban tepat: <strong>A. Pengawasan keuangan negara/daerah dan pembangunan nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Vonis perkara pidana diputuskan oleh majelis hakim peradilan.</li><li><strong>C</strong>: Pencetakan uang rupiah adalah wewenang Bank Indonesia dan Perum Peruri.</li><li><strong>D</strong>: Pelelangan aset sitaan dilakukan oleh pejabat lelang KPKNL.</li><li><strong>E</strong>: Intelijen pertahanan dijalankan oleh Badan Intelijen Negara dan TNI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 72) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sesuai dengan Pasal 2 dan 3 Peraturan Presiden Nomor 192 Tahun 2014 • Nomor 20 Tahun 2023, BPKP mempunyai tugas menyelenggarakan urusan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Fokus mandat pengawasan yang dijalankan oleh BPKP mencakup dua dimensi pokok yaitu...", "options": ["Keuangan perbankan swasta dan bursa berjangka komoditas", "Keuangan negara/daerah dan agenda pembangunan nasional", "Anggaran organisasi nirlaba dan yayasan keagamaan warga", "Kepatuhan pajak perorangan dan tarif retribusi pasar", "Kekayaan pejabat publik dan penerbitan izin pertambangan"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Pengawasan Keuangan dan Pembangunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Mandat pengawasan BPKP berfokus pada <strong>pengawasan keuangan negara/daerah dan pembangunan nasional</strong> secara terpadu.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena BPKP tidak hanya memeriksa akuntabilitas kas, melainkan juga mengawal keberhasilan pencapaian sasaran program prioritas pembangunan nasional.</p><p>Jawaban tepat: <strong>B. Keuangan negara/daerah dan agenda pembangunan nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perbankan dan bursa berjangka diawasi oleh OJK dan Bappebti.</li><li><strong>C</strong>: Yayasan nirlaba tunduk pada pengawasan kementerian hukum dan dinas sosial.</li><li><strong>D</strong>: Pajak dan retribusi dikelola oleh DJP dan dinas pendapatan pemda.</li><li><strong>E</strong>: Kekayaan pejabat dilaporkan melalui LHKPN KPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 72) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pemerintahan di bidang pengawasan keuangan negara/daerah dan • pembangunan nasional.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Keberadaan Inspektorat Jenderal Kementerian/LPND saat ini diatur dalam kerangka regulasi...", "options": ["Keputusan Bersama Menteri Koordinator Bidang Kesejahteraan", "Undang-Undang Tindak Pidana Administrasi Pegawai Negeri", "Peraturan Presiden tentang Pembentukan dan Organisasi Kementerian", "Petunjuk Pelaksanaan Pengawasan Koperasi Simpan Pinjam", "Surat Ketetapan Dewan Pertimbangan Otonomi Daerah Khusus"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi Pembentukan Itjen Kementerian</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Inspektorat Jenderal (Itjen) Kementerian/Unit Pengawasan LPND dibentuk</strong> dan diatur dalam Perpres tentang Pembentukan dan Organisasi Kementerian Negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena struktur kelembagaan Itjen kementerian secara yuridis diatur dalam paket regulasi kelembagaan kementerian negara (Perpres 47/2009 dan perubahannya).</p><p>Jawaban tepat: <strong>C. Peraturan Presiden tentang Pembentukan dan Organisasi Kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: SKB menko mengatur koordinasi program lintas sektor kementerian.</li><li><strong>B</strong>: Pelanggaran pegawai diatur dalam undang-undang ASN dan PP Disiplin.</li><li><strong>D</strong>: Pengawasan koperasi berada di bawah binaan kementerian koperasi.</li><li><strong>E</strong>: DPOD memberikan saran kebijakan otonomi daerah kepada presiden.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 72) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Inspektorat Jenderal (Itjen) Kementerian/Unit Pengawasan LPND dibentuk</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Sesuai PMK 118/2021 jo PMK 135/2023, tugas pokok Inspektorat Jenderal Kemenkeu adalah...", "options": ["Mengadili sengketa perdata antara pengusaha dan kantor bea cukai", "Menetapkan besaran tarif cukai hasil tembakau tahunan", "Menerbitkan surat utang negara di pasar modal komersial", "Menyelenggarakan pengawasan intern pelaksanaan tugas Kemenkeu", "Menyusun rancangan kebijakan moneter dan inflasi nasional"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tugas Pokok Itjen Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan PMK 135/2023, <strong>Itjen mempunyai tugas menyelenggarakan pengawasan intern atas pelaksanaan tugas di lingkungan Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Itjen Kemenkeu bertindak sebagai aparat pengawas internal untuk menjamin kepatuhan, keandalan pelaporan keuangan, dan integritas pegawai Kemenkeu.</p><p>Jawaban tepat: <strong>D. Menyelenggarakan pengawasan intern pelaksanaan tugas Kemenkeu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sengketa pajak/bea cukai diselesaikan di Pengadilan Pajak.</li><li><strong>B</strong>: Tarif cukai tembakau dirumuskan oleh Badan Kebijakan Fiskal dan DJBC.</li><li><strong>C</strong>: Penerbitan SUN dilaksanakan oleh Direktorat Jenderal PPR.</li><li><strong>E</strong>: Kebijakan moneter merupakan wewenang independen Bank Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 72) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kementerian Keuangan terakhir diubah dengan Peraturan Menteri Keuangan • Nomor 135 Tahun 2023 menyebutkan bahwa Itjen mempunya tugas</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Selain pengawasan intern, Itjen Kemenkeu diberi mandat khusus menyelenggarakan koordinasi pengawasan atas...", "options": ["Penyaluran dana investasi swasta asing di kawasan industri", "Pengelolaan dana bantuan sosial partai politik peserta pemilu", "Kegiatan ekspor komoditas mineral mentah ke pasar global", "Pemberian fasilitas kredit pemilikan rumah bersubsidi bank", "Pelaksanaan anggaran Bagian Anggaran Bendahara Umum Negara"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Koordinasi Pengawasan BA BUN Oleh Itjen Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tugas Itjen Kemenkeu mencakup <strong>pelaksanaan koordinasi pengawasan atas pelaksanaan anggaran Bagian Anggaran Bendahara Umum Negara (BA BUN) pada Kementerian/Lembaga</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena alokasi BA BUN dikelola oleh berbagai instansi kementerian, sehingga Itjen Kemenkeu mengoordinasikan pengawasannya demi akuntabilitas belanja BUN.</p><p>Jawaban tepat: <strong>E. Pelaksanaan anggaran Bagian Anggaran Bendahara Umum Negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Investasi swasta dikoordinasikan oleh Kementerian Investasi/BKPM.</li><li><strong>B</strong>: Bantuan keuangan partai politik diawasi oleh BPK dan Kemendagri.</li><li><strong>C</strong>: Ekspor mineral diawasi oleh DJBC dan Kementerian ESDM.</li><li><strong>D</strong>: KPR bersubsidi disalurkan oleh perbankan pelaksana dan BP Tapera.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 72) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Nomor 135 Tahun 2023 menyebutkan bahwa Itjen mempunya tugas</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Sebelum berlakunya Undang-Undang Nomor 17 Tahun 2003, pertanggungjawaban APBN diwujudkan dalam bentuk...", "options": ["Perhitungan Anggaran Negara (PAN)", "Laporan Keuangan Pemerintah Pusat Akrual", "Buku Register Transaksi Harian Kasir Satker", "Surat Pernyataan Tanggung Jawab Belanja Mutlak", "Neraca Konsolidasi Aset Badan Layanan Umum"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pertanggungjawaban APBN Pra-UU 17/2003 (PAN)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebelum terbitnya UU 17/2003, <strong>pertanggungjawaban atas pelaksanaan APBN diwujudkan dalam bentuk Perhitungan Anggaran Negara (PAN)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pada masa sebelum reformasi keuangan negara, dokumen resmi pertanggungjawaban APBN kepada DPR berbentuk PAN yang berbasis sistem kas tradisional.</p><p>Jawaban tepat: <strong>A. Perhitungan Anggaran Negara (PAN)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: LKPP akrual baru diperkenalkan pasca berlakunya paket UU Keuangan Negara.</li><li><strong>C</strong>: Buku kasir adalah catatan pembukuan teknis pada level bendahara.</li><li><strong>D</strong>: SPTJM adalah dokumen pertanggungjawaban pengeluaran belanja satker.</li><li><strong>E</strong>: Neraca BLU adalah laporan keuangan unit pengelola layanan umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 72) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pelaksanaan APBN diwujudkan dalam bentuk Perhitungan Anggaran Negara • (PAN).</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Dalam penyusunan dokumen Perhitungan Anggaran Negara (PAN), Menteri Keuangan ditugasi untuk...", "options": ["Menetapkan besaran laba deviden badan usaha swasta murni", "Mempersiapkan PAN berdasarkan laporan keuangan kementerian", "Menunjuk secara langsung pemenang tender proyek strategis", "Menghapus seluruh sisa pagu anggaran kementerian di daerah", "Menghentikan pembayaran hak pensiun aparatur sipil negara"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Menkeu Mempersiapkan Dokumen PAN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam menyusun PAN, Menteri Keuangan ditugasi untuk <strong>mempersiapkan PAN berdasarkan laporan keuangan Kementerian/Lembaga</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena Menteri Keuangan bertindak sebagai koordinator utama pengumpulan data realisasi anggaran kementerian/lembaga untuk disusun menjadi dokumen PAN nasional.</p><p>Jawaban tepat: <strong>B. Mempersiapkan PAN berdasarkan laporan keuangan kementerian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Deviden swasta murni merupakan keputusan rapat pemegang saham korporasi.</li><li><strong>C</strong>: Penetapan tender adalah kewenangan kelompok kerja pemilihan (pokja).</li><li><strong>D</strong>: Sisa anggaran diatur melalui mekanisme sisa lebih pembiayaan (SiLPA).</li><li><strong>E</strong>: Pensiun ASN merupakan hak pegawai yang dijamin oleh undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 72) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mempersiapkan PAN berdasarkan laporan keuangan Kementerian/Lembaga.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Jika seorang analis menelaah arsip pertanggungjawaban APBN tahun 1995, dokumen resmi yang dikaji berupa...", "options": ["Laporan Arus Kas berbasis Standar Akuntansi Pemerintahan", "Laporan Operasional dan Laporan Perubahan Ekuitas akrual", "Dokumen Perhitungan Anggaran Negara (PAN) sesuai sistem lama", "Neraca Keuangan Pemerintah Pusat berbasis akrual penuh", "Catatan atas Laporan Keuangan berbasis standar internasional"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Evolusi Format Pertanggungjawaban APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebelum tahun 2003, pertanggungjawaban APBN diwujudkan dalam bentuk <strong>Perhitungan Anggaran Negara (PAN)</strong>, bukan laporan keuangan modern.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pada tahun 1995 regulasi yang berlaku masih berakar pada ICW, di mana pertanggungjawaban fiskal tahunan disajikan dalam bentuk PAN.</p><p>Jawaban tepat: <strong>C. Dokumen Perhitungan Anggaran Negara (PAN) sesuai sistem lama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: LAK modern disusun setelah terbitnya UU Perbendaharaan Negara 2004.</li><li><strong>B</strong>: Laporan Operasional baru diwajibkan pasca penerapan PP 71/2010 akrual.</li><li><strong>D</strong>: Neraca pemerintah pusat akrual baru diterapkan bertahap sejak 2005.</li><li><strong>E</strong>: CaLK modern baru menjadi komponen wajib pasca UU 17/2003.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 72) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pelaksanaan APBN diwujudkan dalam bentuk Perhitungan Anggaran Negara</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Penyusunan Perhitungan Anggaran Negara pada era sebelum reformasi mengacu pada...", "options": ["Peraturan Pemerintah tentang Penataan Administrasi Pegawai", "Pasal 15 Kitab Undang-Undang Hukum Perdata tentang Perikatan", "Ketetapan Sidang Istimewa Majelis Permusyawaratan Rakyat", "Pasal 69 Indische Comptabiliteitswet (ICW) peninggalan hukum lama", "Surat Edaran Direktur Jenderal Anggaran tentang Kas Bon"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum PAN Dalam Pasal 69 ICW</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyusunan PAN mengacu pada <strong>pasal 69 ICW yang menyatakan bahwa Pemerintah membuat suatu Perhitungan Anggaran dengan menyebutkan tanggal penutupannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena sebelum paket tiga undang-undang keuangan negara disahkan pada 2003–2004, tata kelola pertanggungjawaban keuangan negara berpedoman pada ICW 1925.</p><p>Jawaban tepat: <strong>D. Pasal 69 Indische Comptabiliteitswet (ICW) peninggalan hukum lama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Administrasi kepegawaian mengatur status dan hak kepegawaian aparatur.</li><li><strong>B</strong>: KUHPerdata mengatur hukum perikatan perdata antar-individu swasta.</li><li><strong>C</strong>: Ketetapan MPR memuat garis besar haluan negara politik.</li><li><strong>E</strong>: Surat edaran kas bon adalah aturan teknis pencairan sementara satker.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 73) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Hal ini mengacu pada pasal 69 ICW yang menyatakan bahwa Pemerintah • Perhitungan • Anggaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Tonggak pembaruan akuntabilitas pasca UU 17/2003 ditandai dengan perubahan bentuk...", "options": ["Pengawasan eksternal BPK menjadi audit internal kementerian", "Pemberian pinjaman luar negeri menjadi penerimaan hibah cuma-cuma", "Penarikan pajak langsung menjadi pungutan iuran sukarela warga", "Penyusunan anggaran tahunan menjadi rencana kerja sepuluh tahun", "Pertanggungjawaban APBN dari PAN menjadi Laporan Keuangan"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Transformasi PAN Menjadi Laporan Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setelah terbitnya UU 17/2003, <strong>pertanggungjawaban atas pelaksanaan APBN berubah dari PAN menjadi Laporan Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena reformasi keuangan negara menggantikan format PAN yang sederhana menjadi laporan keuangan terstruktur sesuai kaidah akuntansi modern.</p><p>Jawaban tepat: <strong>E. Pertanggungjawaban APBN dari PAN menjadi Laporan Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengawasan eksternal BPK tetap berdiri mandiri dan independen.</li><li><strong>B</strong>: Pinjaman dan hibah tetap merupakan pos pembiayaan dan pendapatan APBN.</li><li><strong>C</strong>: Pajak tetap bersifat memaksa berdasarkan undang-undang negara.</li><li><strong>D</strong>: APBN tetap disusun berbasis periode tahunan satu tahun anggaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 73) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pertanggungjawaban atas pelaksanaan APBN berubah dari PAN menjadi Laporan • Keuangan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Penyusunan laporan keuangan pemerintah Indonesia disusun dengan standar akuntansi yang mengacu pada...", "options": ["International Public Sector Accounting Standards (IPSAS)", "Pedoman Akuntansi Komersial Perbankan Investasi Wall Street", "Standar Pembukuan Koperasi Simpan Pinjam Asia Pasifik", "Ketentuan Pelaporan Pajak Penghasilan Badan Usaha Asing", "Petunjuk Teknis Pembukuan Unit Usaha Koperasi Karyawan Swasta"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rujukan Standar Internasional (IPSAS)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Laporan Keuangan pemerintah disusun dengan menggunakan Standar Akuntansi Pemerintahan yang mengacu pada <strong>International Public Sector Accounting Standards (IPSAS)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena adopsi IPSAS memastikan laporan keuangan pemerintah Indonesia diakui secara internasional dan memenuhi prinsip komparabilitas global.</p><p>Jawaban tepat: <strong>A. International Public Sector Accounting Standards (IPSAS)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Akuntansi komersial wall street ditujukan untuk korporasi bisnis pasar saham.</li><li><strong>C</strong>: Pembukuan koperasi mikro ditujukan bagi usaha anggota kelompok tani.</li><li><strong>D</strong>: Ketentuan pajak badan asing adalah regulasi penerimaan perpajakan luar.</li><li><strong>E</strong>: Kas paguyuban warga merupakan catatan keuangan komunitas non-formal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 73) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pertanggungjawaban atas pelaksanaan APBN berubah dari PAN menjadi Laporan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Ketentuan Pasal 30 UU Nomor 17 Tahun 2003 mewajibkan Presiden menyampaikan pertanggungjawaban berupa...", "options": ["Pidato kenegaraan tanpa kewajiban melampirkan berkas keuangan", "Rancangan undang-undang pertanggungjawaban berupa Laporan Keuangan", "Surat edaran menteri koordinator tentang capaian proyek fisik", "Ketetapan moratorium pembayaran seluruh utang pokok pemerintah", "Laporan hasil wawancara survei kepuasan masyarakat terhadap ASN"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Presiden Menyampaikan RUU Pertanggungjawaban</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 30 UU 17/2003, <strong>Presiden berkewajiban untuk menyampaikan rancangan undang-undang tentang pertanggungjawaban pelaksanaan APBN berupa Laporan Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pertanggungjawaban anggaran berkedudukan yuridis setara dengan pengesahan APBN, yaitu dituangkan dalam bentuk produk undang-undang.</p><p>Jawaban tepat: <strong>B. Rancangan undang-undang pertanggungjawaban berupa Laporan Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pertanggungjawaban wajib disertai laporan keuangan audit, bukan pidato saja.</li><li><strong>C</strong>: Surat edaran menko tidak memiliki kedudukan undang-undang pertanggungjawaban.</li><li><strong>D</strong>: Moratorium utang adalah kebijakan fiskal khusus, bukan laporan pertanggungjawaban.</li><li><strong>E</strong>: Survei kepuasan adalah studi persepsi publik, bukan laporan akuntansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 73) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sesuai dengan pasal 30 UU nomor 17 Tahun 2003 tentang Keuangan Negara • Presiden berkewajiban untuk menyampaikan rancangan undang-undang • tentang pertanggungjawaban pelaksanaan APBN berupa Laporan Keuangan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Mulai tahun anggaran 2005, batas waktu penyampaian Laporan Keuangan pemerintah kepada DPR adalah...", "options": ["Dua belas bulan bersamaan dengan pembacaan nota keuangan baru", "Sembilan bulan setelah tahun anggaran yang bersangkutan berakhir", "Enam bulan setelah tahun anggaran yang bersangkutan berakhir", "Tiga bulan setelah berakhirnya masa triwulan keempat kementerian", "Tiga puluh hari setelah bendahara umum menutup kas penerimaan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batas Waktu Penyampaian LK Kepada DPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada TA 2004 batas waktu penyampaian LK adalah 9 bulan, namun <strong>mulai tahun anggaran 2005 batas waktunya diperpendek menjadi 6 bulan</strong> setelah tahun anggaran berakhir.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena percepatan batas waktu menjadi 6 bulan mendorong disiplin penyusunan laporan keuangan dan audit BPK agar pertanggungjawaban disahkan tepat waktu.</p><p>Jawaban tepat: <strong>C. Enam bulan setelah tahun anggaran yang bersangkutan berakhir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dua belas bulan terlalu lambat dan melewati siklus anggaran berikutnya.</li><li><strong>B</strong>: Sembilan bulan adalah masa transisi yang hanya berlaku pada TA 2004.</li><li><strong>D</strong>: Tiga bulan adalah batas waktu audit BPK terhadap LKPP.</li><li><strong>E</strong>: Tiga puluh hari adalah batas penutupan kas pembukuan bendahara satker.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 73) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Misalnya dalam tahun anggaran 2004 batas waktu penyampaian Laporan • Keuangan adalah 9 bulan, mulai tahun anggaran 2005 batas waktunya • diperpendek menjadi 6 bulan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 55 UU Nomor 1 Tahun 2004, tugas menyusun Laporan Keuangan Pemerintah Pusat diemban oleh...", "options": ["Menteri Perencanaan Pembangunan Nasional selaku perencana", "Ketua Badan Pemeriksa Keuangan selaku auditor eksternal", "Gubernur Bank Indonesia selaku pimpinan dewan moneter", "Menteri Keuangan berkedudukan selaku Pengelola Fiskal", "Sekretaris Kabinet selaku pengadministrasi sidang kabinet"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tugas Menkeu Selaku Pengelola Fiskal Menyusun LKPP</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 55 UU 1/2004, <strong>Menteri Keuangan selaku Pengelola Fiskal bertugas menyusun Laporan Keuangan Pemerintah Pusat untuk disampaikan kepada Presiden</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Menteri Keuangan memegang fungsi Chief Financial Officer (CFO) pemerintah yang bertugas menyusun laporan konsolidasian seluruh kementerian/lembaga.</p><p>Jawaban tepat: <strong>D. Menteri Keuangan berkedudukan selaku Pengelola Fiskal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menteri PPN/Bappenas bertugas merencanakan pembangunan nasional.</li><li><strong>B</strong>: Ketua BPK bertugas memeriksa laporan keuangan, bukan menyusun laporan.</li><li><strong>C</strong>: Gubernur BI memimpin otoritas moneter independen, bukan pengelola fiskal.</li><li><strong>E</strong>: Sekretaris kabinet mengelola manajemen dukungan persidangan kabinet.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 73) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keuangan selaku Pengelola Fiskal bertugas menyusun Laporan Keuangan • Pemerintah Pusat untuk disampaikan kepada Presiden</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Menteri/Pimpinan Lembaga selaku Pengguna Anggaran wajib menyampaikan LKKL kepada Menteri Keuangan selambatnya...", "options": ["Tiga puluh hari sebelum pelaksanaan audit pendahuluan badan pemeriksa", "Enam bulan bersamaan dengan penyerahan RUU ke dewan perwakilan", "Empat belas hari setelah penutupan buku kas bendahara pengeluaran", "Satu tahun setelah seluruh proyek fisik di lapangan selesai tuntas", "Dua bulan setelah tahun anggaran yang bersangkutan berakhir"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batas Waktu Penyampaian LKKL Oleh PA/PB</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menteri/Pimpinan Lembaga selaku Pengguna Anggaran menyampaikan laporan keuangan kepada Menteri Keuangan <strong>selambat-lambatnya 2 (dua) bulan setelah tahun anggaran berakhir</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena batas waktu 2 bulan (akhir Februari) memberi waktu bagi Menkeu untuk mengonsolidasikan seluruh LKKL menjadi LKPP sebelum diserahkan ke BPK pada akhir Maret.</p><p>Jawaban tepat: <strong>E. Dua bulan setelah tahun anggaran yang bersangkutan berakhir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Audit pendahuluan BPK biasanya dimulai sebelum tahun anggaran berakhir.</li><li><strong>B</strong>: Enam bulan adalah batas waktu presiden menyerahkan RUU ke DPR.</li><li><strong>C</strong>: Empat belas hari adalah penutupan transaksi teknis akhir tahun.</li><li><strong>D</strong>: Satu tahun melanggar batas legal pelaporan keuangan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 73) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>2 (dua) bulan setelah tahun anggaran berakhir.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Pada akhir Februari, dokumen laporan keuangan kementerian yang wajib diserahkan kepada Menteri Keuangan meliputi...", "options": ["Laporan Realisasi Anggaran, Neraca, dan CaLK dilampiri LK BLU", "Daftar hadir harian aparatur dan slip setoran pajak kendaraan dinas", "Buku kas perorangan milik kepala kantor satuan kerja vertikal", "Kuitansi asli tanda terima pembayaran honorarium narasumber", "Surat penunjukan pemenang tender pengadaan mebel kantor dinas"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen Laporan Keuangan Kementerian Yang Disampaikan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menteri/Pimpinan Lembaga menyampaikan <strong>laporan keuangan yang meliputi Laporan Realisasi Anggaran, Neraca, dan Catatan atas Laporan Keuangan yang dilampiri laporan keuangan Badan Layanan Umum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena ketiga laporan pokok beserta lampiran keuangan satker BLU merupakan komponen utama pertanggungjawaban manajerial tingkat kementerian.</p><p>Jawaban tepat: <strong>A. Laporan Realisasi Anggaran, Neraca, dan CaLK dilampiri LK BLU</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Daftar hadir adalah dokumen pendukung pembayaran tunjangan kinerja satker.</li><li><strong>C</strong>: Kas perorangan dilarang dicampur dengan keuangan kedinasan negara.</li><li><strong>D</strong>: Kuitansi asli disimpan di satker sebagai bukti pertanggungjawaban SPJ.</li><li><strong>E</strong>: Surat pemenang tender adalah arsip berkas pengadaan pejabat pembuat komitmen.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 73) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>2 (dua) bulan setelah tahun anggaran berakhir.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Laporan pertanggungjawaban pengguna anggaran dikonsolidasikan Menteri Keuangan menjadi...", "options": ["Buku Neraca Perdagangan Valuta Asing Antarnegara Regional", "Laporan Keuangan Pemerintah Pusat sebagai bagian pokok RUU", "Kompilasi Daftar Proyek Investasi Swasta Penanaman Modal", "Katalog Pengadaan Barang dan Jasa Elektronik Nasional", "Daftar Riwayat Kepangkatan Pegawai Negeri Sipil Pusat"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsolidasi LKPP Sebagai Bagian Pokok RUU</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Laporan pertanggungjawaban pengguna anggaran dikonsolidasikan menjadi Laporan Keuangan Pemerintah Pusat sebagai bagian pokok dari RUU tentang pertanggungjawaban pelaksanaan APBN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena LKPP merupakan penggabungan menyeluruh atas seluruh laporan kementerian/lembaga dan BUN yang menjadi lampiran utama pengesahan RUU di DPR.</p><p>Jawaban tepat: <strong>B. Laporan Keuangan Pemerintah Pusat sebagai bagian pokok RUU</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Neraca perdagangan valas adalah bagian dari neraca pembayaran Bank Indonesia.</li><li><strong>C</strong>: Daftar investasi swasta disusun oleh kementerian investasi/BKPM.</li><li><strong>D</strong>: Katalog elektronik dikelola oleh Lembaga Kebijakan Pengadaan (LKPP).</li><li><strong>E</strong>: Daftar riwayat kepangkatan dikelola oleh Badan Kepegawaian Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 74) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Oleh Menteri Keuangan laporan-laporan atas pertanggungjawaban pengguna • anggaran/pengguna barang tersebut dikonsolidasikan menjadi Laporan • Keuangan Pemerintah Pusat sebagai bagian pokok dari RUU tentang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pihak yang secara resmi menyampaikan RUU Pertanggungjawaban Pelaksanaan APBN kepada DPR adalah...", "options": ["Ketua Mahkamah Konstitusi selaku penafsir undang-undang", "Gubernur Bank Indonesia selaku pemegang otoritas kas", "Presiden Republik Indonesia selaku kepala pemerintahan", "Ketua Kamar Dagang dan Industri mewakili dunia usaha", "Pimpinan konsorsium kantor akuntan publik pemeriksa"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyampaian RUU Pertanggungjawaban Oleh Presiden</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>RUU tentang pertanggungjawaban pelaksanaan APBN <strong>akan disampaikan Presiden kepada DPR</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Presiden memegang kekuasaan pengelolaan keuangan negara tertinggi dan bertanggung jawab secara konstitusional kepada DPR atas pelaksanaan APBN.</p><p>Jawaban tepat: <strong>C. Presiden Republik Indonesia selaku kepala pemerintahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mahkamah Konstitusi menguji norma undang-undang terhadap konstitusi.</li><li><strong>B</strong>: Gubernur BI tidak bertanggung jawab atas pelaksanaan APBN pemerintah.</li><li><strong>D</strong>: Kadin adalah asosiasi pengusaha dan tidak memegang mandat APBN.</li><li><strong>E</strong>: KAP bertindak sebagai pemeriksa independen, bukan entitas pelapor APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 74) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pertanggungjawaban pelaksanaan APBN yang akan disampaikan Presiden • kepada DPR.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Dalam membahas RUU Pertanggungjawaban APBN dengan Pemerintah, alat kelengkapan DPR memperhatikan...", "options": ["Hasil jajak pendapat kepopuleran menteri di media jejaring", "Tingkat perolehan suara partai politik pemenang pemilihan umum", "Perkiraan pergerakan indeks harga saham gabungan di bursa", "Hasil pemeriksaan semester dan opini yang diberikan BPK", "Ketetapan suku bunga pinjaman antar-bank jangka pendek"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembahasan RUU Pertanggungjawaban di DPR</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pembahasan RUU oleh komisi DPR dengan Pemerintah <strong>dilakukan dengan memperhatikan hasil pemeriksaan semester dan opini BPK</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena hasil audit BPK (IHPS dan LHP LKPP) menjadi rujukan objektif bagi DPR untuk menilai apakah pengelolaan anggaran telah sesuai aturan dan bebas dari salah saji.</p><p>Jawaban tepat: <strong>D. Hasil pemeriksaan semester dan opini yang diberikan BPK</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jajak pendapat media sosial bersifat persepsi subjektif warganet.</li><li><strong>B</strong>: Hasil pemilu legislatif tidak menjadi dasar telaah audit keuangan negara.</li><li><strong>C</strong>: IHSG adalah indikator pasar modal komersial bursa efek.</li><li><strong>E</strong>: Suku bunga antar-bank adalah variabel pasar uang perbankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 74) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pertanggungjawaban pelaksanaan APBN yang akan disampaikan Presiden</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pernyataan Standar Akuntansi Pemerintahan (PSAP) disusun oleh lembaga independen yaitu...", "options": ["Lembaga Sertifikasi Profesi Pengadaan Barang", "Dewan Pengawas Perbankan Syariah Nasional", "Badan Arbitrase Pasar Modal Indonesia Terbuka", "Asosiasi Akuntan Manajemen Korporasi Swasta", "Komite Standar Akuntansi Pemerintahan (KSAP)"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyusun Standar Akuntansi Pemerintahan (KSAP)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PSAP disusun oleh <strong>suatu komite yang independen, yaitu Komite Standar Akuntansi Pemerintahan (KSAP)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena KSAP dibentuk secara independen dan profesional untuk merumuskan prinsip dan standar akuntansi sektor publik yang baku bagi pemerintah.</p><p>Jawaban tepat: <strong>E. Komite Standar Akuntansi Pemerintahan (KSAP)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Lembaga sertifikasi profesi menyelenggarakan uji kompetensi profesi.</li><li><strong>B</strong>: Dewan pengawas syariah mengawasi kepatuhan syariah perbankan.</li><li><strong>C</strong>: Badan arbitrase pasar modal menyelesaikan sengketa perdata efek.</li><li><strong>D</strong>: Asosiasi akuntan manajemen berfokus pada akuntansi biaya korporasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 74) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Komite • Standar • Akuntansi • Pemerintahan, dan ditetapkan dengan Peraturan Pemerintah setelah terlebih</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Sebelum ditetapkan dengan Peraturan Pemerintah, Standar Akuntansi Pemerintahan wajib terlebih dahulu...", "options": ["Mendapat pertimbangan dari Badan Pemeriksa Keuangan", "Mendapat persetujuan pemegang saham bursa efek internasional", "Diuji kelayakannya oleh konsorsium perbankan devisa swasta", "Disahkan melalui ketetapan musyawarah asosiasi serikat buruh", "Disetujui oleh seluruh kepala daerah tingkat kabupaten kota"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pertimbangan BPK Dalam Penetapan Standar Akuntansi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Standar Akuntansi Pemerintahan ditetapkan dengan PP <strong>setelah terlebih dahulu mendapat pertimbangan dari Badan Pemeriksa Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena BPK selaku auditor eksternal negara harus dimintai pertimbangan agar standar akuntansi yang ditetapkan pemerintah dapat diaudit dan selaras dengan standar audit.</p><p>Jawaban tepat: <strong>A. Mendapat pertimbangan dari Badan Pemeriksa Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bursa efek internasional mengatur perdagangan saham emiten komersial.</li><li><strong>C</strong>: Konsorsium bank swasta tidak memiliki wewenang menguji SAP negara.</li><li><strong>D</strong>: Serikat buruh adalah organisasi perwakilan pekerja ketenagakerjaan.</li><li><strong>E</strong>: SAP berlaku nasional tanpa memerlukan persetujuan individual bupati/wali kota.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 74) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dahulu mendapat pertimbangan dari Badan Pemeriksa Keuangan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama penyajian Laporan Keuangan Pemerintah pada hakikatnya adalah untuk...", "options": ["Menghitung besaran pajak penghasilan yang wajib dibayar pegawai", "Menyajikan informasi bermanfaat untuk keputusan dan pertanggungjawaban", "Menetapkan kuota penerimaan calon mahasiswa kedinasan negara", "Mengumumkan nama-nama pemenang tender pengadaan konstruksi", "Menilai tingkat kekayaan pribadi seluruh pejabat eselon kementerian"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pelaporan Keuangan Pemerintah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Tujuan Laporan Keuangan adalah untuk menyajikan informasi yang bermanfaat untuk pengambilan keputusan dan untuk pertanggungjawaban atas pengelolaan sumber daya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena laporan keuangan pemerintah dirancang untuk memenuhi kebutuhan akuntabilitas publik dan membantu para pemangku kepentingan dalam menetapkan kebijakan fiskal.</p><p>Jawaban tepat: <strong>B. Menyajikan informasi bermanfaat untuk keputusan dan pertanggungjawaban</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak penghasilan pegawai dihitung melalui sistem bukti potong PPh 21.</li><li><strong>C</strong>: Kuota mahasiswa kedinasan diatur oleh kementerian PAN-RB dan lembaga terkait.</li><li><strong>D</strong>: Pengumuman pemenang lelang dilakukan melalui portal sistem pengadaan LPSE.</li><li><strong>E</strong>: Kekayaan pejabat publik dilaporkan terpisah melalui sistem LHKPN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 74) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tujuan • Laporan Keuangan adalah untuk menyajikan informasi yang bermanfaat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Laporan yang mengungkap kegiatan keuangan suatu periode yang menunjukkan ketaatan kementerian adalah...", "options": ["Buku Register SP2D Kantor Kas Perbendaharaan", "Daftar Nominatif Perjalanan Dinas Luar Daerah", "Laporan Realisasi Anggaran (LRA)", "Surat Permintaan Pembayaran Uang Persediaan Kas", "Neraca Saldo Penutupan Akun Memorial Akhir Bulan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Laporan Realisasi Anggaran (LRA)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Laporan Realisasi Anggaran mengungkap berbagai kegiatan keuangan Pemerintah untuk satu periode yang menunjukkan ketaatan terhadap ketentuan perundang-undangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena LRA menyajikan perbandingan antara anggaran yang ditetapkan dalam undang-undang dengan realisasi penerimaan, belanja, dan pembiayaan selama satu tahun.</p><p>Jawaban tepat: <strong>C. Laporan Realisasi Anggaran (LRA)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Register SP2D adalah catatan administrasi pencairan dana kantor KPPN.</li><li><strong>B</strong>: Daftar nominatif adalah lampiran bukti perjalanan dinas pegawai.</li><li><strong>D</strong>: SPP-UP adalah surat pengajuan uang muka persediaan bendahara.</li><li><strong>E</strong>: Neraca saldo penutupan adalah tahapan teknis siklus buku besar akuntansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 74) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Laporan Realisasi Anggaran mengungkap berbagai kegiatan keuangan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Informasi pokok yang disajikan dalam lembar muka Laporan Realisasi Anggaran adalah...", "options": ["Perhitungan laba kotor hasil penjualan produk unit usaha daerah", "Perbandingan harga saham perseroan terbuka di pasar modal bursa", "Daftar inventaris gedung dan peralatan operasional satuan kerja", "Keseimbangan antara anggaran pendapatan, belanja, dan pembiayaan", "Besaran cicilan pokok kredit pemilikan kendaraan milik staf dinas"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Informasi Keseimbangan Anggaran Dalam LRA</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>LRA memberikan informasi mengenai <strong>keseimbangan antara anggaran pendapatan, anggaran belanja dan pembiayaan dengan realisasinya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena ketiga pos anggaran ini (pendapatan, belanja, dan pembiayaan) mencerminkan struktur APBN dan memperlihatkan terjadinya surplus atau defisit anggaran.</p><p>Jawaban tepat: <strong>D. Keseimbangan antara anggaran pendapatan, belanja, dan pembiayaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Laba kotor penjualan disajikan dalam laporan keuangan komersial BUMD.</li><li><strong>B</strong>: Harga saham diperdagangkan di pasar sekunder bursa efek.</li><li><strong>C</strong>: Daftar inventaris aset tetap disajikan dalam Neraca dan CaLK.</li><li><strong>E</strong>: Cicilan kredit kendaraan pribadi staf bukan bagian dari anggaran APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 74) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>keseimbangan antara anggaran pendapatan, anggaran belanja dan • pembiayaan dengan realisasinya.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Disamping angka realisasi, informasi penjelas yang wajib disertakan pada LRA mencakup...", "options": ["Besaran bonus insentif bagi panitia lelang pengadaan barang", "Daftar nama seluruh wajib pajak yang menunggak pembayaran denda", "Alamat tempat tinggal lengkap para pegawai pengelola keuangan", "Nomor rekening pribadi bendahara pengeluaran pada bank umum", "Kebijakan fiskal, moneter, dan sebab perbedaan material anggaran"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Informasi Tambahan Penjelas LRA</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>LRA disertai informasi tambahan berisi hal yang mempengaruhi pelaksanaan anggaran seperti <strong>kebijakan fiskal dan moneter, sebab-sebab terjadinya perbedaan yang material antara anggaran dan realisasinya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena penjelasan analisis varians anggaran (sebab selisih lebih/kurang) membantu pembaca memahami faktor ekonomi makro yang mempengaruhi serapan anggaran.</p><p>Jawaban tepat: <strong>E. Kebijakan fiskal, moneter, dan sebab perbedaan material anggaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Honorarium kepanitiaan diatur dalam standar biaya masukan resmi.</li><li><strong>B</strong>: Daftar penunggak pajak dilindungi oleh kerahasiaan data perpajakan.</li><li><strong>C</strong>: Alamat pribadi pegawai adalah data pribadi yang dilindungi undang-undang.</li><li><strong>D</strong>: Rekening yang digunakan wajib rekening dinas resmi, bukan pribadi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 74) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pembiayaan dengan realisasinya.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Laporan yang menyajikan kenaikan atau penurunan Saldo Anggaran Lebih (SAL) adalah...", "options": ["Laporan Perubahan Saldo Anggaran Lebih (LPSAL)", "Laporan Posisi Kas Cadangan Darurat Kementerian", "Daftar Mutasi Penarikan Cek Giro Bendahara Umum", "Buku Kas Pembantu Penerimaan Pajak Pertambahan", "Surat Keterangan Penghentian Pembayaran Belanja"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Laporan Perubahan Saldo Anggaran Lebih</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Laporan perubahan Saldo Anggaran Lebih (SAL) menyajikan kenaikan atau penurunan SAL tahun pelaporan dibandingkan dengan tahun sebelumnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena LPSAL merupakan komponen laporan keuangan berbasis kas yang merangkum akumulasi sisa lebih pembiayaan anggaran tahun-tahun sebelumnya.</p><p>Jawaban tepat: <strong>A. Laporan Perubahan Saldo Anggaran Lebih (LPSAL)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kas cadangan darurat dikelola dalam rekening kas umum negara.</li><li><strong>C</strong>: Mutasi cek giro adalah catatan perbankan penatausahaan kasir.</li><li><strong>D</strong>: Buku kas pembantu adalah sarana pencatatan pembukuan bendahara.</li><li><strong>E</strong>: SKPP adalah surat mutasi gaji pegawai antar-satuan kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 75) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Laporan perubahan Saldo Anggaran Lebih (SAL) menyajikan kenaikan • atau penurunan SAL tahun pelaporan dibandingkan dengan tahun • sebelumnya.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Komponen utama yang disajikan dalam lembar muka LPSAL pemerintah meliputi...", "options": ["Pendapatan dividen BUMN, belanja perjalanan dinas, dan modal", "SAL awal, Penggunaan SAL, SiLPA/SiKPA, Koreksi, dan SAL akhir", "Kas di bank operasional, piutang pajak, dan persediaan barang", "Utang luar negeri komersial, belanja bunga, dan amortisasi utang", "Ekuitas awal, surplus operasional, revaluasi aset, dan ekuitas akhir"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen Unsur Dalam LPSAL</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Komponen pada LPSAL diantaranya <strong>SAL awal, Penggunaan SAL, SiLPA/SiKPA, Koreksi kesalahan pembukuan tahun sebelumnya, Lain-lain, dan SAL akhir</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena seluruh unsur ini secara matematis menunjukkan pergerakan saldo kas lebih dari awal tahun hingga saldo akhir pada penutupan buku anggaran.</p><p>Jawaban tepat: <strong>B. SAL awal, Penggunaan SAL, SiLPA/SiKPA, Koreksi, dan SAL akhir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dividen dan belanja perjalanan dinas adalah pos dalam LRA dan LO.</li><li><strong>C</strong>: Kas, piutang, dan persediaan merupakan pos aset lancar dalam Neraca.</li><li><strong>D</strong>: Utang luar negeri dan belanja bunga adalah pos Neraca dan LRA.</li><li><strong>E</strong>: Ekuitas dan surplus operasional merupakan komponen utama dalam LPE.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 75) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>SAL awal, Penggunaan • SAL, SiLPA/SiKPA, Koreksi kesalahan pembukuan tahun sebelumnya, • Lain-lain, dan SAL akhir.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Bagi satuan kerja yang berstatus Badan Layanan Umum (BLU), rincian perubahan unsur LPSAL disajikan pada...", "options": ["Buku kas perorangan pejabat pengelola keuangan", "Papan pengumuman informasi lobi kantor pelayanan", "Catatan atas Laporan Keuangan (CaLK)", "Koran harian nasional edisi akhir pekan daerah", "Berkas kuitansi penagihan jasa konsultasi dinas"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengungkapan Unsur LPSAL Oleh Satuan Kerja BLU</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Satuan Kerja BLU menyajikan rincian lebih lanjut unsur-unsur perubahan yang terdapat dalam LPSAL pada CaLK</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena fleksibilitas pengelolaan keuangan BLU menuntut rincian penjelas yang memadai dalam CaLK mengenai pergerakan saldo kas dan investasinya.</p><p>Jawaban tepat: <strong>C. Catatan atas Laporan Keuangan (CaLK)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menyimpan catatan dinas di buku perorangan melanggar aturan.</li><li><strong>B</strong>: Papan pengumuman lobi kantor memuat informasi alur pelayanan publik.</li><li><strong>D</strong>: Koran harian memuat pemberitaan umum, bukan rincian CaLK BLU.</li><li><strong>E</strong>: Kuitansi penagihan adalah dokumen lampiran SPJ pengeluaran belanja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 75) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Lain-lain, dan SAL akhir.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Laporan yang menyajikan ikhtisar sumber daya ekonomi yang menambah ekuitas dan penggunaannya adalah...", "options": ["Buku Pembantu Uang Muka Perjalanan Dinas Luar", "Laporan Realisasi Anggaran berbasis kas penuh", "Daftar Nominatif Penerima Honorarium Tim Teknis", "Laporan Operasional (LO) berbasis akrual", "Surat Bukti Setor Pajak Pertambahan Nilai Barang"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Laporan Operasional (LO)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Laporan Operasional menyajikan ikhtisar sumber daya ekonomi yang menambah ekuitas dan penggunaannya yang dikelola oleh pemerintah pusat/daerah dalam satu periode akuntansi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Laporan Operasional (LO) mencatat seluruh hak pendapatan dan kewajiban beban pada saat timbulnya transaksi (akrual), terlepas kas diterima atau dikeluarkan.</p><p>Jawaban tepat: <strong>D. Laporan Operasional (LO) berbasis akrual</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Buku pembantu uang muka adalah sarana penatausahaan kasir internal.</li><li><strong>B</strong>: LRA mencatat aliran anggaran berbasis kas sesuai pagu undang-undang.</li><li><strong>C</strong>: Daftar nominatif adalah lampiran bukti pertanggungjawaban honor staf.</li><li><strong>E</strong>: Surat bukti setor pajak adalah dokumen bukti pembayaran setoran kasir.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 75) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Laporan Operasional menyajikan ikhtisar sumber daya ekonomi yang • menambah ekuitas dan peggunaannya yang dikelola oleh pemerintah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Unsur-unsur pokok yang dicakup dalam Laporan Operasional (LO) pemerintah meliputi...", "options": ["Pagu DIPA, Realisasi kasir belanja, dan Sisa anggaran pagu", "Aset lancar, Aset tetap, Kewajiban jangka pendek, Ekuitas", "Penerimaan pembiayaan, Pengeluaran pembiayaan, dan SiLPA", "Saldo awal kas, Arus kas operasi, dan Saldo akhir kas", "Pendapatan-LO, Beban, Pos Non-Operasional, Pos Luar Biasa"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Unsur-Unsur Yang Dicakup Dalam Laporan Operasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Unsur yang dicakup dalam Laporan Operasional yaitu <strong>Pendapatan-LO dari operasional, Beban operasional, Surplus/defisit kegiatan non-operasional, Pos luar biasa, dan Surplus/defisit-LO</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pos-pos ini mencerminkan kinerja operasional murni instansi pemerintah dalam satu periode pelaporan berbasis akrual.</p><p>Jawaban tepat: <strong>E. Pendapatan-LO, Beban, Pos Non-Operasional, Pos Luar Biasa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pagu DIPA dan serapan anggaran adalah konsep dalam LRA dan DIPA.</li><li><strong>B</strong>: Aset lancar, aset tetap, dan kewajiban adalah unsur-unsur dalam Neraca.</li><li><strong>C</strong>: Penerimaan pembiayaan dan SiLPA adalah unsur-unsur dalam LRA.</li><li><strong>D</strong>: Saldo awal kas dan arus kas operasi adalah unsur-unsur dalam LAK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 75) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pendapatan-LO, dari kegiatan operasional; Beban, dari kegiatan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Laporan yang menggambarkan posisi aset, kewajiban, dan ekuitas pada tanggal tertentu dinamakan...", "options": ["Neraca entitas pelaporan pemerintah", "Laporan Arus Kas Masuk dan Keluar", "Laporan Realisasi Belanja Barang Rutin", "Buku Besar Pembantu Kas Tunai Satker", "Daftar Rincian Gaji Pokok Pegawai Dinas"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi dan Struktur Neraca Pemerintah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Neraca menggambarkan posisi keuangan suatu entitas pelaporan mengenai asset baik lancar maupun tidak lancar, kewajiban jangka pendek maupun jangka panjang, dan ekuitas pada tanggal tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Neraca merupakan potret kumulatif kekayaan bersih dan kewajiban entitas negara pada saat penutupan tahun anggaran (per 31 Desember).</p><p>Jawaban tepat: <strong>A. Neraca entitas pelaporan pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: LAK menyajikan aliran mutasi arus kas selama satu periode pelaporan.</li><li><strong>C</strong>: Laporan realisasi belanja barang adalah rincian belanja operasional LRA.</li><li><strong>D</strong>: Buku besar pembantu kas adalah sarana penatausahaan pembukuan kasir.</li><li><strong>E</strong>: Daftar gaji pokok adalah lampiran pembayaran belanja pegawai bulanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 75) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Neraca menggambarkan posisi keuangan suatu entitas pelaporan • mengenai asset baik lancar maupun tidak lancar, kewajiban jangka • pendek maupun kewajiban jangka panjang, dan ekuitas dana pada</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Neraca tingkat Pemerintah Pusat pada hakikatnya disusun melalui proses...", "options": ["Perkiraan subjektif yang ditetapkan direktur jenderal anggaran", "Konsolidasi dari neraca seluruh Kementerian/Lembaga dan BUN", "Pelelangan terbuka terhadap aset bermasalah kementerian dinas", "Pencatatan kas kecil harian oleh staf tata usaha kementerian", "Penggabungan neraca perusahaan swasta dengan kantor dinas"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsolidasi Neraca Pemerintah Pusat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Neraca tingkat Pemerintah Pusat merupakan konsolidasi dari neraca tingkat Kementerian/Lembaga</strong> serta Bagian Anggaran Bendahara Umum Negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena sistem akuntansi pemerintah pusat (SAPP) mengonsolidasikan neraca seluruh unit akuntansi pengguna anggaran menjadi satu neraca utuh pemerintah pusat.</p><p>Jawaban tepat: <strong>B. Konsolidasi dari neraca seluruh Kementerian/Lembaga dan BUN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Neraca disusun berdasar bukti pembukuan akuntansi, bukan taksiran subjektif.</li><li><strong>C</strong>: Lelang aset bermasalah adalah kegiatan tindak lanjut pemulihan kerugian.</li><li><strong>D</strong>: Kas kecil dicatat pada buku kas kasir, bukan langsung menjadi neraca pusat.</li><li><strong>E</strong>: Neraca negara dilarang menggabungkan aset korporasi swasta murni.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 75) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>konsolidasi dari neraca tingkat Kementerian/Lembaga.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C4 - Analisis", "question": "Kriteria pembeda utama antara pos aset/kewajiban lancar dengan jangka panjang dalam neraca adalah...", "options": ["Nama pejabat pembuat komitmen yang menandatangani kontrak kerja", "Besaran nominal uang yang tercantum di atas sepuluh miliar rupiah", "Jangka waktu realisasi atau jatuh tempo dalam dua belas bulan", "Lokasi fisik penyimpanan aset di dalam gedung kementerian pusat", "Jenis mata uang valuta asing yang dipergunakan dalam pembayaran"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Klasifikasi Pos Aset dan Kewajiban 12 Bulan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam neraca diungkapkan pos aset dan kewajiban yang diharapkan diterima atau dibayar <strong>dalam jangka waktu dua belas bulan setelah tanggal pelaporan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena batas waktu 12 bulan merupakan konvensi standar akuntansi untuk membedakan pos lancar (likuid/jatuh tempo ≤12 bulan) dengan pos tidak lancar/jangka panjang (>12 bulan).</p><p>Jawaban tepat: <strong>C. Jangka waktu realisasi atau jatuh tempo dalam dua belas bulan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nama penandatangan kontrak tidak menentukan klasifikasi pos neraca.</li><li><strong>B</strong>: Klasifikasi neraca didasarkan pada waktu likuiditas, bukan besaran nominal.</li><li><strong>D</strong>: Lokasi fisik aset tidak membedakan sifat kelancaran pos pembukuan.</li><li><strong>E</strong>: Mata uang valas dikonversi ke nilai rupiah saat tanggal neraca pelaporan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 75) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>konsolidasi dari neraca tingkat Kementerian/Lembaga.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Laporan yang ditujukan untuk memberikan informasi mengenai arus masuk dan keluar kas pemerintah adalah...", "options": ["Daftar Rincian Sisa Pagu Anggaran Belanja", "Laporan Perubahan Ekuitas pemda", "Buku Pembantu Kasir Pengeluaran Satker", "Laporan Arus Kas (LAK) pemerintah", "Surat Perintah Membayar Uang Muka DIPA"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Laporan Arus Kas (LAK)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Laporan arus kas ditujukan untuk memberikan informasi mengenai arus masuk dan keluar kas dari Pemerintah dalam suatu periode laporan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena LAK melaporkan penerimaan dan pengeluaran kas riil yang mempengaruhi saldo rekening kas umum negara selama satu tahun anggaran.</p><p>Jawaban tepat: <strong>D. Laporan Arus Kas (LAK) pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sisa pagu anggaran adalah informasi pengendalian anggaran pada DIPA.</li><li><strong>B</strong>: LPE menyajikan perubahan saldo ekuitas kekayaan bersih pemerintah.</li><li><strong>C</strong>: Buku pembantu kasir adalah sarana pembukuan kas harian bendahara.</li><li><strong>E</strong>: SPM adalah dokumen pengajuan pencairan dana ke kantor perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 76) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Laporan arus kas ditujukan untuk memberikan informasi mengenai arus • masuk dan ke keluar kas dari Pemerintah dalam suatu periode laporan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Informasi dalam Laporan Arus Kas dapat dimanfaatkan oleh para pengguna untuk mengevaluasi...", "options": ["Besaran tagihan listrik dan telepon rumah dinas pejabat eselon kementerian", "Tingkat kedisiplinan jam kerja harian para pegawai administrasi kantor", "Kesesuaian busana dinas harian pegawai dengan peraturan tata tertib", "Perolehan keuntungan bersih komersial para pemborong lelang proyek", "Hubungan antara aktivitas operasi, investasi, pembiayaan, dan non-anggaran"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Evaluasi Empat Aktivitas LAK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Informasi LAK digunakan untuk <strong>mengevaluasi hubungan antara aktivitas operasi, investasi, pembiayaan, dan non anggaran</strong> serta pengaruhnya terhadap kas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena keempat kategori arus kas ini memperlihatkan dari mana kas pemerintah diperoleh dan untuk aktivitas apa saja kas tersebut dibelanjakan.</p><p>Jawaban tepat: <strong>E. Hubungan antara aktivitas operasi, investasi, pembiayaan, dan non-anggaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tagihan rumah dinas adalah pos beban utilitas langganan daya dan jasa.</li><li><strong>B</strong>: Disiplin jam kerja dipantau melalui mesin presensi elektronik kepegawaian.</li><li><strong>C</strong>: Pakaian dinas diatur dalam peraturan menteri tentang seragam kerja ASN.</li><li><strong>D</strong>: Keuntungan pemborong adalah rahasia dapur perusahaan swasta rekanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 76) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mengevaluasi hubungan antara aktivitas operasi, • investasi, pembiayaan, dan non anggaran.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Standar Akuntansi Pemerintahan, entitas yang berwenang menyusun Laporan Arus Kas adalah...", "options": ["Unit pemerintah yang melaksanakan fungsi perbendaharaan negara/daerah", "Seluruh kantor cabang perbankan swasta yang membuka kas operasional", "Panitia pengadaan barang dan jasa pada setiap satuan kerja dinas", "Lembaga swadaya masyarakat pemerhati kebijakan keterbukaan publik", "Komisi perwakilan daerah yang membidangi urusan otonomi wilayah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyusun LAK (Fungsi Perbendaharaan)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Laporan Arus Kas ini disusun oleh unit pemerintah yang melaksanakan fungsi perbendaharaan</strong> (BUN/BUD).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena LAK hanya disusun oleh Bendahara Umum Negara (Kementerian Keuangan di pusat) dan Pejabat Pengelola Keuangan Daerah (PPKD di daerah) yang menguasai rekening kas umum.</p><p>Jawaban tepat: <strong>A. Unit pemerintah yang melaksanakan fungsi perbendaharaan negara/daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bank swasta hanya mitra penampung kas, bukan penyusun LAK negara.</li><li><strong>C</strong>: Panitia lelang bertugas memilih penyedia jasa pengadaan barang dinas.</li><li><strong>D</strong>: LSM bertindak sebagai pemantau partisipatif eksternal masyarakat.</li><li><strong>E</strong>: Komisi perwakilan daerah menjalankan fungsi legislasi dan pengawasan dewan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 76) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Laporan Arus Kas ini disusun oleh unit pemerintah yang melaksanakan • fungsi perbendaharaan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Aktivitas yang disajikan dalam lembar muka Laporan Arus Kas (LAK) mencakup...", "options": ["Penerbitan kartu identitas pegawai, cuti tahunan, dan mutasi dinas", "Operasional, investasi non-keuangan, dana cadangan, dan pembiayaan", "Penyitaan kendaraan dinas, pelelangan gedung kantor, dan renovasi", "Pungutan retribusi sampah lingkungan, iuran warga, dan sumbangan", "Penggajian staf honorer, pembelian seragam dinas, dan konsumsi rapat"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Aktivitas Yang Disajikan Dalam LAK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>LAK menyajikan kas sehubungan <strong>aktivitas operasional, investasi aset non keuangan, dana cadangan, pembiayaan, dan transaksi non-anggaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pengelompokan baku ini memetakan seluruh pergerakan kas masuk dan kas keluar kas negara sesuai peruntukan fungsionalnya.</p><p>Jawaban tepat: <strong>B. Operasional, investasi non-keuangan, dana cadangan, dan pembiayaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kartu identitas dan cuti pegawai adalah urusan administrasi kepegawaian.</li><li><strong>C</strong>: Penyitaan kendaraan adalah tindakan penertiban barang milik negara.</li><li><strong>D</strong>: Retribusi sampah dan sumbangan warga adalah pos kas lingkungan daerah.</li><li><strong>E</strong>: Gaji honorer dan konsumsi rapat merupakan rincian belanja barang satker.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 76) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>aktivitas • operasional, investasi aset non keuangan,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Di tingkat pusat fungsi perbendaharaan dijalankan Kemenkeu, sedangkan di tingkat daerah dijalankan oleh...", "options": ["Kepala Satuan Polisi Pamong Praja Kabupaten", "Ketua Komisi Pemilihan Umum Daerah Provinsi", "Pejabat Pengelola Keuangan Daerah (PPKD)", "Direktur Rumah Sakit Umum Daerah Wilayah", "Pimpinan Asosiasi Pengusaha Kamar Dagang Daerah"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembeda Pelaksana Fungsi Perbendaharaan Pusat dan Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Di pemerintah pusat fungsi perbendaharaan dilaksanakan oleh Kementerian Keuangan, sedangkan di daerah dilaksanakan oleh <strong>Pejabat Pengelola Keuangan Daerah (PPKD)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kepala Badan Keuangan Daerah (BPKAD) bertindak sebagai PPKD sekaligus Bendahara Umum Daerah (BUD) yang berwenang menyusun LAK daerah.</p><p>Jawaban tepat: <strong>C. Pejabat Pengelola Keuangan Daerah (PPKD)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Satpol PP menegakkan peraturan daerah dan ketertiban umum masyarakat.</li><li><strong>B</strong>: KPUD adalah penyelenggara pemilu kepala daerah di tingkat lokal.</li><li><strong>D</strong>: Direktur RSUD bertindak sebagai pengguna anggaran dan pimpinan BLUD.</li><li><strong>E</strong>: Kadin daerah adalah wadah koordinasi pelaku usaha niaga lokal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 76) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>fungsi perbendaharaan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Laporan yang menyajikan kenaikan atau penurunan ekuitas tahun pelaporan dibanding tahun lalu adalah...", "options": ["Daftar Piutang Macet Pajak Daerah", "Laporan Mutasi Barang Milik Negara", "Buku Register SP2D Beban Pegawai", "Laporan Perubahan Ekuitas (LPE)", "Surat Penetapan Ganti Rugi Kasir"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Laporan Perubahan Ekuitas (LPE)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Laporan Perubahan Ekuitas menyajikan informasi kenaikan atau penurunan ekuitas tahun pelaporan dibandingkan dengan tahun sebelumnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena LPE menjembatani hasil kinerja operasional (surplus/defisit LO) dengan perubahan posisi kekayaan bersih (ekuitas akhir) yang disajikan dalam Neraca.</p><p>Jawaban tepat: <strong>D. Laporan Perubahan Ekuitas (LPE)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Piutang macet adalah rincian aset piutang dalam neraca keuangan daerah.</li><li><strong>B</strong>: Laporan mutasi BMN menyajikan pergerakan fisik aset barang kantor.</li><li><strong>C</strong>: Register SP2D adalah catatan administrasi pencairan dana kantor KPPN.</li><li><strong>E</strong>: Surat ganti rugi adalah penetapan vonis perbendaharaan kasus bendahara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 76) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Laporan Perubahan Ekuitas menyajikan informasi kenaikan atau • penurunan ekuitas tahun pelaporan dibandingkan dengan tahun • sebelumnya.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Komponen utama yang disajikan dalam lembar muka Laporan Perubahan Ekuitas (LPE) adalah...", "options": ["Arus kas operasi, Arus kas investasi, dan Saldo akhir kas", "Kas di bank sentral, Piutang pajak, Persediaan barang, Utang", "Pendapatan dividen BUMN, Belanja barang dinas, Belanja modal", "Penerimaan pembiayaan utang, Pelunasan amortisasi, SiLPA", "Ekuitas awal, Surplus/defisit LO, Koreksi langsung, Ekuitas akhir"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen Penyusun Laporan Perubahan Ekuitas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>LPE menyajikan <strong>Ekuitas awal; Surplus/defisit LO periode bersangkutan; Koreksi-koreksi yang langsung menambah/mengurangi ekuitas; dan Ekuitas akhir</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena persamaan akuntansi ekuitas memperhitungkan saldo awal, hasil operasional berjalan, penyesuaian koreksi mendasar, sehingga menghasilkan saldo ekuitas penutupan.</p><p>Jawaban tepat: <strong>E. Ekuitas awal, Surplus/defisit LO, Koreksi langsung, Ekuitas akhir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Arus kas operasi dan saldo kas merupakan struktur Laporan Arus Kas.</li><li><strong>B</strong>: Kas, piutang, dan persediaan adalah akun aset lancar dalam Neraca.</li><li><strong>C</strong>: Pendapatan dividen dan belanja barang adalah komponen LRA dan LO.</li><li><strong>D</strong>: Pembiayaan utang dan pelunasan amortisasi adalah pos anggaran LRA.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 76) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Ekuitas awal; Surplus/defisit LO periode • bersangkutan; Koreksi-koreksi yang langsung menambah/mengurangi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Koreksi yang langsung menambah atau mengurangi ekuitas pada LPE antara lain bersumber dari...", "options": ["Dampak kumulatif perubahan kebijakan akuntansi dan koreksi mendasar", "Pemberian dana pinjaman modal kerja kepada perusahaan perorangan", "Penarikan iuran sukarela dari masyarakat di sekitar kantor dinas", "Pembayaran denda keterlambatan proyek oleh kontraktor rekanan", "Penurunan suku bunga kredit konsumsi pada bank umum komersial"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Koreksi Dampak Kumulatif Kebijakan Akuntansi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Koreksi ekuitas berasal dari <strong>dampak kumulatif yang disebabkan oleh perubahan kebijakan akuntansi dan koreksi kesalahan mendasar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena perubahan kebijakan akuntansi (misalnya dari basis kas menuju akrual ke akrual penuh) disesuaikan langsung ke pos ekuitas tanpa melalui laporan operasional berjalan.</p><p>Jawaban tepat: <strong>A. Dampak kumulatif perubahan kebijakan akuntansi dan koreksi mendasar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pinjaman modal kerja swasta bukan pos koreksi ekuitas negara.</li><li><strong>C</strong>: Iuran sukarela bukan transaksi keuangan resmi pemerintahan.</li><li><strong>D</strong>: Denda keterlambatan proyek disetorkan ke kas negara sebagai pos PNBP.</li><li><strong>E</strong>: Suku bunga kredit konsumsi diatur oleh mekanisme pasar perbankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 76) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>bersangkutan; Koreksi-koreksi yang langsung menambah/mengurangi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C3 - Aplikasi", "question": "Contoh transaksi yang diklasifikasikan sebagai koreksi langsung penambah/pengurang ekuitas LPE adalah...", "options": ["Pencairan dana uang muka persediaan untuk pembelian alat tulis", "Koreksi kesalahan persediaan masa lalu dan revaluasi aset tetap", "Pembayaran honorarium narasumber seminar kepegawaian kantor", "Pembelian bahan bakar minyak untuk mobil dinas operasional satker", "Penyetoran bea masuk barang kiriman impor melalui kantor pos"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Contoh Koreksi Kesalahan Mendasar dan Revaluasi Aset</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Contoh koreksi ekuitas adalah <strong>koreksi kesalahan mendasar dari persediaan yang terjadi pada periode sebelumnya dan perubahan nilai aset tetap karena revaluasi aset tetap</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kesalahan persediaan tahun lampau dan surplus revaluasi BMN mencerminkan perubahan nilai kekayaan masa lalu yang langsung dibukukan ke pos ekuitas.</p><p>Jawaban tepat: <strong>B. Koreksi kesalahan persediaan masa lalu dan revaluasi aset tetap</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Uang muka persediaan alat tulis adalah mutasi kas belanja barang berjalan.</li><li><strong>C</strong>: Honorarium seminar dibukukan sebagai beban operasional berjalan LO.</li><li><strong>D</strong>: BBM kendaraan dinas masuk pos beban barang dan jasa tahun berjalan.</li><li><strong>E</strong>: Bea masuk impor dibukukan sebagai pendapatan perpajakan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 76) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>bersangkutan; Koreksi-koreksi yang langsung menambah/mengurangi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Kedudukan Catatan atas Laporan Keuangan (CaLK) dalam pelaporan pertanggungjawaban adalah...", "options": ["Dokumen lampiran tambahan yang bersifat opsional sukarela dinas", "Brosur promosi pelayanan publik yang dibagikan secara gratis", "Bagian yang tak terpisahkan dari laporan keuangan penyaji pos", "Buku catatan harian milik bendahara pengeluaran satuan kerja", "Surat perjanjian rahasia antara menteri dan pihak auditor BPK"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Catatan atas Laporan Keuangan (CaLK)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Catatan atas Laporan Keuangan (CaLK) adalah bagian yang tak terpisahkan dari laporan keuangan yang menyajikan informasi tentang penjelasan pos-pos laporan keuangan dalam rangka pengungkapan yang memadai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena CaLK memberikan penjelasan naratif, dasar hukum, dan rincian angka atas setiap pos yang tertera di lembar muka laporan keuangan (*integral part*).</p><p>Jawaban tepat: <strong>C. Bagian yang tak terpisahkan dari laporan keuangan penyaji pos</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: CaLK bersifat wajib mutlak (*mandatory*), bukan opsional sukarela.</li><li><strong>B</strong>: Brosur promosi adalah materi sosialisasi kehumasan birokrasi dinas.</li><li><strong>D</strong>: Buku kasir harian adalah sarana pembukuan teknis bendahara kantor.</li><li><strong>E</strong>: Hubungan kementerian dan BPK bersifat profesional dan transparan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 77) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Catatan atas Laporan Keuangan (CaLK) adalah bagian yang tak • terpisahkan dari laporan keuangan yang menyajikan informasi tentang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Penyajian CaLK secara memadai ditujukan agar laporan keuangan pemerintah dapat...", "options": ["Menggantikan sepenuhnya fungsi neraca dan laporan realisasi kas", "Dirahasiakan sepenuhnya dari pengawasan dewan perwakilan dewan", "Disimpan secara tertutup tanpa pemeriksaan badan audit negara", "Dipahami dan dibandingkan dengan laporan keuangan entitas lainnya", "Menghapus temuan ketidakpatuhan regulasi yang dicatat auditor"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Penyusunan CaLK (Pemahaman dan Komparabilitas)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>CaLK ditujukan agar laporan keuangan dapat dipahami dan dibandingkan dengan laporan keuangan entitas lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena prinsip *understandability* dan *comparability* memungkinkan para pengambil keputusan membandingkan kinerja fiskal antartahun maupun antarkementerian.</p><p>Jawaban tepat: <strong>D. Dipahami dan dibandingkan dengan laporan keuangan entitas lainnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: CaLK melengkapi dan menjelaskan lembar muka, bukan menggantikannya.</li><li><strong>B</strong>: Keuangan negara menganut asas transparansi bagi lembaga perwakilan.</li><li><strong>C</strong>: Seluruh dokumen keuangan negara wajib terbuka bagi pemeriksaan BPK.</li><li><strong>E</strong>: CaLK menyajikan fakta jujur dan tidak dapat menghapus temuan audit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 77) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Catatan atas Laporan Keuangan (CaLK) adalah bagian yang tak • terpisahkan dari laporan keuangan yang menyajikan informasi tentang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Susunan awal yang disajikan dalam bab pertama Catatan atas Laporan Keuangan adalah...", "options": ["Daftar nomor induk kependudukan seluruh wajib pajak daerah", "Daftar putusan pengadilan perkara pelanggaran disiplin kerja", "Rincian harga penawaran terendah lelang pengadaan barang kantor", "Besaran nominal dividen saham perusahaan terbuka swasta bursa", "Informasi umum entitas pelaporan dan dasar hukum pendiriannya"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Susunan CaLK Butir a (Informasi Umum Entitas)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>CaLK sekurang-kurangnya disajikan dengan susunan: <strong>informasi umum entitas</strong>; kebijakan fiskal/makro; ikhtisar kinerja; kebijakan akuntansi; dan pengungkapan PSAP.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena informasi umum memuat profil organisasi, visi misi, dasar hukum pembentukan instansi, dan ruang lingkup pelayanan entitas pelapor.</p><p>Jawaban tepat: <strong>E. Informasi umum entitas pelaporan dan dasar hukum pendiriannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: NIK wajib pajak adalah basis data rahasia kependudukan dan perpajakan.</li><li><strong>B</strong>: Putusan perkara pelanggaran disiplin dicatat dalam arsip kepegawaian.</li><li><strong>C</strong>: Harga lelang penawaran dicatat dalam berita acara lelang pokja.</li><li><strong>D</strong>: Dividen saham swasta adalah laporan korporasi pasar modal bursa efek.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 77) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>informasi umum entitas;</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Unsur CaLK yang menguraikan asumsi makro, target UU APBN, serta kendala pencapaian adalah...", "options": ["Informasi kebijakan fiskal/keuangan, ekonomi makro, target APBN", "Laporan pengawasan internal satuan pengawas BUMN konstruksi", "Buku catatan agenda rapat dengar pendapat komisi dewan rakyat", "Daftar penetapan tarif pajak retribusi parkir kendaraan umum", "Surat ketetapan sanksi disiplin penurunan pangkat PNS dinas"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Susunan CaLK Butir b (Kebijakan Fiskal dan Makro)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>CaLK menyajikan <strong>informasi tentang kebijakan fiskal/keuangan, ekonomi makro, pencapaian target Undang-Undang APBN, berikut kendala dan hambatan yang dihadapi dalam pencapaian target</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena uraian kondisi ekonomi makro (pertumbuhan ekonomi, inflasi, nilai tukar, harga minyak) menjelaskan deviasi antara target undang-undang dan realisasi fiskal.</p><p>Jawaban tepat: <strong>A. Informasi kebijakan fiskal/keuangan, ekonomi makro, target APBN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Laporan pengawasan internal BUMN adalah arsip SPI korporasi BUMN.</li><li><strong>C</strong>: Catatan rapat dewan adalah risalah persidangan sekretariat DPR.</li><li><strong>D</strong>: Tarif retribusi parkir diatur dalam peraturan daerah kabupaten/kota.</li><li><strong>E</strong>: SK sanksi disiplin adalah arsip keputusan pejabat pembina kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 77) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>informasi tentang kebijakan fiskal/keuangan, ekonomi makro,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Ringkasan capaian keuangan yang disajikan dalam CaLK disusun dalam bentuk...", "options": ["Rangkuman hasil liputan berita media massa atas menteri dinas", "Ikhtisar pencapaian kinerja keuangan selama tahun pelaporan", "Perhitungan laba rugi bersih komersial para debitur perbankan", "Daftar keluhan pelayanan publik yang masuk ke kotak saran", "Buku agenda kunjungan kerja luar daerah pimpinan lembaga"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Susunan CaLK Butir c (Ikhtisar Kinerja Keuangan)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>CaLK memuat <strong>ikhtisar pencapaian kinerja keuangan selama tahun pelaporan</strong> yang menggambarkan efektivitas penyerapan dana dan efisiensi pelaksanaan anggaran.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena ikhtisar kinerja menyandingkan indikator output kegiatan dengan pagu belanja untuk menilai akuntabilitas keuangan instansi.</p><p>Jawaban tepat: <strong>B. Ikhtisar pencapaian kinerja keuangan selama tahun pelaporan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Liputan media massa merupakan pemantauan kehumasan publikasi dinas.</li><li><strong>C</strong>: Laba rugi debitur perbankan adalah dokumen analisis kredit bank swasta.</li><li><strong>D</strong>: Kotak saran adalah instrumen penampung masukan pelayanan masyarakat.</li><li><strong>E</strong>: Agenda kunjungan kerja adalah dokumen protokoler sekretariat kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 77) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>ikhtisar pencapaian kinerja keuangan selama tahun pelaporan;</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Penjelasan mengenai basis akuntansi dan metode penyusutan aset yang dipilih diungkapkan pada...", "options": ["Buku panduan tata persuratan dinas sekretariat jenderal kantor", "Surat keputusan pengangkatan bendahara penerimaan kantor satker", "Dasar penyusunan laporan keuangan dan kebijakan akuntansi terpilih", "Daftar harga pasar wajar tanah dan bangunan dinas perumahan", "Kuitansi bukti setoran bunga pinjaman luar negeri perbankan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Susunan CaLK Butir d (Kebijakan Akuntansi)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>CaLK memuat <strong>informasi tentang dasar penyusunan laporan keuangan dan kebijakan-kebijakan akuntansi yang dipilih untuk diterapkan atas transaksi-transaksi dan kejadian-kejadian penting lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena bab kebijakan akuntansi menjelaskan prinsip pengakuan, pengukuran, dan metode penilaian (seperti garis lurus untuk penyusutan aset) yang diterapkan entitas.</p><p>Jawaban tepat: <strong>C. Dasar penyusunan laporan keuangan dan kebijakan akuntansi terpilih</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tata persuratan dinas mengatur tata kelola kearsipan dan surat dinas.</li><li><strong>B</strong>: SK bendahara adalah penetapan administratif pejabat pengelola kas.</li><li><strong>D</strong>: Daftar harga tanah digunakan oleh tim penilai dalam penetapan NJOP.</li><li><strong>E</strong>: Kuitansi bunga pinjaman adalah dokumen bukti transaksi perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 77) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>informasi tentang dasar penyusunan laporan keuangan dan kebijakankebijakan akuntansi yang dipilih untuk diterapkan atas transaksitransaksi dan kejadian-kejadian penting lainnya;</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Informasi wajib yang diatur dalam standar akuntansi namun tidak muat di lembar muka disajikan melalui...", "options": ["Pemberitahuan melalui selebaran brosur berkala kementerian", "Surat pernyataan lisan menteri dalam konferensi pers tahunan", "Papan baliho iklan layanan masyarakat di persimpangan jalan", "Pengungkapan informasi yang diharuskan oleh PSAP dalam CaLK", "Nota pembicaraan tidak resmi dengan pimpinan fraksi parlemen"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Susunan CaLK Butir e (Pengungkapan Mandatori PSAP)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>CaLK memuat <strong>pengungkapan informasi yang diharuskan oleh PSAP yang belum disajikan dalam lembar muka laporan keuangan</strong> guna memenuhi asas keterbukaan penuh (*full disclosure*).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena PSAP mewajibkan berbagai pengungkapan detail (seperti komitmen jangka panjang, peristiwa pasca tanggal neraca) yang hanya dapat ditampung secara naratif dalam CaLK.</p><p>Jawaban tepat: <strong>D. Pengungkapan informasi yang diharuskan oleh PSAP dalam CaLK</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Selebaran brosur berkala adalah media publikasi informasi masyarakat.</li><li><strong>B</strong>: Pernyataan lisan konferensi pers tidak memiliki kekuatan pembukuan hukum.</li><li><strong>C</strong>: Baliho iklan jalanan adalah sarana promosi sosialisasi dinas.</li><li><strong>E</strong>: Nota tidak resmi di luar saluran konstitusional melanggar asas akuntabilitas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 77) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pengungkapan informasi yang diharuskan oleh PSAP yang belum • disajikan dalam lembar muka laporan keuangan;</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 06", "difficulty": "C2 - Pemahaman", "question": "Pengungkapan pos aset dan kewajiban akibat penerapan akrual serta rekonsiliasi kas disajikan dalam...", "options": ["Daftar hadir peserta rapat pembahasan anggaran kementerian", "Buku register harian pencatatan surat masuk tata usaha dinas", "Buku kas pembantu penampungan penerimaan pajak daerah kota", "Surat ketetapan restitusi kelebihan bayar pajak wajib pajak", "Catatan atas Laporan Keuangan sebagai rincian rekonsiliasi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Susunan CaLK Butir f dan g (Rekonsiliasi Akrual-Kas)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>CaLK memuat <strong>pengungkapan informasi untuk pos-pos aset dan kewajiban yang timbul sehubungan dengan penerapan basis akrual atas pendapatan dan belanja dan rekonsiliasinya dengan penerapan basis kas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena tabel rekonsiliasi dalam CaLK menjembatani perbedaan antara surplus/defisit berbasis akrual (Laporan Operasional) dengan SiLPA berbasis kas (LRA).</p><p>Jawaban tepat: <strong>E. Catatan atas Laporan Keuangan sebagai rincian rekonsiliasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Daftar hadir rapat adalah bukti administratif pelaksanaan kegiatan dinas.</li><li><strong>B</strong>: Register surat masuk adalah sarana ketatausahaan kearsipan kantor.</li><li><strong>C</strong>: Buku kas pembantu pajak daerah adalah pembukuan kasir pemda.</li><li><strong>D</strong>: Surat ketetapan restitusi pajak adalah produk hukum pelayanan pajak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 77) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pengungkapan informasi untuk pos-pos aset dan kewajiban yang • timbul sehubungan dengan penerapan basis akrual atas pendapatan</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_pkn_p06_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_pkn_p06_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_pkn_p06_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Pengelolaan Keuangan Negara Paket 06";
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
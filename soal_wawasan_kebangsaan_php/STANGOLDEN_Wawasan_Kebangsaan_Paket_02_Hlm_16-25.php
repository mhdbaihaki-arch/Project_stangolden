<?php
/**
 * STANGOLDEN.exam • Wawasan Kebangsaan Paket 02 (Halaman 16–25)
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
// $DB->get_record("stgwk_p02_rank") => tabel fisik "mdlhv_stgwk_p02_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgwk_p02_rank';
const STG_REVIEW_T = 'stgwk_p02_rev';
const STG_ANNO_T = 'stgwk_p02_anno';
const STG_KEY = [1, 4, 0, 2, 3, 1, 0, 3, 2, 4, 2, 0, 3, 1, 4, 0, 2, 4, 1, 3, 0, 3, 1, 4, 2, 3, 0, 1, 4, 2, 3, 1, 4, 0, 2, 1, 4, 3, 2, 0, 4, 2, 0, 3, 1, 2, 4, 0, 3, 1, 0, 4, 2, 1, 3, 4, 1, 2, 0, 3, 1, 2, 3, 0, 4, 2, 3, 1, 4, 0, 3, 0, 4, 2, 1, 0, 3, 4, 1, 2, 4, 1, 2, 3, 0, 1, 4, 2, 3, 0, 2, 3, 0, 4, 1, 3, 2, 0, 4, 1];
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
            error_log('STANGOLDEN WK P02 HLM16-25 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN WK P02 HLM16-25: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Wawasan Kebangsaan Paket 02 (Hlm. 16–25) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 02 • Hlm. 16–25</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 02 • Hlm. 16–25</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Wawasan Kebangsaan Paket 02</h2>
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
const STG_LOCAL_KEY="stg_wk_p02_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Nilai material relatif lebih mudah diukur secara kuantitatif karena...", "options": ["Telah disepakati oleh seluruh kelompok pemuka agama", "Dapat diuji menggunakan instrumen alat pengukur fisik", "Memiliki sanksi hukum pidana yang mengikat mutlak", "Dihasilkan melalui perenungan budi pekerti manusia", "Tercantum secara eksplisit dalam anggaran dasar kantor"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Pengukuran Nilai Material</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai material</strong> secara relatif lebih mudah diukur dengan <strong>alat-alat pengukur fisik</strong> seperti gram, meter, meter persegi, atau liter.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Berdasarkan materi modul wawasan kebangsaan, nilai material memiliki wujud fisik yang nyata sehingga besaran dan kadarnya dapat ditentukan secara presisi menggunakan alat ukur standar teknis. Hal ini membedakannya secara fundamental dari nilai kerohanian.</p><p>Jawaban tepat: <strong>B. Dapat diuji menggunakan instrumen alat pengukur fisik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kesepakatan pemuka agama merupakan ranah pembentukan nilai keagamaan.</li><li><strong>C</strong>: Sanksi pidana merupakan ciri norma hukum positif bukan alat ukur nilai.</li><li><strong>D</strong>: Budi pekerti manusia digunakan untuk menakar nilai rohani.</li><li><strong>E</strong>: Anggaran dasar memuat aturan manajerial bukan pengukur nilai material.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Nilai material secara relatif lebih mudah diukur • alat pengukur berat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berbeda dengan nilai material, nilai rohani ditakar menggunakan instrumen...", "options": ["Instrumen pemeringkatan kinerja organisasi birokrasi", "Alat ukur volume dan luas bidang geografis", "Catatan pembukuan perbendaharaan belanja dinas", "Standar operasional prosedur pengawasan aparatur", "Budi nurani manusia yang bersifat mendalam"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tolok Ukur Pengujian Nilai Rohani</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai rohani</strong> tidak dapat diukur dengan alat ukur fisik, melainkan diukur menggunakan <strong>budi nurani manusia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Nilai kerohanian bersifat abstrak dan imaterial sehingga tidak memiliki satuan matematis. Secara hakiki, sarana pengukur nilai rohani terletak pada kedalaman budi nurani manusia yang merefleksikan kebaikan dan kepatutan batin.</p><p>Jawaban tepat: <strong>E. Budi nurani manusia yang bersifat mendalam</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemeringkatan kinerja menilai capaian output tugas pegawai.</li><li><strong>B</strong>: Alat ukur volume merupakan instrumen pengukur nilai material.</li><li><strong>C</strong>: Pembukuan perbendaharaan mengukur transaksi keuangan negara.</li><li><strong>D</strong>: SOP pengawasan mengatur tata cara teknis kepatuhan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sedangkan nilai rohani • budi nurani manusia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam menilai hal yang bersifat kerohanian, hati nurani manusia dibantu oleh...", "options": ["Indera, akal, perasaan, kehendak, dan keyakinan", "Pedoman tata kelola pengadaan barang dan jasa instansi", "Surat perintah penugasan serta kewenangan dinas struktural", "Daftar urut kepangkatan serta alokasi tunjangan kinerja", "Regulasi disiplin serta ancaman pemotongan penghasilan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alat Bantu Penilaian Kerohanian Manusia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penilaian terhadap hal kerohanian dilakukan menggunakan <strong>hati nurani dengan dibantu oleh indera, akal, perasaan, kehendak, dan oleh keyakinan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Manusia menimbang dimensi spiritual dan etis tidak hanya secara pasif, melainkan mengintegrasikan seluruh potensi eksistensialnya yang mencakup fungsi panca indera, penalaran rasional, kepekaan afektif, dorongan kehendak, dan keyakinan religius.</p><p>Jawaban tepat: <strong>A. Indera, akal, perasaan, kehendak, dan keyakinan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pedoman pengadaan barang mengatur prosedur logistik kantor.</li><li><strong>C</strong>: Surat tugas merupakan mandat administratif kedinasan resmi.</li><li><strong>D</strong>: Daftar urut kepangkatan memuat hierarki kepegawaian aparatur.</li><li><strong>E</strong>: Regulasi disiplin menegakkan ketaatan norma kedinasan publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>menggunakan hati nurani dengan dibantu • akal, perasaan, kehendak</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Bagi setiap manusia, kedudukan nilai berfungsi utama sebagai...", "options": ["Alat paksa eksekusi penegakan hukum tata usaha", "Dokumen pelaporan pertanggungjawaban program kerja", "Landasan, alasan, atau motivasi dalam berbuat", "Simbol formalitas upacara peringatan hari kenegaraan", "Syarat pembentukan badan otoritas khusus pemerintah"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Nilai bagi Perilaku Manusia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bagi manusia, <strong>nilai dijadikan landasan, alasan atau motivasi dalam segala perbuatannya</strong> sehari-hari.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Fungsi aksiologis nilai berperan sebagai penggerak fundamental perilaku manusia. Sebelum melakukan suatu tindakan nyata, manusia selalu mendasarkan keputusannya pada tata nilai yang dianutnya sebagai alasan pembenar moral.</p><p>Jawaban tepat: <strong>C. Landasan, alasan, atau motivasi dalam berbuat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Alat paksa hukum merupakan sifat sanksi yuridis eksternal.</li><li><strong>B</strong>: Dokumen pelaporan adalah instrumen akuntabilitas birokrasi.</li><li><strong>D</strong>: Simbol formalitas bukan tujuan hakiki pelembagaan nilai.</li><li><strong>E</strong>: Badan otoritas dibentuk berdasarkan ketentuan undang-undang negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Bagi manusia nilai dijadikan landasan • motivasi dalam segala perbuatannya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam praktik kehidupan bermasyarakat, nilai-nilai dijabarkan dalam bentuk...", "options": ["Statistik keuangan perbendaharaan negara komparatif", "Struktur hierarki eselonisasi kementerian lembaga", "Sistem lelang jabatan terbuka pimpinan tinggi madya", "Norma yang memuat perintah, anjuran, atau celaan", "Matriks analisis beban kerja jabatan pelaksana teknis"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penjabaran Nilai ke Bentuk Norma</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam pelaksanaannya, <strong>nilai-nilai dijabarkan dalam bentuk norma (normatif)</strong> yang memuat <strong>perintah/keharusan, anjuran, larangan, atau celaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Nilai yang bersifat abstrak memerlukan wujud operasional agar dapat ditaati bersama. Norma bertindak sebagai instrumen penjabaran konkret nilai dengan memberikan arahan mana perilaku yang wajib dijalankan, dianjurkan, maupun yang dicela.</p><p>Jawaban tepat: <strong>D. Norma yang memuat perintah, anjuran, atau celaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Statistik keuangan memuat data fiskal operasional pemerintah.</li><li><strong>B</strong>: Hierarki eselon menggambarkan tingkatan kepemimpinan birokrasi.</li><li><strong>C</strong>: Lelang jabatan terbuka merupakan mekanisme seleksi aparatur.</li><li><strong>E</strong>: Analisis beban kerja mengukur formasi kebutuhan pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>nilai-nilai dijabarkan dalam bentuk norma • merupakan suatu perintah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Penerapan konkret nilai kebenaran dalam etika penugasan aparatur menuntut...", "options": ["Penyesuaian kebenaran data mengikuti kehendak pimpinan satker", "Pelaksanaan hal benar serta penolakan atas ketidakbenaran", "Pengalihan tanggung jawab pemeriksaan kepada bawahan langsung", "Penyelarasan laporan keuangan demi memenuhi target serapan", "Pemberian perlakuan khusus kepada rekan satu angkatan kerja"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Nilai Kebenaran dalam Norma</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nilai kebenaran harus dilaksanakan</strong> dan segala sesuatu yang <strong>tidak benar, tidak indah, dan tidak baik dilarang atau dicela</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Standar moralitas menuntut aparatur berpegang teguh pada fakta obyektif. Nilai kebenaran mengamanatkan bahwa tindakan kedinasan wajib merealisasikan kejujuran serta melarang dan mencela segala bentuk manipulasi data.</p><p>Jawaban tepat: <strong>B. Pelaksanaan hal benar serta penolakan atas ketidakbenaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menyesuaikan data demi atasan melanggar asas akuntabilitas dinas.</li><li><strong>C</strong>: Melempar tanggung jawab bertentangan dengan integritas profesional.</li><li><strong>D</strong>: Memanipulasi laporan demi serapan anggaran melanggar tata kelola.</li><li><strong>E</strong>: Memberi perlakuan khusus angkatan mencederai keadilan objektif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Nilai kebenaran harus dilaksanakan • segala sesuatu yang tidak benar</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Peran hakiki nilai dalam eksistensi peradaban manusia adalah sebagai...", "options": ["Dasar pedoman yang menentukan arah kehidupan manusia", "Penghalang mobilitas penugasan lintas wilayah kementerian", "Bahan penyusunan anggaran belanja modal perkantoran", "Alasan penundaan penetapan keputusan mutasi kepegawaian", "Pembatas ruang gerak inovasi teknologi digital birokrasi"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Hakiki Nilai bagi Manusia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Secara fundamental, <strong>nilai berperan sebagai dasar pedoman yang menentukan kehidupan setiap manusia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Nilai memberikan arah orientasi hidup, kompas moralitas, dan standar kelayakan bagi masyarakat. Tanpa adanya tata nilai, kehidupan manusia akan kehilangan pedoman dasar dalam membedakan hal yang luhur dan yang tercela.</p><p>Jawaban tepat: <strong>A. Dasar pedoman yang menentukan arah kehidupan manusia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Nilai organisasi tidak menghambat mobilitas penugasan dinas.</li><li><strong>C</strong>: Anggaran belanja kantor disusun berdasar analisis standar biaya.</li><li><strong>D</strong>: Keputusan mutasi mengacu pada kualifikasi pola karier pegawai.</li><li><strong>E</strong>: Inovasi digital justru didorong oleh nilai adaptif organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>nilai berperan sebagai dasar pedoman • menentukan kehidupan setiap manusia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Gagasan tentang cita negara integralistik disampaikan dalam sidang BPUPKI oleh...", "options": ["Mr. Achmad Soebardjo pada sidang penetapan naskah proklamasi", "Mr. A.A. Maramis dalam sidang perancangan hukum perdata", "Drs. Mohammad Hatta dalam rapat penyusunan anggaran negara", "Prof. R. Soepomo dalam pidato tanggal 31 Mei 1945", "Abikoesno Tjokrosoejoso pada musyawarah panitia perancang"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pencetus Gagasan Cita Negara Integralistik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam pidato tanggal <strong>31 Mei 1945</strong> di depan rapat BPUPKI, <strong>Prof. R. Soepomo</strong> mengemukakan gagasan tentang <strong>cita negara integralistik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Prof. Soepomo meletakkan landasan konseptual mengenai negara integralistik pada hari ketiga sidang pertama BPUPKI. Pandangan ini menawarkan alternatif filosofis bentuk negara yang bersatu padu dengan seluruh rakyatnya.</p><p>Jawaban tepat: <strong>D. Prof. R. Soepomo dalam pidato tanggal 31 Mei 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mr. Achmad Soebardjo berperan penting dalam peristiwa Rengasdengklok.</li><li><strong>B</strong>: Mr. A.A. Maramis berkontribusi dalam perumusan Piagam Jakarta.</li><li><strong>C</strong>: Bung Hatta berpidato pada perumusan asas perekonomian nasional.</li><li><strong>E</strong>: Abikoesno memimpin pembahasan panitia pembelaan tanah air.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dalam pidato tanggal 31 Mei 1945 • Soepomo</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Menurut Prof. Soepomo, dasar dari suatu sistem pemerintahan sangat tergantung pada...", "options": ["Besarnya jumlah anggaran belanja pertahanan negara", "Banyaknya kantor cabang perwakilan dinas di daerah", "Cita negaranya atau staatsidee yang melandasinya", "Tingkat persaingan kekuatan ekonomi pasar global", "Jumlah aliansi pakta pertahanan militer internasional"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fondasi Sistem Pemerintahan Menurut Soepomo</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dasar sistem suatu pemerintahan <strong>tergantung pada cita negaranya, pada staatsidee</strong>, pada pemahaman tentang negara yang hendak digunakan untuk membangun negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Soepomo menegaskan bahwa struktur pemerintahan bukan sekadar teknik administrasi, melainkan cerminan dari falsafah dasar atau staatsidee negara. Cita negara menentukan bagaimana kedaulatan, kepemimpinan, dan hak warga negara diatur.</p><p>Jawaban tepat: <strong>C. Cita negaranya atau staatsidee yang melandasinya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Anggaran belanja pertahanan adalah instrumen operasional militer.</li><li><strong>B</strong>: Jumlah kantor cabang daerah adalah aspek distribusi kelembagaan.</li><li><strong>D</strong>: Persaingan pasar global adalah faktor dinamika eksternal ekonomi.</li><li><strong>E</strong>: Aliansi pertahanan militer adalah pilihan diplomasi luar negeri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dasar sistem suatu pemerintahan tergantung pada cita • pada staatsidee</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pemahaman tentang cita negara (staatsidee) menjadi titik tolak BPUPKI dalam rangka...", "options": ["Membentuk perserikatan perbankan komersial se-Asia Timur", "Menentukan tarif pajak ekspor impor komoditas kelautan", "Menyusun regulasi kenaikan pangkat reguler dinas aparatur", "Mengadopsi secara mutlak sistem monarki konstitusional asing", "Membangun fondasi tata negara Indonesia yang akan dibentuk"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pemahaman Staatsidee Indonesia Merdeka</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemahaman tentang negara (<strong>staatsidee</strong>) dijadikan fondasi pemikiran yang hendak digunakan untuk <strong>membangun negara Indonesia merdeka</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Para pendiri bangsa menyadari pentingnya konsepsi teoritis sebelum merumuskan pasal-pasal konstitusi. Staatsidee berfungsi sebagai cetak biru filosofis dalam membangun arsitektur negara Indonesia yang berdaulat.</p><p>Jawaban tepat: <strong>E. Membangun fondasi tata negara Indonesia yang akan dibentuk</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perserikatan perbankan bukan agenda pokok perumusan dasar negara.</li><li><strong>B</strong>: Tarif pajak komoditas merupakan instrumen fiskal operasional.</li><li><strong>C</strong>: Regulasi pangkat aparatur adalah aturan kepegawaian teknis.</li><li><strong>D</strong>: Pendiri bangsa menolak adopsi mutlak sistem monarki asing.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 16) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pada pemahaman tentang negara • membangun negara Indonesia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Teori integralistik yang dikutip dalam pidato Prof. Soepomo diajarkan oleh tokoh...", "options": ["Karl Marx, Friedrich Engels, dan Vladimir Lenin", "John Locke, Montesquieu, dan Jean-Jacques Rousseau", "B. Spinoza, Adam Muller, dan G.W.F. Hegel", "Thomas Hobbes, Niccolo Machiavelli, dan Francis Bacon", "Adam Smith, David Ricardo, dan John Stuart Mill"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tokoh Pencetus Teori Integralistik Dunia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Teori integralistik dalam literatur filsafat kenegaraan diajarkan oleh para pemikir terkemuka antara lain <strong>B. Spinoza, Adam Muller, dan Hegel</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Dalam pidatonya tanggal 31 Mei 1945, Soepomo mengkaji teori negara dari para pemikir Eropa tersebut untuk menjelaskan bahwa negara memandang seluruh masyarakat sebagai satu kesatuan organis yang utuh dan tidak terpecah-pecah.</p><p>Jawaban tepat: <strong>C. B. Spinoza, Adam Muller, dan G.W.F. Hegel</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Karl Marx dan Friedrich Engels adalah pencetus ideologi marxisme.</li><li><strong>B</strong>: John Locke dan Montesquieu adalah tokoh teori pemisahan kekuasaan.</li><li><strong>D</strong>: Thomas Hobbes dan Machiavelli dikenal lewat teori kekuasaan mutlak.</li><li><strong>E</strong>: Adam Smith dan David Ricardo adalah pemikir ekonomi klasik liberal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 17) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>teori integralistik yang diajarkan oleh • B. Spinosa, Adam Muller, Hegel</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Menurut teori integralistik, tujuan utama pembentukan negara adalah...", "options": ["Menjamin kepentingan masyarakat seluruhnya sebagai kesatuan", "Melindungi monopoli modal korporasi konglomerasi swasta", "Memperluas wilayah pengaruh kekuasaan militer ke luar negeri", "Mengutamakan hegemoni politik golongan pemenang pemilu", "Memusatkan seluruh aset properti warga ke tangan penguasa"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Negara Menurut Aliran Integralistik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menurut teori integralistik, <strong>negara tidak untuk menjamin kepentingan seseorang atau golongan, akan tetapi menjamin kepentingan masyarakat seluruhnya sebagai kesatuan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Teori integralistik menolak paham individualisme yang membela kepentingan orang per orang dan paham golongan yang mengistimewakan kelompok tertentu. Negara hadir untuk memayungi dan menjamin keselamatan seluruh rakyat secara integral.</p><p>Jawaban tepat: <strong>A. Menjamin kepentingan masyarakat seluruhnya sebagai kesatuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Melindungi monopoli modal bertentangan dengan keadilan sosial.</li><li><strong>C</strong>: Ekspansi pengaruh militer adalah corak negara imperialis.</li><li><strong>D</strong>: Mengutamakan hegemoni golongan merupakan ciri paham kelas/golongan.</li><li><strong>E</strong>: Sentralisasi properti sepihak adalah ciri komunisme ekstrem.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 17) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>negara tidak untuk menjamin kepentingan • menjamin kepentingan masyarakat seluruhnya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Prof. Soepomo mendefinisikan negara integralistik sebagai susunan masyarakat yang...", "options": ["Terpisah-pisah dalam blok persaingan kepentingan kelas", "Dikelola seperti perseroan bisnis demi keuntungan dividen", "Menyerahkan kedaulatan mutlak kepada parlemen tunggal", "Saling berhubungan erat membentuk kesatuan organis", "Membatasi keterlibatan rakyat dalam urusan ketatanegaraan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Negara Organis Menurut Soepomo</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Negara adalah suatu <strong>susunan masyarakat yang integral, segala golongan, bagian, dan anggotanya berhubungan erat satu sama lain dan merupakan kesatuan masyarakat yang organis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Konsepsi organis memandang negara layaknya tubuh manusia, di mana setiap organ atau bagian saling terhubung erat dan bekerja sama demi kelangsungan hidup bersama. Tidak ada pemisahan artifisial antara rakyat dan struktur negara.</p><p>Jawaban tepat: <strong>D. Saling berhubungan erat membentuk kesatuan organis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Persaingan kepentingan kelas adalah pandangan marxisme-komunisme.</li><li><strong>B</strong>: Pengelolaan negara seperti korporasi adalah paham kapitalistis.</li><li><strong>C</strong>: Kedaulatan mutlak di parlemen adalah sistem parlementer murni.</li><li><strong>E</strong>: Membatasi rakyat bertentangan dengan prinsip negara kesatuan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 17) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Negara ialah suatu susunan masyarakat yang integral • kesatuan masyarakat yang organis</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Staatsidee integralistik dinilai paling cocok bagi bangsa Indonesia karena...", "options": ["Memudahkan pemerintah membatasi kebebasan berpendapat publik", "Sesuai dengan keistimewaan sifat dan corak masyarakat Indonesia", "Menghilangkan kewajiban negara mengayomi hak-hak warga miskin", "Mendapat dukungan diplomatik dari blok negara-negara adidaya", "Menyeragamkan seluruh tradisi daerah menjadi satu budaya baku"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesesuaian Staatsidee dengan Corak Bangsa Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pendirian negara Indonesia harus berdasar <strong>aliran pikiran (Staatsidee) negara yang integralistik</strong> karena hal itu <strong>sesuai dengan keistimewaan sifat dan corak masyarakat Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Soepomo menggali kepribadian asli bangsa Indonesia yang menjunjung tinggi semangat gotong royong, kebersamaan, dan kekeluargaan. Staatsidee integralistik merupakan sublimasi dari corak sosiologis asli masyarakat Nusantara.</p><p>Jawaban tepat: <strong>B. Sesuai dengan keistimewaan sifat dan corak masyarakat Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Membatasi kebebasan berpendapat adalah praktik rezim otoriter.</li><li><strong>C</strong>: Menghapus pengayoman warga miskin bertentangan dengan keadilan.</li><li><strong>D</strong>: Dukungan adidaya bukan dasar penentuan falsafah bangsa.</li><li><strong>E</strong>: Penyeragaman budaya mematikan asas kebinekaan nusantara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 17) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sesuai dengan keistimewaan sifat dan corak masyarakat Indonesia • negara yang bersatu dengan seluruh rakyatnya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Karakteristik utama negara integralistik dalam memandang keberagaman adalah...", "options": ["Mengutamakan kepentingan golongan mayoritas dalam birokrasi", "Menghapuskan eksistensi kelompok minoritas secara bertahap", "Mewajibkan warga memilih satu partai politik tunggal resmi", "Membagi kekuasaan kementerian kepada kelompok konglomerat", "Bersatu dengan seluruh rakyat dan mengatasi semua golongan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sifat Mengatasi Semua Golongan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Negara integralistik adalah <strong>negara yang bersatu dengan seluruh rakyatnya, yang mengatasi semua golongan dalam lapangan apapun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Posisi negara dalam konsepsi integralistik berdiri netral dan mengatasi segala bentuk pengelompokan suku, agama, ras, maupun strata ekonomi. Negara tidak boleh menjadi instrumen kekuasaan bagi satu golongan tertentu.</p><p>Jawaban tepat: <strong>E. Bersatu dengan seluruh rakyat dan mengatasi semua golongan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengutamakan mayoritas mencederai prinsip persatuan kebangsaan.</li><li><strong>B</strong>: Menghapus minoritas bertentangan dengan HAM dan kemanusiaan.</li><li><strong>C</strong>: Partai tunggal merupakan karakteristik negara totaliter tertutup.</li><li><strong>D</strong>: Membagi kekuasaan ke pemodal adalah bentuk oligarki kekuasaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 17) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>negara yang bersatu dengan seluruh rakyatnya • dalam lapangan apapun</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam aliran integralistik, kepala negara dan badan pemerintah dituntut...", "options": ["Bertindak sebagai figur pemimpin sejati yang mengayomi", "Menumpuk kekayaan pribadi guna memperkuat dinasti keluarga", "Mengekang aspirasi rakyat demi stabilitas semu keamanan", "Memperluas hak istimewa kekebalan hukum bagi aparatur", "Menjalankan roda pemerintahan atas pesanan korporasi swasta"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter Pemimpin dalam Aliran Integralistik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam aliran integralistik, <strong>kepala negara dan lain-lain badan pemerintah harus bersifat pemimpin yang sejati</strong> dan tidak mempunyai kepentingan sendiri.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Kepemimpinan kenegaraan berorientasi integralistik memposisikan pemimpin sebagai pelayan dan pengayom seluruh rakyat. Pemimpin sejati menyatukan jiwanya dengan kehendak rakyat dan bebas dari vested interest pribadi.</p><p>Jawaban tepat: <strong>A. Bertindak sebagai figur pemimpin sejati yang mengayomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Menumpuk kekayaan pribadi adalah bentuk tindak pidana korupsi.</li><li><strong>C</strong>: Mengekang aspirasi mencerminkan pola kepemimpinan tiranik.</li><li><strong>D</strong>: Hak kebal hukum aparatur melanggar asas equality before the law.</li><li><strong>E</strong>: Pemerintahan pesanan pemodal bertentangan dengan kedaulatan rakyat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 17) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kepala negara dan lain-lain • harus bersifat pemimpin yang sejati</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Prinsip kekuasaan negara menurut pandangan Prof. Soepomo menegaskan bahwa...", "options": ["Kepala negara memegang hak mutlak atas seluruh tanah warga", "Pemerintah pusat berhak mengubah batas wilayah tanpa aturan", "Negara tidak bertindak maha kuasa demi kepentingan sendiri", "Aparatur militer berada di atas hukum peradilan umum sipil", "Kekuasaan yudikatif berada di bawah kendali menteri kabinet"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Kekuasaan Negara Integralistik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Negara tidak bertindak sebagai seseorang yang maha kuasa yang mempunyai kepentingan sendiri</strong>, melainkan menyatu utuh dengan kehendak rakyatnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Konsepsi Soepomo mencegah negara berkembang menjadi kekuatan absolut yang menindas. Negara bukan entitas terpisah yang mengejar kepentingan egoistis penguasa, melainkan manifestasi dari kehendak bersatu masyarakat.</p><p>Jawaban tepat: <strong>C. Negara tidak bertindak maha kuasa demi kepentingan sendiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hak atas tanah warga dijamin dan dilindungi oleh hukum negara.</li><li><strong>B</strong>: Pengubahan batas wilayah diatur secara konstitusional dalam undang-undang.</li><li><strong>D</strong>: Prinsip negara hukum menempatkan semua warga setara di hadapan hukum.</li><li><strong>E</strong>: Kekuasaan kehakiman bersifat merdeka bebas dari intervensi eksekutif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 17) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sebagai seseorang yang maha kuasa • mempunyai kepentingan sendiri</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Hubungan antara negara (staat) dan individu dalam aliran integralistik ditandai...", "options": ["Konflik permanen memperebutkan alokasi sumber penerimaan", "Persaingan bebas dalam menentukan tata hukum dan peradilan", "Pemisahan ketat hak individu dari campur tangan otoritas umum", "Peniadaan hak hidup warga negara oleh kekuasaan tunggal raja", "Ketiadaan pertentangan karena individu adalah bagian organik negara"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Negara dan Individu dalam Integralistik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada dasarnya menurut aliran integralistik, <strong>tidak ada pertentangan antara staat dan individu, karena individu tidak lain melainkan suatu bagian organik dari staat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Berbeda dengan pandangan Barat yang sering mempertentangkan hak asasi individu versus kekuasaan negara, teori integralistik melihat keharmonisan dwitunggal di mana kesejahteraan individu melekat pada kemakmuran dan martabat negara.</p><p>Jawaban tepat: <strong>E. Ketiadaan pertentangan karena individu adalah bagian organik negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Konflik permanen adalah asumsi teori pertentangan kelas marxisme.</li><li><strong>B</strong>: Persaingan bebas peradilan merusak kepastian hukum ketatanegaraan.</li><li><strong>C</strong>: Pemisahan ketat hak individu adalah ciri liberalisme klasik.</li><li><strong>D</strong>: Peniadaan hak hidup adalah karakteristik monarki absolut tirani.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 17) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tidak ada pertentangan antara staat dan • suatu bagian organik dari staat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pernyataan bahwa negara bukan 'raksasa politik' di luar kemerdekaan warga bermakna...", "options": ["Negara tidak memiliki angkatan bersenjata dan kepolisian", "Negara tidak berdiri sebagai kekuatan asing penindas warganya", "Warga negara terbebas dari seluruh kewajiban kontribusi kas negara", "Pemerintah tidak berwenang membuat peraturan perundang-undangan", "Kedaulatan hukum sepenuhnya diserahkan kepada pengadilan adat"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Negara Bukan Raksasa Politik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Staat <strong>bukan suatu badan kekuasaan atau raksasa politik yang berada di luar lingkungan suasana kemerdekaan seseorang</strong>, melainkan ruang hidup bersama yang membahagiakan warganya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Metafora 'raksasa politik' digunakan Soepomo untuk menolak konsep negara Leviathan (kekuasaan monster menakutkan). Negara integralistik hadir sebagai rumah besar bersama yang melindungi hak dan kemerdekaan warganya dari dalam.</p><p>Jawaban tepat: <strong>B. Negara tidak berdiri sebagai kekuatan asing penindas warganya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Negara berdaulat wajib memiliki angkatan bersenjata resmi.</li><li><strong>C</strong>: Membayar pajak adalah kewajiban konstitusional setiap warga negara.</li><li><strong>D</strong>: Pemerintah bersama legislatif berwenang membentuk undang-undang.</li><li><strong>E</strong>: Peradilan nasional berada dalam satu atap Mahkamah Agung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 17) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>staat bukan suatu badan kekuasaan atau raksasa politik • suasana kemerdekaan seseorang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam sistem hukum negara integralistik, hukum positif dirancang untuk...", "options": ["Menghapus hukum adat yang hidup di tengah masyarakat desa", "Menjadikan hukum agama tertentu sebagai hukum pidana nasional", "Memberikan hak imunitas hukum bagi kelompok penguasa politik", "Memperhatikan keistimewaan golongan yang bermacam-macam", "Mewajibkan penggunaan satu dialek bahasa daerah secara seragam"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perlindungan Hukum terhadap Keistimewaan Golongan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam negara integralistik, <strong>hukum negara akan memperhatikan segala keistimewaan golongan yang bermacam-macam di tanah air kita</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Soepomo menegaskan bahwa persatuan integralistik tidak membunuh keberagaman (*unity in diversity*). Hukum negara justru mengayomi dan menghormati hak-hak tradisional serta keunikan kultural berbagai suku dan golongan nusantara.</p><p>Jawaban tepat: <strong>D. Memperhatikan keistimewaan golongan yang bermacam-macam</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hukum adat tetap diakui eksistensinya sepanjang hidup dan sesuai asas NKRI.</li><li><strong>B</strong>: Indonesia bukan negara agama yang menerapkan satu hukum keagamaan.</li><li><strong>C</strong>: Imunitas penguasa mencederai keadilan hukum dan konstitusionalisme.</li><li><strong>E</strong>: Bahasa Indonesia adalah bahasa persatuan tanpa menghapus bahasa daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 17) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>hukum negara akan memperhatikan • keistimewaan golongan yang bermacam-macam</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Secara etimologis, kata ideologi berakar dari bahasa Yunani 'idein' yang bermakna...", "options": ["Melihat suatu objek atau realitas gagasan", "Mendengar sabda dan ajaran para leluhur", "Menuliskan aturan tertulis di prasasti batu", "Memerintahkan bawahan dalam tugas kedinasan", "Merasakan kesedihan atas derita sesama warga"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etimologi Kata Ideologi dari Bahasa Yunani</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ideologi berasal dari kata Yunani <strong>Idein yang berarti melihat</strong>, atau <strong>Idea</strong> yang berarti raut muka, perawakan, gagasan, buah pikiran, dan <strong>Logia</strong> yang berarti ajaran.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Berdasarkan asal usul linguistiknya, akar kata 'idein' dalam bahasa Yunani kuno merujuk pada aktivitas persepsi visual atau 'melihat', yang kemudian berkembang menjadi konsep mengenai gagasan atau cita-cita mental yang dipandang akal.</p><p>Jawaban tepat: <strong>A. Melihat suatu objek atau realitas gagasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mendengar berhubungan dengan indera pendengaran bukan akar kata idein.</li><li><strong>C</strong>: Menuliskan aturan adalah ranah kodifikasi perundang-undangan.</li><li><strong>D</strong>: Memerintah bawahan berkaitan dengan fungsi komando administrasi.</li><li><strong>E</strong>: Merasakan kesedihan adalah respon afektif empati emosional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Ideologi berasal dari kata Yunani • Idein, yang berarti melihat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan perpaduan unsur pembentuk katanya, ideologi didefinisikan sebagai...", "options": ["Himpunan teknik negosiasi sengketa perdagangan dunia", "Pedoman tata cara pembagian bantuan subsidi pangan", "Petunjuk teknis pengoperasian sistem teknologi informasi", "Ajaran atau ilmu tentang gagasan dan buah pikiran", "Daftar susunan formasi jabatan karier kepegawaian"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Harfiah Ideologi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan perpaduan unsur idea dan logia, <strong>ideologi adalah ajaran atau ilmu tentang gagasan dan buah pikiran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Penggabungan kata idea (gagasan/buah pikiran) dan logia (ajaran/ilmu) membentuk pemahaman harfiah bahwa ideologi merupakan disiplin atau sistem pemikiran terstruktur mengenai cita-cita mendasar yang diyakini kebenarannya.</p><p>Jawaban tepat: <strong>D. Ajaran atau ilmu tentang gagasan dan buah pikiran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Teknik negosiasi sengketa adalah instrumen diplomasi internasional.</li><li><strong>B</strong>: Tata cara pembagian subsidi adalah kebijakan operasional sosial.</li><li><strong>C</strong>: Petunjuk sistem informasi adalah manual operasional teknologi.</li><li><strong>E</strong>: Susunan formasi jabatan adalah dokumen teknis kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>ideologi adalah ajaran • tentang gagasan dan buah pikiran</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam kehidupan kenegaraan, ideologi diartikan secara substantif sebagai...", "options": ["Maklumat otoriter penguasa yang diberlakukan tanpa konsultasi publik", "Konsensus mayoritas warga mengenai nilai dasar pendirian negara", "Hasil perjanjian dagang bilateral dengan negara tetangga kawasan", "Dokumen rencana kerja tahunan satuan kerja kementerian negara", "Koleksi manuskrip kuno peninggalan kerajaan maritim nusantara"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Ideologi dalam Kehidupan Kenegaraan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ideologi dalam kehidupan kenegaraan dapat diartikan sebagai <strong>suatu konsensus mayoritas warga negara tentang nilai-nilai dasar yang ingin diwujudkan dengan mendirikan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Ideologi negara bukan paksaan elite, melainkan kesepakatan bersama (*modus vivendi*) seluruh elemen bangsa. Konsensus ini memuat nilai-nilai fundamental yang disepakati sebagai tujuan bersama pendirian bangunan negara.</p><p>Jawaban tepat: <strong>B. Konsensus mayoritas warga mengenai nilai dasar pendirian negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perintah sepihak mencerminkan paham kekuasaan otoriter tertutup.</li><li><strong>C</strong>: Perjanjian dagang adalah instrumen kerja sama ekonomi luar negeri.</li><li><strong>D</strong>: Rencana kerja tahunan adalah dokumen manajerial APBN/DIPA.</li><li><strong>E</strong>: Koleksi manuskrip kuno adalah benda cagar budaya sejarah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>konsensus mayoritas warga negara • tentang nilai-nilai dasar yang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Hakikat terdalam dari suatu ideologi negara mencerminkan...", "options": ["Ambisi geopolitik para perwira militer mendominasi kawasan", "Dorongan aparat penegak hukum memperbanyak penjatuhan vonis", "Kepentingan pemegang modal menanamkan modal investasi asing", "Keinginan birokrat mempertahankan privilese fasilitas kedinasan", "Pikiran dan hasrat terdalam warga sebagai fondasi negara"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Ideologi sebagai Hasrat Terdalam Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ideologi merupakan <strong>pikiran-pikiran terdalam, hasrat terdalam warga negaranya, untuk di atasnya didirikan suatu negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Ideologi kenegaraan berakar kuat pada sanubari bangsa yang paling dalam. Ia menampung cita-cita keadilan, kemerdekaan, dan kemakmuran yang dirindukan oleh seluruh rakyat sejak masa penjajahan.</p><p>Jawaban tepat: <strong>E. Pikiran dan hasrat terdalam warga sebagai fondasi negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ambisi geopolitik militer mencirikan negara ekspansionis.</li><li><strong>B</strong>: Penjatuhan vonis adalah tugas peradilan menegakkan hukum.</li><li><strong>C</strong>: Kepentingan pemegang modal adalah watak kapitalisme liberal.</li><li><strong>D</strong>: Privilese birokrat bertentangan dengan semangat pelayanan publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pikiran-pikiran terdalam, hasrat terdalam • atasnya didirikan suatu negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Menurut Mubyarto, ideologi didefinisikan sebagai himpunan...", "options": ["Peraturan teknis pengadaan logistik barang perkantoran", "Instruksi dinas rahasia pimpinan komando angkatan bersenjata", "Doktrin, kepercayaan, dan simbol pedoman mencapai tujuan", "Laporan berkala hasil pemeriksaan keuangan kas perbendaharaan", "Kumpulan dalil matematika untuk menghitung defisit anggaran"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Ideologi Menurut Mubyarto</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Mubyarto</strong> mengartikan bahwa ideologi adalah <strong>sejumlah doktrin, kepercayaan, dan simbol-simbol sekelompok masyarakat atau satu bangsa yang menjadi pegangan dan pedoman karya untuk mencapai tujuan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Definisi Mubyarto menonjolkan tiga unsur utama ideologi: doktrin keyakinan, sistem nilai kepercayaan, dan simbol pemersatu yang menjadi panduan praksis bagi bangsa dalam berkarya mencapai tujuan bersama.</p><p>Jawaban tepat: <strong>C. Doktrin, kepercayaan, dan simbol pedoman mencapai tujuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aturan pengadaan adalah regulasi teknis logistik kantor.</li><li><strong>B</strong>: Instruksi rahasia militer adalah dokumen strategi pertahanan.</li><li><strong>D</strong>: Laporan pemeriksaan adalah wewenang akuntabilitas BPK.</li><li><strong>E</strong>: Dalil matematika adalah instrumen analisis hitung statistik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Mubyarto mengartikan bahwa ideologi adalah • kepercayaan, dan simbol-simbol</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Prof. Padmo Wahjono mengartikan ideologi sebagai suatu...", "options": ["Rancangan peraturan perundang-undangan penarikan pajak", "Perjanjian perdamaian pasca gencatan senjata medan perang", "Sistem komando terpusat pembagian aset perkebunan rakyat", "Kesatuan yang bulat dan utuh dari ide-ide dasar", "Kumpulan petunjuk teknis pelaksanaan lelang barang sitaan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Ideologi Menurut Padmo Wahjono</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Padmo Wahjono</strong> mengartikan ideologi sebagai <strong>kesatuan yang bulat dan utuh dari ide-ide dasar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Padmo Wahjono menekankan keutuhan sistemik suatu ideologi. Ide-ide dasar yang terkandung di dalamnya saling mengunci, tidak berdiri sendiri, dan membentuk satu weltanschauung utuh dalam mengarahkan kehidupan bernegara.</p><p>Jawaban tepat: <strong>D. Kesatuan yang bulat dan utuh dari ide-ide dasar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rancangan undang-undang pajak adalah instrumen hukum fiskal.</li><li><strong>B</strong>: Perjanjian pasca perang adalah produk diplomasi traktat.</li><li><strong>C</strong>: Sistem komando aset perkebunan adalah model ekonomi sosialis.</li><li><strong>E</strong>: Petunjuk lelang sitaan adalah aturan operasional perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Padmo Wahjono mengartikan ideologi sebagai • dan utuh dari ide-ide dasar</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Sebagai konsekuensi dari pandangan hidup bangsa, ideologi hadir berupa...", "options": ["Seperangkat tata nilai yang dicita-citakan untuk direalisasikan", "Standar upah minimum tenaga kerja pada sektor industri", "Daftar sanksi administratif bagi pelanggar etika penugasan", "Alokasi pagu indikatif belanja operasional kementerian dinas", "Mekanisme pengangkatan pegawai honorer menjadi aparatur sipil"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Ideologi dan Pandangan Hidup</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ideologi merupakan suatu konsekuensi daripada pandangan hidup bangsa, dan akan <strong>berupa seperangkat tata nilai yang dicita-citakan akan direalisir di dalam kehidupan berkelompok</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Pandangan hidup bangsa bertransformasi menjadi ideologi saat nilai-nilai luhur tersebut diformulasikan sebagai cita-cita bersama yang hendak diwujudkan secara nyata dalam kehidupan sosial, politik, dan hukum berkelompok.</p><p>Jawaban tepat: <strong>A. Seperangkat tata nilai yang dicita-citakan untuk direalisasikan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Standar upah minimum adalah kebijakan regulasi ketenagakerjaan.</li><li><strong>C</strong>: Sanksi administratif adalah instrumen penegakan disiplin dinas.</li><li><strong>D</strong>: Pagu belanja operasional adalah angka perencanaan anggaran.</li><li><strong>E</strong>: Mekanisme pengangkatan pegawai adalah prosedur manajemen SDM.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>konsekuensi daripada pandangan hidup bangsa • seperangkat tata nilai yang dicita-citakan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam tata politik bangsa Indonesia, kedudukan Pancasila mencerminkan...", "options": ["Alat kekuasaan elite untuk mempertahankan jabatan kabinet", "Seperangkat nilai terpadu sebagai acuan kehidupan politik", "Sistem persaingan modal bebas tanpa batas peraturan negara", "Dasar hukum penundaan pemilihan umum berkala kedinasan", "Dokumen pembatasan partisipasi masyarakat dalam pemilu"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Pancasila dalam Kehidupan Politik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila sebagai ideologi <strong>mencerminkan seperangkat nilai terpadu dalam kehidupan politik bangsa Indonesia</strong>, yaitu sebagai tata nilai yang dipergunakan sebagai acuan hidup.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Politik kenegaraan di Indonesia wajib berakar pada etika Pancasila. Nilai ketuhanan, kemanusiaan, persatuan, musyawarah, dan keadilan menjadi pembimbing seluruh proses pembuatan kebijakan dan penyelenggaraan kekuasaan umum.</p><p>Jawaban tepat: <strong>B. Seperangkat nilai terpadu sebagai acuan kehidupan politik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menjadikan ideologi alat elite mencirikan praktik manipulasi politik.</li><li><strong>C</strong>: Persaingan modal bebas adalah watak sistem politik liberal murni.</li><li><strong>D</strong>: Penundaan pemilu tanpa dasar hukum melanggar konstitusi.</li><li><strong>E</strong>: Membatasi partisipasi masyarakat bertentangan dengan demokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pancasila sebagai ideologi mencerminkan • seperangkat nilai terpadu</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Gagasan yang tumbuh dalam kehidupan masyarakat ditata oleh Pancasila secara...", "options": ["Acak dan fleksibel mengikuti selera kekuasaan penguasa", "Hierarkis di mana hak pemerintah berada di atas hak rakyat", "Ketat dan terpisah antar masing-masing golongan suku", "Bebas tanpa keterikatan pada norma hukum ketatanegaraan", "Sistematis menjadi satu kesatuan bulat dan utuh"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penataan Gagasan Sistematis dalam Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Semua gagasan yang timbul dalam kehidupan bermasyarakat <strong>ditata secara sistematis menjadi satu kesatuan yang utuh</strong> dalam naungan ideologi Pancasila.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Keragaman aspirasi dan kearifan lokal bangsa tidak dibiarkan tercerai-berai. Pancasila menyatukannya dalam tata susunan sila yang hierarkis piramidal dan saling menjiwai, membentuk bangunan filsafat yang bulat dan terintegrasi.</p><p>Jawaban tepat: <strong>E. Sistematis menjadi satu kesatuan bulat dan utuh</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penataan acak merusak kepastian falsafah negara kesatuan.</li><li><strong>B</strong>: Pemerintah di atas rakyat bertentangan dengan kedaulatan rakyat.</li><li><strong>C</strong>: Pemisahan antarsuku memicu segregasi dan disintegrasi nasional.</li><li><strong>D</strong>: Kebebasan tanpa norma merusak tatanan tertib masyarakat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Semua gagasan-gagasan • sistematis menjadi satu kesatuan yang utuh</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Agar tidak tertinggal oleh dinamika zaman, ideologi Pancasila memiliki sifat...", "options": ["Bersifat tertutup terhadap dinamika perkembangan zaman baru", "Statis dan melarang interpretasi baru atas nilai dasar", "Terbuka, luwes, fleksibel, serta tidak bersifat dogmatis", "Mutlak dan membatasi dialog pertukaran kebudayaan dunia", "Dibatasi penggunaannya pada agenda seremonial kenegaraan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sifat Fleksibilitas Ideologi Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebagai pedoman dan acuan dalam segala bidang, ideologi Pancasila <strong>sifatnya terbuka, luwes, fleksibel dan tidak bersifat kaku yang akan menyebabkan ketinggalan zaman</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Pancasila memiliki daya adaptasi tinggi tanpa kehilangan jati diri aslinya. Sifat terbuka dan fleksibel memungkinkan bangsa Indonesia menyerap kemajuan peradaban global dan merespons tantangan zaman secara kreatif.</p><p>Jawaban tepat: <strong>C. Terbuka, luwes, fleksibel, serta tidak bersifat dogmatis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sifat tertutup adalah ciri ideologi totaliter seperti fasisme.</li><li><strong>B</strong>: Sifat statis membuat suatu ideologi kaku dan mudah ditinggalkan.</li><li><strong>D</strong>: Membatasi dialog memutus kemajuan ilmu pengetahuan bangsa.</li><li><strong>E</strong>: Fungsi pasif seremonial mengerdilkan peran hidup ideologi negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sifatnya terbuka, luwes, fleksibel • tidak bersifat kaku</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pengembangan atas nilai-nilai dasar Pancasila wajib dilaksanakan secara...", "options": ["Bersifat dogmatis dan membatasi masukan pemikiran generasi muda", "Menunggu inisiatif bantuan asistensi dari lembaga internasional", "Tertutup dalam forum rahasia pejabat tinggi kementerian", "Kreatif dan dinamis mengikuti kebutuhan masyarakat", "Seragam tanpa memperhatikan kondisi geografis wilayah"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Metode Pengembangan Nilai Dasar Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pengembangan atas nilai-nilai dasar Pancasila dilaksanakan secara kreatif dan dinamis</strong> dengan memperhatikan tingkat kebutuhan serta perkembangan masyarakat Indonesia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Aktualisasi nilai Pancasila tidak boleh mandek pada rumusan tekstual. Pengembangan dinamis dan kreatif memastikan nilai-nilai kebangsaan senantiasa kontekstual, solutif, dan relevan dengan realitas sosial masyarakat modern.</p><p>Jawaban tepat: <strong>D. Kreatif dan dinamis mengikuti kebutuhan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sikap kaku menghambat kontribusi pemikiran generasi penerus.</li><li><strong>B</strong>: Sikap pasif melemahkan kemandirian kedaulatan bangsa.</li><li><strong>C</strong>: Forum tertutup rahasia bertentangan dengan prinsip keterbukaan publik.</li><li><strong>E</strong>: Mengabaikan kondisi geografis mengabaikan karakteristik kepulauan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengembangan atas nilai-nilai dasar Pancasila • dilaksanakan secara kreatif dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Konsekuensi dari kedudukan Pancasila sebagai ideologi terbuka adalah...", "options": ["Memberi kebebasan mutlak bagi paham anarkisme individu", "Membuka ruang musyawarah masyarakat mencapai cita-cita", "Memperbolehkan perubahan konstitusi dasar tanpa musyawarah", "Menghapus wewenang pengawasan lembaga peradilan umum", "Mewajibkan privatisasi seluruh badan usaha milik negara"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsekuensi Keterbukaan Ideologi Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebagai konsekuensinya, Pancasila sebagai ideologi <strong>membuka ruang membentuk kesepakatan masyarakat bagaimana mencapai cita-cita dan nilai-nilai dasar</strong> dalam masyarakat itu sendiri.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Keterbukaan Pancasila mewadahi proses konsensus demokratis. Masyarakat diberikan ruang berdialog dan bermusyawarah guna merumuskan strategi terbaik dalam mengejawantahkan nilai dasar Pancasila ke dalam tata hukum dan kebijakan publik.</p><p>Jawaban tepat: <strong>B. Membuka ruang musyawarah masyarakat mencapai cita-cita</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Paham anarkisme merusak ketertiban dan bertentangan dengan hukum.</li><li><strong>C</strong>: Dasar negara tidak dapat diubah oleh kesepakatan sepihak.</li><li><strong>D</strong>: Pengawasan peradilan mutlak dipertahankan dalam negara hukum.</li><li><strong>E</strong>: Privatisasi total BUMN bertentangan dengan Pasal 33 UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>membuka ruang membentuk kesepakatan masyarakat • bagaimana mencapai</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tiga pilar kesepakatan yang menjadi penyangga konstitusionalisme mencakup...", "options": ["Bagi hasil dividen, subsidi bahan bakar, dan pagu utang", "Penetapan upah buruh, tarif tol lintas daerah, dan ekspor", "Pakta militer pertahanan, batas teritorial, dan bea cukai", "Pembagian kursi kementerian, koalisi parlemen, dan anggaran", "Cita-cita bersama, the rule of law, dan institusi ketatanegaraan"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Kesepakatan Penyangga Konstitusionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tiga kesepakatan penyangga konstitusionalisme mencakup: <strong>1) tujuan atau cita-cita bersama, 2) the rule of law sebagai the basis of government, dan 3) the form of institutions and procedures</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Negara konstitusional berdiri kokoh di atas tiga konsensus fundamental tersebut. Tanpa adanya kesepakatan tentang cita-cita luhur, supremasi hukum yang berkeadilan, dan prosedur kelembagaan yang sah, suatu negara tidak akan beroperasi secara stabil.</p><p>Jawaban tepat: <strong>E. Cita-cita bersama, the rule of law, dan institusi ketatanegaraan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bagi hasil dan pagu utang adalah materi kebijakan fiskal belanja.</li><li><strong>B</strong>: Upah dan tarif tol adalah kebijakan sektoral ekonomi publik.</li><li><strong>C</strong>: Pakta militer adalah perjanjian pertahanan eksternal negara.</li><li><strong>D</strong>: Bagi kursi kementerian adalah negosiasi pragmatis politik kabinet.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kesepakatan sebagai penyangga konstitusionalisme • kesepakatan tentang tujuan atau cita-cita bersama</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kesepakatan penyangga konstitusionalisme ditegaskan hanya dapat dicapai melalui...", "options": ["Pengembangan sistem politik yang berlandaskan demokrasi", "Penerapan hukum darurat perang militer secara permanen", "Sistem monarki absolut dengan kekuasaan turun temurun", "Penyerahan tata kelola negara kepada korporasi swasta", "Sentralisasi kekuasaan tunggal pada tangan birokrasi elit"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sistem Politik Prasyarat Konstitusionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesepakatan-kesepakatan konstitusionalisme tersebut <strong>hanya mungkin dicapai jika sistem yang dikembangkan adalah sistem demokrasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Konstitusionalisme menghendaki pembatasan kekuasaan dan penghormatan hak warga negara. Hal tersebut mustahil terwujud di bawah tirani, melainkan menuntut iklim demokrasi yang menjamin kebebasan berpendapat dan partisipasi aktif rakyat.</p><p>Jawaban tepat: <strong>A. Pengembangan sistem politik yang berlandaskan demokrasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Hukum darurat militer permanen membekukan hak-hak sipil warga.</li><li><strong>C</strong>: Monarki absolut menempatkan kehendak raja di atas hukum.</li><li><strong>D</strong>: Pemerintahan korporasi meniadakan fungsi pengayoman sosial.</li><li><strong>E</strong>: Sentralisasi birokrasi elit memicu timbulnya oligarki tertutup.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>hanya mungkin dicapai jika sistem yang dikembangkan • adalah sistem</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam arsitektur ketatanegaraan RI, Pancasila berkedudukan sebagai...", "options": ["Petunjuk teknis pengawasan anggaran belanja dinas tahunan", "Aturan teknis pelaksanaan operasional penerimaan bea cukai", "Dasar filosofis dalam Pembukaan UUD 1945 penyangga konstitusi", "Surat edaran menteri mengenai mekanisme kerja pegawai shift", "Lampiran pelengkap yang dapat diubah melalui keputusan dirjen"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Pancasila dalam Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pancasila sebagai dasar-dasar filosofis terdapat dalam Pembukaan UUD 1945</strong> yang merupakan <strong>kesepakatan pertama penyangga konstitusionalisme</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Pembukaan UUD 1945 merupakan *staatsfundamentalnorm* yang memuat dasar falsafah negara Pancasila. Kesepakatan filosofis ini menjadi landasan moral dan sumber hukum tertinggi bagi seluruh tata hukum positif di Indonesia.</p><p>Jawaban tepat: <strong>C. Dasar filosofis dalam Pembukaan UUD 1945 penyangga konstitusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengawasan anggaran adalah fungsi auditor internal pemerintah.</li><li><strong>B</strong>: Aturan bea cukai adalah instrumen teknis perundang-undangan.</li><li><strong>D</strong>: Surat edaran jam kerja adalah aturan teknis manajemen kantor.</li><li><strong>E</strong>: Pancasila bukan lampiran pelengkap melainkan norma dasar negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pancasila sebagai dasar-dasar filosofis • terdapat dalam Pembukaan UUD 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Komitmen kesepakatan MPR untuk tidak mengubah Pembukaan UUD 1945 berakibat...", "options": ["Membekukan seluruh pembaruan pasal-pasal batang tubuh konstitusi", "Menjaga keabadian kedudukan Pancasila sebagai dasar filosofis negara", "Mengharuskan pemilihan presiden kembali melalui musyawarah dewan", "Membatalkan seluruh undang-undang organik yang disahkan pemerintah", "Menghapus peran Dewan Perwakilan Daerah dalam proses legislasi"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Yuridis Tidak Diubahnya Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan <strong>tidak diubahnya Pembukaan UUD 1945</strong>, maka <strong>tidak berubah pula kedudukan Pancasila sebagai dasar-dasar filosofis bangunan negara Republik Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Kesepakatan fundamental amandemen UUD 1945 menetapkan Pembukaan tidak boleh diubah karena mengubah Pembukaan sama halnya dengan membubarkan negara proklamasi. Komitmen ini menjaga Pancasila tetap abadi sebagai fondasi bangsa.</p><p>Jawaban tepat: <strong>B. Menjaga keabadian kedudukan Pancasila sebagai dasar filosofis negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pasal batang tubuh telah diubah melalui empat kali amandemen.</li><li><strong>C</strong>: Pemilihan presiden tetap berjalan langsung oleh seluruh rakyat.</li><li><strong>D</strong>: Undang-undang organik tetap berlaku sah sesuai asas peraturan.</li><li><strong>E</strong>: Peran DPD tetap dijamin dalam ketentuan UUD 1945 hasil amandemen.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>diubahnya Pembukaan UUD 1945 • tidak berubah pula kedudukan Pancasila</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam koridor Pancasila sebagai ideologi terbuka, unsur yang dapat berubah adalah...", "options": ["Nilai dasar ketuhanan dan kemanusiaan yang adil dan beradab", "Kewajiban aparatur negara setia pada lambang garuda pancasila", "Bentuk negara kesatuan menjadi serikat federasi kedaerahan", "Tujuan nasional perlindungan segenap tumpah darah indonesia", "Sistem dan bentuk institusi demi mewujudkan cita-cita bangsa"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dimensi Perubahan dalam Ideologi Terbuka</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai dasar Pancasila tetap abadi, sedangkan <strong>yang berubah adalah sistem dan institusi untuk mewujudkan cita-cita berdasarkan nilai-nilai Pancasila</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Keterbukaan Pancasila membedakan antara nilai dasar yang permanen dan nilai instrumental/praksis yang adaptif. Lembaga negara, tata kelola teknologi, dan metode birokrasi dapat bertransformasi mengikuti tuntutan efektivitas zaman.</p><p>Jawaban tepat: <strong>E. Sistem dan bentuk institusi demi mewujudkan cita-cita bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nilai dasar spiritualitas dan moralitas bersifat abadi tidak diubah.</li><li><strong>B</strong>: Kesetiaan pada simbol lambang negara adalah kewajiban mutlak ASN.</li><li><strong>C</strong>: Bentuk negara kesatuan adalah komitmen mutlak yang dipertahankan.</li><li><strong>D</strong>: Tujuan nasional alinea IV Pembukaan merupakan pedoman abadi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>adalah sistem dan institusi • mewujudkan cita-cita berdasarkan nilai-nilai</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Keunggulan ideologi Pancasila dibanding liberalisme dan sosialisme terletak pada...", "options": ["Pengutamaan mutlak hak individu di atas kepentingan masyarakat", "Peniadaan total hak milik individu oleh penguasaan negara", "Penyerahan kewenangan hukum kepada perserikatan buruh pabrik", "Penyelarasan seimbang antara hak individu dan hak masyarakat", "Pemberian konsesi berusaha eksklusif kepada pejabat birokrasi"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perlindungan Hak Individu dan Kolektif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila memiliki perbedaan fundamental: <strong>mengakui dan melindungi baik hak-hak individu maupun hak masyarakat baik di bidang ekonomi maupun politik</strong> secara selaras.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Pancasila menghindari ekstremitas individualisme kapitalis yang mengorbankan masyarakat dan ekstremitas kolektivisme komunis yang menindas hak individu. Keduanya disatukan secara harmonis dalam bingkai kebersamaan yang berkeadilan.</p><p>Jawaban tepat: <strong>D. Penyelarasan seimbang antara hak individu dan hak masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengutamaan mutlak hak individu adalah karakteristik liberalisme.</li><li><strong>B</strong>: Peniadaan hak milik individu adalah watak komunisme marxisme.</li><li><strong>C</strong>: Penyerahan hukum ke serikat buruh adalah sindikalisme radikal.</li><li><strong>E</strong>: Hak istimewa usaha pejabat adalah monopoli oligarkis terlarang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pancasila mengakui dan melindungi • baik hak-hak individu maupun hak masyarakat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Demokrasi yang dikembangkan dalam sistem ketatanegaraan Indonesia mencakup...", "options": ["Demokrasi liberal berorientasi penumpukan kekayaan pribadi", "Demokrasi terpimpin mutlak di bawah kekuasaan seorang presiden", "Demokrasi politik yang dipadukan dengan demokrasi ekonomi", "Demokrasi serikat pekerja yang membatasi investasi teknologi baru", "Demokrasi semu yang melarang warga berserikat dan berkumpul"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Demokrasi Ekonomi Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Demokrasi yang dikembangkan bangsa Indonesia <strong>bukan demokrasi politik semata seperti dalam ideologi liberal-kapitalis, tetapi juga demokrasi ekonomi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Kedaulatan rakyat tidak cukup hanya diwujudkan dalam bilik suara pemilu politik. Pancasila menghendaki demokrasi ekonomi di mana produksi dikerjakan oleh semua untuk semua di bawah pimpinan anggota-anggota masyarakat demi kemakmuran bersama.</p><p>Jawaban tepat: <strong>C. Demokrasi politik yang dipadukan dengan demokrasi ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penumpukan kekayaan pribadi mencirikan watak kapitalis murni.</li><li><strong>B</strong>: Kekuasaan mutlak satu orang adalah penyimpangan otoritarianisme.</li><li><strong>D</strong>: Menolak investasi teknologi bertentangan dengan kemajuan bangsa.</li><li><strong>E</strong>: Melarang berserikat mencederai hak konstitusional Pasal 28 UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Demokrasi yang dikembangkan bukan demokrasi politik • tetapi juga demokrasi ekonomi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Menurut Jimly Asshiddiqie, perbedaan sistem perekonomian Pancasila terletak pada...", "options": ["Prinsip usaha bersama dan asas kekeluargaan seluruh warga", "Pemberantasan seluruh bentuk kepemilikan swasta oleh militer", "Dominasi monopoli mutlak oleh perusahaan multinasional asing", "Kewajiban warga menjual seluruh hasil pertanian kepada pejabat", "Peniadaan mata uang rupiah dan penerapan sistem barter kuno"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perbedaan Sistem Perekonomian Menurut Jimly Asshiddiqie</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan kutipan pemikiran <strong>Jimly Asshiddiqie</strong>, dalam Pancasila dasar perekonomian berpijak pada <strong>usaha bersama dan kekeluargaan</strong>, bukan kebebasan individual liberal atau dominasi etatisme negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Perekonomian Pancasila menyatukan daya cipta swasta, peran strategis BUMN, dan kekuatan gotong royong koperasi. Asas kekeluargaan menjadi jiwa operasional agar perekonomian tidak menghasilkan penindasan manusia atas manusia.</p><p>Jawaban tepat: <strong>A. Prinsip usaha bersama dan asas kekeluargaan seluruh warga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pemberantasan swasta oleh militer mencirikan kediktatoran junta.</li><li><strong>C</strong>: Monopoli asing meruntuhkan kedaulatan ekonomi kemandirian bangsa.</li><li><strong>D</strong>: Penjualan paksa kepada pejabat adalah praktik tanam paksa kolonial.</li><li><strong>E</strong>: Peniadaan mata uang bertentangan dengan UU Mata Uang NKRI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>bukan usaha bersama dan • kebebasan individual untuk berusaha</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan penelusuran sejarah, kelahiran Pancasila pada tahun 1945 terjadi...", "options": ["Secara mendadak sebagai hadiah pemberian dari penjajah", "Melalui pemungutan suara perwakilan kamar dagang dunia", "Hasil penjiplakan utuh konstitusi negara adidaya barat", "Berdasarkan instruksi langsung penguasa militer pendudukan asing", "Melalui proses panjang perjuangan dan kepribadian bangsa"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Historisitas Kelahiran Pancasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila <strong>tidaklah lahir secara mendadak pada tahun 1945, melainkan melalui proses yang panjang</strong>, dengan didasari sejarah perjuangan dan berakar pada kepribadian bangsa Indonesia sendiri.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Kelahiran Pancasila merupakan buah dari perjalanan sejarah spiritual, sosiologis, dan perjuangan fisik berabad-abad. Nilai-nilainya bukan bentukan instan melainkan kristalisasi jiwa bangsa yang digali dari bumi pertiwi.</p><p>Jawaban tepat: <strong>E. Melalui proses panjang perjuangan dan kepribadian bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pancasila bukan hadiah melainkan hasil perjuangan mandiri bangsa.</li><li><strong>B</strong>: Kamar dagang dunia tidak terlibat dalam perumusan falsafah bangsa.</li><li><strong>C</strong>: Pancasila bukan jiplakan melainkan berakar pada budaya lokal.</li><li><strong>D</strong>: Penguasa militer Jepang tidak merumuskan materi dasar negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pancasila tidaklah lahir secara mendadak • melalui proses yang panjang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dua kerajaan maritim dan agraris yang berhasil menyatukan wilayah Nusantara adalah...", "options": ["Kerajaan Tarumanegara dan Kesultanan Samudera Pasai", "Kesultanan Mataram Islam dan Kesultanan Banten", "Kerajaan Sriwijaya dan Kerajaan Majapahit", "Kerajaan Singasari dan Kesultanan Malaka", "Kerajaan Kutai Martapura dan Kesultanan Ternate"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Kerajaan Kedaulatan Nusantara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada zamannya, <strong>kedua kerajaan Sriwijaya dan Majapahit telah merupakan negara yang berdaulat, bersatu, serta mempunyai wilayah yang meliputi seluruh nusantara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Tonggak integrasi kedaulatan wilayah nusantara tercatat dalam era keemasan Kerajaan Sriwijaya (abad ke-7) dan Majapahit (abad ke-14). Keduanya membuktikan kemampuan bangsa menyelenggarakan ketatanegaraan berdaulat.</p><p>Jawaban tepat: <strong>C. Kerajaan Sriwijaya dan Kerajaan Majapahit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tarumanegara dan Samudera Pasai mencakup wilayah regional terbatas.</li><li><strong>B</strong>: Mataram dan Banten berkuasa pada skala pulau Jawa.</li><li><strong>D</strong>: Singasari adalah pendahulu Majapahit dengan wilayah belum penuh nusantara.</li><li><strong>E</strong>: Kutai dan Ternate merupakan kerajaan lokal berbasis kepulauan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kedua kerajaan Sriwijaya dan Majapahit • meliputi seluruh</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pada zaman kejayaan Sriwijaya dan Majapahit, nilai-nilai Pancasila telah...", "options": ["Dihayati dan dijadikan asas kehidupan bermasyarakat", "Diabaikan demi kepentingan hegemoni dinasti keluarga raja", "Digantikan oleh sistem hukum perdata penjajah kolonial", "Dilarang penyebarannya di luar tembok istana kerajaan", "Dibukukan dalam lembaran negara berbahasa asing modern"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penghayatan Nilai Pancasila Zaman Kerajaan Nusantara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada zaman itu, <strong>unsur-unsur atau sila-sila dari Pancasila yaitu ketuhanan, kemanusiaan, persatuan, musyawarah dan keadilan sosial telah dihayati dan dijadikan asas</strong> dalam tata kehidupan pemerintahan dan kemasyarakatan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Meskipun belum dirumuskan dalam kalimat resmi 5 sila seperti dalam rumusan resmi konstitusi, esensi nilai-nilai ketuhanan, gotong royong, toleransi beragama, dan musyawarah telah menjadi nafas kehidupan sehari-hari masyarakat nusantara kuno.</p><p>Jawaban tepat: <strong>A. Dihayati dan dijadikan asas kehidupan bermasyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Nilai-nilai luhur justru dihayati bukan diabaikan dinasti.</li><li><strong>C</strong>: Hukum kolonial baru masuk berabad-abad kemudian pada era VOC.</li><li><strong>D</strong>: Praktik nilai menyebar luas di masyarakat bukan hanya di istana.</li><li><strong>E</strong>: Dokumen masa itu ditulis dalam aksara dan bahasa kuno nusantara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>unsur-unsur atau sila-sila dari Pancasila • telah dihayati dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Bukti tertulis penerapan nilai kebersamaan dan ketuhanan era Sriwijaya termuat dalam...", "options": ["Prasasti Ciaruteun, Kebon Kopi, dan Prasasti Tugu", "Prasasti Yupa Muara Kaman di tepian Sungai Mahakam", "Kitab Centhini, Serat Wedhatama, dan Babad Tanah Jawi", "Prasasti Telaga Batu, Kedukan Bukit, dan Karang Brahi", "Prasasti Canggal, Kalasan, dan Prasasti Mantyasih Kedu"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prasasti Sejarah Pembukti Nilai Sriwijaya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bukti nilai luhur zaman Sriwijaya termuat dalam <strong>dokumen-dokumen tertulis yang ada seperti Telaga Batu, Kedukan Bukit, Karang Brahi, Talang Tuo dan Kota Kapur</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Berbagai prasasti peninggalan Kedatuan Sriwijaya tersebut memuat pesan-pesan moral mengenai kesetiaan pada negara, keadilan, doa kemakmuran bagi seluruh rakyat, serta kutukan bagi para perusak ketertiban umum.</p><p>Jawaban tepat: <strong>D. Prasasti Telaga Batu, Kedukan Bukit, dan Karang Brahi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ciaruteun dan Tugu adalah peninggalan Kerajaan Tarumanegara.</li><li><strong>B</strong>: Prasasti Yupa adalah peninggalan Kerajaan Hindu Kutai.</li><li><strong>C</strong>: Centhini dan Wedhatama adalah karya sastra era Mataram Islam.</li><li><strong>E</strong>: Canggal dan Kalasan adalah prasasti Mataram Kuno di Jawa Tengah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dokumen-dokumen tertulis yang ada seperti • Telaga Batu, Kedukan Bukit,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Unsur musyawarah dan hubungan antaranegara tetangga era Majapahit diuraikan dalam...", "options": ["Kitab Pararaton karangan pujangga kerajaan Singasari", "Buku Nagarakertagama karangan Mpu Prapanca", "Kitab Arjunawiwaha karangan pujangga Mpu Kanwa", "Kitab Bharatayuddha gubahan Mpu Sedah dan Panuluh", "Serat Kalatidha karangan pujangga Raden Ngabehi Ranggawarsita"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai Musyawarah dalam Kitab Nagarakertagama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Dalam buku Nagarakertagama karangan Mpu Prapanca</strong> diuraikan susunan pemerintahan Majapahit yang <strong>menunjukkan adanya unsur musyawarah dan hubungan antar negara tetangga</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Kakawin Nagarakertagama (Desawarnana) yang ditulis tahun 1365 mendokumentasikan tata kelola kenegaraan Majapahit di bawah Prabu Hayam Wuruk dan Mahapatih Gajah Mada, termasuk adanya dewan pertimbangan (*Bhattara Saptaprabhu*).</p><p>Jawaban tepat: <strong>B. Buku Nagarakertagama karangan Mpu Prapanca</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kitab Pararaton menceritakan riwayat raja-raja Singasari dan Majapahit.</li><li><strong>C</strong>: Arjunawiwaha adalah karya sastra era Raja Airlangga di Kahuripan.</li><li><strong>D</strong>: Bharatayuddha digubah pada era Kerajaan Kediri di bawah Jayabaya.</li><li><strong>E</strong>: Serat Kalatidha berisi ramalan zaman edan era pujangga Surakarta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dalam buku Nagarakertagama karangan Mpu Prapanca • menunjukkan adanya unsur musyawarah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Prinsip toleransi kehidupan beragama antara pemeluk Buddha dan Hindu dilukiskan dalam...", "options": ["Kitab Gatotkacasraya gubahan pujangga Mpu Panuluh", "Kitab Smaradahana karangan pujangga Mpu Dharmaja", "Buku Sutasoma karangan pujangga Mpu Tantular", "Kitab Kresnayana karangan pujangga istana Mpu Triguna", "Kitab Lubdhaka karangan pujangga sastra Mpu Tanakung"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Toleransi Kehidupan Beragama dalam Kitab Sutasoma</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Dalam buku Sutasoma karangan Mpu Tantular</strong> dilukiskan adanya <strong>toleransi kehidupan beragama, khususnya antara agama Budha dan Hindu</strong> yang melahirkan semboyan Bhinneka Tunggal Ika.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Mpu Tantular menuliskan frasa monumental *'Bhinneka Tunggal Ika tan hana dharma mangrwa'* dalam Kakawin Sutasoma. Karya ini membuktikan bahwa persatuan dalam kemajemukan agama telah menjadi kearifan luhur bangsa sejak abad ke-14.</p><p>Jawaban tepat: <strong>C. Buku Sutasoma karangan pujangga Mpu Tantular</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Gatotkacasraya berkisah tentang kepahlawanan tokoh pewayangan.</li><li><strong>B</strong>: Smaradahana menceritakan terbakarnya Dewa Kama oleh Dewa Siwa.</li><li><strong>D</strong>: Kresnayana mengisahkan perkawinan Prabu Kresna dan Dewi Rukmini.</li><li><strong>E</strong>: Lubdhaka mengisahkan pengampunan dosa seorang pemburu binatang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dalam buku Sutasoma • toleransi kehidupan beragama</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Puncak kejayaan peradaban masa lampau nusantara membuktikan bahwa nilai Pancasila...", "options": ["Baru tercipta setelah bangsa Indonesia mengenal teknologi cetak", "Gagal menciptakan kedamaian dan kerukunan antarsuku bangsa", "Terbatas pada lingkungan saudagar perantara di pesisir pelabuhan", "Ditiru secara mentah dari undang-undang kerajaan di Eropa barat", "Telah diterapkan secara nyata dalam tata kehidupan bernegara"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Nilai Pancasila Zaman Kejayaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bangsa Indonesia telah mengalami kejayaan pada zaman Sriwijaya dan Majapahit di mana <strong>nilai-nilai yang terkandung dalam Pancasila sudah diterapkan dalam tata kehidupan bernegara dan bermasyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Kejayaan Nusantara bukanlah kemegahan semu tanpa jiwa. Kemakmuran Sriwijaya dan Majapahit terwujud berkat penerapan prinsip-prinsip moral ketuhanan, persatuan teritorial, hukum yang adil, serta keterbukaan terhadap peradaban dunia.</p><p>Jawaban tepat: <strong>E. Telah diterapkan secara nyata dalam tata kehidupan bernegara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nilai-nilai luhur telah hidup berabad-abad sebelum teknologi cetak.</li><li><strong>B</strong>: Penerapan nilai terbukti berhasil menciptakan kejayaan peradaban.</li><li><strong>C</strong>: Nilai dihayati seluruh lapisan masyarakat bukan saudagar semata.</li><li><strong>D</strong>: Pancasila bersumber dari tradisi lokal bukan adopsi hukum Eropa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>bangsa Indonesia telah mengalami • nilai-nilai yang terkandung dalam Pancasila</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kedatangan penjajahan Barat mengubah kehidupan bangsa menjadi penderitaan karena...", "options": ["Penjajah bertindak tidak sesuai dengan nilai-nilai Pancasila", "Penjajah membangun sarana jalan raya dan jaringan pelabuhan", "Masyarakat pedalaman belum mengenal teknologi industri modern", "Terjadi bencana kekeringan berkepanjangan di seluruh pulau", "Para raja nusantara menyerahkan takhta secara sukarela"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Kolonialisme terhadap Kehidupan Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan datangnya penjajahan Barat, kehidupan bangsa berubah menjadi penderitaan karena <strong>penjajah bertindak tidak sesuai dengan nilai-nilai yang terkandung dalam Pancasila</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Praktik kolonialisme berwatak eksploitatif, menindas kemanusiaan, memecah belah persatuan (*devide et impera*), dan meniadakan keadilan sosial. Penjajahan secara mutlak berlawanan dengan harkat martabat dan nilai-nilai luhur Pancasila.</p><p>Jawaban tepat: <strong>A. Penjajah bertindak tidak sesuai dengan nilai-nilai Pancasila</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pembangunan sarana fisik kolonial bermotif eksploitasi ekonomi.</li><li><strong>C</strong>: Rakyat nusantara justru gigih menuntut akses pendidikan modern.</li><li><strong>D</strong>: Penderitaan bangsa disebabkan oleh penindasan manusia atas manusia.</li><li><strong>E</strong>: Para pejuang dan raja nusantara bertempur gigih melawan penjajah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dengan datangnya penjajahan Barat • Indonesia berubah menjadi penderitaan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Prasasti Talang Tuo dan Kota Kapur membuktikan keberadaan tata nilai luhur pada era...", "options": ["Kerajaan Pajajaran di bawah kekuasaan Prabu Siliwangi", "Kerajaan Mataram Kuno di bawah dinasti Sanjaya dan Syailendra", "Kesultanan Demak pada masa kepemimpinan Sultan Trenggana", "Kedatuan Sriwijaya dalam mewujudkan kemakmuran lingkungan", "Kesultanan Gowa Tallo di bawah kepemimpinan Sultan Hasanuddin"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prasasti Talang Tuo dan Kota Kapur</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Prasasti <strong>Talang Tuo dan Kota Kapur</strong> merupakan dokumen tertulis resmi yang membuktikan kejayaan dan penerapan tata nilai pada era <strong>Kedatuan Sriwijaya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Prasasti Talang Tuo (684 M) memuat pembangunan taman Sriksetra demi kesejahteraan seluruh makhluk hidup, merefleksikan nilai luhur kepemimpinan yang berwawasan lingkungan dan mengutamakan kemaslahatan rakyat banyak.</p><p>Jawaban tepat: <strong>D. Kedatuan Sriwijaya dalam mewujudkan kemakmuran lingkungan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kerajaan Pajajaran mewariskan Prasasti Batutulis di Bogor.</li><li><strong>B</strong>: Prasasti Mataram Kuno antara lain Prasasti Canggal dan Balitung.</li><li><strong>C</strong>: Kesultanan Demak mewariskan Masjid Agung dan piagam dakwah Islam.</li><li><strong>E</strong>: Kesultanan Gowa Tallo mewariskan benteng Somba Opu di Makassar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Talang Tua dan Kota Kapur • berdasarkan dokumen-dokumen tertulis</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perpaduan sumber ideologis Pancasila secara historis dicirikan oleh...", "options": ["Penolakan total terhadap seluruh gagasan kemanusiaan peradaban dunia", "Ilham gagasan besar dunia yang berakar pada kepribadian bangsa", "Penerimaan doktrin materialisme sejarah tanpa penyaringan budaya", "Kewajiban mengadopsi sistem parlementer monarki dari kerajaan barat", "Pembatasan hubungan kemitraan pada rumpun bangsa serumpun"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sintesis Gagasan Dunia dan Kepribadian Lokal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila <strong>diilhami oleh gagasan-gagasan besar dunia, tetapi tetap berakar pada kepribadian dan gagasan besar bangsa Indonesia sendiri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Para pendiri bangsa memiliki wawasan global yang luas sekaligus keterikatan batin yang mendalam pada jati diri nusantara. Nilai-nilai universal seperti demokrasi dan kemanusiaan disenyawakan dengan nilai keaslian gotong royong dan ketuhanan.</p><p>Jawaban tepat: <strong>B. Ilham gagasan besar dunia yang berakar pada kepribadian bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pancasila bersikap terbuka pada gagasan luhur peradaban dunia.</li><li><strong>C</strong>: Materialisme sejarah disaring dan ditolak watak ateistiknya.</li><li><strong>D</strong>: Pendiri bangsa menolak bentuk monarki bagi republik Indonesia.</li><li><strong>E</strong>: Diplomasi Indonesia berprinsip bebas aktif menjangkau seluruh dunia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>diilhami oleh gagasan-gagasan besar dunia • dan gagasan besar bangsa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Rintisan sejarah konseptualisasi Pancasila dimulai sejak awal tahun 1900-an melalui...", "options": ["Pencarian sintesis antar-ideologi melalui organisasi pergerakan", "Pemberian dana pinjaman modal usaha oleh bank pemerintah kolonial", "Penyelenggaraan ekspedisi militer gabungan di kepulauan pasifik", "Pelaksanaan pemilihan umum pertama anggota volksraad di batavia", "Pembentukan serikat buruh perusahaan perkebunan milik belanda"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Awal Konseptualisasi Sintesis Ideologi Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Proses konseptualisasi Pancasila melintasi perjalanan panjang, <strong>dimulai sejak awal 1900-an dalam bentuk rintisan-rintisan gagasan untuk mencari sintesis antar ideologi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Kebangkitan nasional melahirkan beragam aliran pemikiran (nasionalisme, Islam, dan sosialisme). Para tokoh pergerakan berusaha mencari titik temu (*sintesis*) yang dapat menyatukan segenap perbedaan pandangan demi kemerdekaan bangsa.</p><p>Jawaban tepat: <strong>A. Pencarian sintesis antar-ideologi melalui organisasi pergerakan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pinjaman modal adalah skema perbankan bukan konseptualisasi ideologi.</li><li><strong>C</strong>: Ekspedisi militer adalah operasi perang bukan pergerakan kebangsaan.</li><li><strong>D</strong>: Pemilihan Volksraad adalah lembaga dewan bentukan penjajah Hindia Belanda.</li><li><strong>E</strong>: Serikat buruh kolonial berfokus pada hubungan industrial perkebunan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dimulai sejak awal 1900-an • mencari sintesis antar ideologi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Fase awal kebangkitan gagasan kebangsaan ditandai oleh kemunculan organisasi...", "options": ["Vereenigde Oostindische Compagnie dan Dewan Hindia Belanda", "Komite Nasional Pemuda Indonesia dan Korps Pegawai Negeri", "Gerakan Non-Blok dan Perhimpunan Bangsa-Bangsa Asia Tenggara", "Partai Demokrat Liberal dan Persatuan Kaum Buruh Eropa", "Boedi Oetomo, Sarekat Dagang Islam, Muhammadiyah, dan NU"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kemunculan Organisasi Pergerakan Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Proses sintesis ditandai oleh kemunculan organisasi kebangkitan: <strong>Boedi Oetomo, SDI, SI, Muhammadiyah, NU, Perhimpunan Indonesia, dan Sumpah Pemuda</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Munculnya organisasi-organisasi modern pada dekade awal abad ke-20 menandai transformasi perjuangan kedaerahan menjadi gerakan kebangsaan terorganisasi. Ruang-ruang organisasi ini melatih dialog nilai yang kelak mengkristal dalam Pancasila.</p><p>Jawaban tepat: <strong>E. Boedi Oetomo, Sarekat Dagang Islam, Muhammadiyah, dan NU</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: VOC dan Raad van Indie adalah organ kelembagaan penjajah kolonial.</li><li><strong>B</strong>: KNPI dan Korpri dibentuk pada paruh kedua abad ke-20 masa Orde Baru.</li><li><strong>C</strong>: GNB dan ASEAN dibentuk pasca kemerdekaan pada era perang dingin.</li><li><strong>D</strong>: Partai Demokrat Liberal adalah entitas politik negara-negara Barat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>berbagai organisasi pergerakan • Boedi Oetomo, SDI, SI, Muhammadiyah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pada tanggal 7 September 1944, Perdana Menteri Koiso mengeluarkan janji berupa...", "options": ["Penyerahan seluruh armada kapal selam tempur kepada pejuang PETA", "Penarikan langsung seluruh tentara pendudukan jepang dari jawa", "Kemerdekaan Indonesia di kemudian hari jika membantu perang", "Pemberian hak suara penuh kepada pemuda indonesia di parlemen tokyo", "Pembubaran organisasi pembela tanah air di seluruh karesidenan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Janji Kemerdekaan Perdana Jepang (Koiso)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada tanggal <strong>7 September 1944</strong>, Jepang berusaha menarik simpati dengan <strong>menjanjikan kemerdekaan Indonesia di kemudian hari, apabila Indonesia membantu Jepang memenangkan perang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Janji Koiso dikeluarkan di depan parlemen Jepang saat posisi militer Jepang semakin terdesak oleh pasukan Sekutu di mandala Pasifik. Janji bersyarat ini dimanfaatkan secara cerdik oleh para tokoh bangsa untuk mempercepat persiapan kemerdekaan.</p><p>Jawaban tepat: <strong>C. Kemerdekaan Indonesia di kemudian hari jika membantu perang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jepang tidak pernah menyerahkan armada kapal perang modernnya.</li><li><strong>B</strong>: Tentara Jepang tetap menduduki Nusantara hingga menyerah pada Sekutu.</li><li><strong>D</strong>: Pemuda Indonesia tidak diberi hak perwakilan di parlemen Jepang.</li><li><strong>E</strong>: Organisasi PETA justru dibentuk Jepang untuk membantu pertahanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tanggal 7 September 1944 Jepang • kemerdekaan Indonesia di kemudian hari</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pengumuman resmi mengenai rencana pembentukan BPUPKI disampaikan pada...", "options": ["Tanggal 7 September 1944 bersamaan janji perdana menteri", "Tanggal 1 Maret 1945 oleh pimpinan militer Bala Tentara Dai Nippon", "Tanggal 29 April 1945 pada peringatan hari lahir kaisar jepang", "Tanggal 28 Mei 1945 pada upacara pelantikan anggota gedung pejambon", "Tanggal 17 Agustus 1945 pada pembacaan naskah proklamasi pegangsaan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengumuman Pembentukan Badan Penyelidik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada tanggal <strong>1 Maret 1945</strong>, Jepang mengulangi janji kemerdekaan tanpa syarat dan <strong>mengumumkan akan dibentuknya BPUPKI (Dokuritsu Junbi Choosakai)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Jenderal Kumakichi Harada (panglima tentara ke-16 di Jawa) mengumumkan pembentukan badan penyelidik ini pada 1 Maret 1945 sebagai realisasi konkret dari janji kemerdekaan sebelumnya.</p><p>Jawaban tepat: <strong>B. Tanggal 1 Maret 1945 oleh pimpinan militer Bala Tentara Dai Nippon</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tanggal 7 September 1944 adalah pidato perdana Koiso di Tokyo.</li><li><strong>C</strong>: Tanggal 29 April 1945 adalah peresmian pembentukan susunan pengurus.</li><li><strong>D</strong>: Tanggal 28 Mei 1945 adalah hari upacara pelantikan resmi BPUPKI.</li><li><strong>E</strong>: Tanggal 17 Agustus 1945 adalah proklamasi kemerdekaan Republik Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pada tanggal 1 Maret 1945 • mengumumkan akan dibentuknya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Secara resmi kelembagaan BPUPKI dibentuk pada 29 April 1945 dengan ketua...", "options": ["Mr. Muhammad Yamin perumus naskah rancangan pembukaan", "Mr. Kasman Singodimedjo komandan batalyon peta jakarta", "Prof. R. Soepomo ahli hukum adat dan tata negara universitas", "Dr. K.R.T. Radjiman Wediodiningrat tokoh pergerakan senior", "Ki Hadjar Dewantara perintis perguruan taman siswa yogyakarta"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peresmian Pembentukan dan Ketua BPUPKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada tanggal <strong>29 April 1945</strong>, dibentuk susunan resmi BPUPKI yang diketuai oleh figur sepuh pergerakan nasional, <strong>Dr. K.R.T. Radjiman Wediodiningrat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Penetapan Radjiman Wediodiningrat sebagai ketua mencerminkan kehormatan bagi tokoh senior Boedi Oetomo yang dihormati semua golongan. Beliau memimpin sidang-sidang perumusan dasar negara secara bijaksana dan demokratis.</p><p>Jawaban tepat: <strong>D. Dr. K.R.T. Radjiman Wediodiningrat tokoh pergerakan senior</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mr. Moh. Yamin bertindak sebagai anggota biasa yang berpidato.</li><li><strong>B</strong>: Kasman Singodimedjo memimpin BKR pasca proklamasi kemerdekaan.</li><li><strong>C</strong>: Prof. Soepomo menjadi anggota dan ketua panitia perancang kecil.</li><li><strong>E</strong>: Ki Hadjar Dewantara berkedudukan sebagai anggota perumus bidang budaya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pada tanggal 29 April 1945 • Jepang membentuk BPUPKI yang diketuai Dr.K.R.T.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dua orang pejabat yang mendampingi ketua BPUPKI sebagai Ketua Muda adalah...", "options": ["Ir. Soekarno dan Drs. Mohammad Hatta dwitunggal", "Sutan Sjahrir dan Amir Sjarifuddin tokoh pemuda", "Mr. A.A. Maramis dan KH. Wachid Hasjim perwakilan", "Abikoesno Tjokrosoejoso dan H. Agus Salim menteri", "Itibangase Yosio dan Raden Pandji Soeroso"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pimpinan Ketua Muda BPUPKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dr. Radjiman didampingi oleh dua orang Ketua Muda (Fuku Kaityo), yaitu <strong>Ketua Muda I Itibangase (Jepang) dan Ketua Muda II Raden Pandji Soeroso (Indonesia)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Unsur pimpinan BPUPKI mencerminkan struktur birokrasi masa pendudukan militer, di mana perwakilan pihak Jepang (Itibangase Yosio) dan perwakilan Indonesia (R.P. Soeroso yang merangkap kepala kantor sekretariat) memegang jabatan wakil ketua.</p><p>Jawaban tepat: <strong>E. Itibangase Yosio dan Raden Pandji Soeroso</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Soekarno-Hatta adalah proklamator dan ketua-wakil panitia sembilan.</li><li><strong>B</strong>: Sutan Sjahrir dan Amir Sjarifuddin memilih jalur perjuangan bawah tanah.</li><li><strong>C</strong>: Maramis dan Wachid Hasjim adalah anggota BPUPKI dan Panitia Sembilan.</li><li><strong>D</strong>: Abikoesno dan Agus Salim memimpin komisi sidang rancangan UUD.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dua orang Ketua Muda (Fuku Kaityo) • Itibangase, dan Ketua Muda II, Raden Pandji Soeroso</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tujuh orang anggota istimewa (Toku Betsu) berkebangsaan Jepang dalam BPUPKI...", "options": ["Memiliki hak veto membatalkan hasil keputusan musyawarah", "Tidak memiliki hak suara dalam pemungutan keputusan sidang", "Menjabat secara otomatis sebagai ketua panitia perancang hukum", "Wajib memberikan persetujuan tertulis pada teks naskah proklamasi", "Berhak menentukan jumlah anggota perwakilan komite daerah"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Status Hak Suara Anggota Istimewa BPUPKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>BPUPKI memiliki <strong>7 (tujuh) orang anggota Istimewa (Toku Betsu) berkebangsaan Jepang yang tidak mempunyai hak suara</strong> dalam sidang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Anggota istimewa Jepang berkedudukan hanya sebagai pengamat (*observer*) perwakilan penguasa militer pendudukan. Mereka hadir memantau jalannya sidang tanpa memiliki hak memberikan suara atau menentukan keputusan akhir naskah.</p><p>Jawaban tepat: <strong>B. Tidak memiliki hak suara dalam pemungutan keputusan sidang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Anggota Jepang tidak memiliki hak veto konstitusional.</li><li><strong>C</strong>: Ketua panitia perancang dipimpin oleh Ir. Soekarno bukan Jepang.</li><li><strong>D</strong>: Naskah proklamasi murni dirumuskan dan diputuskan bangsa Indonesia.</li><li><strong>E</strong>: Penentuan keanggotaan daerah berada di bawah otoritas pergerakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>7 (tujuh) orang anggota Istimewa • tidak mempunyai hak suara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Setelah ditambah 6 anggota asal Indonesia, jumlah keseluruhan anggota BPUPKI adalah...", "options": ["60 orang anggota perwakilan suku daerah", "85 orang perwakilan organisasi keagamaan", "76 orang termasuk Ketua dan Ketua Muda", "90 orang utusan partai politik nusantara", "100 orang perwakilan serikat kerja pemuda"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jumlah Total Keanggotaan Akhir BPUPKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Komposisi awal 60 orang anggota biasa dan 7 anggota istimewa ditambah 6 anggota bangsa Indonesia, sehingga <strong>jumlah keseluruhan anggota BPUPKI adalah 76 orang (termasuk Ketua dan Ketua Muda)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Modul merinci perhitungan keanggotaan BPUPKI secara komprehensif. Penambahan 6 tokoh Indonesia di tengah perjalanan membuat total forum musyawarah berjumlah genap 76 orang pemikir bangsa.</p><p>Jawaban tepat: <strong>C. 76 orang termasuk Ketua dan Ketua Muda</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: 60 orang adalah jumlah awal anggota biasa bangsa Indonesia.</li><li><strong>B</strong>: 85 orang bukan angka resmi ketetapan keanggotaan badan penyelidik.</li><li><strong>D</strong>: 90 orang bukan komposisi keanggotaan badan penyelidik.</li><li><strong>E</strong>: 100 orang adalah representasi kabinet menteri seratus menteri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>jumlah keseluruhan • adalah 76 orang (termasuk Ketua dan Ketua Muda)</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pelantikan resmi anggota BPUPKI di Gedung Chuo Sangi In Jakarta dilaksanakan pada...", "options": ["Tanggal 28 Mei 1945 sehari sebelum sidang perdana", "Tanggal 1 Maret 1945 bertepatan maklumat jenderal", "Tanggal 1 Juni 1945 bertepatan pidato kelahiran pancasila", "Tanggal 22 Juni 1945 bertepatan pengesahan piagam jakarta", "Tanggal 10 Juli 1945 pada pembukaan masa persidangan kedua"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggal Resmi Pelantikan Anggota BPUPKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pada tanggal 28 Mei 1945, Jepang melantik BPUPKI</strong> dan keesokan harinya BPUPKI langsung memulai masa persidangan pertamanya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Upacara pelantikan anggota BPUPKI diselenggarakan secara khidmat pada 28 Mei 1945 di Gedung Cuo Sangi In (sekarang Gedung Pancasila, Kemenlu) dengan pengibaran bendera Hinomaru yang berdampingan dengan bendera Merah Putih.</p><p>Jawaban tepat: <strong>A. Tanggal 28 Mei 1945 sehari sebelum sidang perdana</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Tanggal 1 Maret 1945 adalah hari pengumuman rencana pembentukan.</li><li><strong>C</strong>: Tanggal 1 Juni 1945 adalah hari terakhir masa sidang pertama BPUPKI.</li><li><strong>D</strong>: Tanggal 22 Juni 1945 adalah pertemuan pengesahan Piagam Jakarta.</li><li><strong>E</strong>: Tanggal 10 Juli 1945 adalah hari pertama masa sidang kedua BPUPKI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pada tanggal 28 Mei 1945 • Jepang melantik BPUPKI</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Rentang waktu pelaksanaan Sidang Pertama dan Sidang Kedua BPUPKI adalah...", "options": ["1-10 Mei 1945 dan 1-10 Juni 1945 secara bersambung", "17-18 Agustus 1945 dan 22-25 Agustus 1945 pasca merdeka", "15-20 Juni 1945 dan 1-5 Agustus 1945 di gedung dewan", "29 Mei – 1 Juni 1945 dan 10 – 16 Juli 1945", "5-10 September 1945 dan 1-10 Oktober 1945 sidang pleno"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jadwal Resmi Masa Sidang I dan II BPUPKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>BPUPKI melakukan dua kali masa persidangan resmi, yaitu <strong>sidang pertama tanggal 29 Mei hingga 1 Juni 1945</strong> dan <strong>sidang kedua tanggal 10 Juli hingga 16 Juli 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Sidang pertama difokuskan untuk merumuskan dasar filsafat negara Indonesia merdeka, sedangkan sidang kedua membahas bentuk negara, batas wilayah teritorial, kewarganegaraan, dan rancangan undang-undang dasar.</p><p>Jawaban tepat: <strong>D. 29 Mei – 1 Juni 1945 dan 10 – 16 Juli 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bulan Mei awal belum dimulai persidangan resmi badan penyelidik.</li><li><strong>B</strong>: 18 Agustus 1945 adalah sidang PPKI mengesahkan UUD 1945.</li><li><strong>C</strong>: Akhir Juni adalah masa kerja Panitia Sembilan bukan sidang pleno.</li><li><strong>E</strong>: Bulan September adalah masa awal kerja KNIP pasca proklamasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sidang pertama dari tanggal 29 Mei 1945 hingga 1 Juni • dan sidang kedua dari tanggal 10 Juli 1945</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perumusan resmi naskah dasar negara Indonesia diwarnai diskusi intensif dalam...", "options": ["Rapat kerja pimpinan kantor komando pertahanan militer jepang", "Diskusi dan perdebatan dalam sidang-sidang resmi BPUPKI", "Konferensi meja bundar bersama delegasi kerajaan belanda", "Sidang dewan keamanan perserikatan bangsa-bangsa di new york", "Musyawarah perdana menteri dan jajaran menteri kabinet pertama"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Forum Perumusan Dasar Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Periode perumusan Pancasila sebagai dasar negara diwarnai dengan <strong>kegiatan diskusi dan perdebatan-perdebatan mendalam dalam sidang Badan Penyelidik Usaha-usaha Persiapan Kemerdekaan Indonesia (BPUPKI)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Forum BPUPKI menjadi kancah perdebatan intelektual dan ideologis paling bersejarah. Para bapak bangsa mengemukakan berbagai gagasan falsafah kenegaraan secara terbuka, kritis, dan demokratis hingga mencapai mufakat dasar negara.</p><p>Jawaban tepat: <strong>B. Diskusi dan perdebatan dalam sidang-sidang resmi BPUPKI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rapat militer Jepang mengurus strategi pertahanan pasifik.</li><li><strong>C</strong>: KMB diselenggarakan pada akhir 1949 di Den Haag Belanda.</li><li><strong>D</strong>: PBB belum berdiri saat perumusan awal dasar negara Mei 1945.</li><li><strong>E</strong>: Kabinet pertama baru dibentuk pasca proklamasi September 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kegiatan perumusan Pancasila sebagai dasar • diskusi dan perdebatan-perdebatan dalam sidang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pada hari pertama sidang 29 Mei 1945, Ketua BPUPKI Dr. Radjiman meminta anggota...", "options": ["Menghitung ketersediaan cadangan devisa emas bank peredaran", "Menyusun struktur anggaran belanja kementerian pertahanan", "Mengemukakan gagasan tentang dasar Indonesia merdeka", "Menentukan batas koordinat laut teritorial kepulauan nusantara", "Merancang undang-undang pemilihan umum kepala daerah kota"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Permintaan Ketua BPUPKI pada Sidang Pertama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada hari pertama sidang pertama BPUPKI pada tanggal 29 Mei 1945, <strong>Ketua BPUPKI meminta para anggota BPUPKI untuk mengemukakan dasar Indonesia merdeka</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Pertanyaan pembuka dari Dr. Radjiman Wediodiningrat (*'Apakah dasar dari negara Indonesia yang akan kita bentuk?'*) memicu lahirnya pemikiran-pemikiran filosofis brilian dari tokoh-tokoh utama pergerakan nasional.</p><p>Jawaban tepat: <strong>C. Mengemukakan gagasan tentang dasar Indonesia merdeka</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cadangan devisa emas dibahas dalam komisi perekonomian kemudian.</li><li><strong>B</strong>: Anggaran belanja dibahas setelah kedaulatan negara terbentuk.</li><li><strong>D</strong>: Batas wilayah laut dibahas khusus pada masa persidangan kedua.</li><li><strong>E</strong>: Pilkada adalah instrumen pengisian jabatan politik pasca kemerdekaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pada hari pertama sidang pertama BPUPKI • meminta para anggota BPUPKI untuk mengemukakan dasar</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tiga tokoh bangsa yang menyampaikan pidato konseptual mengenai dasar negara adalah...", "options": ["Sutan Sjahrir, Amir Sjarifuddin, dan Tan Malaka pejuang", "Ki Hadjar Dewantara, K.H. Ahmad Dahlan, dan Hasyim Asyari", "Dr. Soetomo, H.O.S. Tjokroaminoto, dan Douwes Dekker perintis", "Mr. Moh. Yamin, Prof. R. Soepomo, dan Ir. Soekarno", "Mr. Kasman Singodimedjo, Chaerul Saleh, dan Wikana pemuda"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Tokoh Konseptor Dasar Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada sidang pertama BPUPKI, tiga anggota yaitu <strong>Mr. Moh. Yamin (29 Mei), Prof. R. Soepomo (31 Mei), dan Ir. Soekarno (1 Juni 1945)</strong> masing-masing mengemukakan pandangan lima asas dasar negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Ketiga tokoh tersebut mewakili spektrum keilmuan hukum, filsafat kenegaraan, dan kepemimpinan politik pergerakan yang meletakkan pilar-pilar konseptual bagi perumusan falsafah dasar Pancasila.</p><p>Jawaban tepat: <strong>D. Mr. Moh. Yamin, Prof. R. Soepomo, dan Ir. Soekarno</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sutan Sjahrir dan Tan Malaka tidak berpidato dasar negara di BPUPKI.</li><li><strong>B</strong>: Ahmad Dahlan dan Hasyim Asyari adalah tokoh pendiri ormas Islam.</li><li><strong>C</strong>: Tjokroaminoto dan Dr. Soetomo telah wafat sebelum sidang BPUPKI 1945.</li><li><strong>E</strong>: Kasman dan Wikana adalah eksponen pergerakan pemuda Menteng 31.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Yamin, Prof. R. Soepomo, dan Ir. Soekarno • lima asas atau lima dasar negara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Rumusan lima asas dasar negara yang disampaikan dalam pidato lisan Moh. Yamin adalah...", "options": ["Peri Kebangsaan, Kemanusiaan, Ketuhanan, Kerakyatan, Kesejahteraan", "Sosio Nasionalisme, Sosio Demokrasi, dan Ketuhanan berkebudayaan", "Persatuan, Kekeluargaan, Keseimbangan Lahir Batin, dan Musyawarah", "Kemerdekaan, Kedaulatan, Perdamaian Abadi, dan Keadilan Sosial", "Ketuhanan, Kemanusiaan, Persatuan, Kerakyatan, dan Keadilan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rumusan Pidato Lisan Moh. Yamin 29 Mei 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada tanggal 29 Mei 1945, Mr. Moh. Yamin menyampaikan pidato lisan lima asas: <strong>1) Peri Kebangsaan, 2) Peri Kemanusiaan, 3) Peri Ketuhanan, 4) Peri Kerakyatan, dan 5) Kesejahteraan Rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Karakteristik khas usulan lisan Moh. Yamin ditandai dengan penggunaan awalan 'Peri-' pada empat asas pertamanya yang mencerminkan etika perikemanusiaan dan keadaban luhur bangsa timur.</p><p>Jawaban tepat: <strong>A. Peri Kebangsaan, Kemanusiaan, Ketuhanan, Kerakyatan, Kesejahteraan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sosio Nasionalisme dan Sosio Demokrasi adalah Trisila Soekarno.</li><li><strong>C</strong>: Persatuan dan Kekeluargaan adalah rumusan pidato Soepomo.</li><li><strong>D</strong>: Kemerdekaan dan perdamaian adalah rumusan alinea Pembukaan.</li><li><strong>E</strong>: Ketuhanan hingga Keadilan adalah rumusan baku UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Mr. Moh. Yamin menyampaikan dalam pidatonya • Peri Kebangsaan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Ciri unik dari usulan dasar negara yang diajukan oleh Mr. Moh. Yamin adalah...", "options": ["Disampaikan dalam bahasa Belanda tanpa naskah terjemahan", "Disusun bersama penasehat militer jenderal tentara sekutu", "Meniadakan pencantuman norma ketuhanan dalam dasar hukum negara", "Menghendaki bentuk monarki kekaisaran serikat bagi indonesia", "Rumusan rancangan tertulis berbeda dengan isi pidato lisannya"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dualitas Rumusan Usulan Moh. Yamin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Di samping pidato lisan, Moh. Yamin menyampaikan secara tertulis rancangan UUD di mana <strong>lima asas dasar negara rumusannya berbeda dengan yang diucapkannya dalam pidatonya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Dokumen sejarah mencatat bahwa Moh. Yamin menyerahkan lampiran naskah rancangan Pembukaan UUD tertulis yang redaksi lima asasnya lebih sistematis dan mendekati rumusan Pembukaan UUD 1945 yang tercantum dalam Pembukaan UUD 1945.</p><p>Jawaban tepat: <strong>E. Rumusan rancangan tertulis berbeda dengan isi pidato lisannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pidato disampaikan dalam bahasa Indonesia yang fasih dan puitis.</li><li><strong>B</strong>: Penyusunan naskah murni inisiatif pemikiran beliau secara mandiri.</li><li><strong>C</strong>: Moh. Yamin justru menempatkan Ketuhanan Yang Maha Esa pada sila pertama.</li><li><strong>D</strong>: Moh. Yamin memperjuangkan bentuk Republik berdaulat rakyat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Lima asas tersebut rumusannya berbeda</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Asas pertama yang tercantum dalam rancangan naskah tertulis Moh. Yamin adalah...", "options": ["Peri Kebangsaan rakyat nusantara yang merdeka", "Kedaulatan hukum dan keadilan peradilan perdata", "Ketuhanan Yang Maha Esa sebagai pilar keimanan", "Persatuan tanah air kepulauan melayu timur", "Kekeluargaan dan gotong royong warga masyarakat"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sila Pertama Rumusan Tertulis Moh. Yamin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam rancangan tertulis UUD yang diajukan Moh. Yamin, asas pertama dirumuskan berbunyi <strong>'Ketuhanan Yang Maha Esa'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Berbeda dengan pidato lisannya yang menempatkan Peri Kebangsaan di urutan pertama dan Peri Ketuhanan di urutan ketiga, naskah tertulis Moh. Yamin secara berani menempatkan sila Ketuhanan Yang Maha Esa di urutan paling awal.</p><p>Jawaban tepat: <strong>C. Ketuhanan Yang Maha Esa sebagai pilar keimanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Peri Kebangsaan adalah urutan pertama dalam pidato lisannya.</li><li><strong>B</strong>: Kedaulatan hukum bukan rumusan sila pertama naskah tertulis.</li><li><strong>D</strong>: Persatuan kepulauan bukan frasa asas tertulis Moh. Yamin.</li><li><strong>E</strong>: Kekeluargaan adalah usulan Prof. Soepomo pada 31 Mei 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Ketuhanan Yang Maha Esa • Mr. Muh. Yamin menyampaikan pula secara tertulis</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Asas kedua dan ketiga dalam naskah tertulis rancangan Moh. Yamin berbunyi...", "options": ["Musyawarah mufakat dan Kesejahteraan rakyat pekerja", "Keseimbangan lahir batin dan Kekeluargaan masyarakat", "Internasionalisme bangsa dan Keadilan peradilan umum", "Persatuan Indonesia serta Kemanusiaan Adil dan Beradab", "Sosio Nasionalisme dan Sosio Demokrasi kerakyatan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sila Kedua dan Ketiga Tertulis Moh. Yamin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rumusan tertulis asas kedua dan ketiga Moh. Yamin adalah <strong>b. Kebangsaan Persatuan Indonesia</strong> dan <strong>c. Rasa Kemanusiaan yang Adil dan Beradab</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Secara historis, kedua frasa tersebut menjadi rujukan penting yang sangat mirip dengan rumusan Sila Kedua dan Sila Ketiga dalam Pembukaan UUD 1945 pasca pengesahan PPKI.</p><p>Jawaban tepat: <strong>D. Persatuan Indonesia serta Kemanusiaan Adil dan Beradab</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Musyawarah dan kesejahteraan adalah asas keempat dan kelima.</li><li><strong>B</strong>: Keseimbangan lahir batin adalah asas pidato Prof. Soepomo.</li><li><strong>C</strong>: Internasionalisme adalah frasa pidato Bung Karno 1 Juni.</li><li><strong>E</strong>: Sosio Nasionalisme adalah Trisila perasan Bung Karno.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kebangsaan Persatuan Indonesia • Rasa Kemanusiaan yang Adil dan Beradab</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Lima dasar negara yang dikemukakan Prof. R. Soepomo pada 31 Mei 1945 meliputi...", "options": ["Peri Kebangsaan, Kemanusiaan, Ketuhanan, Kerakyatan, Kesejahteraan", "Persatuan, Kekeluargaan, Keseimbangan Lahir Batin, Musyawarah, Keadilan Rakyat", "Kebangsaan, Internasionalisme, Mufakat, Kesejahteraan Sosial, Ketuhanan", "Gotong Royong, Usaha Bersama, Kemakmuran, Keadilan, Kedaulatan", "Keimanan, Ketaqwaan, Keadaban, Kerakyatan, Kesejahteraan Bersama"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Lima Asas Dasar Negara Usulan Prof. Soepomo</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Prof. R. Soepomo mengemukakan lima asas: <strong>1. Persatuan, 2. Kekeluargaan, 3. Keseimbangan Lahir dan Batin, 4. Musyawarah, dan 5. Keadilan Rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Rumusan Soepomo memancarkan cita negara integralistik dengan menekankan nilai kekeluargaan, harmoni lahir batin, dan keadilan bagi seluruh rakyat tanpa mendasarkan diri pada pemikiran individualisme barat.</p><p>Jawaban tepat: <strong>B. Persatuan, Kekeluargaan, Keseimbangan Lahir Batin, Musyawarah, Keadilan Rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Peri Kebangsaan hingga Kesejahteraan adalah usulan Moh. Yamin.</li><li><strong>C</strong>: Kebangsaan hingga Ketuhanan Berkebudayaan adalah usulan Soekarno.</li><li><strong>D</strong>: Gotong royong adalah Ekasila perasan Soekarno.</li><li><strong>E</strong>: Keimanan dan ketaqwaan adalah terminologi pembinaan moralitas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dalam pidatonya Prof. R. Soepomo mengemukakan • Keseimbangan Lahir dan Batin</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Asas 'Keseimbangan Lahir dan Batin' yang digagas Soepomo menegaskan pentingnya...", "options": ["Penumpukan kekayaan materi untuk membiayai belanja perang militer", "Pembatasan perdagangan internasional demi menjaga swasembada total", "Pemisahan mutlak urusan keagamaan dari seluruh kebijakan publik", "Penyerahan tata peradilan sepenuhnya kepada kebiasaan adat istiadat", "Keharmonisan pemenuhan kebutuhan fisik jasmani dan spiritual rohani"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Keseimbangan Lahir dan Batin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Asas <strong>Keseimbangan Lahir dan Batin</strong> menekankan bahwa penyelenggaraan negara harus menyelaraskan dimensi pembangunan fisik material (lahir) dan dimensi moral spiritual (batin).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Manusia Indonesia tidak hanya memburu kemajuan ekonomi kebendaan, melainkan wajib memelihara ketenangan batiniah, budi pekerti luhur, dan hubungan harmonis dengan Tuhan Yang Maha Esa dan sesama makhluk.</p><p>Jawaban tepat: <strong>E. Keharmonisan pemenuhan kebutuhan fisik jasmani dan spiritual rohani</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penumpukan materi demi perang bertentangan dengan kemanusiaan.</li><li><strong>B</strong>: Isolasi perdagangan mematikan pertumbuhan perekonomian bangsa.</li><li><strong>C</strong>: Pemisahan mutlak urusan agama adalah konsep sekularisme ekstrem.</li><li><strong>D</strong>: Penyerahan peradilan total mengabaikan sistem hukum nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keseimbangan Lahir dan Batin • Prof. R. Soepomo mengemukakan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Asas kelima dalam rumusan pidato dasar negara Prof. R. Soepomo berbunyi...", "options": ["Keadilan Rakyat sebagai muara perlindungan bersama", "Kesejahteraan Sosial bagi seluruh kaum buruh tani", "Keadilan Sosial bagi Seluruh Rakyat Indonesia merdeka", "Kemakmuran bersama berlandaskan asas persaudaraan", "Pemerataan kepemilikan tanah dan sumber daya alam"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Keadilan Rakyat dalam Konsepsi Soepomo</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Asas kelima dalam pidato 31 Mei 1945 oleh Prof. R. Soepomo dirumuskan secara otentik dengan istilah <strong>'Keadilan Rakyat'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Soepomo menggunakan istilah 'Keadilan Rakyat' untuk menekankan bahwa hukum dan kebijakan negara harus menghadirkan rasa keadilan nyata yang dirasakan oleh seluruh lapisan rakyat jelata tanpa diskriminasi kedudukan.</p><p>Jawaban tepat: <strong>A. Keadilan Rakyat sebagai muara perlindungan bersama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kesejahteraan sosial adalah asas keempat usulan Ir. Soekarno.</li><li><strong>C</strong>: Keadilan Sosial bagi Seluruh Rakyat adalah Sila V UUD 1945.</li><li><strong>D</strong>: Kemakmuran bersama adalah frasa penjelasan ekonomi nasional.</li><li><strong>E</strong>: Pemerataan tanah adalah tujuan pokok undang-undang pokok agraria.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keadilan Rakyat • Prof. R. Soepomo mengemukakan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Lima asas dasar negara yang diusulkan oleh Ir. Soekarno pada 1 Juni 1945 adalah...", "options": ["Peri Kebangsaan, Kemanusiaan, Ketuhanan, Kerakyatan, Kesejahteraan", "Sosio Nasionalisme, Sosio Demokrasi, dan Ketuhanan yang berkebudayaan", "Persatuan, Kekeluargaan, Keseimbangan Lahir Batin, Musyawarah, Keadilan", "Kebangsaan, Internasionalisme, Mufakat, Kesejahteraan Sosial, Ketuhanan", "Ketuhanan, Kemanusiaan, Persatuan, Musyawarah, dan Keadilan Sosial"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Lima Asas Dasar Negara Pidato Ir. Soekarno</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada 1 Juni 1945, Ir. Soekarno mengusulkan lima asas: <strong>1. Kebangsaan Indonesia, 2. Internasionalisme atau Perikemanusiaan, 3. Mufakat atau Demokrasi, 4. Kesejahteraan Sosial, dan 5. Ketuhanan yang Berkebudayaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Pidato Bung Karno yang berapi-api mensintesiskan nasionalisme kebangsaan yang berperikemanusiaan dengan sistem demokrasi permusyawaratan, keadilan ekonomi sosial, dan penghayatan nilai ketuhanan yang berbudi luhur.</p><p>Jawaban tepat: <strong>D. Kebangsaan, Internasionalisme, Mufakat, Kesejahteraan Sosial, Ketuhanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Peri Kebangsaan hingga Kesejahteraan adalah pidato Moh. Yamin.</li><li><strong>B</strong>: Sosio Nasionalisme dan Demokrasi adalah Trisila perasan.</li><li><strong>C</strong>: Persatuan hingga Keadilan Rakyat adalah pidato Prof. Soepomo.</li><li><strong>E</strong>: Ketuhanan hingga Keadilan Sosial adalah urutan resmi UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pada tanggal 1 Juni 1945, tibalah giliran Ir. Soekarno • mengusulkan pula</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Nama 'Pancasila' yang diusulkan Bung Karno diperoleh atas petunjuk seorang...", "options": ["Sahabat karib beliau yang merupakan seorang ahli bahasa", "Perwira tinggi pimpinan komando angkatan perang jepang", "Utusan khusus perdana menteri dari kementerian luar negeri", "Wartawan media massa perintis surat kabar kabar asia", "Saudagar kaya raya penyumbang dana perjuangan gerilya"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemberian Nama Pancasila Saran Ahli Bahasa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ir. Soekarno mengusulkan nama dasar negara dinamakan <strong>Pancasila yang mana istilah itu diperolehnya dari seorang temannya yang ahli bahasa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Dalam pidatonya, Bung Karno menjelaskan bahwa 'Panca' berarti lima dan 'Sila' berarti dasar atau asas. Beliau meminta saran sahabatnya yang ahli bahasa Sansekerta untuk menamai lima prinsip dasar tersebut sehingga lahirlah nama Pancasila.</p><p>Jawaban tepat: <strong>A. Sahabat karib beliau yang merupakan seorang ahli bahasa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pihak militer pendudukan Jepang tidak menciptakan nama Pancasila.</li><li><strong>C</strong>: Bukan usulan kementerian luar negeri negara asing.</li><li><strong>D</strong>: Bukan berasal dari inisiatif wartawan media kabar.</li><li><strong>E</strong>: Saudagar pejuang membantu logistik bukan peristilahan bahasa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>diusulkan untuk diberi nama Pancasila • diperolehnya dari seorang temannya yang ahli bahasa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pidato bersejarah Bung Karno 1 Juni 1945 pertama kali dipublikasikan pada 1947 dalam buku...", "options": ["Indonesia Menggugat di hadapan sidang pengadilan kolonial", "Sarinah kewajiban wanita dalam revolusi republik indonesia", "Mencapai Indonesia Merdeka risalah panduan kader pergerakan", "Di Bawah Bendera Revolusi kumpulan artikel surat kabar", "Lahirnya Pancasila sebagai risalah pidato dasar negara"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Publikasi Buku Lahirnya Pancasila Tahun 1947</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada tahun 1947, pidato 1 Juni Ir. Soekarno dipublikasikan dalam bentuk buku berjudul <strong>'Lahirnya Pancasila'</strong>, yang melahirkan anggapan umum hari lahir Pancasila pada 1 Juni.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Penerbitan buku 'Lahirnya Pancasila' pada masa revolusi kemerdekaan bertujuan menyebarluaskan landasan falsafah negara kepada seluruh rakyat dan laskar pejuang guna membakar semangat mempertahankan kedaulatan republik.</p><p>Jawaban tepat: <strong>E. Lahirnya Pancasila sebagai risalah pidato dasar negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Indonesia Menggugat adalah pledoi pembelaan Bung Karno tahun 1930.</li><li><strong>B</strong>: Sarinah adalah buku kajian peranan perempuan terbit tahun 1947.</li><li><strong>C</strong>: Mencapai Indonesia Merdeka adalah risalah politik tahun 1933.</li><li><strong>D</strong>: Di Bawah Bendera Revolusi terbit pada era tahun 1959-1960an.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tahun 1947, pidato Ir. Soekarno tersebut • dalam bentuk sebuah buku yang berjudul Lahirnya Pancasila</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Menurut teks modul, perumusan dasar negara oleh Bung Karno didasarkan pada pola pikir...", "options": ["Mistik supranatural ajaran para pujangga keraton masa lalu", "Legalistik formal hukum perdata perdagangan bangsa eropa", "Dialektis atau historis materialisme dalam melihat dinamika", "Statis dogmatis tanpa membuka dialog kebangsaan luas", "Pragmatisme pasar modal berorientasi keuntungan ekonomi"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pola Berpikir Dialektis Perumusan Soekarno</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perumusan dasar negara oleh Ir. Soekarno <strong>tidak didasarkan kepada pola berfikir filosofis/religius, melainkan kepada pola berfikir dialektis atau historis materialisme</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Pendekatan dialektis Bung Karno mempertemukan tesis dan antitesis (seperti kebangsaan vs kemanusiaan, demokrasi politik vs keadilan sosial) untuk menghasilkan sintesis luhur (*Sosio-Nasionalisme* dan *Sosio-Demokrasi*).</p><p>Jawaban tepat: <strong>C. Dialektis atau historis materialisme dalam melihat dinamika</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pola mistik supranatural bukan kerangka ilmiah pidato 1 Juni.</li><li><strong>B</strong>: Pendekatan legalistik perdata adalah corak pemikiran yuridis barat.</li><li><strong>D</strong>: Pola statis dogmatis berlawanan dengan jiwa keterbukaan Pancasila.</li><li><strong>E</strong>: Pragmatisme pasar bertentangan dengan keadilan sosial kerakyatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tidak didasarkan kepada pola berfikir filosofis/ religius • berfikir dialektis atau historis materialisme</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perpaduan antara sila Kebangsaan Indonesia dan sila Internasionalisme menghasilkan...", "options": ["Sosio Demokrasi kerakyatan yang berkeadilan merata", "Sosio Nasionalisme cinta tanah air yang berperikemanusiaan", "Chauvinisme sempit yang mengagungkan bangsa sendiri", "Kosmopolitanisme semu tanpa batas kedaulatan tanah air", "Sindikalisme serikat buruh dalam menguasai parlemen negara"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Sosio Nasionalisme Bung Karno</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sila Kebangsaan disandingkan dengan Internasionalisme/Perikemanusiaan diperas menjadi konsep <strong>'Sosio Nasionalisme'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Bung Karno menolak nasionalisme sempit (*chauvinisme*) yang menindas bangsa lain. Sosio Nasionalisme adalah nasionalisme Indonesia yang bermekaran di taman sarinya internasionalisme berperikemanusiaan.</p><p>Jawaban tepat: <strong>B. Sosio Nasionalisme cinta tanah air yang berperikemanusiaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sosio Demokrasi adalah perpaduan Mufakat dan Kesejahteraan Sosial.</li><li><strong>C</strong>: Chauvinisme adalah racun nasionalisme yang ditentang keras.</li><li><strong>D</strong>: Kosmopolitanisme pasif mengikis rasa cinta dan bela tanah air.</li><li><strong>E</strong>: Sindikalisme adalah paham serikat pekerja radikal eropa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>disandingkan dengan • menjadi “Sosio Nasionalisme”</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Penyatuan sila Mufakat/Demokrasi dengan sila Kesejahteraan Sosial melahirkan konsep...", "options": ["Sosio Demokrasi keadilan politik dan kesejahteraan ekonomi", "Sosio Nasionalisme pertahanan keutuhan kepulauan", "Kapitalisme pasar bebas yang mengutamakan pemodal besar", "Oligarki kepemimpinan partai politik mayoritas dewan", "Etatisme birokrasi yang mematikan partisipasi usaha swasta"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Sosio Demokrasi Bung Karno</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sila Mufakat/Demokrasi disandingkan dengan sila Kesejahteraan Sosial melahirkan sintesis <strong>'Sosio Demokrasi'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Sosio Demokrasi menolak demokrasi Barat yang hanya menjamin kebebasan politik di parlemen namun membiarkan rakyat tertindas secara ekonomi. Demokrasi Indonesia wajib mendatangkan keadilan sosial dan kemakmuran nyata.</p><p>Jawaban tepat: <strong>A. Sosio Demokrasi keadilan politik dan kesejahteraan ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sosio Nasionalisme adalah perasan kebangsaan dan kemanusiaan.</li><li><strong>C</strong>: Kapitalisme pasar bebas menolak campur tangan keadilan sosial.</li><li><strong>D</strong>: Oligarki politik bertentangan dengan asas kerakyatan musyawarah.</li><li><strong>E</strong>: Etatisme mematikan inisiatif masyarakat yang dijamin Pancasila.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sila Mufakat/ Demokrasi disandingkan dengan • sila kesejahteraan Sosial, menjadi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tiga prinsip dasar yang terkandung dalam konsep 'Trisila' Bung Karno adalah...", "options": ["Persatuan, Musyawarah, dan Keadilan Sosial seluruh rakyat", "Kemerdekaan, Perdamaian Abadi, dan Piagam Hak Asasi Manusia", "Kedaulatan Rakyat, Kemandirian Fiskal, dan Pertahanan Negara", "Sosio Nasionalisme, Sosio Demokrasi, dan Nilai Ketuhanan", "Ekonomi Kerakyatan, Kepastian Hukum, dan Reformasi Birokrasi"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Asas dalam Konsep Trisila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Lima asas dasar negara dapat diperas menjadi tiga asas (<strong>Trisila</strong>), yaitu: <strong>1. Sosio Nasionalisme, 2. Sosio Demokrasi, dan 3. Ketuhanan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Dalam pidato 1 Juni 1945, Bung Karno menawarkan alternatif jika peserta sidang menghendaki rumusan yang lebih padat. Lima dasar dapat diringkas menjadi Trisila tanpa mengurangi bobot substansi filosofisnya.</p><p>Jawaban tepat: <strong>D. Sosio Nasionalisme, Sosio Demokrasi, dan Nilai Ketuhanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Persatuan dan musyawarah adalah istilah pokok lima sila UUD.</li><li><strong>B</strong>: Kemerdekaan dan perdamaian adalah materi pembukaan UUD 1945.</li><li><strong>C</strong>: Kemandirian fiskal adalah pilar kebijakan ekonomi perbendaharaan.</li><li><strong>E</strong>: Reformasi birokrasi adalah agenda modernisasi aparatur sipil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>lima dasar tadi menjadi tiga, yang disebut • Trisila</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Intisari pamungkas dari Trisila apabila diperas kembali menjadi satu asas (Ekasila) adalah...", "options": ["Individualisme persaingan pasar terbuka", "Kerahasiaan dokumen penyelenggaraan administrasi dinas", "Hukum kepatuhan mutlak kepada penguasa pusat", "Sentralisasi perencanaan anggaran belanja modal", "Gotong Royong sebagai jiwa persaudaraan asli"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemerasan Trisila Menjadi Ekasila</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Selanjutnya Trisila tersebut dapat diperas kembali menjadi satu asas tunggal yang disebut <strong>Ekasila</strong>, yaitu <strong>'Gotong-Royong'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Bung Karno menegaskan bahwa hakikat sejati bangsa Indonesia berakar pada semangat gotong royong: pembanting tulang bersama, pemerasan keringat bersama, dan perjuangan bantu-binantu demi kebahagiaan seluruh tumpah darah.</p><p>Jawaban tepat: <strong>E. Gotong Royong sebagai jiwa persaudaraan asli</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Individualisme adalah paham asing yang ditolak bapak bangsa.</li><li><strong>B</strong>: Kerahasiaan dokumen berlebihan melanggar transparansi publik.</li><li><strong>C</strong>: Kepatuhan mutlak tanpa kritik mencirikan sistem tirani totaliter.</li><li><strong>D</strong>: Sentralisasi anggaran adalah instrumen teknis perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>“Trisila” itu diperas menjadi “Ekasila” • yaitu “Gotong-Royong”</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pada akhir Sidang I BPUPKI dibentuk Panitia Delapan pimpinan Bung Karno dengan tugas...", "options": ["Mengadakan pembelian persenjataan laras panjang dari eropa", "Mengumpulkan dan menggolongkan usulan para anggota sidang", "Membentuk pasukan pengamanan istana kepresidenan jakarta", "Menetapkan tarif cukai tembakau dan garam seluruh daerah", "Mengadili pimpinan militer asing dalam mahkamah peradilan khusus"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembentukan dan Tugas Pokok Panitia Delapan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketua sidang membentuk panitia kecil yang dikenal sebagai <strong>Panitia Delapan yang diketuai Ir. Soekarno</strong> yang ditugasi <strong>mengumpulkan dan menggolong-golongkan usul-usul yang diajukan peserta sidang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Mengingat banyaknya usulan tertulis dan lisan yang masuk selama sidang pertama, Panitia Delapan dibentuk sebagai badan perumus pendahuluan untuk menginventarisasi aspirasi para anggota mengenai syarat-syarat Indonesia merdeka.</p><p>Jawaban tepat: <strong>B. Mengumpulkan dan menggolongkan usulan para anggota sidang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembelian senjata adalah tugas perwira logistik tentara pasca merdeka.</li><li><strong>C</strong>: Pengamanan istana baru dibentuk pasca proklamasi oleh Paspampres.</li><li><strong>D</strong>: Tarif cukai adalah materi perundang-undangan fiskal APBN.</li><li><strong>E</strong>: Pengadilan perang dilaksanakan oleh mahkamah militer sekutu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>sebagai Panitia Delapan yang diketuai Ir. Soekarno • mengumpulkan dan menggolong-golongkan usul-usul</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pertemuan penting Panitia Delapan dengan anggota BPUPKI di Jakarta diselenggarakan pada...", "options": ["Tanggal 1 Juni 1945 sesaat setelah pidato dasar negara", "Tanggal 10 Juli 1945 pada pembukaan masa persidangan kedua", "Tanggal 22 Juni 1945 menjelang pembentukan panitia sembilan", "Tanggal 7 Agustus 1945 pada saat pembentukan panitia persiapan", "Tanggal 17 Agustus 1945 menjelang pembacaan teks proklamasi"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pertemuan Bersejarah 22 Juni 1945 di Jakarta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pada tanggal 22 Juni 1945, ketua Panitia Delapan mengadakan pertemuan</strong> dengan anggota BPUPKI yang berada di Jakarta yang kemudian melahirkan kesepakatan monumental Piagam Jakarta.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Pertemuan 22 Juni 1945 di Gedung Pegangsaan Timur 56 Jakarta menjadi ajang musyawarah intensif yang mempertemukan golongan kebangsaan dan golongan Islam, membuahkan naskah konsensus Rancangan Pembukaan UUD.</p><p>Jawaban tepat: <strong>C. Tanggal 22 Juni 1945 menjelang pembentukan panitia sembilan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tanggal 1 Juni 1945 adalah penutupan resmi masa sidang pertama.</li><li><strong>B</strong>: Tanggal 10 Juli 1945 adalah pembukaan masa sidang kedua BPUPKI.</li><li><strong>D</strong>: Tanggal 7 Agustus 1945 adalah pembentukan resmi PPKI oleh Jepang.</li><li><strong>E</strong>: Tanggal 17 Agustus 1945 adalah proklamasi kemerdekaan Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pada tanggal 22 Juni • ketua Panitia Delapan telah mengadakan pertemuan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pertemuan penting 22 Juni 1945 di Jakarta mempertemukan dua aliran pemikiran yaitu...", "options": ["Golongan pedagang maritim dan golongan pengusaha perkebunan", "Golongan intelektual universitas dan golongan buruh industri", "Golongan bangsawan keraton dan golongan militer pejuang peta", "Golongan pemuda bawah tanah dan golongan pejabat pangreh praja", "Golongan paham kebangsaan dan golongan paham agama"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Kekuatan Utama dalam Rapat 22 Juni 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pertemuan tanggal 22 Juni 1945 merupakan musyawarah konsensus bersejarah antara <strong>golongan/paham kebangsaan dan golongan/paham agama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Kedua kutub pemikiran tersebut mendominasi diskursus perumusan bentuk negara. Golongan kebangsaan menghendaki negara persatuan nasional yang netral agama, sedangkan golongan Islam memperjuangkan dasar negara yang bersendikan syariat Islam.</p><p>Jawaban tepat: <strong>E. Golongan paham kebangsaan dan golongan paham agama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pedagang dan pengusaha perkebunan adalah entitas ekonomi.</li><li><strong>B</strong>: Intelektual dan buruh bukan klasifikasi fraksi sidang BPUPKI.</li><li><strong>C</strong>: Bangsawan dan militer bukan dua poros ideologis rapat 22 Juni.</li><li><strong>D</strong>: Pemuda dan pamong praja mewakili jalur strategi pergerakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pertemuan tersebut merupakan pertemuan antara • golongan/ paham kebangsaan dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Panitia kecil yang dibentuk pada pertemuan 22 Juni 1945 beranggotakan 9 tokoh disebut...", "options": ["Panitia Delapan perumus usul sidang", "Panitia Sembilan perancang kesepakatan dasar", "Panitia Kecil perancang undang-undang", "Panitia Persiapan Kemerdekaan Indonesia", "Komite Nasional Indonesia Pusat"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembentukan Panitia Sembilan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam rapat 22 Juni 1945, dibentuk <strong>Panitia Sembilan</strong> yang anggotanya terdiri dari 9 tokoh terkemuka mewakili golongan kebangsaan dan golongan Islam.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Panitia Sembilan beranggotakan: Soekarno, Hatta, Maramis, Abikoesno, Kahar Muzakir, Agus Salim, Subardjo, Wachid Hasjim, dan Yamin. Komposisi ini bertugas menyusun naskah kompromi hukum dasar negara.</p><p>Jawaban tepat: <strong>B. Panitia Sembilan perancang kesepakatan dasar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Panitia Delapan dibentuk sebelumnya pada akhir Sidang I BPUPKI.</li><li><strong>C</strong>: Panitia Kecil perancang UUD dibentuk kelak pada Sidang II BPUPKI.</li><li><strong>D</strong>: PPKI baru dibentuk pada bulan Agustus 1945 oleh Jepang.</li><li><strong>E</strong>: KNIP dibentuk pasca proklamasi kemerdekaan pada akhir Agustus.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Dalam rapat tersebut dibentuk Panitia Sembilan • anggotanya terdiri dari Ir.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Keberhasilan monumental dari musyawarah Panitia Sembilan ditandai oleh...", "options": ["Penyerahan mandat kekuasaan darurat kepada perwira tinggi jepang", "Pembubaran paksa seluruh organisasi pergerakan kedaerahan di jawa", "Tercapainya persetujuan bersama antara golongan agama dan kebangsaan", "Penetapan batas teritorial laut nusantara sejauh dua ratus mil", "Pembatalan hasil risalah pidato dasar negara yang telah diajukan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keberhasilan Konsensus Panitia Sembilan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panitia Sembilan telah mencapai hasil baik yang <strong>menghasilkan persetujuan dari golongan/paham agama (Islam) dan golongan/paham kebangsaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Keberhasilan Panitia Sembilan membuktikan kearifan musyawarah mufakat para pendiri bangsa. Mereka berhasil menjembatani polarisasi pandangan menjadi sebuah dokumen konsensus bersama (*gentlemen's agreement*).</p><p>Jawaban tepat: <strong>C. Tercapainya persetujuan bersama antara golongan agama dan kebangsaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bapak bangsa tidak menyerahkan mandat politik kepada Jepang.</li><li><strong>B</strong>: Organisasi pergerakan dihimpun dan dipersatukan energinya.</li><li><strong>D</strong>: Batas laut 200 mil adalah Deklarasi Djuanda tahun 1957.</li><li><strong>E</strong>: Risalah pidato justru menjadi bahan rujukan penyusunan naskah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>menghasilkan persetujuan dari • dan golongan/ paham kebangsaan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Naskah persetujuan bersama Panitia Sembilan oleh Panitia Delapan ditetapkan sebagai...", "options": ["Rancangan Maklumat Pemerintah Militer Bala Tentara Dai Nippon", "Rancangan Deklarasi Hak Asasi Manusia dan Warga Negara", "Rancangan Piagam Perdamaian Kawasan Perang Pasifik Barat", "Rancangan Preambule Hukum Dasar negara Indonesia", "Rancangan Anggaran Pendapatan dan Belanja Perbendaharaan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penetapan Rancangan Preambule Hukum Dasar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Persetujuan bersama tersebut termaktub dalam satu naskah yang oleh Panitia Delapan ditetapkan sebagai <strong>'Rancangan Preambule Hukum Dasar'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Preambule (pembukaan) diposisikan sebagai kepala konstitusi yang memuat asas-asas filosofis, tujuan bernegara, serta pernyataan kemerdekaan bangsa Indonesia sebelum memasuki pasal-pasal batang tubuh hukum dasar.</p><p>Jawaban tepat: <strong>D. Rancangan Preambule Hukum Dasar negara Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan maklumat militer asing melainkan dokumen hukum nasional.</li><li><strong>B</strong>: Deklarasi HAM PBB baru disahkan secara global pada tahun 1948.</li><li><strong>C</strong>: Bukan piagam perdamaian perang melainkan dasar hukum negara.</li><li><strong>E</strong>: Anggaran perbendaharaan adalah instrumen teknis keuangan publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Rancangan Preambule Hukum Dasar • hasil Panitia Sembilan tersebut</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Nama 'Jakarta Charter' atau 'Piagam Jakarta' untuk naskah Preambule diberikan oleh...", "options": ["Mr. Muhammad Yamin sastrawan dan ahli hukum", "Drs. Mohammad Hatta perumus naskah proklamasi", "Prof. R. Soepomo perancang batang tubuh konstitusi", "K.H. Abdul Wahid Hasjim perwakilan ulama islam", "Mr. A.A. Maramis perwakilan tokoh indonesia timur"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemberian Nama Piagam Jakarta oleh Moh. Yamin</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Adapun hasil Panitia Sembilan tersebut sebagai persetujuan golongan agama dan kebangsaan oleh <strong>Mr. Moh. Yamin disebut sebagai Jakarta Charter atau Piagam Jakarta</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Moh. Yamin memberikan penamaan puitis dan historis 'Jakarta Charter' (Piagam Jakarta) untuk mengenang bahwa naskah piagam kemerdekaan dan dasar falsafah bangsa tersebut dilahirkan di kota Jakarta pada 22 Juni 1945.</p><p>Jawaban tepat: <strong>A. Mr. Muhammad Yamin sastrawan dan ahli hukum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bung Hatta menyetujui rumusan namun bukan pencetus nama piagam.</li><li><strong>C</strong>: Prof. Soepomo berfokus pada kodifikasi pasal perancangan UUD.</li><li><strong>D</strong>: Wachid Hasjim mewakili aspirasi ulama Nahdlatul Ulama.</li><li><strong>E</strong>: A.A. Maramis mewakili golongan kebangsaan asal Sulawesi Utara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>oleh Mr. Moh. Yamin disebut • atau Piagam Jakarta</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Di dalam naskah Piagam Jakarta, rumusan dasar negara Pancasila termaktub pada...", "options": ["Alinea pertama yang menguraikan hak kemerdekaan segala bangsa", "Alinea keempat pada perumusan dasar negara dan pemerintahan", "Alinea kedua yang menegaskan perjuangan pergerakan kemerdekaan", "Alinea ketiga yang menyatakan berkat rahmat Allah yang maha kuasa", "Bagian penjelasan penutup mengenai hierarki peraturan daerah"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kandungan Rancangan Dasar Negara dalam Piagam Jakarta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Di dalam Rancangan Preambule Hukum Dasar (Piagam Jakarta) <strong>terdapat rancangan dasar negara Pancasila yang tertuang pada alinea keempat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Alinea keempat memuat sendi-sendi pokok pembentukan negara: tujuan nasional, penyusunan kemerdekaan dalam UUD, bentuk negara Republik yang berkedaulatan rakyat, dan lima asas falsafah dasar negara Pancasila.</p><p>Jawaban tepat: <strong>B. Alinea keempat pada perumusan dasar negara dan pemerintahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Alinea pertama memuat pernyataan filosofis hak kemerdekaan.</li><li><strong>C</strong>: Alinea kedua memuat cita-cita kemerdekaan bersatu berdaulat adil.</li><li><strong>D</strong>: Alinea ketiga memuat motivasi spiritual pengakuan rahmat Allah.</li><li><strong>E</strong>: Piagam Jakarta tidak memuat penjelasan hierarki peraturan daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>rancangan dasar negara Pancasila • Piagam Jakarta</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Rumusan otentik Sila Pertama Pancasila yang tercantum dalam naskah Piagam Jakarta adalah...", "options": ["Ketuhanan Yang Maha Esa bagi seluruh rakyat nusantara", "Kebebasan memeluk keyakinan beragama tanpa diskriminasi", "Ketuhanan yang berkebudayaan dan berbudi pekerti luhur", "Peri Ketuhanan yang menjunjung tinggi toleransi antarumat", "Ketuhanan, dengan kewajiban menjalankan syariat Islam bagi pemeluknya"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sila Pertama dalam Piagam Jakarta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sila pertama dalam Piagam Jakarta berbunyi: <strong>'1. Ketuhanan, dengan kewajiban menjalankan syariat Islam bagi pemeluk-pemeluknya'</strong> yang dikenal dengan 'tujuh kata'.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Klausul tujuh kata tersebut merupakan kompromi antara golongan Islam dan golongan kebangsaan pada 22 Juni 1945, yang kelak pada 18 Agustus 1945 disempurnakan menjadi 'Ketuhanan Yang Maha Esa' demi keutuhan NKRI.</p><p>Jawaban tepat: <strong>E. Ketuhanan, dengan kewajiban menjalankan syariat Islam bagi pemeluknya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ketuhanan YME adalah rumusan final UUD 1945 tanggal 18 Agustus.</li><li><strong>B</strong>: Kebebasan beragama adalah substansi norma Pasal 29 UUD 1945.</li><li><strong>C</strong>: Ketuhanan berkebudayaan adalah rumusan pidato 1 Juni Soekarno.</li><li><strong>D</strong>: Peri Ketuhanan adalah rumusan pidato lisan Moh. Yamin 29 Mei.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kewajiban menjalankan syariat Islam • Rancangan Preambule Hukum Dasar</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Rumusan asas kedua dalam naskah Piagam Jakarta berbunyi...", "options": ["Internasionalisme atau Perikemanusiaan yang merdeka", "Peri Kemanusiaan yang menjunjung tinggi persaudaraan", "Kemanusiaan yang Adil dan Beradab bagi seluruh manusia", "Rasa Kemanusiaan yang Adil dan Beradab seluruh rakyat", "Keadilan bagi sesama makhluk hidup di muka bumi"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sila Kedua dalam Piagam Jakarta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sila kedua dalam Piagam Jakarta tertulis persis berbunyi: <strong>'2. Kemanusiaan yang Adil dan Beradab'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Rumusan sila kedua dalam Piagam Jakarta telah memiliki redaksi final yang identik dengan teks resmi Pancasila yang disahkan oleh PPKI pada 18 Agustus 1945 dan termuat dalam Pembukaan UUD 1945.</p><p>Jawaban tepat: <strong>C. Kemanusiaan yang Adil dan Beradab bagi seluruh manusia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Internasionalisme adalah frasa usulan pidato Ir. Soekarno.</li><li><strong>B</strong>: Peri Kemanusiaan adalah usulan pidato lisan Mr. Moh. Yamin.</li><li><strong>D</strong>: Rasa Kemanusiaan adalah rumusan tertulis rancangan Moh. Yamin.</li><li><strong>E</strong>: Keadilan bagi makhluk hidup bukan rumusan sila dasar negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>2.Kemanusiaan yang Adil dan Beradab • rancangan dasar negara Pancasila yang terdapat dalam</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Rumusan Sila Ketiga dan Sila Keempat dalam naskah Piagam Jakarta adalah...", "options": ["Kebangsaan Indonesia dan Mufakat atau Demokrasi kerakyatan", "Peri Kebangsaan dan Peri Kerakyatan perwakilan seluruh daerah", "Sosio Nasionalisme dan Sosio Demokrasi perwakilan mufakat", "Persatuan Indonesia dan Kerakyatan dipimpin hikmat permusyawaratan", "Kekeluargaan bersama dan Musyawarah gotong royong warga"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sila Ketiga dan Keempat dalam Piagam Jakarta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sila ketiga dan keempat dalam Piagam Jakarta berbunyi: <strong>'3. Persatuan Indonesia'</strong> dan <strong>'4. Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Panitia Sembilan berhasil memadukan asas kebangsaan dan kedaulatan rakyat ke dalam rumusan sila yang baku. Redaksi kedua sila ini tidak mengalami perubahan dan dipertahankan secara utuh dalam UUD 1945.</p><p>Jawaban tepat: <strong>D. Persatuan Indonesia dan Kerakyatan dipimpin hikmat permusyawaratan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kebangsaan dan Mufakat adalah rumusan pidato 1 Juni Bung Karno.</li><li><strong>B</strong>: Peri Kebangsaan dan Peri Kerakyatan adalah pidato Moh. Yamin.</li><li><strong>C</strong>: Sosio Nasionalisme dan Demokrasi adalah Trisila Bung Karno.</li><li><strong>E</strong>: Kekeluargaan dan Musyawarah adalah pidato Prof. Soepomo.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>3.Persatuan Indonesia • 4.Kerakyatan yang dipimpin oleh hikmat kebijaksanaan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Rumusan Sila Kelima yang melengkapi naskah Piagam Jakarta berbunyi...", "options": ["Keadilan Sosial bagi Seluruh Rakyat Indonesia merdeka", "Kesejahteraan Sosial bagi kaum pekerja dan fakir miskin", "Keadilan Rakyat berdasarkan asas kekeluargaan bersama", "Kesejahteraan Rakyat yang merata di seluruh kepulauan", "Kemakmuran bersama yang berkeadilan lahir dan batin"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sila Kelima dalam Piagam Jakarta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sila kelima dalam naskah Piagam Jakarta berbunyi: <strong>'5. Keadilan Sosial bagi Seluruh Rakyat Indonesia'</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Sila kelima menjadi muara dan tujuan akhir dari kemerdekaan Indonesia. Negara didirikan bukan hanya untuk merdeka secara politis, melainkan untuk menciptakan keadilan sosial ekonomi yang dinikmati oleh seluruh rakyat tanpa terkecuali.</p><p>Jawaban tepat: <strong>A. Keadilan Sosial bagi Seluruh Rakyat Indonesia merdeka</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kesejahteraan Sosial adalah istilah asas pidato Bung Karno.</li><li><strong>C</strong>: Keadilan Rakyat adalah asas pidato Prof. R. Soepomo.</li><li><strong>D</strong>: Kesejahteraan Rakyat adalah asas pidato Mr. Moh. Yamin.</li><li><strong>E</strong>: Kemakmuran lahir batin adalah penjelasan tafsir kesejahteraan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>5.Keadilan Sosial bagi Seluruh Rakyat Indonesia • Piagam Jakarta adalah sebagai berikut</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Rancangan Preambule Hukum Dasar (Piagam Jakarta) secara resmi dilaporkan pada...", "options": ["Rapat perdana pembentukan pengurus kantor besar peta", "Pertemuan rahasia pemuda pejuang di asrama menteng 31", "Sidang kedua BPUPKI yang dimulai tanggal 10 Juli 1945", "Sidang pleno pertama komite nasional indonesia pusat", "Upacara peringatan hari proklamasi di pegangsaan timur"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pelaporan Piagam Jakarta pada Sidang Kedua BPUPKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rancangan dasar negara yang tertuang dalam <strong>Rancangan Preambule Hukum Dasar (Piagam Jakarta) dilaporkan oleh Panitia Delapan dalam sidang kedua BPUPKI</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Sidang kedua BPUPKI (10–16 Juli 1945) diawali dengan laporan hasil kerja masa reses oleh Ir. Soekarno selaku ketua Panitia Delapan dan Panitia Sembilan mengenai naskah konsensus Piagam Jakarta untuk disetujui forum pleno.</p><p>Jawaban tepat: <strong>C. Sidang kedua BPUPKI yang dimulai tanggal 10 Juli 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan laporan komando pertahanan militer PETA.</li><li><strong>B</strong>: Asrama Menteng 31 adalah basis gerakan pemuda radikal.</li><li><strong>D</strong>: KNIP baru menggelar sidang pleno pada akhir Agustus 1945.</li><li><strong>E</strong>: Pegangsaan Timur 56 adalah lokasi upacara detik proklamasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dilaporkan dalam sidang kedua BPUPKI • Rancangan Preambule Hukum Dasar</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pada tanggal 11 Juli 1945, Ketua BPUPKI membentuk tiga panitia kerja yaitu...", "options": ["Panitia Anggaran, Panitia Hubungan Luar Negeri, dan Panitia Agama", "Panitia Lambang Negara, Panitia Bendera, dan Panitia Lagu Kebangsaan", "Panitia Persenjataan, Panitia Logistik Beras, dan Panitia Wilayah", "Panitia Perancang UUD, Panitia Pembelaan Tanah Air, dan Panitia Keuangan", "Panitia Pemilihan Presiden, Panitia Kehakiman, dan Panitia Pegawai"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembentukan Tiga Panitia Kerja pada 11 Juli 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada tanggal 11 Juli 1945 dibentuk tiga panitia: <strong>1. Panitia Perancang UUD (ketua Ir. Soekarno), 2. Panitia Pembelaan Tanah Air (ketua Abikoesno Tjokrosoejoso), dan 3. Panitia Soal Keuangan dan Perekonomian (ketua Moh. Hatta)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Pembentukan tiga panitia ini bertujuan mempercepat penyelesaian naskah teknis penyelenggaraan negara Indonesia merdeka, membagi tugas bidang hukum dasar konstitusi, pertahanan militer, dan fondasi perekonomian.</p><p>Jawaban tepat: <strong>D. Panitia Perancang UUD, Panitia Pembelaan Tanah Air, dan Panitia Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Panitia Agama bukan panitia terpisah pada 11 Juli 1945.</li><li><strong>B</strong>: Panitia Lambang Negara dibentuk pada masa RIS tahun 1950.</li><li><strong>C</strong>: Panitia Logistik beras tidak dibentuk dalam struktur sidang.</li><li><strong>E</strong>: Pemilihan presiden diputuskan pada sidang PPKI 18 Agustus.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pada tanggal 11 Juli 1945 • ketua BPUPKI membentuk tiga</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Panitia kecil yang bertugas merumuskan naskah Batang Tubuh UUD diketuai oleh...", "options": ["Prof. R. Soepomo pakar hukum adat dan ketatanegaraan", "Mr. Achmad Soebardjo pejabat perwakilan kementerian", "Drs. Mohammad Hatta perumus pasal asas perekonomian", "Abikoesno Tjokrosoejoso tokoh perancang pertahanan", "Dr. K.R.T. Radjiman Wediodiningrat sesepuh dewan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ketua Panitia Kecil Perancang UUD</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Panitia Perancang UUD membentuk <strong>panitia kecil yang diketuai Prof. R. Soepomo</strong> yang bertugas menyusun draf rancangan Batang Tubuh UUD selama tiga hari kerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Keahlian mendalam Prof. Soepomo dalam bidang hukum tata negara dan sosiologi hukum adat menjadikannya figur paling tepat untuk merumuskan pasal-pasal konstitusi agar mencerminkan cita negara integralistik dan kekeluargaan.</p><p>Jawaban tepat: <strong>A. Prof. R. Soepomo pakar hukum adat dan ketatanegaraan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Achmad Soebardjo bertugas di bidang hubungan diplomasi luar negeri.</li><li><strong>C</strong>: Bung Hatta memimpin panitia bidang keuangan dan perekonomian.</li><li><strong>D</strong>: Abikoesno memimpin panitia bidang pembelaan tanah air.</li><li><strong>E</strong>: Dr. Radjiman bertindak sebagai ketua umum sidang pleno BPUPKI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Panitia Perancang UUD • membentuk panitia kecil yang diketuai</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Ketua Panitia Perancang UUD Ir. Soekarno menyampaikan laporan hasil tugas pada...", "options": ["Tanggal 1 Juni 1945 pada penutupan masa sidang perdana", "Tanggal 18 Agustus 1945 pada penetapan resmi undang-undang dasar", "Tanggal 7 Agustus 1945 pada saat pembentukan komite ppki", "Tanggal 17 Agustus 1945 sesaat sebelum naskah proklamasi dibaca", "Tanggal 14 Juli 1945 dalam rapat pleno Sidang Kedua BPUPKI"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Laporan Hasil Perancang UUD 14 Juli 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pada tanggal 14 Juli 1945, Ketua Perancang UUD Ir. Soekarno melaporkan hasil tugasnya</strong> kepada sidang kedua BPUPKI mengenai naskah rancangan kemerdekaan dan pembukaan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Setelah bekerja intensif, pada 14 Juli 1945 Bung Karno menyerahkan tiga hasil penting panitia perancang UUD: pernyataan Indonesia merdeka, naskah Pembukaan UUD, dan draf rancangan Batang Tubuh konstitusi.</p><p>Jawaban tepat: <strong>E. Tanggal 14 Juli 1945 dalam rapat pleno Sidang Kedua BPUPKI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tanggal 1 Juni 1945 adalah pidato kelahiran istilah Pancasila.</li><li><strong>B</strong>: Tanggal 18 Agustus 1945 adalah sidang pengesahan UUD oleh PPKI.</li><li><strong>C</strong>: Tanggal 7 Agustus 1945 adalah tanggal pembentukan PPKI.</li><li><strong>D</strong>: Tanggal 17 Agustus 1945 adalah hari pembacaan teks proklamasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pada tanggal 14 Juli 1945 • melaporkan hasil tugasnya kepada sidang kedua</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan laporan Panitia Perancang UUD, teks Pembukaan UUD 1945 diambil dari...", "options": ["Deklarasi Kemerdekaan Amerika Serikat tahun 1776 di philadelphia", "Alinea keempat Rancangan Preambule Hukum Dasar (Piagam Jakarta)", "Naskah manifesto politik Perhimpunan Indonesia di negeri belanda", "Maklumat kaisar jepang mengenai kemerdekaan tanpa syarat asia", "Piagam Magna Charta pembatasan kekuasaan monarki inggris"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sumber Naskah Pembukaan UUD 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rancangan Pembukaan UUD 1945 <strong>diambil dari alinea 4 Rancangan Preambule Hukum Dasar (Piagam Jakarta)</strong> yang disempurnakan oleh panitia perancang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Panitia Perancang UUD mengadopsi naskah Piagam Jakarta sebagai naskah Pembukaan UUD. Alinea keempat Piagam Jakarta yang memuat lima dasar negara dijadikan rumusan inti Pembukaan konstitusi negara.</p><p>Jawaban tepat: <strong>B. Alinea keempat Rancangan Preambule Hukum Dasar (Piagam Jakarta)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Deklarasi Amerika adalah rujukan kemerdekaan bangsa barat.</li><li><strong>C</strong>: Manifesto politik adalah dokumen pergerakan mahasiswa 1925.</li><li><strong>D</strong>: Maklumat kaisar adalah janji politis pemerintah pendudukan.</li><li><strong>E</strong>: Magna Charta adalah piagam pembatasan raja Inggris tahun 1215.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Rancangan Pembukaan UUD 1945 diambil dari • alinea 4 Rancangan Preambule</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Sidang BPUPKI secara bulat menerima seluruh naskah Pembukaan dan Batang Tubuh UUD pada...", "options": ["Tanggal 22 Juni 1945 saat penandatanganan piagam jakarta", "Tanggal 17 Agustus 1945 saat naskah proklamasi kemerdekaan", "Tanggal 7 Agustus 1945 saat peresmian pembentukan anggota ppki", "Tanggal 16 Juli 1945 pada penutupan masa sidang kedua BPUPKI", "Tanggal 18 Agustus 1945 saat rapat pleno pengesahan konstitusi"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerimaan Seluruh Naskah Konstitusi 16 Juli 1945</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pada tanggal 16 Juli 1945</strong>, Rancangan Preambule Hukum Dasar dan <strong>Rancangan Batang Tubuh UUD secara resmi dan bulat diterima dalam sidang BPUPKI</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Tanggal 16 Juli 1945 menandai puncak keberhasilan tugas BPUPKI. Seluruh rancangan konstitusi tertulis, yang memuat Pembukaan dan pasal-pasal Batang Tubuh, disetujui dengan suara bulat oleh seluruh anggota sidang.</p><p>Jawaban tepat: <strong>D. Tanggal 16 Juli 1945 pada penutupan masa sidang kedua BPUPKI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tanggal 22 Juni 1945 adalah rapat Panitia Sembilan bukan pleno.</li><li><strong>B</strong>: Tanggal 17 Agustus 1945 adalah momentum pembacaan proklamasi.</li><li><strong>C</strong>: Tanggal 7 Agustus 1945 adalah hari pembubaran resmi BPUPKI.</li><li><strong>E</strong>: Tanggal 18 Agustus 1945 adalah penetapan UUD oleh lembaga PPKI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pada tanggal 16 Juli 1945 • Rancangan Batang Tubuh UUD</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan tinjauan historis, perumusan naskah dasar negara Pancasila resmi lahir dari...", "options": ["Hasil kompromi paksa pejabat militer sekutu pasca perang dunia", "Instruksi tertulis gubernur jenderal hindia belanda di batavia", "Rangkaian musyawarah dan dinamika persidangan resmi BPUPKI", "Dekrit otoriter perdana menteri kabinet perserikatan bangsa", "Keputusan rapat terbatas staf khusus pertahanan jepang tokyo"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Simpulan Peran BPUPKI dalam Dasar Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dapat disimpulkan bahwa <strong>perumusan Pancasila sebagai dasar negara yang tercantum dalam Pembukaan UUD 1945 dirumuskan dalam sidang-sidang BPUPKI</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Fakta konstitusional membuktikan bahwa BPUPKI merupakan rahim historis lahirnya rumusan Pancasila. Dari pertukaran gagasan di forum inilah dasar filsafat negara Indonesia merdeka dirajut hingga menjadi konsensus nasional.</p><p>Jawaban tepat: <strong>C. Rangkaian musyawarah dan dinamika persidangan resmi BPUPKI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sekutu tidak ikut merumuskan falsafah dasar negara Indonesia.</li><li><strong>B</strong>: Gubernur jenderal Belanda telah menyerah tanpa syarat tahun 1942.</li><li><strong>D</strong>: Dekrit perdana menteri bukan sumber yuridis kelahiran Pancasila.</li><li><strong>E</strong>: Pemerintah Tokyo hanya memberikan izin pembentukan badan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pancasila sebagai dasar negara • dirumuskan dalam sidang-sidang BPUPKI</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Setelah menuntaskan seluruh tugas perancangan konstitusi, BPUPKI dibubarkan pada...", "options": ["Tanggal 7 Agustus 1945 dilanjutkan dengan pembentukan PPKI", "Tanggal 16 Juli 1945 sesaat setelah naskah konstitusi disetujui", "Tanggal 14 Agustus 1945 saat jepang menyerah kepada sekutu", "Tanggal 17 Agustus 1945 sesaat sebelum proklamasi kemerdekaan", "Tanggal 22 Agustus 1945 pada pembentukan komite nasional pusat"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembubaran BPUPKI dan Pembentukan PPKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setelah menyelesaikan tugasnya, <strong>BPUPKI dibubarkan, dan pada tanggal 7 Agustus 1945 dibentuk Panitia Persiapan Kemerdekaan Indonesia (PPKI)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Jenderal Terauchi menyetujui pembubaran BPUPKI pada 7 Agustus 1945 karena dianggap telah selesai menyelidiki bahan persiapan kemerdekaan, kemudian digantikan oleh PPKI (*Dokuritsu Junbi Inkai*) untuk mempersiapkan pemindahan kekuasaan.</p><p>Jawaban tepat: <strong>A. Tanggal 7 Agustus 1945 dilanjutkan dengan pembentukan PPKI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Tanggal 16 Juli 1945 adalah penutupan sidang bukan pembubaran.</li><li><strong>C</strong>: Tanggal 14 Agustus 1945 adalah kapitulasi Jepang kepada Sekutu.</li><li><strong>D</strong>: Tanggal 17 Agustus 1945 adalah deklarasi proklamasi kemerdekaan.</li><li><strong>E</strong>: Tanggal 22 Agustus 1945 adalah sidang PPKI membentuk BKR dan PNI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Setelah menyelesaikan tugasnya, BPUPKI dibubarkan • pada tanggal 7 Agustus</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Lembaga PPKI yang dibentuk pada 7 Agustus 1945 dipimpin oleh Dwitunggal...", "options": ["Dr. Radjiman Wediodiningrat dan Raden Pandji Soeroso", "Ki Hadjar Dewantara dan K.H. Abdul Wahid Hasjim perwakilan", "Mr. Achmad Soebardjo dan Mr. Alexander Andries Maramis", "Sutan Sjahrir selaku Perdana Menteri dan Amir Sjarifuddin", "Ir. Soekarno selaku Ketua dan Drs. Moh. Hatta selaku Wakil Ketua"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pimpinan Puncak Lembaga PPKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PPKI dipimpin oleh dwitunggal proklamator, yaitu <strong>diketuai oleh Ir. Soekarno dan wakil ketua Drs. Moh. Hatta</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Kepemimpinan Soekarno-Hatta dalam PPKI menegaskan pergeseran mandat dari badan penyelidik (BPUPKI) ke badan eksekutor persiapan kemerdekaan yang bertugas memproklamasikan dan mengesahkan konstitusi negara merdeka.</p><p>Jawaban tepat: <strong>E. Ir. Soekarno selaku Ketua dan Drs. Moh. Hatta selaku Wakil Ketua</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Radjiman dan R.P. Soeroso adalah pimpinan BPUPKI sebelumnya.</li><li><strong>B</strong>: Ki Hadjar dan Wachid Hasjim memegang posisi menteri departemen.</li><li><strong>C</strong>: Subardjo dan Maramis adalah anggota komisi perumus dasar.</li><li><strong>D</strong>: Sjahrir dan Amir memimpin kabinet parlementer pasca merdeka.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>PPKI yang diketuai oleh Ir. • wakil ketua Drs. Moh. Hatta</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pada saat awal pembentukannya tanggal 7 Agustus 1945, PPKI beranggotakan...", "options": ["9 orang tokoh perwakilan panitia kecil perumus", "21 orang tokoh bangsa yang mewakili seluruh wilayah nusantara", "60 orang anggota perwakilan persidangan gedung pejambon", "76 orang anggota gabungan dewan penyelidik kemerdekaan", "100 orang utusan partai politik dan laskar perjuangan daerah"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jumlah Keanggotaan Awal PPKI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada awal dibentuknya, <strong>PPKI beranggotakan 21 orang</strong> tokoh pergerakan yang mewakili berbagai suku dan pulau di tanah air (Jawa, Sumatera, Sulawesi, Maluku, Sunda Kecil, dan golongan minoritas).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Berbeda dengan BPUPKI yang melibatkan anggota Jepang, seluruh anggota PPKI adalah bangsa Indonesia murni berjumlah awal 21 orang, yang kemudian ditambah 6 orang tanpa sepengetahuan Jepang untuk menegaskan kemandirian proklamasi.</p><p>Jawaban tepat: <strong>B. 21 orang tokoh bangsa yang mewakili seluruh wilayah nusantara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: 9 orang adalah komposisi keanggotaan Panitia Sembilan.</li><li><strong>C</strong>: 60 orang adalah jumlah anggota biasa awal BPUPKI.</li><li><strong>D</strong>: 76 orang adalah jumlah keseluruhan anggota pleno BPUPKI.</li><li><strong>E</strong>: 100 orang adalah representasi dewan formasi seratus menteri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>beranggotakan 21 orang • PPKI yang diketuai oleh Ir.</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_wk_p02_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_wk_p02_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_wk_p02_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Wawasan Kebangsaan Paket 02";
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
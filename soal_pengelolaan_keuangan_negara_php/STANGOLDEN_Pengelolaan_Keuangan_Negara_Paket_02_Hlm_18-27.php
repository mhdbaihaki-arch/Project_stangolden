<?php
/**
 * STANGOLDEN.exam • Pengelolaan Keuangan Negara Paket 02 (Halaman 18–27)
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
// $DB->get_record("stgpkn_p02_rank") => tabel fisik "mdlhv_stgpkn_p02_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgpkn_p02_rank';
const STG_REVIEW_T = 'stgpkn_p02_rev';
const STG_ANNO_T = 'stgpkn_p02_anno';
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
            error_log('STANGOLDEN PKN P02 HLM18-27 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN PKN P02 HLM18-27: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Pengelolaan Keuangan Negara Paket 02 (Hlm. 18–27) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 02 • Hlm. 18–27</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 02 • Hlm. 18–27</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Pengelolaan Keuangan Negara Paket 02</h2>
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
const STG_LOCAL_KEY="stg_pkn_p02_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Secara umum, konsep anggaran didefinisikan sebagai suatu rencana keuangan yang memuat...", "options": ["Perkiraan tentang apa yang akan dilakukan di masa depan", "Catatan pembukuan transaksi perniagaan masa lampau", "Daftar inventaris aset fisik milik perorangan pegawai", "Perhitungan keuntungan dividen saham emiten bursa efek", "Estimasi laba kotor seluruh pelaku usaha mikro swasta"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Anggaran Secara Umum</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Anggaran</strong> adalah <strong>suatu rencana keuangan yang merupakan perkiraan tentang apa yang akan dilakukan dimasa yang akan datang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena hakikat anggaran adalah perencanaan proyeksi finansial ke depan mengenai pendapatan dan belanja yang hendak dilaksanakan. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Perkiraan tentang apa yang akan dilakukan di masa depan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Catatan transaksi masa lampau adalah laporan pertanggungjawaban realisasi.</li><li><strong>C</strong>: Daftar inventaris fisik perorangan merupakan aset privat individu.</li><li><strong>D</strong>: Dividen saham emiten merupakan produk investasi pasar modal.</li><li><strong>E</strong>: Laba usaha mikro swasta merupakan indikator keuangan bisnis komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Anggaran adalah suatu rencana • keuangan yang merupakan perkiraan tentang apa yang akan dilakukan dimasa</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Anggaran negara secara spesifik mengandung makna sebagai rencana keuangan yang...", "options": ["Dikelola mandiri oleh asosiasi pengusaha swasta", "Disusun dan dilaksanakan oleh pemerintah", "Dirancang oleh perwakilan lembaga keuangan asing", "Ditetapkan secara tertutup oleh bank komersial", "Disahkan oleh majelis kehormatan profesi tertentu"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Anggaran Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Anggaran negara</strong> berarti <strong>rencana keuangan yang disusun dan dilaksanakan oleh pemerintah</strong> dalam menyelenggarakan fungsi pemerintahan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena anggaran negara merefleksikan mandat eksekutif dalam merencanakan alokasi keuangan demi kepentingan penyelenggaraan negara. Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Disusun dan dilaksanakan oleh pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Asosiasi pengusaha mengelola anggaran perhimpunan privat.</li><li><strong>C</strong>: Lembaga asing tidak memiliki wewenang menyusun anggaran negara berdaulat.</li><li><strong>D</strong>: Bank komersial menyusun rencana bisnis perbankan masing-masing.</li><li><strong>E</strong>: Majelis profesi mengawasi kode etik keanggotaan profesi internal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>anggaran negara berarti rencana keuangan yang • disusun dan dilaksanakan oleh pemerintah.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Anggaran negara berkedudukan sangat penting dalam tata kelola bernegara karena dokumen tersebut merupakan...", "options": ["Keputusan strategis pembiayaan jajaran direksi perbankan devisa", "Pedoman pinjaman komersial bagi perseroan perbankan daerah", "Keputusan politik bersama pemerintah dan Dewan Perwakilan Rakyat", "Ketentuan batas modal kerja persekutuan perdata bisnis", "Rencana pembagian laba bersih konsorsium perusahaan swasta"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dimensi Politik Anggaran Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Anggaran negara menjadi sangat penting karena rencana tersebut merupakan <strong>keputusan politik antara pemerintah dan badan legislatif, yakni Dewan Perwakilan Rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena anggaran negara mengikat secara hukum sebagai komitmen politik bersama eksekutif dan legislatif dalam mengelola uang rakyat. Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Keputusan politik bersama pemerintah dan Dewan Perwakilan Rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemegang saham bank devisa mengurus korporasi perbankan komersial.</li><li><strong>B</strong>: Pedoman pinjaman bank daerah merupakan kebijakan perbankan daerah.</li><li><strong>D</strong>: Modal persekutuan perdata diatur dalam hukum perdata bisnis.</li><li><strong>E</strong>: Pembagian laba konsorsium swasta adalah dividen korporasi privat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>keputusan politik antara pemerintah • dan badan legislatif, yakni Dewan Perwakilan Rakyat,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Anggaran negara berfungsi sebagai alat pengendalian keuangan negara karena anggaran memuat...", "options": ["Kebebasan pejabat perbendaharaan mencairkan dana tanpa audit", "Pemberian dana talangan bagi perusahaan swasta bermasalah", "Hak prerogatif kementerian mencetak surat utang sendiri", "Batasan-batasan pengeluaran yang diatur dalam perundang-undangan", "Kewenangan mengubah tarif retribusi secara langsung di lapangan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Anggaran sebagai Alat Pengendalian</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Anggaran negara dipandang sebagai <strong>alat pengendalian keuangan negara, karena merupakan batas-batas yang diatur dalam perundangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena plafon anggaran membatasi jumlah pengeluaran tertinggi yang boleh direalisasikan oleh kementerian atau lembaga negara. Oleh karena itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Batasan-batasan pengeluaran yang diatur dalam perundang-undangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pencairan dana tanpa audit melanggar prinsip kepatuhan perbendaharaan.</li><li><strong>B</strong>: Talangan swasta bermasalah bertentangan dengan tujuan keuangan negara.</li><li><strong>C</strong>: Penerbitan obligasi negara merupakan wewenang terpusat Menteri Keuangan.</li><li><strong>E</strong>: Pengubahan retribusi di lapangan tanpa regulasi melanggar legalitas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>alat • pengendalian keuangan negara, karena merupakan batas- batas yang diatur dalam</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Undang-Undang Nomor 17 Tahun 2003 mendefinisikan Anggaran Pendapatan dan Belanja Negara (APBN) sebagai...", "options": ["Rencana belanja bulanan yang ditetapkan oleh badan pemeriksa", "Laporan laba rugi tahunan badan usaha milik negara perbankan", "Pedoman investasi modal ventura bagi korporasi multinasional", "Daftar penerimaan sumbangan sukarela masyarakat kepada pemerintah", "Rencana keuangan tahunan pemerintahan negara yang disetujui oleh DPR"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Legal APBN dalam UU 17/2003</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan UU Nomor 17 Tahun 2003, <strong>APBN adalah rencana keuangan tahunan pemerintahan negara yang disetujui oleh Dewan Perwakilan Rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena secara yuridis APBN memiliki periodisitas satu tahun dan memperoleh persetujuan perwakilan rakyat melalui undang-undang. Maka dari itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Rencana keuangan tahunan pemerintahan negara yang disetujui oleh DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: APBN berlaku tahunan dan disetujui oleh DPR, bukan BPK secara bulanan.</li><li><strong>B</strong>: Laporan laba rugi BUMN adalah laporan komersial korporasi perbankan.</li><li><strong>C</strong>: Investasi modal ventura merupakan transaksi bisnis lembaga pembiayaan.</li><li><strong>D</strong>: APBN bukan daftar sumbangan sukarela melainkan rencana keuangan resmi negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>rencana keuangan tahunan pemerintahan • negara yang disetujui oleh Dewan Perwakilan Rakyat.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pada dasarnya struktur dokumen APBN mengandung tiga komponen perkiraan utama, yaitu...", "options": ["Perkiraan pendapatan, perkiraan belanja, dan perkiraan pembiayaan", "Perkiraan peredaran uang, perkiraan devisa, dan cadangan emas", "Perkiraan tabungan warga, deposito swasta, dan pinjaman bank", "Perkiraan modal serikat, kas koperasi, dan omzet pasar", "Perkiraan laba korporasi, saham emiten, dan dividen bursa"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Komponen Pokok APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada dasarnya, <strong>APBN mengandung perkiraan jumlah pendapatan, perkiraan jumlah belanja dan perkiraan pembiayaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena tri-komponen tersebut membentuk postur anggaran negara dalam mengelola arus masuk, arus keluar, dan pembiayaan selisihnya. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Perkiraan pendapatan, perkiraan belanja, dan perkiraan pembiayaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Cadangan devisa dan uang beredar adalah instrumen pengawasan moneter BI.</li><li><strong>C</strong>: Tabungan dan deposito warga merupakan dana pihak ketiga di perbankan.</li><li><strong>D</strong>: Modal serikat dan koperasi merupakan aset perhimpunan perdata warga.</li><li><strong>E</strong>: Laba korporasi dan saham merupakan instrumen bursa efek pasar modal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mengandung perkiraan jumlah pendapatan, perkiraan jumlah belanja dan perkiraan • pembiayaan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Penyusunan APBN oleh pemerintah diselenggarakan dengan tujuan utama dalam rangka...", "options": ["Memperluas kepemilikan modal saham pada perbankan internasional", "Pelaksanaan tugas kenegaraan yang dibebankan kepada pemerintah", "Mengurangi alokasi pelayanan dasar masyarakat di daerah pelosok", "Memberikan keringanan tarif pajak bagi konglomerasi swasta besar", "Menghapuskan wewenang pengawasan oleh dewan perwakilan rakyat"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Penyusunan APBN oleh Pemerintah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>APBN disusun oleh pemerintah dengan tujuan dalam rangka pelaksanaan tugas yang dibebankan kepada pemerintah</strong> untuk melayani masyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena anggaran dirancang sebagai instrumen operasional guna mendanai mandat fungsi eksekutif dalam melayani kepentingan rakyat. Oleh karena itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Pelaksanaan tugas kenegaraan yang dibebankan kepada pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kepemilikan saham bank internasional bukan tujuan utama perumusan APBN.</li><li><strong>C</strong>: APBN justru bertujuan menjamin pemenuhan pelayanan dasar secara merata.</li><li><strong>D</strong>: Keringanan pajak konglomerat bertentangan dengan keadilan vertikal fiskal.</li><li><strong>E</strong>: Wewenang pengawasan DPR dijamin konstitusi dan tidak dapat dihapus.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>APBN disusun oleh pemerintah dengan tujuan dalam rangka • pelaksanaan tugas yang dibebankan kepada pemerintah.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Sesuai ketentuan perundang-undangan, pihak yang berwenang menyiapkan rancangan undang-undang APBN adalah...", "options": ["Badan Pemeriksa Keuangan untuk diserahkan kepada kementerian", "Bank Indonesia untuk disampaikan kepada asosiasi perbankan", "Pemerintah untuk diajukan kepada Dewan Perwakilan Rakyat", "Otoritas Jasa Keuangan untuk disahkan oleh dewan pakar", "Konsultan keuangan independen untuk dilelang kepada publik"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Inisiator Penyusunan RUU APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pihak yang <strong>menyiapkan rancangan APBN adalah pemerintah yang kemudian diajukan kepada Dewan Perwakilan Rakyat (DPR) untuk mendapat persetujuan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kewenangan inisiatif pengajuan rancangan APBN berada di tangan pemerintah selaku pengelola administrasi kenegaraan. Maka dari itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Pemerintah untuk diajukan kepada Dewan Perwakilan Rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: BPK bertindak sebagai lembaga pemeriksa audit, bukan penyusun RUU APBN.</li><li><strong>B</strong>: Bank Indonesia bertugas di bidang moneter, bukan inisiator APBN.</li><li><strong>D</strong>: OJK mengawasi sektor industri jasa keuangan perbankan dan non-bank.</li><li><strong>E</strong>: Penyusunan RUU APBN adalah tugas pejabat negara, bukan konsultan lelang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pihak yang menyiapkan • rancangan APBN adalah pemerintah yang kemudian diajukan kepada Dewan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Rancangan Undang-Undang APBN yang telah disetujui oleh Dewan Perwakilan Rakyat dinyatakan berlaku setelah...", "options": ["Diberi nomor registrasi oleh perwakilan dewan moneter", "Ditandatangani oleh seluruh direksi bank sentral daerah", "Mendapat akreditasi kelayakan dari konsultan asing", "Disahkan oleh Presiden menjadi undang-undang resmi", "Diumumkan dalam rapat kerja asosiasi perbankan nasional"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tahap Pengesahan Akhir RUU APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam praktiknya, <strong>RUU APBN itu setelah disetujui oleh DPR baru dinyatakan berlaku setelah disahkan oleh Presiden</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pengesahan oleh Presiden menandai tuntasnya proses legislasi formal undang-undang APBN sebelum diundangkan dalam lembaran negara. Oleh sebab itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Disahkan oleh Presiden menjadi undang-undang resmi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dewan moneter tidak memiliki fungsi legal pengesahan undang-undang negara.</li><li><strong>B</strong>: Direksi bank sentral tidak berwenang mengesahkan dokumen APBN.</li><li><strong>C</strong>: Pemberlakuan hukum undang-undang negara tidak memerlukan akreditasi asing.</li><li><strong>E</strong>: Rapat asosiasi perbankan merupakan forum internal industri perbankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>setelah disetujui oleh DPR baru dinyatakan berlaku setelah disahkan oleh • Presiden.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kebijakan perekonomian makro yang secara komprehensif tercantum dan berinteraksi dalam anggaran negara mencakup...", "options": ["Kebijakan merger dan akuisisi perseroan swasta", "Penetapan margin penjualan barang elektronik grosir", "Pengaturan tarif komisi perantara perdagangan tanah", "Penyeragaman sistem bonus tahunan pegawai korporasi", "Kebijakan fiskal dan keterkaitannya dengan moneter"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Kebijakan Makro dalam Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kebijakan yang tercantum dalam anggaran negara mencakup kebijakan fiskal dan moneter</strong> yang saling bersinergi dalam perekonomian.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena keberhasilan pelaksanaan anggaran fiskal sangat dipengaruhi dan berinteraksi dinamis dengan stabilitas moneter makro. Oleh karena itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Kebijakan fiskal dan keterkaitannya dengan moneter</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merger akuisisi merupakan aksi korporasi bisnis perusahaan swasta.</li><li><strong>B</strong>: Margin barang elektronik merupakan keputusan strategi dagang pelaku usaha.</li><li><strong>C</strong>: Komisi perantara tanah diatur dalam kesepakatan perikatan perdata.</li><li><strong>D</strong>: Bonus tahunan korporasi ditentukan oleh manajemen internal perusahaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 18) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kebijakan yang tercantum dalam anggaran negara mencakup • kebijakan fiskal dan moneter.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Otoritas utama penyelenggaraan kebijakan fiskal sebagai bagian dari kebijakan ekonomi makro berada pada...", "options": ["Pemerintah yang diwakili oleh Kementerian Keuangan", "Direksi lembaga penjamin simpanan perbankan", "Badan arbitrase perselisihan perdagangan komersial", "Asosiasi eksportir komoditas perkebunan daerah", "Komite pengawas kepatuhan emiten bursa saham"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Otoritas Pengelola Kebijakan Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kebijakan fiskal merupakan salah satu kebijakan ekonomi makro yang otoritas utamanya berada di tangan pemerintah yang diwakili oleh Kementerian Keuangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Menteri Keuangan bertindak sebagai pengelola fiskal negara yang merumuskan dan mengeksekusi strategi penerimaan serta belanja APBN. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Pemerintah yang diwakili oleh Kementerian Keuangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Lembaga penjamin simpanan bertugas menjamin tabungan nasabah perbankan.</li><li><strong>C</strong>: Badan arbitrase menyelesaikan sengketa perdata bisnis para pihak.</li><li><strong>D</strong>: Asosiasi eksportir merupakan organisasi pelaku industri komoditas.</li><li><strong>E</strong>: Komite pengawas emiten mengawasi kepatuhan perusahaan di pasar modal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kebijakan fiskal merupakan salah satu kebijakan ekonomi makro yang otoritas • utamanya berada di tangan pemerintah yang diwakili oleh Kementerian Keuangan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Undang-Undang Nomor 17 Tahun 2003, kuasa pengelolaan keuangan diberikan Presiden kepada Menteri Keuangan selaku...", "options": ["Pengguna anggaran bagi seluruh unit kementerian teknis", "Pengelola fiskal dan wakil pemerintah dalam kekayaan yang dipisahkan", "Auditor independen pemeriksa laporan keuangan kementerian", "Pemimpin tunggal penentu tingkat suku bunga tabungan bank", "Pengawas sengketa persaingan dagang badan usaha swasta"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Menkeu Berdasarkan UU 17/2003</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden memberikan kuasa pengelolaan keuangan kepada Menteri Keuangan selaku <strong>pengelola fiskal dan wakil pemerintah dalam pemilikan kekayaan negara yang dipisahkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena mandat undang-undang menetapkan peran ganda Menteri Keuangan sebagai pengarah kebijakan fiskal nasional sekaligus pengelola investasi saham negara pada BUMN. Oleh sebab itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Pengelola fiskal dan wakil pemerintah dalam kekayaan yang dipisahkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengguna anggaran kementerian teknis berada pada menteri masing-masing.</li><li><strong>C</strong>: Auditor independen laporan keuangan kementerian adalah tugas BPK.</li><li><strong>D</strong>: Tingkat suku bunga perbankan dipengaruhi oleh kebijakan Bank Indonesia.</li><li><strong>E</strong>: Pengawas persaingan dagang adalah tugas KPPU, bukan Menteri Keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Menteri Keuangan selaku • pengelola fiskal dan wakil pemerintah dalam pemilikan kekayaan negara yang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Instrumen utama pemerintah dalam penyelenggaraan dan pengelolaan kebijakan fiskal tercermin dalam...", "options": ["Ketetapan suku bunga acuan pasar uang antarbank", "Daftar harga patokan ekspor hasil industri pertambangan", "Kebijakan APBN yang dituangkan dalam Undang-Undang APBN", "Perjanjian perikatan dagang bilateral pengusaha daerah", "Besaran komisi penjaminan emisi obligasi swasta nasional"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Instrumen Utama Kebijakan Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Instrumen utama dalam pengelolaan kebijakan fiskal tercermin dalam kebijakan APBN yang dituangkan dalam UU APBN yang disusun oleh pemerintah dan disahkan oleh DPR setiap tahun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena undang-undang APBN memuat kekuatan legal formal seluruh target pendapatan, batasan belanja, dan skema pembiayaan negara. Oleh karena itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Kebijakan APBN yang dituangkan dalam Undang-Undang APBN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Suku bunga acuan merupakan instrumen utama kebijakan moneter BI.</li><li><strong>B</strong>: Harga patokan ekspor tambang merupakan regulasi operasional perdagangan.</li><li><strong>D</strong>: Perikatan bilateral pengusaha adalah transaksi bisnis sektor privat.</li><li><strong>E</strong>: Komisi emisi obligasi swasta merupakan biaya transaksi pasar modal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Instrumen utama dalam pengelolaan kebijakan fiskal tercermin dalam • kebijakan APBN yang dituangkan dalam UU APBN yang disusun oleh pemerintah</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kebijakan fiskal didefinisikan sebagai pengelolaan anggaran pemerintah untuk mempengaruhi perekonomian, yang meliputi...", "options": ["Penetapan kuota pencetakan uang kartal dan uang giral bank", "Pembatasan pemberian fasilitas kredit bagi nasabah komersial", "Penetapan margin bunga simpanan berjangka perbankan swasta", "Kebijakan perpajakan, belanja pemerintah, dan ukuran pembiayaan defisit", "Pengaturan volume penerbitan polis asuransi kerugian daerah"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ruang Lingkup Kebijakan Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan fiskal mencakup <strong>kebijakan perpajakan (pendapatan negara), pembayaran transfer, pembelian barang dan jasa (belanja), serta ukuran defisit dan pembiayaan anggaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pilar-pilar tersebut merupakan instrumen fiskal komprehensif yang dikendalikan otoritas perbendaharaan negara. Maka dari itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Kebijakan perpajakan, belanja pemerintah, dan ukuran pembiayaan defisit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pencetakan uang kartal merupakan wewenang mutlak Bank Sentral.</li><li><strong>B</strong>: Kredit nasabah komersial merupakan domain operasional perbankan.</li><li><strong>C</strong>: Bunga deposito ditentukan mekanisme pasar dan kebijakan moneter.</li><li><strong>E</strong>: Penerbitan polis asuransi adalah bisnis industri jasa keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pengelolaan anggaran pemerintah • untuk mempengaruhi suatu perekonomian, termasuk kebijakan perpajakan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pemanfaatan berbagai instrumen fiskal oleh pemerintah pada akhirnya ditujukan untuk mempengaruhi perekonomian agar...", "options": ["Memperbesar kepemilikan aset pribadi aparatur kementerian", "Membatasi perkembangan sektor usaha industri mikro lokal", "Mengurangi alokasi anggaran infrastruktur sarana publik", "Mewajibkan warga menyimpan seluruh aset pada bank asing", "Memaksimumkan tingkat kesejahteraan hidup seluruh rakyat"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Maksimisasi Kesejahteraan Rakyat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan fiskal diarahkan pada <strong>penggunaan instrumen-instrumen fiskal untuk mempengaruhi bekerjanya sistem ekonomi agar memaksimumkan kesejahteraan rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena tujuan tertinggi intervensi anggaran negara adalah terciptanya kemakmuran, keadilan sosial, dan peningkatan taraf hidup masyarakat. Oleh sebab itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Memaksimumkan tingkat kesejahteraan hidup seluruh rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Memperkaya aset pribadi aparatur melanggar asas integritas birokrasi.</li><li><strong>B</strong>: Kebijakan fiskal justru mendukung pemberdayaan usaha mikro warga.</li><li><strong>C</strong>: Anggaran infrastruktur dialokasikan untuk mendukung konektivitas ekonomi.</li><li><strong>D</strong>: Menyimpan aset pada bank asing bertentangan dengan penguatan devisa domestik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penggunaan instrumen-instrumen fiskal untuk mempengaruhi bekerjanya sistem • ekonomi agar memaksimumkan kesejahteraan rakyat.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Menurut pandangan Tanzi (1991), pemerintah merumuskan tujuan kebijakan fiskal secara spesifik dengan orientasi untuk...", "options": ["Meningkatkan kesejahteraan ekonomi melalui sasaran terukur", "Memonopoli seluruh distribusi bahan makanan di pasar tradisional", "Menghapuskan sistem perbankan komersial swasta nasional", "Membatasi akses perdagangan antarpulau di kawasan terluar", "Mengurangi transparansi perincian pos belanja dinas kantor"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Spesifik Kebijakan Fiskal Menurut Tanzi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah terkadang <strong>memfokuskan pada tujuan-tujuan yang lebih spesifik agar dapat meningkatkan kesejahteraan ekonomi (Tanzi, 1991)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penetapan target spesifik memungkinkan kebijakan fiskal bekerja efektif meredam gejolak dan mendorong pertumbuhan riil. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Meningkatkan kesejahteraan ekonomi melalui sasaran terukur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Monopoli pasar tradisional bukan konsep peningkatan kesejahteraan Tanzi.</li><li><strong>C</strong>: Perbankan komersial merupakan pilar intermediasi keuangan masyarakat.</li><li><strong>D</strong>: Perdagangan antarpulau didukung pemerintah demi integrasi logistik.</li><li><strong>E</strong>: Mengurangi transparansi belanja bertentangan dengan tata kelola fiskal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>memfokuskan pada tujuan-tujuan yang lebih spesifik agar • dapat meningkatkan kesejahteraan ekonomi (Tanzi, 1991).</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Salah satu tujuan spesifik kebijakan fiskal adalah koreksi ketidakseimbangan perekonomian yang ditandai oleh fenomena...", "options": ["Surplus neraca perdagangan barang manufaktur", "Inflasi dan tingkat pengangguran yang tinggi", "Kenaikan cadangan kas devisa di bank sentral", "Pertumbuhan pesat modal investasi industri digital", "Stabilitas nilai tukar mata uang domestik di pasar"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Koreksi Ketidakseimbangan Perekonomian</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan spesifik kebijakan fiskal mencakup: <strong>a) Koreksi atas ketidakseimbangan perekonomian yang antara lain ditandai dengan inflasi dan tingkat pengangguran yang tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kombinasi inflasi tidak terkendali dan pengangguran masif merupakan tanda ketidakseimbangan makro yang wajib dikoreksi melalui instrumen fiskal. Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Inflasi dan tingkat pengangguran yang tinggi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Surplus perdagangan mencerminkan kinerja ekspor yang menguntungkan.</li><li><strong>C</strong>: Kenaikan devisa merupakan indikator penguatan likuiditas eksternal.</li><li><strong>D</strong>: Pertumbuhan investasi digital mencerminkan ekspansi ekonomi positif.</li><li><strong>E</strong>: Stabilitas nilai tukar merupakan kondisi makroekonomi yang diharapkan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Koreksi atas ketidakseimbangan perekonomian yang antara lain ditandai • dengan inflasi dan tingkat pengangguran yang tinggi;</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tiga sasaran spesifik kebijakan fiskal menurut rumusan Tanzi meliputi koreksi ketidakseimbangan, stimulasi pertumbuhan, dan...", "options": ["Sentralisasi seluruh perizinan pendirian perseroan terbatas", "Penetapan harga seragam barang komoditas industri sekunder", "Redistribusi pendapatan untuk mengurangi kesenjangan kesejahteraan warga", "Penghentian penyaluran bantuan sosial bagi masyarakat miskin", "Pengambilalihan saham badan usaha swasta oleh perbankan asing"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Pilar Sasaran Spesifik Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tiga tujuan spesifik fiskal menurut Tanzi adalah koreksi ketidakseimbangan, stimulasi pertumbuhan ekonomi, dan <strong>redistribusi pendapatan untuk mengurangi kesenjangan kesejahteraan antar warga negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pemerataan hasil ekonomi melalui redistribusi fiskal melengkapi sasaran stabilitas dan pertumbuhan ekonomi nasional. Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Redistribusi pendapatan untuk mengurangi kesenjangan kesejahteraan warga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sentralisasi izin perseroan merupakan aspek deregulasi birokrasi niaga.</li><li><strong>B</strong>: Penyeragaman harga barang komoditas merusak efisiensi mekanisme pasar.</li><li><strong>D</strong>: Menghentikan bansos justru memperlebar jurang kesenjangan sosial warga.</li><li><strong>E</strong>: Pengambilalihan saham oleh bank asing adalah transaksi korporasi perdata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Stimulasi terhadap pertumbuhan ekonomi, dan • Redistribusi pendapatan untuk mengurangi kesenjangan kesejahteraan antar</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Penerapan kebijakan fiskal secara terpadu mencakup dua orientasi horizon waktu kerja, yaitu...", "options": ["Jangka harian untuk pencairan dan jangka mingguan untuk penutupan", "Jangka triwulanan untuk audit dan jangka semesteran untuk pengawasan", "Jangka perorangan untuk dinas dan jangka kelompok untuk organisasi", "Jangka pendek untuk stabilisasi dan jangka panjang untuk pertumbuhan", "Jangka darurat untuk bencana dan jangka situasional untuk rapat"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dimensi Waktu Kebijakan Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Secara bersamaan terdapat <strong>kebijakan fiskal jangka pendek atau stabilisasi, dan kebijakan fiskal jangka panjang untuk mendorong pertumbuhan ekonomi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pemerintah harus sigap meredam guncangan siklus bisnis saat ini (stabilisasi) sembari membangun kapasitas produksi masa depan (pertumbuhan). Oleh karena itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Jangka pendek untuk stabilisasi dan jangka panjang untuk pertumbuhan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jangka harian dan mingguan merupakan jadwal teknis pembukuan kasir.</li><li><strong>B</strong>: Triwulanan dan semesteran merupakan jadwal periodik pelaporan dinas.</li><li><strong>C</strong>: Jangka perorangan bukan klasifikasi horizon waktu kebijakan makro.</li><li><strong>E</strong>: Jangka situasional merupakan mekanisme teknis penanganan insidental.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kebijakan fiskal jangka pendek atau stabilisasi, dan kebijakan fiskal jangka • panjang untuk mendorong pertumbuhan ekonomi.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Suatu kementerian keuangan menjalankan kebijakan fiskal secara operasional melalui perpaduan langkah strategis dalam mengelola...", "options": ["Tingkat suku bunga diskonto pinjaman bank sentral", "Cadangan emas batangan pada lemari besi perbankan daerah", "Ketetapan batasan kuota impor kendaraan dinas kementerian", "Tarif sewa kamar hotel untuk kegiatan konsinyasi kedinasan", "Penerimaan perpajakan dan alokasi belanja pengeluaran negara"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Langkah Strategis Fiskal Pemerintah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan fiskal diartikan sebagai <strong>langkah-langkah pemerintah untuk mengelola pengeluaran (belanja) dan perpajakan (penerimaan negara)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena dua sisi neraca fiskal (penerimaan dan pengeluaran) merupakan tuas utama pemerintah dalam mengarahkan perekonomian. Maka dari itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Penerimaan perpajakan dan alokasi belanja pengeluaran negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Suku bunga diskonto merupakan instrumen moneter Bank Indonesia.</li><li><strong>B</strong>: Cadangan emas perbankan merupakan portofolio aset bank komersial.</li><li><strong>C</strong>: Kuota impor kendaraan dinas merupakan kebijakan operasional barang dinas.</li><li><strong>D</strong>: Tarif sewa hotel merupakan komponen standar biaya masukan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 19) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>langkah-langkah pemerintah untuk • mengelola pengeluaran (belanja) dan perpajakan (penerimaan negara)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam perumusan kebijakan fiskal, para pengambil keputusan menyadari bahwa langkah kebijakan jangka pendek senantiasa memiliki...", "options": ["Konsekuensi dan implikasi terhadap kondisi jangka panjang", "Ketiadaan kaitan dengan perencanaan pembangunan lima tahunan", "Dampak yang selesai secara tuntas dalam satu hari pembukuan", "Sifat terisolasi dari perkembangan kondisi sosial masyarakat", "Kewajiban menghapus seluruh catatan transaksi belanja negara"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Interkoneksi Konsekuensi Kebijakan Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebanyakan dari <strong>langkah-langkah kebijakan fiskal jangka pendek juga mempunyai konsekuensi jangka panjang</strong>, dan langkah jangka panjang memiliki implikasi jangka pendek.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena keputusan stimulus atau penarikan utang jangka pendek akan membebani neraca fiskal dan kapasitas anggaran di masa depan. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Konsekuensi dan implikasi terhadap kondisi jangka panjang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kebijakan fiskal selalu selaras dengan kerangka perencanaan pembangunan.</li><li><strong>C</strong>: Dampak stimulus ekonomi membutuhkan waktu transmisi berbulan-bulan.</li><li><strong>D</strong>: Kebijakan fiskal bersentuhan langsung dengan kondisi sosial masyarakat.</li><li><strong>E</strong>: Seluruh transaksi belanja negara wajib dibukukan secara permanen.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kebanyakan dari langkah-langkah kebijakan fiskal jangka • pendek juga mempunyai konsekuensi jangka panjang,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Richard Musgrave (1959) mengklasifikasikan tiga fungsi spesifik kebijakan fiskal pemerintah yang terdiri atas...", "options": ["Fungsi privatisasi, fungsi komersialisasi, dan fungsi subordinasi", "Fungsi alokasi, fungsi distribusi, dan fungsi stabilisasi", "Fungsi sentralisasi, fungsi standardisasi, dan fungsi simplifikasi", "Fungsi akumulasi, fungsi kapitalisasi, dan fungsi eliminasi", "Fungsi liberalisasi, fungsi deregulasi, dan fungsi proteksi"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Trilogi Fungsi Kebijakan Fiskal Musgrave</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Ketiga fungsi spesifik dari kebijakan fiskal itu adalah fungsi alokasi, distribusi, dan stabilisasi (Musgrave, 1959)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena ketiga fungsi Musgrave merupakan fondasi teoritis utama dalam perumusan anggaran pendapatan dan belanja sektor publik modern. Oleh karena itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Fungsi alokasi, fungsi distribusi, dan fungsi stabilisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Privatisasi dan komersialisasi bukan trilogi fungsi pokok fiskal Musgrave.</li><li><strong>C</strong>: Standardisasi dan simplifikasi merupakan prinsip tata laksana kantor.</li><li><strong>D</strong>: Akumulasi dan kapitalisasi merupakan istilah dalam investasi modal.</li><li><strong>E</strong>: Liberalisasi dan deregulasi merupakan mazhab kebijakan pasar bebas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Ketiga fungsi spesifik dari kebijakan fiskal • itu adalah fungsi alokasi, distribusi, dan stabilisasi (Musgrave, 1959).</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Fungsi stabilisasi kebijakan fiskal memikul tanggung jawab kenegaraan untuk menjamin agar perekonomian berada pada...", "options": ["Dominasi mutlak penyerapan tenaga kerja oleh sektor birokrasi", "Tingkat bunga pinjaman komersial perbankan yang seragam", "Kondisi kesempatan kerja penuh dengan tingkat harga yang stabil", "Ketiadaan persaingan usaha antarpelaku niaga swasta lokal", "Penghapusan seluruh subsidi harga barang kebutuhan pokok"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Stabilisasi dan Kesempatan Kerja Penuh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Fungsi stabilisasi terkait dengan tanggung jawab untuk menjamin perekonomian tetap pada kesempatan kerja penuh (full employment) dengan harga yang stabil</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena stabilitas ekonomi tercapai apabila seluruh angkatan kerja terserap optimal (full employment) tanpa memicu lonjakan inflasi harga barang. Maka dari itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Kondisi kesempatan kerja penuh dengan tingkat harga yang stabil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sebagian besar tenaga kerja bekerja di sektor riil swasta dan mandiri.</li><li><strong>B</strong>: Bunga pinjaman komersial ditentukan oleh profil risiko debitur.</li><li><strong>D</strong>: Persaingan usaha yang sehat dilindungi untuk efisiensi pasar makro.</li><li><strong>E</strong>: Subsidi pokok tetap dibutuhkan untuk menjaga daya beli kelompok rentan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Fungsi stabilisasi terkait dengan tanggung jawab untuk menjamin • perekonomian tetap pada kesempatan kerja penuh (full employment)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tercapainya pertumbuhan ekonomi yang stabil melalui pelaksanaan fungsi anggaran pada hakikatnya mencerminkan...", "options": ["Kenaikan laba bersih korporasi penanaman modal asing", "Peningkatan cadangan devisa emas di bank sentral mitra", "Perluasan kepemilikan saham eksekutif pada bursa berjangka", "Peningkatan kesejahteraan hidup warga negara secara umum", "Pertambahan jumlah gedung pencakar langit milik swasta"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Refleksi Pertumbuhan Ekonomi terhadap Kesejahteraan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pertumbuhan ekonomi sebuah negara mencerminkan peningkatan kesejahteraan warga negara secara umum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena esensi pertumbuhan ekonomi bukan angka statistik semata, melainkan naiknya pendapatan riil dan standar hidup masyarakat. Oleh sebab itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Peningkatan kesejahteraan hidup warga negara secara umum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Laba korporasi asing bukan tolak ukur tunggal kesejahteraan umum.</li><li><strong>B</strong>: Cadangan emas mitra asing merupakan aset moneter negara lain.</li><li><strong>C</strong>: Saham eksekutif bursa berjangka adalah portofolio investasi individu.</li><li><strong>E</strong>: Gedung pencakar langit swasta merupakan indikator fisik properti komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pertumbuhan • ekonomi • sebuah • negara • mencerminkan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam menjalankan fungsi alokasi, pemerintah melakukan intervensi terhadap perekonomian dengan tujuan untuk...", "options": ["Menguasai seluruh kepemilikan modal badan usaha komersial", "Menetapkan besaran gaji karyawan perusahaan swasta daerah", "Menyeragamkan pola konsumsi pangan seluruh warga negara", "Membatasi jumlah simpanan tabungan pribadi pada bank umum", "Mengalokasikan sumber daya ekonomi guna penyediaan sarana publik"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Alokasi Sumber Daya Ekonomi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah melakukan <strong>intervensi terhadap perekonomian dalam mengalokasikan sumber daya ekonominya</strong> secara optimal bagi masyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena mekanisme pasar bebas kerap enggan mendanai barang publik bernilai sosial tinggi, sehingga intervensi alokasi pemerintah mutlak hadir. Oleh karena itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Mengalokasikan sumber daya ekonomi guna penyediaan sarana publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemerintah tidak menguasai seluruh modal usaha komersial swasta.</li><li><strong>B</strong>: Gaji karyawan swasta diatur dalam ketentuan upah minimum ketenagakerjaan.</li><li><strong>C</strong>: Pola konsumsi pangan ditentukan oleh preferensi dan budaya masyarakat.</li><li><strong>D</strong>: Simpanan tabungan di bank umum merupakan hak milik privat nasabah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>intervensi • terhadap • perekonomian • dalam • mengalokasikan sumber daya ekonominya.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Bentuk intervensi alokasi langsung yang dilakukan oleh pemerintah melalui belanja APBN tercermin pada pembiayaan...", "options": ["Sektor pertahanan, pendidikan, dan pembangunan infrastruktur", "Pemberian dana talangan bagi spekulan valuta asing di bursa", "Pengadaan armada jet pribadi untuk operasional yayasan sosial", "Pemberian dividen tambahan bagi pemegang saham bank swasta", "Pembebasan kewajiban pembukuan kas bagi bendahara pengeluaran"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Intervensi Alokasi Langsung Melalui Belanja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Intervensi alokasi dilakukan dengan <strong>secara langsung membeli barang-barang atau melakukan belanja negara seperti pertahanan, pendidikan, pembangunan infrastruktur</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena belanja sektor pertahanan, pendidikan, dan infrastruktur memberikan fondasi barang publik dasar yang mendorong kapasitas ekonomi jangka panjang. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Sektor pertahanan, pendidikan, dan pembangunan infrastruktur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Talangan spekulan valas dilarang keras dalam keuangan negara.</li><li><strong>C</strong>: Jet pribadi yayasan sosial bukan pos alokasi belanja barang dinas negara.</li><li><strong>D</strong>: Dividen pemegang saham bank swasta dibayar dari laba bersih bank terkait.</li><li><strong>E</strong>: Bendahara pengeluaran wajib melakukan pembukuan kas secara tertib.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>secara langsung membeli barang-barang atau melakukan • belanja negara seperti pertahanan, pendidikan, pembangunan infrastruktur.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Selain belanja langsung, fungsi alokasi pemerintah dijalankan secara tidak langsung melalui instrumen...", "options": ["Penetapan larangan pendirian badan usaha berbadan hukum", "Pungutan berbagai pajak dan pemberian subsidi bersasaran", "Pembatasan transaksi pembayaran nontunai perbankan nasional", "Penyerahan tata kelola penerimaan cukai kepada lembaga swasta", "Penghapusan tarif bea masuk seluruh komoditas impor mewah"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Intervensi Alokasi Tidak Langsung</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah membuat kebijakan yang <strong>secara tidak langsung mempengaruhi perekonomian dengan melakukan pungutan berbagai pajak dan pemberian subsidi-subsidi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena tarif pajak dapat meredam konsumsi barang berbahaya dan subsidi dapat merangsang kegiatan produktif yang menguntungkan publik. Oleh sebab itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Pungutan berbagai pajak dan pemberian subsidi bersasaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemerintah justru mendorong kemudahan pendirian badan usaha legal.</li><li><strong>C</strong>: Transaksi nontunai didorong demi transparansi dan efisiensi sistem bayar.</li><li><strong>D</strong>: Tata kelola cukai adalah kewenangan eksklusif Direktorat Jenderal Bea Cukai.</li><li><strong>E</strong>: Komoditas impor mewah dikenakan bea masuk dan pajak pertambahan nilai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>secara tidak langsung mempengaruhi perekonomian dengan melakukan • pungutan berbagai pajak dan pemberian subsidi-subsidi,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Fungsi distribusi APBN mewajibkan agar dalam mengelola anggaran negara, pemerintah senantiasa memperhatikan asas...", "options": ["Keuntungan maksimal bagi pemodal industri skala besar", "Kerahasiaan dokumen realisasi pembagian bantuan sosial", "Keadilan dan kepatutan bagi seluruh lapisan masyarakat", "Pemberian fasilitas istimewa kepada kelompok pengusaha elit", "Pemusatan alokasi belanja pada kawasan ibu kota metropolitan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Distribusi APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Fungsi distribusi berkaitan dengan bagaimana Pemerintah dengan kebijakan anggaran negara harus memperhatikan rasa keadilan dan kepatutan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena uang rakyat yang dihimpun ke kas negara wajib dikembalikan dalam bentuk program yang adil dan patut tanpa diskriminasi golongan. Oleh karena itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Keadilan dan kepatutan bagi seluruh lapisan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keuntungan pemodal besar bukan prinsip keadilan fungsi distribusi.</li><li><strong>B</strong>: Realisasi bantuan sosial wajib dilaporkan secara transparan.</li><li><strong>D</strong>: Pemberian fasilitas istimewa pada elit melanggar prinsip kepatutan.</li><li><strong>E</strong>: Alokasi anggaran disebarkan merata ke seluruh pelosok tanah air.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Fungsi distribusi berkaitan dengan bagaimana Pemerintah dengan kebijakan • anggaran negara harus memperhatikan rasa keadilan dan kepatutan.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Hasil-hasil pembangunan yang dibiayai melalui instrumen APBN diharapkan dapat didistribusikan secara merata agar...", "options": ["Korporasi multinasional memperoleh insentif dividen besar", "Aparatur perbendaharaan mendapat imbalan komisi lelang proyek", "Pemerintah terbebas dari kewajiban menyusun laporan APBN", "Seluruh rakyat merasakan keadilan sosial bagi semua", "Dewan legislatif dapat mengalihkan anggaran ke rekening partai"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Distribusi Merata Hasil Pembangunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hasil pembangunan APBN didistribusikan secara merata agar <strong>rakyat merasakan keadilan sosial bagi semua</strong> sesuai amanat Pancasila.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena sila kelima Pancasila menuntut keadilan sosial dinikmati secara nyata oleh segenap tumpah darah Indonesia melalui belanja publik. Maka dari itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Seluruh rakyat merasakan keadilan sosial bagi semua</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Insentif korporasi multinasional bukan tujuan utama keadilan sosial.</li><li><strong>B</strong>: Imbalan komisi proyek bagi aparatur merupakan pelanggaran pidana.</li><li><strong>C</strong>: Pemerintah tetap diwajibkan menyusun laporan pertanggungjawaban APBN.</li><li><strong>E</strong>: Pengalihan anggaran negara ke kas partai politik dilarang keras.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Hasilhasil pembangunan yang dibiayai APBN diharapkan dapat didistribusikan • secara merata, dan rakyat merasakan keadilan sosial bagi semua.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Contoh implementasi paling nyata dari fungsi distribusi APBN di dalam kehidupan masyarakat ditunjukkan melalui kebijakan...", "options": ["Menghapus bea masuk bagi kendaraan mewah yang diimpor pengusaha", "Membebankan tarif retribusi pasar lebih mahal kepada pedagang kecil", "Menyediakan pinjaman bunga komersial tinggi bagi kelompok tani", "Mewajibkan warga berpenghasilan rendah menanggung utang negara", "Memungut pajak dari kalangan mampu dan menyalurkan subsidi bagi warga miskin"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Contoh Nyata Pelaksanaan Fungsi Distribusi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan <strong>memungut pajak dari si kaya dan memberikan subsidi bagi si miskin merupakan salah satu contoh yang paling nyata fungsi APBN terkait distribusi pendapatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena redistribusi vertikal ini mengikis ketimpangan ekonomi dengan mentransfer daya beli dari pihak berkelebihan kepada pihak berkekurangan. Oleh sebab itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Memungut pajak dari kalangan mampu dan menyalurkan subsidi bagi warga miskin</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembebasan pajak impor kendaraan mewah menguntungkan kalangan mampu.</li><li><strong>B</strong>: Retribusi mahal bagi pedagang kecil memperburuk beban warga rentan.</li><li><strong>C</strong>: Kelompok tani justru didukung dengan subsidi pupuk dan bunga lunak.</li><li><strong>D</strong>: Warga berpenghasilan rendah dilindungi oleh jaring pengaman sosial APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 20) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>perpajakan (memungut pajak dari si kaya) dan memberikan subsidi • bagi si miskin merupakan salah satu contoh yang paling nyata fungsi APBN</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam kondisi krisis ekonomi, urgensi fungsi stabilisasi kebijakan fiskal terlihat sangat jelas dalam rangka...", "options": ["Meminimalisir volatilitas dan meredam fluktuasi siklus bisnis", "Menyerahkan mekanisme penyesuaian pasar kepada mekanisme lelang", "Menghentikan seluruh pencairan bantuan perlindungan sosial warga", "Menutup operasional kantor perbankan komersial secara permanen", "Menambah beban pungutan tarif pajak terhadap barang pokok pangan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Stabilisasi pada Masa Krisis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam kondisi krisis, fungsi kebijakan fiskal terlihat jelas ketika <strong>meminimalisir volatilitas atau fluktuasi siklus bisnis, dimana fungsi stabilisasi sangat dibutuhkan perekonomian</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena peran penahan kejut (shock absorber) fiskal mencegah perekonomian terjerumus ke jurang resesi berkepanjangan. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Meminimalisir volatilitas dan meredam fluktuasi siklus bisnis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mekanisme lelang murni saat krisis justru dapat memperdalam resesi.</li><li><strong>C</strong>: Bantuan perlindungan sosial justru ditingkatkan untuk menjaga konsumsi.</li><li><strong>D</strong>: Penutupan bank secara permanen merusak sistem pembayaran nasional.</li><li><strong>E</strong>: Pungutan pajak bahan pokok akan memicu lonjakan angka kemiskinan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>meminimalisir volatilitas atau fluktuasi siklus bisnis, dimana fungsi stabilisasi • sangat dibutuhkan perekonomian.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama pelaksanaan fungsi stabilisasi kebijakan fiskal adalah menjaga agar tingkat pendapatan nasional aktual...", "options": ["Berada jauh di bawah batas rata-rata pendapatan regional", "Mendekati tingkat kapasitas output potensial perekonomian", "Disamakan dengan cadangan devisa mata uang asing negara", "Dikelola sepenuhnya oleh lembaga perbankan swasta komersial", "Dihitung berdasarkan jumlah kepemilikan sertifikat tanah warga"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Target Pendapatan Nasional Aktual dan Potensial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Tujuan utama dari fungsi stabilisasi kebijakan fiskal adalah memelihara tingkat pendapatan nasional aktual mendekati potensialnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena meminimalkan kesenjangan output (output gap) memastikan seluruh sumber daya nasional beroperasi mendekati kapasitas penuh secara efisien. Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Mendekati tingkat kapasitas output potensial perekonomian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pendapatan nasional aktual diupayakan optimal, bukan ditekan ke bawah.</li><li><strong>C</strong>: Pendapatan nasional diukur dengan PDB, bukan cadangan devisa valas.</li><li><strong>D</strong>: Pendapatan nasional agregat merupakan indikator makroekonomi publik.</li><li><strong>E</strong>: Sertifikat tanah adalah instrumen kepemilikan aset properti perseorangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tujuan utama dari fungsi stabilisasi • kebijakan fiskal adalah memelihara tingkat pendapatan nasional aktual</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kebijakan stabilisasi seringkali dimaknai sebagai pengelolaan permintaan agregat yang ditujukan untuk secara bersamaan mencapai...", "options": ["Kenaikan suku bunga kredit komersial dan penurunan tabungan", "Monopoli impor komoditas pangan oleh korporasi multinasional", "Kesempatan kerja penuh (full employment) dan stabilitas harga", "Penyeragaman tingkat upah pekerja di seluruh belahan dunia", "Penghapusan pungutan retribusi dan denda administrasi daerah"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Kebijakan Stabilisasi Permintaan Agregat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan stabilisasi sering dimaknai sebagai pengelolaan permintaan agregat agar mencapai <strong>full employment dan stabilitas harga (price stability)</strong> secara simultan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pencapaian kesempatan kerja tinggi yang diiringi kestabilan inflasi merupakan tanda fundamental keberhasilan manajemen ekonomi makro. Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Kesempatan kerja penuh (full employment) dan stabilitas harga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Lonjakan suku bunga dan penurunan tabungan bukan tujuan stabilisasi.</li><li><strong>B</strong>: Monopoli impor pangan merugikan kedaulatan pangan dan daya beli warga.</li><li><strong>D</strong>: Tingkat upah disesuaikan dengan kondisi biaya hidup wilayah masing-masing.</li><li><strong>E</strong>: Retribusi dan denda daerah tetap dipungut sesuai koridor peraturan resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>manipulasi dari permintaan agregat agar pada saat • yang sama mencapai full employment dan stabilitas harga (price stability).</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Fluktuasi ekonomi jangka pendek dalam perekonomian pada dasarnya bersumber dari terjadinya guncangan pada...", "options": ["Pergantian susunan direksi perbankan komersial daerah", "Pilihan corak seragam kedinasan aparatur sipil kementerian", "Tata letak bangunan kantor dinas pelayanan perizinan terpadu", "Sisi permintaan agregat dan penawaran agregat perekonomian", "Jadwal jam kerja harian para staf administrasi perkantoran"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akar Fluktuasi Ekonomi Jangka Pendek</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sumber penyebab terjadinya fluktuasi ekonomi jangka pendek berasal dari <strong>guncangan permintaan agregat dan penawaran agregat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena ketidakseimbangan antara total belanja agregat dan total barang yang diproduksi memicu gelombang siklus bisnis naik-turun. Oleh karena itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Sisi permintaan agregat dan penawaran agregat perekonomian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pergantian direksi bank merupakan dinamika manajerial internal korporasi.</li><li><strong>B</strong>: Corak seragam kedinasan adalah tata tertib protokoler kantor.</li><li><strong>C</strong>: Tata letak gedung dinas merupakan urusan manajemen sarana prasarana.</li><li><strong>E</strong>: Jam kerja staf kantor adalah administrasi tata laksana kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>guncangan permintaan agregat dan penawaran agregat, maka • usaha untuk mengendalikan fluktuasi siklus bisnis seharusnya dilakukan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Stabilisator otomatis (automatic stabilizer) dalam sistem kebijakan fiskal dicirikan oleh kemampuannya yang...", "options": ["Memerlukan pengesahan undang-undang baru setiap kali terjadi resesi", "Dijalankan berdasarkan persetujuan lisan pimpinan lembaga audit", "Berlaku terbatas saat perekonomian mengalami lonjakan defisit", "Ditetapkan melalui pemungutan suara rapat komisi perbankan", "Bekerja secara alamiah merespon perubahan kegiatan ekonomi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Stabilisator Otomatis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Stabilisator otomatis (automatic stabilizer)</strong> adalah bagian sistem fiskal yang <strong>bekerja secara alamiah meredam guncangan seiring perubahan kegiatan ekonomi tanpa perlu perubahan regulasi baru</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena instrumen ini telah tertanam dalam sistem perpajakan progresif dan belanja transfer yang otomatis menyesuaikan saat siklus berfluktuasi. Maka dari itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Bekerja secara alamiah merespon perubahan kegiatan ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Stabilisator otomatis tidak memerlukan penerbitan undang-undang baru.</li><li><strong>B</strong>: Sistem ini bekerja berdasarkan hukum yang berlaku, bukan izin lisan BPK.</li><li><strong>C</strong>: Stabilisator bekerja baik saat masa resesi maupun saat masa ekspansi.</li><li><strong>D</strong>: Rapat komisi perbankan tidak mengatur mekanisme otomatis anggaran negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>stabilisator otomatis (automatic stabilizer), yang muncul dari bagian • sistem fiskal yang secara alamiah berbeda dengan perubahan pada kegiatan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kebijakan fiskal diskresioner (discretionary actions) berbeda dari stabilisator otomatis karena kebijakan ini melibatkan...", "options": ["Perubahan aktif pada kebijakan belanja, pajak, dan pembayaran transfer", "Penyesuaian otomatis tarif bunga tanpa keputusan pemerintah", "Pembagian modal investasi oleh bursa berjangka komersial", "Penghapusan seluruh sistem pencatatan administrasi kasir negara", "Pelimpahan kewenangan audit belanja kepada lembaga audit asing"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kebijakan Fiskal Diskresioner</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kebijakan fiskal diskresioner melibatkan perubahan aktif pada kebijakan yang berdampak pada pengeluaran Pemerintah, pajak dan transfer</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena tindakan diskresioner menuntut keputusan eksplisit pemerintah untuk merevisi tarif pajak atau menambah paket stimulus belanja darurat. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Perubahan aktif pada kebijakan belanja, pajak, dan pembayaran transfer</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penyesuaian otomatis merupakan karakter dari automatic stabilizers.</li><li><strong>C</strong>: Bursa berjangka memfasilitasi perdagangan derivatif komoditas pasar.</li><li><strong>D</strong>: Pencatatan kasir negara wajib dipertahankan secara akuntabel.</li><li><strong>E</strong>: Audit keuangan negara merupakan mandat eksklusif BPK RI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Melalui kebijakan fiskal diskresioner, yang melibatkan perubahan aktif pada • kebijakan yang berdampak pada pengeluaran Pemerintah, pajak dan transfer,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan rujukan IMF (WEO, 2008), dua langkah kebijakan fiskal yang bekerja menstabilkan fluktuasi ekonomi adalah...", "options": ["Pencetakan obligasi korporasi dan deregulasi pasar saham", "Stabilisator otomatis dan tindakan kebijakan diskresioner", "Penetapan plafon simpanan dan pembatasan devisa valuta", "Pengambilalihan bank swasta dan pemutihan kredit macet", "Penghapusan bea cukai impor dan privatisasi badan usaha"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Instrumen Penstabil Siklus Versi IMF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan fiskal bekerja dengan dua langkah: <strong>stabilisator otomatis (automatic stabilizer) dan kebijakan fiskal diskresioner (discretionary actions / fiscal impulse)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kedua langkah ini saling melengkapi antara respon sistemik yang spontan dan intervensi paket kebijakan pemerintah yang terencana. Oleh karena itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Stabilisator otomatis dan tindakan kebijakan diskresioner</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Obligasi korporasi dan saham merupakan instrumen bursa efek pasar modal.</li><li><strong>C</strong>: Plafon simpanan dan devisa valuta adalah domain regulasi LPS dan BI.</li><li><strong>D</strong>: Pengambilalihan bank swasta merupakan tindakan penyehatan perbankan.</li><li><strong>E</strong>: Privatisasi badan usaha merupakan kebijakan restrukturisasi BUMN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>bekerja dengan dua langkah kebijakan (policy measures) dalam • fluktuasi ekonomi (IMF, WEO,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tindakan intervensi aktif pemerintah yang disengaja dalam merespon siklus ekonomi sering disebut juga sebagai...", "options": ["Likuiditas otomatis perbankan", "Penebusan obligasi komersial", "Impuls fiskal (fiscal impulse)", "Deviasi moneter berjangka", "Dividen portofolio swasta"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Impuls Fiskal (Fiscal Impulse)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tindakan-tindakan diskresioner dikenal sebagai <strong>“discretionary actions” atau “fiscal impulse” (impuls fiskal)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena istilah impuls fiskal mengukur besaran dorongan atau tarikan intervensi pemerintah terhadap permintaan agregat di luar respon otomatis sistem. Maka dari itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Impuls fiskal (fiscal impulse)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Likuiditas otomatis perbankan merupakan konsep ketersediaan dana bank.</li><li><strong>B</strong>: Penebusan obligasi komersial adalah pelunasan utang korporasi.</li><li><strong>D</strong>: Deviasi moneter adalah istilah penyimpangan target jumlah uang.</li><li><strong>E</strong>: Dividen portofolio swasta merupakan imbal hasil investasi saham.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>discretionary actions” (tindakan-tindakan diskresioner) atau “fiscal impulse” • (impuls fiskal).</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kebijakan ekonomi makro fiskal sangat berpotensi menstabilkan perekonomian apabila dilaksanakan dengan prinsip...", "options": ["Cepat tanpa mempertimbangkan ketersediaan dana cadangan", "Tertutup agar terbebas dari pengawasan dewan legislatif", "Fleksibel tanpa terikat pada batasan undang-undang anggaran", "Baik, tepat, akurat, dan penuh kehati-hatian (prudent)", "Spekulatif guna mengejar keuntungan jangka pendek semata"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Kehati-hatian dalam Kebijakan Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan fiskal sangat berpotensi menstabilkan perekonomian jika <strong>dilaksanakan secara baik, tepat, akurat, dan prudent</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kehati-hatian (prudence) menjamin kebijakan stabilisasi efektif meredam gejolak tanpa memicu risiko fiskal atau kebangkrutan utang negara. Oleh sebab itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Baik, tepat, akurat, dan penuh kehati-hatian (prudent)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengabaikan ketersediaan cadangan membahayakan likuiditas kas negara.</li><li><strong>B</strong>: Kebijakan tertutup melanggar asas keterbukaan dan akuntabilitas.</li><li><strong>C</strong>: Pengelolaan anggaran wajib berpedoman pada undang-undang APBN.</li><li><strong>E</strong>: Tindakan spekulatif dilarang keras dalam manajemen keuangan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>dilaksanakan secara baik, tepat, akurat, dan prudent. • Sebaliknya, jika kebijakan-kebijakan tersebut tidak dijalankan dan dikelola</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C4 - Analisis", "question": "Apabila kebijakan fiskal tidak dirancang dan dikelola secara baik dan tepat, risiko makroekonomi yang dapat terjadi adalah...", "options": ["Penurunan rasio utang publik terhadap PDB secara drastis", "Lonjakan surplus neraca pembayaran luar negeri negara", "Peningkatan cadangan devisa pemerintah secara berlebih", "Penghapusan beban bunga pinjaman internasional secara otomatis", "Menciptakan ketidakstabilan baru yang memperburuk perekonomian"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Risiko Kegagalan Tata Kelola Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jika kebijakan tidak dijalankan dan dikelola dengan baik, <strong>justru akan dapat menciptakan masalah baru pada ketidakstabilan ekonomi yang bahkan lebih buruk lagi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena salah perhitungan dosis fiskal dapat memicu lonjakan utang tak terkendali, hiperinflasi, atau distorsi pasar yang memperparah krisis. Oleh karena itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Menciptakan ketidakstabilan baru yang memperburuk perekonomian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengelolaan buruk justru meningkatkan rasio utang negara.</li><li><strong>B</strong>: Ketidakstabilan fiskal cenderung memperlemah neraca pembayaran.</li><li><strong>C</strong>: Cadangan devisa terancam terkuras bila terjadi gejolak makroekonomi.</li><li><strong>D</strong>: Beban bunga pinjaman internasional tetap wajib dibayar sesuai kontrak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 21) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>menciptakan masalah baru pada ketidakstabilan • ekonomi yang bukan tidak mungkin bahkan akan lebih buruk lagi.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Perubahan otomatis pengeluaran pemerintah dan penerimaan pajak merupakan hasil dari sistem fiskal yang memiliki sifat...", "options": ["Fleksibilitas bawaan otomatis (the built-in flexibility)", "Kekakuan administratif yang mutlak dan tertutup", "Ketergantungan penuh pada persetujuan badan legislatif", "Sifat situasional berdasarkan instruksi perbankan swasta", "Kewajiban penyerahan wewenang kepada otoritas moneter"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Built-in Flexibility Sistem Fiskal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perubahan otomatis pengeluaran (G) dan pajak (T) merupakan hasil dari <strong>fleksibilitas otomatis dari sistem fiskal (the built-in flexibility of the fiscal system)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena mekanisme bawaan ini bereaksi seketika terhadap perubahan siklus bisnis tanpa memerlukan proses perdebatan birokrasi yang panjang. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Fleksibilitas bawaan otomatis (the built-in flexibility)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sistem kaku justru menghambat respon adaptif peredam gejolak fiskal.</li><li><strong>C</strong>: Fleksibilitas otomatis bekerja tanpa persetujuan undang-undang baru.</li><li><strong>D</strong>: Sistem fiskal negara tidak bergantung pada instruksi bank swasta.</li><li><strong>E</strong>: Fiskal dikelola pemerintah, bukan diserahkan ke otoritas moneter.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>fleksibilitas otomatis dari sistem fiskal (the built-in • flexibility of the fiscal system).</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C3 - Aplikasi", "question": "Ketika perekonomian mengalami resesi dan pendapatan warga menurun, mekanisme stabilisator otomatis bekerja melalui...", "options": ["Peningkatan pungutan tarif pajak dan penghentian dana bantuan", "Penurunan otomatis penerimaan pajak dan kenaikan belanja bansos", "Penarikan seluruh dana simpanan masyarakat dari perbankan", "Penghentian proyek fasilitas umum demi menimbun kas negara", "Penjualan surat utang dengan suku bunga tinggi kepada warga miskin"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Respon Otomatis Sistem Fiskal saat Resesi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketika pendapatan menurun dan resesi terjadi, <strong>penerimaan pajak secara otomatis menurun dan pengeluaran pemerintah untuk belanja bantuan sosial secara otomatis meningkat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena penurunan beban pajak meringankan warga dan tambahan transfer bansos menopang daya beli konsumsi masyarakat dari kejatuhan lebih dalam. Oleh sebab itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Penurunan otomatis penerimaan pajak dan kenaikan belanja bansos</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menaikkan pajak saat resesi justru memperparah kelesuan ekonomi.</li><li><strong>C</strong>: Penarikan simpanan memicu krisis likuiditas sistem perbankan.</li><li><strong>D</strong>: Menimbun kas saat resesi memotong stimulus ekonomi yang dibutuhkan.</li><li><strong>E</strong>: Surat utang negara diterbitkan untuk investor, bukan membebani warga miskin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>penerimaan pajak secara otomatis menurun • dan pengeluaran pemerintah untuk belanja bantuan sosial, kompensasi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Sebagai instrumen fiskal, peningkatan penerimaan negara melalui pengubahan tarif perpajakan akan memberikan dampak berupa...", "options": ["Penghapusan seluruh utang luar negeri pemerintah secara instan", "Kenaikan otomatis jumlah uang kartal yang dicetak bank sentral", "Pengaruh langsung terhadap perilaku ekonomi dan daya beli warga", "Penyeragaman tingkat keuntungan seluruh badan usaha swasta", "Pemberian kebebasan impor barang komersial tanpa pengawasan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengaruh Perubahan Tarif Pajak</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Peningkatan penerimaan karena perubahan tarif pajak akan berpengaruh pada ekonomi</strong> melalui dampaknya terhadap pendapatan siap konsumsi masyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena modifikasi tarif pajak mengubah insentif konsumsi, tabungan, dan keputusan investasi para pelaku ekonomi riil. Oleh karena itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Pengaruh langsung terhadap perilaku ekonomi dan daya beli warga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak tidak menghapuskan utang luar negeri secara langsung seketika.</li><li><strong>B</strong>: Pencetakan uang kartal adalah wewenang Bank Sentral di bidang moneter.</li><li><strong>D</strong>: Tingkat keuntungan swasta ditentukan oleh kinerja pasar masing-masing.</li><li><strong>E</strong>: Impor komersial tetap tunduk pada kepatuhan kepabeanan resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peningkatan penerimaan karena perubahan tarif pajak akan berpengaruh • pada ekonomi,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Instrumen pengeluaran atau belanja pemerintah memberikan stimulasi terhadap perekonomian melalui dampaknya pada...", "options": ["Tingkat kurs penukaran valuta asing di bursa regional", "Struktur kepengurusan asosiasi pengusaha manufaktur", "Penetapan batas maksimum kepemilikan emas perseorangan", "Sisi pengeluaran dan permintaan agregat masyarakat", "Jadwal keberangkatan armada penerbangan komersial swasta"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Stimulasi Melalui Sisi Pengeluaran Agregat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pengeluaran Pemerintah akan berpengaruh pada stimulasi pada perekonomian melalui dampaknya terhadap sisi pengeluaran agregat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena injeksi belanja negara langsung menyuntikkan likuiditas ke pasar barang dan jasa sehingga menggerakkan roda produksi riil nasional. Maka dari itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Sisi pengeluaran dan permintaan agregat masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kurs valas dipengaruhi oleh arus neraca perdagangan dan pasar valas.</li><li><strong>B</strong>: Kepengurusan asosiasi pengusaha merupakan organisasi non-pemerintah.</li><li><strong>C</strong>: Kepemilikan emas perseorangan adalah hak privat warga negara.</li><li><strong>E</strong>: Jadwal penerbangan diatur oleh otoritas perhubungan dan maskapai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pengeluaran • Pemerintah • akan • berpengaruh • pada • stimulasi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan kondisi perekonomian yang dihadapi, pilihan politik anggaran yang dapat diambil pemerintah mencakup opsi...", "options": ["Anggaran privat, anggaran komersial, atau anggaran rahasia", "Anggaran spekulatif, anggaran lelang, atau anggaran titipan", "Anggaran regional, anggaran terisolasi, atau anggaran parsial", "Anggaran monopoli, anggaran proteksi, atau anggaran barter", "Anggaran surplus, anggaran berimbang, atau anggaran defisit"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pilihan Politik Anggaran Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah merespon kondisi perekonomian melalui instrumen <strong>politik anggaran (surplus, berimbang, atau defisit)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena ketiga postur anggaran tersebut merupakan instrumen respon fiskal klasik dalam menghadapi dinamika siklus ekonomi yang berbeda. Oleh sebab itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Anggaran surplus, anggaran berimbang, atau anggaran defisit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Anggaran privat dan rahasia bertentangan dengan asas universalitas publik.</li><li><strong>B</strong>: Anggaran spekulatif dan titipan dilarang dalam pengelolaan perbendaharaan.</li><li><strong>C</strong>: Anggaran terisolasi bukan konsep ilmiah postur APBN kenegaraan.</li><li><strong>D</strong>: Anggaran barter tidak dikenal dalam sistem keuangan modern berbasis kas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Politik anggaran (surplus, berimbang, atau defisit) sebagai respon atas suatu • kondisi, serta</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Instrumen keempat dari kebijakan fiskal yang melengkapi penerimaan, pengeluaran, dan politik anggaran adalah...", "options": ["Strategi pembiayaan dan pengelolaan utang negara", "Penerbitan kartu debit khusus bagi nasabah perbankan", "Penarikan seluruh dana pensiun pegawai secara mendadak", "Penjualan gedung kantor kementerian kepada korporasi", "Pemberian pinjaman berbunga komersial bagi pihak asing"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Strategi Pembiayaan dan Pengelolaan Utang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Empat instrumen kebijakan fiskal mencakup penerimaan pajak, belanja pemerintah, politik anggaran, serta <strong>strategi pembiayaan dan pengelolaan hutang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena manajemen utang yang prudent menjamin ketersediaan likuiditas pembiayaan defisit dengan risiko beban bunga terukur. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Strategi pembiayaan dan pengelolaan utang negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kartu debit nasabah merupakan produk jasa perbankan komersial.</li><li><strong>C</strong>: Penarikan mendadak dana pensiun melanggar jaminan hari tua aparatur.</li><li><strong>D</strong>: Gedung kementerian merupakan barang milik negara yang dilindungi hukum.</li><li><strong>E</strong>: Pinjaman komersial kepada pihak asing bukan instrumen pembiayaan APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Strategi pembiayaan dan pengelolaan hutang. • Kebijakan APBN Indonesia diarahkan kepada balanced budget over cycle yaitu</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Arah kebijakan APBN Indonesia menekankan konsep 'balanced budget over cycle' yang bermakna kondisi anggaran...", "options": ["Wajib tepat berimbang tanpa defisit setiap satu bulan kalender", "Secara rata-rata menuju seimbang selama periode jangka panjang siklus", "Diharuskan selalu mengalami surplus mutlak sepanjang masa", "Mengalokasikan seluruh saldo simpanan kas negara ke bank swasta", "Menyerahkan penutupan defisit kepada sumbangan sukarela warga"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsep Balanced Budget Over Cycle</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Balanced budget over cycle yaitu kondisi APBN yang secara rata-rata menuju seimbang selama suatu periode jangka panjang siklus ekonomi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena konsep ini memberikan fleksibilitas defisit saat resesi yang ditutup oleh surplus saat boom, sehingga dalam satu siklus penuh berimbang. Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Secara rata-rata menuju seimbang selama periode jangka panjang siklus</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Berimbang kaku setiap bulan mematikan fungsi stabilisator ekonomi negara.</li><li><strong>C</strong>: Surplus mutlak terus-menerus dapat menekan daya beli masyarakat luas.</li><li><strong>D</strong>: Kas negara disimpan pada Rekening Kas Umum Negara di Bank Sentral.</li><li><strong>E</strong>: Defisit dibiayai instrumen pembiayaan resmi, bukan sumbangan sukarela.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>balanced budget over cycle yaitu • kondisi APBN yang secara rata-rata menuju seimbang selama suatu periode</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Guna mewujudkan anggaran berimbang sepanjang siklus, kebijakan fiskal dirancang bersifat kontra-siklis (countercyclical) agar mampu...", "options": ["Mengikuti gelombang kenaikan harga dengan menaikkan beban belanja", "Memperbesar kepemilikan portofolio saham pada bursa komersial", "Berfungsi sebagai stabilisator dengan meredam lonjakan fluktuasi siklus", "Menghentikan seluruh pembiayaan proyek pembangunan infrastruktur", "Menetapkan suku bunga kredit usaha mikro secara sewenang-wenang"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sifat Kontra-Siklis (Countercyclical)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan fiskal seyogyanya <strong>dapat berfungsi sebagai stabilisator bagi perekonomian, dan atau bersifat kontra-siklis (countercyclical)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena sifat kontra-siklis bekerja menahan laju ekonomi saat terlalu panas dan mendorong permintaan saat ekonomi mendingin. Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Berfungsi sebagai stabilisator dengan meredam lonjakan fluktuasi siklus</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengikuti gelombang siklus merupakan karakteristik dari kebijakan pro-cyclical.</li><li><strong>B</strong>: Portofolio saham komersial bukan sasaran kebijakan stabilisator APBN.</li><li><strong>D</strong>: Infrastruktur tetap berjalan dengan pentahapan yang terukur dan efisien.</li><li><strong>E</strong>: Suku bunga kredit usaha ditentukan mekanisme perbankan dan KUR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>stabilisator • bagi perekonomian, dan atau bersifat kontra-siklis (countercyclical) yang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C4 - Analisis", "question": "Perbandingan mekanisme kerja antara kebijakan moneter dan kebijakan fiskal tercermin pada fakta bahwa...", "options": ["Moneter mengelola penerimaan pajak, sedangkan fiskal mengelola cadangan emas", "Moneter mengatur gaji aparatur, sedangkan fiskal mengatur tarif bunga bank", "Moneter diawasi oleh parlemen, sedangkan fiskal diawasi lembaga swasta", "Moneter mengatur uang beredar, fiskal mengatur pendapatan belanja", "Moneter berlaku tahunan, sedangkan fiskal berlaku permanen tanpa revisi"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analogi Kebijakan Fiskal dan Moneter</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Analog dengan cara kerja kebijakan moneter dalam mengatur jumlah uang beredar, kebijakan fiskal mengatur pendapatan dan belanja pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kebijakan moneter mengintervensi likuiditas uang perbankan, sementara kebijakan fiskal mengintervensi arus penerimaan dan belanja kas APBN. Oleh karena itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Moneter mengatur uang beredar, fiskal mengatur pendapatan belanja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak adalah instrumen fiskal, bukan domain kebijakan moneter.</li><li><strong>B</strong>: Gaji aparatur adalah pos belanja fiskal, bunga bank adalah sinyal moneter.</li><li><strong>C</strong>: Fiskal dipertanggungjawabkan kepada DPR, bukan lembaga swasta.</li><li><strong>E</strong>: APBN berlaku tahunan dan dapat direvisi melalui APBN-Perubahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Analog dengan cara kerja kebijakan moneter dalam • mengatur jumlah uang beredar, kebijakan fiskal mengatur pendapatan dan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dokumen APBN memuat instrumen penerimaan dan pengeluaran pemerintah yang pada hakikatnya berkedudukan sebagai suatu...", "options": ["Catatan utang komersial perseroan swasta", "Buku pedoman investasi modal perorangan", "Daftar lelang barang sitaan kepolisian", "Perjanjian perikatan dagang bilateral", "Rencana operasi keuangan pemerintah"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • APBN sebagai Rencana Operasi Keuangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Instrumen kebijakan fiskal adalah penerimaan dan pengeluaran pemerintah yang tertuang dalam APBN sebagai suatu rencana operasi keuangan pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena APBN merupakan cetak biru operasional keuangan negara dalam menjalankan seluruh kegiatan pemerintahan selama satu tahun anggaran. Maka dari itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Rencana operasi keuangan pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Utang korporasi swasta merupakan urusan perdata perusahaan komersial.</li><li><strong>B</strong>: Investasi modal perorangan merupakan keputusan finansial privat.</li><li><strong>C</strong>: Lelang sitaan kepolisian merupakan bagian eksekusi penegakan hukum.</li><li><strong>D</strong>: Perikatan dagang bilateral merupakan perjanjian perdagangan antarnegara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 22) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Instrumen kebijakan fiskal adalah penerimaan dan pengeluaran pemerintah • yang tertuang dalam APBN sebagai suatu rencana operasi keuangan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam kerangka kebijakan fiskal kontra-siklis, saat perekonomian berada dalam masa ekspansi tinggi (boom), pemerintah menjalankan...", "options": ["Surplus Anggaran guna meredam laju inflasi berlebih", "Ekspansi belanja besar-besaran untuk memacu permintaan", "Penarikan utang luar negeri dalam jumlah maksimal", "Pemberian pembebasan pajak bagi seluruh korporasi", "Pencairan seluruh saldo dana cadangan kas negara"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerapan Surplus Anggaran saat Masa Boom</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada saat ekonomi sedang dalam masa boom, <strong>pemerintah dapat menjalankan Surplus Anggaran</strong> guna menstabilkan perekonomian.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena surplus anggaran saat masa boom menarik kelebihan likuiditas dan menumpuk tabungan fiskal untuk antisipasi resesi. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Surplus Anggaran guna meredam laju inflasi berlebih</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Ekspansi belanja saat boom justru memicu lonjakan inflasi ekstrem.</li><li><strong>C</strong>: Penarikan utang saat boom menambah beban bunga yang tidak perlu.</li><li><strong>D</strong>: Pembebasan pajak korporasi saat boom memperburuk kepanasan ekonomi.</li><li><strong>E</strong>: Mencairkan dana cadangan saat boom bertentangan dengan stabilisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>a) Pada saat ekonomi sedang dalam masa boom, pemerintah dapat • menjalankan Surplus Anggaran,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Sebaliknya, pada saat perekonomian menghadapi resesi atau krisis, langkah fiskal yang diambil pemerintah adalah...", "options": ["Pengetatan belanja dinas dan kenaikan tarif pajak serentak", "Ekspansi stimulus fiskal dengan menjalankan Anggaran Defisit", "Penghentian penyaluran bantuan perlindungan sosial warga", "Penutupan fasilitas pasar obligasi surat utang negara", "Penarikan seluruh dana simpanan pemerintah dari perbankan"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ekspansi Fiskal saat Resesi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada saat resesi/krisis, pemerintah melakukan <strong>ekspansi fiskal melalui stimulus fiskal, dengan menjalankan Anggaran Defisit</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena stimulus belanja defisit menopang daya beli masyarakat dan mencegah kejatuhan ekonomi lebih dalam saat swasta lesu. Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Ekspansi stimulus fiskal dengan menjalankan Anggaran Defisit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengetatan belanja dan kenaikan pajak saat krisis memperparah resesi.</li><li><strong>C</strong>: Bantuan sosial justru harus ditambah untuk melindungi warga rentan.</li><li><strong>D</strong>: Pasar obligasi dibutuhkan untuk menerbitkan surat utang pembiayaan.</li><li><strong>E</strong>: Menarik simpanan kas secara masif dapat memicu kekeringan likuiditas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>b) Sebaliknya, pada saat resesi/krisis, pemerintah dapat melakukan ekspansi • fiskal melalui stimulus fiskal, dengan menjalankan Anggaran Defisit, serta</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Secara keseluruhan dalam satu periode siklus anggaran jangka menengah, postur APBN diarahkan untuk...", "options": ["Mengalami defisit permanen yang semakin membengkak", "Menghapuskan fungsi penyaluran subsidi bagi warga", "Mengarah menjadi berimbang sepanjang siklus ekonomi", "Mengalihkan seluruh kas negara ke bursa spekulasi", "Menunda kewajiban pembayaran pokok utang jatuh tempo"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keseimbangan Anggaran Jangka Menengah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Secara overall, dalam satu periode siklus anggaran jangka menengah, <strong>APBN mengarah menjadi Berimbang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena prinsip kesinambungan fiskal menuntut akumulasi surplus saat ekspansi dapat mengimbangi defisit saat resesi. Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Mengarah menjadi berimbang sepanjang siklus ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Defisit permanen membengkak membahayakan kedaulatan fiskal negara.</li><li><strong>B</strong>: Subsidi pokok tetap dijalankan secara terarah dan terukur.</li><li><strong>D</strong>: Spekulasi kas dilarang keras dalam hukum keuangan negara.</li><li><strong>E</strong>: Pembayaran pokok utang jatuh tempo merupakan kewajiban konstitusional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>c) Secara overall, dalam satu periode siklus anggaran jangka menengah, APBN • mengarah menjadi Berimbang.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Sebagai alat kebijakan fiskal, karakteristik utama dari APBN yang sehat dan berkesinambungan ditandai oleh...", "options": ["Peningkatan rasio utang melampaui produk domestik bruto", "Keseimbangan primer yang bernilai negatif secara permanen", "Pencairan belanja dinas tanpa batasan plafon pagu anggaran", "Defisit terkendali, keseimbangan primer positif, dan rasio utang menurun", "Ketergantungan penuh pada pinjaman komersial jangka pendek"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter APBN yang Berkesinambungan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>APBN yang sehat adalah APBN berkesinambungan yang ditunjukkan oleh: <strong>defisit terkendali menuju seimbang, keseimbangan primer positif, dan rasio utang menurun</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena ketiga parameter tersebut menjamin beban keuangan negara dapat ditanggung generasi mendatang tanpa krisis solvabilitas. Oleh karena itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Defisit terkendali, keseimbangan primer positif, dan rasio utang menurun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rasio utang melampaui PDB mengindikasikan risiko kebangkrutan fiskal.</li><li><strong>B</strong>: Keseimbangan primer negatif berarti penarikan utang baru untuk bayar bunga.</li><li><strong>C</strong>: Belanja tanpa batasan plafon melanggar disiplin perbendaharaan.</li><li><strong>E</strong>: Ketergantungan utang jangka pendek menimbulkan risiko gagal bayar tinggi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sebagai Fiscal Policy Tools (Alat Kebijakan Fiskal), APBN yang sehat adalah • APBN yang berkesinambungan, yang ditunjukkan oleh:</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan acuan internasional menurut World Bank, batas aman rasio utang pemerintah terhadap PDB adalah...", "options": ["Maksimal sebesar 95% terhadap PDB", "Tepat sebesar 10% terhadap PDB", "Bebas ditentukan tanpa batasan rasio", "Maksimal sebesar 150% terhadap PDB", "Maksimal sebesar 60% terhadap PDB"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batas Aman Rasio Utang terhadap PDB</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Benchmark rasio hutang terhadap PDB <strong>menurut World Bank maksimal 60%</strong> sebagai batas sustainabilitas utang publik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena batas 60% diadopsi dalam perundang-undangan keuangan negara Indonesia untuk menjaga kehati-hatian pengelolaan utang. Maka dari itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Maksimal sebesar 60% terhadap PDB</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Batas 95% sudah memasuki zona bahaya kerentanan fiskal tinggi.</li><li><strong>B</strong>: Batas 10% terlalu restriktif untuk negara berkembang yang membangun.</li><li><strong>C</strong>: Ketiadaan batas rasio utang merusak disiplin pasar perbendaharaan.</li><li><strong>D</strong>: Batas 150% menunjukkan beban utang negara yang sangat kritis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>c) Rasio yang cenderung menurun (benchmark rasio hutang terhadap PDB • menurut World Bank maksimal 60%).</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kondisi keseimbangan primer yang terjaga positif pada postur APBN memiliki arti bahwa...", "options": ["Total pendapatan negara melebihi belanja di luar pembayaran bunga utang", "Pemerintah tidak mampu membayar bunga utang jatuh tempo", "Seluruh belanja kementerian didanai dari utang komersial baru", "Penerimaan perpajakan berada pada posisi nol rupiah", "Pos belanja negara dialihkan sepenuhnya untuk gaji pegawai"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Keseimbangan Primer Positif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keseimbangan primer positif berarti <strong>total pendapatan negara lebih besar daripada belanja di luar pos pembayaran bunga utang</strong>, sehingga negara tidak meminjam untuk membayar bunga.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena keseimbangan primer positif menunjukkan kapasitas fiskal murni pemerintah masih mencukupi untuk membiayai operasional kenegaraan. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Total pendapatan negara melebihi belanja di luar pembayaran bunga utang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keseimbangan primer positif justru menjamin kemampuan bayar bunga.</li><li><strong>C</strong>: Keseimbangan primer positif mengurangi kebutuhan utang baru.</li><li><strong>D</strong>: Penerimaan pajak tetap menjadi pilar utama pembentuk pendapatan.</li><li><strong>E</strong>: Belanja negara terbagi proporsional antara belanja pegawai, barang, dan modal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>b) Keseimbangan primer terjaga positif, serta • Defisit yang terkendali menuju seimbang atau surplus,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Instrumen APBN digunakan sebagai sarana mencapai kemakmuran rakyat, yang salah satunya diwujudkan melalui...", "options": ["Pemberian dana talangan investasi bagi korporasi spekulan", "Penyediaan barang publik dan peningkatan kualitas pelayanan dasar", "Penetapan tarif bunga pinjaman tinggi bagi koperasi daerah", "Pembatasan operasional fasilitas puskesmas di daerah pedesaan", "Pengurangan alokasi anggaran operasional sekolah negeri"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Saluran Kemakmuran Rakyat via Barang Publik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>APBN digunakan untuk mencapai kemakmuran rakyat melalui <strong>penyediaan barang publik dan peningkatan kualitas pelayanan dasar</strong> bagi seluruh masyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pemenuhan barang publik seperti jalan raya, kesehatan dasar, dan sanitasi langsung mengangkat taraf hidup rakyat. Oleh karena itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Penyediaan barang publik dan peningkatan kualitas pelayanan dasar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Talangan spekulan bertentangan dengan kemakmuran rakyat.</li><li><strong>C</strong>: Koperasi daerah didukung dengan pembiayaan terjangkau dan subsidi.</li><li><strong>D</strong>: Puskesmas desa justru ditingkatkan kualitas dan jangkauan pelayanannya.</li><li><strong>E</strong>: Anggaran pendidikan dijamin minimal 20% dalam konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>b) Penyediaan barang publik dan peningkatan kualitas pelayanan dasar, • a) Penciptaan stabilitas ekonomi,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pengembangan ekonomi daerah melalui desentralisasi fiskal dalam APBN diselenggarakan demi mewujudkan...", "options": ["Pemusatan seluruh pendapatan asli daerah ke kas perbendaharaan pusat", "Penghapusan wewenang kepala daerah dalam menyusun anggaran dinas", "Otonomi daerah yang bertanggung jawab dan pertumbuhan yang inklusif", "Penyeragaman potensi sektor unggulan di seluruh provinsi", "Pembatasan penyaluran dana transfer bagi pemerintah kabupaten"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Desentralisasi Fiskal dan Pertumbuhan Inklusif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>APBN mendukung pengembangan ekonomi daerah melalui <strong>desentralisasi fiskal dalam rangka otonomi daerah yang luas dan bertanggung jawab (pertumbuhan yang inklusif)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena transfer ke daerah mempersempit kesenjangan fiskal antardaerah dan memberdayakan pemerintah daerah memajukan warganya. Maka dari itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Otonomi daerah yang bertanggung jawab dan pertumbuhan yang inklusif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pendapatan asli daerah tetap dikelola mandiri oleh kas daerah.</li><li><strong>B</strong>: Kepala daerah memiliki wewenang sah menyusun APBD bersama DPRD.</li><li><strong>D</strong>: Setiap daerah memiliki keunggulan komparatif yang beragam.</li><li><strong>E</strong>: Dana transfer disalurkan berdasarkan formula kebutuhan fiskal objektif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>e) Pengembangan ekonomi daerah melalui desentralisasi fiskal dalam rangka • otonomi daerah yang luas dan bertanggung jawab (pertumbuhan yang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kebijakan dan strategi pengelolaan APBN sebagai rencana operasi keuangan pemerintah wajib mengacu pada...", "options": ["Rencana bisnis tahunan konsorsium perbankan komersial swasta", "Anggaran dasar dan anggaran rumah tangga serikat buruh", "Rekomendasi tertutup forum pialang efek pasar modal", "Rencana pembangunan nasional dalam bentuk RPJM dan RKP", "Perjanjian perikatan dagang para importir komoditas barang"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keselarasan APBN dengan Dokumen Pembangunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan dan strategi pengelolaan APBN diarahkan dan <strong>mengacu pada rencana pembangunan: RPJM dan RKP</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena penganggaran (budgeting) harus menjadi instrumen penerjemah rencana kerja pembangunan nasional (planning) yang telah disepakati. Oleh sebab itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Rencana pembangunan nasional dalam bentuk RPJM dan RKP</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rencana bisnis konsorsium bank swasta merupakan urusan korporasi perbankan.</li><li><strong>B</strong>: AD/ART serikat buruh merupakan pedoman organisasi ketenagakerjaan.</li><li><strong>C</strong>: Rekomendasi pialang pasar modal adalah analisis transaksi efek.</li><li><strong>E</strong>: Perikatan dagang importir merupakan hubungan hukum dagang perdata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kebijakan dan Strategi pengelolaan • APBN sebagai suatu rencana operasi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C4 - Analisis", "question": "APBN merupakan instrumen fiskal utama guna mencapai kemakmuran rakyat melalui saluran berikut, KECUALI...", "options": ["Penciptaan stabilitas ekonomi dari guncangan inflasi harga", "Penyediaan barang publik dan peningkatan mutu layanan dasar", "Peningkatan pertumbuhan ekonomi dan penciptaan lapangan kerja", "Perlindungan sosial terhadap kelompok masyarakat kurang mampu", "Pemberian dana talangan bagi spekulan bursa valuta komersial"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pilar Kemakmuran Rakyat via APBN (Kecuali)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Saluran kemakmuran rakyat APBN meliputi <strong>stabilitas ekonomi, barang publik, pertumbuhan dan lapangan kerja, perlindungan kelompok miskin, serta desentralisasi fiskal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat sebagai pengecualian karena uang rakyat dalam APBN dilarang disalahgunakan untuk menalangi kerugian spekulan pasar komersial. Oleh karena itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Pemberian dana talangan bagi spekulan bursa valuta komersial</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Stabilitas ekonomi merupakan pilar resmi kemakmuran rakyat APBN.</li><li><strong>B</strong>: Penyediaan barang publik adalah amanat pokok belanja negara.</li><li><strong>C</strong>: Pertumbuhan dan lapangan kerja adalah target utama postur APBN.</li><li><strong>D</strong>: Perlindungan kelompok miskin adalah komitmen keadilan sosial fiskal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 23) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>d) Perlindungan terhadap kelompok miskin, serta • Penciptaan stabilitas ekonomi,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Jumlah besaran dan komposisi pos anggaran dalam APBN sangat dipengaruhi oleh kombinasi faktor di bidang...", "options": ["Ekonomi, politik, dan sosial baik internal maupun eksternal", "Mode pakaian kedinasan aparatur dan desain gedung kantor", "Sistem pembagian dividen emiten perbankan komersial swasta", "Pilihan menu konsumsi rapat dinas para pejabat kementerian", "Kapasitas tempat parkir kendaraan dinas di kantor wilayah"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Faktor Penentu Besaran dan Komposisi APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>APBN sangat dipengaruhi oleh <strong>berbagai faktor, baik di bidang ekonomi, politik, maupun sosial, yang berasal dari internal maupun eksternal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penetapan anggaran negara merupakan titik temu dinamika kondisi perekonomian makro, konsensus politik parlemen, dan kebutuhan sosial masyarakat. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Ekonomi, politik, dan sosial baik internal maupun eksternal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mode pakaian dinas merupakan tata tertib seragam kepegawaian.</li><li><strong>C</strong>: Dividen emiten bank swasta adalah urusan korporasi bursa modal.</li><li><strong>D</strong>: Menu konsumsi rapat merupakan urusan operasional rumah tangga kantor.</li><li><strong>E</strong>: Kapasitas parkir kendaraan merupakan fasilitas fisik sarana dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>APBN, termasuk jumlah besaran dan komposisinya, sangat dipengaruhi oleh • berbagai faktor, baik di bidang ekonomi, politik, maupun sosial.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Faktor internal pertama yang mendasari arah perumusan komposisi APBN adalah...", "options": ["Keputusan direksi perbankan komersial dalam menetapkan bunga", "Arah dan strategi politik pembangunan guna mencapai tujuan bernegara", "Hasil lelang pengadaan kendaraan dinas operasional kantor", "Daftar mutasi jabatan pegawai aparatur pada unit pelaksana teknis", "Perjanjian perikatan dagang ekspor para pelaku usaha swasta"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Faktor Internal Pertama: Arah Politik dan Pembangunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Faktor internal pertama adalah <strong>arah dan strategi politik dan pembangunan yang ingin dilakukan dalam mencapai tujuan bernegara yang berimplikasi pada kebijakan keuangan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena tujuan bernegara dalam konstitusi menjadi panduan utama penentuan prioritas belanja dan mobilisasi pendapatan APBN. Oleh sebab itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Arah dan strategi politik pembangunan guna mencapai tujuan bernegara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keputusan bunga bank komersial adalah aksi perbankan bisnis swasta.</li><li><strong>C</strong>: Hasil lelang kendaraan merupakan pengadaan barang operasional dinas.</li><li><strong>D</strong>: Mutasi jabatan pegawai merupakan manajemen pembinaan karier SDM.</li><li><strong>E</strong>: Perikatan ekspor swasta adalah transaksi perniagaan sektor privat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pertama, arah dan strategi politik dan pembangunan yang ingin dilakukan dalam • mencapai tujuan bernegara yang berimplikasi pada kebijakan keuangan negara.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Penjabaran tujuan bernegara dalam UUD 1945 dituangkan ke dalam dokumen perencanaan jangka panjang yang berjangka waktu...", "options": ["50 tahun dalam Rencana Induk Rekayasa Infrastruktur", "100 tahun dalam Dokumen Visi Kebudayaan Nusantara", "25 tahun dalam Rencana Pembangunan Jangka Panjang (RPJP)", "10 tahun dalam Rencana Konsolidasi Perbankan Nasional", "15 tahun dalam Panduan Tata Kelola Sektor Korporasi"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hierarki Perencanaan Pembangunan Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan bernegara dijabarkan dalam <strong>Rencana Pembangunan Jangka Panjang (25 tahun) dan Rencana Pembangunan Jangka Menengah (RPJM) Nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena sistem perencanaan pembangunan nasional menetapkan RPJP berdurasi 25 tahun sebagai arah makro generasi pembangunan bangsa. Oleh karena itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. 25 tahun dalam Rencana Pembangunan Jangka Panjang (RPJP)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rencana 50 tahun bukan dokumen perencanaan pembangunan berkala resmi.</li><li><strong>B</strong>: Visi 100 tahun merupakan gagasan perayaan seratus tahun kemerdekaan.</li><li><strong>D</strong>: Rencana 10 tahun bukan siklus RPJP dalam undang-undang perencanaan.</li><li><strong>E</strong>: Panduan 15 tahun bukan instrumen resmi tata ruang perencanaan fiskal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Tujuan bernegara tercantum dalam UUD 1945. • Rencana Pembangunan Jangka Panjang (25 tahun) dan Rencana</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Rencana Pembangunan Jangka Menengah Nasional (RPJMN) berdurasi 5 tahun diterjemahkan secara operasional setiap tahun ke dalam...", "options": ["Buku Pedoman Pengawasan Internal Lembaga Pemeriksa", "Daftar Tarif Premi Asuransi Kerugian Perusahaan", "Laporan Evaluasi Tahunan Kamar Dagang dan Industri", "Rencana Kerja Pemerintah (RKP) yang menentukan prioritas nasional", "Katalog Pengadaan Barang Komersial Elektronik Daerah"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerjemahan Tahunan Melalui RKP</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>RPJMN mencakup periode 5 tahun yang <strong>diterjemahkan secara tahunan dalam Rencana Kerja Pemerintah (RKP) yang menentukan prioritas dan arah kebijakan nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena RKP menjadi jembatan resmi antara rencana pembangunan lima tahunan dan penyusunan pagu anggaran belanja APBN tahunan. Maka dari itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Rencana Kerja Pemerintah (RKP) yang menentukan prioritas nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pedoman pengawasan internal adalah standar audit aparat pemeriksa.</li><li><strong>B</strong>: Tarif premi asuransi merupakan produk industri asuransi komersial.</li><li><strong>C</strong>: Laporan Kadin merupakan laporan kegiatan asosiasi pelaku usaha.</li><li><strong>E</strong>: Katalog barang elektronik adalah sistem etalase belanja LPSE.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>RPJMN mencakup periode • 5 tahun yang akan diterjemahkan secara tahunan dalam Rencana Kerja</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Faktor internal kedua yang mempengaruhi besaran dan struktur APBN berkaitan dengan perkembangan perekonomian nasional yang menggambarkan...", "options": ["Jumlah cabang kantor bank komersial swasta di luar negeri", "Desain logo merek dagang korporasi manufaktur lokal", "Tingkat kehadiran peserta pameran otomotif tahunan", "Susunan kepanitiaan lelang pengadaan mebel kantor dinas", "Potensi, kapasitas, dan struktur penerimaan negara"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Faktor Internal Kedua: Kinerja Ekonomi Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Faktor internal kedua adalah <strong>perkembangan dan kinerja perekonomian nasional yang menggambarkan potensi, kapasitas dan struktur penerimaan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pertumbuhan PDB dan perkembangan sektor industri riil secara langsung menentukan kapasitas basis penerimaan perpajakan negara. Oleh sebab itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Potensi, kapasitas, dan struktur penerimaan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cabang bank swasta di luar negeri adalah ekspansi bisnis korporasi.</li><li><strong>B</strong>: Logo merek dagang korporasi merupakan hak kekayaan industri swasta.</li><li><strong>C</strong>: Pameran otomotif merupakan agenda promosi perniagaan komersial.</li><li><strong>D</strong>: Kepanitiaan lelang mebel merupakan panitia ad hoc pengadaan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kedua, • kinerja • perekonomian • nasional • yang • menggambarkan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kapasitas mobilisasi penerimaan perpajakan terhadap skala perekonomian nasional secara umum diukur melalui indikator...", "options": ["Rasio penerimaan pajak terhadap Produk Domestik Bruto (tax ratio)", "Rasio laba kotor bank terhadap modal dasar pendirian", "Rasio jumlah pegawai negeri terhadap penduduk usia produktif", "Rasio luas gedung kantor dinas terhadap volume arsip berkas", "Rasio simpanan deposito valuta asing terhadap uang kartal"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengukuran Tax Ratio terhadap PDB</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerimaan pajak meningkat dengan berkembangnya ekonomi dan <strong>sering diukur dengan rasio penerimaan pajak terhadap perekonomian yang diukur dengan Produk Domestik Bruto (PDB)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena rasio pajak terhadap PDB menunjukkan efektivitas sistem perpajakan dalam memungut pendapatan dari kue ekonomi nasional. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Rasio penerimaan pajak terhadap Produk Domestik Bruto (tax ratio)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Rasio laba bank adalah indikator return on equity korporasi perbankan.</li><li><strong>C</strong>: Rasio aparatur terhadap penduduk adalah analisis beban kepegawaian.</li><li><strong>D</strong>: Rasio gedung kantor adalah tata kelola manajemen aset fisik dinas.</li><li><strong>E</strong>: Rasio simpanan valas adalah statistik likuiditas moneter perbankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Penerimaan pajak suatu negara akan meningkat dengan berkembangnya • perekonomian dan sering diukur dengan rasio penerimaan pajak terhadap</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Faktor internal ketiga yang menentukan keberhasilan alokasi APBN berhubungan dengan kualitas birokrasi dan...", "options": ["Tingkat suku bunga pinjaman kartu kredit perbankan swasta", "Kemampuan perencanaan, pengelolaan, dan pengendalian belanja negara", "Besaran dividen yang dibagikan oleh bursa efek komersial", "Harga sewa gerai pertokoan di pusat perbelanjaan ibu kota", "Komisi penjualan perumahan mewah oleh agen properti privat"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Faktor Internal Ketiga: Tata Kelola Belanja dan Birokrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Faktor internal ketiga adalah <strong>kemampuan perencanaan, pengelolaan, dan pengendalian belanja negara yang berkaitan dengan politik anggaran dan kualitas birokrasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena birokrasi yang kompeten dan berintegritas menjamin belanja negara terencana baik dan bebas dari pemborosan anggaran. Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Kemampuan perencanaan, pengelolaan, dan pengendalian belanja negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bunga kartu kredit diatur oleh regulasi perlindungan konsumen BI.</li><li><strong>C</strong>: Dividen bursa efek dibagikan kepada pemegang saham emiten bursa.</li><li><strong>D</strong>: Harga sewa gerai mal ditentukan oleh mekanisme bisnis properti.</li><li><strong>E</strong>: Komisi agen properti merupakan komisi perniagaan jasa perantara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Ketiga, kemampuan perencanaan, pengelolaan, dan pengendalian belanja • negara. Hal ini berkaitan dengan politik anggaran dan masalah serta kualitas</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pilihan politik anggaran yang diambil oleh pemerintah bersama legislatif membawa konsekuensi langsung berupa...", "options": ["Penyeragaman seluruh tarif penerimaan pajak daerah tanpa regulasi", "Penghapusan sistem pembukuan akuntansi perbendaharaan negara", "Perbedaan pola dan struktur alokasi belanja antar-sektor pembangunan", "Pemberian dana kasir gratis bagi para pemodal asing di bursa", "Kewajiban penutupan rekening kas umum negara di bank sentral"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Pilihan Politik Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Belanja negara yang dilandasi suatu pilihan politik tertentu akan menyebabkan pola dan alokasi anggaran yang berbeda-beda</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena orientasi politik yang memprioritaskan infrastruktur, pendidikan, atau pertahanan akan tercermin pada pergeseran porsi pagu anggaran masing-masing kementerian. Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Perbedaan pola dan struktur alokasi belanja antar-sektor pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak daerah diatur melalui peraturan daerah berdasarkan undang-undang.</li><li><strong>B</strong>: Standar akuntansi pemerintahan wajib diterapkan tanpa terkecuali.</li><li><strong>D</strong>: Dana kasir negara dilarang dibagikan secara gratis kepada pemodal asing.</li><li><strong>E</strong>: Rekening kas umum negara wajib ditempatkan di bank sentral.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Belanja negara yang dilandasi suatu pilihan politik tertentu akan • menyebabkan pola dan alokasi anggaran yang berbeda-beda.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C4 - Analisis", "question": "Meskipun nominal belanja negara meningkat setiap tahun, apabila dialokasikan pada prioritas yang tidak tepat, dampaknya adalah...", "options": ["Penurunan rasio utang publik terhadap produk domestik secara drastis", "Lonjakan surplus neraca pembayaran luar negeri yang berkelanjutan", "Peningkatan cadangan devisa pemerintah pada bank sentral mitra", "Sangat minim kontribusinya terhadap peningkatan kesejahteraan rakyat", "Kemandirian penuh pembiayaan modal koperasi usaha mikro daerah"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Distorsi Alokasi Belanja</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Apabila dialokasikan pada prioritas belanja yang tidak tepat, <strong>maka akan berdampak sangat kecil/minimal terhadap kesejahteraan rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena belanja non-produktif atau salah sasaran gagal menghasilkan multiplier effect ekonomi maupun peningkatan kualitas hidup publik. Oleh karena itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Sangat minim kontribusinya terhadap peningkatan kesejahteraan rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Belanja tidak tepat justru memperbesar beban utang tanpa hasil riil.</li><li><strong>B</strong>: Alokasi keliru tidak memberikan perbaikan neraca pembayaran luar negeri.</li><li><strong>C</strong>: Belanja salah sasaran menguras cadangan devisa untuk barang impor.</li><li><strong>E</strong>: Koperasi mikro membutuhkan program pemberdayaan belanja yang tepat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>proritas belanja yang tidak tepat misalnya untuk membiayai perang atau • kesejahteraan rakyat.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Manfaat belanja negara yang berukuran besar dapat tergerus dan tidak dirasakan masyarakat apabila birokrasi mengalami...", "options": ["Kepatuhan penuh pada standar pemeriksaan keuangan negara", "Peningkatan kompetensi aparatur melalui sertifikasi profesi", "Penerapan sistem akuntansi berbasis akrual secara konsisten", "Penyediaan layanan informasi publik secara terbuka dan jujur", "Praktik kebocoran anggaran serta ketidakefisienan operasional"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ancaman Kebocoran dan Inefisiensi Birokrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Belanja yang besar dapat <strong>digerogoti oleh birokrasi baik dalam bentuk kebocoran maupun ketidak-efisienan, sehingga tidak menghasilkan manfaat kesejahteraan bagi masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena inefisiensi anggaran dan kebocoran dana menghabiskan sumber daya kas negara tanpa menghasilkan keluaran fisik yang bermanfaat. Maka dari itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Praktik kebocoran anggaran serta ketidakefisienan operasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kepatuhan audit justru menjamin akuntabilitas pengeluaran kas.</li><li><strong>B</strong>: Sertifikasi profesi meningkatkan profesionalisme pengelolaan keuangan.</li><li><strong>C</strong>: Akuntansi akrual menyajikan posisi keuangan secara transparan.</li><li><strong>D</strong>: Keterbukaan informasi publik memperkuat pengawasan masyarakat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 24) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Belanja yang besar dapat juga digerogoti oleh birokrasi • baik dalam bentuk kebocoran maupun ketidak-efisienan,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Faktor internal keempat kebijakan fiskal berkaitan dengan kemampuan pembiayaan, di mana saat APBN mengalami surplus tantangannya adalah...", "options": ["Pengelolaan dan pemanfaatan surplus untuk antisipasi kebutuhan masa depan", "Pemberian bonus tunai langsung kepada seluruh komisaris bank swasta", "Penutupan seluruh rekening kas negara agar tidak dapat diaudit BPK", "Penyerahan kelebihan dana kepada lembaga pembiayaan asing", "Pembelian komoditas impor mewah untuk persediaan inventaris kantor"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Faktor Internal Keempat: Pengelolaan Pembiayaan Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bila negara memiliki APBN surplus, <strong>masalah pembiayaan adalah bagaimana dilakukan pengelolaan dan pemanfaatan surplus anggaran terutama untuk tujuan antisipasi kebutuhan negara di masa depan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena surplus fiskal harus diinvestasikan dalam dana abadi atau pengurangan utang guna memperkuat ketahanan fiskal jangka panjang. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Pengelolaan dan pemanfaatan surplus untuk antisipasi kebutuhan masa depan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Bonus komisaris bank swasta dibayar dari laba korporasi bank bersangkutan.</li><li><strong>C</strong>: Menutup rekening kas dari audit BPK merupakan pelanggaran hukum berat.</li><li><strong>D</strong>: Kelebihan kas negara adalah aset publik yang dikelola secara berdaulat.</li><li><strong>E</strong>: Barang mewah impor bukan peruntukan belanja barang inventaris negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Keempat, kemampuan pengelolaan pembiayaan anggaran. Bila suatu negara • memiliki APBN yang surplus, maka masalah pembiayaan adalah bagaimana</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Di negara-negara maju, antisipasi kebutuhan fiskal masa depan dikaitkan erat dengan pergeseran demografi penduduk yang ditandai oleh...", "options": ["Lonjakan angka kelahiran bayi yang menurunkan rasio ketergantungan", "Dominasi kelompok usia tua yang meningkatkan beban belanja kesehatan", "Penurunan usia harapan hidup akibat krisis lingkungan hidup", "Pemusatan angkatan kerja muda pada sektor industri tradisional", "Ketiadaan kebutuhan jaminan hari tua bagi para pekerja formal"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tantangan Fiskal Demografi Penuaan Penduduk</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebutuhan masa depan dikaitkan dengan <strong>kondisi demografi yang didominasi kelompok usia tua, sehingga memakan anggaran pelayanan kesehatan dan menjaga kesejahteraan hari tua yang semakin tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena fenomena penuaan populasi (aging population) menuntut kesiapan bantalan dana fiskal yang kokoh untuk membiayai pensiun dan medis jangka panjang. Oleh karena itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Dominasi kelompok usia tua yang meningkatkan beban belanja kesehatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Negara maju justru menghadapi tantangan penurunan angka kelahiran.</li><li><strong>C</strong>: Usia harapan hidup di negara maju cenderung semakin meningkat.</li><li><strong>D</strong>: Angkatan kerja negara maju bergeser ke sektor jasa teknologi tinggi.</li><li><strong>E</strong>: Jaminan hari tua merupakan pilar utama jaring pengaman sosial warga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kebutuhan negara masa depan dikaitkan dengan kondisi demografi (penduduk) • yang semakin didominasi oleh kelompok usia tua, sehingga diperkirakan akan</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Ketika APBN mengalami defisit, tantangan pembiayaan terbesar bagi otoritas pengelola perbendaharaan negara adalah...", "options": ["Meminjam dana komersial berbunga tinggi dari rentenir internasional", "Menghentikan pembayaran hak gaji aparatur sipil kementerian", "Mendapatkan pembiayaan berisiko kecil, jatuh tempo panjang, dan bunga rendah", "Menjual seluruh pulau terluar negara kepada pengusaha swasta", "Meniadakan penerimaan setoran pajak dari sektor badan usaha"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tantangan Pembiayaan saat APBN Defisit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tantangan terbesar defisit adalah <strong>bagaimana mendapatkan pembiayaan anggaran yang paling beresiko kecil termasuk jatuh tempo utang yang berdurasi panjang dan berbiaya (beban bunga utang) rendah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena strategi pembiayaan utang wajib memprioritaskan biaya modal terendah dengan profil jatuh tempo aman guna menghindari gagal bayar. Maka dari itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Mendapatkan pembiayaan berisiko kecil, jatuh tempo panjang, dan bunga rendah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pinjaman bunga tinggi justru membebani APBN dengan beban utang berat.</li><li><strong>B</strong>: Gaji aparatur merupakan hak kedinasan yang dijamin penganggarannya.</li><li><strong>D</strong>: Pulau terluar adalah wilayah kedaulatan NKRI yang tidak dapat diperjualbelikan.</li><li><strong>E</strong>: Setoran pajak badan usaha merupakan sumber utama pendapatan kas APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sedang bila negara mengalami APBN defisit, maka • tantangan terbesar adalah bagaimana mendapatkan pembiayaan anggaran</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Akses pemerintah dalam memperoleh pembiayaan utang di pasar domestik maupun internasional sangat ditentukan oleh faktor...", "options": ["Jumlah simpanan tabungan pribadi pejabat perbendaharaan di bank", "Luas bangunan gedung kedutaan besar negara di kawasan sahabat", "Pilihan warna sampul dokumen nota keuangan rancangan undang-undang", "Perkembangan pasar surat berharga, peringkat kredit, dan tingkat risiko negara", "Susunan acara jamuan malam diplomatik para menteri kabinet"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akses Pasar Pembiayaan Domestik dan Internasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Akses pembiayaan ditentukan oleh <strong>tingkat perkembangan pasar surat berharga (obligasi), peringkat (rating), dan tingkat resiko dari negara tersebut</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena investor global dan domestik mendasarkan keputusan pembelian obligasi pada rating kelayakan kredit dan persepsi stabilitas makroekonomi negara. Oleh sebab itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Perkembangan pasar surat berharga, peringkat kredit, dan tingkat risiko negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tabungan pribadi pejabat bukan kriteria kelayakan pembiayaan negara.</li><li><strong>B</strong>: Gedung kedutaan besar adalah fasilitas fisik perwakilan diplomatik.</li><li><strong>C</strong>: Warna sampul buku nota keuangan adalah desain grafis tata cetak.</li><li><strong>E</strong>: Jamuan malam diplomatik merupakan kegiatan hubungan internasional dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>pembiayaan (utang) baik dari berasal dari dalam negeri (pasar obligasi/surat • utang domestik) maupun internasional. Akses tersebut ditentukan oleh tingkat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kondisi utang tinggi yang disertai ketidakstabilan politik dan birokrasi buruk dapat menyebabkan peringkat utang jatuh ke status non-investment grade, dengan akibat...", "options": ["Penurunan otomatis suku bunga pinjaman luar negeri pemerintah", "Pemberian dana hibah secara cuma-cuma oleh investor internasional", "Kenaikan cadangan kas negara dari penerimaan dividen perbankan", "Penghapusan seluruh beban bunga surat berharga negara", "Beban biaya utang meningkat atau akses pembiayaan makin sulit"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Peringkat Non-Investment Grade</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Peringkat utang yang buruk (non-investment grade) berakibat <strong>pada akses untuk mendapat pembiayaan menjadi sulit atau sangat mahal, sehingga makin mempersulit prospek perbaikan ekonomi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena investor menuntut premi risiko (yield) yang jauh lebih tinggi atau menolak mendanai obligasi dari negara yang dinilai berisiko gagal bayar. Oleh karena itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Beban biaya utang meningkat atau akses pembiayaan makin sulit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Suku bunga pinjaman justru melonjak tinggi seiring naiknya premi risiko.</li><li><strong>B</strong>: Investor komersial tidak memberikan dana hibah cuma-cuma.</li><li><strong>C</strong>: Status non-investment grade tidak menaikkan cadangan devisa kas negara.</li><li><strong>D</strong>: Beban bunga surat berharga tetap wajib dibayar sesuai nilai kupon.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>peringkat utang negara ini menjadi buruk (non-investment grade), dan berakibat • pada akses untuk mendapat pembiayaan menjadi sulit atau sangat mahal,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Faktor internal kelima yang berdampak drastis terhadap postur APBN mencakup faktor-faktor non-ekonomi, antara lain berupa...", "options": ["Bencana alam, perubahan iklim, gejolak sosial politik, dan gangguan keamanan", "Penurunan suku bunga simpanan tabungan berjangka bank swasta", "Peningkatan volume transaksi efek derivatif pada bursa saham", "Perubahan susunan dewan penasihat asosiasi pedagang retail", "Pemberlakuan jam operasional baru pada pusat perbelanjaan kota"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Faktor Internal Kelima: Faktor Non-Ekonomi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Faktor nonekonomi meliputi <strong>bencana alam, perubahan iklim, gejolak politik atau sosial, gangguan keamanan dan terorisme, serta terjadinya perang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena peristiwa kedaruratan luar biasa tersebut melumpuhkan aktivitas produktif warga dan menuntut respons tanggap darurat negara secara cepat. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Bencana alam, perubahan iklim, gejolak sosial politik, dan gangguan keamanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Suku bunga simpanan perbankan merupakan variabel sektor keuangan ekonomi.</li><li><strong>C</strong>: Transaksi efek derivatif merupakan kegiatan perdagangan di pasar modal.</li><li><strong>D</strong>: Dewan penasihat asosiasi retail adalah organisasi perhimpunan bisnis.</li><li><strong>E</strong>: Jam operasional pusat perbelanjaan adalah kebijakan tata kelola niaga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kelima, faktor-faktor nonekonomi seperti terjadinya bencana alam, perubahan • iklim, gejolak politik atau sosial, gangguan keamanan dan terorisme, serta</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Terjadinya bencana alam atau krisis non-ekonomi menimbulkan fenomena 'beban ganda' pada APBN karena pada saat bersamaan...", "options": ["Penerimaan pajak melonjak drastis dan alokasi bantuan sosial dihapuskan", "Pendapatan negara anjlok sedangkan kebutuhan belanja melonjak tajam", "Seluruh utang luar negeri dilunasi dan kas negara mengalami surplus", "Masyarakat mendanai pemulihan tanpa bantuan dinas APBN", "Seluruh kementerian menghentikan operasional penyerapan anggaran"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Beban Ganda (Double Burden) APBN saat Bencana</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Faktor nonekonomi menyebabkan pendapatan turun karena ekonomi terganggu, sekaligus belanja melonjak untuk penanganan, <strong>sehingga APBN mengalami beban ganda</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena erosi basis pajak akibat terhentinya produksi bertemu dengan lonjakan kebutuhan darurat penyelamatan korban dan rehabilitasi fisik. Oleh sebab itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Pendapatan negara anjlok sedangkan kebutuhan belanja melonjak tajam</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Saat bencana, aktivitas ekonomi terhenti sehingga pajak otomatis merosot.</li><li><strong>C</strong>: Kondisi darurat justru memperlebar defisit pembiayaan negara.</li><li><strong>D</strong>: Korban bencana membutuhkan penyaluran bantuan tanggap darurat negara.</li><li><strong>E</strong>: Kementerian justru dituntut mempercepat penyaluran anggaran darurat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>menyebabkan belanja negara melonjak untuk • penanganan masalah. Dengan demikian APBN akan mengalami beban ganda.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Tingkat risiko fiskal suatu negara dinilai oleh lembaga pemeringkat internasional berdasarkan kualitas APBN yang mencakup...", "options": ["Jumlah kamar hotel yang disewa untuk lokakarya kedinasan aparatur", "Pilihan merk kendaraan operasional yang digunakan para pimpinan lembaga", "Komposisi penerimaan, tingkat utang, profil jatuh tempo, dan beban kontinjensi", "Frekuensi pergantian staf tata usaha pada kantor pelayanan pajak", "Desain arsitektur gedung kantor perwakilan daerah kementerian"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Faktor Penentu Risiko Fiskal Suatu Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Risiko suatu negara ditentukan oleh <strong>kualitas APBN dari segi jumlah, komposisi penerimaan, tingkat utang dan jatuh tempo, serta pengelolaan resiko beban langsung maupun kontinjensi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena faktor-faktor tersebut memberikan gambaran utuh mengenai solvabilitas, likuiditas, dan ketahanan fiskal pemerintah dalam jangka panjang. Oleh karena itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Komposisi penerimaan, tingkat utang, profil jatuh tempo, dan beban kontinjensi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sewa hotel untuk lokakarya adalah belanja operasional dinas reguler.</li><li><strong>B</strong>: Merk mobil operasional dinas adalah spesifikasi pengadaan barang.</li><li><strong>D</strong>: Pergantian staf tata usaha merupakan rotasi kepegawaian internal.</li><li><strong>E</strong>: Arsitektur gedung dinas adalah elemen estetika fasilitas perkantoran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Risiko suatu negara ditentukan oleh seluruh kualitas • APBN, baik dari segi jumlah, komposisi penerimaan, tingkat utang dan jatuh</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Dalam manajemen risiko fiskal APBN, beban yang bersifat kemungkinan (kewajiban kontinjensi) merujuk pada kewajiban...", "options": ["Pasti yang telah terjadwal pembayarannya setiap awal bulan kalender", "Komersial milik badan usaha swasta yang bebas dari campur tangan publik", "Sukarela yang dibayarkan berdasarkan pertimbangan kebaikan pimpinan", "Potensial yang dapat menjadi beban riil negara jika terjadi peristiwa pemicu", "Rutin untuk mendanai pembelian alat tulis kantor kementerian"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengelolaan Kewajiban Kontinjensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Risiko fiskal mencakup beban langsung maupun yang <strong>bersifat kemungkinan (kontinjensi), seperti penjaminan proyek infrastruktur atau penyelamatan krisis perbankan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena kewajiban kontinjensi baru terealisasi membebani kas negara apabila risiko yang dijamin (misal gagal bayar proyek BUMN) benar-benar terjadi. Maka dari itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Potensial yang dapat menjadi beban riil negara jika terjadi peristiwa pemicu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kewajiban pasti bulanan merupakan belanja rutin langsung terikat.</li><li><strong>B</strong>: Kewajiban komersial murni swasta bukan tanggung jawab kontinjensi APBN.</li><li><strong>C</strong>: Pengeluaran negara wajib berdasarkan dasar hukum yang sah, bukan sukarela.</li><li><strong>E</strong>: Alat tulis kantor merupakan pos belanja barang operasional harian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>keseluruhan aspek pengelolaan resiko dan beban baik yang • langsung maupun yang bersifat kemungkinan (kontinjensi).</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C4 - Analisis", "question": "Kualitas birokrasi yang buruk dan belanja berlebihan tanpa kehati-hatian secara langsung merusak kredibilitas pembiayaan negara melalui...", "options": ["Penurunan tarif pajak bumi dan bangunan bagi kawasan industri komersial", "Peningkatan alokasi dana bantuan permodalan bagi usaha mikro daerah", "Pemberian pembebasan bea masuk bagi seluruh komoditas impor pangan", "Penghapusan kewajiban penyusunan dokumen nota keuangan tahunan", "Merosotnya peringkat utang dan melambungnya biaya bunga penerbitan obligasi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterkaitan Kualitas Birokrasi dan Biaya Utang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Birokrasi buruk dan belanja tidak hati-hati dipersepsikan memiliki risiko gagal kelola, <strong>sehingga peringkat utang buruk dan akses pembiayaan menjadi sangat mahal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pasar finansial menghukum tata kelola buruk dengan menuntut imbal hasil obligasi tinggi yang membebani alokasi belanja APBN berikutnya. Oleh sebab itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Merosotnya peringkat utang dan melambungnya biaya bunga penerbitan obligasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tarif pajak bumi dan bangunan ditetapkan berdasarkan undang-undang daerah.</li><li><strong>B</strong>: Pemberdayaan usaha mikro membutuhkan tata kelola anggaran yang sehat.</li><li><strong>C</strong>: Pembebasan bea masuk pangan merupakan kebijakan stabilisasi harga pangan.</li><li><strong>D</strong>: Penyusunan nota keuangan merupakan mandat konstitusional tahunan wajib.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 25) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>tidak mampu) mengelola anggaran dan utangnya. Dengan demikian • peringkat utang negara ini menjadi buruk (non-investment grade), dan berakibat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Selain faktor internal, perkembangan APBN Indonesia sangat dipengaruhi oleh faktor eksternal makro, yaitu...", "options": ["Ekonomi global, nilai tukar rupiah, harga minyak mentah, dan bunga global", "Biaya operasional sewa gudang logistik swasta di pelabuhan regional", "Tarif parkir kendaraan pengunjung di pusat perbelanjaan luar negeri", "Jadwal sidang etik perhimpunan profesi akuntansi negara tetangga", "Harga jual properti perumahan tapak milik warga di kawasan perkotaan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Faktor Eksternal Penentu Perkembangan APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Faktor eksternal meliputi <strong>perkembangan ekonomi global, nilai tukar rupiah, harga minyak mentah internasional (ICP), serta tingkat suku bunga internasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena keempat variabel asumsi dasar makro tersebut menentukan proyeksi penerimaan migas, pajak perdagangan internasional, dan beban pembayaran utang luar negeri. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Ekonomi global, nilai tukar rupiah, harga minyak mentah, dan bunga global</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sewa gudang swasta merupakan komponen biaya logistik komersial.</li><li><strong>C</strong>: Tarif parkir luar negeri adalah retribusi fasilitas daerah negara lain.</li><li><strong>D</strong>: Sidang etik profesi tetangga merupakan urusan organisasi profesi asing.</li><li><strong>E</strong>: Harga properti tapak adalah transaksi jual beli perumahan perdata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Faktor eksternal penting yang juga turut berdampak pada perkembangan APBN • Indonesia di antaranya meliputi perkembangan kondisi ekonomi global,</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Fluktuasi harga minyak mentah di pasar internasional berpengaruh langsung terhadap postur APBN Indonesia terutama pada pos...", "options": ["Penerimaan pajak kendaraan bermotor milik pemerintah kabupaten", "Pendapatan migas, belanja subsidi energi, dan kompensasi bahan bakar", "Belanja pegawai untuk pembayaran tunjangan kinerja aparatur dinas", "Pungutan retribusi pelayanan kebersihan pasar tradisional daerah", "Penerimaan bea meterai atas dokumen perjanjian perikatan perdata"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengaruh Harga Minyak Mentah (ICP)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pergerakan harga minyak mentah dunia mempengaruhi <strong>penerimaan negara bukan pajak sektor migas dan alokasi belanja subsidi energi serta kompensasi BBM</strong> dalam APBN.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena harga minyak dunia merupakan asumsi makro utama yang secara serentak menggerakkan sisi pendapatan migas dan pos belanja subsidi energi. Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Pendapatan migas, belanja subsidi energi, dan kompensasi bahan bakar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak kendaraan bermotor merupakan pendapatan asli daerah provinsi.</li><li><strong>C</strong>: Belanja pegawai aparatur dihitung berdasarkan formasi dan regulasi gaji.</li><li><strong>D</strong>: Retribusi kebersihan merupakan penerimaan asli daerah pemerintah daerah.</li><li><strong>E</strong>: Bea meterai dikenakan atas dokumen perdata sesuai undang-undang meterai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>harga minyak mentah di pasar internasional, serta tingkat bunga • internasional.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Isi, komposisi, dan nilai besaran dalam dokumen APBN pada hakikatnya mencerminkan secara langsung...", "options": ["Daftar kekayaan pribadi para pejabat di lingkungan kementerian", "Keuntungan bersih seluruh pelaku bisnis perhotelan komersial", "Kondisi dan keadaan struktur perekonomian suatu negara", "Jumlah emas batangan yang disimpan di lemari brankas perorangan", "Volume peredaran uang elektronik pada aplikasi dompet digital"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • APBN sebagai Cerminan Kondisi Ekonomi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perkembangan APBN <strong>mencerminkan secara langsung kondisi dan keadaan ekonomi suatu negara</strong>, termasuk struktur dan kemajuannya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena postur APBN memperlihatkan kemampuan produksi riil, daya beli rakyat, serta prioritas pembangunan yang sedang dijalankan oleh bangsa tersebut. Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Kondisi dan keadaan struktur perekonomian suatu negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kekayaan pribadi pejabat dilaporkan terpisah melalui dokumen LHKPN.</li><li><strong>B</strong>: Keuntungan bisnis perhotelan merupakan hasil usaha sektor privat.</li><li><strong>D</strong>: Emas simpanan perorangan adalah aset kekayaan privat masyarakat.</li><li><strong>E</strong>: Dompet digital merupakan instrumen sistem pembayaran uang elektronik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>APBN sebenarnya mencerminkan secara langsung kondisi dan keadaan • ekonomi suatu negara.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Selain kondisi perekonomian, alokasi pos pengeluaran dalam APBN juga mencerminkan jenis dan kualitas dari...", "options": ["Sistem perikatan perdata antara bank swasta dan debitur", "Tarif premi perlindungan asuransi barang ekspor manufaktur", "Strategi promosi pemasaran produk industri elektronik swasta", "Kebijakan publik yang diambil pemerintah dalam melayani warganya", "Perjanjian persekutuan modal usaha para pedagang komoditas"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • APBN sebagai Cerminan Kebijakan Publik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>APBN mencerminkan jenis dan kualitas kebijakan publik yang diambil oleh suatu negara</strong> yang membawa konsekuensi pada keuangan negara, seperti subsidi, pendidikan, dan pertahanan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena komitmen nyata pemerintah terhadap sektor pendidikan, kesehatan, dan perlindungan warga diwujudkan melalui alokasi porsi anggaran riil dalam APBN. Oleh karena itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Kebijakan publik yang diambil pemerintah dalam melayani warganya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perikatan perdata bank dan debitur merupakan hubungan hukum privat.</li><li><strong>B</strong>: Premi asuransi barang ekspor adalah biaya risiko perdagangan swasta.</li><li><strong>C</strong>: Promosi pemasaran adalah kegiatan operasional manajemen korporasi.</li><li><strong>E</strong>: Persekutuan modal pedagang merupakan entitas usaha ekonomi bersama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>APBN juga mencerminkan jenis dan kualitas kebijakan • publik yang diambil oleh suatu negara baik di bidang ekonomi maupun di bidang</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Struktur penerimaan dan efektivitas belanja dalam APBN menggambarkan secara langsung kualitas dari institusi publik, yakni...", "options": ["Peringkat popularitas artis di media penyiaran publik", "Volume perdagangan saham harian di bursa efek komersial", "Kapasitas penumpang moda transportasi darat milik swasta", "Jumlah penghargaan yang diraih yayasan filantropi perdata", "Integritas dan kompetensi birokrasi pemerintahan suatu negara"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • APBN sebagai Cerminan Kualitas Birokrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>APBN juga menggambarkan secara langsung kualitas institusi publik yaitu birokrasi suatu negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kemampuan memungut penerimaan sesuai potensi dan membelanjakannya secara efisien merupakan cermin kapasitas dan moral birokrasi negara. Maka dari itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Integritas dan kompetensi birokrasi pemerintahan suatu negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Popularitas media penyiaran adalah ranah industri hiburan komersial.</li><li><strong>B</strong>: Volume perdagangan saham bursa adalah indikator pasar modal privat.</li><li><strong>C</strong>: Kapasitas moda transportasi swasta adalah kapasitas operasional angkutan.</li><li><strong>D</strong>: Penghargaan yayasan filantropi merupakan capaian lembaga nirlaba.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>APBN juga menggambarkan secara langsung kualitas institusi publik yaitu • birokrasi suatu negara.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C4 - Analisis", "question": "Suatu negara yang kegiatan ekonominya maju namun penerimaan pajaknya sangat kecil menunjukkan adanya masalah berupa...", "options": ["Keterbatasan kemampuan dan kapasitas aparat perpajakan dalam memungut", "Perekonomian warga yang telah mencapai kemakmuran tanpa perlu pajak", "Keberhasilan penuh sistem pengelolaan perbendaharaan tanpa pegawai", "Penghapusan seluruh beban kewajiban anggaran dinas kementerian", "Kelebihan saldo kas negara yang melimpah pada bank sentral mitra"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Anomali Potensi Ekonomi dan Penerimaan Pajak</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bila ekonomi terlihat maju namun penerimaan pajak sangat kecil, <strong>artinya kemampuan aparat dan institusi pajak dalam memungut sangat terbatas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena disparitas lebar antara basis ekonomi potensial dan realisasi pajak mengindikasikan rendahnya kepatuhan serta kelemahan penegakan hukum institusi pajak. Oleh sebab itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Keterbatasan kemampuan dan kapasitas aparat perpajakan dalam memungut</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Negara maju tetap memerlukan penerimaan pajak untuk pelayanan publik.</li><li><strong>C</strong>: Sistem perbendaharaan memerlukan aparatur pengelola yang kompeten.</li><li><strong>D</strong>: Beban kewajiban anggaran tetap berjalan mendanai fungsi pemerintahan.</li><li><strong>E</strong>: Penerimaan pajak rendah justru mengancam ketersediaan saldo kas negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>kemampuan aparat dan institusi • pajak dalam memungut sangat terbatas.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Indikator utama yang lazim digunakan untuk menelaah perkembangan dinamika postur APBN dari tahun ke tahun adalah...", "options": ["Jumlah lembar cek fisik yang dicetak oleh kasir perbankan", "Posisi saldo defisit atau surplus anggaran negara", "Daftar nama pemegang saham bank pembangunan daerah", "Banyaknya kendaraan dinas yang dilelang pada akhir tahun", "Waktu pelaksanaan rapat koordinasi perencanaan kementerian"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Indikator Defisit dan Surplus Anggaran</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Salah satu indikator untuk melihat perkembangan APBN dari tahun ke tahun adalah dari segi defisit/surplus anggaran</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena neraca defisit atau surplus memperlihatkan apakah pendapatan pemerintah mencukupi belanja atau memerlukan instrumen pembiayaan utang. Oleh karena itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Posisi saldo defisit atau surplus anggaran negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jumlah lembar cek fisik merupakan instrumen lalu lintas bayar teknis.</li><li><strong>C</strong>: Nama pemegang saham BPD merupakan kepemilikan saham korporasi pemda.</li><li><strong>D</strong>: Lelang kendaraan dinas adalah kegiatan pemindahtanganan BMN.</li><li><strong>E</strong>: Jadwal rapat kementerian merupakan agenda rapat kerja internal dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Salah satu indikator untuk melihat perkembangan APBN dari tahun ke tahun • adalah dari segi defisit/surplus anggaran.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Meskipun pada masa Orde Baru APBN dinyatakan menganut prinsip berimbang, berdasarkan analisis praktik internasional yang lazim sebenarnya APBN...", "options": ["Mengalami surplus devisa melimpah yang disimpan di bank luar negeri", "Bebas dari komponen pos pengeluaran pembangunan proyek", "Mengalami kondisi defisit karena memasukkan pinjaman sebagai penerimaan", "Dikelola sepenuhnya tanpa melibatkan persetujuan dewan legislatif", "Bebas dari segala bentuk pinjaman utang luar negeri bilateral"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Analisis Realitas Anggaran Orde Baru</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Meskipun dinyatakan berimbang, menurut praktik internasional (international best practices), <strong>pada sebagian besar periode Pemerintahan Orde Baru APBN sebenarnya mengalami defisit</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pada masa itu penerimaan pembangunan (pinjaman utang luar negeri) dicatat sebagai pos pendapatan, padahal secara standar akuntansi merupakan utang pembiayaan defisit. Maka dari itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Mengalami kondisi defisit karena memasukkan pinjaman sebagai penerimaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kondisi fiskal Orde Baru ditopang oleh utang luar negeri yang signifikan.</li><li><strong>B</strong>: Anggaran pembangunan justru menjadi pos belanja fisik yang sangat besar.</li><li><strong>D</strong>: APBN Orde Baru tetap disahkan melalui undang-undang bersama DPR.</li><li><strong>E</strong>: Pinjaman luar negeri merupakan penopang utama neraca anggaran Orde Baru.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>selama masa orde baru APBN Indonesia selalu dinyatakan dalam kondisi • berimbang, namun menurut praktik yang lazim digunakan dalam analisis</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Defisit anggaran tertinggi dalam sejarah APBN Indonesia terjadi pada tahun 2020 akibat pandemi Covid-19, dengan besaran...", "options": ["Tercatat sekitar 15% terhadap Produk Domestik Bruto (PDB)", "Tepat sebesar 0,5% terhadap Produk Domestik Bruto (PDB)", "Tercatat sekitar 25% terhadap Produk Domestik Bruto (PDB)", "Tercatat sekitar 6% terhadap Produk Domestik Bruto (PDB)", "Tepat sebesar 1% terhadap Produk Domestik Bruto (PDB)"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rekor Defisit Tertinggi APBN Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Defisit tertinggi terjadi pada tahun 2020 yaitu tahun pertama terjadinya pandemi COVID-19, <strong>defisit anggaran tercatat sekitar 6% terhadap PDB</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pelebaran defisit hingga sekitar 6% PDB dilakukan sebagai respon luar biasa penanganan darurat kesehatan dan penyelamatan ekonomi nasional. Oleh sebab itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Tercatat sekitar 6% terhadap Produk Domestik Bruto (PDB)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Besaran defisit 15% PDB tidak pernah terjadi dalam sejarah APBN Indonesia.</li><li><strong>B</strong>: Defisit 0,5% adalah rasio defisit sangat rendah dalam kondisi normal.</li><li><strong>C</strong>: Defisit 25% PDB merupakan rasio krisis ekstrim yang tidak terjadi.</li><li><strong>E</strong>: Defisit 1% adalah rasio defisit sangat terkendali pada masa tenang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Defisit tertinggi terjadi pada tahun 2020 • yaitu tahun pertama terjadinya pandemi COVID-19, defisit anggaran tercatat</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Catatan historis menunjukkan bahwa defisit anggaran terendah sepanjang sejarah APBN Indonesia terjadi pada tahun 1978/1979 sebesar...", "options": ["Tepat sebesar 3,00% terhadap Produk Domestik Bruto (PDB)", "Sekitar 2,50% terhadap Produk Domestik Bruto (PDB)", "Tepat sebesar 5,00% terhadap Produk Domestik Bruto (PDB)", "Sekitar 1,20% terhadap Produk Domestik Bruto (PDB)", "Sekitar 0,09% terhadap Produk Domestik Bruto (PDB)"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Rekor Defisit Terendah APBN Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Defisit terendah terjadi pada tahun 1978/1979 sekitar 0,09% terhadap PDB</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pada tahun anggaran 1978/1979 penerimaan negara didorong oleh lonjakan penerimaan minyak bumi (oil boom) sehingga mendekati posisi seimbang sempurna. Oleh karena itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Sekitar 0,09% terhadap Produk Domestik Bruto (PDB)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Angka 3,00% adalah batas maksimal defisit legal dalam UU 17/2003.</li><li><strong>B</strong>: Angka 2,50% merupakan defisit moderat dalam kondisi ekonomi stabil.</li><li><strong>C</strong>: Angka 5,00% adalah defisit ekspansif masa krisis pandemi 2021.</li><li><strong>D</strong>: Angka 1,20% merupakan estimasi defisit pada masa konsolidasi fiskal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 26) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sedangkan defisit terendah terjadi pada tahun • 1978/1979 sekitar 0,09% terhadap PDB.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pada kurun waktu lima tahun sebelum terjadinya krisis moneter 1998, APBN Indonesia sempat mencatatkan surplus rata-rata sebesar...", "options": ["2,0% terhadap Produk Domestik Bruto (PDB)", "8,5% terhadap Produk Domestik Bruto (PDB)", "12,0% terhadap Produk Domestik Bruto (PDB)", "0,1% terhadap Produk Domestik Bruto (PDB)", "5,5% terhadap Produk Domestik Bruto (PDB)"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Surplus APBN Sebelum Krisis 1998</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada kurun waktu lima tahun sebelum krisis 1998, <strong>APBN mengalami surplus rata-rata 2,0% terhadap PDB</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena penerimaan negara sebelum 1998 mengalami ekspansi kuat dari aktivitas ekonomi dan pendapatan migas sebelum guncangan nilai tukar terjadi. Maka dari itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. 2,0% terhadap Produk Domestik Bruto (PDB)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Surplus 8,5% PDB terlalu tinggi dan tidak pernah tercatat di Indonesia.</li><li><strong>C</strong>: Surplus 12% PDB adalah angka ekstrem yang melampaui data historis.</li><li><strong>D</strong>: Surplus 0,1% mendekati seimbang, bukan rata-rata lima tahun pra-krisis.</li><li><strong>E</strong>: Surplus 5,5% PDB bukan angka rata-rata resmi periode tersebut.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Pada kurun waktu lima tahun sebelum krisis, APBN mengalami surplus rata-rata • 2,0% terhadap PDB.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Saat krisis moneter 1998 melanda, APBN kembali berbalik menjadi defisit besar terutama akibat menanggung beban biaya...", "options": ["Pembelian gedung kantor kedutaan besar baru di luar negeri", "Pemulihan dan penyehatan perbankan serta stimulus pemulihan ekonomi", "Pemberian dana bonus tahunan bagi komisaris korporasi asing", "Penyelenggaraan pameran produk kerajinan di negara sahabat", "Pengadaan seragam dinas baru untuk seluruh pegawai aparatur"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyebab Defisit APBN saat Krisis Moneter</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>APBN kembali defisit saat krisis karena <strong>menanggung beban sangat berat biaya pemulihan dan penyehatan sektor perbankan dan stimulus fiskal pemulihan ekonomi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena program rekapitalisasi perbankan dan bantuan likuiditas bank (BLBI) menyedot pembiayaan obligasi negara dalam jumlah sangat besar. Oleh sebab itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Pemulihan dan penyehatan perbankan serta stimulus pemulihan ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembelian gedung kedutaan bukan pos pengeluaran darurat krisis.</li><li><strong>C</strong>: APBN tidak mengalokasikan bonus komisaris korporasi asing.</li><li><strong>D</strong>: Pameran kerajinan merupakan kegiatan promosi dagang reguler.</li><li><strong>E</strong>: Pengadaan seragam dinas adalah belanja barang aparatur rutin.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>beban sangat berat biaya pemulihan dan • penyehatan sektor perbankan dan untuk menciptakan stimulus fiskal dalam</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan ketentuan baku Undang-Undang Nomor 17 Tahun 2003, batas maksimal defisit APBN terhadap PDB ditetapkan sebesar...", "options": ["Maksimal sebesar 7% terhadap PDB", "Maksimal sebesar 10% terhadap PDB", "Maksimal sebesar 3% terhadap PDB", "Tepat sebesar 1% terhadap PDB", "Bebas tanpa pembatasan persentase"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batas Maksimal Defisit UU 17/2003</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai UU Nomor 17 Tahun 2003 tentang Keuangan Negara, <strong>batas maksimal defisit APBN terhadap Produk Domestik Bruto (PDB) ditetapkan sebesar 3%</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena ambang batas 3% PDB merupakan aturan disiplin fiskal (fiscal rule) yang wajib dipatuhi guna mencegah ledakan utang publik yang tidak berkelanjutan. Oleh karena itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Maksimal sebesar 3% terhadap PDB</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Batas 7% melampaui ketentuan undang-undang keuangan negara.</li><li><strong>B</strong>: Batas 10% adalah angka defisit sangat tinggi yang dilarang regulasi.</li><li><strong>D</strong>: Batas 1% terlalu sempit dan bukan bunyi ketentuan pasal UU 17/2003.</li><li><strong>E</strong>: Keuangan negara menganut aturan batas numerik yang mengikat secara hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Sesuai dengan Undang-Undang Nomor 17 Tahun 2003 tentang Keuangan • Negara, batas maksimal defisit APBN terhadap Produk Domestik Bruto (PDB)</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pelonggaran batas defisit APBN melampaui 3% selama masa krisis pandemi Covid-19 diberikan payung hukum resmi melalui...", "options": ["Surat Edaran Direksi Bank Sentral Bidang Pengawasan", "Keputusan Bersama Dewan Penasihat Perbankan Swasta", "Instruksi Lisan Pejabat Eselon Badan Pemeriksa Keuangan", "Perppu Nomor 1 Tahun 2020 tentang Kebijakan Keuangan Negara", "Maklumat Perhimpunan Pedagang Pasar Modal Nasional"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Landasan Hukum Relaksasi Defisit Masa Pandemi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah menerbitkan <strong>Perppu Nomor 1 Tahun 2020 tentang Kebijakan Keuangan Negara dan Stabilitas Sistem Keuangan untuk Penanganan Pandemi Covid-19</strong> yang merelaksasi batas defisit 3%.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Perppu tersebut (yang disahkan menjadi UU No. 2 Tahun 2020) memberikan kepastian hukum bagi pemerintah memperlebar defisit demi keselamatan rakyat saat darurat. Maka dari itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Perppu Nomor 1 Tahun 2020 tentang Kebijakan Keuangan Negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Surat edaran bank sentral mengatur ketentuan operasional perbankan.</li><li><strong>B</strong>: Dewan penasihat bank swasta bukan pembentuk peraturan perundangan negara.</li><li><strong>C</strong>: Instruksi lisan tidak sah sebagai dasar hukum pelonggaran undang-undang.</li><li><strong>E</strong>: Maklumat perhimpunan pasar modal bukan produk hukum kenegaraan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Peraturan Pemerintah Pengganti Undang-Undang (Perppu) Nomor 1 Tahun 2020 • tentang Kebijakan Keuangan Negara dan Stabilitas Sistem Keuangan untuk</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Kebijakan fiskal ekspansif yang dijalankan pemerintah pada periode 2020–2022 secara khusus difokuskan pada agenda...", "options": ["Menghapuskan pungutan pajak penghasilan bagi konglomerat multinasional", "Menghentikan seluruh pembiayaan pendidikan dan riset perguruan tinggi", "Membeli saham spekulatif pada bursa perdagangan internasional", "Menyeragamkan struktur kepemilikan modal badan usaha swasta", "Menjaga daya beli masyarakat, mendukung kesehatan, dan pemulihan ekonomi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Kebijakan Fiskal Ekspansif 2020-2022</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan fiskal ekspansif 2020–2022 difokuskan <strong>untuk menjaga daya beli masyarakat, mendukung sektor kesehatan, serta mempercepat pemulihan ekonomi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena ketiga prioritas tersebut merupakan penanganan darurat terpadu yang menyelamatkan jiwa dan mencegah krisis multidimensi. Oleh sebab itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Menjaga daya beli masyarakat, mendukung kesehatan, dan pemulihan ekonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak konglomerat tetap dipungut sesuai koridor undang-undang perpajakan.</li><li><strong>B</strong>: Anggaran pendidikan tetap dialokasikan minimal 20% sesuai UUD 1945.</li><li><strong>C</strong>: Investasi saham spekulatif dilarang dalam pengelolaan kas negara.</li><li><strong>D</strong>: Kepemilikan modal swasta diatur dalam ketentuan hukum perseroan perdata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Kebijakan fiskal yang ekspansif pada periode 2020–2022 tersebut difokuskan • untuk menjaga daya beli masyarakat, mendukung sektor kesehatan, serta</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Pada tahun 2023, keberhasilan tata kelola fiskal Indonesia ditandai oleh pencapaian komitmen konsolidasi fiskal berupa...", "options": ["Penurunan kembali defisit APBN ke bawah 3% terhadap PDB", "Penghapusan seluruh utang pokok pemerintah luar negeri", "Penarikan seluruh bantuan jaring pengaman sosial warga", "Pemberian dana hibah cuma-cuma kepada negara tetangga", "Kewajiban penyerahan dividen bursa efek kepada kasir dinas"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsolidasi Fiskal di Bawah 3% Tahun 2023</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Memasuki tahun 2023, pemerintah berhasil <strong>menurunkan kembali defisit ke bawah 3% sejalan dengan komitmen konsolidasi fiskal dan pemulihan ekonomi</strong> (tercatat 1,61%).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Indonesia berhasil mengembalikan disiplin defisit di bawah 3% setahun lebih cepat dari jadwal yang diamanatkan perundangan. Oleh karena itu, opsi A benar.</p><p>Jawaban tepat: <strong>A. Penurunan kembali defisit APBN ke bawah 3% terhadap PDB</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Utang pokok pemerintah tetap dikelola dan dilunasi sesuai jadwal jatuh tempo.</li><li><strong>C</strong>: Bantuan sosial tetap dipertahankan bagi masyarakat berpenghasilan rendah.</li><li><strong>D</strong>: APBN tidak memberikan hibah cuma-cuma yang menguras kas negara.</li><li><strong>E</strong>: Dividen bursa efek adalah hak para pemegang saham emiten terkait.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Memasuki tahun 2023, pemerintah berhasil • menurunkan kembali defisit ke bawah 3% sejalan dengan komitmen konsolidasi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Arah kebijakan fiskal dalam outlook APBN 2024 dirancang dengan karakteristik yang...", "options": ["Sangat ekspansif tanpa batas defisit hingga melampaui 10% PDB", "Tetap berhati-hati (prudent) namun produktif dengan proyeksi defisit 2,29%", "Mewajibkan kondisi surplus mutlak tanpa adanya belanja modal baru", "Menghentikan seluruh program subsidi bahan bakar bagi masyarakat", "Mengalihkan kewenangan perbendaharaan kepada badan usaha swasta"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Arah Kebijakan Fiskal Outlook APBN 2024</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada tahun 2024, outlook APBN menunjukkan arah kebijakan fiskal yang <strong>tetap prudent namun produktif, dengan defisit yang diproyeksikan sekitar 2,29% terhadap PDB</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena perpaduan prudent dan produktif menjaga keberlanjutan fiskal sembari tetap mendanai proyek strategis nasional dan bansos. Maka dari itu, opsi B benar.</p><p>Jawaban tepat: <strong>B. Tetap berhati-hati (prudent) namun produktif dengan proyeksi defisit 2,29%</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Defisit ekspansif 10% melanggar koridor disiplin fiskal UU 17/2003.</li><li><strong>C</strong>: Surplus mutlak tanpa belanja modal akan melumpuhkan pembangunan.</li><li><strong>D</strong>: Subsidi bahan bakar tetap dialokasikan untuk menjaga daya beli.</li><li><strong>E</strong>: Kewenangan perbendaharaan negara tetap pada Menteri Keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>APBN menunjukkan arah kebijakan fiskal yang tetap prudent namun produktif, • dengan defisit yang diproyeksikan sekitar 2,29% terhadap PDB.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Fokus kebijakan penerimaan negara pada perkembangan APBN mutakhir diarahkan pada optimalisasi melalui...", "options": ["Kenaikan tarif denda lalu lintas kendaraan secara sewenang-wenang", "Penarikan pungutan iuran wajib bagi seluruh anak usia sekolah", "Reformasi perpajakan, perluasan basis pajak, dan transformasi ekonomi hijau", "Penyitaan rekening simpanan nasabah perbankan komersial daerah", "Penjualan aset cagar budaya nasional kepada investor perorangan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Reformasi Penerimaan dan Transformasi 2024</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Fokus kebijakan diarahkan pada <strong>percepatan transformasi ekonomi hijau dan digital, serta optimalisasi penerimaan negara melalui reformasi perpajakan dan perluasan basis pajak</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena perluasan basis wajib pajak dan ekonomi hijau menciptakan penerimaan negara yang berkesinambungan dan ramah lingkungan. Oleh sebab itu, opsi C benar.</p><p>Jawaban tepat: <strong>C. Reformasi perpajakan, perluasan basis pajak, dan transformasi ekonomi hijau</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Denda lalu lintas dipungut berdasarkan putusan hukum penegakan tertib.</li><li><strong>B</strong>: Pendidikan dasar dijamin bebas biaya dalam program wajib belajar.</li><li><strong>D</strong>: Penyitaan rekening warga tanpa proses hukum melanggar hak asasi.</li><li><strong>E</strong>: Cagar budaya nasional dilindungi undang-undang dari komersialisasi privat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>transformasi • ekonomi hijau dan digital, serta optimalisasi penerimaan negara melalui • reformasi perpajakan dan perluasan basis pajak.</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Strategi pembiayaan APBN untuk menjaga sustainabilitas utang negara dilaksanakan dengan cara...", "options": ["Memperbanyak pinjaman valuta asing komersial berjangka sangat pendek", "Membatasi penerbitan surat berharga negara di bursa keuangan lokal", "Mengalihkan seluruh beban utang publik menjadi utang pemerintah daerah", "Pengelolaan portofolio secara hati-hati dan memperbesar porsi domestik", "Menutup kerja sama pembiayaan dengan lembaga keuangan multilateral"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Strategi Sustainabilitas Portofolio Utang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Di sisi pembiayaan, strategi diarahkan <strong>untuk menjaga sustainabilitas utang melalui pengelolaan portofolio utang yang hati-hati, memperbesar porsi pembiayaan dalam negeri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena memperbesar porsi pembiayaan domestik mengurangi kerentanan terhadap gejolak nilai tukar valuta asing dan pelarian modal. Oleh karena itu, opsi D benar.</p><p>Jawaban tepat: <strong>D. Pengelolaan portofolio secara hati-hati dan memperbesar porsi domestik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Utang valas jangka pendek sangat rentan memicu krisis likuiditas nilai tukar.</li><li><strong>B</strong>: Penerbitan SBN domestik justru diperkuat untuk kemandirian fiskal.</li><li><strong>C</strong>: Utang publik dikelola terpusat oleh Bendahara Umum Negara.</li><li><strong>E</strong>: Kerja sama multilateral tetap dijaga untuk diversifikasi portofolio.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>Di sisi pembiayaan, strategi diarahkan untuk menjaga sustainabilitas utang • melalui pengelolaan portofolio utang yang hati-hati, memperbesar porsi</strong></div></div>"}, {"category": "PKN", "sub": "Pengelolaan Keuangan Negara • Paket 02", "difficulty": "C2 - Pemahaman", "question": "Inovasi instrumen pembiayaan berkelanjutan yang dikembangkan pemerintah dalam postur pembiayaan APBN terkini diwujudkan melalui penerbitan...", "options": ["Surat utang komersial berbunga spekulatif di pasar gelap uang", "Kupon berhadiah langsung bagi nasabah pembeli barang elektronik", "Obligasi tanpa jaminan hukum bagi para pengusaha tambang emas", "Sertifikat pinjaman berbunga harian untuk konsumsi dinas aparatur", "Green bonds dan SDG bonds untuk mendukung pembangunan berkelanjutan"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Inovasi Pembiayaan Hijau (Green & SDG Bonds)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah mendorong <strong>inovasi pembiayaan berkelanjutan seperti green bonds dan SDG bonds</strong> untuk mendanai proyek ramah lingkungan dan sasaran pembangunan berkelanjutan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena obligasi tematik hijau (green/SDG bonds) menarik investor global yang peduli lingkungan sekaligus mempercepat transisi energi bersih nasional. Maka dari itu, opsi E benar.</p><p>Jawaban tepat: <strong>E. Green bonds dan SDG bonds untuk mendukung pembangunan berkelanjutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Surat utang pasar gelap melanggar hukum perbendaharaan dan pidana perbankan.</li><li><strong>B</strong>: Kupon berhadiah barang elektronik adalah promosi niaga pelaku usaha.</li><li><strong>C</strong>: SBN diterbitkan berlandaskan jaminan penuh undang-undang negara.</li><li><strong>D</strong>: Pinjaman bunga harian bertentangan dengan prinsip manajemen utang negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 27) &nbsp;|&nbsp; 🔍 Kata Kunci Cek Buku: <strong>mendorong inovasi pembiayaan berkelanjutan • seperti green bonds dan SDG bonds.</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_pkn_p02_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_pkn_p02_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_pkn_p02_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Pengelolaan Keuangan Negara Paket 02";
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
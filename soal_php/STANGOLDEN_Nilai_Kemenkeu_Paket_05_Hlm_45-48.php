<?php
/**
 * STANGOLDEN.exam • Nilai-Nilai Kemenkeu Paket 05 (Halaman 45–48)
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
// Deteksi konfigurasi Moodle atau gunakan Mode Preview Lokal
$isLocalPreview = ($configPath === null);
if ($isLocalPreview) {
    // Mode Standalone / Preview Lokal (Memungkinkan file dibuka tanpa server Moodle)
    if (!isset($USER)) {
        $USER = (object)['id' => 1, 'username' => 'peserta_lokal', 'firstname' => 'Peserta', 'lastname' => 'Lokal'];
    }
    if (!function_exists('sesskey')) { function sesskey() { return 'preview_sesskey_local_123'; } }
    if (!function_exists('is_siteadmin')) { function is_siteadmin($u = null) { return true; } }
    if (!defined('PARAM_ALPHANUMEXT')) define('PARAM_ALPHANUMEXT', 'alphanumext');
    if (!defined('PARAM_RAW')) define('PARAM_RAW', 'raw');
    if (!defined('PARAM_INT')) define('PARAM_INT', 'int');
    if (!function_exists('optional_param')) {
        function optional_param($name, $default, $type = null) {
            return $_GET[$name] ?? $_POST[$name] ?? $default;
        }
    }
    if (!function_exists('required_param')) {
        function required_param($name, $type = null) {
            return $_GET[$name] ?? $_POST[$name] ?? '';
        }
    }
    if (!function_exists('require_sesskey')) {
        function require_sesskey() { return true; }
    }
} else {
    require_once($configPath);
    require_login();
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
}

// Database Moodle: elearning; prefix fisik mdlhv_ diambil otomatis dari config.php.
// $DB->get_record("stgnk716_rank") => tabel fisik "mdlhv_stgnk716_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgnk_p05_rank';
const STG_REVIEW_T = 'stgnk_p05_rev';
const STG_ANNO_T = 'stgnk_p05_anno';
const STG_KEY = [2, 0, 3, 1, 4, 0, 2, 1, 3, 4, 1, 3, 0, 2, 4, 1, 3, 0, 4, 2, 2, 0, 4, 3, 1, 2, 0, 3, 1, 4, 1, 3, 0, 2, 4, 1, 3, 0, 2, 4];
// Cek tabel saja; tidak ada migrasi otomatis ketika peserta membuka halaman.
function stgnk_table(string $name, bool $review): void {
    global $DB, $CFG, $isLocalPreview;
    if (!empty($isLocalPreview)) return;
    require_once($CFG->libdir . '/ddllib.php');
    if (!$DB->get_manager()->table_exists(new xmldb_table($name))) {
        throw new RuntimeException('Tabel latihan belum disiapkan oleh administrator.');
    }
}
function stgnk_create_table(string $name, bool $review): void {
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
function stgnk_create_annotation_table(): void {
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
function stgnk_annotation_exists(): void {
    global $CFG, $DB;
    require_once($CFG->libdir . '/ddllib.php');
    if (!$DB->get_manager()->table_exists(new xmldb_table(STG_ANNO_T))) {
        throw new RuntimeException('Tabel anotasi belum disiapkan oleh administrator.');
    }
}
function stgnk_json(array $data,int $status=200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
}
$stgaction=optional_param('action','',PARAM_ALPHANUMEXT);
if ($stgaction === 'setup') {
    if (!is_siteadmin($USER)) {
        http_response_code(403);
        exit('Akses hanya untuk administrator Stangolden.');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_sesskey();
        try {
            stgnk_create_table(STG_LB_T, false);
            stgnk_create_table(STG_REVIEW_T, true);
            stgnk_create_annotation_table();
            echo '<h2>✅ Tabel ranking, review dan anotasi berhasil disiapkan.</h2><p>Database Moodle memakai prefix dari config.php (contoh: mdlhv_). Tidak perlu membuat tabel tambahan apabila tiga tabel sudah ada.</p><p><a href="' . htmlspecialchars((new moodle_url('/my/'))->out(false), ENT_QUOTES, 'UTF-8') . '">Kembali ke dashboard</a></p>';
        } catch (Throwable $e) {
            error_log('STANGOLDEN NK716 SETUP: ' . $e->getMessage());
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
    // Semua tindakan tetap di balik verifikasi login + enrollment di atas.
    try {
        if($stgaction==='save_score') {
            if($_SERVER['REQUEST_METHOD']!=='POST') stgnk_json(['success'=>false,'message'=>'Metode tidak valid.'],405);
            require_sesskey();
            $raw=required_param('answersjson',PARAM_RAW);
            $a=json_decode($raw,true);
            if(!is_array($a)||count($a)!==100||array_keys($a)!==range(0,99)) stgnk_json(['success'=>false,'message'=>'Format jawaban tidak valid.'],400);
            $clean=[];$correct=0;
            foreach($a as $i=>$v){
                if($v===null){$clean[]=-1;continue;}
                if(!is_int($v)||$v<0||$v>4) stgnk_json(['success'=>false,'message'=>'Pilihan jawaban tidak valid.'],400);
                $clean[]=$v;
                if($v===STG_KEY[$i]) $correct++;
            }
            $score=$correct;
            $time=max(0,min(3900,required_param('timetaken',PARAM_INT)));
            stgnk_table(STG_LB_T,false);stgnk_table(STG_REVIEW_T,true);
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
            stgnk_json(['success'=>true,'firstattemptsaved'=>$first]);
        }
        if ($stgaction === 'anno_load') {
            stgnk_annotation_exists();
            $rows = $DB->get_records(STG_ANNO_T, ['userid'=>(int)$USER->id]);
            $out = new stdClass();
            foreach ($rows as $r) {
                $q = (int)$r->questionnum;
                if ($q < 0 || $q > 99) { continue; }
                $strokes = json_decode((string)$r->strokesjson, true);
                $out->{(string)$q} = is_array($strokes) ? $strokes : [];
            }
            stgnk_json(['success'=>true,'annotations'=>$out]);
        }
        if ($stgaction === 'anno_save') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') stgnk_json(['success'=>false,'message'=>'Metode tidak valid.'],405);
            require_sesskey();
            stgnk_annotation_exists();
            // Anotasi hanya boleh disimpan setelah peserta punya review yang tersimpan.
            stgnk_table(STG_REVIEW_T,true);
            if (!$DB->record_exists(STG_REVIEW_T, ['userid'=>(int)$USER->id])) {
                stgnk_json(['success'=>false,'message'=>'Selesaikan ujian dahulu.'],403);
            }
            $q = required_param('question', PARAM_INT);
            if ($q < 0 || $q >= 100) stgnk_json(['success'=>false,'message'=>'Nomor soal tidak valid.'],400);
            $raw = required_param('strokesjson', PARAM_RAW);
            if (strlen($raw) > 250000) stgnk_json(['success'=>false,'message'=>'Coretan terlalu besar.'],413);
            $strokes = json_decode($raw, true);
            if (!is_array($strokes) || count($strokes)>350) stgnk_json(['success'=>false,'message'=>'Data coretan tidak valid.'],400);
            foreach ($strokes as $stroke) {
                if (!is_array($stroke) || !isset($stroke['tool'], $stroke['points']) ||
                    !in_array($stroke['tool'], ['pen','marker','eraser'], true) || !is_array($stroke['points']) || count($stroke['points']) > 3000) {
                    stgnk_json(['success'=>false,'message'=>'Format coretan tidak valid.'],400);
                }
                foreach ($stroke['points'] as $point) {
                    if (!is_array($point) || count($point)!==2 || !is_numeric($point[0]) || !is_numeric($point[1]) ||
                        $point[0]<0 || $point[0]>1 || $point[1]<0 || $point[1]>1) {
                        stgnk_json(['success'=>false,'message'=>'Koordinat coretan tidak valid.'],400);
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
            stgnk_json(['success'=>true]);
        }
        if ($stgaction === 'anno_clear') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') stgnk_json(['success'=>false,'message'=>'Metode tidak valid.'],405);
            require_sesskey();
            stgnk_annotation_exists();
            $DB->delete_records(STG_ANNO_T, ['userid'=>(int)$USER->id]);
            stgnk_json(['success'=>true]);
        }
        if($stgaction==='leaderboard') {
            stgnk_table(STG_LB_T,false);
            $records=$DB->get_records(STG_LB_T,null,'score DESC, timetaken ASC, timecreated ASC');
            $rows=[];$rank=0;
            foreach($records as $r){$rank++;$rows[]=['rank'=>$rank,'username'=>$r->username,'score'=>(int)$r->score,'correct'=>(int)$r->correct,'timetaken'=>(int)$r->timetaken,'isme'=>((int)$r->userid===(int)$USER->id)];}
            stgnk_json(['success'=>true,'leaderboard'=>$rows]);
        }
        if($stgaction==='last_review') {
            stgnk_table(STG_REVIEW_T,true);
            $r=$DB->get_record(STG_REVIEW_T,['userid'=>(int)$USER->id]);
            if(!$r) stgnk_json(['success'=>false,'message'=>'Belum ada jawaban terakhir.'],404);
            $a=json_decode($r->answersjson,true);
            if(!is_array($a)||count($a)!==100) stgnk_json(['success'=>false,'message'=>'Data jawaban tidak valid.'],500);
            stgnk_json(['success'=>true,'review'=>['answers'=>array_map(static function($v) { return ((int)$v < 0) ? null : (int)$v; }, $a),'score'=>(int)$r->score,'correct'=>(int)$r->correct,'timetaken'=>(int)$r->timetaken]]);
        }
        stgnk_json(['success'=>false,'message'=>'Aksi tidak dikenal.'],400);
    }catch(Throwable $e){error_log('STANGOLDEN NK716: '.$e->getMessage());stgnk_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Nilai-Nilai Kemenkeu Paket 05 (Hlm. 45–48) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 05 • Hlm. 45–48</span>
    </h1>

    <p class="start-sub">
      Ujian belum dimulai. Baca informasi singkat di bawah, lalu klik
      <b>Mulai Ujian</b> jika sudah siap.
    </p>

    <div class="start-info">
      <div class="start-info-box">
        <small>Jumlah Soal</small>
        <strong>40 Soal</strong>
      </div>
      <div class="start-info-box">
        <small>Waktu</small>
        <strong>30 Menit</strong>
      </div>
      <div class="start-info-box">
        <small>Komposisi</small>
        <strong>4 halaman × 10 soal</strong>
      </div>
    </div>

    <div class="start-rules">
      <h3>📌 Sebelum mulai</h3>
      <ul>
        <li>Timer baru berjalan setelah tombol <b>Mulai Ujian</b> diklik.</li>
        <li>Durasi latihan ditetapkan 30 menit.</li>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 05 • Hlm. 45–48</span></h1>
</section>
<div class="status"><div class="subject" id="subject" style="display:none"></div><div class="progress-wrap"><div class="progress-meta"><span id="progText">0 dari 40 dijawab</span><span id="progPct">0%</span></div><div class="track"><div class="fill" id="progFill"></div></div></div><div class="timer" id="timer">30:00</div></div>
<div class="grid" id="examQuestionGrid"><button type="button" class="map-toggle map-reopen" id="mapReopenButton" onclick="toggleQuestionMap(true)" aria-controls="questionMapPanel" aria-expanded="false">☷ Tampilkan Peta Soal</button><main class="card qcard"><div class="qmeta"><span class="badge no" id="qNo">Soal 1</span></div><div id="passage"></div><div class="qtext" id="qText"></div><div id="figArea" class="fig-wrap"></div><div class="options" id="options"></div><div class="qnav"><button class="btn" id="prevBtn" onclick="prevQ()">← Sebelumnya</button><button class="btn primary" id="nextBtn" onclick="nextQ()">Berikutnya →</button></div></main><aside class="sidebar" id="questionMapPanel"><div class="card side"><div class="map-head"><h3>Peta Soal</h3><button type="button" class="map-toggle" id="mapHideButton" onclick="toggleQuestionMap(false)" aria-controls="questionMapPanel" aria-expanded="true">Sembunyikan ✕</button></div><div class="numgrid" id="numGrid"></div></div><button class="btn dark" onclick="openSubmit()">Kumpulkan Jawaban</button><button type="button" class="btn primary" onclick="openSubmit()" style="width:100%;">Selesai Ujian →</button></aside></div>
</div>
<div class="wrap result" id="resultScreen"><div class="topbar"><div class="brand"><div class="brandmark">S</div> STANGOLDEN<span>.exam</span></div><div class="top-actions"><button class="btn" onclick="openLeaderboard()">🏆 Lihat Ranking</button><button class="btn" onclick="reviewLastAttempt()">📝 Review Jawaban Terakhir</button><button class="btn" onclick="retakeQuiz()">Ulangi tes</button></div></div><div id="resultContent"></div></div>

<div class="modal-overlay" id="modal"><div class="modal"><h3 style="font-size:22px;margin:0 0 8px">🚀 Kumpulkan jawaban?</h3><p id="modalText" style="color:var(--muted);line-height:1.55"></p><div style="display:flex;gap:10px"><button class="btn" style="flex:1" onclick="closeSubmit()">Cek lagi</button><button class="btn primary" style="flex:1" onclick="submitExam()">Kumpulkan</button></div></div></div>
<!-- Dialog ranking: dipakai tombol Lihat Ranking di awal, ujian, hasil dan pembahasan. -->
<div class="leaderboard-overlay" id="leaderboardOverlay" role="dialog" aria-modal="true" aria-labelledby="rankingModalTitle" onclick="leaderboardBackdrop(event)">
  <div class="leaderboard-modal">
    <div class="leaderboard-head">
      <div>
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Hlm. 7–16</h2>
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
const STG_LOCAL_KEY="stg_nk_0716_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 45", "difficulty": "HOTS • Analitis", "question": "Berdasarkan Tabel 3 SE-15/MK.1/2022, kelompok sasaran pelaksana masa kerja 1 tahun atau lebih memiliki target kematangan 'understand the values', sedangkan pejabat pengawas/administrator memiliki target 'implementing the values'. Bagaimanakah analisis rasional atas peningkatan target kematangan berjenjang tersebut?", "options": ["Peningkatan target kematangan bertujuan membebaskan pejabat pengawas dari kewajiban mematuhi kode etik dinas.", "Peningkatan target kematangan dirancang untuk memotong gaji pelaksana senior yang gagal menghafal regulasi menteri.", "Jenjang manajerial menuntut peningkatan dari sekadar pemahaman personal menjadi penerapan nyata sebagai teladan.", "Peningkatan target kematangan bertujuan membubarkan jenjang kepangkatan fungsional di lingkungan kementerian negara dinas.", "Peningkatan target kematangan mewajibkan pelaksana senior menggantikan seluruh tugas pimpinan kantor pelayanan."], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan, halaman 45", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 45</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/MK.1/2022: Pelaksana >= 1 tahun ditargetkan paham dan terapkan sebagian (understand the values); sedangkan Pejabat Pengawas/Administrator ditargetkan menerapkan seluruh Sikap Dasar dan Perilaku Efektif (implementing the values).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Hierarki maturitas budaya organisasi dirancang selaras dengan perluasan wewenang dan beban tanggung jawab kepemimpinan aparatur di lingkungan birokrasi pemerintahan. Pelaksana dengan masa kerja satu tahun atau lebih diharapkan telah memahami secara mendalam makna nilai organisasi (understand the values), sedangkan pejabat pengawas dan administrator yang memegang kendali tim dituntut untuk mengimplementasikan seluruh Sikap Dasar dan Perilaku Efektif secara konsisten (implementing the values) guna menjadi role model bagi bawahan. Uraian Tabel 3 SE-15/2022 membuktikan bahwa semakin tinggi jenjang jabatan, semakin mutlak tuntutan keteladanan penerapan nilai di ruang kerja. Oleh sebab itu, opsi C memberikan analisis rasional berjenjang yang paling akurat.</p><p>Jawaban yang sesuai adalah <strong>C. Jenjang manajerial menuntut peningkatan dari sekadar pemahaman personal menjadi penerapan nyata sebagai teladan.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena pejabat pengawas justru memikul tanggung jawab kepatuhan kode etik yang lebih tinggi.</li><li><strong>B</strong>: Keliru karena pembudayaan nilai berorientasi pembinaan karakter, bukan pemotongan gaji tanpa dasar hukum.</li><li><strong>D</strong>: Keliru karena jenjang jabatan fungsional tetap dipertahankan kokoh dalam kerangka meritokrasi modern.</li><li><strong>E</strong>: Keliru karena pembagian tugas operasional tetap mengacu pada uraian jabatan dan standar prosedur resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 45.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 45", "difficulty": "HOTS • Aplikasi", "question": "Sebuah kantor wilayah kementerian keuangan hendak mengintensifkan strategi Internalisasi nilai bagi para pelaksana senior. Berdasarkan Tabel 3 SE-15/MK.1/2022, instrumen aplikasi internalisasi yang secara khusus melibatkan agen perubahan di unit kerja adalah...", "options": ["Penyelenggaraan internalisasi terencana, sistematis, dan berkelanjutan yang dipandu langsung oleh Duta Transformasi.", "Menyewa konsultan motivasi swasta komersial dengan membebankan biaya jutaan rupiah pada uang saku pegawai kantor.", "Mewajibkan seluruh staf pelaksana senior menghafal nomor telepon genggam seluruh pejabat eselon satu kementerian.", "Menugaskan satpam kantor memberikan hukuman fisik push-up kepada staf yang lupa mengucapkan salam saat masuk lobi dinas.", "Menutup sistem jaringan komputer kantor selama satu bulan penuh guna menyelenggarakan perenungan batin pegawai."], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan, halaman 45", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 45</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/MK.1/2022 menetapkan strategi Internalisasi bagi pelaksana >= 1 tahun meliputi: internalisasi terencana, sistematis, dan berkelanjutan; internalisasi oleh Duta Transformasi; Program Habituasi; FGD sikap dasar; E-learning.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Duta Transformasi memegang posisi strategis sebagai motor penggerak perubahan (change agents) di tingkat tapak yang menjembatani arahan kebijakan pimpinan puncak dengan dinamika psikologis rekan sejawat. Pelaksanaan internalisasi nilai yang dipandu oleh Duta Transformasi secara terencana, konsisten, dan komunikatif mampu mencairkan resistensi budaya serta membangun kesadaran kolektif mengenai pentingnya integritas kerja. Tabel 3 SE-15/2022 secara eksplisit mencantumkan 'Internalisasi oleh Duta Transformasi' sebagai pilar utama strategi internalisasi kelompok sasaran ini. Dengan demikian, pelaksanaan internalisasi oleh Duta Transformasi pada opsi A adalah aplikasi regulasi yang paling sahih.</p><p>Jawaban yang sesuai adalah <strong>A. Penyelenggaraan internalisasi terencana, sistematis, dan berkelanjutan yang dipandu langsung oleh Duta Transformasi.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keliru karena pembudayaan mengutamakan kapasitas agen internal, bukan pembebanan biaya konsultan swasta.</li><li><strong>C</strong>: Keliru karena penghafalan nomor kontak pejabat tidak ada kaitannya dengan internalisasi nilai organisasi.</li><li><strong>D</strong>: Keliru karena penjatuhan hukuman fisik oleh petugas keamanan melanggar hak asasi dan kode etik kedinasan.</li><li><strong>E</strong>: Keliru karena pemadaman jaringan komputer melumpuhkan tugas pelayanan perbendaharaan dan fiskal negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 45.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 45", "difficulty": "HOTS • Analitis", "question": "Dalam strategi Institusionalisasi bagi pelaksana maupun pejabat struktural, SE-15/MK.1/2022 mengamanatkan penguatan peran tritunggal: Inspektorat Jenderal, Unit Kepatuhan Internal (UKI), dan Kehumasan. Bagaimanakah analisis keterpaduan peran ketiga unit tersebut dalam mengawal budaya organisasi?", "options": ["Itjen memungut pajak daerah, UKI menangani pinjaman koperasi dinas, dan Kehumasan mencetak kalender kantor tahunan.", "Ketiga unit bertugas mengambil alih wewenang menteri keuangan dalam menandatangani pengesahan anggaran belanja APBN.", "Ketiga unit berfungsi mengamankan seluruh rahasia pelanggaran korupsi pejabat agar tidak diketahui oleh penegak hukum.", "Itjen mengawal audit kepatuhan, UKI memitigasi risiko melekat, dan Humas menyosialisasikan narasi budaya positif.", "Ketiga unit dirancang untuk membubarkan seluruh loket pelayanan publik dan menggantinya dengan gerai perbankan dinas."], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan, halaman 45", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 45</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Penguatan peran Itjen, UKI, dan Kehumasan dalam Institusionalisasi: Inspektorat Jenderal sebagai auditor pengawas intern, UKI sebagai pengawal kepatuhan lini operasional, dan Kehumasan sebagai penyebar pesan edukasi budaya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Keberhasilan pelembagaan budaya kerja menuntut keseimbangan harmonis antara fungsi pengawasan penegakan aturan dan fungsi komunikasi publik yang persuasif. Inspektorat Jenderal menjalankan pengawasan tingkat makro melalui audit investigatif, Unit Kepatuhan Internal (UKI) melakukan pemantauan risiko dan kepatuhan kode etik secara harian di lini depan, sementara unit Kehumasan mengemas keberhasilan penegakan nilai menjadi narasi inspiratif yang memperkuat citra positif institusi. Sinergi tritunggal ini menciptakan ekosistem akuntabilitas yang kokoh dan transparan. Oleh karenanya, opsi D memberikan analisis integrasi kelembagaan yang paling akurat dan bernilai tinggi.</p><p>Jawaban yang sesuai adalah <strong>D. Itjen mengawal audit kepatuhan, UKI memitigasi risiko melekat, dan Humas menyosialisasikan narasi budaya positif.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena pemungutan pajak dan urusan koperasi bukan merupakan tupoksi pembudayaan ketiga unit tersebut.</li><li><strong>B</strong>: Keliru karena pengesahan APBN merupakan kewenangan konstitusional menteri keuangan dan lembaga legislatif.</li><li><strong>C</strong>: Keliru karena tugas pengawasan intern adalah membongkar kecurangan, bukan menyembunyikan tindak pidana korupsi.</li><li><strong>E</strong>: Keliru karena penguatan pengawasan intern bertujuan memurnikan pelayanan publik, bukan membubarkan loket dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 45.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 45", "difficulty": "HOTS • Evaluatif", "question": "Seorang pejabat pengawas menuntut staf bawahannya untuk disiplin waktu kerja dan ramah melayani warga, namun dirinya sendiri kerap datang terlambat dan bersikap kasar kepada tamu kantor. Mengapa perilaku pejabat pengawas tersebut KELIRU?", "options": ["Perilaku pejabat sangat tepat karena status struktural memberikan hak istimewa bebas dari seluruh aturan dinas.", "Perilaku pejabat keliru karena strategi eksternalisasi pejabat pengawas mewajibkan menjadi role model bagi unitnya.", "Perilaku pejabat dapat dibenarkan sepanjang pejabat bersangkutan memiliki hubungan kekerabatan dengan pimpinan.", "Perilaku pejabat sangat bijak dalam rangka melatih kesabaran psikologis masyarakat yang sedang berkunjung ke kantor.", "Perilaku pejabat mencerminkan kepemimpinan feodal otentik yang sah dalam mengendalikan staf bawahan di ruang kerja."], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan, halaman 45", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 45</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/MK.1/2022: Strategi Eksternalisasi bagi Pejabat Pengawas/Administrator mewajibkan implementasi budaya dan menjadi role model (teladan nyata) bagi unit terkecilnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Kepemimpinan di lingkungan Kementerian Keuangan bertumpu mutlak pada doktrin keteladanan (tone at the top), di mana tindakan nyata seorang pemimpin berbicara jauh lebih keras dibandingkan seribu instruksi lisan. Menuntut kedisiplinan dan kesantunan dari bawahan sementara diri sendiri menampilkan kemalasan dan kekasaran merupakan paradoks kemunafikan yang meruntuhkan legitimasi moral kepemimpinan dan memicu pembangkangan pasif dari pegawai. Tabel 3 SE-15/2022 secara eksplisit mewajibkan pejabat pengawas menjadi role model implementasi budaya bagi unit terkecilnya. Maka dari itu, penilaian pada opsi B membuktikan secara tajam bahwa perilaku pejabat tersebut adalah kekeliruan fatal.</p><p>Jawaban yang sesuai adalah <strong>B. Perilaku pejabat keliru karena strategi eksternalisasi pejabat pengawas mewajibkan menjadi role model bagi unitnya.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena regulasi etika dan disiplin mengikat seluruh aparatur sipil negara tanpa hak kekebalan jabatan.</li><li><strong>C</strong>: Keliru karena nepotisme kekerabatan dilarang keras dan tidak melegitimasi pelanggaran disiplin kedinasan.</li><li><strong>D</strong>: Keliru karena kekasaran kepada tamu kantor melanggar kode etik pelayanan prima dan merusak citra institusi.</li><li><strong>E</strong>: Keliru karena tata kelola pemerintahan modern berlandaskan kepemimpinan teladan, bukan feodalisme kuno.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 45.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 45", "difficulty": "HOTS • Aplikasi", "question": "Dalam rangka mengoperasionalkan strategi Institusionalisasi butir keenam bagi pelaksana senior di kantor pelayanan, langkah terstruktur yang paling tepat dilaksanakan oleh atasan langsung adalah...", "options": ["Mengunci staf pelaksana di ruang bawah tanah kantor selama jam kerja guna membaca buku undang-undang dinas kantor.", "Menyuruh staf senior mengerjakan seluruh urusan belanja dapur pribadi rumah tangga keluarga atasan langsung.", "Mewajibkan staf membayar uang bimbingan konsultasi karier sebesar satu juta rupiah setiap sesi pertemuan dinas.", "Menghapus seluruh catatan rekam jejak penilaian kinerja masa lalu agar staf merasa memiliki hutang budi kantor.", "Memasukkan materi nilai dan perilaku efektif dalam sesi Coaching, Counselling, Mentoring, dan On-the-job Training."], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan, halaman 45", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 45</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/MK.1/2022 strategi Institusionalisasi butir keenam: Memasukkan dalam materi Coaching, Counselling, Mentoring, dan On-the-job Training (OJT).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Metode pengembangan SDM melalui pendekatan personal dan praktis di tempat kerja (workplace learning) terbukti sangat efektif dalam menjembatani pemahaman konseptual nilai ke dalam eksekusi tugas nyata. Memasukkan penguatan Sikap Dasar dan pemecahan dilema etika ke dalam sesi berkala coaching (penggalian potensi), counselling (bimbingan psikologis), mentoring (arahan pengalaman), dan OJT (praktik langsung) membimbing pegawai bekerja profesional di bawah kompas nilai kementerian. Tabel 3 SE-15/2022 secara spesifik mengamanatkan integrasi materi budaya ke dalam keempat pilar pembinaan SDM tersebut. Oleh karenanya, pelaksanaan pembinaan pada opsi E adalah aplikasi regulasi yang paling sahih.</p><p>Jawaban yang sesuai adalah <strong>E. Memasukkan materi nilai dan perilaku efektif dalam sesi Coaching, Counselling, Mentoring, dan On-the-job Training.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena pengurungan pegawai melanggar hak asasi kemerdekaan fisik dan merupakan tindak pidana kejahatan.</li><li><strong>B</strong>: Keliru karena pemanfaatan pegawai untuk urusan domestik pribadi atasan adalah penyalahgunaan wewenang jabatan.</li><li><strong>C</strong>: Keliru karena pemungutan biaya pembinaan dinas merupakan tindak pidana korupsi pungutan liar kantor.</li><li><strong>D</strong>: Keliru karena manipulasi arsip penilaian kinerja melanggar asas akuntabilitas manajemen kepegawaian negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 45.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 45", "difficulty": "HOTS • Evaluatif", "question": "Seorang pejabat administrator menganggap bahwa penandatanganan Pakta Integritas tahunan oleh pegawai hanyalah formalitas seremonial yang tidak memiliki kekuatan hukum maupun moral, sehingga tidak perlu ditindaklanjuti dengan pengawasan riil. Mengapa cara pandang pejabat tersebut KELIRU?", "options": ["Cara pandang pejabat keliru karena Pakta Integritas adalah instrumen institusionalisasi komitmen anti-korupsi sah.", "Cara pandang pejabat sangat benar karena dokumen di atas kertas tidak pernah diakui dalam persidangan tindak pidana.", "Cara pandang pejabat dapat dibenarkan sepanjang unit bersangkutan mampu mencapai target penyerapan belanja negara.", "Cara pandang pejabat mencerminkan pemikiran rasional modern yang membebaskan aparatur dari ikrar tertulis negara.", "Cara pandang pejabat sangat efisien demi menghemat penggunaan materai dan tinta cetak dokumen di ruang kantor dinas."], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan, halaman 45", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 45</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/MK.1/2022 menetapkan pembuatan Pakta Integritas sebagai strategi Institusionalisasi komitmen moral dan hukum aparatur dalam menegakkan budaya anti-korupsi dan kepatuhan kode etik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Pakta Integritas merupakan dokumen janji hukum dan ikrar moral yang mengikat kesadaran aparatur secara personal di hadapan institusi dan Tuhan YME untuk tidak melakukan perbuatan curang, menolak suap, dan menghindari benturan kepentingan. Meremehkan Pakta Integritas sebagai formalitas kosong membuktikan lemahnya komitmen antikorupsi pimpinan yang membuka jalan bagi merajalelanya praktik penyelewengan di lingkungan kerjanya. Tabel 3 SE-15/2022 secara tegas menempatkan Pakta Integritas sebagai pilar institusionalisasi budaya yang wajib ditegakkan melalui pengawasan operasional yang nyata. Oleh sebab itu, evaluasi pada opsi A secara tepat menegaskan bahwa pengabaian tersebut adalah kekeliruan fatal.</p><p>Jawaban yang sesuai adalah <strong>A. Cara pandang pejabat keliru karena Pakta Integritas adalah instrumen institusionalisasi komitmen anti-korupsi sah.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keliru karena Pakta Integritas merupakan alat bukti hukum tertulis yang sah dalam pemeriksaan disiplin dan pidana.</li><li><strong>C</strong>: Keliru karena penyerapan belanja tanpa integritas justru memperbesar peluang terjadinya tindak pidana korupsi.</li><li><strong>D</strong>: Keliru karena ikrar integritas tertulis adalah fondasi akuntabilitas birokrasi pemerintahan di seluruh dunia modern.</li><li><strong>E</strong>: Keliru karena biaya materai tidak sebanding dengan perlindungan triliunan rupiah uang negara dari kebocoran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 45.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 45", "difficulty": "HOTS • Analitis", "question": "Manakah di antara pernyataan berikut yang BUKAN merupakan strategi yang tercantum dalam Tabel 3 SE-15/MK.1/2022 pada kolom sasaran Pelaksana masa kerja satu tahun atau lebih?", "options": ["Penyelenggaraan focus group discussion (FGD) mengenai penguatan sikap dasar dan perilaku efektif di lingkungan unit.", "Pemanfaatan media pembelajaran elektronik (e-learning) nilai-nilai dan kode etik yang terstruktur bagi pegawai.", "Pemberian izin resmi kepada pegawai untuk menerima komisi persenan uang lelang pengadaan barang dan jasa kantor.", "Penerapan sistem apresiasi penghargaan (reward) dan penjatuhan sanksi (punishment) yang adil dan transparan.", "Mendukung pelaksanaan penerapan budaya Kementerian Keuangan yang memberi manfaat nyata bagi masyarakat luas."], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan, halaman 45", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 45</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/2022 untuk Pelaksana >= 1 tahun meliputi: internalisasi terencana, Duta Transformasi, Habituasi, FGD, E-learning, regulasi, pembelajaran, reward/punishment, penguatan Itjen/UKI/Humas, Pakta Integritas, Coaching/Mentoring/OJT, manfaat masyarakat, kompetisi video/desain grafis. Menerima komisi persenan dilarang keras.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Seluruh butir strategi dalam Surat Edaran Nomor SE-15/MK.1/2022 dirancang secara konsisten untuk membersihkan birokrasi dari segala bentuk korupsi, kolusi, dan nepotisme demi menjaga kehormatan pengelola keuangan negara. Menerima komisi persenan dari proses pengadaan barang dan jasa merupakan tindak pidana suap dan gratifikasi yang diancam hukuman pidana penjara serta pemberhentian tidak dengan hormat sebagai aparatur sipil negara. Dokumen tabel resmi SE-15/2022 mengamanatkan penguatan integritas melalui reward and punishment serta pakta integritas. Maka dari itu, pemberian izin menerima persenan pada opsi C jelas merupakan pernyataan terlarang yang sama sekali bukan strategi yang diatur dalam tabel materi.</p><p>Jawaban yang sesuai adalah <strong>C. Pemberian izin resmi kepada pegawai untuk menerima komisi persenan uang lelang pengadaan barang dan jasa kantor.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Strategi internalisasi sah mengenai penyelenggaraan FGD sikap dasar yang tercantum dalam Tabel 3 materi.</li><li><strong>B</strong>: Strategi internalisasi sah mengenai pemanfaatan e-learning nilai dan kode etik yang tercantum dalam aturan.</li><li><strong>D</strong>: Strategi institusionalisasi sah mengenai sistem reward dan punishment yang tercantum dalam Tabel 3 aturan.</li><li><strong>E</strong>: Strategi eksternalisasi sah mengenai penerapan budaya yang memberi kemanfaatan luas bagi masyarakat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 45.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 45", "difficulty": "HOTS • Aplikasi", "question": "Sebuah satuan kerja di wilayah terpencil menghadapi kendala keterbatasan narasumber tatap muka untuk menyosialisasikan nilai-nilai organisasi kepada staf pelaksana senior. Berdasarkan butir kelima strategi Internalisasi pada Tabel 3 SE-15/MK.1/2022, solusi aplikatif yang paling tepat adalah...", "options": ["Membubarkan satuan kerja di wilayah terpencil tersebut dan memindahkan seluruh fungsinya ke kantor ibu kota pusat.", "Memanfaatkan pembelajaran mandiri berbasis digital melalui platform E-learning nilai-nilai dan kode etik kementerian.", "Membiarkan staf bekerja tanpa pernah mendapatkan pembinaan etika dan kode etik selama bertahun-tahun masa dinas.", "Menyuruh staf membaca novel fiksi komersial setiap pagi sebelum loket pelayanan publik dibuka untuk masyarakat.", "Mengirimkan staf berlibur ke luar negeri menggunakan anggaran belanja pemeliharaan gedung kantor dinas pelayanan kantor."], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan, halaman 45", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 45</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/MK.1/2022 strategi Internalisasi butir kelima menetapkan: E-learning nilai-nilai/kode etik sebagai instrumen pembelajaran digital yang fleksibel dan menjangkau seluruh pelosok negeri.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Pemanfaatan platform pembelajaran elektronik (e-learning) yang terstandardisasi memangkas kendala geografis dan jarak fisik, sehingga seluruh aparatur di daerah pelosok mendapatkan akses materi edukasi nilai dan kode etik dengan kualitas materi yang setara dengan kantor pusat. Melalui modul interaktif, simulasi studi kasus digital, dan evaluasi pemahaman daring, aparatur pelaksana senior dapat meningkatkan literasi etikanya secara mandiri dan fleksibel tanpa mengorbankan jam operasional pelayanan kantor. Butir kelima strategi internalisasi Tabel 3 SE-15/2022 secara spesifik menetapkan E-learning nilai/kode etik sebagai instrumen wajib. Oleh sebab itu, adopsi e-learning pada opsi B adalah solusi aplikatif yang paling tepat dan modern.</p><p>Jawaban yang sesuai adalah <strong>B. Memanfaatkan pembelajaran mandiri berbasis digital melalui platform E-learning nilai-nilai dan kode etik kementerian.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena penutupan satker terpencil menelantarkan hak pelayanan fiskal masyarakat di daerah perbatasan.</li><li><strong>C</strong>: Keliru karena pembiaran tanpa edukasi etika meningkatkan kerentanan moral dan pelanggaran hukum di daerah.</li><li><strong>D</strong>: Keliru karena membaca novel fiksi tidak relevan dengan kompetensi etika dan tata kelola perbendaharaan negara.</li><li><strong>E</strong>: Keliru karena penggunaan anggaran pemeliharaan gedung untuk liburan staf melanggar hukum perbendaharaan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 45.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 45", "difficulty": "HOTS • Kreasi", "question": "Rancanglah sebuah rencana aksi penguatan budaya bagi para pejabat pengawas di lingkungan kantor pelayanan yang menyinergikan target 'implementing the values' dengan peran role model!", "options": ["Pejabat pengawas diberi kebebasan mutlak tidak mematuhi SOP -> staf dihukum push-up -> laporan ke menteri dipalsukan.", "Mengganti seluruh jam kerja kantor dengan seminar motivasi -> menutup loket pelayanan warga -> memungut iuran staf.", "Menyita telepon genggam staf bawahan selama bekerja -> melarang staf bertanya -> mewajibkan sujud kepada atasan kantor dinas.", "Keteladanan kepatuhan jam kerja, keterbukaan membimbing tim (mentoring), aksi nyata pelayanan, dan evaluasi integritas.", "Mewajibkan pejabat pengawas mengundurkan diri massal guna memberikan kesempatan kepada pegawai lulusan baru."], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan, halaman 45", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 45</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Pejabat Pengawas ditargetkan 'implementing the values' melalui implementasi budaya nyata, menjadi role model unit terkecil, pembimbingan OJT/mentoring, dan pengarahan sosialisasi budaya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Penerapan seluruh Sikap Dasar dan Perilaku Efektif (implementing the values) oleh pejabat pengawas menuntut adanya integrasi antara disiplin pribadi dan pembinaan tim secara aktif di ruang kerja. Rencana aksi yang menggabungkan keteladanan hadir tepat waktu di loket, kesediaan meluangkan waktu untuk mendampingi staf menyelesaikan kendala teknis (mentoring), keterlibatan langsung dalam aksi nyata perbaikan layanan, serta penilaian kepatuhan etika berkala memastikan budaya organisasi terimplementasi secara hidup dan nyata. Sintesis rencana aksi pada opsi D secara paripurna menerjemahkan mandat Tabel 3 SE-15/2022 bagi pejabat pengawas ke dalam praktik manajerial unggul.</p><p>Jawaban yang sesuai adalah <strong>D. Keteladanan kepatuhan jam kerja, keterbukaan membimbing tim (mentoring), aksi nyata pelayanan, dan evaluasi integritas.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena kesewenang-wenangan dan pemalsuan laporan merusak akuntabilitas birokrasi pemerintahan.</li><li><strong>B</strong>: Keliru karena penutupan loket layanan demi seminar motivasi menelantarkan kewajiban publik kenegaraan.</li><li><strong>C</strong>: Keliru karena tindakan represif penyitaan dan feodalisme sujud melanggar hak asasi dan kesopanan dinas.</li><li><strong>E</strong>: Keliru karena pengunduran diri massal melumpuhkan kesinambungan tata kelola dan regenerasi kepemimpinan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 45.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 45", "difficulty": "HOTS • Kreasi", "question": "Rancanglah sebuah kerangka operasional pemberdayaan Duta Transformasi pada tingkat satuan kerja yang mampu mengakselerasi pencapaian target kematangan 'understand the values' bagi pelaksana senior!", "options": ["Duta Transformasi diberi wewenang memotong gaji pegawai yang tidak tersenyum saat apel bendera senin pagi.", "Duta Transformasi ditugaskan berkeliling kota membagikan selebaran komersial promosi bisnis keluarga pimpinan satker.", "Duta Transformasi melarang staf senior berkomunikasi dengan pimpinan kantor dan memutus akses internet meja kerja.", "Duta Transformasi mengambil alih seluruh fungsi kasir perbendaharaan dan menyetorkan uang kas ke rekening privatnya.", "Pemetaan kendala budaya unit, fasilitasi FGD tematik dwimingguan, diseminasi materi e-learning, dan konseling rekan sejawat."], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan, halaman 45", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 45</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Internalisasi oleh Duta Transformasi bagi pelaksana >= 1 tahun: memfasilitasi FGD sikap dasar, mengawal habituasi budaya, mendorong e-learning, dan mendampingi rekan kerja memahami nilai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Duta Transformasi bekerja sebagai katalisator perubahan budaya yang efektif manakala dibekali dengan metode kerja partisipatif yang menyentuh akar permasalahan di lingkungan kerja sehari-hari. Kerangka kerja operasional yang memadukan identifikasi titik resistensi budaya tim, fasilitasi forum diskusi kelompok terarah (FGD) secara berkala dengan bahasa yang akrab, pendampingan penyelesaian modul e-learning, serta konseling rekan sejawat (peer counselling) berhasil mengubah keengganan staf menjadi pemahaman nilai yang tulus (understand the values). Sintesis kerangka operasional pada opsi E menyajikan model pemberdayaan agen perubahan yang paling ilmiah dan aplikatif.</p><p>Jawaban yang sesuai adalah <strong>E. Pemetaan kendala budaya unit, fasilitasi FGD tematik dwimingguan, diseminasi materi e-learning, dan konseling rekan sejawat.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena Duta Transformasi bukanlah penegak hukuman disiplin finansial melainkan fasilitator edukasi nilai.</li><li><strong>B</strong>: Keliru karena penyebaran pamflet bisnis privat menyalahgunakan peran agen perubahan dan fasilitas negara.</li><li><strong>C</strong>: Keliru karena memutus komunikasi dan internet bertolak belakang dengan etos keterbukaan dan sinergi dinas.</li><li><strong>D</strong>: Keliru karena pengalihan kas negara ke rekening pribadi merupakan tindak pidana korupsi penggelapan uang negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 45.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 46", "difficulty": "HOTS • Analitis", "question": "Berdasarkan Tabel 3 SE-15/MK.1/2022, kelompok sasaran Pejabat Pimpinan Tinggi (JPT) memiliki target kematangan minimal 'memiliki, mengajak orang lain untuk menerapkan, serta mengembangkan Sikap Dasar dan Perilaku Efektif' (owning the values). Bagaimanakah analisis makna filosofis dari 'owning the values' bagi seorang pimpinan puncak?", "options": ["Owning the values bermakna pimpinan tinggi memiliki hak cipta intelektual komersial pribadi atas seluruh nilai negara.", "Owning the values menuntut nilai telah menyatu dalam jiwa pimpinan sehingga menjadi kompas setiap kebijakan strategis.", "Owning the values berarti pimpinan tinggi berhak mengubah rumusan nilai kementerian sesuai kepentingan pribadi dinas.", "Owning the values membebaskan pimpinan tinggi dari kewajiban mematuhi instruksi presiden dan regulasi menteri keuangan.", "Owning the values mengharuskan pimpinan tinggi membeli sertifikat kepemilikan saham pada perusahaan swasta luar negeri."], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan, halaman 46", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 46</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/MK.1/2022: Pejabat Pimpinan Tinggi/Fungsional setara ditargetkan memiliki, mengajak orang lain menerapkan, serta mengembangkan Sikap Dasar dan Perilaku Efektif (owning the values) sebagai puncak kepemilikan nilai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Tingkat kematangan 'owning the values' merupakan derajat tertinggi dalam evolusi budaya organisasi, di mana nilai-nilai luhur tidak lagi dipandang sebagai kewajiban eksternal yang dipaksakan oleh aturan, melainkan telah menyatu utuh (terinternalisasi sempurna) menjadi identitas moral dan falsafah hidup seorang pemimpin. Pada level ini, Pejabat Pimpinan Tinggi tidak hanya patuh pada aturan, tetapi secara aktif menjiwai nilai dalam setiap pengambilan keputusan kebijakan publik, menginspirasi ribuan aparatur di bawahnya untuk hidup berintegritas, serta mengembangkan inovasi budaya yang menjawab tantangan zaman. Tabel 3 SE-15/2022 secara presisi menetapkan target 'owning the values' bagi pimpinan puncak kementerian. Oleh sebab itu, opsi B menyajikan analisis filosofis kepemimpinan transformasional yang paling paripurna.</p><p>Jawaban yang sesuai adalah <strong>B. Owning the values menuntut nilai telah menyatu dalam jiwa pimpinan sehingga menjadi kompas setiap kebijakan strategis.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena nilai-nilai kementerian adalah pedoman etika moral publik, bukan hak paten komersial pribadi.</li><li><strong>C</strong>: Keliru karena perumusan nilai kementerian ditetapkan secara sah oleh keputusan menteri, bukan diubah sepihak.</li><li><strong>D</strong>: Keliru karena pimpinan tinggi tetap tunduk mutlak pada konstitusi, instruksi presiden, dan regulasi hukum negara.</li><li><strong>E</strong>: Keliru karena kepemilikan nilai bersifat spiritual afektif, bukan kepemilikan saham komersial finansial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 46.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 46", "difficulty": "HOTS • Aplikasi", "question": "Ketika menyusun draf kontrak perjanjian pengadaan teknologi informasi dengan pimpinan perusahaan multinasional, seorang Pejabat Pimpinan Tinggi Madya menerapkan butir kelima strategi Eksternalisasi pada Tabel 3 SE-15/MK.1/2022. Tindakan aplikasi yang paling tepat dilakukan pejabat tersebut adalah...", "options": ["Meminta perusahaan multinasional menyediakan mobil mewah pribadi untuk keluarga pejabat selama masa kontrak dinas.", "Menghapus seluruh klausul audit kepatuhan agar perusahaan multinasional dapat menaikkan harga pengadaan sesukanya dinas.", "Menandatangani kontrak pengadaan di ruang kasino perjudian luar negeri agar terbebas dari pengawasan KPK RI.", "Memasukkan ketentuan yang dilandasi Core Values ASN dan Nilai Kementerian Keuangan ke dalam klausul kerja sama.", "Meminta bagian saham kepemilikan perusahaan multinasional sebesar lima persen sebagai imbalan penandatanganan dinas."], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan, halaman 46", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 46</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/MK.1/2022 kolom Eksternalisasi butir kelima bagi Pejabat Pimpinan Tinggi: Memasukkan ketentuan yang dilandasi core values ASN dan nilai-nilai Kementerian Keuangan ke dalam klausul kerja sama dengan pihak eksternal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Mengikat pihak mitra swasta eksternal dalam koridor nilai-nilai organisasi merupakan instrumen pencegahan korupsi yang sangat ampuh untuk memastikan vendor tidak mencoba memberikan suap, gratifikasi, atau perlakuan diskriminatif dalam pelaksanaan kontrak kerja. Dengan mencantumkan klausul kepatuhan nilai BerAKHLAK dan Nilai Kemenkeu secara eksplisit di dalam naskah perjanjian kerja sama, pimpinan institusi menegaskan bahwa pelanggaran etika oleh mitra bisnis akan berakibat pemutusan kontrak dan sanksi daftar hitam resmi negara. Tabel 3 SE-15/2022 butir kelima secara eksplisit mewajibkan pimpinan tinggi memasukkan nilai ke dalam klausul kontrak eksternal. Oleh karenanya, tindakan pada opsi D adalah aplikasi regulasi yang paling sahih dan bermartabat.</p><p>Jawaban yang sesuai adalah <strong>D. Memasukkan ketentuan yang dilandasi Core Values ASN dan Nilai Kementerian Keuangan ke dalam klausul kerja sama.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena meminta fasilitas mobil mewah pribadi merupakan tindak pidana korupsi pemerasan jabatan.</li><li><strong>B</strong>: Keliru karena penghapusan klausul audit melanggar hukum perbendaharaan dan merugikan keuangan negara.</li><li><strong>C</strong>: Keliru karena bertransaksi di arena perjudian melanggar kode etik integritas dan moral kesusilaan aparatur.</li><li><strong>E</strong>: Keliru karena meminta bagian kepemilikan saham perusahaan vendor merupakan kejahatan korupsi gratifikasi suap.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 46.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 46", "difficulty": "HOTS • Evaluatif", "question": "Seorang Pejabat Pimpinan Tinggi menerbitkan surat keputusan internal yang memotong alokasi belanja fasilitas disabilitas dan loket masyarakat demi membiayai renovasi ruang kerja mewahnya sendiri. Mengapa keputusan pejabat tersebut KELIRU?", "options": ["Keputusan pejabat keliru karena pimpinan tinggi wajib membuat keputusan yang merupakan perwujudan budaya kementerian.", "Keputusan pejabat sangat tepat karena kenyamanan ruang kerja pimpinan tinggi menduduki prioritas di atas kepentingan publik.", "Keputusan pejabat dapat dibenarkan sepanjang renovasi ruang kerja dikerjakan oleh tukang bangunan lokal daerah.", "Keputusan pejabat mencerminkan kearifan birokrasi yang sah dalam menunjukkan simbol kekuasaan mutlak pimpinan.", "Keputusan pejabat sangat efisien karena pengorbanan fasilitas disabilitas berhasil menghemat kas operasional kantor."], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan, halaman 46", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 46</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/MK.1/2022: Pejabat Pimpinan Tinggi mengemban mandat eksternalisasi butir ketiga: Membuat keputusan/kebijakan yang merupakan perwujudan dari budaya Kemenkeu di unitnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Setiap keputusan dan kebijakan administratif yang ditandatangani oleh pejabat eselon puncak merupakan manifestasi otentik dari nilai moral institusi yang dipimpinnya. Mengorbankan hak fasilitas kaum rentan dan loket pelayanan masyarakat demi kemewahan ruang pribadi pimpinan merupakan pengkhianatan berat terhadap nilai Pelayanan, Integritas, dan etos anti-hedonisme aparatur negara. Tabel 3 SE-15/2022 secara tegas mewajibkan pejabat pimpinan tinggi membuat kebijakan yang secara nyata merefleksikan dan memperkuat budaya kementerian. Maka dari itu, penilaian kritis pada opsi A secara tepat membuktikan bahwa keputusan egois tersebut adalah kekeliruan fatal yang menodai marwah kelembagaan.</p><p>Jawaban yang sesuai adalah <strong>A. Keputusan pejabat keliru karena pimpinan tinggi wajib membuat keputusan yang merupakan perwujudan budaya kementerian.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keliru karena kepentingan kemaslahatan masyarakat pengguna layanan selalu menduduki derajat di atas kemewahan pejabat.</li><li><strong>C</strong>: Keliru karena penyalahgunaan anggaran publik tetap merupakan perbuatan tercela tanpa memandang asal pekerja.</li><li><strong>D</strong>: Keliru karena birokrasi pemerintahan modern menolak feodalisme kemewahan yang menindas hak masyarakat luas.</li><li><strong>E</strong>: Keliru karena meniadakan fasilitas kelompok disabilitas melanggar undang-undang hak asasi dan etika pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 46.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 46", "difficulty": "HOTS • Aplikasi", "question": "Dalam rangka mengimplementasikan butir keenam strategi Institusionalisasi bagi Pejabat Pimpinan Tinggi pada Tabel 3 SE-15/MK.1/2022, program pengembangan kepemimpinan tingkat lanjut yang wajib diselenggarakan kementerian adalah...", "options": ["Menyelenggarakan lomba adu ketahanan fisik menyelam di laut lepas tanpa tabung oksigen bagi pejabat eselon satu dinas kantor.", "Mewajibkan pejabat pimpinan tinggi berjualan makanan keliling kota pada hari sabtu dan minggu libur kedinasan.", "Memasukkan materi nilai dalam Executive Coaching, Counselling, Mentoring, dan Diklat Kepemimpinan / Executive Training.", "Mengharuskan pejabat pimpinan tinggi membayar uang tebusan jabatan setiap awal tahun anggaran kepada panitia diklat.", "Melarang pejabat pimpinan tinggi membaca buku manajemen modern dan mengharuskan pemakaian metode kepemimpinan kuno."], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan, halaman 46", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 46</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/MK.1/2022 strategi Institusionalisasi butir keenam bagi Pejabat Pimpinan Tinggi: Memasukkan dalam materi Executive Coaching, Counselling, Mentoring, Diklat Kepemimpinan / Executive Training, dan lainnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Pimpinan tingkat tinggi memerlukan ruang refleksi, pengasahan visi transformasional, dan pembaharuan paradigma kepemimpinan yang berintegritas tinggi melalui instrumen pengembangan eksekutif berstandar global. Mengintegrasikan materi penguatan budaya ke dalam wadah Executive Coaching, Executive Mentoring, dan Executive Training membekali para pimpinan puncak kemampuan menavigasi dinamika krisis fiskal dengan tetap berpegang teguh pada kompas moral organisasi. Rincian tabel resmi SE-15/2022 secara eksplisit menetapkan program eksekutif ini sebagai instrumen institusionalisasi bagi pejabat pimpinan tinggi. Oleh karenanya, pelaksanaan program eksekutif pada opsi C adalah aplikasi regulasi yang paling sahih.</p><p>Jawaban yang sesuai adalah <strong>C. Memasukkan materi nilai dalam Executive Coaching, Counselling, Mentoring, dan Diklat Kepemimpinan / Executive Training.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena latihan menyelam ekstrem membahayakan keselamatan nyawa dan tidak relevan dengan kepemimpinan.</li><li><strong>B</strong>: Keliru karena perniagaan jalanan bukan merupakan metode pembinaan kapasitas kepemimpinan strategis eksekutif.</li><li><strong>D</strong>: Keliru karena pungutan uang tebusan jabatan merupakan tindak pidana korupsi pemerasan dan jual beli jabatan.</li><li><strong>E</strong>: Keliru karena pejabat puncak dituntut menguasai ilmu manajemen mutakhir yang visioner dan adaptif teknologi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 46.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 46", "difficulty": "HOTS • Analitis", "question": "Tabel 3 SE-15/MK.1/2022 butir keempat strategi Eksternalisasi mewajibkan Pejabat Pimpinan Tinggi: menjadi narasumber yang memasukkan budaya dalam materi sosialisasi baik ke dalam maupun ke luar. Bagaimanakah korelasi peran narasumber eksternal tersebut terhadap pengaruh reputasi kementerian?", "options": ["Peran narasumber bertujuan agar pimpinan tinggi mendapatkan penghasilan honorarium pribadi sebesar-besarnya dari luar.", "Peran narasumber diwajibkan untuk mengalihkan tugas pengawasan teknis perbendaharaan kepada kementerian koordinator.", "Peran narasumber dirancang agar pimpinan tinggi dapat mempromosikan partai politik pendukungnya kepada kementerian lain.", "Peran narasumber membebaskan kementerian dari kewajiban menyampaikan laporan pertanggungjawaban kepada presiden.", "Menjadi narasumber eksternal memperluas diseminasi standar integritas Kemenkeu sebagai tolok ukur reformasi birokrasi nasional."], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan, halaman 46", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 46</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/MK.1/2022: Pejabat Pimpinan Tinggi menjadi narasumber yang memasukkan budaya dalam materi sosialisasi ke dalam maupun ke luar guna menyebarkan 'virus integritas' ke ekosistem birokrasi nasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Kementerian Keuangan diakui secara luas sebagai lokomotif dan tolok ukur (benchmark) reformasi birokrasi nasional dalam tata kelola anggaran dan kepatuhan kode etik pegawai. Ketika para Pejabat Pimpinan Tingginya tampil aktif sebagai narasumber di forum-forum kementerian lain, lembaga negara, dan perguruan tinggi dengan menggaungkan nilai BerAKHLAK dan budaya Kemenkeu Satu, reputasi kelembagaan kementerian semakin kokoh sekaligus menginspirasi transformasi integritas pada skala nasional. Butir keempat strategi eksternalisasi SE-15/2022 menempatkan pimpinan tinggi sebagai juru bicara peradaban integritas bangsa. Oleh sebab itu, opsi E menyajikan analisis dampak strategis reputasi kementerian secara paling komprehensif.</p><p>Jawaban yang sesuai adalah <strong>E. Menjadi narasumber eksternal memperluas diseminasi standar integritas Kemenkeu sebagai tolok ukur reformasi birokrasi nasional.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena pengabdian sebagai narasumber berakar pada misi edukasi kenegaraan, bukan komersialisasi honor.</li><li><strong>B</strong>: Keliru karena tugas pengawasan teknis perbendaharaan tetap melekat pada fungsi internal kementerian keuangan.</li><li><strong>C</strong>: Keliru karena keterlibatan politik praktis dalam sosialisasi melanggar kewajiban netralitas mutlak aparatur sipil negara.</li><li><strong>D</strong>: Keliru karena akuntabilitas kepada Presiden Republik Indonesia merupakan kewajiban konstitusional mutlak menteri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 46.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 46", "difficulty": "HOTS • Evaluatif", "question": "Sebuah satuan kerja menandatangani kontrak pengadaan infrastruktur gedung bernilai miliaran rupiah dengan rekanan swasta tanpa menyertakan klausul kepatuhan nilai BerAKHLAK dan integritas Kemenkeu dengan alasan kontrak sudah standar. Mengapa kelalaian tersebut KELIRU?", "options": ["Kelalaian tersebut sangat tepat karena klausul etika tidak pernah memiliki dampak hukum dalam hukum perikatan perdata.", "Kelalaian tersebut keliru karena ketiadaan klausul nilai menghilangkan dasar mitigasi hukum atas potensi suap rekanan.", "Kelalaian tersebut dapat dibenarkan sepanjang rekanan swasta bersedia memberikan potongan harga semen dan pasir.", "Kelalaian tersebut mencerminkan kecepatan birokrasi yang sah dalam memangkas ketebalan kertas draf kontrak dinas.", "Kelalaian tersebut sangat efisien karena satker tidak perlu lagi repot mengawasi perilaku etika para pekerja bangunan."], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan, halaman 46", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 46</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/MK.1/2022 mewajibkan: memasukkan ketentuan yang dilandasi core values ASN dan nilai-nilai Kementerian Keuangan ke dalam klausul kerja sama dengan pihak eksternal sebagai benteng integritas pengadaan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Kontrak kerja sama pengadaan barang dan jasa pemerintah merupakan titik paling rawan terjadinya praktik persekongkolan, suap, dan gratifikasi yang merugikan keuangan negara. Tidak dicantumkannya klausul kepatuhan integritas dan etika nilai Kemenkeu menyebabkan kementerian kehilangan pijakan hukum yang kokoh untuk menjatuhkan sanksi pemutusan sepihak atau tuntutan ganti rugi apabila vendor terbukti mencoba menyuap pejabat pengadaan. Tabel 3 SE-15/2022 butir kelima secara imperatif mewajibkan penyisipan klausul nilai ke dalam perjanjian dengan pihak eksternal demi memitigasi risiko korupsi. Maka dari itu, penilaian pada opsi B secara tepat membuktikan bahwa kelalaian tersebut adalah kekeliruan fatal tata kelola pengadaan.</p><p>Jawaban yang sesuai adalah <strong>B. Kelalaian tersebut keliru karena ketiadaan klausul nilai menghilangkan dasar mitigasi hukum atas potensi suap rekanan.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena klausul etika dan integritas memiliki kekuatan hukum mengikat sah dalam hukum perikatan kontrak.</li><li><strong>C</strong>: Keliru karena diskon material bahan bangunan tidak menghapus bahaya kerugian negara akibat kebocoran integritas.</li><li><strong>D</strong>: Keliru karena kecepatan penandatanganan buta tanpa klausul pengaman mencederai kehati-hatian perbendaharaan.</li><li><strong>E</strong>: Keliru karena pengawasan kepatuhan etika rekanan merupakan kewajiban mutlak pejabat pembuat komitmen negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 46.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 46", "difficulty": "HOTS • Analitis", "question": "Manakah di antara pernyataan berikut yang BUKAN merupakan butir strategi Eksternalisasi yang ditetapkan bagi Pejabat Pimpinan Tinggi dalam Tabel 3 SE-15/MK.1/2022?", "options": ["Menjadi role model (teladan nyata perilaku berintegritas) bagi unit yang dipimpinnya maupun bagi instansi lain.", "Menginisiasi program atau aksi nyata sebagai wujud konkret penerapan budaya Kementerian Keuangan di masyarakat.", "Membuat keputusan atau kebijakan yang merupakan perwujudan dari budaya Kementerian Keuangan di lingkungan unitnya.", "Mengalokasikan dana hibah bantuan sosial kepada tim sukses pemenangan partai politik saat masa pemilihan umum.", "Memasukkan ketentuan yang dilandasi core values ASN dan nilai Kemenkeu ke dalam klausul kerja sama eksternal."], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan, halaman 46", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 46</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/2022 kolom Eksternalisasi Pejabat Pimpinan Tinggi: (1) role model bagi unitnya & instansi lain; (2) inisiasi program/aksi nyata; (3) buat keputusan/kebijakan perwujudan budaya; (4) narasumber sosialisasi ke dalam/luar; (5) klausul kerja sama eksternal. Hibah dana politik dilarang keras.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Kelima butir strategi Eksternalisasi bagi Pejabat Pimpinan Tinggi dalam SE-15/MK.1/2022 berfokus penuh pada keteladanan moral, kebijakan yang bermartabat, penyebaran nilai melalui forum edukasi, dan pengamanan kontrak kerja sama pihak ketiga. Menyalurkan bantuan dana sosial untuk kepentingan politik praktis merupakan kejahatan tindak pidana korupsi penyalahgunaan wewenang dan pelanggaran berat terhadap netralitas mutlak aparatur sipil negara. Teks resmi tabel materi tidak pernah membenarkan pendanaan politik dalam bentuk apa pun. Oleh sebab itu, klausul pengalokasian dana politik pada opsi D jelas merupakan perbuatan terlarang yang sama sekali bukan strategi eksternalisasi yang diatur dalam regulasi.</p><p>Jawaban yang sesuai adalah <strong>D. Mengalokasikan dana hibah bantuan sosial kepada tim sukses pemenangan partai politik saat masa pemilihan umum.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Butir eksternalisasi sah mengenai peran role model bagi unit dan instansi lain sesuai Tabel 3 aturan.</li><li><strong>B</strong>: Butir eksternalisasi sah mengenai inisiasi program atau aksi nyata budaya kementerian sesuai regulasi.</li><li><strong>C</strong>: Butir eksternalisasi sah mengenai perumusan kebijakan yang mencerminkan budaya kementerian di unitnya.</li><li><strong>E</strong>: Butir eksternalisasi sah mengenai penyisipan klausul nilai ke dalam naskah perjanjian kerja sama mitra luar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 46.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 46", "difficulty": "HOTS • Aplikasi", "question": "Sebuah direktorat jenderal hendak mencetak kader-kader penggerak budaya baru yang tangguh di seluruh unit vertikal daerah. Berdasarkan Tabel 3 SE-15/MK.1/2022, program pelatihan intensif yang secara spesifik dirancang bagi Duta Transformasi adalah...", "options": ["Penyelenggaraan Training of Trainers (ToT) terkait Sikap Dasar dan Perilaku Efektif bagi Duta Transformasi.", "Pemberian latihan keterampilan menari modern di atas panggung terbuka pusat perbelanjaan komersial ibu kota.", "Menugaskan Duta Transformasi berjaga di loket parkir kendaraan selama dua puluh empat jam setiap hari dinas.", "Mewajibkan Duta Transformasi mengikuti pendidikan dasar militer infanteri tempur hutan selama dua tahun penuh.", "Menyuruh Duta Transformasi menghafal seluruh nomor seri lembaran uang kertas rupiah yang beredar di masyarakat dinas."], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan, halaman 46", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 46</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/MK.1/2022 mencantumkan program: Training of Trainers (ToT) terkait Sikap Dasar/Perilaku Efektif bagi Duta Transformasi guna membekali keterampilan memfasilitasi dan mengedukasi pegawai lain.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Duta Transformasi tidak hanya dituntut memiliki komitmen moral pribadi, tetapi juga wajib dibekali dengan keterampilan metodologis dalam memfasilitasi dialog kelompok, teknik komunikasi persuasif, serta penguasaan substansi Sikap Dasar dan Perilaku Efektif. Program Training of Trainers (ToT) membekali para agen perubahan ini kemampuan pedagogis untuk melatih, membimbing, dan menginspirasi rekan-rekan sejawatnya di unit kerja daerah masing-masing secara sistematis dan percaya diri. Dokumen Tabel 3 SE-15/2022 secara eksplisit menetapkan program ToT bagi Duta Transformasi sebagai instrumen penguatan kapasitas internalisasi. Dengan demikian, penyelenggaraan ToT pada opsi A adalah aplikasi regulasi yang paling sahih.</p><p>Jawaban yang sesuai adalah <strong>A. Penyelenggaraan Training of Trainers (ToT) terkait Sikap Dasar dan Perilaku Efektif bagi Duta Transformasi.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keliru karena pertunjukan tari komersial tidak relevan dengan kompetensi fasilitasi nilai budaya kementerian.</li><li><strong>C</strong>: Keliru karena penugasan parkir berlebihan merupakan eksploitasi tenaga di luar kompetensi pembinaan budaya.</li><li><strong>D</strong>: Keliru karena aparatur sipil negara pengelola fiskal dibina dalam kompetensi tata kelola sipil, bukan militer tempur.</li><li><strong>E</strong>: Keliru karena penghafalan nomor seri uang kertas adalah tindakan irasional yang membuang waktu dan energi dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 46.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 46", "difficulty": "HOTS • Kreasi", "question": "Rancanglah sebuah klausul kepatuhan integritas (integrity clause) yang wajib dicantumkan dalam kontrak kerja sama pengadaan jasa kebersihan gedung kantor kementerian sesuai mandat SE-15/MK.1/2022!", "options": ["Penyedia jasa wajib memberikan bingkisan parsel makanan dan uang tunai kepada pejabat pembuat komitmen setiap hari raya.", "Penyedia jasa berhak memotong gaji tenaga kebersihan sebesar lima puluh persen tanpa perlu melapor kepada kementerian.", "Penyedia jasa dibebaskan dari seluruh tuntutan hukum apabila terbukti menyuap staf pengawas kebersihan kantor dinas.", "Penyedia jasa diwajibkan membelikan tiket liburan ke luar negeri bagi seluruh panitia lelang pengadaan barang kantor.", "Penyedia jasa wajib menjunjung nilai BerAKHLAK dan Kemenkeu, menolak suap gratifikasi, dan siap diputus kontrak bila curang."], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan, halaman 46", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 46</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Tabel 3 SE-15/MK.1/2022: Memasukkan ketentuan yang dilandasi core values ASN dan nilai-nilai Kementerian Keuangan ke dalam klausul kerja sama dengan pihak eksternal guna memitigasi risiko kecurangan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Penyusunan klausul integritas dalam kontrak pengadaan publik memerlukan rumusan hukum yang tegas, mengikat, dan memiliki sanksi pemutus yang jelas terhadap setiap upaya kecurangan atau penyuapan. Klausul yang mewajibkan vendor menghormati Core Values ASN BerAKHLAK dan Nilai-Nilai Kemenkeu, mengharamkan pemberian gratifikasi atau fasilitas apa pun kepada pejabat kementerian, serta menetapkan sanksi pemutusan kontrak sepihak dan ganti rugi apabila terjadi pelanggaran integritas membentengi keuangan negara secara preventif. Sintesis klausul kontrak pada opsi E secara presisi merealisasikan mandat strategi eksternalisasi SE-15/2022 menjadi instrumen hukum yang kokoh.</p><p>Jawaban yang sesuai adalah <strong>E. Penyedia jasa wajib menjunjung nilai BerAKHLAK dan Kemenkeu, menolak suap gratifikasi, dan siap diputus kontrak bila curang.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena kewajiban memberi parsel dan uang tunai merupakan persekongkolan gratifikasi suap terlarang.</li><li><strong>B</strong>: Keliru karena pemotongan upah tenaga kebersihan melanggar hukum ketenagakerjaan dan asas keadilan sosial.</li><li><strong>C</strong>: Keliru karena klausul impunitas suap melanggar hukum pidana pemberantasan tindak pidana korupsi kenegaraan.</li><li><strong>D</strong>: Keliru karena pemberian tiket liburan kepada panitia pengadaan merupakan tindak pidana suap jabatan aktif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 46.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 46", "difficulty": "HOTS • Kreasi", "question": "Rancanglah sebuah kerangka tata kelola kepemimpinan transformasional bagi Pejabat Pimpinan Tinggi yang mengintegrasikan pencapaian 'owning the values' dengan perumusan kebijakan strategis kementerian!", "options": ["Pimpinan memusatkan seluruh proyek kepada kerabat keluarga -> menutup kanal audit Itjen -> menghapus aturan kode etik.", "Pimpinan membiarkan pelanggaran bawahan -> merekayasa laporan kinerja anggaran -> menolak audit dari BPK RI.", "Internalisasi nurani (owning), teladan role model, perumusan kebijakan berlandaskan nilai, dan klausul integritas eksternal.", "Pimpinan memotong tunjangan seluruh staf -> membagikan dana kantor untuk kampanye -> menghapus pakta integritas.", "Pimpinan mengisolasi diri di ruang kerja -> menolak bertemu tamu -> melimpahkan wewenang keputusan kepada staf magang."], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan, halaman 46", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 46</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Sintesis Pejabat Pimpinan Tinggi Tabel 3 SE-15/2022: menjiwai nilai (owning the values), menjadi role model bagi instansi lain, membuat keputusan/kebijakan perwujudan budaya, dan memasukkan klausul nilai ke ranah eksternal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Kepemimpinan puncak yang transformasional menuntut keselarasan paripurna antara penghayatan batiniah, keteladanan perilaku publik, dan produk kebijakan hukum yang dihasilkannya bagi kemaslahatan bangsa. Kerangka kerja yang merajut penjiwaan nilai luhur ke dalam kesadaran diri (owning the values), penampilan keteladanan sebagai teladan nyata (role model) di dalam maupun di luar kementerian, penerbitan kebijakan operasional yang mengedepankan etika pelayanan, serta penegakan integritas dalam relasi dengan pihak mitra eksternal membentuk ekosistem Kemenkeu Satu yang berdaya saing global. Sintesis manajerial pada opsi C merupakan model kepemimpinan puncak yang paling komprehensif dan visioner.</p><p>Jawaban yang sesuai adalah <strong>C. Internalisasi nurani (owning), teladan role model, perumusan kebijakan berlandaskan nilai, dan klausul integritas eksternal.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena nepotisme proyek dan penutupan audit merupakan kejahatan korupsi yang menghancurkan negara.</li><li><strong>B</strong>: Keliru karena penolakan audit BPK dan rekayasa kinerja melanggar hukum tata kelola keuangan negara.</li><li><strong>D</strong>: Keliru karena pemotongan tunjangan sepihak demi dana politik melanggar hukum perbendaharaan dan netralitas.</li><li><strong>E</strong>: Keliru karena isolasi diri dan pelimpahan wewenang kepada staf magang merupakan pelepasan tanggung jawab jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 46.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 47", "difficulty": "HOTS • Analitis", "question": "Pada Bab V Penutup materi modul, diuraikan pembedaan antara definisi Nilai secara umum dengan Nilai-Nilai Organisasi. Manakah di antara pernyataan berikut yang secara akurat menganalisis pembedaan esensial kedua konsep tersebut?", "options": ["Nilai umum bersifat mengikat secara hukum pidana, sedangkan Nilai Organisasi hanya merupakan anjuran lisan semata.", "Nilai umum hanya berlaku bagi masyarakat di luar negeri, sedangkan Nilai Organisasi berlaku bagi warga lokal daerah.", "Nilai adalah keyakinan baik-buruk individu, sedangkan Nilai Organisasi adalah keyakinan kolektif menuju tujuan bersama.", "Nilai umum ditetapkan oleh peraturan menteri keuangan, sedangkan Nilai Organisasi ditentukan oleh serikat buruh pabrik dinas.", "Nilai umum berfokus pada teknologi kecerdasan buatan, sedangkan Nilai Organisasi berfokus pada pembukuan akuntansi kas."], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan, halaman 47", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 47</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Bab V Penutup: Nilai adalah sebuah keyakinan tentang apa yang dianggap baik atau buruk yang memengaruhi perilaku orang dalam melakukan tindakan. Sedangkan Nilai-Nilai Organisasi merupakan keyakinan dan kepercayaan yang diyakini oleh individu, kelompok, dan seluruh pemangku kepentingan dalam organisasi untuk berpikir, bertindak, dan berperilaku menuju tujuan organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Konsep Nilai pada tataran individual berfungsi sebagai kompas subjektif yang membimbing seseorang menilai mana perbuatan yang patut atau tercela dalam kehidupannya. Namun ketika individu-individu tersebut bergabung ke dalam sebuah institusi publik, nilai-nilai individual tersebut harus dikristalisasikan dan diselaraskan menjadi Nilai-Nilai Organisasi—yakni keyakinan, standar etika, dan kesepakatan filosofis bersama yang dipegang teguh oleh seluruh insan institusi demi mencapai visi dan tujuan bersama negara. Teks Bab V Penutup secara gamblang membedakan ranah individual dari nilai umum dengan ranah kolektif integratif dari nilai organisasi. Oleh karena itu, opsi C menyajikan analisis pembedaan konseptual yang paling tepat.</p><p>Jawaban yang sesuai adalah <strong>C. Nilai adalah keyakinan baik-buruk individu, sedangkan Nilai Organisasi adalah keyakinan kolektif menuju tujuan bersama.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena nilai umum berada di ranah etika sosial, sedangkan nilai organisasi diikat regulasi formal kedinasan.</li><li><strong>B</strong>: Keliru karena nilai individual dan nilai organisasi sama-sama berlaku universal tanpa batas geografis negara.</li><li><strong>D</strong>: Keliru karena nilai organisasi kementerian dirumuskan resmi oleh kementerian, bukan serikat buruh eksternal.</li><li><strong>E</strong>: Keliru karena kedua konsep berakar pada domain psikologi moral dan perilaku manusia, bukan perangkat keras TI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 47.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 47", "difficulty": "HOTS • Analitis", "question": "Bab V Penutup menegaskan bahwa kohesi antar-individu, kesatuan kelompok, dan kesatuan organisasi merupakan prasyarat sebuah organisasi untuk meningkatkan kinerjanya melalui budaya organisasi. Bagaimanakah korelasi sosiologis antara kohesi sosial dengan peningkatan kinerja institusi?", "options": ["Kohesi yang kuat memupuk rasa saling percaya, memangkas friksi koordinasi, dan menyatukan energi mencapai target.", "Kohesi sosial bertujuan agar seluruh pegawai kementerian dapat saling meminjamkan uang kas kantor secara bebas.", "Kohesi kelompok menuntut agar pegawai yang berbuat curang dilindungi dari pemeriksaan aparat penegak hukum dinas.", "Kohesi organisasi dirancang untuk menghapus kewajiban pegawai dalam mematuhi jam kerja dan tata tertib dinas.", "Kohesi antar-individu membenarkan praktik nepotisme dalam penerimaan calon pegawai negeri sipil yang baru."], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan, halaman 47", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 47</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Penutup Bab V: kohesi antar individu, kesatuan kelompok, dan kesatuan organisasi merupakan prasyarat sebuah organisasi untuk meningkatkan kinerjanya, melalui budaya organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Organisasi publik yang terfragmentasi oleh ego sektoral, kecurigaan antarpribadi, dan konflik kepentingan internal akan membuang sebagian besar energinya untuk gesekan birokrasi (organizational friction) yang melumpuhkan produktivitas. Terwujudnya kohesi sosial dan kesatuan batiniah yang diikat oleh budaya organisasi melahirkan modal sosial (social capital) yang tinggi, memangkas biaya koordinasi, memperlancar arus data transaksi, serta menyatukan seluruh potensi insan pegawai menjadi daya dorong dahsyat dalam mengawal penerimaan dan perbendaharaan negara. Teks Penutup secara presisi menetapkan kohesi sebagai conditio sine qua non (syarat mutlak) bagi lonjakan kinerja. Oleh sebab itu, opsi A memberikan analisis sosiologis kinerja yang paling sahih.</p><p>Jawaban yang sesuai adalah <strong>A. Kohesi yang kuat memupuk rasa saling percaya, memangkas friksi koordinasi, dan menyatukan energi mencapai target.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keliru karena pengelolaan uang kas negara tunduk ketat pada regulasi perbendaharaan dan dilarang untuk pinjaman privat.</li><li><strong>C</strong>: Keliru karena solidaritas korps yang melindungi pelaku kejahatan korupsi merupakan konspirasi sesat terlarang.</li><li><strong>D</strong>: Keliru karena kohesi yang sehat justru memperkokoh kedisiplinan dan kepatuhan sukarela terhadap aturan dinas.</li><li><strong>E</strong>: Keliru karena nepotisme melanggar asas meritokrasi dan keadilan hukum dalam penerimaan aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 47.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 47", "difficulty": "HOTS • Aplikasi", "question": "Bab V menguraikan bahwa konsep dasar nilai adalah agar pegawai dapat mengelola pribadi yang selaras dengan tujuan dari pelaksanaan tugasnya. Bentuk aplikasi nyata dari kemampuan mengelola pribadi yang selaras dengan tugas kedinasan adalah...", "options": ["Melampiaskan amarah pribadi kepada wajib pajak di loket pelayanan saat pegawai menghadapi masalah keluarga di rumah.", "Menolak penugasan dinas ke luar daerah dengan alasan acara pesta perayaan ulang tahun teman pribadi lebih utama.", "Menggunakan wewenang tanda tangan jabatan untuk mengintimidasi tetangga rumah dalam sengketa tanah keluarga pribadi.", "Menuntut kenaikan pangkat kehormatan luar biasa setiap kali pegawai berhasil datang ke kantor sebelum jam delapan pagi.", "Mengendalikan emosi diri, memisahkan urusan privat dari dinas, dan mencurahkan kompetensi demi kemaslahatan publik."], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan, halaman 47", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 47</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Bab V Penutup: Konsep dasar nilai bersama adalah agar ASN atau pegawai dapat mengelola pribadi yang selaras dengan tujuan dari pelaksanaan tugasnya dalam pengabdian kenegaraan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Kematangan emosional dan integritas aparatur tecermin dari kemampuannya melakukan manajemen diri (self-management) secara bijaksana, sehingga kepentingan publik yang diemban dalam tugas kedinasan selalu berada di atas kepentingan nafsu atau masalah pribadi. Menahan diri dari luapan amarah, bersikap objektif dan profesional di loket pelayanan meskipun sedang dirundung beban personal, serta mengarahkan segenap keahlian untuk menuntaskan amanah negara merupakan perwujudan sejati dari pribadi yang selaras dengan tujuan organisasi. Teks Bab V Penutup menegaskan keharusan mengelola pribadi selaras dengan amanah penugasan. Dengan demikian, penguasaan diri yang luhur pada opsi E adalah aplikasi nilai yang paling sempurna.</p><p>Jawaban yang sesuai adalah <strong>E. Mengendalikan emosi diri, memisahkan urusan privat dari dinas, dan mencurahkan kompetensi demi kemaslahatan publik.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena melampiaskan masalah privat kepada pengguna layanan mencerminkan kelemahan mentalitas aparatur.</li><li><strong>B</strong>: Keliru karena mengutamakan pesta pribadi di atas penugasan dinas negara melanggar asas loyalitas kedinasan.</li><li><strong>C</strong>: Keliru karena intimidasi pribadi menggunakan wewenang jabatan merupakan tindak pidana penyalahgunaan kekuasaan.</li><li><strong>D</strong>: Keliru karena ketepatan waktu jam kerja merupakan kewajiban dasar standar, bukan dasar penuntutan pangkat luar biasa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 47.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 47", "difficulty": "HOTS • Evaluatif", "question": "Seorang pimpinan satuan kerja berpendapat bahwa membangun kohesi dan kesatuan kelompok hanyalah kegiatan seremonial yang membuang waktu, sehingga ia sengaja membiarkan timbulnya persaingan tidak sehat dan kebencian antar-seksi. Mengapa kebijakan pimpinan tersebut KELIRU?", "options": ["Kebijakan pimpinan sangat tepat karena kebencian antar-seksi terbukti melipatgandakan kepatuhan wajib pajak daerah.", "Kebijakan pimpinan dapat dibenarkan sepanjang anggaran operasional perjalanan dinas kantor dapat terserap habis.", "Kebijakan pimpinan mencerminkan kepemimpinan adu domba modern yang sah dalam menjaga supremasi wewenang atasan.", "Kebijakan pimpinan keliru karena kohesi dan kesatuan organisasi adalah prasyarat mutlak peningkatan kinerja institusi.", "Kebijakan pimpinan sangat efisien karena satker tidak perlu lagi mengeluarkan biaya untuk kegiatan pembinaan tim."], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan, halaman 47", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 47</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Bab V Penutup menegaskan: kohesi antar individu, kesatuan kelompok dan kesatuan organisasi merupakan prasyarat sebuah organisasi untuk meningkatkan kinerjanya, melalui budaya organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Menerapkan politik pecah belah (divide et impera) dan membiarkan permusuhan antarunit di lingkungan birokrasi pemerintahan merupakan tindakan destruktif yang merusak koordinasi, memicu sabotase data, dan menghancurkan efektivitas kelembagaan kementerian. Keruntuhan kohesi kerja meracuni iklim psikologis kantor dan mematikan inovasi, sehingga target kinerja organisasi gagal tercapai secara berkualitas. Bab V Penutup secara eksplisit menggariskan bahwa kohesi individu dan kesatuan kelompok adalah syarat mutlak (prasyarat) bagi lompatan kinerja organisasi publik. Oleh sebab itu, kritik evaluatif pada opsi D secara tepat membuktikan bahwa pembiaran konflik tersebut adalah kekeliruan fatal.</p><p>Jawaban yang sesuai adalah <strong>D. Kebijakan pimpinan keliru karena kohesi dan kesatuan organisasi adalah prasyarat mutlak peningkatan kinerja institusi.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena perseteruan internal justru melumpuhkan koordinasi pelayanan perpajakan dan merugikan negara.</li><li><strong>B</strong>: Keliru karena penyerapan anggaran di tengah perpecahan internal sarat dengan inefisiensi dan pemborosan.</li><li><strong>C</strong>: Keliru karena taktik adu domba adalah warisan kolonialisme terlarang yang merusak martabat tata kelola modern.</li><li><strong>E</strong>: Keliru karena penghematan biaya pembinaan tim tidak sebanding dengan kehancuran produktivitas institusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 47.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 47", "difficulty": "HOTS • Analitis", "question": "Materi Bab V Penutup menyatakan bahwa Nilai Dasar ASN BerAKHLAK dan Nilai-Nilai Kementerian Keuangan merupakan dua kerangka nilai yang saling mendukung. Bagaimanakah keterkaitan harmonis kedua kerangka nilai tersebut dalam menopang profesionalitas aparatur?", "options": ["Keterkaitan tersebut bermakna bahwa pegawai kementerian dibebaskan dari kewajiban mematuhi undang-undang ASN.", "Keduanya memiliki konsep dasar yang sama yaitu memandu aparatur mengelola diri selaras dengan tujuan tugas negara.", "Keduanya dirancang untuk menghapus kewajiban ujian seleksi dalam pengangkatan calon aparatur sipil negara.", "Keduanya mewajibkan seluruh transaksi penerimaan keuangan negara disalurkan ke rekening bank swasta di luar negeri.", "Keduanya menetapkan bahwa seluruh kebijakan perpajakan nasional harus disetujui oleh lembaga moneter asing."], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan, halaman 47", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 47</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Bab V Penutup: Nilai Dasar ASN dan Nilai-nilai Kementerian Keuangan merupakan dua nilai yang dapat saling mendukung karena memiliki konsep dasar yang sama yaitu agar ASN atau pegawai dapat mengelola pribadi yang selaras dengan tujuan dari pelaksanaan tugasnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Hubungan antara Core Values ASN BerAKHLAK dan Nilai-Nilai Kementerian Keuangan bukanlah hubungan hierarki yang saling meniadakan atau bertentangan, melainkan simbiosis filosofis yang saling memperkokoh satu sama lain. Keduanya bermuara pada satu cita-cita luhur yang sama: membentuk karakter aparatur sipil negara yang berintegritas tinggi, berdedikasi melayani rakyat, mampu mengendalikan kepentingan pribadi di bawah kompas etika publik, serta profesional dalam menjaga kedaulatan negara. Uraian Bab V Penutup secara gamblang menyatakan bahwa kedua nilai saling mendukung karena memiliki kesamaan konsep dasar manajemen diri aparatur. Oleh karenanya, opsi B memberikan analisis keterkaitan harmonis yang paling tepat.</p><p>Jawaban yang sesuai adalah <strong>B. Keduanya memiliki konsep dasar yang sama yaitu memandu aparatur mengelola diri selaras dengan tujuan tugas negara.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena ASN kementerian tetap tunduk mutlak pada undang-undang aparatur sipil negara yang berlaku.</li><li><strong>C</strong>: Keliru karena seleksi kompetensi CPNS tetap diwajibkan secara ketat berdasarkan prinsip meritokrasi nasional.</li><li><strong>D</strong>: Keliru karena seluruh penerimaan negara wajib disetorkan langsung ke Rekening Kas Umum Negara Republik Indonesia.</li><li><strong>E</strong>: Keliru karena kebijakan perpajakan nasional merupakan hak kedaulatan fiskal mutlak negara Republik Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 47.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 47", "difficulty": "HOTS • Evaluatif", "question": "Seorang pejabat fungsional perbendaharaan berpendapat bahwa selama hasil audit akuntansi berstatus wajar tanpa pengecualian, seorang aparatur bebas berkata bohong dan bertindak menyimpang di luar kantor. Mengapa cara pandang pejabat tersebut KELIRU?", "options": ["Cara pandang pejabat sangat benar karena opini wajar tanpa pengecualian secara otomatis menghapus seluruh dosa moral.", "Cara pandang pejabat dapat dibenarkan sepanjang kebohongan yang diucapkan tidak dilaporkan kepada pihak kepolisian.", "Cara pandang pejabat keliru karena nilai Integritas mewajibkan keselarasan berpikir, berkata, berperilaku, dan bertindak.", "Cara pandang pejabat mencerminkan kearifan birokrasi yang sah dalam memisahkan urusan administrasi dari moralitas.", "Cara pandang pejabat sangat efisien demi menjaga agar laporan keuangan kantor tetap terlihat sempurna di mata publik."], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan, halaman 47", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 47</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Ringkasan Bab V Penutup butir 1 Integritas: seluruh pegawai harus berpikir, berkata, berperilaku, dan bertindak dengan baik dan benar serta selalu memegang teguh Kode Etik dan prinsip-prinsip moral dalam setiap aspek kehidupan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Integritas bukanlah sekadar angka administratif di atas kertas neraca akuntansi, melainkan mahkota moral yang menuntut kebenaran sejati dari hulu pikiran batiniah, tutur kata lisan, hingga tindakan lahiriah di ruang kantor maupun dalam pergaulan masyarakat luas. Menafsirkan integritas secara parsial—di mana pembukuan rapi namun lisan berdusta dan tindakan korup—merupakan pengkhianatan terhadap hakikat integritas itu sendiri. Rangkuman materi Bab V Penutup secara kategoris menegaskan bahwa Integritas berarti berpikir, berkata, berperilaku, dan bertindak dengan baik dan benar serta memegang teguh moralitas. Oleh karena itu, evaluasi pada opsi C secara tepat membuktikan terjadinya kekeliruan pemahaman nilai yang sangat mendasar.</p><p>Jawaban yang sesuai adalah <strong>C. Cara pandang pejabat keliru karena nilai Integritas mewajibkan keselarasan berpikir, berkata, berperilaku, dan bertindak.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena opini audit akuntansi keuangan tidak pernah melegitimasi kebohongan dan penyimpangan moral aparatur.</li><li><strong>B</strong>: Keliru karena ketiadaan laporan kepolisian tidak menggugurkan status pelanggaran kode etik integritas kedinasan.</li><li><strong>D</strong>: Keliru karena tata kelola pemerintahan yang bersih menolak pemisahan munafik antara administrasi dan moralitas.</li><li><strong>E</strong>: Keliru karena kejujuran substantif jauh lebih utama daripada pencitraan kesempurnaan laporan yang manipulatif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 47.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 47", "difficulty": "HOTS • Analitis", "question": "Manakah di antara pernyataan berikut yang BUKAN merupakan esensi utama dari nilai Profesionalisme sebagaimana diringkas kembali pada Bab V Penutup materi modul?", "options": ["Bekerja secara lambat dan asal-asalan sepanjang pegawai memiliki hubungan kekeluargaan yang dekat dengan atasan.", "Bekerja berdasarkan standar kompetensi terbaik yang dimiliki dan terus dikembangkan secara berkelanjutan.", "Menyelesaikan setiap amanah pekerjaan dengan penuh rasa tanggung jawab moral dan akuntabilitas hukum yang tinggi.", "Menjalankan tugas kedinasan dengan komitmen yang tinggi demi kemajuan organisasi kementerian dan kejayaan bangsa.", "Bekerja dengan tuntas dan akurat dalam setiap penyelesaian tugas dan tanggung jawab kedinasan kenegaraan."], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan, halaman 47", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 47</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Ringkasan Bab V Penutup butir 2 Profesionalisme: seluruh pegawai harus bekerja dengan tuntas dan akurat berdasarkan kompetensi terbaik dan penuh tanggung jawab serta komitmen yang tinggi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Nilai Profesionalisme menempatkan kompetensi terbaik, penyelesaian tuntas, akurasi data yang presisi, rasa tanggung jawab, dan komitmen tinggi sebagai pilar yang tidak dapat ditawar dalam birokrasi pengelola perbendaharaan negara. Sikap kerja yang lamban, ceroboh, dan mengandalkan koneksi kekeluargaan (nepotisme) merupakan penyakit birokrasi masa lalu yang merusak tatanan meritokrasi dan bertolak belakang secara frontal dengan profesionalisme. Teks penutup halaman 47 secara gamblang menggariskan pilar-pilar keunggulan profesional aparatur negara. Oleh sebab itu, klausul kerja lamban berbasis kekeluargaan pada opsi A jelas merupakan perbuatan yang sama sekali bukan esensi nilai profesionalisme.</p><p>Jawaban yang sesuai adalah <strong>A. Bekerja secara lambat dan asal-asalan sepanjang pegawai memiliki hubungan kekeluargaan yang dekat dengan atasan.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Esensi sah nilai profesionalisme mengenai pendasaran tugas pada kompetensi terbaik aparatur sipil negara.</li><li><strong>C</strong>: Esensi sah nilai profesionalisme mengenai pemenuhan tugas dengan penuh tanggung jawab moral dan hukum.</li><li><strong>D</strong>: Esensi sah nilai profesionalisme mengenai pelaksanaan tugas kenegaraan dengan komitmen dedikasi yang tinggi.</li><li><strong>E</strong>: Esensi sah nilai profesionalisme mengenai penyelesaian kerja tuntas dan akurat sesuai teks penutup materi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 47.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 47", "difficulty": "HOTS • Aplikasi", "question": "Dalam rangka merealisasikan ringkasan butir ketiga nilai Sinergi pada Bab V Penutup materi modul, tindakan nyata yang paling tepat ditunjukkan oleh seorang pejabat administrator di kantor wilayah adalah...", "options": ["Memboikot seluruh agenda rapat kerja dengan instansi pemerintah daerah dan melarang pertukaran informasi dinas kantor.", "Menolak kerja sama dengan direktorat jenderal lain demi mengamankan alokasi anggaran belanja kantor sendiri.", "Menyembunyikan data potensi penerimaan perpajakan agar tidak diketahui oleh seksi pengawasan perbendaharaan.", "Membangun hubungan kerja sama internal yang produktif dan kemitraan harmonis untuk hasilkan karya bermanfaat.", "Meminta uang suap fasilitasi koordinasi kepada satuan kerja mitra setiap kali menyelenggarakan forum dinas."], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan, halaman 47", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 47</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Ringkasan Bab V Penutup butir 3 Sinergi: seluruh pegawai harus berkomitmen untuk membangun dan memastikan hubungan kerja sama internal yang produktif serta kemitraan yang harmonis dengan para pemangku kepentingan, untuk menghasilkan karya yang bermanfaat dan berkualitas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Komitmen membangun kemitraan yang produktif dan harmonis merupakan kunci pembuka bagi lahirnya karya-karya kebijakan fiskal yang berdaya guna tinggi bagi kemakmuran rakyat Indonesia. Meruntuhkan dinding pemisah antarunit kerja internal dan menjalin komunikasi yang setara dengan para pemangku kepentingan eksternal melipatgandakan efektivitas tata kelola keuangan negara secara menyeluruh. Teks ringkasan nilai Sinergi pada Bab V Penutup secara presisi memerintahkan pembangunan kerja sama internal produktif dan kemitraan harmonis demi karya berkualitas. Maka dari itu, tindakan integratif pada opsi D adalah aplikasi nilai sinergi yang paling tepat dan profesional.</p><p>Jawaban yang sesuai adalah <strong>D. Membangun hubungan kerja sama internal yang produktif dan kemitraan harmonis untuk hasilkan karya bermanfaat.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena pemboikotan rapat koordinasi merusak hubungan kemitraan harmonis dengan pemerintah daerah.</li><li><strong>B</strong>: Keliru karena menolak kolaborasi demi ego anggaran sektoral mencederai asas keutuhan Kemenkeu Satu.</li><li><strong>C</strong>: Keliru karena penyembunyian data potensi penerimaan negara melumpuhkan pengawasan fiskal yang akuntabel.</li><li><strong>E</strong>: Keliru karena memungut suap koordinasi merupakan tindak pidana korupsi pemerasan jabatan yang tercela.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 47.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 47", "difficulty": "HOTS • Kreasi", "question": "Rancanglah sebuah program kerja pembinaan kohesi internal (internal cohesion program) pada satuan kerja pelayanan di daerah terpencil guna meningkatkan kinerja organisasi sesuai amanat Bab V!", "options": ["Mewajibkan pegawai menyetor uang tabungan pribadi kepada bendahara pengeluaran tanpa bukti kuitansi resmi.", "Dialog berkala lintas seksi, bedah tantangan bersama, apresiasi kolaborasi, dan pembudayaan empati kedinasan.", "Melarang seluruh staf saling berbicara dan mengharuskan komunikasi dilakukan melalui surat kaleng tanpa nama.", "Meniadakan jam istirahat siang dan mewajibkan pegawai bekerja terus-menerus selama delapan belas jam per hari.", "Menyerahkan seluruh keputusan pengelolaan kepegawaian kantor kepada pihak yayasan swasta komersial luar."], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan, halaman 47", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 47</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Bab V Penutup menegaskan pentingnya kohesi antar individu, kesatuan kelompok, dan kesatuan organisasi melalui budaya organisasi sebagai prasyarat peningkatan kinerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Membangun kohesi sosial dan kesatuan korps di wilayah operasional yang penuh tantangan geografis menuntut program pembinaan yang mengedepankan keterbukaan dialog, rasa saling memiliki, dan empati kemanusiaan. Penyelenggaraan forum koordinasi berkala yang membedah kendala kerja secara terbuka, perancangan solusi bersama tanpa saling menyalahkan, pemberian penghargaan bagi tim yang paling kompak berkolaborasi, serta pembiasaan saling peduli berhasil merekatkan kohesi batiniah aparatur. Sintesis program pembinaan kohesi pada opsi B secara tepat mengoperasionalkan amanat sosiologis Bab V Penutup menjadi aksi nyata satuan kerja.</p><p>Jawaban yang sesuai adalah <strong>B. Dialog berkala lintas seksi, bedah tantangan bersama, apresiasi kolaborasi, dan pembudayaan empati kedinasan.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena pungutan tabungan tanpa kuitansi merupakan praktik penggelapan uang yang melanggar hukum.</li><li><strong>C</strong>: Keliru karena melarang komunikasi terbuka dan menyuburkan surat kaleng merusak iklim saling percaya tim.</li><li><strong>D</strong>: Keliru karena eksploitasi jam kerja tanpa istirahat melanggar regulasi kepegawaian dan merusak kesehatan mental.</li><li><strong>E</strong>: Keliru karena privatisasi manajemen ASN kepada yayasan luar melanggar kedaulatan tata kelola aparatur negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 47.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 47", "difficulty": "HOTS • Kreasi", "question": "Rancanglah sebuah sintesis konseptual mengenai bagaimana penyelarasan Nilai Dasar ASN BerAKHLAK dan Nilai-Nilai Kementerian Keuangan mampu mewujudkan tata kelola keuangan negara yang berkelas dunia!", "options": ["Penyelarasan nilai membebaskan aparatur dari kewajiban menyusun laporan keuangan berbasis standar akuntansi sah.", "Penyelarasan nilai mewajibkan kementerian membubarkan fungsi pengawasan melekat yang selama ini diemban UKI.", "Penyelarasan nilai mengharuskan penutupan seluruh kantor pelayanan pajak dan bea cukai di seluruh wilayah Indonesia.", "Penyelarasan nilai membenarkan penerimaan hadiah gratifikasi dari pengusaha swasta demi meningkatkan daya beli.", "Penyelarasan nilai membangun karakter aparatur yang amanah, kompeten, kolaboratif, dan adaptif berstandar global."], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan, halaman 47", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 47</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Bab V Penutup: Nilai Dasar ASN BerAKHLAK dan Nilai Kemenkeu saling mendukung dalam mengelola pribadi aparatur selaras tujuan tugas, guna membangun budaya organisasi unggul penopang kinerja institusi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Tata kelola keuangan negara yang kredibel, transparan, dan berkelas dunia (world-class public financial management) hanya dapat ditegakkan di atas fondasi sumber daya manusia aparatur yang memiliki perpaduan antara kompetensi teknis unggul dan integritas moral yang tidak tergoyahkan. Harmonisasi nilai BerAKHLAK sebagai kompas nasional dengan Nilai-Nilai Kementerian Keuangan mencetak profil aparatur yang amanah dalam mengelola kas negara, kompeten menyelesaikan tugas secara tuntas, kolaboratif meruntuhkan sekat birokrasi, serta adaptif memanfaatkan disrupsi teknologi demi kemaslahatan rakyat. Sintesis konseptual pada opsi E menyajikan visi transformasi birokrasi yang paling paripurna dan visioner.</p><p>Jawaban yang sesuai adalah <strong>E. Penyelarasan nilai membangun karakter aparatur yang amanah, kompeten, kolaboratif, dan adaptif berstandar global.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena standar akuntansi pemerintahan merupakan pilar wajib akuntabilitas perbendaharaan negara.</li><li><strong>B</strong>: Keliru karena penyelarasan nilai justru menuntut penguatan peran UKI sebagai pagar pengaman integritas.</li><li><strong>C</strong>: Keliru karena kantor pelayanan merupakan instrumen vital kedaulatan penerimaan dan pengawasan fiskal negara.</li><li><strong>D</strong>: Keliru karena penerimaan gratifikasi merupakan tindak pidana korupsi yang mencederai nilai integritas publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 47.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 48", "difficulty": "HOTS • Analitis", "question": "Bab V Penutup merumuskan kembali definisi nilai Pelayanan sebagai: memberikan pelayanan untuk memenuhi kepuasan pemangku kepentingan yang dilaksanakan dengan sepenuh hati, transparan, cepat, akurat, dan aman. Mengapa unsur 'aman' menjadi dimensi yang mutlak dalam pelayanan kementerian?", "options": ["Unsur aman bertujuan memastikan gedung kantor pelayanan dijaga oleh tentara bersenjata berat selama jam buka loket kantor.", "Unsur aman menjamin kepastian hukum, perlindungan data rahasia pihak terlayani, dan pencegahan risiko penyelewengan.", "Unsur aman menuntut agar seluruh permohonan layanan publik disimpan di dalam brankas baja tanpa pernah diproses.", "Unsur aman bermakna bahwa pegawai kementerian dibebaskan dari kewajiban mematuhi kode etik dan disiplin dinas.", "Unsur aman dirancang agar kementerian dapat menolak melayani masyarakat yang mengkritik prosedur pelayanan kantor."], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan, halaman 48", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 48</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Ringkasan Bab V Penutup butir 4 Pelayanan: memberikan pelayanan untuk memenuhi kepuasan para pemangku kepentingan dan dilaksanakan dengan sepenuh hati, transparan, cepat, akurat, dan aman.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Pelayanan di bidang pengelolaan perbendaharaan, perpajakan, kepabeanan, dan kekayaan negara berurusan langsung dengan hak finansial warga negara, dokumen hukum kepemilikan aset, serta data transaksi yang bernilai strategis tinggi. Pelayanan yang cepat namun mengabaikan aspek keamanan (security) akan membuka celah terjadinya kebocoran data rahasia wajib pajak, sengketa kepemilikan aset negara, hingga potensi manipulasi pencairan kas perbendaharaan yang merugikan keuangan negara dan masyarakat. Penegasan unsur 'aman' pada Bab V Penutup menjamin bahwa proses layanan diselenggarakan dengan standar kepatuhan hukum, proteksi kerahasiaan siber, dan mitigasi risiko yang andal. Oleh karena itu, opsi B menyajikan analisis esensi keamanan layanan secara paling presisi.</p><p>Jawaban yang sesuai adalah <strong>B. Unsur aman menjamin kepastian hukum, perlindungan data rahasia pihak terlayani, dan pencegahan risiko penyelewengan.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena pelayanan publik sipil mengedepankan keramahan dan kenyamanan, bukan militeristik bersenjata.</li><li><strong>C</strong>: Keliru karena pengurungan berkas di brankas baja tanpa diproses melanggar hak pelayanan publik warga negara.</li><li><strong>D</strong>: Keliru karena keamanan pelayanan justru terwujud dari kepatuhan ketat aparatur terhadap kode etik dan disiplin.</li><li><strong>E</strong>: Keliru karena kritik masyarakat merupakan instrumen evaluasi perbaikan, bukan dasar penolakan pelayanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 48.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 48", "difficulty": "HOTS • Analitis", "question": "Pada Bab V Penutup, nilai Kesempurnaan didefinisikan sebagai: senantiasa melakukan upaya perbaikan di segala bidang untuk menjadi dan memberikan yang terbaik. Bagaimanakah analisis filosofis mengenai sifat dinamis frasa 'senantiasa melakukan upaya perbaikan'?", "options": ["Frasa tersebut bermakna bahwa seluruh standar operasional prosedur kantor harus diganti setiap hari senin pagi.", "Frasa tersebut menuntut agar kementerian membubarkan seluruh struktur organisasi yang ada setiap akhir tahun dinas.", "Frasa tersebut mewajibkan pegawai bekerja terus-menerus tanpa pernah tidur demi mengejar rekor jam kerja terpanjang.", "Frasa tersebut menegaskan bahwa kesempurnaan bukanlah titik henti melainkan proses continuous improvement abadi.", "Frasa tersebut dirancang agar pimpinan unit kerja dapat memecat staf pelaksana sesuka hati tanpa dasar aturan sah."], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan, halaman 48", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 48</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Ringkasan Bab V Penutup butir 5 Kesempurnaan: seluruh pegawai harus senantiasa melakukan upaya perbaikan di segala bidang untuk menjadi dan memberikan yang terbaik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Nilai Kesempurnaan dalam filosofi manajemen kementerian bukanlah klaim kesombongan bahwa institusi telah mencapai kondisi absolut yang statis dan bebas cela, melainkan suatu komitmen etos kerja tiada henti (kaizen / continuous improvement) untuk terus mengevaluasi diri, memperbaiki kelemahan, dan menyempurnakan kualitas pelayanan. Ketika suatu standar keunggulan telah tercapai, insan kementerian terdorong untuk menetapkan standar baru yang lebih tinggi demi menjawab dinamika kemajuan peradaban dan tuntutan rakyat. Rumusan Bab V Penutup secara presisi memposisikan kesempurnaan sebagai ikhtiar perbaikan berkelanjutan di segala lini. Oleh sebab itu, opsi D memberikan analisis filosofis yang paling tepat dan mendalam.</p><p>Jawaban yang sesuai adalah <strong>D. Frasa tersebut menegaskan bahwa kesempurnaan bukanlah titik henti melainkan proses continuous improvement abadi.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena perubahan SOP harian tanpa evaluasi ilmiah hanya menciptakan kekacauan kepastian hukum dinas.</li><li><strong>B</strong>: Keliru karena pembubaran struktur tahunan merusak kesinambungan tata kelola perbendaharaan negara.</li><li><strong>C</strong>: Keliru karena nilai kesempurnaan menuntut efektivitas kerja cerdas, bukan perusakan kesehatan biologis aparatur.</li><li><strong>E</strong>: Keliru karena pemecatan sewenang-wenang melanggar asas hukum kepegawaian dan kepastian karier meritokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 48.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 48", "difficulty": "HOTS • Aplikasi", "question": "Ketika mengelola loket pendaftaran perizinan kepabeanan, seorang petugas memverifikasi keaslian tanda tangan digital pemohon dan memastikan berkas permohonan tersimpan dalam peladen terenkripsi. Penerapan dimensi nilai Pelayanan apakah yang ditunjukkan petugas tersebut?", "options": ["Penerapan dimensi pelayanan yang aman dalam rangka menjaga kerahasiaan data dan kepastian hukum pemohon.", "Tindakan memperlambat proses layanan demi mencari-cari kesalahan administratif pemohon izin pabean dinas.", "Upaya memeras pemohon izin kepabeanan agar bersedia membayar uang pelicin verifikasi dokumen kantor.", "Penerapan prosedur pengamanan fisik militer yang berlebihan dalam interaksi pelayanan sipil perbendaharaan dinas.", "Tindakan melarikan diri dari kewajiban melayani dengan cara bersembunyi di balik sistem peladen komputer."], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan, halaman 48", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 48</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Nilai Pelayanan pada Bab V Penutup menegaskan pelaksanaan pelayanan yang transparan, cepat, akurat, dan aman demi menjamin kepuasan pemangku kepentingan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Verifikasi tanda tangan elektronik dan enkripsi arsip digital merupakan implementasi konkret dari pilar pelayanan yang aman (secure service) dalam tata kelola kepabeanan modern. Langkah kehati-hatian ini memproteksi hak hukum pemohon dari bahaya pemalsuan identitas oleh pihak tidak berhak sekaligus membentengi sistem kementerian dari ancaman fraud siber. Definisi resmi nilai Pelayanan pada Bab V Penutup secara spesifik mencantumkan dimensi 'aman' berdampingan dengan cepat dan akurat. Dengan demikian, tindakan petugas pada opsi A secara tepat mengaplikasikan dimensi keamanan dalam nilai pelayanan.</p><p>Jawaban yang sesuai adalah <strong>A. Penerapan dimensi pelayanan yang aman dalam rangka menjaga kerahasiaan data dan kepastian hukum pemohon.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keliru karena verifikasi keaslian tanda tangan sah merupakan prosedur baku wajib, bukan mencari kesalahan.</li><li><strong>C</strong>: Keliru karena petugas menjalankan verifikasi sah tanpa memungut uang suap atau pungutan liar apa pun.</li><li><strong>D</strong>: Keliru karena enkripsi data digital adalah standar keamanan siber internasional pada birokrasi modern.</li><li><strong>E</strong>: Keliru karena pengelolaan basis data terenkripsi merupakan pelaksanaan tugas inti, bukan bentuk pelarian diri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 48.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 48", "difficulty": "HOTS • Evaluatif", "question": "Sebuah kantor pelayanan membanggakan diri mampu menyelesaikan permohonan izin dalam tempo lima menit, namun mengabaikan verifikasi bukti pembayaran pajak dan membagikan data identitas pemohon ke publik. Mengapa klaim keunggulan kantor tersebut KELIRU?", "options": ["Klaim kantor sangat terpuji karena kecepatan waktu lima menit merupakan satu-satunya ukuran keberhasilan layanan.", "Klaim kantor dapat dibenarkan sepanjang masyarakat merasa senang tidak perlu melampirkan berkas bukti pajak.", "Klaim kantor keliru karena nilai Pelayanan mewajibkan keterpaduan antara cepat, akurat, dan aman secara utuh.", "Klaim kantor mencerminkan inovasi revolusioner modern yang harus segera ditiru oleh seluruh kementerian negara.", "Klaim kantor sangat efisien karena satker berhasil menghemat anggaran belanja audit kepatuhan perbendaharaan."], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan, halaman 48", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 48</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Nilai Pelayanan menuntut kesatuan utuh: sepenuh hati, transparan, cepat, akurat, dan aman. Kecepatan tanpa akurasi dan rasa aman merupakan kecerobohan yang membahayakan negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Pelayanan prima tidak pernah boleh mereduksi kualitasnya semata-mata pada catatan waktu stopwatch (kecepatan semu) dengan mengorbankan akurasi verifikasi dan keamanan data hukum kenegaraan. Mengeluarkan izin tanpa pengujian bukti setor pajak menimbulkan kerugian penerimaan kas negara, sementara membocorkan identitas pemohon melanggar hak privasi konstitusional warga negara. Bab V Penutup menegaskan bahwa nilai Pelayanan mengintegrasikan unsur sepenuh hati, transparan, cepat, akurat, dan aman sebagai satu kesatuan yang tidak boleh dipisahkan. Maka dari itu, penilaian pada opsi C secara tepat membuktikan bahwa mengorbankan akurasi dan keamanan demi kecepatan semata adalah kekeliruan fatal.</p><p>Jawaban yang sesuai adalah <strong>C. Klaim kantor keliru karena nilai Pelayanan mewajibkan keterpaduan antara cepat, akurat, dan aman secara utuh.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena kecepatan yang menghasilkan kebocoran kas negara dan pembocoran rahasia adalah malapetaka.</li><li><strong>B</strong>: Keliru karena kepuasan semu masyarakat tidak boleh melanggar hukum kewajiban pembayaran pajak negara.</li><li><strong>D</strong>: Keliru karena pembiaran kebocoran data dan pengabaian bukti setor adalah tindak pidana korupsi maladministrasi.</li><li><strong>E</strong>: Keliru karena penghematan anggaran verifikasi tidak sebanding dengan hancurnya kepatuhan hukum perpajakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 48.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 48", "difficulty": "HOTS • Aplikasi", "question": "Ketika menyusun draf nota analisis kebijakan pengelolaan utang negara, seorang analis tidak hanya menyajikan angka realisasi tetapi juga menambahkan simulasi mitigasi risiko fluktuasi kurs mata uang asing. Tindakan analis tersebut merupakan aplikasi nilai Kesempurnaan dalam bentuk...", "options": ["Tindakan pamer kepandaian di hadapan pimpinan agar rekan kerja lain merasa rendah diri dan tertekan di kantor.", "Upaya membuang-buang anggaran kertas dinas kantor dengan mencetak grafik simulasi yang tidak berguna bagi pimpinan.", "Penerapan strategi manipulasi data keuangan agar kementerian terpaksa menambah pinjaman utang luar negeri baru.", "Tindakan mencuri data rahasia perbankan internasional tanpa hak guna diperjualbelikan kepada spekulan valas.", "Senantiasa berupaya memberikan kinerja terbaik yang bernilai tambah tinggi melampaui ekspektasi standar tugas."], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan, halaman 48", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 48</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Ringkasan Nilai Kesempurnaan pada Bab V Penutup: senantiasa melakukan upaya perbaikan di segala bidang untuk menjadi dan memberikan yang terbaik (memberikan kinerja bernilai tambah unggul).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Aparatur yang menjiwai nilai Kesempurnaan tidak pernah berpuas diri hanya dengan menyelesaikan pekerjaan pada batas minimal standar kelulusan (compliance mindset), melainkan selalu terdorong untuk memberikan nilai tambah (value added) tertinggi yang memperkuat mutu pengambilan kebijakan negara. Inisiatif menambahkan simulasi mitigasi fluktuasi kurs membekali pimpinan wawasan strategis yang mendalam untuk melindungi stabilitas fiskal dari risiko gejolak pasar global. Rumusan Bab V Penutup butir kelima secara eksplisit mengamanatkan aparatur untuk berupaya 'memberikan yang terbaik'. Dengan demikian, dedikasi analis pada opsi E adalah aplikasi nilai kesempurnaan yang paling paripurna.</p><p>Jawaban yang sesuai adalah <strong>E. Senantiasa berupaya memberikan kinerja terbaik yang bernilai tambah tinggi melampaui ekspektasi standar tugas.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena dedikasi keahlian teknis bertujuan menopang mutu kebijakan publik, bukan untuk menyombongkan diri.</li><li><strong>B</strong>: Keliru karena simulasi mitigasi kurs merupakan analisis strategis bernilai tinggi bagi perbendaharaan negara.</li><li><strong>C</strong>: Keliru karena analisis data objektif memitigasi risiko utang, bukan memanipulasi pinjaman luar negeri.</li><li><strong>D</strong>: Keliru karena analis menggunakan data resmi perbendaharaan yang sah tanpa melanggar hukum perbankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 48.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 48", "difficulty": "HOTS • Evaluatif", "question": "Sebuah satuan kerja pelayanan perbendaharaan berpuas diri dengan predikat unit kerja terbaik yang diraihnya tiga tahun lalu, sehingga menghentikan seluruh agenda inovasi dan pelatihan peningkatan kompetensi pegawai. Mengapa sikap satuan kerja tersebut KELIRU?", "options": ["Sikap satker sangat tepat karena predikat terbaik di masa lalu menjamin keunggulan abadi tanpa perlu belajar lagi.", "Sikap satker keliru karena nilai Kesempurnaan menuntut upaya perbaikan secara terus-menerus di segala bidang.", "Sikap satker dapat dibenarkan sepanjang piagam penghargaan masa lalu dipajang di ruang tamu kantor pelayanan.", "Sikap satker mencerminkan penghematan anggaran yang paling rasional dalam tatanan reformasi birokrasi modern.", "Sikap satker sangat bijaksana demi menjaga ketenangan batin pegawai agar tidak terganggu oleh dinamika perubahan."], "answer": 1, "source": "Nilai-Nilai Kementerian Keuangan, halaman 48", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 48</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Nilai Kesempurnaan Bab V Penutup: seluruh pegawai harus senantiasa melakukan upaya perbaikan di segala bidang untuk menjadi dan memberikan yang terbaik; tidak boleh terjebak dalam zona nyaman prestasi masa lalu.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Tantangan makroekonomi, tuntutan transparansi masyarakat, dan evolusi teknologi informasi bergerak maju secara eksponensial setiap hari. Satuan kerja yang berhenti berinovasi dan terlena oleh romantisme kejayaan masa lalu akan mengalami stagnasi kualitas, tertinggal oleh kemajuan zaman, dan pada akhirnya gagal menjawab kebutuhan riil pemangku kepentingan saat ini. Definisi nilai Kesempurnaan pada Bab V Penutup mengamanatkan aparatur untuk 'senantiasa' melakukan upaya perbaikan di segala bidang tiada henti. Oleh sebab itu, kritik evaluatif pada opsi B membuktikan secara tajam bahwa sikap berpuas diri tersebut adalah kekeliruan fatal.</p><p>Jawaban yang sesuai adalah <strong>B. Sikap satker keliru karena nilai Kesempurnaan menuntut upaya perbaikan secara terus-menerus di segala bidang.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena predikat masa lalu tidak pernah menjamin keunggulan masa kini tanpa adanya continuous improvement.</li><li><strong>C</strong>: Keliru karena pajangan piagam penghargaan masa lalu hanyalah ornamen nostalgia yang tidak memperbaiki sistem.</li><li><strong>D</strong>: Keliru karena menghentikan pelatihan kompetensi pegawai adalah kemunduran kapasitas SDM yang destruktif.</li><li><strong>E</strong>: Keliru karena zona nyaman aparatur yang menolak belajar bertentangan dengan etos insan pemelajar kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 48.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 48", "difficulty": "HOTS • Analitis", "question": "Manakah di antara pernyataan berikut yang BUKAN merupakan karakteristik pelaksanaan nilai Pelayanan sebagaimana dirumuskan pada Bab V Penutup materi modul?", "options": ["Pelayanan dilaksanakan dengan sepenuh hati yang memancarkan ketulusan nurani aparatur sipil negara pengabdi.", "Pelayanan dilaksanakan secara transparan sehingga prosedur dan persyaratan dapat diakses terbuka oleh publik.", "Pelayanan dilaksanakan dengan cepat dan akurat demi memenuhi kepuasan para pemangku kepentingan instansi dinas.", "Pelayanan dilaksanakan dengan mempersulit pemohon agar pemohon bersedia membayar upah uang lelah di bawah meja.", "Pelayanan dilaksanakan dengan aman guna menjamin kepastian hukum dan perlindungan hak pengguna layanan."], "answer": 3, "source": "Nilai-Nilai Kementerian Keuangan, halaman 48", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 48</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Karakteristik Pelayanan pada Bab V Penutup: memenuhi kepuasan pemangku kepentingan, dilaksanakan dengan sepenuh hati, transparan, cepat, akurat, dan aman. Mempersulit layanan dan meminta uang suap dilarang keras.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Kelima pilar pelaksanaan nilai Pelayanan (sepenuh hati, transparan, cepat, akurat, aman) dirancang secara terpadu untuk memberikan kemudahan, kebahagiaan, dan keadilan bagi seluruh warga negara pengguna layanan publik kementerian. Mempersulit alur prosedur dengan tujuan memeras uang pelicin di bawah meja merupakan tindak pidana korupsi maladministrasi yang mencoreng kehormatan birokrasi dan melanggar sumpah jabatan. Teks penutup halaman 48 secara gamblang menetapkan pilar keluhuran pelayanan prima yang bersih dari pungutan liar. Oleh sebab itu, klausul mempersulit demi uang lelah pada opsi D jelas merupakan perbuatan terlarang yang sama sekali bukan karakteristik nilai pelayanan.</p><p>Jawaban yang sesuai adalah <strong>D. Pelayanan dilaksanakan dengan mempersulit pemohon agar pemohon bersedia membayar upah uang lelah di bawah meja.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Karakteristik sah nilai pelayanan mengenai ketulusan sepenuh hati sesuai teks resmi Bab V Penutup.</li><li><strong>B</strong>: Karakteristik sah nilai pelayanan mengenai asas transparansi keterbukaan publik sesuai materi penutup.</li><li><strong>C</strong>: Karakteristik sah nilai pelayanan mengenai kecepatan dan akurasi pemenuhan kepuasan pemangku kepentingan.</li><li><strong>E</strong>: Karakteristik sah nilai pelayanan mengenai jaminan keamanan dan kepastian hukum sesuai teks penutup.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 48.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 48", "difficulty": "HOTS • Analitis", "question": "Bab V Penutup menempatkan nilai Pelayanan dan nilai Kesempurnaan sebagai dua pilar penutup dari rangkaian lima nilai kementerian. Bagaimanakah hubungan dialektis antara pemberian pelayanan terbaik dengan ikhtiar mencapai kesempurnaan institusi?", "options": ["Umpan balik dari pelayanan publik menjadi sumber inspirasi utama bagi ikhtiar continuous improvement menuju kesempurnaan.", "Pelayanan dan Kesempurnaan saling meniadakan di mana pegawai yang melayani publik dilarang berinovasi sistem kerja.", "Nilai Kesempurnaan mewajibkan kementerian menghentikan seluruh interaksi tatap muka dengan masyarakat pembayar pajak.", "Nilai Pelayanan hanya berlaku di tingkat kantor daerah, sedangkan Kesempurnaan hanya diwajibkan bagi menteri keuangan.", "Kedua nilai tersebut dirancang untuk mempermudah pegawai menerima gratifikasi perjalanan dinas dari pihak swasta luar."], "answer": 0, "source": "Nilai-Nilai Kementerian Keuangan, halaman 48", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 48</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Keterkaitan Pelayanan dan Kesempurnaan: Kepuasan pelayanan pemangku kepentingan merupakan muara tugas kementerian, sedangkan nilai Kesempurnaan menjadi mesin penggerak evaluasi dan inovasi perbaikan berkelanjutan atas mutu layanan tersebut.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Hubungan antara nilai Pelayanan dan nilai Kesempurnaan merupakan dialektika dinamis antara output kemanfaatan sosial dengan proses evolusi mutu kelembagaan. Interaksi pelayanan di garda terdepan menghasilkan umpan balik, masukan, dan evaluasi riil mengenai ekspektasi masyarakat pengguna layanan; data pengalaman pelayanan inilah yang kemudian diolah melalui semangat nilai Kesempurnaan untuk merekayasa perbaikan proses bisnis, menambal kelemahan sistem, dan menciptakan inovasi baru yang lebih canggih. Rangkuman Bab V Penutup secara harmonis menempatkan kedua nilai ini sebagai puncak transformasi birokrasi fiskal negara. Maka dari itu, opsi A menyajikan analisis hubungan dialektis kedua nilai secara paling tepat dan bernas.</p><p>Jawaban yang sesuai adalah <strong>A. Umpan balik dari pelayanan publik menjadi sumber inspirasi utama bagi ikhtiar continuous improvement menuju kesempurnaan.</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Keliru karena inovasi kesempurnaan justru didedikasikan secara langsung untuk melipatgandakan mutu pelayanan.</li><li><strong>C</strong>: Keliru karena nilai kesempurnaan memperkuat kualitas interaksi pelayanan publik, bukan menghentikannya.</li><li><strong>D</strong>: Keliru karena seluruh lima nilai kementerian mengikat seluruh jenjang hierarki pegawai tanpa pemilahan kasta.</li><li><strong>E</strong>: Keliru karena penerimaan gratifikasi perjalanan dinas swasta dilarang keras oleh hukum tindak pidana korupsi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 48.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 48", "difficulty": "HOTS • Kreasi", "question": "Rancanglah sebuah cetak biru standar pelayanan prima (Service Excellence Blueprint) pada kantor pelayanan terpadu yang memadukan kelima unsur nilai Pelayanan: sepenuh hati, transparan, cepat, akurat, dan aman!", "options": ["Petugas bersikap ketus -> persyaratan dirahasiakan -> waktu pelayanan 3 bulan -> data salah -> arsip dibiarkan tercecer.", "Menolak melayani warga miskin -> memungut tarif ganda -> melarang warga bertanya -> memalsukan kuitansi kasir.", "Sambut santun tulus (Sepenuh Hati), SOP terbuka (Transparan), SLA ketat (Cepat), validasi data (Akurat), enkripsi (Aman).", "Menutup gerbang loket layanan -> memasang robot penjawab rusak -> mengabaikan keluhan -> meliburkan seluruh staf.", "Menyerahkan seluruh wewenang verifikasi perizinan kepada calo komersial di luar pagar kantor tanpa pengawasan dinas."], "answer": 2, "source": "Nilai-Nilai Kementerian Keuangan, halaman 48", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 48</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Sintesis Cetak Biru Nilai Pelayanan Bab V Penutup: memadukan kehangatan budi nurani (sepenuh hati), kepastian informasi publik (transparan), standar waktu pasti (cepat), ketelitian pengujian (akurat), dan proteksi hukum data (aman).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Cetak biru pelayanan publik modern yang unggul mensyaratkan operasionalisasi kelima pilar nilai Pelayanan ke dalam setiap titik sentuh interaksi (service touchpoints) pengguna layanan. Penyambutan dengan kehangatan ramah dan peduli (sepenuh hati), pemajangan alur prosedur dan persyaratan yang jelas di papan informasi digital (transparan), penetapan janji waktu layanan (Service Level Agreement) yang presisi (cepat), pemeriksaan berkas berlapis tanpa cela (akurat), serta penyimpanan basis data terenkripsi dan berkepastian hukum (aman) menjamin kepuasan tertinggi pemangku kepentingan. Sintesis cetak biru pada opsi C menyajikan model tata kelola pelayanan prima yang paling paripurna dan teruji.</p><p>Jawaban yang sesuai adalah <strong>C. Sambut santun tulus (Sepenuh Hati), SOP terbuka (Transparan), SLA ketat (Cepat), validasi data (Akurat), enkripsi (Aman).</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena sikap ketus, kerahasiaan syarat, dan kesalahan data merupakan antitesis dari pelayanan prima.</li><li><strong>B</strong>: Keliru karena diskriminasi warga dan pemalsuan kuitansi merupakan kejahatan jabatan yang melanggar hukum.</li><li><strong>D</strong>: Keliru karena penutupan loket dan pengabaian keluhan menelantarkan hak konstitusional masyarakat pengguna layanan.</li><li><strong>E</strong>: Keliru karena menyerahkan wewenang dinas kepada calo komersial mencederai integritas institusi pemerintahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 48.</div></div>"}, {"category": "TSKKWK", "sub": "Nilai-Nilai Kemenkeu • Hlm. 48", "difficulty": "HOTS • Kreasi", "question": "Rancanglah sebuah ikrar manifesto pengamalan komprehensif lima Nilai Kementerian Keuangan (Integritas, Profesionalisme, Sinergi, Pelayanan, Kesempurnaan) sebagai pedoman hidup aparatur sipil negara pengelola fiskal!", "options": ["Saya berjanji memanfaatkan jabatan dinas untuk memperkaya keluarga pribadi dan menolak kerja sama dengan pihak lain.", "Saya berikrar bekerja lambat dan ceroboh demi menghindari tanggung jawab hukum perbendaharaan negara Republik Indonesia.", "Saya berjanji menyembunyikan seluruh informasi data fiskal negara dari menteri keuangan demi kepentingan kelompok politik.", "Saya berikrar memprioritaskan kepentingan kelompok sendiri di atas kedaulatan bangsa dan menolak inovasi perbaikan sistem.", "Berpikir benar (Integritas), tuntas akurat (Profesional), bermitra harmonis (Sinergi), melayani prima (Pelayanan), tiada henti berbenah (Kesempurnaan)."], "answer": 4, "source": "Nilai-Nilai Kementerian Keuangan, halaman 48", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Rangkuman Belajar • Halaman 48</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep inti</div><p>Kesimpulan Akhir Bab V Penutup: Lima Nilai-Nilai Kementerian Keuangan (Integritas, Profesionalisme, Sinergi, Pelayanan, Kesempurnaan) merupakan pedoman moral, cara berpikir, bersikap, dan bertindak yang utuh dan tak terpisahkan dalam mengawal kedaulatan keuangan negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Mengapa jawaban ini benar?</div><p>Manifesto moral aparatur kementerian merupakan kristalisasi janji suci pengabdian yang merangkum kelima nilai luhur Kementerian Keuangan ke dalam satu komitmen integritas hidup yang kokoh. Menjaga keselarasan pikiran dan tindakan yang benar (Integritas), menuntaskan pekerjaan secara akurat berbasis keahlian terbaik (Profesionalisme), merajut kerja sama harmonis tanpa sekat silo (Sinergi), mempersembahkan layanan tulus berkepastian hukum (Pelayanan), serta senantiasa terdorong melakukan perbaikan tiada henti (Kesempurnaan) membentengi aparatur dari segala godaan penyimpangan sekaligus mempersembahkan pengabdian terbaik bagi kejayaan nusa dan bangsa. Sintesis manifesto pada opsi E menyajikan rangkuman filosofis penutup modul yang paling agung dan sempurna.</p><p>Jawaban yang sesuai adalah <strong>E. Berpikir benar (Integritas), tuntas akurat (Profesional), bermitra harmonis (Sinergi), melayani prima (Pelayanan), tiada henti berbenah (Kesempurnaan).</strong>.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Cara membedakan pilihan</div><p><strong>Pembeda dan Catatan Pengecoh:</strong></p><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keliru karena janji memperkaya diri sendiri merupakan ikrar kejahatan korupsi yang mengkhianati amanah negara.</li><li><strong>B</strong>: Keliru karena bekerja lambat dan ceroboh mencerminkan mentalitas pemalas yang merusak perbendaharaan negara.</li><li><strong>C</strong>: Keliru karena penyembunyian data dari menteri keuangan melanggar asas loyalitas kedinasan dan hukum tata negara.</li><li><strong>D</strong>: Keliru karena mementingkan kelompok sendiri di atas bangsa adalah pengkhianatan terhadap sumpah jabatan PNS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Nilai-Nilai Kementerian Keuangan • halaman 48.</div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=30*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_kemenkeu_p05_review_annot_v1_user_<?= (int)$USER->id ?>';
let reviewAnnoStore={};
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
   raServerReady=true;
   // Gambar coretan yang sudah tersimpan SECARA OTOMATIS, tanpa menekan
   // tombol Coret/Anotasi. Canvas tetap pasif agar halaman mudah discroll.
   raRestoreAnnotations();
 }catch(e){console.error('Anotasi server:',e);alert('Anotasi belum dapat dimuat dari server. Coba buka ulang pembahasan.');}
}
async function raSendSave(i){
 if(!raServerReady)return;
 const b=new URLSearchParams();b.set('action','anno_save');b.set('sesskey',MOODLE_SESSKEY);
 b.set('question',String(i));b.set('strokesjson',JSON.stringify(raStrokes(i)));
 try {const r=await fetch(LEADERBOARD_ENDPOINT,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:b.toString()});
 const d=await r.json();if(!r.ok||!d.success)throw new Error(d.message||'Gagal menyimpan');}
 catch(e){console.error('Gagal menyimpan anotasi nomor',i+1,e);alert('Coretan nomor '+(i+1)+' belum tersimpan. Periksa koneksi dan coba coret lagi.');}
}
function raSave(i){clearTimeout(raSaveTimers[i]);raSaveTimers[i]=setTimeout(()=>{delete raSaveTimers[i];raSavePending[i]=raSendSave(i).finally(()=>{delete raSavePending[i];});},350);}

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
 try{localStorage.setItem('stg_nk_p05_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_nk_p05_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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
        <td>${row.correct}/40</td>
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Hlm. 7–16";
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
  const ok=confirm('⚠️ Retake Quiz?\n\nSeluruh anotasi pada pembahasan percobaan sebelumnya akan dihapus dari akun Stangolden dan semua perangkat. Jawaban terakhir tetap tersimpan sampai percobaan baru dikumpulkan.\n\nLanjutkan ulangi ujian?');
  if(!ok)return;
  Object.keys(raSaveTimers).forEach(k=>{clearTimeout(raSaveTimers[k]);delete raSaveTimers[k];});
  await Promise.all(Object.keys(raSavePending).map(k=>raSavePending[k]));
  try {
    const b=new URLSearchParams();b.set('action','anno_clear');b.set('sesskey',MOODLE_SESSKEY);
    const r=await fetch(LEADERBOARD_ENDPOINT,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:b.toString()});
    const d=await r.json();if(!r.ok||!d.success)throw new Error(d.message||'Gagal menghapus anotasi');
    reviewAnnoStore={};window.location.reload();
  }catch(e){alert('Coretan belum berhasil dihapus dari server. Retake dibatalkan agar catatan tidak hilang sebagian. '+e.message);}
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
   : `<div class="lb-note" style="margin:0 0 16px">🔒 Kamu dapat membuka <b>3 pembahasan pertama</b>. Nomor 4–40 khusus peserta Starbuster 2026.</div>`;

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
       <div class="break tpa"><strong>📗 Hlm. 7–11</strong><div style="font-size:26px;font-weight:900">${tpa.c}/${tpa.t}</div></div>
       <div class="break tsk"><strong>📘 Hlm. 12–16</strong><div style="font-size:26px;font-weight:900">${tsk.c}/${tsk.t}</div></div>
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

</script></body></html>
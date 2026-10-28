<?php
/**
 * STANGOLDEN.exam — Etika PNS Halaman 4–13
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
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Akses Terbatas — STANGOLDEN</title><style>body{font-family:system-ui,sans-serif;background:#f2eeff;min-height:100vh;margin:0;display:grid;place-items:center;color:#17131f}.card{background:white;border:3px solid #17131f;border-radius:18px;box-shadow:6px 6px 0 #17131f;padding:30px;max-width:500px;margin:20px;text-align:center}a{display:inline-block;background:#c7ff42;color:#17131f;padding:12px 18px;border:2px solid #17131f;border-radius:10px;font-weight:800;text-decoration:none}</style></head><body><div class="card"><h1>🔒 Akses Terbatas</h1><p>Materi latihan ini hanya tersedia untuk peserta yang terdaftar pada program persiapan UPKP Kemenkeu 2026 di Stangolden.</p><a href="' . $dashboardUrl . '">Kembali ke Dashboard</a></div></body></html>';
    exit;
}

// Database Moodle: elearning; prefix fisik mdlhv_ diambil otomatis dari config.php.
// $DB->get_record("stgep413_rank") => tabel fisik "mdlhv_stgep413_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgep413_rank';
const STG_REVIEW_T = 'stgep413_review';
const STG_ANNO_T = 'stgep413_anno';
const STG_KEY = [4,2,2,4,4,0,0,1,1,3,2,3,2,1,4,2,1,0,3,1,0,0,3,2,4,3,1,1,1,0,1,4,1,2,1,4,3,4,0,4,2,0,2,2,2,2,3,0,1,3,3,1,4,1,4,2,2,3,1,2,4,0,4,1,0,0,0,1,3,2,4,0,2,3,4,0,4,3,1,0,0,3,2,0,2,0,1,4,1,2,3,2,2,4,0,2,0,4,4,1];
// Saat submit pertama, tabel yang belum ada disiapkan otomatis.
// Halaman biasa tidak menjalankan CREATE TABLE.
function stgnk_table(string $name, bool $review): void {
    global $DB, $CFG;
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
            error_log('STANGOLDEN EP413 SETUP: ' . $e->getMessage());
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
            // Auto-create HANYA saat peserta submit; tidak perlu SQL/manual setup.
            // Semua nama tabel tanpa prefix: Moodle menambahkan mdlhv_ otomatis.
            stgnk_create_table(STG_LB_T, false);
            stgnk_create_table(STG_REVIEW_T, true);
            stgnk_create_annotation_table();
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
            if (!$DB->get_manager()->table_exists(new xmldb_table(STG_ANNO_T))) {
                stgnk_json(['success'=>true,'annotations'=>new stdClass()]);
            }
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
            if (!$DB->get_manager()->table_exists(new xmldb_table(STG_LB_T))) {
                stgnk_json(['success'=>true,'leaderboard'=>[]]);
            }
            $records=$DB->get_records(STG_LB_T,null,'score DESC, timetaken ASC, timecreated ASC');
            $rows=[];$rank=0;
            foreach($records as $r){$rank++;$rows[]=['rank'=>$rank,'username'=>$r->username,'score'=>(int)$r->score,'correct'=>(int)$r->correct,'timetaken'=>(int)$r->timetaken,'isme'=>((int)$r->userid===(int)$USER->id)];}
            stgnk_json(['success'=>true,'leaderboard'=>$rows]);
        }
        if($stgaction==='last_review') {
            if (!$DB->get_manager()->table_exists(new xmldb_table(STG_REVIEW_T))) {
                stgnk_json(['success'=>false,'message'=>'Belum ada jawaban terakhir.'],404);
            }
            $r=$DB->get_record(STG_REVIEW_T,['userid'=>(int)$USER->id]);
            if(!$r) stgnk_json(['success'=>false,'message'=>'Belum ada jawaban terakhir.'],404);
            $a=json_decode($r->answersjson,true);
            if(!is_array($a)||count($a)!==100) stgnk_json(['success'=>false,'message'=>'Data jawaban tidak valid.'],500);
            stgnk_json(['success'=>true,'review'=>['answers'=>array_map(static function($v) { return ((int)$v < 0) ? null : (int)$v; }, $a),'score'=>(int)$r->score,'correct'=>(int)$r->correct,'timetaken'=>(int)$r->timetaken]]);
        }
        stgnk_json(['success'=>false,'message'=>'Aksi tidak dikenal.'],400);
    }catch(Throwable $e){error_log('STANGOLDEN EP413: '.$e->getMessage());stgnk_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Etika PNS Hlm. 4–13 — STANGOLDEN</title>
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
      Latihan Etika PNS
      <span class="series-tag">Hlm. 4–13</span>
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
<h1>Latihan Etika PNS <span class="series-tag">Hlm. 4–13</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Hlm. 4–13</h2>
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
const STG_LOCAL_KEY="stg_ep_0413_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Konsep","question":"Etika dalam penyelenggaraan tugas PNS berfungsi sebagai fondasi pembentukan tiga kualitas utama, yaitu ....","options":["Kecakapan, kepatutan, dan pelayanan","Kompetensi, disiplin, dan akuntabilitas","Tanggung jawab, keterbukaan, dan loyalitas","Integritas, keteladanan, dan keadilan","Karakter, integritas, dan profesionalisme"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 4","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Tiga unsur yang disebut secara eksplisit dalam pengantar ialah karakter, integritas, dan profesionalisme; opsi lain memakai konsep etis yang relevan tetapi mengganti satu atau lebih unsur.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Karakter, integritas, dan profesionalisme</mark>.</strong> Tiga unsur yang disebut secara eksplisit dalam pengantar ialah karakter, integritas, dan profesionalisme; opsi lain memakai konsep etis yang relevan tetapi mengganti satu atau lebih unsur.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Kecakapan, kepatutan, dan pelayanan: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Karakter, integritas, dan profesionalisme</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Kompetensi, disiplin, dan akuntabilitas: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Karakter, integritas, dan profesionalisme</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Tanggung jawab, keterbukaan, dan loyalitas: menyasar kesetiaan dan kepentingan negara. Fokus tersebut berbeda dari <strong>Karakter, integritas, dan profesionalisme</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Integritas, keteladanan, dan keadilan: menekankan integritas, independensi, dan pencegahan penyalahgunaan kewenangan. Fokus tersebut berbeda dari <strong>Karakter, integritas, dan profesionalisme</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Tiga unsur yang disebut secara eksplisit dalam pengantar ialah karakter, integritas, dan profesionalisme; opsi lain memakai konsep etis yang relevan tetapi mengganti satu atau lebih unsur.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 4.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Pernyataan mana yang paling tepat membedakan tuntutan kinerja PNS dari landasan etikanya?","options":["Efektivitas dan efisiensi perlu disertai nilai moral, hukum, dan kepatutan","Efektivitas dan efisiensi dipadukan dengan pengembangan kompetensi kerja","Efektivitas dan efisiensi terutama dinilai melalui keteladanan pimpinan","Efektivitas dan efisiensi mensyaratkan kerja sama lintas unit organisasi","Efektivitas dan efisiensi terutama dijamin melalui pengawasan internal"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 4","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Pengantar menghubungkan efektivitas-efisiensi dengan tiga landasan perilaku: moral, hukum, kepatutan. Pilihan lain menyajikan faktor organisasi yang dapat bermanfaat tetapi bukan triad landasan yang dimaksud.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Efektivitas dan efisiensi perlu disertai nilai moral, hukum, dan kepatutan</mark>.</strong> Pengantar menghubungkan efektivitas-efisiensi dengan tiga landasan perilaku: moral, hukum, kepatutan. Pilihan lain menyajikan faktor organisasi yang dapat bermanfaat tetapi bukan triad landasan yang dimaksud.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Efektivitas dan efisiensi dipadukan dengan pengembangan kompetensi kerja: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Efektivitas dan efisiensi perlu disertai nilai moral, hukum, dan kepatutan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Efektivitas dan efisiensi terutama dinilai melalui keteladanan pimpinan: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Efektivitas dan efisiensi perlu disertai nilai moral, hukum, dan kepatutan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Efektivitas dan efisiensi mensyaratkan kerja sama lintas unit organisasi: menekankan kerja sama dan koordinasi. Fokus tersebut berbeda dari <strong>Efektivitas dan efisiensi perlu disertai nilai moral, hukum, dan kepatutan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Efektivitas dan efisiensi terutama dijamin melalui pengawasan internal: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Efektivitas dan efisiensi perlu disertai nilai moral, hukum, dan kepatutan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Pengantar menghubungkan efektivitas-efisiensi dengan tiga landasan perilaku: moral, hukum, kepatutan. Pilihan lain menyajikan faktor organisasi yang dapat bermanfaat tetapi bukan triad landasan yang dimaksud.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 4.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Ketentuan","question":"Siapa yang menjadi rujukan penyebutan tujuh prinsip moral PNS untuk pelayanan prima?","options":["Peraturan Pemerintah Nomor 11 Tahun 2017","Peraturan Pemerintah Nomor 42 Tahun 2004","Undang-Undang Nomor 20 Tahun 2023","A. Sonny Keraf (2002)","Magnis-Suseno (2002)"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 4","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Pengelompokan tujuh prinsip moral dalam pembahasan dikaitkan dengan Dr. A. Sonny Keraf tahun 2002, bukan dengan tokoh atau regulasi lain dalam pilihan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">A. Sonny Keraf (2002)</mark>.</strong> Pengelompokan tujuh prinsip moral dalam pembahasan dikaitkan dengan Dr. A. Sonny Keraf tahun 2002, bukan dengan tokoh atau regulasi lain dalam pilihan.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Peraturan Pemerintah Nomor 11 Tahun 2017: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>A. Sonny Keraf (2002)</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Peraturan Pemerintah Nomor 42 Tahun 2004: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>A. Sonny Keraf (2002)</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Undang-Undang Nomor 20 Tahun 2023: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>A. Sonny Keraf (2002)</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Magnis-Suseno (2002): menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>A. Sonny Keraf (2002)</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Pengelompokan tujuh prinsip moral dalam pembahasan dikaitkan dengan Dr. A. Sonny Keraf tahun 2002, bukan dengan tokoh atau regulasi lain dalam pilihan.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 4.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Konsep","question":"Makna profesionalisme Keraf yang berkaitan langsung dengan pelaksanaan pekerjaan adalah ....","options":["Menempatkan kepentingan masyarakat di atas kepentingan pribadi","Bertindak sesuai kemampuan, keahlian, dan ketentuan yang berlaku","Memastikan sikap pribadi tidak dapat dikendalikan pihak lain","Memperlakukan masyarakat secara adil tanpa pembedaan","Menyatakan yang benar sebagai benar dan yang salah sebagai salah"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 4","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Profesionalisme menuntut kecocokan antara pekerjaan, kemampuan/keahlian, serta ketentuan perundang-undangan. Empat opsi lain merujuk pada integritas moral, keadilan, kepentingan publik, dan kebenaran-kejujuran.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Bertindak sesuai kemampuan, keahlian, dan ketentuan yang berlaku</mark>.</strong> Profesionalisme menuntut kecocokan antara pekerjaan, kemampuan/keahlian, serta ketentuan perundang-undangan. Empat opsi lain merujuk pada integritas moral, keadilan, kepentingan publik, dan kebenaran-kejujuran.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Menempatkan kepentingan masyarakat di atas kepentingan pribadi: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Bertindak sesuai kemampuan, keahlian, dan ketentuan yang berlaku</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Memastikan sikap pribadi tidak dapat dikendalikan pihak lain: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Bertindak sesuai kemampuan, keahlian, dan ketentuan yang berlaku</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Memperlakukan masyarakat secara adil tanpa pembedaan: berkaitan dengan keadilan serta perlakuan setara. Fokus tersebut berbeda dari <strong>Bertindak sesuai kemampuan, keahlian, dan ketentuan yang berlaku</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Menyatakan yang benar sebagai benar dan yang salah sebagai salah: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Bertindak sesuai kemampuan, keahlian, dan ketentuan yang berlaku</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Profesionalisme menuntut kecocokan antara pekerjaan, kemampuan/keahlian, serta ketentuan perundang-undangan. Empat opsi lain merujuk pada integritas moral, keadilan, kepentingan publik, dan kebenaran-kejujuran.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 4.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Aplikasi","question":"Dua prinsip yang dapat dibedakan melalui fokus 'kompetensi pelaksanaan' versus 'ketahanan dari intervensi penyalahgunaan wewenang' adalah ....","options":["Integritas moral dan larangan menghalalkan cara","Profesionalisme dan berpihak kepada kejujuran","Keadilan dan tanggung jawab kepentingan publik","Profesionalisme dan integritas moral yang tinggi","Tanggung jawab kepentingan publik dan keadilan"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 4","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Kompetensi dan konsistensi menjalankan profesi merupakan unsur profesionalisme, sedangkan tidak dikendalikan pihak lain untuk menyalahgunakan kewenangan merupakan unsur integritas moral.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Profesionalisme dan integritas moral yang tinggi</mark>.</strong> Kompetensi dan konsistensi menjalankan profesi merupakan unsur profesionalisme, sedangkan tidak dikendalikan pihak lain untuk menyalahgunakan kewenangan merupakan unsur integritas moral.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Integritas moral dan larangan menghalalkan cara: menekankan integritas, independensi, dan pencegahan penyalahgunaan kewenangan. Fokus tersebut berbeda dari <strong>Profesionalisme dan integritas moral yang tinggi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Profesionalisme dan berpihak kepada kejujuran: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Profesionalisme dan integritas moral yang tinggi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Keadilan dan tanggung jawab kepentingan publik: berkaitan dengan keadilan serta perlakuan setara. Fokus tersebut berbeda dari <strong>Profesionalisme dan integritas moral yang tinggi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Tanggung jawab kepentingan publik dan keadilan: berkaitan dengan keadilan serta perlakuan setara. Fokus tersebut berbeda dari <strong>Profesionalisme dan integritas moral yang tinggi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Kompetensi dan konsistensi menjalankan profesi merupakan unsur profesionalisme, sedangkan tidak dikendalikan pihak lain untuk menyalahgunakan kewenangan merupakan unsur integritas moral.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 4.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Konsep","question":"Komitmen moral dalam prinsip profesionalisme secara khusus diarahkan untuk ....","options":["Memastikan setiap pemohon menerima perlakuan setara","Membela kepentingan publik saat menjalankan profesi","Mencegah penyimpangan tujuan dalam proses pelayanan","Menjaga nama baik jabatan dari campur tangan pihak lain","Menjunjung ketepatan fakta dalam setiap keterangan"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 4","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Keraf memasukkan komitmen moral membela kepentingan publik sebagai bagian profesionalisme. Empat alternatif menunjukkan karakter prinsip moral lain, bukan penegasan komitmen dalam butir profesionalisme.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Membela kepentingan publik saat menjalankan profesi</mark>.</strong> Keraf memasukkan komitmen moral membela kepentingan publik sebagai bagian profesionalisme. Empat alternatif menunjukkan karakter prinsip moral lain, bukan penegasan komitmen dalam butir profesionalisme.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Memastikan setiap pemohon menerima perlakuan setara: berkaitan dengan keadilan serta perlakuan setara. Fokus tersebut berbeda dari <strong>Membela kepentingan publik saat menjalankan profesi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Mencegah penyimpangan tujuan dalam proses pelayanan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Membela kepentingan publik saat menjalankan profesi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Menjaga nama baik jabatan dari campur tangan pihak lain: menekankan integritas, independensi, dan pencegahan penyalahgunaan kewenangan. Fokus tersebut berbeda dari <strong>Membela kepentingan publik saat menjalankan profesi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Menjunjung ketepatan fakta dalam setiap keterangan: menekankan kebenaran fakta dan kejujuran keterangan. Fokus tersebut berbeda dari <strong>Membela kepentingan publik saat menjalankan profesi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Keraf memasukkan komitmen moral membela kepentingan publik sebagai bagian profesionalisme. Empat alternatif menunjukkan karakter prinsip moral lain, bukan penegasan komitmen dalam butir profesionalisme.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 4.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Aplikasi","question":"Seorang pejabat menguasai keahlian teknis, tetapi selalu berubah-ubah menjalankan standar profesinya. Unsur profesionalisme mana yang paling jelas belum terpenuhi?","options":["Kebenaran keterangan saat menjelaskan hasil layanan","Konsistensi dan konsekuensi dalam menjalankan profesi","Kesetaraan perlakuan kepada seluruh penerima layanan","Pengutamaan kepentingan umum atas ambisi kedudukan","Keteguhan menolak kendali pihak lain atas kewenangan"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 4","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Butir profesionalisme mencakup kewajiban konsekuen dan konsisten dalam profesi pilihan. Soal tidak menyebut kendali pihak lain, diskriminasi, penyimpangan fakta, atau ambisi jabatan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Konsistensi dan konsekuensi dalam menjalankan profesi</mark>.</strong> Butir profesionalisme mencakup kewajiban konsekuen dan konsisten dalam profesi pilihan. Soal tidak menyebut kendali pihak lain, diskriminasi, penyimpangan fakta, atau ambisi jabatan.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Kebenaran keterangan saat menjelaskan hasil layanan: menekankan kebenaran fakta dan kejujuran keterangan. Fokus tersebut berbeda dari <strong>Konsistensi dan konsekuensi dalam menjalankan profesi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Kesetaraan perlakuan kepada seluruh penerima layanan: berkaitan dengan keadilan serta perlakuan setara. Fokus tersebut berbeda dari <strong>Konsistensi dan konsekuensi dalam menjalankan profesi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Pengutamaan kepentingan umum atas ambisi kedudukan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Konsistensi dan konsekuensi dalam menjalankan profesi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Keteguhan menolak kendali pihak lain atas kewenangan: menekankan integritas, independensi, dan pencegahan penyalahgunaan kewenangan. Fokus tersebut berbeda dari <strong>Konsistensi dan konsekuensi dalam menjalankan profesi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Butir profesionalisme mencakup kewajiban konsekuen dan konsisten dalam profesi pilihan. Soal tidak menyebut kendali pihak lain, diskriminasi, penyimpangan fakta, atau ambisi jabatan.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 4.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Dalam penjelasan profesionalisme, keputusan seseorang memilih karier birokrasi mengandung konsekuensi untuk ....","options":["Tetap menjalankan profesi secara konsisten beserta konsekuensinya","Memusatkan tugas pada pemenuhan kepentingan organisasi profesi","Mengutamakan hasil pelayanan walaupun prosedurnya perlu ditafsir ulang","Menyeragamkan tingkat penghasilan seluruh pekerjaan pemerintah","Menjaga setiap kebijakan pimpinan agar tidak berubah dalam pelaksanaan"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 4","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Penjelasan profesionalisme meminta konsistensi menjalani profesi beserta konsekuensinya, termasuk tingkat penghasilan yang relatif tidak besar. Opsi lain mengalihkan konsekuensi pilihan profesi ke ranah lain.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Tetap menjalankan profesi secara konsisten beserta konsekuensinya</mark>.</strong> Penjelasan profesionalisme meminta konsistensi menjalani profesi beserta konsekuensinya, termasuk tingkat penghasilan yang relatif tidak besar. Opsi lain mengalihkan konsekuensi pilihan profesi ke ranah lain.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Memusatkan tugas pada pemenuhan kepentingan organisasi profesi: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Tetap menjalankan profesi secara konsisten beserta konsekuensinya</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Mengutamakan hasil pelayanan walaupun prosedurnya perlu ditafsir ulang: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Tetap menjalankan profesi secara konsisten beserta konsekuensinya</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Menyeragamkan tingkat penghasilan seluruh pekerjaan pemerintah: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Tetap menjalankan profesi secara konsisten beserta konsekuensinya</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Menjaga setiap kebijakan pimpinan agar tidak berubah dalam pelaksanaan: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Tetap menjalankan profesi secara konsisten beserta konsekuensinya</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Penjelasan profesionalisme meminta konsistensi menjalani profesi beserta konsekuensinya, termasuk tingkat penghasilan yang relatif tidak besar. Opsi lain mengalihkan konsekuensi pilihan profesi ke ranah lain.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 4.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Konsep","question":"Perbedaan utama profesionalisme dan integritas moral dalam rumusan Keraf adalah ....","options":["Profesionalisme menekankan kepentingan umum; integritas menekankan daya juang pribadi","Profesionalisme menekankan kecakapan-konsistensi; integritas menolak penyalahgunaan kuasa","Profesionalisme menekankan loyalitas negara; integritas mengutamakan kerja sama unit","Profesionalisme menekankan prosedur adil; integritas menekankan urutan antrean","Profesionalisme menekankan perlakuan adil; integritas mewajibkan ketepatan layanan"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 4","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Kedua prinsip sama-sama etis, namun unsur pembeda yang dinyatakan di halaman 4 adalah profesional sesuai kemampuan dan konsisten, sedangkan integritas moral menjaga prinsip dan tak tunduk pada penyalahgunaan wewenang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Profesionalisme menekankan kecakapan-konsistensi; integritas menolak penyalahgunaan kuasa</mark>.</strong> Kedua prinsip sama-sama etis, namun unsur pembeda yang dinyatakan di halaman 4 adalah profesional sesuai kemampuan dan konsisten, sedangkan integritas moral menjaga prinsip dan tak tunduk pada penyalahgunaan wewenang.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Profesionalisme menekankan kepentingan umum; integritas menekankan daya juang pribadi: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Profesionalisme menekankan kecakapan-konsistensi; integritas menolak penyalahgunaan kuasa</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Profesionalisme menekankan loyalitas negara; integritas mengutamakan kerja sama unit: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Profesionalisme menekankan kecakapan-konsistensi; integritas menolak penyalahgunaan kuasa</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Profesionalisme menekankan prosedur adil; integritas menekankan urutan antrean: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Profesionalisme menekankan kecakapan-konsistensi; integritas menolak penyalahgunaan kuasa</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Profesionalisme menekankan perlakuan adil; integritas mewajibkan ketepatan layanan: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Profesionalisme menekankan kecakapan-konsistensi; integritas menolak penyalahgunaan kuasa</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Kedua prinsip sama-sama etis, namun unsur pembeda yang dinyatakan di halaman 4 adalah profesional sesuai kemampuan dan konsisten, sedangkan integritas moral menjaga prinsip dan tak tunduk pada penyalahgunaan wewenang.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 4.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Aplikasi","question":"Ada tekanan dari pihak luar agar pejabat memakai kewenangan untuk menguntungkan suatu kelompok. Prinsip Keraf yang paling spesifik terancam ialah ....","options":["Bertindak secara adil","Tanggung jawab terhadap kepentingan publik","Integritas moral yang tinggi","Berpihak kepada kebenaran dan kejujuran","Profesionalisme"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 4","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Integritas moral secara spesifik melarang pejabat dikendalikan pihak lain untuk menyalahgunakan kuasa. Prinsip lain juga bernilai positif tetapi tidak menekankan sumber tekanan dan penyalahgunaan kuasa sebagaimana skenario.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Integritas moral yang tinggi</mark>.</strong> Integritas moral secara spesifik melarang pejabat dikendalikan pihak lain untuk menyalahgunakan kuasa. Prinsip lain juga bernilai positif tetapi tidak menekankan sumber tekanan dan penyalahgunaan kuasa sebagaimana skenario.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Bertindak secara adil: berkaitan dengan keadilan serta perlakuan setara. Fokus tersebut berbeda dari <strong>Integritas moral yang tinggi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Tanggung jawab terhadap kepentingan publik: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Integritas moral yang tinggi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Berpihak kepada kebenaran dan kejujuran: menekankan kebenaran fakta dan kejujuran keterangan. Fokus tersebut berbeda dari <strong>Integritas moral yang tinggi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Profesionalisme: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Integritas moral yang tinggi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Integritas moral secara spesifik melarang pejabat dikendalikan pihak lain untuk menyalahgunakan kuasa. Prinsip lain juga bernilai positif tetapi tidak menekankan sumber tekanan dan penyalahgunaan kuasa sebagaimana skenario.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 4.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Konsep","question":"Kedudukan kepentingan publik dalam prinsip tanggung jawab Keraf dirumuskan sebagai ....","options":["Nilai tertinggi yang tidak digantikan uang atau jabatan","Pedoman yang terutama digunakan saat ada konflik antarrekan kerja","Tujuan kerja yang harus diseimbangkan dengan pencapaian karier","Prinsip pelayanan yang mengikuti preferensi tiap penerima layanan","Ukuran keberhasilan yang disesuaikan dengan kompensasi pekerjaan"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 5","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Halaman 5 menegaskan kepentingan publik tidak dapat dikalahkan uang atau jabatan. Alternatif lain mengubah prioritas absolut itu menjadi ukuran karier, preferensi, atau relasi internal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Nilai tertinggi yang tidak digantikan uang atau jabatan</mark>.</strong> Halaman 5 menegaskan kepentingan publik tidak dapat dikalahkan uang atau jabatan. Alternatif lain mengubah prioritas absolut itu menjadi ukuran karier, preferensi, atau relasi internal.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Pedoman yang terutama digunakan saat ada konflik antarrekan kerja: membahas pengaruh lingkungan dan etos kerja. Fokus tersebut berbeda dari <strong>Nilai tertinggi yang tidak digantikan uang atau jabatan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Tujuan kerja yang harus diseimbangkan dengan pencapaian karier: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Nilai tertinggi yang tidak digantikan uang atau jabatan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Prinsip pelayanan yang mengikuti preferensi tiap penerima layanan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Nilai tertinggi yang tidak digantikan uang atau jabatan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Ukuran keberhasilan yang disesuaikan dengan kompensasi pekerjaan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Nilai tertinggi yang tidak digantikan uang atau jabatan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Halaman 5 menegaskan kepentingan publik tidak dapat dikalahkan uang atau jabatan. Alternatif lain mengubah prioritas absolut itu menjadi ukuran karier, preferensi, atau relasi internal.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 5.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Konsep","question":"Pembeda 'tanggung jawab terhadap kepentingan publik' dari 'bertindak adil' adalah ....","options":["Yang pertama mewajibkan keterbukaan; yang kedua menetapkan sanksi moral","Yang pertama menjaga informasi; yang kedua mengembangkan kreativitas","Yang pertama memprioritaskan pengabdian; yang kedua menolak perlakuan istimewa","Yang pertama mengawasi kode etik; yang kedua menjaga kepentingan bangsa","Yang pertama menolak intervensi; yang kedua mengembangkan kompetensi"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 5","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Tanggung jawab publik menempatkan pengabdian sebagai panggilan profesi; prinsip keadilan menuntut perlakuan setara dan menolak keistimewaan tidak sah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Yang pertama memprioritaskan pengabdian; yang kedua menolak perlakuan istimewa</mark>.</strong> Tanggung jawab publik menempatkan pengabdian sebagai panggilan profesi; prinsip keadilan menuntut perlakuan setara dan menolak keistimewaan tidak sah.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Yang pertama mewajibkan keterbukaan; yang kedua menetapkan sanksi moral: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Yang pertama memprioritaskan pengabdian; yang kedua menolak perlakuan istimewa</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Yang pertama menjaga informasi; yang kedua mengembangkan kreativitas: menitikberatkan pembaruan atau perbaikan berkelanjutan. Fokus tersebut berbeda dari <strong>Yang pertama memprioritaskan pengabdian; yang kedua menolak perlakuan istimewa</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Yang pertama mengawasi kode etik; yang kedua menjaga kepentingan bangsa: menyasar kesetiaan dan kepentingan negara. Fokus tersebut berbeda dari <strong>Yang pertama memprioritaskan pengabdian; yang kedua menolak perlakuan istimewa</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Yang pertama menolak intervensi; yang kedua mengembangkan kompetensi: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Yang pertama memprioritaskan pengabdian; yang kedua menolak perlakuan istimewa</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Tanggung jawab publik menempatkan pengabdian sebagai panggilan profesi; prinsip keadilan menuntut perlakuan setara dan menolak keistimewaan tidak sah.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 5.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Konsep","question":"Prinsip keberpihakan pada kebenaran dan kejujuran dijelaskan terutama melalui ....","options":["Penggunaan urutan kedatangan untuk seluruh permohonan","Penggunaan keahlian sesuai ketentuan profesi birokrasi","Penolakan pengaruh luar terhadap penggunaan kewenangan","Penempatan kepentingan masyarakat di atas peluang jabatan","Pembedaan yang tegas antara tindakan benar dan salah"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 5","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Rumusan &#x27;yang salah adalah salah, yang benar adalah benar&#x27; merupakan inti kebenaran dan kejujuran; opsi lain menguji empat prinsip tetangga yang nyata.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Pembedaan yang tegas antara tindakan benar dan salah</mark>.</strong> Rumusan &#x27;yang salah adalah salah, yang benar adalah benar&#x27; merupakan inti kebenaran dan kejujuran; opsi lain menguji empat prinsip tetangga yang nyata.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Penggunaan urutan kedatangan untuk seluruh permohonan: berkaitan dengan keadilan serta perlakuan setara. Fokus tersebut berbeda dari <strong>Pembedaan yang tegas antara tindakan benar dan salah</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Penggunaan keahlian sesuai ketentuan profesi birokrasi: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Pembedaan yang tegas antara tindakan benar dan salah</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Penolakan pengaruh luar terhadap penggunaan kewenangan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Pembedaan yang tegas antara tindakan benar dan salah</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Penempatan kepentingan masyarakat di atas peluang jabatan: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Pembedaan yang tegas antara tindakan benar dan salah</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Rumusan &#x27;yang salah adalah salah, yang benar adalah benar&#x27; merupakan inti kebenaran dan kejujuran; opsi lain menguji empat prinsip tetangga yang nyata.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 5.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Prinsip 'yang datang pertama harus pertama dilayani' merupakan contoh khusus ....","options":["Tanggung jawab terhadap kepentingan publik","Integritas moral yang tinggi","Bertindak secara adil","Berpihak kepada kebenaran dan kejujuran","Profesionalisme"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 5","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Contoh antrean pertama datang pertama dilayani diletakkan pada prinsip bertindak adil karena menolak keistimewaan layanan. Opsi lain merupakan prinsip moral yang relevan namun berbeda pusat penekanannya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Bertindak secara adil</mark>.</strong> Contoh antrean pertama datang pertama dilayani diletakkan pada prinsip bertindak adil karena menolak keistimewaan layanan. Opsi lain merupakan prinsip moral yang relevan namun berbeda pusat penekanannya.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Tanggung jawab terhadap kepentingan publik: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Bertindak secara adil</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Integritas moral yang tinggi: menekankan integritas, independensi, dan pencegahan penyalahgunaan kewenangan. Fokus tersebut berbeda dari <strong>Bertindak secara adil</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Berpihak kepada kebenaran dan kejujuran: menekankan kebenaran fakta dan kejujuran keterangan. Fokus tersebut berbeda dari <strong>Bertindak secara adil</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Profesionalisme: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Bertindak secara adil</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Contoh antrean pertama datang pertama dilayani diletakkan pada prinsip bertindak adil karena menolak keistimewaan layanan. Opsi lain merupakan prinsip moral yang relevan namun berbeda pusat penekanannya.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 5.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Aplikasi","question":"Dalam penjelasan keadilan, netralitas pejabat terutama terhubung dengan kewajiban ....","options":["Memprioritaskan pengabdian publik sebagai panggilan profesi","Menjalankan pekerjaan berdasarkan kompetensi yang tersedia","Menolak campur tangan pihak lain dalam menggunakan kewenangan","Membela pihak yang benar sesuai prosedur tanpa diskriminasi","Mempertahankan kebenaran fakta yang diterima dari masyarakat"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 5","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Keadilan pada halaman 5 menuntut netral, membela yang benar menurut aturan, dan tidak diskriminatif. Pilihan lain adalah unsur integritas, tanggung jawab publik, profesionalisme, atau kejujuran.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Membela pihak yang benar sesuai prosedur tanpa diskriminasi</mark>.</strong> Keadilan pada halaman 5 menuntut netral, membela yang benar menurut aturan, dan tidak diskriminatif. Pilihan lain adalah unsur integritas, tanggung jawab publik, profesionalisme, atau kejujuran.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Memprioritaskan pengabdian publik sebagai panggilan profesi: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Membela pihak yang benar sesuai prosedur tanpa diskriminasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Menjalankan pekerjaan berdasarkan kompetensi yang tersedia: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Membela pihak yang benar sesuai prosedur tanpa diskriminasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Menolak campur tangan pihak lain dalam menggunakan kewenangan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Membela pihak yang benar sesuai prosedur tanpa diskriminasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Mempertahankan kebenaran fakta yang diterima dari masyarakat: menekankan kebenaran fakta dan kejujuran keterangan. Fokus tersebut berbeda dari <strong>Membela pihak yang benar sesuai prosedur tanpa diskriminasi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Keadilan pada halaman 5 menuntut netral, membela yang benar menurut aturan, dan tidak diskriminatif. Pilihan lain adalah unsur integritas, tanggung jawab publik, profesionalisme, atau kejujuran.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 5.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Aplikasi","question":"Dua pemohon memenuhi syarat sama, tetapi satu didahulukan karena kedekatan pribadi. Prinsip yang paling langsung dilanggar adalah ....","options":["Integritas moral yang tinggi","Profesionalisme","Tanggung jawab pada kepentingan publik","Kejujuran dan kebenaran","Bertindak secara adil"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 5","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Butir bertindak adil melarang perlakuan khusus dan diskriminasi, dengan antrean sebagai ilustrasi eksplisit. Kasus tidak menyatakan kekurangan kemampuan, manipulasi keterangan, atau tekanan pihak lain.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Bertindak secara adil</mark>.</strong> Butir bertindak adil melarang perlakuan khusus dan diskriminasi, dengan antrean sebagai ilustrasi eksplisit. Kasus tidak menyatakan kekurangan kemampuan, manipulasi keterangan, atau tekanan pihak lain.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Integritas moral yang tinggi: menekankan integritas, independensi, dan pencegahan penyalahgunaan kewenangan. Fokus tersebut berbeda dari <strong>Bertindak secara adil</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Profesionalisme: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Bertindak secara adil</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Tanggung jawab pada kepentingan publik: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Bertindak secara adil</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Kejujuran dan kebenaran: menekankan kebenaran fakta dan kejujuran keterangan. Fokus tersebut berbeda dari <strong>Bertindak secara adil</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Butir bertindak adil melarang perlakuan khusus dan diskriminasi, dengan antrean sebagai ilustrasi eksplisit. Kasus tidak menyatakan kekurangan kemampuan, manipulasi keterangan, atau tekanan pihak lain.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 5.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Larangan menghalalkan segala cara secara khusus menyoroti risiko birokrasi yang ....","options":["Melakukan manipulasi untuk tujuan menyimpang dan merugikan publik","Memberikan sanksi sesuai ketentuan kepada pihak pelanggar","Bertindak netral dalam menangani permohonan masyarakat","Mendahulukan penerima layanan sesuai urutan kedatangannya","Menolak hadiah jabatan demi mengutamakan pengabdian publik"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 5","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Prinsip keenam membahas anggapan birokrasi &#x27;bisa diatur&#x27;, yaitu manipulasi demi tujuan yang merugikan publik. Alternatif lain justru contoh dari prinsip lain.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Melakukan manipulasi untuk tujuan menyimpang dan merugikan publik</mark>.</strong> Prinsip keenam membahas anggapan birokrasi &#x27;bisa diatur&#x27;, yaitu manipulasi demi tujuan yang merugikan publik. Alternatif lain justru contoh dari prinsip lain.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Memberikan sanksi sesuai ketentuan kepada pihak pelanggar: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Melakukan manipulasi untuk tujuan menyimpang dan merugikan publik</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Bertindak netral dalam menangani permohonan masyarakat: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Melakukan manipulasi untuk tujuan menyimpang dan merugikan publik</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Mendahulukan penerima layanan sesuai urutan kedatangannya: berkaitan dengan keadilan serta perlakuan setara. Fokus tersebut berbeda dari <strong>Melakukan manipulasi untuk tujuan menyimpang dan merugikan publik</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Menolak hadiah jabatan demi mengutamakan pengabdian publik: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Melakukan manipulasi untuk tujuan menyimpang dan merugikan publik</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Prinsip keenam membahas anggapan birokrasi &#x27;bisa diatur&#x27;, yaitu manipulasi demi tujuan yang merugikan publik. Alternatif lain justru contoh dari prinsip lain.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 5.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Manakah pasangan penjelasan yang paling tepat membedakan prinsip keempat dan keenam Keraf?","options":["Kejujuran menekankan keahlian; larangan segala cara menekankan penghasilan","Kejujuran memprioritaskan pengabdian; larangan segala cara mengatur karier","Kejujuran menolak diskriminasi; larangan segala cara memperkuat solidaritas","Kejujuran menjaga benar-salah; larangan segala cara mencegah manipulasi tujuan","Kejujuran menata urutan layanan; larangan segala cara menjaga sikap netral"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 5","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Prinsip keempat berpihak kepada kebenaran dan kejujuran; prinsip keenam menolak manipulasi proses demi mencapai tujuan menyimpang. Opsi lainnya memasangkan ciri dari prinsip berbeda.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Kejujuran menjaga benar-salah; larangan segala cara mencegah manipulasi tujuan</mark>.</strong> Prinsip keempat berpihak kepada kebenaran dan kejujuran; prinsip keenam menolak manipulasi proses demi mencapai tujuan menyimpang. Opsi lainnya memasangkan ciri dari prinsip berbeda.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Kejujuran menekankan keahlian; larangan segala cara menekankan penghasilan: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Kejujuran menjaga benar-salah; larangan segala cara mencegah manipulasi tujuan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Kejujuran memprioritaskan pengabdian; larangan segala cara mengatur karier: menekankan kebenaran fakta dan kejujuran keterangan. Fokus tersebut berbeda dari <strong>Kejujuran menjaga benar-salah; larangan segala cara mencegah manipulasi tujuan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Kejujuran menolak diskriminasi; larangan segala cara memperkuat solidaritas: berkaitan dengan keadilan serta perlakuan setara. Fokus tersebut berbeda dari <strong>Kejujuran menjaga benar-salah; larangan segala cara mencegah manipulasi tujuan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Kejujuran menata urutan layanan; larangan segala cara menjaga sikap netral: berkaitan dengan keadilan serta perlakuan setara. Fokus tersebut berbeda dari <strong>Kejujuran menjaga benar-salah; larangan segala cara mencegah manipulasi tujuan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Prinsip keempat berpihak kepada kebenaran dan kejujuran; prinsip keenam menolak manipulasi proses demi mencapai tujuan menyimpang. Opsi lainnya memasangkan ciri dari prinsip berbeda.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 5.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Klasifikasi","question":"Sebuah unit mengejar target pelayanan dengan cara memanipulasi tahapan yang wajib ditempuh. Prinsip Keraf yang paling spesifik menjadi acuan evaluasi adalah ....","options":["Profesionalisme dalam pelaksanaan pekerjaan","Berpihak kepada kebenaran dan kejujuran","Bertindak secara adil kepada seluruh pemohon layanan","Tanggung jawab terhadap kepentingan publik","Jangan menghalalkan segala cara untuk mencapai tujuan"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 5","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Kasus menekankan cara manipulatif untuk mengejar tujuan, tepat sasaran prinsip keenam. Opsi lain memiliki keterkaitan etis tetapi tidak menguji hubungan sarana-tujuan secara spesifik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Jangan menghalalkan segala cara untuk mencapai tujuan</mark>.</strong> Kasus menekankan cara manipulatif untuk mengejar tujuan, tepat sasaran prinsip keenam. Opsi lain memiliki keterkaitan etis tetapi tidak menguji hubungan sarana-tujuan secara spesifik.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Profesionalisme dalam pelaksanaan pekerjaan: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Jangan menghalalkan segala cara untuk mencapai tujuan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Berpihak kepada kebenaran dan kejujuran: menekankan kebenaran fakta dan kejujuran keterangan. Fokus tersebut berbeda dari <strong>Jangan menghalalkan segala cara untuk mencapai tujuan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Bertindak secara adil kepada seluruh pemohon layanan: berkaitan dengan keadilan serta perlakuan setara. Fokus tersebut berbeda dari <strong>Jangan menghalalkan segala cara untuk mencapai tujuan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Tanggung jawab terhadap kepentingan publik: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Jangan menghalalkan segala cara untuk mencapai tujuan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Kasus menekankan cara manipulatif untuk mengejar tujuan, tepat sasaran prinsip keenam. Opsi lain memiliki keterkaitan etis tetapi tidak menguji hubungan sarana-tujuan secara spesifik.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 5.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Konsep","question":"Dorongan utama memilih profesi PNS dalam prinsip tanggung jawab publik adalah ....","options":["Keinginan luhur untuk mengabdi pada kepentingan publik","Keteguhan menjaga nama baik dari penyalahgunaan kuasa","Kesediaan melaksanakan tugas dengan keahlian terbaik","Keinginan menjaga kebenaran dalam setiap keterangan","Komitmen melayani semua orang tanpa perlakuan khusus"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 5","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Bagian tanggung jawab publik menyatakan menjadi PNS merupakan panggilan mengabdi kepada publik, bangsa, dan negara. Unsur lain juga etis tetapi berasal dari prinsip yang berbeda.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Keinginan luhur untuk mengabdi pada kepentingan publik</mark>.</strong> Bagian tanggung jawab publik menyatakan menjadi PNS merupakan panggilan mengabdi kepada publik, bangsa, dan negara. Unsur lain juga etis tetapi berasal dari prinsip yang berbeda.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Keteguhan menjaga nama baik dari penyalahgunaan kuasa: menekankan integritas, independensi, dan pencegahan penyalahgunaan kewenangan. Fokus tersebut berbeda dari <strong>Keinginan luhur untuk mengabdi pada kepentingan publik</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Kesediaan melaksanakan tugas dengan keahlian terbaik: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Keinginan luhur untuk mengabdi pada kepentingan publik</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Keinginan menjaga kebenaran dalam setiap keterangan: menekankan kebenaran fakta dan kejujuran keterangan. Fokus tersebut berbeda dari <strong>Keinginan luhur untuk mengabdi pada kepentingan publik</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Komitmen melayani semua orang tanpa perlakuan khusus: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Keinginan luhur untuk mengabdi pada kepentingan publik</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Bagian tanggung jawab publik menyatakan menjadi PNS merupakan panggilan mengabdi kepada publik, bangsa, dan negara. Unsur lain juga etis tetapi berasal dari prinsip yang berbeda.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 5.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Konsep","question":"Makna praktis prinsip ketujuh Keraf terhadap pemberian layanan publik adalah ....","options":["Tidak mempersulit dan tidak meminta imbalan yang tidak patut","Mengutamakan pelayanan dengan tujuan kesejahteraan masyarakat","Menyampaikan fakta secara benar pada tiap penerima layanan","Menghindari diskriminasi berdasarkan latar belakang pemohon","Mematuhi standar profesi berdasarkan kemampuan yang dimiliki"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 6","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Prinsip ketujuh menyatakan jangan melakukan pada orang lain hal yang tidak ingin dialami sendiri; contoh eksplisitnya tidak mempersulit dan tidak meminta sesuatu untuk layanan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Tidak mempersulit dan tidak meminta imbalan yang tidak patut</mark>.</strong> Prinsip ketujuh menyatakan jangan melakukan pada orang lain hal yang tidak ingin dialami sendiri; contoh eksplisitnya tidak mempersulit dan tidak meminta sesuatu untuk layanan.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Mengutamakan pelayanan dengan tujuan kesejahteraan masyarakat: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Tidak mempersulit dan tidak meminta imbalan yang tidak patut</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Menyampaikan fakta secara benar pada tiap penerima layanan: menekankan kebenaran fakta dan kejujuran keterangan. Fokus tersebut berbeda dari <strong>Tidak mempersulit dan tidak meminta imbalan yang tidak patut</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Menghindari diskriminasi berdasarkan latar belakang pemohon: berkaitan dengan keadilan serta perlakuan setara. Fokus tersebut berbeda dari <strong>Tidak mempersulit dan tidak meminta imbalan yang tidak patut</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Mematuhi standar profesi berdasarkan kemampuan yang dimiliki: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Tidak mempersulit dan tidak meminta imbalan yang tidak patut</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Prinsip ketujuh menyatakan jangan melakukan pada orang lain hal yang tidak ingin dialami sendiri; contoh eksplisitnya tidak mempersulit dan tidak meminta sesuatu untuk layanan.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 6.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Ketentuan","question":"Istilah etika secara etimologis berasal dari bahasa Yunani kuno, yaitu ....","options":["Moralitas","Ethos","Mores","Moralis","Mos"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 6","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Istilah asal etika adalah ethos (Yunani); mos dan mores merupakan asal istilah moral dari Latin. Dua opsi lain bukan istilah asal yang disebut.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Ethos</mark>.</strong> Istilah asal etika adalah ethos (Yunani); mos dan mores merupakan asal istilah moral dari Latin. Dua opsi lain bukan istilah asal yang disebut.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Moralitas: menjelaskan kesesuaian sikap pribadi terhadap norma moral. Fokus tersebut berbeda dari <strong>Ethos</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Mores: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Ethos</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Moralis: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Ethos</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Mos: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Ethos</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Istilah asal etika adalah ethos (Yunani); mos dan mores merupakan asal istilah moral dari Latin. Dua opsi lain bukan istilah asal yang disebut.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 6.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Klasifikasi","question":"Dalam tiga pengertian KBBI, makna etika sebagai bidang kajian menekankan ....","options":["Pedoman perilaku profesi dalam menjalankan tugas negara","Ilmu mengenai baik-buruk serta hak dan kewajiban moral","Kumpulan asas atau nilai moral sebagai kode etik","Adat kebiasaan atau cara hidup yang dianggap baik","Sistem nilai dan norma moral yang memandu tingkah laku"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 6","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>KBBI merinci etika sebagai sistem nilai, ilmu baik-buruk dan hak-kewajiban moral, serta kumpulan asas. Yang menunjukkan &#x27;bidang kajian&#x27; adalah makna kedua sebagai ilmu.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Ilmu mengenai baik-buruk serta hak dan kewajiban moral</mark>.</strong> KBBI merinci etika sebagai sistem nilai, ilmu baik-buruk dan hak-kewajiban moral, serta kumpulan asas. Yang menunjukkan &#x27;bidang kajian&#x27; adalah makna kedua sebagai ilmu.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Pedoman perilaku profesi dalam menjalankan tugas negara: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Ilmu mengenai baik-buruk serta hak dan kewajiban moral</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Kumpulan asas atau nilai moral sebagai kode etik: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Ilmu mengenai baik-buruk serta hak dan kewajiban moral</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Adat kebiasaan atau cara hidup yang dianggap baik: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Ilmu mengenai baik-buruk serta hak dan kewajiban moral</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Sistem nilai dan norma moral yang memandu tingkah laku: menjelaskan kesesuaian sikap pribadi terhadap norma moral. Fokus tersebut berbeda dari <strong>Ilmu mengenai baik-buruk serta hak dan kewajiban moral</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>KBBI merinci etika sebagai sistem nilai, ilmu baik-buruk dan hak-kewajiban moral, serta kumpulan asas. Yang menunjukkan &#x27;bidang kajian&#x27; adalah makna kedua sebagai ilmu.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 6.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Makna etika sebagai 'kode etik' dalam daftar KBBI menunjuk pada ....","options":["Cara hidup yang dibiasakan dalam komunitas","Adat istiadat yang diakui masyarakat tertentu","Kumpulan asas atau nilai moral","Ilmu mengenai baik-buruk tindakan manusia","Sistem nilai yang mengatur perilaku kelompok"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 6","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>KBBI mengartikan etika salah satunya sebagai kumpulan asas atau nilai moral, yaitu kode etik. Pilihan lain mengacu pada dua arti KBBI lain atau etimologi ethos.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Kumpulan asas atau nilai moral</mark>.</strong> KBBI mengartikan etika salah satunya sebagai kumpulan asas atau nilai moral, yaitu kode etik. Pilihan lain mengacu pada dua arti KBBI lain atau etimologi ethos.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Cara hidup yang dibiasakan dalam komunitas: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Kumpulan asas atau nilai moral</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Adat istiadat yang diakui masyarakat tertentu: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Kumpulan asas atau nilai moral</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Ilmu mengenai baik-buruk tindakan manusia: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Kumpulan asas atau nilai moral</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Sistem nilai yang mengatur perilaku kelompok: membahas pengaruh lingkungan dan etos kerja. Fokus tersebut berbeda dari <strong>Kumpulan asas atau nilai moral</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>KBBI mengartikan etika salah satunya sebagai kumpulan asas atau nilai moral, yaitu kode etik. Pilihan lain mengacu pada dua arti KBBI lain atau etimologi ethos.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 6.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Klasifikasi","question":"Pasangan asal bahasa yang benar untuk etika dan moral ialah ....","options":["Etika—Yunani ethos; moral—Latin mos/mores","Etika—Latin mos; moral—Yunani ethos","Etika—Yunani mores; moral—Latin ethos","Etika—Latin ethos; moral—Yunani mos","Etika—Latin mores; moral—Yunani moralis"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 6","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Etika berasal dari ethos bahasa Yunani kuno, sedangkan moral berasal dari mos atau jamaknya mores bahasa Latin. Jawaban lainnya membalik atau memindahkan istilah asal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Etika—Yunani ethos; moral—Latin mos/mores</mark>.</strong> Etika berasal dari ethos bahasa Yunani kuno, sedangkan moral berasal dari mos atau jamaknya mores bahasa Latin. Jawaban lainnya membalik atau memindahkan istilah asal.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Etika—Latin mos; moral—Yunani ethos: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Etika—Yunani ethos; moral—Latin mos/mores</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Etika—Yunani mores; moral—Latin ethos: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Etika—Yunani ethos; moral—Latin mos/mores</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Etika—Latin ethos; moral—Yunani mos: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Etika—Yunani ethos; moral—Latin mos/mores</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Etika—Latin mores; moral—Yunani moralis: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Etika—Yunani ethos; moral—Latin mos/mores</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Etika berasal dari ethos bahasa Yunani kuno, sedangkan moral berasal dari mos atau jamaknya mores bahasa Latin. Jawaban lainnya membalik atau memindahkan istilah asal.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 6.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Konsep","question":"Istilah etika PNS dalam konteks pengaturan perilaku aparatur secara khusus merujuk pada ....","options":["Moralitas pribadi PNS","Budaya organisasi PNS","Kode etik PNS","Prinsip profesionalisme PNS","Etos kerja kelompok PNS"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 6","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Setelah membedakan etika dan moral, uraian secara eksplisit menetapkan &#x27;etika PNS&#x27; sebagai kode etik PNS untuk bahasan berikutnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Kode etik PNS</mark>.</strong> Setelah membedakan etika dan moral, uraian secara eksplisit menetapkan &#x27;etika PNS&#x27; sebagai kode etik PNS untuk bahasan berikutnya.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Moralitas pribadi PNS: menjelaskan kesesuaian sikap pribadi terhadap norma moral. Fokus tersebut berbeda dari <strong>Kode etik PNS</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Budaya organisasi PNS: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Kode etik PNS</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Prinsip profesionalisme PNS: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Kode etik PNS</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Etos kerja kelompok PNS: membahas pengaruh lingkungan dan etos kerja. Fokus tersebut berbeda dari <strong>Kode etik PNS</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Setelah membedakan etika dan moral, uraian secara eksplisit menetapkan &#x27;etika PNS&#x27; sebagai kode etik PNS untuk bahasan berikutnya.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 6.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Tujuan kode etik dan kode perilaku ASN pada Pasal 4 ayat (1) UU 20/2023 adalah ....","options":["Membina hubungan antarpegawai dan penegakan prosedur kerja","Menjaga martabat dan kehormatan ASN serta kepentingan bangsa dan negara","Menjaga mutu kompetensi ASN serta harmonisasi budaya organisasi","Meningkatkan kualitas pelayanan publik serta efisiensi pengelolaan aset","Membina perilaku profesi serta pelaksanaan pengawasan rutin"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 6","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Pasal 4 ayat (1) secara khusus menegaskan martabat dan kehormatan ASN sekaligus kepentingan bangsa dan negara. Pilihan lain membahas manfaat etika, bukan bunyi tujuan ketentuan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Menjaga martabat dan kehormatan ASN serta kepentingan bangsa dan negara</mark>.</strong> Pasal 4 ayat (1) secara khusus menegaskan martabat dan kehormatan ASN sekaligus kepentingan bangsa dan negara. Pilihan lain membahas manfaat etika, bukan bunyi tujuan ketentuan.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Membina hubungan antarpegawai dan penegakan prosedur kerja: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Menjaga martabat dan kehormatan ASN serta kepentingan bangsa dan negara</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Menjaga mutu kompetensi ASN serta harmonisasi budaya organisasi: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Menjaga martabat dan kehormatan ASN serta kepentingan bangsa dan negara</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Meningkatkan kualitas pelayanan publik serta efisiensi pengelolaan aset: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Menjaga martabat dan kehormatan ASN serta kepentingan bangsa dan negara</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Membina perilaku profesi serta pelaksanaan pengawasan rutin: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Menjaga martabat dan kehormatan ASN serta kepentingan bangsa dan negara</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Pasal 4 ayat (1) secara khusus menegaskan martabat dan kehormatan ASN sekaligus kepentingan bangsa dan negara. Pilihan lain membahas manfaat etika, bukan bunyi tujuan ketentuan.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 6.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Pembedaan tiga makna etika KBBI yang paling tepat adalah ....","options":["Pegangan profesi, sikap individu, dan hierarki kelembagaan","Kebiasaan hidup, penegakan disiplin, dan sanksi administratif","Ilmu moral, tata kelola jabatan, dan persyaratan kompetensi","Pegangan perilaku, ilmu moral, dan kumpulan asas moral","Kepatuhan hukum, pembinaan organisasi, dan pengawasan"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 6","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Tiga arti KBBI terdiri dari sistem nilai dan norma yang mengatur tingkah laku, ilmu tentang baik-buruk dan hak-kewajiban moral, serta kumpulan asas atau nilai moral.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Pegangan perilaku, ilmu moral, dan kumpulan asas moral</mark>.</strong> Tiga arti KBBI terdiri dari sistem nilai dan norma yang mengatur tingkah laku, ilmu tentang baik-buruk dan hak-kewajiban moral, serta kumpulan asas atau nilai moral.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Pegangan profesi, sikap individu, dan hierarki kelembagaan: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Pegangan perilaku, ilmu moral, dan kumpulan asas moral</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Kebiasaan hidup, penegakan disiplin, dan sanksi administratif: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Pegangan perilaku, ilmu moral, dan kumpulan asas moral</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Ilmu moral, tata kelola jabatan, dan persyaratan kompetensi: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Pegangan perilaku, ilmu moral, dan kumpulan asas moral</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Kepatuhan hukum, pembinaan organisasi, dan pengawasan: berkaitan dengan proses pengenalan dan pembinaan etika. Fokus tersebut berbeda dari <strong>Pegangan perilaku, ilmu moral, dan kumpulan asas moral</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Tiga arti KBBI terdiri dari sistem nilai dan norma yang mengatur tingkah laku, ilmu tentang baik-buruk dan hak-kewajiban moral, serta kumpulan asas atau nilai moral.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 6.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Aplikasi","question":"Seseorang menyebut 'etika' sebagai ilmu untuk menelaah baik-buruk dan hak-kewajiban moral. Ia sedang menggunakan makna KBBI yang ....","options":["Berupa adat kebiasaan dari istilah ethos","Berupa kumpulan asas dalam kode etik","Bersifat keilmuan atau kajian moral","Berupa sistem nilai pembimbing tingkah laku","Berupa cara hidup dari istilah mores"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 6","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Frasa ilmu dan penelaahan baik-buruk mengacu arti kedua KBBI. Pilihan lainnya adalah arti pertama, arti ketiga, atau penjelasan etimologis yang berbeda.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Bersifat keilmuan atau kajian moral</mark>.</strong> Frasa ilmu dan penelaahan baik-buruk mengacu arti kedua KBBI. Pilihan lainnya adalah arti pertama, arti ketiga, atau penjelasan etimologis yang berbeda.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Berupa adat kebiasaan dari istilah ethos: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Bersifat keilmuan atau kajian moral</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Berupa kumpulan asas dalam kode etik: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Bersifat keilmuan atau kajian moral</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Berupa sistem nilai pembimbing tingkah laku: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Bersifat keilmuan atau kajian moral</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Berupa cara hidup dari istilah mores: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Bersifat keilmuan atau kajian moral</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Frasa ilmu dan penelaahan baik-buruk mengacu arti kedua KBBI. Pilihan lainnya adalah arti pertama, arti ketiga, atau penjelasan etimologis yang berbeda.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 6.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Perbedaan utama fungsi kode etik ASN dengan asal-usul kata ethos ialah ....","options":["Kode etik menata kebiasaan Latin; ethos mengatur disiplin pegawai","Kode etik menjelaskan asal kata moral; ethos mengatur hak dan kewajiban ASN","Kode etik menetapkan etimologi Yunani; ethos mengatur sanksi profesi","Kode etik menerangkan asal kata etika; ethos menentukan kehormatan ASN","Kode etik menata standar perilaku ASN; ethos menerangkan asal istilah etika"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 6","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Kode etik berisi norma dan standar moral bagi ASN, sedangkan ethos adalah istilah asal bahasa Yunani yang merujuk pada adat/kebiasaan yang dianggap baik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Kode etik menata standar perilaku ASN; ethos menerangkan asal istilah etika</mark>.</strong> Kode etik berisi norma dan standar moral bagi ASN, sedangkan ethos adalah istilah asal bahasa Yunani yang merujuk pada adat/kebiasaan yang dianggap baik.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Kode etik menata kebiasaan Latin; ethos mengatur disiplin pegawai: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Kode etik menata standar perilaku ASN; ethos menerangkan asal istilah etika</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Kode etik menjelaskan asal kata moral; ethos mengatur hak dan kewajiban ASN: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Kode etik menata standar perilaku ASN; ethos menerangkan asal istilah etika</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Kode etik menetapkan etimologi Yunani; ethos mengatur sanksi profesi: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Kode etik menata standar perilaku ASN; ethos menerangkan asal istilah etika</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Kode etik menerangkan asal kata etika; ethos menentukan kehormatan ASN: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Kode etik menata standar perilaku ASN; ethos menerangkan asal istilah etika</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Kode etik berisi norma dan standar moral bagi ASN, sedangkan ethos adalah istilah asal bahasa Yunani yang merujuk pada adat/kebiasaan yang dianggap baik.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 6.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Ketentuan","question":"Ketentuan nilai dasar ASN dalam UU Nomor 20 Tahun 2023 dirujuk melalui ....","options":["Pasal 13","Pasal 4 ayat (2)","Pasal 7","Pasal 4 ayat (1)","Pasal 3"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 7","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Halaman 7 menghubungkan daftar tujuh nilai dasar ASN dengan Pasal 3 UU 20/2023. Pasal 4 membahas kode etik, bukan lokasi daftar nilai dasar pada pertanyaan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Pasal 3</mark>.</strong> Halaman 7 menghubungkan daftar tujuh nilai dasar ASN dengan Pasal 3 UU 20/2023. Pasal 4 membahas kode etik, bukan lokasi daftar nilai dasar pada pertanyaan.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Pasal 13: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 3</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Pasal 4 ayat (2): menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 3</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Pasal 7: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 3</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Pasal 4 ayat (1): menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 3</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Halaman 7 menghubungkan daftar tujuh nilai dasar ASN dengan Pasal 3 UU 20/2023. Pasal 4 membahas kode etik, bukan lokasi daftar nilai dasar pada pertanyaan.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 7.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Konsep","question":"Aspek yang paling langsung berkaitan dengan tujuan menjaga martabat ASN adalah ....","options":["Penciptaan budaya organisasi melalui kerja sama lintas unit","Ucapan, tindakan, dan keputusan yang mencerminkan integritas pribadi","Peran ASN yang menghindari kepentingan kelompok tertentu","Sikap netral untuk melindungi kepentingan nasional","Standar profesi yang menjaga kepercayaan terhadap birokrasi"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 7","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Penjagaan martabat dijabarkan melalui citra ASN sebagai wajah pemerintah serta tindakan, ucapan, dan keputusan individual yang berintegritas; opsi lain terutama mengenai profesi atau kepentingan nasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Ucapan, tindakan, dan keputusan yang mencerminkan integritas pribadi</mark>.</strong> Penjagaan martabat dijabarkan melalui citra ASN sebagai wajah pemerintah serta tindakan, ucapan, dan keputusan individual yang berintegritas; opsi lain terutama mengenai profesi atau kepentingan nasional.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Penciptaan budaya organisasi melalui kerja sama lintas unit: menekankan kerja sama dan koordinasi. Fokus tersebut berbeda dari <strong>Ucapan, tindakan, dan keputusan yang mencerminkan integritas pribadi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Peran ASN yang menghindari kepentingan kelompok tertentu: membahas pengaruh lingkungan dan etos kerja. Fokus tersebut berbeda dari <strong>Ucapan, tindakan, dan keputusan yang mencerminkan integritas pribadi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Sikap netral untuk melindungi kepentingan nasional: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Ucapan, tindakan, dan keputusan yang mencerminkan integritas pribadi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Standar profesi yang menjaga kepercayaan terhadap birokrasi: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Ucapan, tindakan, dan keputusan yang mencerminkan integritas pribadi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Penjagaan martabat dijabarkan melalui citra ASN sebagai wajah pemerintah serta tindakan, ucapan, dan keputusan individual yang berintegritas; opsi lain terutama mengenai profesi atau kepentingan nasional.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 7.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Analisis","question":"Hal yang secara khusus membedakan kehormatan profesi ASN dari martabat pribadi ASN adalah ....","options":["Kehormatan profesi menekankan perilaku setiap keputusan individu","Kehormatan profesi menekankan kepercayaan publik pada birokrasi secara kolektif","Kehormatan profesi menekankan kesetiaan pada Pancasila dan UUD","Kehormatan profesi menekankan ketaatan standar operasional jabatan","Kehormatan profesi menekankan pencegahan konflik kepentingan kebangsaan"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 7","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Satu tindakan tidak etis dapat merusak citra keseluruhan instansi; ini menjadi alasan menjaga kehormatan profesi. Martabat lebih menekankan batas moral individu sebagai wajah pemerintah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Kehormatan profesi menekankan kepercayaan publik pada birokrasi secara kolektif</mark>.</strong> Satu tindakan tidak etis dapat merusak citra keseluruhan instansi; ini menjadi alasan menjaga kehormatan profesi. Martabat lebih menekankan batas moral individu sebagai wajah pemerintah.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Kehormatan profesi menekankan perilaku setiap keputusan individu: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Kehormatan profesi menekankan kepercayaan publik pada birokrasi secara kolektif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Kehormatan profesi menekankan kesetiaan pada Pancasila dan UUD: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Kehormatan profesi menekankan kepercayaan publik pada birokrasi secara kolektif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Kehormatan profesi menekankan ketaatan standar operasional jabatan: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Kehormatan profesi menekankan kepercayaan publik pada birokrasi secara kolektif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Kehormatan profesi menekankan pencegahan konflik kepentingan kebangsaan: menekankan integritas, independensi, dan pencegahan penyalahgunaan kewenangan. Fokus tersebut berbeda dari <strong>Kehormatan profesi menekankan kepercayaan publik pada birokrasi secara kolektif</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Satu tindakan tidak etis dapat merusak citra keseluruhan instansi; ini menjadi alasan menjaga kehormatan profesi. Martabat lebih menekankan batas moral individu sebagai wajah pemerintah.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 7.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Konsep","question":"Fokus perlindungan kepentingan bangsa dan negara melalui kode etik adalah ....","options":["Membangun budaya kerja yang mengutamakan kerja sama internal","Mempertahankan citra organisasi melalui penghormatan profesi","Menjaga reputasi individu melalui standar komunikasi profesional","Mengembangkan kompetensi pelayanan dengan pendidikan berkelanjutan","Mencegah konflik kepentingan dan penyalahgunaan kewenangan dalam pelayanan"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 7","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Halaman 7 menyebut adil, netral, bebas konflik kepentingan, tidak menyalahgunakan kewenangan agar kebijakan memihak kepentingan nasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Mencegah konflik kepentingan dan penyalahgunaan kewenangan dalam pelayanan</mark>.</strong> Halaman 7 menyebut adil, netral, bebas konflik kepentingan, tidak menyalahgunakan kewenangan agar kebijakan memihak kepentingan nasional.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Membangun budaya kerja yang mengutamakan kerja sama internal: menekankan kerja sama dan koordinasi. Fokus tersebut berbeda dari <strong>Mencegah konflik kepentingan dan penyalahgunaan kewenangan dalam pelayanan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Mempertahankan citra organisasi melalui penghormatan profesi: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Mencegah konflik kepentingan dan penyalahgunaan kewenangan dalam pelayanan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Menjaga reputasi individu melalui standar komunikasi profesional: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Mencegah konflik kepentingan dan penyalahgunaan kewenangan dalam pelayanan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Mengembangkan kompetensi pelayanan dengan pendidikan berkelanjutan: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Mencegah konflik kepentingan dan penyalahgunaan kewenangan dalam pelayanan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Halaman 7 menyebut adil, netral, bebas konflik kepentingan, tidak menyalahgunakan kewenangan agar kebijakan memihak kepentingan nasional.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 7.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Manakah susunan yang memuat hanya nilai dasar ASN sebagaimana Pasal 3 UU 20/2023?","options":["Integritas, kompeten, harmonis, pelayanan, adaptif","Kompeten, keadilan, kolaboratif, sinergi, loyal","Akuntabel, harmonis, loyal, adaptif, kolaboratif","Akuntabel, profesionalisme, loyal, adaptif, sinergi","Berorientasi pelayanan, kesempurnaan, harmonis, sinergi, loyal"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 7","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Nilai dasar ASN terdiri dari tujuh nilai BerAKHLAK; opsi benar berisi lima di antaranya saja, sementara alternatif mencampur dengan Nilai Kemenkeu atau prinsip etika lain.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Akuntabel, harmonis, loyal, adaptif, kolaboratif</mark>.</strong> Nilai dasar ASN terdiri dari tujuh nilai BerAKHLAK; opsi benar berisi lima di antaranya saja, sementara alternatif mencampur dengan Nilai Kemenkeu atau prinsip etika lain.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Integritas, kompeten, harmonis, pelayanan, adaptif: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Akuntabel, harmonis, loyal, adaptif, kolaboratif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Kompeten, keadilan, kolaboratif, sinergi, loyal: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Akuntabel, harmonis, loyal, adaptif, kolaboratif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Akuntabel, profesionalisme, loyal, adaptif, sinergi: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Akuntabel, harmonis, loyal, adaptif, kolaboratif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Berorientasi pelayanan, kesempurnaan, harmonis, sinergi, loyal: menekankan kerja sama dan koordinasi. Fokus tersebut berbeda dari <strong>Akuntabel, harmonis, loyal, adaptif, kolaboratif</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Nilai dasar ASN terdiri dari tujuh nilai BerAKHLAK; opsi benar berisi lima di antaranya saja, sementara alternatif mencampur dengan Nilai Kemenkeu atau prinsip etika lain.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 7.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Daftar nilai dasar ASN dalam Pasal 3 memuat istilah yang tepat berupa ....","options":["Berorientasi pelayanan, sinergi, loyal, adaptif","Integritas, sinergi, kompeten, akuntabel","Profesionalisme, akuntabel, kompeten, integritas","Berorientasi pelayanan, akuntabel, kompeten, harmonis","Pelayanan, harmonis, kolaboratif, kesempurnaan"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 7","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Istilah resmi yang disebut pada halaman 7 adalah berorientasi pelayanan, akuntabel, kompeten, harmonis, loyal, adaptif, dan kolaboratif. Opsi lain mencampurnya dengan istilah nilai organisasi yang berbeda.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Berorientasi pelayanan, akuntabel, kompeten, harmonis</mark>.</strong> Istilah resmi yang disebut pada halaman 7 adalah berorientasi pelayanan, akuntabel, kompeten, harmonis, loyal, adaptif, dan kolaboratif. Opsi lain mencampurnya dengan istilah nilai organisasi yang berbeda.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Berorientasi pelayanan, sinergi, loyal, adaptif: menitikberatkan pembaruan atau perbaikan berkelanjutan. Fokus tersebut berbeda dari <strong>Berorientasi pelayanan, akuntabel, kompeten, harmonis</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Integritas, sinergi, kompeten, akuntabel: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Berorientasi pelayanan, akuntabel, kompeten, harmonis</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Profesionalisme, akuntabel, kompeten, integritas: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Berorientasi pelayanan, akuntabel, kompeten, harmonis</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Pelayanan, harmonis, kolaboratif, kesempurnaan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Berorientasi pelayanan, akuntabel, kompeten, harmonis</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Istilah resmi yang disebut pada halaman 7 adalah berorientasi pelayanan, akuntabel, kompeten, harmonis, loyal, adaptif, dan kolaboratif. Opsi lain mencampurnya dengan istilah nilai organisasi yang berbeda.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 7.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Pasangan 'perlindungan kepentingan nasional' dan 'kehormatan profesi' tepat dibedakan melalui ....","options":["Ketepatan administrasi dan kecakapan teknis","Netralitas keputusan dan reputasi birokrasi","Pengawasan internal dan tata urutan pelayanan","Kerja sama unit dan pengembangan kompetensi","Kedisiplinan pribadi dan solidaritas rekan kerja"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 7","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Kepentingan nasional dikaitkan dengan keputusan yang adil-netral-bebas konflik; kehormatan profesi dikaitkan dengan kepercayaan publik dan citra lembaga.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Netralitas keputusan dan reputasi birokrasi</mark>.</strong> Kepentingan nasional dikaitkan dengan keputusan yang adil-netral-bebas konflik; kehormatan profesi dikaitkan dengan kepercayaan publik dan citra lembaga.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Ketepatan administrasi dan kecakapan teknis: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Netralitas keputusan dan reputasi birokrasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Pengawasan internal dan tata urutan pelayanan: berkaitan dengan keadilan serta perlakuan setara. Fokus tersebut berbeda dari <strong>Netralitas keputusan dan reputasi birokrasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Kerja sama unit dan pengembangan kompetensi: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Netralitas keputusan dan reputasi birokrasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Kedisiplinan pribadi dan solidaritas rekan kerja: membahas pengaruh lingkungan dan etos kerja. Fokus tersebut berbeda dari <strong>Netralitas keputusan dan reputasi birokrasi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Kepentingan nasional dikaitkan dengan keputusan yang adil-netral-bebas konflik; kehormatan profesi dikaitkan dengan kepercayaan publik dan citra lembaga.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 7.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Aplikasi","question":"Satu tindakan tidak etis seorang ASN menurunkan kepercayaan masyarakat terhadap instansi secara keseluruhan. Hal ini paling tepat menjelaskan ....","options":["Perlunya menata sistem penugasan pegawai ASN","Perlunya meningkatkan kompetensi teknis ASN","Perlunya memperkuat mekanisme kerja sama ASN","Perlunya menjaga kehormatan profesi ASN","Perlunya menjaga martabat individual ASN"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 7","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Subbagian kehormatan profesi menyebut perilaku tidak etis satu ASN dapat merusak citra seluruh instansi. Soal tidak menitikberatkan kompetensi, koordinasi, atau penugasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Perlunya menjaga kehormatan profesi ASN</mark>.</strong> Subbagian kehormatan profesi menyebut perilaku tidak etis satu ASN dapat merusak citra seluruh instansi. Soal tidak menitikberatkan kompetensi, koordinasi, atau penugasan.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Perlunya menata sistem penugasan pegawai ASN: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Perlunya menjaga kehormatan profesi ASN</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Perlunya meningkatkan kompetensi teknis ASN: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Perlunya menjaga kehormatan profesi ASN</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Perlunya memperkuat mekanisme kerja sama ASN: menekankan kerja sama dan koordinasi. Fokus tersebut berbeda dari <strong>Perlunya menjaga kehormatan profesi ASN</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Perlunya menjaga martabat individual ASN: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Perlunya menjaga kehormatan profesi ASN</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Subbagian kehormatan profesi menyebut perilaku tidak etis satu ASN dapat merusak citra seluruh instansi. Soal tidak menitikberatkan kompetensi, koordinasi, atau penugasan.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 7.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Kedudukan nilai dasar ASN terhadap PNS yang bekerja pada instansi berbeda adalah ....","options":["Berlaku bagi semua PNS tanpa membedakan tempat kerja","Berlaku hanya jika ditetapkan ulang tiap pejabat pembina","Berlaku bagi PNS yang memiliki jabatan tertentu saja","Berlaku khusus PNS yang melayani langsung masyarakat","Berlaku secara berbeda sesuai kelompok profesi instansi"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 7","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Halaman 7 menyebut nilai dasar menjadi pedoman bagi seluruh PNS tanpa membedakan tempat kerja. Distraktor membatasi lingkup penerapan tanpa dasar pada halaman sumber.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Berlaku bagi semua PNS tanpa membedakan tempat kerja</mark>.</strong> Halaman 7 menyebut nilai dasar menjadi pedoman bagi seluruh PNS tanpa membedakan tempat kerja. Distraktor membatasi lingkup penerapan tanpa dasar pada halaman sumber.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Berlaku hanya jika ditetapkan ulang tiap pejabat pembina: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Berlaku bagi semua PNS tanpa membedakan tempat kerja</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Berlaku bagi PNS yang memiliki jabatan tertentu saja: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Berlaku bagi semua PNS tanpa membedakan tempat kerja</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Berlaku khusus PNS yang melayani langsung masyarakat: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Berlaku bagi semua PNS tanpa membedakan tempat kerja</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Berlaku secara berbeda sesuai kelompok profesi instansi: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Berlaku bagi semua PNS tanpa membedakan tempat kerja</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Halaman 7 menyebut nilai dasar menjadi pedoman bagi seluruh PNS tanpa membedakan tempat kerja. Distraktor membatasi lingkup penerapan tanpa dasar pada halaman sumber.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 7.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Konsep","question":"Tujuh nilai dasar ASN dikenal melalui akronim ....","options":["Good Governance","BerAKHLAK","Etos Kerja","IP-Sinergi","Kode Korps"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 7","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Ketujuh nilai dasar yang dicantumkan adalah berorientasi pelayanan, akuntabel, kompeten, harmonis, loyal, adaptif, kolaboratif, lazim disingkat BerAKHLAK.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">BerAKHLAK</mark>.</strong> Ketujuh nilai dasar yang dicantumkan adalah berorientasi pelayanan, akuntabel, kompeten, harmonis, loyal, adaptif, kolaboratif, lazim disingkat BerAKHLAK.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Good Governance: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>BerAKHLAK</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Etos Kerja: membahas pengaruh lingkungan dan etos kerja. Fokus tersebut berbeda dari <strong>BerAKHLAK</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> IP-Sinergi: menekankan kerja sama dan koordinasi. Fokus tersebut berbeda dari <strong>BerAKHLAK</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Kode Korps: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>BerAKHLAK</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Ketujuh nilai dasar yang dicantumkan adalah berorientasi pelayanan, akuntabel, kompeten, harmonis, loyal, adaptif, kolaboratif, lazim disingkat BerAKHLAK.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 7.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Klasifikasi","question":"Indikator 'melakukan perbaikan tiada henti' termasuk nilai dasar ....","options":["Adaptif","Kolaboratif","Kompeten","Akuntabel","Berorientasi pelayanan"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 8","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Perbaikan tiada henti tercantum pada nilai berorientasi pelayanan. Adaptif menekankan inovasi dan respons perubahan; kompeten menekankan belajar dan kualitas tugas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Berorientasi pelayanan</mark>.</strong> Perbaikan tiada henti tercantum pada nilai berorientasi pelayanan. Adaptif menekankan inovasi dan respons perubahan; kompeten menekankan belajar dan kualitas tugas.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Adaptif: menitikberatkan pembaruan atau perbaikan berkelanjutan. Fokus tersebut berbeda dari <strong>Berorientasi pelayanan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Kolaboratif: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Berorientasi pelayanan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Kompeten: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Berorientasi pelayanan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Akuntabel: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Berorientasi pelayanan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Perbaikan tiada henti tercantum pada nilai berorientasi pelayanan. Adaptif menekankan inovasi dan respons perubahan; kompeten menekankan belajar dan kualitas tugas.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 8.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Dua indikator yang keduanya masuk nilai akuntabel adalah ....","options":["Meningkatkan kompetensi diri dan membantu rekan belajar","Menghargai pendapat dan memberi kesempatan berkontribusi","Membangun lingkungan kondusif dan menolong sesama","Menjaga rahasia jabatan dan setia pada pemerintahan sah","Penggunaan BMN efisien dan tidak menyalahgunakan kewenangan"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 8","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Akuntabel mencakup pengelolaan kekayaan/BMN secara efektif-efisien dan larangan menyalahgunakan kewenangan. Kombinasi lain adalah kompeten, harmonis, kolaboratif, atau loyal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Penggunaan BMN efisien dan tidak menyalahgunakan kewenangan</mark>.</strong> Akuntabel mencakup pengelolaan kekayaan/BMN secara efektif-efisien dan larangan menyalahgunakan kewenangan. Kombinasi lain adalah kompeten, harmonis, kolaboratif, atau loyal.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Meningkatkan kompetensi diri dan membantu rekan belajar: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Penggunaan BMN efisien dan tidak menyalahgunakan kewenangan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Menghargai pendapat dan memberi kesempatan berkontribusi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Penggunaan BMN efisien dan tidak menyalahgunakan kewenangan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Membangun lingkungan kondusif dan menolong sesama: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Penggunaan BMN efisien dan tidak menyalahgunakan kewenangan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Menjaga rahasia jabatan dan setia pada pemerintahan sah: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Penggunaan BMN efisien dan tidak menyalahgunakan kewenangan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Akuntabel mencakup pengelolaan kekayaan/BMN secara efektif-efisien dan larangan menyalahgunakan kewenangan. Kombinasi lain adalah kompeten, harmonis, kolaboratif, atau loyal.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 8.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Perilaku 'membantu orang lain belajar' dipetakan ke nilai ....","options":["Berorientasi pelayanan","Adaptif","Kolaboratif","Kompeten","Harmonis"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 8","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Pada Pasal 4 ayat (2), kompeten tidak hanya peningkatan diri dan kinerja terbaik, tetapi juga membantu orang lain belajar; kolaboratif lebih menekankan sinergi kontribusi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Kompeten</mark>.</strong> Pada Pasal 4 ayat (2), kompeten tidak hanya peningkatan diri dan kinerja terbaik, tetapi juga membantu orang lain belajar; kolaboratif lebih menekankan sinergi kontribusi.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Berorientasi pelayanan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Kompeten</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Adaptif: menitikberatkan pembaruan atau perbaikan berkelanjutan. Fokus tersebut berbeda dari <strong>Kompeten</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Kolaboratif: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Kompeten</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Harmonis: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Kompeten</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Pada Pasal 4 ayat (2), kompeten tidak hanya peningkatan diri dan kinerja terbaik, tetapi juga membantu orang lain belajar; kolaboratif lebih menekankan sinergi kontribusi.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 8.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Manakah indikator yang secara khusus menjadi unsur harmonis?","options":["Menjaga rahasia jabatan dan rahasia negara","Terbuka bekerja sama untuk menghasilkan nilai tambah","Meningkatkan kompetensi menghadapi tantangan berubah","Membangun lingkungan kerja yang kondusif","Menggunakan kekayaan negara secara bertanggung jawab"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 8","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Harmonis mencakup menghargai perbedaan, suka menolong, dan membangun lingkungan kondusif. Alternatif lain adalah kolaboratif, kompeten, akuntabel, dan loyal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Membangun lingkungan kerja yang kondusif</mark>.</strong> Harmonis mencakup menghargai perbedaan, suka menolong, dan membangun lingkungan kondusif. Alternatif lain adalah kolaboratif, kompeten, akuntabel, dan loyal.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Menjaga rahasia jabatan dan rahasia negara: menyasar kesetiaan dan kepentingan negara. Fokus tersebut berbeda dari <strong>Membangun lingkungan kerja yang kondusif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Terbuka bekerja sama untuk menghasilkan nilai tambah: menekankan kerja sama dan koordinasi. Fokus tersebut berbeda dari <strong>Membangun lingkungan kerja yang kondusif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Meningkatkan kompetensi menghadapi tantangan berubah: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Membangun lingkungan kerja yang kondusif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Menggunakan kekayaan negara secara bertanggung jawab: menyasar kesetiaan dan kepentingan negara. Fokus tersebut berbeda dari <strong>Membangun lingkungan kerja yang kondusif</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Harmonis mencakup menghargai perbedaan, suka menolong, dan membangun lingkungan kondusif. Alternatif lain adalah kolaboratif, kompeten, akuntabel, dan loyal.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 8.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Perilaku menjaga rahasia jabatan dan rahasia negara dijabarkan sebagai unsur ....","options":["Kompeten","Loyal","Kolaboratif","Harmonis","Akuntabel"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 8","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Loyal mencakup kesetiaan pada ideologi/konstitusi/pemerintahan sah, penjagaan nama baik, serta rahasia jabatan dan negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Loyal</mark>.</strong> Loyal mencakup kesetiaan pada ideologi/konstitusi/pemerintahan sah, penjagaan nama baik, serta rahasia jabatan dan negara.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Kompeten: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Loyal</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Kolaboratif: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Loyal</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Harmonis: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Loyal</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Akuntabel: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Loyal</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Loyal mencakup kesetiaan pada ideologi/konstitusi/pemerintahan sah, penjagaan nama baik, serta rahasia jabatan dan negara.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 8.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Pembeda utama nilai adaptif dan kompeten dalam butir Pasal 4 ayat (2) adalah ....","options":["Adaptif menggerakkan inovasi; kompeten mengembangkan kapabilitas belajar","Adaptif membantu orang lain belajar; kompeten memegang teguh Pancasila","Adaptif mengelola BMN efisien; kompeten membangun lingkungan kondusif","Adaptif melayani masyarakat; kompeten mengedepankan kesetiaan negara","Adaptif menjaga rahasia negara; kompeten melibatkan berbagai pihak"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 8","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Adaptif menitikberatkan inovasi, kreativitas, respons terhadap perubahan, dan sikap proaktif; kompeten menitikberatkan belajar, membantu pembelajaran, dan kualitas terbaik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Adaptif menggerakkan inovasi; kompeten mengembangkan kapabilitas belajar</mark>.</strong> Adaptif menitikberatkan inovasi, kreativitas, respons terhadap perubahan, dan sikap proaktif; kompeten menitikberatkan belajar, membantu pembelajaran, dan kualitas terbaik.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Adaptif membantu orang lain belajar; kompeten memegang teguh Pancasila: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Adaptif menggerakkan inovasi; kompeten mengembangkan kapabilitas belajar</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Adaptif mengelola BMN efisien; kompeten membangun lingkungan kondusif: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Adaptif menggerakkan inovasi; kompeten mengembangkan kapabilitas belajar</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Adaptif melayani masyarakat; kompeten mengedepankan kesetiaan negara: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Adaptif menggerakkan inovasi; kompeten mengembangkan kapabilitas belajar</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Adaptif menjaga rahasia negara; kompeten melibatkan berbagai pihak: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Adaptif menggerakkan inovasi; kompeten mengembangkan kapabilitas belajar</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Adaptif menitikberatkan inovasi, kreativitas, respons terhadap perubahan, dan sikap proaktif; kompeten menitikberatkan belajar, membantu pembelajaran, dan kualitas terbaik.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 8.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Butir 'memberi kesempatan berbagai pihak untuk berkontribusi' merupakan penjabaran ....","options":["Harmonis","Berorientasi pelayanan","Kolaboratif","Kompeten","Adaptif"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 8","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Kolaboratif dijabarkan melalui kesempatan berkontribusi, keterbukaan kerja sama bernilai tambah, dan pemanfaatan sumber daya untuk tujuan bersama.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Kolaboratif</mark>.</strong> Kolaboratif dijabarkan melalui kesempatan berkontribusi, keterbukaan kerja sama bernilai tambah, dan pemanfaatan sumber daya untuk tujuan bersama.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Harmonis: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Kolaboratif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Berorientasi pelayanan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Kolaboratif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Kompeten: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Kolaboratif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Adaptif: menitikberatkan pembaruan atau perbaikan berkelanjutan. Fokus tersebut berbeda dari <strong>Kolaboratif</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Kolaboratif dijabarkan melalui kesempatan berkontribusi, keterbukaan kerja sama bernilai tambah, dan pemanfaatan sumber daya untuk tujuan bersama.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 8.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Aplikasi","question":"Tim mengundang unit lain memberikan kontribusi dan menggabungkan sumber daya menuju sasaran bersama. Nilai yang paling khusus tercermin adalah ....","options":["Harmonis","Kolaboratif","Adaptif","Akuntabel","Kompeten"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 8","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Skenario memuat dua indikator eksplisit kolaboratif: melibatkan kontribusi banyak pihak dan pemanfaatan sumber daya untuk tujuan bersama, bukan sekadar saling peduli atau proaktif.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Kolaboratif</mark>.</strong> Skenario memuat dua indikator eksplisit kolaboratif: melibatkan kontribusi banyak pihak dan pemanfaatan sumber daya untuk tujuan bersama, bukan sekadar saling peduli atau proaktif.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Harmonis: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Kolaboratif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Adaptif: menitikberatkan pembaruan atau perbaikan berkelanjutan. Fokus tersebut berbeda dari <strong>Kolaboratif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Akuntabel: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Kolaboratif</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Kompeten: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Kolaboratif</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Skenario memuat dua indikator eksplisit kolaboratif: melibatkan kontribusi banyak pihak dan pemanfaatan sumber daya untuk tujuan bersama, bukan sekadar saling peduli atau proaktif.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 8.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Pasangan indikator nilai dasar yang tepat adalah ....","options":["Loyal—perbaikan tiada henti; harmonis—mengelola BMN","Loyal—memberi kesempatan kontribusi; harmonis—inovasi","Loyal—membangun kerja sama; harmonis—menjaga rahasia","Loyal—menjaga nama baik ASN; harmonis—menghargai perbedaan","Loyal—meningkatkan kompetensi; harmonis—membantu belajar"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 8","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Loyal meliputi penjagaan nama baik ASN/instansi/negara, harmonis mencakup penghargaan atas latar belakang berbeda. Alternatif lain memasangkan indikator nilai berbeda.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Loyal—menjaga nama baik ASN; harmonis—menghargai perbedaan</mark>.</strong> Loyal meliputi penjagaan nama baik ASN/instansi/negara, harmonis mencakup penghargaan atas latar belakang berbeda. Alternatif lain memasangkan indikator nilai berbeda.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Loyal—perbaikan tiada henti; harmonis—mengelola BMN: menitikberatkan pembaruan atau perbaikan berkelanjutan. Fokus tersebut berbeda dari <strong>Loyal—menjaga nama baik ASN; harmonis—menghargai perbedaan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Loyal—memberi kesempatan kontribusi; harmonis—inovasi: menitikberatkan pembaruan atau perbaikan berkelanjutan. Fokus tersebut berbeda dari <strong>Loyal—menjaga nama baik ASN; harmonis—menghargai perbedaan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Loyal—membangun kerja sama; harmonis—menjaga rahasia: menekankan kerja sama dan koordinasi. Fokus tersebut berbeda dari <strong>Loyal—menjaga nama baik ASN; harmonis—menghargai perbedaan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Loyal—meningkatkan kompetensi; harmonis—membantu belajar: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Loyal—menjaga nama baik ASN; harmonis—menghargai perbedaan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Loyal meliputi penjagaan nama baik ASN/instansi/negara, harmonis mencakup penghargaan atas latar belakang berbeda. Alternatif lain memasangkan indikator nilai berbeda.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 8.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Ketentuan","question":"Penjabaran nilai dasar ASN menjadi kode etik dan kode perilaku dalam UU 20/2023 tercantum pada ....","options":["Pasal 7","Pasal 101","Pasal 4 ayat (2)","Pasal 3","Pasal 4 ayat (1)"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 8","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Halaman 8 mengawali rincian tujuh nilai pada Pasal 4 ayat (2). Pasal 3 berisi daftar nilai dan Pasal 4 ayat (1) memuat tujuan kode etik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Pasal 4 ayat (2)</mark>.</strong> Halaman 8 mengawali rincian tujuh nilai pada Pasal 4 ayat (2). Pasal 3 berisi daftar nilai dan Pasal 4 ayat (1) memuat tujuan kode etik.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Pasal 7: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 4 ayat (2)</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Pasal 101: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 4 ayat (2)</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Pasal 3: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 4 ayat (2)</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Pasal 4 ayat (1): menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 4 ayat (2)</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Halaman 8 mengawali rincian tujuh nilai pada Pasal 4 ayat (2). Pasal 3 berisi daftar nilai dan Pasal 4 ayat (1) memuat tujuan kode etik.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 8.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Ketentuan","question":"Pengertian kode etik PNS sebagai pedoman sikap, tingkah laku, dan perbuatan diatur pada ....","options":["Pasal 101 PP 11 Tahun 2017","Pasal 7 PP 42 Tahun 2004","Pasal 1 ayat (2) PP 42 Tahun 2004","Pasal 13 PP 42 Tahun 2004","Pasal 4 ayat (1) UU 20 Tahun 2023"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 9","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Definisi kode etik PNS berasal dari Pasal 1 ayat (2) PP 42/2004, bukan pasal tentang jenis etika, pengaturan profesi, atau nilai ASN.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Pasal 1 ayat (2) PP 42 Tahun 2004</mark>.</strong> Definisi kode etik PNS berasal dari Pasal 1 ayat (2) PP 42/2004, bukan pasal tentang jenis etika, pengaturan profesi, atau nilai ASN.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Pasal 101 PP 11 Tahun 2017: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 1 ayat (2) PP 42 Tahun 2004</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Pasal 7 PP 42 Tahun 2004: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 1 ayat (2) PP 42 Tahun 2004</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Pasal 13 PP 42 Tahun 2004: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 1 ayat (2) PP 42 Tahun 2004</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Pasal 4 ayat (1) UU 20 Tahun 2023: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 1 ayat (2) PP 42 Tahun 2004</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Definisi kode etik PNS berasal dari Pasal 1 ayat (2) PP 42/2004, bukan pasal tentang jenis etika, pengaturan profesi, atau nilai ASN.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 9.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"PNS wajib berpedoman pada ranah etika sebagaimana Pasal 7 PP 42/2004 dalam konteks ....","options":["Pengawasan internal dan penilaian disiplin saja","Tugas kedinasan dan kehidupan sehari-hari","Pelayanan publik dan evaluasi prestasi kerja saja","Kegiatan kedinasan dan pembinaan karier saja","Pelaksanaan jabatan dan seleksi pegawai saja"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 9","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Pasal 7 secara eksplisit mengatur perilaku dalam pelaksanaan tugas kedinasan sekaligus kehidupan sehari-hari; pilihan lain membatasi secara tidak tepat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Tugas kedinasan dan kehidupan sehari-hari</mark>.</strong> Pasal 7 secara eksplisit mengatur perilaku dalam pelaksanaan tugas kedinasan sekaligus kehidupan sehari-hari; pilihan lain membatasi secara tidak tepat.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Pengawasan internal dan penilaian disiplin saja: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Tugas kedinasan dan kehidupan sehari-hari</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Pelayanan publik dan evaluasi prestasi kerja saja: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Tugas kedinasan dan kehidupan sehari-hari</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Kegiatan kedinasan dan pembinaan karier saja: berkaitan dengan proses pengenalan dan pembinaan etika. Fokus tersebut berbeda dari <strong>Tugas kedinasan dan kehidupan sehari-hari</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Pelaksanaan jabatan dan seleksi pegawai saja: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Tugas kedinasan dan kehidupan sehari-hari</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Pasal 7 secara eksplisit mengatur perilaku dalam pelaksanaan tugas kedinasan sekaligus kehidupan sehari-hari; pilihan lain membatasi secara tidak tepat.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 9.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Butir 'menjadi perekat dan pemersatu bangsa dalam NKRI' diklasifikasikan sebagai etika ....","options":["Dalam bermasyarakat","Dalam bernegara","Terhadap sesama PNS","Terhadap diri sendiri","Dalam berorganisasi"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 9","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Butir pemersatu bangsa tercantum dalam etika bernegara; kategori lainnya berfokus tata kerja organisasi, layanan sosial, pengelolaan diri, atau relasi sesama pegawai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Dalam bernegara</mark>.</strong> Butir pemersatu bangsa tercantum dalam etika bernegara; kategori lainnya berfokus tata kerja organisasi, layanan sosial, pengelolaan diri, atau relasi sesama pegawai.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Dalam bermasyarakat: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Dalam bernegara</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Terhadap sesama PNS: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Dalam bernegara</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Terhadap diri sendiri: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Dalam bernegara</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Dalam berorganisasi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Dalam bernegara</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Butir pemersatu bangsa tercantum dalam etika bernegara; kategori lainnya berfokus tata kerja organisasi, layanan sosial, pengelolaan diri, atau relasi sesama pegawai.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 9.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Kewajiban 'tidak memberikan kesaksian palsu' termasuk etika ....","options":["Dalam bernegara","Terhadap sesama PNS","Terhadap diri sendiri","Dalam bermasyarakat","Dalam berorganisasi"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 9","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Larangan kesaksian palsu tercantum pada butir etika bernegara. Meski berkaitan dengan kejujuran pribadi, klasifikasi normatif di halaman 9 adalah bernegara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Dalam bernegara</mark>.</strong> Larangan kesaksian palsu tercantum pada butir etika bernegara. Meski berkaitan dengan kejujuran pribadi, klasifikasi normatif di halaman 9 adalah bernegara.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Terhadap sesama PNS: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Dalam bernegara</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Terhadap diri sendiri: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Dalam bernegara</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Dalam bermasyarakat: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Dalam bernegara</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Dalam berorganisasi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Dalam bernegara</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Larangan kesaksian palsu tercantum pada butir etika bernegara. Meski berkaitan dengan kejujuran pribadi, klasifikasi normatif di halaman 9 adalah bernegara.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 9.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Pembeda akuntabilitas dalam etika bernegara dan kompetensi dalam etika berorganisasi adalah ....","options":["Yang pertama menyusun SOP; yang kedua memberikan kesaksian sesuai fakta","Yang pertama mengembangkan pemikiran; yang kedua menggunakan sumber daya negara","Yang pertama menjaga informasi rahasia; yang kedua menjunjung nilai Pancasila","Yang pertama menghormati rekan; yang kedua mengutamakan layanan masyarakat","Yang pertama tanggung jawab penyelenggaraan bersih; yang kedua kecakapan melaksanakan tugas"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 9","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Etika bernegara memuat akuntabilitas pemerintahan bersih/berwibawa; etika berorganisasi memuat kompetensi pelaksanaan tugas. Opsi lain membalik atau mencampur klasifikasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Yang pertama tanggung jawab penyelenggaraan bersih; yang kedua kecakapan melaksanakan tugas</mark>.</strong> Etika bernegara memuat akuntabilitas pemerintahan bersih/berwibawa; etika berorganisasi memuat kompetensi pelaksanaan tugas. Opsi lain membalik atau mencampur klasifikasi.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Yang pertama menyusun SOP; yang kedua memberikan kesaksian sesuai fakta: menekankan kebenaran fakta dan kejujuran keterangan. Fokus tersebut berbeda dari <strong>Yang pertama tanggung jawab penyelenggaraan bersih; yang kedua kecakapan melaksanakan tugas</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Yang pertama mengembangkan pemikiran; yang kedua menggunakan sumber daya negara: menyasar kesetiaan dan kepentingan negara. Fokus tersebut berbeda dari <strong>Yang pertama tanggung jawab penyelenggaraan bersih; yang kedua kecakapan melaksanakan tugas</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Yang pertama menjaga informasi rahasia; yang kedua menjunjung nilai Pancasila: menyasar kesetiaan dan kepentingan negara. Fokus tersebut berbeda dari <strong>Yang pertama tanggung jawab penyelenggaraan bersih; yang kedua kecakapan melaksanakan tugas</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Yang pertama menghormati rekan; yang kedua mengutamakan layanan masyarakat: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Yang pertama tanggung jawab penyelenggaraan bersih; yang kedua kecakapan melaksanakan tugas</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Etika bernegara memuat akuntabilitas pemerintahan bersih/berwibawa; etika berorganisasi memuat kompetensi pelaksanaan tugas. Opsi lain membalik atau mencampur klasifikasi.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 9.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Butir 'menjalin kerja sama secara kooperatif dengan unit kerja lain' termasuk etika ....","options":["Terhadap diri sendiri","Terhadap sesama PNS","Dalam bernegara","Dalam bermasyarakat","Dalam berorganisasi"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 9","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Kolaborasi lintas unit untuk pencapaian tujuan tercantum dalam daftar etika berorganisasi; bukan ranah kehidupan masyarakat atau hubungan pribadi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Dalam berorganisasi</mark>.</strong> Kolaborasi lintas unit untuk pencapaian tujuan tercantum dalam daftar etika berorganisasi; bukan ranah kehidupan masyarakat atau hubungan pribadi.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Terhadap diri sendiri: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Dalam berorganisasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Terhadap sesama PNS: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Dalam berorganisasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Dalam bernegara: menyasar kesetiaan dan kepentingan negara. Fokus tersebut berbeda dari <strong>Dalam berorganisasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Dalam bermasyarakat: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Dalam berorganisasi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Kolaborasi lintas unit untuk pencapaian tujuan tercantum dalam daftar etika berorganisasi; bukan ranah kehidupan masyarakat atau hubungan pribadi.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 9.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Pasal 7 PP 42 Tahun 2004 mencakup semua ranah berikut, kecuali ....","options":["Etika dalam pengelolaan keuangan pribadi","Etika dalam kehidupan bermasyarakat","Etika dalam penyelenggaraan pemerintahan","Etika terhadap sesama Pegawai Negeri Sipil","Etika terhadap diri sendiri"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 9","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Enam ranah disebut: bernegara, penyelenggaraan pemerintahan, berorganisasi, bermasyarakat, diri sendiri, dan sesama PNS. &#x27;Pengelolaan keuangan pribadi&#x27; bukan nama ranah Pasal 7.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Etika dalam pengelolaan keuangan pribadi</mark>.</strong> Enam ranah disebut: bernegara, penyelenggaraan pemerintahan, berorganisasi, bermasyarakat, diri sendiri, dan sesama PNS. &#x27;Pengelolaan keuangan pribadi&#x27; bukan nama ranah Pasal 7.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Etika dalam kehidupan bermasyarakat: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Etika dalam pengelolaan keuangan pribadi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Etika dalam penyelenggaraan pemerintahan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Etika dalam pengelolaan keuangan pribadi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Etika terhadap sesama Pegawai Negeri Sipil: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Etika dalam pengelolaan keuangan pribadi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Etika terhadap diri sendiri: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Etika dalam pengelolaan keuangan pribadi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Enam ranah disebut: bernegara, penyelenggaraan pemerintahan, berorganisasi, bermasyarakat, diri sendiri, dan sesama PNS. &#x27;Pengelolaan keuangan pribadi&#x27; bukan nama ranah Pasal 7.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 9.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Aplikasi","question":"Pemanfaatan sumber daya negara secara efisien dalam pelaksanaan kebijakan pemerintah termasuk etika PNS dalam ....","options":["Etika terhadap diri sendiri","Etika terhadap sesama PNS","Etika dalam berorganisasi","Etika dalam bernegara","Etika dalam bermasyarakat"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 9","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Pemanfaatan sumber daya negara secara efektif-efisien berada di bawah etika bernegara, meskipun penggunaan sarana juga dapat terkait nilai akuntabel pada bagian lain.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Etika dalam bernegara</mark>.</strong> Pemanfaatan sumber daya negara secara efektif-efisien berada di bawah etika bernegara, meskipun penggunaan sarana juga dapat terkait nilai akuntabel pada bagian lain.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Etika terhadap diri sendiri: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Etika dalam bernegara</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Etika terhadap sesama PNS: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Etika dalam bernegara</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Etika dalam berorganisasi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Etika dalam bernegara</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Etika dalam bermasyarakat: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Etika dalam bernegara</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Pemanfaatan sumber daya negara secara efektif-efisien berada di bawah etika bernegara, meskipun penggunaan sarana juga dapat terkait nilai akuntabel pada bagian lain.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 9.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Manakah pasangan yang sama-sama tercantum dalam etika berorganisasi?","options":["Menjaga persatuan bangsa dan tidak memberi kesaksian palsu","Menghargai pendapat rekan dan menjalin kerja sama sejawat","Memelihara kesehatan diri dan menjaga keutuhan keluarga","Memberi layanan tanpa diskriminasi dan menunjukkan empati","Menjaga informasi rahasia dan mematuhi standar tata kerja"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 9","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Etika berorganisasi berisi penjagaan informasi rahasia dan patuh standar operasional/tata kerja. Pasangan lain merupakan etika bernegara, bermasyarakat, terhadap diri, atau sesama pegawai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Menjaga informasi rahasia dan mematuhi standar tata kerja</mark>.</strong> Etika berorganisasi berisi penjagaan informasi rahasia dan patuh standar operasional/tata kerja. Pasangan lain merupakan etika bernegara, bermasyarakat, terhadap diri, atau sesama pegawai.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Menjaga persatuan bangsa dan tidak memberi kesaksian palsu: menyasar kesetiaan dan kepentingan negara. Fokus tersebut berbeda dari <strong>Menjaga informasi rahasia dan mematuhi standar tata kerja</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Menghargai pendapat rekan dan menjalin kerja sama sejawat: membahas pengaruh lingkungan dan etos kerja. Fokus tersebut berbeda dari <strong>Menjaga informasi rahasia dan mematuhi standar tata kerja</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Memelihara kesehatan diri dan menjaga keutuhan keluarga: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Menjaga informasi rahasia dan mematuhi standar tata kerja</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Memberi layanan tanpa diskriminasi dan menunjukkan empati: berkaitan dengan keadilan serta perlakuan setara. Fokus tersebut berbeda dari <strong>Menjaga informasi rahasia dan mematuhi standar tata kerja</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Etika berorganisasi berisi penjagaan informasi rahasia dan patuh standar operasional/tata kerja. Pasangan lain merupakan etika bernegara, bermasyarakat, terhadap diri, atau sesama pegawai.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 9.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Konsep","question":"Ruang lingkup definisi kode etik PNS pada PP 42/2004 mencakup ....","options":["Pelaksanaan fungsi jabatan dan penilaian masa kerja","Pengembangan kompetensi dan promosi jabatan","Pengawasan internal dan evaluasi hasil anggaran","Pelaksanaan tugas dan pergaulan hidup sehari-hari","Pembinaan karier dan penetapan organisasi profesi"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 9","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Definisi kode etik PNS mencakup perilaku dalam tugas sekaligus pergaulan sehari-hari; empat opsi lainnya membatasi pada aspek manajemen kepegawaian tertentu.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Pelaksanaan tugas dan pergaulan hidup sehari-hari</mark>.</strong> Definisi kode etik PNS mencakup perilaku dalam tugas sekaligus pergaulan sehari-hari; empat opsi lainnya membatasi pada aspek manajemen kepegawaian tertentu.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Pelaksanaan fungsi jabatan dan penilaian masa kerja: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Pelaksanaan tugas dan pergaulan hidup sehari-hari</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Pengembangan kompetensi dan promosi jabatan: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Pelaksanaan tugas dan pergaulan hidup sehari-hari</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Pengawasan internal dan evaluasi hasil anggaran: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Pelaksanaan tugas dan pergaulan hidup sehari-hari</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Pembinaan karier dan penetapan organisasi profesi: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Pelaksanaan tugas dan pergaulan hidup sehari-hari</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Definisi kode etik PNS mencakup perilaku dalam tugas sekaligus pergaulan sehari-hari; empat opsi lainnya membatasi pada aspek manajemen kepegawaian tertentu.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 9.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Butir 'mewujudkan pola hidup sederhana' diklasifikasikan sebagai etika ....","options":["Dalam berorganisasi","Dalam bernegara","Terhadap diri sendiri","Terhadap sesama PNS","Dalam bermasyarakat"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 10","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>PP 42/2004 dalam uraian halaman 10 menempatkan pola hidup sederhana pada etika bermasyarakat, bukan etika diri sendiri meskipun berhubungan dengan perilaku pribadi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Dalam bermasyarakat</mark>.</strong> PP 42/2004 dalam uraian halaman 10 menempatkan pola hidup sederhana pada etika bermasyarakat, bukan etika diri sendiri meskipun berhubungan dengan perilaku pribadi.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Dalam berorganisasi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Dalam bermasyarakat</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Dalam bernegara: menyasar kesetiaan dan kepentingan negara. Fokus tersebut berbeda dari <strong>Dalam bermasyarakat</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Terhadap diri sendiri: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Dalam bermasyarakat</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Terhadap sesama PNS: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Dalam bermasyarakat</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>PP 42/2004 dalam uraian halaman 10 menempatkan pola hidup sederhana pada etika bermasyarakat, bukan etika diri sendiri meskipun berhubungan dengan perilaku pribadi.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 10.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Butir 'berpenampilan sederhana, rapi, dan sopan' berada pada kategori ....","options":["Terhadap diri sendiri","Terhadap sesama PNS","Dalam bernegara","Dalam berorganisasi","Dalam bermasyarakat"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 10","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Penampilan sederhana, rapi, dan sopan merupakan butir etika terhadap diri sendiri. Ini berbeda dari pola hidup sederhana yang dikategorikan dalam etika bermasyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Terhadap diri sendiri</mark>.</strong> Penampilan sederhana, rapi, dan sopan merupakan butir etika terhadap diri sendiri. Ini berbeda dari pola hidup sederhana yang dikategorikan dalam etika bermasyarakat.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Terhadap sesama PNS: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Terhadap diri sendiri</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Dalam bernegara: menyasar kesetiaan dan kepentingan negara. Fokus tersebut berbeda dari <strong>Terhadap diri sendiri</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Dalam berorganisasi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Terhadap diri sendiri</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Dalam bermasyarakat: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Terhadap diri sendiri</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Penampilan sederhana, rapi, dan sopan merupakan butir etika terhadap diri sendiri. Ini berbeda dari pola hidup sederhana yang dikategorikan dalam etika bermasyarakat.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 10.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Pasangan kewajiban dan kelompok etika PNS yang tepat adalah ....","options":["Bermasyarakat—pola hidup sederhana; diri sendiri—penampilan rapi","Bermasyarakat—menjaga kesehatan; diri sendiri—melayani dengan empati","Bermasyarakat—mengembangkan kompetensi; diri sendiri—menghargai pendapat","Bermasyarakat—menjaga persatuan PNS; diri sendiri—menjaga kerja sama","Bermasyarakat—menjaga rahasia; diri sendiri—mematuhi standar kerja"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 10","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Butir pola hidup sederhana ada pada bermasyarakat, sedangkan penampilan rapi ada pada diri sendiri. Pilihan lain memindahkan butir ke ranah yang tidak sesuai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Bermasyarakat—pola hidup sederhana; diri sendiri—penampilan rapi</mark>.</strong> Butir pola hidup sederhana ada pada bermasyarakat, sedangkan penampilan rapi ada pada diri sendiri. Pilihan lain memindahkan butir ke ranah yang tidak sesuai.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Bermasyarakat—menjaga kesehatan; diri sendiri—melayani dengan empati: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Bermasyarakat—pola hidup sederhana; diri sendiri—penampilan rapi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Bermasyarakat—mengembangkan kompetensi; diri sendiri—menghargai pendapat: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Bermasyarakat—pola hidup sederhana; diri sendiri—penampilan rapi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Bermasyarakat—menjaga persatuan PNS; diri sendiri—menjaga kerja sama: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Bermasyarakat—pola hidup sederhana; diri sendiri—penampilan rapi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Bermasyarakat—menjaga rahasia; diri sendiri—mematuhi standar kerja: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Bermasyarakat—pola hidup sederhana; diri sendiri—penampilan rapi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Butir pola hidup sederhana ada pada bermasyarakat, sedangkan penampilan rapi ada pada diri sendiri. Pilihan lain memindahkan butir ke ranah yang tidak sesuai.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 10.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Kewajiban 'menghargai perbedaan pendapat' berada pada etika ....","options":["Dalam bermasyarakat","Dalam bernegara","Dalam berorganisasi","Terhadap sesama Pegawai Negeri Sipil","Terhadap diri sendiri"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 10","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Penghargaan terhadap perbedaan pendapat dicantumkan pada etika terhadap sesama PNS bersama penghormatan rekan dan kerja sama antarsesama pegawai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Terhadap sesama Pegawai Negeri Sipil</mark>.</strong> Penghargaan terhadap perbedaan pendapat dicantumkan pada etika terhadap sesama PNS bersama penghormatan rekan dan kerja sama antarsesama pegawai.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Dalam bermasyarakat: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Terhadap sesama Pegawai Negeri Sipil</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Dalam bernegara: menyasar kesetiaan dan kepentingan negara. Fokus tersebut berbeda dari <strong>Terhadap sesama Pegawai Negeri Sipil</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Dalam berorganisasi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Terhadap sesama Pegawai Negeri Sipil</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Terhadap diri sendiri: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Terhadap sesama Pegawai Negeri Sipil</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Penghargaan terhadap perbedaan pendapat dicantumkan pada etika terhadap sesama PNS bersama penghormatan rekan dan kerja sama antarsesama pegawai.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 10.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Aplikasi","question":"Kewajiban menjaga keutuhan dan keharmonisan keluarga berada pada kategori ....","options":["Etika terhadap sesama PNS","Etika dalam berorganisasi","Etika dalam bermasyarakat","Etika terhadap diri sendiri","Etika dalam bernegara"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 10","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Butir menjaga keluarga secara eksplisit berada pada daftar etika terhadap diri sendiri pada halaman 10.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Etika terhadap diri sendiri</mark>.</strong> Butir menjaga keluarga secara eksplisit berada pada daftar etika terhadap diri sendiri pada halaman 10.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Etika terhadap sesama PNS: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Etika terhadap diri sendiri</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Etika dalam berorganisasi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Etika terhadap diri sendiri</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Etika dalam bermasyarakat: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Etika terhadap diri sendiri</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Etika dalam bernegara: menyasar kesetiaan dan kepentingan negara. Fokus tersebut berbeda dari <strong>Etika terhadap diri sendiri</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Butir menjaga keluarga secara eksplisit berada pada daftar etika terhadap diri sendiri pada halaman 10.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 10.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Dalam etika bermasyarakat, pelayanan yang diharapkan ditandai oleh kombinasi ....","options":["Kesetiaan, penghormatan jabatan, dan rahasia dinas","Kompetensi, ketegasan sanksi, dan standar tata kerja","Kerja sama antarrekan, persatuan korps, dan rasa hormat","Kreativitas, daya juang pribadi, dan pemeliharaan kesehatan","Empati, kesantunan, tanpa pamrih, dan tanpa pemaksaan"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 10","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Pelayanan dengan empati, hormat, santun, tanpa pamrih/pemaksaan merupakan butir bermasyarakat; opsi lain menggabungkan hal baik dari ranah etika berbeda.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Empati, kesantunan, tanpa pamrih, dan tanpa pemaksaan</mark>.</strong> Pelayanan dengan empati, hormat, santun, tanpa pamrih/pemaksaan merupakan butir bermasyarakat; opsi lain menggabungkan hal baik dari ranah etika berbeda.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Kesetiaan, penghormatan jabatan, dan rahasia dinas: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Empati, kesantunan, tanpa pamrih, dan tanpa pemaksaan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Kompetensi, ketegasan sanksi, dan standar tata kerja: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Empati, kesantunan, tanpa pamrih, dan tanpa pemaksaan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Kerja sama antarrekan, persatuan korps, dan rasa hormat: membahas pengaruh lingkungan dan etos kerja. Fokus tersebut berbeda dari <strong>Empati, kesantunan, tanpa pamrih, dan tanpa pemaksaan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Kreativitas, daya juang pribadi, dan pemeliharaan kesehatan: menitikberatkan pembaruan atau perbaikan berkelanjutan. Fokus tersebut berbeda dari <strong>Empati, kesantunan, tanpa pamrih, dan tanpa pemaksaan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Pelayanan dengan empati, hormat, santun, tanpa pamrih/pemaksaan merupakan butir bermasyarakat; opsi lain menggabungkan hal baik dari ranah etika berbeda.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 10.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Aplikasi","question":"Pegawai melakukan pembaruan kemampuan dan keterampilan secara mandiri, tanpa mengaitkannya pada kebutuhan unit tertentu. Butir yang paling sesuai adalah ....","options":["Etika dalam berorganisasi","Etika terhadap diri sendiri","Etika terhadap sesama PNS","Etika dalam bernegara","Etika dalam bermasyarakat"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 10","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Inisiatif meningkatkan kualitas pengetahuan, kemampuan, keterampilan, dan sikap tercantum pada etika diri sendiri. Etika berorganisasi juga menyebut kompetensi, namun skenario sengaja menitikberatkan inisiatif pengembangan kualitas diri.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Etika terhadap diri sendiri</mark>.</strong> Inisiatif meningkatkan kualitas pengetahuan, kemampuan, keterampilan, dan sikap tercantum pada etika diri sendiri. Etika berorganisasi juga menyebut kompetensi, namun skenario sengaja menitikberatkan inisiatif pengembangan kualitas diri.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Etika dalam berorganisasi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Etika terhadap diri sendiri</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Etika terhadap sesama PNS: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Etika terhadap diri sendiri</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Etika dalam bernegara: menyasar kesetiaan dan kepentingan negara. Fokus tersebut berbeda dari <strong>Etika terhadap diri sendiri</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Etika dalam bermasyarakat: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Etika terhadap diri sendiri</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Inisiatif meningkatkan kualitas pengetahuan, kemampuan, keterampilan, dan sikap tercantum pada etika diri sendiri. Etika berorganisasi juga menyebut kompetensi, namun skenario sengaja menitikberatkan inisiatif pengembangan kualitas diri.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 10.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Pernyataan yang termasuk etika terhadap sesama PNS, bukan terhadap diri sendiri, adalah ....","options":["Mengembangkan kualitas pengetahuan dan keterampilan pribadi","Saling menghormati teman sejawat secara vertikal maupun horizontal","Menjaga keutuhan serta keharmonisan hubungan keluarga","Berpenampilan sederhana, rapi, serta sopan dalam bekerja","Memelihara kondisi kesehatan jasmani maupun rohani"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 10","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Menghormati rekan sejawat baik vertikal maupun horizontal terdapat dalam etika sesama PNS. Keempat opsi lain merupakan butir etika terhadap diri sendiri.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Saling menghormati teman sejawat secara vertikal maupun horizontal</mark>.</strong> Menghormati rekan sejawat baik vertikal maupun horizontal terdapat dalam etika sesama PNS. Keempat opsi lain merupakan butir etika terhadap diri sendiri.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Mengembangkan kualitas pengetahuan dan keterampilan pribadi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Saling menghormati teman sejawat secara vertikal maupun horizontal</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Menjaga keutuhan serta keharmonisan hubungan keluarga: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Saling menghormati teman sejawat secara vertikal maupun horizontal</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Berpenampilan sederhana, rapi, serta sopan dalam bekerja: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Saling menghormati teman sejawat secara vertikal maupun horizontal</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Memelihara kondisi kesehatan jasmani maupun rohani: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Saling menghormati teman sejawat secara vertikal maupun horizontal</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Menghormati rekan sejawat baik vertikal maupun horizontal terdapat dalam etika sesama PNS. Keempat opsi lain merupakan butir etika terhadap diri sendiri.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 10.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Dua kewajiban yang sama-sama merupakan etika terhadap diri sendiri adalah ....","options":["Memberi pelayanan berempati dan menanggapi lingkungan","Menghargai pendapat sejawat dan menjaga persatuan PNS","Menjaga informasi rahasia dan menata etos organisasi","Menghindari konflik kepentingan dan memelihara kesehatan","Menggunakan sumber daya negara dan menaati peraturan"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 10","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Daftar terhadap diri sendiri meliputi menghindari konflik kepentingan dan memelihara kesehatan jasmani-rohani. Pasangan lainnya berasal dari bermasyarakat, berorganisasi, sesama PNS, atau bernegara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Menghindari konflik kepentingan dan memelihara kesehatan</mark>.</strong> Daftar terhadap diri sendiri meliputi menghindari konflik kepentingan dan memelihara kesehatan jasmani-rohani. Pasangan lainnya berasal dari bermasyarakat, berorganisasi, sesama PNS, atau bernegara.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Memberi pelayanan berempati dan menanggapi lingkungan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Menghindari konflik kepentingan dan memelihara kesehatan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Menghargai pendapat sejawat dan menjaga persatuan PNS: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Menghindari konflik kepentingan dan memelihara kesehatan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Menjaga informasi rahasia dan menata etos organisasi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Menghindari konflik kepentingan dan memelihara kesehatan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Menggunakan sumber daya negara dan menaati peraturan: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Menghindari konflik kepentingan dan memelihara kesehatan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Daftar terhadap diri sendiri meliputi menghindari konflik kepentingan dan memelihara kesehatan jasmani-rohani. Pasangan lainnya berasal dari bermasyarakat, berorganisasi, sesama PNS, atau bernegara.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 10.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Konsep","question":"Pelayanan tanpa unsur pemaksaan ditempatkan pada etika PNS dalam ....","options":["Sesama Pegawai Negeri Sipil","Berorganisasi","Bermasyarakat","Diri sendiri","Bernegara"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 10","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Halaman 10 memasukkan pelayanan empatik, hormat, santun, tanpa pamrih dan pemaksaan dalam etika bermasyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Bermasyarakat</mark>.</strong> Halaman 10 memasukkan pelayanan empatik, hormat, santun, tanpa pamrih dan pemaksaan dalam etika bermasyarakat.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Sesama Pegawai Negeri Sipil: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Bermasyarakat</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Berorganisasi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Bermasyarakat</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Diri sendiri: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Bermasyarakat</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Bernegara: menyasar kesetiaan dan kepentingan negara. Fokus tersebut berbeda dari <strong>Bermasyarakat</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Halaman 10 memasukkan pelayanan empatik, hormat, santun, tanpa pamrih dan pemaksaan dalam etika bermasyarakat.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 10.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Ketentuan","question":"Pihak yang menetapkan kode etik instansi menurut Pasal 13–14 PP 42/2004 adalah ....","options":["Direktur unit kerja yang menyelenggarakan layanan","Majelis khusus pemeriksa pelanggaran kode etik","Pimpinan instansi pembina Jabatan Fungsional terkait","Pejabat Pembina Kepegawaian pada instansi masing-masing","Pengurus organisasi profesi Jabatan Fungsional terkait"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 11","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Kode etik instansi ditetapkan Pejabat Pembina Kepegawaian sesuai karakter instansi; kode etik profesi ditetapkan organisasi profesinya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Pejabat Pembina Kepegawaian pada instansi masing-masing</mark>.</strong> Kode etik instansi ditetapkan Pejabat Pembina Kepegawaian sesuai karakter instansi; kode etik profesi ditetapkan organisasi profesinya.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Direktur unit kerja yang menyelenggarakan layanan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Pejabat Pembina Kepegawaian pada instansi masing-masing</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Majelis khusus pemeriksa pelanggaran kode etik: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Pejabat Pembina Kepegawaian pada instansi masing-masing</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Pimpinan instansi pembina Jabatan Fungsional terkait: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Pejabat Pembina Kepegawaian pada instansi masing-masing</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Pengurus organisasi profesi Jabatan Fungsional terkait: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Pejabat Pembina Kepegawaian pada instansi masing-masing</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Kode etik instansi ditetapkan Pejabat Pembina Kepegawaian sesuai karakter instansi; kode etik profesi ditetapkan organisasi profesinya.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 11.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Pihak yang menetapkan kode etik organisasi profesi di lingkungan PNS adalah ....","options":["Pejabat Pembina Kepegawaian setiap instansi","Organisasi profesi masing-masing","Unit yang mengelola data kepegawaian","Majelis Kehormatan Kode Etik instansi","Atasan langsung PNS yang bersangkutan"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 11","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>PP 42/2004 membedakan penetap kode etik profesi (organisasi profesi) dari kode etik instansi (PPK).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Organisasi profesi masing-masing</mark>.</strong> PP 42/2004 membedakan penetap kode etik profesi (organisasi profesi) dari kode etik instansi (PPK).</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Pejabat Pembina Kepegawaian setiap instansi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Organisasi profesi masing-masing</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Unit yang mengelola data kepegawaian: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Organisasi profesi masing-masing</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Majelis Kehormatan Kode Etik instansi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Organisasi profesi masing-masing</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Atasan langsung PNS yang bersangkutan: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Organisasi profesi masing-masing</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>PP 42/2004 membedakan penetap kode etik profesi (organisasi profesi) dari kode etik instansi (PPK).</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 11.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Konsep","question":"Dua syarat agar kode etik berfungsi adalah ....","options":["Ditetapkan organisasi profesi dan dijalankan tanpa evaluasi berkala","Ditetapkan PPK dan diterapkan oleh pimpinan unit kerja","Dirumuskan anggota profesi dan diawasi terus-menerus oleh komite khusus","Disusun kementerian dan diperbarui berdasarkan penilaian kinerja","Dirumuskan auditor dan diperiksa melalui tata kelola organisasi"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 11","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Dua syarat yang disebut: kode etik lahir dari nilai serta pemikiran anggota profesi, dan pelaksanaannya diawasi berkelanjutan termasuk penanganan pelanggaran oleh komite khusus.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Dirumuskan anggota profesi dan diawasi terus-menerus oleh komite khusus</mark>.</strong> Dua syarat yang disebut: kode etik lahir dari nilai serta pemikiran anggota profesi, dan pelaksanaannya diawasi berkelanjutan termasuk penanganan pelanggaran oleh komite khusus.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Ditetapkan organisasi profesi dan dijalankan tanpa evaluasi berkala: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Dirumuskan anggota profesi dan diawasi terus-menerus oleh komite khusus</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Ditetapkan PPK dan diterapkan oleh pimpinan unit kerja: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Dirumuskan anggota profesi dan diawasi terus-menerus oleh komite khusus</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Disusun kementerian dan diperbarui berdasarkan penilaian kinerja: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Dirumuskan anggota profesi dan diawasi terus-menerus oleh komite khusus</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Dirumuskan auditor dan diperiksa melalui tata kelola organisasi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Dirumuskan anggota profesi dan diawasi terus-menerus oleh komite khusus</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Dua syarat yang disebut: kode etik lahir dari nilai serta pemikiran anggota profesi, dan pelaksanaannya diawasi berkelanjutan termasuk penanganan pelanggaran oleh komite khusus.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 11.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Ketentuan","question":"Pasal yang mengatur organisasi profesi Jabatan Fungsional sebagaimana diuraikan ialah ....","options":["Pasal 4 UU Nomor 20 Tahun 2023","Pasal 7 PP Nomor 42 Tahun 2004","Pasal 13 PP Nomor 42 Tahun 2004","Pasal 101 PP Nomor 11 Tahun 2017","Pasal 14 PP Nomor 42 Tahun 2004"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 11","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Pasal 101 PP 11/2017 secara eksplisit memuat kewajiban organisasi profesi JF, kode etik, dan tugasnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Pasal 101 PP Nomor 11 Tahun 2017</mark>.</strong> Pasal 101 PP 11/2017 secara eksplisit memuat kewajiban organisasi profesi JF, kode etik, dan tugasnya.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Pasal 4 UU Nomor 20 Tahun 2023: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 101 PP Nomor 11 Tahun 2017</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Pasal 7 PP Nomor 42 Tahun 2004: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 101 PP Nomor 11 Tahun 2017</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Pasal 13 PP Nomor 42 Tahun 2004: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 101 PP Nomor 11 Tahun 2017</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Pasal 14 PP Nomor 42 Tahun 2004: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pasal 101 PP Nomor 11 Tahun 2017</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Pasal 101 PP 11/2017 secara eksplisit memuat kewajiban organisasi profesi JF, kode etik, dan tugasnya.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 11.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Batas waktu yang dicantumkan untuk memiliki organisasi profesi JF adalah ....","options":["Paling lama dua tahun sejak pelaksanaan tugas JF","Paling lama lima tahun sejak pengangkatan pertama pejabat","Paling lama tiga tahun setelah persetujuan instansi pembina","Paling lama tiga tahun sejak penetapan kode etik profesi","Paling lama lima tahun sejak tanggal penetapan JF"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 11","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Uraian Pasal 101 menetapkan paling lama lima tahun dihitung sejak tanggal penetapan Jabatan Fungsional, bukan sejak pengangkatan orang atau persetujuan kode etik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Paling lama lima tahun sejak tanggal penetapan JF</mark>.</strong> Uraian Pasal 101 menetapkan paling lama lima tahun dihitung sejak tanggal penetapan Jabatan Fungsional, bukan sejak pengangkatan orang atau persetujuan kode etik.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Paling lama dua tahun sejak pelaksanaan tugas JF: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Paling lama lima tahun sejak tanggal penetapan JF</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Paling lama lima tahun sejak pengangkatan pertama pejabat: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Paling lama lima tahun sejak tanggal penetapan JF</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Paling lama tiga tahun setelah persetujuan instansi pembina: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Paling lama lima tahun sejak tanggal penetapan JF</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Paling lama tiga tahun sejak penetapan kode etik profesi: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Paling lama lima tahun sejak tanggal penetapan JF</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Uraian Pasal 101 menetapkan paling lama lima tahun dihitung sejak tanggal penetapan Jabatan Fungsional, bukan sejak pengangkatan orang atau persetujuan kode etik.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 11.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Kode etik dan kode perilaku profesi JF ditetapkan setelah memperoleh persetujuan dari ....","options":["Atasan langsung pejabat fungsional","Majelis Kehormatan Kode Etik instansi","Pejabat Pembina Kepegawaian instansi pengguna","Pimpinan instansi pembina","Pengurus unit kepatuhan internal"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 11","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Organisasi profesi menyusun dan menetapkan kode etik serta perilaku profesi setelah mendapat persetujuan pimpinan instansi pembina sebagaimana Pasal 101 PP 11/2017.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Pimpinan instansi pembina</mark>.</strong> Organisasi profesi menyusun dan menetapkan kode etik serta perilaku profesi setelah mendapat persetujuan pimpinan instansi pembina sebagaimana Pasal 101 PP 11/2017.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Atasan langsung pejabat fungsional: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Pimpinan instansi pembina</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Majelis Kehormatan Kode Etik instansi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Pimpinan instansi pembina</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Pejabat Pembina Kepegawaian instansi pengguna: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Pimpinan instansi pembina</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Pengurus unit kepatuhan internal: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Pimpinan instansi pembina</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Organisasi profesi menyusun dan menetapkan kode etik serta perilaku profesi setelah mendapat persetujuan pimpinan instansi pembina sebagaimana Pasal 101 PP 11/2017.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 11.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Yang merupakan tugas organisasi profesi JF menurut Pasal 101 adalah ....","options":["Menetapkan formasi jabatan tiap unit","Melakukan pemeriksaan anggaran instansi","Mengangkat pejabat fungsional di instansi","Memberikan advokasi kepada anggota profesi","Menetapkan seluruh sanksi disiplin ASN"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 11","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Tugas organisasi profesi meliputi menyusun kode etik, memberi advokasi, dan memeriksa serta merekomendasikan pelanggaran kode etik profesi; opsi lain adalah kewenangan di luar daftar tersebut.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Memberikan advokasi kepada anggota profesi</mark>.</strong> Tugas organisasi profesi meliputi menyusun kode etik, memberi advokasi, dan memeriksa serta merekomendasikan pelanggaran kode etik profesi; opsi lain adalah kewenangan di luar daftar tersebut.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Menetapkan formasi jabatan tiap unit: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Memberikan advokasi kepada anggota profesi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Melakukan pemeriksaan anggaran instansi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Memberikan advokasi kepada anggota profesi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Mengangkat pejabat fungsional di instansi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Memberikan advokasi kepada anggota profesi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Menetapkan seluruh sanksi disiplin ASN: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Memberikan advokasi kepada anggota profesi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Tugas organisasi profesi meliputi menyusun kode etik, memberi advokasi, dan memeriksa serta merekomendasikan pelanggaran kode etik profesi; opsi lain adalah kewenangan di luar daftar tersebut.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 11.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Dalam penanganan dugaan pelanggaran, peran organisasi profesi JF yang tepat adalah ....","options":["Menetapkan sanksi administratif terhadap semua PNS instansi","Mengubah status kepegawaian anggota melalui keputusan sendiri","Memeriksa dan memberikan rekomendasi pelanggaran etik profesi","Membentuk instansi pembina untuk setiap JF yang ditetapkan","Memutus hukuman disiplin tanpa pemeriksaan kepegawaian"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 11","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Pasal 101 memberikan tugas pemeriksaan dan rekomendasi atas pelanggaran etik/perilaku profesi, bukan kewenangan penjatuhan hukuman disiplin atau perubahan status kepegawaian.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Memeriksa dan memberikan rekomendasi pelanggaran etik profesi</mark>.</strong> Pasal 101 memberikan tugas pemeriksaan dan rekomendasi atas pelanggaran etik/perilaku profesi, bukan kewenangan penjatuhan hukuman disiplin atau perubahan status kepegawaian.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Menetapkan sanksi administratif terhadap semua PNS instansi: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Memeriksa dan memberikan rekomendasi pelanggaran etik profesi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Mengubah status kepegawaian anggota melalui keputusan sendiri: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Memeriksa dan memberikan rekomendasi pelanggaran etik profesi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Membentuk instansi pembina untuk setiap JF yang ditetapkan: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Memeriksa dan memberikan rekomendasi pelanggaran etik profesi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Memutus hukuman disiplin tanpa pemeriksaan kepegawaian: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Memeriksa dan memberikan rekomendasi pelanggaran etik profesi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Pasal 101 memberikan tugas pemeriksaan dan rekomendasi atas pelanggaran etik/perilaku profesi, bukan kewenangan penjatuhan hukuman disiplin atau perubahan status kepegawaian.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 11.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Kedudukan Pasal 101 PP 11/2017 setelah terbit PP 17/2020 adalah ....","options":["Tidak mengalami perubahan","Dicabut dari aturan organisasi profesi Jabatan Fungsional","Dibatasi hanya untuk profesi tertentu di instansi pusat","Diganti dengan mekanisme penegakan oleh atasan langsung","Diubah pada ketentuan jangka waktu organisasi profesi"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 11","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Halaman 11 menyebut PP 11/2017 diubah oleh PP 17/2020, tetapi Pasal 101 tidak mengalami perubahan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Tidak mengalami perubahan</mark>.</strong> Halaman 11 menyebut PP 11/2017 diubah oleh PP 17/2020, tetapi Pasal 101 tidak mengalami perubahan.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Dicabut dari aturan organisasi profesi Jabatan Fungsional: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Tidak mengalami perubahan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Dibatasi hanya untuk profesi tertentu di instansi pusat: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Tidak mengalami perubahan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Diganti dengan mekanisme penegakan oleh atasan langsung: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Tidak mengalami perubahan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Diubah pada ketentuan jangka waktu organisasi profesi: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Tidak mengalami perubahan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Halaman 11 menyebut PP 11/2017 diubah oleh PP 17/2020, tetapi Pasal 101 tidak mengalami perubahan.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 11.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Aplikasi","question":"Sebuah instansi ingin menetapkan kode etik yang sesuai karakteristik organisasinya. Pemegang kewenangan penetap yang tepat adalah ....","options":["Pejabat Pembina Kepegawaian instansi tersebut","Pimpinan instansi pembina seluruh Jabatan Fungsional","Sekretaris organisasi profesi yang bersangkutan","Komite khusus pengawas kode etik profesi","Pengurus organisasi profesi setiap Jabatan Fungsional"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 11","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Kode etik instansi ditetapkan oleh PPK masing-masing berdasarkan karakteristiknya. Jangan disamakan dengan kewenangan organisasi profesi menyusun kode etik profesi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Pejabat Pembina Kepegawaian instansi tersebut</mark>.</strong> Kode etik instansi ditetapkan oleh PPK masing-masing berdasarkan karakteristiknya. Jangan disamakan dengan kewenangan organisasi profesi menyusun kode etik profesi.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Pimpinan instansi pembina seluruh Jabatan Fungsional: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Pejabat Pembina Kepegawaian instansi tersebut</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Sekretaris organisasi profesi yang bersangkutan: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Pejabat Pembina Kepegawaian instansi tersebut</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Komite khusus pengawas kode etik profesi: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Pejabat Pembina Kepegawaian instansi tersebut</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Pengurus organisasi profesi setiap Jabatan Fungsional: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Pejabat Pembina Kepegawaian instansi tersebut</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Kode etik instansi ditetapkan oleh PPK masing-masing berdasarkan karakteristiknya. Jangan disamakan dengan kewenangan organisasi profesi menyusun kode etik profesi.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 11.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Konsep","question":"Kedudukan etika dalam pengambilan keputusan organisasi pemerintah adalah ....","options":["Pedoman pengawasan administratif yang hanya dipakai sesudah pelanggaran","Ketentuan yang berlaku terutama ketika unit menyusun laporan kepegawaian","Fondasi moral kebijakan dan pelaksanaan tugas, bukan sekadar kepatuhan formal","Sistem penilaian kinerja yang menggantikan norma dan tanggung jawab","Instrumen pengukuran efektivitas kerja yang terpisah dari budaya organisasi"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 12","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Etika dijelaskan sebagai fondasi moral pengambilan keputusan, kebijakan, hubungan kerja dan pelayanan, melampaui kepatuhan peraturan formal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Fondasi moral kebijakan dan pelaksanaan tugas, bukan sekadar kepatuhan formal</mark>.</strong> Etika dijelaskan sebagai fondasi moral pengambilan keputusan, kebijakan, hubungan kerja dan pelayanan, melampaui kepatuhan peraturan formal.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Pedoman pengawasan administratif yang hanya dipakai sesudah pelanggaran: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Fondasi moral kebijakan dan pelaksanaan tugas, bukan sekadar kepatuhan formal</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Ketentuan yang berlaku terutama ketika unit menyusun laporan kepegawaian: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Fondasi moral kebijakan dan pelaksanaan tugas, bukan sekadar kepatuhan formal</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Sistem penilaian kinerja yang menggantikan norma dan tanggung jawab: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Fondasi moral kebijakan dan pelaksanaan tugas, bukan sekadar kepatuhan formal</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Instrumen pengukuran efektivitas kerja yang terpisah dari budaya organisasi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Fondasi moral kebijakan dan pelaksanaan tugas, bukan sekadar kepatuhan formal</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Etika dijelaskan sebagai fondasi moral pengambilan keputusan, kebijakan, hubungan kerja dan pelayanan, melampaui kepatuhan peraturan formal.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 12.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Mengapa daftar larangan dan sanksi saja tidak cukup untuk mewujudkan etika organisasi?","options":["Diperlukan langkah sistematis dan partisipasi aktif seluruh unsur organisasi","Diperlukan pembentukan organisasi profesi pada setiap unit pelaksana","Diperlukan pengalihan seluruh pembinaan dari pimpinan kepada pegawai","Diperlukan penyeragaman target kerja di setiap jabatan kementerian","Diperlukan penggantian seluruh peraturan formal dengan pedoman informal"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 12","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Halaman 12 menegaskan etika yang baik perlu langkah nyata, sistematis, sungguh-sungguh, dan partisipasi aktif seluruh pihak; aturan serta sanksi semata tidak memadai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Diperlukan langkah sistematis dan partisipasi aktif seluruh unsur organisasi</mark>.</strong> Halaman 12 menegaskan etika yang baik perlu langkah nyata, sistematis, sungguh-sungguh, dan partisipasi aktif seluruh pihak; aturan serta sanksi semata tidak memadai.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Diperlukan pembentukan organisasi profesi pada setiap unit pelaksana: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Diperlukan langkah sistematis dan partisipasi aktif seluruh unsur organisasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Diperlukan pengalihan seluruh pembinaan dari pimpinan kepada pegawai: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Diperlukan langkah sistematis dan partisipasi aktif seluruh unsur organisasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Diperlukan penyeragaman target kerja di setiap jabatan kementerian: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Diperlukan langkah sistematis dan partisipasi aktif seluruh unsur organisasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Diperlukan penggantian seluruh peraturan formal dengan pedoman informal: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Diperlukan langkah sistematis dan partisipasi aktif seluruh unsur organisasi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Halaman 12 menegaskan etika yang baik perlu langkah nyata, sistematis, sungguh-sungguh, dan partisipasi aktif seluruh pihak; aturan serta sanksi semata tidak memadai.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 12.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Langkah awal implementasi etika organisasi menurut Keraf (2002) ialah ....","options":["Komitmen moral dan politik dari pimpinan kementerian/lembaga","Penghargaan yang didasarkan pada penilaian kinerja objektif","Sosialisasi etika pada proses awal penerimaan pegawai","Sanksi yang diterapkan secara konsisten atas seluruh pelanggaran","Keteladanan nyata pimpinan kementerian dalam praktik moral"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 12","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Urutan Keraf diawali komitmen moral-politik pimpinan; pilihan lain adalah langkah lanjutan yang memang tercantum dalam uraian, bukan langkah awal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Komitmen moral dan politik dari pimpinan kementerian/lembaga</mark>.</strong> Urutan Keraf diawali komitmen moral-politik pimpinan; pilihan lain adalah langkah lanjutan yang memang tercantum dalam uraian, bukan langkah awal.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Penghargaan yang didasarkan pada penilaian kinerja objektif: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Komitmen moral dan politik dari pimpinan kementerian/lembaga</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Sosialisasi etika pada proses awal penerimaan pegawai: berkaitan dengan proses pengenalan dan pembinaan etika. Fokus tersebut berbeda dari <strong>Komitmen moral dan politik dari pimpinan kementerian/lembaga</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Sanksi yang diterapkan secara konsisten atas seluruh pelanggaran: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Komitmen moral dan politik dari pimpinan kementerian/lembaga</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Keteladanan nyata pimpinan kementerian dalam praktik moral: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Komitmen moral dan politik dari pimpinan kementerian/lembaga</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Urutan Keraf diawali komitmen moral-politik pimpinan; pilihan lain adalah langkah lanjutan yang memang tercantum dalam uraian, bukan langkah awal.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 12.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Komitmen moral-politik pimpinan selanjutnya perlu diwujudkan dalam ....","options":["Penghargaan tertulis berbasis penilaian yang objektif","Praktik keteladanan pejabat tinggi pada tugas sehari-hari","Kegiatan pelatihan setiap pegawai pada masa pembenahan","Penerapan sanksi untuk menjadi alat pendidikan pegawai","Aturan formal internal yang rinci dan memuat sanksi jelas"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 12","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Langkah kedua menerjemahkan komitmen pimpinan menjadi aturan formal internal yang konkret, rinci, dan memiliki sanksi jelas. Alternatif lain merupakan langkah berikutnya pada bagian 12–13.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Aturan formal internal yang rinci dan memuat sanksi jelas</mark>.</strong> Langkah kedua menerjemahkan komitmen pimpinan menjadi aturan formal internal yang konkret, rinci, dan memiliki sanksi jelas. Alternatif lain merupakan langkah berikutnya pada bagian 12–13.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Penghargaan tertulis berbasis penilaian yang objektif: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Aturan formal internal yang rinci dan memuat sanksi jelas</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Praktik keteladanan pejabat tinggi pada tugas sehari-hari: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Aturan formal internal yang rinci dan memuat sanksi jelas</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Kegiatan pelatihan setiap pegawai pada masa pembenahan: berkaitan dengan proses pengenalan dan pembinaan etika. Fokus tersebut berbeda dari <strong>Aturan formal internal yang rinci dan memuat sanksi jelas</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Penerapan sanksi untuk menjadi alat pendidikan pegawai: berkaitan dengan proses pengenalan dan pembinaan etika. Fokus tersebut berbeda dari <strong>Aturan formal internal yang rinci dan memuat sanksi jelas</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Langkah kedua menerjemahkan komitmen pimpinan menjadi aturan formal internal yang konkret, rinci, dan memiliki sanksi jelas. Alternatif lain merupakan langkah berikutnya pada bagian 12–13.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 12.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Kelompok pihak yang secara eksplisit harus berpedoman pada aturan formal internal ialah ....","options":["Dari menteri, direktur jenderal, sampai pegawai tingkat terendah","Hanya unit kepatuhan internal dan pejabat pengawas instansi","Hanya pejabat eselon yang memegang kewenangan pembinaan","Hanya pegawai baru yang masuk melalui kegiatan pelatihan","Hanya pejabat pembina kepegawaian dan seluruh pejabat fungsional"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 12","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Komitmen diterjemahkan menjadi pegangan konkret dari menteri/direktur jenderal hingga pegawai paling rendah, menunjukkan cakupan lintas tingkatan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Dari menteri, direktur jenderal, sampai pegawai tingkat terendah</mark>.</strong> Komitmen diterjemahkan menjadi pegangan konkret dari menteri/direktur jenderal hingga pegawai paling rendah, menunjukkan cakupan lintas tingkatan.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Hanya unit kepatuhan internal dan pejabat pengawas instansi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Dari menteri, direktur jenderal, sampai pegawai tingkat terendah</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Hanya pejabat eselon yang memegang kewenangan pembinaan: berkaitan dengan proses pengenalan dan pembinaan etika. Fokus tersebut berbeda dari <strong>Dari menteri, direktur jenderal, sampai pegawai tingkat terendah</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Hanya pegawai baru yang masuk melalui kegiatan pelatihan: berkaitan dengan proses pengenalan dan pembinaan etika. Fokus tersebut berbeda dari <strong>Dari menteri, direktur jenderal, sampai pegawai tingkat terendah</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Hanya pejabat pembina kepegawaian dan seluruh pejabat fungsional: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Dari menteri, direktur jenderal, sampai pegawai tingkat terendah</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Komitmen diterjemahkan menjadi pegangan konkret dari menteri/direktur jenderal hingga pegawai paling rendah, menunjukkan cakupan lintas tingkatan.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 12.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Pendidikan etika menurut Keraf (2002) dilaksanakan dengan pendekatan ....","options":["Disampaikan sesudah ada pelanggaran dan menjelang penjatuhan sanksi","Dijalankan sebagai pengganti aturan internal dan pengawasan organisasi","Diajarkan saat pertama masuk, pelatihan, dan proses pembenahan","Dipusatkan pada sidang organisasi profesi dan pemeriksaan etik","Dilakukan hanya pada pimpinan lalu diteruskan melalui penilaian kinerja"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 12","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Langkah ketiga berupa sosialisasi/pengajaran etos dan aturan sejak masuk, selama pelatihan, dan proses pembenahan; bukan reaksi sesudah pelanggaran saja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Diajarkan saat pertama masuk, pelatihan, dan proses pembenahan</mark>.</strong> Langkah ketiga berupa sosialisasi/pengajaran etos dan aturan sejak masuk, selama pelatihan, dan proses pembenahan; bukan reaksi sesudah pelanggaran saja.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Disampaikan sesudah ada pelanggaran dan menjelang penjatuhan sanksi: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Diajarkan saat pertama masuk, pelatihan, dan proses pembenahan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Dijalankan sebagai pengganti aturan internal dan pengawasan organisasi: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Diajarkan saat pertama masuk, pelatihan, dan proses pembenahan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Dipusatkan pada sidang organisasi profesi dan pemeriksaan etik: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Diajarkan saat pertama masuk, pelatihan, dan proses pembenahan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Dilakukan hanya pada pimpinan lalu diteruskan melalui penilaian kinerja: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Diajarkan saat pertama masuk, pelatihan, dan proses pembenahan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Langkah ketiga berupa sosialisasi/pengajaran etos dan aturan sejak masuk, selama pelatihan, dan proses pembenahan; bukan reaksi sesudah pelanggaran saja.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 12.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Aplikasi","question":"Pimpinan menyatakan komitmen integritas, tetapi seluruh pegawai belum memiliki acuan operasional. Langkah Keraf berikut yang secara khusus belum dipenuhi adalah ....","options":["Meningkatkan kegiatan pengawasan publik di luar organisasi","Menyiapkan mekanisme pembentukan organisasi profesi pegawai","Menyelenggarakan penilaian penghargaan bagi pegawai teladan","Melaksanakan sidang majelis untuk dugaan pelanggaran kode etik","Menerjemahkan komitmen menjadi aturan formal internal yang konkret"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 12","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Kesenjangan antara komitmen lisan dan pegangan kerja menunjukkan perlunya aturan internal rinci. Alternatif lain bukan langkah kedua dalam urutan implementasi pada halaman 12.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Menerjemahkan komitmen menjadi aturan formal internal yang konkret</mark>.</strong> Kesenjangan antara komitmen lisan dan pegangan kerja menunjukkan perlunya aturan internal rinci. Alternatif lain bukan langkah kedua dalam urutan implementasi pada halaman 12.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Meningkatkan kegiatan pengawasan publik di luar organisasi: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Menerjemahkan komitmen menjadi aturan formal internal yang konkret</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Menyiapkan mekanisme pembentukan organisasi profesi pegawai: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Menerjemahkan komitmen menjadi aturan formal internal yang konkret</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Menyelenggarakan penilaian penghargaan bagi pegawai teladan: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Menerjemahkan komitmen menjadi aturan formal internal yang konkret</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Melaksanakan sidang majelis untuk dugaan pelanggaran kode etik: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Menerjemahkan komitmen menjadi aturan formal internal yang konkret</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Kesenjangan antara komitmen lisan dan pegangan kerja menunjukkan perlunya aturan internal rinci. Alternatif lain bukan langkah kedua dalam urutan implementasi pada halaman 12.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 12.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Konsep","question":"Hasil yang diharapkan dari penerapan etika secara konsisten dalam organisasi adalah ....","options":["Tata kelola bersih, akuntabel, dan terpercaya","Penetapan standar penerimaan untuk semua profesi","Pemusatan seluruh keputusan pada pengawas internal","Pembentukan pengelola kode etik pada tiap jabatan","Promosi pegawai berbasis senioritas dan masa kerja"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 12","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Pembahasan menghubungkan konsistensi etika dengan tata kelola bersih-akuntabel-terpercaya, kinerja lembaga, dan legitimasi publik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Tata kelola bersih, akuntabel, dan terpercaya</mark>.</strong> Pembahasan menghubungkan konsistensi etika dengan tata kelola bersih-akuntabel-terpercaya, kinerja lembaga, dan legitimasi publik.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Penetapan standar penerimaan untuk semua profesi: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Tata kelola bersih, akuntabel, dan terpercaya</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Pemusatan seluruh keputusan pada pengawas internal: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Tata kelola bersih, akuntabel, dan terpercaya</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Pembentukan pengelola kode etik pada tiap jabatan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Tata kelola bersih, akuntabel, dan terpercaya</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Promosi pegawai berbasis senioritas dan masa kerja: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Tata kelola bersih, akuntabel, dan terpercaya</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Pembahasan menghubungkan konsistensi etika dengan tata kelola bersih-akuntabel-terpercaya, kinerja lembaga, dan legitimasi publik.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 12.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Hubungan keteladanan pimpinan dan pengawasan yang efektif terhadap etika organisasi adalah ....","options":["Keteladanan menggantikan keharusan aturan internal dan mekanisme pembinaan","Keduanya menopang budaya etis bersama pembinaan dan disiplin adil","Pengawasan hanya dibutuhkan bila keteladanan tidak dicontohkan pimpinan","Pengawasan menentukan tujuan organisasi sedangkan teladan menetapkan jabatan","Keteladanan mengatur profesi sedangkan pengawasan menetapkan nilai dasar"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 12","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Halaman 12 mensyaratkan keteladanan, pengawasan efektif, serta pembinaan dan disiplin adil-transparan untuk membangun organisasi berintegritas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Keduanya menopang budaya etis bersama pembinaan dan disiplin adil</mark>.</strong> Halaman 12 mensyaratkan keteladanan, pengawasan efektif, serta pembinaan dan disiplin adil-transparan untuk membangun organisasi berintegritas.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Keteladanan menggantikan keharusan aturan internal dan mekanisme pembinaan: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Keduanya menopang budaya etis bersama pembinaan dan disiplin adil</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Pengawasan hanya dibutuhkan bila keteladanan tidak dicontohkan pimpinan: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Keduanya menopang budaya etis bersama pembinaan dan disiplin adil</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Pengawasan menentukan tujuan organisasi sedangkan teladan menetapkan jabatan: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Keduanya menopang budaya etis bersama pembinaan dan disiplin adil</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Keteladanan mengatur profesi sedangkan pengawasan menetapkan nilai dasar: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Keduanya menopang budaya etis bersama pembinaan dan disiplin adil</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Halaman 12 mensyaratkan keteladanan, pengawasan efektif, serta pembinaan dan disiplin adil-transparan untuk membangun organisasi berintegritas.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 12.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Dasar • Konsep","question":"Dalam implementasi etika, hubungan kepatuhan aturan formal dengan budaya kerja ialah ....","options":["Kepatuhan berfokus pada profesi, sedangkan budaya berfokus pada penghasilan","Kepatuhan terbatas pada pengawasan, sedangkan budaya pada kinerja","Kepatuhan merupakan unsur, tetapi sikap dan perilaku etis tetap diperlukan","Kepatuhan berlaku bagi pimpinan, sedangkan budaya berlaku bagi pegawai","Kepatuhan diperlukan saat pelanggaran, sedangkan budaya saat penilaian"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 12","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Implementasi etika tidak hanya berupa kepatuhan formal, melainkan juga sikap, perilaku, dan budaya kerja yang memuliakan kejujuran, keadilan dan pelayanan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Kepatuhan merupakan unsur, tetapi sikap dan perilaku etis tetap diperlukan</mark>.</strong> Implementasi etika tidak hanya berupa kepatuhan formal, melainkan juga sikap, perilaku, dan budaya kerja yang memuliakan kejujuran, keadilan dan pelayanan.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Kepatuhan berfokus pada profesi, sedangkan budaya berfokus pada penghasilan: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Kepatuhan merupakan unsur, tetapi sikap dan perilaku etis tetap diperlukan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Kepatuhan terbatas pada pengawasan, sedangkan budaya pada kinerja: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Kepatuhan merupakan unsur, tetapi sikap dan perilaku etis tetap diperlukan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Kepatuhan berlaku bagi pimpinan, sedangkan budaya berlaku bagi pegawai: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Kepatuhan merupakan unsur, tetapi sikap dan perilaku etis tetap diperlukan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Kepatuhan diperlukan saat pelanggaran, sedangkan budaya saat penilaian: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Kepatuhan merupakan unsur, tetapi sikap dan perilaku etis tetap diperlukan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Implementasi etika tidak hanya berupa kepatuhan formal, melainkan juga sikap, perilaku, dan budaya kerja yang memuliakan kejujuran, keadilan dan pelayanan.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 12.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Dalam langkah Keraf, fungsi sanksi yang diterapkan secara konsekuen adalah ....","options":["Pengganti sosialisasi nilai moral saat pegawai masuk","Cara menetapkan besaran penghargaan kenaikan pangkat","Dasar utama pengukuran kompetensi teknis pegawai","Instrumen penetapan persetujuan kode etik profesi","Alat pendidikan atas perilaku dalam organisasi"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 13","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Sanksi yang diterapkan konsisten merupakan alat pendidikan, sementara penghargaan jujur-objektif menjadi motivasi; keduanya tidak saling menggantikan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Alat pendidikan atas perilaku dalam organisasi</mark>.</strong> Sanksi yang diterapkan konsisten merupakan alat pendidikan, sementara penghargaan jujur-objektif menjadi motivasi; keduanya tidak saling menggantikan.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Pengganti sosialisasi nilai moral saat pegawai masuk: berkaitan dengan proses pengenalan dan pembinaan etika. Fokus tersebut berbeda dari <strong>Alat pendidikan atas perilaku dalam organisasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Cara menetapkan besaran penghargaan kenaikan pangkat: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Alat pendidikan atas perilaku dalam organisasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Dasar utama pengukuran kompetensi teknis pegawai: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Alat pendidikan atas perilaku dalam organisasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Instrumen penetapan persetujuan kode etik profesi: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Alat pendidikan atas perilaku dalam organisasi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Sanksi yang diterapkan konsisten merupakan alat pendidikan, sementara penghargaan jujur-objektif menjadi motivasi; keduanya tidak saling menggantikan.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 13.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Hubungan sanksi dan penghargaan dalam perbaikan etos organisasi adalah ....","options":["Sanksi menata jabatan; penghargaan mengawasi kode etik profesi","Sanksi memotivasi kompetensi; penghargaan mengawasi penyimpangan","Sanksi bersifat mendidik; penghargaan objektif bersifat memotivasi","Sanksi merumuskan nilai organisasi; penghargaan menetapkan standar moral","Sanksi membina pegawai baru; penghargaan membentuk aturan internal"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 13","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Keraf menyebut sanksi sebagai alat pendidikan dan penghargaan jujur-objektif sebagai motivasi etos. Empat opsi membalik atau menggeser fungsi yang dijabarkan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Sanksi bersifat mendidik; penghargaan objektif bersifat memotivasi</mark>.</strong> Keraf menyebut sanksi sebagai alat pendidikan dan penghargaan jujur-objektif sebagai motivasi etos. Empat opsi membalik atau menggeser fungsi yang dijabarkan.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Sanksi menata jabatan; penghargaan mengawasi kode etik profesi: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Sanksi bersifat mendidik; penghargaan objektif bersifat memotivasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Sanksi memotivasi kompetensi; penghargaan mengawasi penyimpangan: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Sanksi bersifat mendidik; penghargaan objektif bersifat memotivasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Sanksi merumuskan nilai organisasi; penghargaan menetapkan standar moral: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Sanksi bersifat mendidik; penghargaan objektif bersifat memotivasi</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Sanksi membina pegawai baru; penghargaan membentuk aturan internal: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Sanksi bersifat mendidik; penghargaan objektif bersifat memotivasi</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Keraf menyebut sanksi sebagai alat pendidikan dan penghargaan jujur-objektif sebagai motivasi etos. Empat opsi membalik atau menggeser fungsi yang dijabarkan.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 13.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Dalam butir keteladanan Keraf, unsur pimpinan yang disebut secara spesifik adalah ....","options":["Atasan langsung dan pejabat pemeriksa disiplin","Unit kepatuhan internal dan Inspektorat Jenderal","Pengurus profesi dan pimpinan instansi pembina","Pejabat pembina kepegawaian dan organisasi profesi","Menteri dan pejabat Eselon I"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 13","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Teladan nyata dalam menghayati dan mempraktikkan prinsip moral ditekankan khususnya pada Menteri dan Eselon I.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Menteri dan pejabat Eselon I</mark>.</strong> Teladan nyata dalam menghayati dan mempraktikkan prinsip moral ditekankan khususnya pada Menteri dan Eselon I.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Atasan langsung dan pejabat pemeriksa disiplin: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Menteri dan pejabat Eselon I</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Unit kepatuhan internal dan Inspektorat Jenderal: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Menteri dan pejabat Eselon I</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Pengurus profesi dan pimpinan instansi pembina: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Menteri dan pejabat Eselon I</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Pejabat pembina kepegawaian dan organisasi profesi: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Menteri dan pejabat Eselon I</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Teladan nyata dalam menghayati dan mempraktikkan prinsip moral ditekankan khususnya pada Menteri dan Eselon I.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 13.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Ketentuan","question":"Tokoh yang merumuskan empat unsur utama keberhasilan etika organisasi ialah ....","options":["Peraturan Pemerintah Nomor 42 Tahun 2004","Undang-Undang Nomor 20 Tahun 2023","Magnis-Suseno (2002)","A. Sonny Keraf (2002)","Peraturan Pemerintah Nomor 11 Tahun 2017"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 13","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Halaman 13 mengatribusi empat unsur yang memengaruhi keberhasilan etika organisasi kepada Magnis-Suseno (2002), berbeda dari lima langkah Keraf.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Magnis-Suseno (2002)</mark>.</strong> Halaman 13 mengatribusi empat unsur yang memengaruhi keberhasilan etika organisasi kepada Magnis-Suseno (2002), berbeda dari lima langkah Keraf.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Peraturan Pemerintah Nomor 42 Tahun 2004: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Magnis-Suseno (2002)</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Undang-Undang Nomor 20 Tahun 2023: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Magnis-Suseno (2002)</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> A. Sonny Keraf (2002): menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Magnis-Suseno (2002)</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Peraturan Pemerintah Nomor 11 Tahun 2017: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Magnis-Suseno (2002)</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Halaman 13 mengatribusi empat unsur yang memengaruhi keberhasilan etika organisasi kepada Magnis-Suseno (2002), berbeda dari lima langkah Keraf.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 13.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Aplikasi","question":"Pengertian etos kerja dalam kerangka Magnis-Suseno (2002) adalah ....","options":["Pengakuan kompetensi yang dibuktikan dengan hasil ujian profesi","Kesesuaian perilaku individu dengan norma baik-buruk perbuatan","Kepatuhan formal pada standar jabatan dan prosedur kepegawaian","Sikap dasar seseorang atau kelompok dalam mengerjakan pekerjaan","Penolakan penggunaan kewenangan untuk kepentingan kelompok"],"answer":3,"source":"Etika Pegawai Negeri Sipil, halaman 13","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Etos kerja didefinisikan sebagai sikap dasar individu atau kelompok dalam melakukan pekerjaan. Moralitas pribadi, sebaliknya, berkaitan dengan kesesuaian perilaku pada norma moral.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci D — <mark class=\"study-highlight\">Sikap dasar seseorang atau kelompok dalam mengerjakan pekerjaan</mark>.</strong> Etos kerja didefinisikan sebagai sikap dasar individu atau kelompok dalam melakukan pekerjaan. Moralitas pribadi, sebaliknya, berkaitan dengan kesesuaian perilaku pada norma moral.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Pengakuan kompetensi yang dibuktikan dengan hasil ujian profesi: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Sikap dasar seseorang atau kelompok dalam mengerjakan pekerjaan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Kesesuaian perilaku individu dengan norma baik-buruk perbuatan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Sikap dasar seseorang atau kelompok dalam mengerjakan pekerjaan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Kepatuhan formal pada standar jabatan dan prosedur kepegawaian: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Sikap dasar seseorang atau kelompok dalam mengerjakan pekerjaan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Penolakan penggunaan kewenangan untuk kepentingan kelompok: membahas pengaruh lingkungan dan etos kerja. Fokus tersebut berbeda dari <strong>Sikap dasar seseorang atau kelompok dalam mengerjakan pekerjaan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Etos kerja didefinisikan sebagai sikap dasar individu atau kelompok dalam melakukan pekerjaan. Moralitas pribadi, sebaliknya, berkaitan dengan kesesuaian perilaku pada norma moral.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 13.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Keadaan yang secara khusus memperlihatkan kuat-lemah etos kerja adalah saat ....","options":["Mendapatkan pembaruan rincian penugasan jabatan","Mengikuti pertemuan penetapan program organisasi","Menjalani kegiatan formal pembinaan kepegawaian","Menyelesaikan prosedur evaluasi administratif unit","Menghadapi hambatan atau tantangan dalam pekerjaan"],"answer":4,"source":"Etika Pegawai Negeri Sipil, halaman 13","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Etos kerja dapat kuat/lemah dan positif/negatif, yang terlihat saat menghadapi tantangan/hambatan pekerjaan. Yang lain bukan indikator khusus yang disebut.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci E — <mark class=\"study-highlight\">Menghadapi hambatan atau tantangan dalam pekerjaan</mark>.</strong> Etos kerja dapat kuat/lemah dan positif/negatif, yang terlihat saat menghadapi tantangan/hambatan pekerjaan. Yang lain bukan indikator khusus yang disebut.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Mendapatkan pembaruan rincian penugasan jabatan: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Menghadapi hambatan atau tantangan dalam pekerjaan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Mengikuti pertemuan penetapan program organisasi: menggambarkan aspek lain yang berdekatan dengan pokok pertanyaan. Fokus tersebut berbeda dari <strong>Menghadapi hambatan atau tantangan dalam pekerjaan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Menjalani kegiatan formal pembinaan kepegawaian: berkaitan dengan proses pengenalan dan pembinaan etika. Fokus tersebut berbeda dari <strong>Menghadapi hambatan atau tantangan dalam pekerjaan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Menyelesaikan prosedur evaluasi administratif unit: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Menghadapi hambatan atau tantangan dalam pekerjaan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Etos kerja dapat kuat/lemah dan positif/negatif, yang terlihat saat menghadapi tantangan/hambatan pekerjaan. Yang lain bukan indikator khusus yang disebut.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 13.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Aplikasi","question":"Pegawai yang semula beretos tinggi menjadi enggan bertanggung jawab setelah lama berada di kelompok beretos rendah. Konsep yang dijelaskan adalah ....","options":["Pembentukan keteladanan nyata oleh pimpinan tertinggi","Pengaruh etos kerja kelompok terhadap etos individu","Penerapan komitmen moral-politik dari pimpinan lembaga","Penerapan penghargaan objektif untuk penguatan etos","Penyusunan aturan internal dengan sanksi yang tegas"],"answer":1,"source":"Etika Pegawai Negeri Sipil, halaman 13","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Magnis-Suseno menekankan etos individu sangat dipengaruhi lingkungan rekan kerja; contoh penurunan etos akibat kelompok rendah dicantumkan secara eksplisit.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci B — <mark class=\"study-highlight\">Pengaruh etos kerja kelompok terhadap etos individu</mark>.</strong> Magnis-Suseno menekankan etos individu sangat dipengaruhi lingkungan rekan kerja; contoh penurunan etos akibat kelompok rendah dicantumkan secara eksplisit.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Pembentukan keteladanan nyata oleh pimpinan tertinggi: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Pengaruh etos kerja kelompok terhadap etos individu</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Penerapan komitmen moral-politik dari pimpinan lembaga: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Pengaruh etos kerja kelompok terhadap etos individu</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Penerapan penghargaan objektif untuk penguatan etos: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Pengaruh etos kerja kelompok terhadap etos individu</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Penyusunan aturan internal dengan sanksi yang tegas: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Pengaruh etos kerja kelompok terhadap etos individu</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Magnis-Suseno menekankan etos individu sangat dipengaruhi lingkungan rekan kerja; contoh penurunan etos akibat kelompok rendah dicantumkan secara eksplisit.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 13.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Klasifikasi","question":"Pernyataan yang termasuk unsur etos kerja kondusif adalah ....","options":["Menjaga integritas pimpinan melalui keteladanan kebijakan","Menetapkan kode etik profesi melalui persetujuan instansi pembina","Menyambut kritik masyarakat lalu menggunakan masukan untuk perbaikan","Mengawasi kasus pelanggaran melalui komite profesi khusus","Menyusun aturan internal dengan sanksi bagi seluruh pegawai"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 13","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Salah satu unsur etos kerja kondusif ialah bersedia memperhatikan tuntutan/kritik masyarakat lalu memperbaiki diri; opsi lain lebih tepat untuk kode etik profesi atau langkah implementasi etika organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Menyambut kritik masyarakat lalu menggunakan masukan untuk perbaikan</mark>.</strong> Salah satu unsur etos kerja kondusif ialah bersedia memperhatikan tuntutan/kritik masyarakat lalu memperbaiki diri; opsi lain lebih tepat untuk kode etik profesi atau langkah implementasi etika organisasi.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Menjaga integritas pimpinan melalui keteladanan kebijakan: menekankan integritas, independensi, dan pencegahan penyalahgunaan kewenangan. Fokus tersebut berbeda dari <strong>Menyambut kritik masyarakat lalu menggunakan masukan untuk perbaikan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Menetapkan kode etik profesi melalui persetujuan instansi pembina: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Menyambut kritik masyarakat lalu menggunakan masukan untuk perbaikan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Mengawasi kasus pelanggaran melalui komite profesi khusus: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Menyambut kritik masyarakat lalu menggunakan masukan untuk perbaikan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Menyusun aturan internal dengan sanksi bagi seluruh pegawai: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Menyambut kritik masyarakat lalu menggunakan masukan untuk perbaikan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Salah satu unsur etos kerja kondusif ialah bersedia memperhatikan tuntutan/kritik masyarakat lalu memperbaiki diri; opsi lain lebih tepat untuk kode etik profesi atau langkah implementasi etika organisasi.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 13.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"Menengah • Konsep","question":"Moralitas pribadi dalam etika organisasi berkaitan dengan ....","options":["Kesesuaian sikap-perilaku dengan norma yang mengatur baik-buruk perbuatan","Keterpaduan aturan dan sanksi yang berlaku dalam organisasi publik","Pengaruh keteladanan pimpinan terhadap evaluasi pekerjaan pegawai","Sikap dasar kelompok dalam bekerja saat menghadapi hambatan dan tantangan","Komitmen politik pimpinan untuk mengarahkan perubahan budaya kerja"],"answer":0,"source":"Etika Pegawai Negeri Sipil, halaman 13","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Moralitas pribadi berkaitan dengan kualitas moral individu, yakni kesesuaian sikap dan perilaku pada norma baik-buruk; opsi lain menjelaskan etos kerja atau langkah organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci A — <mark class=\"study-highlight\">Kesesuaian sikap-perilaku dengan norma yang mengatur baik-buruk perbuatan</mark>.</strong> Moralitas pribadi berkaitan dengan kualitas moral individu, yakni kesesuaian sikap dan perilaku pada norma baik-buruk; opsi lain menjelaskan etos kerja atau langkah organisasi.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>B.</strong> Keterpaduan aturan dan sanksi yang berlaku dalam organisasi publik: menitikberatkan norma atau pengaturan formal. Fokus tersebut berbeda dari <strong>Kesesuaian sikap-perilaku dengan norma yang mengatur baik-buruk perbuatan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>C.</strong> Pengaruh keteladanan pimpinan terhadap evaluasi pekerjaan pegawai: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Kesesuaian sikap-perilaku dengan norma yang mengatur baik-buruk perbuatan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Sikap dasar kelompok dalam bekerja saat menghadapi hambatan dan tantangan: membahas pengaruh lingkungan dan etos kerja. Fokus tersebut berbeda dari <strong>Kesesuaian sikap-perilaku dengan norma yang mengatur baik-buruk perbuatan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Komitmen politik pimpinan untuk mengarahkan perubahan budaya kerja: merujuk pada peran atau keteladanan pemimpin. Fokus tersebut berbeda dari <strong>Kesesuaian sikap-perilaku dengan norma yang mengatur baik-buruk perbuatan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Moralitas pribadi berkaitan dengan kualitas moral individu, yakni kesesuaian sikap dan perilaku pada norma baik-buruk; opsi lain menjelaskan etos kerja atau langkah organisasi.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 13.</div></div>"},{"category":"TSKKWK","sub":"Etika PNS","difficulty":"HOTS • Analisis","question":"Perbedaan 'tidak lari dari masalah' dan 'transparansi keputusan' pada unsur etos kerja adalah ....","options":["Yang pertama pengelolaan rekan; yang kedua pemberian penghargaan","Yang pertama pengawasan etik; yang kedua penyusunan kode profesi","Yang pertama identifikasi ancaman dini; yang kedua keterbukaan keputusan","Yang pertama perhatian kritik; yang kedua kewajiban pengabdian","Yang pertama kepatuhan profesi; yang kedua penetapan kompetensi"],"answer":2,"source":"Etika Pegawai Negeri Sipil, halaman 13","explanation":"<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Pembahasan dan Rangkuman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Dasar konsep</div><p>Dua unsur etos kondusif yang terpisah ialah proaktif mengenali/mengatasi ancaman sebelum memburuk dan transparansi dalam seluruh keputusan. Pilihan lain tidak cocok dengan pasangan unsur tersebut.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Kunci dan alasan</div><p><strong>Kunci C — <mark class=\"study-highlight\">Yang pertama identifikasi ancaman dini; yang kedua keterbukaan keputusan</mark>.</strong> Dua unsur etos kondusif yang terpisah ialah proaktif mengenali/mengatasi ancaman sebelum memburuk dan transparansi dalam seluruh keputusan. Pilihan lain tidak cocok dengan pasangan unsur tersebut.</p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Analisis pilihan lain</div><p><strong>A.</strong> Yang pertama pengelolaan rekan; yang kedua pemberian penghargaan: berkaitan dengan pemantauan, konsekuensi, dan penegakan. Fokus tersebut berbeda dari <strong>Yang pertama identifikasi ancaman dini; yang kedua keterbukaan keputusan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>B.</strong> Yang pertama pengawasan etik; yang kedua penyusunan kode profesi: menyangkut kedudukan dan tugas organisasi profesi. Fokus tersebut berbeda dari <strong>Yang pertama identifikasi ancaman dini; yang kedua keterbukaan keputusan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>D.</strong> Yang pertama perhatian kritik; yang kedua kewajiban pengabdian: berkaitan dengan prioritas pelayanan dan kepentingan masyarakat. Fokus tersebut berbeda dari <strong>Yang pertama identifikasi ancaman dini; yang kedua keterbukaan keputusan</strong> yang diminta secara spesifik pada pertanyaan.</p><p><strong>E.</strong> Yang pertama kepatuhan profesi; yang kedua penetapan kompetensi: berkaitan dengan kompetensi dan mutu pelaksanaan profesi. Fokus tersebut berbeda dari <strong>Yang pertama identifikasi ancaman dini; yang kedua keterbukaan keputusan</strong> yang diminta secara spesifik pada pertanyaan.</p></div><div class=\"lesson-memory\"><strong>💡 Ingat untuk UPKP</strong><span>Dua unsur etos kondusif yang terpisah ialah proaktif mengenali/mengatasi ancaman sebelum memburuk dan transparansi dalam seluruh keputusan. Pilihan lain tidak cocok dengan pasangan unsur tersebut.</span></div><div class=\"lesson-source\">📖 Rujukan: Halaman 13.</div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_etika_4_13_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_ep413_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_ep413_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Hlm. 4–13";
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
       <span class="source">Sumber: ${q.source||"Etika PNS"}</span>
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
<?php
/**
 * STANGOLDEN.exam • Pengelolaan Keuangan Negara Paket 04 (Halaman 42–53)
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
// $DB->get_record("stgpkn_p04_rank") => tabel fisik "mdlhv_stgpkn_p04_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgpkn_p04_rank';
const STG_REVIEW_T = 'stgpkn_p04_rev';
const STG_ANNO_T = 'stgpkn_p04_anno';
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
            error_log('STANGOLDEN PKN P04 HLM42-53 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN PKN P04 HLM42-53: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Pengelolaan Keuangan Negara Paket 04 (Hlm. 42–53) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 04 • Hlm. 42–53</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 04 • Hlm. 42–53</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Pengelolaan Keuangan Negara Paket 04</h2>
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
const STG_LOCAL_KEY="stg_pkn_p04_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Struktur pendapatan negara dalam anggaran pendapatan dan belanja negara terdiri atas...", "options": ["Penerimaan perpajakan, PNBP, dan penerimaan hibah", "Penerimaan pajak, penjualan aset, dan pinjaman obligasi", "Penerimaan kepabeanan, retribusi daerah, dan devisa luar", "Penerimaan deviden BUMN, laba BI, dan utang komersial", "Penerimaan cukai, dana bagi hasil, dan alokasi umum"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Komponen Pendapatan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pendapatan Negara terdiri atas <strong>Penerimaan Perpajakan, Penerimaan Negara Bukan Pajak (PNBP) dan Hibah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena secara konstitusional tiga pos inilah yang membentuk seluruh sumber pendapatan pada postur APBN. Ketiganya menampung seluruh penerimaan negara yang tidak menimbulkan kewajiban pengembalian utang.</p><p>Jawaban tepat: <strong>A. Penerimaan perpajakan, PNBP, dan penerimaan hibah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pinjaman obligasi merupakan pos pembiayaan anggaran, bukan pendapatan negara.</li><li><strong>C</strong>: Retribusi daerah merupakan komponen pendapatan asli daerah dalam APBD.</li><li><strong>D</strong>: Utang komersial tergolong dalam pos pembiayaan utang pemerintah.</li><li><strong>E</strong>: Dana bagi hasil dan alokasi umum merupakan pos belanja transfer ke daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penerimaan Perpajakan, Penerimaan Negara Bukan • Pajak (PNBP) dan Hibah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penerimaan perpajakan pemerintah pusat secara garis besar bersumber dari...", "options": ["Pajak pusat kementerian dan retribusi perizinan daerah", "Pajak dalam negeri dan pajak perdagangan internasional", "Pajak bumi perkotaan dan bea perolehan hak tanah", "Pajak penghasilan badan dan pungutan ekspor migas", "Pajak transaksi perbankan dan cukai komoditas impor"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sumber Penerimaan Perpajakan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerimaan Perpajakan bersumber dari penerimaan <strong>Pajak Dalam Negeri dan Pajak Perdagangan Internasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena klasifikasi perpajakan membedakan antara pajak yang dipungut atas aktivitas ekonomi di dalam yurisdiksi nasional dengan pajak atas perlintasan barang antarnegara. Pembagian ini menjadi standar postur penerimaan APBN.</p><p>Jawaban tepat: <strong>B. Pajak dalam negeri dan pajak perdagangan internasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Retribusi perizinan daerah merupakan sumber pendapatan asli daerah.</li><li><strong>C</strong>: PBB perkotaan dan BPHTB telah dialihkan menjadi pajak daerah.</li><li><strong>D</strong>: Pajak badan dan pungutan ekspor adalah rincian sub-pos spesifik.</li><li><strong>E</strong>: Pajak transaksi perbankan bukan pos klasifikasi utama perpajakan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pajak Dalam Negeri dan Pajak • Perdagangan Internasional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penerimaan perpajakan dari sektor perdagangan internasional terdiri atas...", "options": ["Cukai hasil tembakau dan pungutan alkohol impor", "Pajak pertambahan nilai dan bea meterai dokumen", "Bea masuk barang impor dan pajak ekspor", "Pajak penjualan barang mewah dan pajak penghasilan", "Bea lelang negara dan retribusi pelabuhan udara"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pajak Perdagangan Internasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pajak Perdagangan Internasional terdiri atas <strong>Bea Masuk dan Pajak Ekspor</strong> (bea keluar).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kedua instrumen tarif ini dikenakan atas lalu lintas barang yang melintasi batas daerah pabean Indonesia. Bea masuk dipungut atas impor, sedangkan pajak ekspor dipungut atas komoditas ekspor tertentu.</p><p>Jawaban tepat: <strong>C. Bea masuk barang impor dan pajak ekspor</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cukai adalah pungutan konsumsi barang tertentu dalam kelompok pajak domestik.</li><li><strong>B</strong>: PPN dan bea meterai digolongkan dalam kelompok pajak dalam negeri.</li><li><strong>D</strong>: PPnBM dan PPh merupakan jenis pajak dalam negeri yang utama.</li><li><strong>E</strong>: Bea lelang dan retribusi pelabuhan tergolong dalam penerimaan PNBP.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bea Masuk dan Pajak Ekspor</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kelompok pajak dalam negeri pada postur penerimaan perpajakan mencakup...", "options": ["Pajak hotel, restoran, hiburan, dan penerangan jalan", "Bea masuk impor, bea keluar ekspor, dan retribusi jasa", "Pajak kendaraan bermotor, retribusi parkir, dan reklame", "Pajak penghasilan, PPN, PPnBM, cukai, dan pajak lainnya", "Pungutan hasil perikanan dan dividen perusahaan negara"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen Pajak Dalam Negeri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerimaan Pajak Dalam Negeri terdiri atas <strong>Pajak Penghasilan, Pajak Penghasilan Migas, Pajak Pertambahan Nilai (PPN) dan Pajak Penjualan atas Barang Mewah (PPn BM), Cukai, dan Pajak Lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena jenis-jenis pajak tersebut dipungut langsung oleh pemerintah pusat melalui Direktorat Jenderal Pajak dan Direktorat Jenderal Bea Cukai atas aktivitas ekonomi domestik. Penerimaan ini menjadi penopang utama pendapatan negara.</p><p>Jawaban tepat: <strong>D. Pajak penghasilan, PPN, PPnBM, cukai, dan pajak lainnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak hotel dan restoran merupakan jenis pajak daerah kabupaten kota.</li><li><strong>B</strong>: Bea masuk dan bea keluar tergolong pajak perdagangan internasional.</li><li><strong>C</strong>: Pajak kendaraan dan retribusi parkir adalah wewenang pemerintah daerah.</li><li><strong>E</strong>: Pungutan perikanan dan dividen BUMN tergolong dalam kelompok PNBP.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pajak Pertambahan Nilai (PPN) dan Pajak • Penjualan atas Barang Mewah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dua jenis pajak dalam negeri yang telah diserahkan pengelolaannya kepada pemda adalah...", "options": ["Pajak penghasilan migas dan bea masuk barang kiriman", "Pajak pertambahan nilai dan pajak penjualan barang mewah", "Pajak penghasilan badan dan cukai hasil tembakau", "Bea meterai dokumen dan pajak perdagangan luar negeri", "Pajak bumi bangunan perdesaan perkotaan dan BPHTB"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pajak Dalam Negeri yang Dialihkan ke Pemda</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ada dua jenis pajak dalam negeri yang telah diserahkan pengelolaannya kepada Pemerintah daerah yakni <strong>Pajak Bumi dan Bangunan dan Bea Perolehan Hak atas Tanah dan Bangunan (BPHTB)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena dalam rangka otonomi daerah dan desentralisasi fiskal, kewenangan penetapan serta pemungutan PBB sektor P2 dan BPHTB dialihkan penuh menjadi pendapatan asli daerah pemerintah kabupaten/kota.</p><p>Jawaban tepat: <strong>E. Pajak bumi bangunan perdesaan perkotaan dan BPHTB</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PPh migas dan bea masuk dikelola pusat di bawah Kementerian Keuangan.</li><li><strong>B</strong>: PPN dan PPnBM tetap dikelola secara terpusat oleh pemerintah pusat.</li><li><strong>C</strong>: PPh badan dan cukai merupakan penerimaan pajak pusat yang vital.</li><li><strong>D</strong>: Bea meterai dan perdagangan luar negeri adalah kewenangan pusat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bangunan dan Bea Perolehan Hak atas Tanah dan Bangunan (BPHTB)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penerimaan negara selain dari sektor perpajakan dan hibah dikelompokkan ke dalam...", "options": ["Penerimaan Negara Bukan Pajak (PNBP)", "Penerimaan pembiayaan non-anggaran kementerian", "Penerimaan dana alokasi khusus transfer daerah", "Penerimaan cadangan devisa moneter perbankan", "Penerimaan pinjaman program luar negeri bersyarat"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan PNBP dalam Pendapatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pendapatan negara di luar perpajakan dan hibah ditampung dalam <strong>Penerimaan Negara Bukan Pajak (PNBP)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena PNBP merupakan pilar pendapatan negara yang bersumber dari pemanfaatan sumber daya alam, pelayanan instansi, dan dividen BUMN. PNBP wajib disetor ke kas negara dan dikelola dalam mekanisme APBN.</p><p>Jawaban tepat: <strong>A. Penerimaan Negara Bukan Pajak (PNBP)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penerimaan non-anggaran dilarang dalam pengelolaan keuangan negara.</li><li><strong>C</strong>: Dana alokasi khusus merupakan pos belanja transfer ke daerah.</li><li><strong>D</strong>: Cadangan devisa merupakan aset moneter yang dikelola Bank Indonesia.</li><li><strong>E</strong>: Pinjaman luar negeri merupakan komponen pembiayaan utang negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penerimaan Negara Bukan • Pajak (PNBP)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam komposisi pendapatan APBN, penerimaan perpajakan berperan sebagai...", "options": ["Dana cadangan darurat penanggulangan krisis moneter", "Sumber utama pendanaan belanja penyelenggaraan negara", "Penyedia likuiditas pinjaman lunak badan usaha swasta", "Instrumen penjaminan emisi obligasi daerah otonom", "Pos penyangga defisit kas operasional badan perbankan"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Utama Penerimaan Perpajakan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Penerimaan Perpajakan bersumber dari</strong> pajak dalam negeri dan internasional yang menjadi tumpuan utama pendapatan negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pajak menyumbang porsi terbesar dalam membiayai belanja operasional dan belanja pembangunan nasional. Kedaulatan fiskal dicapai ketika belanja negara mampu ditopang secara mandiri oleh penerimaan perpajakan.</p><p>Jawaban tepat: <strong>B. Sumber utama pendanaan belanja penyelenggaraan negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Cadangan krisis moneter dikelola melalui koordinasi KSSK dan BI.</li><li><strong>C</strong>: Pajak tidak dialokasikan untuk pinjaman lunak badan usaha swasta.</li><li><strong>D</strong>: Penjaminan obligasi daerah tunduk pada aturan investasi modal perbendaharaan.</li><li><strong>E</strong>: Operasional perbankan komersial bukan sasaran alokasi belanja pajak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penerimaan Perpajakan bersumber dari</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pungutan negara atas barang tertentu yang konsumsinya perlu dikendalikan masuk dalam pos...", "options": ["Retribusi umum pada kelompok pendapatan bukan pajak", "Bea masuk pada kelompok perdagangan internasional", "Cukai pada kelompok penerimaan pajak dalam negeri", "Pajak pertambahan nilai barang mewah impor", "Pungutan royalti sumber daya mineral dan batu bara"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cukai dan Pajak Lainnya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerimaan pajak dalam negeri mencakup pos <strong>Cukai dan Pajak Lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena cukai merupakan pungutan khusus atas barang yang memiliki sifat dan karakteristik tertentu seperti tembakau dan alkohol. Cukai dimasukkan ke dalam pos pajak dalam negeri pada postur penerimaan APBN.</p><p>Jawaban tepat: <strong>C. Cukai pada kelompok penerimaan pajak dalam negeri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Retribusi umum adalah pungutan atas jasa layanan pemda.</li><li><strong>B</strong>: Bea masuk dipungut atas barang impor yang melintasi perbatasan.</li><li><strong>D</strong>: PPnBM adalah pajak konsumsi atas barang berstatus mewah.</li><li><strong>E</strong>: Royalti minerba tergolong dalam pendapatan sumber daya alam PNBP.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 42) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Cukai dan Pajak Lainnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Objek Pajak Penghasilan pada hakikatnya didefinisikan sebagai...", "options": ["Nilai transaksi penyerahan barang kena pajak di pasar modal", "Seluruh harta kekayaan tetap yang dimiliki oleh suatu badan", "Total omzet penjualan kotor barang tanpa memperhitungkan biaya", "Setiap tambahan kemampuan ekonomis yang diterima wajib pajak", "Selisih kurs valuta asing pada rekening giro kas umum negara"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Objek Pajak Penghasilan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek Pajak Penghasilan adalah <strong>setiap tambahan kemampuan ekonomis yang diterima atau diperoleh Wajib Pajak</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena konsep penghasilan di Indonesia menganut definisi materiil yang luas (*accretion concept*). Setiap tambahan daya pikul ekonomis dengan nama dan dalam bentuk apa pun yang menambah kekayaan menjadi objek pengenaan PPh.</p><p>Jawaban tepat: <strong>D. Setiap tambahan kemampuan ekonomis yang diterima wajib pajak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyerahan barang kena pajak merupakan objek pengenaan PPN.</li><li><strong>B</strong>: Harta kekayaan tetap merupakan objek pengenaan PBB, bukan PPh.</li><li><strong>C</strong>: Omzet kotor adalah dasar peredaran bruto, bukan esensi penghasilan netto.</li><li><strong>E</strong>: Selisih kurs kas negara merupakan transaksi moneter perbendaharaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>setiap tambahan kemampuan ekonomis • yang diterima atau diperoleh Wajib Pajak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Tambahan kemampuan ekonomis dikategorikan sebagai objek PPh apabila dapat dipakai untuk...", "options": ["Menjamin kelancaran likuiditas transaksi pasar uang", "Membayar utang luar negeri badan usaha milik negara", "Membiayai program bantuan sosial pemerintah daerah", "Menambah setoran pokok tabungan perumahan rakyat", "Konsumsi atau menambah kekayaan wajib pajak bersangkutan"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Penggunaan Kemampuan Ekonomis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kemampuan ekonomis objek PPh adalah yang <strong>dapat dipakai untuk konsumsi atau untuk menambah kekayaan Wajib Pajak yang bersangkutan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena indikator penikmatan penghasilan tercermin dari dua hal, yaitu dibelanjakan untuk kebutuhan konsumsi pribadi atau disimpan/diinvestasikan guna menambah aset kekayaan wajib pajak.</p><p>Jawaban tepat: <strong>E. Konsumsi atau menambah kekayaan wajib pajak bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Likuiditas pasar uang merupakan fungsi sistem intermediasi keuangan.</li><li><strong>B</strong>: Membayar utang BUMN bukan indikator kemampuan konsumsi wajib pajak.</li><li><strong>C</strong>: Bantuan sosial pemda didanai melalui belanja APBD pemerintah.</li><li><strong>D</strong>: Tabungan tapera adalah salah satu bentuk instrumen simpanan wajib kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dapat dipakai untuk konsumsi atau • untuk menambah kekayaan Wajib Pajak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan prinsip *worldwide income*, penghasilan yang menjadi objek PPh berasal dari...", "options": ["Dalam negeri Indonesia maupun dari luar negeri Indonesia", "Wilayah kepulauan terluar dan daerah perbatasan negara", "Sentra kawasan industri manufaktur terpadu nasional", "Badan usaha yang terdaftar di bursa efek domestik", "Entitas bisnis yang beroperasi di wilayah pabean bebas"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cakupan Geografis Penghasilan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh mencakup penghasilan <strong>baik yang berasal dari Indonesia maupun dari luar Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena wajib pajak dalam negeri dikenakan pajak atas seluruh penghasilan globalnya (*worldwide income principle*). Tidak dibedakan apakah sumber uang tersebut dihasilkan dari dalam negeri atau dari luar yurisdiksi Indonesia.</p><p>Jawaban tepat: <strong>A. Dalam negeri Indonesia maupun dari luar negeri Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Wilayah perbatasan adalah bagian integral wilayah domestik Indonesia.</li><li><strong>C</strong>: Kawasan industri adalah sentra manufaktur dengan fasilitas fiskal tertentu.</li><li><strong>D</strong>: Bursa efek domestik adalah pasar modal perdagangan saham terdaftar.</li><li><strong>E</strong>: Kawasan bebas pabean mendapat fasilitas penangguhan kepabeanan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berasal dari Indonesia • maupun dari luar Indonesia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Bentuk imbalan berkenaan dengan pekerjaan yang termasuk objek PPh mencakup...", "options": ["Klaim santunan kecelakaan kerja dari asuransi umum", "Gaji, upah, tunjangan, honorarium, bonus, dan pensiun", "Pemberian warisan tanah dan bangunan keluarga lurus", "Setoran tunai pengganti penyertaan modal saham baru", "Bantuan bencana alam dari lembaga filantropi sosial"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Imbalan Pekerjaan sebagai Objek PPh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Imbalan pekerjaan objek PPh meliputi <strong>gaji, upah, tunjangan, honorarium, komisi, bonus, gratifikasi, uang pensiun, atau imbalan bentuk lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena seluruh kompensasi finansial yang diterima pekerja sebagai imbal jasa atas hubungan kerja merupakan penghasilan kena pajak PPh Pasal 21. Bentuk natura tertentu kini juga dapat menjadi objek pajak.</p><p>Jawaban tepat: <strong>B. Gaji, upah, tunjangan, honorarium, bonus, dan pensiun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Klaim asuransi kecelakaan dikecualikan dari objek pajak penghasilan.</li><li><strong>C</strong>: Warisan bukan merupakan objek pajak penghasilan menurut undang-undang.</li><li><strong>D</strong>: Setoran modal pengganti saham dikecualikan dari pengenaan PPh.</li><li><strong>E</strong>: Bantuan bencana sosial memenuhi syarat bukan objek pajak penghasilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>gaji, upah, tunjangan, honorarium, • komisi, bonus, gratifikasi, uang pensiun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Tambahan penghasilan yang diperoleh dari perlombaan atau sayembara digolongkan sebagai...", "options": ["Penyerahan jasa kena pajak dengan pemotongan tarif PPN", "Penerimaan santunan kemanusiaan bebas pengenaan tarif pajak", "Hadiah undian, pekerjaan, kegiatan, dan penghargaan objek PPh", "Dividen terselubung atas penyertaan modal badan hukum", "Bantuan sosial keagamaan yang dikecualikan dari pelaporan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hadiah dan Penghargaan Objek PPh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh mencakup <strong>hadiah dari undian atau pekerjaan atau kegiatan, dan penghargaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena perolehan hadiah undian maupun penghargaan prestasi perlombaan menambah kemampuan ekonomis pemenang secara nyata. Oleh karena itu, panitia wajib memotong PPh atas hadiah tersebut.</p><p>Jawaban tepat: <strong>C. Hadiah undian, pekerjaan, kegiatan, dan penghargaan objek PPh</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hadiah perlombaan bukan merupakan transaksi penyerahan jasa PPN.</li><li><strong>B</strong>: Hadiah sayembara bukan santunan sosial kemanusiaan.</li><li><strong>D</strong>: Dividen adalah pembagian keuntungan perseroan kepada pemegang saham.</li><li><strong>E</strong>: Bantuan keagamaan diatur tersendiri sebagai bukan objek pajak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>hadiah dari undian atau pekerjaan atau kegiatan, dan penghargaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Keuntungan bersih yang diperoleh badan dari hasil kegiatan operasional bisnis disebut...", "options": ["Surplus likuiditas kas operasional bendahara umum", "Pendapatan hibah terikat operasional kementerian", "Penerimaan bukan pajak atas pemanfaatan sumber daya", "Laba usaha yang merupakan objek pajak penghasilan", "Nilai tambah atas penyerahan barang konsumsi akhir"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Laba Usaha sebagai Objek PPh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh secara tegas mencakup <strong>laba usaha</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena selisih lebih penerimaan bruto atas biaya-biaya yang diperkenankan dalam kegiatan perniagaan merupakan laba usaha kena pajak. Laba usaha menjadi basis perhitungan PPh Badan.</p><p>Jawaban tepat: <strong>D. Laba usaha yang merupakan objek pajak penghasilan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Surplus kas bendahara adalah posisi likuiditas perbendaharaan negara.</li><li><strong>B</strong>: Pendapatan hibah adalah transfer sukarela tanpa imbalan komersial.</li><li><strong>C</strong>: Penerimaan SDA PNBP dipungut oleh negara atas eksploitasi alam.</li><li><strong>E</strong>: Nilai tambah penyerahan barang merupakan basis pengenaan PPN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>laba usaha</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Keuntungan yang diperoleh karena pengalihan harta kepada perseroan diakui sebagai...", "options": ["Pengembalian setoran modal awal kepada perseroan", "Penyerahan hibah murni yang bebas dari pengenaan pajak", "Bantuan kemanusiaan darurat bagi kelangsungan usaha", "Penghapusan piutang macet yang disahkan pengadilan", "Keuntungan pengalihan harta pengganti saham atau modal"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keuntungan Pengalihan Harta Badan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh mencakup keuntungan karena pengalihan harta kepada perseroan, persekutuan, dan badan lainnya <strong>sebagai pengganti saham atau penyertaan modal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena selisih lebih antara nilai pasar harta yang diserahkan dengan nilai buku fiskalnya diakui sebagai keuntungan kena pajak (capital gain). Transaksi *inbreng* ini dikenakan PPh atas keuntungan tersebut.</p><p>Jawaban tepat: <strong>E. Keuntungan pengalihan harta pengganti saham atau modal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengembalian modal terjadi saat likuidasi atau penurunan modal disetor.</li><li><strong>B</strong>: Pengalihan saham bermotif investasi komersial, bukan hibah murni.</li><li><strong>C</strong>: Inbreng modal adalah transaksi bisnis korporasi, bukan bantuan sosial.</li><li><strong>D</strong>: Penghapusan piutang merupakan pos pembukuan kerugian piutang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sebagai pengganti saham atau • penyertaan modal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Keuntungan karena peristiwa restrukturisasi perusahaan yang menjadi objek PPh meliputi...", "options": ["Likuidasi, penggabungan, peleburan, pemekaran, dan pemecahan usaha", "Penurunan tarif bea masuk impor bahan baku industri", "Penerbitan surat izin usaha perdagangan oleh pemerintah", "Pemberian dispensasi pembayaran retribusi daerah tahunan", "Perpanjangan masa berlaku hak guna bangunan industri"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keuntungan Restrukturisasi Usaha</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh mencakup keuntungan karena <strong>likuidasi, penggabungan, peleburan, pemekaran, pemecahan, pengambilalihan usaha, atau reorganisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena proses merger, akuisisi, atau likuidasi sering kali menimbulkan revaluasi atau pelepasan aset dengan nilai di atas nilai buku. Keuntungan pelepasan aset tersebut wajib dilaporkan sebagai objek PPh.</p><p>Jawaban tepat: <strong>A. Likuidasi, penggabungan, peleburan, pemekaran, dan pemecahan usaha</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penurunan tarif bea masuk adalah fasilitas tarif kepabeanan.</li><li><strong>C</strong>: Izin usaha perdagangan merupakan dokumen legalitas operasional bisnis.</li><li><strong>D</strong>: Dispensasi retribusi adalah kebijakan keringanan pungutan pemda.</li><li><strong>E</strong>: Perpanjangan HGB adalah urusan hak atas tanah pada kantor pertanahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>likuidasi, penggabungan, peleburan, pemekaran, • pemecahan, pengambilalihan usaha, atau reorganisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Keuntungan yang diperoleh dari penjualan atau pengalihan hak penambangan termasuk...", "options": ["Penerimaan hibah sukarela sektor energi dan sumber daya", "Objek pajak penghasilan atas pengalihan hak penambangan", "Pungutan cukai atas komoditas hasil tambang bernilai tinggi", "Bea keluar atas pengiriman mineral mentah ke luar negeri", "Retribusi daerah atas pemanfaatan air bawah tanah tambang"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keuntungan Pengalihan Hak Tambang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh mencakup keuntungan karena <strong>penjualan atau pengalihan sebagian atau seluruh hak penambangan</strong>, tanda turut serta dalam pembiayaan, atau permodalan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pengalihan konsesi atau izin usaha pertambangan (participating interest) menghasilkan keuntungan modal bernilai ekonomis tinggi. Keuntungan transaksi ini secara tegas dikunci sebagai objek PPh.</p><p>Jawaban tepat: <strong>B. Objek pajak penghasilan atas pengalihan hak penambangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengalihan izin tambang adalah transaksi komersial, bukan hibah.</li><li><strong>C</strong>: Komoditas tambang tidak dikenai cukai melainkan royalti dan pajak.</li><li><strong>D</strong>: Bea keluar dipungut saat ekspor fisik mineral, bukan saat jual-beli izin.</li><li><strong>E</strong>: Retribusi air bawah tanah merupakan pungutan pemda atas pemakaian air.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 43) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penjualan atau pengalihan sebagian atau • seluruh hak penambangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penerimaan kembali pembayaran pajak menjadi objek PPh apabila pajak tersebut sebelumnya...", "options": ["Dipungut dari konsumen akhir pada transaksi ritel", "Disetor ke rekening kas umum daerah secara langsung", "Telah dibebankan sebagai biaya dalam laporan fiskal", "Dihapuskan karena kedaluwarsa masa penagihan utang", "Dialihkan menjadi setoran modal perseroan terbatas"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penerimaan Kembali Pembayaran Pajak</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh meliputi <strong>penerimaan kembali pembayaran pajak yang telah dibebankan sebagai biaya dan pembayaran tambahan pengembalian pajak</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena saat pajak tersebut dibayarkan dulu, wajib pajak telah membebankannya sebagai pengurang penghasilan bruto. Ketika dana tersebut dikembalikan oleh otoritas pajak, restitusi tersebut harus dipulihkan kembali sebagai objek penghasilan.</p><p>Jawaban tepat: <strong>C. Telah dibebankan sebagai biaya dalam laporan fiskal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak konsumen akhir seperti PPN bukan beban biaya penghasilan bruto penjual.</li><li><strong>B</strong>: Penyetoran ke kas pemda tidak otomatis menentukan status beban fiskal.</li><li><strong>D</strong>: Pajak kedaluwarsa gugur demi hukum, bukan diterima kembali kasnya.</li><li><strong>E</strong>: Setoran modal adalah transaksi ekuitas, bukan pembebanan biaya operasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pembayaran pajak yang telah dibebankan sebagai • biaya dan pembayaran tambahan pengembalian pajak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Imbalan finansial atas pinjaman dana yang menjadi objek PPh mencakup...", "options": ["Biaya administrasi penutupan rekening tabungan nasabah", "Pengembalian pokok pinjaman bank yang telah jatuh tempo", "Tambahan saldo simpanan giro akibat koreksi pembukuan", "Bunga termasuk premium, diskonto, dan imbalan jaminan utang", "Selisih cadangan wajib minimum perbankan di bank sentral"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bunga, Premium, dan Diskonto</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh meliputi <strong>bunga termasuk premium, diskonto, dan imbalan karena jaminan pengembalian utang</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena keuntungan dari penempatan modal pinjaman menghasilkan return ekonomis bagi kreditur. Baik berupa bunga periodik, premium obligasi, maupun diskonto surat utang, seluruhnya merupakan objek PPh.</p><p>Jawaban tepat: <strong>D. Bunga termasuk premium, diskonto, dan imbalan jaminan utang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Biaya administrasi bank merupakan beban pengeluaran bagi nasabah.</li><li><strong>B</strong>: Pengembalian pokok pinjaman bukan penghasilan melainkan pelunasan aset piutang.</li><li><strong>C</strong>: Koreksi pembukuan internal bukan imbalan bunga transaksi permodalan.</li><li><strong>E</strong>: Giro wajib minimum adalah instrumen kebijakan likuiditas bank sentral.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bunga termasuk premium, diskonto, dan imbalan karena jaminan • pengembalian utang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Bagian keuntungan perseroan yang dibagikan kepada pemegang saham diakui sebagai...", "options": ["Biaya bunga pinjaman modal kerja tahunan perseroan", "Pengembalian setoran modal pokok perseroan terbatas", "Tambahan dana cadangan umum badan usaha swasta", "Penyerahan hibah murni bebas pemotongan tarif pajak", "Dividen dengan nama dan dalam bentuk apa pun objek PPh"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dividen sebagai Objek PPh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh meliputi <strong>dividen dengan nama dan dalam bentuk apapun</strong>, termasuk dividen dari perusahaan asuransi kepada pemegang polis.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena dividen merupakan distribusi laba bersih perseroan yang dinikmati pemegang saham sebagai imbal hasil investasi ekuitas. Penghasilan dividen dikenakan pajak sesuai ketentuan tarif yang berlaku.</p><p>Jawaban tepat: <strong>E. Dividen dengan nama dan dalam bentuk apa pun objek PPh</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bunga pinjaman adalah imbal hasil utang, bukan pembagian laba ekuitas.</li><li><strong>B</strong>: Pengembalian modal pokok hanya terjadi saat penarikan saham resmi.</li><li><strong>C</strong>: Cadangan umum adalah laba ditahan yang tidak dibagikan kepada pemegang saham.</li><li><strong>D</strong>: Dividen adalah hak pemegang saham atas laba, bukan hibah sosial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dividen dengan nama dan dalam bentuk apapun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Imbalan yang diterima pemilik hak cipta atau paten atas pemanfaatan karyanya adalah...", "options": ["Royalti atau imbalan atas penggunaan hak kekayaan intelektual", "Uang ganti rugi pembebasan lahan untuk proyek pemerintah", "Sewa atas pemakaian gedung perkantoran dan ruang rapat", "Hadiah penghargaan pengabdian masa kerja dari yayasan", "Upah borongan tenaga kerja lepas harian industri pabrik"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Royalti sebagai Objek PPh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh meliputi <strong>royalti atau imbalan atas penggunaan hak</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pemanfaatan hak kekayaan intelektual (paten, merek, hak cipta) oleh pihak lain menimbulkan kewajiban pembayaran royalti. Penerimaan royalti ini merupakan objek PPh yang dipotong di sumbernya.</p><p>Jawaban tepat: <strong>A. Royalti atau imbalan atas penggunaan hak kekayaan intelektual</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Ganti rugi tanah adalah kompensasi pelepasan hak atas barang tidak bergerak.</li><li><strong>C</strong>: Sewa gedung merupakan penghasilan sewa atas pemanfaatan harta fisik.</li><li><strong>D</strong>: Hadiah masa kerja digolongkan sebagai imbalan pekerjaan atau penghargaan.</li><li><strong>E</strong>: Upah harian adalah kompensasi pekerjaan buruh yang masuk PPh Pasal 21.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>royalti atau imbalan atas penggunaan hak</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penghasilan yang diperoleh pemilik dari penyerahan hak pakai kendaraan atau mesin disebut...", "options": ["Bunga pinjaman modal kerja penyedia peralatan proyek", "Sewa dan penghasilan lain sehubungan penggunaan harta", "Keuntungan penjualan aset tetap modal perusahaan pabrik", "Dividen saham atas kepemilikan alat berat konstruksi", "Imbalan jasa konstruksi perencanaan tata ruang kota"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sewa Penggunaan Harta</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh mencakup <strong>sewa dan penghasilan lain sehubungan dengan penggunaan harta</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena imbalan atas pemakaian harta bergerak maupun harta tak bergerak memberikan tambahan kemampuan ekonomis bagi pemilik aset. Penghasilan sewa dikenakan pemotongan PPh sesuai jenis hartanya.</p><p>Jawaban tepat: <strong>B. Sewa dan penghasilan lain sehubungan penggunaan harta</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bunga timbul dari transaksi utang piutang uang, bukan sewa barang fisik.</li><li><strong>C</strong>: Keuntungan penjualan timbul saat hak milik aset dialihkan secara permanen.</li><li><strong>D</strong>: Dividen berasal dari kepemilikan saham pada perseroan, bukan sewa mesin.</li><li><strong>E</strong>: Jasa konstruksi adalah imbalan pengerjaan fisik bangunan fasilitas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sewa dan penghasilan lain sehubungan dengan penggunaan harta</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penerimaan kas yang didapatkan seseorang secara rutin setiap bulan atau tahunan diklasifikasikan sebagai...", "options": ["Penarikan simpanan pokok anggota koperasi simpan pinjam", "Pengembalian dana cadangan risiko likuiditas perbankan", "Penerimaan atau perolehan pembayaran berkala objek PPh", "Pelunasan utang pokok perbendaharaan luar negeri pemerintah", "Tambahan modal disetor oleh pemegang saham pengendali"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembayaran Berkala Objek PPh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh meliputi <strong>penerimaan atau perolehan pembayaran berkala</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pembayaran tunjangan hidup atau anuitas periodik yang diterima secara teratur merupakan aliran penghasilan bagi penerimanya. Sifat penerimaan yang berulang ini menjadikannya objek PPh.</p><p>Jawaban tepat: <strong>C. Penerimaan atau perolehan pembayaran berkala objek PPh</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Simpanan pokok koperasi adalah modal anggota, bukan pembayaran berkala.</li><li><strong>B</strong>: Pengembalian dana cadangan bank merupakan pergeseran likuiditas aset moneter.</li><li><strong>D</strong>: Pelunasan utang luar negeri adalah arus kas keluar pembiayaan anggaran.</li><li><strong>E</strong>: Tambahan modal disetor adalah transaksi penerimaan ekuitas perseroan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penerimaan atau perolehan pembayaran berkala</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Apabila debitur dibebaskan dari kewajiban membayar utang oleh kreditur, debitur memperoleh...", "options": ["Tambahan setoran modal dari kreditur ke dalam kas badan", "Penerimaan bantuan sosial murni tanpa implikasi perpajakan", "Keringanan tarif bea meterai atas perjanjian pinjaman dana", "Keuntungan karena pembebasan utang yang menjadi objek PPh", "Pengurangan kewajiban penyampaian surat pemberitahuan tahunan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keuntungan Pembebasan Utang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh mencakup <strong>keuntungan karena pembebasan utang, kecuali sampai dengan jumlah tertentu yang ditetapkan dengan Peraturan Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena penghapusan kewajiban utang menyebabkan liabilitas berkurang sehingga kekayaan bersih debitur bertambah secara ekonomis. Tambahan kekayaan ini diakui sebagai penghasilan kena pajak bagi debitur.</p><p>Jawaban tepat: <strong>D. Keuntungan karena pembebasan utang yang menjadi objek PPh</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembebasan utang tidak otomatis mengalihkan utang menjadi penyertaan saham.</li><li><strong>B</strong>: Pembebasan utang bisnis bukan merupakan bantuan sosial filantropi.</li><li><strong>C</strong>: Bea meterai dikenakan atas dokumen perikatan, bukan atas pembebasan utang.</li><li><strong>E</strong>: Kewajiban pelaporan SPT tahunan tetap melekat bagi setiap wajib pajak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>keuntungan karena pembebasan utang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Keuntungan pembukuan yang timbul dari fluktuasi nilai tukar valuta asing merupakan...", "options": ["Pendapatan bunga atas penempatan surat perbendaharaan", "Penerimaan bea masuk atas barang impor komoditas mewah", "Cadangan modal likuiditas perbankan di bank sentral", "Koreksi kesalahan pencatatan transaksi kas perbendaharaan", "Keuntungan selisih kurs mata uang asing objek PPh"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keuntungan Selisih Kurs Valuta Asing</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh mencakup <strong>keuntungan selisih kurs mata uang asing</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena apresiasi nilai aset moneter valas terhadap rupiah menghasilkan keuntungan finansial nyata bagi wajib pajak saat direalisasikan maupun di akhir tahun buku. Keuntungan selisih kurs wajib dihitung dalam penghasilan kena pajak.</p><p>Jawaban tepat: <strong>E. Keuntungan selisih kurs mata uang asing objek PPh</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pendapatan bunga dihitung terpisah dari keuntungan selisih kurs valuta.</li><li><strong>B</strong>: Bea masuk adalah tarif kepabeanan atas importasi barang fisik.</li><li><strong>C</strong>: Cadangan likuiditas bank sentral bukan pos penghasilan kena pajak wajib pajak.</li><li><strong>D</strong>: Koreksi pencatatan kas merupakan prosedur teknis akuntansi buku.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>keuntungan selisih kurs mata uang asing</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Salah satu objek PPh yang bersumber dari lembaga bank sentral adalah...", "options": ["Surplus Bank Indonesia setelah pembentukan cadangan tujuan", "Penerbitan uang kartal kertas dan logam emisi baru nasional", "Penarikan giro wajib minimum perbankan komersial daerah", "Penyediaan fasilitas likuiditas intrahari kepada perbankan", "Pemberian dana talangan penjaminan simpanan nasabah perbankan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Surplus Bank Indonesia sebagai Objek PPh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PPh secara khusus mencakup <strong>surplus Bank Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena undang-undang menetapkan surplus hasil operasional Bank Indonesia yang dibagikan kepada pemerintah diperlakukan sebagai objek pajak penghasilan. Hal ini menjamin perlakuan fiskal yang adil atas penerimaan negara.</p><p>Jawaban tepat: <strong>A. Surplus Bank Indonesia setelah pembentukan cadangan tujuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pencetakan uang kartal adalah fungsi pencetakan rupiah, bukan objek pajak.</li><li><strong>C</strong>: Giro wajib minimum merupakan instrumen pengaturan moneter perbankan.</li><li><strong>D</strong>: Fasilitas likuiditas intrahari adalah layanan perbankan bank sentral.</li><li><strong>E</strong>: Penjaminan simpanan adalah fungsi Lembaga Penjamin Simpanan (LPS).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 44) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>surplus Bank Indonesia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Harta peninggalan orang tua yang beralih kepada ahli waris dikategorikan sebagai...", "options": ["Objek PPh final atas pengalihan hak tanah dan bangunan", "Bukan objek pajak penghasilan yang diatur undang-undang", "Penghasilan kena pajak yang wajib dipotong oleh notaris", "Penerimaan negara bukan pajak yang disetor ke kas negara", "Penyerahan barang kena pajak dengan pemotongan tarif PPN"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Warisan Bukan Objek Pajak</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dikecualikan dari objek pajak penghasilan adalah <strong>warisan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena peralihan harta karena pewarisan bukan merupakan transaksi komersial melainkan peristiwa hukum peralihan hak keluarga. Oleh undang-undang, harta warisan secara tegas dinyatakan bukan objek PPh.</p><p>Jawaban tepat: <strong>B. Bukan objek pajak penghasilan yang diatur undang-undang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengalihan warisan dikecualikan dari PPh final pengalihan tanah/bangunan.</li><li><strong>C</strong>: Notaris tidak memungut PPh atas pembagian harta warisan sah.</li><li><strong>D</strong>: Warisan bukan penerimaan PNBP pemerintah.</li><li><strong>E</strong>: Warisan bukan transaksi penyerahan barang kena pajak PPN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>warisan;</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Setoran tunai yang diterima perseroan dari investor dikategorikan bukan objek PPh sebagai...", "options": ["Penerimaan pinjaman dana subordinasi jangka panjang badan", "Penjualan aset operasional perusahaan dengan nilai pasar", "Harta pengganti saham atau pengganti penyertaan modal", "Keuntungan likuidasi sisa kekayaan perseroan terbatas", "Pendapatan jasa pengelolaan portofolio efek pasar modal"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Setoran Modal Pengganti Saham</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bukan objek PPh meliputi <strong>harta termasuk setoran tunai yang diterima oleh suatu badan sebagai pengganti saham atau pengganti penyertaan modal</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena setoran modal merupakan pembentukan ekuitas dasar perusahaan, bukan imbalan atas penyerahan barang atau jasa. Penyetoran modal tidak dikenai pajak penghasilan.</p><p>Jawaban tepat: <strong>C. Harta pengganti saham atau pengganti penyertaan modal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pinjaman subordinasi adalah liabilitas utang, bukan setoran modal saham.</li><li><strong>B</strong>: Penjualan aset operasional merupakan transaksi penyerahan yang kena pajak.</li><li><strong>D</strong>: Keuntungan likuidasi merupakan objek PPh atas pengalihan restrukturisasi.</li><li><strong>E</strong>: Jasa portofolio efek adalah imbalan jasa yang kena PPh Pasal 23.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengganti saham atau pengganti penyertaan modal</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pembayaran klaim yang diterima orang pribadi dari perusahaan asuransi bukan objek PPh untuk...", "options": ["Asuransi gagal bayar obligasi korporasi di bursa efek", "Asuransi kebakaran pabrik dan kehilangan barang kargo laut", "Asuransi tanggung gugat hukum direksi perusahaan publik", "Asuransi kesehatan, kecelakaan, jiwa, dwiguna, dan beasiswa", "Asuransi penurunan nilai aset tanah dan bangunan industri"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Klaim Asuransi Orang Pribadi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bukan objek PPh adalah pembayaran dari perusahaan asuransi kepada orang pribadi sehubungan dengan <strong>asuransi kesehatan, asuransi kecelakaan, asuransi jiwa, asuransi dwiguna, dan asuransi beasiswa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena penggantian biaya medis dan santunan jiwa bertujuan memulihkan kerugian fisik atau musibah yang dialami individu. Penerimaan santunan ini dikecualikan dari pemotongan PPh.</p><p>Jawaban tepat: <strong>D. Asuransi kesehatan, kecelakaan, jiwa, dwiguna, dan beasiswa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Asuransi gagal bayar obligasi adalah instrumen lindung nilai investasi.</li><li><strong>B</strong>: Klaim asuransi properti/kargo badan usaha diperhitungkan dalam laba rugi.</li><li><strong>C</strong>: Asuransi direksi badan usaha bukan asuransi santunan pribadi perorangan.</li><li><strong>E</strong>: Asuransi penurunan nilai tanah bukan jenis asuransi santunan perorangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>asuransi kesehatan, asuransi kecelakaan, asuransi • jiwa, asuransi dwiguna, dan asuransi beasiswa</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dividen yang diterima perseroan terbatas dalam negeri dari penyertaan modal di Indonesia dikategorikan...", "options": ["Pendapatan operasional murni yang wajib dipungut PPN", "Dikenakan tarif PPh final sebesar dua puluh persen", "Objek pemotongan PPh Pasal 23 dengan tarif tertinggi", "Penerimaan hibah terikat bagi perseroan penerima dividen", "Dikecualikan dari objek pajak penghasilan badan penerima"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dividen Antar Badan Usaha Domestik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bukan objek PPh adalah <strong>dividen atau bagian laba yang diterima perseroan terbatas sebagai wajib pajak dalam negeri</strong>, koperasi, yayasan, BUMN/BUMD dari penyertaan modal pada badan usaha di Indonesia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pengecualian ini bertujuan mencegah pengenaan pajak berganda (*economic double taxation*) atas laba yang sama di tingkat anak perusahaan dan induk perusahaan. Dividen antarbadan domestik bebas dari PPh.</p><p>Jawaban tepat: <strong>E. Dikecualikan dari objek pajak penghasilan badan penerima</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dividen adalah transaksi aliran modal yang berada di luar objek PPN.</li><li><strong>B</strong>: Tarif final 20% tidak berlaku untuk dividen antarbadan usaha domestik.</li><li><strong>C</strong>: Pemotongan PPh 23 dihapuskan untuk dividen antarkorporasi dalam negeri.</li><li><strong>D</strong>: Dividen adalah hak pemegang saham, bukan penerimaan hibah sosial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dividen atau bagian laba yang diterima perseroan terbatas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Iuran yang diterima dana pensiun yang pendiriannya disahkan Menteri Keuangan berstatus...", "options": ["Bukan objek pajak penghasilan bagi lembaga dana pensiun", "Objek pemotongan PPh Pasal 21 atas penghasilan bruto", "Penerimaan negara bukan pajak pada kementerian keuangan", "Penyerahan jasa keuangan kena pajak dengan pungutan PPN", "Penerimaan hibah terikat dari pemberi kerja perusahaan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Iuran Dana Pensiun yang Disahkan Menkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bukan objek PPh mencakup <strong>iuran yang diterima atau diperoleh dari dana pensiun yang pendiriannya telah disahkan oleh Menteri Keuangan</strong>, baik dibayar pemberi kerja maupun pegawai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pemerintah memberikan fasilitas perpajakan guna mendorong pembentukan dana pensiun hari tua masyarakat. Iuran dan hasil investasi pada bidang tertentu yang ditetapkan Menkeu bebas PPh.</p><p>Jawaban tepat: <strong>A. Bukan objek pajak penghasilan bagi lembaga dana pensiun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Iuran yang masuk ke dana pensiun bukan penghasilan kena PPh 21 lembaga.</li><li><strong>C</strong>: Iuran pensiun dikelola oleh lembaga dana pensiun mandiri, bukan PNBP.</li><li><strong>D</strong>: Jasa keuangan penghimpunan dana pensiun dikecualikan dari PPN.</li><li><strong>E</strong>: Iuran pensiun adalah kewajiban jaminan kerja, bukan hibah sosial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>iuran yang diterima atau diperoleh dari dana pensiun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Bagian laba yang diterima oleh anggota persekutuan komanditer (CV) atau firma berstatus...", "options": ["Objek PPh final atas penghasilan dividen orang pribadi", "Bukan objek pajak penghasilan di tangan anggota sekutu", "Objek pemotongan PPh Pasal 21 atas imbalan pekerjaan", "Penerimaan royalti atas penyertaan modal kerja sekutu", "Pendapatan jasa manajemen operasional persekutuan CV"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bagian Laba Anggota Persekutuan (CV/Firma)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bukan objek PPh meliputi <strong>bagian laba yang diterima atau diperoleh dari anggota perseroan komanditer, persekutuan, perkumpulan, firma dan kongsi</strong> yang modalnya tidak terbagi atas saham.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena laba CV atau firma telah dikenakan pajak penghasilan badan pada tingkat persekutuan. Saat laba tersebut diambil oleh para sekutu (prive), bagian laba tersebut tidak lagi dikenai PPh agar tidak terkena pajak ganda.</p><p>Jawaban tepat: <strong>B. Bukan objek pajak penghasilan di tangan anggota sekutu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Prive CV bukan dividen perseroan dan tidak dikenakan PPh final.</li><li><strong>C</strong>: Bagian laba bukan imbalan gaji pekerjaan yang dipotong PPh 21.</li><li><strong>D</strong>: Prive adalah penarikan laba modal, bukan pembayaran royalti hak cipta.</li><li><strong>E</strong>: Penarikan bagian laba bukan imbalan jasa manajemen persekutuan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>anggota perseroan • komanditer, persekutuan, perkumpulan, firma dan kongsi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Bagian laba yang diterima perusahaan modal ventura dari badan pasangan usaha di Indonesia...", "options": ["Diwajibkan memungut PPN atas penyediaan jasa permodalan", "Dikenai tarif PPh final tertinggi pada saat pembagian laba", "Dikecualikan dari objek pajak penghasilan modal ventura", "Dialihkan sepenuhnya menjadi penerimaan dividen kas negara", "Dikenai bea meterai tetap atas nominal keuntungan bersih"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penghasilan Modal Ventura</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bukan objek PPh adalah <strong>penghasilan yang diterima oleh perusahaan modal ventura berupa bagian laba dari badan pasangan usaha</strong> yang didirikan dan menjalankan kegiatan di Indonesia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kebijakan fiskal ini dirancang untuk merangsang pendanaan ventura bagi usaha rintisan mikro, kecil, dan menengah. Fasilitas bebas PPh diberikan selama pasangan usahanya bukan perusahaan terbuka.</p><p>Jawaban tepat: <strong>C. Dikecualikan dari objek pajak penghasilan modal ventura</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyertaan modal ventura adalah investasi modal ekuitas, bukan jasa PPN.</li><li><strong>B</strong>: Penghasilan ini justru dibebaskan dari pengenaan PPh final.</li><li><strong>D</strong>: Laba ventura dinikmati investor ventura swasta, bukan dividen negara.</li><li><strong>E</strong>: Bea meterai hanya dikenakan atas dokumen fisik, bukan atas nominal laba.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penghasilan yang diterima oleh perusahaan modal ventura</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Bantuan dana pendidikan yang diterima pelajar atau mahasiswa dikecualikan dari PPh sebagai...", "options": ["Penghargaan atas jasa pengabdian dosen perguruan tinggi", "Gaji honorarium asisten pengajar di lingkungan universitas", "Hadiah perlombaan karya ilmiah tingkat internasional", "Beasiswa yang memenuhi persyaratan tertentu perundang-undangan", "Uang kompensasi ikatan dinas pegawai pada kementerian"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Beasiswa Bukan Objek PPh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dikecualikan dari objek PPh adalah <strong>beasiswa yang memenuhi persyaratan tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena beasiswa ditujukan untuk peningkatan mutu sumber daya manusia dan pendidikan nasional. Sepanjang penerima beasiswa tidak memiliki hubungan istimewa dengan pemberi beasiswa, dana tersebut bebas PPh.</p><p>Jawaban tepat: <strong>D. Beasiswa yang memenuhi persyaratan tertentu perundang-undangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penghargaan jasa pengabdian merupakan objek pajak penghasilan.</li><li><strong>B</strong>: Honorarium asisten pengajar adalah imbalan kerja yang kena PPh 21.</li><li><strong>C</strong>: Hadiah perlombaan karya ilmiah merupakan hadiah yang kena PPh.</li><li><strong>E</strong>: Kompensasi dinas pegawai diperhitungkan dalam penghasilan kena pajak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>beasiswa yang memenuhi persyaratan tertentu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Santunan yang dibayarkan BPJS kepada wajib pajak tertentu ditetapkan sebagai...", "options": ["Objek pungutan pajak pertambahan nilai penyerahan jasa", "Objek PPh Pasal 21 dengan tarif pemotongan progresif", "Penerimaan retribusi daerah perlindungan jaminan sosial", "Penghasilan kena pajak final atas transaksi perasuransian", "Bukan objek pajak penghasilan bagi wajib pajak penerima"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Santunan BPJS Bukan Objek PPh</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bukan objek PPh meliputi <strong>bantuan atau santunan yang dibayarkan oleh Badan Penyelenggara Jaminan Sosial kepada Wajib Pajak tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena santunan jaminan sosial (seperti jaminan kematian atau kecelakaan kerja) berfungsi sebagai jaring pengaman sosial dasar bagi pekerja yang tertimpa musibah. Santunan ini dibebaskan dari potongan pajak.</p><p>Jawaban tepat: <strong>E. Bukan objek pajak penghasilan bagi wajib pajak penerima</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemberian santunan musibah berada di luar ranah pengenaan PPN.</li><li><strong>B</strong>: Santunan jaminan sosial tidak dipotong tarif PPh Pasal 21 progresif.</li><li><strong>C</strong>: BPJS adalah badan hukum publik nasional, bukan penarik retribusi pemda.</li><li><strong>D</strong>: Santunan jaminan sosial bukan objek PPh final transaksi komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 45) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bantuan atau santunan yang dibayarkan oleh Badan Penyelenggara • Jaminan Sosial</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Sisa lebih lembaga pendidikan nirlaba bukan objek PPh jika ditanamkan sarpras maksimal...", "options": ["Dalam jangka waktu paling lama empat tahun sejak diperoleh", "Dalam kurun waktu paling lambat satu tahun anggaran berjalan", "Setelah lewat masa sepuluh tahun penyimpanan di perbankan", "Sebelum akhir masa jabatan pimpinan dewan pembina yayasan", "Dalam batas waktu dua tahun setelah pemeriksaan kantor pajak"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sisa Lebih Lembaga Pendidikan/Litbang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sisa lebih lembaga pendidikan/penelitian nirlaba bukan objek PPh jika ditanamkan kembali dalam sarana prasarana <strong>dalam jangka waktu paling lama 4 (empat) tahun</strong> sejak diperolehnya sisa lebih.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena batas waktu empat tahun memberikan ruang yang memadai bagi yayasan pendidikan untuk membangun fasilitas belajar mengajar tanpa terbebani pajak. Jika dalam 4 tahun tidak digunakan, barulah dikenakan PPh.</p><p>Jawaban tepat: <strong>A. Dalam jangka waktu paling lama empat tahun sejak diperoleh</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Batas satu tahun terlalu singkat untuk proyek pembangunan fisik sarpras.</li><li><strong>C</strong>: Sepuluh tahun melampaui ketentuan undang-undang perpajakan.</li><li><strong>D</strong>: Masa jabatan pengurus yayasan tidak berkaitan dengan batas waktu fiskal.</li><li><strong>E</strong>: Pemeriksaan pajak bukan titik awal perhitungan pemanfaatan sarpras.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dalam jangka waktu paling lama 4 (empat) tahun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dana setoran BPIH dan hasil pengembangannya yang diterima BPKH berstatus sebagai...", "options": ["Objek PPh final atas jasa keuangan simpanan perbankan", "Bukan objek pajak penghasilan yang dikecualikan undang-undang", "Penerimaan negara bukan pajak pada kementerian agama", "Penghasilan usaha syariah yang dikenakan pajak badan", "Dana cadangan umum yang wajib disetor ke kas negara"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dana Setoran BPIH Dikelola BPKH</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dikecualikan dari objek PPh adalah <strong>dana setoran Biaya Penyelenggaraan Ibadah Haji (BPIH)</strong> dan hasil pengembangan keuangan haji yang diterima Badan Pengelola Keuangan Haji (BPKH).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena dana haji merupakan dana amanah umat yang dihimpun semata-mata untuk membiayai operasional ibadah haji jamaah Indonesia. Hasil investasi BPKH dikembalikan untuk kemaslahatan jamaah sehingga bebas PPh.</p><p>Jawaban tepat: <strong>B. Bukan objek pajak penghasilan yang dikecualikan undang-undang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dana haji di BPKH dibebaskan dari pemotongan PPh final perbankan.</li><li><strong>C</strong>: BPKH adalah badan hukum independen di luar pos PNBP kementerian agama.</li><li><strong>D</strong>: BPKH bukan entitas komersial perseroan yang mencari laba usaha kena pajak.</li><li><strong>E</strong>: Dana haji adalah milik jamaah, bukan milik kas umum negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dana setoran Biaya Penyelenggaraan Ibadah Haji (BPIH)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Sisa lebih lembaga sosial keagamaan bukan objek PPh jika ditanamkan dalam sarana atau...", "options": ["Dibelanjakan untuk pembelian kendaraan dinas mewah yayasan", "Dibagikan merata kepada seluruh anggota dewan pengurus", "Ditempatkan sebagai dana abadi lembaga bersangkutan", "Dipinjamkan tanpa bunga kepada badan usaha komersial rekanan", "Dikonversi menjadi portofolio investasi spekulatif valas"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sisa Lebih Lembaga Sosial Keagamaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sisa lebih lembaga sosial/keagamaan bukan objek PPh jika ditanamkan sarpras dalam jangka waktu 4 tahun atau <strong>ditempatkan sebagai dana abadi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pembentukan dana abadi (*endowment fund*) menjamin kesinambungan misi pelayanan keagamaan dan kemanusiaan lembaga dalam jangka panjang. Penempatan dana abadi dilindungi dari pemotongan PPh.</p><p>Jawaban tepat: <strong>C. Ditempatkan sebagai dana abadi lembaga bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Membeli aset mewah pribadi pengurus membatalkan fasilitas pembebasan pajak.</li><li><strong>B</strong>: Membagikan sisa lebih kepada pengurus melanggar asas nirlaba yayasan.</li><li><strong>D</strong>: Meminjamkan dana ke badan komersial menyalahi tujuan sosial keagamaan.</li><li><strong>E</strong>: Investasi spekulatif berisiko tinggi bertentangan dengan tata kelola dana amanah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ditempatkan sebagai dana abadi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Subjek Pajak Penghasilan dalam ketentuan umum perpajakan terdiri atas...", "options": ["Kementerian negara dan lembaga pemerintah non-kementerian", "Pejabat perwakilan diplomatik dan konsulat luar negeri", "Organisasi internasional dan perwakilan lembaga donor", "Orang pribadi atau perorangan dan badan hukum", "Satuan kerja perangkat daerah dan kantor dinas teknis"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Subjek Pajak Penghasilan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Subjek Pajak Penghasilan adalah orang pribadi atau perorangan dan badan hukum</strong> di luar perusahaan minyak bumi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena hukum pajak menetapkan subjek pajak sebagai pihak yang memiliki hak dan kewajiban perpajakan, yang terdiri dari orang pribadi serta badan usaha (PT, CV, BUMN, yayasan, koperasi) yang menerima penghasilan.</p><p>Jawaban tepat: <strong>D. Orang pribadi atau perorangan dan badan hukum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kementerian negara bertindak sebagai pemotong pajak, bukan subjek pajak badan.</li><li><strong>B</strong>: Pejabat diplomatik dan konsulat asing secara tegas dikecualikan dari subjek pajak.</li><li><strong>C</strong>: Organisasi internasional dan staf asingnya bukan merupakan subjek pajak PPh.</li><li><strong>E</strong>: Dinas daerah merupakan unit administrasi pemerintah pengguna anggaran.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Subjek Pajak Penghasilan adalah orang pribadi atau perorangan dan badan • hukum di luar perusahaan minyak bumi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pihak asing yang secara tegas dikecualikan dari Subjek Pajak Penghasilan mencakup...", "options": ["Direktur berkebangsaan asing pada perusahaan perseroan terbuka", "Warga negara asing pemegang izin kerja di korporasi swasta", "Konsultan keuangan asing yang membuka kantor cabang di Jakarta", "Tenaga ahli asing pada proyek konstruksi swasta nasional", "Pejabat perwakilan diplomatik, konsulat, dan staf negara asing"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengecualian Pejabat Diplomatik Asing</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dikecualikan dari subjek pajak adalah <strong>pejabat-pejabat Perwakilan Diplomatik, Konsulat dan pejabat negara asing</strong> serta orang yang bekerja bersama mereka.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena hukum kebiasaan internasional dan Konvensi Wina memberikan hak kekebalan diplomatik (*diplomatic immunity*) bagi perwakilan resmi negara sahabat, termasuk pembebasan dari kewajiban pajak penghasilan di negara penerima.</p><p>Jawaban tepat: <strong>E. Pejabat perwakilan diplomatik, konsulat, dan staf negara asing</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Direktur asing pada PT di Indonesia adalah subjek pajak orang pribadi.</li><li><strong>B</strong>: Pekerja asing di korporasi swasta berstatus subjek pajak dalam negeri.</li><li><strong>C</strong>: Konsultan asing yang membuka cabang merupakan Bentuk Usaha Tetap (BUT).</li><li><strong>D</strong>: Tenaga ahli swasta asing wajib membayar PPh atas penghasilan jasanya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Perwakilan Diplomatik, Konsulat dan pejabat negara asing</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Organisasi internasional dikecualikan dari subjek pajak penghasilan apabila memenuhi syarat...", "options": ["Indonesia menjadi anggota dan tidak menjalankan usaha bisnis", "Menyetorkan dividen keuntungan tahunan kepada pemerintah", "Memiliki kantor cabang pembantu di setiap ibu kota provinsi", "Mendaftarkan seluruh staf lokal menjadi pegawai negeri sipil", "Membatasi kegiatan operasional khusus pada bantuan bencana alam"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengecualian Organisasi Internasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dikecualikan dari subjek pajak adalah <strong>organisasi internasional dan pejabat perwakilan organisasi internasional</strong> yang ditetapkan Menkeu dengan syarat Indonesia anggota dan tidak menjalankan usaha mencari keuntungan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kekebalan pajak bagi badan PBB atau lembaga multilateral diberikan atas dasar keanggotaan Indonesia dan non-profitabilitas misinya. Jika mereka berbisnis komersial, kekebalan pajaknya gugur.</p><p>Jawaban tepat: <strong>A. Indonesia menjadi anggota dan tidak menjalankan usaha bisnis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Organisasi internasional adalah entitas nirlaba, tidak menyetor dividen.</li><li><strong>C</strong>: Kantor cabang di setiap provinsi bukan syarat pembebasan pajak internasional.</li><li><strong>D</strong>: Staf lokal tetap berstatus warga sipil biasa, bukan pegawai negeri sipil.</li><li><strong>E</strong>: Kegiatan mencakup ekonomi, pendidikan, dan kesehatan, tidak hanya bencana.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>internasional dan pejabat perwakilan organisasi internasional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pejabat diplomatik asing dikecualikan dari subjek pajak selama mereka tidak...", "options": ["Menyewa rumah tinggal di luar area kantor kedutaan besar", "Melaksanakan kegiatan yang bersifat perusahaan di Indonesia", "Membuka rekening tabungan pada bank umum nasional berizin", "Membeli kebutuhan pokok harian di pasar ritel modern lokal", "Menggunakan kendaraan pribadi berpelat nomor diplomatik khusus"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Pengecualian Diplomat Asing</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pejabat diplomatik bebas PPh <strong>selama mereka tidak melaksanakan kegiatan yang bersifat perusahaan di Indonesia (secara timbal balik)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kekebalan diplomatik hanya melindungi tugas kedinasan perwakilan negara. Apabila seorang diplomat melakukan aktivitas bisnis komersial pribadi di luar tugas kedutaan, ia wajib membayar pajak atas bisnisnya.</p><p>Jawaban tepat: <strong>B. Melaksanakan kegiatan yang bersifat perusahaan di Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menyewa tempat tinggal diperbolehkan untuk menunjang kehidupan diplomatik.</li><li><strong>C</strong>: Membuka rekening perbankan adalah fasilitas operasional harian yang sah.</li><li><strong>D</strong>: Membeli kebutuhan pokok adalah transaksi konsumsi harian biasa.</li><li><strong>E</strong>: Pelat nomor diplomatik merupakan hak fasilitas resmi negara perwakilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bersifat perusahaan di Indonesia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Undang-Undang Pajak Penghasilan terakhir kali diubah melalui pengesahan regulasi...", "options": ["UU Nomor 15 Tahun 2004 tentang Pemeriksaan Tanggung Jawab Keuangan", "UU Nomor 1 Tahun 2004 tentang Perbendaharaan Negara Republik", "UU Nomor 7 Tahun 2021 tentang Harmonisasi Peraturan Perpajakan", "UU Nomor 25 Tahun 2004 tentang Sistem Perencanaan Pembangunan", "UU Nomor 9 Tahun 2018 tentang Penerimaan Negara Bukan Pajak"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi Terakhir Pajak Penghasilan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketentuan PPh diatur dalam UU Nomor 7 tahun 1983 yang diubah terakhir kali dengan <strong>UU Nomor 7 Tahun 2021 tentang Harmonisasi Peraturan Perpajakan (HPP)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Modul pengelolaan keuangan negara menegaskan bahwa reformasi perpajakan penghasilan terkini dilakukan melalui pengesahan Undang-Undang Nomor 7 Tahun 2021 tentang Harmonisasi Peraturan Perpajakan yang menyelaraskan lapisan tarif dan objek pajak.</p><p>Jawaban tepat: <strong>C. UU Nomor 7 Tahun 2021 tentang Harmonisasi Peraturan Perpajakan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: UU 15/2004 mengatur pemeriksaan pengelolaan keuangan oleh BPK.</li><li><strong>B</strong>: UU 1/2004 mengatur asas dan pelaksanaan perbendaharaan negara.</li><li><strong>D</strong>: UU 25/2004 mengatur sistem perencanaan pembangunan nasional.</li><li><strong>E</strong>: UU 9/2018 mengatur tentang tata kelola Penerimaan Negara Bukan Pajak (PNBP).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 46) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>UU Nomor 7 Tahun 2021 tentang Harmonisasi Peraturan Perpajakan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Perusahaan yang melakukan kegiatan eksplorasi dan eksploitasi migas diwajibkan mendapat...", "options": ["Sertifikat hak milik atas tanah cadangan ladang minyak", "Surat rekomendasi izin impor dari kementerian perdagangan", "Persetujuan lisan dari pimpinan pemerintah daerah setempat", "Izin dan perikatan kontrak kerja sama dengan Pertamina", "Izin pinjam pakai kawasan hutan dari badan pertanahan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kerja Sama Eksplorasi Migas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perusahaan yang akan melakukan kegiatan di bidang <strong>minyak bumi dan gas alam harus mendapat izin dari Pertamina</strong> melalui perjanjian kontrak kerja sama.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena negara memegang kuasa pertambangan migas dan mendelegasikannya melalui badan yang ditunjuk untuk mengikat kontrak dengan kontraktor migas. Kontrak ini mengatur hak dan kewajiban eksploitasi sumber daya migas.</p><p>Jawaban tepat: <strong>D. Izin dan perikatan kontrak kerja sama dengan Pertamina</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ladang migas adalah milik negara yang dikuasai penuh, bukan milik swasta.</li><li><strong>B</strong>: Izin impor kemendag adalah izin perdagangan umum, bukan kuasa hulu migas.</li><li><strong>C</strong>: Persetujuan lisan pemda tidak memiliki kekuatan hukum kontrak perikatan migas.</li><li><strong>E</strong>: Izin kawasan hutan adalah izin lingkungan pendukung, bukan kontrak hulu migas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>minyak bumi dan gas alam harus mendapat izin dari Pertamina</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan perjanjian kontrak migas, kewajiban pokok kontraktor minyak meliputi penyerahan...", "options": ["Cadangan devisa hasil ekspor langsung ke kas bank swasta", "Seluruh saham kepemilikan korporasi kepada pemerintah daerah", "Laba bersih operasional perusahaan setelah dikurangi dividen", "Peralatan pengeboran tanpa memperhitungkan pengembalian biaya", "Sebagian hasil minyak dan gas serta membayar PPh migas"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Kontraktor Migas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kontraktor migas wajib <strong>menyerahkan sebagian dari minyak dan gas alam yang dihasilkannya kepada Pertamina, membayar pajak atas penghasilan (PPh MIGAS)</strong>, membayar PBB, dan pajak lainnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena sistem bagi hasil mewajibkan kontraktor menyetorkan porsi bagian minyak mentah/gas milik negara (*government share*) di samping memenuhi kewajiban pembayaran PPh Migas atas keuntungan bersih yang diperolehnya.</p><p>Jawaban tepat: <strong>E. Sebagian hasil minyak dan gas serta membayar PPh migas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Devisa migas wajib ditempatkan di bank dalam negeri sesuai aturan BI.</li><li><strong>B</strong>: Kontraktor tidak menyerahkan saham perusahaan melainkan bagi hasil produksi.</li><li><strong>C</strong>: Kewajiban bukan pembagian laba deviden melainkan *split* produksi dan pajak.</li><li><strong>D</strong>: Biaya peralatan dihitung melalui mekanisme *cost recovery* kontrak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kontraktor harus menyerahkan sebagian dari minyak dan • gas alam yang dihasilkannya kepada Pertamina</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Bentuk perjanjian kerja sama perminyakan yang membagi langsung hasil produksi migas adalah...", "options": ["Kontrak Bagi Hasil (Production Sharing Contract)", "Kontrak karya pertambangan umum era pra-kemerdekaan", "Perjanjian sewa guna usaha kilang pengolahan minyak", "Perjanjian kemitraan lisensi waralaba bahan bakar ritel", "Kontrak pengadaan barang jasa konstruksi pipa lepas pantai"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Production Sharing Contract (PSC)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perjanjian kontraktor minyak semula dalam bentuk Kontrak Karya, dan berkembang menjadi <strong>Kontrak Bagi Hasil (Production Sharing Contract)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena PSC merupakan konsep kontrak khas Indonesia yang membagi komoditas fisik minyak mentah/gas antara negara dan kontraktor setelah dikurangi biaya operasi. Model ini menjaga kedaulatan negara atas sumber daya alam.</p><p>Jawaban tepat: <strong>A. Kontrak Bagi Hasil (Production Sharing Contract)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kontrak karya menerapkan sistem konsesi dengan pembayaran royalti dan pajak.</li><li><strong>C</strong>: Sewa kilang adalah transaksi sewa fasilitas hilir pengolahan BBM.</li><li><strong>D</strong>: Waralaba ritel adalah bisnis hilir stasiun pengisian bahan bakar umum.</li><li><strong>E</strong>: Pengadaan pipa adalah kontrak jasa teknis instalasi fasilitas migas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kontrak Bagi Hasil (Production Sharing Contract)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Bentuk Kontrak Operasi Bersama (Joint Operation Contract) dalam sektor migas mulai berlaku pada...", "options": ["Tahun 1945 bersamaan dengan penetapan UUD negara Republik", "Tahun 1977 dengan bagian negara yang lebih menguntungkan", "Tahun 1960 saat pengesahan undang-undang pokok agraria", "Tahun 1998 pada masa awal bergulirnya reformasi birokrasi", "Tahun 2004 setelah pengesahan undang-undang perbendaharaan"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Joint Operation Contract (JOC)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kontrak Bagi Hasil yang Disempurnakan atau <strong>Kontrak Operasi Bersama (Joint Operation Contract) mulai berlaku tahun 1977</strong> dengan perbedaan utama bagian Indonesia yang lebih menguntungkan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena evolusi regulasi migas terus disempurnakan untuk meningkatkan penerimaan kas negara. Mulai tahun 1977, skema JOC diterapkan dengan formula bagi hasil yang lebih memprioritaskan kepentingan nasional.</p><p>Jawaban tepat: <strong>B. Tahun 1977 dengan bagian negara yang lebih menguntungkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tahun 1945 adalah masa proklamasi kemerdekaan dan pembentukan konstitusi.</li><li><strong>C</strong>: Tahun 1960 adalah tahun diundangkannya UUPA mengenai pertanahan nasional.</li><li><strong>D</strong>: Tahun 1998 merupakan titik awal reformasi politik nasional.</li><li><strong>E</strong>: Tahun 2004 adalah tahun pengesahan UU Perbendaharaan Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Operation Contract) yang mulai berlaku tahun 1977</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pajak Pertambahan Nilai (PPN) diklasifikasikan sebagai pajak tidak langsung karena...", "options": ["Dibayarkan khusus oleh importir barang modal industri berat", "Dipungut langsung dari laba bersih pembukuan tahunan badan", "Beban pajaknya dialihkan kepada pihak konsumen akhir", "Ditetapkan secara mandiri oleh bendahara pengeluaran satker", "Disetorkan ke kas daerah tanpa melalui rekening kas negara"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter PPN sebagai Pajak Tidak Langsung</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pajak Pertambahan Nilai merupakan pajak tidak langsung</strong>, yang dikenakan atas transaksi penyerahan Barang Kena Pajak maupun Jasa Kena Pajak.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena penanggung jawab pembayaran pajak (penjual/PKP) berbeda dengan penanggung beban pajak sebenarnya (konsumen akhir). Penjual memungut PPN dari pembeli untuk kemudian disetorkan ke kas negara.</p><p>Jawaban tepat: <strong>C. Beban pajaknya dialihkan kepada pihak konsumen akhir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PPN dikenakan atas seluruh rantai konsumsi domestik, bukan khusus importir.</li><li><strong>B</strong>: Pajak atas laba bersih pembukuan merupakan karakter Pajak Penghasilan (PPh).</li><li><strong>D</strong>: Tarif PPN ditetapkan oleh undang-undang, bukan oleh bendahara satker.</li><li><strong>E</strong>: PPN adalah pajak pusat yang disetorkan ke Kas Umum Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pajak Pertambahan Nilai merupakan pajak tidak langsung</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pada dasarnya pengenaan Pajak Pertambahan Nilai (PPN) secara definitif dibebankan kepada...", "options": ["Petugas pemeriksa pajak di kantor pelayanan perpajakan", "Produsen utama penghasil bahan baku mentah perkebunan", "Pedagang perantara distributor tingkat wilayah provinsi", "Konsumen akhir yang memanfaatkan barang atau jasa kena pajak", "Penyedia jasa transportasi logistik pengiriman barang"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembebanan PPN kepada Konsumen Akhir</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada dasarnya <strong>pengenaan Pajak Pertambahan Nilai akan dibebankan kepada konsumen akhir</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena sebagai pajak konsumsi domestik, setiap mata rantai distribusi dapat mengkreditkan pajak masukannya. Beban pajak riil berhenti dan ditanggung sepenuhnya oleh pengguna atau konsumen akhir barang/jasa.</p><p>Jawaban tepat: <strong>D. Konsumen akhir yang memanfaatkan barang atau jasa kena pajak</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Petugas pajak bertindak sebagai aparatur fiskus pengawas kepatuhan.</li><li><strong>B</strong>: Produsen bahan baku mengkreditkan pajak masukan atas belanja modalnya.</li><li><strong>C</strong>: Distributor perantara bertindak sebagai pemungut PPN perantara rantai pasok.</li><li><strong>E</strong>: Penyedia logistik adalah pengusaha kena pajak pemungut PPN jasa angkut.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengenaan Pajak Pertambahan Nilai akan dibebankan • kepada konsumen akhir</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Karena merupakan pajak tidak langsung, pengenaan PPN atas barang yang sama...", "options": ["Wajib dibayarkan sebelum barang diproduksi oleh industri", "Boleh dipungut satu kali pada tingkat pabrikan utama", "Dibatasi pemungutannya maksimal dua kali dalam setahun", "Gugur dengan sendirinya apabila barang disimpan di gudang", "Dapat dikenakan berkali-kali pada setiap jalur distribusi"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Multi Stage Levy dalam PPN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengenaan PPN atas Barang Kena Pajak yang sama <strong>dapat dikenakan berkali-kali</strong> pada setiap tingkat mata rantai penyerahan barang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena PPN menganut sistem pemungutan bertahap (*multi-stage levy*). PPN dikenakan mulai dari pabrikan ke distributor, distributor ke pengecer, hingga pengecer ke konsumen, namun tidak kumulatif karena ada mekanisme pengkreditan.</p><p>Jawaban tepat: <strong>E. Dapat dikenakan berkali-kali pada setiap jalur distribusi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PPN terutang saat penyerahan barang, bukan sebelum produksi dimulai.</li><li><strong>B</strong>: Pemungutan satu kali di pabrik adalah ciri sistem pajak penjualan lama (PPn).</li><li><strong>C</strong>: Pemungutan PPN terjadi setiap saat terjadi transaksi, bukan tahunan.</li><li><strong>D</strong>: Penyimpanan di gudang tidak menggugurkan status barang kena pajak.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 47) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dapat dikenakan berkali-kali</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pungutan negara yang dikenakan atas barang berkonsumsi khusus disebut...", "options": ["Cukai atas barang yang mempunyai karakteristik tertentu", "Pajak pertambahan nilai atas penyerahan barang mewah", "Bea masuk atas komoditas impor kawasan perdagangan bebas", "Retribusi daerah atas pemakaian fasilitas gedung publik", "Pungutan hasil perkebunan kelapa sawit mentah ekspor"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Pungutan Cukai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cukai adalah <strong>pungutan negara yang dikenakan terhadap barang-barang tertentu yang mempunyai sifat dan karakteristik yang ditentukan</strong> (Barang Kena Cukai).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena cukai merupakan instrumen fiskal pengendali konsumsi barang tertentu demi melindungi kesehatan masyarakat dan ketertiban umum. Pungutan ini memiliki karakteristik pembebanan yang selektif.</p><p>Jawaban tepat: <strong>A. Cukai atas barang yang mempunyai karakteristik tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: PPnBM adalah pajak konsumsi umum atas barang mewah.</li><li><strong>C</strong>: Bea masuk adalah tarif kepabeanan atas importasi barang lintas batas.</li><li><strong>D</strong>: Retribusi daerah adalah pembayaran atas pemanfaatan jasa dinas pemda.</li><li><strong>E</strong>: Pungutan sawit tergolong dalam tarif pungutan ekspor atau PNBP sawit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pungutan negara yang dikenakan terhadap barang-barang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pungutan cukai di Indonesia diatur dalam ketentuan perundang-undangan pokok...", "options": ["UU Nomor 17 Tahun 2003 tentang Keuangan Negara", "UU Nomor 11 Tahun 1995 tentang Cukai", "UU Nomor 1 Tahun 2004 tentang Perbendaharaan Negara", "UU Nomor 15 Tahun 2004 tentang Pemeriksaan BPK", "UU Nomor 25 Tahun 2004 tentang Perencanaan Nasional"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Regulasi Cukai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cukai diatur dalam <strong>Undang-undang No. 11 tahun 1995 tentang Cukai sebagaimana telah dirubah dalam Undang-Undang nomor 7 Tahun 2021 tentang Harmonisasi Peraturan Perpajakan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena UU 11/1995 merupakan payung hukum formil pengenaan dan pengawasan cukai nasional. Ketentuan ini terus diselaraskan dengan kebutuhan pengendalian barang kena cukai modern.</p><p>Jawaban tepat: <strong>B. UU Nomor 11 Tahun 1995 tentang Cukai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: UU 17/2003 mengatur asas umum tata kelola keuangan negara.</li><li><strong>C</strong>: UU 1/2004 mengatur administrasi perbendaharaan negara.</li><li><strong>D</strong>: UU 15/2004 mengatur prosedur pemeriksaan keuangan oleh BPK.</li><li><strong>E</strong>: UU 25/2004 mengatur sistem perencanaan pembangunan nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>11 tahun 1995 tentang Cukai sebagaimana telah dirubah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Barang Kena Cukai (BKC) pada hakikatnya merupakan barang-barang yang...", "options": ["Diproduksi khusus oleh badan usaha milik negara tunggal", "Diberikan subsidi harga secara penuh oleh pemerintah pusat", "Dalam pemakaiannya perlu dibatasi dan diawasi peredarannya", "Bebas didistribusikan tanpa memerlukan izin pita cukai", "Memiliki harga jual paling murah di pasar komoditas ritel"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Pokok BKC</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Yang dimaksud dengan BKC yaitu <strong>barang-barang yang dalam pemakaiannya perlu dibatasi dan diawasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena motif utama pengenaan cukai bukan semata-mata mencari penerimaan kas (*budgetair*), melainkan fungsi mengatur (*regulerend*) untuk menekan dampak negatif konsumsi barang tersebut bagi masyarakat.</p><p>Jawaban tepat: <strong>C. Dalam pemakaiannya perlu dibatasi dan diawasi peredarannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: BKC dapat diproduksi oleh industri swasta pemegang izin pabrik NPPBKC.</li><li><strong>B</strong>: BKC tidak disubsidi melainkan dibebani pungutan tambahan yang tinggi.</li><li><strong>D</strong>: Peredaran BKC wajib diawasi ketat dan menggunakan pita cukai sah.</li><li><strong>E</strong>: Pengenaan cukai justru sengaja menaikkan harga jual komoditas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pemakaiannya perlu dibatasi dan diawasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pihak yang ditetapkan sebagai Subjek Cukai dalam undang-undang meliputi...", "options": ["Pengemudi kendaraan ekspedisi pengantar barang pabrikan", "Konsumen akhir yang membeli rokok di warung kelontong", "Petani penanam daun tembakau dan perajin cengkih lokal", "Pengusaha pabrik, pengusaha tempat menimbun, dan importir BKC", "Petugas kasir toko ritel modern penjual minuman kemasan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Subjek Pungutan Cukai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Subjek cukai adalah <strong>pengusaha pabrik atau pengusaha tempat menimbun dan importir BKC</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena titik pemungutan cukai berada pada produsen di pabrik atau importir saat barang dikeluarkan ke peredaran bebas. Merekalah pihak yang berkewajiban melunasi pita cukai kepada negara.</p><p>Jawaban tepat: <strong>D. Pengusaha pabrik, pengusaha tempat menimbun, dan importir BKC</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengemudi ekspedisi adalah pihak penyedia jasa angkutan logistik.</li><li><strong>B</strong>: Konsumen akhir adalah penanggung beban ekonomi cukai, bukan subjek pelunas.</li><li><strong>C</strong>: Petani tembakau adalah penyedia bahan baku mentah sebelum diolah pabrik.</li><li><strong>E</strong>: Kasir ritel adalah karyawan toko penjual eceran tingkat akhir.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pabrik atau pengusaha tempat menimbun dan importir BKC</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Objek Barang Kena Cukai kelompok pertama menurut ketentuan undang-undang adalah...", "options": ["Zat kimia pewarna makanan buatan berskala industri besar", "Bahan bakar minyak bensin dengan nilai oktan tinggi impor", "Minyak pelumas mesin industri pabrik manufaktur otomotif", "Gas alam cair yang disalurkan melalui jaringan pipa pipa", "Etil alkohol atau etanol tanpa membedakan bahan pembuatannya"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Etil Alkohol sebagai Objek Cukai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek BKC meliputi <strong>etil alkohol atau etanol, dengan tidak mengindahkan bahan yang digunakan dan proses pembuatannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena senyawa etanol murni merupakan bahan dasar yang dapat disalahgunakan atau digunakan dalam industri minuman keras. Oleh karena itu, etanol ditetapkan sebagai objek cukai tanpa memandang bahan asalnya.</p><p>Jawaban tepat: <strong>E. Etil alkohol atau etanol tanpa membedakan bahan pembuatannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pewarna makanan tunduk pada regulasi pangan dan izin edar BPOM.</li><li><strong>B</strong>: BBM bensin bukan objek cukai melainkan objek PPN dan PBBKB daerah.</li><li><strong>C</strong>: Minyak pelumas belum ditetapkan sebagai barang kena cukai undang-undang.</li><li><strong>D</strong>: Gas alam cair adalah komoditas migas di luar pos pungutan cukai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>etil alkohol atau etanol, dengan tidak mengindahkan bahan yang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kriteria pengenaan cukai terhadap minuman beralkohol mencakup minuman yang...", "options": ["Mengandung etil alkohol dalam kadar berapa pun termasuk konsentrat", "Mengandung pemanis buatan dengan kadar gula melebihi standar", "Menggunakan bahan pengawet cair sintetis bersertifikat pabrik", "Dihasilkan dari perasan sari buah alami tanpa bahan kimia", "Dikemas dalam wadah botol kaca daur ulang ramah lingkungan"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Minuman Mengandung Etil Alkohol (MMEA)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek BKC mencakup <strong>minuman yang mengandung etil alkohol dalam kadar berapa pun, dengan tidak mengindahkan bahan yang digunakan dan proses pembuatannya, termasuk konsentrat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pengenaan cukai MMEA tidak membatasi ambang batas minimal kadar alkohol. Sepanjang terdapat kandungan etanol di dalamnya, minuman tersebut terutang cukai sesuai golongan tarifnya.</p><p>Jawaban tepat: <strong>A. Mengandung etil alkohol dalam kadar berapa pun termasuk konsentrat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pemanis buatan adalah bahan tambahan pangan di luar objek cukai MMEA.</li><li><strong>C</strong>: Bahan pengawet sintetis diatur dalam standar keamanan pangan.</li><li><strong>D</strong>: Sari buah murni tanpa alkohol adalah minuman konsumsi non-cukai.</li><li><strong>E</strong>: Kemasan botol kaca adalah media kemasan fisik produk ritel.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>minuman yang mengandung etil alkohol dalam kadar berapa pun,</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Cakupan objek cukai hasil tembakau menurut regulasi harmonisasi perpajakan meliputi...", "options": ["Pupuk organik daun tembakau untuk penyubur tanah perkebunan", "Sigaret, cerutu, rokok daun, tembakau iris, dan rokok elektrik", "Kertas pembungkus kayu gelondongan pengiriman kargo ekspor", "Cairan pembersih lantai beraroma ekstrak tanaman cengkih", "Pestisida nabati pembasmi hama tanaman industri pangan"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ragam Objek Hasil Tembakau</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hasil tembakau objek cukai meliputi <strong>sigaret, cerutu, rokok daun, tembakau iris, rokok elektrik, dan hasil pengolahan tembakau lainnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena perluasan objek cukai hasil tembakau mencakup produk tembakau konvensional maupun produk modern seperti rokok elektrik (vape). Pengenaan cukai ditujukan untuk membatasi konsumsi nikotin.</p><p>Jawaban tepat: <strong>B. Sigaret, cerutu, rokok daun, tembakau iris, dan rokok elektrik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pupuk organik tanaman tembakau adalah sarana produksi pertanian.</li><li><strong>C</strong>: Kertas pembungkus kargo adalah bahan kemasan industri logistik.</li><li><strong>D</strong>: Pembersih lantai beraroma cengkih bukan produk konsumsi hisap tembakau.</li><li><strong>E</strong>: Pestisida nabati adalah bahan kimia pengendali hama tanaman budidaya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>iris, rokok elektrik, dan hasil pengolahan tembakau lainnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "PBB diklasifikasikan sebagai pajak yang bersifat kebendaan karena...", "options": ["Pembayaran pajak dapat diangsur menggunakan komoditas hasil tani", "Tarif pajak dihitung berdasarkan tingkat penghasilan pemilik", "Besarnya pajak terutang ditentukan oleh keadaan fisik objek", "Kewajiban pajak gugur secara otomatis apabila pemilik berpindah domisili", "Penetapan pajak mendasarkan pada status kewarganegaraan pembayar"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sifat Kebendaan Pajak Bumi dan Bangunan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PBB adalah <strong>pajak yang bersifat kebendaan dalam arti besarnya pajak terutang ditentukan oleh keadaan objek yaitu bumi/tanah dan atau bangunan</strong>. Keadaan subjek tidak ikut menentukan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena besarnya tagihan PBB semata-mata dihitung dari Nilai Jual Objek Pajak (NJOP) tanah dan bangunan. Siapa pun subjek yang memiliki atau memanfaatkan tanah tersebut membayar tarif nominal yang sama.</p><p>Jawaban tepat: <strong>C. Besarnya pajak terutang ditentukan oleh keadaan fisik objek</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembayaran PBB wajib dalam bentuk uang rupiah, bukan komoditas tani.</li><li><strong>B</strong>: Pajak berdasarkan kemampuan penghasilan subjek adalah ciri PPh.</li><li><strong>D</strong>: Kewajiban PBB melekat pada objek tanah, tidak gugur karena pindah domisili.</li><li><strong>E</strong>: Status kewarganegaraan tidak memengaruhi perhitungan nilai objek PBB.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pajak yang bersifat kebendaan dalam arti besarnya pajak • terutang ditentukan oleh keadaan objek</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dalam pembagian klasifikasinya, objek PBB terbagi ke dalam sektor...", "options": ["Jasa keuangan perbankan, pasar modal, asuransi, dan dana pensiun", "Industri manufaktur, perdagangan ritel, perhotelan, dan pariwisata", "Transportasi laut, penerbangan udara, logistik, dan pergudangan", "Perkotaan, perdesaan, perkebunan, perhutanan, dan pertambangan", "Pendidikan tinggi, penelitian kesehatan, kebudayaan, dan olahraga"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Lima Sektor Objek PBB</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PBB terbagi ke dalam lima sektor: <strong>Sektor Perkotaan, Sektor Pedesaan, Sektor Perkebunan, Sektor Perhutanan, dan Sektor Pertambangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena undang-undang mengelompokkan tanah dan bangunan berdasarkan bentang pemanfaatannya. Sektor perkotaan dan perdesaan kini dikelola pemda (PBB-P2), sedangkan perkebunan, perhutanan, pertambangan (PBB-P3) tetap di pusat.</p><p>Jawaban tepat: <strong>D. Perkotaan, perdesaan, perkebunan, perhutanan, dan pertambangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perbankan dan asuransi adalah sektor industri jasa keuangan OJK.</li><li><strong>B</strong>: Manufaktur dan perhotelan adalah sektor usaha bisnis komersial.</li><li><strong>C</strong>: Transportasi dan pergudangan adalah bidang operasional logistik niaga.</li><li><strong>E</strong>: Pendidikan dan kebudayaan adalah bidang urusan sosial kemasyarakatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 48) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Perkotaan, Sektor Pedesaan, Sektor Perkebunan, Sektor Perhutanan, dan • Sektor Pertambangan Migas dan Pertambangan Umum</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "PBB Sektor Perdesaan dan Sektor Perkotaan dialihkan dari pajak pusat menjadi...", "options": ["Pungutan sukarela masyarakat di lingkungan rukun tetangga", "Retribusi perizinan tertentu pada kementerian perindustrian", "Penerimaan negara bukan pajak pada kementerian keuangan", "Pajak khusus perbatasan yang dikelola badan pengelola pos", "Pajak daerah paling lambat tanggal satu Januari 2014"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengalihan PBB-P2 Menjadi Pajak Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>PBB Sektor Pedesaan dan PBB Sektor Perkotaan dialihkan pajak pusat menjadi pajak daerah</strong> selambat-lambatnya tanggal 1 Januari 2014.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena desentralisasi perpajakan menyerahkan basis pajak tanah permukiman kepada pemerintah kabupaten/kota sebagai PBB-P2. Hal ini memperkuat kemandirian fiskal dan Pendapatan Asli Daerah (PAD).</p><p>Jawaban tepat: <strong>E. Pajak daerah paling lambat tanggal satu Januari 2014</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PBB-P2 adalah pungutan pajak resmi yang berkekuatan hukum memaksa.</li><li><strong>B</strong>: PBB-P2 bukan retribusi perizinan kementerian melainkan pajak daerah.</li><li><strong>C</strong>: PBB-P2 bukan pos PNBP melainkan pajak daerah kabupaten/kota.</li><li><strong>D</strong>: PBB-P2 bukan pajak khusus perbatasan pos melainkan pajak daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 49) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PBB Sektor Pedesaan dan PBB Sektor Perkotaan dialihkan pajak pusat • menjadi pajak daerah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pajak atas perolehan hak tanah (BPHTB) secara definitif telah dialihkan menjadi...", "options": ["Pajak daerah terhitung sejak tanggal satu Januari 2011", "Penerimaan bea masuk kepabeanan di pelabuhan laut", "Pungutan cukai atas transaksi peralihan sertifikat tanah", "Pos penerimaan hibah daerah pada rekening kas daerah", "Pajak perdagangan internasional di kawasan ekonomi khusus"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengalihan BPHTB Menjadi Pajak Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Bea Perolehan Hak atas Tanah dan Bangunan (BPHTB) per 1 januari 2011 telah dialihkan</strong> menjadi pajak daerah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena sesuai undang-undang pajak daerah dan retribusi daerah, per 1 Januari 2011 pemungutan BPHTB sepenuhnya beralih dari Direktorat Jenderal Pajak kepada dinas pendapatan daerah kabupaten/kota.</p><p>Jawaban tepat: <strong>A. Pajak daerah terhitung sejak tanggal satu Januari 2011</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: BPHTB bukan penerimaan kepabeanan laut melainkan pajak transaksi tanah.</li><li><strong>C</strong>: Peralihan tanah tidak dipungut cukai melainkan BPHTB daerah.</li><li><strong>D</strong>: BPHTB adalah pos pendapatan pajak daerah resmi, bukan dana hibah.</li><li><strong>E</strong>: BPHTB berlaku umum di wilayah kabupaten/kota, bukan pajak perdagangan luar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 49) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bea Perolehan Hak atas • Tanah dan Bangunan (BPHTB) per 1 januari 2011 telah dialihkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "PBB-P2 menurut regulasi hubungan keuangan pusat dan daerah didefinisikan sebagai pajak atas...", "options": ["Aset mesin bergerak yang disewa oleh korporasi industri", "Bumi dan bangunan yang dimiliki, dikuasai, atau dimanfaatkan", "Hasil panen komoditas perkebunan yang dijual ke pasar induk", "Penyerahan jasa kebersihan lingkungan oleh dinas pertamanan", "Penjualan bahan material bangunan oleh toko bangunan lokal"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi PBB-P2</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>PBB-P2 adalah Pajak atas bumi dan/atau bangunan yang dimiliki, dikuasai, dan/atau dimanfaatkan oleh orang pribadi atau Badan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena dasar pengenaan PBB-P2 mencakup kepemilikan maupun hak penguasaan/pemanfaatan fisik atas tanah dan konstruksi bangunan perdesaan dan perkotaan. Hubungan kebendaan ini melahirkan kewajiban pajak.</p><p>Jawaban tepat: <strong>B. Bumi dan bangunan yang dimiliki, dikuasai, atau dimanfaatkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mesin bergerak adalah harta bergerak yang berada di luar objek PBB.</li><li><strong>C</strong>: Hasil panen perkebunan merupakan komoditas yang dikenakan PPh atau PPN.</li><li><strong>D</strong>: Jasa kebersihan pemda dipungut melalui retribusi persampahan daerah.</li><li><strong>E</strong>: Penjualan material toko bangunan dikenakan PPN atas peredaran barang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 49) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PBB-P2 adalah Pajak atas bumi dan/atau bangunan yang dimiliki, • dikuasai, dan/atau dimanfaatkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Ketentuan PBB-P2 dan pajak daerah saat ini diatur dalam perundang-undangan pokok...", "options": ["UU Nomor 1 Tahun 2004 tentang Tata Aturan Perbendaharaan", "UU Nomor 17 Tahun 2003 tentang Asas Pokok Keuangan Negara", "UU Nomor 1 Tahun 2022 tentang Hubungan Keuangan Pusat Daerah", "UU Nomor 15 Tahun 2004 tentang Badan Pemeriksa Keuangan", "UU Nomor 25 Tahun 2004 tentang Rencana Pembangunan Daerah"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi Hubungan Keuangan Pusat Daerah (HKPD)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PBB-P2 diatur dalam <strong>Undang-Undang Nomor 1 Tahun 2022 tentang Hubungan Keuangan antara Pemerintah Pusat dan Pemerintah Daerah (UU HKPD)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena UU HKPD menyederhanakan jenis pajak daerah dan retribusi daerah guna meningkatkan efisiensi pemungutan serta keselarasan fiskal antara pemerintah pusat dan daerah otonom.</p><p>Jawaban tepat: <strong>C. UU Nomor 1 Tahun 2022 tentang Hubungan Keuangan Pusat Daerah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: UU 1/2004 mengatur administrasi perbendaharaan negara.</li><li><strong>B</strong>: UU 17/2003 mengatur kerangka umum keuangan negara.</li><li><strong>D</strong>: UU 15/2004 mengatur audit dan pemeriksaan keuangan negara.</li><li><strong>E</strong>: UU 25/2004 mengatur sistem perencanaan pembangunan nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 49) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Undang-Undang Nomor 1 Tahun 2022 tentang Hubungan • Keuangan antara Pemerintah Pusat dan Pemerintah Daerah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penerimaan perpajakan dalam Pos Pajak Lainnya berupa Bea Meterai diatur dalam...", "options": ["UU Nomor 8 Tahun 1983 tentang Pertambahan Nilai", "UU Nomor 13 Tahun 1985 tentang Bea Meterai Kuno", "UU Nomor 7 Tahun 1983 tentang Pajak Penghasilan", "UU Nomor 10 Tahun 2020 tentang Bea Meterai", "UU Nomor 12 Tahun 1985 tentang Pajak Bumi Bangunan"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum Bea Meterai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bea Meterai merupakan pajak atas dokumen yang diatur dalam <strong>UU Nomor 10 tahun 2020 tentang Bea Materai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena UU 10/2020 menggantikan undang-undang lama tahun 1985 untuk menyesuaikan pengenaan bea meterai di era digital, termasuk pengenalan meterai elektronik atas dokumen digital.</p><p>Jawaban tepat: <strong>D. UU Nomor 10 Tahun 2020 tentang Bea Meterai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: UU 8/1983 mengatur tentang Pajak Pertambahan Nilai (PPN).</li><li><strong>B</strong>: UU 13/1985 telah dicabut dan digantikan penuh oleh UU 10/2020.</li><li><strong>C</strong>: UU 7/1983 mengatur tentang Pajak Penghasilan (PPh).</li><li><strong>E</strong>: UU 12/1985 mengatur tentang Pajak Bumi dan Bangunan (PBB).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 49) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>UU Nomor 10 tahun 2020 tentang Bea Materai</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dokumen perdata yang terutang bea meterai sebagai alat penerang kejadian mencakup...", "options": ["Ijazah kelulusan pendidikan dasar dan menengah kejuruan", "Surat tilang pelanggaran rambu lalu lintas dari kepolisian", "Kartu tanda penduduk elektronik yang diterbitkan dinas dukcapil", "Buku paspor perjalanan dinas luar negeri aparatur negara", "Surat perjanjian, keterangan, pernyataan sejenis, dan rangkapnya"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Objek Perdata Bea Meterai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bea Meterai dikenakan atas <strong>surat perjanjian, surat keterangan, surat pernyataan, atau surat lainnya yang sejenis, beserta rangkapnya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena surat perjanjian dan pernyataan yang dibuat untuk membuktikan hubungan hukum keperdataan antarpemberi tanda tangan merupakan objek utama pengenaan bea meterai.</p><p>Jawaban tepat: <strong>E. Surat perjanjian, keterangan, pernyataan sejenis, dan rangkapnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ijazah sekolah secara tegas dikecualikan dari pengenaan bea meterai.</li><li><strong>B</strong>: Surat tilang adalah dokumen penindakan pidana pelanggaran lalu lintas.</li><li><strong>C</strong>: KTP adalah dokumen identitas kependudukan, bukan objek meterai.</li><li><strong>D</strong>: Paspor adalah dokumen perjalanan internasional resmi antarnegara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 49) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>surat perjanjian, surat keterangan, surat pernyataan, atau surat • lainnya yang sejenis, beserta rangkapnya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dokumen autentik yang wajib dilunasi bea meterai pada saat pembuatannya adalah...", "options": ["Akta notaris beserta grosse, salinan, dan kutipannya", "Buku catatan harian rapat panitia pengadaan barang", "Nota dinas edaran internal kepala seksi satuan kerja", "Surat undangan menghadiri kegiatan pembukaan seminar", "Daftar hadir absensi kehadiran kerja aparatur kantor"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akta Notaris Objek Meterai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek Bea Meterai meliputi <strong>akta notaris beserta grosse, salinan, dan kutipannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena akta notaris merupakan akta autentik berkekuatan pembuktian sempurna di mata hukum perdata. Oleh karena itu, setiap akta notaris terutang bea meterai.</p><p>Jawaban tepat: <strong>A. Akta notaris beserta grosse, salinan, dan kutipannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Buku catatan harian rapat adalah dokumen kerja internal kepanitiaan.</li><li><strong>C</strong>: Nota dinas adalah korespondensi administrasi internal instansi.</li><li><strong>D</strong>: Surat undangan adalah warkat pemberitahuan biasa tanpa nilai perdata.</li><li><strong>E</strong>: Daftar hadir absensi adalah dokumen rekapitulasi kehadiran dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 49) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>akta notaris beserta grosse, salinan, dan kutipannya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Akta perdata mengenai peralihan hak atas tanah yang terutang bea meterai adalah...", "options": ["Surat pemberitahuan pajak terutang PBB sektor perkotaan", "Akta Pejabat Pembuat Akta Tanah beserta salinan dan kutipannya", "Buku kas umum pembantu bendahara penerimaan daerah", "Surat izin mendirikan bangunan gedung dari pemda", "Sertifikat laik fungsi fasilitas instalasi kelistrikan"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akta PPAT Objek Meterai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek Bea Meterai meliputi <strong>akta Pejabat Pembuat Akta Tanah beserta salinan dan kutipannya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena akta jual beli, hibah, atau hak tanggungan yang dibuat oleh PPAT adalah bukti hukum peralihan hak kebendaan tanah yang wajib dibubuhi bea meterai.</p><p>Jawaban tepat: <strong>B. Akta Pejabat Pembuat Akta Tanah beserta salinan dan kutipannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: SPPT PBB adalah surat ketetapan pajak, bukan akta perdata PPAT.</li><li><strong>C</strong>: Buku kas pembantu adalah sarana pembukuan bendahara perbendaharaan.</li><li><strong>D</strong>: Izin mendirikan bangunan adalah produk izin administrasi tata kota.</li><li><strong>E</strong>: Sertifikat laik fungsi adalah penetapan kelayakan teknis bangunan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 49) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>akta Pejabat Pembuat Akta Tanah beserta salinan dan kutipannya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Instrumen komersial pasar uang dan modal yang terutang bea meterai mencakup...", "options": ["Struk bukti belanja barang kebutuhan dapur di swalayan", "Karcis pembayaran tiket bus angkutan umum dalam kota", "Surat berharga dengan nama dan dalam bentuk apa pun", "Nomor antrean pelayanan loket pembayaran listrik kantor", "Kupon potongan harga promosi produk makanan cepat saji"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Surat Berharga Objek Meterai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek Bea Meterai meliputi <strong>surat berharga dengan nama dan dalam bentuk apapun</strong> serta dokumen transaksi efek.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena wesel, promes, cek, dan bilyet giro merupakan surat berharga bernilai finansial yang berfungsi sebagai alat bayar atau investasi. Dokumen tersebut dikenai bea meterai.</p><p>Jawaban tepat: <strong>C. Surat berharga dengan nama dan dalam bentuk apa pun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Struk belanja ritel kebutuhan pokok bukan surat berharga investasi.</li><li><strong>B</strong>: Tiket angkutan penumpang umum dikecualikan dari bea meterai.</li><li><strong>D</strong>: Nomor antrean loket adalah sarana penertiban antrean pelayanan.</li><li><strong>E</strong>: Kupon promosi diskon adalah materi iklan pemasaran komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 49) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>surat berharga dengan nama dan dalam bentuk apapun</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dokumen yang sebelumnya belum dilunasi meterai tetapi diajukan ke sidang pengadilan...", "options": ["Disita secara otomatis oleh panitera pengadilan negeri", "Dinyatakan batal demi hukum dan tidak dapat disidangkan", "Dikenakan sanksi denda pidana kurungan kepada pemegang berkas", "Wajib dilakukan pemeteraian kemudian sebagai alat bukti pengadilan", "Dialihkan menjadi dokumen rahasia negara di kementerian"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dokumen Alat Bukti Pengadilan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek Bea Meterai mencakup <strong>Dokumen yang digunakan sebagai alat bukti di pengadilan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena dokumen apa pun yang hendak dijadikan alat bukti di muka persidangan perdata wajib memenuhi bea meterai melalui mekanisme pemeteraian kemudian di kantor pos.</p><p>Jawaban tepat: <strong>D. Wajib dilakukan pemeteraian kemudian sebagai alat bukti pengadilan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Panitera tidak menyita dokumen melainkan memeriksa bukti meterai.</li><li><strong>B</strong>: Dokumen tidak batal demi hukum, cukup dilunasi bea meterainya.</li><li><strong>C</strong>: Ketiadaan meterai bukan tindak pidana yang diancam kurungan.</li><li><strong>E</strong>: Dokumen perdata sidang bukan arsip rahasia kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 50) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dokumen yang digunakan sebagai alat bukti di pengadilan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dokumen kuitansi atau pengakuan utang terutang bea meterai apabila bernilai nominal...", "options": ["Maksimal sebesar sepuluh juta rupiah dalam satu periode buku", "Di atas satu juta rupiah untuk transaksi antar perorangan", "Kurang dari dua juta rupiah pada pembelian barang tunai", "Tepat sebesar lima ratus ribu rupiah pada transaksi dinas", "Lebih dari lima juta rupiah yang menyebutkan penerimaan uang"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Batas Nominal Dokumen Uang Terutang Meterai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dokumen yang menyatakan jumlah uang dengan <strong>nilai nominal lebih dari Rp5.000.000,00 (lima juta rupiah)</strong> yang menyebutkan penerimaan uang atau pelunasan utang terutang Bea Meterai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena UU 10/2020 menaikkan ambang batas dokumen keuangan yang terutang meterai menjadi di atas Rp5.000.000 guna melindungi transaksi UMKM dan masyarakat kecil dari beban meterai.</p><p>Jawaban tepat: <strong>E. Lebih dari lima juta rupiah yang menyebutkan penerimaan uang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Batas bukan plafon maksimal melainkan ambang batas minimal pemungutan.</li><li><strong>B</strong>: Batas satu juta rupiah merupakan batas lama era undang-undang terdahulu.</li><li><strong>C</strong>: Nominal di bawah Rp5 juta bebas dari pengenaan bea meterai.</li><li><strong>D</strong>: Nominal Rp500 ribu tidak terutang bea meterai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 50) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>nilai nominal • lebih dari Rp5.000.000,00 (lima juta rupiah)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Besaran tarif bea meterai yang berlaku tunggal saat ini adalah...", "options": ["Bersifat tetap sebesar sepuluh ribu rupiah per dokumen", "Bersifat bertingkat antara tiga ribu dan enam ribu rupiah", "Dihitung satu persen dari total nilai nominal perjanjian", "Disesuaikan dengan tingkat laba kotor tahunan wajib pajak", "Ditetapkan secara bebas oleh pejabat kantor pos setempat"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tarif Tunggal Bea Meterai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Besaran tarif bea materai bersifat tetap sebesar Rp10.000</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena UU 10/2020 menerapkan sistem tarif tunggal (*single tariff*) Rp10.000 menggantikan rezim tarif ganda Rp3.000 dan Rp6.000 yang berlaku pada aturan sebelumnya. Hal ini memberikan kepastian hukum bagi masyarakat.</p><p>Jawaban tepat: <strong>A. Bersifat tetap sebesar sepuluh ribu rupiah per dokumen</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Tarif Rp3.000 dan Rp6.000 sudah dihapus dan tidak berlaku lagi.</li><li><strong>C</strong>: Bea meterai adalah tarif tetap (*fixed duty*), bukan persentase nilai.</li><li><strong>D</strong>: Bea meterai tidak berkaitan dengan tingkat laba kotor usaha.</li><li><strong>E</strong>: Kantor pos bertindak sebagai penyalur, bukan penentu tarif meterai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 50) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Besaran tarif bea materai bersifat tetap sebesar Rp10.000</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pelunasan bea meterai atas dokumen dapat dilakukan dengan menggunakan...", "options": ["Kuitansi bukti setor pajak bumi dan bangunan daerah", "Meterai tempel, meterai elektronik, atau bentuk lain ketetapan Menkeu", "Surat pernyataan lunas dari kepala kantor perbendaharaan", "Stempel basah tanda tangan notaris tanpa perekat fisik", "Potongan kupon obligasi negara yang telah jatuh tempo"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ragam Bentuk Meterai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Meterai dapat berupa <strong>meterai tempel, meterai elaktornik, atau Meterai dalam bentuk lain yang ditetapkan oleh Menteri Keuangan</strong> atau Surat Setoran Pajak.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena undang-undang memfasilitasi meterai tempel untuk dokumen fisik kertas dan meterai elektronik (*e-meterai*) untuk dokumen digital elektronik. Fleksibilitas ini mempermudah transaksi bisnis modern.</p><p>Jawaban tepat: <strong>B. Meterai tempel, meterai elektronik, atau bentuk lain ketetapan Menkeu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kuitansi PBB adalah bukti bayar pajak tanah, bukan meterai dokumen.</li><li><strong>C</strong>: Pernyataan KPPN adalah bukti pembayaran SP2D APBN.</li><li><strong>D</strong>: Stempel notaris bukan tanda pelunasan bea meterai negara.</li><li><strong>E</strong>: Kupon obligasi adalah instrumen pencairan bunga surat utang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 50) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>meterai tempel, meterai elaktornik, atau Meterai dalam • bentuk lain</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Objek pengenaan Bea Masuk menurut hukum kepabeanan adalah...", "options": ["Komoditas hasil pertanian lokal yang dijual antarpulau", "Kapal pesiar asing yang melintas di perairan internasional", "Barang impor yang dimasukkan ke dalam daerah pabean", "Peralatan dinas kantor kementerian yang dibeli dari APBN", "Barang produksi dalam negeri yang dipamerkan di pameran"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Objek Bea Masuk</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Objek Bea Masuk adalah barang yang dimasukkan kedalam Daerah Pabean (barang yang diimpor)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena yurisdiksi pabean mengenakan bea masuk pada saat barang luar negeri melintasi garis pabean untuk masuk dan beredar di wilayah Republik Indonesia. Importasi inilah yang melahirkan utang bea masuk.</p><p>Jawaban tepat: <strong>C. Barang impor yang dimasukkan ke dalam daerah pabean</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perdagangan antarpulau adalah perdagangan domestik bebas bea pabean.</li><li><strong>B</strong>: Kapal yang melintas damai di laut lepas tidak terutang bea masuk.</li><li><strong>D</strong>: Belanja APBN barang lokal tidak melintasi batas daerah pabean.</li><li><strong>E</strong>: Barang pameran lokal bukan barang impor dari luar negeri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 50) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Objek Bea Masuk adalah barang yang dimasukkan • kedalam Daerah Pabean</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Ketentuan mengenai kepabeanan dan bea masuk diatur dalam perundang-undangan...", "options": ["UU Nomor 15 Tahun 2004 tentang Tata Cara Audit BPK", "UU Nomor 10 Tahun 1995 tentang Lembaga Perbankan", "UU Nomor 1 Tahun 2004 tentang Kas Daerah Otonom", "UU Nomor 17 Tahun 2006 tentang Kepabeanan", "UU Nomor 25 Tahun 2004 tentang Kerangka Rencana Kerja"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum Kepabeanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kepabeanan diatur dalam <strong>Undang-undang No. 17 tahun 2006 tentang Kepabeanan</strong> (perubahan atas UU No. 10 Tahun 1995).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena UU 17/2006 merupakan landasan hukum formil dan materiil bagi Direktorat Jenderal Bea dan Cukai dalam mengawasi lalu lintas barang ekspor dan impor di seluruh wilayah pabean Indonesia.</p><p>Jawaban tepat: <strong>D. UU Nomor 17 Tahun 2006 tentang Kepabeanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: UU 15/2004 adalah undang-undang pemeriksaan tanggung jawab keuangan.</li><li><strong>B</strong>: UU perbankan bukan undang-undang kepabeanan bea masuk.</li><li><strong>C</strong>: UU 1/2004 adalah undang-undang perbendaharaan negara.</li><li><strong>E</strong>: UU 25/2004 adalah undang-undang sistem perencanaan pembangunan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 50) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Undang-undang • No. 17 tahun 2006 tentang Kepabeanan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Daerah Pabean menurut undang-undang kepabeanan didefinisikan sebagai...", "options": ["Zona netral perbatasan antarnegara yang bebas dari hukum negara", "Area terbatas di dalam kompleks gedung kantor pelayanan pajak", "Ruang tunggu keberangkatan penumpang pesawat udara domestik", "Kawasan perairan laut lepas di luar batas zona ekonomi eksklusif", "Wilayah Republik Indonesia yang berlaku undang-undang kepabeanan"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Daerah Pabean</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Daerah Pabean adalah wilayah Republik Indonesia yang didalamnya berlaku Undang-undang Kepabeanan</strong>, meliputi wilayah darat, perairan, dan ruang udara di atasnya, serta tempat-tempat tertentu di ZEE dan landas kontinen.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kedaulatan hukum kepabeanan mencakup seluruh batas kedaulatan negara Indonesia. Setiap barang luar yang menembus garis batas daerah pabean ini tunduk pada pengawasan bea cukai.</p><p>Jawaban tepat: <strong>E. Wilayah Republik Indonesia yang berlaku undang-undang kepabeanan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tidak ada zona netral tanpa hukum di wilayah perbatasan resmi.</li><li><strong>B</strong>: Gedung kantor pajak bukan batas daerah pabean nasional.</li><li><strong>C</strong>: Ruang tunggu bandara domestik adalah terminal penerbangan dalam negeri.</li><li><strong>D</strong>: Laut lepas berada di luar batas kedaulatan pabean Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 50) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Daerah Pabean adalah wilayah Republik Indonesia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pihak yang bertanggung jawab atas pembayaran Bea Masuk ke kas negara adalah...", "options": ["Importir yang memasukkan barang ke dalam daerah pabean", "Nakhoda kapal asing yang sekadar transit mengisi bahan bakar", "Petugas bea cukai yang melakukan pemeriksaan fisik barang", "Konsumen akhir yang membeli barang impor di pasar eceran", "Otoritas pelabuhan laut yang menyediakan dermaga sandar"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Subjek Pembayar Bea Masuk</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Subjek Bea Masuk adalah pihak-pihak yang pada prinsipnya harus bertanggung jawab atas pembayaran Bea Masuk dimaksud yaitu pihak-pihak yang memasukkan barang ke dalam Daerah Pabean (importir)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena importir adalah entitas yang melakukan tindakan hukum mendatangkan barang dari luar negeri. Kewajiban pemberitahuan pabean (PIB) dan pelunasan bea masuk dibebankan secara hukum kepada importir.</p><p>Jawaban tepat: <strong>A. Importir yang memasukkan barang ke dalam daerah pabean</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Nakhoda kapal transit tidak mengimpor barang ke peredaran bebas.</li><li><strong>C</strong>: Petugas bea cukai adalah aparatur negara pemeriksa dokumen pabean.</li><li><strong>D</strong>: Konsumen eceran bukan subjek hukum langsung pembayar bea masuk pabean.</li><li><strong>E</strong>: Otoritas pelabuhan adalah penyedia jasa kepelabuhanan maritim.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 50) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Subjek Bea • Masuk adalah pihak-pihak yang pada prinsipnya harus bertanggung jawab • atas • pembayaran</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Tujuan utama pengenaan tarif bea masuk terhadap barang impor adalah...", "options": ["Menghambat hubungan diplomatik kerja sama antarnegara sahabat", "Melindungi industri dalam negeri dari serbuan produk luar negeri", "Memaksa produsen luar negeri menjual pabriknya ke pihak lokal", "Menghapuskan peredaran barang kebutuhan pokok di pasar domestik", "Mengurangi cadangan devisa negara pada bank sentral Indonesia"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Proteksi Bea Masuk</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada dasarnya <strong>tujuan bea masuk adalah melindungi industri dalam negeri dari masuknya barang-barang yang berasal dari luar negeri</strong> (fungsi proteksi/barrier).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena dengan membebankan bea masuk, harga produk impor menjadi setara atau lebih mahal sehingga produk industri lokal memiliki daya saing yang adil di pasar domestik.</p><p>Jawaban tepat: <strong>B. Melindungi industri dalam negeri dari serbuan produk luar negeri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bea masuk adalah instrumen dagang wajar, bukan pemutus diplomasi.</li><li><strong>C</strong>: Bea masuk tidak bertujuan mengambil alih kepemilikan pabrik asing.</li><li><strong>D</strong>: Tujuan bea masuk menjaga industri, bukan melenyapkan kebutuhan pokok.</li><li><strong>E</strong>: Pengendalian impor justru membantu menghemat cadangan devisa negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 51) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tujuan bea masuk adalah melindungi industri dalam negeri</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kawasan perdagangan bebas yang menghapuskan tarif bea masuk impor bagi Indonesia adalah...", "options": ["Perjanjian bantuan pinjaman darurat dana moneter global", "Pakta pertahanan militer bersama negara-negara Atlantik Utara", "Kawasan perdagangan bebas ASEAN Free Trade Area (AFTA)", "Kerja sama riset penjelajahan ruang angkasa regional Asia", "Asosiasi federasi olahraga sepak bola antarnegara Asia"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dampak Kawasan Bebas Perdagangan (AFTA)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kawasan bebas perdagangan yaitu <strong>ASEAN Free Trade Area/AFTA</strong> menghapuskan semua bea masuk impor barang bagi Indonesia pada tahun 2015.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena komitmen integrasi ekonomi ASEAN melalui skema CEPT-AFTA memangkas tarif bea masuk antarsesama negara anggota hingga nol persen. Hal ini menuntut peningkatan daya saing produsen dalam negeri.</p><p>Jawaban tepat: <strong>C. Kawasan perdagangan bebas ASEAN Free Trade Area (AFTA)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pinjaman darurat moneter adalah bantuan stabilitas neraca IMF.</li><li><strong>B</strong>: Pakta Atlantik Utara (NATO) adalah aliansi pertahanan militer.</li><li><strong>D</strong>: Riset ruang angkasa bukan perjanjian perdagangan tarif nol komoditas.</li><li><strong>E</strong>: Federasi sepak bola adalah organisasi olahraga regional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 51) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ASEAN Free Trade Area/AFTA</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penghapusan tarif bea masuk impor bagi Indonesia sejak tahun 2015 menjadi tantangan untuk...", "options": ["Menghapuskan pemungutan pajak pertambahan nilai barang mewah", "Menutup seluruh akses pelabuhan pabean internasional di daerah", "Menaikkan batas tarif bea keluar hasil tambang mentah ekspor", "Meningkatkan daya saing dan efisiensi produk industri nasional", "Menghentikan seluruh kegiatan produksi pabrik manufaktur lokal"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tantangan Penghapusan Bea Masuk AFTA 2015</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan dihapuskannya bea masuk impor AFTA pada tahun 2015, manfaat pelindung industri hilang sehingga menjadi <strong>tantangan bagi pengusaha/produsen Indonesia untuk dapat meningkatkan daya saing</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena hilangnya dinding proteksi tarif menuntut produsen dalam negeri memproduksi barang berkualitas tinggi dengan harga kompetitif agar tidak kalah bersaing dengan barang impor dari kawasan ASEAN.</p><p>Jawaban tepat: <strong>D. Meningkatkan daya saing dan efisiensi produk industri nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PPnBM tetap dipungut sebagai instrumen konsumsi barang mewah.</li><li><strong>B</strong>: Menutup pelabuhan bertentangan dengan komitmen perdagangan internasional.</li><li><strong>C</strong>: Bea keluar tambang adalah kebijakan terpisah terkait bahan baku mentah.</li><li><strong>E</strong>: Industri lokal justru harus dipacu produktivitasnya, bukan dihentikan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 51) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menghapuskan semua bea • masuk impor barang bagi Indonesia pada tahun 2015</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penerimaan Pajak Ekspor dikenakan pemerintah atas pengiriman komoditas tertentu seperti...", "options": ["Buku literatur pendidikan dan publikasi ilmiah dosen kampus", "Barang kerajinan tangan jadi dan suvenir budaya tradisional", "Produk pakaian jadi siap pakai hasil konveksi garmen lokal", "Komponen elektronika rakitan siap pasang industri manufaktur", "Kulit mentah, rotan mentah, kayu gelondongan, karet, dan sawit"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pajak Ekspor Komoditas Tertentu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pajak Ekspor pada APBN adalah hasil pungutan pajak yang <strong>dikenakan atas ekspor beberapa komoditi tertentu misalnya kulit, rotan mentah, kayu gelondongan, karet, kopra, kopi dan sebagainya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena pajak ekspor (bea keluar) secara selektif dikenakan pada bahan mentah atau bahan baku alami untuk mencegah eksploitasi berlebihan dan memastikan ketersediaan bahan baku bagi industri pengolahan dalam negeri.</p><p>Jawaban tepat: <strong>E. Kulit mentah, rotan mentah, kayu gelondongan, karet, dan sawit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Buku literatur pendidikan bukan komoditas sasaran pungutan ekspor.</li><li><strong>B</strong>: Kerajinan tangan jadi didorong ekspornya dan dibebaskan dari pajak ekspor.</li><li><strong>C</strong>: Pakaian jadi adalah produk bernilai tambah yang ekspornya didukung penuh.</li><li><strong>D</strong>: Elektronika adalah produk manufaktur ekspor yang bebas bea keluar.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 51) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dikenakan atas • ekspor beberapa komoditi tertentu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kebijaksanaan di bidang ekspor secara terpadu ditujukan pemerintah untuk...", "options": ["Mendorong ekspor barang jadi, lapangan kerja, dan industri hilir", "Memperbanyak pengiriman bahan mentah tanpa proses pengolahan", "Menghapuskan pungutan devisa atas komoditas tambang strategis", "Membatasi investasi modal asing di sektor manufaktur pengolahan", "Menurunkan harga jual komoditas mentah Indonesia di pasar global"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Kebijakan Hilirisasi Ekspor</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijaksanaan ekspor ditujukan untuk <strong>mendorong ekspor barang jadi, menciptakan lapangan kerja, mendorong industri hilir, dan meningkatkan pendapatan devisa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena industrialisasi dan hilirisasi mengubah struktur ekspor dari sekadar pengekspor bahan mentah bernilai rendah menjadi pengekspor barang jadi bernilai tambah tinggi yang menyerap tenaga kerja dalam jumlah besar.</p><p>Jawaban tepat: <strong>A. Mendorong ekspor barang jadi, lapangan kerja, dan industri hilir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Ekspor bahan mentah mentah justru dibatasi untuk hilirisasi.</li><li><strong>C</strong>: Devisa ekspor wajib masuk ke sistem keuangan dalam negeri.</li><li><strong>D</strong>: Investasi hilirisasi justru sangat dibuka dan difasilitasi pemerintah.</li><li><strong>E</strong>: Menurunkan harga bahan mentah merugikan neraca perdagangan nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 51) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mendorong ekspor barang jadi, • menciptakan lapangan kerja, mendorong industri hilir</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pembatasan ekspor jenis komoditi tertentu oleh pemerintah dilaksanakan dalam rangka...", "options": ["Menghentikan seluruh aktivitas perniagaan ekspor pelabuhan laut", "Menjamin pasokan bahan baku domestik dan menciptakan lapangan kerja", "Menurunkan penerimaan devisa negara dari sektor perdagangan luar", "Mendorong impor barang konsumsi sejenis dari kawasan regional", "Menyerahkan pengelolaan komoditas kepada kartel bisnis swasta"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembatasan Ekspor Bahan Baku</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan ekspor ditujukan untuk <strong>membatasi ekspor jenis komoditi tertentu dalam rangka usaha menciptakan lapangan kerja misalnya dengan dibatasinya ekspor kulit mentah, rotan mentah dan kayu gelondongan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena jika seluruh rotan dan kayu mentah diekspor ke luar negeri, pabrik mebel dan pengrajin dalam negeri akan kekurangan bahan baku dan mengalami penutupan usaha. Pembatasan ekspor menjaga kelangsungan industri lokal.</p><p>Jawaban tepat: <strong>B. Menjamin pasokan bahan baku domestik dan menciptakan lapangan kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kebijakan tidak menghentikan perniagaan ekspor melainkan menyeleksinya.</li><li><strong>C</strong>: Tujuan hilirisasi justru melipatgandakan perolehan devisa barang jadi.</li><li><strong>D</strong>: Tujuan pembatasan bukan mendorong impor melainkan memacu olahan lokal.</li><li><strong>E</strong>: Pengelolaan komoditas diatur oleh negara demi kemakmuran rakyat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 51) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>membatasi ekspor • jenis komoditi tertentu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Instrumen fiskal yang digunakan untuk membatasi ekspor komoditas mentah adalah...", "options": ["Pembebasan seluruh pungutan kepabeanan di pelabuhan ekspor", "Pemberian subsidi tunai langsung bagi eksportir bahan mentah", "Pengenaan tarif pajak ekspor yang tinggi atas bahan mentah", "Pemberian kemudahan kredit perbankan tanpa bunga bagi eksportir", "Penurunan tarif bea masuk atas produk olahan impor sejenis"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tarif Pajak Ekspor Tinggi sebagai Disinsentif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Untuk membatasi ekspor komoditi mentah tersebut <strong>dikenai pajak ekspor yang tinggi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pengenaan tarif bea keluar yang tinggi bertindak sebagai disinsentif ekonomi yang membuat ekspor bahan mentah menjadi tidak menguntungkan secara finansial, sehingga pengusaha terdorong mengolahnya di dalam negeri.</p><p>Jawaban tepat: <strong>C. Pengenaan tarif pajak ekspor yang tinggi atas bahan mentah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembebasan pungutan justru akan memicu banjir ekspor bahan mentah.</li><li><strong>B</strong>: Memberi subsidi kepada eksportir mentah bertentangan dengan hilirisasi.</li><li><strong>D</strong>: Kredit lunak tanpa bunga bagi eksportir mentah menyalahi arah kebijakan.</li><li><strong>E</strong>: Penurunan bea masuk impor olahan merugikan pasar industri dalam negeri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 51) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dikenai pajak • ekspor yang tinggi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Ketentuan pokok Penerimaan Negara Bukan Pajak (PNBP) saat ini diatur dalam...", "options": ["UU Nomor 1 Tahun 2004 tentang Perbendaharaan Negara", "UU Nomor 20 Tahun 1997 tentang PNBP Era Lama", "UU Nomor 17 Tahun 2003 tentang Keuangan Negara", "UU Nomor 9 Tahun 2018 tentang PNBP", "UU Nomor 15 Tahun 2004 tentang Pemeriksaan BPK"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Regulasi PNBP</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketentuan tentang PNBP diatur dalam <strong>UU Nomor 9 tahun 2018</strong> tentang Penerimaan Negara Bukan Pajak.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena UU 9/2018 menggantikan regulasi tahun 1997 guna mewujudkan tata kelola PNBP yang lebih transparan, akuntabel, dan berkeadilan serta mengoptimalkan penerimaan negara di luar perpajakan.</p><p>Jawaban tepat: <strong>D. UU Nomor 9 Tahun 2018 tentang PNBP</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: UU 1/2004 mengatur administrasi perbendaharaan negara.</li><li><strong>B</strong>: UU 20/1997 telah dicabut dan digantikan penuh oleh UU 9/2018.</li><li><strong>C</strong>: UU 17/2003 mengatur asas umum kerangka keuangan negara.</li><li><strong>E</strong>: UU 15/2004 mengatur sistem pemeriksaan keuangan negara oleh BPK.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>UU Nomor 9 tahun 2018</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penerimaan Negara Bukan Pajak (PNBP) didefinisikan sebagai pungutan yang dibayar oleh...", "options": ["Masyarakat umum berupa sumbangan sukarela tanpa dasar undang-undang", "Seluruh aparatur sipil negara dari pemotongan gaji rutin bulanan", "Konsumen akhir atas konsumsi barang kena cukai tembakau", "Importir barang modal atas bea masuk yang dilunasi di pabean", "Orang pribadi atau badan dengan imbalan manfaat atas layanan atau SDA"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi PNBP</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNBP adalah <strong>pungutan yang dibayar oleh orang pribadi atau badan dengan memperoleh manfaat langsung maupun tidak langsung atas layanan atau pemanfaatan sumber daya dan hak yang diperoleh negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena ciri pembeda utama PNBP dari pajak adalah adanya kontraprestasi atau manfaat langsung/tidak langsung yang dinikmati pembayar pungutan, seperti jasa pelayanan paspor, pendidikan, atau sewa aset negara.</p><p>Jawaban tepat: <strong>E. Orang pribadi atau badan dengan imbalan manfaat atas layanan atau SDA</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PNBP wajib didasarkan pada peraturan perundang-undangan, bukan sukarela.</li><li><strong>B</strong>: Potongan gaji ASN adalah iuran jaminan pensiun dan potongan PPh 21.</li><li><strong>C</strong>: Pungutan atas rokok merupakan penerimaan cukai, bukan PNBP.</li><li><strong>D</strong>: Pungutan importir di pelabuhan pabean merupakan pos Bea Masuk.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pungutan yang dibayar oleh • orang pribadi atau badan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Perolehan manfaat langsung maupun tidak langsung dalam PNBP timbul dari...", "options": ["Layanan pemerintah atau pemanfaatan sumber daya dan hak negara", "Pemberian pinjaman komersial tanpa jaminan dari bank umum", "Kenaikan nilai saham perusahaan swasta di bursa efek domestik", "Bantuan keuangan kemanusiaan dari lembaga filantropi asing", "Penyelesaian sengketa tata usaha negara oleh majelis hakim"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Manfaat Langsung/Tidak Langsung PNBP</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Manfaat PNBP timbul <strong>atas layanan atau pemanfaatan sumber daya dan hak yang diperoleh negara, berdasarkan perundang-undangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena negara bertindak sebagai penyedia jasa layanan publik administratif/barang dan pemilik kedaulatan atas kekayaan sumber daya alam. Manfaat atas fasilitas inilah yang menjadi dasar pungutan PNBP.</p><p>Jawaban tepat: <strong>A. Layanan pemerintah atau pemanfaatan sumber daya dan hak negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pinjaman bank komersial adalah transaksi pembiayaan perbankan privat.</li><li><strong>C</strong>: Kenaikan saham adalah keuntungan modal swasta di pasar modal.</li><li><strong>D</strong>: Bantuan filantropi asing masuk dalam pos penerimaan hibah.</li><li><strong>E</strong>: Putusan sengketa TUN adalah fungsi penegakan keadilan peradilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memperoleh manfaat langsung maupun tidak • langsung atas layanan atau pemanfaatan sumber daya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Salah satu kriteria utama suatu aktivitas menjadi objek PNBP adalah adanya...", "options": ["Tuntutan pembagian dividen laba dari korporasi multinasional", "Pelaksanaan tugas dan fungsi pemerintah dalam pelayanan publik", "Kewajiban pembayaran ganti rugi sengketa perdata masyarakat", "Penyitaan rekening simpanan nasabah bermasalah oleh perbankan", "Perubahan batas wilayah administratif kelurahan di daerah"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kriteria Objek PNBP Tugas Fungsi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kriteria objek PNBP mencakup <strong>Pelaksanaan tugas dan fungsi pemerintah</strong>, penggunaan dana APBN, pengelolaan kekayaan negara, dan/atau penetapan peraturan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena apabila suatu kementerian/lembaga menjalankan tugas pelayanan publik (seperti penerbitan SIM, paspor, uji laboratorium) dan memungut biaya dari masyarakat, pungutan tersebut wajib disahkan sebagai PNBP.</p><p>Jawaban tepat: <strong>B. Pelaksanaan tugas dan fungsi pemerintah dalam pelayanan publik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dividen korporasi multinasional bukan tupoksi pelayanan pemerintah.</li><li><strong>C</strong>: Ganti rugi sengketa perdata adalah kompensasi antarpribadi hukum.</li><li><strong>D</strong>: Penyitaan rekening bank diatur dalam hukum perbankan dan eksekusi pengadilan.</li><li><strong>E</strong>: Perubahan batas kelurahan adalah kebijakan penataan wilayah administratif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pelaksanaan tugas dan fungsi pemerintah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Kegiatan yang menghasilkan penerimaan dikategorikan objek PNBP apabila memanfaatkan...", "options": ["Hasil iuran sukarela dari pegawai pada perkumpulan kantor", "Pinjaman modal kerja jangka pendek dari rentenir perorangan", "Penggunaan dana yang bersumber dari APBN dalam pelaksanaannya", "Dana sumbangan perorangan yang tidak tercatat dalam buku dinas", "Penerimaan kredit komersial berbunga pasar dari bank swasta"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kriteria Objek PNBP Berbasis Dana APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kriteria objek PNBP meliputi <strong>Penggunaan dana yang bersumber dari anggaran pendapatan dan belanja negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena setiap fasilitas atau layanan yang dibiayai menggunakan uang APBN tidak boleh menghasilkan penerimaan yang dinikmati secara pribadi oleh pejabat satker. Penerimaan tersebut wajib disetor penuh ke kas negara sebagai PNBP.</p><p>Jawaban tepat: <strong>C. Penggunaan dana yang bersumber dari APBN dalam pelaksanaannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Iuran sosial pegawai adalah dana paguyuban non-kedinasan.</li><li><strong>B</strong>: Penggunaan pinjaman rentenir melanggar aturan tata kelola instansi.</li><li><strong>D</strong>: Sumbangan tanpa pencatatan adalah gratifikasi liar yang melanggar hukum.</li><li><strong>E</strong>: Instansi pemerintah dilarang meminjam modal kredit dari bank swasta.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penggunaan dana yang bersumber dari anggaran pendapatan dan belanja • negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pemanfaatan bumi, air, udara, dan kekayaan alam yang terkandung di dalamnya masuk klaster...", "options": ["Pajak penjualan atas barang mewah hasil tambang", "Pajak Pertambahan Nilai penyerahan barang tambang", "Pungutan bea masuk kepabeanan atas komoditas impor", "Objek PNBP Pemanfaatan Sumber Daya Alam", "Penerimaan hibah luar negeri sektor kelautan maritim"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Klaster PNBP Pemanfaatan SDA</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Objek PNBP meliputi <strong>Pemanfaatan Sumber Daya Alam</strong>, yaitu pemanfaatan bumi, air, udara, ruang angkasa, dan kekayaan alam yang dikuasai negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena royalti minyak, gas bumi, mineral, batubara, kehutanan, dan perikanan merupakan hak negara atas kekayaan alamnya yang dikelola dalam kelompok PNBP SDA.</p><p>Jawaban tepat: <strong>D. Objek PNBP Pemanfaatan Sumber Daya Alam</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PPnBM adalah pajak konsumsi, bukan royalti eksploitasi alam.</li><li><strong>B</strong>: Penyerahan barang tambang mentah tunduk pada ketentuan PPN tersendiri.</li><li><strong>C</strong>: Bea masuk dikenakan atas impor barang, bukan eksploitasi alam domestik.</li><li><strong>E</strong>: Hibah maritim adalah bantuan sukarela, bukan imbal hasil pemanfaatan SDA.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pemanfaatan Sumber Daya Alam</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Klaster objek PNBP berupa penyediaan barang, jasa, atau pelayanan administratif disebut...", "options": ["Pajak penghasilan atas imbalan jasa profesi konsultan", "Penerimaan retribusi daerah pada dinas perhubungan kota", "Bea meterai atas dokumen perjanjian perikatan perdata", "Pungutan cukai atas konsumsi minuman mengandung alkohol", "Objek PNBP Pelayanan yang menjadi tanggung jawab pemerintah"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Klaster PNBP Pelayanan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pelayanan adalah jasa, segala bentuk penyediaan barang, atau pelayanan administratif yang menjadi tanggung jawab pemerintah</strong>, baik pemenuhan kebutuhan masyarakat maupun peraturan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena layanan jasa diklat kedinasan, penerbitan sertifikat keahlian, izin operasional, dan visa kunjungan merupakan bentuk pelayanan pemerintah pusat yang menghasilkan PNBP Pelayanan.</p><p>Jawaban tepat: <strong>E. Objek PNBP Pelayanan yang menjadi tanggung jawab pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak jasa konsultan dipotong melalui skema PPh Pasal 23 atau 21.</li><li><strong>B</strong>: Retribusi perhubungan daerah adalah penerimaan kas daerah pemda.</li><li><strong>C</strong>: Bea meterai adalah pajak atas dokumen perdata, bukan PNBP jasa layanan.</li><li><strong>D</strong>: Cukai alkohol adalah pungutan pembatasan konsumsi barang kena cukai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pelayanan adalah jasa, segala bentuk penyediaan barang, atau pelayanan • administratif</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Dividen dan bagian laba pemerintah yang berasal dari penyertaan modal pada BUMN masuk klaster...", "options": ["Pengelolaan Kekayaan Negara Dipisahkan objek PNBP", "Pajak penghasilan badan atas keuntungan korporasi BUMN", "Penerimaan pembiayaan utang pinjaman modal investasi", "Penerimaan hibah terikat dari direksi perusahaan negara", "Dana cadangan umum stabilisasi neraca moneter negara"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Klaster PNBP Kekayaan Negara Dipisahkan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pengelolaan Kekayaan Negara Dipisahkan adalah pengelolaan atas kekayaan negara yang berasal dari anggaran pendapatan dan belanja negara yang dijadikan penyertaan modal negara</strong> atau perolehan lain yang sah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pemerintah bertindak sebagai pemegang saham pada BUMN dan lembaga keuangan. Bagian laba/dividen yang disetorkan BUMN ke kas negara dibukukan dalam pos PNBP Kekayaan Negara Dipisahkan (KND).</p><p>Jawaban tepat: <strong>A. Pengelolaan Kekayaan Negara Dipisahkan objek PNBP</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: PPh badan disetor BUMN ke pos perpajakan, bukan pos PNBP dividen.</li><li><strong>C</strong>: Pembiayaan utang adalah pos penerbitan utang, bukan bagian laba ekuitas.</li><li><strong>D</strong>: Setoran dividen adalah kewajiban laba modal, bukan hibah sosial.</li><li><strong>E</strong>: Cadangan moneter dikelola di bank sentral untuk stabilitas rupiah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 52) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengelolaan Kekayaan Negara Dipisahkan adalah pengelolaan atas kekayaan • negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penerimaan dari sewa gedung atau pemindahtanganan aset instansi pemerintah masuk klaster...", "options": ["Penerimaan pajak bumi dan bangunan sektor perhutanan", "Objek PNBP Pengelolaan Barang Milik Negara (BMN)", "Retribusi daerah atas pemakaian kekayaan daerah otonom", "Pajak penjualan atas barang mewah kepemilikan kementerian", "Penerimaan bantuan hibah modal dari pihak ketiga rekanan"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Klaster PNBP Pengelolaan BMN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pengelolaan Barang Milik Negara adalah kegiatan penggunaan, pemanfaatan, dan pemindahtanganan semua barang yang dibeli atau diperoleh atas beban anggaran pendapatan dan belanja negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena sewa tanah, gedung, atau penjualan lelang BMN yang tidak digunakan lagi untuk operasional menghasilkan penerimaan kas negara yang wajib disetor sebagai PNBP Pengelolaan BMN.</p><p>Jawaban tepat: <strong>B. Objek PNBP Pengelolaan Barang Milik Negara (BMN)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PBB perhutanan adalah pajak atas kepemilikan tanah konsesi hutan.</li><li><strong>C</strong>: Retribusi kekayaan daerah berlaku untuk aset milik pemerintah daerah.</li><li><strong>D</strong>: PPnBM adalah pajak konsumsi barang mewah, bukan sewa BMN.</li><li><strong>E</strong>: Sewa BMN adalah transaksi pemanfaatan aset, bukan penerimaan hibah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengelolaan Barang Milik Negara adalah kegiatan penggunaan, pemanfaatan, • dan pemindahtanganan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Jasa giro dan imbal hasil penempatan dana pemerintah pada rekening perbankan masuk klaster...", "options": ["Penerimaan pembiayaan penarikan pinjaman dalam negeri", "Pajak penghasilan final bunga deposito perbankan swasta", "Objek PNBP Pengelolaan Dana untuk tujuan tertentu", "Cadangan wajib likuiditas transaksi pasar uang antarbank", "Penerimaan bea meterai dokumen setoran kas perbendaharaan"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Klaster PNBP Pengelolaan Dana</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pengelolaan Dana adalah pengelolaan atas dana Pemerintah yang berasal dari anggaran pendapatan dan belanja negara atau perolehan lain yang sah untuk tujuan tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena bunga atau imbal hasil dari penempatan dana bergulir, dana abadi pendidikan, dan saldo kas perbendaharaan di perbankan dibukukan sebagai PNBP Pengelolaan Dana.</p><p>Jawaban tepat: <strong>C. Objek PNBP Pengelolaan Dana untuk tujuan tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembiayaan pinjaman adalah penerimaan pokok utang, bukan bunga dana.</li><li><strong>B</strong>: Bunga dana pemerintah dikecualikan dari pemotongan PPh final komersial.</li><li><strong>D</strong>: GWM adalah instrumen kebijakan likuiditas makro bank sentral.</li><li><strong>E</strong>: Bea meterai adalah pajak dokumen perdata, bukan hasil jasa penempatan dana.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengelolaan Dana adalah pengelolaan atas dana Pemerintah yang berasal dari • anggaran pendapatan dan belanja negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penerimaan dari denda tilang, uang sitaan, atau hasil lelang barang rampasan masuk klaster...", "options": ["Pajak bumi dan bangunan sektor pertambangan mineral umum", "Penerimaan pajak perdagangan internasional komoditas ekspor", "Pungutan cukai atas peredaran barang berbahaya tanpa izin", "Objek PNBP Hak Negara Lainnya sesuai ketentuan regulasi", "Penerimaan hibah terikat dari penegak hukum luar negeri"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Klaster PNBP Hak Negara Lainnya</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Hak Negara Lainnya adalah hak negara selain dari Pemanfaatan Sumber Daya Alam, pelayanan, Pengelolaan Kekayaan Negara Dipisahkan, pengelolaan Barang Milik Negara, pengelolaan dana</strong>, yang diatur dalam undang-undang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena klaster Hak Negara Lainnya berfungsi sebagai penampung penerimaan resmi negara dari instrumen penegakan hukum (denda tilang, uang rampasan perkara korupsi/pidana, biaya perkara) yang wajib disetor ke kas negara.</p><p>Jawaban tepat: <strong>D. Objek PNBP Hak Negara Lainnya sesuai ketentuan regulasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PBB pertambangan adalah pajak atas areal konsesi pertambangan.</li><li><strong>B</strong>: Pajak perdagangan internasional memungut lalu lintas barang ekspor/impor.</li><li><strong>C</strong>: Cukai memungut etil alkohol, minuman beralkohol, dan hasil tembakau.</li><li><strong>E</strong>: Denda dan rampasan adalah sanksi hukum pidana resmi, bukan hibah sukarela.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Hak Negara Lainnya adalah hak negara selain dari Pemanfaatan Sumber Daya • Alam</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penerimaan pemerintah dalam bentuk uang, barang, atau jasa yang tidak menimbulkan kewajiban utang adalah...", "options": ["Penerimaan piutang jatuh tempo dari badan usaha milik daerah", "Penerimaan pinjaman obligasi negara bersyarat lunak", "Penerimaan pembiayaan defisit anggaran dari bank sentral", "Penerimaan penarikan utang komersial pasar modal dunia", "Penerimaan hibah dari pihak dalam negeri atau luar negeri"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Penerimaan Hibah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Hibah adalah pendapatan Pemerintah dalam bentuk uang/barang atau jasa dari Pemerintah lainnya, perusahaan negara/daerah, masyarakat dan organisasi kemasyarakatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena hibah merupakan aliran masuk kekayaan kepada pemerintah yang bersifat cuma-cuma tanpa ada kewajiban bagi pemerintah untuk mengembalikan pokok atau membayar bunga kepada pihak pemberi.</p><p>Jawaban tepat: <strong>E. Penerimaan hibah dari pihak dalam negeri atau luar negeri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penerimaan piutang adalah pelunasan tagihan aset kas, bukan hibah.</li><li><strong>B</strong>: Obligasi adalah surat utang yang wajib dilunasi pokok dan bunganya.</li><li><strong>C</strong>: Pembiayaan defisit dari BI bukan pendapatan cuma-cuma melainkan instrumen moneter.</li><li><strong>D</strong>: Utang komersial membebankan kewajiban pengembalian cicilan kas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Hibah adalah pendapatan Pemerintah dalam bentuk uang/barang atau jasa</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Karakteristik yuridis penerimaan hibah yang membedakannya dari pajak adalah...", "options": ["Bersifat tidak wajib, tidak mengikat, dan tidak secara terus-menerus", "Bersifat memaksa dan didasarkan pada sanksi pidana kurungan", "Wajib disetorkan secara rutin setiap awal bulan tahun anggaran", "Memiliki ikatan politik untuk mendukung kebijakan negara donor", "Diperuntukkan khusus guna membiayai belanja perjalanan dinas"], "answer": 0, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Karakteristik Pokok Hibah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penerimaan hibah <strong>bersifat tidak wajib dan tidak mengikat serta tidak secara terusmenerus</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena berbeda dengan pajak yang bersifat memaksa berdasarkan undang-undang, hibah diberikan secara sukarela (*voluntary*), tidak mengikat secara politis, dan tidak ada jaminan akan berulang setiap tahunnya.</p><p>Jawaban tepat: <strong>A. Bersifat tidak wajib, tidak mengikat, dan tidak secara terus-menerus</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pungutan memaksa bersanksi pidana adalah karakter pungutan pajak.</li><li><strong>C</strong>: Hibah tidak memiliki jadwal setoran rutin bulanan tetap.</li><li><strong>D</strong>: Penerimaan hibah dilarang memuat ikatan politik yang menyandera kedaulatan.</li><li><strong>E</strong>: Hibah diprioritaskan untuk pembangunan dan bencana, bukan perjalanan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bersifat tidak wajib dan tidak mengikat serta tidak secara terusmenerus</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penerimaan hibah yang masuk ke dalam kas negara dialokasikan untuk...", "options": ["Membayar bonus tahunan bagi pejabat pimpinan kementerian", "Mendukung pembangunan nasional dan penanggulangan bencana alam", "Menambah modal cadangan badan usaha perbankan komersial", "Membeli saham spekulatif perusahaan swasta di bursa efek", "Membiayai kegiatan kampanye partai politik peserta pemilihan"], "answer": 1, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Alokasi Pemanfaatan Hibah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hibah digunakan untuk <strong>mendukung program pembangunan nasional dan/atau mendukung penanggulangan bencana alam dan bantuan kemanusiaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena dana hibah harus dimanfaatkan untuk kepentingan publik yang mendesak, seperti rehabilitasi pascabencana alam atau pembangunan fasilitas kesehatan/pendidikan masyarakat.</p><p>Jawaban tepat: <strong>B. Mendukung pembangunan nasional dan penanggulangan bencana alam</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dana hibah dilarang digunakan untuk bonus pribadi pejabat.</li><li><strong>C</strong>: Penyertaan modal perbankan komersial bukan tujuan peruntukan hibah.</li><li><strong>D</strong>: Spekulasi saham swasta dilarang dalam pengelolaan keuangan negara.</li><li><strong>E</strong>: Penggunaan dana negara untuk kampanye politik adalah pelanggaran hukum berat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mendukung program pembangunan nasional • dan/atau mendukung penanggulangan bencana alam</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pemerintah hanya dapat menerima hibah apabila memenuhi prinsip...", "options": ["Memberikan hak konsesi tambang eksklusif kepada pihak donor", "Rahasia, tertutup, menguntungkan pendonor, dan bebas audit", "Transparan, akuntabel, efisien, efektif, dan tanpa ikatan politik", "Menempatkan perwakilan donor sebagai penasihat militer negara", "Meniadakan sistem pencatatan perbendaharaan pada kas negara"], "answer": 2, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Penerimaan Hibah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Hibah dapat diterima apabila memenuhi prinsip <strong>transparan, akuntabel, efisien dan efektif, kehati-hatian, tidak ada ikatan politik, dan tidak memiliki muatan yang dapat mengganggu stabilitas keamanan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kedaulatan dan keamanan nasional berada di atas segalanya. Pemerintah dilarang menerima hibah yang memiliki agenda terselubung (*strings attached*) yang dapat mendikte kebijakan publik Indonesia.</p><p>Jawaban tepat: <strong>C. Transparan, akuntabel, efisien, efektif, dan tanpa ikatan politik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hibah dilarang dikaitkan dengan kompensasi konsesi tambang negara.</li><li><strong>B</strong>: Pengelolaan hibah wajib terbuka dan diaudit oleh pemeriksa BPK.</li><li><strong>D</strong>: Penempatan penasihat militer asing melanggar kedaulatan pertahanan.</li><li><strong>E</strong>: Penerimaan hibah wajib dicatat resmi dalam mekanisme perbendaharaan APBN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>transparan, akuntabel, efisien dan • efektif, kehati-hatian, tidak ada ikatan politik</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Pos penerimaan hibah mulai dicantumkan dalam susunan APBN sejak penerapan struktur baru yaitu...", "options": ["Tahun 1983 bersamaan dengan reformasi paket undang-undang pajak", "Tahun 1945 bersamaan dengan penetapan kemerdekaan bangsa", "Tahun 1969 pada saat peluncuran rencana pembangunan lima tahun", "Tahun 2000 dengan realisasi penerimaan dimulai tahun 2001", "Tahun 2014 saat berlakunya sistem akuntansi berbasis akrual penuh"], "answer": 3, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Masuknya Pos Hibah dalam Struktur APBN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pos penerimaan hibah baru mulai ada dalam APBN <strong>sejak APBN menggunakan struktur baru yaitu tahun 2000</strong>. Realisasi penerimaannya baru mulai pada tahun 2001.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena sebelum reformasi penganggaran tahun 2000, bantuan luar negeri dicampur dalam pos penerimaan pembangunan. Format anggaran baru memisahkannya secara transparan ke dalam pos penerimaan hibah tersendiri.</p><p>Jawaban tepat: <strong>D. Tahun 2000 dengan realisasi penerimaan dimulai tahun 2001</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tahun 1983 adalah momentum perombakan undang-undang perpajakan modern.</li><li><strong>B</strong>: Tahun 1945 adalah masa awal pembentukan kas negara kemerdekaan.</li><li><strong>C</strong>: Tahun 1969 adalah awal Repelita era Orde Baru dengan format lama.</li><li><strong>E</strong>: Tahun 2014 merupakan masa transisi sistem akuntansi akrual pemerintahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sejak APBN menggunakan • struktur baru yaitu tahun 2000</strong></div></div>"}, {"category": "TSKKWK", "sub": "Pengelolaan Keuangan Negara • Paket 04", "difficulty": "C2 - Pemahaman", "question": "Penerimaan hibah mulai dianggarkan secara definitif pertama kali dalam dokumen...", "options": ["Rencana Pembangunan Jangka Menengah Nasional tahun 2020", "Nota Keuangan dan RAPBN tahun 1995 era sebelum krisis", "Rancangan DIPA induk kementerian keuangan tahun 2005", "Laporan Keuangan Pemerintah Pusat tahun anggaran 2010", "APBN Perubahan tahun 2002 oleh pemerintah dan DPR"], "answer": 4, "source": "Pengelolaan Keuangan Negara", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penganggaran Pertama Hibah dalam APBN-P 2002</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Awalnya hibah hanya dicatat realisasinya tetapi tidak dianggarkan, namun sehubungan adanya program yang dibiayai hibah, penerimaan hibah <strong>mulai dianggarkan pertama kali dalam APBN-P 2002</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena untuk pertama kalinya proyeksi nominal hibah dimasukkan secara terencana ke dalam dokumen anggaran pada APBN-P 2002, sehingga tidak hanya dicatat saat kas masuk saja (*ex-post*).</p><p>Jawaban tepat: <strong>E. APBN Perubahan tahun 2002 oleh pemerintah dan DPR</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: RPJMN adalah dokumen perencanaan strategis pembangunan lima tahunan.</li><li><strong>B</strong>: Tahun 1995 hibah belum dimasukkan dalam pos pendapatan APBN.</li><li><strong>C</strong>: DIPA adalah dokumen pelaksanaan, bukan undang-undang penetapan APBN-P.</li><li><strong>D</strong>: LKPP 2010 adalah laporan pertanggungjawaban realisasi, bukan penganggaran awal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Pengelolaan Keuangan Negara (Hlm. 53) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mulai • dianggarkan pertama kali dalam APBN-P 2002</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_pkn_p04_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_pkn_p04_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_pkn_p04_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Pengelolaan Keuangan Negara Paket 04";
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

</script></body></html>
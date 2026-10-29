<?php
/**
 * STANGOLDEN.exam • Tata Aturan Kepegawaian Paket 01 (Halaman 07–20)
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
// $DB->get_record("stgtak_p01_rank") => tabel fisik "mdlhv_stgtak_p01_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgtak_p01_rank';
const STG_REVIEW_T = 'stgtak_p01_rev';
const STG_ANNO_T = 'stgtak_p01_anno';
const STG_KEY = [0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 0, 2, 4, 1, 3, 0, 2, 4, 1, 3, 1, 3, 0, 2, 4, 1, 3, 0, 2, 4, 2, 4, 1, 3, 0, 2, 4, 1, 3, 0, 3, 0, 2, 4, 1, 3, 0, 2, 4, 1, 4, 1, 3, 0, 2, 4, 1, 3, 0, 2];
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
            error_log('STANGOLDEN TAK P01 HLM07-20 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN TAK P01 HLM07-20: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Tata Aturan Kepegawaian Paket 01 (Hlm. 07–20) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 01 • Hlm. 07–20</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 01 • Hlm. 07–20</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Tata Aturan Kepegawaian Paket 01</h2>
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
const STG_LOCAL_KEY="stg_tak_p01_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Rancangan Undang-Undang Aparatur Sipil Negara disetujui DPR-RI dalam Rapat Paripurna pada tanggal...", "options": ["3 Oktober 2023", "17 Oktober 2023", "24 Oktober 2023", "31 Oktober 2023", "10 November 2023"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Persetujuan DPR atas RUU ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Rancangan Undang-Undang (RUU) Aparatur Sipil Negara disetujui oleh <strong>DPR-RI</strong> pada <strong>Rapat Paripurna tanggal 3 Oktober 2023</strong> sebelum disahkan menjadi undang-undang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan tanggal yang tepat sesuai catatan kronologi pembentukan undang-undang ASN terbaru. Lembaga legislatif meresmikan persetujuan naskah regulasi aparatur sipil negara pada sidang paripurna awal Oktober. Sebelum disahkan presiden, RUU ASN resmi disetujui DPR-RI dalam Rapat Paripurna tanggal 3 Oktober 2023. Dengan demikian, opsi A adalah ketetapan waktu yang benar.</p><p>Jawaban tepat: <strong>A. 3 Oktober 2023</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan tanggal pertengahan bulan pembahasan komisi dan bukan hari rapat paripurna DPR.</li><li><strong>C</strong>: Merupakan tanggal penyiapan naskah final perundangan di tingkat sekretariat negara.</li><li><strong>D</strong>: Merupakan tanggal resmi pengesahan oleh Presiden Joko Widodo menjadi UU Nomor 20 Tahun 2023.</li><li><strong>E</strong>: Merupakan tanggal peringatan nasional hari pahlawan dan bukan jadwal agenda persetujuan DPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Rapat Paripurna tanggal 3 Oktober 2023 • Rancangan Undang-Undang (RUU)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Presiden Republik Indonesia mengesahkan Undang-Undang Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara pada...", "options": ["3 Oktober 2023", "31 Oktober 2023", "10 November 2023", "1 Desember 2023", "2 Januari 2024"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengesahan UU Nomor 20 Tahun 2023</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada <strong>31 Oktober 2023</strong>, RUU ASN resmi <strong>disahkan oleh Presiden Joko Widodo</strong> menjadi <strong>Undang-Undang Nomor 20 Tahun 2023</strong> Tentang Aparatur Sipil Negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat mengenai momentum penetapan regulasi kepegawaian nasional. Kepala negara menandatangani pengesahan naskah hukum ASN pada akhir bulan Oktober 2023. Tanggal 31 Oktober 2023 menjadi landasan yuridis berlakunya undang-undang kepegawaian menggantikan regulasi sebelumnya. Oleh karena itu, opsi B adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. 31 Oktober 2023</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tanggal persetujuan naskah oleh DPR-RI dalam rapat paripurna.</li><li><strong>C</strong>: Merupakan penanggalan bulan berikutnya setelah proses pengundangan resmi dilakukan.</li><li><strong>D</strong>: Merupakan tanggal awal pelaksanaan teknis sosialisasi kelembagaan kementerian.</li><li><strong>E</strong>: Merupakan awal tahun anggaran berikutnya dan bukan waktu pengesahan presiden.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>31 Oktober telah disahkan oleh Presiden • Undang-Undang Nomor 20 Tahun 2023</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Undang-Undang Nomor 20 Tahun 2023 secara resmi diundangkan untuk menggantikan regulasi kepegawaian...", "options": ["Undang-Undang Nomor 43 Tahun 1999", "Undang-Undang Nomor 8 Tahun 1974", "Undang-Undang Nomor 5 Tahun 2014", "Undang-Undang Nomor 30 Tahun 2014", "Undang-Undang Nomor 23 Tahun 2014"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi yang Digantikan UU ASN 2023</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Undang-Undang Nomor 20 Tahun 2023</strong> tentang Aparatur Sipil Negara diterbitkan untuk <strong>menggantikan Undang-Undang Nomor 5 Tahun 2014</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat terkait kedudukan hukum regulasi baru aparatur. Regulasi ASN tahun 2014 memerlukan pembaruan menyeluruh guna menjawab dinamika manajemen talenta dan transformasi digital birokrasi. Ketentuan undang-undang menyatakan tegas bahwa UU Nomor 20 Tahun 2023 menggantikan Undang-Undang Nomor 5 Tahun 2014. Dengan demikian, opsi C adalah rujukan yang benar.</p><p>Jawaban tepat: <strong>C. Undang-Undang Nomor 5 Tahun 2014</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan regulasi pokok kepegawaian masa transisi era reformasi sebelum tahun 2014.</li><li><strong>B</strong>: Merupakan undang-undang pokok-pokok kepegawaian awal yang telah lama dicabut.</li><li><strong>D</strong>: Merupakan regulasi perundang-undangan yang mengatur tentang administrasi pemerintahan.</li><li><strong>E</strong>: Merupakan regulasi yang mengatur tentang pemerintahan daerah otonom.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Undang-Undang Nomor 20 Tahun 2023 • menggantikan Undang-Undang Nomor 5 Tahun 2014</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 77 Undang-Undang Nomor 20 Tahun 2023, undang-undang ini mulai berlaku pada...", "options": ["Enam bulan sejak persetujuan dewan perwakilan", "Satu tahun setelah tanggal pengesahan presiden", "Awal tahun anggaran baru tanggal 1 Januari 2024", "Tanggal resmi undang-undang diundangkan", "Saat seluruh aturan pelaksana selesai ditetapkan"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Waktu Pemberlakuan UU Nomor 20 Tahun 2023</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai ketentuan <strong>Pasal 77 UU Nomor 20 Tahun 2023</strong>, undang-undang tentang aparatur sipil negara ini <strong>mulai berlaku pada tanggal diundangkan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat mengenai saat mulai berlakunya norma hukum ASN. Pembentuk undang-undang menetapkan keberlakuan seketika demi kepastian hukum tanpa masa penundaan bertahun-tahun. Norma Pasal 77 menyatakan secara lugas bahwa UU Nomor 20 Tahun 2023 mulai berlaku pada tanggal diundangkan. Oleh karena itu, opsi D merupakan jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Tanggal resmi undang-undang diundangkan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kelaziman masa transisi regulasi tertentu namun bukan ketentuan Pasal 77.</li><li><strong>B</strong>: Merupakan masa toleransi penyesuaian aturan turunan dan bukan saat mulai berlaku undang-undang.</li><li><strong>C</strong>: Merupakan siklus pembukuan keuangan dan bukan asas keberlakuan yuridis pasal penutup.</li><li><strong>E</strong>: Merupakan kondisi operasional lapangan yang tidak menunda keberlakuan pokok undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pasal 77 UU Nomor 20 Tahun 2023 • pada tanggal diundangkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Sebuah peraturan pemerintah turunan UU 5/2014 belum diperbarui saat UU 20/2023 terbit. Status keberlakuannya adalah...", "options": ["Batal demi hukum sejak undang-undang baru disahkan", "Diberhentikan sementara menunggu persetujuan presiden", "Hanya berlaku jika ada penetapan diskresi menteri", "Dibatalkan sebagian pada pasal yang memiliki sanksi", "Tetap berlaku sepanjang tidak bertentangan aturan baru"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Status Peraturan Pelaksanaan Regulasi Lama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Berdasarkan <strong>ketentuan penutup UU Nomor 20 Tahun 2023</strong>, peraturan pelaksanaan UU Nomor 5 Tahun 2014 <strong>tetap berlaku sepanjang tidak bertentangan dan belum diganti</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan sikap hukum yang tepat dalam asas transisi peraturan perundang-undangan. Kekosongan hukum dihindari dengan tetap mempertahankan aturan pelaksana lama selama materinya selaras dengan norma baru. Ketentuan penutup menyatakan seluruh regulasi turunan tetap berlaku sepanjang tidak bertentangan dan belum diganti berdasarkan undang-undang baru. Dengan demikian, opsi E adalah opsi yang tepat.</p><p>Jawaban tepat: <strong>E. Tetap berlaku sepanjang tidak bertentangan aturan baru</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pemahaman keliru yang dapat menciptakan kekosongan hukum operasional kepegawaian.</li><li><strong>B</strong>: Merupakan tindakan administratif darurat yang tidak diatur dalam pasal peralihan regulasi.</li><li><strong>C</strong>: Merupakan penerapan diskresi sepihak yang menyimpang dari amanat ketentuan penutup.</li><li><strong>D</strong>: Merupakan pembatalan sepihak tanpa dasar pengujian materi perundang-undangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ketentuan penutup Undang-Undang Nomor 20 Tahun 2023 • peraturan perundang-undangan yang merupakan peraturan pelaksanaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C4 - Analisis", "question": "Peraturan pelaksanaan UU Nomor 5 Tahun 2014 dinyatakan tetap sah berlaku apabila memenuhi kriteria...", "options": ["Tidak bertentangan dan belum diganti aturan baru", "Mendapat persetujuan tertulis dari komisi dewan", "Telah diuji materiil oleh Mahkamah Konstitusi", "Diterbitkan paling lambat dua tahun sebelum pembaruan", "Memiliki alokasi anggaran operasional tersendiri"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Kumulatif Keberlakuan Aturan Turunan Lama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Peraturan pelaksanaan regulasi lama tetap berlaku dengan dua syarat pokok: <strong>sepanjang tidak bertentangan</strong> dengan UU Nomor 20 Tahun 2023 dan <strong>belum diganti</strong> berdasarkan ketentuan baru.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan analisis yuridis yang tepat atas rumusan klausul peralihan penutup. Keberlakuan regulasi turunan bergantung pada ketiadaan pertentangan substansi serta ketiadaan regulasi pengganti yang baru diterbitkan. Dua syarat tersebut menjaga kesinambungan layanan administrasi publik aparatur sipil negara. Oleh karena itu, opsi A merupakan kriteria yang benar.</p><p>Jawaban tepat: <strong>A. Tidak bertentangan dan belum diganti aturan baru</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan mekanisme pengawasan politik parlemen dan bukan syarat keberlakuan aturan turunan.</li><li><strong>C</strong>: Merupakan proses sengketa yudisial khusus dan bukan syarat umum keberlakuan norma.</li><li><strong>D</strong>: Merupakan pembatasan waktu fiktif yang tidak tercantum dalam ketentuan penutup.</li><li><strong>E</strong>: Merupakan aspek penganggaran administratif yang terpisah dari status legalitas norma.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>peraturan perundang-undangan yang merupakan peraturan pelaksanaan • belum diganti berdasarkan Undang-Undang ini</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pengesahan Undang-Undang Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara secara konstitusional dilakukan oleh...", "options": ["Ketua Dewan Perwakilan Rakyat Republik Indonesia", "Presiden Republik Indonesia Joko Widodo", "Menteri Pendayagunaan Aparatur Negara dan RB", "Kepala Badan Kepegawaian Negara Indonesia", "Ketua Komisi Aparatur Sipil Negara Republik"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pejabat Pengesah UU Nomor 20 Tahun 2023</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>RUU ASN yang telah disetujui bersama disahkan secara resmi oleh <strong>Presiden Joko Widodo</strong> menjadi <strong>Undang-Undang Nomor 20 Tahun 2023</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat sesuai wewenang ketatanegaraan pembentukan undang-undang. Presiden bertindak selaku kepala pemerintahan yang memegang mandat pengesahan naskah hukum negara. Undang-undang kepegawaian tahun 2023 disahkan secara resmi oleh Presiden Joko Widodo pada 31 Oktober 2023. Dengan demikian, opsi B adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Presiden Republik Indonesia Joko Widodo</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pimpinan lembaga legislatif yang memimpin pengesahan tahap paripurna DPR.</li><li><strong>C</strong>: Merupakan pimpinan kementerian bidang aparatur yang menjalankan peran teknis regulasi.</li><li><strong>D</strong>: Merupakan kepala lembaga pengelola operasional manajemen kepegawaian aparatur.</li><li><strong>E</strong>: Merupakan pimpinan komisi pengawas norma dan etika sistem merit kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>disahkan oleh Presiden Joko Widodo • Tentang Aparatur Sipil Negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nomor pasal dalam Undang-Undang Nomor 20 Tahun 2023 yang mengatur bahwa undang-undang mulai berlaku pada tanggal diundangkan adalah...", "options": ["Pasal 55", "Pasal 62", "Pasal 77", "Pasal 81", "Pasal 94"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal Ketentuan Penutup UU ASN 2023</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketentuan mengenai saat mulai berlakunya Undang-Undang Nomor 20 Tahun 2023 tercantum pada <strong>Pasal 77</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan nomor pasal penutup yang tepat dalam naskah legal undang-undang ASN 2023. Norma ini menjadi penentu kepastian hukum awal keberlakuan seluruh ketentuan di lingkungan instansi pemerintah. Pasal 77 menegaskan undang-undang ini mulai berlaku pada tanggal diundangkan. Oleh karena itu, opsi C merupakan rujukan pasal yang benar.</p><p>Jawaban tepat: <strong>C. Pasal 77</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pasal yang mengatur aspek substantif pembinaan profesi dan penghargaan.</li><li><strong>B</strong>: Merupakan pasal yang memuat ketentuan mengenai pemberhentian dan masa tugas.</li><li><strong>D</strong>: Merupakan nomor pasal fiktif karena naskah undang-undang ini berakhir pada Pasal 77.</li><li><strong>E</strong>: Merupakan nomor regulasi peraturan pemerintah disiplin dan bukan pasal undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pasal 77 UU Nomor 20 Tahun 2023 • mulai berlaku</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Forum resmi di DPR-RI yang menyetujui pengesahan naskah RUU ASN pada tanggal 3 Oktober 2023 adalah...", "options": ["Rapat Dengar Pendapat Komisi Teknis", "Rapat Kerja Terbuka Bersama Menteri", "Sidang Khusus Badan Urusan Legislasi", "Rapat Paripurna Dewan Perwakilan Rakyat", "Pertemuan Konsultasi Antar-Fraksi Gabungan"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Agenda Sidang Paripurna DPR Terkait ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Persetujuan akhir rancangan regulasi aparatur sipil negara dilaksanakan melalui <strong>Rapat Paripurna</strong> DPR-RI tanggal <strong>3 Oktober 2023</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jenis forum permusyawaratan tertinggi DPR-RI yang tepat. Seluruh fraksi menyampaikan keputusan bulat pengesahan naskah regulasi aparatur dalam forum paripurna. Berdasarkan catatan pembentukan hukum, RUU ASN disetujui dalam forum Rapat Paripurna. Dengan demikian, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Rapat Paripurna Dewan Perwakilan Rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan agenda pendalaman materi kerja komisi bersama para mitra kerja eksekutif.</li><li><strong>B</strong>: Merupakan forum pembahasan pasal demi pasal antara pimpinan komisi dan menteri terkait.</li><li><strong>C</strong>: Merupakan mekanisme harmonisasi draf sebelum diajukan ke sidang pengambilan keputusan.</li><li><strong>E</strong>: Merupakan lobi informal perumusan kesepakatan politik sebelum sidang paripurna dibuka.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Rancangan Undang-Undang (RUU) Aparatur Sipil Negara • Rapat Paripurna tanggal 3 Oktober 2023</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Pemberlakuan sementara aturan pelaksana turunan UU 5/2014 bertujuan utama untuk...", "options": ["Menunda kewajiban pelaksanaan sistem merit nasional", "Mencegah pembatalan sepihak tunjangan kinerja daerah", "Mengurangi beban audit kepatuhan inspektorat jenderal", "Memberikan hak imunitas hukum bagi pejabat kepegawaian", "Menjamin kesinambungan hukum tata kelola kepegawaian"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Penyelenggaraan Ketentuan Peralihan UU ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketentuan penutup yang mempertahankan peraturan pelaksanaan lama berfungsi untuk <strong>menjaga kepastian dan kesinambungan hukum operasional</strong> administrasi kepegawaian ASN.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan tujuan yuridis mendasar dari penerapan ketentuan penutup. Penggantian undang-undang induk tidak boleh menghentikan proses administrasi harian aparatur seperti pengangkatan, mutasi, dan penggajian. Seluruh instansi tetap berpedoman pada peraturan pelaksanaan yang ada sepanjang tidak bertentangan. Oleh karena itu, opsi E adalah tujuan yang tepat.</p><p>Jawaban tepat: <strong>E. Menjamin kesinambungan hukum tata kelola kepegawaian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sistem merit justru diperkuat penerapannya dalam undang-undang kepegawaian terbaru.</li><li><strong>B</strong>: Pemberian tunjangan kinerja diatur melalui mekanisme anggaran daerah dan standar nasional.</li><li><strong>C</strong>: Fungsi pengawasan aparat pengawas internal pemerintah tetap berjalan normal tanpa pengurangan.</li><li><strong>D</strong>: Ketentuan kepegawaian tidak memberikan hak kebal hukum istimewa bagi pejabat mana pun.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ketentuan penutup Undang-Undang Nomor 20 Tahun 2023 • peraturan perundang-undangan yang merupakan peraturan pelaksanaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Aparatur Sipil Negara secara hukum didefinisikan sebagai suatu profesi bagi...", "options": ["Seluruh tenaga kontrak outsourcing di kementerian negara", "Pegawai Negeri Sipil dan PPPK di instansi pemerintah", "Karyawan tetap pada badan usaha milik negara dan swasta", "Pekerja profesional independen pada lembaga peradilan", "Aparat pertahanan dan keamanan penjaga kedaulatan negara"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Aparatur Sipil Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Aparatur Sipil Negara (ASN)</strong> adalah <strong>profesi bagi Pegawai Negeri Sipil dan pegawai pemerintah dengan perjanjian kerja</strong> yang bekerja pada <strong>instansi pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan rumusan pengertian yuridis ASN yang tepat. Korps aparatur negara secara formal terdiri atas dua komponen utama pegawai pemerintah. Regulasi menegaskan ASN adalah profesi bagi Pegawai Negeri Sipil dan PPPK yang bertugas pada instansi pemerintah. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Pegawai Negeri Sipil dan PPPK di instansi pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tenaga alih daya outsourcing bukan merupakan bagian dari status profesi aparatur sipil negara.</li><li><strong>C</strong>: Karyawan korporasi BUMN berstatus pekerja badan usaha di bawah regulasi ketenagakerjaan umum.</li><li><strong>D</strong>: Profesi profesional swasta berada di luar ranah kelembagaan aparatur sipil instansi pemerintah.</li><li><strong>E</strong>: Personel TNI dan Polri diatur dalam konstitusi dan undang-undang pertahanan keamanan tersendiri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>profesi bagi Pegawai Negeri Sipil • bekerja pada instansi pemerintah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pegawai ASN yang terdiri atas PNS dan PPPK secara resmi diangkat oleh...", "options": ["Kepala Badan Riset dan Inovasi Nasional", "Pimpinan tertinggi lembaga swadaya masyarakat", "Pejabat Pembina Kepegawaian di instansi", "Ketua Komisi Pemberantasan Korupsi Republik", "Sekretaris Daerah Khusus Ibu Kota Nusantara"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komposisi Pegawai ASN dan Pejabat Pengangkat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai ASN terdiri atas PNS dan PPPK yang <strong>diangkat oleh Pejabat Pembina Kepegawaian (PPK)</strong> dan diserahi tugas jabatan pemerintahan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan otoritas pemegang kewenangan pengangkatan aparatur yang tepat. Pejabat Pembina Kepegawaian memegang wewenang legal menetapkan pengangkatan pegawai di instansinya. Ketentuan pasal menyebutkan PNS dan PPPK diangkat oleh pejabat pembina kepegawaian untuk menduduki tugas negara. Dengan demikian, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Pejabat Pembina Kepegawaian di instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pimpinan lembaga riset nasional dan bukan pejabat pembina seluruh kementerian.</li><li><strong>B</strong>: Merupakan entitas organisasi nonpemerintah yang tidak memiliki wewenang hukum kepegawaian.</li><li><strong>D</strong>: Merupakan pimpinan komisi penegak hukum yang menangani tindak pidana korupsi khusus.</li><li><strong>E</strong>: Merupakan pejabat pimpinan daerah yang hanya berwenang pada lingkup pemda setempat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pegawai ASN terdiri dari • diangkat oleh pejabat pembina kepegawaian</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Ciri khas status kepegawaian Pegawai Negeri Sipil (PNS) berdasarkan undang-undang adalah diangkat...", "options": ["Berdasarkan kontrak tahunan berorientasi proyek khusus", "Melalui penunjukan langsung pimpinan fraksi partai", "Hanya untuk masa jabatan maksimal lima tahun berturut", "Sebagai Pegawai ASN secara tetap oleh pejabat pembina", "Secara berkala mengikuti pergantian kabinet menteri"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Pengangkatan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>PNS</strong> adalah warga negara Indonesia yang memenuhi syarat tertentu, <strong>diangkat sebagai Pegawai ASN secara tetap</strong> oleh PPK untuk <strong>menduduki jabatan pemerintahan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D melengkapi karakteristik pokok PNS yang tepat dibanding unsur aparatur lainnya. Sifat kepegawaian tetap memberikan kepastian karier jangka panjang dalam birokrasi pemerintahan. Regulasi menetapkan PNS diangkat secara tetap oleh PPK setelah menuntaskan masa percobaan. Oleh karena itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Sebagai Pegawai ASN secara tetap oleh pejabat pembina</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sistem kontrak berbatas waktu merupakan mekanisme kerja PPPK dan bukan karakter status PNS.</li><li><strong>B</strong>: Pengangkatan aparatur sipil negara wajib steril dari intervensi politik dan titipan golongan.</li><li><strong>C</strong>: Batasan masa jabatan lima tahun berlaku pada evaluasi jabatan struktural pimpinan tinggi.</li><li><strong>E</strong>: Karier aparatur sipil negara bersifat profesional permanen tanpa terikat masa jabatan menteri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pegawai Negeri Sipil yang selanjutnya disingkat PNS • diangkat sebagai Pegawai ASN secara tetap</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pegawai Pemerintah dengan Perjanjian Kerja (PPPK) diangkat oleh pemerintah berdasarkan landasan...", "options": ["Ketetapan konsorsium perguruan tinggi negeri", "Piagam kesepakatan serikat buruh nasional", "Nota kesepahaman antarduta besar negara sahabat", "Instruksi lisan kepala dinas perbendaharaan", "Perjanjian kerja untuk jangka waktu tertentu"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian dan Landasan Kerja PPPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>PPPK</strong> diangkat <strong>berdasarkan perjanjian kerja untuk jangka waktu tertentu</strong> dalam rangka melaksanakan tugas dan/atau menduduki jabatan pemerintahan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan dasar ikatan kerja legal PPPK yang tepat. Hubungan dinas pegawai didasarkan pada dokumen kontrak formal dengan durasi waktu kerja yang telah disepakati. Ketentuan aturan merumuskan PPPK diangkat berdasarkan perjanjian kerja untuk jangka waktu tertentu. Dengan demikian, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Perjanjian kerja untuk jangka waktu tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan kerja sama akademik universitas dan bukan instrumen legal pengangkatan aparatur.</li><li><strong>B</strong>: Merupakan instrumen hubungan industrial ketenagakerjaan umum di luar birokrasi negara.</li><li><strong>C</strong>: Merupakan instrumen diplomatik hubungan luar negeri dan bukan hukum kepegawaian domestik.</li><li><strong>D</strong>: Perjanjian kerja aparatur wajib berbentuk naskah hukum tertulis dan bukan instruksi lisan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PPPK merupakan warga negara Indonesia • berdasarkan perjanjian kerja untuk jangka waktu tertentu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Secara kedudukan hukum, Pegawai ASN berkedudukan sebagai unsur aparatur negara yang bertugas...", "options": ["Melaksanakan kebijakan yang ditetapkan pimpinan instansi", "Menyusun undang-undang bersama komisi legislatif pusat", "Membuat putusan hukum yang mengikat badan peradilan", "Menentukan arah ideologi dan platform partai politik", "Mengelola keuangan perusahaan swasta multinasional"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan dan Tugas Pokok Pegawai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai ASN <strong>berkedudukan sebagai unsur aparatur negara</strong> yang bertugas <strong>melaksanakan kebijakan yang ditetapkan oleh pimpinan Instansi Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan tugas fungsional kedudukan aparatur sipil negara yang tepat. Sebagai motor penggerak eksekutif, pegawai bertanggung jawab mengimplementasikan arah program kerja instansinya. Sesuai ketentuan, aparatur berkedudukan menjalankan kebijakan yang ditetapkan oleh pimpinan instansi pemerintah. Oleh karena itu, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>A. Melaksanakan kebijakan yang ditetapkan pimpinan instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kewenangan legislasi pembentukan undang-undang berada pada ranah DPR bersama presiden.</li><li><strong>C</strong>: Kekuasaan kehakiman yudikatif dijalankan secara independen oleh Mahkamah Agung dan badan peradilan.</li><li><strong>D</strong>: Aparatur sipil negara dilarang keras terlibat dalam perumusan platform atau politik kepartaian.</li><li><strong>E</strong>: Tata kelola korporasi swasta bukan merupakan ruang lingkup penugasan aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berkedudukan sebagai unsur aparatur negara • melaksanakan kebijakan yang ditetapkan oleh pimpinan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Menjelang pemilihan umum, seorang pegawai ASN diminta bergabung menjadi tim sukses partai. Tindakan yang wajib diambil adalah...", "options": ["Menerima tawaran secara diam-diam tanpa surat tugas", "Menolak tawaran demi menjaga prinsip netralitas ASN", "Meminta izin pimpinan untuk menjadi pengurus aktif", "Menggunakan fasilitas kantor untuk kampanye terbatas", "Membagi dokumen kerja dinas kepada simpatisan partai"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Netralitas Pegawai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai ASN <strong>harus bebas dari pengaruh dan intervensi semua golongan dan partai politik</strong> demi menjamin netralitas pelayanan publik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan keputusan yang selaras dengan kewajiban etika aparatur negara. Pegawai dilarang memihak pada kekuatan politik mana pun agar pelayanan publik tetap adil dan nondiskriminatif. Undang-undang menegaskan ASN wajib netral dari segala intervensi partai politik. Dengan demikian, menolak tawaran adalah langkah yang tepat.</p><p>Jawaban tepat: <strong>B. Menolak tawaran demi menjaga prinsip netralitas ASN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keterlibatan terselubung tetap merupakan pelanggaran berat terhadap netralitas aparatur sipil negara.</li><li><strong>C</strong>: Pimpinan instansi tidak berwenang memberikan izin kepada bawahan untuk aktif di partai politik.</li><li><strong>D</strong>: Penggunaan barang milik negara untuk kegiatan kampanye dilarang keras oleh ketentuan perundang-undangan.</li><li><strong>E</strong>: Penyalahgunaan data dinas kedinasan untuk kepentingan politik melanggar rahasia jabatan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bebas dari pengaruh dan intervensi • semua golongan dan partai politik</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Ketentuan mengenai kedudukan ASN dan asas bebas dari intervensi partai politik diatur dalam UU 20/2023 pada...", "options": ["Pasal 1 dan 2", "Pasal 4 dan 5", "Pasal 8 dan 9", "Pasal 12 dan 13", "Pasal 16 dan 17"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal Kedudukan dan Netralitas ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kedudukan Pegawai ASN sebagai aparatur negara pelaksana kebijakan dan asas bebas dari intervensi politik diatur dalam <strong>Pasal 8 dan 9 UU Nomor 20 Tahun 2023</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan nomor pasal yang tepat dalam regulasi kepegawaian nasional terbaru. Pasal 8 dan Pasal 9 menjadi fondasi yuridis tegaknya profesionalisme birokrasi yang steril dari kepentingan partisan. Ketentuan hukum menegaskan kedudukan dan netralitas aparatur pada kedua pasal tersebut. Oleh karena itu, opsi C adalah rujukan yang benar.</p><p>Jawaban tepat: <strong>C. Pasal 8 dan 9</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pasal yang memuat definisi umum serta asas dasar penyelenggaraan aparatur negara.</li><li><strong>B</strong>: Merupakan pasal yang mengatur nilai dasar serta kode etik dan kode perilaku ASN.</li><li><strong>D</strong>: Merupakan pasal yang mengatur jenis jabatan manajerial dan jenjang karier struktural.</li><li><strong>E</strong>: Merupakan pasal yang mengatur hak dan komponen penghargaan materiel pegawai pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pasal 8 dan 9 UU Nomor 20 Tahun 2023 • bebas dari pengaruh dan intervensi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Setiap Pegawai ASN yang diserahi tugas negara berhak memperoleh penghasilan yang bersumber dari...", "options": ["Sumbangan sukarela masyarakat pengguna layanan publik", "Bagi hasil keuntungan proyek investasi swasta rekanan", "Iuran wajib bulanan para pegawai senior di kementerian", "Ketentuan resmi berdasarkan peraturan perundang-undangan", "Dana talangan pribadi pimpinan satuan kerja dinas"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Konsekuensi Penugasan dan Hak Finansial ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai ASN yang diserahi tugas jabatan pemerintahan <strong>diberikan penghasilan berdasarkan peraturan perundang-undangan</strong> yang sah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan dasar legalitas pemberian kompensasi keuangan bagi aparatur yang tepat. Hak keuangan pegawai bersumber dari kas negara yang dialokasikan dan dicairkan sesuai standar aturan hukum. Ketentuan regulasi menegaskan penghasilan aparatur wajib berlandaskan peraturan perundang-undangan. Dengan demikian, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Ketentuan resmi berdasarkan peraturan perundang-undangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penerimaan uang di luar tarif resmi dari masyarakat dikategorikan sebagai pungutan liar ilegal.</li><li><strong>B</strong>: Penerimaan imbalan langsung dari pihak rekanan swasta berpotensi menimbulkan gratifikasi terlarang.</li><li><strong>C</strong>: Kesejahteraan dinas pegawai bukan berasal dari pungutan internal iuran pegawai lainnya.</li><li><strong>E</strong>: Penggajian resmi aparatur negara menggunakan anggaran belanja negara dan bukan dana privat pimpinan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diserahi tugas dalam suatu jabatan pemerintahan • diberikan penghasilan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C4 - Analisis", "question": "Di lingkungan instansi pemerintah Republik Indonesia, Pegawai ASN dikelompokkan secara sah ke dalam...", "options": ["Pegawai Tetap Daerah dan Pegawai Harian Lepas Pusat", "Tenaga Honorer Kategori I dan Tenaga Kontrak Magang", "Pegawai Organik Lembaga dan Pekerja Alih Daya Swasta", "Tenaga Bantuan Operasional dan Staf Khusus Kementerian", "Pegawai Negeri Sipil dan Pegawai Pemerintah Perjanjian Kerja"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Klasifikasi Dua Jenis Pegawai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai undang-undang, Pegawai ASN hanya terdiri dari dua jenis status resmi: <strong>Pegawai Negeri Sipil (PNS)</strong> dan <strong>Pegawai Pemerintah dengan Perjanjian Kerja (PPPK)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan klasifikasi yuridis kepegawaian yang tepat dan diakui secara nasional. Transformasi aparatur menghapus status honorer dan menyatukan status kepegawaian ke dalam dua jalur resmi. Undang-undang kepegawaian menegaskan Pegawai ASN terdiri atas PNS dan PPPK. Oleh karena itu, opsi E merupakan klasifikasi yang tepat.</p><p>Jawaban tepat: <strong>E. Pegawai Negeri Sipil dan Pegawai Pemerintah Perjanjian Kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Istilah pegawai harian lepas bukan merupakan nomenklatur status pegawai aparatur sipil negara.</li><li><strong>B</strong>: Penggolongan tenaga honorer telah dihapuskan dalam reformasi manajemen aparatur sipil negara.</li><li><strong>C</strong>: Pekerja alih daya pihak ketiga memiliki ikatan kerja dengan vendor swasta dan bukan ASN.</li><li><strong>D</strong>: Staf khusus kementerian memiliki status penugasan politis nonkarier di luar jabatan ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pegawai ASN terdiri dari Pegawai Negeri Sipil • dan Pegawai Pemerintah dengan Perjanjian Kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Syarat mutlak status kewarganegaraan untuk dapat diangkat menjadi PNS maupun PPPK adalah...", "options": ["Warga Negara Indonesia yang memenuhi persyaratan", "Warga negara asing pemegang visa izin tinggal tetap", "Mantan warga negara asing yang memiliki keahlian sains", "Penduduk berstatus dwi-kewarganegaraan aktif resmi", "Warga negara asing peserta program beasiswa bilateral"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Kewarganegaraan Calon Pegawai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Baik PNS maupun PPPK disyaratkan secara mutlak merupakan <strong>warga negara Indonesia (WNI) yang memenuhi syarat tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan syarat kedaulatan utama dalam pengisian jabatan birokrasi pemerintahan. Pengabdian pada tugas negara menuntut kesetiaan tunggal kepada Negara Kesatuan Republik Indonesia. Ketentuan hukum menegaskan pelamar wajib berstatus Warga Negara Indonesia yang memenuhi kriteria jabatan. Dengan demikian, opsi A adalah persyaratan yang benar.</p><p>Jawaban tepat: <strong>A. Warga Negara Indonesia yang memenuhi persyaratan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Warga negara asing tidak diperkenankan menjadi aparatur sipil negara pengelola pemerintahan.</li><li><strong>C</strong>: Keahlian teknologi tenaga asing dapat difasilitasi melalui mekanisme konsultan ahli non-ASN.</li><li><strong>D</strong>: Hukum nasional Republik Indonesia tidak mengakui kewarganegaraan ganda bagi aparatur sipil negara.</li><li><strong>E</strong>: Penerima beasiswa luar negeri berstatus mahasiswa dan bukan calon pegawai aparatur sipil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>warga negara Indonesia yang memenuhi syarat tertentu • untuk menduduki jabatan pemerintahan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi terbaru, jabatan di lingkungan aparatur sipil negara terbagi ke dalam...", "options": ["Jabatan struktural murni dan jabatan staf pembantu umum", "Jabatan karier terbuka dan jabatan politis perwakilan", "Jabatan Manajerial dan Jabatan Nonmanajerial", "Jabatan eselon wilayah dan jabatan fungsional daerah", "Jabatan operasional lapangan dan jabatan audit internal"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Klasifikasi Utama Jabatan ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan dalam ASN terdiri atas 2 (dua) jabatan</strong>, yaitu <strong>Jabatan Manajerial</strong> dan <strong>Jabatan Nonmanajerial</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan pembagian rumpun jabatan ASN yang tepat sesuai ketentuan regulasi kepegawaian modern. Sistem karier memisahkan peran kepemimpinan manajerial dengan peran teknis pelayanan fungsional. Ketentuan aturan menegaskan jabatan ASN terdiri atas 2 jenis yaitu manajerial dan nonmanajerial. Oleh karena itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Jabatan Manajerial dan Jabatan Nonmanajerial</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nomenklatur jabatan struktural murni dan staf pembantu merupakan istilah lama era pra-reformasi birokrasi.</li><li><strong>B</strong>: Aparatur sipil negara tidak menduduki jabatan politis perwakilan partai politik.</li><li><strong>D</strong>: Pengelompokan jabatan eselon wilayah bukan pembagian rumpun jabatan dalam regulasi ASN baru.</li><li><strong>E</strong>: Jabatan operasional dan audit merupakan spesialisasi tugas dan bukan klasifikasi payung jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan dalam ASN terdiri atas 2 (dua) jabatan • Jabatan Manajerial</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Fungsi utama yang melekat pada kelompok Jabatan Manajerial dalam birokrasi pemerintahan adalah...", "options": ["Menjalankan riset sains laboratorium secara mandiri", "Menyusun pembukuan teknis bukti kas kuitansi dinas", "Melakukan penindakan hukum tindak pidana kepolisian", "Memimpin unit organisasi dan mengelola kinerja pegawai", "Memberikan pertimbangan politik kepada kepala daerah"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Jabatan Manajerial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan Manajerial</strong> adalah sekelompok jabatan yang memiliki fungsi <strong>memimpin unit organisasi</strong> serta <strong>mengelola dan mengawasi kinerja pegawai</strong> demi mencapai tujuan organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merangkum esensi tugas pimpinan manajerial birokrasi secara tepat. Pejabat manajerial memegang kendali kepemimpinan atas satuan kerja dan sumber daya manusia di bawahnya. Sesuai rumusan konsep, fungsi manajerial adalah memimpin unit serta mengawasi kinerja staf. Dengan demikian, opsi D adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>D. Memimpin unit organisasi dan mengelola kinerja pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pelaksanaan riset ilmiah mandiri merupakan fungsi jabatan fungsional peneliti nonmanajerial.</li><li><strong>B</strong>: Pencatatan administrasi pembukuan kas merupakan fungsi tugas jabatan fungsional atau pelaksana.</li><li><strong>C</strong>: Penyelidikan tindak pidana kejahatan umum merupakan mandat aparat penegak hukum kepolisian.</li><li><strong>E</strong>: Pemberian nasihat politik partisan dilarang keras bagi pejabat aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan Manajerial adalah sekelompok jabatan • memiliki fungsi memimpin unit organisasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kelompok Jabatan Manajerial dalam tata kelola ASN terdiri atas...", "options": ["Jabatan Keahlian, Jabatan Keterampilan, dan Mahir", "Jabatan Ahli Utama, Ahli Madya, dan Ahli Muda", "Jabatan Pembina, Penata Tingkat Satu, dan Pengatur", "Jabatan Pelaksana Tugas, Staf Ahli, dan Konsultan", "Jabatan Pimpinan Tinggi, Administrator, dan Pengawas"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Susunan Kelompok Jabatan Manajerial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan manajerial</strong> terdiri atas tiga jenjang: <strong>Jabatan Pimpinan Tinggi (JPT)</strong>, <strong>Jabatan Administrator</strong>, dan <strong>Jabatan Pengawas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan tingkatan jabatan manajerial yang tepat sesuai undang-undang. Ketiga posisi tersebut menjalankan fungsi kepemimpinan struktural dari level puncak hingga operasional pengawasan. Ketentuan regulasi membagi jabatan manajerial menjadi JPT, administrator, dan pengawas. Oleh karena itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Jabatan Pimpinan Tinggi, Administrator, dan Pengawas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keahlian dan keterampilan merupakan kategori rumpun jabatan fungsional nonmanajerial.</li><li><strong>B</strong>: Ahli utama, madya, dan muda adalah jenjang keahlian jabatan fungsional teknis.</li><li><strong>C</strong>: Pembina, penata, dan pengatur merupakan nama pangkat golongan ruang PNS.</li><li><strong>D</strong>: Pelaksana tugas dan konsultan bukan nama kelompok jenjang jabatan manajerial resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan manajerial terdiri atas • Jabatan Pimpinan Tinggi • Jabatan Administrator</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jabatan Pimpinan Tinggi (JPT) terbagi ke dalam tiga tingkatan jenjang yaitu...", "options": ["JPT Utama, JPT Madya, dan JPT Pratama", "JPT Pusat, JPT Provinsi, dan JPT Kabupaten", "JPT Keahlian, JPT Keterampilan, dan JPT Khusus", "JPT Senior, JPT Menengah, dan JPT Pertama", "JPT Eselon Satu, JPT Eselon Dua, dan JPT Tiga"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Tingkatan Jabatan Pimpinan Tinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan Pimpinan Tinggi</strong> terdiri atas: <strong>jabatan pimpinan tinggi utama</strong>, <strong>jabatan pimpinan tinggi madya</strong>, dan <strong>jabatan pimpinan tinggi pratama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan pembagian tingkatan JPT yang tepat secara normatif. Struktur pucuk pimpinan birokrasi tersusun secara hierarkis mulai dari level pratama (eselon II), madya (eselon I), hingga utama. Ketentuan hukum menegaskan jenjang JPT mencakup utama, madya, dan pratama. Dengan demikian, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. JPT Utama, JPT Madya, dan JPT Pratama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pusat dan daerah merupakan wilayah yurisdiksi kerja instansi dan bukan nama jenjang JPT.</li><li><strong>C</strong>: Keahlian dan keterampilan adalah pembagian jenis jabatan fungsional.</li><li><strong>D</strong>: Senior dan menengah bukan penamaan resmi tingkatan jabatan pimpinan tinggi birokrasi.</li><li><strong>E</strong>: Penamaan eselon satu dan dua merupakan istilah sistem lama yang disetarakan ke JPT.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>JPT Utama, JPT Madya, dan JPT Pratama • Jabatan Pimpinan Tinggi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Tanggung jawab utama yang dibebankan kepada pejabat dalam Jabatan Pimpinan Tinggi adalah...", "options": ["Mencatat arsip surat masuk dan keluar di loket unit", "Memimpin unit organisasi dan memotivasi Pegawai ASN", "Melakukan pemeriksaan fisik sarana gedung kantor", "Menjaga keamanan gerbang masuk kantor pemerintahan", "Mengantar dokumen dinas ke instansi kementerian lain"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Jabatan Pimpinan Tinggi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pejabat dalam <strong>Jabatan pimpinan tinggi</strong> bertanggung jawab <strong>memimpin unit organisasi dan memotivasi Pegawai ASN</strong> pada unit organisasi tersebut.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan ruang lingkup tanggung jawab strategis pimpinan tinggi yang tepat. Figur JPT dituntut memberikan arah haluan kepemimpinan sekaligus menginspirasi kinerja seluruh staf di bawahnya. Regulasi menegaskan JPT memimpin unit dan memotivasi pegawai organisasi. Oleh karena itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Memimpin unit organisasi dan memotivasi Pegawai ASN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengelolaan agenda persuratan rutin merupakan tugas fungsional pranata administrasi umum.</li><li><strong>C</strong>: Pemeriksaan teknis gedung merupakan ruang lingkup teknis jabatan pengelola sarana prasarana.</li><li><strong>D</strong>: Pengamanan fisik gerbang kantor menjadi tanggung jawab petugas satuan pengamanan.</li><li><strong>E</strong>: Pengantaran surat dinas merupakan tugas bantuan operasional petugas ekspedisi kantor.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan manajerial tingkat tinggi • pengembangan Pegawai ASN</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pejabat dalam Jabatan Administrator bertanggung jawab memimpin pelaksanaan seluruh kegiatan...", "options": ["Penyelidikan perkara pelanggaran pidana korupsi", "Diplomasi perdagangan bilateral di forum internasional", "Pelayanan publik, administrasi pemerintahan, dan pembangunan", "Audit investigasi atas kebocoran rahasia militer", "Penyusunan doktrin pertahanan teritorial matra udara"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Jabatan Administrator</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan administrator</strong> bertanggung jawab <strong>memimpin pelaksanaan seluruh kegiatan pelayanan publik serta administrasi pemerintahan dan pembangunan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan lingkup tanggung jawab manajerial jenjang administrator yang tepat. Administrator (setara eselon III) bertindak sebagai penggerak operasional pelayanan masyarakat dan agenda pembangunan instansi. Aturan menyatakan administrator memimpin kegiatan pelayanan publik, administrasi, dan pembangunan. Dengan demikian, opsi C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Pelayanan publik, administrasi pemerintahan, dan pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyelidikan perkara pidana khusus merupakan fungsi aparat kejaksaan dan penegak hukum.</li><li><strong>B</strong>: Negosiasi hubungan dagang luar negeri menjadi wewenang korps diplomatik kementerian terkait.</li><li><strong>D</strong>: Audit investigasi militer merupakan tugas inspektorat jenderal kementerian pertahanan.</li><li><strong>E</strong>: Perumusan doktrin matra tempur berada pada domain komando pertahanan markas besar militer.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan Administrator • pelayanan publik dan administrasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Tanggung jawab utama yang diemban oleh pejabat dalam Jabatan Pengawas adalah...", "options": ["Menetapkan standar baku pengujian laboratorium forensik", "Mengadili sengketa perselisihan hubungan industrial", "Menandatangani nota pinjaman keuangan antar-negara", "Mengendalikan pelaksanaan kegiatan oleh pejabat pelaksana", "Memimpin sidang kabinet terbatas bersama para menteri"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Jabatan Pengawas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pejabat dalam <strong>Jabatan pengawas</strong> bertanggung jawab <strong>mengendalikan pelaksanaan kegiatan yang dilakukan oleh pejabat pelaksana</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan fungsi pengawasan lini pertama (*first-line supervisor*) yang tepat. Pengawas (setara eselon IV) bertugas mengontrol langsung mutu dan ketepatan eksekusi kerja staf pelaksana di lapangannya. Regulasi merumuskan bahwa tugas jabatan pengawas adalah mengendalikan kegiatan pejabat pelaksana. Oleh karena itu, opsi D adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>D. Mengendalikan pelaksanaan kegiatan oleh pejabat pelaksana</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penetapan standar ilmiah forensik merupakan tugas jabatan fungsional ahli forensik.</li><li><strong>B</strong>: Penyelesaian perselisihan ketenagakerjaan menjadi kewenangan hakim pengadilan industrial.</li><li><strong>C</strong>: Penandatanganan utang luar negeri dilakukan oleh menteri keuangan atas mandat presiden.</li><li><strong>E</strong>: Memimpin rapat kabinet pemerintahan adalah wewenang konstitusional presiden atau wapres.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan Pengawas • Jabatan Manajerial tingkat dasar</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Seorang Direktur Jenderal memimpin rapat koordinasi penetapan target output tahunan satker. Tindakan ini menjamin...", "options": ["Kenaikan pangkat otomatis tanpa penilaian kinerja tahunan", "Pemberian dispensasi jam kerja bagi staf unit pusat", "Penundaan pelaporan keuangan kepada auditor eksternal", "Pengurangan frekuensi audit operasional inspektorat jenderal", "Pencapaian tujuan dan sasaran unit organisasi instansi"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pelaksanaan Tanggung Jawab JPT</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tanggung jawab Jabatan Pimpinan Tinggi dilaksanakan untuk menjamin <strong>pencapaian tujuan dan sasaran unit organisasi</strong> instansi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan tujuan akhir dari pelaksanaan wewenang pimpinan tinggi yang tepat. Arahan strategis JPT memastikan seluruh program kerja satker terarah tepat sasaran sesuai visi kementerian. Undang-undang menegaskan kepemimpinan JPT dilaksanakan guna menjamin pencapaian tujuan dan sasaran unit organisasi. Dengan demikian, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Pencapaian tujuan dan sasaran unit organisasi instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kenaikan pangkat aparatur sipil negara wajib didasarkan pada prestasi kinerja dan kompetensi.</li><li><strong>B</strong>: Ketentuan kehadiran kerja berlaku mengikat bagi seluruh jajaran tanpa hak dispensasi sepihak.</li><li><strong>C</strong>: Kewajiban pelaporan pertanggungjawaban keuangan negara terikat tenggat regulasi perbendaharaan.</li><li><strong>D</strong>: Pengawasan akuntabilitas tata kelola oleh inspektorat jenderal berjalan independen dan periodik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mencapai tujuan organisasi • Jabatan Manajerial</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Unsur yang wajib dikelola dan diawasi oleh pejabat manajerial dalam rangka mencapai tujuan organisasi adalah...", "options": ["Kinerja dan akuntabilitas pelaksanaan tugas para pegawai", "Pilihan politik pribadi aparatur pada pemilihan kepala daerah", "Aktivitas transaksi keuangan perbankan privat seluruh staf", "Kehidupan rumah tangga pegawai di luar kedinasan kantor", "Pilihan investasi saham pribadi bawahan di pasar modal"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fokus Pengawasan Jabatan Manajerial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pejabat manajerial memiliki tanggung jawab dalam <strong>mengelola dan mengawasi kinerja pegawai</strong> dalam rangka mencapai tujuan organisasi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan objek pengawasan manajerial profesional yang tepat di lingkungan kerja. Fokus manajemen diarahkan pada pemenuhan target kerja, kedisiplinan, dan mutu pelayanan kedinasan. Regulasi menyatakan pejabat manajerial mengelola serta mengawasi kinerja pegawai. Oleh karena itu, opsi A merupakan pilihan yang benar.</p><p>Jawaban tepat: <strong>A. Kinerja dan akuntabilitas pelaksanaan tugas para pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pilihan politik di bilik suara merupakan hak privat asalkan pegawai menjaga netralitas dinas.</li><li><strong>C</strong>: Rekening privat pegawai dilindungi kerahasiaan perbankan dan bukan ranah pengawasan harian.</li><li><strong>D</strong>: Ranah domestik privat keluarga pegawai di luar lingkup pengawasan manajemen profesional kerja.</li><li><strong>E</strong>: Kepemilikan aset wajar merupakan hak pribadi sejauh mematuhi pelaporan LHKPN/LHKASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan Manajerial adalah sekelompok jabatan • mengelola dan mengawasi kinerja pegawai</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C4 - Analisis", "question": "Jenjang struktural yang menjadi tingkatan terbawah dalam kelompok Jabatan Manajerial adalah...", "options": ["Jabatan Pelaksana Tata Usaha", "Jabatan Pengawas Operasional", "Jabatan Fungsional Terampil", "Jabatan Fungsional Pertama", "Jabatan Pranata Administrasi"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tingkatan Terbawah dalam Jabatan Manajerial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kelompok jabatan manajerial hanya mencakup tiga jenjang: JPT, Administrator, dan jenjang terbawahnya adalah <strong>Jabatan Pengawas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan analisis posisi manajerial lini pertama yang tepat. Jabatan Pengawas adalah batas akhir kepemimpinan manajerial sebelum memasuki kelompok jabatan pelaksana nonmanajerial. Ketentuan hierarki menegaskan jabatan pengawas berada di bawah administrator. Dengan demikian, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Jabatan Pengawas Operasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jabatan Pelaksana bukan bagian dari kelompok jabatan manajerial melainkan nonmanajerial.</li><li><strong>C</strong>: Jabatan fungsional terampil merupakan jenjang keterampilan pada rumpun nonmanajerial.</li><li><strong>D</strong>: Jabatan fungsional ahli pertama berstatus jabatan nonmanajerial berbasis keahlian profesi.</li><li><strong>E</strong>: Pranata administrasi berstatus jabatan pelaksana atau fungsional nonmanajerial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan manajerial terdiri atas • Jabatan Pengawas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Untuk dapat diangkat dalam jabatan administrasi, PNS wajib memenuhi tiga jenis kompetensi yaitu...", "options": ["Kompetensi Finansial, Keberanian Berisiko, dan Komersial", "Kompetensi Diplomasi, Hukum Pidana, dan Intelijen", "Kompetensi Vokasi, Desain Grafis, dan Pemasaran", "Kompetensi Teknis, Manajerial, dan Sosial Kultural", "Kompetensi Teritorial, Strategi Tempur, dan Militer"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Standar Kompetensi Jabatan Administrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Syarat pengangkatan dalam jabatan administrasi mewajibkan pemenuhan: <strong>Kompetensi Teknis</strong>, <strong>Kompetensi Manajerial</strong>, dan <strong>Kompetensi Sosial Kultural</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan triad standar kompetensi aparatur yang tepat menurut regulasi manajemen ASN. Ketiga rumpun kompetensi ini menjamin penguasaan keahlian teknis tugas, kapabilitas kepemimpinan manajerial, serta kearifan berinteraksi majemuk. Ketentuan aturan mensyaratkan kompetensi teknis, manajerial, dan sosial kultural. Dengan demikian, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Kompetensi Teknis, Manajerial, dan Sosial Kultural</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kompetensi komersial dan risiko finansial merupakan standar korporasi swasta nonpemerintah.</li><li><strong>B</strong>: Keahlian hukum pidana dan intelijen adalah kompetensi spesifik aparat penegak hukum khusus.</li><li><strong>C</strong>: Desain grafis dan pemasaran produk merupakan subkategori keahlian industri kreatif.</li><li><strong>E</strong>: Strategi tempur teritorial adalah kompetensi prajurit militer angkatan bersenjata.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memiliki Kompetensi Teknis • Kompetensi Manajerial, dan Kompetensi Sosial Kultural</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kompetensi Teknis PNS diukur dari tingkat dan spesialisasi pendidikan, pengalaman bekerja teknis, serta...", "options": ["Jumlah sertifikat keikutsertaan ormas kedaerahan", "Frekuensi kehadiran pada acara keagamaan nasional", "Hasil perolehan suara pemilihan pimpinan daerah", "Tingkat kepemilikan aset properti dan kendaraan dinas", "Pelatihan teknis fungsional kedinasan yang diikuti"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Indikator Pengukuran Kompetensi Teknis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kompetensi Teknis</strong> diukur dari <strong>tingkat dan spesialisasi pendidikan</strong>, <strong>pelatihan teknis fungsional</strong>, dan <strong>pengalaman bekerja secara teknis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E melengkapi indikator ukur kompetensi teknis secara tepat. Pendidikan kejuruan dan jam terbang pelatihan fungsional mencerminkan kesiapan mengeksekusi tugas substansial unit. Peraturan kepegawaian merumuskan kompetensi teknis dinilai dari pendidikan, pelatihan fungsional, dan pengalaman teknis. Oleh karena itu, opsi E merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Pelatihan teknis fungsional kedinasan yang diikuti</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keikutsertaan dalam ormas kedaerahan bukan merupakan ukuran kompetensi profesional kerja.</li><li><strong>B</strong>: Ibadah personal dilindungi konstitusi dan bukan indikator teknis keahlian jabatan.</li><li><strong>C</strong>: Hasil pemilihan politik tidak boleh memengaruhi penilaian kompetensi meritokrasi.</li><li><strong>D</strong>: Kepemilikan aset pribadi tidak mencerminkan tingkat kompetensi atau keterampilan teknis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kompetensi Teknis diukur dari • tingkat dan spesialisasi pendidikan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Indikator yang digunakan untuk mengukur Kompetensi Manajerial aparatur sipil negara mencakup...", "options": ["Pendidikan, pelatihan struktural, dan kepemimpinan", "Bakat kesenian tradisional dan kemahiran musik", "Keahlian mekanik reparasi perangkat keras server", "Daftar publikasi artikel jurnal internasional terindeks", "Kapasitas fisik ketahanan lari jarak jauh maraton"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Indikator Pengukuran Kompetensi Manajerial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kompetensi Manajerial</strong> diukur dari <strong>tingkat pendidikan</strong>, <strong>pelatihan struktural atau manajemen</strong>, dan <strong>pengalaman kepemimpinan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan instrumen penilaian kapasitas kepemimpinan manajerial yang tepat. Penguasaan tata kelola organisasi dinilai dari rekam jejak diklat struktural (seperti PKN/PKA/PKP) dan jam terbang memimpin tim kerja. Aturan menetapkan kompetensi manajerial diukur dari pendidikan, pelatihan manajemen, dan kepemimpinan. Dengan demikian, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>A. Pendidikan, pelatihan struktural, dan kepemimpinan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kemahiran seni merupakan minat rekreasi pribadi dan bukan indikator kapabilitas manajerial.</li><li><strong>C</strong>: Keterampilan perangkat keras komputer masuk kategori kompetensi teknis teknologi informasi.</li><li><strong>D</strong>: Publikasi jurnal ilmiah menjadi indikator kompetensi jabatan fungsional dosen atau peneliti.</li><li><strong>E</strong>: Kebugaran fisik prajurit bukan tolok ukur kompetensi kepemimpinan manajerial birokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kompetensi Manajerial diukur dari • pelatihan struktural atau manajemen</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kompetensi Sosial Kultural dinilai dari pengalaman kerja aparatur yang berkaitan dengan...", "options": ["Pengujian kepatuhan transaksi perbankan komersial", "Masyarakat majemuk dalam agama, suku, dan budaya", "Koleksi benda pusaka peninggalan kerajaan kuno", "Penyusunan kode pemrograman aplikasi berbasis awan", "Negosiasi sengketa batas wilayah maritim terluar"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengukuran Kompetensi Sosial Kultural</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kompetensi Sosial Kultural</strong> diukur dari <strong>pengalaman kerja berkaitan dengan masyarakat majemuk dalam hal agama, suku, dan budaya</strong> sehingga memiliki wawasan kebangsaan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan indikator keberhasilan interaksi sosial kultural yang tepat bagi aparatur. ASN sebagai perekat persatuan bangsa wajib piawai melayani warga negara dengan latar belakang yang beragam tanpa prasangka etnis atau sektarian. Regulasi menegaskan kompetensi sosial kultural dinilai dari pengalaman berinteraksi dengan masyarakat majemuk. Oleh karena itu, opsi B adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>B. Masyarakat majemuk dalam agama, suku, dan budaya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemeriksaan perbankan merupakan kompetensi teknis pengawas keuangan dan perbendaharaan.</li><li><strong>C</strong>: Konservasi benda bersejarah merupakan ranah teknis keahlian kurator kepurbakalaan.</li><li><strong>D</strong>: Pengembangan perangkat lunak termasuk domain kompetensi teknis pranata komputer.</li><li><strong>E</strong>: Batas maritim merupakan ruang lingkup diplomasi geopolitik kementerian luar negeri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kompetensi Sosial Kultural diukur dari • pengalaman kerja berkaitan dengan masyarakat majemuk</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Seorang PNS administrator menempuh tugas belajar doktoral selama 24 bulan. Sesuai PP 11/2017 jo PP 17/2020, konsekuensi terhadap jabatannya adalah...", "options": ["Tetap memangku jabatan dengan tunjangan penuh", "Dipindahkan secara otomatis menjadi staf ahli menteri", "Diberhentikan dari jabatan administrasi yang diemban", "Diberikan kenaikan pangkat istimewa dua tingkat", "Diberhentikan statusnya sebagai Pegawai Negeri Sipil"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemberhentian Jabatan karena Tugas Belajar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS <strong>diberhentikan dari jabatan administrasi</strong> apabila <strong>menjalani tugas belajar lebih dari 6 (enam) bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan sanksi administratif pelepasan jabatan yang tepat demi efektivitas satker. Tugas belajar jangka panjang menuntut fokus akademik penuh sehingga posisi kepemimpinan harus diserahkan kepada pejabat pengganti. Ketentuan PP 11/2017 jo PP 17/2020 menyatakan PNS yang tugas belajar > 6 bulan diberhentikan dari jabatan administrasinya. Dengan demikian, opsi C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Diberhentikan dari jabatan administrasi yang diemban</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Memangku jabatan aktif saat menjalani studi purna waktu melanggar efektivitas manajemen dinas.</li><li><strong>B</strong>: Penugasan staf ahli menteri memiliki mekanisme seleksi tersendiri dan bukan tempat penampungan studi.</li><li><strong>D</strong>: Kenaikan pangkat tugas belajar diatur tersendiri sesuai batas ijazah dan bukan otomatis istimewa.</li><li><strong>E</strong>: Pemberhentian hanya berlaku pada jabatannya, sedangkan status sebagai PNS tetap melekat utuh.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS dapat diberhentikan dari jabatan administrasi • menjalani tugas belajar lebih dari 6 (enam) bulan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C4 - Analisis", "question": "Berikut ini merupakan sebab seorang PNS diberhentikan dari jabatan administrasi, KECUALI...", "options": ["Mengundurkan diri dari jabatan administrasi", "Diberhentikan sementara statusnya sebagai PNS", "Menjalani cuti di luar tanggungan negara", "Menjalani cuti tahunan selama dua belas hari kerja", "Ditugaskan secara penuh di luar jabatan administrasi"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sebab Pemberhentian dari Jabatan Administrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS diberhentikan dari jabatan administrasi apabila: mengundurkan diri, diberhentikan sementara, cuti di luar tanggungan negara, tugas belajar > 6 bulan, ditugaskan penuh di luar jabatan, atau tidak memenuhi syarat jabatan. <strong>Cuti tahunan reguler bukan alasan pemberhentian</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan pengecualian yang tepat atas alasan pemberhentian jabatan. Penggunaan hak cuti tahunan adalah hak normatif aparatur yang tidak menggugurkan status jabatan yang sedang disandangnya. Regulasi tidak mencantumkan cuti tahunan sebagai alasan pemberhentian jabatan administrasi. Oleh karena itu, opsi D adalah opsi pengecualian yang benar.</p><p>Jawaban tepat: <strong>D. Menjalani cuti tahunan selama dua belas hari kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengundurkan diri sah merupakan sebab sah pemberhentian dari jabatan administrasi.</li><li><strong>B</strong>: Pemberhentian sementara sebagai PNS berakibat langsung pelepasan jabatan yang diemban.</li><li><strong>C</strong>: Menjalani cuti di luar tanggungan negara mewajibkan pemberhentian dari jabatan dinas.</li><li><strong>E</strong>: Penugasan penuh di luar jabatan administrasi menjadi alasan yuridis pelepasan jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diberhentikan dari jabatan administrasi apabila • cuti di luar tanggungan negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kelompok jabatan dalam ASN yang mengutamakan kompetensi teknis dan tidak memiliki tanggung jawab langsung memimpin pegawai disebut...", "options": ["Jabatan Administrator Utama Wilayah", "Jabatan Pimpinan Tinggi Madya Pusat", "Jabatan Pengawas Pelayanan Operasional", "Jabatan Pelaksana Tugas Eselon Daerah", "Jabatan Nonmanajerial Aparatur Negara"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Jabatan Nonmanajerial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan nonmanajerial</strong> adalah sekelompok jabatan yang <strong>mengutamakan kompetensi yang bersifat teknis</strong> sesuai bidangnya dan <strong>tidak memiliki tanggung jawab langsung dalam mengelola dan mengawasi kinerja pegawai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan definisi kelompok jabatan nonmanajerial yang tepat. Kelompok ini fokus pada pelayanan teknis dan profesionalisme keahlian tanpa beban manajerial struktural. Sesuai ketentuan, jabatan yang mengutamakan kompetensi teknis tanpa memimpin bawahan disebut jabatan nonmanajerial. Dengan demikian, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Jabatan Nonmanajerial Aparatur Negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jabatan administrator berstatus manajerial dengan tanggung jawab memimpin unit kerja.</li><li><strong>B</strong>: JPT Madya merupakan kelompok manajerial puncak yang memimpin eselon tingkat satu.</li><li><strong>C</strong>: Jabatan pengawas merupakan posisi manajerial yang mengendalikan pejabat pelaksana.</li><li><strong>D</strong>: Pelaksana tugas eselon merupakan istilah status sementara dan bukan kelompok resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan nonmanajerial adalah sekelompok jabatan • mengutamakan kompetensi yang bersifat teknis</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Bagi Jabatan Administrator, tanggung jawab manajerial dilaksanakan guna menjamin seluruh kegiatan berjalan efisien serta terselenggaranya...", "options": ["Peningkatan kinerja pegawai secara berkesinambungan", "Penghapusan seluruh mekanisme laporan pertanggungjawaban", "Pemberian fasilitas mobil dinas mewah bagi staf baru", "Pengalihan seluruh aset kantor kepada yayasan sosial", "Pengurangan hari kerja operasional kantor pelayanan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Peningkatan Kinerja Administrator</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tanggung jawab Jabatan Administrator dilaksanakan untuk menjamin perencanaan efisien sesuai SOP serta <strong>terselenggaranya peningkatan kinerja secara berkesinambungan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan sasaran keluaran kepemimpinan administrator yang tepat. Peningkatan produktivitas yang berkesinambungan menjadi tolok ukur kesuksesan pengelolaan unit administrasi. Regulasi menegaskan kepemimpinan administrator menjamin perbaikan kinerja secara berkesinambungan. Oleh karena itu, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>A. Peningkatan kinerja pegawai secara berkesinambungan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penghapusan laporan pertanggungjawaban bertentangan mutlak dengan prinsip akuntabilitas kinerja.</li><li><strong>C</strong>: Fasilitas dinas diatur secara ketat berdasarkan pagu anggaran dan standar barang kementerian.</li><li><strong>D</strong>: Pemindahtanganan barang milik negara wajib melalui mekanisme persetujuan pengelola barang.</li><li><strong>E</strong>: Penetapan jam kerja instansi pemerintah terikat pada peraturan presiden dan regulasi menteri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>terselenggaranya peningkatan kinerja secara berkesinambungan • bagi Jabatan administrator</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Tanggung jawab yang wajib dipenuhi oleh pegawai yang menduduki Jabatan Pelaksana adalah memastikan pelaksanaan...", "options": ["Audit investigasi atas pengelolaan utang luar negeri", "Kegiatan kerja sesuai standar operasional prosedur", "Perumusan kebijakan fiskal makro nasional tahunan", "Pengangkatan pejabat eselon di lingkungan kementerian", "Penetapan tarif cukai hasil tembakau dan alkohol"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Jabatan Pelaksana</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pejabat dalam <strong>Jabatan pelaksana</strong> bertanggung jawab menjamin pelaksanaan <strong>kegiatan sesuai dengan standar operasional prosedur (SOP)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan ruang lingkup akuntabilitas kerja pejabat pelaksana yang tepat. Sebagai garda terdepan eksekusi operasional, pelaksana wajib patuh pada alur kerja SOP yang telah dibakukan. Regulasi menyatakan tanggung jawab pelaksana adalah menjamin kegiatan sesuai dengan SOP. Dengan demikian, opsi B adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Kegiatan kerja sesuai standar operasional prosedur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Audit investigasi utang negara merupakan mandat pemeriksa BPK atau auditor BPKP.</li><li><strong>C</strong>: Perumusan kebijakan makro ekonomi merupakan fungsi strategis badan kebijakan fiskal.</li><li><strong>D</strong>: Pengangkatan pejabat struktural menjadi wewenang presiden atau pejabat pembina kepegawaian.</li><li><strong>E</strong>: Penetapan tarif cukai nasional diputuskan oleh menteri keuangan melalui peraturan menteri.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kegiatan sesuai dengan standar operasional prosedur • bagi Jabatan pelaksana</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Sekelompok jabatan dalam birokrasi yang berisi fungsi pelayanan berbasis keahlian dan keterampilan tertentu adalah...", "options": ["Jabatan Struktural Wilayah Komando", "Jabatan Politis Perwakilan Rakyat", "Jabatan Fungsional Aparatur Sipil", "Jabatan Honorer Pembantu Kepegawaian", "Jabatan Ad-Hoc Penasehat Kementerian"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Jabatan Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan Fungsional (JF)</strong> adalah sekelompok jabatan yang berisi fungsi dan tugas berkaitan dengan pelayanan fungsional yang <strong>berdasarkan pada keahlian dan keterampilan tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan definisi yuridis jabatan fungsional yang tepat. JF mewadahi para profesional spesialis dalam memberikan kontribusi kepakaran langsung kepada organisasi. Sesuai rumusan konsep, jabatan pelayanan berbasis keahlian dan keterampilan tertentu adalah Jabatan Fungsional. Oleh karena itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Jabatan Fungsional Aparatur Sipil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jabatan komando struktural merupakan terminologi hierarki satuan militer atau kepolisian.</li><li><strong>B</strong>: Jabatan politis perwakilan merupakan posisi pejabat publik hasil pemilihan umum legislatif.</li><li><strong>D</strong>: Jabatan honorer bukan bagian dari nomenklatur jabatan aparatur sipil negara modern.</li><li><strong>E</strong>: Penasehat ad-hoc merupakan staf temporer nonkarier di luar kelompok jabatan fungsional ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan Fungsional (JF) adalah sekelompok jabatan • berdasarkan pada keahlian dan keterampilan tertentu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jenjang Jabatan Fungsional Keahlian dari tingkatan tertinggi ke terendah secara berurutan adalah...", "options": ["Penyelia, Mahir, Terampil, dan Pemula", "Utama, Madya, Pratama, dan Administrator", "Pembina Utama, Penata Muda, Pengatur, dan Juru", "Senior, Madya, Yunior, dan Asisten Peneliti", "Ahli Utama, Ahli Madya, Ahli Muda, dan Ahli Pertama"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jenjang Jabatan Fungsional Keahlian</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan fungsional keahlian</strong> terdiri atas empat jenjang: <strong>Ahli utama</strong>, <strong>Ahli madya</strong>, <strong>Ahli muda</strong>, dan <strong>Ahli pertama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan urutan hierarki jenjang JF keahlian yang tepat dari tingkat teratas hingga permulaan. Klasifikasi keahlian mensyaratkan kualifikasi pendidikan minimal sarjana dengan spesialisasi profesi. Regulasi menegaskan jenjang keahlian terdiri atas Ahli utama, madya, muda, dan pertama. Oleh karena itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Ahli Utama, Ahli Madya, Ahli Muda, dan Ahli Pertama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyelia, mahir, terampil, dan pemula merupakan hierarki pada jabatan fungsional keterampilan.</li><li><strong>B</strong>: Utama, madya, pratama merupakan pembagian tingkatan jabatan pimpinan tinggi struktural.</li><li><strong>C</strong>: Pembina, penata, pengatur, dan juru adalah nama jenjang golongan kepangkatan PNS.</li><li><strong>D</strong>: Senior dan yunior bukan penamaan standar jenjang jabatan fungsional aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan fungsional keahlian terdiri atas • Ahli utama, Ahli madya, Ahli muda dan Ahli pertama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jenjang tingkatan pada Jabatan Fungsional Keterampilan secara sah terdiri atas...", "options": ["Penyelia, Mahir, Terampil, dan Pemula", "Ahli Utama, Ahli Madya, dan Ahli Pertama", "Administrator, Pengawas, dan Pelaksana Harian", "Kepala Seksi, Kasubbag, dan Staf Administrasi", "Pengatur Muda, Pengatur, dan Penata Tingkat Satu"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jenjang Jabatan Fungsional Keterampilan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan fungsional keterampilan</strong> terdiri atas: <strong>Penyelia</strong>, <strong>Mahir</strong>, <strong>Terampil</strong>, dan <strong>Pemula</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan tingkatan resmi pada kelompok jabatan fungsional berbasis vokasi keterampilan. Posisi penyelia merupakan jenjang puncak fungsional keterampilan yang membimbing jenjang di bawahnya. Sesuai ketentuan, jenjang keterampilan mencakup penyelia, mahir, terampil, dan pemula. Dengan demikian, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Penyelia, Mahir, Terampil, dan Pemula</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Ahli utama, madya, dan pertama merupakan penamaan pada jabatan fungsional keahlian.</li><li><strong>C</strong>: Administrator dan pengawas merupakan kelompok jabatan manajerial pimpinan unit.</li><li><strong>D</strong>: Kepala seksi dan kasubbag adalah sebutan jabatan struktural pengawas dalam tata kerja.</li><li><strong>E</strong>: Pengatur muda dan penata merupakan nama pangkat golongan ruang kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan fungsional keterampilan terdiri atas • Penyelia, Mahir, Terampil, dan Pemula</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kriteria penilaian keluaran kegiatan pada Jabatan Fungsional diukur dengan satuan nilai atau akumulasi nilai butir dalam bentuk...", "options": ["Persentase dividen saham perusahaan", "Angka kredit butir kegiatan tugas", "Indeks kepuasan politik dewan perwakilan", "Nominal bonus komisi transaksi penjualan", "Nilai tukar mata uang asing kedinasan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Alat Ukur Kinerja Jabatan Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu kriteria utama Jabatan Fungsional adalah <strong>kegiatannya dapat diukur dengan satuan nilai atau akumulasi nilai butir-butir kegiatan dalam bentuk angka kredit</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan instrumen akuntabilitas kinerja fungsional yang tepat. Angka kredit merefleksikan pencapaian butir-butir output profesi yang menjadi syarat kenaikan jabatan dan pangkat. Ketentuan regulasi menetapkan capaian fungsional diukur melalui satuan angka kredit. Oleh karena itu, opsi B merupakan jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Angka kredit butir kegiatan tugas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dividen saham merupakan imbal hasil investasi finansial pada entitas bisnis perseroan.</li><li><strong>C</strong>: Kepuasan politik lembaga legislatif bukan tolok ukur teknis kinerja jabatan fungsional.</li><li><strong>D</strong>: Komisi penjualan merupakan skema remunerasi tenaga pemasar komersial sektor swasta.</li><li><strong>E</strong>: Kurs valuta asing merupakan indikator moneter makro perbankan dan bukan kinerja ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kegiatannya dapat diukur dengan satuan nilai • angka kredit</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pengangkatan PNS ke dalam Jabatan Fungsional dapat dilaksanakan melalui empat mekanisme resmi yaitu...", "options": ["Lelang terbuka, voting staf, warisan jabatan, dan tender", "Penunjukan keluarga, diskresi bupati, undian, dan rotasi", "Pengangkatan pertama, perpindahan jabatan, penyesuaian, dan promosi", "Rekomendasi ormas, kuota daerah, afirmasi politik, dan mutasi", "Permohonan pribadi, seleksi daring, magang sukarela, dan magang"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Empat Mekanisme Pengangkatan Jabatan Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengangkatan ke dalam Jabatan Fungsional dilakukan melalui: <strong>pengangkatan pertama</strong>, <strong>perpindahan dari Jabatan lain</strong>, <strong>penyesuaian (inpassing)</strong>, dan/atau <strong>promosi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan empat pintu masuk karier fungsional yang diatur dalam regulasi kepegawaian. Mekanisme ini menjamin pengangkatan berbasis kompetensi baik bagi CPNS baru, pegawai alih jabatan, maupun pegawai berprestasi. Ketentuan aturan menegaskan empat jalur tersebut. Dengan demikian, opsi C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Pengangkatan pertama, perpindahan jabatan, penyesuaian, dan promosi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mekanisme voting suara dan tender komersial bertentangan dengan asas sistem merit.</li><li><strong>B</strong>: Penunjukan nepotisme keluarga dan sistem undian dilarang mutlak dalam hukum kepegawaian.</li><li><strong>D</strong>: Rekomendasi ormas dan afirmasi partai politik melanggar asas netralitas aparatur.</li><li><strong>E</strong>: Magang sukarela bukan jalur pengangkatan formal jabatan fungsional aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengangkatan PNS ke dalam Jabatan Fungsional • pengangkatan pertama, perpindahan dari Jabatan lain, penyesuaian dan/atau promosi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat fungsional auditor ditugaskan secara penuh menjadi pejabat administrator struktural. Status jabatan fungsionalnya adalah...", "options": ["Merangkap kedua jabatan sekaligus dengan dua tunjangan", "Diberikan cuti besar selama memegang jabatan struktural", "Diberhentikan tetap statusnya sebagai pegawai negeri", "Diberhentikan dari jabatan fungsional yang diduduki", "Ditunda penerbitan surat keputusan pengangkatannya"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemberhentian JF karena Penugasan di Luar Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS <strong>diberhentikan dari Jabatan Fungsional</strong> apabila <strong>ditugaskan secara penuh di luar Jabatan Fungsional</strong> (misalnya diangkat dalam jabatan manajerial).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan konsekuensi larangan rangkap jabatan struktural dan fungsional. Penugasan penuh pada jabatan manajerial menuntut pelepasan jabatan fungsional agar tidak terjadi tumpang tindih fungsi dan tunjangan ganda. Regulasi menetapkan PNS diberhentikan dari JF jika ditugaskan penuh di luar JF. Oleh karena itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Diberhentikan dari jabatan fungsional yang diduduki</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rangkap jabatan struktural dan fungsional dilarang keras dalam manajemen aparatur sipil negara.</li><li><strong>B</strong>: Cuti besar merupakan hak istirahat pegawai dan bukan mekanisme pengalihan jabatan dinas.</li><li><strong>C</strong>: Pemberhentian hanya terjadi pada jabatan fungsionalnya, sedangkan status PNS tetap aktif.</li><li><strong>E</strong>: Penundaan SK tanpa dasar hukum melanggar tertib administrasi ketetapan kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS dapat diberhentikan dari Jabatan Fungsional apabila • ditugaskan secara penuh di luar JF</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "PNS yang telah selesai menjalani tugas belajar lebih dari enam bulan dapat diangkat kembali ke dalam JF asalkan...", "options": ["Mendapat persetujuan lisan dari seluruh rekan sejawat", "Membayar uang administrasi pemulihan formasi dinas", "Menempati kantor cabang di luar pulau tempat domisili", "Menyerahkan sertifikat kelulusan kepada serikat pekerja", "Tersedia lowongan formasi pada jenjang jabatan terakhir"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Pengangkatan Kembali ke Jabatan Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang diberhentikan dari JF karena tugas belajar atau penugasan luar <strong>dapat diangkat kembali sesuai dengan jenjang terakhir apabila tersedia lowongan Jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan syarat utama pemulihan jabatan fungsional yang tepat. Pengangkatan kembali wajib mempertimbangkan ketersediaan peta jabatan dan formasi pada instansi pemerintah. Ketentuan pasal menegaskan pengangkatan kembali dapat dilakukan apabila tersedia lowongan jabatan. Dengan demikian, opsi E adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Tersedia lowongan formasi pada jenjang jabatan terakhir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Persetujuan rekan kerja bukan syarat legal administratif pengangkatan kembali aparatur.</li><li><strong>B</strong>: Proses kepegawaian negara bebas biaya dan pungutan liar merupakan tindak pidana gratifikasi.</li><li><strong>C</strong>: Penempatan kembali disesuaikan dengan kebutuhan formasi dan bukan keharusan pindah pulau.</li><li><strong>D</strong>: Serikat pekerja tidak memiliki wewenang hukum dalam pengangkatan formasi jabatan ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dapat diangkat kembali sesuai dengan jenjang Jabatan Fungsional terakhir • apabila tersedia lowongan Jabatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi kepegawaian, kelompok Jabatan Pelaksana bertanggung jawab dalam memberikan pelayanan serta...", "options": ["Melaksanakan pekerjaan yang bersifat rutin dan sederhana", "Menetapkan formula tarif perpajakan internasional", "Memimpin operasi militer pengamanan perbatasan negara", "Membuat keputusan yudisial atas perkara sengketa pemilu", "Mengoordinasikan perumusan rencana strategis lima menteri"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Pokok Jabatan Pelaksana</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan Pelaksana</strong> bertanggung jawab <strong>memberikan pelayanan dan melaksanakan pekerjaan yang bersifat rutin dan sederhana</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A mendefinisikan ruang lingkup penugasan jabatan pelaksana secara tepat. Kelompok pelaksana (dahulu fungsional umum) menangani eksekusi tugas teknis harian administratif yang telah terstandardisasi. Sesuai rumusan konsep, tanggung jawabnya adalah melaksanakan pekerjaan rutin dan sederhana. Oleh karena itu, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>A. Melaksanakan pekerjaan yang bersifat rutin dan sederhana</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penetapan formula pajak merupakan kompetensi analis kebijakan fiskal tingkat tinggi.</li><li><strong>C</strong>: Komando operasi teritorial perbatasan merupakan wewenang perwira tinggi militer.</li><li><strong>D</strong>: Putusan sengketa hasil pemilihan umum merupakan wewenang hakim Mahkamah Konstitusi.</li><li><strong>E</strong>: Koordinasi lintas kementerian dijalankan oleh menteri koordinator atau pejabat pimpinan tinggi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>b. Jabatan Pelaksana • Bertanggung jawab memberikan pelayanan dan melaksanakan pekerjaan yang bersifat rutin dan sederhana</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Salah satu kriteria khusus yang membedakan pelaksanaan tugas Jabatan Fungsional dengan jabatan struktural adalah...", "options": ["Ketiadaan kewajiban mematuhi jam kerja kedinasan kantor", "Pelaksanaan tugas bersifat mandiri sesuai profesinya", "Bebas dari kewajiban penilaian prestasi kerja tahunan", "Hak imunitas dari sanksi pelanggaran tata tertib dinas", "Wewenang mengabaikan disposisi instruksi pimpinan unit"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sifat Kemandirian Tugas Jabatan Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kriteria Jabatan Fungsional meliputi <strong>pelaksanaan tugas yang bersifat mandiri dalam menjalankan tugas profesinya</strong> dengan tolok ukur standar keahlian.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan prinsip profesionalisme fungsional yang tepat. Pejabat fungsional bekerja secara otonom berdasarkan kode etik profesi dan metodologi keahlian spesifiknya. Regulasi merinci kriteria JF mencakup pelaksanaan tugas yang bersifat mandiri dalam menjalankan profesinya. Dengan demikian, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Pelaksanaan tugas bersifat mandiri sesuai profesinya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pejabat fungsional tetap terikat secara penuh pada ketentuan jam kerja aparatur sipil negara.</li><li><strong>C</strong>: Penilaian kinerja tetap wajib dilakukan setiap periode melalui evaluasi angka kredit/hasil kerja.</li><li><strong>D</strong>: Aparatur sipil negara dalam jabatan apa pun tunduk mutlak pada peraturan disiplin PNS.</li><li><strong>E</strong>: Kemandirian profesi tidak meniadakan hierarki kepatuhan terhadap penugasan sah kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pelaksanaan tugas yang bersifat mandiri • dalam menjalankan tugas profesinya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penguasaan keahlian atau keterampilan tertentu pada Jabatan Fungsional wajib dibuktikan melalui...", "options": ["Rekomendasi tertulis dari pimpinan partai politik", "Bukti pelunasan pajak bumi dan bangunan tempat tinggal", "Sertifikasi dan/atau penilaian kompetensi tertentu", "Kuitansi pembelian perlengkapan dinas secara mandiri", "Daftar riwayat perjalanan wisata liburan ke luar negeri"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bukti Kompetensi Jabatan Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan Fungsional mensyaratkan keahlian atau keterampilan tertentu yang <strong>dibuktikan dengan sertifikasi dan/atau penilaian tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan instrumen verifikasi kompetensi profesi yang sah menurut regulasi. Standar keahlian diuji melalui uji kompetensi resmi dan sertifikat profesi dari instansi pembina. Ketentuan aturan menetapkan pembuktian keahlian melalui sertifikasi atau penilaian kompetensi. Oleh karena itu, opsi C adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Sertifikasi dan/atau penilaian kompetensi tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rekomendasi partai politik dilarang keras dan melanggar prinsip netralitas serta meritokrasi.</li><li><strong>B</strong>: Kepatuhan pajak pribadi merupakan kewajiban warga negara umum dan bukan sertifikasi profesi.</li><li><strong>D</strong>: Pengadaan perlengkapan kantor menjadi tanggung jawab anggaran dinas dan bukan bukti keahlian.</li><li><strong>E</strong>: Riwayat perjalanan pribadi tidak memiliki relevansi dengan kompetensi profesional jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mensyaratkan keahlian atau keterampilan tertentu • dibuktikan dengan sertifikasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C4 - Analisis", "question": "Pemberhentian PNS dari Jabatan Fungsional dapat dijatuhkan oleh pejabat pembina kepegawaian apabila pegawai...", "options": ["Mengajukan usulan perbaikan tata kerja di unit kantor", "Mengikuti pelatihan peningkatan kompetensi kepemimpinan", "Menyelesaikan studi sarjana tepat waktu sesuai jadwal", "Tidak lagi memenuhi persyaratan kualifikasi jabatannya", "Mencapai target kinerja tahunan melebihi ekspektasi unit"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemberhentian karena Tidak Memenuhi Syarat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu sebab normatif pemberhentian PNS dari Jabatan Fungsional adalah <strong>tidak memenuhi persyaratan Jabatan</strong> yang telah ditentukan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan alasan diskualifikasi jabatan yang sah dalam hukum kepegawaian. Jika pejabat fungsional kehilangan standar kualifikasi, kesehatan profesi, atau sertifikasi yang diwajibkan, maka jabatan harus dicopot. Regulasi menegaskan pemberhentian berlaku jika tidak memenuhi persyaratan jabatan. Dengan demikian, opsi D adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>D. Tidak lagi memenuhi persyaratan kualifikasi jabatannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyampaian gagasan inovatif tata kerja merupakan kontribusi positif yang justru didorong.</li><li><strong>B</strong>: Keikutsertaan dalam pelatihan pengembangan kompetensi merupakan hak normatif setiap aparatur.</li><li><strong>C</strong>: Penyelesaian studi sarjana meningkatkan kualifikasi dan bukan dasar pemberhentian jabatan.</li><li><strong>E</strong>: Capaian kinerja tinggi merupakan prestasi kerja yang diapresiasi dengan penghargaan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS dapat diberhentikan dari Jabatan Fungsional apabila • tidak memenuhi persyaratan Jabatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan penyetaraan jabatan PNS, jabatan eselon Ia kepala lembaga pemerintah nonkementerian setara dengan...", "options": ["Jabatan Pimpinan Tinggi Utama", "Jabatan Pimpinan Tinggi Madya", "Jabatan Pimpinan Tinggi Pratama", "Jabatan Administrator Utama", "Jabatan Pengawas Wilayah Pusat"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyetaraan Eselon Ia Kepala LPNK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan eselon Ia kepala lembaga pemerintah nonkementerian disetarakan dengan <strong>jabatan pimpinan tinggi utama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan hasil konversi penyetaraan hierarki kepemimpinan tertinggi yang tepat. Kepala LPNK menduduki puncak jabatan karier ASN pada institusi nonkementerian. Ketentuan aturan menegaskan eselon Ia kepala LPNK setara dengan jabatan pimpinan tinggi utama. Oleh karena itu, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>A. Jabatan Pimpinan Tinggi Utama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Eselon Ia biasa dan eselon Ib di kementerian disetarakan dengan JPT Madya.</li><li><strong>C</strong>: Eselon II (direktur/kepala biro) disetarakan dengan JPT Pratama.</li><li><strong>D</strong>: Jabatan administrator merupakan penyetaraan bagi eselon III dan bukan pimpinan lembaga.</li><li><strong>E</strong>: Jabatan pengawas merupakan penyetaraan bagi eselon IV pengawas lini operasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan eselon Ia kepala lembaga pemerintah nonkementerian • jabatan pimpinan tinggi utama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jabatan eselon Ia (selain kepala LPNK) dan eselon Ib dalam birokrasi kementerian disetarakan dengan...", "options": ["Jabatan Fungsional Ahli Utama", "Jabatan Pimpinan Tinggi Pratama", "Jabatan Pimpinan Tinggi Madya", "Jabatan Administrator Tingkat Satu", "Jabatan Pengawas Eksekutif Madya"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyetaraan Eselon Ia dan Eselon Ib</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan eselon Ia dan eselon Ib setara dengan jabatan pimpinan tinggi madya</strong> (seperti Sekretaris Jenderal, Direktur Jenderal, atau Inspektur Jenderal).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan padanan jabatan struktural pimpinan eselon I kementerian yang tepat. Posisi sekjen, dirjen, dan irjen dikonversikan ke dalam kelas jabatan JPT Madya. Aturan hukum merumuskan eselon Ia dan Ib setara dengan jabatan pimpinan tinggi madya. Dengan demikian, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Jabatan Pimpinan Tinggi Madya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ahli utama merupakan jenjang tertinggi pada kelompok jabatan fungsional profesi.</li><li><strong>B</strong>: JPT Pratama merupakan konversi penyetaraan bagi jabatan eselon II.</li><li><strong>D</strong>: Administrator merupakan konversi bagi jabatan eselon III kepala bidang/bagian.</li><li><strong>E</strong>: Pengawas eksekutif bukan nomenklatur resmi dalam klasifikasi jabatan kepegawaian ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan eselon Ia dan eselon Ib • jabatan pimpinan tinggi madya</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam penyetaraan jabatan kepegawaian, jabatan eselon II (Direktur atau Kepala Kantor Wilayah) setara dengan...", "options": ["Jabatan Administrator Wilayah Khusus", "Jabatan Pengawas Tingkat Pratama", "Jabatan Pimpinan Tinggi Madya Pusat", "Jabatan Fungsional Mahir Pratama", "Jabatan Pimpinan Tinggi Pratama"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyetaraan Jabatan Eselon II</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan eselon II setara dengan jabatan pimpinan tinggi pratama</strong> (seperti Direktur, Kepala Biro, atau Kepala Pusat).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jenjang konversi struktural eselon dua yang tepat. Pejabat setingkat kepala biro atau kakanwil menduduki level JPT Pratama dalam sistem karier terbuka. Ketentuan regulasi menetapkan eselon II setara dengan JPT Pratama. Oleh karena itu, opsi E adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Jabatan Pimpinan Tinggi Pratama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jabatan administrator diperuntukkan bagi pejabat eselon III dan bukan eselon II.</li><li><strong>B</strong>: Jabatan pengawas merupakan padanan konversi bagi pejabat eselon IV.</li><li><strong>C</strong>: JPT Madya adalah tingkatan pimpinan eselon I di atas eselon II.</li><li><strong>D</strong>: Fungsional mahir merupakan jenjang keterampilan dan bukan jabatan pimpinan struktural.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan eselon II setara dengan • jabatan pimpinan tinggi pratama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penyetaraan posisi jabatan untuk pejabat struktural eselon III adalah ke dalam...", "options": ["Jabatan Pimpinan Tinggi Pratama", "Jabatan Administrator", "Jabatan Pengawas Pelayanan", "Jabatan Pelaksana Madya", "Jabatan Fungsional Ahli Muda"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyetaraan Jabatan Eselon III</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan eselon III setara dengan jabatan administrator</strong> (seperti Kepala Bagian, Kepala Bidang, atau Kepala Balai).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan konversi kedudukan hierarki eselon tiga yang tepat. Pejabat administrator bertugas mengoordinasikan pelayanan dan operasional unit eselon III. Regulasi menyebutkan secara lugas bahwa eselon III setara dengan jabatan administrator. Dengan demikian, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Jabatan Administrator</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: JPT Pratama merupakan tingkatan jabatan struktural bagi pejabat eselon II.</li><li><strong>C</strong>: Jabatan pengawas merupakan konversi kedudukan bagi pejabat struktural eselon IV.</li><li><strong>D</strong>: Jabatan pelaksana merupakan posisi nonmanajerial pelaksana kegiatan rutin.</li><li><strong>E</strong>: Fungsional ahli muda adalah jenjang profesi keahlian berbasis angka kredit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan eselon III setara dengan • jabatan administrator</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pejabat struktural eselon IV (Kepala Seksi atau Kepala Subbagian) disetarakan kedudukannya ke dalam...", "options": ["Jabatan Pimpinan Pratama", "Jabatan Administrator Utama", "Jabatan Fungsional Terampil", "Jabatan Pengawas", "Jabatan Pelaksana Harian"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyetaraan Jabatan Eselon IV</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan eselon IV setara dengan jabatan pengawas</strong> (seperti Kepala Subbagian, Kepala Seksi, atau Kepala Subbidang).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan hasil penyetaraan hierarki manajerial eselon empat yang tepat. Pengawas bertindak mengontrol langsung pelaksanaan tugas harian staf pelaksana. Sesuai rumusan regulasi, eselon IV disetarakan dengan jabatan pengawas. Oleh karena itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Jabatan Pengawas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pimpinan pratama setara dengan eselon II pimpinan kantor wilayah atau direktorat.</li><li><strong>B</strong>: Administrator setara dengan eselon III kepala bidang atau kepala bagian.</li><li><strong>C</strong>: Fungsional terampil merupakan jenjang karier profesi keterampilan nonstruktural.</li><li><strong>E</strong>: Pelaksana harian merupakan penugasan temporer (*Plh*) dan bukan nomenklatur jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan eselon IV setara dengan • jabatan pengawas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan ketentuan penyetaraan jabatan ASN, jabatan eselon V dan fungsional umum setara dengan...", "options": ["Jabatan Pelaksana", "Jabatan Pengawas", "Jabatan Administrator", "Jabatan Fungsional Mahir", "Jabatan Fungsional Pertama"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyetaraan Eselon V dan Fungsional Umum</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan eselon V dan fungsional umum setara dengan jabatan pelaksana</strong> yang bertugas melaksanakan pekerjaan rutin operasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan hasil penyetaraan bagi kelompok staf pelaksana teknis yang tepat. Penyetaraan menyederhanakan birokrasi dengan melebur posisi eselon V dan fungsional umum menjadi jabatan pelaksana. Ketentuan perundangan menyatakan eselon V dan fungsional umum setara dengan jabatan pelaksana. Dengan demikian, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>A. Jabatan Pelaksana</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Jabatan pengawas merupakan padanan penyetaraan bagi posisi eselon IV.</li><li><strong>C</strong>: Jabatan administrator merupakan hasil konversi dari posisi eselon III.</li><li><strong>D</strong>: Fungsional mahir merupakan jenjang keterampilan profesi bersertifikasi khusus.</li><li><strong>E</strong>: Fungsional pertama adalah jenjang keahlian bagi lulusan sarjana berkarier spesialis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan eselon V dan fungsional umum • setara dengan jabatan pelaksana</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Regulasi pemerintah yang mengatur penyesuaian jabatan administrasi ke dalam Jabatan Fungsional demi penataan birokrasi adalah...", "options": ["Peraturan Pemerintah Nomor 10 Tahun 1983", "Peraturan Pemerintah Nomor 53 Tahun 2010", "Peraturan Pemerintah Nomor 17 Tahun 2020", "Peraturan Pemerintah Nomor 94 Tahun 2021", "Peraturan Pemerintah Nomor 46 Tahun 2011"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi Penyetaraan ke Jabatan Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada saat <strong>Peraturan Pemerintah Nomor 17 Tahun 2020</strong> mulai berlaku, dalam rangka penataan birokrasi dapat dilakukan penyetaraan jabatan administrasi ke dalam <strong>Jabatan Fungsional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan nomor regulasi pemerintah yang tepat terkait dasar hukum penyetaraan birokrasi. PP ini memperkuat transformasi perampingan struktur hierarkis menuju organisasi yang lincah berbasis fungsional. Ketentuan pasal menegaskan kebijakan penyetaraan jabatan diatur dalam PP Nomor 17 Tahun 2020. Oleh karena itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Peraturan Pemerintah Nomor 17 Tahun 2020</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan regulasi lawas yang mengatur tentang izin perkawinan dan perceraian PNS.</li><li><strong>B</strong>: Merupakan regulasi disiplin pegawai negeri sipil terdahulu sebelum dicabut PP 94/2021.</li><li><strong>D</strong>: Merupakan peraturan pemerintah tentang disiplin PNS dan bukan payung hukum inpassing jabatan.</li><li><strong>E</strong>: Merupakan peraturan pemerintah tentang penilaian kinerja pegawai negeri sipil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Peraturan Pemerintah Nomor 17 Tahun 2020 • penyesuaian jabatan ke dalam Jabatan Fungsional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Mekanisme pengangkatan Pejabat Administrasi ke dalam Jabatan Fungsional yang setara dalam penataan birokrasi disebut...", "options": ["Pengangkatan pertama formasi umum CPNS", "Demosi hukuman penurunan pangkat dinas", "Promosi terbuka lintas instansi daerah", "Pemberhentian dengan hormat atas pensiun", "Penyesuaian atau Inpassing jabatan fungsional"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Mekanisme Penyetaraan Inpassing</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyetaraan jabatan adalah pengangkatan Pejabat Administrasi ke dalam Jabatan Fungsional melalui <strong>Penyesuaian/Inpassing pada jabatan fungsional yang setara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan istilah teknis manajemen kepegawaian yang tepat untuk peralihan jabatan administrasi ke fungsional. Inpassing memungkinkan pejabat eselon beralih fungsi menjadi fungsional ahli madya atau muda tanpa kehilangan kelas jabatan. Ketentuan hukum merumuskan pengangkatan penyetaraan ini melalui mekanisme Penyesuaian/Inpassing. Dengan demikian, opsi E adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Penyesuaian atau Inpassing jabatan fungsional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengangkatan pertama merupakan jalur masuk bagi pelamar yang baru lolos seleksi CPNS.</li><li><strong>B</strong>: Demosi merupakan bentuk hukuman disiplin berat berupa pemindahan jabatan lebih rendah.</li><li><strong>C</strong>: Promosi terbuka merupakan seleksi pengisian jabatan kosong melalui lelang kompetensi.</li><li><strong>D</strong>: Pemberhentian pensiun merupakan pengakhiran masa dinas aparatur karena batas usia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penyetaraan jabatan dimaksud adalah pengangkatan Pejabat Administrasi • melalui Penyesuaian/Inpassing pada jabatan fungsional yang setara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan ketentuan PP 17/2020, pedoman teknis penyetaraan jabatan diatur lebih lanjut dengan...", "options": ["Surat Edaran Lurah setempat", "Peraturan Menteri terkait", "Keputusan Camat wilayah", "Maklumat Kepala Desa binaan", "Nota Dinas Internal Staf"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Aturan Pelaksana Penyetaraan Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketentuan penataan birokrasi menegaskan bahwa <strong>penyetaraan jabatan diatur dengan Peraturan Menteri</strong> (seperti PermenPAN-RB).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan bentuk produk hukum yang dimandatkan untuk mengatur rincian tata cara penyetaraan jabatan. Peraturan Menteri menetapkan standar kompetensi, konversi butir angka kredit, dan formasi inpassing bagi seluruh instansi. Regulasi menyebutkan penyetaraan jabatan diatur dengan Peraturan Menteri. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Peraturan Menteri terkait</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Surat edaran lurah merupakan administrasi tingkat kelurahan dan bukan regulasi nasional.</li><li><strong>C</strong>: Keputusan camat berada pada hierarki tata kelola kecamatan dan tidak berwenang atas ASN.</li><li><strong>D</strong>: Maklumat kepala desa mengatur ketertiban desa dan bukan tata aturan jabatan kepegawaian.</li><li><strong>E</strong>: Nota dinas bersifat korespondensi internal satker dan bukan instrumen hukum regulatif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penyetaraan jabatan diatur • dengan Peraturan Menteri</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Tujuan pokok dilaksanakannya kebijakan penyetaraan jabatan administrasi ke dalam jabatan fungsional adalah...", "options": ["Menambah panjang rantai hierarki persetujuan surat dinas", "Menghapuskan fungsi pelayanan publik pada kantor vertikal", "Mengurangi alokasi belanja pegawai untuk bantuan sosial", "Mewujudkan penataan birokrasi yang lincah dan profesional", "Menurunkan kelas jabatan dan memotong penghasilan pegawai"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Kebijakan Penyetaraan Jabatan ke JF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyetaraan jabatan administrasi ke dalam Jabatan Fungsional dilaksanakan dalam rangka <strong>penataan birokrasi</strong> guna menciptakan birokrasi yang lebih ramping, cepat, dan profesional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan tujuan strategis reformasi birokrasi nasional yang tepat. Pengalihan posisi manajerial eselon ke fungsional memperpendek rantai komando pengambilan keputusan (*delayering*). Sesuai ketentuan, inpassing jabatan fungsional dilakukan demi kepentingan penataan birokrasi yang efektif. Dengan demikian, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Mewujudkan penataan birokrasi yang lincah dan profesional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyetaraan justru memangkas rantai birokrasi hierarkis agar proses kerja semakin singkat.</li><li><strong>B</strong>: Pelayanan publik tetap berjalan dan diperkuat dengan tenaga fungsional yang berkeahlian.</li><li><strong>C</strong>: Kebijakan ini fokus pada struktur organisasi kerja dan bukan pemangkasan bantuan sosial.</li><li><strong>E</strong>: Prinsip penyetaraan jabatan menjaga agar penghasilan pegawai tidak mengalami penurunan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penyesuaian jabatan ke dalam Jabatan Fungsional • penataan birokrasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan undang-undang, Pegawai ASN berhak memperoleh penghargaan dan pengakuan yang berwujud...", "options": ["Saham kepemilikan aset gedung kantor dinas", "Penghargaan berupa materiel dan/atau nonmateriel", "Imunitas pidana khusus bagi seluruh pejabat", "Hak veto atas kebijakan strategis kementerian", "Pemberian gelar bangsawan kehormatan adat"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Penghargaan dan Pengakuan Hak ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai ASN berhak memperoleh penghargaan dan pengakuan berupa <strong>materiel dan/atau nonmateriel</strong> yang terdiri atas 7 komponen pokok.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan klasifikasi bentuk apresiasi negara kepada aparatur yang tepat. Hak pegawai kini dirumuskan sebagai paket penghargaan komprehensif baik yang berwujud finansial (materiel) maupun apresiasi moral dan karier (nonmateriel). Aturan undang-undang menyebutkan hak berupa penghargaan materiel dan/atau nonmateriel. Oleh karena itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Penghargaan berupa materiel dan/atau nonmateriel</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Gedung kantor dinas merupakan barang milik negara yang dilarang dimiliki secara pribadi.</li><li><strong>C</strong>: Aparatur sipil negara berkedudukan sama di depan hukum tanpa ada hak kebal hukum pidana.</li><li><strong>D</strong>: Hak veto tidak dimiliki oleh aparatur dalam struktur hierarki pemerintahan presidensial.</li><li><strong>E</strong>: Gelar adat kehormatan diberikan oleh lembaga adat daerah dan bukan hak kedinasan ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pegawai ASN berhak memperoleh penghargaan • berupa materiel dan/atau nonmateriel</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berikut ini merupakan komponen penghargaan dan pengakuan hak Pegawai ASN, KECUALI...", "options": ["Penghasilan serta tunjangan dan fasilitas", "Jaminan sosial dan lingkungan kerja", "Pengembangan diri dan bantuan hukum", "Kepemilikan kendaraan operasional dinas secara pribadi", "Penghargaan yang bersifat motivasi"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuh Komponen Penghargaan Hak Pegawai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuh komponen penghargaan ASN mencakup: 1. Penghasilan; 2. Penghargaan yang bersifat motivasi; 3. Tunjangan dan fasilitas; 4. Jaminan sosial; 5. Lingkungan kerja; 6. Pengembangan diri; dan 7. Bantuan hukum. <strong>Kepemilikan pribadi kendaraan dinas bukan merupakan hak ASN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan pengecualian yang tepat karena fasilitas kendaraan dinas berstatus pinjam pakai milik negara dan tidak dapat dialihkan menjadi hak milik pribadi. Ketujuh hak ASN diatur secara limitatif dan tidak mencakup pemilikan aset negara. Dengan demikian, opsi D adalah jawaban pengecualian yang tepat.</p><p>Jawaban tepat: <strong>D. Kepemilikan kendaraan operasional dinas secara pribadi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penghasilan serta tunjangan dan fasilitas merupakan butir 1 dan butir 3 hak ASN.</li><li><strong>B</strong>: Jaminan sosial dan lingkungan kerja merupakan butir 4 dan butir 5 hak resmi ASN.</li><li><strong>C</strong>: Pengembangan diri dan bantuan hukum tercantum pada butir 6 dan butir 7 hak ASN.</li><li><strong>E</strong>: Penghargaan yang bersifat motivasi tercantum pada butir 2 penghargaan resmi ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berupa materiel dan/atau nonmateriel • Penghargaan yang bersifat motivasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat perbendaharaan digugat di pengadilan perdata terkait pelaksanaan tugas kedinasan resminya. Hak yang wajib diberikan pemerintah adalah...", "options": ["Bantuan hukum dalam perkara pelaksanaan tugas", "Pemberian dana ganti rugi tanpa melalui sidang", "Penutupan paksa lembaga pengadilan yang menyidang", "Pemberhentian seketika dari dinas tanpa pemeriksaan", "Pengalihan gugatan menjadi tanggung jawab keluarga"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Bantuan Hukum bagi ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu komponen hak ASN adalah memperoleh <strong>bantuan hukum</strong> dalam perkara yang dihadapi di pengadilan terkait pelaksanaan tugas kedinasannya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan bentuk perlindungan hukum kedinasan yang wajib disediakan oleh instansi pemerintah. Pegawai yang bertindak atas nama jabatan negara berhak didampingi oleh biro hukum instansi selama proses persidangan berlangsung. Ketentuan hak ASN butir 7 menegaskan hak memperoleh bantuan hukum. Oleh karena itu, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>A. Bantuan hukum dalam perkara pelaksanaan tugas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pemberian ganti rugi keuangan negara wajib menunggu putusan pengadilan yang berkekuatan hukum tetap.</li><li><strong>C</strong>: Pemerintah dilarang mengintervensi independensi dan kewenangan lembaga peradilan negara.</li><li><strong>D</strong>: Pemberhentian tanpa audit dan pemeriksaan melanggar hak asasi serta prosedur disiplin ASN.</li><li><strong>E</strong>: Perkara kedinasan melekat pada jabatan negara dan bukan beban privat tanggung jawab keluarga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berupa materiel dan/atau nonmateriel yang terdiri atas • Bantuan hukum</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kewajiban pertama dan utama bagi setiap insan Aparatur Sipil Negara sebagaimana diatur dalam undang-undang adalah...", "options": ["Mendukung program kerja asosiasi pengusaha daerah", "Menghafalkan seluruh nomor registrasi aset kantor", "Setia dan taat kepada Pancasila, UUD 1945, NKRI, dan pemerintah", "Mengikuti kegiatan studi banding dinas ke luar negeri", "Mencapai target penerimaan kas nonpajak pribadi"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Pokok Kesetiaan ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewajiban pertama ASN adalah <strong>setia dan taat kepada Pancasila, UUD Tahun 1945, NKRI, dan pemerintah yang sah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan fondasi ikrar pengabdian ideologis tertinggi aparatur sipil negara. Kesetiaan pada dasar negara dan konstitusi menjamin loyalitas mutlak pegawai kepada eksistensi bangsa dan negara Republik Indonesia. Ketentuan kewajiban butir 1 menempatkan kesetiaan kepada Pancasila dan UUD 1945 pada urutan teratas. Dengan demikian, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Setia dan taat kepada Pancasila, UUD 1945, NKRI, dan pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kemitraan usaha dijalankan sesuai regulasi dan bukan merupakan kewajiban pokok ideologis ASN.</li><li><strong>B</strong>: Penatausahaan kode aset merupakan rincian tugas teknis pengelola barang milik negara.</li><li><strong>D</strong>: Studi banding luar negeri merupakan sarana pelatihan dinas selektif dan bukan kewajiban dasar.</li><li><strong>E</strong>: Penerimaan kas negara merupakan target fungsional bendahara penerimaan tertentu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>B. Kewajiban Aparatur Sipil Negara (ASN) • setia dan taat kepada Pancasila, UUD Tahun 1945, NKRI, dan pemerintah yang sah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam penyelenggaraan birokrasi pemerintahan, asas legalitas aparatur diwujudkan melalui kewajiban...", "options": ["Menerapkan kebijakan diskresi tanpa batasan wewenang", "Mengutamakan tradisi lisan dibanding naskah regulasi", "Menyesuaikan standar layanan demi kepentingan kerabat", "Mengesampingkan regulasi saat menghadapi audit rutin", "Menaati ketentuan peraturan perundang-undangan yang sah"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Kepatuhan Peraturan Perundang-undangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewajiban kedua ASN adalah <strong>menaati ketentuan peraturan perundang-undangan</strong> dalam setiap tindakan kedinasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan pilar utama negara hukum (*rechtsstaat*) dalam tata kelola birokrasi publik. Setiap tindakan administrasi, perizinan, dan pengelolaan keuangan negara wajib didasarkan pada payung hukum yang berlaku. Aturan kewajiban butir 2 menegaskan kewajiban menaati peraturan perundang-undangan. Oleh karena itu, opsi E merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Menaati ketentuan peraturan perundang-undangan yang sah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Diskresi aparatur memiliki batasan ketat sesuai Undang-Undang Administrasi Pemerintahan.</li><li><strong>B</strong>: Tindakan birokrasi modern wajib berpijak pada hukum tertulis resmi negara.</li><li><strong>C</strong>: Pemberian perlakuan khusus kepada kerabat melanggar larangan korupsi, kolusi, dan nepotisme.</li><li><strong>D</strong>: Regulasi kedinasan wajib ditaati secara konsisten termasuk saat proses audit berlangsung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kewajiban ASN adalah • menaati ketentuan peraturan perundang-undangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Dalam berinteraksi dengan masyarakat pemohon layanan, seorang pegawai ASN wajib senantiasa...", "options": ["Meminta uang pelicin agar berkas izin lekas disetujui", "Melaksanakan nilai dasar serta kode etik dan kode perilaku", "Menutup loket pelayanan sebelum jam kerja dinas berakhir", "Menolak berkas permohonan tanpa memberikan penjelasan", "Menyimpan berkas warga hingga ada titipan pihak berpengaruh"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Penerapan Nilai Dasar dan Etika</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewajiban ketiga ASN adalah <strong>melaksanakan nilai dasar ASN dan kode etik dan kode perilaku ASN</strong> dalam kehidupan bermasyarakat dan kedinasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan cerminan akhlak dan etika integritas pelayanan publik yang tepat. Pegawai wajib bersikap ramah, kompeten, transparan, dan tidak meminta imbalan haram saat melayani warga. Ketentuan kewajiban butir 3 mewajibkan pengamalan nilai dasar dan kode etik ASN. Dengan demikian, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Melaksanakan nilai dasar serta kode etik dan kode perilaku</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Meminta uang pelicin merupakan tindak pidana korupsi suap dan pungutan liar berat.</li><li><strong>C</strong>: Menutup loket sebelum waktunya melanggar jam kerja standar operasional pelayanan publik.</li><li><strong>D</strong>: Penolakan berkas wajib disertai dasar hukum dan catatan perbaikan tertulis yang jelas.</li><li><strong>E</strong>: Menunda layanan demi titipan melanggar asas kesetaraan perlakuan di depan hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melaksanakan nilai dasar ASN • dan kode etik dan kode perilaku ASN</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kewajiban butir keempat yang ditegaskan dalam Undang-Undang ASN untuk menjamin independensi pelayanan adalah...", "options": ["Menjaga kerahasiaan tarif pelayanan publik di loket", "Menghadiri kampanye pemilihan kepala daerah secara rutin", "Menolak instruksi penugasan di daerah pelosok perbatasan", "Menjaga netralitas dari kepentingan politik mana pun", "Membentuk serikat politik khusus pegawai kementerian"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Menjaga Netralitas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewajiban butir keempat ASN adalah <strong>menjaga netralitas</strong> dari pengaruh politik kepartaian maupun golongan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan perwujudan dari asas netralitas yang wajib dipatuhi seluruh pegawai negara. Sikap netral memastikan aparatur tidak terkooptasi oleh kepentingan pergantian rezim politik kekuasaan. Ketentuan butir 4 merumuskan secara tegas kewajiban menjaga netralitas. Oleh karena itu, opsi D adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>D. Menjaga netralitas dari kepentingan politik mana pun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tarif layanan publik wajib diumumkan secara terbuka dan transparan kepada masyarakat.</li><li><strong>B</strong>: Menghadiri kampanye politik merupakan pelanggaran disiplin netralitas aparatur.</li><li><strong>C</strong>: Menolak penempatan tugas di pelosok melanggar sumpah jabatan kesediaan ditempatkan.</li><li><strong>E</strong>: ASN dilarang membentuk atau menjadi anggota faksi organisasi politik kepartaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kewajiban ASN adalah • menjaga netraliras</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Ruang lingkup kewajiban kesediaan penempatan tugas kedinasan bagi seluruh Pegawai ASN mencakup...", "options": ["Seluruh wilayah NKRI dan perwakilan di luar negeri", "Hanya di wilayah ibu kota provinsi tempat domisili", "Khusus pada kantor pusat kementerian di ibu kota", "Terbatas pada radius lima puluh kilometer dari tempat lahir", "Hanya pada instansi vertikal yang memiliki fasilitas lengkap"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Kesediaan Penempatan Penugasan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewajiban kelima ASN adalah <strong>bersedia ditempatkan di seluruh wilayah NKRI dan perwakilan NKRI yang berkedudukan di luar wilayah Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan komitmen penugasan universal aparatur sipil negara yang tepat. Sebagai abdi negara, pegawai harus siap menjalankan mandat pembangunan hingga pelosok terluar maupun kantor perwakilan kedutaan besar luar negeri. Ketentuan butir 5 merinci penempatan di seluruh NKRI dan perwakilan luar negeri. Dengan demikian, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>A. Seluruh wilayah NKRI dan perwakilan di luar negeri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Penempatan dinas tidak dibatasi oleh faktor domisili tempat tinggal asal pegawai.</li><li><strong>C</strong>: Kebutuhan organisasi negara tersebar di unit pusat maupun kantor vertikal daerah.</li><li><strong>D</strong>: Pembatasan radius geografis tidak berlaku dalam penugasan resmi aparatur sipil negara.</li><li><strong>E</strong>: Pegawai ASN wajib siap bertugas pada satuan kerja dengan segala kondisi fasilitas yang ada.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bersedia ditempatkan di seluruh wilayah NKRI • dan perwakilan NKRI yang berkedudukan di luar wilayah Indonesia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Sebuah instansi pemerintah menyediakan ruang kerja yang aman, sehat, serta memfasilitasi beasiswa studi bagi stafnya. Instansi ini memenuhi hak...", "options": ["Fasilitas rumah dinas mewah dan mobil operasional", "Imunitas dari pengawasan aparat pengawas internal", "Lingkungan kerja dan hak pengembangan diri pegawai", "Pemberian gratifikasi liburan akhir tahun anggaran", "Dispensasi bebas absensi sidik jari kedinasan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Lingkungan Kerja dan Pengembangan Diri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyediaan sarana kerja yang kondusif dan fasilitas peningkatan kompetensi merupakan wujud pemenuhan hak <strong>lingkungan kerja</strong> dan <strong>pengembangan diri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan padanan komponen penghargaan hak pegawai yang tepat sesuai tindakan instansi. Lingkungan kerja yang nyaman menopang kesehatan mental dan fisik, sedangkan beasiswa meningkatkan kapasitas talenta pegawai. Ketentuan butir 5 dan 6 mencakup hak lingkungan kerja dan pengembangan diri. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Lingkungan kerja dan hak pengembangan diri pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Rumah dinas dan kendaraan dialokasikan terbatas sesuai jabatan dan bukan hak umum staf.</li><li><strong>B</strong>: Pengawasan internal tetap melekat mutlak pada setiap instansi tanpa ada imunitas.</li><li><strong>D</strong>: Pemberian tiket liburan dari rekanan atau kas dinas tanpa dasar hukum melanggar aturan.</li><li><strong>E</strong>: Presensi kehadiran elektronik wajib dipatuhi oleh seluruh aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pegawai ASN berhak memperoleh penghargaan dan pengakuan • Lingkungan kerja • Pengembangan diri</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C4 - Analisis", "question": "Secara keseluruhan, kewajiban pokok Aparatur Sipil Negara yang dirumuskan dalam ketentuan undang-undang terdiri atas...", "options": ["Tiga butir kewajiban pokok kedinasan", "Empat butir kewajiban pokok kedinasan", "Enam butir kewajiban pokok kedinasan", "Tujuh butir kewajiban pokok kedinasan", "Lima butir kewajiban pokok kedinasan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jumlah Butir Kewajiban ASN dalam Undang-Undang</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewajiban ASN dalam undang-undang dirumuskan ke dalam <strong>5 (lima) butir kewajiban pokok</strong> yang mengikat seluruh aparatur negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan analisis kuantitatif butir-butir kewajiban ASN yang tepat. Kelima butir tersebut meliputi: setia kepada Pancasila/UUD, menaati peraturan, mengamalkan nilai dasar/etika, menjaga netralitas, dan bersedia ditempatkan di seluruh NKRI/luar negeri. Ketentuan yuridis menyusun kewajiban ini secara lugas dalam 5 butir. Dengan demikian, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Lima butir kewajiban pokok kedinasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tiga butir merupakan jumlah klasifikasi standar kompetensi dan bukan butir kewajiban.</li><li><strong>B</strong>: Empat butir merupakan jumlah jenjang fungsional keahlian dan bukan butir kewajiban.</li><li><strong>C</strong>: Enam butir merupakan jumlah alasan pemberhentian jabatan administrasi kepegawaian.</li><li><strong>D</strong>: Tujuh butir merupakan jumlah komponen hak penghargaan ASN dan bukan jumlah kewajiban.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kewajiban ASN adalah • bersedia ditempatkan di seluruh wilayah NKRI</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pemegang kekuasaan pemerintahan tertinggi dalam kebijakan, pembinaan profesi, dan Manajemen ASN adalah...", "options": ["Menteri Pendayagunaan Aparatur Negara dan RB", "Kepala Badan Kepegawaian Negara Republik", "Presiden Republik Indonesia", "Ketua Dewan Perwakilan Rakyat Republik Indonesia", "Ketua Mahkamah Agung Republik Indonesia"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemegang Kekuasaan Tertinggi Manajemen ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Presiden merupakan pemegang kekuasaan pemerintahan tertinggi</strong> dalam kebijakan, pembinaan profesi, dan Manajemen Aparatur Sipil Negara (ASN).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan pemegang mandat konstitusi tertinggi kekuasaan eksekutif pemerintahan negara. Presiden memegang otoritas puncak atas seluruh sistem manajemen kepegawaian aparatur sipil nasional. Aturan undang-undang menegaskan Presiden adalah pemegang kekuasaan tertinggi manajemen ASN. Oleh karena itu, opsi C merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>C. Presiden Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menteri PAN-RB menjalankan kewenangan yang didelegasikan oleh Presiden dan bukan pemegang kekuasaan tertinggi.</li><li><strong>B</strong>: Kepala BKN menjalankan fungsi operasional teknis pelayanan administrasi kepegawaian.</li><li><strong>D</strong>: DPR menjalankan fungsi legislasi dan pengawasan anggaran parlemen.</li><li><strong>E</strong>: Ketua Mahkamah Agung memimpin lembaga kekuasaan yudikatif peradilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Presiden merupakan pemegang kekuasaan pemerintahan tertinggi • kebijakan, pembinaan profesi, dan Manajemen Aparatur Sipil Negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Untuk menyelenggarakan kekuasaan manajemen ASN, Presiden secara resmi mendelegasikan kewenangan kepada...", "options": ["Lembaga Administrasi Negara Republik Indonesia", "Kementerian Koordinator Bidang Perekonomian", "Badan Kepegawaian Negara Republik Indonesia", "Komisi Pemilihan Umum Republik Indonesia", "Kementerian PAN-RB Republik Indonesia"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pendelegasian Kewenangan Kebijakan Manajemen ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden mendelegasikan kewenangan perumusan dan penetapan kebijakan strategis ASN kepada <strong>Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (PAN-RB)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan kementerian teknis yang menerima pendelegasian mandat kebijakan ASN secara sah. Kementerian PAN-RB bertindak sebagai arsitek regulasi, perumus kebijakan makro, dan pengendali reformasi birokrasi aparatur nasional. Ketentuan pasal menegaskan Presiden mendelegasikan kepada Kementerian PAN-RB. Dengan demikian, opsi E adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Kementerian PAN-RB Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: LAN bertugas menyelenggarakan kajian desentralisasi dan diklat kepemimpinan aparatur.</li><li><strong>B</strong>: Kemenko Perekonomian mengoordinasikan bidang makro ekonomi dan investasi fiskal.</li><li><strong>C</strong>: BKN menerima delegasi pengelolaan dan pembinaan teknis operasional kepegawaian.</li><li><strong>D</strong>: KPU merupakan lembaga independen penyelenggara pemilihan umum kepala daerah dan legislatif.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Presiden mendelegasikan kepada • Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pendelegasian kewenangan Presiden kepada Kementerian PAN-RB berkaitan erat dengan tiga mandat utama yaitu...", "options": ["Penyidikan korupsi, penuntutan pidana, dan eksekusi denda", "Perumusan kebijakan, koordinasi sinkronisasi, dan pengendalian", "Penerbitan surat utang, lelang obligasi, dan moneter", "Pengadaan alutsista, logistik militer, dan pertahanan", "Penetapan vonis perdata, banding sengketa, dan kasasi"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewenangan Strategis Kementerian PAN-RB</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pendelegasian kewenangan kepada Kementerian PAN-RB meliputi: <strong>perumusan dan penetapan kebijakan strategis</strong>, <strong>koordinasi dan sinkronisasi kebijakan</strong>, serta <strong>pengendalian kebijakan Manajemen ASN</strong> secara nasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan rincian mandat regulatif Kementerian PAN-RB yang tepat. Ketiga pilar tersebut memastikan kebijakan aparatur tersusun terpadu dari tahap formulasi hingga evaluasi implementasi. Regulasi merinci kewenangan pada perumusan, koordinasi sinkronisasi, dan pengendalian kebijakan. Oleh karena itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Perumusan kebijakan, koordinasi sinkronisasi, dan pengendalian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyidikan dan penuntutan perkara merupakan fungsi aparat penegak hukum kejaksaan dan kepolisian.</li><li><strong>C</strong>: Pengelolaan utang dan obligasi surat berharga negara merupakan kewenangan kementerian keuangan.</li><li><strong>D</strong>: Pengadaan peralatan perang militer berada pada ranah kementerian pertahanan dan TNI.</li><li><strong>E</strong>: Penyelenggaraan peradilan hukum perdata merupakan mandat yudisial Mahkamah Agung.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>perumusan dan penetapan kebijakan strategis • koordinasi dan sinkronisasi kebijakan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam penyelenggaraan Manajemen ASN, Kementerian PAN-RB mengoordinasikan rencana kerja lembaga serta melakukan...", "options": ["Pencetakan uang kertas dan uang logam rupiah baru", "Pemberian sanksi pemecatan langsung kepada kepala daerah", "Pemeriksaan forensik atas transaksi pasar bursa modal", "Sinkronisasi pengendalian kapasitas dan pembelajaran ASN", "Penyitaan rekening simpanan wajib perbankan swasta"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Koordinasi Pembelajaran ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian PAN-RB melakukan <strong>sinkronisasi pengendalian tugas dan fungsi pengembangan kapasitas dan pembelajaran ASN</strong> secara nasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan lingkup fungsi penguatan SDM aparatur yang tepat di tingkat kementerian koordinator teknis. Peningkatan kompetensi aparatur diselaraskan agar kurikulum pembelajaran kementerian/lembaga relevan dengan kebutuhan transformasi birokrasi. Ketentuan aturan menegaskan sinkronisasi kapasitas dan pembelajaran ASN. Dengan demikian, opsi D adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>D. Sinkronisasi pengendalian kapasitas dan pembelajaran ASN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pencetakan mata uang rupiah merupakan mandat Bank Indonesia dan Perum Peruri.</li><li><strong>B</strong>: Pemberhentian kepala daerah diatur tersendiri dalam undang-undang pemerintahan daerah.</li><li><strong>C</strong>: Pemeriksaan transaksi bursa efek merupakan kewenangan Otoritas Jasa Keuangan.</li><li><strong>E</strong>: Penyitaan simpanan nasabah perbankan memerlukan penetapan aparat penegak hukum pengadilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengembangan kapasitas dan pembelajaran ASN • sinkronisasi pengendalian tugas dan fungsi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Salah satu tugas pengawasan krusial yang dijalankan oleh Kementerian PAN-RB pada instansi pemerintah adalah...", "options": ["Pengawasan penerapan Sistem Merit di K/L", "Pemeriksaan fisik sarana persenjataan pangkalan", "Penyadapan saluran telekomunikasi pejabat daerah", "Pemeriksaan kepatuhan rambu-rambu lalu lintas dinas", "Audit penerimaan devisa ekspor industri tekstil"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengawasan Penerapan Sistem Merit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Selain pengendalian kebijakan, <strong>Kementerian PAN-RB juga melakukan pengawasan penerapan Sistem Merit pada Kementerian/Lembaga</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan mandat pengawasan kelembagaan kementerian yang tepat pasca-penguatan regulasi aparatur. Sistem merit menjamin promosi dan mutasi berbasis kualifikasi objektif tanpa nepotisme. Regulasi menyatakan Kementerian PAN-RB melakukan pengawasan penerapan Sistem Merit pada kementerian/lembaga. Oleh karena itu, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>A. Pengawasan penerapan Sistem Merit di K/L</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pengawasan gudang persenjataan militer merupakan urusan internal markas besar keprajuritan.</li><li><strong>C</strong>: Penyadapan komunikasi privat wajib mengantongi izin pengadilan dalam proses peradilan pidana.</li><li><strong>D</strong>: Ketertiban lalu lintas kendaraan umum merupakan kewenangan dinas perhubungan dan kepolisian.</li><li><strong>E</strong>: Audit devisa perdagangan ekspor impor merupakan domain kepabeanan dan Bank Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kementerian PAN-RB juga melakukan pengawasan • penerapan Sistem Merit pada Kementerian/Lembaga</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pengendalian kebijakan Manajemen ASN yang dijalankan oleh Kementerian PAN-RB memiliki ruang lingkup...", "options": ["Hanya terbatas pada instansi pemerintah pusat", "Khusus untuk unit kantor wilayah pulau terluar", "Secara menyeluruh mencakup skala nasional", "Hanya bagi pegawai yang berstatus masa percobaan", "Terbatas pada instansi yang mengalami defisit anggaran"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cakupan Wilayah Pengendalian Kebijakan ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewenangan pengendalian kebijakan Manajemen ASN oleh Kementerian PAN-RB berlaku <strong>secara nasional</strong> (meliputi instansi pusat dan instansi daerah).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan lingkup yurisdiksi tata kelola kepegawaian yang tepat. Kebijakan ASN bersifat terpadu (*single system*) dari Sabang sampai Merauke demi menjaga keutuhan korps aparatur negara. Aturan menyebutkan kewenangan pengendalian kebijakan manajemen ASN berlaku secara nasional. Dengan demikian, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Secara menyeluruh mencakup skala nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengendalian kebijakan tidak terbatas pada pusat melainkan mencakup seluruh pemerintah daerah.</li><li><strong>B</strong>: Kebijakan aparatur berlaku menyeluruh dan tidak dibatasi pada pulau terluar saja.</li><li><strong>D</strong>: Ruang lingkup manajemen ASN berlaku bagi seluruh pegawai tetap maupun calon aparatur.</li><li><strong>E</strong>: Standar kepegawaian berlaku merata tanpa memandang kondisi fiskal anggaran instansi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengendalian kebijakan Manajemen ASN • secara nasional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kementerian PAN-RB selain menetapkan kebijakan makro juga bertugas melakukan pengendalian atas...", "options": ["Tarif suku bunga pinjaman perbankan swasta", "Penetapan harga eceran tertinggi sembako pangan", "Jadwal penerbangan maskapai komersial swasta", "Pemenang tender lelang jalan tol lintas wilayah", "Pelaksanaan kebijakan teknis Manajemen ASN"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengendalian Kebijakan Teknis Manajemen ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian PAN-RB bertugas <strong>melakukan pengendalian pelaksanaan kebijakan teknis Manajemen ASN</strong> pada seluruh instansi pemerintah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan fungsi kontrol implementasi regulasi kepegawaian yang tepat. Pengendalian teknis menjamin instansi pemerintah mematuhi ketentuan pengadaan, promosi, penilaian kinerja, dan disiplin pegawai. Regulasi menyebutkan Kementerian PAN-RB mengendalikan pelaksanaan kebijakan teknis Manajemen ASN. Oleh karena itu, opsi E merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Pelaksanaan kebijakan teknis Manajemen ASN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tingkat suku bunga perbankan diatur oleh Bank Indonesia melalui kebijakan moneter.</li><li><strong>B</strong>: Harga kebutuhan pokok masyarakat ditetapkan oleh kementerian perdagangan dan badan pangan.</li><li><strong>C</strong>: Jadwal dan rute penerbangan udara diatur oleh kementerian perhubungan direktorat jenderal perhubungan udara.</li><li><strong>D</strong>: Tender infrastruktur jalan bebas hambatan berada di bawah wewenang kementerian pekerjaan umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengendalian pelaksanaan kebijakan teknis • Manajemen ASN</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "BKN dan LAN menyusun rencana kerja tahunan terkait rekrutmen dan pelatihan kepemimpinan. Kementerian yang mengoordinasikan rencana kerja tersebut adalah...", "options": ["Kementerian Hukum dan Hak Asasi Manusia", "Kementerian PAN-RB Republik Indonesia", "Kementerian Sekretariat Negara Republik", "Kementerian Koordinator Bidang Politik dan Keamanan", "Kementerian Keuangan Republik Indonesia"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Koordinasi Rencana Kerja Lembaga Penyelenggara ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian PAN-RB mengemban tugas <strong>mengoordinasikan rencana kerja lembaga yang berkaitan dengan penyelenggaraan Manajemen ASN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan koordinator sektoral kepegawaian aparatur sipil negara yang tepat. Lembaga pengelola ASN seperti BKN dan LAN bersinergi di bawah payung koordinasi Kementerian PAN-RB. Regulasi menegaskan Kementerian PAN-RB mengoordinasikan rencana kerja lembaga penyelenggara manajemen ASN. Dengan demikian, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Kementerian PAN-RB Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kemenkumham membidangi urusan perundang-undangan dan pemasyarakatan narapidana umum.</li><li><strong>C</strong>: Kemensetneg bertugas memberikan dukungan teknis dan administrasi kepada lembaga kepresidenan.</li><li><strong>D</strong>: Kemenko Polkam mengoordinasikan stabilitas politik dan pertahanan keamanan nasional.</li><li><strong>E</strong>: Kementerian Keuangan mengelola kebijakan fiskal dan perbendaharaan keuangan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengoordinasikan rencana kerja lembaga yang berkaitan • dengan penyelenggaraan Manajemen ASN</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C4 - Analisis", "question": "Kekuasaan pemerintahan tertinggi yang dipegang oleh Presiden dalam ranah kepegawaian aparatur negara mencakup tiga aspek yaitu...", "options": ["Pajak daerah, retribusi pasar, dan royalti tambang", "Pendidikan militer, komando tempur, dan intelijen", "Hukum pidana khusus, grasi hukum, dan remisi tahanan", "Kebijakan, pembinaan profesi, dan Manajemen ASN", "Audit perbankan, peredaran obligasi, dan kurs rupiah"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Aspek Kekuasaan Pemerintahan ASN pada Presiden</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden merupakan pemegang kekuasaan tertinggi dalam tiga domain pokok: <strong>kebijakan</strong>, <strong>pembinaan profesi</strong>, dan <strong>Manajemen Aparatur Sipil Negara (ASN)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merangkum trilogi mandat kekuasaan eksekutif presiden atas kepegawaian ASN yang tepat. Ketiga ranah ini memberikan wewenang regulatif, etik moral profesi, serta operasional karier pegawai pemerintah. Ketentuan perundang-undangan menyatakan secara tegas ketiga aspek kekuasaan tersebut. Oleh karena itu, opsi D merupakan jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Kebijakan, pembinaan profesi, dan Manajemen ASN</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pajak daerah dan retribusi merupakan instrumen pendapatan asli daerah pada fiskal lokal.</li><li><strong>B</strong>: Pendidikan militer dan komando tempur merupakan ranah pertahanan prajurit angkatan bersenjata.</li><li><strong>C</strong>: Grasi dan remisi merupakan hak prerogatif presiden dalam pelaksanaan putusan yudisial.</li><li><strong>E</strong>: Kebijakan obligasi dan audit perbankan berada di bawah otoritas fiskal dan moneter keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kebijakan, pembinaan profesi, dan Manajemen Aparatur Sipil Negara • Presiden merupakan pemegang kekuasaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Prinsip utama pendelegasian kekuasaan Presiden kepada Kementerian PAN-RB dalam mengendalikan kebijakan ASN bertujuan mewujudkan...", "options": ["Sistem pengelolaan aparatur yang terpadu dan selaras", "Kemandirian mutlak setiap daerah tanpa kontrol pusat", "Penyerahan tata kelola kepegawaian kepada korporasi", "Peniadaan batas persyaratan kompetensi jabatan staf", "Pembagian jatah kursi birokrasi bagi kelompok tertentu"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sifat Pendelegasian Wewenang Pengendalian ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pendelegasian kewenangan pengendalian kepada Kementerian PAN-RB bertujuan menjamin <strong>keselarasan, efektivitas, dan keterpaduan sistem manajemen kepegawaian</strong> secara nasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan tujuan normatif pendelegasian manajemen ASN yang tepat. Tanpa kendali terpusat, standar kualitas dan etika birokrasi antar-kementerian dan daerah akan terfragmentasi. Sesuai asas ketatanegaraan, pendelegasian memastikan kebijakan berjalan selaras dan terintegrasi. Dengan demikian, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Sistem pengelolaan aparatur yang terpadu dan selaras</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Desentralisasi daerah tetap wajib tunduk pada standar norma dan regulasi aparatur nasional.</li><li><strong>C</strong>: Birokrasi pemerintahan negara dilarang diprivatisasi menjadi kepemilikan korporasi bisnis.</li><li><strong>D</strong>: Standar kompetensi jabatan wajib dipertahankan secara ketat demi mutu pelayanan publik.</li><li><strong>E</strong>: Sistem merit melarang keras praktik pembagian jabatan berdasarkan jatah kelompok primordial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pendelegasian tersebut berkaitan dengan kewenangan • pengendalian kebijakan Manajemen ASN secara nasional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kebijakan dan manajemen ASN yang didasarkan pada kualifikasi, kompetensi, dan kinerja secara adil dan wajar tanpa diskriminasi disebut...", "options": ["Sistem Patronase Politik Terbuka", "Sistem Senioritas Pangkat Mutlak", "Sistem Spoils Pembagian Jabatan", "Sistem Merit Manajemen Aparatur", "Sistem Kuota Afirmasi Wilayah"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Sistem Merit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Sistem Merit</strong> adalah kebijakan dan manajemen ASN yang didasarkan pada <strong>kualifikasi, kompetensi, dan kinerja secara adil dan wajar</strong> tanpa membedakan latar belakang politik, ras, warna kulit, agama, asal-usul, jenis kelamin, status pernikahan, umur, atau kondisi kecacatan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan definisi yuridis sistem merit yang tepat dalam hukum kepegawaian Indonesia. Sistem merit memastikan setiap aparatur dinilai secara murni berdasarkan kemampuan kerja nyata dan integritas profesionalnya. Regulasi menegaskan kebijakan berbasis kualifikasi dan kompetensi adil disebut Sistem Merit. Oleh karena itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Sistem Merit Manajemen Aparatur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sistem patronase mengangkat pegawai berdasarkan kedekatan personal atau dukungan politik.</li><li><strong>B</strong>: Sistem senioritas mendasarkan promosi hanya pada usia atau lamanya masa dinas.</li><li><strong>C</strong>: Sistem spoils membagikan jabatan birokrasi sebagai hadiah kepada pendukung pemilu.</li><li><strong>E</strong>: Sistem kuota afirmasi bukan konsep dasar yang mendefinisikan meritokrasi profesional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kualifikasi, kompetensi, dan kinerja secara adil dan wajar • (Sistem Merit)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam penerapan Sistem Merit, manajemen ASN wajib memperlakukan pegawai secara adil tanpa membedakan faktor...", "options": ["Latar belakang politik, ras, agama, kelamin, dan kecacatan", "Tingkat pencapaian target kinerja bulanan di unit dinas", "Skor hasil pengujian kompetensi teknis dan manajerial", "Tingkat kualifikasi ijazah pendidikan formal yang dimiliki", "Riwayat rekam jejak integritas penugasan kedinasan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Nondiskriminasi Sistem Merit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sistem Merit menjamin perlakuan adil <strong>tanpa membedakan latar belakang politik, ras, warna kulit, agama, asal-usul, jenis kelamin, status pernikahan, umur, atau kondisi kecacatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merinci aspek nondiskriminasi yang dilindungi oleh undang-undang aparatur. Promosi dan pengembangan karier dilarang dihambat oleh identitas primordial, gender, atau disabilitas fisik pegawai. Aturan menyebutkan perlakuan adil tanpa membeda-bedakan latar belakang tersebut. Dengan demikian, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Latar belakang politik, ras, agama, kelamin, dan kecacatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Capaian target kinerja justru menjadi variabel utama penentu penghargaan dan promosi merit.</li><li><strong>C</strong>: Nilai uji kompetensi merupakan syarat objektif penempatan jabatan meritokrasi.</li><li><strong>D</strong>: Kualifikasi pendidikan merupakan kriteria wajib pemenuhan standar formasi jabatan.</li><li><strong>E</strong>: Rekam jejak integritas menjadi pertimbangan utama kepatutan menduduki jabatan publik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tanpa membedakan latar belakang politik, ras, warna kulit • status pernikahan, umur, atau kondisi kecacatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Ruang lingkup tata kelola Manajemen ASN secara komprehensif meliputi dua bidang yaitu...", "options": ["Manajemen Angkatan Bersenjata dan Manajemen Polisi Sipil", "Manajemen Pejabat Diplomatik dan Manajemen Konsuler Luar", "Manajemen Pegawai Negeri Sipil dan Manajemen PPPK", "Manajemen Hakim Peradilan dan Manajemen Jaksa Agung", "Manajemen Pekerja BUMN dan Manajemen Karyawan Swasta"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Sub-Bidang Manajemen ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Manajemen ASN ini meliputi Manajemen Pegawai Negeri Sipil (PNS) dan Manajemen Pegawai Pemerintah dengan Perjanjian Kerja (PPPK)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan dua pilar tata kelola sumber daya manusia aparatur sipil negara yang tepat. Keduanya memiliki tata cara pengadaan, pembinaan, dan pemberhentian yang terstandardisasi dalam regulasi pemerintah. Aturan hukum menegaskan manajemen ASN mencakup manajemen PNS dan manajemen PPPK. Oleh karena itu, opsi C merupakan jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Manajemen Pegawai Negeri Sipil dan Manajemen PPPK</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Manajemen militer dan kepolisian diatur dalam regulasi pertahanan dan kepolisian tersendiri.</li><li><strong>B</strong>: Pejabat diplomatik merupakan bagian fungsional spesifik dalam rumpun aparatur sipil.</li><li><strong>D</strong>: Jabatan hakim merupakan pejabat negara di bawah naungan kekuasaan kehakiman Mahkamah Agung.</li><li><strong>E</strong>: Pekerja korporasi BUMN dan swasta tunduk pada regulasi ketenagakerjaan ketenagakerjaan umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Manajemen ASN ini meliputi • Manajemen Pegawai Negeri Sipil (PNS) dan Manajemen Pegawai Pemerintah dengan Perjanjian Kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Presiden dapat mendelegasikan kewenangan pembinaan Manajemen ASN kepada Pejabat yang Berwenang (PyB) di antaranya yaitu...", "options": ["Pimpinan redaksi media pers cetak swasta nasional", "Ketua dewan perwakilan rakyat tingkat kabupaten", "Direktur utama perusahaan penyedia tenaga kerja alih daya", "Pimpinan organisasi masyarakat keagamaan daerah", "Sekretaris jenderal kementerian atau sekretariat lembaga"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Delegasi Pembinaan Manajemen ASN oleh Presiden</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden dapat mendelegasikan kewenangan pembinaan Manajemen ASN kepada <strong>Pejabat yang Berwenang (PyB)</strong> di kementerian, <strong>sekretaris jenderal/sekretariat lembaga negara</strong>, sekretariat LNS, dan sekretaris daerah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan figur Pejabat yang Berwenang (PyB) yang tepat di lingkungan birokrasi pemerintahan. Sekjen kementerian memimpin pembinaan administrasi kepegawaian internal sebelum diusulkan kepada menteri selaku PPK. Aturan merinci delegasi kepada sekjen/sekretariat lembaga dan sekda. Dengan demikian, opsi E adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>E. Sekretaris jenderal kementerian atau sekretariat lembaga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pimpinan media pers swasta berada di luar struktur organisasi pemerintahan negara.</li><li><strong>B</strong>: Ketua DPRD memegang mandat pengawasan politik daerah dan bukan pejabat birokrasi PyB.</li><li><strong>C</strong>: Direktur perusahaan alih daya merupakan pihak rekanan swasta nonpemerintah.</li><li><strong>D</strong>: Pimpinan ormas keagamaan bukan bagian dari pemegang wewenang birokrasi kepegawaian ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Presiden dapat mendelegasikan kewenangan pembinaan Manajemen ASN • kepada Pejabat yang Berwenang di kementerian</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Dalam proses pengangkatan pejabat administrasi, peran utama Pejabat yang Berwenang (PyB) adalah...", "options": ["Menetapkan surat keputusan pengangkatan tanpa koordinasi", "Memberikan rekomendasi usulan kepada Pejabat Pembina Kepegawaian", "Melakukan pelantikan pejabat di hadapan dewan perwakilan", "Memotong tunjangan kinerja pejabat yang baru diangkat", "Membatalkan undang-undang yang mengatur syarat jabatan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Wewenang antara PyB dan PPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Pejabat yang Berwenang (PyB)</strong> memberikan <strong>rekomendasi usulan pengangkatan, pemindahan, dan pemberhentian</strong> kepada <strong>Pejabat Pembina Kepegawaian (PPK)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B menjelaskan pembagian kewenangan administratif kepegawaian secara tepat. PyB meneliti kelayakan teknis dan mengajukan usulan nama kepada PPK yang memiliki wewenang yuridis menetapkan keputusan. Regulasi menyatakan PyB memberikan rekomendasi usulan kepada PPK di instansi masing-masing. Oleh karena itu, opsi B merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Memberikan rekomendasi usulan kepada Pejabat Pembina Kepegawaian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penetapan surat keputusan pengangkatan merupakan wewenang mutlak PPK dan bukan PyB.</li><li><strong>C</strong>: Pelantikan pejabat struktural dipimpin oleh PPK atau pejabat yang ditunjuk resmi.</li><li><strong>D</strong>: Pemotongan hak tunjangan tanpa dasar pelanggaran disiplin hukum merupakan tindakan ilegal.</li><li><strong>E</strong>: Pembatalan undang-undang hanya dapat diputuskan melalui pengujian yudisial Mahkamah Konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pejabat yang berwenang sebagaimana dimaksud memberikan rekomendasi usulan • kepada Pejabat Pembina Kepegawaian (PPK)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pejabat yang Berwenang mengusulkan pengangkatan, pemindahan, dan pemberhentian kepada PPK khusus untuk...", "options": ["Menteri koordinator dan wakil menteri kabinet", "Ketua dan wakil ketua komisi yudisial negara", "Gubernur dan wakil gubernur hasil pemilihan", "Pejabat Administrasi dan Pejabat Fungsional", "Panglima militer dan kepala staf angkatan"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Objek Usulan Kepegawaian oleh PyB</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pejabat yang Berwenang mengusulkan pengangkatan, pemindahan, dan pemberhentian <strong>Pejabat Administrasi dan Pejabat Fungsional</strong> kepada Pejabat Pembina Kepegawaian.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merinci kelompok jabatan aparatur yang menjadi domain usulan administratif PyB. Jabatan administrasi (administrator, pengawas, pelaksana) dan jabatan fungsional diverifikasi oleh PyB sebelum diterbitkan SK oleh PPK. Aturan perundangan menyebutkan khusus usulan pejabat administrasi dan fungsional. Dengan demikian, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Pejabat Administrasi dan Pejabat Fungsional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menteri dan wakil menteri merupakan pembantu presiden yang diangkat langsung oleh presiden.</li><li><strong>B</strong>: Pimpinan komisi yudisial merupakan pejabat negara yang diusulkan melalui seleksi khusus.</li><li><strong>C</strong>: Kepala daerah memperoleh mandat melalui pemilihan umum kepala daerah langsung.</li><li><strong>E</strong>: Panglima TNI diangkat oleh presiden dengan persetujuan Dewan Perwakilan Rakyat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengusulkan pengangkatan, pemindahan, dan pemberhentian • Pejabat Administrasi dan Pejabat Fungsional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penyelenggaraan Manajemen PNS pada tingkat instansi daerah dilaksanakan secara sah oleh...", "options": ["Pemerintah daerah bersangkutan", "Konsulat jenderal negara sahabat", "Yayasan kemanusiaan nirlaba lokal", "Komando resor militer setempat", "Kamar dagang dan industri daerah"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pembagian Penyelenggaraan Manajemen PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Manajemen PNS pada Instansi Pusat dilaksanakan oleh pemerintah pusat</strong>, sementara <strong>Manajemen PNS pada Instansi Daerah dilaksanakan oleh pemerintah daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan pembagian tata kelola otonomi kepegawaian yang tepat. Pemerintah daerah melalui kepala daerah (Gubernur/Bupati/Walikota) bertindak selaku PPK yang mengelola kepegawaian ASN di yurisdiksi daerahnya. Regulasi menegaskan manajemen pada instansi daerah dilaksanakan oleh pemerintah daerah. Oleh karena itu, opsi A adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. Pemerintah daerah bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Konsulat jenderal asing merupakan korps perwakilan negara lain di bidang imigrasi/diplomasi.</li><li><strong>C</strong>: Organisasi nirlaba merupakan lembaga swadaya masyarakat dan bukan pengelola birokrasi ASN.</li><li><strong>D</strong>: Komando militer menjalankan fungsi pertahanan teritorial pertahanan angkatan bersenjata.</li><li><strong>E</strong>: Kamar dagang merupakan asosiasi pelaku usaha perdagangan sektor industri komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Manajemen PNS pada Instansi Pusat dilaksanakan oleh pemerintah pusat • pada Instansi Daerah dilaksanakan oleh pemerintah daerah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Otoritas yang berwenang menetapkan Kebijakan Perencanaan Kebutuhan Pegawai ASN secara nasional adalah...", "options": ["Kepala Kepolisian Negara Republik Indonesia", "Gubernur Bank Indonesia bidang moneter", "Menteri Pendayagunaan Aparatur Negara dan RB", "Ketua Badan Pemeriksa Keuangan Republik", "Menteri Perencanaan Pembangunan Nasional"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penetapan Kebijakan Perencanaan Kebutuhan ASN Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam UU Nomor 20 Tahun 2023 disebutkan bahwa <strong>Menteri Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (PAN-RB) menetapkan Kebijakan Perencanaan Kebutuhan Pegawai ASN secara nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan menteri teknis pemegang wewenang formasi aparatur negara yang tepat. Menteri PAN-RB menghimpun usulan formasi dari seluruh K/L dan daerah untuk diselaraskan dengan arah prioritas pembangunan nasional. Ketentuan pasal menetapkan Menteri PAN-RB sebagai pemegang kewenangan perencanaan formasi nasional. Dengan demikian, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Menteri Pendayagunaan Aparatur Negara dan RB</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kapolri membina personel internal kepolisian dan bukan perencana kebutuhan ASN nasional.</li><li><strong>B</strong>: Gubernur BI mengelola stabilitas nilai tukar rupiah dan sistem pembayaran perbankan.</li><li><strong>D</strong>: BPK menjalankan fungsi pemeriksaan keuangan eksternal pertanggungjawaban kas negara.</li><li><strong>E</strong>: Menteri PPN/Bappenas merancang rencana pembangunan makro di luar penetapan kuota formasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menteri Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (PAN-RB) • menetapkan Kebijakan Perencanaan Kebutuhan Pegawai ASN secara nasional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Perencanaan Kebutuhan Pegawai ASN secara nasional ditetapkan oleh Menteri PAN-RB berlandaskan pada...", "options": ["Hasil jajak pendapat popularitas partai politik", "Laporan laba bersih emiten perseroan terbuka", "Permintaan kuota sponsor dari organisasi kemasyarakatan", "Jumlah pengajuan izin cuti melahirkan di daerah", "Prioritas nasional sesuai rencana pembangunan menengah"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Perencanaan Kebutuhan ASN Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan Perencanaan Kebutuhan Pegawai ASN ditetapkan <strong>berdasarkan prioritas nasional sesuai dengan rencana pembangunan jangka menengah nasional (RPJMN)</strong> serta kemampuan fiskal.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan dasar strategis penetapan alokasi formasi kepegawaian yang tepat. Pengadaan ASN tidak dilakukan secara acak, melainkan diarahkan untuk mendukung target-target prioritas pembangunan nasional (seperti sektor kesehatan, pendidikan, dan digitalisasi). Regulasi menyebutkan penetapan berlandaskan prioritas nasional sesuai RPJMN. Oleh karena itu, opsi E adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>E. Prioritas nasional sesuai rencana pembangunan menengah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Survei politik partisan dilarang memengaruhi perencanaan alokasi sumber daya aparatur.</li><li><strong>B</strong>: Kinerja emiten saham bursa tidak menjadi landasan penetapan formasi kepegawaian ASN.</li><li><strong>C</strong>: Penetapan kebutuhan pegawai steril dari tekanan atau lobi kuota organisasi kemasyarakatan.</li><li><strong>D</strong>: Data cuti melahirkan merupakan administrasi rutin dan bukan dasar perencanaan formasi nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berdasarkan prioritas nasional • rencana pembangunan jangka menengah nasional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C4 - Analisis", "question": "Tiga pilar penilaian objektif yang menjadi dasar pertimbangan dalam setiap pengambilan keputusan karier ASN adalah...", "options": ["Kekayaan pribadi, koneksi pejabat, dan popularitas", "Kualifikasi pendidikan, kompetensi kerja, dan kinerja nyata", "Asal kedaerahan, suku keluarga, dan senioritas umur", "Afiliasi organisasi kemasyarakatan dan pilihan politik", "Besaran sumbangan sosial dan rekomendasi tokoh informal"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Tolok Ukur Inti Manajemen ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan dan manajemen ASN secara mutlak didasarkan pada tiga pilar inti: <strong>kualifikasi</strong>, <strong>kompetensi</strong>, dan <strong>kinerja</strong> aparatur.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan tiga pilar utama sistem merit yang tepat. Kualifikasi menjamin standar latar belakang keilmuan, kompetensi membuktikan kemahiran teknis, dan kinerja memperlihatkan hasil kerja nyata. Ketentuan perundang-undangan menegaskan ketiga pilar tersebut sebagai dasar manajemen ASN. Dengan demikian, opsi B adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>B. Kualifikasi pendidikan, kompetensi kerja, dan kinerja nyata</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penilaian berbasis kekayaan dan koneksi merupakan praktik korupsi, kolusi, dan nepotisme.</li><li><strong>C</strong>: Pertimbangan kesukuan dan kedekatan primordial melanggar prinsip keadilan nondiskriminasi.</li><li><strong>D</strong>: Keterikatan ormas dan afiliasi politik dilarang keras memengaruhi keputusan karier ASN.</li><li><strong>E</strong>: Pemberian sumbangan dan rekomendasi informal tidak diakui dalam instrumen seleksi meritokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kebijakan dan manajemen ASN didasarkan pada • kualifikasi, kompetensi, dan kinerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Setiap Instansi Pemerintah wajib menyusun kebutuhan jumlah dan jenis jabatan PNS berdasarkan...", "options": ["Perkiraan subjektif kepala subbagian kepegawaian", "Jumlah pendaftar magang sukarela tahun sebelumnya", "Permintaan kuota tambahan dari serikat pekerja dinas", "Kapasitas daya tampung ruangan gedung kantor baru", "Analisis jabatan dan analisis beban kerja organisasi"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Penyusunan Kebutuhan Formasi PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Instansi Pemerintah wajib menyusun kebutuhan jumlah dan jenis jabatan PNS berdasarkan <strong>analisis jabatan (anjab)</strong> dan <strong>analisis beban kerja (ABK)</strong> serta peta jabatan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan metodologi ilmiah perencanaan formasi ASN yang diwajibkan regulasi. Anjab dan ABK memetakan uraian tugas serta beban volume kerja nyata sehingga kebutuhan pegawai tidak berlebih (*overstaffing*) atau kurang. PP 11/2017 jo PP 17/2020 mewajibkan dasar analisis jabatan dan analisis beban kerja. Oleh karena itu, opsi E merupakan pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Analisis jabatan dan analisis beban kerja organisasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perencanaan kebutuhan pegawai dilarang didasarkan pada selera atau taksiran subjektif pejabat.</li><li><strong>B</strong>: Data pelamar magang bukan instrumen analisis beban kerja organisasi pemerintahan.</li><li><strong>C</strong>: Penetapan formasi kepegawaian steril dari lobi atau permintaan kuota serikat pekerja.</li><li><strong>D</strong>: Luas bangunan kantor merupakan fasilitas fisik dan bukan dasar penentuan beban kerja tugas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>wajib menyusun kebutuhan jumlah dan jenis jabatan PNS • analisis jabatan dan analisis beban kerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Selain analisis jabatan dan beban kerja, penyusunan kebutuhan formasi PNS wajib memperhatikan...", "options": ["Hasil perolehan suara partai pemenang pemilihan umum", "Kondisi geografis, jumlah penduduk, dan belanja pegawai", "Jumlah kendaraan dinas operasional yang tersedia", "Tingkat suku bunga kredit perbankan komersial daerah", "Daftar perusahaan swasta rekanan pengadaan barang"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Faktor Pertimbangan dalam Kebutuhan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyusunan peta kebutuhan jabatan PNS wajib memperhatikan: <strong>kondisi geografis daerah</strong>, <strong>jumlah penduduk</strong>, dan <strong>rasio alokasi anggaran belanja pegawai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan tiga variabel makro daerah yang wajib diperhitungkan dalam formasi ASN. Faktor demografi dan wilayah menentukan beban jangkauan layanan, sedangkan rasio belanja pegawai menjaga kesehatan fiskal APBD/APBN. Ketentuan PP 11/2017 jo PP 17/2020 merinci ketiga variabel tersebut. Dengan demikian, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Kondisi geografis, jumlah penduduk, dan belanja pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perencanaan birokrasi profesional tidak boleh terikat oleh peta politik hasil pemilu.</li><li><strong>C</strong>: Ketersediaan armada kendaraan merupakan aspek sarana penunjang dan bukan faktor demografi.</li><li><strong>D</strong>: Suku bunga pinjaman bank adalah instrumen pasar modal dan moneter di luar formasi ASN.</li><li><strong>E</strong>: Daftar vendor rekanan swasta berkaitan dengan pengadaan barang/jasa dan bukan kebutuhan SDM.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memperhatikan kondisi geografis daerah • jumlah penduduk, dan rasio alokasi anggaran belanja pegawai</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Prinsip kesetaraan kesempatan bagi seluruh WNI dalam pengadaan calon Pegawai ASN diwujudkan melalui kewajiban instansi untuk...", "options": ["Merekrut pegawai baru melalui musyawarah tertutup pejabat", "Membatasi pendaftaran hanya bagi kerabat aparatur aktif", "Memprioritaskan lulusan dari satu almamater pimpinan dinas", "Mengumumkan lowongan kebutuhan jabatan secara terbuka", "Menjual formulir pendaftaran melalui pihak ketiga swasta"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Keterbukaan Pengadaan Calon Pegawai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Instansi Pemerintah wajib <strong>mengumumkan secara terbuka kepada masyarakat</strong> adanya kebutuhan jabatan agar setiap WNI memiliki <strong>kesempatan yang sama</strong> menjadi ASN.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan perwujudan transparansi publik dalam rekrutmen aparatur negara yang tepat. Keterbukaan informasi menjamin setiap warga negara yang memenuhi kualifikasi dapat mendaftar tanpa ada kuota tersembunyi. Undang-Undang Nomor 20 Tahun 2023 mewajibkan pengumuman secara terbuka kepada masyarakat. Oleh karena itu, opsi D merupakan jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Mengumumkan lowongan kebutuhan jabatan secara terbuka</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perekrutan tertutup melanggar prinsip transparansi dan rentan terhadap korupsi seleksi.</li><li><strong>B</strong>: Membatasi pendaftar pada kerabat dinas merupakan pelanggaran berat nepotisme.</li><li><strong>C</strong>: Afiliasi almamater kampus pimpinan dilarang dijadikan dasar diskriminasi pelamaran.</li><li><strong>E</strong>: Seleksi aparatur negara berprinsip transparan, akuntabel, dan bebas pungutan komersial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengumumkan secara terbuka kepada masyarakat • mempunyai kesempatan yang sama</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jabatan administrasi dalam instansi pemerintah yang dapat diisi melalui seleksi pengadaan PNS secara nasional adalah...", "options": ["Khusus pada kelompok Jabatan Pelaksana", "Seluruh posisi Jabatan Administrator eselon tiga", "Posisi pimpinan Jabatan Pengawas kepala subbagian", "Seluruh jenjang Jabatan Pimpinan Tinggi Pratama", "Posisi pimpinan Jabatan Pimpinan Tinggi Utama"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jabatan yang Diisi Pengadaan PNS Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan administrasi yang dapat diisi melalui pengadaan PNS secara nasional adalah <strong>khusus pada Jabatan Pelaksana</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan batasan pengisian jalur seleksi CPNS formasi umum yang tepat. Formasi terbuka bagi pelamar baru umum pada rumpun administrasi hanya dialokasikan untuk tingkat pelaksana, sedangkan administrator dan pengawas diisi melalui mekanisme promosi karier internal. Ketentuan regulasi menegaskan khusus pada Jabatan Pelaksana. Dengan demikian, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>A. Khusus pada kelompok Jabatan Pelaksana</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Jabatan administrator diisi melalui promosi atau mutasi PNS berpengalaman dan bukan CPNS baru.</li><li><strong>C</strong>: Jabatan pengawas diisi melalui jenjang karier promosi pegawai yang telah memenuhi kualifikasi.</li><li><strong>D</strong>: JPT Pratama diisi melalui seleksi terbuka (*open bidding*) bagi pejabat berpengalaman.</li><li><strong>E</strong>: JPT Utama diangkat melalui penetapan presiden dari kalangan profesional berintegritas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengadaan PNS dilakukan secara nasional • Jabatan Administrasi (khusus pada Jabatan Pelaksana)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jenjang Jabatan Fungsional Keahlian yang dapat diisi melalui seleksi pengadaan PNS secara nasional adalah...", "options": ["Jabatan Fungsional Ahli Utama dan Ahli Madya", "Jabatan Fungsional Penyelia dan Fungsional Mahir", "Jabatan Fungsional Ahli Pertama dan Ahli Muda", "Jabatan Fungsional Terampil dan Fungsional Pemula", "Jabatan Administrator Utama dan Penata Keahlian"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jenjang JF Keahlian dalam Pengadaan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan Fungsional Keahlian yang dapat diisi melalui pengadaan PNS secara nasional adalah <strong>Jabatan Fungsional Ahli Pertama dan Ahli Muda</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jenjang masuk awal formasi fungsional keahlian yang tepat. Jalur CPNS memfasilitasi lulusan sarjana untuk menduduki jenjang Ahli Pertama serta lulusan pascasarjana/spesialis tertentu untuk Ahli Muda. Regulasi menegaskan formasi JF keahlian dibuka untuk Ahli Pertama dan Ahli Muda. Oleh karena itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Jabatan Fungsional Ahli Pertama dan Ahli Muda</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ahli Utama dan Madya merupakan jenjang senior yang dicapai melalui kenaikan jenjang promosi.</li><li><strong>B</strong>: Penyelia dan mahir merupakan tingkatan pada rumpun jabatan fungsional keterampilan.</li><li><strong>D</strong>: Terampil dan pemula adalah klasifikasi formasi pada jabatan fungsional keterampilan.</li><li><strong>E</strong>: Administrator merupakan jabatan manajerial dan bukan rumpun fungsional keahlian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan Fungsional Keahlian • Jabatan Fungsional Ahli Pertama dan Ahli Muda</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jenjang Jabatan Fungsional Keterampilan yang dialokasikan dalam seleksi pengadaan PNS nasional adalah...", "options": ["Jabatan Fungsional Penyelia dan Jabatan Mahir", "Jabatan Fungsional Ahli Madya dan Ahli Utama", "Jabatan Fungsional Ahli Muda dan Ahli Pertama", "Jabatan Administrator dan Jabatan Pengawas Lini", "Jabatan Fungsional Pemula dan Jabatan Terampil"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jenjang JF Keterampilan dalam Pengadaan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan Fungsional Keterampilan yang dapat diisi melalui pengadaan PNS secara nasional adalah <strong>Jabatan Fungsional Pemula dan Terampil</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jenjang awal rekrutmen lulusan diploma dan vokasi kepegawaian yang tepat. Formasi awal terbuka bagi jenjang Pemula (kualifikasi menengah) dan Terampil (kualifikasi diploma). Ketentuan PP 11/2017 jo PP 17/2020 menyatakan formasi keterampilan nasional dialokasikan bagi Pemula dan Terampil. Dengan demikian, opsi E adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Jabatan Fungsional Pemula dan Jabatan Terampil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyelia dan mahir adalah jenjang lanjutan yang diraih melalui kenaikan jabatan berkala.</li><li><strong>B</strong>: Ahli madya dan utama merupakan strata kepakaran puncak jabatan fungsional keahlian.</li><li><strong>C</strong>: Ahli muda dan pertama merupakan kategori pada rumpun fungsional keahlian bergelar sarjana.</li><li><strong>D</strong>: Administrator dan pengawas merupakan kelompok jabatan manajerial operasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan Fungsional Keterampilan • Jabatan Fungsional Pemula dan Terampil</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Secara kronologis berurutan, tahapan resmi dalam penyelenggaraan pengadaan PNS terdiri atas...", "options": ["Pelamaran, masa percobaan, seleksi berkas, dan pengangkatan", "Perencanaan, pengumuman, pelamaran, seleksi, hasil, percobaan, pengangkatan", "Pengumuman kelulusan, masa dinas, magang kerja, dan sumpah jabatan", "Pelatihan dasar, seleksi kompetensi, wawancara direktur, dan SK dinas", "Penetapan NIP, orientasi kerja, pelamaran, dan pembekalan materi tugas"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuh Tahapan Lengkap Pengadaan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengadaan PNS dilakukan melalui 7 tahapan resmi: <strong>perencanaan</strong>, <strong>pengumuman lowongan</strong>, <strong>pelamaran</strong>, <strong>seleksi</strong>, <strong>pengumuman hasil seleksi</strong>, <strong>masa percobaan</strong>, dan <strong>pengangkatan menjadi PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan siklus alur rekrutmen aparatur negara yang lengkap dan urut secara kronologis. Alur ini dimulai dari penyiapan formasi nasional hingga pengangkatan definitif setelah menuntaskan masa percobaan satu tahun. Regulasi merinci ketujuh tahapan tersebut secara sistematis. Oleh karena itu, opsi B merupakan jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Perencanaan, pengumuman, pelamaran, seleksi, hasil, percobaan, pengangkatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masa percobaan dijalani setelah pelamar dinyatakan lulus seleksi dan bukan sebelum seleksi.</li><li><strong>C</strong>: Kelulusan diumumkan setelah seluruh rangkaian seleksi kompetensi rampung dievaluasi.</li><li><strong>D</strong>: Pelatihan dasar CPNS diselenggarakan di dalam masa percobaan sebelum pengangkatan PNS.</li><li><strong>E</strong>: Penetapan nomor induk pegawai (NIP) diproses setelah peserta lulus seleksi akhir.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengadaan PNS sebagaimana dimaksud dilakukan melalui tahapan • perencanaan, pengumuman lowongan, pelamaran, seleksi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Tiga tahapan ujian seleksi pengadaan PNS yang wajib diselenggarakan secara objektif adalah...", "options": ["Uji kebugaran jasmani, tes psikologi mendalam, dan wawancara ormas", "Seleksi berkas keluarga, tes kepribadian dinas, dan portofolio kerja", "Tes bahasa asing, ujian kepemimpinan regu, dan verifikasi keuangan", "Seleksi administrasi, seleksi kompetensi dasar, dan seleksi kompetensi bidang", "Seleksi kesehatan umum, wawancara pejabat daerah, dan uji bakat seni"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Tahapan Ujian Seleksi Pengadaan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyelenggaraan seleksi pengadaan PNS terdiri dari 3 (tiga) tahap objektif: <strong>seleksi administrasi</strong>, <strong>seleksi kompetensi dasar (SKD)</strong>, dan <strong>seleksi kompetensi bidang (SKB)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan tiga tahapan filtrasi baku penerimaan CPNS nasional yang tepat. Ketiga tahap ini menguji keabsahan berkas legal pelamar, kemampuan dasar wawasan kebangsaan/intelegensia, serta penguasaan bidang teknis jabatan. Aturan perundangan merinci seleksi terdiri atas seleksi administrasi, SKD, dan SKB. Dengan demikian, opsi D adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>D. Seleksi administrasi, seleksi kompetensi dasar, dan seleksi kompetensi bidang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Wawancara ormas dilarang keras dan melanggar prinsip netralitas seleksi aparatur sipil.</li><li><strong>B</strong>: Latar belakang berkas keluarga bukan bagian dari penilaian kompetensi objektif calon pegawai.</li><li><strong>C</strong>: Verifikasi keuangan privat tidak menjadi tahapan seleksi pengadaan CPNS formasi umum.</li><li><strong>E</strong>: Uji bakat kesenian bukan bagian dari tiga tahapan utama pengadaan aparatur nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penyelenggaraan seleksi sebagaimana dimaksud terdiri dari 3 (tiga) tahap • seleksi administrasi, seleksi kompetensi dasar, dan seleksi kompetensi bidang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Fungsi utama dari penyelenggaraan tahap seleksi administrasi dalam pengadaan PNS adalah...", "options": ["Mencocokkan persyaratan administrasi dengan dokumen pelamar", "Mengukur tingkat kecerdasan intelegensia dan logika abstrak", "Menilai penguasaan substansi teknis spesialisasi jabatan", "Menguji kemampuan fisik lari ketahanan di lapangan terbuka", "Menentukan besaran gaji pokok yang akan diterima pegawai"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan dan Fungsi Seleksi Administrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan <strong>seleksi administrasi</strong> adalah untuk <strong>mencocokkan antara persyaratan administrasi dengan dokumen pelamaran yang disampaikan oleh pelamar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A mendefinisikan esensi seleksi administrasi secara tepat dan lugas. Tim verifikator memeriksa keabsahan ijazah, transkrip, surat lamaran, dan syarat formal agar pelamar memenuhi kriteria minimum formasi. Ketentuan regulasi menegaskan fungsi mencocokkan persyaratan dengan dokumen pelamaran. Oleh karena itu, opsi A merupakan jawaban yang benar.</p><p>Jawaban tepat: <strong>A. Mencocokkan persyaratan administrasi dengan dokumen pelamar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pengukuran kecerdasan intelegensia umum merupakan fungsi dari seleksi kompetensi dasar (SKD).</li><li><strong>C</strong>: Penilaian penguasaan spesialisasi teknis merupakan fungsi seleksi kompetensi bidang (SKB).</li><li><strong>D</strong>: Uji ketahanan fisik hanya dilakukan pada formasi khusus seperti penjaga tahanan/kesamaptaan.</li><li><strong>E</strong>: Besaran gaji pokok ditentukan oleh skala golongan ruang pangkat sesuai regulasi pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Seleksi administrasi dalam tahap ini adalah untuk mencocokkan • persyaratan administrasi dengan dokumen pelamaran</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C4 - Analisis", "question": "Penyelenggaraan seleksi pengadaan PNS wajib dilakukan secara objektif dengan menitikberatkan penilaian pada...", "options": ["Besaran kontribusi finansial yang disetorkan pelamar", "Afiliasi politik orang tua dan asal partai pendukung", "Kompetensi, kualifikasi, dan persyaratan jabatan", "Tingkat kedekatan hubungan keluarga dengan panitia seleksi", "Rekomendasi dari tokoh masyarakat dan pejabat daerah"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Objektivitas Seleksi Pengadaan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyelenggaraan seleksi pengadaan PNS wajib dilakukan melalui <strong>penilaian secara objektif berdasarkan kompetensi, kualifikasi, dan persyaratan lain yang dibutuhkan oleh jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan pilar objektivitas meritokrasi pengadaan aparatur sipil negara yang tepat. Seleksi yang transparan menjaring talenta unggul yang benar-benar kompeten tanpa celah kecurangan atau intervensi nepotisme. Aturan undang-undang menetapkan penilaian berbasis kompetensi, kualifikasi, dan persyaratan jabatan. Dengan demikian, opsi C adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>C. Kompetensi, kualifikasi, dan persyaratan jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyetoran uang dalam seleksi merupakan tindak pidana suap dan gratifikasi terlarang.</li><li><strong>B</strong>: Afiliasi politik keluarga bertentangan dengan asas netralitas aparatur sipil negara.</li><li><strong>D</strong>: Kedekatan kekerabatan dengan panitia melanggar larangan konflik kepentingan birokrasi.</li><li><strong>E</strong>: Surat rekomendasi informal tidak diakui dan dilarang memengaruhi sistem skor seleksi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>penilaian secara objektif berdasarkan kompetensi, kualifikasi • kebutuhan jabatan untuk diisi dari calon Pegawai ASN</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_tak_p01_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_tak_p01_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_tak_p01_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Tata Aturan Kepegawaian Paket 01";
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
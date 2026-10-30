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
const STG_KEY = [0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 0, 2, 4, 1, 3, 0, 2, 4, 1, 3, 1, 3, 0, 2, 4, 1, 3, 0, 2, 4, 2, 4, 1, 3, 0, 2, 4, 1, 3, 0, 1, 3, 0, 2, 4, 3, 0, 2, 4, 1, 4, 1, 3, 0, 2, 4, 1, 3, 0, 2];
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
const questions=[{"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Rancangan Undang-Undang tentang Aparatur Sipil Negara disetujui oleh DPR-RI pada Rapat Paripurna tanggal...", "options": ["3 Oktober 2023", "1 Oktober 2023", "17 Agustus 2023", "28 Oktober 2023", "31 Oktober 2023"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Persetujuan RUU ASN oleh DPR-RI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>RUU ASN <strong>disetujui oleh DPR-RI pada Rapat Paripurna tanggal 3 Oktober 2023</strong> sebelum disahkan menjadi undang-undang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan tanggal yang tepat saat DPR-RI memberikan persetujuan paripurna terhadap RUU ASN. Persetujuan legislatif ini menjadi landasan formal bagi presiden untuk mengesahkan undang-undang aparatur sipil negara yang baru. Catatan resmi risalah sidang mencatat secara eksplisit persetujuan paripurna pada 3 Oktober 2023. Oleh karena itu, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>A. 3 Oktober 2023</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan peringatan Hari Kesaktian Pancasila dalam kalender nasional.</li><li><strong>C</strong>: Merupakan peringatan hari kemerdekaan Republik Indonesia dan bukan tanggal sidang paripurna.</li><li><strong>D</strong>: Merupakan peringatan Hari Sumpah Pemuda dalam sejarah nasional bangsa.</li><li><strong>E</strong>: Merupakan tanggal pengesahan UU oleh Presiden dan bukan tanggal persetujuan DPR.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Rapat Paripurna tanggal 3 Oktober 2023 • disetujui oleh DPR-RI</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Rancangan Undang-Undang Aparatur Sipil Negara disahkan oleh Presiden Joko Widodo pada 31 Oktober 2023 menjadi...", "options": ["Undang-Undang Nomor 5 Tahun 2014", "Undang-Undang Nomor 20 Tahun 2023", "Undang-Undang Nomor 30 Tahun 2014", "Undang-Undang Nomor 11 Tahun 2020", "Undang-Undang Nomor 1 Tahun 2022"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengesahan UU Nomor 20 Tahun 2023</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>RUU ASN disahkan pada 31 Oktober 2023 menjadi <strong>Undang-Undang Nomor 20 Tahun 2023 Tentang Aparatur Sipil Negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan nomor undang-undang kepegawaian yang sah dan berlaku saat ini. UU Nomor 20 Tahun 2023 menjadi payung hukum utama penyelenggaraan manajemen aparatur sipil negara di seluruh Indonesia. Lembaran Negara Republik Indonesia menegaskan pengesahan menjadi UU Nomor 20 Tahun 2023. Dengan demikian, opsi B adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>B. Undang-Undang Nomor 20 Tahun 2023</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan undang-undang ASN lama yang telah digantikan oleh regulasi baru.</li><li><strong>C</strong>: Merupakan Undang-Undang tentang Administrasi Pemerintahan.</li><li><strong>D</strong>: Merupakan Undang-Undang tentang Cipta Kerja awal sebelum perubahan.</li><li><strong>E</strong>: Merupakan Undang-Undang tentang Hubungan Keuangan Pusat dan Daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>disahkan oleh Presiden Joko Widodo • Undang-Undang Nomor 20 Tahun 2023</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Lembaga legislatif yang memberikan persetujuan terhadap RUU ASN pada Rapat Paripurna tanggal 3 Oktober 2023 adalah...", "options": ["Badan Pemeriksa Keuangan", "Dewan Perwakilan Daerah", "Dewan Perwakilan Rakyat", "Mahkamah Konstitusi", "Komisi Yudisial"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Lembaga Legislatif Persetujuan UU ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>RUU ASN <strong>disetujui oleh DPR-RI</strong> (Dewan Perwakilan Rakyat) dalam Rapat Paripurna sebelum diundangkan secara resmi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan lembaga legislatif pemegang kekuasaan membentuk undang-undang yang tepat. Sesuai konstitusi, persetujuan bersama antara DPR dan Presiden menjadi syarat sah lahirnya undang-undang baru. Ketentuan hukum menegaskan persetujuan pengesahan RUU ASN dilakukan oleh DPR-RI. Maka dari itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>C. Dewan Perwakilan Rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: BPK merupakan lembaga pemeriksa pengelolaan dan tanggung jawab keuangan negara.</li><li><strong>B</strong>: DPD memiliki wewenang pengajuan dan pembahasan terbatas bidang otonomi daerah.</li><li><strong>D</strong>: MK merupakan lembaga yudikatif penguji undang-undang terhadap konstitusi.</li><li><strong>E</strong>: KY bertugas mengusulkan pengangkatan hakim agung dan menegakkan martabat hakim.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>disetujui oleh DPR-RI • Rapat Paripurna tanggal 3 Oktober 2023</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Undang-Undang Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara secara sah menggantikan...", "options": ["Undang-Undang Nomor 8 Tahun 1974", "Undang-Undang Nomor 43 Tahun 1999", "Undang-Undang Nomor 32 Tahun 2004", "Undang-Undang Nomor 5 Tahun 2014", "Undang-Undang Nomor 23 Tahun 2014"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi yang Digantikan UU 20/2023</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Undang-Undang Nomor 20 Tahun 2023 secara resmi <strong>menggantikan Undang-Undang Nomor 5 Tahun 2014 tentang Aparatur Sipil Negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan regulasi terdahulu yang digantikan oleh undang-undang aparatur terbaru. Penggantian ini dilakukan guna mengakomodasi transformasi manajemen ASN, digitalisasi birokrasi, dan fleksibilitas mobilitas talenta. Rujukan resmi modul menyatakan undang-undang ini menggantikan UU 5/2014. Oleh sebab itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Undang-Undang Nomor 5 Tahun 2014</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan undang-undang pokok-pokok kepegawaian masa orde baru.</li><li><strong>B</strong>: Merupakan undang-undang perubahan atas pokok-pokok kepegawaian tahun 1999.</li><li><strong>C</strong>: Merupakan undang-undang pemerintahan daerah yang telah dicabut.</li><li><strong>E</strong>: Merupakan undang-undang tentang pemerintahan daerah yang berlaku saat ini.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Undang-undang ini menggantikan Undang-Undang Nomor 5 Tahun 2014 • Aparatur Sipil Negara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 77 Undang-Undang Nomor 20 Tahun 2023, ketentuan undang-undang ini mulai berlaku pada...", "options": ["Enam bulan sejak disetujui oleh DPR", "Awal tahun anggaran berikutnya", "Satu tahun setelah ditetapkan presiden", "Dua tahun setelah masa sosialisasi", "Tanggal diundangkan regulasi bersangkutan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Waktu Pemberlakuan UU ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 77 UU Nomor 20 Tahun 2023, undang-undang ini <strong>mulai berlaku pada tanggal diundangkan</strong> yaitu 31 Oktober 2023.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan klausul keberlakuan undang-undang yang tepat. Asas keberlakuan pada tanggal diundangkan memberikan kepastian hukum seketika bagi seluruh ketentuan baru dalam manajemen aparatur negara. Rujukan modul menegaskan undang-undang mulai berlaku pada tanggal diundangkan. Dengan demikian, opsi E adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Tanggal diundangkan regulasi bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Masa tenggang enam bulan tidak dicantumkan dalam klausul pemberlakuan UU 20/2023.</li><li><strong>B</strong>: Pemberlakuan undang-undang tidak ditunda hingga awal tahun anggaran baru.</li><li><strong>C</strong>: Masa transisi satu tahun bukan merupakan klausul tanggal mulai berlaku pasal 77.</li><li><strong>D</strong>: Masa sosialisasi dua tahun bukan klausul resmi pengundangan pasal penutup.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Undang-Undang ASN ini mulai berlaku pada tanggal diundangkan • (Pasal 77 UU Nomor 20 Tahun 2023)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Semua peraturan perundang-undangan yang merupakan peraturan pelaksanaan dari UU ASN lama dinyatakan tetap berlaku dengan syarat...", "options": ["Tidak bertentangan dan belum diganti undang-undang baru", "Mendapat persetujuan tertulis dari seluruh pimpinan kementerian", "Telah diuji materiil oleh Mahkamah Konstitusi Republik Indonesia", "Disesuaikan berdasarkan kebijakan otonomi daerah di wilayah masing-masing", "Telah diperbarui nomor registrasinya pada lembaran negara"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keabsahan Peraturan Pelaksanaan Lama</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Peraturan pelaksanaan dinyatakan <strong>masih tetap berlaku sepanjang tidak bertentangan dan belum diganti berdasarkan Undang-Undang ini</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A memuat syarat kumulatif keabsahan hukum aturan turunan lama. Prinsip ini mencegah kekosongan hukum (rechtsvacuum) selama peraturan pelaksanaan yang baru sedang disusun oleh instansi terkait. Ketentuan perundang-undangan menegaskan aturan lama tetap berlaku sepanjang tidak bertentangan dan belum diganti. Oleh karena itu, opsi A merupakan pilihan yang benar.</p><p>Jawaban tepat: <strong>A. Tidak bertentangan dan belum diganti undang-undang baru</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Persetujuan tertulis seluruh menteri bukan syarat yuridis keberlakuan aturan turunan.</li><li><strong>C</strong>: Uji materiil MK adalah mekanisme pengujian norma bukan syarat keabsahan transisi.</li><li><strong>D</strong>: Kepala daerah tidak berwenang mengubah keberlakuan peraturan pelaksanaan nasional.</li><li><strong>E</strong>: Pembaruan nomor registrasi tidak diperlukan bagi peraturan pelaksanaan yang masih berlaku.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dinyatakan masih tetap berlaku sepanjang tidak bertentangan • belum diganti</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pejabat negara yang mengesahkan Undang-Undang Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara adalah...", "options": ["Menteri Pendayagunaan Aparatur Negara", "Presiden Republik Indonesia Joko Widodo", "Ketua Dewan Perwakilan Rakyat Republik", "Kepala Badan Kepegawaian Negara Pusat", "Ketua Majelis Permusyawaratan Rakyat"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pejabat yang Mengesahkan UU 20/2023</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Undang-Undang Nomor 20 Tahun 2023 secara konstitusional <strong>disahkan oleh Presiden Joko Widodo</strong> pada tanggal 31 Oktober 2023.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan pejabat negara yang memegang kekuasaan pemerintahan dan pengesahan undang-undang sesuai konstitusi. Pengesahan oleh presiden memberikan kekuatan mengikat secara hukum bagi seluruh warga negara dan aparatur birokrasi. Naskah resmi pengundangan menyebutkan pengesahan dilakukan oleh Presiden Joko Widodo. Maka dari itu, opsi B adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Presiden Republik Indonesia Joko Widodo</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menteri PAN-RB merupakan menteri teknis yang mengoordinasikan bidang aparatur negara.</li><li><strong>C</strong>: Ketua DPR memimpin sidang persetujuan legislatif bukan pejabat yang mengesahkan UU.</li><li><strong>D</strong>: Kepala BKN adalah pelaksana teknis pembinaan dan pengawasan manajemen kepegawaian.</li><li><strong>E</strong>: Ketua MPR memimpin lembaga permusyawaratan tertinggi negara pada sidang pelantikan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>disahkan oleh Presiden Joko Widodo • Undang-Undang Nomor 20 Tahun 2023</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Nomor pasal dalam Undang-Undang Nomor 20 Tahun 2023 yang menetapkan tanggal mulai berlakunya regulasi ini adalah...", "options": ["Pasal 55", "Pasal 62", "Pasal 77", "Pasal 84", "Pasal 91"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pasal Pemberlakuan UU 20/2023</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketentuan penutup mengenai saat mulai berlakunya undang-undang tercantum dalam <strong>Pasal 77 UU Nomor 20 Tahun 2023</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan pasal penutup yang secara tegas mengatur waktu keberlakuan regulasi aparatur. Pasal 77 menjadi pedoman bagi seluruh praktisi hukum dan pengelola kepegawaian dalam menerapkan norma baru. Rujukan modul menyebutkan eksplisit Pasal 77 UU Nomor 20 Tahun 2023. Dengan demikian, opsi C adalah rujukan pasal yang tepat.</p><p>Jawaban tepat: <strong>C. Pasal 77</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan pasal penutup yang mengatur saat berlakunya regulasi kepegawaian nasional.</li><li><strong>B</strong>: Merupakan pasal yang mengatur substansi pembinaan karier dan bukan ketentuan penutup.</li><li><strong>D</strong>: UU Nomor 20 Tahun 2023 tidak memiliki bab pasal hingga nomor 84.</li><li><strong>E</strong>: UU Nomor 20 Tahun 2023 tidak memiliki rumusan pasal hingga nomor 91.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Undang-Undang ASN ini mulai berlaku pada tanggal diundangkan • (Pasal 77 UU Nomor 20 Tahun 2023)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Tanggal resmi pengesahan dan pengundangan Undang-Undang Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara adalah...", "options": ["3 Oktober 2023", "15 Oktober 2023", "20 Oktober 2023", "31 Oktober 2023", "10 November 2023"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggal Resmi Pengesahan UU ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>RUU ASN disahkan menjadi Undang-Undang Nomor 20 Tahun 2023 pada <strong>31 Oktober 2023</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan tanggal yuridis diundangkannya undang-undang aparatur sipil negara dalam lembaran negara. Tanggal ini menjadi tonggak baru dimulainya era transformasi manajemen ASN yang terpadu dan profesional. Naskah hukum perundangan menegaskan pengesahan dilakukan pada 31 Oktober 2023. Oleh karena itu, opsi D merupakan jawaban yang benar.</p><p>Jawaban tepat: <strong>D. 31 Oktober 2023</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tanggal persetujuan RUU pada Rapat Paripurna DPR-RI.</li><li><strong>B</strong>: Bukan tanggal yuridis pengesahan undang-undang aparatur sipil negara.</li><li><strong>C</strong>: Bukan tanggal resmi lembaran negara untuk pengundangan UU 20/2023.</li><li><strong>E</strong>: Merupakan peringatan Hari Pahlawan nasional dalam kalender resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Rancangan Undang-Undang (RUU) Aparatur Sipil Negara (ASN) • pada 31 Oktober telah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C4 - Analisis", "question": "Berdasarkan prinsip hukum ketentuan penutup UU 20/2023, peraturan turunan lama yang bertentangan dengan UU baru berstatus...", "options": ["Tetap mengikat secara mutlak seluruh instansi", "Diberikan dispensasi pelaksanaan selama lima tahun", "Disesuaikan mengikuti petunjuk teknis kepala daerah", "Menjadi bahan evaluasi pembinaan aparatur berkala", "Tidak berlaku lagi demi kepastian hukum yang adil"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Hukum Ketentuan Penutup UU 20/2023</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Peraturan pelaksanaan lama <strong>masih tetap berlaku sepanjang tidak bertentangan dan belum diganti</strong>. Secara *a contrario*, norma yang bertentangan <strong>tidak berlaku lagi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan analisis hukum normatif yang tepat atas ketentuan penutup. Asas hukum *lex posterior derogat legi priori* menegaskan peraturan yang bertentangan dengan undang-undang baru kehilangan daya laku hukumnya demi kepastian tatanan hukum birokrasi. Rumusan ketentuan penutup membatasi keberlakuan hanya pada aturan yang tidak bertentangan. Dengan demikian, opsi E adalah simpulan yuridis yang tepat.</p><p>Jawaban tepat: <strong>E. Tidak berlaku lagi demi kepastian hukum yang adil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aturan yang bertentangan tidak boleh lagi diterapkan dalam tata kelola kepegawaian.</li><li><strong>B</strong>: Dispensasi lima tahun tidak pernah diberikan bagi ketentuan yang melanggar norma UU.</li><li><strong>C</strong>: Kepala daerah tidak memiliki wewenang menegakkan peraturan yang telah bertentangan.</li><li><strong>D</strong>: Bukan sekadar bahan evaluasi berkala, melainkan langsung kehilangan kekuatan hukumnya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 7) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>semua peraturan perundang-undangan yang merupakan peraturan • dinyatakan masih tetap berlaku sepanjang tidak bertentangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Aparatur Sipil Negara (ASN) didefinisikan secara resmi dalam undang-undang sebagai profesi bagi...", "options": ["Seluruh tenaga honorer dan pekerja harian lepas perkantoran", "Pegawai Negeri Sipil dan Pegawai Pemerintah Perjanjian Kerja", "Karyawan badan usaha milik negara dan daerah di wilayah", "Tenaga konsultan swasta yang dikontrak instansi pemerintah", "Staf pembantu pimpinan lembaga nonstruktural independen"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Profesi Aparatur Sipil Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Aparatur Sipil Negara (disingkat ASN) adalah profesi bagi Pegawai Negeri Sipil dan pegawai pemerintah dengan perjanjian kerja yang bekerja pada instansi pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan definisi profesi aparatur sipil negara yang sah dan baku. Undang-undang menyatukan dua entitas kepegawaian resmi pemerintah (PNS dan PPPK) di bawah payung profesi ASN guna menjamin integritas dan standar kompetensi pelayanan publik. Definisi modul merumuskan ASN sebagai profesi bagi PNS dan PPPK. Oleh karena itu, opsi A merupakan jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Pegawai Negeri Sipil dan Pegawai Pemerintah Perjanjian Kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tenaga honorer dan pekerja harian lepas bukan merupakan kelompok profesi ASN resmi.</li><li><strong>C</strong>: Karyawan BUMN dan BUMD tunduk pada hukum ketenagakerjaan dan bukan berstatus ASN.</li><li><strong>D</strong>: Konsultan swasta terikat pada perjanjian perdata pengadaan jasa konsultansi.</li><li><strong>E</strong>: Staf pembantu nonstruktural berstatus pegawai non-ASN di luar manajemen kepegawaian negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Aparatur Sipil Negara (disingkat ASN) adalah profesi bagi Pegawai Negeri Sipil • dan pegawai pemerintah dengan perjanjian kerja yang bekerja pada instansi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pegawai ASN terdiri atas PNS dan PPPK yang secara resmi diangkat oleh...", "options": ["Pimpinan organisasi masyarakat di daerah kerja", "Ketua asosiasi profesi pegawai tingkat provinsi", "Pejabat Pembina Kepegawaian di instansi pemerintah", "Pejabat pembuat komitmen pada satuan kerja kantor", "Direktur badan usaha rekanan pengadaan barang jasa"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengangkatan Pegawai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai ASN <strong>diangkat oleh pejabat pembina kepegawaian dan diserahi tugas dalam suatu jabatan pemerintahan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan pejabat yang memiliki kewenangan menetapkan pengangkatan aparatur negara. Pejabat Pembina Kepegawaian (seperti Menteri, Kepala Lembaga, atau Gubernur/Bupati/Walikota) memegang mandat hukum dalam pembinaan karier pegawai ASN di lingkungannya. Ketentuan hukum menegaskan pengangkatan aparatur dilakukan oleh Pejabat Pembina Kepegawaian. Dengan demikian, opsi C adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>C. Pejabat Pembina Kepegawaian di instansi pemerintah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Organisasi masyarakat tidak memiliki kewenangan hukum dalam tata kelola kepegawaian negara.</li><li><strong>B</strong>: Asosiasi profesi berperan dalam penegakan etika dan bukan pejabat pengangkat ASN.</li><li><strong>D</strong>: Pejabat pembuat komitmen mengelola keuangan proyek dan bukan pengangkat pegawai ASN.</li><li><strong>E</strong>: Rekan usaha swasta tidak memiliki wewenang dalam struktur kepegawaian pemerintahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>yang diangkat oleh pejabat pembina • kepegawaian dan diserahi tugas dalam suatu jabatan pemerintahan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Ciri pembeda utama pengangkatan Pegawai Negeri Sipil (PNS) dibandingkan dengan PPPK adalah PNS diangkat secara...", "options": ["Periodik berdasarkan masa kontrak tahunan kerja", "Musiman mengikuti siklus panen komoditas pangan", "Bergiliran antarkeluarga pegawai purnatugas", "Tetap oleh pejabat pembina kepegawaian instansi", "Sementara selama pelaksanaan proyek strategis"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Pengangkatan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS adalah WNI yang memenuhi syarat tertentu, <strong>diangkat sebagai Pegawai ASN secara tetap oleh pejabat pembina kepegawaian untuk menduduki jabatan pemerintahan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan karakter yuridis status PNS yang berorientasi pada kesinambungan karier jangka panjang hingga batas usia pensiun. Berbeda dengan PPPK yang berbasis kontrak perjanjian kerja, PNS memegang status pegawai tetap aparatur negara. Rumusan modul menegaskan PNS diangkat sebagai Pegawai ASN secara tetap. Oleh sebab itu, opsi C adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Tetap oleh pejabat pembina kepegawaian instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengangkatan periodik berbasis masa kontrak merupakan karakteristik utama status PPPK.</li><li><strong>B</strong>: Siklus musiman bukan dasar pengangkatan pegawai aparatur sipil negara.</li><li><strong>C</strong>: Pengangkatan ASN dilarang keras berbasis hubungan kekerabatan atau pewarisan jabatan.</li><li><strong>E</strong>: Penugasan sementara berbasis proyek merupakan karakteristik pekerja kontrak proyek.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diangkat sebagai Pegawai ASN secara • tetap oleh pejabat pembina kepegawaian</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pegawai Pemerintah dengan Perjanjian Kerja (PPPK) diangkat dalam jabatan pemerintahan berdasarkan...", "options": ["Surat penunjukan lisan dari kepala seksi operasional", "Hasil undian terbuka pelamar pada portal kepegawaian", "Warisan jabatan keluarga aparatur yang telah pensiun", "Persetujuan tertulis paguyuban pegawai purnabakti", "Perjanjian kerja untuk jangka waktu tertentu kedinasan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakteristik Pengangkatan PPPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PPPK diangkat <strong>berdasarkan perjanjian kerja untuk jangka waktu tertentu dalam rangka melaksanakan tugas pemerintahan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan dasar ikatan kerja legalitas aparatur PPPK yang tepat. Hubungan kerja PPPK dituangkan dalam perjanjian kerja yang menetapkan hak, kewajiban, masa tugas, dan target kinerja secara terukur. Sesuai ketentuan modul, PPPK diangkat berdasarkan perjanjian kerja untuk jangka waktu tertentu. Maka dari itu, opsi D adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>E. Perjanjian kerja untuk jangka waktu tertentu kedinasan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengangkatan aparatur negara wajib melalui surat keputusan resmi dan bukan penunjukan lisan.</li><li><strong>B</strong>: Sistem seleksi ASN berbasis meritokrasi kompetensi objektif dan bukan undian keberuntungan.</li><li><strong>C</strong>: Jabatan birokrasi pemerintahan bukan hak milik privat yang dapat diwariskan ke keluarga.</li><li><strong>D</strong>: Paguyuban pensiunan tidak memiliki legalitas kewenangan mengangkat aparatur sipil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diangkat berdasarkan perjanjian kerja untuk jangka waktu tertentu • Adapun PPPK merupakan warga negara Indonesia yang memenuhi syarat</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pegawai ASN berkedudukan sebagai unsur aparatur negara yang mengemban tugas pokok untuk...", "options": ["Melaksanakan kebijakan yang ditetapkan pimpinan instansi", "Menyusun undang-undang bersama dewan perwakilan rakyat", "Membuat putusan hukum yang mengikat badan peradilan", "Menentukan arah ideologi dan platform partai politik", "Mengelola keuangan perusahaan swasta multinasional"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan dan Tugas Pegawai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai ASN <strong>berkedudukan sebagai unsur aparatur negara</strong> yang bertugas <strong>melaksanakan kebijakan yang ditetapkan oleh pimpinan Instansi Pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan tugas fungsional kedudukan aparatur sipil negara yang tepat. Sebagai motor penggerak eksekutif, pegawai bertanggung jawab mengimplementasikan arah program kerja instansinya. Sesuai ketentuan modul, aparatur berkedudukan menjalankan kebijakan yang ditetapkan oleh pimpinan instansi pemerintah. Oleh karena itu, opsi A adalah pilihan yang benar.</p><p>Jawaban tepat: <strong>A. Melaksanakan kebijakan yang ditetapkan pimpinan instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kewenangan legislasi pembentukan undang-undang berada pada ranah DPR bersama presiden.</li><li><strong>C</strong>: Kekuasaan kehakiman yudikatif dijalankan secara independen oleh Mahkamah Agung.</li><li><strong>D</strong>: Aparatur sipil negara dilarang keras terlibat dalam perumusan platform partai politik.</li><li><strong>E</strong>: Tata kelola korporasi swasta bukan merupakan ruang lingkup penugasan aparatur sipil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pegawai ASN berkedudukan sebagai unsur aparatur negara • kebijakan yang ditetapkan oleh pimpinan Instansi Pemerintah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Sesuai asas netralitas yang diatur dalam Pasal 9 UU Nomor 20 Tahun 2023, kewajiban pegawai ASN terhadap politik adalah...", "options": ["Menjadi anggota penasihat independen pada perumusan kebijakan partai", "Bebas dari pengaruh dan intervensi semua golongan dan partai politik", "Menyalurkan aspirasi politik kedinasan melalui fraksi perwakilan daerah", "Memfasilitasi kegiatan konsultasi publik bersama perwakilan partai", "Memberikan masukan teknis penyusunan program kerja calon kepala daerah"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Asas Netralitas Pegawai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai ASN <strong>harus bebas dari pengaruh dan intervensi semua golongan dan partai politik</strong> demi menjamin keadilan pelayanan publik.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan bunyi norma netralitas aparatur sipil negara yang tepat. Netralitas politik menjamin birokrasi pemerintahan tetap fokus bekerja secara profesional, adil, dan tidak terkooptasi oleh kepentingan partisan. Rumusan modul menegaskan pegawai ASN harus bebas dari pengaruh dan intervensi semua golongan dan partai politik. Dengan demikian, opsi B adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>B. Bebas dari pengaruh dan intervensi semua golongan dan partai politik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menjadi penasihat kebijakan partai politik melanggar larangan keterlibatan partisan ASN.</li><li><strong>C</strong>: Aparatur sipil negara tidak memiliki fraksi politik di dewan perwakilan rakyat daerah.</li><li><strong>D</strong>: Fasilitasi kegiatan konsultasi partai melanggar larangan penggunaan fasilitas untuk parpol.</li><li><strong>E</strong>: Memberikan masukan program pasangan calon melanggar larangan keterlibatan pilkada.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>harus bebas dari • pengaruh dan intervensi semua golongan dan partai politik</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Ketentuan mengenai kedudukan ASN dan asas bebas dari intervensi partai politik diatur dalam UU 20/2023 pada...", "options": ["Pasal 1 dan 2", "Pasal 4 dan 5", "Pasal 8 dan 9", "Pasal 12 dan 13", "Pasal 16 dan 17"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum Kedudukan dan Netralitas ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kedudukan Pegawai ASN sebagai pelaksana kebijakan dan asas netralitas diatur dalam <strong>Pasal 8 dan 9 UU Nomor 20 Tahun 2023</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan nomor pasal yang tepat dalam regulasi kepegawaian nasional terbaru. Pasal 8 dan Pasal 9 menjadi fondasi yuridis tegaknya profesionalisme birokrasi yang steril dari kepentingan partisan. Rujukan modul menegaskan kedudukan dan netralitas aparatur pada kedua pasal tersebut. Oleh karena itu, opsi C adalah rujukan yang benar.</p><p>Jawaban tepat: <strong>C. Pasal 8 dan 9</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan pasal yang memuat definisi umum serta asas dasar penyelenggaraan ASN.</li><li><strong>B</strong>: Merupakan pasal yang mengatur nilai dasar serta kode etik dan kode perilaku ASN.</li><li><strong>D</strong>: Merupakan pasal yang mengatur jenis jabatan manajerial dan jenjang karier struktural.</li><li><strong>E</strong>: Merupakan pasal yang mengatur hak dan komponen penghargaan materiel pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>(Pasal 8 dan 9 UU Nomor • 20 Tahun 2023)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Setiap Pegawai ASN yang diserahi tugas negara berhak memperoleh penghasilan yang bersumber dari...", "options": ["Sumbangan sukarela masyarakat pengguna layanan publik", "Bagi hasil keuntungan proyek investasi swasta rekanan", "Iuran wajib bulanan para pegawai senior di kementerian", "Ketentuan resmi berdasarkan peraturan perundang-undangan", "Dana talangan pribadi pimpinan satuan kerja dinas"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Penghasilan Pegawai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai ASN yang diserahi tugas jabatan pemerintahan <strong>diberikan penghasilan berdasarkan peraturan perundang-undangan</strong> yang sah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan dasar legalitas pemberian kompensasi keuangan bagi aparatur yang tepat. Hak keuangan pegawai bersumber dari kas negara yang dialokasikan dan dicairkan sesuai standar aturan hukum. Modul menegaskan penghasilan aparatur wajib berlandaskan peraturan perundang-undangan. Dengan demikian, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>D. Ketentuan resmi berdasarkan peraturan perundang-undangan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penerimaan uang di luar tarif resmi dari masyarakat dikategorikan sebagai pungutan liar.</li><li><strong>B</strong>: Penerimaan imbalan langsung dari pihak rekanan swasta memicu gratifikasi terlarang.</li><li><strong>C</strong>: Kesejahteraan dinas pegawai bukan berasal dari pungutan internal sesama pegawai.</li><li><strong>E</strong>: Penggajian resmi aparatur negara menggunakan anggaran belanja negara dan bukan dana privat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diserahi tugas dalam suatu jabatan pemerintahan atau diserahi • tugas negara lainnya dan diberikan penghasilan berdasarkan peraturan perundangundangan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Di lingkungan instansi pemerintah Republik Indonesia, Pegawai ASN dikelompokkan secara sah ke dalam...", "options": ["Pegawai Tetap Daerah dan Pegawai Harian Lepas Pusat", "Tenaga Honorer Kategori I dan Tenaga Kontrak Magang", "Pegawai Organik Lembaga dan Pekerja Alih Daya Swasta", "Tenaga Bantuan Operasional dan Staf Khusus Kementerian", "Pegawai Negeri Sipil dan Pegawai Pemerintah Perjanjian Kerja"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komposisi Pegawai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai undang-undang, Pegawai ASN terdiri atas dua jenis status resmi: <strong>Pegawai Negeri Sipil (PNS)</strong> dan <strong>Pegawai Pemerintah dengan Perjanjian Kerja (PPPK)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan klasifikasi yuridis kepegawaian yang tepat dan diakui secara nasional. Transformasi aparatur menghapus status honorer dan menyatukan status kepegawaian ke dalam dua jalur resmi pemerintah. Modul menegaskan Pegawai ASN terdiri atas PNS dan PPPK. Oleh karena itu, opsi E merupakan klasifikasi yang tepat.</p><p>Jawaban tepat: <strong>E. Pegawai Negeri Sipil dan Pegawai Pemerintah Perjanjian Kerja</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Istilah pegawai harian lepas bukan merupakan nomenklatur status pegawai aparatur sipil.</li><li><strong>B</strong>: Penggolongan tenaga honorer telah dihapuskan dalam reformasi manajemen aparatur.</li><li><strong>C</strong>: Pekerja alih daya pihak ketiga memiliki ikatan kerja dengan vendor swasta dan bukan ASN.</li><li><strong>D</strong>: Staf khusus kementerian memiliki status penugasan politis nonkarier di luar jabatan ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pegawai ASN terdiri dari Pegawai Negeri Sipil (PNS) dan Pegawai • Pemerintah dengan Perjanjian Kerja (PPPK)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Syarat mutlak status kewarganegaraan untuk dapat diangkat menjadi Pegawai Negeri Sipil adalah...", "options": ["Warga Negara Indonesia yang memenuhi persyaratan", "Warga negara asing pemegang visa izin tinggal tetap", "Mantan warga negara asing yang memiliki keahlian sains", "Penduduk berstatus dwi-kewarganegaraan aktif resmi", "Warga negara asing peserta program beasiswa bilateral"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Kewarganegaraan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS disyaratkan secara mutlak merupakan <strong>warga negara Indonesia yang memenuhi syarat tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan syarat kedaulatan utama dalam pengisian jabatan birokrasi pemerintahan. Pengabdian pada tugas negara menuntut kesetiaan tunggal kepada Negara Kesatuan Republik Indonesia. Ketentuan modul menegaskan pelamar wajib berstatus Warga Negara Indonesia yang memenuhi kriteria jabatan. Dengan demikian, opsi A adalah persyaratan yang benar.</p><p>Jawaban tepat: <strong>A. Warga Negara Indonesia yang memenuhi persyaratan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Warga negara asing tidak diperkenankan menjadi aparatur sipil negara pengelola pemerintahan.</li><li><strong>C</strong>: Keahlian teknologi tenaga asing dapat difasilitasi melalui konsultan ahli non-ASN.</li><li><strong>D</strong>: Hukum nasional Republik Indonesia tidak mengakui kewarganegaraan ganda bagi aparatur.</li><li><strong>E</strong>: Penerima beasiswa luar negeri berstatus mahasiswa dan bukan calon pegawai aparatur sipil.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 9) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pegawai Negeri Sipil yang selanjutnya disingkat PNS adalah warga negara • Indonesia yang memenuhi syarat tertentu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Undang-Undang Nomor 20 Tahun 2023, jabatan dalam ASN secara garis besar terdiri atas...", "options": ["Jabatan Militer Aktif dan Jabatan Sipil Biasa", "Jabatan Struktural dan Jabatan Politis Daerah", "Jabatan Manajerial dan Jabatan Nonmanajerial", "Jabatan Kehormatan dan Jabatan Operasional Staf", "Jabatan Eksekutif Perusahaan dan Staf Teknis Ahli"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Klasifikasi Dua Jabatan ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai undang-undang, <strong>Jabatan dalam ASN terdiri atas 2 (dua) jabatan yaitu: Jabatan Manajerial dan Jabatan Nonmanajerial</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan dikotomi pengelompokan jabatan baru dalam UU ASN yang tepat. Pengelompokan ini membedakan posisi yang mengemban kepemimpinan unit (manajerial) dengan posisi yang fokus pada keahlian pelayanan teknis fungsional (nonmanajerial). Rujukan modul menegaskan jabatan terdiri atas Jabatan Manajerial dan Nonmanajerial. Oleh sebab itu, opsi A adalah klasifikasi yang benar.</p><p>Jawaban tepat: <strong>C. Jabatan Manajerial dan Jabatan Nonmanajerial</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jabatan militer diatur dalam undang-undang pertahanan tentara nasional tersendiri.</li><li><strong>B</strong>: Jabatan politis bukan merupakan kelompok jabatan dalam sistem karier aparatur sipil.</li><li><strong>D</strong>: Jabatan kehormatan bersifat seremonial di luar rumpun formasi resmi kepegawaian.</li><li><strong>E</strong>: Jabatan eksekutif perusahaan merupakan posisi korporasi komersial swasta atau BUMN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan dalam ASN terdiri atas 2 (dua) jabatan yaitu: • Jabatan Manajerial</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Sekelompok jabatan dalam birokrasi yang memiliki fungsi memimpin unit organisasi dan membawahkan pegawai langsung disebut...", "options": ["Jabatan Fungsional Terampil", "Jabatan Penasihat Ahli Khusus", "Jabatan Pelaksana Mandiri", "Jabatan Manajerial", "Jabatan Konsultan Teknis Luar"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Jabatan Manajerial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan Manajerial adalah sekelompok jabatan yang memiliki fungsi memimpin unit organisasi</strong> dan memiliki pegawai langsung di bawahnya.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D mendefinisikan kelompok jabatan manajerial secara tepat. Jabatan ini memegang kendali kepemimpinan, pengawasan, serta pengoordinasian sumber daya manusia demi pencapaian tujuan unit kerja. Sesuai rumusan modul, kelompok jabatan pemegang fungsi memimpin unit organisasi adalah Jabatan Manajerial. Dengan demikian, opsi B adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>D. Jabatan Manajerial</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jabatan fungsional terampil berfokus pada eksekusi tugas teknis non-kepemimpinan.</li><li><strong>B</strong>: Penasihat khusus bertugas memberikan telaah pertimbangan tanpa bawahan langsung.</li><li><strong>C</strong>: Jabatan pelaksana berstatus nonmanajerial pelaksana tugas operasional rutin.</li><li><strong>E</strong>: Konsultan teknis berstatus pekerja kontrak eksternal di luar birokrasi pemerintahan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan Manajerial adalah sekelompok jabatan yang memiliki fungsi • memimpin unit organisasi dan memiliki pegawai yang berkedudukan langsung di</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Tiga tingkatan hierarki yang menyusun kelompok Jabatan Manajerial dalam sistem birokrasi ASN adalah...", "options": ["Jabatan Direktur, Kepala Divisi, dan Staf Khusus", "Jabatan Komisioner, Sekretaris Jenderal, dan Staf Ahli", "Jabatan Peneliti Utama, Perekayasa, dan Pranata Humas", "Jabatan Pembina Utama, Pembina Madya, dan Pratama", "Jabatan Pimpinan Tinggi, Administrator, dan Pengawas"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Tingkatan Jabatan Manajerial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan Manajerial terdiri atas: <strong>a. Jabatan Pimpinan Tinggi (JPT), b. Jabatan Administrator, dan c. Jabatan Pengawas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merinci tiga strata kepemimpinan manajerial aparatur negara secara tepat. Ketiganya membentuk rantai komando mulai dari pimpinan tingkat tinggi (JPT), tingkat menengah (Administrator), hingga tingkat operasional (Pengawas). Ketentuan perundang-undangan menyebutkan rincian manajerial terdiri atas JPT, Administrator, dan Pengawas. Oleh karena itu, opsi E adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Jabatan Pimpinan Tinggi, Administrator, dan Pengawas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Direktur dan divisi merupakan nomenklatur struktur internal pada tata kelola korporasi.</li><li><strong>B</strong>: Komisioner merupakan pimpinan lembaga nonstruktural independen hasil seleksi publik.</li><li><strong>C</strong>: Peneliti dan pranata humas merupakan rumpun jabatan fungsional nonmanajerial.</li><li><strong>D</strong>: Pembina utama dan madya merupakan nama pangkat golongan ruang kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan Manajerial terdiri atas: • Jabatan Pimpinan Tinggi (JPT)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jabatan Pimpinan Tinggi (JPT) sebagai manajerial tingkat tinggi diklasifikasikan ke dalam tiga jenjang yaitu...", "options": ["JPT Utama, JPT Madya, dan JPT Pratama", "JPT Penasihat, JPT Pengarah, dan JPT Pelaksana", "JPT Eksekutif, JPT Legislatif, dan JPT Yudikatif", "JPT Kehormatan, JPT Pembina, dan JPT Pendamping", "JPT Nasional, JPT Wilayah, dan JPT Distrik Kantor"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Klasifikasi Jenjang JPT</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan Pimpinan Tinggi terdiri atas JPT Utama, JPT Madya, dan JPT Pratama</strong> yang merupakan jabatan manajerial tingkat tinggi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan klasifikasi resmi jenjang Jabatan Pimpinan Tinggi dalam hukum kepegawaian. Ketiga tingkatan ini mencakup posisi kepemimpinan puncak (eselon I dan II) pada instansi pemerintah pusat maupun daerah. Regulasi modul menegaskan pembagian JPT terdiri atas Utama, Madya, dan Pratama. Dengan demikian, opsi D adalah pilihan yang tepat.</p><p>Jawaban tepat: <strong>A. JPT Utama, JPT Madya, dan JPT Pratama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pengarah dan pelaksana merupakan fungsi kepanitiaan dan bukan jenjang karier JPT.</li><li><strong>C</strong>: Eksekutif, legislatif, dan yudikatif merupakan cabang trias politika kekuasaan negara.</li><li><strong>D</strong>: Istilah JPT kehormatan dan pendamping bukan klasifikasi yuridis dalam undang-undang.</li><li><strong>E</strong>: Nasional, wilayah, dan distrik merupakan pembagian teritorial administratif umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan Pimpinan Tinggi terdiri atas JPT Utama, JPT Madya, dan JPT • Pratama yang merupakan jabatan manajerial tingkat tinggi yang bertanggung</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jabatan manajerial tingkat menengah yang bertanggung jawab memimpin dan mengoordinasikan pelayanan publik serta administrasi adalah...", "options": ["Jabatan Fungsional Ahli Muda", "Jabatan Administrator Instansi", "Jabatan Pengawas Satuan Kerja", "Jabatan Pelaksana Lapangan", "Jabatan Pimpinan Tinggi Utama"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Jabatan Administrator</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan Administrator</strong> merupakan <strong>Jabatan Manajerial tingkat menengah yang bertanggung jawab memimpin dan mengoordinasikan pelaksanaan strategi pencapaian tujuan organisasi serta pelayanan publik dan administrasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan nomenklatur jabatan manajerial tingkat menengah yang tepat. Pejabat Administrator (setara eselon III) bertugas menjembatani kebijakan strategis pimpinan tinggi ke dalam aksi operasional pelayanan. Sesuai rumusan modul, jabatan manajerial tingkat menengah adalah Jabatan Administrator. Oleh sebab itu, opsi E merupakan jawaban yang benar.</p><p>Jawaban tepat: <strong>B. Jabatan Administrator Instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Fungsional ahli muda berkedudukan nonmanajerial pada keahlian profesi spesifik.</li><li><strong>C</strong>: Jabatan pengawas merupakan posisi manajerial tingkat dasar di bawah administrator.</li><li><strong>D</strong>: Jabatan pelaksana merupakan staf operasional rutin tanpa wewenang manajerial.</li><li><strong>E</strong>: JPT Utama memegang tampuk manajerial tingkat tinggi pada puncak kementerian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Merupakan Jabatan Manajerial tingkat menengah yang bertanggung • b. Jabatan Administrator</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Terkait integritas tata kelola kelembagaan, ketentuan PP 11/2017 jo PP 17/2020 melarang pejabat administrasi untuk...", "options": ["Mengikuti pelatihan kepemimpinan yang ditugaskan instansi", "Menghadiri rapat pembahasan anggaran dinas di kementerian", "Merangkap jabatan struktural dengan Jabatan Fungsional", "Menggunakan kendaraan dinas operasional untuk tugas luar", "Menyampaikan usulan perbaikan tata laksana pelayanan"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Larangan Rangkap Jabatan Administrasi dan Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketentuan manajemen PNS menegaskan: <strong>setiap pejabat administrasi tidak diperbolehkan merangkap jabatan dengan Jabatan Fungsional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan larangan rangkap jabatan yang bertujuan mencegah benturan kepentingan dan menjaga akuntabilitas pelaksanaan wewenang. Pejabat tidak boleh memegang komando manajerial sekaligus mengklaim butir angka kredit jabatan fungsional secara bersamaan. Regulasi modul menegaskan larangan merangkap jabatan fungsional bagi pejabat administrasi. Dengan demikian, opsi A adalah larangan yang tepat.</p><p>Jawaban tepat: <strong>C. Merangkap jabatan struktural dengan Jabatan Fungsional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengikuti diklat kepemimpinan merupakan hak pengembangan kompetensi pegawai.</li><li><strong>B</strong>: Menghadiri rapat koordinasi anggaran merupakan tugas sah pejabat administrasi.</li><li><strong>D</strong>: Pemanfaatan sarana operasional dinas diperbolehkan untuk menunjang kedinasan.</li><li><strong>E</strong>: Penyampaian masukan tata laksana selaras dengan prinsip continuous improvement.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>setiap pejabat administrasi • tidak diperbolehkan merangkap jabatan dengan Jabatan Fungsional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jabatan manajerial tingkat dasar dalam birokrasi aparatur sipil negara yang membawahkan langsung pejabat pelaksana adalah...", "options": ["Jabatan Fungsional Utama", "Jabatan Administrator Unit", "Jabatan Pimpinan Pratama", "Jabatan Pengawas Instansi", "Jabatan Pelaksana Teknis"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Jabatan Pengawas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan Pengawas</strong> merupakan <strong>Jabatan Manajerial tingkat dasar yang bertanggung jawab dan berperan dalam mengelola, memotivasi, dan mendukung pengembangan Pegawai ASN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan posisi kepemimpinan lini pertama (*first-line manager*) dalam hierarki birokrasi yang tepat. Pejabat Pengawas (setara eselon IV) mengawasi dan mengendalikan langsung kegiatan pelayanan yang dilakukan oleh staf pelaksana. Sesuai rumusan modul, jabatan manajerial tingkat dasar adalah Jabatan Pengawas. Maka dari itu, opsi B adalah jawaban yang tepat.</p><p>Jawaban tepat: <strong>D. Jabatan Pengawas Instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Fungsional utama merupakan jenjang tertinggi profesi keahlian nonstruktural.</li><li><strong>B</strong>: Administrator memimpin jenjang manajerial tingkat menengah di atas pengawas.</li><li><strong>C</strong>: Pimpinan pratama menduduki jabatan manajerial tingkat tinggi memimpin eselon dua.</li><li><strong>E</strong>: Pelaksana merupakan staf operasional dasar yang berada di bawah kendali pengawas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Merupakan Jabatan Manajerial tingkat dasar yang bertanggung jawab • c. • Jabatan Pengawas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan Pasal 16 UU Nomor 20 Tahun 2023, pengisian setiap jabatan ASN wajib ditetapkan berdasarkan...", "options": ["Daftar urut kepangkatan tertua pada satuan kerja", "Rekomendasi lisan perwakilan serikat pegawai kantor", "Besaran tunjangan kinerja yang dialokasikan satker", "Masa pengabdian purna tugas di wilayah perbatasan", "Kompetensi dan pemenuhan persyaratan jabatan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penetapan Jabatan Sesuai Kompetensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sesuai Pasal 16 UU Nomor 20 Tahun 2023, <strong>setiap jabatan ditetapkan sesuai dengan kompetensi dan persyaratan jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E mencerminkan pilar meritokrasi penempatan aparatur yang tepat. Jabatan tidak boleh diberikan berdasarkan senioritas pasif atau kedekatan informal, melainkan harus berbasis kesesuaian standar kompetensi teknis, manajerial, dan sosiokultural pelamar. Aturan modul menegaskan penetapan jabatan sesuai kompetensi dan persyaratan jabatan. Oleh karena itu, opsi D adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>E. Kompetensi dan pemenuhan persyaratan jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Senioritas nomor urut kepangkatan semata bukan tolok ukur utama pengisian jabatan.</li><li><strong>B</strong>: Rekomendasi lisan organisasi pekerja tidak sah dalam prosedur penempatan ASN.</li><li><strong>C</strong>: Alokasi anggaran tunjangan kinerja tidak menentukan kualifikasi kompetensi jabatan.</li><li><strong>D</strong>: Masa bakti wilayah perbatasan diapresiasi melalui tunjangan khusus bukan penetapan jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Setiap jabatan sebagaimana dimaksud ditetapkan sesuai dengan kompetensi • dan persyaratan jabatan (Pasal 16 UU Nomor 20 Tahun 2023)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Peraturan Pemerintah yang mengatur manajemen Pegawai Negeri Sipil sebagaimana telah diubah dengan PP 17 Tahun 2020 adalah...", "options": ["Peraturan Pemerintah Nomor 11 Tahun 2017", "Peraturan Pemerintah Nomor 42 Tahun 2004", "Peraturan Pemerintah Nomor 53 Tahun 2010", "Peraturan Pemerintah Nomor 46 Tahun 2011", "Peraturan Pemerintah Nomor 30 Tahun 2019"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi Manajemen PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Regulasi induk tata kelola PNS adalah <strong>Peraturan Pemerintah Nomor 11 Tahun 2017 tentang Manajemen PNS sebagaimana telah diubah dengan PP Nomor 17 Tahun 2020</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan nomor regulasi manajemen PNS yang tepat. PP 11/2017 menjadi pedoman teknis operasional pengadaan, pangkat, jabatan, pola karier, mutasi, penilaian kinerja, hingga pemberhentian PNS. Ketentuan peraturan pemerintah menyebutkan secara jelas PP 11 Tahun 2017 stdd PP 17 Tahun 2020. Dengan demikian, opsi A adalah jawaban yang benar.</p><p>Jawaban tepat: <strong>A. Peraturan Pemerintah Nomor 11 Tahun 2017</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: PP Nomor 42 Tahun 2004 mengatur tentang Pembinaan Jiwa Korps dan Kode Etik PNS.</li><li><strong>C</strong>: PP Nomor 53 Tahun 2010 mengatur disiplin lama yang telah dicabut oleh PP 94/2021.</li><li><strong>D</strong>: PP Nomor 46 Tahun 2011 mengatur penilaian prestasi kerja lama yang telah dicabut.</li><li><strong>E</strong>: PP Nomor 30 Tahun 2019 mengatur khusus tentang Penilaian Kinerja PNS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Tentang Manajemen Pegawai Negeri Sipil • (yang selanjutnya disebut PP 11/2017 stdd PP 17/2020)</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Selain dilarang merangkap jabatan fungsional, kewajiban mutlak yang harus dijamin oleh pejabat administrasi adalah terlaksananya...", "options": ["Penyelenggaraan koordinasi rutin bersama instansi vertikal lain", "Akuntabilitas jabatan sesuai standar operasional dinas", "Pemberian fasilitas program pembinaan karier bagi staf baru", "Pengembangan sistem digitalisasi tata naskah dinas persuratan", "Penyediaan fasilitas sarana kerja pendukung efektivitas dinas"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Pejabat Administrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pejabat administrasi <strong>harus menjamin Akuntabilitas Jabatan</strong>, yaitu terlaksananya seluruh kegiatan sesuai SOP dan rencana kerja.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan kewajiban substansi kepemimpinan administrasi yang tepat. Menjamin akuntabilitas jabatan memastikan anggaran dan wewenang dipergunakan tepat sasaran serta menghasilkan pelayanan publik yang berkualitas tinggi. Regulasi kepegawaian secara tegas mewajibkan pejabat administrasi menjamin Akuntabilitas Jabatan. Oleh karena itu, opsi B merupakan pilihan yang benar.</p><p>Jawaban tepat: <strong>B. Akuntabilitas jabatan sesuai standar operasional dinas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Koordinasi eksternal merupakan aktivitas kerja sama operasional dan bukan definisi akuntabilitas jabatan.</li><li><strong>C</strong>: Pembinaan staf merupakan fungsi manajemen SDM dan bukan kewajiban akuntabilitas jabatan spesifik.</li><li><strong>D</strong>: Digitalisasi naskah dinas merupakan sarana tata kelola persuratan kantor.</li><li><strong>E</strong>: Penyediaan sarana kerja merupakan pemenuhan fasilitas logistik dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 10) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjamin Akuntabilitas Jabatan, yaitu meliputi terlaksananya: • tidak diperbolehkan merangkap jabatan dengan Jabatan Fungsional dan harus</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "PNS yang akan diangkat dalam jabatan administrasi wajib memenuhi tiga jenis standar kompetensi jabatan yaitu...", "options": ["Kompetensi Konseptual, Strategis, dan Hubungan Antarlembaga", "Kompetensi Administratif, Vokasional, dan Komunikasi Publik", "Kompetensi Operasional, Analitis, dan Manajemen Risiko Kerja", "Kompetensi Teknis, Manajerial, dan Sosial Kultural Aparatur", "Kompetensi Kepemimpinan, Kelembagaan, dan Transformasi Digital"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Standar Kompetensi Jabatan Administrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Untuk dapat diangkat dalam <strong>jabatan administrasi</strong>, PNS harus memenuhi standar kompetensi yang mencakup <strong>Kompetensi Teknis</strong>, <strong>Kompetensi Manajerial</strong>, dan <strong>Kompetensi Sosial Kultural</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Regulasi kepegawaian menetapkan bahwa pengangkatan PNS ke dalam jabatan administrasi mensyaratkan pemenuhan tiga pilar standar kompetensi, yaitu Kompetensi Teknis untuk keahlian tugas, Kompetensi Manajerial untuk kepemimpinan dan pengelolaan organisasi, serta Kompetensi Sosial Kultural untuk wawasan kebangsaan dalam masyarakat majemuk. Dengan demikian, ketiga kompetensi tersebut menjadi prasyarat mutlak pengangkatan jabatan administrasi.</p><p>Jawaban tepat: <strong>D. Kompetensi Teknis, Manajerial, dan Sosial Kultural Aparatur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan merupakan pembagian standar kompetensi baku pengangkatan aparatur ke dalam jabatan administrasi.</li><li><strong>B</strong>: Merupakan rumpun keahlian umum dalam kepegawaian dan bukan nomenklatur resmi tiga kompetensi jabatan administrasi.</li><li><strong>C</strong>: Merupakan keterampilan manajerial operasional dan bukan klasifikasi resmi standar kompetensi kepegawaian.</li><li><strong>E</strong>: Merupakan fokus pengembangan organisasi era modern dan bukan nomenklatur tri-kompetensi resmi ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kompetensi Teknis • Kompetensi Manajerial, dan Kompetensi Sosial Kultural</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pengukuran Kompetensi Teknis aparatur sipil negara didasarkan pada kualifikasi dan spesialisasi pendidikan, pengalaman kerja teknis, serta...", "options": ["Jenjang kepangkatan serta masa kerja dinas aparatur", "Riwayat penugasan kepemimpinan lintas unit organisasi", "Tingkat keikutsertaan dalam pembinaan wawasan kebangsaan", "Pemenuhan sertifikasi manajerial kepemimpinan nasional", "Pelatihan teknis fungsional kedinasan yang telah diikuti"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Indikator Pengukuran Kompetensi Teknis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kompetensi Teknis</strong> diukur dari <strong>tingkat dan spesialisasi pendidikan</strong>, <strong>pelatihan teknis fungsional</strong>, dan <strong>pengalaman bekerja secara teknis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Berdasarkan ketentuan standar kompetensi kepegawaian, Kompetensi Teknis mencerminkan penguasaan spesifik tugas substansi yang diukur melalui tiga indikator utama, yaitu tingkat dan spesialisasi pendidikan, pelatihan teknis fungsional, serta pengalaman bekerja secara teknis. Keikutsertaan dalam pelatihan teknis fungsional melengkapi kualifikasi formal dan pengalaman kerja teknis pegawai.</p><p>Jawaban tepat: <strong>E. Pelatihan teknis fungsional kedinasan yang telah diikuti</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan komponen riwayat kepangkatan umum dan bukan indikator spesifik pengukur Kompetensi Teknis.</li><li><strong>B</strong>: Merupakan indikator yang digunakan dalam mengukur Kompetensi Manajerial aparatur.</li><li><strong>C</strong>: Merupakan aspek yang dinilai dalam lingkup Kompetensi Sosial Kultural aparatur.</li><li><strong>D</strong>: Merupakan bagian dari instrumen pengembangan Kompetensi Manajerial aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kompetensi Teknis diukur dari tingkat dan • pelatihan teknis fungsional, dan pengalaman bekerja</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Indikator utama yang digunakan dalam mengukur Kompetensi Manajerial aparatur sipil negara mencakup...", "options": ["Pendidikan, pelatihan struktural atau manajemen, dan kepemimpinan", "Pengalaman interaksi pelayanan pada lingkungan masyarakat yang majemuk", "Tingkat dan spesialisasi pendidikan serta pelatihan teknis fungsional", "Penguasaan keahlian teknis operasional serta sertifikasi keahlian khusus", "Pemenuhan target angka kredit kinerja serta portofolio profesi mandiri"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Indikator Pengukuran Kompetensi Manajerial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kompetensi Manajerial</strong> diukur dari <strong>tingkat pendidikan</strong>, <strong>pelatihan struktural atau manajemen</strong>, dan <strong>pengalaman kepemimpinan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Dalam sistem penilaian standar kompetensi ASN, Kompetensi Manajerial diarahkan untuk menilai kemampuan memimpin, mengelola, dan mengarahkan unit kerja. Ketentuan kepegawaian menetapkan bahwa kompetensi ini diukur secara terpadu melalui tingkat pendidikan aparatur, pelatihan struktural atau manajemen yang ditempuh, serta pengalaman kepemimpinan nyata dalam organisasi birokrasi.</p><p>Jawaban tepat: <strong>A. Pendidikan, pelatihan struktural atau manajemen, dan kepemimpinan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan indikator utama dalam pengukuran Kompetensi Sosial Kultural aparatur.</li><li><strong>C</strong>: Merupakan indikator yang dipakai dalam pengukuran Kompetensi Teknis pegawai.</li><li><strong>D</strong>: Merupakan persyaratan khusus penguasaan keahlian pada kelompok Jabatan Fungsional.</li><li><strong>E</strong>: Merupakan instrumen pengukuran capaian kinerja mandiri pejabat fungsional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kompetensi Manajerial diukur dari tingkat pendidikan, pelatihan • struktural atau manajemen, dan pengalaman kepemimpinan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penilaian Kompetensi Sosial Kultural aparatur sipil negara diukur melalui pengalaman kerja yang berkaitan dengan...", "options": ["Pengelolaan anggaran belanja dan evaluasi akuntabilitas kinerja satker", "Masyarakat majemuk dalam agama, suku, dan budaya berwawasan kebangsaan", "Koordinasi teknis fungsional dan pendampingan implementasi regulasi baru", "Penyusunan perencanaan strategis serta manajemen tata kelola birokrasi", "Pelaksanaan audit investigatif kepatuhan dan manajemen risiko organisasi"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Indikator Pengukuran Kompetensi Sosial Kultural</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kompetensi Sosial Kultural</strong> diukur dari <strong>pengalaman kerja berkaitan dengan masyarakat majemuk</strong> dalam hal <strong>agama, suku, dan budaya</strong> sehingga memiliki <strong>wawasan kebangsaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Ketentuan manajemen ASN menegaskan bahwa Kompetensi Sosial Kultural bertujuan memastikan setiap pegawai mampu menjadi perekat dan pemersatu bangsa dalam penyelenggaraan tugas pemerintahan. Pengukurannya dilakukan melalui pengalaman kerja aparatur dalam berinteraksi dengan masyarakat majemuk yang beraneka ragam latar belakang agama, suku, dan budaya.</p><p>Jawaban tepat: <strong>B. Masyarakat majemuk dalam agama, suku, dan budaya berwawasan kebangsaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan bidang tugas perbendaharaan dan akuntabilitas keuangan negara.</li><li><strong>C</strong>: Merupakan aspek pelaksanaan koordinasi teknis dan kepatuhan administratif.</li><li><strong>D</strong>: Merupakan ruang lingkup keahlian perencanaan manajerial pimpinan unit kerja.</li><li><strong>E</strong>: Merupakan fungsi pengawasan internal dan mitigasi risiko kelembagaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kompetensi Sosial • dalam hal agama, suku, dan budaya sehingga memiliki wawasan kebangsaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat administrator ditugaskan menjalani tugas belajar selama 12 bulan. Berdasarkan PP 11/2017 jo PP 17/2020, konsekuensi terhadap jabatannya adalah...", "options": ["Tetap memangku jabatan dengan penunjukan pelaksana tugas harian", "Dialihkan menjadi pejabat fungsional keahlian madya secara otomatis", "Diberhentikan dari jabatan administrasi yang sedang didudukinya", "Diturunkan satu tingkat ke dalam jabatan pengawas selama studi", "Dibebaskan sementara dari tugas dinas tanpa kehilangan jabatannya"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemberhentian Jabatan Administrasi karena Tugas Belajar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS <strong>diberhentikan dari jabatan administrasi</strong> apabila <strong>menjalani tugas belajar lebih dari 6 (enam) bulan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Berdasarkan ketentuan Pasal dalam PP 11/2017 stdd PP 17/2020, salah satu penyebab pemberhentian PNS dari jabatan administrasi adalah penugasan menjalani tugas belajar yang berdurasi lebih dari 6 bulan. Karena pejabat administrator tersebut menjalani tugas belajar selama 12 bulan, secara hukum ia wajib diberhentikan dari jabatan administrasi yang diembannya.</p><p>Jawaban tepat: <strong>C. Diberhentikan dari jabatan administrasi yang sedang didudukinya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ketentuan regulasi mewajibkan pemberhentian dari jabatan definitif bila tugas belajar melampaui 6 bulan.</li><li><strong>B</strong>: Pengalihan ke jabatan fungsional memerlukan mekanisme penyesuaian/inpassing tersendiri dan tidak terjadi otomatis.</li><li><strong>D</strong>: Penurunan jabatan hanya dapat dijatuhkan sebagai sanksi hukuman disiplin berat dan bukan akibat tugas belajar.</li><li><strong>E</strong>: Pembebasan sementara tanpa pemberhentian jabatan bertentangan dengan ketentuan PP 11/2017 jo PP 17/2020.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diberhentikan dari jabatan administrasi apabila • menjalani tugas belajar lebih dari 6 (enam) bulan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C4 - Analisis", "question": "Berikut ini merupakan alasan sah pemberhentian PNS dari jabatan administrasi sesuai regulasi, KECUALI...", "options": ["Mengundurkan diri secara resmi dari jabatan administrasi", "Diberhentikan untuk sementara waktu dari status sebagai PNS", "Menjalani cuti resmi di luar tanggungan negara dari instansi", "Menjalani cuti tahunan kedinasan sesuai hak pegawai aparatur", "Ditugaskan secara penuh pada penugasan luar jabatan administrasi"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyebab Pemberhentian dari Jabatan Administrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS <strong>diberhentikan dari jabatan administrasi</strong> apabila: <strong>mengundurkan diri</strong>, <strong>diberhentikan sementara sebagai PNS</strong>, <strong>menjalani cuti di luar tanggungan negara</strong>, <strong>tugas belajar lebih dari 6 bulan</strong>, <strong>ditugaskan penuh di luar jabatan administrasi</strong>, atau <strong>tidak memenuhi persyaratan jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sebagai pengecualian. Menjalani cuti tahunan merupakan hak normatif kedinasan berkala bagi aparatur sipil negara yang tidak mengakibatkan pemberhentian dari jabatan administrasi. Sebaliknya, opsi A, B, C, dan E merupakan sebab sah pemberhentian dari jabatan administrasi sebagaimana diatur secara limitatif dalam PP 11/2017 jo PP 17/2020.</p><p>Jawaban tepat: <strong>D. Menjalani cuti tahunan kedinasan sesuai hak pegawai aparatur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan alasan sah pemberhentian dari jabatan administrasi huruf a.</li><li><strong>B</strong>: Merupakan alasan sah pemberhentian dari jabatan administrasi huruf b.</li><li><strong>C</strong>: Merupakan alasan sah pemberhentian dari jabatan administrasi huruf c.</li><li><strong>E</strong>: Merupakan alasan sah pemberhentian dari jabatan administrasi huruf e.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>PNS dapat • diberhentikan dari jabatan administrasi apabila</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kelompok jabatan dalam aparatur yang mengutamakan kompetensi teknis tanpa tanggung jawab memimpin dan mengawasi kinerja pegawai adalah...", "options": ["Kelompok Jabatan Pengawas Wilayah", "Kelompok Jabatan Administrator Daerah", "Kelompok Jabatan Pimpinan Tinggi Madya", "Kelompok Jabatan Pimpinan Tinggi Pratama", "Kelompok Jabatan Nonmanajerial Aparatur"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Jabatan Nonmanajerial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan nonmanajerial</strong> adalah sekelompok jabatan yang <strong>mengutamakan kompetensi yang bersifat teknis sesuai bidangnya</strong> dan <strong>tidak memiliki tanggung jawab langsung dalam mengelola dan mengawasi kinerja pegawai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Berdasarkan klasifikasi jabatan aparatur sipil negara, jabatan dibagi menjadi Jabatan Manajerial dan Jabatan Nonmanajerial. Jabatan Nonmanajerial berfokus pada keahlian dan keterampilan teknis tanpa fungsi kepemimpinan struktural bawahan, yang terdiri atas Jabatan Fungsional dan Jabatan Pelaksana. Hal ini membedakannya secara tegas dari seluruh jenjang jabatan manajerial.</p><p>Jawaban tepat: <strong>E. Kelompok Jabatan Nonmanajerial Aparatur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan jenjang jabatan manajerial tingkat dasar yang mengawasi kinerja pejabat pelaksana.</li><li><strong>B</strong>: Merupakan jenjang jabatan manajerial tingkat menengah yang memimpin unit kerja administrator.</li><li><strong>C</strong>: Merupakan jenjang jabatan manajerial tingkat tinggi pada eselon madya.</li><li><strong>D</strong>: Merupakan jenjang jabatan manajerial tingkat tinggi pada eselon pratama.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan nonmanajerial adalah sekelompok jabatan yang mengutamakan • jawab langsung dalam mengelola dan mengawasi kinerja pegawai. Jabatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Akuntabilitas jabatan yang wajib dipenuhi oleh seorang pejabat administrator di lingkungan birokrasi pemerintahan adalah...", "options": ["Terlaksananya kegiatan secara efisien dan peningkatan kinerja berkesinambungan", "Pengendalian langsung pelaksanaan seluruh kegiatan pejabat pelaksana di unitnya", "Pelaksanaan kegiatan operasional rutin pelayanan sesuai standar operasional dinas", "Perumusan dan penetapan arah kebijakan strategis makro kepegawaian instansi", "Pengawasan independen penerapan sistem merit aparatur pada kementerian/lembaga"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akuntabilitas Jabatan Administrator</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Akuntabilitas <strong>Jabatan Administrator</strong> menjamin <strong>seluruh kegiatan yang sudah direncanakan dengan baik dan efisien sesuai SOP</strong> dan terselenggaranya <strong>peningkatan kinerja secara berkesinambungan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Ketentuan akuntabilitas jabatan dalam PP manajemen PNS menetapkan bahwa pejabat administrator bertanggung jawab memastikan perencanaan kegiatan terlaksana secara efisien sesuai SOP sekaligus memacu peningkatan kinerja organisasi secara berkelanjutan. Tanggung jawab ini menempatkan administrator sebagai penggerak utama efisiensi program kerja instansi.</p><p>Jawaban tepat: <strong>A. Terlaksananya kegiatan secara efisien dan peningkatan kinerja berkesinambungan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan wujud akuntabilitas jabatan yang menjadi tanggung jawab Jabatan Pengawas.</li><li><strong>C</strong>: Merupakan wujud akuntabilitas jabatan yang menjadi beban tugas Jabatan Pelaksana.</li><li><strong>D</strong>: Merupakan kewenangan manajerial tingkat tinggi pada jenjang Jabatan Pimpinan Tinggi.</li><li><strong>E</strong>: Merupakan mandat kelembagaan yang diemban oleh Kementerian PAN-RB.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>seluruh kegiatan yang sudah direncanakan dengan baik dan efisien sesuai • secara berkesinambungan, bagi Jabatan administrator</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Akuntabilitas jabatan yang menjadi tanggung jawab utama pegawai pada kelompok Jabatan Pelaksana adalah menjamin terlaksananya...", "options": ["Pengendalian seluruh kegiatan pelaksanaan tugas teknis pejabat pengawas", "Kegiatan tugas kedinasan secara tertib sesuai standar operasional prosedur", "Perencanaan program kerja unit kerja secara efisien dan berkesinambungan", "Evaluasi capaian kinerja strategis organisasi tingkat kementerian/lembaga", "Pembinaan profesi serta fasilitasi pengembangan kompetensi teknis pegawai"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Akuntabilitas Jabatan Pelaksana</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Akuntabilitas <strong>Jabatan Pelaksana</strong> adalah menjamin terlaksananya <strong>kegiatan sesuai dengan standar operasional prosedur</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Berdasarkan regulasi manajemen ASN, akuntabilitas jabatan bagi kelompok Jabatan Pelaksana difokuskan pada eksekusi tugas pelayanan dan pekerjaan administrasi rutin secara tepat dan taat pada SOP yang berlaku. Dengan memastikan setiap tahapan pekerjaan sesuai SOP, mutu output pelayanan satuan kerja dapat terjaga secara konsisten.</p><p>Jawaban tepat: <strong>B. Kegiatan tugas kedinasan secara tertib sesuai standar operasional prosedur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengendalian kegiatan pelaksana merupakan akuntabilitas Jabatan Pengawas, bukan pelaksana.</li><li><strong>C</strong>: Perencanaan efisien dan peningkatan kinerja berkesinambungan adalah akuntabilitas Jabatan Administrator.</li><li><strong>D</strong>: Evaluasi kinerja strategis makro merupakan tanggung jawab Jabatan Pimpinan Tinggi.</li><li><strong>E</strong>: Pembinaan profesi kepegawaian merupakan fungsi kementerian dan instansi pembina.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kegiatan sesuai dengan standar operasional prosedur, bagi Jabatan • pelaksana.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Sekelompok jabatan dalam birokrasi yang berisi fungsi dan tugas pelayanan berbasis keahlian serta keterampilan tertentu disebut...", "options": ["Jabatan Manajerial Tingkat Pratama", "Jabatan Administrasi Bidang Pengawasan", "Jabatan Fungsional Aparatur Sipil Negara", "Jabatan Eksekutif Perencanaan Pembangunan", "Jabatan Struktural Pelaksana Operasional"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Jabatan Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan Fungsional (JF)</strong> adalah sekelompok jabatan yang berisi <strong>fungsi dan tugas berkaitan dengan pelayanan fungsional</strong> yang berdasarkan pada <strong>keahlian dan keterampilan tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Ketentuan UU ASN dan peraturan pelaksanaannya mendefinisikan Jabatan Fungsional sebagai kelompok jabatan karier profesional aparatur yang bertugas menyelenggarakan pelayanan teknis fungsional berdasarkan disiplin keahlian atau keterampilan tertentu. Jabatan ini mandiri dan terbagi ke dalam kategori keahlian dan kategori keterampilan.</p><p>Jawaban tepat: <strong>C. Jabatan Fungsional Aparatur Sipil Negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan jabatan manajerial struktural eselon dua yang memimpin unit organisasi.</li><li><strong>B</strong>: Merupakan rumpun jabatan administrasi manajerial tingkat dasar (pengawas).</li><li><strong>D</strong>: Merupakan nomenklatur fungsional perencana dan bukan definisi umum rumpun JF.</li><li><strong>E</strong>: Merupakan sebutan jabatan administrasi operasional dan bukan jabatan berbasis keahlian profesi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 11) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jabatan Fungsional (JF) adalah sekelompok jabatan yang berisi fungsi • keahlian dan keterampilan tertentu. Jabatan fungsional dalam ASN terdiri atas</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jenjang Jabatan Fungsional Keahlian dari tingkatan tertinggi hingga tingkatan terendah secara berurutan adalah...", "options": ["Penyelia, Mahir, Terampil, dan Pemula", "Utama, Madya, Pratama, dan Administrator", "Pembina Utama, Penata, Pengatur, dan Juru", "Pengawas, Administrator, Pratama, dan Madya", "Ahli Utama, Ahli Madya, Ahli Muda, dan Ahli Pertama"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jenjang Jabatan Fungsional Keahlian</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan fungsional keahlian</strong> terdiri atas <strong>Ahli utama</strong>, <strong>Ahli madya</strong>, <strong>Ahli muda</strong>, dan <strong>Ahli pertama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Regulasi kepegawaian menetapkan secara hierarkis bahwa Jabatan Fungsional Keahlian terdiri atas 4 jenjang tingkatan, dimulai dari yang tertinggi yaitu Ahli Utama, diikuti Ahli Madya, Ahli Muda, dan jenjang awal yaitu Ahli Pertama. Setiap jenjang mencerminkan tingkat kesulitan tugas, kompetensi, dan persyaratan kualifikasi pendidikan minimal.</p><p>Jawaban tepat: <strong>E. Ahli Utama, Ahli Madya, Ahli Muda, dan Ahli Pertama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan susunan 4 jenjang tingkatan pada Jabatan Fungsional Keterampilan.</li><li><strong>B</strong>: Merupakan campuran antara jenjang jabatan pimpinan tinggi dan jabatan administrator.</li><li><strong>C</strong>: Merupakan susunan golongan ruang kepangkatan PNS tradisional (IV, III, II, I).</li><li><strong>D</strong>: Merupakan perpaduan jenjang jabatan administrasi dan jabatan manajerial tinggi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan fungsional keahlian terdiri atas Ahli utama, Ahli madya, Ahli muda dan • Ahli pertama. Sementara jabatan fungsional keterampilan terdiri atas Penyelia</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jenjang tingkatan resmi pada Jabatan Fungsional Keterampilan terdiri atas...", "options": ["Penyelia, Mahir, Terampil, dan Pemula", "Ahli Utama, Ahli Madya, dan Ahli Muda", "Administrator, Pengawas, dan Pelaksana", "Utama, Madya, Muda, dan Pertama", "Penata Tingkat Satu, Penata, dan Pengatur"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jenjang Jabatan Fungsional Keterampilan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan fungsional keterampilan</strong> terdiri atas <strong>Penyelia</strong>, <strong>Mahir</strong>, <strong>Terampil</strong>, dan <strong>Pemula</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Berdasarkan ketentuan PP manajemen PNS, Jabatan Fungsional Keterampilan memiliki 4 tingkatan jenjang jabatan dari yang tertinggi hingga terendah, yaitu jenjang Penyelia, Mahir, Terampil, dan Pemula. Pengelompokan ini disesuaikan dengan tingkat keterampilan teknis dan sertifikasi profesi yang dipersyaratkan.</p><p>Jawaban tepat: <strong>A. Penyelia, Mahir, Terampil, dan Pemula</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Merupakan tiga jenjang teratas dari rumpun Jabatan Fungsional Keahlian.</li><li><strong>C</strong>: Merupakan tiga tingkatan pada rumpun Jabatan Administrasi aparatur.</li><li><strong>D</strong>: Merupakan penamaan singkat jenjang pada Jabatan Fungsional Keahlian.</li><li><strong>E</strong>: Merupakan susunan pangkat reguler dalam golongan ruang III dan II kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mahir, Terampil, dan Pemula. • Berbeda dengan Jabatan Administrasi, Jabatan Fungsional ditetapkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kriteria penilaian kegiatan pada Jabatan Fungsional diukur dengan satuan nilai atau akumulasi butir kegiatan berupa...", "options": ["Persentase serapan anggaran kegiatan satuan kerja", "Angka kredit butir kegiatan tugas profesi jabatan", "Indeks kepuasan pelayanan pemangku kepentingan unit", "Poin evaluasi kepatuhan tata kelola administrasi", "Nilai kuantitatif capaian perjanjian kinerja tahunan"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengukuran Kinerja JF melalui Angka Kredit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu kriteria Jabatan Fungsional adalah <strong>kegiatannya dapat diukur dengan satuan nilai atau akumulasi nilai butir-butir kegiatan dalam bentuk angka kredit</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Sesuai kriteria pokok pembentukan Jabatan Fungsional, setiap rincian uraian tugas kedinasan memiliki bobot nilai tertentu yang diakumulasikan dalam bentuk angka kredit. Angka kredit ini menjadi instrumen utama penetapan prestasi kerja serta syarat kenaikan pangkat atau kenaikan jenjang jabatan fungsional.</p><p>Jawaban tepat: <strong>B. Angka kredit butir kegiatan tugas profesi jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan tolok ukur penyerapan anggaran keuangan dan bukan pengukuran butir tugas JF.</li><li><strong>C</strong>: Merupakan indikator kepuasan publik terhadap unit pelayanan birokrasi.</li><li><strong>D</strong>: Merupakan instrumen kepatuhan tata kelola pengawasan internal satker.</li><li><strong>E</strong>: Merupakan capaian sasaran kinerja pegawai (SKP) tahunan secara umum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kegiatannya dapat diukur dengan satuan nilai atau akumulasi nilai butir-butir • kegiatan dalam bentuk angka kredit.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pengangkatan PNS ke dalam Jabatan Fungsional dapat dilaksanakan melalui empat mekanisme resmi yaitu...", "options": ["Seleksi terbuka, pemindahan instansi, rotasi periodik, dan afirmasi", "Uji kelayakan dinas, penugasan khusus, pelimpahan tugas, dan demosi", "Pengangkatan pertama, perpindahan jabatan, penyesuaian, dan promosi", "Penyetaraan jabatan, penugasan perbantuan, magang dinas, dan seleksi", "Rekrutmen umum, pemeringkatan kinerja, kenaikan pangkat, dan mutasi"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Empat Mekanisme Pengangkatan Jabatan Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengangkatan PNS ke dalam Jabatan Fungsional dilakukan melalui mekanisme <strong>pengangkatan pertama</strong>, <strong>perpindahan dari Jabatan lain</strong>, <strong>penyesuaian</strong>, dan/atau <strong>promosi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Berdasarkan ketentuan PP 11/2017 stdd PP 17/2020, pengangkatan PNS ke dalam Jabatan Fungsional keahlian maupun keterampilan diatur secara ketat melalui 4 jalur resmi, yaitu pengangkatan pertama (dari formasi CPNS), perpindahan dari jabatan lain (struktural/pelaksana), penyesuaian/inpassing, dan jalur promosi kinerja.</p><p>Jawaban tepat: <strong>C. Pengangkatan pertama, perpindahan jabatan, penyesuaian, dan promosi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan metode pengisian Jabatan Pimpinan Tinggi dan pola mutasi pegawai.</li><li><strong>B</strong>: Merupakan ragam penugasan administratif serta sanksi penurunan jabatan kedinasan.</li><li><strong>D</strong>: Merupakan mekanisme penataan birokrasi dan bukan empat pintu resmi pengangkatan JF.</li><li><strong>E</strong>: Merupakan tahapan manajemen karier umum dan bukan mekanisme pengangkatan JF.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengangkatan PNS ke dalam Jabatan Fungsional keahlian maupun • pertama, perpindahan dari Jabatan lain, penyesuaian dan/atau promosi dengan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Seorang pejabat fungsional ditugaskan secara penuh menduduki jabatan struktural administrator. Kedudukan jabatan fungsionalnya adalah...", "options": ["Merangkap kedua jabatan dengan pembagian beban kerja berimbang", "Berstatus cuti jabatan fungsional selama masa penugasan berlangsung", "Dinonaktifkan sementara hak penggajiannya sebagai pegawai aparatur", "Diberhentikan dari jabatan fungsional yang sebelumnya didudukinya", "Ditangguhkan penilaian angka kreditnya hingga masa jabatan berakhir"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemberhentian JF karena Penugasan Penuh di Luar JF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS <strong>diberhentikan dari Jabatan Fungsional</strong> apabila <strong>ditugaskan secara penuh di luar JF</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Dalam sistem tata aturan kepegawaian ASN, penugasan secara penuh pada jabatan lain di luar Jabatan Fungsional (misalnya memegang jabatan manajerial administrator struktural) mengakibatkan PNS diberhentikan dari JF yang didudukinya. Hal ini bertujuan mencegah terjadinya rangkap jabatan serta menjaga kepatuhan akuntabilitas pada jabatan definitif yang baru.</p><p>Jawaban tepat: <strong>D. Diberhentikan dari jabatan fungsional yang sebelumnya didudukinya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Regulasi melarang keras perangkapan jabatan struktural dan jabatan fungsional.</li><li><strong>B</strong>: Hukum kepegawaian tidak mengenal status cuti jabatan fungsional akibat penugasan struktural.</li><li><strong>C</strong>: Hak penggajian PNS tetap berjalan sesuai ketentuan jabatan definitif yang sedang diemban.</li><li><strong>E</strong>: Bukan sekadar penangguhan angka kredit, melainkan diberhentikan dari jabatan fungsionalnya.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Demikian pula PNS dapat diberhentikan dari Jabatan Fungsional apabila: • ditugaskan secara penuh di luar JF; atau</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "PNS yang telah selesai menjalani tugas belajar lebih dari 6 bulan dapat diangkat kembali ke jenjang JF terakhir dengan syarat...", "options": ["Memperoleh persetujuan tertulis dari instansi pembina fungsional", "Menyelesaikan masa uji kompetensi penyesuaian selama satu tahun", "Memiliki rekomendasi khusus dari pejabat pembina kepegawaian pusat", "Mengajukan usulan formasi baru ke kementerian koordinator terkait", "Tersedia lowongan kebutuhan formasi jabatan pada jenjang terakhir"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengangkatan Kembali ke Jabatan Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS yang diberhentikan dari JF karena tugas belajar lebih dari 6 bulan <strong>dapat diangkat kembali sesuai dengan jenjang Jabatan Fungsional terakhir apabila tersedia lowongan Jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Ketentuan Pasal PP 11/2017 jo PP 17/2020 menegaskan bahwa PNS yang diberhentikan dari JF karena alasan huruf b sampai dengan e (termasuk tugas belajar lebih dari 6 bulan) berhak diangkat kembali ke jenjang jabatan fungsional terakhir yang didudukinya, dengan syarat mutlak tersedianya formasi atau lowongan jabatan pada instansi tersebut.</p><p>Jawaban tepat: <strong>E. Tersedia lowongan kebutuhan formasi jabatan pada jenjang terakhir</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Persetujuan instansi pembina bukan syarat pokok yang ditentukan dalam teks regulasi pengangkatan kembali.</li><li><strong>B</strong>: Tidak dipersyaratkan masa uji kompetensi penyesuaian satu tahun untuk pengangkatan kembali ke jenjang terakhir.</li><li><strong>C</strong>: Pengangkatan kembali diproses oleh PPK instansi berdasar ketersediaan lowongan tanpa butuh rekomendasi khusus pusat.</li><li><strong>D</strong>: Pengangkatan kembali memanfaatkan alokasi formasi internal instansi yang tersedia tanpa membuat usulan ke kemenko.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>s.d.huruf (e) dapat diangkat kembali sesuai dengan jenjang Jabatan Fungsional • terakhir apabila tersedia lowongan Jabatan.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kelompok Jabatan Pelaksana pada instansi pemerintah memiliki tanggung jawab utama dalam memberikan pelayanan serta...", "options": ["Melaksanakan pekerjaan operasional yang bersifat rutin dan sederhana", "Mengendalikan pelaksanaan seluruh program kerja yang dipimpin administrator", "Menetapkan arah kebijakan teknis instansi secara mandiri dan profesional", "Merumuskan perencanaan strategis penataan kelembagaan birokrasi daerah", "Melakukan koordinasi lintas sektoral dalam pengawasan sistem merit ASN"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Jabatan Pelaksana</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Jabatan Pelaksana</strong> bertanggung jawab <strong>memberikan pelayanan dan melaksanakan pekerjaan yang bersifat rutin dan sederhana</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Berdasarkan klasifikasi jabatan nonmanajerial pada modul kepegawaian, kelompok Jabatan Pelaksana dirancang untuk menopang kelancaran operasional birokrasi sehari-hari dengan fokus tanggung jawab pada pemberian pelayanan kedinasan dan penyelesaian tugas-tugas administratif rutin dan sederhana sesuai arahan pimpinan.</p><p>Jawaban tepat: <strong>A. Melaksanakan pekerjaan operasional yang bersifat rutin dan sederhana</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pengendalian pelaksanaan program kerja pelaksana merupakan lingkup tanggung jawab Jabatan Pengawas.</li><li><strong>C</strong>: Penetapan kebijakan teknis mandiri berbasis profesi merupakan ranah Jabatan Fungsional keahlian.</li><li><strong>D</strong>: Perumusan perencanaan kelembagaan birokrasi merupakan fungsi Jabatan Pimpinan Tinggi dan Administrator.</li><li><strong>E</strong>: Koordinasi pengawasan penerapan sistem merit merupakan kewenangan Kementerian PAN-RB.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>b. Jabatan Pelaksana • Bertanggung jawab memberikan pelayanan dan melaksanakan pekerjaan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Salah satu kriteria khusus pelaksanaan tugas pada Jabatan Fungsional yang membedakannya dengan jabatan administrasi adalah...", "options": ["Kewenangan memimpin langsung unit organisasi struktural dinas", "Pelaksanaan tugas yang bersifat mandiri dalam profesi keahlian", "Tanggung jawab mengendalikan kinerja pejabat pelaksana di unit", "Kebebasan menentukan standar operasional prosedur kerja sendiri", "Kewenangan menetapkan struktur formasi kepegawaian unit kantor"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sifat Mandiri dalam Tugas Jabatan Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu kriteria Jabatan Fungsional adalah <strong>pelaksanaan tugas yang bersifat mandiri dalam menjalankan tugas profesinya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Kriteria pembentukan Jabatan Fungsional menegaskan bahwa pejabat fungsional menjalankan tugas profesi secara mandiri dengan bersandar pada keahlian, kode etik profesi, dan metodologi teknis tertentu, bukan berdasarkan rantai komando instruksi struktural harian seperti pada jabatan administrasi.</p><p>Jawaban tepat: <strong>B. Pelaksanaan tugas yang bersifat mandiri dalam profesi keahlian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Memimpin unit organisasi merupakan kewenangan pada kelompok Jabatan Manajerial.</li><li><strong>C</strong>: Mengendalikan kinerja pejabat pelaksana merupakan akuntabilitas Jabatan Pengawas.</li><li><strong>D</strong>: SOP tetap ditetapkan secara kelembagaan oleh instansi dan tidak ditentukan sendiri oleh pegawai.</li><li><strong>E</strong>: Penetapan struktur formasi kepegawaian merupakan wewenang PPK dan Menteri PAN-RB.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pelaksanaan tugas yang bersifat mandiri dalam menjalankan tugas • profesinya; dan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penguasaan keahlian atau keterampilan tertentu pada Jabatan Fungsional wajib dibuktikan oleh aparatur melalui...", "options": ["Penilaian kepatuhan disiplin kehadiran dari atasan langsung", "Hasil evaluasi akuntabilitas kinerja instansi pemerintah tahunan", "Sertifikasi kompetensi dan/atau penilaian kompetensi tertentu", "Surat rekomendasi kelayakan tugas dari tim penilai kinerja dinas", "Bukti kelulusan pendidikan dan pelatihan kepemimpinan pengawas"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bukti Sertifikasi dan Penilaian Jabatan Fungsional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kriteria penetapan Jabatan Fungsional mensyaratkan <strong>keahlian atau keterampilan tertentu yang dibuktikan dengan sertifikasi dan/atau penilaian tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Dalam tata kelola JF, penugasan aparatur ke dalam rumpun jabatan berbasis profesi menuntut legitimasi formal atas kompetensi yang dimiliki. Ketentuan modul kepegawaian secara eksplisit menyebutkan bahwa penguasaan keahlian atau keterampilan wajib dibuktikan melalui sertifikasi kompetensi resmi dan/atau proses penilaian teknis tertentu.</p><p>Jawaban tepat: <strong>C. Sertifikasi kompetensi dan/atau penilaian kompetensi tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Disiplin kehadiran merupakan kewajiban umum seluruh PNS dan bukan bukti keahlian fungsional.</li><li><strong>B</strong>: Evaluasi AKIP mengukur kinerja kelembagaan instansi secara makro, bukan sertifikasi profesi individu.</li><li><strong>D</strong>: Surat rekomendasi merupakan bagian proses administratif namun bukan instrumen sertifikasi/penilaian keahlian.</li><li><strong>E</strong>: Diklat kepemimpinan pengawas diperuntukkan bagi pejabat manajerial pengawas, bukan sertifikasi JF.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mensyaratkan keahlian atau keterampilan tertentu yang dibuktikan • dengan sertifikasi dan/atau penilaian tertentu;</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pejabat Pembina Kepegawaian dapat memberhentikan seorang PNS dari Jabatan Fungsional yang didudukinya apabila pegawai...", "options": ["Mengajukan usulan kenaikan jenjang jabatan fungsional baru", "Mengikuti penugasan uji kompetensi kenaikan jenjang profesi", "Mengambil hak cuti melahirkan atau cuti sakit sesuai aturan", "Tidak lagi memenuhi persyaratan yang ditentukan bagi jabatannya", "Memperoleh penugasan sebagai narasumber teknis pada diklat resmi"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemberhentian JF karena Tidak Memenuhi Syarat Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>PNS <strong>diberhentikan dari Jabatan Fungsional</strong> apabila <strong>tidak memenuhi persyaratan Jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Berdasarkan PP 11/2017 jo PP 17/2020 poin ke-6, seorang PNS diberhentikan dari Jabatan Fungsional apabila tidak lagi memenuhi persyaratan jabatan yang ditetapkan, misalnya tidak terpenuhinya target angka kredit minimal dalam jangka waktu tertentu, sertifikasi profesi kedaluwarsa tanpa perpanjangan, atau persyaratan kualifikasi lain yang diwajibkan.</p><p>Jawaban tepat: <strong>D. Tidak lagi memenuhi persyaratan yang ditentukan bagi jabatannya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengusulan kenaikan jenjang merupakan proses pengembangan karier yang sah dan wajar bagi pejabat fungsional.</li><li><strong>B</strong>: Mengikuti uji kompetensi merupakan tahapan reguler untuk promosi jenjang dan bukan dasar pemberhentian.</li><li><strong>C</strong>: Cuti melahirkan dan cuti sakit merupakan hak cuti sah pegawai yang dilindungi undang-undang.</li><li><strong>E</strong>: Penugasan narasumber diklat adalah aktivitas pengembangan yang lazim dan bernilai angka kredit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 12) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Demikian pula PNS dapat diberhentikan dari Jabatan Fungsional apabila: • tidak memenuhi persyaratan Jabatan.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan ketentuan penyetaraan jabatan PNS, posisi eselon Ia kepala lembaga pemerintah nonkementerian setara dengan...", "options": ["Jabatan Pimpinan Tinggi Utama", "Jabatan Pimpinan Tinggi Madya", "Jabatan Pimpinan Tinggi Pratama", "Jabatan Administrator Tingkat Satu", "Jabatan Fungsional Ahli Utama"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyetaraan Eselon Ia Kepala LPNK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada saat UU Nomor 5 Tahun 2014 mulai berlaku, <strong>jabatan eselon Ia kepala lembaga pemerintah nonkementerian setara dengan jabatan pimpinan tinggi utama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Dalam penyetaraan nomenklatur jabatan struktural lama ke dalam struktur UU ASN, posisi eselon Ia yang memimpin lembaga pemerintah nonkementerian (LPNK) secara khusus disetarakan ke dalam Jabatan Pimpinan Tinggi (JPT) Utama. Posisi ini memegang kepemimpinan tertinggi pada instansi nonkementerian di bawah Presiden.</p><p>Jawaban tepat: <strong>A. Jabatan Pimpinan Tinggi Utama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Eselon Ia non-kepala LPNK dan eselon Ib disetarakan dengan JPT Madya, bukan Utama.</li><li><strong>C</strong>: Eselon II disetarakan dengan JPT Pratama.</li><li><strong>D</strong>: Eselon III disetarakan dengan Jabatan Administrator.</li><li><strong>E</strong>: Jabatan Fungsional Ahli Utama merupakan jenjang pada rumpun jabatan fungsional keahlian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan eselon Ia kepala lembaga pemerintah nonkementerian setara • dengan jabatan pimpinan tinggi utama;</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jabatan struktural eselon Ia (selain kepala LPNK) dan eselon Ib dalam birokrasi pemerintah disetarakan ke dalam...", "options": ["Jabatan Pimpinan Tinggi Utama", "Jabatan Pimpinan Tinggi Pratama", "Jabatan Pimpinan Tinggi Madya", "Jabatan Administrator Kementerian", "Jabatan Fungsional Ahli Madya"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyetaraan Eselon Ia dan Ib</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan <strong>eselon Ia dan eselon Ib setara dengan jabatan pimpinan tinggi madya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Ketentuan penyetaraan jabatan kepegawaian mengatur bahwa posisi eselon Ia (seperti Sekretaris Jenderal atau Direktur Jenderal kementerian) dan eselon Ib (seperti Staf Ahli Menteri) disetarakan ke dalam kelompok Jabatan Pimpinan Tinggi (JPT) Madya. Penyetaraan ini menjamin kesinambungan hierarki kepemimpinan manajerial tingkat tinggi.</p><p>Jawaban tepat: <strong>C. Jabatan Pimpinan Tinggi Madya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: JPT Utama khusus diperuntukkan bagi penyetaraan jabatan eselon Ia kepala LPNK.</li><li><strong>B</strong>: JPT Pratama merupakan penyetaraan bagi jabatan struktural eselon II.</li><li><strong>D</strong>: Jabatan Administrator merupakan hasil penyetaraan bagi jabatan struktural eselon III.</li><li><strong>E</strong>: Jabatan Fungsional Ahli Madya merupakan rumpun nonmanajerial berbasis profesi keahlian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan eselon Ia dan eselon Ib setara dengan jabatan pimpinan tinggi madya;</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam ketentuan penyetaraan jabatan PNS, posisi struktural eselon II (seperti Direktur atau Kepala Kantor Wilayah) setara dengan...", "options": ["Jabatan Administrator Wilayah", "Jabatan Pengawas Teknis Pusat", "Jabatan Pimpinan Tinggi Utama", "Jabatan Pimpinan Tinggi Madya", "Jabatan Pimpinan Tinggi Pratama"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyetaraan Eselon II</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan <strong>eselon II setara dengan jabatan pimpinan tinggi pratama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Berdasarkan aturan peralihan dan penyetaraan jabatan PNS, jabatan pimpinan struktural eselon II—yang mencakup posisi Direktur, Kepala Biro, Kepala Pusat, atau Kepala Kantor Wilayah—disetarakan secara sah ke dalam Jabatan Pimpinan Tinggi (JPT) Pratama.</p><p>Jawaban tepat: <strong>E. Jabatan Pimpinan Tinggi Pratama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jabatan Administrator merupakan penyetaraan untuk pejabat eselon III.</li><li><strong>B</strong>: Jabatan Pengawas merupakan penyetaraan untuk pejabat eselon IV.</li><li><strong>C</strong>: JPT Utama diperuntukkan bagi penyetaraan eselon Ia kepala LPNK.</li><li><strong>D</strong>: JPT Madya merupakan penyetaraan untuk pejabat eselon Ia kementerian dan eselon Ib.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan eselon II setara dengan jabatan pimpinan tinggi pratama;</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penyetaraan posisi jabatan bagi pejabat struktural eselon III (seperti Kepala Bagian atau Kepala Bidang) adalah ke dalam...", "options": ["Jabatan Pimpinan Pratama", "Jabatan Administrator", "Jabatan Pengawas Senior", "Jabatan Pelaksana Utama", "Jabatan Fungsional Madya"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyetaraan Eselon III</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan <strong>eselon III setara dengan jabatan administrator</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Dalam skema penyetaraan jabatan kepegawaian ASN, pejabat manajerial tingkat menengah yang menduduki eselon III (misalnya Kepala Bagian, Kepala Bidang, atau Kepala Subdirektorat) dikelompokkan dan disetarakan status kedudukannya ke dalam Jabatan Administrator.</p><p>Jawaban tepat: <strong>B. Jabatan Administrator</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jabatan Pimpinan Tinggi Pratama merupakan penyetaraan untuk pejabat eselon II.</li><li><strong>C</strong>: Jabatan Pengawas merupakan penyetaraan untuk pejabat eselon IV.</li><li><strong>D</strong>: Jabatan Pelaksana merupakan penyetaraan bagi eselon V dan fungsional umum.</li><li><strong>E</strong>: Jabatan Fungsional Ahli Madya merupakan rumpun nonmanajerial keahlian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan eselon III setara dengan jabatan administrator;</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pejabat struktural eselon IV (seperti Kepala Seksi atau Kepala Subbagian) disetarakan kedudukannya ke dalam...", "options": ["Jabatan Pelaksana Teknis", "Jabatan Administrator", "Jabatan Fungsional Muda", "Jabatan Pengawas", "Jabatan Pimpinan Pratama"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyetaraan Eselon IV</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan <strong>eselon IV setara dengan jabatan pengawas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Berdasarkan ketentuan penyetaraan jabatan struktural aparatur sipil negara, posisi eselon IV (Kepala Seksi, Kepala Subbagian, atau Kepala Subbidang) yang memimpin unit kerja manajerial lini dasar secara sah disetarakan ke dalam Jabatan Pengawas.</p><p>Jawaban tepat: <strong>D. Jabatan Pengawas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jabatan Pelaksana merupakan penyetaraan untuk eselon V dan staf fungsional umum.</li><li><strong>B</strong>: Jabatan Administrator merupakan penyetaraan untuk pejabat eselon III.</li><li><strong>C</strong>: Jabatan Fungsional Ahli Muda merupakan rumpun keahlian nonmanajerial.</li><li><strong>E</strong>: Jabatan Pimpinan Tinggi Pratama merupakan penyetaraan untuk eselon II.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan eselon IV setara dengan jabatan pengawas; dan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan aturan penyetaraan jabatan kepegawaian, jabatan eselon V dan staf fungsional umum setara dengan...", "options": ["Jabatan Pelaksana", "Jabatan Pengawas", "Jabatan Administrator", "Jabatan Fungsional Mahir", "Jabatan Fungsional Pemula"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyetaraan Eselon V dan Fungsional Umum</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan <strong>eselon V dan fungsional umum setara dengan jabatan pelaksana</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Dalam ketentuan penyetaraan jabatan aparatur sesuai UU ASN, jabatan struktural eselon V serta kelompok staf fungsional umum (staf administrasi operasional) seluruhnya disetarakan kedudukan dan fungsinya ke dalam nomenklatur resmi Jabatan Pelaksana.</p><p>Jawaban tepat: <strong>A. Jabatan Pelaksana</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Jabatan Pengawas merupakan penyetaraan bagi pejabat struktural eselon IV.</li><li><strong>C</strong>: Jabatan Administrator merupakan penyetaraan bagi pejabat struktural eselon III.</li><li><strong>D</strong>: Jabatan Fungsional Mahir merupakan jenjang pada rumpun JF Keterampilan.</li><li><strong>E</strong>: Jabatan Fungsional Pemula merupakan jenjang dasar pada JF Keterampilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan eselon V dan fungsional umum setara dengan jabatan pelaksana.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Peraturan Pemerintah yang mengatur ketentuan penyetaraan jabatan administrasi ke dalam Jabatan Fungsional demi penataan birokrasi adalah...", "options": ["Peraturan Pemerintah Nomor 11 Tahun 2017", "Peraturan Pemerintah Nomor 30 Tahun 2019", "Peraturan Pemerintah Nomor 17 Tahun 2020", "Peraturan Pemerintah Nomor 94 Tahun 2021", "Peraturan Pemerintah Nomor 20 Tahun 2023"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Hukum Penyetaraan Jabatan Administrasi ke JF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pada saat <strong>Peraturan Pemerintah Nomor 17 Tahun 2020</strong> mulai berlaku, dalam hal diperlukan penataan birokrasi penyesuaian jabatan ke dalam Jabatan Fungsional dapat dilakukan <strong>penyetaraan jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Ketentuan penataan birokrasi melalui penyetaraan jabatan administrasi (pengawas dan administrator) ke dalam Jabatan Fungsional yang setara secara resmi diatur dalam Peraturan Pemerintah Nomor 17 Tahun 2020 yang mengubah PP Nomor 11 Tahun 2017 tentang Manajemen PNS.</p><p>Jawaban tepat: <strong>C. Peraturan Pemerintah Nomor 17 Tahun 2020</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PP 11/2017 merupakan regulasi awal manajemen PNS sebelum penambahan norma khusus penyetaraan pada PP 17/2020.</li><li><strong>B</strong>: PP 30/2019 mengatur tentang Penilaian Kinerja Pegawai Negeri Sipil.</li><li><strong>D</strong>: PP 94/2021 mengatur tentang Disiplin Pegawai Negeri Sipil.</li><li><strong>E</strong>: Nomor 20 Tahun 2023 merupakan Undang-Undang tentang ASN, bukan Peraturan Pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pada saat Peraturan Pemerintah Nomor 17 Tahun 2020 mulai berlaku, dalam hal • diperlukan penataan birokrasi penyesuaian jabatan ke dalam Jabatan Fungsional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pengangkatan Pejabat Administrasi ke dalam Jabatan Fungsional yang setara dalam rangka penataan birokrasi dilaksanakan melalui mekanisme...", "options": ["Seleksi pengadaan formasi umum CPNS", "Penugasan perbantuan di luar instansi", "Promosi terbuka lintas instansi daerah", "Pengangkatan pertama jalur kedinasan", "Penyesuaian atau inpassing jabatan"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Mekanisme Penyesuaian/Inpassing Penyetaraan Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyetaraan jabatan dimaksud adalah pengangkatan Pejabat Administrasi ke dalam Jabatan Fungsional melalui <strong>Penyesuaian/Inpassing pada jabatan fungsional yang setara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Berdasarkan PP 17/2020, pengalihan pejabat administrasi (eselon IV dan eselon III) ke dalam jabatan fungsional yang bersesuaian dilakukan melalui mekanisme Penyesuaian atau Inpassing. Mekanisme ini memfasilitasi transisi pejabat struktural ke fungsional tanpa mengurangi hak kepegawaian dan kepangkatan yang telah dimiliki.</p><p>Jawaban tepat: <strong>E. Penyesuaian atau inpassing jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan mekanisme rekrutmen pegawai baru dari masyarakat umum.</li><li><strong>B</strong>: Merupakan bentuk penugasan sementara pegawai pada instansi lain.</li><li><strong>C</strong>: Merupakan metode seleksi terbuka (open bidding) untuk pengisian JPT.</li><li><strong>D</strong>: Merupakan jalur pengangkatan awal CPNS yang baru lulus pendidikan formasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penyetaraan jabatan dimaksud adalah • Penyesuaian/Inpassing pada jabatan fungsional yang setara. Penyetaraan jabatan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Sesuai ketentuan PP 17/2020, pedoman pelaksanaan teknis penyetaraan jabatan ke dalam Jabatan Fungsional diatur dengan...", "options": ["Keputusan Lembaga Nonstruktural", "Peraturan Menteri yang berwenang", "Surat Keputusan Pimpinan Daerah", "Peraturan Pemerintah Pengganti UU", "Nota Kesepahaman Antarlembaga"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Instrumen Regulasi Penyetaraan Jabatan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Ketentuan penutup tentang penyetaraan jabatan menegaskan bahwa <strong>Penyetaraan jabatan diatur dengan Peraturan Menteri</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Pasal penutup terkait penyetaraan jabatan dalam PP 17/2020 mendelegasikan pengaturan teknis operasional penyetaraan jabatan kepada Menteri PAN-RB melalui instrumen Peraturan Menteri (PermenPAN-RB), sehingga kementerian dan lembaga memiliki pedoman seragam dalam melakukan inpassing pejabat administrasi ke jabatan fungsional.</p><p>Jawaban tepat: <strong>B. Peraturan Menteri yang berwenang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Lembaga nonstruktural tidak memiliki kewenangan atributif menerbitkan pedoman penyetaraan jabatan ASN.</li><li><strong>C</strong>: Pimpinan daerah hanya menjalankan peraturan menteri dan tidak berwenang mengatur norma pokok penyetaraan.</li><li><strong>D</strong>: Perpu merupakan instrumen kegentingan memaksa setingkat undang-undang dan bukan instrumen teknis kepegawaian.</li><li><strong>E</strong>: Nota kesepahaman bukan produk hukum perundang-undangan yang mengikat penataan struktur jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penyetaraan jabatan • diatur dengan Peraturan Menteri.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kebijakan penyetaraan jabatan administrasi ke dalam jabatan fungsional dilaksanakan pemerintah dengan tujuan utama untuk...", "options": ["Mengurangi jumlah unit kerja operasional pelayanan daerah", "Membatasi mobilitas penugasan karier lintas kementerian", "Memperpanjang rentang kendali komando hierarki pimpinan", "Mewujudkan penataan birokrasi yang lebih lincah dan efektif", "Menyederhanakan seluruh sistem pengelolaan keuangan negara"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Penyetaraan Jabatan Administrasi ke JF</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyetaraan jabatan dilakukan dalam hal <strong>diperlukan penataan birokrasi</strong> penyesuaian jabatan ke dalam Jabatan Fungsional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Berdasarkan pertimbangan hukum PP 17/2020, kebijakan penyetaraan jabatan administrasi ke dalam jabatan fungsional merupakan bagian dari agenda transformasi dan penataan birokrasi pemerintah guna memangkas hierarki yang berlapis (*delayering*) sehingga birokrasi bergerak lebih lincah (*agile*), responsif, dan profesional dalam melayani publik.</p><p>Jawaban tepat: <strong>D. Mewujudkan penataan birokrasi yang lebih lincah dan efektif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Bukan untuk mengurangi unit pelayanan daerah, melainkan mengoptimalkan peran fungsional keahlian.</li><li><strong>B</strong>: Penyetaraan justru memperluas mobilitas penugasan profesional berbasis kompetensi JF.</li><li><strong>C</strong>: Tujuan penyetaraan adalah merampingkan dan memperpendek rentang kendali, bukan memperpanjangnya.</li><li><strong>E</strong>: Pengelolaan keuangan negara diatur dalam rumpun perundang-undangan perbendaharaan dan bukan fokus penyetaraan birokrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 13) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diperlukan penataan birokrasi penyesuaian jabatan ke dalam Jabatan Fungsional • dapat dilakukan penyetaraan jabatan. Penyetaraan jabatan dimaksud adalah</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan ketentuan UU ASN, Pegawai ASN berhak memperoleh penghargaan dan pengakuan dalam bentuk...", "options": ["Saham kepemilikan aset sarana dan prasarana milik satuan kerja", "Penghargaan dan pengakuan berupa materiel dan/atau nonmateriel", "Perlindungan imunitas hukum mutlak dari segala bentuk audit dinas", "Kuota kepemilikan rumah dinas permanen setelah masa purnatugas", "Hak prerogatif dalam penentuan formasi rekrutmen pegawai baru"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Penghargaan dan Pengakuan Hak ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai ASN berhak memperoleh <strong>penghargaan dan pengakuan berupa materiel dan/atau nonmateriel</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Sesuai Bab II ketentuan Hak dan Kewajiban ASN dalam undang-undang kepegawaian terbaru, seluruh hak pegawai dirumuskan dalam payung penghargaan dan pengakuan yang berwujud materiel (seperti penghasilan dan fasilitas) dan/atau nonmateriel (seperti lingkungan kerja dan motivasi). Pendekatan ini menggantikan dikotomi hak PNS dan PPPK lama.</p><p>Jawaban tepat: <strong>B. Penghargaan dan pengakuan berupa materiel dan/atau nonmateriel</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Aset sarana prasarana merupakan Barang Milik Negara yang tidak dapat dimiliki sebagai saham privat.</li><li><strong>C</strong>: ASN tunduk pada akuntabilitas hukum dan audit dan tidak memiliki imunitas mutlak.</li><li><strong>D</strong>: Rumah dinas merupakan fasilitas negara selama berdinas dan bukan hak kepemilikan permanen pensiun.</li><li><strong>E</strong>: Penentuan formasi merupakan wewenang Menteri PAN-RB dan PPK instansi, bukan hak prerogatif pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pegawai ASN berhak memperoleh penghargaan dan pengakuan berupa materiel • dan/atau nonmateriel yang terdiri atas:</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C4 - Analisis", "question": "Komponen penghargaan dan pengakuan yang menjadi hak Pegawai ASN mencakup komponen berikut, KECUALI...", "options": ["Penghasilan serta tunjangan dan fasilitas kedinasan", "Jaminan sosial serta penyediaan lingkungan kerja", "Fasilitas pengembangan diri dan bantuan perlindungan", "Kepemilikan permanen atas aset perlengkapan operasional", "Penghargaan yang bersifat motivasi dalam pelaksanaan tugas"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komponen Penghargaan dan Pengakuan Hak ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuh komponen penghargaan dan pengakuan ASN adalah: <strong>1. Penghasilan; 2. Penghargaan yang bersifat motivasi; 3. Tunjangan dan fasilitas; 4. Jaminan sosial; 5. Lingkungan kerja; 6. Pengembangan diri; dan 7. Bantuan hukum</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat sebagai pengecualian. Kepemilikan permanen atas aset perlengkapan dinas bukan merupakan hak ASN, karena perlengkapan dinas berstatus aset milik negara yang dipinjamkan semata-mata untuk menunjang kelancaran tugas. Sebaliknya, penghasilan, jaminan sosial, lingkungan kerja, pengembangan diri, dan penghargaan motivasi adalah komponen sah hak ASN.</p><p>Jawaban tepat: <strong>D. Kepemilikan permanen atas aset perlengkapan operasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan komponen resmi hak ASN pada butir 1 dan butir 3.</li><li><strong>B</strong>: Merupakan komponen resmi hak ASN pada butir 4 dan butir 5.</li><li><strong>C</strong>: Merupakan komponen resmi hak ASN pada butir 6 dan butir 7.</li><li><strong>E</strong>: Merupakan komponen resmi hak ASN pada butir 2.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dan/atau nonmateriel yang terdiri atas: • Penghargaan yang bersifat motivasi;</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Seorang pegawai ASN menghadapi permasalahan hukum terkait pelaksanaan tugas resmi kedinasannya. Hak yang wajib difasilitasi oleh pemerintah adalah...", "options": ["Bantuan hukum dalam perkara pelaksanaan tugas kedinasan resmi", "Pembebasan mutlak dari proses pemeriksaan peradilan umum", "Pelimpahan seluruh pertanggungjawaban hukum kepada atasan kerja", "Pemberhentian sementara hak penerimaan penghasilan pokok bulanan", "Penundaan pemeriksaan hingga masa purnatugas pegawai selesai"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak ASN Memperoleh Bantuan Hukum</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Salah satu komponen penghargaan dan pengakuan Pegawai ASN butir ke-7 adalah hak memperoleh <strong>Bantuan hukum</strong> terkait pelaksanaan tugas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Regulasi kepegawaian menjamin bahwa negara memberikan perlindungan berupa bantuan hukum bagi aparatur yang menghadapi sengketa atau perkara hukum dalam menjalankan tugas kedinasan yang sah. Bantuan hukum ini memberikan kepastian rasa aman bagi ASN dalam mengeksekusi kebijakan dan pelayanan publik.</p><p>Jawaban tepat: <strong>A. Bantuan hukum dalam perkara pelaksanaan tugas kedinasan resmi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Aparatur tidak kebal hukum dan tetap wajib menjalani proses peradilan sesuai asas kesetaraan di depan hukum.</li><li><strong>C</strong>: Pertanggungjawaban pidana bersifat personal dan tidak dapat dilimpahkan begitu saja kepada atasan.</li><li><strong>D</strong>: Penghasilan tidak serta-merta diberhentikan hanya karena adanya gugatan hukum terkait tugas resmi.</li><li><strong>E</strong>: Proses penegakan hukum berjalan sesuai agenda pengadilan tanpa menunggu status masa purnatugas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bantuan hukum • dan/atau nonmateriel yang terdiri atas:</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kewajiban pertama dan mendasar bagi setiap Pegawai ASN sebagaimana dirumuskan dalam ketentuan perundang-undangan adalah...", "options": ["Menyelenggarakan kegiatan pembinaan kemasyarakatan di wilayah satker", "Menyusun laporan keuangan instansi secara berkala setiap akhir triwulan", "Setia dan taat kepada Pancasila, UUD 1945, NKRI, dan pemerintah yang sah", "Mengikuti program uji sertifikasi profesi internasional secara mandiri", "Mengembangkan sistem informasi layanan berbasis teknologi digital modern"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Pokok Kesetiaan kepada Pancasila dan NKRI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewajiban ASN butir pertama adalah <strong>setia dan taat kepada Pancasila, UUD Tahun 1945, NKRI, dan pemerintah yang sah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Berdasarkan ketentuan Bab II huruf B angka 1 modul kepegawaian, komitmen kebangsaan tertinggi seorang abdi negara diletakkan pada kesetiaan dan ketaatan mutlak terhadap konsensus dasar bernegara, yaitu ideologi Pancasila, konstitusi UUD 1945, kedaulatan NKRI, dan legitimasi pemerintah yang sah.</p><p>Jawaban tepat: <strong>C. Setia dan taat kepada Pancasila, UUD 1945, NKRI, dan pemerintah yang sah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan wujud partisipasi sosial kemasyarakatan namun bukan butir kewajiban pokok undang-undang.</li><li><strong>B</strong>: Merupakan uraian tugas administratif perbendaharaan pada jabatan pengelola keuangan.</li><li><strong>D</strong>: Merupakan inisiatif pengembangan kompetensi pribadi dan bukan kewajiban fundamental nomor satu.</li><li><strong>E</strong>: Merupakan tugas teknis pada jabatan fungsional pranata komputer atau pengembang TI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kewajiban ASN adalah: • setia dan taat kepada Pancasila, UUD Tahun 1945, NKRI, dan pemerintah yang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam penyelenggaraan tugas pemerintahan, asas legalitas aparatur diwujudkan melalui kewajiban untuk...", "options": ["Menerapkan diskresi kedinasan tanpa berpedoman pada ketentuan regulasi", "Menyesuaikan tata kelola birokrasi berdasar kebiasaan informal setempat", "Mendahulukan instruksi lisan pimpinan di atas peraturan perundang-undangan", "Menetapkan kebijakan internal tanpa melalui proses harmonisasi hukum", "Menaati seluruh ketentuan peraturan perundang-undangan yang berlaku sah"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Menaati Ketentuan Peraturan Perundang-undangan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewajiban ASN butir kedua adalah <strong>menaati ketentuan peraturan perundang-undangan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Asas legalitas merupakan fondasi utama negara hukum di mana setiap tindakan administrasi pemerintahan wajib bersandar pada dasar hukum yang sah. UU ASN menetapkan pada butir kewajiban ke-2 bahwa setiap pegawai wajib menaati ketentuan peraturan perundang-undangan, sehingga seluruh pelayanan dan keputusan terbebas dari penyalahgunaan wewenang.</p><p>Jawaban tepat: <strong>E. Menaati seluruh ketentuan peraturan perundang-undangan yang berlaku sah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Diskresi wajib dilaksanakan dengan tetap tunduk pada asas umum pemerintahan yang baik dan batas undang-undang.</li><li><strong>B</strong>: Tata kelola birokrasi wajib berpedoman pada hukum positif formal, bukan kebiasaan informal.</li><li><strong>C</strong>: Hierarki hukum menempatkan peraturan perundang-undangan jauh lebih tinggi daripada instruksi lisan pimpinan.</li><li><strong>D</strong>: Setiap kebijakan kelembagaan wajib melalui proses penyelarasan dan harmonisasi regulasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kewajiban ASN adalah: • menaati ketentuan peraturan perundang-undangan;</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Dalam berinteraksi dan menyelenggarakan pelayanan kepada masyarakat, setiap Pegawai ASN berkewajiban untuk...", "options": ["Mengutamakan penyelesaian berkas pemohon yang memiliki kedekatan sosial", "Melaksanakan nilai dasar ASN serta kode etik dan kode perilaku aparatur", "Menetapkan persyaratan tambahan secara mandiri di luar standar layanan", "Memberikan perlakuan istimewa kepada kelompok tertentu saat bertugas", "Membatasi pemberian informasi layanan kepada masyarakat umum pemohon"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Melaksanakan Nilai Dasar dan Kode Etik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewajiban ASN butir ketiga adalah <strong>melaksanakan nilai dasar ASN dan kode etik dan kode perilaku ASN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Penyelenggaraan pelayanan publik oleh aparatur sipil negara terikat pada standar etika moral profesi yang ketat. Ketaatan terhadap nilai dasar BerAKHLAK serta penegakan kode etik dan kode perilaku mencegah pegawai melakukan tindakan diskriminatif, menjaga objektivitas, serta memastikan masyarakat dilayani secara adil dan terhormat.</p><p>Jawaban tepat: <strong>B. Melaksanakan nilai dasar ASN serta kode etik dan kode perilaku aparatur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mendahulukan berkas berdasar kedekatan sosial melanggar prinsip keadilan dan kode perilaku profesional.</li><li><strong>C</strong>: Menambah syarat di luar standar operasional prosedur merupakan pelanggaran standar pelayanan publik.</li><li><strong>D</strong>: Memberi perlakuan istimewa bertentangan dengan asas kesetaraan pelayanan birokrasi.</li><li><strong>E</strong>: Membatasi informasi layanan publik melanggar prinsip transparansi dan keterbukaan informasi dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>melaksanakan nilai dasar ASN dan kode etik dan kode perilaku ASN;</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Butir kewajiban aparatur yang ditegaskan guna menjamin kemandirian dan keadilan pelayanan dari intervensi politik adalah...", "options": ["Menjaga kerahasiaan seluruh dokumen anggaran belanja instansi pemerintah", "Memberikan dukungan finansial kepada salah satu calon peserta pemilihan", "Menghadiri kegiatan deklarasi pemenangan partai politik saat masa cuti", "Menjaga netralitas aparatur dari segala pengaruh kepentingan politik", "Membentuk organisasi kelompok kepentingan di dalam lingkungan birokrasi"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Menjaga Netralitas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewajiban ASN butir keempat adalah <strong>menjaga netralitas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Ketentuan UU ASN mewajibkan setiap pegawai menjaga netralitas dari segala bentuk kepentingan golongan, kontestasi politik praktis, dan pemilu/pilkada. Asas netralitas memastikan bahwa seluruh kebijakan publik, distribusi sumber daya anggaran, dan pelayanan kepada masyarakat dilakukan secara adil dan imparsial tanpa memihak.</p><p>Jawaban tepat: <strong>D. Menjaga netralitas aparatur dari segala pengaruh kepentingan politik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Anggaran pemerintah merupakan informasi publik yang transparan dan bukan dokumen rahasia tertutup.</li><li><strong>B</strong>: Memberikan dukungan finansial bagi peserta pemilihan adalah pelanggaran disiplin berat netralitas ASN.</li><li><strong>C</strong>: Menghadiri deklarasi politik tetap dilarang meskipun pegawai sedang menjalani masa cuti.</li><li><strong>E</strong>: Mendirikan kelompok kepentingan politik di birokrasi mencederai profesionalitas aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kewajiban ASN adalah: • menjaga netraliras;</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Ruang lingkup kewajiban kesediaan penempatan tugas kedinasan bagi seluruh Pegawai ASN mencakup...", "options": ["Seluruh wilayah NKRI dan kantor perwakilan di luar negeri", "Lingkungan kantor pemerintahan tingkat daerah provinsi asal", "Wilayah pusat kementerian yang berkedudukan di ibu kota", "Unit kerja instansi vertikal pada kota-kota besar nasional", "Zona perkantoran yang memiliki fasilitas pendukung lengkap"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kewajiban Penempatan Tugas Seluruh NKRI dan Luar Negeri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kewajiban ASN butir kelima adalah <strong>bersedia ditempatkan di seluruh wilayah NKRI dan perwakilan NKRI yang berkedudukan di luar wilayah Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Sebagai perekat dan pemersatu bangsa, seorang Pegawai ASN sejak awal penandatanganan komitmen pengangkatan wajib bersedia ditugaskan di mana pun di seluruh pelosok wilayah kedaulatan Negara Kesatuan Republik Indonesia serta pada kantor-kantor perwakilan diplomatik dan konsuler RI di luar negeri.</p><p>Jawaban tepat: <strong>A. Seluruh wilayah NKRI dan kantor perwakilan di luar negeri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: ASN tidak dapat membatasi penempatan kedinasan hanya di provinsi asalnya.</li><li><strong>C</strong>: Penugasan dinas tidak terpusat di ibu kota saja melainkan tersebar ke seluruh penjuru negeri.</li><li><strong>D</strong>: Pemerataan aparatur mencakup daerah terpencil dan perbatasan, bukan hanya kota-kota besar.</li><li><strong>E</strong>: ASN dituntut siap bertugas di daerah penugasan dengan sarana terbatas demi melayani bangsa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>bersedia ditempatkan di seluruh wilayah NKRI dan perwakilan NKRI yang • berkedudukan di luar wilayah Indonesia.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Sebuah instansi pemerintah menyediakan ruang kerja yang aman dan sehat serta memfasilitasi beasiswa studi pegawai. Instansi ini memenuhi hak...", "options": ["Fasilitas sarana kendaraan dinas jabatan serta tunjangan hari raya", "Bantuan pendampingan hukum dan perlindungan profesi pegawai aparatur", "Penyediaan lingkungan kerja yang kondusif serta hak pengembangan diri", "Pemberian jaminan sosial pensiun dan kompensasi penghargaan motivasi", "Pemenuhan penghasilan pokok bulanan serta tunjangan keluarga pegawai"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hak Lingkungan Kerja dan Pengembangan Diri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Komponen hak penghargaan dan pengakuan ASN mencakup <strong>Lingkungan kerja</strong> (kondusif, aman, sehat) dan <strong>Pengembangan diri</strong> (pendidikan, pelatihan, beasiswa).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Berdasarkan perincian hak ASN dalam Bab II, penyediaan ruang kerja fisik yang sehat, aman, dan berkeselamatan merupakan wujud pemenuhan hak lingkungan kerja (butir 5). Sementara pemberian kesempatan peningkatan kualifikasi melalui beasiswa studi lanjut merupakan pelaksanaan nyata hak pengembangan diri (butir 6).</p><p>Jawaban tepat: <strong>C. Penyediaan lingkungan kerja yang kondusif serta hak pengembangan diri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan bagian dari komponen tunjangan dan fasilitas, bukan sarana ruang kerja dan studi.</li><li><strong>B</strong>: Bantuan hukum diperuntukkan bagi pendampingan perkara hukum, bukan fasilitas beasiswa studi.</li><li><strong>D</strong>: Jaminan sosial dan penghargaan motivasi berkaitan dengan asuransi dan pengakuan prestasi kerja.</li><li><strong>E</strong>: Penghasilan pokok berkaitan dengan kompensasi gaji langsung atas pelaksanaan tugas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Lingkungan kerja; • Pengembangan diri; dan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Secara keseluruhan, jumlah butir kewajiban pokok Aparatur Sipil Negara yang dirumuskan dalam ketentuan UU ASN terdiri atas...", "options": ["Tiga butir kewajiban kedinasan aparatur sipil", "Empat butir kewajiban kedinasan aparatur sipil", "Enam butir kewajiban kedinasan aparatur sipil", "Tujuh butir kewajiban kedinasan aparatur sipil", "Lima butir kewajiban kedinasan aparatur sipil"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Jumlah Butir Kewajiban Pokok ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kewajiban ASN terdiri atas 5 butir</strong>: 1. Setia pada Pancasila, UUD 1945, NKRI, dan pemerintah sah; 2. Menaati peraturan perundang-undangan; 3. Melaksanakan nilai dasar dan kode etik/perilaku; 4. Menjaga netralitas; 5. Bersedia ditempatkan di seluruh NKRI dan perwakilan di luar negeri.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Berbeda dengan komponen hak penghargaan dan pengakuan yang berjumlah 7 komponen, kewajiban pokok Pegawai ASN secara limitatif dirinci ke dalam 5 butir fundamental pada huruf B modul kepegawaian. Kelima butir ini wajib dipedomani dan ditaati oleh seluruh ASN tanpa terkecuali.</p><p>Jawaban tepat: <strong>E. Lima butir kewajiban kedinasan aparatur sipil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Tiga butir merupakan jumlah standar kompetensi jabatan administrasi (Teknis, Manajerial, Soskuldik).</li><li><strong>B</strong>: Empat butir merupakan jumlah pilar konsensus kebangsaan dan bukan jumlah kewajiban ASN.</li><li><strong>C</strong>: Enam butir bukan pembagian baku dalam ketentuan pasal hak maupun kewajiban ASN.</li><li><strong>D</strong>: Tujuh butir merupakan jumlah komponen hak penghargaan dan pengakuan bagi Pegawai ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 15) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>B. Kewajiban Aparatur Sipil Negara (ASN) • Kewajiban ASN adalah:</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pemegang kekuasaan pemerintahan tertinggi dalam kebijakan, pembinaan profesi, dan Manajemen ASN adalah...", "options": ["Menteri Pendayagunaan Aparatur Negara dan Reformasi Birokrasi", "Kepala Badan Kepegawaian Negara Republik Indonesia", "Presiden Republik Indonesia selaku kepala pemerintahan", "Ketua Dewan Perwakilan Rakyat Republik Indonesia", "Ketua Mahkamah Konstitusi Republik Indonesia"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pemegang Kekuasaan Pemerintahan Tertinggi Manajemen ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Presiden merupakan pemegang kekuasaan pemerintahan tertinggi</strong> dalam <strong>kebijakan, pembinaan profesi, dan Manajemen Aparatur Sipil Negara (ASN)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Bab III Kelembagaan modul kepegawaian menegaskan kembali asas konstitusional bahwa Presiden RI adalah pemegang kekuasaan eksekutif dan pemerintahan tertinggi negara. Oleh karena itu, seluruh kebijakan strategis, sistem pembinaan profesi, serta manajemen kepegawaian aparatur sipil negara bermuara pada mandat kekuasaan Presiden.</p><p>Jawaban tepat: <strong>C. Presiden Republik Indonesia selaku kepala pemerintahan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menteri PAN-RB menerima pendelegasian wewenang dari Presiden, bukan pemegang kekuasaan tertinggi.</li><li><strong>B</strong>: Kepala BKN menjalankan fungsi manajemen teknis operasional dan pembinaan penyelenggaraan manajemen ASN.</li><li><strong>D</strong>: Ketua DPR memegang fungsi legislasi, anggaran, dan pengawasan di lembaga perwakilan rakyat.</li><li><strong>E</strong>: Ketua MK memegang kekuasaan kehakiman dalam menguji undang-undang terhadap konstitusi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Presiden merupakan pemegang kekuasaan pemerintahan tertinggi dalam • kebijakan, pembinaan profesi, dan Manajemen Aparatur Sipil Negara (ASN). Untuk</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Untuk menyelenggarakan kekuasaan kebijakan dan manajemen ASN, Presiden secara resmi mendelegasikan kewenangannya kepada...", "options": ["Lembaga Administrasi Negara Republik Indonesia", "Kementerian Koordinator Bidang Perekonomian RI", "Badan Kepegawaian Negara Republik Indonesia", "Kementerian Sekretariat Negara Republik Indonesia", "Kementerian PAN-RB Republik Indonesia"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pendelegasian Kekuasaan Manajemen ASN kepada Kementerian PAN-RB</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Untuk menyelenggarakan kekuasaan dimaksud, <strong>Presiden mendelegasikan kepada Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (PAN-RB)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Dalam penyelenggaraan pemerintahan yang efektif, Presiden mendelegasikan kewenangan perumusan kebijakan, koordinasi, dan pengendalian manajemen ASN secara nasional kepada Kementerian PAN-RB sebagai kementerian yang membidangi urusan aparatur negara dan reformasi birokrasi.</p><p>Jawaban tepat: <strong>E. Kementerian PAN-RB Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: LAN merupakan lembaga nonkementerian yang bertugas di bidang kajian dan kediklatan ASN.</li><li><strong>B</strong>: Kemenko Perekonomian mengoordinasikan bidang pembangunan ekonomi dan fiskal nasional.</li><li><strong>C</strong>: BKN merupakan lembaga pemerintah nonkementerian yang menyelenggarakan manajemen teknis kepegawaian.</li><li><strong>D</strong>: Kemensetneg bertugas memberikan dukungan teknis dan administrasi kepada Presiden dan Wakil Presiden.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menyelenggarakan kekuasaan dimaksud, Presiden mendelegasikan kepada Kementerian • Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (PAN-RB). Pendelegasian</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pendelegasian kewenangan dari Presiden kepada Kementerian PAN-RB berkaitan erat dengan tiga mandat utama yaitu...", "options": ["Perencanaan penganggaran belanja, audit keuangan, dan pengadaan barang", "Perumusan kebijakan strategis, koordinasi sinkronisasi, dan pengendalian", "Pengawasan kepatuhan hukum pidana, penindakan disiplin, dan persidangan", "Penyelenggaraan pendidikan kedinasan, pelatihan vokasi, dan sertifikasi", "Pengelolaan data base digital, mutasi kepegawaian, dan penetapan pensiun"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Mandat Pendelegasian kepada Kementerian PAN-RB</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pendelegasian tersebut berkaitan dengan kewenangan <strong>perumusan dan penetapan kebijakan strategis</strong>, <strong>koordinasi dan sinkronisasi kebijakan</strong>, dan <strong>pengendalian kebijakan Manajemen ASN secara nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Berdasarkan ketentuan Bab III modul kepegawaian, tiga pilar utama pendelegasian wewenang Presiden kepada Kementerian PAN-RB adalah merumuskan kebijakan strategis ASN, mengoordinasikan dan menyinkronkan kebijakan antarinstitusi, serta mengendalikan penerapan kebijakan manajemen ASN secara nasional.</p><p>Jawaban tepat: <strong>B. Perumusan kebijakan strategis, koordinasi sinkronisasi, dan pengendalian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan ranah kewenangan Kementerian Keuangan dan Lembaga Kebijakan Pengadaan Barang/Jasa Pemerintah.</li><li><strong>C</strong>: Merupakan fungsi aparat penegak hukum dan lembaga peradilan negara.</li><li><strong>D</strong>: Merupakan tugas pokok Lembaga Administrasi Negara dan instansi penyelenggara diklat.</li><li><strong>E</strong>: Merupakan lingkup operasional administrasi yang diselenggarakan oleh Badan Kepegawaian Negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tersebut berkaitan dengan kewenangan perumusan dan penetapan kebijakan strategis, • koordinasi dan sinkronisasi kebijakan, dan pengendalian kebijakan Manajemen ASN</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam penyelenggaraan Manajemen ASN, Kementerian PAN-RB mengoordinasikan rencana kerja lembaga serta melakukan...", "options": ["Pengawasan transaksi perbankan pada rekening operasional bendahara kas", "Penetapan formasi alokasi belanja modal kementerian secara terpusat", "Penyelenggaraan lelang pengadaan aset gedung kantor satuan kerja daerah", "Sinkronisasi pengendalian tugas pengembangan kapasitas dan pembelajaran", "Pemeriksaan laporan harta kekayaan penyelenggara negara seluruh instansi"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sinkronisasi Tugas Pengembangan Kapasitas ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian PAN-RB bertugas melakukan <strong>sinkronisasi pengendalian tugas dan fungsi pengembangan kapasitas dan pembelajaran ASN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Ketentuan modul menjelaskan bahwa secara lebih mendalam, Kementerian PAN-RB menyelaraskan program pengembangan kapasitas dan pembelajaran ASN di seluruh lembaga diklat pemerintah guna memastikan seluruh program pelatihan selaras dengan arah transformasi birokrasi dan kebutuhan kompetensi nasional.</p><p>Jawaban tepat: <strong>D. Sinkronisasi pengendalian tugas pengembangan kapasitas dan pembelajaran</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengawasan transaksi kas perbankan bendahara merupakan tugas pengawasan internal perbendaharaan Kemenkeu.</li><li><strong>B</strong>: Alokasi anggaran belanja modal ditetapkan oleh Kemenkeu dan Bappenas dalam APBN.</li><li><strong>C</strong>: Pengadaan aset gedung kantor dilaksanakan oleh masing-masing Pejabat Pembuat Komitmen satker.</li><li><strong>E</strong>: Pemeriksaan LHKPN merupakan fungsi konstitusional Komisi Pemberantasan Korupsi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sinkronisasi pengendalian tugas dan fungsi pengembangan kapasitas dan pembelajaran • ASN. Kementerian PAN-RB juga melakukan pengendalian pelaksanaan kebijakan teknis</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Salah satu tugas pengawasan krusial yang dijalankan oleh Kementerian PAN-RB pada instansi pemerintah adalah...", "options": ["Pengawasan penerapan Sistem Merit pada Kementerian/Lembaga", "Pemeriksaan fisik seluruh sarana perlengkapan kantor dinas", "Evaluasi kepatuhan tata tertib jam kerja harian pegawai satker", "Pengawasan proses pengadaan barang dan jasa bernilai strategis", "Audit keselarasan laporan keuangan tahunan tingkat kementerian"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengawasan Penerapan Sistem Merit pada K/L</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian PAN-RB juga melakukan <strong>pengawasan penerapan Sistem Merit pada Kementerian/Lembaga</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Berdasarkan UU ASN terbaru pasca penghapusan KASN, pengawasan terhadap kepatuhan instansi pemerintah dalam menerapkan sistem merit—mulai dari rekrutmen, mutasi, promosi, hingga manajemen talenta berbasis kualifikasi dan kompetensi—dijalankan di bawah kendali Kementerian PAN-RB.</p><p>Jawaban tepat: <strong>A. Pengawasan penerapan Sistem Merit pada Kementerian/Lembaga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pemeriksaan fisik sarana inventaris kantor merupakan tugas pengelola barang milik negara satker.</li><li><strong>C</strong>: Pengawasan jam kerja harian merupakan tugas atasan langsung dan unit kepatuhan internal.</li><li><strong>D</strong>: Pengawasan pengadaan barang dan jasa merupakan fungsi LKPP dan inspektorat jenderal.</li><li><strong>E</strong>: Audit laporan keuangan kementerian merupakan mandat Badan Pemeriksa Keuangan dan APIP.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Selain hal di atas, Kementerian PAN-RB juga melakukan pengawasan • penerapan Sistem Merit pada Kementerian/Lembaga.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pengendalian kebijakan Manajemen ASN yang dijalankan oleh Kementerian PAN-RB memiliki ruang lingkup...", "options": ["Khusus pada instansi vertikal yang berkedudukan di ibu kota", "Terbatas pada unit pelaksana teknis yang membidangi diklat", "Menyeluruh dan terpadu pada tataran instansi skala nasional", "Tertuju pada kelompok pegawai yang menjalani masa percobaan", "Berfokus pada penataan struktur kepegawaian kantor daerah"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ruang Lingkup Pengendalian Kebijakan Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pendelegasian kewenangan kepada Kementerian PAN-RB mencakup <strong>pengendalian kebijakan Manajemen ASN secara nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Pengendalian kebijakan kepegawaian oleh Kementerian PAN-RB bersifat terpadu dan menjangkau seluruh spektrum instansi pemerintah di Indonesia, baik kementerian/lembaga di tingkat pusat maupun pemerintah daerah provinsi dan kabupaten/kota, guna menjamin standar manajemen ASN yang seragam secara nasional.</p><p>Jawaban tepat: <strong>C. Menyeluruh dan terpadu pada tataran instansi skala nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengendalian tidak dibatasi pada kantor ibu kota saja, melainkan mencakup seluruh instansi daerah.</li><li><strong>B</strong>: Pengendalian mencakup seluruh siklus manajemen ASN, bukan hanya lembaga kediklatan.</li><li><strong>D</strong>: Pengendalian berlaku bagi seluruh jenjang status ASN dan bukan hanya pegawai masa percobaan.</li><li><strong>E</strong>: Penataan kepegawaian meliputi instansi pemerintah pusat sekaligus pemerintah daerah secara setara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengendalian kebijakan Manajemen ASN • secara nasional.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Selain menetapkan kebijakan strategis makro, Kementerian PAN-RB bertugas melakukan pengendalian atas...", "options": ["Alokasi besaran pagu indikatif belanja pegawai kementerian", "Penetapan standar tarif penerimaan negara bukan pajak instansi", "Mekanisme penyelesaian sengketa tata usaha negara kepegawaian", "Penyusunan kurikulum teknis pendidikan vokasi instansi vertikal", "Pelaksanaan kebijakan teknis operasional Manajemen Aparatur"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengendalian Pelaksanaan Kebijakan Teknis Manajemen ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian PAN-RB juga melakukan <strong>pengendalian pelaksanaan kebijakan teknis Manajemen ASN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Berdasarkan uraian kelembagaan pada modul kepegawaian, peran Kementerian PAN-RB tidak berhenti pada perumusan norma regulasi strategis di atas kertas, tetapi juga aktif mengendalikan eksekusi dan pelaksanaan kebijakan teknis manajemen ASN agar implementasi di setiap K/L dan Pemda berjalan tepat sasaran.</p><p>Jawaban tepat: <strong>E. Pelaksanaan kebijakan teknis operasional Manajemen Aparatur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penetapan pagu belanja pegawai merupakan kewenangan Menteri Keuangan dalam proses penganggaran.</li><li><strong>B</strong>: Penetapan tarif PNBP diatur melalui Peraturan Pemerintah atas usul kementerian terkait dan Kemenkeu.</li><li><strong>C</strong>: Penyelesaian sengketa tata usaha negara merupakan ranah peradilan PTUN dan Badan Pertimbangan ASN.</li><li><strong>D</strong>: Kurikulum teknis diklat vokasi dikembangkan oleh masing-masing instansi pembina dan LAN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kementerian PAN-RB juga melakukan pengendalian pelaksanaan kebijakan teknis • Manajemen ASN. Selain hal di atas, Kementerian PAN-RB juga melakukan pengawasan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C3 - Aplikasi", "question": "Instansi kepegawaian menyusun rencana kerja terkait rekrutmen dan pelatihan. Kementerian yang mengoordinasikan rencana kerja tersebut adalah...", "options": ["Kementerian Hukum dan Hak Asasi Manusia", "Kementerian PAN-RB Republik Indonesia", "Kementerian Sekretariat Negara Republik", "Kementerian Koordinator Bidang Perekonomian", "Kementerian Dalam Negeri Republik Indonesia"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Koordinasi Rencana Kerja Lembaga Manajemen ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian PAN-RB <strong>mengoordinasikan rencana kerja lembaga yang berkaitan dengan penyelenggaraan Manajemen ASN</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Ketentuan perundang-undangan menempatkan Kementerian PAN-RB sebagai koordinator lintas lembaga kepegawaian (seperti BKN dan LAN). Rencana kerja strategis lembaga-lembaga tersebut yang bersentuhan dengan pengadaan, pembinaan, pembelajaran, dan penilaian kinerja ASN wajib dikoordinasikan dan disinkronkan oleh Kementerian PAN-RB.</p><p>Jawaban tepat: <strong>B. Kementerian PAN-RB Republik Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kemenkumham membidangi urusan hukum dan perundang-undangan nasional.</li><li><strong>C</strong>: Kemensetneg melayani kebutuhan administratif kelembagaan kepresidenan.</li><li><strong>D</strong>: Kemenko Perekonomian mengoordinasikan sektor riil, moneter, dan industri.</li><li><strong>E</strong>: Kemendagri mengoordinasikan urusan umum pemerintahan dalam negeri dan otonomi daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Secara lebih detil, Kementerian PAN-RB mengoordinasikan rencana kerja • lembaga yang berkaitan dengan penyelenggaraan Manajemen ASN serta melakukan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Tiga aspek penyelenggaraan aparatur yang menjadi kewenangan tertinggi Presiden selaku kepala pemerintahan meliputi...", "options": ["Pengawasan internal, penuntutan pidana, dan penegakan hukum", "Perencanaan tata ruang, pembangunan fisik, dan perizinan", "Penetapan tarif fiskal, peredaran moneter, dan perbankan", "Kebijakan, pembinaan profesi, dan Manajemen Aparatur Sipil", "Pengadaan logistik, pertahanan maritim, dan ketahanan pangan"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Aspek Mandat Kekuasaan Tertinggi Presiden</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden merupakan pemegang kekuasaan pemerintahan tertinggi dalam <strong>kebijakan, pembinaan profesi, dan Manajemen Aparatur Sipil Negara (ASN)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Modul kepegawaian merumuskan secara tegas tritunggal kekuasaan kepegawaian Presiden, yaitu penetapan arah kebijakan umum kepegawaian, pembinaan profesi korps ASN, dan penyelenggaraan manajemen aparatur secara terpadu di seluruh NKRI.</p><p>Jawaban tepat: <strong>D. Kebijakan, pembinaan profesi, dan Manajemen Aparatur Sipil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Merupakan rumpun tugas yudisial dan penegakan hukum pidana oleh kejaksaan dan kepolisian.</li><li><strong>B</strong>: Merupakan lingkup tugas kementerian bidang infrastruktur, tata ruang, dan investasi.</li><li><strong>C</strong>: Merupakan lingkup kebijakan fiskal Kemenkeu dan moneter Bank Indonesia.</li><li><strong>E</strong>: Merupakan bidang pertahanan negara serta ketahanan logistik pangan nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kebijakan, pembinaan profesi, dan Manajemen Aparatur Sipil Negara (ASN). Untuk • menyelenggarakan kekuasaan dimaksud, Presiden mendelegasikan kepada Kementerian</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Prinsip utama pendelegasian kewenangan pengendalian kebijakan Manajemen ASN secara nasional bertujuan untuk mewujudkan...", "options": ["Tata kelola kepegawaian yang selaras, terpadu, dan berstandar nasional", "Otonomi kepegawaian yang mandiri pada setiap satuan kerja instansi daerah", "Pembatasan keterlibatan instansi pusat dalam pengembangan karier pegawai", "Pengalihan tanggung jawab pengelolaan aparatur kepada lembaga nonpemerintah", "Penyederhanaan jenjang jabatan struktural tanpa mempertimbangkan fungsi"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pengendalian Kebijakan ASN Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengendalian kebijakan Manajemen ASN dilaksanakan <strong>secara nasional</strong> guna mewujudkan keterpaduan dan keselarasan aparatur.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Kebijakan pengendalian manajemen ASN secara terpusat oleh Kementerian PAN-RB dirancang untuk mengatasi fragmentasi, ego sektoral antardaerah, dan disparitas kualitas aparatur. Dengan adanya pengendalian nasional, standar meritokrasi, profesionalitas, dan integritas ASN seragam di seluruh Indonesia.</p><p>Jawaban tepat: <strong>A. Tata kelola kepegawaian yang selaras, terpadu, dan berstandar nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Otonomi daerah yang lepas kendali justru melahirkan ketimpangan meritokrasi dan politisasi birokrasi.</li><li><strong>C</strong>: Instansi pusat tetap memegang kendali standar kompetensi dan pembinaan karier nasional.</li><li><strong>D</strong>: Manajemen ASN adalah kewenangan publik konstitusional yang tidak dapat dialihkan ke nonpemerintah.</li><li><strong>E</strong>: Penyederhanaan birokrasi tetap mengutamakan analisis fungsi dan kebutuhan keahlian organisasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 17) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>koordinasi dan sinkronisasi kebijakan, dan pengendalian kebijakan Manajemen ASN • secara nasional.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penyelenggaraan kebijakan dan manajemen ASN yang didasarkan pada kualifikasi, kompetensi, dan kinerja secara adil dan wajar disebut...", "options": ["Sistem Patronase Birokrasi Terpadu", "Sistem Merit Aparatur Sipil Negara", "Sistem Senioritas Masa Kerja Pegawai", "Sistem Afirmasi Kewilayahan Daerah", "Sistem Perjanjian Kinerja Periodik"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Dasar Sistem Merit</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan dan manajemen ASN didasarkan pada <strong>kualifikasi, kompetensi, dan kinerja secara adil dan wajar</strong> tanpa diskriminasi (<strong>Sistem Merit</strong>).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Bab IV Manajemen ASN menegaskan bahwa Sistem Merit merupakan asas fundamental birokrasi modern di mana seluruh keputusan kepegawaian (pengadaan, penempatan, promosi, mutasi, dan penggajian) semata-mata didasarkan pada perpaduan objektif kualifikasi pendidikan, rekam jejak kompetensi, dan capaian kinerja nyata tanpa diskriminasi.</p><p>Jawaban tepat: <strong>B. Sistem Merit Aparatur Sipil Negara</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sistem patronase merupakan sistem rekrutmen berbasis kedekatan politis/nepotisme yang dilarang.</li><li><strong>C</strong>: Sistem senioritas hanya mendasarkan pada masa kerja dan umur tanpa menilai kompetensi objektif.</li><li><strong>D</strong>: Sistem afirmasi merupakan kebijakan khusus perlakuan tertentu dan bukan definisi umum sistem merit.</li><li><strong>E</strong>: Perjanjian kinerja merupakan salah satu instrumen penilaian dan bukan nama sistem manajemen ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kebijakan dan manajemen ASN didasarkan pada kualifikasi, kompetensi, dan • kinerja secara adil dan wajar dengan tanpa membedakan latar belakang politik, ras, warna</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan ketentuan UU ASN, ruang lingkup Manajemen Aparatur Sipil Negara secara komprehensif mencakup...", "options": ["Manajemen Pegawai Tetap dan Pegawai Magang", "Manajemen Aparatur Sipil dan Anggota Militer", "Manajemen Tenaga Alih Daya dan Staf Honorer", "Manajemen Pegawai Negeri Sipil dan Manajemen PPPK", "Manajemen Tenaga Kontrak dan Tenaga Pendukung"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Ruang Lingkup Manajemen ASN (PNS dan PPPK)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Manajemen ASN</strong> meliputi <strong>Manajemen Pegawai Negeri Sipil (PNS)</strong> dan <strong>Manajemen Pegawai Pemerintah dengan Perjanjian Kerja (PPPK)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Sebagaimana ditegaskan dalam ketentuan Pasal UU ASN pada modul kepegawaian, pilar ASN hanya terdiri atas dua kelompok aparatur yaitu PNS dan PPPK. Oleh sebab itu, ruang lingkup manajemen ASN secara utuh terbagi menjadi dua sub-manajemen yaitu Manajemen PNS dan Manajemen PPPK.</p><p>Jawaban tepat: <strong>D. Manajemen Pegawai Negeri Sipil dan Manajemen PPPK</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pegawai magang bukan merupakan unsur Pegawai ASN menurut undang-undang.</li><li><strong>B</strong>: Anggota militer (TNI) diatur dalam undang-undang pertahanan negara tersendiri.</li><li><strong>C</strong>: Tenaga alih daya dan staf honorer bukan bagian dari profesi Pegawai ASN.</li><li><strong>E</strong>: Tenaga pendukung non-ASN tidak termasuk dalam ruang lingkup manajemen ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Manajemen ASN ini meliputi Manajemen Pegawai Negeri Sipil (PNS) dan • Manajemen Pegawai Pemerintah dengan Perjanjian Kerja (PPPK).</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Presiden dapat mendelegasikan kewenangan pembinaan Manajemen ASN di lingkungan instansi pemerintah kepada...", "options": ["Pejabat yang Berwenang di kementerian atau lembaga", "Pimpinan dewan perwakilan rakyat tingkat daerah", "Tim independen penilai kinerja bentukan organisasi", "Kepala satuan pengawas internal perusahaan negara", "Ketua asosiasi profesi pegawai instansi perbankan"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Delegasi Pembinaan Manajemen ASN kepada PyB</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Presiden dapat mendelegasikan kewenangan pembinaan Manajemen ASN kepada <strong>Pejabat yang Berwenang di kementerian, sekretaris jendral/sekretariat lembaga negara, sekretariat lembaga nonstruktural, sekretaris daerah provinsi dan kabupaten/kota</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Dalam hierarki manajemen aparatur, Presiden dapat melimpahkan wewenang pembinaan teknis manajemen kepegawaian kepada Pejabat yang Berwenang (PyB), seperti Sekretaris Jenderal pada kementerian/lembaga atau Sekretaris Daerah pada pemerintah daerah provinsi/kabupaten/kota.</p><p>Jawaban tepat: <strong>A. Pejabat yang Berwenang di kementerian atau lembaga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pimpinan DPRD merupakan unsur lembaga legislatif daerah dan bukan pejabat birokrasi kepegawaian.</li><li><strong>C</strong>: Tim penilai independen bukan pejabat birokrasi yang memegang kewenangan delegasi Presiden.</li><li><strong>D</strong>: Pengawas internal BUMN mengawasi korporasi bisnis dan bukan aparatur sipil negara.</li><li><strong>E</strong>: Asosiasi perbankan bergerak di bidang industri keuangan swasta di luar ranah ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Presiden dapat mendelegasikan kewenangan pembinaan Manajemen ASN • kepada Pejabat yang Berwenang di kementerian, sekretaris jendral/sekretariat lembaga</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam mekanisme manajemen kepegawaian, Pejabat yang Berwenang (PyB) menjalankan fungsi utama yaitu memberikan...", "options": ["Persetujuan penetapan besaran tunjangan kinerja kepada kasubbag", "Putusan mutlak pemberhentian dengan hormat tanpa persetujuan menteri", "Rekomendasi usulan kepegawaian kepada Pejabat Pembina Kepegawaian", "Surat perintah penyelidikan pelanggaran tata tertib bendahara satker", "Otorisasi anggaran belanja pegawai kepada kuasa pengguna anggaran"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi PyB Memberikan Usulan kepada PPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pejabat yang berwenang <strong>memberikan rekomendasi usulan kepada Pejabat Pembina Kepegawaian (PPK) di instansi masing-masing</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Berdasarkan tata aturan manajemen kepegawaian, terdapat pembagian peran tegas antara PyB (pejabat karier tertinggi seperti Sekjen/Sekda) dan PPK (Menteri/Kepala Lembaga/Gubernur/Bupati/Walikota). PyB memproses telaah administratif dan teknis untuk menghasilkan rekomendasi usulan mutasi/karier pegawai yang diajukan kepada PPK selaku pengambil keputusan definitif.</p><p>Jawaban tepat: <strong>C. Rekomendasi usulan kepegawaian kepada Pejabat Pembina Kepegawaian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penetapan formula besaran tunjangan diatur melalui peraturan perundang-undangan dan keputusan PPK.</li><li><strong>B</strong>: Keputusan akhir pemberhentian berada di tangan PPK dan Presiden, bukan keputusan mutlak PyB.</li><li><strong>D</strong>: Penyelidikan pelanggaran kode etik/disiplin merupakan tugas tim pemeriksa dan inspektorat.</li><li><strong>E</strong>: Otorisasi anggaran merupakan fungsi Pengguna Anggaran dalam rezim keuangan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pejabat yang berwenang sebagaimana dimaksud memberikan rekomendasi usulan • kepada Pejabat Pembina Kepegawaian (PPK) di instansi masing-masing. Pejabat yang</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Pejabat yang Berwenang (PyB) mengusulkan pengangkatan, pemindahan, dan pemberhentian kepada PPK khusus untuk...", "options": ["Pejabat Pimpinan Tinggi Utama serta pimpinan lembaga nonstruktural", "Anggota dewan perwakilan rakyat serta hakim peradilan umum negara", "Direktur utama serta dewan komisaris badan usaha milik pemerintah", "Pejabat pembina kepegawaian wilayah serta kepala satuan polisi pamong", "Pejabat Administrasi serta Pejabat Fungsional di lingkungan instansi"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Subjek Usulan PyB kepada PPK</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pejabat yang Berwenang mengusulkan pengangkatan, pemindahan, dan pemberhentian <strong>Pejabat Administrasi dan Pejabat Fungsional kepada Pejabat Pembina Kepegawaian di instansi masing-masing</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Teks regulasi kepegawaian secara eksplisit merumuskan bahwa lingkup usulan pengangkatan, pemindahan (rotasi/mutasi), dan pemberhentian yang diproses oleh PyB kepada PPK adalah bagi Pejabat Administrasi (administrator, pengawas, pelaksana) dan Pejabat Fungsional di unit kerja instansi yang bersangkutan.</p><p>Jawaban tepat: <strong>E. Pejabat Administrasi serta Pejabat Fungsional di lingkungan instansi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengangkatan JPT Utama dan Madya diusulkan oleh PPK kepada Presiden melalui Tim Penilai Akhir.</li><li><strong>B</strong>: Anggota DPR dipilih melalui pemilu dan hakim diangkat melalui proses tersendiri di Komisi Yudisial.</li><li><strong>C</strong>: Direksi BUMN diangkat melalui Rapat Umum Pemegang Saham oleh Kementerian BUMN.</li><li><strong>D</strong>: PPK adalah kepala daerah/menteri itu sendiri yang tidak diusulkan oleh PyB.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Berwenang mengusulkan pengangkatan, pemindahan, dan pemberhentian Pejabat • Administrasi dan Pejabat Fungsional kepada Pejabat Pembina Kepegawaian di instansi</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penyelenggaraan Manajemen Pegawai Negeri Sipil pada instansi tingkat pusat secara sah dilaksanakan oleh...", "options": ["Badan usaha swasta rekanan kerja kementerian", "Komisi independen pemerhati aparatur birokrasi", "Lembaga swadaya masyarakat pemerhati tata kelola", "Pemerintah pusat Republik Indonesia yang berwenang", "Dewan perwakilan daerah tingkat wilayah provinsi"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyelenggaraan Manajemen PNS Instansi Pusat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Manajemen PNS pada Instansi Pusat dilaksanakan oleh pemerintah pusat</strong>, sementara Manajemen PNS pada Instansi Daerah dilaksanakan oleh pemerintah daerah.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Sesuai prinsip desentralisasi dan dekonsentrasi administrasi negara, pengelolaan aparatur pada instansi kementerian/lembaga pusat dijalankan secara langsung oleh pemerintah pusat di bawah koordinasi instansi kepegawaian nasional.</p><p>Jawaban tepat: <strong>D. Pemerintah pusat Republik Indonesia yang berwenang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Manajemen kepegawaian aparatur negara merupakan kewenangan publik mutlak yang dilarang diserahkan ke swasta.</li><li><strong>B</strong>: Komisi independen hanya menjalankan peran kajian/rekomendasi dan bukan eksekutor manajemen.</li><li><strong>C</strong>: LSM tidak memiliki legitimasi yuridis dalam menyelenggarakan manajemen PNS pemerintah.</li><li><strong>E</strong>: DPD merupakan lembaga perwakilan legislatif dan bukan pelaksana eksekutif manajemen kepegawaian pusat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Manajemen PNS pada Instansi Pusat dilaksanakan oleh pemerintah pusat, • sementara Manajemen PNS pada Instansi Daerah dilaksanakan oleh pemerintah daerah.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penyelenggaraan Manajemen Pegawai Negeri Sipil pada unit instansi daerah secara sah dilaksanakan oleh...", "options": ["Pemerintah daerah yang bersangkutan", "Kantor wilayah instansi kementerian", "Dewan pengawas independen daerah", "Komando kewilayahan aparat teritorial", "Kamar dagang dan industri tingkat wilayah"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penyelenggaraan Manajemen PNS Instansi Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Manajemen PNS pada Instansi Daerah dilaksanakan oleh pemerintah daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Berdasarkan ketentuan Bab IV modul kepegawaian, pelaksanaan pembinaan dan manajemen teknis kepegawaian bagi PNS daerah (provinsi, kabupaten, dan kota) didelegasikan pelaksanaannya kepada pemerintah daerah bersangkutan dengan tetap berpedoman pada norma, standar, prosedur, dan kriteria nasional.</p><p>Jawaban tepat: <strong>A. Pemerintah daerah yang bersangkutan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Kantor wilayah kementerian mengelola PNS instansi vertikal pusat di daerah, bukan PNS pemda.</li><li><strong>C</strong>: Dewan pengawas bukan lembaga eksekutif pemegang kekuasaan kepegawaian daerah.</li><li><strong>D</strong>: Komando teritorial merupakan satuan militer pertahanan negara dan bukan pengelola sipil.</li><li><strong>E</strong>: Kadin merupakan wadah dunia usaha komersial yang terpisah dari struktur aparatur negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pada Instansi Daerah dilaksanakan oleh pemerintah daerah.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Otoritas yang berwenang menetapkan Kebijakan Perencanaan Kebutuhan Pegawai ASN secara nasional adalah...", "options": ["Menteri Perencanaan Pembangunan Nasional", "Kepala Badan Kepegawaian Negara", "Menteri Pendayagunaan Aparatur Negara dan RB", "Menteri Keuangan Republik Indonesia", "Menteri Koordinator Bidang Perekonomian"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penetapan Kebijakan Perencanaan Kebutuhan ASN Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Menteri Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (PAN-RB) <strong>menetapkan Kebijakan Perencanaan Kebutuhan Pegawai ASN secara nasional</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Berdasarkan ketentuan Undang-Undang Nomor 20 Tahun 2023, kewenangan atributif untuk merumuskan dan menetapkan Kebijakan Perencanaan Kebutuhan Pegawai ASN dalam skala nasional berada di bawah mandat Menteri PAN-RB. Kebijakan ini menjadi panduan induk bagi setiap instansi pusat dan daerah dalam menyusun formasi pengadaan ASN.</p><p>Jawaban tepat: <strong>C. Menteri Pendayagunaan Aparatur Negara dan RB</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menteri PPN/Bappenas menyusun RPJMN secara makro, namun penetapan kebutuhan ASN ditetapkan Menteri PAN-RB.</li><li><strong>B</strong>: Kepala BKN mengelola sistem informasi dan teknis pengadaan, bukan menetapkan kebijakan perencanaan nasional.</li><li><strong>D</strong>: Menteri Keuangan memberikan pertimbangan ketersediaan fiskal belanja pegawai.</li><li><strong>E</strong>: Kemenko Perekonomian mengoordinasikan bidang ekonomi makro dan bukan urusan ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Menteri • Pendayagunaan Aparatur Negara dan Reformasi Birokrasi (PAN-RB) • menetapkan Kebijakan Perencanaan Kebutuhan Pegawai ASN secara</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Kebijakan Perencanaan Kebutuhan Pegawai ASN secara nasional ditetapkan oleh Menteri PAN-RB berdasarkan...", "options": ["Hasil jajak pendapat elektoral serta survei aspirasi masyarakat", "Laporan pertanggungjawaban tahunan badan usaha milik pemerintah", "Permintaan kebutuhan penambahan personel dari serikat kerja dinas", "Realisasi serapan pagu anggaran belanja barang masing-masing satker", "Prioritas nasional sesuai rencana pembangunan jangka menengah nasional"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Penetapan Perencanaan Kebutuhan Pegawai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebijakan Perencanaan Kebutuhan Pegawai ASN secara nasional ditetapkan <strong>berdasarkan prioritas nasional sesuai dengan rencana pembangunan jangka menengah nasional (RPJMN)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Ketentuan UU 20/2023 Pasal 19 mengamanatkan bahwa pengadaan dan perencanaan formasi ASN tidak boleh berdiri sendiri atau sekadar mengganti pegawai pensiun secara mekanis, melainkan wajib diselaraskan dengan prioritas pembangunan nasional yang tertuang dalam dokumen RPJMN.</p><p>Jawaban tepat: <strong>E. Prioritas nasional sesuai rencana pembangunan jangka menengah nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Survei elektoral merupakan instrumen politik praktis yang dilarang menjadi dasar birokrasi.</li><li><strong>B</strong>: Laporan BUMN mengukur kinerja perseroan komersial dan bukan rujukan perencanaan ASN.</li><li><strong>C</strong>: Perencanaan formasi berakar pada analisis jabatan dan prioritas pembangunan, bukan desakan serikat.</li><li><strong>D</strong>: Serapan belanja barang mengukur realisasi pengadaan fisik, bukan kebutuhan formasi pegawai.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berdasarkan • prioritas • rencana • pembangunan jangka menengah nasional serta dengan mempertimbangkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Selain prioritas pembangunan jangka menengah, penyusunan kebijakan perencanaan kebutuhan ASN nasional wajib mempertimbangkan...", "options": ["Fluktuasi indeks harga saham gabungan di bursa pasar modal", "Kemampuan keuangan negara dalam membiayai belanja kepegawaian", "Tren perubahan kurs mata uang asing terhadap cadangan devisa", "Tingkat suku bunga acuan pinjaman perbankan lembaga komersial", "Target pertumbuhan ekonomi global kawasan perdagangan bersama"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pertimbangan Kemampuan Keuangan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Perencanaan kebutuhan pegawai ASN ditetapkan berdasar prioritas nasional serta <strong>dengan mempertimbangkan kemampuan keuangan negara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Pengangkatan ASN baru memiliki implikasi belanja pegawai jangka panjang (gaji, tunjangan, dan jaminan pensiun). Oleh karena itu, modul kepegawaian secara tegas mensyaratkan bahwa penetapan kebutuhan pegawai ASN nasional wajib memperhitungkan kapasitas dan kesinambungan fiskal keuangan negara.</p><p>Jawaban tepat: <strong>B. Kemampuan keuangan negara dalam membiayai belanja kepegawaian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: IHSG merupakan indikator pasar modal yang fluktuatif harian dan bukan penentu formasi ASN.</li><li><strong>C</strong>: Cadangan devisa dan kurs valas merupakan domain moneter Bank Indonesia.</li><li><strong>D</strong>: Suku bunga acuan pinjaman adalah instrumen perbankan komersial.</li><li><strong>E</strong>: Pertumbuhan ekonomi global merupakan asumsi makro internasional dan bukan syarat limitatif perencanaan ASN.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 19) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menetapkan Kebijakan Perencanaan Kebutuhan Pegawai ASN secara • pembangunan jangka menengah nasional serta dengan mempertimbangkan</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Setiap Instansi Pemerintah wajib menyusun kebutuhan jumlah dan jenis jabatan PNS berdasarkan...", "options": ["Rekomendasi subjektif pejabat administrator kepegawaian instansi", "Jumlah usulan permohonan mutasi pegawai antarwilayah pemerintahan", "Estimasi proyeksi penerimaan kas bukan pajak tahun anggaran depan", "Ketersediaan daya tampung gedung kantor operasional satuan kerja", "Analisis jabatan, analisis beban kerja, serta peta jabatan unit"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dasar Penyusunan Kebutuhan PNS (Anjab dan ABK)</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setiap Instansi Pemerintah wajib menyusun kebutuhan jumlah dan jenis jabatan PNS berdasarkan <strong>analisis jabatan dan analisis beban kerja, peta Jabatan di masing-masing unit organisasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Berdasarkan PP 11/2017 stdd PP 17/2020, pengusulan formasi PNS oleh kementerian, lembaga, maupun pemda wajib didasarkan pada metode ilmiah dan terukur, yaitu Anjab (untuk mengidentifikasi jenis jabatan), ABK (untuk menghitung beban volume kerja nyata), dan peta jabatan (untuk memetakan ketersediaan pegawai per jenjang).</p><p>Jawaban tepat: <strong>E. Analisis jabatan, analisis beban kerja, serta peta jabatan unit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyusunan formasi wajib berbasis metode terukur (Anjab-ABK), bukan perkiraan subjektif pejabat.</li><li><strong>B</strong>: Permohonan mutasi merupakan dinamika mobilitas karier dan bukan dasar penghitungan formasi induk.</li><li><strong>C</strong>: Proyeksi PNBP berkaitan dengan target pendapatan negara, bukan beban kerja pegawai.</li><li><strong>D</strong>: Kapasitas fisik gedung bukan variabel penentu kebutuhan formasi kompetensi aparatur.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menyusun kebutuhan jumlah dan jenis jabatan PNS berdasarkan analisis • jabatan dan analisis beban kerja, peta Jabatan di masing-masing unit</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Dalam menyusun kebutuhan jenis dan jumlah PNS, instansi pemerintah selain mendasarkan pada peta jabatan wajib memperhatikan...", "options": ["Tren dinamika politik daerah serta komposisi perwakilan rakyat", "Kondisi geografis daerah, jumlah penduduk, dan belanja pegawai", "Jumlah kepemilikan aset sarana dan fasilitas kendaraan operasional", "Tingkat persaingan dunia usaha komersial pada wilayah bersangkutan", "Realisasi pembiayaan program bantuan sosial kemasyarakatan daerah"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Variabel Kontekstual Penyusunan Formasi PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyusunan formasi PNS wajib <strong>memperhatikan kondisi geografis daerah, jumlah penduduk, dan rasio alokasi anggaran belanja pegawai</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Ketentuan manajemen PNS menegaskan bahwa selain Anjab-ABK, terdapat tiga variabel kontekstual yang wajib dipertimbangkan: rentang kendali geografis (kepulauan/pegunungan), rasio jumlah penduduk yang dilayani, serta rasio belanja pegawai dalam APBD/APBN agar anggaran belanja tidak habis hanya untuk menggaji aparatur.</p><p>Jawaban tepat: <strong>B. Kondisi geografis daerah, jumlah penduduk, dan belanja pegawai</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dinamika politik lokal bukan pertimbangan sah dalam manajemen kepegawaian berbasis merit.</li><li><strong>C</strong>: Aset kendaraan operasional merupakan sarana pendukung tugas dan bukan faktor penentu formasi.</li><li><strong>D</strong>: Persaingan dunia usaha swasta merupakan indikator iklim bisnis dan bukan formula formasi PNS.</li><li><strong>E</strong>: Realisasi belanja bansos tidak menjadi variabel penghitungan kebutuhan aparatur negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dan memperhatikan kondisi geografis daerah, • jumlah penduduk, dan rasio alokasi anggaran belanja pegawai.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Prinsip transparansi dan nondiskriminasi dalam pengadaan ASN diwujudkan melalui kewajiban instansi pemerintah untuk...", "options": ["Melakukan seleksi calon aparatur melalui mekanisme internal tertutup", "Memprioritaskan pendaftar yang memiliki ikatan alumni almamater dinas", "Menetapkan batas seleksi berdasarkan rekomendasi tokoh kelembagaan", "Mengumumkan secara terbuka kepada masyarakat adanya kebutuhan jabatan", "Menyeleksi berkas pelamar berdasar urutan waktu penyerahan dokumen"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengumuman Terbuka dan Kesempatan yang Sama bagi WNI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Setiap Instansi Pemerintah <strong>mengumumkan secara terbuka kepada masyarakat adanya kebutuhan jabatan</strong> untuk diisi dari calon Pegawai ASN dan setiap WNI <strong>mempunyai kesempatan yang sama</strong> setelah memenuhi persyaratan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Berdasarkan UU ASN Nomor 20 Tahun 2023, seluruh proses pengadaan aparatur wajib diumumkan secara terbuka dan transparan kepada publik melalui portal resmi nasional. Setiap WNI yang memenuhi kualifikasi memiliki hak dan kesempatan yang sama untuk melamar tanpa perlakuan diskriminatif.</p><p>Jawaban tepat: <strong>D. Mengumumkan secara terbuka kepada masyarakat adanya kebutuhan jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perekrutan secara tertutup melanggar asas transparansi dan bertentangan dengan UU ASN.</li><li><strong>B</strong>: Memprioritaskan almamater tertentu melanggar prinsip kesetaraan kesempatan bagi seluruh WNI.</li><li><strong>C</strong>: Rekomendasi tokoh informal tidak diakui dalam seleksi meritokrasi aparatur sipil negara.</li><li><strong>E</strong>: Seleksi dilakukan berbasis penilaian kompetensi objektif, bukan kecepatan penyerahan dokumen.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Setiap Instansi Pemerintah mengumumkan secara terbuka kepada • masyarakat adanya kebutuhan jabatan untuk diisi dari calon Pegawai ASN. • mempunyai kesempatan yang sama untuk</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jabatan dalam suatu instansi pemerintah yang dapat diisi melalui seleksi pengadaan PNS secara nasional untuk Jabatan Administrasi adalah...", "options": ["Khusus pada kelompok Jabatan Pelaksana", "Seluruh jenjang pada Jabatan Pengawas", "Posisi manajerial Jabatan Administrator", "Tingkatan eselon Jabatan Pimpinan Utama", "Formasi strategis Jabatan Pimpinan Madya"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Formasi Jabatan Administrasi Pengadaan PNS Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan administrasi yang dapat diisi melalui pengadaan PNS secara nasional adalah <strong>Jabatan Administrasi (khusus pada Jabatan Pelaksana)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Dalam sistem pengadaan PNS nasional, jabatan administrasi yang dibuka untuk formasi rekrutmen umum CPNS dibatasi khusus pada Jabatan Pelaksana. Jabatan struktural manajerial seperti Jabatan Pengawas dan Jabatan Administrator tidak diisi dari jalur pengadaan umum melainkan melalui promosi dan mutasi karier PNS internal.</p><p>Jawaban tepat: <strong>A. Khusus pada kelompok Jabatan Pelaksana</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Jabatan Pengawas diisi melalui mekanisme promosi PNS berprestasi dan bukan seleksi CPNS umum.</li><li><strong>C</strong>: Jabatan Administrator merupakan jabatan manajerial tingkat menengah yang diisi lewat promosi.</li><li><strong>D</strong>: Jabatan Pimpinan Tinggi Utama diisi melalui mekanisme seleksi terbuka atau penugasan Presiden.</li><li><strong>E</strong>: Jabatan Pimpinan Tinggi Madya diisi melalui seleksi terbuka bagi PNS yang telah memenuhi syarat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>secara nasional. Jabatan dalam suatu instansi pemerintah yang dapat diisi • (khusus pada Jabatan Pelaksana), Jabatan Fungsional Keahlian (yaitu</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jenjang Jabatan Fungsional Keahlian yang dapat dialokasikan dalam pengadaan PNS secara nasional adalah...", "options": ["Jabatan Fungsional Ahli Utama dan Ahli Madya", "Jabatan Fungsional Penyelia dan Ahli Pertama", "Jabatan Fungsional Ahli Pertama dan Ahli Muda", "Jabatan Fungsional Terampil dan Ahli Pratama", "Jabatan Fungsional Ahli Madya dan Ahli Muda"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Formasi JF Keahlian Pengadaan PNS Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan Fungsional Keahlian yang dapat diisi melalui pengadaan PNS secara nasional adalah <strong>Jabatan Fungsional Ahli Pertama dan Ahli Muda</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Berdasarkan PP 11/2017 jo PP 17/2020, pengadaan CPNS formasi keahlian hanya dialokasikan untuk dua jenjang awal, yaitu Ahli Pertama (kualifikasi sarjana/D4) dan Ahli Muda (kualifikasi magister/spesialis tertentu). Jenjang Ahli Madya dan Ahli Utama tidak dibuka untuk pengadaan PNS nasional melainkan melalui kenaikan jenjang promosi karier.</p><p>Jawaban tepat: <strong>C. Jabatan Fungsional Ahli Pertama dan Ahli Muda</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ahli Utama dan Ahli Madya merupakan jenjang kehormatan tinggi yang dicapai lewat promosi karier.</li><li><strong>B</strong>: Penyelia merupakan jenjang pada kategori JF Keterampilan, bukan JF Keahlian.</li><li><strong>D</strong>: Terampil merupakan jenjang pada kategori Keterampilan dan tidak ada nomenklatur Ahli Pratama.</li><li><strong>E</strong>: Ahli Madya tidak dapat diisi langsung melalui formasi pengadaan CPNS secara nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>(yaitu • Jabatan Fungsional Ahli Pertama dan Ahli Muda), serta Jabatan Fungsional</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Jenjang Jabatan Fungsional Keterampilan yang dialokasikan dalam seleksi pengadaan PNS nasional terdiri atas...", "options": ["Jabatan Fungsional Penyelia dan Mahir", "Jabatan Fungsional Terampil dan Mahir", "Jabatan Fungsional Penyelia dan Terampil", "Jabatan Fungsional Pemula dan Penyelia", "Jabatan Fungsional Pemula dan Terampil"], "answer": 4, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Formasi JF Keterampilan Pengadaan PNS Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Jabatan Fungsional Keterampilan yang dapat diisi melalui pengadaan PNS secara nasional adalah <strong>Jabatan Fungsional Pemula dan Terampil</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E merupakan jawaban yang tepat. Regulasi pengadaan PNS menegaskan bahwa formasi JF Keterampilan yang dibuka melalui seleksi CPNS nasional dibatasi pada dua jenjang awal yaitu jenjang Pemula (kualifikasi SMK/sederajat) dan jenjang Terampil (kualifikasi Diploma III). Jenjang lanjutan seperti Mahir dan Penyelia diisi melalui promosi kenaikan jenjang berdasar angka kredit.</p><p>Jawaban tepat: <strong>E. Jabatan Fungsional Pemula dan Terampil</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penyelia dan Mahir merupakan jenjang lanjutan yang diraih melalui kenaikan jenjang internal.</li><li><strong>B</strong>: Mahir merupakan jenjang menengah keterampilan yang tidak dialokasikan pada rekrutmen CPNS baru.</li><li><strong>C</strong>: Penyelia merupakan jenjang tertinggi pada JF Keterampilan yang dicapai melalui promosi.</li><li><strong>D</strong>: Penyelia bukan jenjang awal rekrutmen terbuka CPNS.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Keterampilan (yaitu Jabatan Fungsional Pemula dan Terampil).</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Secara kronologis berurutan, tujuh tahapan resmi penyelenggaraan pengadaan PNS terdiri atas...", "options": ["Pengumuman, pelamaran, seleksi administrasi, ujian dasar, percobaan, pelantikan, penempatan", "Perencanaan, pengumuman lowongan, pelamaran, seleksi, pengumuman hasil, percobaan, pengangkatan", "Pelamaran, seleksi kompetensi, penetapan NIP, pelatihan dasar, masa kerja, evaluasi, kenaikan pangkat", "Perencanaan formasi, verifikasi berkas, wawancara direktur, diklat dasar, pembekalan, pengangkatan tetap", "Pengumuman kuota, pendaftaran mandiri, ujian tertulis, orientasi unit, sumpah dinas, penempatan staf"], "answer": 1, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuh Tahapan Resmi Pengadaan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tahapan pengadaan PNS secara berurutan adalah: <strong>1. Perencanaan, 2. Pengumuman lowongan, 3. Pelamaran, 4. Seleksi, 5. Pengumuman hasil seleksi, 6. Masa percobaan, dan 7. Pengangkatan menjadi PNS</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B merupakan jawaban yang tepat. Berdasarkan ketentuan PP 11/2017 jo PP 17/2020 huruf e pada modul kepegawaian, proses pengadaan aparatur diatur dalam 7 rangkaian tahapan yang wajib dilalui secara berurutan, dimulai dari perencanaan kebutuhan formasi hingga penetapan pengangkatan resmi sebagai PNS setelah lulus masa percobaan.</p><p>Jawaban tepat: <strong>B. Perencanaan, pengumuman lowongan, pelamaran, seleksi, pengumuman hasil, percobaan, pengangkatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Melewatkan tahapan perencanaan di awal dan menggunakan penamaan tahapan yang tidak baku.</li><li><strong>C</strong>: Tidak mencantumkan tahapan perencanaan dan pengumuman serta mencampuradukkan manajemen karier.</li><li><strong>D</strong>: Memuat tahapan yang tidak ada dalam regulasi resmi seperti wawancara direktur.</li><li><strong>E</strong>: Bukan merupakan susunan tujuh tahapan baku pengadaan aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pengadaan PNS sebagaimana dimaksud dilakukan melalui tahapan • perencanaan, pengumuman lowongan, pelamaran, seleksi, pengumuman • hasil seleksi, masa percobaan, dan pengangkatan menjadi PNS.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Tiga tahapan ujian seleksi pengadaan PNS yang wajib diselenggarakan secara objektif terdiri atas...", "options": ["Uji kesamaptaan fisik, evaluasi portofolio kerja, dan asesmen psikologi", "Seleksi kualifikasi umum, uji pemahaman integritas, dan tes kesehatan", "Uji kemampuan bahasa, penilaian kepemimpinan dinas, dan wawancara akhir", "Seleksi administrasi, seleksi kompetensi dasar, dan kompetensi bidang", "Seleksi berkas registrasi, uji wawasan umum, dan verifikasi faktual staf"], "answer": 3, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tiga Tahapan Ujian Seleksi Pengadaan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyelenggaraan seleksi pengadaan PNS terdiri dari 3 tahap: <strong>seleksi administrasi</strong>, <strong>seleksi kompetensi dasar (SKD)</strong>, dan <strong>seleksi kompetensi bidang (SKB)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D merupakan jawaban yang tepat. Ketentuan Pasal pengadaan PNS pada modul menetapkan bahwa penyaringan calon pegawai dilakukan melalui tiga tahapan seleksi berjenjang, dimulai dari seleksi administrasi (pemeriksaan berkas dokumen), dilanjutkan dengan Seleksi Kompetensi Dasar (TWK, TIU, TKP), dan diakhiri dengan Seleksi Kompetensi Bidang sesuai keahlian teknis formasi.</p><p>Jawaban tepat: <strong>D. Seleksi administrasi, seleksi kompetensi dasar, dan kompetensi bidang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kesamaptaan fisik dan psikotes hanya merupakan bagian dari variasi tes SKB pada instansi tertentu.</li><li><strong>B</strong>: Tes kesehatan merupakan tahapan akhir pemberkasan atau bagian SKB, bukan tahapan seleksi pokok tripartit.</li><li><strong>C</strong>: Tes bahasa dan wawancara merupakan instrumen turunan pada tahapan SKB.</li><li><strong>E</strong>: Bukan nomenklatur resmi tiga tahapan seleksi baku aparatur sipil negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penyelenggaraan seleksi • sebagaimana dimaksud terdiri dari 3 (tiga) tahap, meliputi seleksi • administrasi, seleksi kompetensi dasar, dan seleksi kompetensi bidang.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Tujuan pokok diselenggarakannya tahap seleksi administrasi dalam pengadaan calon PNS adalah...", "options": ["Mencocokkan persyaratan administrasi dengan dokumen pelamaran pelamar", "Mengukur tingkat kecerdasan intelegensia umum serta logika pemikiran", "Menilai penguasaan substansi teknis keahlian pada formasi jabatan dituju", "Menguji ketahanan fisik dan kesiapan mental dalam menghadapi tugas dinas", "Menentukan besaran tunjangan kinerja awal yang akan diterima pegawai"], "answer": 0, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Pokok Tahap Seleksi Administrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Seleksi administrasi</strong> adalah untuk <strong>mencocokkan antara persyaratan administrasi dengan dokumen pelamaran yang disampaikan oleh pelamar</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A merupakan jawaban yang tepat. Berdasarkan rumusan resmi dalam modul kepegawaian, esensi tahap seleksi administrasi adalah melakukan verifikasi dan pencocokan keabsahan dokumen kualifikasi formal (seperti ijazah, transkrip, surat pernyataan, dan batas usia) yang diunggah pelamar terhadap persyaratan jabatan yang ditetapkan.</p><p>Jawaban tepat: <strong>A. Mencocokkan persyaratan administrasi dengan dokumen pelamaran pelamar</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mengukur kecerdasan dan logika merupakan fungsi Seleksi Kompetensi Dasar materi TIU.</li><li><strong>C</strong>: Menilai substansi teknis bidang keahlian merupakan fungsi Seleksi Kompetensi Bidang (SKB).</li><li><strong>D</strong>: Menguji fisik dan kesiapan mental merupakan fungsi tes kesamaptaan atau kesehatan.</li><li><strong>E</strong>: Besaran tunjangan kinerja telah diatur berdasarkan regulasi kelas jabatan dan bukan ditentukan seleksi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>1) Seleksi administrasi dalam tahap ini adalah untuk mencocokkan antara • persyaratan • administrasi • dengan • dokumen • pelamaran • yang • disampaikan oleh pelamar.</strong></div></div>"}, {"category": "TSKKWK", "sub": "Tata Aturan Kepegawaian • Paket 01", "difficulty": "C2 - Pemahaman", "question": "Penyelenggaraan seleksi pengadaan Pegawai Negeri Sipil wajib dilaksanakan melalui penilaian objektif yang berdasar pada...", "options": ["Tingkat senioritas usia serta pengalaman kerja dinas sebelumnya", "Daerah asal kelahiran pelamar serta rekomendasi instansi daerah", "Kompetensi, kualifikasi, dan persyaratan lain kebutuhan jabatan", "Hasil evaluasi kemampuan finansial dalam menempuh masa pelatihan", "Keselarasan latar belakang organisasi kemasyarakatan yang diikuti"], "answer": 2, "source": "Tata Aturan Kepegawaian", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penilaian Objektif Seleksi Pengadaan PNS</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penyelenggaraan seleksi pengadaan PNS harus dilakukan melalui <strong>penilaian secara objektif berdasarkan kompetensi, kualifikasi, dan persyaratan lain yang dibutuhkan oleh jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C merupakan jawaban yang tepat. Penutup materi pengadaan PNS menegaskan prinsip meritokrasi bahwa seleksi pengadaan aparatur diselenggarakan secara objektif, transparan, dan kompetitif. Kelulusan pelamar semata-mata diukur dari kompetensi dasar dan bidang, kualifikasi pendidikan, serta pemenuhan persyaratan jabatan tanpa membedakan latar belakang apa pun.</p><p>Jawaban tepat: <strong>C. Kompetensi, kualifikasi, dan persyaratan lain kebutuhan jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Seleksi CPNS terbuka bagi lulusan baru dan tidak mendasarkan kelulusan pada senioritas usia.</li><li><strong>B</strong>: Daerah asal dan rekomendasi pejabat dilarang menjadi pertimbangan kelulusan.</li><li><strong>D</strong>: Kemampuan finansial bukan indikator penilaian seleksi aparatur sipil negara.</li><li><strong>E</strong>: Afiliasi organisasi kemasyarakatan dilarang memengaruhi objektivitas seleksi merit.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Tata Aturan Kepegawaian (Hlm. 20) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>secara objektif berdasarkan kompetensi, kualifikasi, dan • persyaratan lain yang dibutuhkan oleh jabatan. Penyelenggaraan seleksi</strong></div></div>"}];
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
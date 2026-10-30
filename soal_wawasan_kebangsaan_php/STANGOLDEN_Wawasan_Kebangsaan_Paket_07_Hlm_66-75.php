<?php
/**
 * STANGOLDEN.exam • Wawasan Kebangsaan Paket 07 (Halaman 66–75)
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
// $DB->get_record("stgwk_p07_rank") => tabel fisik "mdlhv_stgwk_p07_rank".
// Jangan menambahkan prefix secara manual di konstanta berikut.
// Penyimpanan bersama melalui database Moodle. Nama tabel pendek, kompatibel dengan batas Moodle.
const STG_LB_T = 'stgwk_p07_rank';
const STG_REVIEW_T = 'stgwk_p07_rev';
const STG_ANNO_T = 'stgwk_p07_anno';
const STG_KEY = [0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3, 0, 1, 2, 3, 4, 0, 1, 2, 3, 4, 1, 2, 3, 4, 0, 1, 2, 3, 4, 0, 2, 3, 4, 0, 1, 2, 3, 4, 0, 1, 3, 4, 0, 1, 2, 3, 4, 0, 1, 2, 4, 0, 1, 2, 3, 4, 0, 1, 2, 3];
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
            error_log('STANGOLDEN WK P07 HLM66-75 SETUP: ' . $e->getMessage());
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
    }catch(Throwable $e){error_log('STANGOLDEN WK P07 HLM66-75: '.$e->getMessage());stgtak_json(['success'=>false,'message'=>($e instanceof RuntimeException ? $e->getMessage() : 'Penyimpanan belum tersedia. Hubungi administrator.')],500);}
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
<title>Latihan Wawasan Kebangsaan Paket 07 (Hlm. 66–75) • STANGOLDEN</title>
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
      <span class="series-tag">Paket 07 • Hlm. 66–75</span>
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
<h1>Latihan Nilai-Nilai Kemenkeu <span class="series-tag">Paket 07 • Hlm. 66–75</span></h1>
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
        <h2 class="leaderboard-title" id="rankingModalTitle">🏆 Lihat Ranking Wawasan Kebangsaan Paket 07</h2>
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
const STG_LOCAL_KEY="stg_wk_p07_review_user_<?= (int)$USER->id ?>";
const HAS_FULL_REVIEW=true;
const IS_SITE_ADMIN=<?= is_siteadmin($USER) ? "true" : "false" ?>;
const LEADERBOARD_ENDPOINT=window.location.href.split("?")[0].split("#")[0];
const questions=[{"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Sudut pandang yang memuat kemampuan seseorang atau kelompok dalam memahami jati diri bangsa dan bertingkah laku sesuai falsafah hidup disebut...", "options": ["Wawasan kebangsaan", "Wawasan nusantara", "Ketahanan nasional", "Kewaspadaan dini", "Diplomasi maritim"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengertian Wawasan Kebangsaan Berbasis Jati Diri</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Wawasan kebangsaan</strong> diartikan sebagai <strong>sudut pandang yang mengandung kemampuan memahami jati diri sebagai suatu bangsa dan bertingkah laku sesuai falsafah hidup bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena wawasan kebangsaan merupakan cara pandang mendalam mengenai pemahaman jati diri dan kepribadian bangsa dalam pergaulan internal maupun eksternal. Kesadaran jati diri ini mengarahkan setiap warga bertingkah laku selaras dengan nilai falsafah Pancasila. Konsep ini menjadi fondasi karakter moral warga negara.</p><p>Jawaban tepat: <strong>A. Wawasan kebangsaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Wawasan nusantara lebih berorientasi pada cara pandang geopolitik kewilayahan kesatuan laut dan kepulauan.</li><li><strong>C</strong>: Ketahanan nasional berfokus pada keuletan dan daya tangguh bangsa dalam menghadapi ancaman.</li><li><strong>D</strong>: Kewaspadaan dini berkaitan dengan kemampuan deteksi dini terhadap potensi bahaya keamanan.</li><li><strong>E</strong>: Diplomasi maritim merupakan strategi perundingan batas wilayah kelautan di tingkat internasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Wawasan kebangsaan dapat juga diartikan sebagai sudut pandang/ cara</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dalam kerangka Negara Kesatuan Republik Indonesia, wawasan kebangsaan berpedoman pada falsafah luhur...", "options": ["Deklarasi Hak Asasi dan Piagam PBB", "Pancasila dan konstitusi UUD 1945", "Hukum adat dan konvensi ketatanegaraan", "Doktrin pertahanan dan keamanan semesta", "Prinsip ekonomi pasar dan perdagangan bebas"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Wawasan Kebangsaan dalam Kerangka NKRI</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam kerangka NKRI, wawasan kebangsaan dilaksanakan dengan <strong>berpedoman pada falsafah Pancasila dan UUD 1945</strong> guna mewujudkan tujuan nasional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena falsafah Pancasila dan UUD 1945 berkedudukan sebagai pedoman fundamental hidup berbangsa dan bernegara di Indonesia. Seluruh rumusan cara pandang kebangsaan harus bersumber dan bermuara pada nilai-nilai konstitusi tersebut. Hal ini menjaga konsistensi arah pencapaian cita-cita kemerdekaan.</p><p>Jawaban tepat: <strong>B. Pancasila dan konstitusi UUD 1945</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Deklarasi Hak Asasi dan Piagam PBB merupakan instrumen hukum internasional, bukan falsafah pokok NKRI.</li><li><strong>C</strong>: Hukum adat dan konvensi ketatanegaraan merupakan sumber hukum pelengkap dalam tata hukum positif.</li><li><strong>D</strong>: Doktrin pertahanan semesta merupakan turunan teknis di bidang pertahanan keamanan negara.</li><li><strong>E</strong>: Prinsip ekonomi pasar bebas bertentangan dengan asas kekeluargaan ekonomi Pasal 33 UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dengan demikian, dalam kerangka NKRI, wawasan kebangsaan adalah cara kita</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Perwujudan Kepulauan Nusantara melalui wawasan kebangsaan dipahami sebagai satu kesatuan utuh yang meliputi bidang...", "options": ["Pertanian, perindustrian, perbankan, dan pariwisata", "Administrasi, perpajakan, kepabeanan, dan keimigrasian", "Politik, sosial budaya, ekonomi, dan hankam", "Kewilayahan, demografi, kehutanan, dan pertambangan", "Peradilan, pemasyarakatan, advokasi, dan legislasi"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dimensi Kesatuan Wawasan Nusantara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kepulauan Nusantara diwujudkan sebagai <strong>satu kesatuan politik, sosial budaya, ekonomi dan pertahanan keamanan (POLEKSOSBUD dan HANKAM)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena konsepsi Wawasan Nusantara mengintegrasikan seluruh dimensi kehidupan bernegara dalam satu kesatuan utuh POLEKSOSBUD dan HANKAM. Keterpaduan ini memastikan tidak ada keterpisahan nasib antardaerah dalam bingkai kepulauan Indonesia. Pendekatan terpadu ini menjadi ciri khas geopolitik nusantara.</p><p>Jawaban tepat: <strong>C. Politik, sosial budaya, ekonomi, dan hankam</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pertanian, industri, perbankan, dan pariwisata merupakan sektor teknis dalam kegiatan ekonomi sektoral.</li><li><strong>B</strong>: Administrasi, pajak, kepabeanan, dan imigrasi merupakan fungsi pelayanan teknis birokrasi kementerian.</li><li><strong>D</strong>: Kewilayahan, demografi, kehutanan, dan tambang merupakan unsur fisik geografis dan sumber daya alam.</li><li><strong>E</strong>: Peradilan, pemasyarakatan, advokasi, dan legislasi merupakan sub-bidang dalam penegakan hukum peradilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kesatuan politik, sosial budaya, ekonomi dan pertahanan keamanan, dengan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Bagi bangsa Indonesia, konsep kebangsaan memiliki kedudukan sangat mendasar karena telah dijadikan sebagai...", "options": ["Perjanjian pertahanan bersama negara sahabat", "Buku petunjuk teknis pelayanan perizinan terpadu", "Hukum acara tata usaha negara di peradilan", "Dasar negara dan ideologi nasional Pancasila", "Ketentuan baku tata cara diplomasi internasional"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Konsep Kebangsaan Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Konsep kebangsaan</strong> merupakan hal yang sangat mendasar karena <strong>telah dijadikan dasar negara dan ideologi nasional yang terumus di dalam Pancasila</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena konsep kebangsaan Indonesia telah diangkat menjadi fondasi filosofis dasar negara dan pandangan hidup resmi bangsa dalam Pancasila. Pengakuan ini termaktub secara agung dalam Pembukaan konstitusi negara. Hal ini menjadikan kebangsaan sebagai identitas mutlak setiap penyelenggaraan negara.</p><p>Jawaban tepat: <strong>D. Dasar negara dan ideologi nasional Pancasila</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perjanjian pertahanan merupakan kesepakatan militer bilateral di luar landasan ideologi dasar negara.</li><li><strong>B</strong>: Petunjuk teknis pelayanan perizinan merupakan aturan standar operasional di instansi teknis.</li><li><strong>C</strong>: Hukum acara tata usaha negara merupakan prosedur formal di pengadilan administrasi.</li><li><strong>E</strong>: Ketentuan diplomasi internasional merupakan instrumen hubungan luar negeri antar-pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Konsep kebangsaan merupakan hal yang sangat mendasar bagi bangsa Indonesia.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Unsur fundamental yang membedakan bangsa Indonesia secara khas dengan bangsa-bangsa lain di dunia adalah...", "options": ["Jumlah penduduk kepulauan di kawasan regional", "Sistem persenjataan modern armada angkatan laut", "Kekayaan sumber daya mineral di perut bumi", "Luas perairan yurisdiksi zona ekonomi eksklusif", "Konsep kebangsaan yang terumus dalam Pancasila"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Karakter Pembeda Bangsa Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Konsep kebangsaan</strong> yang terumus dalam <strong>Pancasila pada Alinea IV Pembukaan UUD 1945</strong> merupakan pembeda utama bangsa Indonesia dengan bangsa lain.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena konsep kebangsaan Indonesia lahir dari pengalaman persatuan dalam kemajemukan yang berlandaskan falsafah ketuhanan dan kemanusiaan adil beradab. Ciri khas ini berbeda dengan nasionalisme sempit berbasis etnisitas atau ras murni di negara lain. Nilai filosofis ini meneguhkan jati diri bangsa di mata dunia.</p><p>Jawaban tepat: <strong>E. Konsep kebangsaan yang terumus dalam Pancasila</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jumlah penduduk merupakan variabel demografis kuantitatif yang dinamis dan dapat dimiliki negara lain.</li><li><strong>B</strong>: Sistem persenjataan militer merupakan instrumen pertahanan fisik teknologi buatan manusia.</li><li><strong>C</strong>: Kekayaan sumber daya mineral adalah potensi fisik alam yang tersebar di berbagai belahan benua.</li><li><strong>D</strong>: Luas perairan zona ekonomi eksklusif merupakan data geografis maritim hasil konvensi hukum laut.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Alinea IV Pembukaan UUD 1945. Konsep kebangsaan itulah yang membedakan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dorongan historis yang melahirkan wawasan kebangsaan Indonesia bersumber dari tekad luhur untuk...", "options": ["Mewujudkan kemerdekaan dan memulihkan martabat manusia", "Menguasai jalur pelayaran rempah di kepulauan", "Membangun hegemoni kekuasaan di kawasan benua", "Menandingi kekuatan ekonomi kongsi dagang barat", "Memperluas batas wilayah koloni ke seberang laut"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sumber Dorongan Kelahiran Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dorongan yang melahirkan kebangsaan bersumber dari perjuangan untuk <strong>mewujudkan kemerdekaan dan memulihkan martabat kita sebagai manusia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kebangsaan Indonesia lahir bukan atas dasar agresi melainkan perlawanan moral terhadap penindasan kolonialisme yang merendahkan martabat kemanusiaan. Perjuangan kemerdekaan bertujuan mulia menegakkan hak asasi dan kedaulatan warga negara secara terhormat. Cita-cita kemanusiaan ini menjiwai seluruh gerakan kebangsaan.</p><p>Jawaban tepat: <strong>A. Mewujudkan kemerdekaan dan memulihkan martabat manusia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Menguasai jalur rempah merupakan motif monopoli ekonomi persekutuan dagang kolonial seperti VOC.</li><li><strong>C</strong>: Membangun hegemoni kekuasaan mencerminkan watak imperialisme yang ditentang oleh bangsa Indonesia.</li><li><strong>D</strong>: Menandingi kekuatan dagang adalah persaingan komersial kapitalistik masa kolonial.</li><li><strong>E</strong>: Memperluas koloni bertentangan dengan prinsip penolakan atas segala bentuk penjajahan di atas dunia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dorongan yang melahirkan kebangsaan kita bersumber dari perjuangan untuk</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C3 - Aplikasi", "question": "Kebijakan penerimaan aparatur sipil negara yang membuka kesempatan setara bagi seluruh putra-putri daerah tanpa membedakan latar belakang suku mencerminkan prinsip...", "options": ["Eksklusivisme kedaerahan aparatur", "Wawasan kebangsaan anti-diskriminasi", "Partikularisme kelembagaan dinas", "Sentralisasi birokrasi pemerintahan", "Primordialisme penempatan pegawai"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Anti-Diskriminasi Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kebangsaan Indonesia menentang segala <strong>diskriminasi suku, ras, asal-usul, keturunan, warna kulit, kedaerahan, golongan, agama, maupun status sosial</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena sistem merit dalam rekrutmen ASN yang adil dan terbuka bagi seluruh warga mencerminkan hakikat wawasan kebangsaan yang menentang diskriminasi. Semua warga negara dipandang memiliki kedudukan hukum dan kehormatan yang setara di hadapan negara. Hal ini memperkokoh integrasi sosial aparatur sipil.</p><p>Jawaban tepat: <strong>B. Wawasan kebangsaan anti-diskriminasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Eksklusivisme kedaerahan adalah sikap tertutup yang membatasi akses warga dari suku atau wilayah lain.</li><li><strong>C</strong>: Partikularisme mengutamakan kepentingan kelompok khusus di atas kepentingan umum warga bangsa.</li><li><strong>D</strong>: Sentralisasi birokrasi adalah pemusatan wewenang teknis pada pimpinan pusat pemerintahan.</li><li><strong>E</strong>: Primordialisme penempatan pegawai merupakan praktik mengistimewakan kerabat atau golongan sedaerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>warna kulit, kedaerahan, golongan, agama dan kepercayaan kepada Tuhan Yang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Secara mendasar dan konsisten, konsep kebangsaan Indonesia bertujuan untuk...", "options": ["Mempertajam persaingan antargolongan masyarakat", "Membentuk kelompok elite penguasa sumber daya", "Membangun dan mengembangkan persatuan dan kesatuan", "Menyeragamkan pandangan politik warga negara", "Mengurangi hak-hak sipil penduduk kepulauan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Konsep Kebangsaan Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Konsep kebangsaan Indonesia secara mendasar <strong>bertujuan membangun dan mengembangkan persatuan dan kesatuan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena tujuan hakiki dari konsepsi kebangsaan adalah merajut segenap kemajemukan bangsa menjadi satu simpul persatuan dan kesatuan yang kokoh. Persatuan ini menjadi modalitas utama dalam mempertahankan kemerdekaan dan melaksanakan pembangunan nasional. Arah persatuan ini membimbing segenap warga hidup berdampingan secara damai.</p><p>Jawaban tepat: <strong>C. Membangun dan mengembangkan persatuan dan kesatuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mempertajam persaingan golongan justru memicu disintegrasi sosial dan perpecahan bangsa.</li><li><strong>B</strong>: Membentuk kelompok elite melahirkan oligarki yang bertentangan dengan prinsip keadilan sosial.</li><li><strong>D</strong>: Menyeragamkan pandangan politik bertentangan dengan kebebasan berpendapat dalam sistem demokrasi.</li><li><strong>E</strong>: Mengurangi hak sipil bertentangan dengan jaminan perlindungan hak asasi manusia dalam UUD 1945.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Maha Esa, kedudukan maupun status sosial. Konsep kebangsaan kita bertujuan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Momentum sejarah pergerakan kebangsaan yang menjadi tonggak awal terjadinya proses Bhinneka Tunggal Ika adalah...", "options": ["Perang Diponegoro di Jawa Tengah", "Konferensi Meja Bundar di Den Haag", "Perjanjian Renville di kapal perang sekutu", "Kebangkitan Nasional 1908 oleh Budi Utomo", "Deklarasi Juanda tentang batas laut teritorial"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tonggak Bhinneka Tunggal Ika</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Zaman <strong>Kebangkitan Nasional 1908 yang dipelopori oleh Budi Utomo</strong> menjadi <strong>tonggak terjadinya proses Bhinneka Tunggal Ika</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena berdirinya organisasi Budi Utomo mendorong lahirnya aneka ragam pergerakan pemuda kedaerahan yang kemudian saling bersinergi dalam cita-cita nasional bersama. Dari keragaman latar belakang itulah proses kesadaran Bhinneka Tunggal Ika mulai bertunas secara terorganisasi. Peristiwa ini menjadi titik tolak integrasi nasional modern.</p><p>Jawaban tepat: <strong>D. Kebangkitan Nasional 1908 oleh Budi Utomo</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perang Diponegoro (1825-1830) merupakan perlawanan bersenjata tradisional sebelum era pergerakan modern.</li><li><strong>B</strong>: Konferensi Meja Bundar (1949) merupakan kesepakatan penyerahan kedaulatan dengan pihak Belanda.</li><li><strong>C</strong>: Perjanjian Renville (1948) merupakan perundingan gencatan senjata pada masa revolusi fisik kemerdekaan.</li><li><strong>E</strong>: Deklarasi Juanda (1957) merupakan pernyataan batas perairan kepulauan nusantara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dalam zaman Kebangkitan Nasional 1908 yang dipelopori oleh Budi Utomo menjadi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Peristiwa bersejarah yang secara sadar memadukan prinsip kebhinekaan dengan ketunggalikaan dalam ikrar persatuan kaum muda adalah...", "options": ["Sidang pertama BPUPK", "Kongres Perempuan Pertama di Mataram", "Rapat Panitia Persiapan Kemerdekaan", "Pertemuan Pemuda Menteng Tiga Satu", "Deklarasi Sumpah Pemuda"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perpaduan Kebhinekaan dan Ketunggalikaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan <strong>Sumpah Pemuda</strong>, gerakan Kebangkitan Nasional khususnya kaum pemuda <strong>berusaha memadukan kebhinekaan dengan ketunggalikaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena ikrar Sumpah Pemuda 1928 mengakui realitas kemajemukan suku dan tradisi daerah sekaligus menyatukannya ke dalam tekad tunggal satu tumpah darah, satu bangsa, dan satu bahasa. Harmonisasi ini membuktikan bahwa persatuan Indonesia tidak menghilangkan kekayaan adat lokal. Komitmen pemuda ini menjadi tonggak emas integrasi bangsa.</p><p>Jawaban tepat: <strong>E. Deklarasi Sumpah Pemuda</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sidang BPUPK berfokus pada perumusan dasar falsafah negara dan rancangan undang-undang dasar pada 1945.</li><li><strong>B</strong>: Kongres Perempuan Pertama di Yogyakarta (1928) memperjuangkan emansipasi dan pendidikan wanita pergerakan.</li><li><strong>C</strong>: Rapat PPKI berfokus pada penetapan UUD, presiden, wakil presiden, dan kementerian pada Agustus 1945.</li><li><strong>D</strong>: Pertemuan Menteng 31 mengorganisasikan desakan pemuda agar proklamasi dilakukan tanpa campur tangan Jepang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 66) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dengan Sumpah Pemuda, gerakan Kebangkitan Nasional, khususnya kaum pemuda</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Wawasan kebangsaan Indonesia menegaskan kedudukan setiap warga negara yang menjunjung asas kesetaraan dengan...", "options": ["Menetapkan hak istimewa bagi kelompok bangsawan", "Meniadakan perbedaan kelas warga mayoritas dan minoritas", "Membagi hierarki warga negara berdasarkan besaran pajak", "Mengutamakan warga ibu kota dalam seleksi pimpinan", "Membatasi hak suara warga kepulauan terluar"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Kesetaraan Derajat Warga Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kebangsaan Indonesia <strong>tidak mengenal adanya warga negara kelas satu, kelas dua, mayoritas, atau minoritas</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena prinsip kebangsaan Indonesia menepis segala stratifikasi kasta kependudukan, sehingga seluruh warga berdiri setara di depan hukum dan pemerintahan. Kedudukan setara ini menumbuhkan rasa persaudaraan sejati lintas etnis dan agama. Prinsip egalitarian ini menjadi fondasi tegaknya demokrasi Pancasila.</p><p>Jawaban tepat: <strong>B. Meniadakan perbedaan kelas warga mayoritas dan minoritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menetapkan hak istimewa bangsawan merupakan sistem feodalisme yang ditinggalkan pasca-proklamasi kemerdekaan.</li><li><strong>C</strong>: Membagi hierarki berdasarkan pajak mencerminkan sistem plutokrasi yang membedakan derajat manusia dari kekayaan materi.</li><li><strong>D</strong>: Mengutamakan warga ibu kota bertentangan dengan hak kesetaraan geografis warga negara di seluruh nusantara.</li><li><strong>E</strong>: Membatasi hak suara warga kepulauan bertentangan dengan prinsip kedaulatan rakyat universal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Wawasan kebangsaan Indonesia tidak mengenal adanya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Bukti historis bahwa wawasan kebangsaan Indonesia menepis dominasi mayoritas tampak pada kebijakan...", "options": ["Pengangkatan pejabat tinggi dari suku terbesar saja", "Penerapan hukum adat tertentu menjadi hukum nasional", "Penetapan bahasa Melayu sebagai akar bahasa persatuan", "Pemusatan seluruh lembaga pendidikan tinggi di Jawa", "Penggunaan simbol kedaerahan menjadi lambang negara"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bukti Nyata Kesetaraan Bahasa Persatuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesetaraan terbukti dengan <strong>tidak digunakannya bahasa mayoritas seperti bahasa Jawa, melainkan bahasa Melayu yang berkembang menjadi bahasa Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena para pendiri bangsa dan pemuda 1928 secara berjiwa besar memilih bahasa Melayu sebagai akar bahasa Indonesia demi mencegah hegemoni etnis mayoritas. Kebijakan ini mencerminkan keikhlasan kolektif dan kearifan luhur dalam membangun persatuan yang adil. Bahasa Indonesia menjadi lingua franca perekat kebangsaan.</p><p>Jawaban tepat: <strong>C. Penetapan bahasa Melayu sebagai akar bahasa persatuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengangkatan pejabat berbasis suku mayoritas merupakan praktik nepotisme etnosentrisme yang dilarang.</li><li><strong>B</strong>: Penerapan satu hukum adat tertentu secara seragam justru menimbulkan friksi di kalangan suku lain.</li><li><strong>D</strong>: Pemusatan perguruan tinggi di satu pulau memicu ketimpangan intelektual dan kecemburuan daerah.</li><li><strong>E</strong>: Penggunaan simbol daerah tertentu sebagai lambang negara mencederai asas keadilan simbol kenegaraan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>nasional tetapi justru bahasa melayu yang kemudian</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Derasnya pengaruh globalisasi berpotensi membawa dampak negatif terhadap ketahanan bangsa berupa ancaman...", "options": ["Peningkatan ekspor komoditas pangan olahan ke mancanegara", "Pertukaran tenaga pengajar dan pelajar berprestasi dunia", "Perluasan akses jaringan telekomunikasi hingga perdesaan", "Pergeseran adat budaya jati diri dan melemahnya nasionalisme", "Penguatan cadangan devisa hasil investasi modal bersama"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tantangan Pengaruh Globalisasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengaruh globalisasi dapat <strong>mengikis adat budaya yang menjadi jati diri bangsa serta melemahkan paham nasionalisme</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena gelombang budaya asing yang konsumtif dan individualistis dapat mengikis nilai-nilai luhur kearifan lokal bila tidak dibentengi wawasan kebangsaan yang kokoh. Pelemahan jati diri kultural berimplikasi langsung pada merosotnya rasa bangga dan loyalitas terhadap bangsa sendiri. Wawasan kebangsaan berfungsi sebagai penyaring nilai budaya luar.</p><p>Jawaban tepat: <strong>D. Pergeseran adat budaya jati diri dan melemahnya nasionalisme</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Peningkatan ekspor pangan merupakan dampak positif pengembangan perdagangan luar negeri.</li><li><strong>B</strong>: Pertukaran pelajar merupakan program peningkatan kapasitas intelektual generasi muda.</li><li><strong>C</strong>: Perluasan telekomunikasi merupakan keberhasilan pembangunan infrastruktur digital nasional.</li><li><strong>E</strong>: Penguatan cadangan devisa merupakan indikator stabilitas makroekonomi perbankan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Derasnya pengaruh globalisasi, bukan mustahil akan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Paham yang menyatakan bahwa loyalitas tertinggi terhadap masalah duniawi dari setiap warga ditujukan kepada negara dan bangsa adalah...", "options": ["Paham primordialisme", "Paham etnosentrisme", "Paham kosmopolitanisme", "Paham individualisme", "Paham nasionalisme"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Paham Nasionalisme</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Paham nasionalisme</strong> adalah <strong>suatu paham yang menyatakan bahwa loyalitas tertinggi terhadap masalah duniawi dari setiap warga bangsa ditujukan kepada negara dan bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena loyalitas utama dalam urusan kemasyarakatan dan tata kelola kehidupan duniawi diletakkan pada kepentingan eksistensi negara bangsa. Paham nasionalisme mengarahkan kesetiaan warga agar mengutamakan keselamatan umum di atas kepentingan golongan atau pribadi. Dedikasi ini menjadi pilar kelangsungan kedaulatan negara.</p><p>Jawaban tepat: <strong>E. Paham nasionalisme</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Primordialisme meletakkan loyalitas pada ikatan kesukuan, garis darah, atau tradisi kedaerahan awal.</li><li><strong>B</strong>: Etnosentrisme menilai kebudayaan suku sendiri sebagai pusat nilai yang lebih unggul dari suku lain.</li><li><strong>C</strong>: Kosmopolitanisme memandang seluruh umat manusia sebagai warga dunia tanpa terikat batas negara berdaulat.</li><li><strong>D</strong>: Individualisme menempatkan kebebasan dan kepentingan pribadi sebagai hak tertinggi di atas masyarakat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>nasionalisme. Paham nasionalisme adalah suatu paham</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dalam pergaulan internasional, wawasan kebangsaan membentuk karakter bangsa Indonesia yang...", "options": ["Tidak dapat mengisolasi diri dari pergaulan bangsa lain", "Menutup perbatasan secara mutlak dari dunia luar", "Memutuskan hubungan diplomatik dengan negara adidaya", "Menjauhi keanggotaan dalam forum organisasi dunia", "Membatasi pertukaran ilmu pengetahuan antarbangsa"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keterbukaan dalam Pergaulan Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kebangsaan menjadikan <strong>bangsa Indonesia tidak dapat mengisolasi diri dari bangsa lain</strong>, menjiwai pergaulan dunia dengan semangat terbuka.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Indonesia menyadari posisi strategis silang nusantara dalam percaturan peradaban dunia, sehingga mengedepankan kerja sama antarbangsa yang setara. Sikap inklusif ini selaras dengan mandat Pembukaan UUD 1945 untuk ikut melaksanakan ketertiban dunia berdasarkan kemerdekaan dan perdamaian abadi. Keterbukaan berimbang ini memperkuat martabat bangsa.</p><p>Jawaban tepat: <strong>A. Tidak dapat mengisolasi diri dari pergaulan bangsa lain</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Menutup perbatasan secara mutlak mencerminkan kebijakan isolasionisme kuno yang merugikan pembangunan.</li><li><strong>C</strong>: Memutuskan hubungan diplomatik merusak jalinan kerja sama dan kepentingan strategis nasional di luar negeri.</li><li><strong>D</strong>: Menjauhi forum dunia bertentangan dengan prinsip diplomasi multilateral yang aktif.</li><li><strong>E</strong>: Membatasi pertukaran ilmu menghambat kemajuan iptek dan daya saing sumber daya manusia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Wawasan kebangsaan Indonesia menjadikan bangsa yang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Semangat bangsa bahari yang terimplementasi dalam wawasan nusantara memandang wilayah laut Indonesia sebagai...", "options": ["Pemisah permanen antarpulau di kawasan kepulauan", "Bagian wilayah negara kepulauan yang diakui dunia", "Area perairan bebas tanpa kepemilikan kedaulatan", "Wilayah konsesi khusus bagi armada pelayaran asing", "Kawasan sengketa abadi dengan negara perbatasan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Semangat Bangsa Bahari dan Wilayah Laut</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Implementasi semangat bahari menegaskan <strong>wilayah laut Indonesia adalah bagian dari wilayah negara kepulauan yang diakui dunia</strong> sebagai perajut kedaulatan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena laut nusantara bukan pemisah daratan, melainkan jembatan air pemersatu ribuan pulau di bawah payung yurisdiksi negara kepulauan. Pengakuan internasional melalui konvensi hukum laut PBB (UNCLOS 1982) mengukuhkan kedaulatan maritim Indonesia secara utuh. Doktrin bahari ini menjaga keutuhan teritorial nasional.</p><p>Jawaban tepat: <strong>B. Bagian wilayah negara kepulauan yang diakui dunia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Laut sebagai pemisah merupakan konsep hukum kolonial Belanda (ordonansi 1939) yang melemahkan persatuan nusantara.</li><li><strong>C</strong>: Area bebas tanpa kedaulatan bertentangan dengan konsep perairan pedalaman dan kepulauan berdaulat.</li><li><strong>D</strong>: Wilayah konsesi khusus asing merugikan hak berdaulat atas sumber daya laut nasional.</li><li><strong>E</strong>: Kawasan sengketa abadi keliru karena batas maritim diselesaikan melalui perundingan hukum diplomatik resmi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>wawasan nusantara bahwa wilayah laut Indonesia adalah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Wawasan kebangsaan menyatakan bahwa negara Indonesia merupakan satu kesatuan utuh yang dipandang dari...", "options": ["Aspek kepemilikan modal dan saham perbankan", "Aspek kepangkatan aparatur militer dan sipil", "Semua aspek kehidupan sebagai pandangan hidup bangsa", "Aspek kekuatan armada industri pertambangan", "Aspek penguasaan teknologi digital multinasional"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Kesatuan Komprehensif Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Negara Indonesia merupakan <strong>satu kesatuan dipandang dari semua aspek sebagai pandangan hidup bangsa</strong> dalam mendayagunakan konstelasi lingkungan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena wawasan kebangsaan menuntut cara pandang terpadu yang memandang seluruh segi kehidupan berbangsa terjalin secara integral. Pandangan komprehensif ini membimbing bangsa mengelola potensi geografi, sejarah, dan kebudayaan demi mewujudkan aspirasi nasional bersama. Kesatuan multiperspektif ini mencerminkan falsafah kenegaraan Indonesia.</p><p>Jawaban tepat: <strong>C. Semua aspek kehidupan sebagai pandangan hidup bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kepemilikan modal perbankan merupakan instrumen moneter dalam sistem perbankan nasional.</li><li><strong>B</strong>: Kepangkatan aparatur berkaitan dengan manajemen hierarki kepegawaian institusi teknis.</li><li><strong>D</strong>: Kekuatan armada pertambangan berkaitan dengan kapasitas industri ekstraktif sumber daya alam.</li><li><strong>E</strong>: Teknologi digital multinasional merupakan produk kemajuan teknologi informasi global.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dipandang dari semua aspek sebagai pandangan hidup</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C4 - Analisis", "question": "Perwujudan aspirasi bangsa dan pencapaian tujuan nasional dalam wawasan kebangsaan mencakup empat kesatuan utama, KECUALI...", "options": ["Kesatuan politik bernegara", "Kesatuan sosial budaya rakyat", "Kesatuan pertahanan keamanan nasional", "Kesatuan kartel monopoli harga komoditas", "Kesatuan ekonomi perniagaan nusantara"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Cakupan Aspirasi Bangsa dan Tujuan Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan nasional mencakup <strong>kesatuan politik, kesatuan sosial budaya, kesatuan ekonomi, dan kesatuan pertahanan keamanan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat sebagai pengecualian karena kartel monopoli harga bertentangan dengan asas demokrasi ekonomi Pancasila dan merugikan daya beli rakyat. Empat pilar integrasi nasional yang dirumuskan adalah kesatuan politik, sosial budaya, ekonomi, dan hankam. Tujuan kebangsaan berorientasi pada pemerataan kemakmuran dan keadilan sosial bagi seluruh rakyat.</p><p>Jawaban tepat: <strong>D. Kesatuan kartel monopoli harga komoditas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kesatuan politik merupakan konsensus keutuhan negara di bawah naungan konstitusi yang sah.</li><li><strong>B</strong>: Kesatuan sosial budaya merupakan perekat harmoni pergaulan dalam semboyan Bhinneka Tunggal Ika.</li><li><strong>C</strong>: Kesatuan pertahanan keamanan merupakan keterpaduan sistem pertahanan keamanan rakyat semesta.</li><li><strong>E</strong>: Kesatuan ekonomi merupakan keterpaduan pasar domestik dan kemakmuran bersama nusantara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>nasional yang mencakup kesatuan politik, kesatuan sosial</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dalam penyelenggaraan pemerintahan daerah, wawasan kebangsaan Indonesia berkedudukan sebagai...", "options": ["Pedoman teknis penatausahaan barang milik daerah", "Format evaluasi penilaian kinerja bulanan dinas", "Rencana tata ruang kawasan industri terpadu", "Petunjuk teknis pengawasan anggaran belanja satker", "Sumber perumusan kebijakan desentralisasi dan otonomi"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Landasan Kebijakan Desentralisasi dan Otonomi Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kebangsaan menjadi <strong>sumber perumusan kebijakan desentralisasi pemerintahan dan pembangunan dalam rangka pengembangan otonomi daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kebijakan pemencaran urusan pemerintahan kepada daerah otonom harus berlandaskan kerangka wawasan kebangsaan. Landasan ini menjamin agar otonomi daerah memperkuat integrasi nasional dan bukan menciptakan fragmentasi kekuasaan. Hal ini menjaga keharmonisan tata kelola pemerintahan pusat dan daerah.</p><p>Jawaban tepat: <strong>E. Sumber perumusan kebijakan desentralisasi dan otonomi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penatausahaan barang milik daerah merupakan ranah teknis administrasi aset bendahara daerah.</li><li><strong>B</strong>: Format evaluasi kinerja bulanan merupakan instrumen pengukuran beban kerja pegawai teknis.</li><li><strong>C</strong>: Rencana tata ruang kawasan merupakan dokumen teknis perencanaan tata ruang wilayah fisik.</li><li><strong>D</strong>: Petunjuk pengawasan anggaran merupakan regulasi teknis aparat pengawasan intern pemerintah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Wawasan kebangsaan Indonesia yang menjadi sumber</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Pengembangan otonomi daerah yang berlandaskan wawasan kebangsaan harus mampu mengemban misi utama untuk...", "options": ["Mencegah disintegrasi dan menjaga wibawa pemerintah pusat", "Mendorong pembentukan milisi keamanan bersenjata daerah", "Menghambat koordinasi pembangunan dengan ibu kota negara", "Menetapkan aturan keimigrasian khusus antarprovinsi", "Mengalihkan kepemilikan aset nasional menjadi milik privat"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Wawasan Kebangsaan dalam Otonomi Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pengembangan otonomi daerah harus dapat <strong>mencegah disintegrasi/pemecahan negara kesatuan, mencegah merongrong wibawa pemerintah pusat, dan mencegah pertentangan pusat-daerah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena otonomi daerah didesain untuk mempercepat pelayanan publik dan kemakmuran daerah dalam batas keutuhan NKRI, bukan untuk memecah kedaulatan negara. Penjagaan wibawa pemerintah pusat dan pencegahan konflik antartingkat pemerintahan menjaga kestabilan nasional. Sinergi ini menjamin efektivitas pembangunan semesta.</p><p>Jawaban tepat: <strong>A. Mencegah disintegrasi dan menjaga wibawa pemerintah pusat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mendorong milisi bersenjata daerah bertentangan dengan monopoli pertahanan negara oleh TNI/Polri.</li><li><strong>C</strong>: Menghambat koordinasi bertentangan dengan prinsip sinergi penyelenggaraan pemerintahan yang efektif.</li><li><strong>D</strong>: Aturan imigrasi antarprovinsi bertentangan dengan hak kebebasan bergerak warga di wilayah kedaulatan NKRI.</li><li><strong>E</strong>: Mengalihkan aset nasional ke privat merugikan kekayaan perbendaharaan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 67) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kesatuan, mencegah merongrong wibawa pemerintah pusat,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Tata kelola desentralisasi yang berlandaskan wawasan kebangsaan bertujuan mewujudkan pemerintah pusat yang bersih serta pemerintah daerah yang...", "options": ["Mendirikan lembaga moneter independen di tiap provinsi", "Menetapkan mata uang tersendiri untuk transaksi lokal", "Tumbuh dan berkembang mandiri dengan daya saing sehat", "Membatasi arus distribusi sembako antarkabupaten", "Membentuk hubungan diplomatik mandiri ke luar negeri"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Harapan Tata Kelola Pusat dan Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Otonomi daerah diharapkan mewujudkan <strong>pemerintah pusat yang bersih dan akuntabel serta pemerintah daerah yang tumbuh dan berkembang secara mandiri dengan daya saing sehat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena desentralisasi yang sehat memicu kemandirian fiskal dan kreativitas daerah otonom dalam koridor kesatuan ekonomi nasional. Persaingan yang sehat antarwilayah mempercepat peningkatan kesejahteraan masyarakat lokal tanpa mencederai persatuan nasional. Keselarasan pusat dan daerah menjadi kunci kemakmuran bersama.</p><p>Jawaban tepat: <strong>C. Tumbuh dan berkembang mandiri dengan daya saing sehat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Lembaga moneter nasional dipegang secara terpusat oleh Bank Indonesia sesuai mandat konstitusi.</li><li><strong>B</strong>: Mata uang rupiah merupakan simbol kedaulatan tunggal yang berlaku di seluruh NKRI.</li><li><strong>D</strong>: Membatasi distribusi pangan bertentangan dengan prinsip kesatuan ekonomi kepulauan nusantara.</li><li><strong>E</strong>: Urusan hubungan luar negeri merupakan kewenangan mutlak pemerintah pusat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Melalui upaya tersebut diharapkan dapat terwujud pemerintah pusat yang bersih dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Terwujudnya kesatuan ekonomi, politik, dan budaya dalam kehidupan berbangsa menuntut adanya aparatur pemerintah yang menjunjung tinggi asas...", "options": ["Keberpihakan politik pada kelompok penguasa", "Prioritas pelayanan berbasis suku kesukuan", "Keterikatan emosional pada partai politik", "Netralitas birokrasi berwawasan kebangsaan", "Loyalitas sempit pada tokoh kedaerahan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prasyarat Terwujudnya Kesatuan Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kesatuan nasional menuntut <strong>warga bangsa yang kompak bersatu, netralitas birokrasi pemerintahan yang berwawasan kebangsaan, dan kader pembangunan yang terdidik</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena netralitas birokrasi menjamin pelayanan publik aparatur negara terselenggara secara adil, objektif, dan bebas dari diskriminasi politik atau kedaerahan. Birokrasi yang berwawasan kebangsaan menjadi perekat persatuan di tengah keberagaman masyarakat. Sikap profesional ini menjaga kepercayaan publik terhadap aparatur negara.</p><p>Jawaban tepat: <strong>D. Netralitas birokrasi berwawasan kebangsaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Keberpihakan politik merusak integritas dan profesionalitas aparatur sipil negara.</li><li><strong>B</strong>: Pelayanan berbasis kesukuan merupakan bentuk diskriminasi etnosentrisme yang mencederai keadilan sosial.</li><li><strong>C</strong>: Keterikatan pada partai politik bertentangan dengan undang-undang aparatur sipil negara tentang larangan berafiliasi politik.</li><li><strong>E</strong>: Loyalitas sempit kedaerahan memicu primordialisme yang merapuhkan keutuhan negara kesatuan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>warga bangsa yang kompak dan bersatu dengan ciri kebangsaan, netralitas birokrasi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dalam menghadapi perkembangan lingkungan strategis global, wawasan kebangsaan memberi peran bagi bangsa Indonesia untuk...", "options": ["Mengadakan perlombaan senjata rudal di kawasan", "Menyatakan sengketa terbuka dengan negara tetangga", "Menjalin aliansi militer ofensif lintas benua", "Mengadopsi seluruh kebudayaan populer barat", "Proaktif mengantisipasi dan memberi teladan peradaban"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran Proaktif Bangsa Indonesia</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kebangsaan memberi peran bagi Indonesia untuk <strong>proaktif mengantisipasi perkembangan lingkungan strategis dengan memberi contoh bagi bangsa lain tanpa konfrontasi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena bangsa Indonesia senantiasa bersikap aktif merespons dinamika global dengan mengedepankan keteladanan diplomasi damai dan pembangunan jati diri bangsa. Pendekatan non-konfrontatif ini memperkokoh kemandirian nasional sekaligus mempromosikan tata pergaulan dunia yang berkeadaban. Karakter proaktif ini selaras dengan prinsip bebas aktif.</p><p>Jawaban tepat: <strong>E. Proaktif mengantisipasi dan memberi teladan peradaban</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Perlombaan senjata rudal memicu ketegangan militer dan mengancam stabilitas kawasan dunia.</li><li><strong>B</strong>: Menyatakan sengketa terbuka merusak stabilitas perdamaian antarbangsa yang diperjuangkan Indonesia.</li><li><strong>C</strong>: Aliansi militer ofensif bertentangan dengan doktrin pertahanan negara yang bersifat defensif aktif.</li><li><strong>D</strong>: Mengadopsi budaya luar tanpa seleksi melunturkan nilai kepribadian dan kearifan lokal bangsa.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Wawasan kebangsaan Indonesia memberi peran bagi bangsa Indonesia untuk</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Wawasan kebangsaan meyakinkan dunia internasional bahwa keberadaan eksistensi bangsa Indonesia merupakan modal berharga dalam...", "options": ["Mengembangkan nilai kemanusiaan yang beradab", "Menguasai pasar bursa efek multinasional", "Mendirikan imperium kolonial baru di kepulauan", "Menentukan kurs mata uang tunggal kawasan", "Mengatur kuota ekspor minyak bumi dunia"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Eksistensi Bangsa sebagai Aset Peradaban</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Eksistensi bangsa Indonesia merupakan <strong>aset yang diperlukan dalam mengembangkan nilai kemanusiaan yang beradab</strong> di mata dunia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena keberhasilan Indonesia memadukan keragaman ratusan suku dalam bingkai persatuan damai menjadi teladan luhur bagi peradaban kemanusiaan dunia. Bukti empiris ini membuktikan bahwa nilai toleransi dan kemanusiaan yang beradab dapat tumbuh kokoh di tengah kemajemukan. Pengalaman historis ini menjadi sumbangan berharga bagi tata dunia.</p><p>Jawaban tepat: <strong>A. Mengembangkan nilai kemanusiaan yang beradab</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Menguasai bursa efek merupakan motif spekulasi finansial kapitalistik global.</li><li><strong>C</strong>: Mendirikan imperium kolonial bertentangan dengan prinsip penentangan kolonialisme.</li><li><strong>D</strong>: Menentukan kurs mata uang merupakan kebijakan teknis perbankan sentral internasional.</li><li><strong>E</strong>: Mengatur kuota minyak bumi adalah ranah organisasi eksportir minyak dunia (OPEC).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>merupakan aset yang diperlukan dalam mengembangkan nilai kemanusiaan yang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Pemahaman mendalam mengenai wawasan kebangsaan bersumber dari nilai dasar falsafah Pancasila yang bermuara pada...", "options": ["Dominasi mutlak kekuasaan eksekutif pusat", "Terbentuknya karakter luhur kepribadian bangsa", "Pembagian keuntungan saham korporasi negara", "Perluasan wilayah teritorial ke batas samudra", "Pemberlakuan jam malam di seluruh kota besar"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Falsafah Pancasila sebagai Muara Karakter Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Memahami wawasan kebangsaan perlu <strong>memahami secara mendalam falsafah Pancasila yang bermuara pada terbentuknya karakter bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena internalisasi nilai Pancasila membentuk watak, mentalitas, dan perilaku etis setiap insan Indonesia dalam kehidupan sehari-hari. Karakter bangsa yang berintegritas, adil, dan berorientasi persatuan menjadi modal utama ketahanan nasional. Muara etis ini memandu gerak langkah pembangunan nasional.</p><p>Jawaban tepat: <strong>B. Terbentuknya karakter luhur kepribadian bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dominasi eksekutif mencerminkan sistem otoriter yang bertentangan dengan prinsip pembagian kekuasaan konstitusi.</li><li><strong>C</strong>: Pembagian keuntungan korporasi merupakan urusan bisnis teknis deviden keuangan perseroan.</li><li><strong>D</strong>: Perluasan teritorial ekspansif bertentangan dengan penghormatan kedaulatan negara tetangga.</li><li><strong>E</strong>: Pemberlakuan jam malam merupakan instrumen darurat militer yang bersifat sementara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Akhirnya, bagi bangsa Indonesia, untuk memahami bagaimana wawasan kebangsaan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Makna pertama wawasan kebangsaan mengamanatkan kepada seluruh rakyat Indonesia agar senantiasa menempatkan kepentingan bangsa di atas...", "options": ["Hukum acara pidana dan perdata yang berlaku", "Kewajiban menjaga kelestarian ekosistem alam", "Kepentingan pribadi atau golongan tertentu", "Tanggung jawab pembinaan generasi keluarga", "Ketentuan baku perikatan perjanjian kerja"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Pertama Wawasan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Makna wawasan kebangsaan mengamanatkan agar <strong>menempatkan persatuan, kesatuan, serta kepentingan dan keselamatan bangsa dan negara di atas kepentingan pribadi atau golongan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kesediaan mendahulukan keselamatan umum dan kepentingan keutuhan bangsa merupakan esensi patriotisme sejati. Pengorbanan ego perseorangan maupun kelompok menjamin eksistensi negara tetap kokoh berdiri menghadapi cobaan zaman. Amanat luhur ini menjiwai pengabdian seluruh aparatur negara.</p><p>Jawaban tepat: <strong>C. Kepentingan pribadi atau golongan tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Hukum acara pidana tetap wajib ditegakkan sebagai bagian dari pilar negara hukum.</li><li><strong>B</strong>: Kelestarian lingkungan alam merupakan amanat pembangunan berkelanjutan yang wajib dirawat.</li><li><strong>D</strong>: Pembinaan keluarga adalah kewajiban asasi dalam membentuk sel terkecil masyarakat beradab.</li><li><strong>E</strong>: Perjanjian kerja harus dihormati sesuai dengan asas kepastian hukum keperdataan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menempatkan persatuan, kesatuan, serta kepentingan dan keselamatan bangsa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Pengembangan persatuan Indonesia dalam wawasan kebangsaan dilaksanakan sedemikian rupa dengan tujuan utama mempertahankan...", "options": ["Kekuasaan politik kelompok koalisi parlemen", "Sistem feodal kebangsawanan daerah istimewa", "Monopoli tata niaga perdagangan komoditas", "Asas keluhuran semboyan Bhinneka Tunggal Ika", "Hak imunitas pejabat tinggi dari pemeriksaan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Kedua Wawasan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kebangsaan <strong>mengembangkan persatuan Indonesia sedemikian rupa sehingga asas Bhinneka Tunggal Ika dipertahankan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena persatuan yang dibangun di Indonesia bukan persatuan yang melebur atau mematikan keragaman adat lokal, melainkan persatuan yang merayakan kebinekaan dalam satu wadah ketunggalikaan. Asas Bhinneka Tunggal Ika menjadi jiwa pemersatu yang menjamin kelestarian budaya nusantara. Pemeliharaan asas ini menjaga keaslian identitas bangsa.</p><p>Jawaban tepat: <strong>D. Asas keluhuran semboyan Bhinneka Tunggal Ika</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kekuasaan koalisi parlemen bersifat pragmatis politik dan dinamis berubah sesuai pemilu.</li><li><strong>B</strong>: Sistem feodal bertentangan dengan prinsip kesetaraan derajat manusia dalam republik merdeka.</li><li><strong>C</strong>: Monopoli tata niaga merusak keadilan ekonomi rakyat dan bertentangan dengan hukum persaingan usaha.</li><li><strong>E</strong>: Hak imunitas mutlak dari pemeriksaan mencederai asas kesetaraan di hadapan hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>2.Wawasan kebangsaan mengembangkan persatuan Indonesia sedemikian rupa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan makna substantif wawasan kebangsaan Indonesia, negara secara tegas tidak memberikan ruang bagi tumbuhnya...", "options": ["Semangat pengabdian tulus warga di perbatasan", "Solidaritas kemanusiaan dalam musibah bencana", "Partisipasi aktif pemuda dalam pembangunan", "Kecintaan mendalam terhadap warisan leluhur", "Patriotisme licik dan chauvinisme sempit"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Ketiga Wawasan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Makna wawasan kebangsaan menegaskan bahwa <strong>wawasan kebangsaan tidak memberi tempat pada patriotisme yang licik</strong> (chauvinisme sempit).</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena nasionalisme Indonesia berlandaskan kemanusiaan adil dan beradab yang menghormati harkat bangsa lain, bukan patriotisme manipulatif yang membenarkan kecurangan atau merendahkan martabat pihak lain. Cinta tanah air di Indonesia adalah cinta yang bermoral dan berkeadaban luhur. Penolakan atas patriotisme licik menjaga integritas moral bangsa.</p><p>Jawaban tepat: <strong>E. Patriotisme licik dan chauvinisme sempit</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengabdian tulus warga perbatasan merupakan teladan bela negara yang patut diapresiasi.</li><li><strong>B</strong>: Solidaritas bencana adalah perwujudan gotong royong dan kesetiakawanan sosial nasional.</li><li><strong>C</strong>: Partisipasi aktif pemuda adalah energi positif penggerak kemajuan peradaban bangsa.</li><li><strong>D</strong>: Kecintaan pada warisan leluhur memperkaya khazanah ketahanan budaya nusantara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>3.Wawasan kebangsaan tidak memberi tempat pada patriotisme yang licik;</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Berbekal wawasan kebangsaan berlandaskan pandangan hidup Pancasila, bangsa Indonesia berhasil merintis jalan dalam...", "options": ["Menjalani misinya di tengah kehidupan dunia", "Mendominasi kepemilikan modal perbankan dunia", "Membentuk pakta pertahanan militer eksklusif", "Menetapkan aturan perdagangan diskriminatif kawasan", "Menyaingi kekuatan persenjataan nuklir global"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Keempat Wawasan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dengan wawasan kebangsaan yang dilandasi Pancasila, bangsa Indonesia berhasil <strong>merintis jalan menjalani misinya di tengah-tengah tata kehidupan di dunia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Indonesia secara konsisten membuktikan kepeloporan diplomasi kemerdekaan dan perdamaian abadi seperti melalui Konferensi Asia Afrika dan Gerakan Non-Blok. Pandangan hidup Pancasila memandu kiprah internasional bangsa agar senantiasa berkontribusi bagi ketertiban dunia yang berkeadilan. Misi mulia ini mengukuhkan wibawa diplomatik negara.</p><p>Jawaban tepat: <strong>A. Menjalani misinya di tengah kehidupan dunia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mendominasi perbankan merupakan ambisi korporasi keuangan raksasa di negara kapitalis.</li><li><strong>C</strong>: Pakta pertahanan militer eksklusif bertentangan dengan politik luar negeri bebas dan aktif.</li><li><strong>D</strong>: Perdagangan diskriminatif bertentangan dengan prinsip kemitraan ekonomi multilateral yang saling menguntungkan.</li><li><strong>E</strong>: Persenjataan nuklir bertentangan dengan komitmen traktat pelarangan senjata pemusnah massal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>4.Dengan wawasan kebangsaan yang dilandasi oleh pandangan hidup Pancasila,</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Makna kelima wawasan kebangsaan menegaskan tekad seluruh elemen Negara Kesatuan Republik Indonesia untuk mewujudkan...", "options": ["Ketergantungan ekonomi permanen pada pinjaman luar", "Bangsa maju, mandiri, sejahtera lahir batin sejajar bangsa lain", "Pemusatan seluruh industri strategis di ibu kota negara", "Pembatasan keterlibatan putra daerah dalam sains global", "Penurunan anggaran riset dan teknologi kemandirian pangan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Kelima Wawasan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>NKRI yang merdeka, bersatu, berdaulat, adil, dan makmur <strong>bertekad mewujudkan bangsa yang maju dan mandiri serta sejahtera lahir batin, sejajar dengan bangsa lain yang sudah maju</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena cita-cita akhir perjuangan kebangsaan adalah mewujudkan kemakmuran lahiriah dan batiniah bagi segenap rakyat Indonesia secara berkeadilan. Kemandirian nasional dan kemajuan peradaban menempatkan bangsa Indonesia sejajar dengan negara maju di kancah global. Visi strategis ini menjadi arah tujuan pembangunan jangka panjang.</p><p>Jawaban tepat: <strong>B. Bangsa maju, mandiri, sejahtera lahir batin sejajar bangsa lain</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ketergantungan utang luar negeri merapuhkan kemandirian kedaulatan ekonomi negara.</li><li><strong>C</strong>: Pemusatan industri di satu kota memicu ketimpangan wilayah dan kesenjangan ekonomi daerah.</li><li><strong>D</strong>: Pembatasan akses sains melemahkan daya saing keilmuan sumber daya manusia nusantara.</li><li><strong>E</strong>: Penurunan anggaran riset menghambat kedaulatan pangan dan inovasi teknologi nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 68) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>5.NKRI yang merdeka, bersatu, berdaulat, adil, dan makmur bertekad untuk</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dimensi pertama yang mendasar dalam nilai wawasan kebangsaan Indonesia adalah...", "options": ["Pengembangan industri pertahanan rudal modern", "Penerapan sistem satu partai tunggal berkuasa", "Penguasaan cadangan emas moneter internasional", "Penghargaan harkat dan martabat manusia ciptaan Tuhan", "Pembatasan mobilitas warga antarpulau perbatasan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dimensi Pertama Nilai Dasar Wawasan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dimensi pertama nilai dasar wawasan kebangsaan adalah <strong>penghargaan terhadap harkat dan martabat manusia sebagai makhluk ciptaan Tuhan Yang Maha Esa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pengakuan atas martabat luhur kemanusiaan merupakan fondasi moral tertinggi yang melandasi seluruh peri kehidupan berbangsa di Indonesia. Nilai religius dan humanis ini menjamin penghormatan atas hak asasi dan persamaan derajat setiap insan. Landasan spiritual ini mencegah perlakuan sewenang-wenang dalam penyelenggaraan negara.</p><p>Jawaban tepat: <strong>D. Penghargaan harkat dan martabat manusia ciptaan Tuhan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pengembangan industri pertahanan rudal merupakan aspek teknis alat utama sistem persenjataan.</li><li><strong>B</strong>: Sistem satu partai tunggal bertentangan dengan asas demokrasi multipartai kedaulatan rakyat.</li><li><strong>C</strong>: Penguasaan cadangan emas adalah instrumen cadangan devisa perbankan sentral.</li><li><strong>E</strong>: Pembatasan mobilitas antarpulau bertentangan dengan kebebasan bergerak warga di wilayah kedaulatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>1.Penghargaan terhadap harkat dan martabat manusia sebagai makhluk ciptaan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Unsur yang menjadi penegasan dalam dimensi kedua nilai dasar wawasan kebangsaan adalah...", "options": ["Kewajiban mengikuti wajib militer bagi seluruh pemuda", "Penghapusan status kepegawaian aparatur sipil daerah", "Pemberian hak istimewa dagang bagi investor asing", "Penyeragaman pakaian adat di seluruh wilayah dinas", "Tekad bersama berkehidupan bebas, merdeka, dan bersatu"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dimensi Kedua Nilai Dasar Wawasan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dimensi kedua nilai dasar wawasan kebangsaan adalah <strong>tekad bersama untuk berkehidupan kebangsaan yang bebas, merdeka, dan bersatu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kemerdekaan dan kebebasan dari belenggu penjajahan merupakan kehendak bersama seluruh anak bangsa yang dipersatukan oleh cita-cita kedaulatan. Tekad kolektif ini mendorong segenap suku bangsa rela berkorban merebut dan menjaga kemerdekaan Indonesia. Semangat persatuan ini menjadi energi abadi pembangunan bangsa.</p><p>Jawaban tepat: <strong>E. Tekad bersama berkehidupan bebas, merdeka, dan bersatu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Wajib militer merupakan kebijakan teknis pertahanan pada situasi kedaruratan tertentu.</li><li><strong>B</strong>: Penghapusan status kepegawaian daerah bertentangan dengan undang-undang ASN terpadu.</li><li><strong>C</strong>: Hak istimewa dagang asing bertentangan dengan kedaulatan ekonomi kerakyatan nasional.</li><li><strong>D</strong>: Penyeragaman pakaian adat mencederai kekayaan keberagaman budaya lokal nusantara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>2.Tekad bersama untuk berkehidupan kebangsaan yang bebas, merdeka, dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dimensi ketiga dan keempat dalam enam nilai dasar wawasan kebangsaan mencakup perpaduan antara...", "options": ["Cinta tanah air bangsa serta demokrasi kedaulatan rakyat", "Persaingan usaha bebas dan privatisasi sektor tambang", "Pemusatan anggaran belanja dan penurunan tarif pajak", "Pembatasan ekspor komoditas dan pemutusan jalur laut", "Dominasi golongan mayoritas dan pengurangan hak minoritas"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dimensi Ketiga dan Keempat Nilai Dasar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dimensi ketiga adalah <strong>cinta akan tanah air dan bangsa</strong>, serta dimensi keempat adalah <strong>demokrasi atau kedaulatan rakyat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena rasa cinta tanah air menumbuhkan jiwa patriotisme yang kemudian disalurkan melalui mekanisme demokrasi kedaulatan rakyat yang bermartabat. Rakyat berkedudukan sebagai pemegang mandat kekuasaan tertinggi yang menjalankan pemerintahan dari, oleh, dan untuk rakyat berlandaskan Pancasila. Dua pilar ini menjaga kedaulatan negara.</p><p>Jawaban tepat: <strong>A. Cinta tanah air bangsa serta demokrasi kedaulatan rakyat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Persaingan usaha bebas dan privatisasi tambang merupakan doktrin ekonomi pasar liberal.</li><li><strong>C</strong>: Pemusatan anggaran dan penurunan tarif adalah kebijakan teknis pengelolaan fiskal negara.</li><li><strong>D</strong>: Pembatasan ekspor dan pemutusan jalur laut merugikan perdagangan maritim nasional.</li><li><strong>E</strong>: Dominasi mayoritas dan penindasan minoritas bertentangan dengan prinsip kesetaraan wawasan kebangsaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>3.Cinta akan tanah air dan bangsa;</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C3 - Aplikasi", "question": "Penyelenggaraan program perlindungan sosial bagi warga miskin dan korban bencana alam mencerminkan perwujudan dimensi nilai dasar...", "options": ["Sentralisme birokrasi kepegawaian", "Kesetiakawanan sosial dan masyarakat adil makmur", "Eksploitasi sumber daya produktif daerah", "Pemusatan kekuasaan yudikatif pemerintahan", "Primordialisme bantuan sosial kemasyarakatan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dimensi Kelima dan Keenam Nilai Dasar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dimensi kelima dan keenam nilai dasar wawasan kebangsaan adalah <strong>kesetiakawanan sosial dan masyarakat adil-makmur</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena bantuan perlindungan sosial merupakan wujud nyata gotong royong dan kesetiakawanan sosial demi mencapai kemakmuran bersama yang adil. Nilai empati kebangsaan ini memastikan tidak ada warga yang tertinggal dalam mengakses pemenuhan kebutuhan dasar hidup. Pengamalan ini mewujudkan amanat sila kelima Pancasila.</p><p>Jawaban tepat: <strong>B. Kesetiakawanan sosial dan masyarakat adil makmur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sentralisme birokrasi mengatur hierarki perintah dan rantai komando administratif dinas.</li><li><strong>C</strong>: Eksploitasi sumber daya bertentangan dengan asas kelestarian dan keadilan pemanfaatan alam.</li><li><strong>D</strong>: Pemusatan kekuasaan yudikatif merusak kemandirian dan kebebasan peradilan hukum.</li><li><strong>E</strong>: Primordialisme bantuan sosial menyalurkan bantuan secara diskriminatif berdasarkan kesukuan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>5.Kesetiakawanan sosial; dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C4 - Analisis", "question": "Nilai wawasan kebangsaan yang terwujud dalam persatuan dan kesatuan memiliki enam dimensi fundamental, KECUALI...", "options": ["Penghargaan terhadap harkat dan martabat manusia", "Tekad bersama berkehidupan bebas dan bersatu", "Pemisahan kedaulatan kepulauan menjadi negara bagian", "Kesetiakawanan sosial dan masyarakat adil-makmur", "Cinta akan tanah air serta kedaulatan rakyat"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Enam Dimensi Nilai Dasar Wawasan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Enam dimensi nilai wawasan kebangsaan mencakup: <strong>martabat manusia, tekad bebas bersatu, cinta tanah air, kedaulatan rakyat, kesetiakawanan sosial, dan masyarakat adil-makmur</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat sebagai pengecualian karena pemisahan wilayah menjadi negara bagian bertentangan dengan komitmen bentuk final Negara Kesatuan Republik Indonesia. Enam dimensi nilai wawasan kebangsaan seluruhnya berorientasi pada persatuan, keadilan, dan kemanusiaan luhur. Gagasan disintegrasi federal bertentangan dengan konsensus kebangsaan.</p><p>Jawaban tepat: <strong>C. Pemisahan kedaulatan kepulauan menjadi negara bagian</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penghargaan harkat martabat manusia merupakan dimensi nilai fundamental pertama.</li><li><strong>B</strong>: Tekad bersama berkehidupan bebas dan bersatu merupakan dimensi nilai kedua.</li><li><strong>D</strong>: Kesetiakawanan sosial dan masyarakat adil-makmur merupakan dimensi nilai kelima dan keenam.</li><li><strong>E</strong>: Cinta tanah air dan kedaulatan rakyat merupakan dimensi nilai ketiga dan keempat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>memiliki enam dimensi yang bersifat mendasar dan fundamental, yaitu:</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Bentuk negara yang disepakati oleh para pendiri bangsa pada proklamasi 17 Agustus 1945 dengan pemikiran arif adalah...", "options": ["Negara monarki konstitusional kepulauan", "Negara persemakmuran di bawah protektorat", "Negara serikat dengan parlemen bikameral", "Negara kesatuan yang berbentuk Republik", "Negara konfederasi provinsi otonom"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Bentuk Negara dan Kearifan Pendiri Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Negara Indonesia yang diproklamasikan 17 Agustus 1945 adalah <strong>negara kesatuan yang berbentuk Republik</strong> dengan dasar persatuan dan kesatuan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena bentuk negara kesatuan bersusunan republik merupakan konsensus murni para pendiri bangsa yang ditetapkan dalam Pasal 1 ayat (1) UUD 1945. Keputusan arif ini menjamin kesetaraan seluruh warga di bawah hukum dan kepemimpinan yang dipilih secara demokratis. Pilihan bentuk negara ini menjadi jangkar keutuhan Indonesia.</p><p>Jawaban tepat: <strong>D. Negara kesatuan yang berbentuk Republik</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Monarki konstitusional mempertahankan tahta keturunan raja sebagai kepala negara formal.</li><li><strong>B</strong>: Negara persemakmuran berada di bawah naungan ikatan bekas jajahan suatu imperium asing.</li><li><strong>C</strong>: Negara serikat federal pernah dicoba pada era RIS 1949 dan terbukti memicu perpecahan bangsa.</li><li><strong>E</strong>: Konfederasi merupakan gabungan negara berdaulat yang masing-masing memegang kedaulatan penuh.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Negara Indonesia yang diproklamasikan pada tanggal 17 Agustus 1945 adalah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Kondisi objektif bangsa Indonesia yang berpenduduk besar dan majemuk memiliki karakteristik dua sisi berupa...", "options": ["Potensi inflasi tinggi dan devaluasi nilai tukar", "Kepadatan demografi dan penurunan usia harapan", "Peningkatan belanja modal dan penurunan ekspor", "Pemusatan kekuasaan dan pelemahan kedaulatan", "Potensi kekuatan dan potensi kelemahan bangsa"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dua Sisi Kondisi Objektif Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kondisi objektif kemajemukan penduduk Indonesia <strong>pada satu sisi mengandung kekuatan tetapi pada sisi lain mengandung kelemahan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena keanekaragaman suku dan budaya dapat menjadi sumber kekayaan daya cipta dan solidaritas sosial bila dikelola dengan harmoni. Sebaliknya, kemajemukan yang sama dapat memicu kerentanan disintegrasi apabila dirasuki prasangka buruk dan fanatisme sempit. Karakteristik dwitunggal ini menuntut pengelolaan wawasan kebangsaan yang bijak.</p><p>Jawaban tepat: <strong>E. Potensi kekuatan dan potensi kelemahan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Inflasi dan devaluasi merupakan variabel ketidakseimbangan makroekonomi perbankan.</li><li><strong>B</strong>: Kepadatan demografi dan usia harapan merupakan data statistik kesehatan populasi.</li><li><strong>C</strong>: Belanja modal dan ekspor merupakan neraca anggaran dan perdagangan luar negeri.</li><li><strong>D</strong>: Pemusatan kekuasaan adalah masalah tata struktur kelembagaan otoriter.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Negara Indonesia yang dikelola dengan jumlah penduduk yang cukup besar, yang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Keanekaragaman suku, bahasa, dan adat istiadat di Indonesia bertransformasi menjadi modal kekuatan bangsa manakala...", "options": ["Perbedaan keanekaragaman hidup bersama dalam harmoni", "Setiap daerah menutup diri dari interaksi luar", "Kelompok mayoritas mendominasi kepengurusan satker", "Bahasa daerah tertentu dijadikan bahasa nasional", "Diberlakukan batas tarif masuk barang antarpulau"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Syarat Kemajemukan Menjadi Kekuatan Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kemajemukan <strong>mengandung kekuatan bila perbedaan dari keanekaragaman dapat hidup bersama dalam satu kesatuan yang harmonis</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena harmoni sosial dan sikap saling menghormati keragaman menyatukan berbagai potensi kearifan lokal menjadi sinergi pembangunan raksasa. Kehidupan yang rukun menepis benih permusuhan serta menciptakan rasa aman dan stabilitas nasional yang kokoh. Harmonisasi ini merealisasikan semboyan Bhinneka Tunggal Ika.</p><p>Jawaban tepat: <strong>A. Perbedaan keanekaragaman hidup bersama dalam harmoni</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Menutup diri melahirkan sikap isolasionis dan kecurigaan primordial antardaerah.</li><li><strong>C</strong>: Dominasi mayoritas memicu rasa ketidakadilan dan ketidakpuasan di kalangan warga lain.</li><li><strong>D</strong>: Menjadikan bahasa suku tertentu sebagai bahasa nasional merusak konsensus bahasa Indonesia.</li><li><strong>E</strong>: Tarif masuk antarpulau merusak keutuhan sistem logistik dan pasar ekonomi nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengandung kekuatan bila perbedaan dari keanekaragaman dapat hidup bersama</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Karakteristik sistem pemerintahan yang ingin dikembangkan dalam kerangka negara kesatuan yang utuh adalah...", "options": ["Sistem monarki absolut dan sentralistik", "Sistem demokratis dan desentralistis", "Sistem parlementer kabinet terpisah", "Sistem federasi dengan mahkamah serikat", "Sistem konfederasi provinsi kepulauan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sistem Kenegaraan yang Ingin Dikembangkan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sistem pemerintahan yang dikembangkan adalah <strong>sistem pemerintahan yang bersifat demokratis dan desentralistis dalam negara kesatuan yang utuh dan menyeluruh</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena perpaduan prinsip demokrasi dan desentralisasi memberikan ruang partisipasi luas bagi rakyat daerah untuk mengelola potensinya secara mandiri. Pada saat yang sama, ikatan negara kesatuan menjamin agar otonomi tidak melenceng menjadi pemisahan diri atau disintegrasi nasional. Kombinasi ini menghadirkan keadilan sekaligus stabilitas bernegara.</p><p>Jawaban tepat: <strong>B. Sistem demokratis dan desentralistis</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Monarki absolut dan sentralistik meniadakan hak partisipasi rakyat dan otonomi daerah.</li><li><strong>C</strong>: Sistem parlementer kabinet terpisah rentan memicu instabilitas jatuh-bangun kabinet koalisi.</li><li><strong>D</strong>: Sistem federasi serikat bertentangan dengan bentuk final negara kesatuan Pasal 1 ayat (1) UUD 1945.</li><li><strong>E</strong>: Konfederasi melemahkan ikatan kebangsaan dan kedaulatan yurisdiksi nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sistem kenegaraan dan sistem pemerintahan yang ingin dikembangkan adalah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Perimbangan tata kelola dalam wawasan kebangsaan menuntut pemerintah pusat yang kuat dan berwibawa guna menjamin...", "options": ["Pembatasan hak berpendapat warga kepulauan", "Pemusatan seluruh pendapatan retribusi pariwisata", "Terpeliharanya stabilitas nasional dan kesatuan bangsa", "Penghentian penyelenggaraan pemilihan kepala daerah", "Penghapusan bahasa daerah dari percakapan warga"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keseimbangan Peran Pusat dan Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemerintah pusat yang berwibawa <strong>menjamin terpeliharanya stabilitas nasional dan kesatuan bangsa</strong>, sedangkan desentralisasi menghendaki daerah mandiri dan demokratis.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kewibawaan pemerintah pusat diperlukan untuk menegakkan hukum konstitusi, menjaga keutuhan batas wilayah, dan menjamin kepastian berusaha di seluruh pelosok negeri. Sementara itu, pemerintah daerah yang mandiri mempercepat penyediaan layanan kesejahteraan publik yang kontekstual. Hubungan harmonis ini menjaga kesinambungan kedaulatan negara.</p><p>Jawaban tepat: <strong>C. Terpeliharanya stabilitas nasional dan kesatuan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembatasan hak berpendapat bertentangan dengan jaminan kemerdekaan berserikat dan berkumpul Pasal 28 UUD 1945.</li><li><strong>B</strong>: Pemusatan retribusi lokal bertentangan dengan hak perolehan pendapatan asli daerah otonom.</li><li><strong>D</strong>: Penghentian pilkada mencederai prinsip demokrasi lokal yang dijamin oleh konstitusi.</li><li><strong>E</strong>: Penghapusan bahasa daerah bertentangan dengan amanat Pasal 32 ayat (2) UUD 1945 tentang pelestarian bahasa daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 69) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kesatuan yang utuh dan menyeluruh yang menghendaki adanya pemerintahan pusat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Harmonisasi hubungan antara pemerintah pusat dan pemerintah daerah dalam wawasan kebangsaan menuntut pemahaman terhadap...", "options": ["Pemisahan permanen yurisdiksi batas kepulauan", "Pemberian hak veto bagi kepala daerah otonom", "Sentralisasi mutlak seluruh perizinan investasi usaha", "Penghapusan kebudayaan tradisional di daerah terpencil", "Keberadaan wawasan kewilayahan dengan ciri khas tertentu"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Harmonisasi Hubungan Pusat dan Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Harmonisasi pusat dan daerah menuntut wawasan kebangsaan yang <strong>memahami keberadaan wawasan kewilayahan/kedaerahan yang memiliki karakteristik tertentu</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena setiap daerah di nusantara memiliki keunikan geografis, sosio-kultural, dan potensi ekonomi yang berbeda. Pengakuan terhadap karakteristik lokal ini menjadi fondasi dalam merumuskan kebijakan desentralisasi yang selaras dengan kepentingan nasional. Pemahaman ini memperkuat ikatan persatuan antara pusat dan daerah.</p><p>Jawaban tepat: <strong>E. Keberadaan wawasan kewilayahan dengan ciri khas tertentu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemisahan yurisdiksi kepulauan mengarah pada disintegrasi yang merusak keutuhan NKRI.</li><li><strong>B</strong>: Hak veto bagi kepala daerah tidak dikenal dalam sistem ketatanegaraan negara kesatuan.</li><li><strong>C</strong>: Sentralisasi mutlak mematikan inisiatif dan kemandirian pemerintah daerah otonom.</li><li><strong>D</strong>: Penghapusan kebudayaan tradisional bertentangan dengan amanat konstitusi pemajuan kebudayaan daerah.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dengan harmonisasi hubungan pusat dan daerah menuntut adanya</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Karakteristik wawasan kewilayahan atau kedaerahan hendaknya dikembangkan oleh pemerintah daerah dengan sikap...", "options": ["Penuh prakarsa, kreasi, dewasa, dan mandiri", "Pasrah menunggu petunjuk teknis tanpa inovasi", "Eksklusif dan menutup kerja sama antardaerah", "Ketergantungan permanen pada subsidi fiskal pusat", "Mengutamakan kepentingan kelompok keluarga birokrat"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengembangan Wawasan Kedaerahan yang Sehat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kedaerahan yang memiliki karakteristik tertentu dikembangkan dengan <strong>penuh prakarsa, kreasi, dewasa dan mandiri</strong> dalam wadah kesatuan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena kemajuan daerah otonom membutuhkan kreativitas dan kepemimpinan lokal yang proaktif dalam menggali potensi wilayah demi kemaslahatan warganya. Kedewasaan sikap ini menumbuhkan kemandirian pembangunan tanpa melepaskan diri dari komitmen kebangsaan. Inisiatif positif ini mempercepat laju pertumbuhan ekonomi daerah.</p><p>Jawaban tepat: <strong>A. Penuh prakarsa, kreasi, dewasa, dan mandiri</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sikap pasrah tanpa kreasi menghambat akselerasi pelayanan publik dan daya saing daerah.</li><li><strong>C</strong>: Sikap eksklusif menciptakan ego kedaerahan yang mengancam integrasi ekonomi nusantara.</li><li><strong>D</strong>: Ketergantungan permanen melemahkan ketahanan fiskal dan kemandirian keuangan daerah.</li><li><strong>E</strong>: Mengutamakan keluarga birokrat merupakan bentuk nepotisme yang merusak profesionalisme tata kelola.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kewilayahan/ kedaerahan yang memiliki karakteristik tertentu untuk</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Wawasan kewilayahan atau kedaerahan yang semakin dewasa dan mandiri hendaknya senantiasa ditempatkan secara proporsional untuk...", "options": ["Menuntut hak pemisahan teritorial dari negara", "Memperkuat pembinaan wawasan kebangsaan", "Membentuk mata rantai pasar komoditas tertutup", "Menghapus kurikulum bahasa Indonesia di sekolah", "Mengurangi alokasi belanja infrastruktur desa"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Proporsional Wawasan Kedaerahan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kewilayahan/kedaerahan yang mandiri hendaknya <strong>senantiasa ditempatkan secara proporsional untuk memperkuat pembinaan wawasan kebangsaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena keberhasilan dan kemajuan suatu daerah harus bermuara pada penguatan keutuhan dan kejayaan bangsa Indonesia secara keseluruhan. Wawasan kedaerahan tidak boleh berkembang menjadi fanatisme sempit yang mengisolasi diri dari nasib wilayah lain. Penempatan proporsional ini menjaga keseimbangan geopolitik nusantara.</p><p>Jawaban tepat: <strong>B. Memperkuat pembinaan wawasan kebangsaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menuntut pemisahan teritorial merupakan tindakan makar dan bertentangan dengan Pasal 37 ayat (5) UUD 1945.</li><li><strong>C</strong>: Pasar tertutup merusak konektivitas perdagangan antarpulau dan memperlambat arus barang nasional.</li><li><strong>D</strong>: Menghapus kurikulum bahasa Indonesia bertentangan dengan fungsi bahasa persatuan sebagai sarana integrasi.</li><li><strong>E</strong>: Mengurangi belanja infrastruktur desa bertentangan dengan komitmen pemerataan pembangunan berkeadilan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>proporsional untuk memperkuat pembinaan wawasan kebangsaan.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Pemberian ruang dan kesempatan berkembang bagi wawasan kedaerahan pada hakikatnya dilandasi oleh kesadaran bahwa...", "options": ["Pemerintah pusat tidak sanggup mengurus daerah terluar", "Daerah kepulauan memiliki hak mendirikan peradilan terpisah", "Kemajemukan wilayah dapat menjadi kekuatan pemersatu bangsa", "Kekayaan sumber daya alam sepenuhnya milik penduduk asli setempat", "Penyelenggaraan pertahanan negara diserahkan kepada suku lokal"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Pemberian Ruang bagi Daerah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pemberian ruang bagi daerah menyadari bahwa <strong>wilayah luas dan kemajemukan suku, bahasa, adat istiadat justru dapat dimanfaatkan sebagai kekuatan mempersatukan bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena keanekaragaman sosial budaya Indonesia dipandang sebagai modal sosial strategis yang bila dirangkul secara inklusif akan memperkaya daya lentur bangsa. Keterbukaan terhadap aspirasi daerah memupuk rasa kepemilikan bersama atas rumah besar Indonesia. Kesadaran ini membuktikan bahwa keragaman adalah kekuatan pemersatu.</p><p>Jawaban tepat: <strong>C. Kemajemukan wilayah dapat menjadi kekuatan pemersatu bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemerintah pusat tetap hadir dan bertanggung jawab penuh atas keutuhan pulau-pulau terdepan.</li><li><strong>B</strong>: Kekuasaan kehakiman berada dalam satu sistem peradilan nasional di bawah Mahkamah Agung.</li><li><strong>D</strong>: Bumi dan air dikuasai oleh negara dan dipergunakan untuk sebesar-besar kemakmuran seluruh rakyat.</li><li><strong>E</strong>: Pertahanan negara merupakan domain TNI dan kewajiban bela negara terpadu segenap warga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Wawasan kebangsaan yang memberikan ruang dan kesempatan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Secara historis perkembangan pemikiran peradaban dunia, paham kebangsaan atau nasionalisme modern lahir pada rentang...", "options": ["Awal abad ke-14 masa penjelajahan samudra", "Awal abad ke-12 masa perang salib", "Pertengahan abad ke-16 era renaisans seni", "Akhir abad ke-18 atau permulaan abad ke-19", "Penghujung abad ke-20 pasca-perang dingin"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sejarah Lahirnya Paham Nasionalisme Modern</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Paham kebangsaan/nasionalisme modern <strong>lahir pada akhir abad ke-18 atau permulaan abad ke-19</strong> sebagai respons atas tuntutan kedaulatan warga bangsa.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena gelombang nasionalisme modern muncul bersamaan dengan revolusi kemerdekaan di Amerika dan Prancis yang menegaskan kedaulatan rakyat atas monarki absolut. Paham ini kemudian bermutasi di kawasan Asia-Afrika sebagai gerakan perlawanan terhadap kolonialisme barat. Latar waktu historis ini menandai era kebangkitan negara bangsa.</p><p>Jawaban tepat: <strong>D. Akhir abad ke-18 atau permulaan abad ke-19</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Awal abad ke-14 merupakan era feodalisme abad pertengahan di Eropa sebelum munculnya konsepsi negara bangsa modern.</li><li><strong>B</strong>: Awal abad ke-12 merupakan era pertikaian kekuasaan monarki religius feodal.</li><li><strong>C</strong>: Pertengahan abad ke-16 merupakan era reformasi gereja dan renaisans kultural keilmuan.</li><li><strong>E</strong>: Penghujung abad ke-20 merupakan era globalisasi pasca-runtuhnya Uni Soviet.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>merupakan paham modern yang lahir pada akhir abad ke 18 atau</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Jati diri bangsa Indonesia dalam penyelenggaraan pembangunan nasional berakar pada kesepakatan luhur bahwa keberhasilan bangsa merupakan...", "options": ["Kemenangan mutlak kelompok militer atas sipil", "Dampak persaingan suku bangsa dalam memperebutkan modal", "Hadiah kedermawanan dari negara-negara pendonor asing", "Hasil eksploitasi penuh atas kekayaan hutan lindung", "Rahmat Tuhan Yang Maha Esa dan keteguhan falsafah Pancasila"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengakuan Religius dalam Pembangunan Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bangsa Indonesia meyakini bahwa <strong>keberhasilan pembangunan nasional adalah rahmat Tuhan Yang Maha Esa dan keteguhan pada falsafah Pancasila</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena masyarakat Indonesia senantiasa menyadari dimensi spiritual dan etis dalam setiap capaian kemajuan peradaban bangsa. Keyakinan atas berkah ketuhanan dipadukan dengan komitmen teguh pada nilai Pancasila menjadikan pembangunan berdimensi holistik lahir dan batin. Nilai ini menjadi ciri khas kepribadian bangsa.</p><p>Jawaban tepat: <strong>E. Rahmat Tuhan Yang Maha Esa dan keteguhan falsafah Pancasila</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kemenangan kelompok militer mencerminkan junta kekuasaan yang bertentangan dengan supremasi sipil.</li><li><strong>B</strong>: Persaingan antarsuku memicu gesekan sosial yang merusak semangat persatuan kebangsaan.</li><li><strong>C</strong>: Hadiah donor asing mencerminkan mental inlander yang menafikan kerja keras dan kemandirian rakyat.</li><li><strong>D</strong>: Eksploitasi hutan lindung merusak daya dukung lingkungan dan bertentangan dengan keadilan ekologis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Jati diri bangsa Indonesia dapat dikenali dalam berbagai rumusan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Kedudukan Pancasila dalam seluruh gerak, arah, dan semangat pembangunan nasional bangsa Indonesia adalah sebagai...", "options": ["Landasan idiil dan pengamalan nilai pembangunan", "Landasan operasional jangka pendek kementerian", "Instrumen teknis pembagian devisa ekspor migas", "Petunjuk teknis pengadaan barang dan jasa instansi", "Standar evaluasi efisiensi anggaran belanja daerah"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Falsafah Pancasila sebagai Landasan Idiil</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pancasila berkedudukan sebagai <strong>landasan idiil pembangunan nasional; keseluruhan semangat, arah dan gerak pembangunan dilaksanakan sebagai pengamalan Pancasila</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena Pancasila menjadi kompas moral dan sumber rujukan nilai tertinggi yang mengarahkan setiap kebijakan pembangunan agar tetap berpihak pada kemanusiaan dan keadilan sosial. Seluruh program pembangunan merupakan perwujudan praktis dari sila-sila Pancasila. Status idiil ini menjamin keselarasan tujuan negara.</p><p>Jawaban tepat: <strong>A. Landasan idiil dan pengamalan nilai pembangunan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Landasan operasional berwujud rencana pembangunan jangka menengah atau peraturan pemerintah teknis.</li><li><strong>C</strong>: Pembagian devisa merupakan kebijakan moneter di bawah kewenangan bank sentral.</li><li><strong>D</strong>: Petunjuk pengadaan barang jasa merupakan regulasi operasional tata kelola pengadaan.</li><li><strong>E</strong>: Evaluasi efisiensi belanja daerah merupakan instrumen akuntabilitas keuangan dinas.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>landasan idiil pembangunan nasional; keseluruhan semangat, arah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan falsafah Pancasila dan konstitusi UUD 1945, tujuan utama pembangunan nasional Indonesia adalah mewujudkan...", "options": ["Kekuatan militer maritim terbesar di kawasan samudra", "Masyarakat adil dan makmur yang merata materiil dan spiritual", "Pemusatan kekayaan alam pada segelintir konglomerasi", "Ketergantungan ekonomi permanen pada pasar impor", "Pembatasan keterlibatan swasta dalam perniagaan domestik"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pembangunan Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tujuan pembangunan nasional adalah <strong>mewujudkan suatu masyarakat adil dan makmur yang merata materiil dan spiritual berdasarkan Pancasila dan UUD 1945</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena cita-cita kemakmuran bangsa Indonesia tidak sekadar diukur dari peningkatan angka produk domestik bruto, tetapi mencakup kesejahteraan batiniah, kebebasan beragama, dan keadilan distribusi kekayaan. Keseimbangan materiil dan spiritual menjamin terwujudnya kebahagiaan sejati warga negara. Orientasi ini menjiwai seluruh strategi pembangunan.</p><p>Jawaban tepat: <strong>B. Masyarakat adil dan makmur yang merata materiil dan spiritual</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kekuatan militer merupakan instrumen penjaga kedaulatan teritorial, bukan tujuan akhir kebahagiaan masyarakat.</li><li><strong>C</strong>: Pemusatan kekayaan pada konglomerasi melahirkan ketimpangan yang dilarang Pasal 33 UUD 1945.</li><li><strong>D</strong>: Ketergantungan impor merapuhkan ketahanan ekonomi dan kemandirian pangan nasional.</li><li><strong>E</strong>: Pembatasan sewenang swasta menghambat dinamika partisipasi ekonomi rakyat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pancasila; tujuan pembangunan nasional adalah mewujudkan suatu</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dalam tatanan kehidupan berbangsa, wahana kehidupan religius warga negara diwujudkan melalui kemerdekaan memeluk agama yang...", "options": ["Dibatasi sesuai instruksi pejabat kepolisian setempat", "Diarahkan menjadi dasar pembentukan negara agama formal", "Dilindungi oleh negara dan mewarnai hidup kebangsaan", "Dipisahkan secara sekuler dogmatis dari urusan kenegaraan", "Dikenakan pungutan pajak khusus bagi tempat ibadah"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perlindungan Hak Beragama dan Berkepercayaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wahana kehidupan religius diwujudkan dengan <strong>memeluk agama dan menganut kepercayaan terhadap Tuhan Yang Maha Esa, dilindungi oleh negara, dan mewarnai hidup kebangsaan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena Pasal 29 UUD 1945 menjamin kemerdekaan setiap penduduk untuk memeluk agama dan beribadat menurut kepercayaannya. Nilai-nilai ketuhanan yang luhur menjadi sumber moral yang memperkaya etika dan integritas dalam penyelenggaraan tata kelola negara. Pengayoman negara ini menciptakan kedamaian kerukunan antarumat beragama.</p><p>Jawaban tepat: <strong>C. Dilindungi oleh negara dan mewarnai hidup kebangsaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pembatasan sewenang bertentangan dengan hak asasi kemerdekaan beragama yang tidak dapat dikurangi (non-derogable rights).</li><li><strong>B</strong>: Indonesia berdasar pada Pancasila, bukan negara agama teokrasi dan bukan negara sekuler murni.</li><li><strong>D</strong>: Pemisahan sekuler dogmatis menafikan peran nilai spiritual ketuhanan dalam membentuk karakter etis bangsa.</li><li><strong>E</strong>: Pungutan pajak tempat ibadah bertentangan dengan kebijakan fasilitasi sarana peribadatan keagamaan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menganut kepercayaan terhadap Tuhan Yang Maha Esa, dilindungi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C3 - Aplikasi", "question": "Aparatur sipil negara yang memulai tugas kedinasan dengan doa dan menjalankan pekerjaan dengan penuh integritas moral mencerminkan perwujudan...", "options": ["Sikap primordialisme dalam instansi pemerintah", "Formalitas birokrasi tanpa makna substantif tugas", "Eksklusivisme golongan keagamaan tertentu di kantor", "Nilai religius yang mewarnai kehidupan kebangsaan", "Pemisahan mutlak moral pribadi dari tugas pelayanan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Harmonisasi Nilai Religius dan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Nilai religius dan kepercayaan kepada Tuhan Yang Maha Esa <strong>sewajarnya mewarnai hidup kebangsaan</strong> dalam bentuk integritas moral kerja nyata.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pengamalan nilai spiritualitas dalam tugas kedinasan membuahkan etos kerja yang jujur, amanah, dan berintegritas tinggi demi melayani masyarakat. Hal ini membuktikan bahwa wawasan kebangsaan Indonesia senantiasa menyatu dengan penghayatan moral ketuhanan. Keteladanan ini memperkokoh budaya birokrasi bersih.</p><p>Jawaban tepat: <strong>D. Nilai religius yang mewarnai kehidupan kebangsaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Primordialisme mementingkan kelompok suku atau kelompok sendiri secara tertutup dan diskriminatif.</li><li><strong>B</strong>: Formalitas birokrasi sekadar menggugurkan kewajiban seremonial tanpa penghayatan nilai moral.</li><li><strong>C</strong>: Eksklusivisme keagamaan membatasi interaksi dengan rekan kerja yang berbeda keyakinan.</li><li><strong>E</strong>: Pemisahan mutlak moral melahirkan aparat amoral yang rentan melakukan penyimpangan wewenang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 70) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>oleh negara, dan sewajarnya mewarnai hidup kebangsaan.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dalam strategi pembangunan nasional, wawasan kebangsaan menempatkan manusia seutuhnya dan masyarakat seluruhnya sebagai...", "options": ["Objek dan subjek usaha pembangunan nasional", "Penonton statis jalannya kebijakan modal korporasi", "Alat pemenuhan target penerimaan dividen bisnis", "Komoditas tenaga kerja murah bagi industri multinasional", "Beban pembiayaan anggaran belanja rutin pemerintahan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Manusia dalam Pembangunan Nasional</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kebangsaan membentuk <strong>manusia Indonesia seutuhnya dan masyarakat Indonesia seluruhnya sebagai objek dan subjek usaha pembangunan nasional menuju masyarakat adil dan makmur</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena manusia Indonesia bukan sekadar penerima manfaat pembangunan (objek), melainkan pelaku aktif yang merencanakan dan menggerakkan kemajuan bangsanya (subjek). Pendekatan humanistik ini memastikan pembangunan bertumpu pada martabat dan potensi manusia seutuhnya. Paradigma ini meneguhkan keadilan pembangunan nasional.</p><p>Jawaban tepat: <strong>A. Objek dan subjek usaha pembangunan nasional</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Menjadikan masyarakat penonton statis bertentangan dengan prinsip demokrasi partisipatif.</li><li><strong>C</strong>: Alat dividen bisnis merendahkan harkat kemanusiaan menjadi sekadar faktor produksi komersial.</li><li><strong>D</strong>: Komoditas tenaga kerja murah bertentangan dengan amanat konstitusi atas penghidupan yang layak.</li><li><strong>E</strong>: Memandang warga sebagai beban anggaran bertentangan dengan orientasi negara kesejahteraan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 71) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Wawasan kebangsaan membentuk manusia Indonesia seutuhnya dan masyarakat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Penghargaan terhadap harkat dan martabat manusia dalam wawasan kebangsaan mengandung konsekuensi mendasar bahwa...", "options": ["Kepentingan suku mayoritas dapat mengorbankan hak warga", "Setiap pribadi warga bangsa wajib dihormati martabatnya", "Kedudukan warga negara ditentukan oleh trah kebangsawanan", "Hak hukum individu dihapuskan demi kepentingan penguasa", "Warga berpenghasilan rendah memiliki hak suara lebih sedikit"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penghormatan Martabat Pribadi dalam Persatuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kebangsaan <strong>mengetengahkan manusia ke dalam pusat hidup bangsa, yang berarti dalam persatuan dan kesatuan bangsa setiap pribadi harus dihormati</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena persatuan yang sejati tidak boleh menindas hak individu, melainkan harus menjamin perlindungan harkat dan martabat setiap warga negara secara utuh. Setiap orang diakui keberadaannya sebagai ciptaan Tuhan yang bernilai luhur. Penghormatan pribadi ini memperkuat solidaritas batiniah persatuan nasional.</p><p>Jawaban tepat: <strong>B. Setiap pribadi warga bangsa wajib dihormati martabatnya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengorbankan hak warga atas nama suku mayoritas merupakan tirani mayoritas yang dilarang.</li><li><strong>C</strong>: Trah kebangsawanan adalah warisan feodalisme yang bertentangan dengan prinsip kesetaraan republik.</li><li><strong>D</strong>: Penghapusan hak individu oleh penguasa mencerminkan totaliterisme otoriter yang menindas.</li><li><strong>E</strong>: Pembagian hak suara berdasarkan kekayaan bertentangan dengan asas persamaan suara dalam pemilu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 71) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Penghargaan terhadap harkat dan martabat manusia menunjukkan bahwa wawasan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Penetapan manusia seutuhnya sebagai pribadi subjek pembangunan bertujuan agar setiap warga negara dapat...", "options": ["Menjauhi keterlibatan dalam kegiatan sosial kemasyarakatan", "Menuntut fasilitas istimewa di luar ketentuan aturan dinas", "Menjalankan hidupnya secara bertanggung jawab demi persatuan", "Memaksa orang lain mengikuti pandangan politik pribadinya", "Mementingkan keuntungan finansial pribadi di atas aturan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tanggung Jawab Pribadi demi Persatuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Manusia seutuhnya adalah subjek pembangunan <strong>bertujuan agar setiap pribadi warga bangsa dapat menjalankan hidupnya secara bertanggung jawab demi persatuan dan kesatuan bangsa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kesadaran sebagai subjek pembangunan menumbuhkan rasa tanggung jawab moral untuk berkontribusi menjaga keharmonisan dan keutuhan lingkungan sosial. Kebebasan yang dimiliki senantiasa diimbangi dengan kewajiban memelihara keutuhan bangsa. Akuntabilitas pribadi ini menjadi pilar kestabilan masyarakat.</p><p>Jawaban tepat: <strong>C. Menjalankan hidupnya secara bertanggung jawab demi persatuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menjauhi kegiatan sosial mencerminkan sikap apatis yang merugikan kehidupan bertetangga.</li><li><strong>B</strong>: Menuntut fasilitas istimewa bertentangan dengan prinsip keadilan dan transparansi birokrasi.</li><li><strong>D</strong>: Memaksa pandangan politik mencederai kemerdekaan berpikir dan hak asasi sesama warga.</li><li><strong>E</strong>: Mencari keuntungan pribadi di atas aturan memicu tindakan koruptif dan maladministrasi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 71) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Wawasan kebangsaan menegaskan bahwa manusia seutuhnya adalah pribadi subjek</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Tekad bersama untuk mewujudkan kehidupan kebangsaan yang bebas, merdeka, maju, dan mandiri dapat berhasil apabila ditopang oleh...", "options": ["Penambahan utang luar negeri secara berkelanjutan", "Pemusatan seluruh industri di wilayah pantai utara", "Pembatasan kebebasan pers dan arus pertukaran informasi", "Kesatuan dan persatuan bangsa yang kukuh dan berjaya", "Pemberian dispensasi hukum bagi pemegang saham utama"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prasyarat Keberhasilan Kehidupan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Tekad bersama berkehidupan kebangsaan yang bebas, merdeka, maju, dan mandiri <strong>akan berhasil dengan kesatuan dan persatuan bangsa yang kukuh dan berjaya</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena persatuan yang solid menjadi modalitas dasar dalam menggerakkan segenap potensi bangsa menuju kemandirian peradaban. Tanpa adanya persatuan yang tangguh, cita-cita kemakmuran dan kemerdekaan sejati akan mudah dirapuhkan oleh intervensi kepentingan luar. Persatuan yang kukuh mengantarkan bangsa pada kejayaan.</p><p>Jawaban tepat: <strong>D. Kesatuan dan persatuan bangsa yang kukuh dan berjaya</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penambahan utang berkelanjutan justru membebani keuangan negara dan kemandirian fiskal.</li><li><strong>B</strong>: Pemusatan industri di satu pantai memperparah ketimpangan antardaerah di kepulauan.</li><li><strong>C</strong>: Pembatasan pers membungkam kontrol publik yang sehat terhadap penyelenggaraan pemerintahan.</li><li><strong>E</strong>: Dispensasi hukum bagi pemodal mencederai prinsip persamaan di hadapan hukum (equality before the law).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 71) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>berkehidupan kebangsaan yang bebas, merdeka, maju, dan mandiri akan berhasil</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Nilai cinta akan tanah air dan bangsa dalam wawasan kebangsaan menegaskan penghargaan tinggi terhadap asas...", "options": ["Pemberian gelar kehormatan bagi pembayar pajak tertinggi", "Persaingan bebas yang memenangkan kelompok terkuat", "Eksploitasi teritorial demi keuntungan kelompok investor", "Pemisahan hak sipil berdasarkan kepemilikan aset tanah", "Kebersamaan luas yang melindungi dan memajukan setiap warga"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Cinta Tanah Air dan Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cinta tanah air dan bangsa <strong>menempatkan penghargaan tinggi akan kebersamaan yang luas yang melindungi setiap warga dan menyediakan tempat perkembangan pribadi</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kecintaan pada tanah air diwujudkan melalui komitmen untuk saling mengayomi dan menyediakan ruang tumbuh yang aman bagi seluruh warga negara. Semangat kebersamaan ini memastikan setiap individu dapat mengaktualisasikan potensinya demi kemajuan peradaban bersama. Nilai cinta tanah air menjalin ikatan persaudaraan sejati.</p><p>Jawaban tepat: <strong>E. Kebersamaan luas yang melindungi dan memajukan setiap warga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Gelar kehormatan berbayar mereduksi nilai integritas kebangsaan menjadi transaksi materiil.</li><li><strong>B</strong>: Pilihan ini adalah jawaban yang benar.</li><li><strong>C</strong>: Eksploitasi teritorial merusak keseimbangan alam dan merugikan hak generasi masa depan.</li><li><strong>D</strong>: Pemisahan hak sipil berdasarkan aset tanah merupakan praktik feodal tuan tanah zaman kolonial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 71) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Cinta akan tanah air dan bangsa menegaskan nilai sosial dasar dan wawasan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Solidaritas manusia dalam wawasan kebangsaan mengakui hak dan kewajiban asasi sesama manusia dengan prinsip...", "options": ["Tanpa membedakan suku, keturunan, agama, dan jenis kelamin", "Membeda-bedakan berdasarkan garis keturunan dan agama", "Mengutamakan warga berpendidikan tinggi dalam pelayanan", "Membatasi hak berpendapat warga minoritas di daerah", "Memberikan hak istimewa hukum bagi pejabat pemerintahan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Solidaritas Kemanusiaan Inklusif</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Solidaritas manusia <strong>mengakui hak dan kewajiban asasi sesama manusia tanpa membedakan suku, keturunan, agama dan kepercayaan, jenis kelamin, kedudukan sosial, maupun warna kulit</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena solidaritas kebangsaan Indonesia menjunjung kesetaraan universal seluruh manusia di hadapan hukum dan tatanan sosial kemasyarakatan. Pengakuan tanpa diskriminasi ini mencegah primordialisme sempit dan memupuk rasa persaudaraan lintas kelompok. Prinsip inklusif ini menjadi fondasi harmoni nasional.</p><p>Jawaban tepat: <strong>A. Tanpa membedakan suku, keturunan, agama, dan jenis kelamin</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Membeda-bedakan berdasarkan keturunan dan agama merupakan pelanggaran berat atas hak asasi manusia.</li><li><strong>C</strong>: Mengutamakan warga berpendidikan tinggi bertentangan dengan asas kesetaraan akses pelayanan publik.</li><li><strong>D</strong>: Membatasi hak minoritas mencederai jaminan perlindungan kebebasan menyatakan pendapat.</li><li><strong>E</strong>: Hak istimewa pejabat menyimpang dari prinsip kepatuhan hukum yang setara bagi seluruh warga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 71) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>setiap warga dan sekaligus mengungkapkan hormat terhadap solidaritas manusia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Konsepsi nasionalisme Indonesia dirumuskan sebagai suatu tekad bersama yang tumbuh dari bawah untuk bersedia...", "options": ["Menerima pembagian kekuasaan dengan negara tetangga", "Hidup sebagai suatu bangsa dalam negara merdeka", "Menjalankan doktrin satu ideologi mutlak tanpa musyawarah", "Mengurangi hak kepemilikan tanah bagi masyarakat lokal", "Menyerahkan pengelolaan pelabuhan utama kepada armada asing"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pertumbuhan Nasionalisme dari Bawah</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Nasionalisme sebagai suatu tekad bersama yang tumbuh dari bawah untuk bersedia hidup sebagai suatu bangsa dalam negara merdeka</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena nasionalisme Indonesia bukan rekayasa paksaan elite penguasa dari atas, melainkan kehendak murni (*volonté générale*) rakyat nusantara dari akar rumput untuk bersatu dalam kemerdekaan. Kesukarelaan hidup bersama ini menjadi fondasi batiniah yang mengikat seluruh suku bangsa secara sukarela dan tulus. Hakikat ini menjamin kekekalan ikatan kebangsaan.</p><p>Jawaban tepat: <strong>B. Hidup sebagai suatu bangsa dalam negara merdeka</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menerima pembagian kekuasaan dengan negara asing mencederai integritas kedaulatan wilayah nasional.</li><li><strong>C</strong>: Doktrin mutlak tanpa musyawarah bertentangan dengan prinsip permusyawaratan/perwakilan sila keempat.</li><li><strong>D</strong>: Mengurangi hak tanah masyarakat adat bertentangan dengan perlindungan konstitusional hak ulayat.</li><li><strong>E</strong>: Menyerahkan pelabuhan utama ke pihak asing mengancam kedaulatan maritim dan ekonomi nusantara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 71) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Nasionalisme sebagai suatu tekad bersama yang tumbuh dari bawah untuk bersedia</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Paham kebangsaan senantiasa berkaitan erat dengan demokrasi, karena tanpa adanya demokrasi maka kebangsaan berisiko merosot menjadi...", "options": ["Monarki parlementer yang menjamin pemilu terbuka", "Sistem persemakmuran yang terintegrasi secara ekonomi", "Fasisme atau Naziisme yang berbahaya bagi kemanusiaan", "Negara kepulauan dengan otonomi daerah yang luas", "Sistem meritokrasi birokrasi yang berbasis kinerja"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hubungan Erat Kebangsaan dan Demokrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kebangsaan selalu berkaitan erat dengan demokrasi, karena <strong>tanpa demokrasi kebangsaan akan mati bahkan merosot menjadi Fasisme/Naziisme yang berbahaya bagi minoritas dan bangsa lain</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena nasionalisme tanpa kontrol demokrasi dan musyawarah akan bermutasi menjadi chauvinisme agresif dan fasisme yang menindas keberagaman di dalam negeri serta mengancam negara lain. Demokrasi menjamin agar paham kebangsaan tetap bernafaskan kemanusiaan adil dan beradab. Sinergi ini melindungi kaum minoritas dari penindasan.</p><p>Jawaban tepat: <strong>C. Fasisme atau Naziisme yang berbahaya bagi kemanusiaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Monarki parlementer merupakan bentuk pemerintahan demokratis di beberapa negara sahabat.</li><li><strong>B</strong>: Sistem persemakmuran adalah ikatan kerja sama ekonomi antar-negara berdaulat.</li><li><strong>D</strong>: Negara kepulauan berotonomi luas adalah bentuk implementasi tata kelola NKRI modern.</li><li><strong>E</strong>: Meritokrasi birokrasi adalah tata kelola kepegawaian profesional berbasis kualifikasi dan kompetensi.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 71) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mati bahkan merosot menjadi Fasisme/ Naziisme yang bukan saja berbahaya bagi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dalam sistem nilai wawasan kebangsaan Indonesia, nilai kesetiakawanan sosial pada hakikatnya merupakan rumusan lain dari...", "options": ["Kebebasan persaingan pasar modal multinasional", "Pemberlakuan hukum adat di tingkat peradilan kasasi", "Kewenangan mutlak bendahara umum negara", "Keadilan sosial bagi seluruh rakyat Indonesia", "Pengurangan alokasi anggaran jaminan kesehatan"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kesetiakawanan Sosial sebagai Nilai Keadilan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kesetiakawanan sosial sebagai nilai merupakan rumusan lain dari keadilan sosial bagi seluruh rakyat</strong> dalam wawasan kebangsaan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena semangat kesetiakawanan sosial menuntut kepedulian bersama terhadap nasib sesama warga yang lemah melalui redistribusi keadilan dan gotong royong. Nilai ini menjadi jiwa dari sila kelima Pancasila yang memastikan kemakmuran dapat dinikmati secara merata oleh seluruh lapisan masyarakat. Persaudaraan sosial ini mencegah kesenjangan ekstrem.</p><p>Jawaban tepat: <strong>D. Keadilan sosial bagi seluruh rakyat Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Persaingan pasar modal liberal berfokus pada akumulasi keuntungan pemilik modal pribadi.</li><li><strong>B</strong>: Hukum adat di tingkat kasasi merupakan materi teknis hukum acara peradilan perdata.</li><li><strong>C</strong>: Kewenangan bendahara umum negara merupakan fungsi teknis administrasi kas perbendaharaan.</li><li><strong>E</strong>: Pengurangan anggaran jaminan kesehatan bertentangan dengan komitmen perlindungan sosial warga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 71) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kesetiakawanan sosial sebagai nilai merupakan rumusan lain dari keadilan sosial</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Wawasan kebangsaan menegaskan bahwa kesejahteraan rakyat sejati tidak sekadar diukur dari kemakmuran segelintir kelompok, melainkan...", "options": ["Jumlah penerbitan obligasi utang korporasi swasta", "Tingkat perolehan bunga simpanan perbankan komersial", "Ketinggian menara gedung pencakar langit di ibu kota", "Banyaknya armada mobil dinas bagi pejabat kementerian", "Lebih dari keseimbangan kewajiban sosial dan keuntungan individu"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Makna Sejati Kesejahteraan Rakyat</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kebangsaan menegaskan bahwa <strong>kesejahteraan rakyat lebih dari keseimbangan antara kewajiban sosial dan keuntungan individu</strong>, melainkan pemenuhan kebahagiaan hidup bersama yang bermartabat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kesejahteraan rakyat hakiki melampaui kalkulasi materialistik utilitas segelintir orang kaya dan menyentuh pemenuhan hak-hak asasi kemanusiaan seluruh rakyat. Negara hadir untuk menjamin keadilan distributif yang memanusiakan setiap warga negara. Konsepsi ini meneguhkan komitmen ekonomi kerakyatan Pancasila.</p><p>Jawaban tepat: <strong>E. Lebih dari keseimbangan kewajiban sosial dan keuntungan individu</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Penerbitan obligasi swasta merupakan instrumen pasar modal pembiayaan investasi bisnis.</li><li><strong>B</strong>: Bunga simpanan bank adalah imbal hasil instrumen investasi perbankan konvensional.</li><li><strong>C</strong>: Gedung pencakar langit adalah simbol fisik pembangunan kota yang belum tentu mencerminkan kesejahteraan rakyat kecil.</li><li><strong>D</strong>: Mobil dinas pejabat merupakan fasilitas penunjang operasional dinas kedinasan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 71) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>keuntungan individu.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Keseluruhan pranata dan usaha dalam kehidupan sosial yang memungkinkan setiap pribadi dan keluarga mencapai kesempurnaan hidup disebut...", "options": ["Jaminan asuransi perbankan komersial", "Kesejahteraan sosial atau kesejahteraan umum", "Sistem proteksi investasi pasar modal", "Kekuatan cadangan devisa moneter negara", "Tingkat likuiditas obligasi korporasi"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Kesejahteraan Sosial</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kesejahteraan sosial disebut juga kesejahteraan umum</strong> yang mencakup keseluruhan lembaga dan usaha dalam hidup sosial yang membangun dan memungkinkan setiap pribadi mencapai kesempurnaan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kesejahteraan umum mencakup segenap tatanan kelembagaan masyarakat dan negara yang menopang pemenuhan kebutuhan dasar materiil serta spiritual warga negara. Konsep ini menjamin terciptanya lingkungan sosial yang memampukan setiap orang bertumbuh secara utuh dan bermartabat. Orientasi ini merealisasikan amanat Pembukaan UUD 1945.</p><p>Jawaban tepat: <strong>B. Kesejahteraan sosial atau kesejahteraan umum</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Asuransi perbankan komersial merupakan produk proteksi finansial privat berbasis premi nasabah.</li><li><strong>C</strong>: Proteksi investasi pasar modal merupakan instrumen perlindungan pemodal dari risiko transaksi saham.</li><li><strong>D</strong>: Cadangan devisa moneter merupakan simpanan mata uang asing yang dikelola bank sentral.</li><li><strong>E</strong>: Likuiditas obligasi korporasi adalah kemampuan surat utang swasta untuk dicairkan di bursa efek.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 72) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kesejahteraan sosial disebut juga kesejahteraan umum yang mencakup keseluruhan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dalam tatanan filosofis kehidupan bernegara di Indonesia, kebangsaan dan demokrasi berkedudukan sebagai...", "options": ["Tujuan akhir berdirinya negara kesatuan", "Instrumen formalitas pembagian kursi menteri", "Sarana dan wahana mencapai masyarakat adil makmur", "Siasat diplomasi menghadapi tekanan luar", "Doktrin mutlak penyeragaman suku bangsa"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Kebangsaan dan Demokrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Kebangsaan dan demokrasi bukanlah tujuan</strong>, tetapi merupakan <strong>sarana dan wahana untuk mencapai tujuan yang lebih tinggi yaitu masyarakat yang adil dan makmur</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena paham kebangsaan dan sistem demokrasi berfungsi sebagai instrumen etis dan mekanisme tata kelola demi merealisasikan keadilan sosial bagi seluruh rakyat. Muara tertinggi dari seluruh perjuangan kenegaraan adalah kemakmuran dan kesejahteraan rakyat yang berkeadilan. Pemahaman ini meluruskan orientasi politik bangsa.</p><p>Jawaban tepat: <strong>C. Sarana dan wahana mencapai masyarakat adil makmur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kebangsaan dan demokrasi bukan tujuan akhir, melainkan wahana pengantar menuju kemakmuran adil.</li><li><strong>B</strong>: Pembagian kursi menteri merupakan dinamika teknis koalisi politik pemerintahan.</li><li><strong>D</strong>: Siasat diplomasi merupakan strategi perundingan hubungan internasional.</li><li><strong>E</strong>: Penyeragaman suku bangsa bertentangan dengan prinsip Bhinneka Tunggal Ika.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 72) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kebangsaan dan demokrasi bukanlah tujuan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Salah satu ciri khas utama dari negara demokrasi yang membedakannya secara tegas dari negara totaliter adalah...", "options": ["Kekuatan militer yang mendominasi pemerintahan", "Pemberlakuan jam malam di seluruh wilayah kota", "Pengawasan terpusat atas seluruh media informasi", "Sikap toleransi dan penghargaan atas perbedaan", "Penyeragaman keyakinan seluruh warga negara"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Toleransi sebagai Ciri Negara Demokrasi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Salah satu ciri khas dari negara demokrasi yang membedakan dari negara totaliter adalah toleransi</strong> terhadap kemajemukan pandangan dan identitas.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena dalam demokrasi yang sehat, perbedaan pendapat, keyakinan, dan aspirasi dihormati serta diwadahi secara damai melalui hukum yang berkeadilan. Sebaliknya, rezim totaliter memaksakan keseragaman mutlak dan membungkam keberagaman pandangan rakyat. Sikap toleran ini menjadi napas utama peradaban Indonesia.</p><p>Jawaban tepat: <strong>D. Sikap toleransi dan penghargaan atas perbedaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Dominasi militer mencerminkan kediktatoran junta militer yang otoriter.</li><li><strong>B</strong>: Pemberlakuan jam malam rutin merupakan pembatasan kebebasan gerak sipil.</li><li><strong>C</strong>: Sensor media informasi merupakan ciri represi rezim totaliter tertutup.</li><li><strong>E</strong>: Penyeragaman keyakinan bertentangan dengan hak asasi kemerdekaan beragama universal.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 72) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Salah satu ciri khas dari negara demokrasi yang membedakan dari negara totaliter</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dalam prinsip demokrasi Pancasila, pengambilan keputusan melalui mekanisme pemungutan suara terbanyak diposisikan sebagai...", "options": ["Langkah pertama yang wajib diambil di awal sidang", "Simbol formalitas persidangan tanpa makna musyawarah", "Satu-satunya metode sah penentuan kebijakan publik", "Hak mutlak fraksi mayoritas untuk menekan oposisi", "Jalan terakhir setelah musyawarah diupayakan optimal"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Hakikat Musyawarah dan Suara Terbanyak</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam demokrasi wawasan kebangsaan, <strong>pengambilan keputusan dengan suara terbanyak merupakan jalan yang terakhir setelah diupayakan musyawarah</strong> untuk mufakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena demokrasi Pancasila mengutamakan musyawarah mufakat yang dilandasi akal sehat dan hati nurani luhur guna mengakomodasi seluruh aspirasi. Voting atau pemungutan suara terbanyak baru ditempuh apabila perundingan musyawarah telah menemui jalan buntu (deadlock). Hierarki ini mencegah tirani mayoritas maupun tirani minoritas.</p><p>Jawaban tepat: <strong>E. Jalan terakhir setelah musyawarah diupayakan optimal</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menjadikan voting langkah awal menafikan esensi dialog musyawarah mufakat sila keempat.</li><li><strong>B</strong>: Memandang voting sekadar simbol mengesampingkan legitimasi mekanisme pengambilan keputusan konstitusional.</li><li><strong>C</strong>: Menjadikan voting satu-satunya metode meniru demokrasi liberal barat yang bertumpu pada angka mutlak.</li><li><strong>D</strong>: Hak mutlak menekan oposisi mencerminkan kediktatoran mayoritas yang ditolak Pancasila.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 72) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>terbanyak merupakan jalan yang terakhir setelah diupayakan musyawarah. Hal yang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C3 - Aplikasi", "question": "Sikap saling menghormati kebebasan beribadah dan kesediaan bekerja sama antarpemeluk agama yang berbeda di lingkungan kerja merupakan perwujudan...", "options": ["Kerukunan hidup beragama berlandaskan kebangsaan", "Sinkretisme ajaran agama dalam pergaulan dinas", "Pemisahan mutlak nilai moral dari ruang birokrasi", "Formalitas administratif pemenuhan toleransi semu", "Dominasi tradisi keagamaan kelompok mayoritas lokal"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kerukunan Hidup Beragama dan Kepercayaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Prinsip toleransi tampak dalam <strong>kerukunan hidup beragama dengan sikap saling menghormati dan bekerja sama antarpemeluk agama serta menghormati kebebasan beribadah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena toleransi keagamaan yang aktif dan kerja sama kebajikan lintas iman memperkokoh rajutan persaudaraan kebangsaan di lingkungan kerja aparatur. Penghormatan atas keyakinan masing-masing menciptakan suasana kerja yang harmonis, produktif, dan berkeadaban. Sikap ini membuktikan kedewasaan berbangsa.</p><p>Jawaban tepat: <strong>A. Kerukunan hidup beragama berlandaskan kebangsaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sinkretisme mencampurkan dogma ajaran agama yang berbeda menjadi satu ajaran baru.</li><li><strong>C</strong>: Pemisahan nilai moral meniadakan etika spiritual dalam pelayanan publik.</li><li><strong>D</strong>: Toleransi semu tanpa inisiatif tanpa adanya kerja sama sosial nyata.</li><li><strong>E</strong>: Dominasi tradisi mayoritas bertentangan dengan prinsip keadilan dan perlindungan hak sesama warga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 72) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>sama nampak dalam kerukunan hidup beragama dan kepercayaan terhadap Tuhan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Wawasan kebangsaan mengutamakan persatuan dan keselamatan bangsa di atas kepentingan golongan serta menuntut setiap warga negara untuk...", "options": ["Mengelak dari penugasan dinas di wilayah kepulauan terluar", "Sanggup dan rela berkorban demi kepentingan bangsa", "Memprioritaskan keuntungan bisnis pribadi di atas tugas", "Menuntut kenaikan tunjangan sebelum melaksanakan pengabdian", "Membatasi komunikasi dengan aparatur dari suku lain"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kerelaan Berkorban bagi Bangsa dan Negara</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kebangsaan menghendaki agar <strong>manusia Indonesia sanggup dan rela berkorban untuk kepentingan bangsa Indonesia</strong> di atas kepentingan pribadi.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena patriotisme sejati menuntut keikhlasan mendahulukan keselamatan kedaulatan tanah air dan kesejahteraan umum ketika bangsa menghadapi tantangan besar. Kerelaan berkorban ini telah dicontohkan oleh para pahlawan kemerdekaan dan harus diteruskan oleh seluruh generasi penerus. Jiwa pengorbanan ini mengukuhkan ketahanan nasional.</p><p>Jawaban tepat: <strong>B. Sanggup dan rela berkorban demi kepentingan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mengelak dari tugas perbatasan mencerminkan sikap egois yang melalaikan amanah bela negara.</li><li><strong>C</strong>: Memprioritaskan bisnis pribadi mencederai integritas dan kode etik aparatur sipil negara.</li><li><strong>D</strong>: Menuntut tunjangan sebelum bekerja mencerminkan mentalitas materialistis yang tidak loyal.</li><li><strong>E</strong>: Membatasi komunikasi antarsuku merusak soliditas korps pegawai republik Indonesia.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 72) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>persatuan, kesatuan serta kepentingan dan keselamatan bangsa dan negara di atas</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dalam rangka memelihara keutuhan bangsa, nilai-nilai luhur yang hendaknya senantiasa dipupuk dalam jiwa setiap insan Indonesia adalah...", "options": ["Fanatisme kedaerahan dan persaingan primordial", "Eksploitasi ekonomi dan pemusatan kekayaan modal", "Martabat manusia, cinta tanah air, demokrasi, dan solidaritas", "Ketertutupan budaya dan penolakan kemajuan teknologi", "Sikap acuh tak acuh terhadap nasib warga tertinggal"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Nilai-Nilai Luhur yang Wajib Dipupuk</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam wawasan kebangsaan <strong>hendaknya dipupuk penghargaan terhadap martabat manusia, cinta kepada tanah air dan bangsa, demokrasi dan kesetiakawanan sosial</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena keterpaduan keempat pilar nilai ini membentuk kepribadian bangsa yang humanis, nasionalis, demokratis, dan berkeadilan sosial. Pembinaan karakter ini secara berkesinambungan menjaga imunitas bangsa dari ancaman disintegrasi dan polarisasi sosial. Nilai luhur ini menjadi pedoman perilaku bernegara.</p><p>Jawaban tepat: <strong>C. Martabat manusia, cinta tanah air, demokrasi, dan solidaritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Fanatisme kedaerahan memecah belah keutuhan wilayah dan memicu konflik horizontal.</li><li><strong>B</strong>: Eksploitasi ekonomi memperlebar jurang kesenjangan sosial antarwarga.</li><li><strong>D</strong>: Ketertutupan budaya membuat bangsa tertinggal dalam persaingan peradaban global.</li><li><strong>E</strong>: Sikap acuh tak acuh melunturkan rasa kebersamaan gotong royong nusantara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 72) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Sehubungan dengan itu, hendaknya dipupuk penghargaan terhadap martabat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Prinsip persatuan yang dikembangkan dalam wawasan kebangsaan Indonesia menegaskan batasan mendasar bahwa...", "options": ["Seluruh dialek bahasa daerah wajib dilebur menjadi seragam", "Pemerintah pusat berhak membubarkan lembaga masyarakat adat", "Adat istiadat lokal dihapuskan demi efisiensi birokrasi", "Persatuan tidak boleh mematikan keanekaan dan kemajemukan", "Pakaian adat daerah dilarang ditampilkan dalam acara resmi"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Persatuan dan Keberagaman</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Persatuan dikembangkan sedemikian rupa sehingga asas Bhinneka Tunggal Ika dipertahankan, karena <strong>persatuan tidak boleh mematikan keanekaan dan kemajemukan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena persatuan Indonesia adalah persatuan yang menghargai dan melindungi keanekaragaman suku, bahasa, serta adat istiadat sebagai kekayaan bersama bangsa. Persatuan bukanlah penyeragaman paksa yang membungkam ciri khas lokal, melainkan harmoni simfoni nusantara. Asas ini menjamin kelangsungan semboyan Bhinneka Tunggal Ika.</p><p>Jawaban tepat: <strong>D. Persatuan tidak boleh mematikan keanekaan dan kemajemukan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Melebur dialek bahasa daerah bertentangan dengan kewajiban negara memelihara bahasa daerah.</li><li><strong>B</strong>: Membubarkan lembaga adat sewenang bertentangan dengan penghormatan kesatuan masyarakat adat.</li><li><strong>C</strong>: Menghapus adat istiadat merusak warisan budaya luhur yang dilindungi undang-undang dasar.</li><li><strong>E</strong>: Melarang pakaian adat daerah menafikan identitas kebhinekaan dalam perhelatan nasional.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 72) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Wawasan kebangsaan mengembangkan persatuan Indonesia sehingga asas</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Sebaliknya, keanekaan dan kemajemukan masyarakat Indonesia dalam wawasan kebangsaan tidak boleh menjadi faktor pemecah belah, melainkan...", "options": ["Alasan untuk meminta otonomi terpisah dari negara kesatuan", "Alat pembenaran penguasaan politik oleh kelompok tertentu", "Bahan persaingan dagang antarsuku di pasar perniagaan", "Dasar pembagian jatah bantuan sosial secara diskriminatif", "Hal yang memperkaya dan memperkokoh persatuan bangsa"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kedudukan Kemajemukan sebagai Pengaya Persatuan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Keanekaan dan kemajemukan <strong>tidak boleh menjadi pemecah belah tetapi menjadi hal yang memperkaya persatuan</strong> kebangsaan Indonesia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena warna-warni budaya, adat, dan kearifan lokal memperkaya khazanah kebudayaan nasional serta memberikan sumbangan gagasan bagi kemajuan bersama. Kemajemukan yang dirajut dalam persatuan menghadirkan sinergi kekuatan yang luar biasa bagi ketahanan nasional. Keragaman ini menjadi kebanggaan identitas keindonesiaan.</p><p>Jawaban tepat: <strong>E. Hal yang memperkaya dan memperkokoh persatuan bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Meminta otonomi terpisah mengarah pada separatisme yang merusak keutuhan wilayah negara.</li><li><strong>B</strong>: Alat penguasaan politik oleh kelompok tertentu mencederai prinsip demokrasi yang setara.</li><li><strong>C</strong>: Persaingan dagang antarsuku memicu gesekan etnis yang merugikan persaudaraan warga.</li><li><strong>D</strong>: Pembagian bantuan diskriminatif mencederai keadilan sosial dan memicu kecemburuan sosial.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 72) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjadi pemecah belah tetapi menjadi hal yang memperkaya persatuan.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C3 - Aplikasi", "question": "Kebijakan kementerian yang memberikan ruang bagi pegawai mengenakan busana tradisional nusantara pada hari kerja tertentu mencerminkan komitmen...", "options": ["Mempertahankan asas Bhinneka Tunggal Ika yang memperkaya persatuan", "Formalitas seremonial tanpa kepatuhan aturan kedinasan", "Pembedaan perlakuan pangkat berdasarkan keindahan busana", "Pengurangan jam kerja produktif demi kegiatan pameran", "Pemisahan kelompok kerja berdasarkan kesamaan asal suku"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Implementasi Bhinneka Tunggal Ika dalam Kebijakan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Asas <strong>Bhinneka Tunggal Ika dipertahankan, karena persatuan tidak boleh mematikan keanekaan dan kemajemukan</strong> dalam kehidupan bernegara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena apresiasi terhadap busana adat nusantara di lingkungan kerja menumbuhkan rasa bangga terhadap kekayaan budaya nasional sekaligus merajut kebersamaan dalam keberagaman. Kebijakan ini membuktikan bahwa persatuan aparatur sipil negara ditegakkan tanpa menyingkirkan identitas kultural daerah. Praktik ini menguatkan integrasi sosial birokrasi.</p><p>Jawaban tepat: <strong>A. Mempertahankan asas Bhinneka Tunggal Ika yang memperkaya persatuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Mengenakan pakaian adat merupakan implementasi kebijakan resmi afirmatif yang sah dan bermakna.</li><li><strong>C</strong>: Pangkat dan karier dinilai berdasarkan kualifikasi, kompetensi, dan kinerja pegawai, bukan busana.</li><li><strong>D</strong>: Kebijakan busana adat tidak mengurangi jam pelayanan melainkan menyemangati etos kerja berbudaya.</li><li><strong>E</strong>: Pemisahan kelompok kerja berdasarkan suku merupakan segregasi primordial yang dilarang keras.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 72) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Bhinneka Tunggal Ika dipertahankan, karena persatuan tidak boleh mematikan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Wawasan kebangsaan Indonesia secara tegas menentang patriotisme picik dengan mengamanatkan setiap warga negara agar...", "options": ["Menganggap rendah kebudayaan dan peradaban bangsa lain", "Menepis perjanjian perdamaian dan bantuan kemanusiaan global", "Membina cinta tanah air dengan jiwa besar tanpa kepicikan", "Memutuskan seluruh hubungan kerja sama diplomatik luar", "Memaksakan kehendak nasional di forum perundingan dunia"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Penolakan terhadap Patriotisme Picik</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Wawasan kebangsaan <strong>tidak memberi tempat pada patriotisme yang picik, dengan mengamanatkan agar para warga membina jiwa besar dengan setia cinta akan tanah air tanpa kepicikan jiwa</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena nasionalisme Indonesia berlandaskan kemanusiaan adil dan beradab yang menghormati kedaulatan bangsa-bangsa lain di dunia. Patriotisme sejati menjauhi chauvinisme sempit dan kebencian buta terhadap pihak luar. Jiwa besar ini memandu kepeloporan diplomasi damai Indonesia.</p><p>Jawaban tepat: <strong>C. Membina cinta tanah air dengan jiwa besar tanpa kepicikan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menganggap rendah bangsa lain adalah chauvinisme yang bertentangan dengan sila kedua Pancasila.</li><li><strong>B</strong>: Menepis kerja sama kemanusiaan bertentangan dengan amanat ketertiban dunia dalam Pembukaan UUD 1945.</li><li><strong>D</strong>: Memutuskan hubungan diplomatik mengisolasi Indonesia dari pergaulan peradaban internasional.</li><li><strong>E</strong>: Memaksakan kehendak bertentangan dengan asas musyawarah dan diplomasi multilateral yang setara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 73) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Wawasan kebangsaan tidak memberi tempat pada patriotisme yang picik, dengan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Cinta akan tanah air dan bangsa Indonesia senantiasa diarahkan secara berimbang pada kepentingan...", "options": ["Ekspansi kekuasaan militer ke wilayah negara tetangga", "Dominasi perdagangan monopoli hasil rempah kepulauan", "Kelompok pemegang hak konsesi pertambangan lepas pantai", "Seluruh umat manusia dalam jaringan persaudaraan antarbangsa", "Pemisahan diri dari keanggotaan badan-badan dunia"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Orientasi Kemanusiaan Universal</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Cinta tanah air dan bangsa <strong>sekaligus diarahkan pada kepentingan seluruh umat manusia yang saling berhubungan dengan berbagai jaringan antarras, antarbangsa dan antarnegara</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena wawasan kebangsaan Indonesia menempatkan kepentingan nasional selaras dengan kemaslahatan peradaban kemanusiaan global. Hubungan antarbangsa dipandang sebagai jalinan persaudaraan yang saling membutuhkan dalam mewujudkan perdamaian abadi dan keadilan sosial. Keseimbangan ini menegaskan kepribadian internasional bangsa.</p><p>Jawaban tepat: <strong>D. Seluruh umat manusia dalam jaringan persaudaraan antarbangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Ekspansi militer bertentangan dengan prinsip penolakan agresi dan penjajahan.</li><li><strong>B</strong>: Monopoli rempah mencerminkan sistem imperialisme komersial yang ditentang para pendiri bangsa.</li><li><strong>C</strong>: Kepentingan pemegang konsesi adalah keuntungan bisnis sektoral di luar nilai kemanusiaan.</li><li><strong>E</strong>: Pemisahan dari badan dunia melemahkan partisipasi aktif dalam perumusan kebijakan global.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 73) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pada kepentingan seluruh umat manusia yang saling berhubungan dengan berbagai</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Pada hakikatnya, seluruh konsepsi wawasan kebangsaan Indonesia dilandasi oleh kedudukan Pancasila sebagai...", "options": ["Buku referensi kajian perbandingan hukum perdata", "Pedoman operasional sistem pembayaran digital", "Kaidah teknis pencatatan aset inventaris kekayaan", "Peraturan pemerintah mengenai tarif kepabeanan", "Falsafah dan pandangan hidup bangsa Indonesia"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Landasan Hakiki Wawasan Kebangsaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Wawasan Kebangsaan Indonesia pada hakikatnya dilandasi oleh Pancasila sebagai falsafah dan pandangan hidup bangsa Indonesia</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Pancasila merupakan konsensus dasar dan sumber dari segala sumber hukum yang menjiwai pandangan geopolitik kebangsaan. Nilai-nilai ketuhanan, kemanusiaan, persatuan, kerakyatan, dan keadilan sosial meresap ke dalam setiap sendi wawasan kebangsaan. Landasan filosofis ini menjaga keteguhan identitas bangsa.</p><p>Jawaban tepat: <strong>E. Falsafah dan pandangan hidup bangsa Indonesia</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Buku referensi kajian hukum perdata merupakan literatur akademis di bidang hukum.</li><li><strong>B</strong>: Pedoman pembayaran digital merupakan regulasi teknis bank sentral dalam sistem pembayaran.</li><li><strong>C</strong>: Kaidah pencatatan aset merupakan standar akuntansi perbendaharaan negara teknis.</li><li><strong>D</strong>: Tarif kepabeanan merupakan regulasi fiskal yang ditetapkan oleh menteri keuangan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 73) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Wawasan Kebangsaan Indonesia pada hakikatnya dilandasi oleh Pancasila sebagai</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan undang-undang tentang aparatur sipil negara, salah satu fungsi pokok pegawai ASN adalah berkedudukan sebagai...", "options": ["Perekat dan pemersatu bangsa dalam kesatuan NKRI", "Pelobi kebijakan anggaran bagi partai koalisi", "Penyelidik rahasia perbankan nasabah korporasi", "Pengelola tunggal aset perseroan terbatas daerah", "Penanggung jawab tunggal penetapan hukum peradilan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi ASN Pemersatu Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam UU Nomor 5 Tahun 2014, fungsi ketiga ASN ditegaskan sebagai <strong>perekat dan pemersatu bangsa</strong> dalam wadah Negara Kesatuan Republik Indonesia.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena ASN merupakan benteng pertahanan persatuan nasional yang tersebar di seluruh pelosok wilayah nusantara. Dalam menjalankan tugas kedinasan, ASN wajib merekatkan kemajemukan masyarakat dan menjaga keutuhan wilayah negara dari ancaman perpecahan. Fungsi mulia ini menuntut loyalitas tanpa syarat kepada Pancasila dan NKRI.</p><p>Jawaban tepat: <strong>A. Perekat dan pemersatu bangsa dalam kesatuan NKRI</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pelobi partai politik dilarang keras bagi ASN karena bertentangan dengan asas netralitas.</li><li><strong>C</strong>: Penyelidikan rahasia perbankan merupakan kewenangan penyidik aparat penegak hukum tertentu.</li><li><strong>D</strong>: Pengelolaan aset perseroan daerah dijalankan oleh direksi BUMD profesional.</li><li><strong>E</strong>: Penetapan hukum peradilan merupakan kewenangan mutlak hakim pada lembaga kekuasaan kehakiman.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 73) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Fungsi ASN sebagai Perekat dan Pemersatu</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Saat diangkat menjadi pegawai ASN, sumpah dan janji yang diikrarkan mewajibkan kesetiaan dan ketaatan sepenuhnya kepada...", "options": ["Pimpinan partai politik pendukung pejabat pembina", "Pancasila, UUD 1945, negara, dan pemerintah yang sah", "Organisasi kemasyarakatan kedaerahan setempat", "Investor penyedia dana proyek pembangunan kantor", "Kelompok perhimpunan alumni almamater perguruan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Sumpah dan Janji Pegawai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam sumpah janji ASN, dinyatakan <strong>ASN akan senantiasa setia dan taat sepenuhnya kepada Pancasila, UUD 1945, negara, dan pemerintah</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena ikrar sumpah janji mengikat kesadaran moral dan yuridis setiap pegawai aparatur sipil negara untuk mengabdi secara teguh kepada konsensus dasar bangsa. Loyalitas kepada konstitusi dan pemerintahan yang sah menjamin kontinuitas pelayanan publik di segala situasi. Sumpah ini menjadi benteng integritas abdi negara.</p><p>Jawaban tepat: <strong>B. Pancasila, UUD 1945, negara, dan pemerintah yang sah</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kesetiaan kepada partai politik bertentangan dengan asas netralitas yang diwajibkan undang-undang ASN.</li><li><strong>C</strong>: Kesetiaan pada ormas kedaerahan dapat memicu konflik loyalitas primordial.</li><li><strong>D</strong>: Keterikatan pada investor proyek membuka peluang terjadinya gratifikasi dan konflik kepentingan.</li><li><strong>E</strong>: Loyalitas pada paguyuban alumni menumbuhkan nepotisme dalam promosi jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 73) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dalam UU Nomor 5 Tahun 2014 pasal 66 ayat 1-2 terkait sumpah dan janji ketika</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dalam ikrar sumpah jabatan, setiap pegawai ASN wajib menjunjung martabat korps serta senantiasa mengutamakan...", "options": ["Kepentingan pribadi di atas kelancaran pelayanan", "Keuntungan bisnis sampingan di luar jam kantor", "Kepentingan negara daripada kepentingan diri dan golongan", "Popularitas di media sosial di atas kerahasiaan dinas", "Kepentingan keluarga dalam penyaluran dana bansos"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Pengutamaan Kepentingan Negara oleh ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai ASN menjunjung tinggi martabat serta <strong>senantiasa mengutamakan kepentingan negara daripada kepentingan diri sendiri, seseorang dan golongan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena dedikasi utama seorang aparatur adalah mengabdi bagi kemaslahatan bangsa dan negara tanpa terdistraksi oleh ambisi keuntungan pribadi atau golongan. Sikap mengutamakan negara mencegah terjadinya praktik penyelewengan, kolusi, dan nepotisme di birokrasi. Keutamaan sikap ini menjaga integritas aparatur.</p><p>Jawaban tepat: <strong>C. Kepentingan negara daripada kepentingan diri dan golongan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Mendahulukan kepentingan pribadi menyimpang dari kode etik dan disiplin pegawai negeri sipil.</li><li><strong>B</strong>: Fokus pada bisnis sampingan dapat mengganggu profesionalitas dan konsentrasi jam kerja kedinasan.</li><li><strong>D</strong>: Mengejar popularitas dengan membocorkan rahasia dinas merupakan pelanggaran disiplin berat.</li><li><strong>E</strong>: Menyalurkan bansos untuk keluarga merupakan penyalahgunaan wewenang dan tindak penyelewengan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 73) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>martabat ASN serta senantiasa mengutamakan kepentingan negara dari pada</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Bagi seorang pegawai aparatur sipil negara, kedudukan Pancasila, UUD 1945, dan NKRI dipandang sebagai...", "options": ["Aturan sementara yang dapat ditinjau ulang tiap pemilu", "Dokumen historis yang tidak mengikat tindakan nyata", "Kesepakatan bersyarat tergantung kemampuan fiskal daerah", "Konsensus yang bersifat final dan harga mati bagi bangsa", "Pilihan alternatif di antara aneka ideologi impor"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Komitmen Finalitas Konsensus Kebangsaan bagi ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Bagi seorang ASN, <strong>Pancasila, UUD 1945 dan NKRI adalah sesuatu yang final dan harga mati</strong>, serta siap mempertahankan keutuhan negara.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena aparatur negara terikat komitmen ideologis tanpa kompromi untuk menjaga tegaknya dasar negara dan keutuhan teritorial Republik Indonesia. Seorang ASN dilarang menganut atau menyebarkan paham yang bertentangan dengan Pancasila dan konstitusi. Keteguhan ini menjamin stabilitas eksistensi negara kesatuan.</p><p>Jawaban tepat: <strong>D. Konsensus yang bersifat final dan harga mati bagi bangsa</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Meninjau ulang dasar negara setiap pemilu dapat memicu kekacauan ketatanegaraan.</li><li><strong>B</strong>: Memandang konstitusi tidak mengikat merupakan bentuk pembangkangan hukum ketatanegaraan.</li><li><strong>C</strong>: Kesepakatan bersyarat melemahkan ikatan persatuan dan kesatuan wilayah nasional.</li><li><strong>E</strong>: Mencari ideologi alternatif bertentangan dengan status Pancasila sebagai ideologi tunggal negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 73) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Pancasila, UUD 1945 dan NKRI adalah sesuatu yang final dan harga mati. Dia siap</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Guna menjalankan fungsinya sebagai perekat dan pemersatu bangsa, setiap pegawai ASN wajib dibekali dengan standar...", "options": ["Kompetensi intelijen operasi khusus luar negeri", "Kewenangan mutlak pembekuan rekening wajib pajak", "Keahlian arbitrase sengketa penanaman modal asing", "Sertifikasi keahlian persenjataan tempur infanteri", "Kompetensi sosial kultural dalam masyarakat majemuk"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Kebutuhan Kompetensi Sosial Kultural</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Dalam rangka menjalankan fungsi perekat dan pemersatu bangsa, <strong>setiap ASN perlu memiliki kompetensi sosial kultural</strong> sebagaimana amanah peraturan manajemen ASN.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kompetensi sosial kultural membekali pegawai dengan kepekaan dan kecakapan berinteraksi secara efektif di tengah masyarakat Indonesia yang majemuk. Penguasaan kompetensi ini mencegah timbulnya konflik antarkelompok serta menumbuhkan kepercayaan publik terhadap birokrasi. Hal ini mendukung pelaksanaan fungsi pemersatu bangsa.</p><p>Jawaban tepat: <strong>E. Kompetensi sosial kultural dalam masyarakat majemuk</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Intelijen operasi khusus merupakan kompetensi personel lembaga intelijen negara (BIN).</li><li><strong>B</strong>: Pembekuan rekening wajib pajak merupakan wewenang teknis jurusita pajak sesuai aturan.</li><li><strong>C</strong>: Arbitrase modal asing merupakan keahlian advokat spesialis hukum dagang internasional.</li><li><strong>D</strong>: Keahlian persenjataan infanteri merupakan kualifikasi prajurit tempur TNI.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 73) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Dalam rangka menjalankan fungsi ketiga (perekat dan pemersatu bangsa), setiap</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi aparatur negara, kompetensi sosial kultural didefinisikan sebagai perpaduan terpadu antara...", "options": ["Pengetahuan, keterampilan, dan sikap perilaku yang terukur", "Tingkat kekayaan, gelar kehormatan, dan jabatan politik", "Kekuatan fisik, ketangkasan bela diri, dan kepemilikan senjata", "Jumlah pengikut media sosial dan popularitas opini publik", "Kedekatan hubungan kekeluargaan dengan pejabat pembina"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Kompetensi Sosial Kultural</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kompetensi sosial kultural adalah <strong>pengetahuan, keterampilan dan sikap/perilaku yang dapat diamati, diukur, dan dikembangkan terkait pengalaman berinteraksi dengan masyarakat majemuk</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena definisi kompetensi ASN secara baku merujuk pada tiga ranah terpadu kognitif (pengetahuan), psikomotorik (keterampilan), dan afektif (sikap kerja) yang terukur secara objektif. Tiga komponen ini memastikan bahwa kapasitas sosial kultural dapat dilatih dan dievaluasi kinerjanya dalam dinas. Standar ini menjamin mutu pelayanan aparatur.</p><p>Jawaban tepat: <strong>A. Pengetahuan, keterampilan, dan sikap perilaku yang terukur</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Tingkat kekayaan dan gelar kehormatan bukan tolok ukur kompetensi profesionalitas aparatur.</li><li><strong>C</strong>: Kekuatan fisik dan senjata merupakan atribut kebugaran prajurit militer atau kepolisian.</li><li><strong>D</strong>: Popularitas media sosial tidak mencerminkan kedalaman pemahaman wawasan kebangsaan.</li><li><strong>E</strong>: Hubungan kekeluargaan merupakan praktik nepotisme yang merusak sistem merit kepegawaian.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 73) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kultural menurut Peraturan Menteri Pendayagunaan Aparatur Negara Reformasi dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Kompetensi sosial kultural ASN diuji dan dikembangkan melalui pengalaman nyata dalam berinteraksi dengan masyarakat majemuk dalam hal...", "options": ["Persaingan harga saham dan spekulasi kurs valuta asing", "Agama, suku, budaya, etika, moral, dan wawasan kebangsaan", "Teknik pembentukan opini massa propaganda media online", "Pengaturan kuota impor komoditas pangan pokok kementerian", "Penetapan tarif batas atas tiket penerbangan komersial"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Dimensi Interaksi Kompetensi Sosial Kultural</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kompetensi sosial kultural terasah melalui interaksi dengan masyarakat majemuk dalam hal <strong>agama, suku dan budaya, perilaku, wawasan kebangsaan, etika, nilai-nilai, moral, emosi dan prinsip</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kematangan aparatur tercermin dari kemampuannya menjalin komunikasi yang inklusif, empatik, dan bebas prasangka saat melayani warga dari berbagai latar belakang budaya. Penguasaan aspek etika dan moral kebangsaan menjaga wibawa pelayanan publik yang adil dan merata. Dimensi komprehensif ini menjadi kunci sukses integrasi sosial.</p><p>Jawaban tepat: <strong>B. Agama, suku, budaya, etika, moral, dan wawasan kebangsaan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Persaingan saham dan valas merupakan transaksi keuangan pasar modal dan valuta.</li><li><strong>C</strong>: Teknik propaganda media merupakan ranah kehumasan taktis komunikasi politik.</li><li><strong>D</strong>: Pengaturan kuota impor pangan merupakan kebijakan teknis kementerian perdagangan.</li><li><strong>E</strong>: Penetapan tarif penerbangan merupakan regulasi teknis kementerian perhubungan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 73) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengalaman berinteraksi dengan masyarakat majemuk dalam hal agama, suku dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Sebagai aparatur negara yang bertugas menciptakan kondisi damai, setiap pegawai ASN dituntut untuk senantiasa bersikap...", "options": ["Eksklusif dan membatasi komunikasi publik", "Mengutamakan keluarga pejabat dalam antrean", "Berpihak pada kelompok pendukung tertentu", "Netral dan adil kepada seluruh warga", "Pasrah mengikuti kehendak kelompok terkuat"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Peran ASN dalam Kondisi Damai</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Sebagai perpanjangan tangan pemerintah dalam menciptakan kondisi damai, posisi <strong>ASN sebagai aparatur negara harus bersikap netral dan adil</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena netralitas dan keadilan perlakuan merupakan syarat mutlak agar kehadiran aparatur negara diterima dengan penuh kepercayaan oleh seluruh komponen masyarakat. Sikap imparsial ini mendinginkan potensi friksi sosial dan menghadirkan rasa aman dalam pelayanan publik. Karakter adil ini mencerminkan asas umum pemerintahan yang baik.</p><p>Jawaban tepat: <strong>D. Netral dan adil kepada seluruh warga</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sikap eksklusif membatasi komunikasi merusak keterbukaan informasi dan memicu prasangka warga.</li><li><strong>B</strong>: Mengutamakan keluarga pejabat merupakan praktik nepotisme yang merusak sistem pelayanan.</li><li><strong>C</strong>: Berpihak pada kelompok pendukung bertentangan dengan asas netralitas yang diwajibkan oleh undang-undang aparatur.</li><li><strong>E</strong>: Pasrah pada kelompok terkuat menunjukkan lemahnya integritas penegakan aturan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 74) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menciptakan kondisi damai, misalnya posisi ASN sebagai aparatur negara, dia harus</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dalam melaksanakan tugas kedinasan sehari-hari, seorang pegawai aparatur sipil negara diwajibkan menjunjung prinsip...", "options": ["Tertutup dalam penetapan pungutan tarif liar", "Memperlambat berkas perizinan warga perdesaan", "Subjektif sesuai selera dan kedekatan pribadi", "Mengutamakan warga yang memberi cinderamata", "Objektif, jujur, transparan, dan anti-diskriminatif"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Prinsip Pelaksanaan Tugas Kedinasan ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>ASN dalam melaksanakan tugasnya <strong>tidak boleh berlaku diskriminatif dan harus obyektif, jujur, serta transparan</strong> guna mewujudkan kondisi damai.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena integritas dan transparansi dalam proses pelayanan menjamin keadilan bagi seluruh pemangku kepentingan tanpa memandang status sosial ekonomi. Perlakuan yang setara dan objektif membangun reputasi positif birokrasi pemerintahan di mata rakyat. Prinsip kerja ini mengokohkan kepercayaan publik.</p><p>Jawaban tepat: <strong>E. Objektif, jujur, transparan, dan anti-diskriminatif</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pungutan liar merupakan tindak pidana kejahatan jabatan yang merusak tatanan integritas birokrasi.</li><li><strong>B</strong>: Memperlambat berkas perizinan adalah maladministrasi yang menurunkan kepuasan publik.</li><li><strong>C</strong>: Sikap subjektif melahirkan diskriminasi yang merugikan hak konstitusional pemohon layanan.</li><li><strong>D</strong>: Menerima cinderamata dalam pelayanan merupakan gratifikasi yang dilarang undang-undang.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 74) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>diskriminatif dan harus obyektif, jujur, transparan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C3 - Aplikasi", "question": "Sikap yang wajib ditampilkan oleh pegawai aparatur sipil negara saat berlangsungnya agenda politik lima tahunan pemilu dan pilkada adalah...", "options": ["Menjaga netralitas dan tidak memihak kontestan mana pun", "Menjadi juru kampanye aktif pasangan calon tertentu", "Menggunakan fasilitas mobil dinas untuk deklarasi partai", "Mengunggah ajakan memilih kandidat di media sosial dinas", "Mengalokasikan dana hibah satker untuk tim pemenangan"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Netralitas ASN dalam Kontestasi Pemilu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai ASN wajib memegang teguh <strong>sikap netral dan adil dalam politik lima tahunan yaitu pemilu dan pilkada</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena netralitas politik ASN melindungi integritas pelayanan publik agar tidak dipolitisasi atau dimanfaatkan demi kepentingan pemenangan calon tertentu. Pegawai negara harus tetap fokus melayani seluruh rakyat secara profesional tanpa memandang afiliasi pilihan politik warga. Sikap independen ini menjaga stabilitas jalannya roda pemerintahan.</p><p>Jawaban tepat: <strong>A. Menjaga netralitas dan tidak memihak kontestan mana pun</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Menjadi juru kampanye aktif merupakan pelanggaran disiplin berat yang berakibat sanksi pemberhentian.</li><li><strong>C</strong>: Menggunakan mobil dinas untuk kegiatan politik praktis merupakan penyalahgunaan fasilitas aset negara.</li><li><strong>D</strong>: Mengunggah ajakan memilih calon di media sosial dinas bertentangan dengan larangan kampanye bagi ASN.</li><li><strong>E</strong>: Mengalirkan dana hibah untuk tim pemenangan merupakan tindak pidana kejahatan keuangan keuangan negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 74) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>ASN dalam politik lima tahunan yaitu pemilu dan pilkada; ASN juga harus bisa</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Peran ASN dalam mengayomi kepentingan kelompok minoritas diwujudkan melalui komitmen nyata untuk...", "options": ["Membatasi hak beribadah bagi komunitas kecil di daerah", "Mencegah regulasi yang mendiskriminasi keberadaan minoritas", "Menghalangi akses pendidikan bagi anak-anak kelompok rentan", "Mengharuskan asimilasi budaya paksa bagi suku minoritas", "Menyerahkan urusan keamanan minoritas pada ormas tertentu"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Perlindungan atas Kelompok Minoritas</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>ASN harus bisa <strong>mengayomi kepentingan kelompok-kelompok minoritas, dengan tidak membuat kebijakan atau peraturan yang mendiskriminasi keberadaan kelompok tersebut</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena perlindungan terhadap kelompok rentan dan minoritas merupakan bukti tegaknya supremasi hukum dan keadilan hak asasi manusia dalam negara demokrasi Pancasila. Birokrasi yang inklusif menjamin setiap kelompok masyarakat merasa terlindungi dan dihargai hak-hak kewarganegaraannya. Hal ini membendung benih segregasi sosial.</p><p>Jawaban tepat: <strong>B. Mencegah regulasi yang mendiskriminasi keberadaan minoritas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Membatasi hak ibadah komunitas minoritas bertentangan dengan jaminan kemerdekaan beragama Pasal 29 UUD 1945.</li><li><strong>C</strong>: Menghalangi akses pendidikan bertentangan dengan hak konstitusional mencerdaskan kehidupan seluruh bangsa.</li><li><strong>D</strong>: Asimilasi paksa merusak kelestarian keanekaragaman budaya lokal yang dilindungi negara.</li><li><strong>E</strong>: Menyerahkan pengamanan pada ormas menyimpang dari kewenangan monopoli keamanan negara oleh aparat.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 74) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>mengayomi kepentingan kelompok kelompok minoritas, dengan tidak membuat</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Di tengah pergaulan sosial masyarakat tempat tinggalnya, setiap pegawai ASN berkedudukan dan berperan sebagai...", "options": ["Pengamat statis tanpa kepedulian terhadap lingkungan", "Pengatur bisnis monopoli perdagangan perumahan", "Figur atau tokoh teladan dalam menjaga persatuan", "Tokoh penggerak perselisihan antarkelompok warga", "Pemberi izin kegiatan sewenang tanpa koordinasi"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Keteladanan Sosial Pegawai ASN</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Pegawai ASN mengemban tanggung jawab moral untuk <strong>menjadi figur/tokoh dan teladan di lingkungan masyarakatnya</strong> dalam merawat harmoni sosial.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena citra aparatur negara tercermin langsung dari sikap, tutur kata, dan perilaku sehari-hari pegawai di lingkungan tempat tinggalnya. Keteladanan dalam gotong royong, musyawarah, dan ketaatan hukum menginspirasi warga masyarakat untuk hidup rukun dan tertib. Pengaruh positif ini memperkuat kohesi sosial kemasyarakatan.</p><p>Jawaban tepat: <strong>C. Figur atau tokoh teladan dalam menjaga persatuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Sikap statis dan masa bodoh mencerminkan rendahnya kepedulian sosial seorang abdi masyarakat.</li><li><strong>B</strong>: Mengatur monopoli bisnis perumahan bertentangan dengan etika kepantasan dan persaingan usaha yang sehat.</li><li><strong>D</strong>: Memicu perselisihan bertentangan secara diametral dengan fungsi ASN sebagai perekat bangsa.</li><li><strong>E</strong>: Pemberian izin sewenang menyimpang dari tata tertib dan prosedur administrasi lingkungan warga.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 74) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menjadi figur/tokoh dan teladan di lingkungan masyarakatnya.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Regulasi internal Kementerian Keuangan yang mengatur standar kompetensi sosio kultural bagi seluruh pegawainya adalah...", "options": ["PMK Nomor 190/PMK.05/2012 tentang Pembayaran Tagihan", "KMK Nomor 543/KMK.01/2017 tentang Tata Naskah Dinas", "PMK Nomor 214/PMK.05/2013 tentang Bagan Akun Standar", "KMK Nomor 954/KMK.01/2019 tentang Kompetensi Sosio Kultural", "PMK Nomor 145/PMK.05/2017 tentang Tata Cara Pembayaran"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Regulasi Kompetensi Sosio Kultural Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kementerian Keuangan menetapkan <strong>KMK Nomor 954/KMK.01/2019 tentang Kompetensi Sosio Kultural di Lingkungan Kementerian Keuangan</strong> selaras dengan Permenpan-RB 38/2017.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena Keputusan Menteri Keuangan Nomor 954/KMK.01/2019 menjadi landasan hukum operasional dalam menyusun kamus kompetensi, mengukur profisiensi, dan membina kapasitas sosial kultural jajaran pegawai Kemenkeu. Regulasi ini menegaskan pentingnya wawasan kebangsaan dalam pengelolaan fiskal negara. Ketentuan ini memandu pembinaan SDM Kemenkeu.</p><p>Jawaban tepat: <strong>D. KMK Nomor 954/KMK.01/2019 tentang Kompetensi Sosio Kultural</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: PMK 190/PMK.05/2012 mengatur tata cara pembayaran dalam rangka pelaksanaan APBN.</li><li><strong>B</strong>: KMK 543/KMK.01/2017 mengatur pedoman tata naskah dinas di lingkungan Kemenkeu.</li><li><strong>C</strong>: PMK 214/PMK.05/2013 mengatur bagan akun standar dalam sistem akuntansi pemerintah.</li><li><strong>E</strong>: PMK 145/PMK.05/2017 mengatur pembayaran atas beban anggaran pendapatan dan belanja negara.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 74) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>menetapkan KMK Nomor 954/KMK.01/2019 tentang Kompetensi Sosio Kultural di</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dalam Kamus Kompetensi Sosio Kultural, kompetensi perekat dan pemersatu bangsa didefinisikan sebagai kemampuan dalam...", "options": ["Menetapkan tarif sanksi denda administrasi perpajakan", "Mengaudit laporan keuangan korporasi milik daerah", "Menghitung proyeksi pertumbuhan ekonomi makro regional", "Menyusun pembelaan hukum perkara perdata di pengadilan", "Mempromosikan toleransi dan peka perbedaan masyarakat"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Definisi Perekat dan Pemersatu Bangsa</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Kompetensi perekat bangsa didefinisikan sebagai <strong>kemampuan mempromosikan sikap toleransi, keterbukaan, peka terhadap perbedaan individu/kelompok masyarakat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kompetensi ini menitikberatkan pada keterampilan komunikasi antarbudaya dan kepedulian sosial dalam menyatukan berbagai elemen bangsa yang majemuk. Kemampuan membangun kelekatan sosial ini mencegah gesekan antarkelompok pemangku kepentingan. Hal ini memperkokoh persatuan nasional di ranah birokrasi.</p><p>Jawaban tepat: <strong>E. Mempromosikan toleransi dan peka perbedaan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menetapkan sanksi pajak merupakan ranah kompetensi teknis pemeriksa pajak.</li><li><strong>B</strong>: Mengaudit laporan keuangan BUMD merupakan tugas teknis auditor inspektorat atau BPKP.</li><li><strong>C</strong>: Menghitung proyeksi makroekonomi merupakan kompetensi teknis analis kebijakan fiskal.</li><li><strong>D</strong>: Menyusun pembelaan perkara merupakan keahlian advokat biro bantuan hukum.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 74) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>tersebut, perekat dan pemersatu bangsa didefinisikan sebagai kemampuan dalam</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Sebagai perpanjangan tangan pemerintah, aparatur dituntut memiliki kemampuan mempersatukan masyarakat dengan membangun...", "options": ["Hubungan sosial psikologis dan kelekatan yang kuat", "Sistem permodalan konglomerasi bisnis tertutup", "Pangkalan persenjataan militer di kantor pelayanan", "Jaringan informasi intelijen guna memata-matai rekan", "Aturan tarif retribusi khusus bagi pemohon luar kota"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Membangun Hubungan Sosial Psikologis</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>ASN menjadi perpanjangan tangan pemerintah dalam <strong>mempersatukan masyarakat dan membangun hubungan sosial psikologis dengan masyarakat sehingga menciptakan kelekatan yang kuat</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena ikatan batiniah dan kedekatan emosional antara aparatur dengan warga masyarakat menciptakan rasa saling percaya (*social trust*) yang kokoh. Kelekatan sosial psikologis ini mereduksi potensi kecurigaan publik dan memperlancar implementasi kebijakan negara di lapangan. Sinergi ini menjamin keberhasilan program pembangunan.</p><p>Jawaban tepat: <strong>A. Hubungan sosial psikologis dan kelekatan yang kuat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Sistem permodalan tertutup menciptakan oligarki ekonomi yang merugikan rakyat luas.</li><li><strong>C</strong>: Pangkalan senjata di kantor pelayanan menyimpang dari tata tertib fasilitas umum birokrasi sipil.</li><li><strong>D</strong>: Memata-matai rekan kerja merusak budaya saling percaya dan iklim kerja kolaboratif.</li><li><strong>E</strong>: Tarif khusus warga luar kota bertentangan dengan prinsip keadilan tarif layanan publik terpadu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 74) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>dalam mempersatukan masyarakat dan membangun hubungan sosial psikologis</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Di tengah keanekaragaman latar belakang sosial ekonomi masyarakat, pegawai Kementerian Keuangan dituntut untuk senantiasa...", "options": ["Memprioritaskan pelayanan pada pemilik modal besar saja", "Melayani seluruh lapisan masyarakat tanpa diskriminasi", "Menyederhanakan prosedur audit khusus bagi kerabat", "Membatasi pelayanan tatap muka pada jam kerja tertentu", "Mengenakan biaya tambahan untuk konsultasi perbendaharaan"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tuntutan Pelayanan Anti-Diskriminasi Kemenkeu</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>ASN khususnya pegawai Kementerian Keuangan <strong>dituntut untuk dapat melayani masyarakat tanpa diskriminasi</strong> di tengah berbagai perbedaan suku, agama, dan latar belakang.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena setiap warga negara memiliki hak yang setara dalam mendapatkan pelayanan perpajakan, kepabeanan, dan perbendaharaan secara adil, profesional, dan akuntabel. Pelayanan tanpa diskriminasi mencerminkan pengamalan nilai integritas dan pelayanan prima Kemenkeu. Sikap ini memperkokoh persatuan dan keadilan sosial.</p><p>Jawaban tepat: <strong>B. Melayani seluruh lapisan masyarakat tanpa diskriminasi</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Memprioritaskan pemodal besar bertentangan dengan asas kesetaraan pelayanan publik yang adil.</li><li><strong>C</strong>: Menyederhanakan audit bagi kerabat merupakan bentuk kolusi dan pelanggaran kode etik berat.</li><li><strong>D</strong>: Membatasi layanan sewenang mengesampingkan standar operasional prosedur janji layanan.</li><li><strong>E</strong>: Mengenakan biaya tambahan liar merupakan tindak pidana pemerasan dalam jabatan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 74) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Kementerian Keuangan dituntut untuk dapat melayani masyarakat tanpa diskriminasi.</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Rangkaian pelaksanaan uji kompetensi sosial kultural bagi pegawai aparatur sipil negara diselenggarakan dengan tujuan utama untuk...", "options": ["Menghitung besaran pemotongan tunjangan kinerja bulanan", "Menentukan giliran pengawasan piket kebersihan kantor", "Mengukur tingkat profisiensi dibandingkan standar jabatan", "Membatasi masa pengabdian aparatur di luar pulau Jawa", "Mengurangi alokasi belanja perjalanan dinas dalam negeri"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Tujuan Pelaksanaan Uji Kompetensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Uji kompetensi dilakukan <strong>dalam rangka mengukur tingkat profisiensi yang dimiliki oleh pegawai dibandingkan dengan standar kompetensi jabatan</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena penilaian kompetensi yang objektif memetakan kesesuaian kemahiran sosial kultural pegawai dengan profil tuntutan jabatan yang diembannya. Hasil pengukuran ini menjadi dasar pembinaan pengembangan kapasitas karier yang tepat sasaran. Evaluasi ini menjamin terpenuhinya standar profesionalitas jabatan.</p><p>Jawaban tepat: <strong>C. Mengukur tingkat profisiensi dibandingkan standar jabatan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pemotongan tunjangan kinerja didasarkan pada capaian kinerja individu dan presensi kehadiran dinas.</li><li><strong>B</strong>: Piket kebersihan merupakan tata kelola rumah tangga umum kantor satker.</li><li><strong>D</strong>: Membatasi penugasan luar Jawa bertentangan dengan prinsip persebaran merata aparatur NKRI.</li><li><strong>E</strong>: Alokasi perjalanan dinas diatur dalam standar biaya masukan pagu anggaran tahunan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 74) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pelaksanaan uji kompetensi. Rangkaian pelaksanaan uji kompetensi dilakukan dalam</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Berdasarkan regulasi manajemen ASN, tingkat kemahiran kompetensi sosial kultural dibagi ke dalam lima level profisiensi yang mencakup...", "options": ["Tingkat pemula, amatir, madya, utama, dan paripurna", "Level operasional, manajerial, taktis, teknis, dan strategis", "Level perintisan, penataan, evaluasi, transisi, dan mandiri", "Tingkat pengenalan, adaptasi, internalisasi, kreasi, dan inovasi", "Level paham, dasar, menengah, mumpuni, dan ahli"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Lima Tingkatan Level Profisiensi</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Level profisiensi kompetensi dibagi dalam lima tingkatan: <strong>Level 1 (paham/dalam pengembangan), Level 2 (dasar), Level 3 (menengah), Level 4 (mumpuni), dan Level 5 (ahli)</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena Permenpan-RB 38/2017 dan KMK 954/2019 secara resmi menetapkan nomenklatur lima tingkat kemahiran kompetensi dari Level 1 hingga Level 5. Tingkatan ini menunjukkan graduasi penguasaan pengetahuan dan kematangan perilaku pegawai dalam jabatan. Struktur baku ini menjadi tolok ukur uji kompetensi.</p><p>Jawaban tepat: <strong>E. Level paham, dasar, menengah, mumpuni, dan ahli</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Nomenklatur pemula, amatir, madya, utama bukan urutan resmi level profisiensi regulasi ASN.</li><li><strong>B</strong>: Operasional, manajerial, dan strategis merupakan pengelompokan tingkatan level manajemen kepemimpinan.</li><li><strong>C</strong>: Perintisan, penataan, dan evaluasi merupakan tahapan manajemen proyek pembangunan.</li><li><strong>D</strong>: Pengenalan, adaptasi, dan internalisasi merupakan proses psikologis pembelajaran sikap.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 75) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>yaitu: level 1 (paham/ dalam pengembangan), level 2 (dasar), level 3 (menengah),</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Standar kompetensi sosial kultural Level 1 (paham/dalam pengembangan) merupakan target yang wajib dicapai oleh pemangku jabatan...", "options": ["Pelaksana dan jabatan fungsional pemula", "Pejabat pimpinan tinggi madya eselon satu", "Jabatan administrator dan fungsional muda", "Pejabat pimpinan tinggi pratama eselon dua", "Jabatan pengawas dan fungsional pertama"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Standar Kompetensi Level 1</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Level 1 adalah standar kompetensi jabatan yang harus dicapai untuk pelaksana dan jabatan fungsional pemula</strong> dalam jenjang karier ASN.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pegawai pada jenjang awal pelaksanaan tugas dituntut memiliki pemahaman dasar dan kepekaan dalam menerima keberagaman lingkungan kerja dan masyarakat. Penguasaan Level 1 menjadi landasan untuk membangun kemampuan kerja sama lintas latar belakang. Standar ini menjadi prasyarat kematangan karier selanjutnya.</p><p>Jawaban tepat: <strong>A. Pelaksana dan jabatan fungsional pemula</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Pejabat eselon satu dan fungsional utama dipersyaratkan menguasai kompetensi Level 5 (ahli).</li><li><strong>C</strong>: Jabatan administrator dan fungsional muda dipersyaratkan menguasai kompetensi Level 3 (menengah).</li><li><strong>D</strong>: Pejabat eselon dua dan fungsional madya dipersyaratkan menguasai kompetensi Level 4 (mumpuni).</li><li><strong>E</strong>: Jabatan pengawas dan fungsional pertama dipersyaratkan menguasai kompetensi Level 2 (dasar).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 75) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Level 1, adalah standar kompetensi jabatan yang harus dicapai untuk pelaksana dan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Tingkat profisiensi Level 2 (dasar) dalam kompetensi perekat bangsa dipersyaratkan bagi aparatur yang memangku jabatan...", "options": ["Jabatan fungsional utama kementerian", "Jabatan pengawas dan jabatan fungsional pertama", "Pelaksana staf administrasi umum pemula", "Pejabat pimpinan tinggi madya eselon satu", "Jabatan administrator kepala bagian satker"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Standar Kompetensi Level 2</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Level 2 adalah standar kompetensi jabatan untuk jabatan pengawas dan jabatan fungsional pertama</strong> dalam tatanan birokrasi ASN.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena pejabat pengawas membimbing tim pelaksana operasional, sehingga dituntut mampu secara aktif mengembangkan sikap saling menghargai dan membangun hubungan baik dalam organisasi. Kematangan pada Level 2 menjamin koordinasi kerja tim tetap solid di tengah perbedaan latar belakang. Tingkat kemahiran dasar ini menjamin kelancaran supervisi tugas.</p><p>Jawaban tepat: <strong>B. Jabatan pengawas dan jabatan fungsional pertama</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Jabatan fungsional utama dituntut memenuhi standar Level 5 (ahli).</li><li><strong>C</strong>: Pelaksana staf pemula dipersyaratkan memenuhi standar Level 1 (paham).</li><li><strong>D</strong>: Pejabat eselon satu dituntut memenuhi standar Level 5 (ahli).</li><li><strong>E</strong>: Jabatan administrator dipersyaratkan memenuhi standar Level 3 (menengah).</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 75) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>pengawas dan jabatan fungsional pertama; Level 3 adalah standar kompetensi</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Standar kemahiran kompetensi sosial kultural Level 3 (menengah) merupakan kualifikasi minimal yang harus dipenuhi oleh pemangku...", "options": ["Jabatan pelaksana administrasi pemula", "Pejabat pimpinan tinggi utama eselon satu", "Jabatan administrator dan jabatan fungsional muda", "Tenaga honorer pembantu operasional dinas", "Jabatan fungsional keterampilan terampil"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Standar Kompetensi Level 3</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Level 3 adalah standar kompetensi jabatan untuk jabatan administrator dan jabatan fungsional muda</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena pemangku jabatan administrator memimpin unit kerja manajerial menengah yang berinteraksi luas dengan para pemangku kepentingan antardaerah. Penguasaan Level 3 memampukan pejabat mengelola keberagaman secara efektif serta menyelesaikan potensi konflik kepentingan secara bijaksana. Standar menengah ini mengokohkan efektivitas koordinasi.</p><p>Jawaban tepat: <strong>C. Jabatan administrator dan jabatan fungsional muda</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Pelaksana pemula dipersyaratkan menguasai kompetensi Level 1.</li><li><strong>B</strong>: Pejabat pimpinan utama eselon satu dipersyaratkan menguasai kompetensi Level 5.</li><li><strong>D</strong>: Tenaga pembantu operasional tidak termasuk dalam pemetaan standar jabatan ASN struktural.</li><li><strong>E</strong>: Fungsional keterampilan terampil berada pada jenjang dasar di bawah Level 3.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 75) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>jabatan untuk jabatan administrator dan jabatan fungsional muda; Level 4 adalah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Dalam peta kompetensi sosial kultural, pemangku jabatan Eselon II/fungsional madya dan Eselon I/fungsional utama secara berurutan dipersyaratkan mencapai...", "options": ["Level 1 (paham) dan Level 2 (dasar)", "Level 3 (menengah) dan Level 4 (mumpuni)", "Level 2 (dasar) dan Level 3 (menengah)", "Level 4 (mumpuni) dan Level 5 (ahli)", "Level 1 (paham) dan Level 5 (ahli)"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Standar Kompetensi Level 4 dan Level 5</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p><strong>Level 4 adalah standar jabatan untuk eselon II dan fungsional madya</strong>, sedangkan <strong>Level 5 adalah standar kompetensi jabatan untuk eselon I dan fungsional utama</strong>.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena level pimpinan tinggi kementerian memikul tanggung jawab strategis dalam merumuskan kebijakan publik yang berdampak luas bagi integrasi bangsa. Penguasaan Level 4 dan Level 5 mencerminkan kapasitas kepemimpinan nasional yang mampu menjadi role model kebangsaan dan pemersatu keberagaman masyarakat. Standar puncak ini menjaga marwah institusi negara.</p><p>Jawaban tepat: <strong>D. Level 4 (mumpuni) dan Level 5 (ahli)</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Level 1 dan Level 2 merupakan standar bagi pelaksana dan pengawas.</li><li><strong>B</strong>: Level 3 dan Level 4 merupakan standar bagi administrator dan eselon dua.</li><li><strong>C</strong>: Level 2 dan Level 3 merupakan standar bagi pengawas dan administrator.</li><li><strong>E</strong>: Level 1 dan Level 5 adalah lompatan jenjang yang tidak berurutan bagi eselon dua.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 75) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>standar jabatan untuk eselon II dan jabatan fungsional madya, dan Level 5 adalah</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Tingkat kemahiran kompetensi sosial kultural seorang pegawai aparatur sipil negara dinilai dan dibuktikan secara nyata melalui pengamatan terhadap...", "options": ["Besaran saldo simpanan tabungan pribadi di bank", "Jumlah kepemilikan piagam penghargaan seremonial", "Panjangnya masa tunggu daftar antrean kenaikan pangkat", "Frekuensi kehadiran dalam kegiatan perjamuan luar kantor", "Indikator sikap dan perilaku yang ditampilkan dalam tugas"], "answer": 4, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Fungsi Penilaian Indikator Perilaku</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Penilaian akan tingkat kemahiran kompetensi <strong>dapat dilihat dari indikator perilaku yang dimiliki dan ditampilkan oleh pegawai</strong> dalam tugas kedinasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi E tepat karena kompetensi aparatur bersifat teramati dan terukur (*observable and measurable*) melalui tindakan konkret dalam memperlakukan masyarakat dan rekan kerja majemuk. Bukti perilaku nyata menjadi dasar asesmen yang adil dan valid tanpa praduga subjektif. Indikator perilaku ini memastikan evaluasi berbasis kinerja empiris.</p><p>Jawaban tepat: <strong>E. Indikator sikap dan perilaku yang ditampilkan dalam tugas</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Saldo tabungan merupakan urusan keuangan privat yang tidak mencerminkan integritas perilaku.</li><li><strong>B</strong>: Piagam penghargaan seremonial bukan bukti empiris konsistensi perilaku kerja sehari-hari.</li><li><strong>C</strong>: Masa tunggu kenaikan pangkat merupakan urusan administrasi kepegawaian berkala.</li><li><strong>D</strong>: Kehadiran perjamuan seremonial tidak membuktikan kemahiran dalam merekatkan persatuan.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 75) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>kompetensi akan diketahui tingkat leveling dari pegawai, apakah sudah sesuai dengan</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Deskripsi utama kompetensi sosial kultural pada Level 1 (paham/dalam pengembangan) berfokus pada kemampuan pegawai untuk...", "options": ["Peka memahami dan menerima kemajemukan masyarakat", "Memimpin negosiasi perdamaian sengketa perbatasan", "Merumuskan naskah undang-undang otonomi kepulauan", "Mengadili pelanggaran tata tertib kepegawaian kementerian", "Menetapkan tarif batas perizinan komoditas tambang"], "answer": 0, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Deskripsi Kompetensi Level 1</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Deskripsi kompetensi Level 1 adalah <strong>peka memahami dan menerima kemajemukan</strong>, serta terbuka belajar tentang perbedaan individu/kelompok masyarakat.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi A tepat karena pada tingkat pemula, pegawai dituntut memiliki kesadaran dasar dan empati untuk menerima perbedaan latar belakang suku, agama, dan budaya di lingkungan sekitarnya tanpa berprasangka buruk. Keterbukaan batiniah ini menjadi modal awal membangun kerja sama tim yang solid. Kepekaan ini mendukung pelayanan yang humanis.</p><p>Jawaban tepat: <strong>A. Peka memahami dan menerima kemajemukan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>B</strong>: Memimpin negosiasi sengketa perbatasan merupakan kapasitas strategis Level 5 (ahli).</li><li><strong>C</strong>: Merumuskan undang-undang merupakan ranah perancang peraturan perundang-undangan utama.</li><li><strong>D</strong>: Mengadili pelanggaran disiplin adalah fungsi atasan berwenang dan majelis kehormatan kode etik.</li><li><strong>E</strong>: Menetapkan tarif perizinan merupakan kewenangan menteri terkait regulasi teknis.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 75) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Peka memahami</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Sikap pegawai aparatur yang bersedia mendengarkan dan mempelajari kearifan adat masyarakat setempat di tempat tugas baru mencerminkan indikator...", "options": ["Sikap primordialisme pegawai terhadap tradisi kuno", "Terbuka dan ingin belajar tentang kemajemukan masyarakat", "Ketidakmampuan menegakkan aturan baku kementerian", "Pelanggaran prinsip independensi aparatur sipil", "Formalitas birokrasi tanpa keikhlasan pengabdian"], "answer": 1, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Indikator Perilaku Keterbukaan Belajar</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Indikator perilaku 1.2 menegaskan sikap <strong>terbuka dan ingin belajar tentang perbedaan atau kemajemukan masyarakat</strong> di lingkungan penugasan.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi B tepat karena kemauan belajar memahami budaya lokal memudahkan adaptasi penugasan aparatur dan mencegah terjadinya gegar budaya maupun kesalahpahaman komunikasi dengan warga. Sikap rendah hati dan terbuka ini membangun jembatan empati sosial antara pemerintah dengan masyarakat setempat. Hal ini memperlancar pelaksanaan tugas kedinasan.</p><p>Jawaban tepat: <strong>B. Terbuka dan ingin belajar tentang kemajemukan masyarakat</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Primordialisme bersikap eksklusif memuja suku sendiri dan menutup diri dari suku lain.</li><li><strong>C</strong>: Mempelajari kearifan lokal justru mempermudah penegakan aturan secara kontekstual dan persuasif.</li><li><strong>D</strong>: Mempelajari budaya warga tidak bertentangan dengan independensi melainkan memperkaya wawasan pelayanan.</li><li><strong>E</strong>: Sikap ingin belajar merupakan wujud keikhlasan adaptasi aparatur yang autentik.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 75) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>masyarakat; Terbuka, ingin belajar tentang</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C3 - Aplikasi", "question": "Dalam penugasan kelompok kerja satker, pegawai yang mampu berkolaborasi secara produktif bersama rekan yang berbeda suku dan agama mewujudkan indikator...", "options": ["Keterikatan koalisi kepentingan materiil proyek", "Kelemahan prinsip kepribadian dalam mempertahankan adat", "Mampu bekerja bersama individu yang berbeda latar belakang", "Sikap kompromistis terhadap pelanggaran disiplin kerja", "Toleransi semu demi kelancaran laporan penilaian atasan"], "answer": 2, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Indikator Bekerja Sama Lintas Perbedaan</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Indikator perilaku 1.3 adalah <strong>mampu bekerja bersama dengan individu yang berbeda latar belakang dengannya</strong> secara profesional.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi C tepat karena kemampuan bersinergi tanpa memandang sekat perbedaan latar belakang menghasilkan tim kerja birokrasi yang solid, inklusif, dan berkinerja tinggi. Kolaborasi ini membuktikan bahwa persatuan aparatur dapat diwujudkan secara nyata dalam ruang kerja sehari-hari. Praktik ini merefleksikan nilai dasar wawasan kebangsaan.</p><p>Jawaban tepat: <strong>C. Mampu bekerja bersama individu yang berbeda latar belakang</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Kerja sama profesional berorientasi pada target output tugas negara, bukan kepentingan materiil pribadi.</li><li><strong>B</strong>: Bekerja sama lintas suku tidak menghilangkan identitas diri melainkan memperkaya pergaulan sosial.</li><li><strong>D</strong>: Kolaborasi kerja tidak berkompromi dengan pelanggaran disiplin dan integritas.</li><li><strong>E</strong>: Kerja sama tim yang produktif berakar pada keikhlasan profesionalitas, bukan kepura-puraan semu.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 75) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Mampu bekerja bersama dengan individu</strong></div></div>"}, {"category": "WK", "sub": "Wawasan Kebangsaan • Paket 07", "difficulty": "C2 - Pemahaman", "question": "Deskripsi kemahiran kompetensi sosial kultural pada Level 2 (dasar) menekankan peran aktif aparatur untuk...", "options": ["Menutup pergaulan kerja dengan mitra lembaga eksternal", "Membentuk kelompok paguyuban eksklusif di kantor satker", "Mempersoalkan perbedaan keyakinan keagamaan rekan kerja", "Mengembangkan saling menghargai dan menekankan persatuan", "Meluapkan emosi kemarahan secara terbuka saat rapat dinas"], "answer": 3, "source": "Wawasan Kebangsaan", "explanation": "<div class=\"learn-box lesson-card\"><div class=\"learn-title\">📚 Intisari Materi • Deskripsi Kompetensi Level 2</div><div class=\"lesson-section\"><div class=\"lesson-kicker\">01 · Konsep Inti Textbook</div><p>Deskripsi Level 2 adalah <strong>aktif mengembangkan sikap saling menghargai, menekankan persamaan dan persatuan</strong>, serta mampu mengendalikan emosi diri.</p></div><div class=\"lesson-section lesson-reason\"><div class=\"lesson-kicker\">02 · Alasan Kunci Jawaban (AREL)</div><p>Opsi D tepat karena pada Level 2 aparatur bukan sekadar menerima perbedaan, melainkan proaktif menginisiasi jalinan hubungan baik, menghargai nilai keberagaman, serta mengutamakan titik temu persatuan di atas perbedaan. Sikap ini memperkokoh kerukunan dan stabilitas korps aparatur negara. Komitmen ini mewujudkan peran ASN pemersatu bangsa.</p><p>Jawaban tepat: <strong>D. Mengembangkan saling menghargai dan menekankan persatuan</strong></p></div><div class=\"lesson-section\"><div class=\"lesson-kicker\">03 · Bedah Opsi Lainnya</div><ul style=\"margin:6px 0 0;padding-left:20px;line-height:1.6\"><li><strong>A</strong>: Menutup pergaulan eksternal menghambat sinergi kemitraan pelayanan publik antar-instansi.</li><li><strong>B</strong>: Membentuk paguyuban eksklusif memicu polarisasi primordial dalam organisasi kepegawaian.</li><li><strong>C</strong>: Mempersoalkan keyakinan rekan kerja mencederai toleransi dan memicu disharmoni lingkungan kerja.</li><li><strong>E</strong>: Meluapkan amarah bertentangan dengan indikator kemampuan mengendalikan emosi dan ketenangan kerja.</li></ul></div><div class=\"lesson-source\">📖 Referensi: Wawasan Kebangsaan (Hlm. 75) &nbsp;|&nbsp; 🔎 Kata Kunci Cek Buku: <strong>Aktif</strong></div></div>"}];
let current=0;const answers=new Array(questions.length).fill(null);const letters=["A","B","C","D","E"];let seconds=65*60;let submitted=false;


// Anotasi hanya dalam pembahasan. Satu set coretan untuk setiap nomor soal.
const ANNO_KEY='stangolden_wk_p07_review_annot_v1_user_<?= (int)$USER->id ?>';
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
 try{localStorage.setItem('stg_wk_p07_map_visible',show?'1':'0')}catch(e){}
}
try{if(localStorage.getItem('stg_wk_p07_map_visible')==='0')toggleQuestionMap(false)}catch(e){}
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

  if(modalTitle) modalTitle.textContent="🏆 Lihat Ranking Wawasan Kebangsaan Paket 07";
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